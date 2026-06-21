import store from '@/store'
import { Message } from 'element-ui'

const GUARD_LABELS = {
  platform: '平台端',
  supplier: '供应商端',
  distributor: '经销商端'
}

const GUARD_TAG_TYPES = {
  platform: '',
  supplier: 'success',
  distributor: 'warning'
}

export default {
  data() {
    return {
      guardLabels: GUARD_LABELS,
      guardTagTypes: GUARD_TAG_TYPES
    }
  },
  methods: {
    $hasPermission(permission) {
      const permissions = store.getters.permissions
      if (Array.isArray(permission)) {
        return permission.some(p => permissions.includes(p) || permissions.includes('*'))
      }
      return permissions.includes(permission) || permissions.includes('*')
    },

    $hasGuardPermission(permission, guardName) {
      const permissions = store.getters.permissions
      const currentGuard = guardName || store.getters.currentGuard
      const fullPermission = currentGuard ? `${currentGuard}.${permission}` : permission
      return permissions.includes(fullPermission) || permissions.includes(permission) || permissions.includes('*')
    },

    $checkPermissionOrFail(permission, message) {
      if (!this.$hasPermission(permission)) {
        Message.warning(message || '您没有该操作权限')
        return false
      }
      return true
    },

    isSystemRole(row) {
      const systemRoles = ['platform', 'supplier', 'distributor', 'platform-admin', 'supplier-admin', 'distributor-admin']
      return systemRoles.includes(row.name)
    },

    getGroupLabel(group) {
      const labels = {
        role: '角色管理',
        user: '用户管理',
        order: '订单管理',
        product: '商品管理',
        inventory: '库存管理',
        refund: '退款管理',
        coupon: '优惠券管理',
        dashboard: '数据概览',
        system: '系统设置'
      }
      return labels[group] || group
    }
  }
}
