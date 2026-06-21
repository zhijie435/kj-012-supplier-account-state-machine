<template>
  <div class="status-timeline">
    <el-timeline>
      <el-timeline-item
        v-for="(log, index) in logs"
        :key="log.id"
        :timestamp="formatTime(log.created_at)"
        :type="getTimelineType(log.to_status)"
        :color="getTimelineColor(log.to_status)"
        placement="top"
      >
        <div class="timeline-content">
          <div class="timeline-header">
            <span class="transition-text">
              <StatusBadge :status="log.from_status" size="mini" :show-icon="false" />
              <i class="el-icon-arrow-right transition-arrow"></i>
              <StatusBadge :status="log.to_status" size="mini" :show-icon="false" />
            </span>
            <span class="operator" v-if="log.operator">
              <i class="el-icon-user"></i>
              {{ log.operator.name }}
            </span>
          </div>
          <div class="timeline-body" v-if="log.remark">
            <el-input
              type="textarea"
              :rows="2"
              :value="log.remark"
              readonly
              class="remark-textarea"
            />
          </div>
          <div class="timeline-footer">
            <span class="timestamp">
              <i class="el-icon-time"></i>
              {{ formatFullTime(log.created_at) }}
            </span>
          </div>
        </div>
      </el-timeline-item>
      <el-timeline-item v-if="!logs || logs.length === 0" type="info">
        <span class="empty-text">暂无状态变更记录</span>
      </el-timeline-item>
    </el-timeline>
  </div>
</template>

<script>
import StatusBadge from './StatusBadge.vue'
import { getStatusColor } from '@/utils/constants'

const colorMap = {
  success: '#67c23a',
  warning: '#e6a23c',
  danger: '#f56c6c',
  info: '#909399',
  primary: '#409eff'
}

export default {
  name: 'StatusTimeline',
  components: { StatusBadge },
  props: {
    logs: {
      type: Array,
      default: () => []
    }
  },
  methods: {
    formatTime(dateStr) {
      if (!dateStr) return ''
      const date = new Date(dateStr)
      return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
    },
    formatFullTime(dateStr) {
      if (!dateStr) return ''
      const date = new Date(dateStr)
      return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')} ${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}:${String(date.getSeconds()).padStart(2, '0')}`
    },
    getTimelineType(status) {
      const color = getStatusColor(status)
      if (color === 'success') return 'success'
      if (color === 'danger') return 'danger'
      if (color === 'warning') return 'warning'
      return 'primary'
    },
    getTimelineColor(status) {
      const color = getStatusColor(status)
      return colorMap[color] || colorMap.info
    }
  }
}
</script>

<style lang="scss" scoped>
.status-timeline {
  padding: 16px 0;

  .timeline-content {
    background: #fff;
    border: 1px solid #e4e7ed;
    border-radius: 4px;
    padding: 12px;
    margin-bottom: 8px;

    .timeline-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 8px;

      .transition-text {
        display: flex;
        align-items: center;
        gap: 8px;

        .transition-arrow {
          color: #c0c4cc;
        }
      }

      .operator {
        color: #909399;
        font-size: 12px;

        i {
          margin-right: 4px;
        }
      }
    }

    .timeline-body {
      margin-bottom: 8px;

      .remark-textarea {
        ::v-deep .el-textarea__inner {
          background: #f5f7fa;
          border: 1px solid #e4e7ed;
          color: #606266;
          resize: none;
        }
      }
    }

    .timeline-footer {
      .timestamp {
        color: #c0c4cc;
        font-size: 12px;

        i {
          margin-right: 4px;
        }
      }
    }
  }

  .empty-text {
    color: #909399;
  }
}
</style>
