<template>
  <div class="supplier-status-log-page">
    <el-breadcrumb separator="/" class="breadcrumb">
      <el-breadcrumb-item :to="{ path: '/suppliers' }">供应商管理</el-breadcrumb-item>
      <el-breadcrumb-item :to="{ path: `/suppliers/${supplierId}` }">供应商详情</el-breadcrumb-item>
      <el-breadcrumb-item>状态变更日志</el-breadcrumb-item>
    </el-breadcrumb>

    <el-card class="page-card" shadow="never">
      <div class="page-header">
        <div class="header-info">
          <h2 class="page-title">状态变更日志</h2>
          <div class="supplier-basic" v-if="supplier">
            <span class="supplier-name">{{ supplier.name }}</span>
            <StatusBadge :status="supplier.status" size="small" />
          </div>
        </div>
        <div class="header-actions">
          <el-button
            icon="el-icon-refresh"
            :loading="loading"
            @click="fetchData"
          >
            刷新
          </el-button>
          <el-button
            type="primary"
            icon="el-icon-back"
            @click="handleBack"
          >
            返回详情
          </el-button>
        </div>
      </div>

      <el-form :model="filterForm" inline class="filter-form">
        <el-form-item label="变更状态">
          <el-select
            v-model="filterForm.to_status"
            placeholder="全部状态"
            clearable
            style="width: 160px"
            @change="handleFilter"
          >
            <el-option
              v-for="option in statusOptions"
              :key="option.value"
              :label="option.label"
              :value="option.value"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="操作人">
          <el-input
            v-model="filterForm.operator"
            placeholder="操作人姓名"
            clearable
            style="width: 180px"
            @keyup.enter.native="handleFilter"
          />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" icon="el-icon-search" @click="handleFilter">筛选</el-button>
          <el-button icon="el-icon-refresh-left" @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>

      <div class="stats-row">
        <el-row :gutter="20">
          <el-col :span="6">
            <div class="stat-card total">
              <div class="stat-icon">
                <i class="el-icon-document"></i>
              </div>
              <div class="stat-content">
                <span class="stat-value">{{ pagination.total }}</span>
                <span class="stat-label">总变更次数</span>
              </div>
            </div>
          </el-col>
          <el-col :span="6">
            <div class="stat-card success">
              <div class="stat-icon">
                <i class="el-icon-circle-check"></i>
              </div>
              <div class="stat-content">
                <span class="stat-value">{{ statusStats.active || 0 }}</span>
                <span class="stat-label">激活次数</span>
              </div>
            </div>
          </el-col>
          <el-col :span="6">
            <div class="stat-card warning">
              <div class="stat-icon">
                <i class="el-icon-warning"></i>
              </div>
              <div class="stat-content">
                <span class="stat-value">{{ statusStats.suspended || 0 }}</span>
                <span class="stat-label">暂停次数</span>
              </div>
            </div>
          </el-col>
          <el-col :span="6">
            <div class="stat-card danger">
              <div class="stat-icon">
                <i class="el-icon-circle-close"></i>
              </div>
              <div class="stat-content">
                <span class="stat-value">{{ statusStats.rejected || 0 }}</span>
                <span class="stat-label">拒绝次数</span>
              </div>
            </div>
          </el-col>
        </el-row>
      </div>

      <div v-loading="loading" class="logs-content">
        <StatusTimeline :logs="logList" />

        <el-pagination
          v-if="pagination.total > 0"
          class="pagination"
          background
          layout="total, prev, pager, next, jumper"
          :total="pagination.total"
          :page-size="pagination.pageSize"
          :current-page="pagination.currentPage"
          @current-change="handlePageChange"
        />

        <el-empty
          v-if="!loading && logList.length === 0"
          description="暂无状态变更记录"
          class="empty-state"
        />
      </div>
    </el-card>
  </div>
</template>

<script>
import StatusBadge from '@/components/StateMachine/StatusBadge.vue'
import StatusTimeline from '@/components/StateMachine/StatusTimeline.vue'
import asyncTask from '@/mixins/asyncTask'
import { getSupplierStatusLogs, getSupplierDetail } from '@/api/supplier'
import { SUPPLIER_STATUS_OPTIONS, SUPPLIER_ACCOUNT_STATUS } from '@/utils/constants'

