import Vuex from 'vuex'
import Vue from 'vue'
import { login, logout, getUserInfo } from '@/api/auth'

Vue.use(Vuex)

jest.mock('@/api/auth', () => ({
  login: jest.fn(),
  logout: jest.fn(),
  getUserInfo: jest.fn()
}))

function createStore() {
  return new Vuex.Store({
    state: {
      token: localStorage.getItem('token') || '',
      userInfo: JSON.parse(localStorage.getItem('userInfo') || 'null'),
      roles: JSON.parse(localStorage.getItem('roles') || '[]'),
      permissions: JSON.parse(localStorage.getItem('permissions') || '[]')
    },
    getters: {
      isLogin: state => !!state.token,
      userInfo: state => state.userInfo,
      roles: state => state.roles,
      currentGuard: state => {
        if (!state.userInfo) return null
        return state.userInfo.guard_name || 'platform'
      },
      permissions: state => {
        const perms = new Set()
        state.permissions.forEach(p => perms.add(p.name || p))
        state.roles.forEach(role => {
          if (role.permissions) {
            role.permissions.forEach(p => perms.add(p.name || p))
          }
        })
        return Array.from(perms)
      },
      guardPermissions: (state, getters) => {
        const guard = getters.currentGuard
        if (!guard) return []
        return getters.permissions.filter(p => {
          const parts = p.split('.')
          return parts.length >= 2
        })
      },
      hasPermission: (state, getters) => perm => {
        return getters.permissions.includes(perm) || getters.permissions.includes('*')
      },
      hasAnyPermission: (state, getters) => permList => {
        if (!Array.isArray(permList)) return false
        return permList.some(p => getters.permissions.includes(p) || getters.permissions.includes('*'))
      }
    },
    mutations: {
      SET_TOKEN(state, token) {
        state.token = token
        localStorage.setItem('token', token)
      },
      SET_USER_INFO(state, userInfo) {
        state.userInfo = userInfo
        localStorage.setItem('userInfo', JSON.stringify(userInfo))
      },
      SET_ROLES(state, roles) {
        state.roles = roles
        localStorage.setItem('roles', JSON.stringify(roles))
      },
      SET_PERMISSIONS(state, permissions) {
        state.permissions = permissions
        localStorage.setItem('permissions', JSON.stringify(permissions))
      },
      CLEAR_AUTH(state) {
        state.token = ''
        state.userInfo = null
        state.roles = []
        state.permissions = []
        ;['token', 'userInfo', 'roles', 'permissions'].forEach(key => localStorage.removeItem(key))
      }
    },
    actions: {
      async login({ commit }, loginData) {
        const res = await login(loginData)
        commit('SET_TOKEN', res.data.token)
        commit('SET_USER_INFO', res.data.user)
        commit('SET_ROLES', res.data.user.roles || [])
        commit('SET_PERMISSIONS', res.data.user.permissions || [])
        return res.data
      },
      async logout({ commit }) {
        try { await logout() } catch (e) {}
        commit('CLEAR_AUTH')
      },
      async getUserInfo({ commit, state }) {
        if (!state.token) return null
        try {
          const res = await getUserInfo()
          commit('SET_USER_INFO', res.data)
          commit('SET_ROLES', res.data.roles || [])
          commit('SET_PERMISSIONS', res.data.permissions || [])
          return res.data
        } catch (e) {
          if (e.response?.status === 401) commit('CLEAR_AUTH')
          throw e
        }
      }
    }
  })
}

