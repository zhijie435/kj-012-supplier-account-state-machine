<template>
  <div class="supplier-detail-page">
    <el-breadcrumb separator="/" class="breadcrumb">
      <el-breadcrumb-item :to="{ path: '/suppliers' }">供应商管理</el-breadcrumb-item>
      <el-breadcrumb-item>供应商详情</el-breadcrumb-item>
    </el-breadcrumb>

    <el-row :gutter="20">
      <el-col :span="16">
        <el-card class="info-card" shadow="never">
          <div slot="header" class="card-header">
            <span class="card-title">基本信息</span>
            <div class="header-actions">
              <el-button
                type="primary"
                size="small"
                icon="el-icon-edit"
                v-permission="'supplier.edit'"
                @click="handleEdit"
              >
                编辑
              </el-button>
              <el-dropdown
                v-if="!isTerminalStatus(supplier?.status) && $hasPermission('supplier.approve')"
                @command="handleStatusAction"
              >
                <el-button type="success" size="small" icon="el-icon-setting">
                  状态操作
                  <i class="el-icon-arrow-down el-icon--right"></i>
                </el-button>
                <el-dropdown-menu slot="dropdown">
                  <el-dropdown-item
                    v-for="transition in allowedTransitions"
                    :key="transition.value"
                    :command="transition.value"
                  >
                    <span :style="{ color: getTransitionColor(transition.color) }">
                      变更为「{{ transition.label }}」
                    </span>
                  </el-dropdown-item>
                </el-dropdown-menu>
              </el-dropdown>
            </div>
          </div>

          <div v-loading="loading" class="info-content">
            <div class="info-header">
              <div class="supplier-name">
                <h2>{{ supplier?.name || '-' }}</h2>
                <StatusBadge :status="supplier?.status" size="medium" />
              </div>
              <div class="supplier-stats">
                <div class="stat-item">
                  <span class="stat-value">{{ supplier?.products_count || 0 }}</span>
                  <span class="stat-label">商品数</span>
                </div>
                <div class="stat-item">
                  <span class="stat-value">{{ supplier?.orders_count || 0 }}</span>
                  <span class="stat-label">订单数</span>
                </div>
              </div>
            </div>

            <el-divider />

            <el-descriptions :column="2" border>
              <el-descriptions-item label="公司名称">
                {{ supplier?.company_name || '-' }}
              </el-descriptions-item>
              <el-descriptions-item label="统一社会信用代码">
                {{ supplier?.tax_number || '-' }}
              </el-descriptions-item>
              <el-descriptions-item label="联系人">
                {{ supplier?.contact_person || '-' }}
              </el-descriptions-item>
              <el-descriptions-item label="联系电话">
                {{ supplier?.phone || '-' }}
              </el-descriptions-item>
              <el-descriptions-item label="邮箱">
                {{ supplier?.email || '-' }}
              </el-descriptions-item>
              <el-descriptions-item label="是否跨境">
                <el-tag :type="supplier?.is_cross_border ? 'success' : 'info'" size="small">
                  {{ supplier?.is_cross_border ? '是' : '否' }}
                </el-tag>
              </el-descriptions-item>
              <el-descriptions-item label="营业执照" :span="2">
                <el-tag v-if="supplier?.business_license" type="success" size="small">
                  已上传
                </el-tag>
                <el-tag v-else type="warning" size="small">
                  未上传
                </el-tag>
              </el-descriptions-item>
              <el-descriptions-item label="备注" :span="2">
                {{ supplier?.remark || '-' }}
              </el-descriptions-item>
            </el-descriptions>

            <el-divider />

            <div class="status-times">
              <h4 class="times-title">状态时间线</h4>
              <el-row :gutter="20">
                <el-col :span="8" v-if="supplier?.verifying_at">
                  <div class="time-item">
                    <span class="time-label">审核时间</span>
                    <span class="time-value">{{ formatTime(supplier.verifying_at) }}</span>
                  </div>
                </el-col>
                <el-col :span="8" v-if="supplier?.activated_at">
                  <div class="time-item">
                    <span class="time-label">激活时间</span>
                    <span class="time-value">{{ formatTime(supplier.activated_at) }}</span>
                  </div>
                </el-col>
                <el-col :span="8" v-if="supplier?.suspended_at">
                  <div class="time-item">
                    <span class="time-label">暂停时间</span>
                    <span class="time-value">{{ formatTime(supplier.suspended_at) }}</span>
                  </div>
                </el-col>
                <el-col :span="8" v-if="supplier?.rejected_at">
                  <div class="time-item">
                    <span class="time-label">拒绝时间</span>
                    <span class="time-value">{{ formatTime(supplier.rejected_at) }}</span>
                  </div>
                </el-col>
                <el-col :span="8" v-if="supplier?.cancelled_at">
                  <div class="time-item">
                    <span class="time-label">注销时间</span>
                    <span class="time-value">{{ formatTime(supplier.cancelled_at) }}</span>
                  </div>
                </el-col>
              </el-row>
            </div>
          </div>
        </el-card>

        <el-card class="logs-card" shadow="never">
          <div slot="header" class="card-header">
            <span class="card-title">最近状态变更记录</span>
            <el-button
              type="text"
              icon="el-icon-more"
              @click="handleViewAllLogs"
            >
              查看全部
            </el-button>
          </div>
          <div v-loading="logsLoading" class="recent-logs">
            <StatusTimeline :logs="recentLogs" />
          </div>
        </el-card>
      </el-col>

      <el-col :span="8">
        <el-card class="state-card" shadow="never">
          <div slot="header" class="card-header">
            <span class="card-title">状态机流程图</span>
          </div>
          <div v-loading="loading" class="state-diagram-wrapper">
            <StateFlowDiagram
              :current-status="supplier?.status"
              :allowed-transitions="allowedTransitions"
            />
          </div>
        </el-card>

        <el-card class="quick-actions-card" shadow="never">
          <div slot="header" class="card-header">
            <span class="card-title">快捷操作</span>
          </div>
          <div class="quick-actions">
            <el-button
              type="primary"
              size="medium"
              icon="el-icon-time"
              style="width: 100%; margin-bottom: 12px"
              @click="handleViewAllLogs"
            >
              查看状态日志
            </el-button>
            <el-button
              type="info"
              size="medium"
              icon="el-icon-goods"
              style="width: 100%"
              @click="handleViewProducts"
            >
              查看商品列表
            </el-button>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <StateTransitionModal
      v-model="transitionModalVisible"
      :supplier-id="supplierId"
      :current-status="supplier?.status"
      :target-status="targetStatus"
      :require-remark="targetStatus === 'rejected'"
      @success="handleTransitionSuccess"
    />
  </div>
