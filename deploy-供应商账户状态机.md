# 供应商账户状态机 — 部署文档

## 1. 状态流转总览

```
pending ──→ verifying ──→ active ──→ suspended ──→ cancelled
  │            │             │
  │            ├──→ pending  ├──→ cancelled
  │            │             │
  ├──→ rejected(终态)        └──→ suspended
  │            │
  ├──→ cancelled             suspended ──→ active
  │
  └──→ verifying
```

| 状态 | 值 | 标签 | 终态 | 时间戳字段 |
|------|------|------|------|------|
| PENDING | `pending` | 待审核 | ✗ | — |
| VERIFYING | `verifying` | 审核中 | ✗ | `verifying_at` |
| ACTIVE | `active` | 已激活 | ✗ | `activated_at` |
| SUSPENDED | `suspended` | 已暂停 | ✗ | `suspended_at` |
| REJECTED | `rejected` | 已拒绝 | ✓ | `rejected_at` |
| CANCELLED | `cancelled` | 已注销 | ✓ | `cancelled_at` |

### 允许的状态转换与权限要求

| 从 → 到 | 所需权限 | 是否必填备注 |
|---------|---------|------------|
| pending → verifying | `supplier.approve` | 否 |
| pending → rejected | `supplier.approve` | **是** |
| pending → cancelled | `supplier.approve` | 否 |
| verifying → active | `supplier.approve` | 否 |
| verifying → rejected | `supplier.approve` | **是** |
| verifying → suspended | `supplier.approve` | 否 |
| verifying → pending | `supplier.approve` | 否 |
| active → suspended | `supplier.approve` | 否 |
| active → cancelled | `supplier.approve` | 否 |
| suspended → active | `supplier.approve` | 否 |
| suspended → cancelled | `supplier.approve` | 否 |

### 业务规则约束

- **激活 (→ active)**：供应商必须有 `business_license`、`contact_person` 和 `phone`，否则拒绝转换
- **注销 (→ cancelled)**：供应商不能有关联订单 (`orders`)，否则拒绝转换

---

## 2. 环境变量

在 `.env` 中确认以下变量，生产环境按需修改：

```env
# ── 应用基础 ──
APP_NAME=内容审核标注平台
APP_ENV=production          # 生产设为 production
APP_DEBUG=false             # 生产务必关闭
APP_KEY=                    # php artisan key:generate 生成
APP_URL=https://your-domain.com
APP_TIMEZONE=UTC
APP_LOCALE=zh_CN
APP_FALLBACK_LOCALE=en

# ── 数据库 ──
DB_CONNECTION=mysql         # 生产推荐 mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=audit_platform
DB_USERNAME=app_user
DB_PASSWORD=STRONG_PASSWORD

# ── 队列 ──
QUEUE_CONNECTION=database   # 生产推荐 redis
# 若用 redis 队列：
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_QUEUE=default

# 若用 database 队列：
DB_QUEUE_CONNECTION=
DB_QUEUE_TABLE=jobs
DB_QUEUE=default
DB_QUEUE_RETRY_AFTER=90

# ── 失败任务 ──
QUEUE_FAILED_DRIVER=database-uuids

# ── 会话 ──
SESSION_DRIVER=redis        # 生产推荐 redis
SESSION_LIFETIME=120

# ── 缓存 ──
CACHE_STORE=redis           # 生产推荐 redis
CACHE_PREFIX=audit_platform_

# ── 日志 ──
LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=warning           # 生产用 warning 及以上
```

---

## 3. 数据库迁移

### 3.1 涉及的迁移文件

| 迁移文件 | 作用 |
|---------|------|
| `2024_01_01_000001_create_suppliers_table.php` | 创建 `suppliers` 表，含 `status` enum、5 个状态时间戳字段、`operated_by` 外键 |
| `2024_01_01_000002_create_supplier_account_status_logs_table.php` | 创建 `supplier_account_status_logs` 日志表 |

### 3.2 执行迁移

```bash
php artisan migrate --force
```

### 3.3 回滚（仅调试用）

```bash
php artisan migrate:rollback --step=2 --force
```

---

## 4. 种子数据

当前项目无 `database/seeders` 目录，首次部署需创建权限与角色种子。

