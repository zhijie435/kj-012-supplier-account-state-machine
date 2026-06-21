import store from '@/store'

function resolvePermissionValue(binding) {
  const { value, arg } = binding
  if (arg) return arg
  return value
}

function checkPermission(el, binding) {
  const permValue = resolvePermissionValue(binding)
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

const permission = {
  install(Vue) {
    Vue.directive('permission', {
      inserted(el, binding) {
        checkPermission(el, binding)
      },
      componentUpdated(el, binding) {
        checkPermission(el, binding)
      }
    })

    Vue.prototype.$hasPermission = function(perm, guardName) {
      const permissions = store.getters.permissions
      const guardPerms = guardName
        ? store.getters.guardPermissions.filter(p => {
            const guard = store.getters.currentGuard
            return guard === guardName
          })
        : []

      const allPerms = [...permissions, ...guardPerms]

      if (Array.isArray(perm)) {
        return perm.some(p => allPerms.includes(p) || allPerms.includes('*'))
      }
      return allPerms.includes(perm) || allPerms.includes('*')
    }
  }
}

export default permission
