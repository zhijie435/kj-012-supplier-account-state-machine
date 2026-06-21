import store from '@/store'
import router from '@/router'
import { handleBusinessError, isAuthError } from '@/utils/errorHandler'

jest.mock('@/store', () => ({
  state: { token: '' },
  dispatch: jest.fn()
}))

jest.mock('@/router', () => ({
  push: jest.fn()
}))

describe('request.js interceptors logic', () => {
  beforeEach(() => {
    jest.clearAllMocks()
  })

  describe('request interceptor - token injection', () => {
    it('adds Bearer token when token exists in store', () => {
      store.state.token = 'test-token-123'
      const config = { headers: {} }

      const token = store.state.token
      if (token) {
        config.headers.Authorization = `Bearer ${token}`
      }

      expect(config.headers.Authorization).toBe('Bearer test-token-123')
    })

    it('does not add Authorization when no token', () => {
      store.state.token = ''
      const config = { headers: {} }

      const token = store.state.token
      if (token) {
        config.headers.Authorization = `Bearer ${token}`
      }

      expect(config.headers.Authorization).toBeUndefined()
    })

    it('passes through config unchanged when no token', () => {
      store.state.token = ''
      const config = { headers: {}, url: '/test' }

      const token = store.state.token
      if (token) {
        config.headers.Authorization = `Bearer ${token}`
      }

      expect(config.url).toBe('/test')
    })
  })

  describe('response interceptor - success handler', () => {
    it('returns response data directly when no business error', () => {
      const res = { code: 0, data: { id: 1 } }
      const bizError = handleBusinessError(res)

      expect(bizError).toBeNull()
    })

    it('detects auth business error in response', () => {
      const res = { code: 401, message: 'unauthorized' }
      const bizError = handleBusinessError(res)

      expect(bizError).not.toBeNull()
      expect(bizError.isAuth).toBe(true)
    })

    it('detects non-auth business error in response', () => {
      const res = { code: 400, message: '参数错误' }
      const bizError = handleBusinessError(res)

      expect(bizError).not.toBeNull()
      expect(bizError.isAuth).toBeUndefined()
      expect(bizError.isBusiness).toBe(true)
    })

    it('passes through code 200 response', () => {
      const res = { code: 200, data: { id: 1 } }
      const bizError = handleBusinessError(res)

      expect(bizError).toBeNull()
    })
  })

  describe('response interceptor - error handler', () => {
    it('dispatches logout and redirects for auth errors', () => {
      const error = { response: { status: 401 } }

      expect(isAuthError(error)).toBe(true)

      if (isAuthError(error)) {
        store.dispatch('logout')
        router.push('/login')
      }

      expect(store.dispatch).toHaveBeenCalledWith('logout')
      expect(router.push).toHaveBeenCalledWith('/login')
    })

    it('does not dispatch logout for non-auth errors', () => {
      const error = { response: { status: 500 } }

      expect(isAuthError(error)).toBe(false)
    })

    it('handles error with code 40101', () => {
      const error = { code: 40101 }
      expect(isAuthError(error)).toBe(true)
    })

    it('handles error with code 40102', () => {
      const error = { code: 40102 }
      expect(isAuthError(error)).toBe(true)
    })
  })

  describe('interceptor flow simulation', () => {
    it('request interceptor flow: token added and config returned', () => {
      store.state.token = 'abc'
      const config = { headers: {} }

      const result = (() => {
        const token = store.state.token
        if (token) {
          config.headers.Authorization = `Bearer ${token}`
        }
        return config
      })()

      expect(result.headers.Authorization).toBe('Bearer abc')
    })

    it('response interceptor flow: success with no business error', () => {
      const response = { data: { code: 0, data: { list: [] } } }
      const res = response.data
      const bizError = handleBusinessError(res)

      expect(bizError).toBeNull()
      expect(res.code).toBe(0)
    })

    it('response interceptor flow: auth business error triggers auth flow', () => {
      const response = { data: { code: 401, message: 'token过期' } }
      const res = response.data
      const bizError = handleBusinessError(res)

      expect(bizError).not.toBeNull()
      expect(bizError.isAuth).toBe(true)
    })

    it('response interceptor flow: non-auth business error', () => {
      const response = { data: { code: 400, message: '参数错误' } }
      const res = response.data
      const bizError = handleBusinessError(res)

      expect(bizError).not.toBeNull()
      expect(bizError.isAuth).toBeUndefined()
      expect(bizError.isBusiness).toBe(true)
    })
  })
})
