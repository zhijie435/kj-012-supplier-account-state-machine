<?php

namespace Tests\Feature;

use App\Enums\SupplierAccountStatus;
use App\Models\Supplier;
use App\Models\SupplierAccountStatusLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SupplierControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $viewerUser;

    protected User $editorUser;

    protected User $approverUser;

    protected User $noPermUser;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('supplier.view', 'web');
        Permission::findOrCreate('supplier.create', 'web');
        Permission::findOrCreate('supplier.edit', 'web');
        Permission::findOrCreate('supplier.delete', 'web');
        Permission::findOrCreate('supplier.approve', 'web');

        $adminRole = Role::findOrCreate('admin', 'web');
        $adminRole->givePermissionTo([
            'supplier.view',
            'supplier.create',
            'supplier.edit',
            'supplier.delete',
            'supplier.approve',
        ]);

        $viewerRole = Role::findOrCreate('viewer', 'web');
        $viewerRole->givePermissionTo('supplier.view');

        $editorRole = Role::findOrCreate('editor', 'web');
        $editorRole->givePermissionTo(['supplier.view', 'supplier.create', 'supplier.edit']);

        $approverRole = Role::findOrCreate('approver', 'web');
        $approverRole->givePermissionTo(['supplier.view', 'supplier.approve']);

        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');

        $this->viewerUser = User::factory()->create();
        $this->viewerUser->assignRole('viewer');

        $this->editorUser = User::factory()->create();
        $this->editorUser->assignRole('editor');

        $this->approverUser = User::factory()->create();
        $this->approverUser->assignRole('approver');

        $this->noPermUser = User::factory()->create();
    }

    protected function authAs(User $user): self
    {
        Sanctum::actingAs($user);

        return $this;
    }

    protected function validSupplierData(array $overrides = []): array
    {
        return array_merge([
            'name' => '测试供应商',
            'company_name' => '测试供应商有限公司',
            'business_license' => '1234567890',
            'contact_person' => '张三',
            'phone' => '13800138000',
            'email' => 'test@example.com',
            'address' => '测试地址',
            'country_code' => 'CN',
        ], $overrides);
    }

    public function test_unauthenticated_cannot_access_suppliers(): void
    {
        $response = $this->getJson('/api/suppliers');

        $response->assertUnauthorized();
    }

    public function test_index_with_permission(): void
    {
        Supplier::factory()->count(3)->create();

        $this->authAs($this->viewerUser);
        $response = $this->getJson('/api/suppliers');

        $response->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta'])
            ->assertJsonCount(3, 'data');
    }

    public function test_index_without_permission(): void
    {
        $this->authAs($this->noPermUser);
        $response = $this->getJson('/api/suppliers');

        $response->assertForbidden();
    }

    public function test_index_search(): void
    {
        Supplier::factory()->create(['name' => 'Alibaba Group']);
        Supplier::factory()->create(['name' => 'Tencent Tech']);
        Supplier::factory()->create(['company_name' => 'Baidu Online']);

        $this->authAs($this->viewerUser);
        $response = $this->getJson('/api/suppliers?search=Alibaba');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_index_filter_by_status(): void
    {
        Supplier::factory()->pending()->create();
        Supplier::factory()->active()->create();
        Supplier::factory()->active()->create();

        $this->authAs($this->viewerUser);
        $response = $this->getJson('/api/suppliers?status=active');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_index_filter_by_cross_border(): void
    {
        Supplier::factory()->crossBorder()->create();
        Supplier::factory()->create(['is_cross_border' => false]);

        $this->authAs($this->viewerUser);
        $response = $this->getJson('/api/suppliers?is_cross_border=true');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_index_pagination(): void
    {
        Supplier::factory()->count(25)->create();

        $this->authAs($this->viewerUser);
        $response = $this->getJson('/api/suppliers?per_page=10');

        $response->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 25);
    }

    public function test_store_with_permission(): void
    {
        $this->authAs($this->editorUser);
        $response = $this->postJson('/api/suppliers', $this->validSupplierData());

        $response->assertCreated()
            ->assertJsonStructure(['data' => ['id', 'name', 'status']])
            ->assertJsonPath('data.name', '测试供应商')
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('suppliers', [
            'name' => '测试供应商',
            'status' => 'pending',
        ]);
    }

    public function test_store_without_permission(): void
    {
        $this->authAs($this->viewerUser);
        $response = $this->postJson('/api/suppliers', $this->validSupplierData());

        $response->assertForbidden();
    }

    public function test_store_validation_fails(): void
    {
        $this->authAs($this->editorUser);
        $response = $this->postJson('/api/suppliers', [
            'name' => '',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonStructure(['details' => ['name', 'contact_person', 'phone']]);
    }

    public function test_show_with_permission(): void
    {
        $supplier = Supplier::factory()->create();

        $this->authAs($this->viewerUser);
        $response = $this->getJson("/api/suppliers/{$supplier->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $supplier->id)
            ->assertJsonStructure(['data' => ['status_logs']]);
    }

    public function test_show_without_permission(): void
    {
        $supplier = Supplier::factory()->create();

        $this->authAs($this->noPermUser);
        $response = $this->getJson("/api/suppliers/{$supplier->id}");

        $response->assertForbidden();
    }

    public function test_show_returns_404_for_missing(): void
    {
        $this->authAs($this->viewerUser);
        $response = $this->getJson('/api/suppliers/99999');

        $response->assertNotFound();
    }

    public function test_update_with_permission(): void
    {
        $supplier = Supplier::factory()->create(['name' => '旧名称']);

        $this->authAs($this->editorUser);
        $response = $this->putJson("/api/suppliers/{$supplier->id}", [
            'name' => '新名称',
            'contact_person' => '李四',
            'phone' => '13900139000',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', '新名称');

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => '新名称',
        ]);
    }

    public function test_update_without_permission(): void
    {
        $supplier = Supplier::factory()->create();

        $this->authAs($this->viewerUser);
        $response = $this->putJson("/api/suppliers/{$supplier->id}", [
            'name' => '新名称',
            'contact_person' => '李四',
            'phone' => '13900139000',
        ]);

        $response->assertForbidden();
    }

    public function test_destroy_with_permission(): void
    {
        $supplier = Supplier::factory()->create();

        $this->authAs($this->adminUser);
        $response = $this->deleteJson("/api/suppliers/{$supplier->id}");

        $response->assertOk()
            ->assertJson(['message' => '删除成功']);

        $this->assertSoftDeleted($supplier);
    }

    public function test_destroy_without_permission(): void
    {
        $supplier = Supplier::factory()->create();

        $this->authAs($this->editorUser);
        $response = $this->deleteJson("/api/suppliers/{$supplier->id}");

        $response->assertForbidden();
    }

    public function test_update_status_success(): void
    {
        $supplier = Supplier::factory()->pending()->create();

        $this->authAs($this->adminUser);
        $response = $this->putJson("/api/suppliers/{$supplier->id}/status", [
            'status' => 'verifying',
            'remark' => '开始审核',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'verifying');

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'status' => 'verifying',
        ]);
    }

    public function test_update_status_without_edit_permission(): void
    {
        $supplier = Supplier::factory()->pending()->create();

        $this->authAs($this->viewerUser);
        $response = $this->putJson("/api/suppliers/{$supplier->id}/status", [
            'status' => 'verifying',
        ]);

        $response->assertForbidden();
    }

    public function test_update_status_invalid_status(): void
    {
        $supplier = Supplier::factory()->pending()->create();

        $this->authAs($this->adminUser);
        $response = $this->putJson("/api/suppliers/{$supplier->id}/status", [
            'status' => 'active',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('error_code', 'STATE_TRANSITION_ERROR');
    }

    public function test_update_status_missing_remark(): void
    {
        $supplier = Supplier::factory()->pending()->create();

        $this->authAs($this->adminUser);
        $response = $this->putJson("/api/suppliers/{$supplier->id}/status", [
            'status' => 'rejected',
        ]);

        $response->assertUnprocessable();
    }

    public function test_verify_success(): void
    {
        $supplier = Supplier::factory()->pending()->create();

        $this->authAs($this->approverUser);
        $response = $this->putJson("/api/suppliers/{$supplier->id}/verify", [
            'remark' => '提交审核',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'verifying');

        $this->assertSame(SupplierAccountStatus::VERIFYING, $supplier->fresh()->status);
    }

    public function test_verify_without_approve_permission(): void
    {
        $supplier = Supplier::factory()->pending()->create();

        $this->authAs($this->editorUser);
        $response = $this->putJson("/api/suppliers/{$supplier->id}/verify");

        $response->assertForbidden();
    }

    public function test_verify_from_wrong_state(): void
    {
        $supplier = Supplier::factory()->active()->create();

        $this->authAs($this->approverUser);
        $response = $this->putJson("/api/suppliers/{$supplier->id}/verify");

        $response->assertUnprocessable();
    }

    public function test_activate_success(): void
    {
        $supplier = Supplier::factory()->verifying()->create();

        $this->authAs($this->approverUser);
        $response = $this->putJson("/api/suppliers/{$supplier->id}/activate");

        $response->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->assertSame(SupplierAccountStatus::ACTIVE, $supplier->fresh()->status);
        $this->assertNotNull($supplier->fresh()->activated_at);
    }

    public function test_activate_without_approve_permission(): void
    {
        $supplier = Supplier::factory()->verifying()->create();

        $this->authAs($this->editorUser);
        $response = $this->putJson("/api/suppliers/{$supplier->id}/activate");

        $response->assertForbidden();
    }

    public function test_activate_from_wrong_state(): void
    {
        $supplier = Supplier::factory()->pending()->create();

        $this->authAs($this->approverUser);
        $response = $this->putJson("/api/suppliers/{$supplier->id}/activate");

        $response->assertUnprocessable();
    }

    public function test_activate_without_license(): void
    {
        $supplier = Supplier::factory()->verifying()->withoutLicense()->create();

        $this->authAs($this->approverUser);
        $response = $this->putJson("/api/suppliers/{$supplier->id}/activate");

        $response->assertUnprocessable();
    }

    public function test_suspend_success(): void
    {
        $supplier = Supplier::factory()->active()->create();

        $this->authAs($this->approverUser);
        $response = $this->putJson("/api/suppliers/{$supplier->id}/suspend", [
            'remark' => '违规操作',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'suspended');

        $this->assertSame(SupplierAccountStatus::SUSPENDED, $supplier->fresh()->status);
    }

    public function test_suspend_without_approve_permission(): void
    {
        $supplier = Supplier::factory()->active()->create();

        $this->authAs($this->editorUser);
        $response = $this->putJson("/api/suppliers/{$supplier->id}/suspend");

        $response->assertForbidden();
    }

    public function test_suspend_from_wrong_state(): void
    {
        $supplier = Supplier::factory()->pending()->create();

        $this->authAs($this->approverUser);
        $response = $this->putJson("/api/suppliers/{$supplier->id}/suspend");

        $response->assertUnprocessable();
    }

    public function test_reject_success(): void
    {
        $supplier = Supplier::factory()->verifying()->create();

        $this->authAs($this->approverUser);
        $response = $this->putJson("/api/suppliers/{$supplier->id}/reject", [
            'remark' => '资料不齐全',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'rejected');

        $this->assertSame(SupplierAccountStatus::REJECTED, $supplier->fresh()->status);
    }

    public function test_reject_requires_remark(): void
    {
        $supplier = Supplier::factory()->verifying()->create();

        $this->authAs($this->approverUser);
        $response = $this->putJson("/api/suppliers/{$supplier->id}/reject");

        $response->assertUnprocessable()
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonStructure(['details' => ['remark']]);
    }

    public function test_reject_without_approve_permission(): void
    {
        $supplier = Supplier::factory()->verifying()->create();

        $this->authAs($this->editorUser);
        $response = $this->putJson("/api/suppliers/{$supplier->id}/reject", [
            'remark' => '资料不齐全',
        ]);

        $response->assertForbidden();
    }

    public function test_cancel_success(): void
    {
        $supplier = Supplier::factory()->active()->create();

        $this->authAs($this->approverUser);
        $response = $this->putJson("/api/suppliers/{$supplier->id}/cancel", [
            'remark' => '供应商主动注销',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertSame(SupplierAccountStatus::CANCELLED, $supplier->fresh()->status);
    }

    public function test_cancel_with_orders_fails(): void
    {
        $supplier = Supplier::factory()->active()->create();
        \App\Models\Order::factory()->create(['supplier_id' => $supplier->id]);

        $this->authAs($this->approverUser);
        $response = $this->putJson("/api/suppliers/{$supplier->id}/cancel");

        $response->assertUnprocessable()
            ->assertJsonPath('error_code', 'STATE_TRANSITION_ERROR');
    }

    public function test_cancel_without_approve_permission(): void
    {
        $supplier = Supplier::factory()->active()->create();

        $this->authAs($this->editorUser);
        $response = $this->putJson("/api/suppliers/{$supplier->id}/cancel");

        $response->assertForbidden();
    }

    public function test_allowed_transitions_pending(): void
    {
        $supplier = Supplier::factory()->pending()->create();

        $this->authAs($this->adminUser);
        $response = $this->getJson("/api/suppliers/{$supplier->id}/allowed-transitions");

        $response->assertOk()
            ->assertJsonStructure(['data'])
            ->assertJsonCount(3, 'data');

        $values = collect($response->json('data'))->pluck('value')->toArray();
        $this->assertContains('verifying', $values);
        $this->assertContains('rejected', $values);
        $this->assertContains('cancelled', $values);
    }

    public function test_allowed_transitions_rejected_terminal(): void
    {
        $supplier = Supplier::factory()->rejected()->create();

        $this->authAs($this->adminUser);
        $response = $this->getJson("/api/suppliers/{$supplier->id}/allowed-transitions");

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_allowed_transitions_includes_requires_remark(): void
    {
        $supplier = Supplier::factory()->pending()->create();

        $this->authAs($this->adminUser);
        $response = $this->getJson("/api/suppliers/{$supplier->id}/allowed-transitions");

        $data = collect($response->json('data'));
        $rejected = $data->firstWhere('value', 'rejected');
        $verifying = $data->firstWhere('value', 'verifying');

        $this->assertTrue($rejected['requires_remark']);
        $this->assertFalse($verifying['requires_remark']);
    }

    public function test_validate_transition_valid(): void
    {
        $supplier = Supplier::factory()->pending()->create();

        $this->authAs($this->adminUser);
        $response = $this->postJson("/api/suppliers/{$supplier->id}/validate-transition", [
            'status' => 'verifying',
        ]);

        $response->assertOk()
            ->assertJson([
                'valid' => true,
            ]);
    }

    public function test_validate_transition_invalid(): void
    {
        $supplier = Supplier::factory()->pending()->create();

        $this->authAs($this->adminUser);
        $response = $this->postJson("/api/suppliers/{$supplier->id}/validate-transition", [
            'status' => 'active',
        ]);

        $response->assertOk()
            ->assertJson([
                'valid' => false,
            ])
            ->assertJsonStructure(['errors']);
    }

    public function test_validate_transition_terminal_state(): void
    {
        $supplier = Supplier::factory()->rejected()->create();

        $this->authAs($this->adminUser);
        $response = $this->postJson("/api/suppliers/{$supplier->id}/validate-transition", [
            'status' => 'active',
        ]);

        $response->assertOk()
            ->assertJson([
                'valid' => false,
            ])
            ->assertJsonPath('errors.terminal_state', true);
    }

    public function test_status_logs_index(): void
    {
        $supplier = Supplier::factory()->pending()->create();
        SupplierAccountStatusLog::factory()->count(3)->create([
            'supplier_id' => $supplier->id,
        ]);

        $this->authAs($this->viewerUser);
        $response = $this->getJson("/api/suppliers/{$supplier->id}/status-logs");

        $response->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta'])
            ->assertJsonCount(4, 'data');
    }

    public function test_status_logs_without_permission(): void
    {
        $supplier = Supplier::factory()->create();

        $this->authAs($this->noPermUser);
        $response = $this->getJson("/api/suppliers/{$supplier->id}/status-logs");

        $response->assertForbidden();
    }

    public function test_status_logs_structure(): void
    {
        $operator = User::factory()->create();
        $supplier = Supplier::factory()->pending()->create();
        SupplierAccountStatusLog::create([
            'supplier_id' => $supplier->id,
            'from_status' => SupplierAccountStatus::PENDING,
            'to_status' => SupplierAccountStatus::VERIFYING,
            'remark' => '开始审核',
            'operated_by' => $operator->id,
        ]);

        $this->authAs($this->viewerUser);
        $response = $this->getJson("/api/suppliers/{$supplier->id}/status-logs");

        $response->assertOk()
            ->assertJsonStructure(['data' => [0 => [
                'id',
                'supplier_id',
                'from_status',
                'from_status_label',
                'to_status',
                'to_status_label',
                'remark',
                'operated_by',
                'created_at',
            ]]]);
    }

    public function test_show_includes_allowed_transitions_with_permission(): void
    {
        $supplier = Supplier::factory()->pending()->create();

        $this->authAs($this->editorUser);
        $response = $this->getJson("/api/suppliers/{$supplier->id}");

        $response->assertOk()
            ->assertJsonStructure(['data' => ['allowed_transitions']]);
    }

    public function test_show_excludes_allowed_transitions_without_edit_permission(): void
    {
        $supplier = Supplier::factory()->pending()->create();

        $this->authAs($this->viewerUser);
        $response = $this->getJson("/api/suppliers/{$supplier->id}");

        $data = $response->json('data');
        $this->assertArrayNotHasKey('allowed_transitions', $data);
    }

    public function test_supplier_resource_includes_status_label_and_color(): void
    {
        $supplier = Supplier::factory()->pending()->create();

        $this->authAs($this->viewerUser);
        $response = $this->getJson("/api/suppliers/{$supplier->id}");

        $response->assertOk()
            ->assertJsonPath('data.status_label', '待审核')
            ->assertJsonPath('data.status_color', 'warning');
    }
}