export default {
  name: 'SupplierStatusLog',
  components: { StatusBadge, StatusTimeline },
  mixins: [asyncTask],
  data() {
    return {
      statusOptions: SUPPLIER_STATUS_OPTIONS,
      supplier: null,
      logList: [],
      filterForm: {
        to_status: '',
        operator: ''
      },
      pagination: {
        total: 0,
        currentPage: 1,
        pageSize: 20
      },
      statusStats: {
        active: 0,
        suspended: 0,
        rejected: 0
      }
    }
  },
  computed: {
    supplierId() {
      return this.$route.params.id
    }
  },
  created() {
    this.fetchSupplierInfo()
    this.fetchData()
  },
  methods: {
    async fetchSupplierInfo() {
      try {
        const res = await getSupplierDetail(this.supplierId)
        this.supplier = res.data.data
      } catch (e) {
        // ignore
      }
    },
    async fetchData() {
      await this.withLoading(async () => {
        const params = this.cleanParams({
          page: this.pagination.currentPage,
          per_page: this.pagination.pageSize,
          to_status: this.filterForm.to_status
        })

        const res = await getSupplierStatusLogs(this.supplierId, params)
        this.logList = res.data.data
        this.pagination.total = res.data.meta?.total || res.data.data?.length || 0

        this.calculateStatusStats()
      })
    },
    calculateStatusStats() {
      const stats = {
        active: 0,
        suspended: 0,
        rejected: 0
      }

      this.logList.forEach(log => {
        if (log.to_status === SUPPLIER_ACCOUNT_STATUS.ACTIVE) {
          stats.active++
        } else if (log.to_status === SUPPLIER_ACCOUNT_STATUS.SUSPENDED) {
          stats.suspended++
        } else if (log.to_status === SUPPLIER_ACCOUNT_STATUS.REJECTED) {
          stats.rejected++
        }
      })

      this.statusStats = stats
    },
    handleFilter() {
      this.pagination.currentPage = 1
      this.fetchData()
    },
    handleReset() {
      this.filterForm.to_status = ''
      this.filterForm.operator = ''
      this.handleFilter()
    },
    handlePageChange(page) {
      this.pagination.currentPage = page
      this.fetchData()
    },
    handleBack() {
      this.$router.push(`/suppliers/${this.supplierId}`)
    }
  }
}
</script>

<style lang="scss" scoped>
.supplier-status-log-page {
  .breadcrumb {
    margin-bottom: 20px;
  }

  .page-card {
    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;

      .header-info {
        display: flex;
        align-items: center;
        gap: 16px;

        .page-title {
          margin: 0;
          font-size: 18px;
          color: #303133;
        }

        .supplier-basic {
          display: flex;
          align-items: center;
          gap: 8px;

          .supplier-name {
            font-size: 14px;
            color: #606266;
          }
        }
      }
    }

    .filter-form {
      margin-bottom: 20px;
      background: #fafafa;
      padding: 16px;
      border-radius: 4px;
    }

    .stats-row {
      margin-bottom: 24px;

      .stat-card {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 20px;
        border-radius: 8px;
        background: #fff;
        border: 1px solid #e4e7ed;

        .stat-icon {
          width: 48px;
          height: 48px;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 24px;

          i {
            color: #fff;
          }
        }

        .stat-content {
          display: flex;
          flex-direction: column;
          gap: 4px;

          .stat-value {
            font-size: 24px;
            font-weight: 600;
            color: #303133;
          }

          .stat-label {
            font-size: 12px;
            color: #909399;
          }
        }

        &.total {
          .stat-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
          }
        }

        &.success {
          .stat-icon {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
          }
        }

        &.warning {
          .stat-icon {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
          }
        }

        &.danger {
          .stat-icon {
            background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
          }
        }
      }
    }

    .logs-content {
      min-height: 300px;

      .pagination {
        display: flex;
        justify-content: center;
        margin-top: 24px;
      }

      .empty-state {
        padding: 60px 0;
      }
    }
  }
}
</style>
