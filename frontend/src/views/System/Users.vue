<template>
  <div class="users-page">
    <div class="page-header">
      <div class="header-left">
        <h2 class="page-title">用户管理</h2>
        <p class="page-desc">管理系统用户和角色分配</p>
      </div>
      <div class="header-right">
        <el-button type="primary" @click="handleAdd" v-permission="'user.create'">
          <i class="el-icon-plus"></i> 新建用户
        </el-button>
      </div>
    </div>

    <el-card class="filter-card">
      <el-form :model="queryParams" inline>
        <el-form-item label="关键词">
          <el-input
            v-model="queryParams.keyword"
            placeholder="姓名/邮箱"
            clearable
            style="width: 200px"
            @keyup.enter.native="handleSearch"
          />
        </el-form-item>
        <el-form-item label="所属端">
          <el-select v-model="queryParams.guard_name" placeholder="全部" clearable style="width: 140px">
            <el-option label="平台端" value="platform" />
            <el-option label="供应商端" value="supplier" />
            <el-option label="经销商端" value="distributor" />
          </el-select>
        </el-form-item>
        <el-form-item label="角色">
          <el-select v-model="queryParams.role" placeholder="全部" clearable style="width: 140px">
            <el-option v-for="role in roleOptions" :key="role.name" :label="role.display_name || role.name" :value="role.name" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" size="small" @click="handleSearch"><i class="el-icon-search"></i> 搜索</el-button>
          <el-button size="small" @click="handleReset"><i class="el-icon-refresh"></i> 重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card class="table-card">
      <el-table :data="tableData" v-loading="loading" border stripe>
        <el-table-column type="index" label="序号" width="60" align="center" />
        <el-table-column prop="name" label="姓名" width="120" />
        <el-table-column prop="email" label="邮箱" min-width="180" />
        <el-table-column label="所属端" width="120" align="center">
          <template slot-scope="scope">
            <el-tag :type="guardTagTypes[scope.row.guard_name]" size="mini">
              {{ guardLabels[scope.row.guard_name] || scope.row.guard_name || '-' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="角色" width="140" align="center">
          <template slot-scope="scope">
            <el-tag v-for="role in (scope.row.roles || []).slice(0, 2)" :key="role.id" size="mini" style="margin: 2px">
              {{ role.display_name || role.name }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="80" align="center">
          <template slot-scope="scope">
            <el-switch v-model="scope.row.is_active" :disabled="rowToggling[scope.row.id]" @change="handleToggleActive(scope.row)" />
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="160" />
        <el-table-column label="操作" width="180" align="center" fixed="right">
          <template slot-scope="scope">
            <el-button type="primary" link size="mini" @click="handleEdit(scope.row)" v-permission="'user.update'">编辑</el-button>
            <el-button type="danger" link size="mini" @click="handleDelete(scope.row)" v-permission="'user.delete'">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination">
        <el-pagination
          v-model:current-page="queryParams.page"
          v-model:page-size="queryParams.per_page"
          :page-sizes="[10, 15, 20, 50]"
          :total="total"
          layout="total, sizes, prev, pager, next, jumper"
          background
          @size-change="fetchData"
          @current-change="fetchData"
        />
      </div>
    </el-card>

    <el-dialog
      :visible.sync="dialogVisible"
      :title="isEdit ? '编辑用户' : '新建用户'"
      width="640px"
      append-to-body
      :close-on-click-modal="false"
      :before-close="handleDialogClose"
    >
      <el-form :model="formData" :rules="formRules" ref="formRef" label-width="100px">
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="姓名" prop="name">
              <el-input v-model="formData.name" placeholder="请输入姓名" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="邮箱" prop="email">
              <el-input v-model="formData.email" placeholder="请输入邮箱" :disabled="isEdit" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="密码" prop="password">
              <el-input v-model="formData.password" type="password" :placeholder="isEdit ? '不修改请留空' : '请输入密码'" show-password />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="所属端" prop="guard_name">
              <el-select v-model="formData.guard_name" style="width: 100%" :disabled="isEdit" @change="handleFormGuardChange">
                <el-option label="平台端" value="platform" />
                <el-option label="供应商端" value="supplier" />
                <el-option label="经销商端" value="distributor" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="角色" prop="roles">
              <el-select v-model="formData.roles" multiple style="width: 100%">
                <el-option v-for="role in filteredRoleOptions" :key="role.name" :label="role.display_name || role.name" :value="role.name" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="是否启用">
              <el-switch v-model="formData.is_active" />
            </el-form-item>
          </el-col>
        </el-row>
      </el-form>
      <template slot="footer">
        <el-button @click="closeDialog" :disabled="submitLoading">取消</el-button>
        <el-button type="primary" :loading="submitLoading" @click="handleSubmit">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import { getUsers, createUser, updateUser, deleteUser } from '@/api/user'
import { getRoles } from '@/api/role'
import asyncTask from '@/mixins/asyncTask'
import permissionGuard from '@/mixins/permissionGuard'
import formState from '@/mixins/formState'

export default {
  name: 'Users',
  mixins: [asyncTask, permissionGuard, formState],
  data() {
    return {
      tableData: [],
      total: 0,
      roleOptions: [],
      rowToggling: {},
      queryParams: {
        page: 1,
        per_page: 15,
        keyword: '',
        guard_name: '',
        role: ''
      },
      formData: {
        id: null,
        name: '',
        email: '',
        password: '',
        guard_name: 'platform',
        roles: [],
        is_active: true
      },
      formRules: {
        name: [{ required: true, message: '请输入姓名', trigger: 'blur' }],
        email: [
          { required: true, message: '请输入邮箱', trigger: 'blur' },
          { type: 'email', message: '请输入正确的邮箱地址', trigger: 'blur' }
        ],
        guard_name: [{ required: true, message: '请选择所属端', trigger: 'change' }]
      }
    }
  },
  computed: {
    filteredRoleOptions() {
      return this.roleOptions.filter(r => r.guard_name === this.formData.guard_name)
    }
  },
  created() {
    this.fetchRoles()
    this.fetchData()
  },
  methods: {
    async fetchRoles() {
      await this.safeCall(() => getRoles(), {
        onSuccess: res => {
          this.roleOptions = res.data?.roles || res.data || []
        },
        silent: true
      })
    },
    async fetchData() {
      const params = this.cleanParams(this.queryParams)
      await this.withLoading(() => getUsers(params), {
        onSuccess: res => {
          this.tableData = res.data?.list || []
          this.total = res.data?.total || 0
        },
        silent: true
      })
    },
    handleSearch() {
      this.queryParams.page = 1
      this.fetchData()
    },
    handleReset() {
      this.queryParams = { page: 1, per_page: 15, keyword: '', guard_name: '', role: '' }
      this.fetchData()
    },
    handleAdd() {
      if (!this.$checkPermissionOrFail('user.create')) return
      this.openCreateForm()
      this.formData = { id: null, name: '', email: '', password: '', guard_name: 'platform', roles: [], is_active: true }
    },
    handleEdit(row) {
      if (!this.$checkPermissionOrFail('user.update')) return
      this.openEditForm(row, () => {
        this.formData = {
          id: row.id,
          name: row.name,
          email: row.email,
          password: '',
          guard_name: row.guard_name || 'platform',
          roles: (row.roles || []).map(r => r.name),
          is_active: row.is_active
        }
      })
    },
    handleFormGuardChange() {
      this.formData.roles = []
    },
    async handleToggleActive(row) {
      if (this.rowToggling[row.id]) return
      this.$set(this.rowToggling, row.id, true)
      try {
        await updateUser(row.id, { is_active: row.is_active })
        this.$message.success(row.is_active ? '已启用' : '已禁用')
      } catch (e) {
        row.is_active = !row.is_active
        const msg = e.response?.data?.message || e.message || '操作失败'
        this.$message.error(msg)
      } finally {
        this.$nextTick(() => {
          this.$delete(this.rowToggling, row.id)
        })
      }
    },
    handleDelete(row) {
      this.confirmAndDelete({
        message: `确定要删除用户"${row.name}"吗？`,
        apiCall: () => deleteUser(row.id),
        onSuccess: () => this.fetchData()
      })
    },
    handleSubmit() {
      this.submitForm(
        this.isEdit
          ? () => {
              const data = { ...this.formData }
              if (!data.password) delete data.password
              return updateUser(this.formData.id, data)
            }
          : () => createUser(this.formData),
        {
          formRef: 'formRef',
          onSuccess: () => this.fetchData()
        }
      )
    }
  }
}
</script>

<style lang="scss" scoped>
.users-page {
  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;

    .header-left {
      .page-title { font-size: 20px; color: #303133; margin-bottom: 4px; }
      .page-desc { font-size: 14px; color: #909399; }
    }
  }

  .filter-card { margin-bottom: 20px; }
}
</style>