</template>

<script>
import StatusBadge from '@/components/StateMachine/StatusBadge.vue'
import StatusTimeline from '@/components/StateMachine/StatusTimeline.vue'
import StateFlowDiagram from '@/components/StateMachine/StateFlowDiagram.vue'
import StateTransitionModal from '@/components/StateMachine/StateTransitionModal.vue'
import asyncTask from '@/mixins/asyncTask'
import { getSupplierDetail, getAllowedTransitions } from '@/api/supplier'
import { isTerminalStatus } from '@/utils/constants'

export default {
  name: 'SupplierDetail',
  components: { StatusBadge, StatusTimeline, StateFlowDiagram, StateTransitionModal },
  mixins: [asyncTask],
  data() {
    return {
      supplier: null,
      allowedTransitions: [],
      recentLogs: [],
      logsLoading: false,
      transitionModalVisible: false,
      targetStatus: ''
    }
  },
  computed: {
    supplierId() {
      return this.$route.params.id
    }
  },
  created() {
    this.fetchData()
  },
  methods: {
    isTerminalStatus,
    getTransitionColor(color) {
      const colorMap = {
        success: '#67c23a',
        warning: '#e6a23c',
        danger: '#f56c6c',
        info: '#909399',
        primary: '#409eff',
        secondary: '#909399'
      }
      return colorMap[color] || '#606266'
    },
    async fetchData() {
      if (!this.supplierId) return

      await this.withLoading(async () => {
        const res = await getSupplierDetail(this.supplierId)
        this.supplier = res.data.data
        this.recentLogs = this.supplier.status_logs || []

        if (!isTerminalStatus(this.supplier.status)) {
          this.fetchAllowedTransitions()
        }
      })
    },
    async fetchAllowedTransitions() {
      try {
        const res = await getAllowedTransitions(this.supplierId)
        this.allowedTransitions = res.data.data || []
      } catch (e) {
        // ignore
      }
    },
    handleEdit() {
      this.$message.info('编辑功能开发中')
    },
    handleStatusAction(status) {
      this.targetStatus = status
      this.transitionModalVisible = true
    },
    handleTransitionSuccess() {
      this.allowedTransitions = []
      this.fetchData()
    },
    handleViewAllLogs() {
      this.$router.push(`/suppliers/${this.supplierId}/status-logs`)
    },
    handleViewProducts() {
      this.$message.info('商品列表功能开发中')
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
.supplier-detail-page {
  .breadcrumb {
    margin-bottom: 20px;
  }

  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;

    .card-title {
      font-weight: 600;
      color: #303133;
    }
  }

  .info-card {
    margin-bottom: 20px;

    .info-content {
      .info-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 16px;

        .supplier-name {
          display: flex;
          align-items: center;
          gap: 12px;

          h2 {
            margin: 0;
            font-size: 22px;
            color: #303133;
          }
        }

        .supplier-stats {
          display: flex;
          gap: 32px;

          .stat-item {
            text-align: center;

            .stat-value {
              display: block;
              font-size: 24px;
              font-weight: 600;
              color: #409eff;
            }

            .stat-label {
              display: block;
              font-size: 12px;
              color: #909399;
              margin-top: 4px;
            }
          }
        }
      }

      .status-times {
        .times-title {
          font-size: 14px;
          color: #606266;
          margin-bottom: 12px;
        }

        .time-item {
          display: flex;
          flex-direction: column;
          gap: 4px;

          .time-label {
            font-size: 12px;
            color: #909399;
          }

          .time-value {
            font-size: 13px;
            color: #606266;
          }
        }
      }
    }
  }

  .logs-card {
    margin-bottom: 20px;

    .recent-logs {
      max-height: 400px;
      overflow-y: auto;
    }
  }

  .state-card {
    margin-bottom: 20px;

    .state-diagram-wrapper {
      overflow-x: auto;
    }
  }

  .quick-actions-card {
    .quick-actions {
      padding: 8px 0;
    }
  }
}
</style>
