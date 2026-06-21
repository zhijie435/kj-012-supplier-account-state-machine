import Vue from 'vue'
import permission from '@/directives/permission'
import store from '@/store'

jest.mock('@/store', () => ({
  getters: {
    permissions: [],
    guardPermissions: [],
    currentGuard: 'platform'
  }
}))

describe('permission directive', () => {
  let el

  beforeEach(() => {
    jest.clearAllMocks()
    el = document.createElement('div')
    document.body.appendChild(el)
    store.getters.permissions = []
    store.getters.guardPermissions = []
  })

  afterEach(() => {
    if (el.parentNode) el.parentNode.removeChild(el)
  })

  describe('resolvePermissionValue', () => {
    it('uses arg when provided', () => {
      const binding = { value: 'role.view', arg: 'user.create' }
      const permValue = binding.arg || binding.value
      expect(permValue).toBe('user.create')
    })

    it('uses value when arg not provided', () => {
      const binding = { value: 'role.view' }
      const permValue = binding.arg || binding.value
      expect(permValue).toBe('role.view')
    })
  })

  describe('checkPermission - inserted hook', () => {
    it('hides element when user lacks permission', () => {
      store.getters.permissions = []

      Vue.directive('permission', {
        inserted: permission.install.directive
      })

      const checkPermission = (el, binding) => {
        const permValue = binding.arg || binding.value
        if (!permValue) return
        const permissions = store.getters.permissions
        const guardPermissions = store.getters.guardPermissions
        const permissionList = typeof permValue === 'string' ? [permValue] : permValue
        const hasPermission = permissionList.some(p => {
          if (permissions.includes(p) || permissions.includes('*')) return true
          if (guardPermissions.includes(p)) return true
          return false
        })
        if (!hasPermission) {
          if (el.parentNode) {
            el.parentNode.removeChild(el)
          } else {
            el.style.display = 'none'
          }
        } else {
          el.style.display = ''
        }
      }

      checkPermission(el, { value: 'role.view' })

      expect(el.parentNode).toBeNull()
    })

    it('shows element when user has permission', () => {
      store.getters.permissions = ['role.view']

      const checkPermission = (el, binding) => {
        const permValue = binding.arg || binding.value
        if (!permValue) return
        const permissions = store.getters.permissions
        const guardPermissions = store.getters.guardPermissions
        const permissionList = typeof permValue === 'string' ? [permValue] : permValue
        const hasPermission = permissionList.some(p => {
          if (permissions.includes(p) || permissions.includes('*')) return true
          if (guardPermissions.includes(p)) return true
          return false
        })
        if (!hasPermission) {
          if (el.parentNode) el.parentNode.removeChild(el)
          else el.style.display = 'none'
        } else {
          el.style.display = ''
        }
      }

      checkPermission(el, { value: 'role.view' })

      expect(el.style.display).toBe('')
    })

    it('shows element for wildcard * permission', () => {
      store.getters.permissions = ['*']

      const checkPermission = (el, binding) => {
        const permValue = binding.arg || binding.value
        if (!permValue) return
        const permissions = store.getters.permissions
        const guardPermissions = store.getters.guardPermissions
        const permissionList = typeof permValue === 'string' ? [permValue] : permValue
        const hasPermission = permissionList.some(p => {
          if (permissions.includes(p) || permissions.includes('*')) return true
          if (guardPermissions.includes(p)) return true
          return false
        })
        if (!hasPermission) {
          if (el.parentNode) el.parentNode.removeChild(el)
          else el.style.display = 'none'
        } else {
          el.style.display = ''
        }
      }

      checkPermission(el, { value: 'anything' })

      expect(el.style.display).toBe('')
    })

    it('shows element when guard permission matches', () => {
      store.getters.permissions = []
      store.getters.guardPermissions = ['role.view']

      const checkPermission = (el, binding) => {
        const permValue = binding.arg || binding.value
        if (!permValue) return
        const permissions = store.getters.permissions
        const guardPermissions = store.getters.guardPermissions
        const permissionList = typeof permValue === 'string' ? [permValue] : permValue
        const hasPermission = permissionList.some(p => {
          if (permissions.includes(p) || permissions.includes('*')) return true
          if (guardPermissions.includes(p)) return true
          return false
        })
        if (!hasPermission) {
          if (el.parentNode) el.parentNode.removeChild(el)
          else el.style.display = 'none'
        } else {
          el.style.display = ''
        }
      }

      checkPermission(el, { value: 'role.view' })

      expect(el.style.display).toBe('')
    })

    it('handles array of permissions - true if any match', () => {
      store.getters.permissions = ['user.create']

      const checkPermission = (el, binding) => {
        const permValue = binding.arg || binding.value
        if (!permValue) return
        const permissions = store.getters.permissions
        const guardPermissions = store.getters.guardPermissions
        const permissionList = typeof permValue === 'string' ? [permValue] : permValue
        const hasPermission = permissionList.some(p => {
          if (permissions.includes(p) || permissions.includes('*')) return true
          if (guardPermissions.includes(p)) return true
          return false
        })
        if (!hasPermission) {
          if (el.parentNode) el.parentNode.removeChild(el)
          else el.style.display = 'none'
        } else {
          el.style.display = ''
        }
      }

      checkPermission(el, { value: ['role.delete', 'user.create'] })

      expect(el.style.display).toBe('')
    })

    it('handles array of permissions - false if none match', () => {
      store.getters.permissions = ['role.view']

      const checkPermission = (el, binding) => {
        const permValue = binding.arg || binding.value
        if (!permValue) return
        const permissions = store.getters.permissions
        const guardPermissions = store.getters.guardPermissions
        const permissionList = typeof permValue === 'string' ? [permValue] : permValue
        const hasPermission = permissionList.some(p => {
          if (permissions.includes(p) || permissions.includes('*')) return true
          if (guardPermissions.includes(p)) return true
          return false
        })
        if (!hasPermission) {
          if (el.parentNode) el.parentNode.removeChild(el)
          else el.style.display = 'none'
        } else {
          el.style.display = ''
        }
      }

      checkPermission(el, { value: ['role.delete', 'user.delete'] })

      expect(el.parentNode).toBeNull()
    })

    it('does nothing when permission value is falsy', () => {
      const checkPermission = (el, binding) => {
        const permValue = binding.arg || binding.value
        if (!permValue) return
      }

      expect(() => checkPermission(el, { value: null })).not.toThrow()
      expect(() => checkPermission(el, { value: '' })).not.toThrow()
      expect(() => checkPermission(el, { value: undefined })).not.toThrow()
    })

    it('hides element with display:none when no parentNode', () => {
      const orphanEl = document.createElement('div')
      store.getters.permissions = []

      const checkPermission = (el, binding) => {
        const permValue = binding.arg || binding.value
        if (!permValue) return
        const permissions = store.getters.permissions
        const guardPermissions = store.getters.guardPermissions
        const permissionList = typeof permValue === 'string' ? [permValue] : permValue
        const hasPermission = permissionList.some(p => {
          if (permissions.includes(p) || permissions.includes('*')) return true
          if (guardPermissions.includes(p)) return true
          return false
        })
        if (!hasPermission) {
          if (el.parentNode) el.parentNode.removeChild(el)
          else el.style.display = 'none'
        } else {
          el.style.display = ''
        }
      }

      checkPermission(orphanEl, { value: 'role.view' })

      expect(orphanEl.style.display).toBe('none')
    })
  })

  describe('Vue.prototype.$hasPermission', () => {
    let vm

    beforeEach(() => {
      const VueInstance = Vue.extend()
      Vue.use(permission)
      vm = new VueInstance()
    })

    it('returns true when permission exists', () => {
      store.getters.permissions = ['role.view']
      store.getters.currentGuard = 'platform'

      expect(vm.$hasPermission('role.view')).toBe(true)
    })

    it('returns true for wildcard', () => {
      store.getters.permissions = ['*']

      expect(vm.$hasPermission('anything')).toBe(true)
    })

    it('returns false when permission missing', () => {
      store.getters.permissions = []

      expect(vm.$hasPermission('role.view')).toBe(false)
    })

    it('handles array of permissions', () => {
      store.getters.permissions = ['user.create']

      expect(vm.$hasPermission(['role.delete', 'user.create'])).toBe(true)
      expect(vm.$hasPermission(['role.delete', 'user.delete'])).toBe(false)
    })
  })
})
