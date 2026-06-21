import Vue from 'vue'
import VueRouter from 'vue-router'
import store from '@/store'

Vue.use(VueRouter)

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/Login/index.vue'),
    meta: { title: '登录', requiresAuth: false }
  },
  {
    path: '/',
    redirect: '/dashboard'
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: () => import('@/views/Dashboard/index.vue'),
    meta: { title: '数据概览', requiresAuth: true }
  },
  {
    path: '/suppliers',
    name: 'SupplierManagement',
    component: () => import('@/views/Supplier/List.vue'),
    meta: { title: '供应商管理', requiresAuth: true, permission: 'supplier.view' }
  },
  {
    path: '/suppliers/:id',
    name: 'SupplierDetail',
    component: () => import('@/views/Supplier/Detail.vue'),
    meta: { title: '供应商详情', requiresAuth: true, permission: 'supplier.view' }
  },
  {
    path: '/suppliers/:id/status-logs',
    name: 'SupplierStatusLogs',
    component: () => import('@/views/Supplier/StatusLog.vue'),
    meta: { title: '状态变更日志', requiresAuth: true, permission: 'supplier.view' }
  },
  {
    path: '/products',
    name: 'ProductManagement',
    component: () => import('@/views/Product/Index.vue'),
    meta: { title: '商品管理', requiresAuth: true, permission: 'product.view' }
  },
  {
    path: '/product-costs',
    name: 'ProductCostManagement',
    component: () => import('@/views/ProductCost/Index.vue'),
    meta: { title: '商品成本管理', requiresAuth: true, permission: 'product.cost.view' }
  },
  {
    path: '/settlements',
    name: 'SettlementManagement',
    component: () => import('@/views/Settlement/Index.vue'),
    meta: { title: '结算分账', requiresAuth: true, permission: 'settlement.view' }
  },
  {
    path: '/roles',
    name: 'RoleManagement',
    component: () => import('@/views/Role/Index.vue'),
    meta: { title: '角色管理', requiresAuth: true, permission: 'role.view' }
  },
  {
    path: '/system/users',
    name: 'UserManagement',
    component: () => import('@/views/System/Users.vue'),
    meta: { title: '用户管理', requiresAuth: true, permission: 'user.view' }
  },
  {
    path: '/403',
    name: 'Forbidden',
    component: () => import('@/views/NotFound/index.vue'),
    meta: { title: '无权限' }
  },
  {
    path: '*',
    name: 'NotFound',
    component: () => import('@/views/NotFound/index.vue'),
    meta: { title: '页面不存在' }
  }
]

const router = new VueRouter({
  mode: 'history',
  base: import.meta.env.VITE_APP_BASE_URL || '/',
  routes
})

router.beforeEach((to, from, next) => {
  document.title = to.meta.title ? `${to.meta.title} - 内容审核标注平台` : '内容审核标注平台'

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
})

export default router
