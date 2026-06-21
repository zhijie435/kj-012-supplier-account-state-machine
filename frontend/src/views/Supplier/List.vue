<template>
  <div class="supplier-list-page">
    <el-card class="page-card" shadow="never">
      <div class="page-header">
        <h2 class="page-title">供应商管理</h2>
        <div class="header-actions">
          <el-button
            type="primary"
            icon="el-icon-plus"
            v-permission="'supplier.create'"
            @click="handleCreate"
          >
            新增供应商
          </el-button>
        </div>
      </div>

      <el-form :model="searchForm" inline class="search-form">
        <el-form-item label="状态">
          <el-select
            v-model="searchForm.status"
            placeholder="全部状态"
            clearable
            style="width: 160px"
            @change="handleSearch"
          >
            <el-option
              v-for="option in statusOptions"
              :key="option.value"
              :label="option.label"
              :value="option.value"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="关键词">
          <el-input
            v-model="searchForm.keyword"
            placeholder="供应商名称/公司/联系人/电话"
            clearable
            style="width: 280px"
            @keyup.enter.native="handleSearch"
          />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" icon="el-icon-search" @click="handleSearch">搜索</el-button>
          <el-button icon="el-icon-refresh" @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>

      <el-table
        v-loading="loading"
        :data="tableData"
        border
        stripe
        class="supplier-table"
      >
        <el-table-column type="index" label="序号" width="60" align="center" />
        <el-table-column prop="name" label="供应商名称" min-width="160">
          <template slot-scope="scope">
            <el-link
              type="primary"
              :underline="false"
              @click="handleViewDetail(scope.row)"
            >
              {{ scope.row.name }}
            </el-link>
          </template>
        </el-table-column>
        <el-table-column prop="company_name" label="公司名称" min-width="180" show-overflow-tooltip />
        <el-table-column prop="contact_person" label="联系人" width="100" />
        <el-table-column prop="phone" label="联系电话" width="130" />
        <el-table-column label="状态" width="120" align="center">
          <template slot-scope="scope">
            <StatusBadge :status="scope.row.status" />
          </template>
        </el-table-column>
        <el-table-column prop="products_count" label="商品数量" width="100" align="center">
          <template slot-scope="scope">
            <el-tag type="info" size="small">{{ scope.row.products_count || 0 }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="orders_count" label="订单数量" width="100" align="center">
          <template slot-scope="scope">
            <el-tag type="warning" size="small">{{ scope.row.orders_count || 0 }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="160">
          <template slot-scope="scope">
            {{ formatTime(scope.row.created_at) }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="240" fixed="right" align="center">
          <template slot-scope="scope">
            <el-button
              type="text"
              size="small"
              icon="el-icon-view"
              @click="handleViewDetail(scope.row)"
            >
              详情
            </el-button>
            <el-button
              type="text"
              size="small"
              icon="el-icon-time"
              @click="handleViewLogs(scope.row)"
            >
              日志
            </el-button>
            <el-dropdown
              v-if="!isTerminalStatus(scope.row.status) && $hasPermission('supplier.approve')"
              @command="(cmd) => handleStatusAction(cmd, scope.row)"
            >
              <el-button type="text" size="small" icon="el-icon-setting">
                状态操作
                <i class="el-icon-arrow-down el-icon--right"></i>
              </el-button>
              <el-dropdown-menu slot="dropdown">
                <el-dropdown-item
                  v-for="transition in getAllowedTransitions(scope.row)"
                  :key="transition.value"
                  :command="{ status: transition.value, row: scope.row }"
                >
                  <span :style="{ color: getTransitionColor(transition.color) }">
                    变更为「{{ transition.label }}」
                  </span>
                </el-dropdown-item>
              </el-dropdown-menu>
            </el-dropdown>
          </template>
        </el-table-column>
      </el-table>

      <el-pagination
        class="pagination"
        background
        layout="total, sizes, prev, pager, next, jumper"
        :total="pagination.total"
        :page-size="pagination.pageSize"
        :page-sizes="[10, 20, 50, 100]"
        :current-page="pagination.currentPage"
        @size-change="handleSizeChange"
        @current-change="handlePageChange"
      />
    </el-card>

    <StateTransitionModal
      v-model="transitionModalVisible"
      :supplier-id="currentSupplierId"
      :current-status="currentStatus"
      :target-status="targetStatus"
      :require-remark="targetStatus === 'rejected'"
      @success="handleTransitionSuccess"
    />
  </div>
</template>

<script>
import StatusBadge from '@/components/StateMachine/StatusBadge.vue'
import StateTransitionModal from '@/components/StateMachine/StateTransitionModal.vue'
import asyncTask from '@/mixins/asyncTask'
import { getSupplierList, getAllowedTransitions } from '@/api/supplier'
import { SUPPLIER_STATUS_OPTIONS, isTerminalStatus, getStatusLabel } from '@/utils/constants'

export default {
  name: 'SupplierList',
  components: { StatusBadge, StateTransitionModal },
  mixins: [asyncTask],
  data() {
    return {
      statusOptions: SUPPLIER_STATUS_OPTIONS,
      searchForm: {
        status: '',
        keyword: ''
      },
      tableData: [],
      pagination: {
        total: 0,
        currentPage: 1,
        pageSize: 20
      },
      transitionModalVisible: false,
      currentSupplierId: null,
      currentStatus: '',
      targetStatus: '',
      allowedTransitionsMap: {}
    }
  },
  created() {
    this.fetchData()
  },
  methods: {
    isTerminalStatus,
    getStatusLabel,
    getTransitionColor(color) {
      const colorMap = {
        success: '#67c23a',
        warning: '#e6a23c',
        danger: '#f56c6c',
        info: '#909399',
        primary: '#409eff'
      }
      return colorMap[color] || '#606266'
    },
    getAllowedTransitions(row) {
      return this.allowedTransitionsMap[row.id] || []
    },
    async fetchAllowedTransitions(row) {
      if (this.allowedTransitionsMap[row.id]) return
      try {
        const res = await getAllowedTransitions(row.id)
        this.$set(this.allowedTransitionsMap, row.id, res.data.data || [])
      } catch (e) {
        // ignore
      }
    },
    async fetchData() {
      await this.withLoading(async () => {
        const params = this.cleanParams({
          page: this.pagination.currentPage,
          per_page: this.pagination.pageSize,
          status: this.searchForm.status,
          search: this.searchForm.keyword
        })

        const res = await getSupplierList(params)
        this.tableData = res.data.data

        this.tableData.forEach(row => {
          if (!isTerminalStatus(row.status)) {
            this.fetchAllowedTransitions(row)
          }
        })

        this.pagination.total = res.data.meta?.total || res.data.data?.length || 0
      })
    },
    handleSearch() {
      this.pagination.currentPage = 1
      this.fetchData()
    },
    handleReset() {
      this.searchForm.status = ''
      this.searchForm.keyword = ''
      this.handleSearch()
    },
    handleSizeChange(size) {
      this.pagination.pageSize = size
      this.pagination.currentPage = 1
      this.fetchData()
    },
    handlePageChange(page) {
      this.pagination.currentPage = page
      this.fetchData()
    },
    handleCreate() {
      this.$message.info('新增供应商功能开发中')
    },
    handleViewDetail(row) {
      this.$router.push(`/suppliers/${row.id}`)
    },
    handleViewLogs(row) {
      this.$router.push(`/suppliers/${row.id}/status-logs`)
    },
    handleStatusAction(action) {
      const { status, row } = action
      this.currentSupplierId = row.id
      this.currentStatus = row.status
      this.targetStatus = status
      this.transitionModalVisible = true
    },
    handleTransitionSuccess() {
      this.allowedTransitionsMap = {}
      this.fetchData()
    },
    formatTime(dateStr) {
      if (!dateStr) return ''
      const date = new Date(dateStr)
      return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')} ${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`
    }
  }
}
</script>

<style lang="scss" scoped>
.supplier-list-page {
  .page-card {
    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;

      .page-title {
        margin: 0;
        font-size: 18px;
        color: #303133;
      }
    }

    .search-form {
      margin-bottom: 20px;
      background: #fafafa;
      padding: 16px;
      border-radius: 4px;
    }

    .supplier-table {
      margin-bottom: 20px;
    }

    .pagination {
      display: flex;
      justify-content: flex-end;
    }
  }
}
</style>
