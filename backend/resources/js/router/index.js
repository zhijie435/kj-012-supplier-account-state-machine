import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const routes = [
    {
        path: '/login',
        name: 'login',
        component: () => import('../views/Login.vue'),
        meta: { guest: true },
    },
    {
        path: '/',
        component: () => import('../layouts/AppLayout.vue'),
        meta: { auth: true },
        children: [
            {
                path: '',
                name: 'dashboard',
                component: () => import('../views/Dashboard.vue'),
            },
            {
                path: 'suppliers',
                name: 'suppliers.index',
                component: () => import('../views/suppliers/Index.vue'),
                meta: { permission: 'supplier.view' },
            },
            {
                path: 'distributors',
                name: 'distributors.index',
                component: () => import('../views/distributors/Index.vue'),
                meta: { permission: 'distributor.view' },
            },
            {
                path: 'products',
                name: 'products.index',
                component: () => import('../views/products/Index.vue'),
                meta: { permission: 'product.view' },
            },
            {
                path: 'orders',
                name: 'orders.index',
                component: () => import('../views/orders/Index.vue'),
                meta: { permission: 'order.view' },
            },
            {
                path: 'categories',
                name: 'categories.index',
                component: () => import('../views/categories/Index.vue'),
            },
            {
                path: 'inventory',
                name: 'inventory.index',
                component: () => import('../views/inventory/Index.vue'),
                meta: { permission: 'inventory.view' },
            },
            {
                path: 'payments',
                name: 'payments.index',
                component: () => import('../views/payments/Index.vue'),
                meta: { permission: 'payment.view' },
            },
            {
                path: 'recharge',
                name: 'recharge.index',
                component: () => import('../views/recharge/Index.vue'),
                meta: { permission: 'payment.create', title: '余额充值' },
            },
            {
                path: 'wallet',
                name: 'wallet.balance',
                component: () => import('../views/wallet/Balance.vue'),
                meta: { title: '钱包余额' },
            },
            {
                path: 'wallet/transactions',
                name: 'wallet.transactions',
                component: () => import('../views/wallet/Transactions.vue'),
                meta: { title: '交易记录' },
            },
            {
                path: 'withdraw',
                name: 'withdraw.index',
                component: () => import('../views/withdraw/Index.vue'),
                meta: { permission: 'payment.create', title: '余额提现' },
            },
            {
                path: 'withdraw/list',
                name: 'withdraw.list',
                component: () => import('../views/withdraw/List.vue'),
                meta: { title: '提现记录' },
            },
            {
                path: 'withdraw-accounts',
                name: 'withdraw-accounts.index',
                component: () => import('../views/withdraw-accounts/Index.vue'),
                meta: { title: '提现账户管理' },
            },
            {
                path: 'withdraw-config',
                name: 'withdraw-config.index',
                component: () => import('../views/withdraw-config/Index.vue'),
                meta: { permission: 'user.manage', title: '提现规则配置' },
            },
            {
                path: 'withdraw-methods',
                name: 'withdraw-methods.index',
                component: () => import('../views/withdraw-methods/Index.vue'),
                meta: { permission: 'user.manage', title: '提现方式管理' },
            },
            {
                path: 'withdraw-rules',
                name: 'withdraw-rules.index',
                component: () => import('../views/withdraw-rules/Index.vue'),
                meta: { permission: 'user.manage', title: '提现规则管理(旧)' },
            },
            {
                path: 'withdrawal-rules',
                name: 'withdrawal-rules.index',
                component: () => import('../views/withdrawal-rules/Index.vue'),
                meta: { permission: 'user.manage', title: '提现规则管理' },
            },
            {
                path: 'users',
                name: 'users.index',
                component: () => import('../views/users/Index.vue'),
                meta: { permission: 'user.manage', title: '用户管理' },
            },
        ],
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to, from, next) => {
    const auth = useAuthStore();

    if (to.meta.guest && auth.isAuthenticated) {
        return next({ name: 'dashboard' });
    }

    if (to.meta.auth && !auth.isAuthenticated) {
        return next({ name: 'login' });
    }

    if (to.meta.permission && !auth.can(to.meta.permission)) {
        return next({ name: 'dashboard' });
    }

    next();
});

export default router;