### 4.1 创建 Seeder

```bash
php artisan make:seeder SupplierPermissionSeeder
```

### 4.2 种子内容

`database/seeders/SupplierPermissionSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SupplierPermissionSeeder extends \Illuminate\Database\Seeder
{
    public function run(): void
    {
        $permissions = [
            'supplier.view',
            'supplier.create',
            'supplier.edit',
            'supplier.delete',
            'supplier.approve',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $viewer = Role::firstOrCreate(['name' => 'supplier-viewer', 'guard_name' => 'web']);
        $viewer->syncPermissions(['supplier.view']);

        $editor = Role::firstOrCreate(['name' => 'supplier-editor', 'guard_name' => 'web']);
        $editor->syncPermissions(['supplier.view', 'supplier.create', 'supplier.edit']);

        $approver = Role::firstOrCreate(['name' => 'supplier-approver', 'guard_name' => 'web']);
        $approver->syncPermissions(['supplier.view', 'supplier.approve']);

        $admin = Role::firstOrCreate(['name' => 'supplier-admin', 'guard_name' => 'web']);
        $admin->syncPermissions($permissions);
    }
}
```

### 4.3 执行种子

```bash
php artisan db:seed --class=SupplierPermissionSeeder --force
```

### 4.4 给已有用户赋权

```bash
php artisan tinker
```

```php
$user = \App\Models\User::find(1);
$user->assignRole('supplier-admin');
```

---

## 5. 队列任务

### 5.1 当前队列配置

项目使用 `QUEUE_CONNECTION=database`，队列任务存储在 `jobs` 表。

状态机本身 **同步执行**（`DB::transaction` 内完成状态更新 + 日志写入），暂无异步 Job。但以下场景后续可能引入队列任务：

| 预期 Job | 队列名 | 说明 |
|----------|-------|------|
| `SupplierStatusChangedNotification` | `default` | 状态变更后发送通知给供应商 |
| `SupplierActivationCheck` | `default` | 定期扫描活跃供应商资质是否过期 |
| `SupplierSuspendAutoCheck` | `default` | 违规自动暂停检测 |

### 5.2 启动队列 Worker

```bash
# 生产推荐 Supervisor 守护
php artisan queue:work database --queue=default --sleep=3 --tries=3 --max-time=3600

# 或使用 queue:daemon（性能更优）
php artisan queue:work database --daemon
```

### 5.3 Supervisor 配置示例

`/etc/supervisor/conf.d/audit-platform-worker.conf`:

```ini
[program:audit-platform-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/backend/artisan queue:work database --queue=default --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start audit-platform-worker:*
```

### 5.4 处理失败任务

```bash
# 查看失败任务
php artisan queue:failed

# 重试单个
php artisan queue:retry <id>

# 重试全部
php artisan queue:retry all

# 清除失败任务
php artisan queue:flush
```

---

## 6. 计划任务

当前 `routes/console.php` 注册了 3 个提现相关定时任务，状态机暂无专属计划任务。部署时确保 Cron 已配置：

```bash
* * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
```

---

## 7. API 端点

| Method | URI | 权限 | 说明 |
|--------|-----|------|------|
| GET | `/api/suppliers` | `supplier.view` | 供应商列表（支持 `status`、`is_cross_border` 筛选） |
| POST | `/api/suppliers` | `supplier.create` | 创建供应商 |
| GET | `/api/suppliers/{id}` | `supplier.view` | 供应商详情（含最近 10 条状态日志） |
| PUT/PATCH | `/api/suppliers/{id}` | `supplier.edit` | 更新供应商信息 |
| DELETE | `/api/suppliers/{id}` | `supplier.delete` | 删除供应商（软删除） |
| PUT | `/api/suppliers/{id}/status` | `supplier.edit` | 通用状态变更 |
| GET | `/api/suppliers/{id}/status-logs` | `supplier.view` | 状态变更日志 |
| GET | `/api/suppliers/{id}/allowed-transitions` | `supplier.view` | 当前允许的目标状态 |
| POST | `/api/suppliers/{id}/validate-transition` | `supplier.view` | 预校验状态变更是否可行 |
| PUT | `/api/suppliers/{id}/verify` | `supplier.approve` | 提交审核 |
| PUT | `/api/suppliers/{id}/activate` | `supplier.approve` | 激活 |
| PUT | `/api/suppliers/{id}/suspend` | `supplier.approve` | 暂停 |
| PUT | `/api/suppliers/{id}/reject` | `supplier.approve` | 拒绝（**备注必填**） |
| PUT | `/api/suppliers/{id}/cancel` | `supplier.approve` | 注销 |

