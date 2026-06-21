import Vue from 'vue'
import permissionGuard from '@/mixins/permissionGuard'
import store from '@/store'
import { Message } from 'element-ui'

jest.mock('@/store', () => ({
  getters: {
    permissions: [],
    currentGuard: 'platform'
  }
}))

jest.mock('element-ui', () => ({
  Message: {
    warning: jest.fn()
  }
}))

function createComponent() {
  return new Vue({
    mixins: [permissionGuard]
  })
}

describe('permissionGuard mixin', () => {
  beforeEach(() => {
    jest.clearAllMocks()
    store.getters.permissions = []
    store.getters.currentGuard = 'platform'
  })

  describe('data', () => {
    it('provides guardLabels for all three guards', () => {
      const vm = createComponent()
      expect(vm.guardLabels.platform).toBe('平台端')
      expect(vm.guardLabels.supplier).toBe('供应商端')
      expect(vm.guardLabels.distributor).toBe('经销商端')
    })

    it('provides guardTagTypes for all three guards', () => {
      const vm = createComponent()
      expect(vm.guardTagTypes.platform).toBe('')
      expect(vm.guardTagTypes.supplier).toBe('success')
      expect(vm.guardTagTypes.distributor).toBe('warning')
    })
  })

  describe('$hasPermission', () => {
    it('returns true when permission exists in store', () => {
      const vm = createComponent()
      store.getters.permissions = ['role.view', 'user.create']

      expect(vm.$hasPermission('role.view')).toBe(true)
    })

    it('returns false when permission does not exist', () => {
      const vm = createComponent()
      store.getters.permissions = ['role.view']

      expect(vm.$hasPermission('role.delete')).toBe(false)
    })

    it('returns true for wildcard *', () => {
      const vm = createComponent()
      store.getters.permissions = ['*']

      expect(vm.$hasPermission('any.thing')).toBe(true)
    })

    it('handles array of permissions - true if any match', () => {
      const vm = createComponent()
      store.getters.permissions = ['role.view']

      expect(vm.$hasPermission(['role.delete', 'role.view'])).toBe(true)
    })

    it('handles array of permissions - false if none match', () => {
      const vm = createComponent()
      store.getters.permissions = ['role.view']

      expect(vm.$hasPermission(['role.delete', 'user.delete'])).toBe(false)
    })

    it('handles array with wildcard *', () => {
      const vm = createComponent()
      store.getters.permissions = ['*']

      expect(vm.$hasPermission(['role.delete', 'user.create'])).toBe(true)
    })
  })

  describe('$hasGuardPermission', () => {
    it('returns true when guard-prefixed permission exists', () => {
      const vm = createComponent()
      store.getters.permissions = ['platform.role.view']

      expect(vm.$hasGuardPermission('role.view', 'platform')).toBe(true)
    })

    it('returns true when plain permission exists', () => {
      const vm = createComponent()
      store.getters.permissions = ['role.view']

      expect(vm.$hasGuardPermission('role.view', 'platform')).toBe(true)
    })

    it('uses currentGuard when guardName not provided', () => {
      const vm = createComponent()
      store.getters.permissions = ['platform.role.view']
      store.getters.currentGuard = 'platform'

      expect(vm.$hasGuardPermission('role.view')).toBe(true)
    })

    it('returns true for wildcard *', () => {
      const vm = createComponent()
      store.getters.permissions = ['*']

      expect(vm.$hasGuardPermission('anything', 'supplier')).toBe(true)
    })

    it('returns false when no matching permission', () => {
      const vm = createComponent()
      store.getters.permissions = ['supplier.role.view']

      expect(vm.$hasGuardPermission('role.view', 'platform')).toBe(false)
    })

    it('handles case when guardName is empty string', () => {
      const vm = createComponent()
      store.getters.permissions = ['role.view']

      expect(vm.$hasGuardPermission('role.view', '')).toBe(true)
    })
  })

  describe('$checkPermissionOrFail', () => {
    it('returns true when has permission', () => {
      const vm = createComponent()
      store.getters.permissions = ['role.view']

      expect(vm.$checkPermissionOrFail('role.view')).toBe(true)
    })

    it('returns false and shows warning when lacks permission', () => {
      const vm = createComponent()
      store.getters.permissions = []

      expect(vm.$checkPermissionOrFail('role.delete')).toBe(false)
      expect(Message.warning).toHaveBeenCalledWith('您没有该操作权限')
    })

    it('uses custom warning message', () => {
      const vm = createComponent()
      store.getters.permissions = []

      expect(vm.$checkPermissionOrFail('role.delete', '无权删除角色')).toBe(false)
      expect(Message.warning).toHaveBeenCalledWith('无权删除角色')
    })
  })

  describe('isSystemRole', () => {
    it('identifies platform as system role', () => {
      const vm = createComponent()
      expect(vm.isSystemRole({ name: 'platform' })).toBe(true)
    })

    it('identifies supplier as system role', () => {
      const vm = createComponent()
      expect(vm.isSystemRole({ name: 'supplier' })).toBe(true)
    })

    it('identifies distributor as system role', () => {
      const vm = createComponent()
      expect(vm.isSystemRole({ name: 'distributor' })).toBe(true)
    })

    it('identifies platform-admin as system role', () => {
      const vm = createComponent()
      expect(vm.isSystemRole({ name: 'platform-admin' })).toBe(true)
    })

    it('identifies supplier-admin as system role', () => {
      const vm = createComponent()
      expect(vm.isSystemRole({ name: 'supplier-admin' })).toBe(true)
    })

    it('identifies distributor-admin as system role', () => {
      const vm = createComponent()
      expect(vm.isSystemRole({ name: 'distributor-admin' })).toBe(true)
    })

    it('does not identify custom role as system role', () => {
      const vm = createComponent()
      expect(vm.isSystemRole({ name: 'custom-role' })).toBe(false)
    })

    it('does not identify admin without guard prefix as system role', () => {
      const vm = createComponent()
      expect(vm.isSystemRole({ name: 'admin' })).toBe(false)
    })
  })

  describe('getGroupLabel', () => {
    it('returns correct label for role group', () => {
      const vm = createComponent()
      expect(vm.getGroupLabel('role')).toBe('角色管理')
    })

    it('returns correct label for user group', () => {
      const vm = createComponent()
      expect(vm.getGroupLabel('user')).toBe('用户管理')
    })

    it('returns correct label for order group', () => {
      const vm = createComponent()
      expect(vm.getGroupLabel('order')).toBe('订单管理')
    })

    it('returns correct label for product group', () => {
      const vm = createComponent()
      expect(vm.getGroupLabel('product')).toBe('商品管理')
    })

    it('returns correct label for inventory group', () => {
      const vm = createComponent()
      expect(vm.getGroupLabel('inventory')).toBe('库存管理')
    })

    it('returns correct label for refund group', () => {
      const vm = createComponent()
      expect(vm.getGroupLabel('refund')).toBe('退款管理')
    })

    it('returns correct label for coupon group', () => {
      const vm = createComponent()
      expect(vm.getGroupLabel('coupon')).toBe('优惠券管理')
    })

    it('returns correct label for dashboard group', () => {
      const vm = createComponent()
      expect(vm.getGroupLabel('dashboard')).toBe('数据概览')
    })

    it('returns correct label for system group', () => {
      const vm = createComponent()
      expect(vm.getGroupLabel('system')).toBe('系统设置')
    })

    it('returns the group key itself when no label found', () => {
      const vm = createComponent()
      expect(vm.getGroupLabel('unknown')).toBe('unknown')
    })
  })
})
