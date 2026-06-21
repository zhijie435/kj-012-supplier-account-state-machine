<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="relative">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="搜索订单号、供应商、分销商..."
                        class="w-80 pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"
                    />
                    <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <select
                    v-model="filterStatus"
                    class="px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white"
                >
                    <option value="">全部状态</option>
                    <option value="pending">待确认</option>
                    <option value="confirmed">已确认</option>
                    <option value="processing">处理中</option>
                    <option value="shipped">已发货</option>
                    <option value="delivered">已送达</option>
                    <option value="completed">已完成</option>
                    <option value="cancelled">已取消</option>
                    <option value="refunded">已退款</option>
                    <option value="rejected">已拒绝</option>
                </select>
                <select
                    v-model="filterPaymentStatus"
                    class="px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white"
                >
                    <option value="">全部付款状态</option>
                    <option value="unpaid">未付款</option>
                    <option value="partial">部分付款</option>
                    <option value="paid">已付款</option>
                </select>
                <select
                    v-model="filterType"
                    class="px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white"
                >
                    <option value="">全部类型</option>
                    <option value="distributor_order">分销商订单</option>
                    <option value="agent_order">代理订单</option>
                </select>
            </div>
            <button
                v-if="auth.can('order.create')"
                @click="openCreateModal"
                class="px-4 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors flex items-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                新建订单
            </button>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">订单号</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">供应商</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">分销商</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">金额</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">付款状态</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">订单状态</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">创建时间</th>
                        <th class="text-right text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-if="loading" class="text-center">
                        <td colspan="8" class="px-6 py-12 text-gray-500">加载中...</td>
                    </tr>
                    <tr v-else-if="!orders.length" class="text-center">
                        <td colspan="8" class="px-6 py-12 text-gray-500">暂无数据</td>
                    </tr>
                    <tr v-for="order in orders" :key="order.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="font-mono text-sm font-medium text-gray-900">{{ order.order_no }}</div>
                            <div class="text-xs text-gray-500">{{ typeLabel(order.type) }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            {{ order.supplier?.name || '-' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            {{ order.distributor?.name || '-' }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">¥{{ formatNumber(order.total) }}</div>
                            <div class="text-xs text-gray-500">已付: ¥{{ formatNumber(order.paid_amount) }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span :class="paymentStatusBadge(order.payment_status)">
                                {{ paymentStatusLabel(order.payment_status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span :class="statusBadge(order.status)">
                                {{ statusLabel(order.status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ formatDate(order.created_at) }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button
                                    @click="viewDetail(order)"
                                    class="text-sm text-gray-600 hover:text-gray-700"
                                >
                                    详情
                                </button>
                                <button
                                    v-if="auth.can('order.approve') && order.status === 'pending'"
                                    @click="approveOrder(order)"
                                    class="text-sm text-green-600 hover:text-green-700"
                                >
                                    确认
                                </button>
                                <button
                                    v-if="auth.can('order.edit') && canTransition(order, 'processing')"
                                    @click="updateOrderStatus(order, 'processing')"
                                    class="text-sm text-blue-600 hover:text-blue-700"
                                >
                                    处理
                                </button>
                                <button
                                    v-if="auth.can('order.ship') && canTransition(order, 'shipped')"
                                    @click="updateOrderStatus(order, 'shipped')"
                                    class="text-sm text-purple-600 hover:text-purple-700"
                                >
                                    发货
                                </button>
                                <button
                                    v-if="auth.can('order.edit') && canTransition(order, 'delivered')"
                                    @click="updateOrderStatus(order, 'delivered')"
                                    class="text-sm text-cyan-600 hover:text-cyan-700"
                                >
                                    送达
                                </button>
                                <button
                                    v-if="auth.can('order.edit') && canTransition(order, 'completed')"
                                    @click="updateOrderStatus(order, 'completed')"
                                    class="text-sm text-green-600 hover:text-green-700"
                                >
                                    完成
                                </button>
                                <button
                                    v-if="auth.can('order.edit') && canTransition(order, 'cancelled')"
                                    @click="cancelOrder(order)"
                                    class="text-sm text-red-600 hover:text-red-700"
                                >
                                    取消
                                </button>
                                <button
                                    v-if="auth.can('order.edit') && !isTerminal(order.status)"
                                    @click="openEditModal(order)"
                                    class="text-sm text-indigo-600 hover:text-indigo-700"
                                >
                                    编辑
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-if="total > perPage" class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                <div class="text-sm text-gray-500">
                    共 {{ total }} 条记录
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="page > 1 && loadOrders(page - 1)"
                        :disabled="page <= 1"
                        class="px-3 py-1.5 rounded border border-gray-300 text-sm hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        上一页
                    </button>
                    <span class="text-sm text-gray-700">第 {{ page }} 页</span>
                    <button
                        @click="page * perPage < total && loadOrders(page + 1)"
                        :disabled="page * perPage >= total"
                        class="px-3 py-1.5 rounded border border-gray-300 text-sm hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        下一页
                    </button>
                </div>
            </div>
        </div>

        <div v-if="showDetailModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl w-full max-w-3xl max-h-[90vh] overflow-auto">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">订单详情</h3>
                    <button @click="closeDetailModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <div class="text-sm text-gray-500">订单号</div>
                            <div class="font-mono text-sm font-medium text-gray-900 mt-1">{{ currentOrder?.order_no }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">订单类型</div>
                            <div class="text-sm text-gray-900 mt-1">{{ typeLabel(currentOrder?.type) }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">供应商</div>
                            <div class="text-sm text-gray-900 mt-1">{{ currentOrder?.supplier?.name || '-' }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">分销商</div>
                            <div class="text-sm text-gray-900 mt-1">{{ currentOrder?.distributor?.name || '-' }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">订单状态</div>
                            <div class="mt-1">
                                <span :class="statusBadge(currentOrder?.status)">{{ statusLabel(currentOrder?.status) }}</span>
                            </div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">付款状态</div>
                            <div class="mt-1">
                                <span :class="paymentStatusBadge(currentOrder?.payment_status)">{{ paymentStatusLabel(currentOrder?.payment_status) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 pt-6">
                        <h4 class="font-medium text-gray-900 mb-4">商品明细</h4>
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">商品</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">单价</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">数量</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">小计</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="item in currentOrder?.items || []" :key="item.id">
                                    <td class="px-4 py-3 text-gray-900">{{ item.product?.name || item.product_name }}</td>
                                    <td class="px-4 py-3 text-right text-gray-700">¥{{ formatNumber(item.unit_price) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-700">{{ item.quantity }}</td>
                                    <td class="px-4 py-3 text-right font-medium text-gray-900">¥{{ formatNumber(item.unit_price * item.quantity) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t border-gray-200 pt-6">
                        <div class="flex justify-end">
                            <div class="w-64 space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">小计</span>
                                    <span class="text-gray-900">¥{{ formatNumber(currentOrder?.subtotal) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">税费</span>
                                    <span class="text-gray-900">¥{{ formatNumber(currentOrder?.tax) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">运费</span>
                                    <span class="text-gray-900">¥{{ formatNumber(currentOrder?.shipping) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">折扣</span>
                                    <span class="text-gray-900">-¥{{ formatNumber(currentOrder?.discount) }}</span>
                                </div>
                                <div class="flex justify-between pt-2 border-t border-gray-200 font-medium">
                                    <span class="text-gray-900">总计</span>
                                    <span class="text-gray-900">¥{{ formatNumber(currentOrder?.total) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="currentOrder?.shipping_address" class="border-t border-gray-200 pt-6">
                        <div class="text-sm text-gray-500">收货地址</div>
                        <div class="text-sm text-gray-900 mt-1">{{ currentOrder?.shipping_address }}</div>
                    </div>

                    <div v-if="currentOrder?.remark" class="border-t border-gray-200 pt-6">
                        <div class="text-sm text-gray-500">备注</div>
                        <div class="text-sm text-gray-900 mt-1">{{ currentOrder?.remark }}</div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                        <button type="button" @click="closeDetailModal" class="px-4 py-2 rounded-lg border border-gray-300 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                            关闭
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl w-full max-w-3xl max-h-[90vh] overflow-auto">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">{{ isEdit ? '编辑订单' : '新建订单' }}</h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <form @submit.prevent="submitForm" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">订单类型 <span class="text-red-500">*</span></label>
                            <select v-model="form.type" required class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white">
                                <option value="distributor_order">分销商订单</option>
                                <option value="agent_order">代理订单</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">供应商 <span class="text-red-500">*</span></label>
                            <select v-model="form.supplier_id" required class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white">
                                <option value="">请选择</option>
                                <option v-for="sup in suppliers" :key="sup.id" :value="sup.id">{{ sup.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">分销商 <span class="text-red-500">*</span></label>
                            <select v-model="form.distributor_id" required class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white">
                                <option value="">请选择</option>
                                <option v-for="dist in distributors" :key="dist.id" :value="dist.id">{{ dist.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700">商品明细 <span class="text-red-500">*</span></label>
                            <button type="button" @click="addItem" class="text-sm text-indigo-600 hover:text-indigo-700">+ 添加商品</button>
                        </div>
                        <div class="space-y-2">
                            <div v-for="(item, index) in form.items" :key="index" class="grid grid-cols-4 gap-2 items-end">
                                <div>
                                    <select v-model="item.product_id" required class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white">
                                        <option value="">请选择商品</option>
                                        <option v-for="prod in products" :key="prod.id" :value="prod.id">{{ prod.name }} (¥{{ prod.wholesale_price }})</option>
                                    </select>
                                </div>
                                <div>
                                    <input v-model.number="item.unit_price" type="number" step="0.01" min="0" required placeholder="单价" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                                </div>
                                <div>
                                    <input v-model.number="item.quantity" type="number" min="1" required placeholder="数量" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-gray-500">¥{{ formatNumber((item.unit_price || 0) * (item.quantity || 0)) }}</span>
                                    <button type="button" @click="removeItem(index)" class="text-red-500 hover:text-red-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">税费</label>
                            <input v-model.number="form.tax" type="number" step="0.01" min="0" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">运费</label>
                            <input v-model.number="form.shipping" type="number" step="0.01" min="0" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">折扣</label>
                            <input v-model.number="form.discount" type="number" step="0.01" min="0" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">收货地址</label>
                            <input v-model="form.shipping_address" type="text" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">账单地址</label>
                            <input v-model="form.billing_address" type="text" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">备注</label>
                        <textarea v-model="form.remark" rows="3" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"></textarea>
                    </div>

                    <div class="flex justify-end">
                        <div class="text-right">
                            <div class="text-sm text-gray-500">订单总额</div>
                            <div class="text-xl font-bold text-gray-900">¥{{ formatNumber(calculateTotal) }}</div>
                        </div>
                    </div>

                    <div v-if="submitError" class="p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
                        {{ submitError }}
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" @click="closeModal" class="px-4 py-2 rounded-lg border border-gray-300 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                            取消
                        </button>
                        <button type="submit" :disabled="submitting" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700 transition-colors disabled:opacity-50">
                            {{ submitting ? '保存中...' : '保存' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref, onMounted, watch, computed } from 'vue';
import { useAuthStore } from '../../stores/auth';
import api from '../../api/axios';

const auth = useAuthStore();

const orders = ref([]);
const suppliers = ref([]);
const distributors = ref([]);
const products = ref([]);
const loading = ref(false);
const search = ref('');
const filterStatus = ref('');
const filterPaymentStatus = ref('');
const filterType = ref('');
const page = ref(1);
const perPage = ref(15);
const total = ref(0);

const showModal = ref(false);
const isEdit = ref(false);
const editingId = ref(null);
const submitting = ref(false);
const submitError = ref('');

const showDetailModal = ref(false);
const currentOrder = ref(null);

const defaultForm = () => ({
    type: 'distributor_order',
    supplier_id: null,
    distributor_id: null,
    items: [{ product_id: null, unit_price: 0, quantity: 1 }],
    tax: 0,
    discount: 0,
    shipping: 0,
    shipping_address: '',
    billing_address: '',
    remark: '',
});

const form = reactive(defaultForm());

const transitions = {
    pending: ['confirmed', 'cancelled', 'rejected'],
    confirmed: ['processing', 'shipped', 'cancelled'],
    processing: ['shipped', 'cancelled', 'confirmed'],
    shipped: ['delivered', 'cancelled', 'processing', 'confirmed'],
    delivered: ['completed', 'refunded', 'shipped'],
    cancelled: [],
    completed: [],
    refunded: [],
    rejected: [],
};

const terminalStatuses = ['cancelled', 'completed', 'refunded', 'rejected'];

const typeLabel = (t) => ({ distributor_order: '分销商订单', agent_order: '代理订单' }[t] || t);
const statusLabel = (s) => ({ pending: '待确认', confirmed: '已确认', processing: '处理中', shipped: '已发货', delivered: '已送达', completed: '已完成', cancelled: '已取消', refunded: '已退款', rejected: '已拒绝' }[s] || s);
const statusBadge = (s) => {
    const map = {
        pending: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700',
        confirmed: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700',
        processing: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700',
        shipped: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700',
        delivered: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-cyan-100 text-cyan-700',
        completed: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700',
        cancelled: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700',
        refunded: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-rose-100 text-rose-700',
        rejected: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700',
    };
    return map[s] || '';
};
const paymentStatusLabel = (s) => ({ unpaid: '未付款', partial: '部分付款', paid: '已付款' }[s] || s);
const paymentStatusBadge = (s) => {
    const map = {
        unpaid: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700',
        partial: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700',
        paid: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700',
    };
    return map[s] || '';
};
const formatNumber = (num) => {
    if (num === null || num === undefined) return '0.00';
    return Number(num).toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
const formatDate = (d) => d ? new Date(d).toLocaleString('zh-CN') : '-';

const calculateTotal = computed(() => {
    const itemsTotal = form.items.reduce((sum, item) => sum + (item.unit_price || 0) * (item.quantity || 0), 0);
    return itemsTotal + (form.tax || 0) + (form.shipping || 0) - (form.discount || 0);
});

const canTransition = (order, target) => {
    const allowed = transitions[order.status] || [];
    return allowed.includes(target);
};

const isTerminal = (status) => terminalStatuses.includes(status);

const loadOrders = async (p = 1) => {
    loading.value = true;
    page.value = p;
    try {
        const params = { page: p, per_page: perPage.value };
        if (search.value) params.search = search.value;
        if (filterStatus.value) params.status = filterStatus.value;
        if (filterPaymentStatus.value) params.payment_status = filterPaymentStatus.value;
        if (filterType.value) params.type = filterType.value;
        const { data } = await api.get('/orders', { params });
        orders.value = data.data;
        total.value = data.total;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

const loadSuppliers = async () => {
    try {
        const { data } = await api.get('/suppliers', { params: { per_page: 100, status: 'active' } });
        suppliers.value = data.data || [];
    } catch (e) {
        console.error(e);
    }
};

const loadDistributors = async () => {
    try {
        const { data } = await api.get('/distributors', { params: { per_page: 100, status: 'active' } });
        distributors.value = data.data || [];
    } catch (e) {
        console.error(e);
    }
};

const loadProducts = async () => {
    try {
        const { data } = await api.get('/products', { params: { per_page: 200, status: 'on_sale' } });
        products.value = data.data || [];
    } catch (e) {
        console.error(e);
    }
};

let debounceTimer = null;
watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => loadOrders(1), 300);
});
watch([filterStatus, filterPaymentStatus, filterType], () => loadOrders(1));

const addItem = () => {
    form.items.push({ product_id: null, unit_price: 0, quantity: 1 });
};

const removeItem = (index) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    }
};

const openCreateModal = () => {
    Object.assign(form, defaultForm());
    isEdit.value = false;
    editingId.value = null;
    submitError.value = '';
    loadSuppliers();
    loadDistributors();
    loadProducts();
    showModal.value = true;
};

const openEditModal = (order) => {
    Object.assign(form, {
        type: order.type,
        supplier_id: order.supplier_id,
        distributor_id: order.distributor_id,
        items: order.items?.map(item => ({
            product_id: item.product_id,
            unit_price: item.unit_price,
            quantity: item.quantity,
        })) || [{ product_id: null, unit_price: 0, quantity: 1 }],
        tax: order.tax || 0,
        discount: order.discount || 0,
        shipping: order.shipping || 0,
        shipping_address: order.shipping_address || '',
        billing_address: order.billing_address || '',
        remark: order.remark || '',
    });
    isEdit.value = true;
    editingId.value = order.id;
    submitError.value = '';
    loadSuppliers();
    loadDistributors();
    loadProducts();
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

const submitForm = async () => {
    submitting.value = true;
    submitError.value = '';
    try {
        if (isEdit.value) {
            await api.put(`/orders/${editingId.value}`, form);
        } else {
            await api.post('/orders', form);
        }
        closeModal();
        loadOrders(page.value);
    } catch (e) {
        submitError.value = e.response?.data?.message || '保存失败';
    } finally {
        submitting.value = false;
    }
};

const viewDetail = async (order) => {
    try {
        const { data } = await api.get(`/orders/${order.id}`);
        currentOrder.value = data.data || data;
        showDetailModal.value = true;
    } catch (e) {
        alert(e.response?.data?.message || '加载失败');
    }
};

const closeDetailModal = () => {
    showDetailModal.value = false;
    currentOrder.value = null;
};

const approveOrder = async (order) => {
    if (!confirm(`确认订单「${order.order_no}」吗？`)) return;
    try {
        await api.post(`/orders/${order.id}/approve`);
        loadOrders(page.value);
    } catch (e) {
        alert(e.response?.data?.message || '操作失败');
    }
};

const updateOrderStatus = async (order, status) => {
    if (!confirm(`确定将订单「${order.order_no}」状态更新为「${statusLabel(status)}」吗？`)) return;
    try {
        await api.put(`/orders/${order.id}/status`, { status });
        loadOrders(page.value);
    } catch (e) {
        alert(e.response?.data?.message || '操作失败');
    }
};

const cancelOrder = async (order) => {
    if (!confirm(`确定取消订单「${order.order_no}」吗？`)) return;
    try {
        await api.put(`/orders/${order.id}/status`, { status: 'cancelled' });
        loadOrders(page.value);
    } catch (e) {
        alert(e.response?.data?.message || '操作失败');
    }
};

onMounted(() => {
    loadOrders();
});
</script>