---

## 8. 部署步骤

```bash
# 1. 拉取代码
git pull origin main

# 2. 安装依赖
composer install --no-dev --optimize-autoloader

# 3. 更新环境变量（确认 .env 内容，见第 2 节）
#    特别注意：APP_ENV=production, APP_DEBUG=false

# 4. 生成应用密钥（首次部署）
php artisan key:generate

# 5. 执行迁移
php artisan migrate --force

# 6. 执行种子（首次部署或权限变更时）
php artisan db:seed --class=SupplierPermissionSeeder --force

# 7. 清理并缓存配置
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 8. 重启队列 Worker
php artisan queue:restart

# 9. 确认队列 Worker 已重启
php artisan queue:status
```

---

## 9. 验收命令

### 9.1 基础环境检查

```bash
# PHP 版本（需 ≥ 8.3）
php -v

# 扩展检查
php -m | grep -E 'pdo_sqlite|pdo_mysql|redis|mbstring|openssl'

# Composer 依赖完整性
composer check-platform-reqs
```

### 9.2 应用健康检查

```bash
# 配置缓存是否生效
php artisan config:cache && php artisan config:show app.env

# 路由是否注册
php artisan route:list --path=suppliers

# 数据库连接
php artisan db:show

# 队列连接测试
php artisan queue:monitor database:default
```

### 9.3 迁移验证

```bash
# 检查迁移状态
php artisan migrate:status

# 确认 suppliers 表存在且结构正确
php artisan db:table suppliers

# 确认 supplier_account_status_logs 表存在
php artisan db:table supplier_account_status_logs
```

### 9.4 权限与角色验证

```bash
php artisan tinker --execute="
    \$perms = Spatie\Permission\Models\Permission::where('name', 'like', 'supplier.%')->pluck('name');
    echo 'Permissions: ' . \$perms->join(', ') . PHP_EOL;
    \$roles = Spatie\Permission\Models\Role::where('name', 'like', 'supplier-%')->pluck('name');
    echo 'Roles: ' . \$roles->join(', ') . PHP_EOL;
"
```

### 9.5 状态机功能验收（Tinker 脚本）

```bash
php artisan tinker --execute="
    \$user = \App\Models\User::first();
    if (!\$user) { echo 'No user found, create one first'; exit(1); }
    if (!\$user->hasRole('supplier-admin')) {
        \$user->assignRole('supplier-admin');
    }

    // 创建供应商
    \$s = \App\Models\Supplier::factory()->pending()->create();
    echo 'Created supplier #' . \$s->id . ' status=' . \$s->status->value . PHP_EOL;

    // pending → verifying
    \$s->transitionTo(\App\Enums\SupplierAccountStatus::VERIFYING, ['operated_by' => \$user->id]);
    echo 'After verify: ' . \$s->fresh()->status->value . PHP_EOL;

    // verifying → active
    \$s->transitionTo(\App\Enums\SupplierAccountStatus::ACTIVE, ['operated_by' => \$user->id]);
    echo 'After activate: ' . \$s->fresh()->status->value . PHP_EOL;

    // active → suspended
    \$s->transitionTo(\App\Enums\SupplierAccountStatus::SUSPENDED, ['operated_by' => \$user->id, 'remark' => '测试暂停']);
    echo 'After suspend: ' . \$s->fresh()->status->value . PHP_EOL;

    // suspended → active
    \$s->transitionTo(\App\Enums\SupplierAccountStatus::ACTIVE, ['operated_by' => \$user->id, 'remark' => '恢复']);
    echo 'After reactivate: ' . \$s->fresh()->status->value . PHP_EOL;

    // active → cancelled（无关联订单）
    \$s->transitionTo(\App\Enums\SupplierAccountStatus::CANCELLED, ['operated_by' => \$user->id]);
    echo 'After cancel: ' . \$s->fresh()->status->value . PHP_EOL;

    // 终态无法再变更
    try {
        \$s->transitionTo(\App\Enums\SupplierAccountStatus::ACTIVE, ['operated_by' => \$user->id]);
    } catch (\App\Exceptions\StateTransitionException \$e) {
        echo 'Terminal state blocked: ' . \$e->getMessage() . PHP_EOL;
    }

    echo 'All transitions OK';
"
```