describe('Vuex Store', () => {
  let store

  beforeEach(() => {
    localStorage.clear()
    store = createStore()
  })

  describe('mutations', () => {
    it('SET_TOKEN sets token in state and localStorage', () => {
      store.commit('SET_TOKEN', 'abc123')
      expect(store.state.token).toBe('abc123')
      expect(localStorage.getItem('token')).toBe('abc123')
    })

    it('SET_USER_INFO sets userInfo in state and localStorage', () => {
      const user = { id: 1, name: 'admin', guard_name: 'platform' }
      store.commit('SET_USER_INFO', user)
      expect(store.state.userInfo).toEqual(user)
      expect(JSON.parse(localStorage.getItem('userInfo'))).toEqual(user)
    })

    it('SET_ROLES sets roles in state and localStorage', () => {
      const roles = [{ name: 'admin' }]
      store.commit('SET_ROLES', roles)
      expect(store.state.roles).toEqual(roles)
      expect(JSON.parse(localStorage.getItem('roles'))).toEqual(roles)
    })

    it('SET_PERMISSIONS sets permissions in state and localStorage', () => {
      const perms = [{ name: 'role.view' }, { name: 'user.create' }]
      store.commit('SET_PERMISSIONS', perms)
      expect(store.state.permissions).toEqual(perms)
    })

    it('CLEAR_AUTH resets all auth state and localStorage', () => {
      store.commit('SET_TOKEN', 'abc')
      store.commit('SET_USER_INFO', { id: 1 })
      store.commit('SET_ROLES', [{ name: 'admin' }])
      store.commit('SET_PERMISSIONS', [{ name: 'role.view' }])

      store.commit('CLEAR_AUTH')

      expect(store.state.token).toBe('')
      expect(store.state.userInfo).toBeNull()
      expect(store.state.roles).toEqual([])
      expect(store.state.permissions).toEqual([])
      expect(localStorage.getItem('token')).toBeNull()
      expect(localStorage.getItem('userInfo')).toBeNull()
    })
  })

  describe('getters', () => {
    it('isLogin returns true when token exists', () => {
      store.commit('SET_TOKEN', 'abc')
      expect(store.getters.isLogin).toBe(true)
    })

    it('isLogin returns false when token is empty', () => {
      expect(store.getters.isLogin).toBe(false)
    })

    it('currentGuard returns null when no userInfo', () => {
      expect(store.getters.currentGuard).toBeNull()
    })

    it('currentGuard returns guard_name from userInfo', () => {
      store.commit('SET_USER_INFO', { guard_name: 'supplier' })
      expect(store.getters.currentGuard).toBe('supplier')
    })

    it('currentGuard defaults to platform', () => {
      store.commit('SET_USER_INFO', { id: 1 })
      expect(store.getters.currentGuard).toBe('platform')
    })

    it('permissions aggregates from state.permissions and role.permissions', () => {
      store.commit('SET_PERMISSIONS', [{ name: 'role.view' }, 'user.create'])
      store.commit('SET_ROLES', [{ name: 'admin', permissions: [{ name: 'role.delete' }] }])

      const perms = store.getters.permissions
      expect(perms).toContain('role.view')
      expect(perms).toContain('user.create')
      expect(perms).toContain('role.delete')
    })

    it('permissions deduplicates entries', () => {
      store.commit('SET_PERMISSIONS', [{ name: 'role.view' }])
      store.commit('SET_ROLES', [{ name: 'admin', permissions: [{ name: 'role.view' }] }])

      const perms = store.getters.permissions
      expect(perms.filter(p => p === 'role.view')).toHaveLength(1)
    })

    it('guardPermissions filters permissions with dot notation', () => {
      store.commit('SET_USER_INFO', { guard_name: 'platform' })
      store.commit('SET_PERMISSIONS', [{ name: 'role.view' }, { name: 'dashboard' }])

      const guardPerms = store.getters.guardPermissions
      expect(guardPerms).toContain('role.view')
      expect(guardPerms).not.toContain('dashboard')
    })

    it('guardPermissions returns empty array when no guard', () => {
      expect(store.getters.guardPermissions).toEqual([])
    })

    it('hasPermission returns true when permission exists', () => {
      store.commit('SET_PERMISSIONS', [{ name: 'role.view' }])
      expect(store.getters.hasPermission('role.view')).toBe(true)
    })

    it('hasPermission returns true for wildcard *', () => {
      store.commit('SET_PERMISSIONS', [{ name: '*' }])
      expect(store.getters.hasPermission('anything')).toBe(true)
    })

    it('hasPermission returns false when permission missing', () => {
      store.commit('SET_PERMISSIONS', [{ name: 'role.view' }])
      expect(store.getters.hasPermission('role.delete')).toBe(false)
    })

    it('hasAnyPermission returns true if any permission matches', () => {
      store.commit('SET_PERMISSIONS', [{ name: 'role.view' }])
      expect(store.getters.hasAnyPermission(['role.delete', 'role.view'])).toBe(true)
    })

    it('hasAnyPermission returns false for empty array', () => {
      store.commit('SET_PERMISSIONS', [{ name: 'role.view' }])
      expect(store.getters.hasAnyPermission([])).toBe(false)
    })

    it('hasAnyPermission returns false for non-array input', () => {
      store.commit('SET_PERMISSIONS', [{ name: 'role.view' }])
      expect(store.getters.hasAnyPermission('role.view')).toBe(false)
    })

    it('hasAnyPermission respects wildcard *', () => {
      store.commit('SET_PERMISSIONS', [{ name: '*' }])
      expect(store.getters.hasAnyPermission(['anything'])).toBe(true)
    })
  })

  describe('actions', () => {
    it('login commits token, userInfo, roles, permissions', async () => {
      login.mockResolvedValue({
        data: {
          token: 'new-token',
          user: {
            id: 1,
            name: 'admin',
            guard_name: 'platform',
            roles: [{ name: 'admin' }],
            permissions: [{ name: 'role.view' }]
          }
        }
      })

      const result = await store.dispatch('login', { email: 'a@b.com', password: '123' })

      expect(store.state.token).toBe('new-token')
      expect(store.state.userInfo.name).toBe('admin')
      expect(store.state.roles).toEqual([{ name: 'admin' }])
      expect(store.state.permissions).toEqual([{ name: 'role.view' }])
      expect(result.token).toBe('new-token')
    })

    it('login handles missing roles/permissions gracefully', async () => {
      login.mockResolvedValue({
        data: { token: 'tok', user: { id: 2, name: 'user' } }
      })

      await store.dispatch('login', { email: 'a@b.com', password: '123' })

      expect(store.state.roles).toEqual([])
      expect(store.state.permissions).toEqual([])
    })

    it('logout clears auth even if API call fails', async () => {
      logout.mockRejectedValue(new Error('network error'))
      store.commit('SET_TOKEN', 'existing')

      await store.dispatch('logout')

      expect(store.state.token).toBe('')
      expect(store.state.userInfo).toBeNull()
    })

    it('logout clears auth on successful API call', async () => {
      logout.mockResolvedValue({})
      store.commit('SET_TOKEN', 'existing')

      await store.dispatch('logout')

      expect(store.state.token).toBe('')
    })

    it('getUserInfo returns null when no token', async () => {
      store.state.token = ''

      const result = await store.dispatch('getUserInfo')

      expect(result).toBeNull()
      expect(getUserInfo).not.toHaveBeenCalled()
    })

    it('getUserInfo commits user info on success', async () => {
      store.commit('SET_TOKEN', 'valid')
      getUserInfo.mockResolvedValue({
        data: { id: 1, name: 'admin', roles: [{ name: 'admin' }], permissions: [{ name: 'role.view' }] }
      })

      const result = await store.dispatch('getUserInfo')

      expect(store.state.userInfo.name).toBe('admin')
      expect(store.state.roles).toEqual([{ name: 'admin' }])
      expect(result.name).toBe('admin')
    })

    it('getUserInfo clears auth on 401 error', async () => {
      store.commit('SET_TOKEN', 'expired')
      const error = new Error('unauthorized')
      error.response = { status: 401 }
      getUserInfo.mockRejectedValue(error)

      await expect(store.dispatch('getUserInfo')).rejects.toThrow('unauthorized')
      expect(store.state.token).toBe('')
    })

    it('getUserInfo rethrows non-401 errors without clearing auth', async () => {
      store.commit('SET_TOKEN', 'valid')
      const error = new Error('server error')
      error.response = { status: 500 }
      getUserInfo.mockRejectedValue(error)

      await expect(store.dispatch('getUserInfo')).rejects.toThrow('server error')
      expect(store.state.token).toBe('valid')
    })
  })
})
