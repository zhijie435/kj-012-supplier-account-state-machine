<template>
  <el-tag
    :type="tagType"
    :effect="effect"
    :size="size"
    class="status-badge"
  >
    <i v-if="showIcon" :class="iconClass" class="status-icon"></i>
    {{ statusLabel }}
  </el-tag>
</template>

<script>
import { getStatusLabel, getStatusColor, isTerminalStatus } from '@/utils/constants'

const statusIcons = {
  pending: 'el-icon-time',
  verifying: 'el-icon-loading',
  active: 'el-icon-circle-check',
  suspended: 'el-icon-warning',
  rejected: 'el-icon-circle-close',
  cancelled: 'el-icon-delete'
}

export default {
  name: 'StatusBadge',
  props: {
    status: {
      type: String,
      required: true
    },
    size: {
      type: String,
      default: 'small'
    },
    effect: {
      type: String,
      default: 'light'
    },
    showIcon: {
      type: Boolean,
      default: true
    }
  },
  computed: {
    statusLabel() {
      return getStatusLabel(this.status)
    },
    tagType() {
      const color = getStatusColor(this.status)
      return color === 'secondary' ? 'info' : color
    },
    iconClass() {
      return statusIcons[this.status] || 'el-icon-circle'
    },
    isTerminal() {
      return isTerminalStatus(this.status)
    }
  }
}
</script>

<style lang="scss" scoped>
.status-badge {
  .status-icon {
    margin-right: 4px;
  }
}
</style>
