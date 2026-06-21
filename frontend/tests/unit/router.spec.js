import store from '@/store'

jest.mock('@/store', () => ({
  getters: {
    isLogin: false,
    hasPermission: jest.fn(),
    permissions: [],
    guardPermissions: []
  },
  dispatch: jest.fn()
}))

function createGuard() {
  return (to, from, next) => {
    document.title = to.meta.title ? `${to.meta.title} - 电商订单库存后台` : '电商订单库存后台'

    if (to.meta.requiresAuth) {
      if (!store.getters.isLogin) {
        next({ path: '/login', query: { redirect: to.fullPath } })
        return
      }

      if (to.meta.permission) {
        const hasPermission = store.getters.hasPermission(to.meta.permission)
        if (!hasPermission) {
          next({ path: '/403' })
          return
        }
      }
    }

    next()
  }
}

describe('Router beforeEach guard', () => {
  let guard

  beforeEach(() => {
    jest.clearAllMocks()
    guard = createGuard()
  })

  it('allows access to login without auth', () => {
    store.getters.isLogin = false
    const next = jest.fn()

    guard({ meta: { requiresAuth: false }, fullPath: '/login' }, {}, next)

    expect(next).toHaveBeenCalledWith()
  })

  it('redirects to login when not authenticated and route requires auth', () => {
    store.getters.isLogin = false
    const next = jest.fn()

    guard({ meta: { requiresAuth: true }, fullPath: '/dashboard' }, {}, next)

    expect(next).toHaveBeenCalledWith({ path: '/login', query: { redirect: '/dashboard' } })
  })

  it('allows authenticated user to access auth-required route without permission meta', () => {
    store.getters.isLogin = true
    const next = jest.fn()

    guard({ meta: { requiresAuth: true } }, {}, next)

    expect(next).toHaveBeenCalledWith()
  })

  it('redirects to /403 when lacks permission', () => {
    store.getters.isLogin = true
    store.getters.hasPermission.mockReturnValue(false)
    const next = jest.fn()

    guard({ meta: { requiresAuth: true, permission: 'role.view' } }, {}, next)

    expect(next).toHaveBeenCalledWith({ path: '/403' })
  })

  it('allows access when has required permission', () => {
    store.getters.isLogin = true
    store.getters.hasPermission.mockReturnValue(true)
    const next = jest.fn()

    guard({ meta: { requiresAuth: true, permission: 'role.view' } }, {}, next)

    expect(next).toHaveBeenCalledWith()
  })

  it('sets document title based on route meta', () => {
    const next = jest.fn()
    const to = { meta: { title: '角色管理', requiresAuth: false } }

    guard(to, {}, next)

    expect(document.title).toBe('角色管理 - 电商订单库存后台')
  })

  it('sets default document title when no meta title', () => {
    const next = jest.fn()
    const to = { meta: {} }

    guard(to, {}, next)

    expect(document.title).toBe('电商订单库存后台')
  })

  it('preserves redirect path in query when redirecting to login', () => {
    store.getters.isLogin = false
    const next = jest.fn()

    guard({ meta: { requiresAuth: true }, fullPath: '/roles' }, {}, next)

    expect(next).toHaveBeenCalledWith({ path: '/login', query: { redirect: '/roles' } })
  })

  it('skips auth check for non-auth routes', () => {
    store.getters.isLogin = false
    const next = jest.fn()

    guard({ meta: { requiresAuth: false }, fullPath: '/login' }, {}, next)

    expect(next).toHaveBeenCalledWith()
  })

  it('skips permission check when no permission meta', () => {
    store.getters.isLogin = true
    store.getters.hasPermission.mockReturnValue(false)
    const next = jest.fn()

    guard({ meta: { requiresAuth: true } }, {}, next)

    expect(next).toHaveBeenCalledWith()
    expect(store.getters.hasPermission).not.toHaveBeenCalled()
  })
})