### 9.6 单元测试验收

```bash
# 运行全部测试
composer test

# 仅运行状态机相关测试
php artisan test --filter=SupplierAccountStateMachine
php artisan test --filter=HasStateMachine
php artisan test --filter=SupplierAccountStatus
php artisan test --filter=StateTransitionException
php artisan test --filter=SupplierController

# 详细输出
php artisan test --filter=SupplierAccountStateMachine --testdox
```

### 9.7 API 端点冒烟测试

```bash
# 登录获取 token
TOKEN=$(curl -s -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}' \
  | jq -r '.token')

# 供应商列表
curl -s http://localhost/api/suppliers \
  -H "Authorization: Bearer $TOKEN" | jq '.data | length'

# 创建供应商
SUPPLIER_ID=$(curl -s -X POST http://localhost/api/suppliers \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"测试供应商","contact_person":"张三","phone":"13800138000"}' \
  | jq '.data.id')

# 查看允许的目标状态
curl -s http://localhost/api/suppliers/$SUPPLIER_ID/allowed-transitions \
  -H "Authorization: Bearer $TOKEN" | jq '.data'

# 提交审核
curl -s -X PUT http://localhost/api/suppliers/$SUPPLIER_ID/verify \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" | jq '.data.status'

# 激活
curl -s -X PUT http://localhost/api/suppliers/$SUPPLIER_ID/activate \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" | jq '.data.status'

# 查看状态日志
curl -s http://localhost/api/suppliers/$SUPPLIER_ID/status-logs \
  -H "Authorization: Bearer $TOKEN" | jq '.data | length'
```

---

## 10. 回滚方案

```bash
# 1. 切回上一版本代码
git checkout <previous-tag>

# 2. 安装依赖
composer install --no-dev --optimize-autoloader

# 3. 回滚迁移（如需要）
php artisan migrate:rollback --step=2 --force

# 4. 清理缓存
php artisan config:cache
php artisan route:cache

# 5. 重启队列
php artisan queue:restart
```

---

## 11. 核心文件清单

| 文件 | 作用 |
|------|------|
| `app/Enums/SupplierAccountStatus.php` | 状态枚举，定义转换映射、权限、备注要求 |
| `app/Services/StateMachine/SupplierAccountStateMachine.php` | 状态机核心逻辑 |
| `app/Contracts/StateMachine/StateMachineInterface.php` | 状态机接口契约 |
| `app/Contracts/StateMachine/TransitionResult.php` | 转换校验结果 DTO |
| `app/Exceptions/StateTransitionException.php` | 状态转换异常 |
| `app/Models/Supplier.php` | 供应商模型（含 HasStateMachine trait） |
| `app/Models/SupplierAccountStatusLog.php` | 状态变更日志模型 |
| `app/Models/Concerns/HasStateMachine.php` | 状态机 Trait，挂载到 Model |
| `app/Observers/SupplierObserver.php` | 创建时自动记录初始状态日志 |
| `app/Http/Controllers/SupplierController.php` | 供应商 API 控制器 |
| `app/Http/Requests/SupplierRequest.php` | 供应商表单校验 |
| `app/Http/Resources/SupplierResource.php` | 供应商 JSON 资源 |
| `app/Http/Resources/SupplierAccountStatusLogResource.php` | 状态日志 JSON 资源 |
| `app/Providers/AppServiceProvider.php` | 注册 SupplierObserver |
| `database/migrations/2024_01_01_000001_create_suppliers_table.php` | 供应商表迁移 |
| `database/migrations/2024_01_01_000002_create_supplier_account_status_logs_table.php` | 状态日志表迁移 |
| `database/factories/SupplierFactory.php` | 供应商工厂（含各状态 state） |
| `routes/api.php` | API 路由定义 |
