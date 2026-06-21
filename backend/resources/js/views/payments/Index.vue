<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="relative">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="搜索支付单号、交易号、关联订单..."
                        class="w-80 pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"
                    />
                    <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <select
                    v-model="filterType"
                    class="px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white"
                >
                    <option value="">全部类型</option>
                    <option value="escrow_deposit">托管存款</option>
                    <option value="escrow_release">托管释放</option>
                    <option value="platform_fee">平台费用</option>
                    <option value="refund">退款</option>
                    <option value="recharge">余额充值</option>
                </select>
                <select
                    v-model="filterStatus"
                    class="px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white"
                >
                    <option value="">全部状态</option>
                    <option value="pending">待处理</option>
                    <option value="completed">已完成</option>
                    <option value="failed">已失败</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button
                    v-if="auth.can('payment.create')"
                    @click="openCreateModal"
                    class="px-4 py-2.5 rounded-lg border border-gray-300 text-sm text-gray-700 hover:bg-gray-50 transition-colors flex items-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    新建支付
                </button>
                <button
                    v-if="auth.can('payment.create')"
                    @click="openRechargeModal"
                    class="px-4 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors flex items-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    账户充值
                </button>
            </div>
        </div>

        <div v-if="failedCount > 0" class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <div class="font-medium text-amber-900">有 {{ failedCount }} 笔支付失败需要处理</div>
                    <div class="text-sm text-amber-700">点击操作栏的"重试"按钮重新发起支付</div>
                </div>
            </div>
            <button
                @click="filterStatus = 'failed'"
                class="px-3 py-1.5 rounded-lg bg-amber-600 text-white text-sm hover:bg-amber-700 transition-colors"
            >
                查看全部
            </button>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">支付单号</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">类型</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">关联订单</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">分销商</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">金额</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">支付方式</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">状态</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">创建时间</th>
                        <th class="text-right text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-if="loading" class="text-center">
                        <td colspan="9" class="px-6 py-12 text-gray-500">加载中...</td>
                    </tr>
                    <tr v-else-if="!payments.length" class="text-center">
                        <td colspan="9" class="px-6 py-12 text-gray-500">暂无数据</td>
                    </tr>
                    <tr v-for="payment in payments" :key="payment.id" :class="['hover:bg-gray-50', payment.is_insufficient_balance ? 'bg-amber-50/50' : '']">
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900 text-sm">{{ payment.payment_no }}</div>
                            <div v-if="payment.transaction_no" class="text-xs text-gray-500">{{ payment.transaction_no }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span :class="typeBadge(payment.type)">
                                {{ payment.type_label }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            {{ payment.order?.order_no || '-' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            <div>{{ payment.distributor?.name || '-' }}</div>
                            <div v-if="payment.distributor?.balance !== null && payment.distributor?.balance !== undefined" class="text-xs text-gray-500">
                                余额: ¥{{ formatNumber(payment.distributor.balance) }}
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900 text-sm">
                                <span :class="payment.type === 'refund' || payment.type === 'escrow_release' ? 'text-red-600' : 'text-green-600'">
                                    {{ payment.type === 'refund' || payment.type === 'escrow_release' ? '-' : '+' }}¥{{ formatNumber(payment.amount) }}
                                </span>
                            </div>
                            <div v-if="payment.fee_amount > 0" class="text-xs text-gray-500">
                                手续费: ¥{{ formatNumber(payment.fee_amount) }}
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            {{ methodLabel(payment.method) }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <span :class="statusBadge(payment.status)">
                                    {{ payment.status_label }}
                                </span>
                                <div v-if="payment.is_insufficient_balance" class="group relative">
                                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                                        余额不足
                                    </div>
                                </div>
                            </div>
                            <div v-if="payment.fail_reason" class="text-xs text-red-500 mt-1">
                                {{ failReasonLabel(payment.fail_reason) }}
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ formatDate(payment.created_at) }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button
                                    v-if="payment.can_retry && auth.can('payment.create')"
                                    @click="retryPayment(payment)"
                                    :disabled="retryingId === payment.id"
                                    class="text-sm text-indigo-600 hover:text-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1"
                                >
                                    <svg v-if="retryingId === payment.id" class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    重试
                                </button>
                                <button
                                    v-if="payment.is_insufficient_balance && auth.can('payment.create')"
                                    @click="rechargeForPayment(payment)"
                                    class="text-sm text-amber-600 hover:text-amber-700 flex items-center gap-1"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                    充值
                                </button>
                                <button
                                    v-if="payment.is_insufficient_balance && auth.can('payment.create')"
                                    @click="showInsufficientBalance(payment)"
                                    class="text-sm text-gray-600 hover:text-gray-700"
                                >
                                    详情
                                </button>
                                <button
                                    v-if="payment.status === 'completed' && payment.type === 'escrow_deposit' && auth.can('payment.settle')"
                                    @click="openSettleModal(payment)"
                                    class="text-sm text-green-600 hover:text-green-700"
                                >
                                    结算
                                </button>
                                <button
                                    v-if="payment.status === 'completed' && payment.type === 'escrow_deposit' && auth.can('payment.refund')"
                                    @click="openRefundModal(payment)"
                                    class="text-sm text-red-600 hover:text-red-700"
                                >
                                    退款
                                </button>
                                <button
                                    v-if="auth.can('payment.delete')"
                                    @click="deletePayment(payment)"
                                    class="text-sm text-red-600 hover:text-red-700"
                                >
                                    删除
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
                        @click="page > 1 && loadPayments(page - 1)"
                        :disabled="page <= 1"
                        class="px-3 py-1.5 rounded border border-gray-300 text-sm hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        上一页
                    </button>
                    <span class="text-sm text-gray-700">第 {{ page }} 页</span>
                    <button
                        @click="page * perPage < total && loadPayments(page + 1)"
                        :disabled="page * perPage >= total"
                        class="px-3 py-1.5 rounded border border-gray-300 text-sm hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        下一页
                    </button>
                </div>
            </div>
        </div>

        <div v-if="showCreateModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="closeCreateModal">
            <div class="bg-white rounded-xl w-full max-w-2xl max-h-[90vh] overflow-auto">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">新建支付</h3>
                    <button @click="closeCreateModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <form @submit.prevent="submitCreate" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">支付类型 <span class="text-red-500">*</span></label>
                            <select v-model="createForm.type" required class="w-full px-3 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white">
                                <option value="escrow_deposit">托管存款</option>
                                <option value="platform_fee">平台费用</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">关联订单</label>
                            <select v-model="createForm.order_id" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white">
                                <option value="">无</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">分销商</label>
                            <select v-model="createForm.distributor_id" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white">
                                <option value="">无</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">金额 <span class="text-red-500">*</span></label>
                            <input v-model.number="createForm.amount" type="number" step="0.01" min="0.01" required class="w-full px-3 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">支付方式 <span class="text-red-500">*</span></label>
                            <select v-model="createForm.method" required class="w-full px-3 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white">
                                <option value="cash">现金</option>
                                <option value="bank_transfer">银行转账</option>
                                <option value="alipay">支付宝</option>
                                <option value="wechat">微信支付</option>
                                <option value="credit">赊账</option>
                                <option value="other">其他</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">支付日期 <span class="text-red-500">*</span></label>
                            <input v-model="createForm.payment_date" type="date" required class="w-full px-3 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">交易单号</label>
                        <input v-model="createForm.transaction_no" type="text" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">备注</label>
                        <textarea v-model="createForm.remark" rows="2" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm resize-none"></textarea>
                    </div>
                    <div v-if="createError" class="p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
                        {{ createError }}
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" @click="closeCreateModal" class="px-4 py-2.5 rounded-lg border border-gray-300 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                            取消
                        </button>
                        <button type="submit" :disabled="creating" class="px-4 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors disabled:opacity-50">
                            {{ creating ? '保存中...' : '保存' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <RechargeModal
            v-model:visible="showRechargeModal"
            :distributor-id="rechargeDistributorId"
            :default-amount="rechargeDefaultAmount"
            :minimum-amount="rechargeMinimumAmount"
            @success="handleRechargeSuccess"
        />

        <InsufficientBalanceModal
            v-model:visible="showInsufficientModal"
            :current-balance="insufficientData.current_balance"
            :required-amount="insufficientData.required_amount"
            :deficit="insufficientData.deficit"
            :failed-payment-id="insufficientData.payment_id"
            :distributor-id="insufficientData.distributor_id"
            @close="showInsufficientModal = false"
            @success="handleRechargeSuccess"
            @retry-success="handleRetrySuccess"
        />

        <div v-if="showSettleModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="closeSettleModal">
            <div class="bg-white rounded-xl w-full max-w-md max-h-[90vh] overflow-auto">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">结算支付</h3>
                    <button @click="closeSettleModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <form @submit.prevent="submitSettle" class="p-6 space-y-4">
                    <div class="p-4 bg-green-50 rounded-lg border border-green-200">
                        <div class="text-sm text-green-700">
                            结算后，托管资金将释放给供应商。请确认操作无误。
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">备注</label>
                        <textarea v-model="settleForm.remark" rows="2" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm resize-none"></textarea>
                    </div>
                    <div v-if="settleError" class="p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
                        {{ settleError }}
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" @click="closeSettleModal" class="px-4 py-2.5 rounded-lg border border-gray-300 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                            取消
                        </button>
                        <button type="submit" :disabled="settling" class="px-4 py-2.5 rounded-lg bg-green-600 text-white text-sm font-medium hover:bg-green-700 transition-colors disabled:opacity-50">
                            {{ settling ? '处理中...' : '确认结算' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="showRefundModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="closeRefundModal">
            <div class="bg-white rounded-xl w-full max-w-md max-h-[90vh] overflow-auto">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">退款支付</h3>
                    <button @click="closeRefundModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <form @submit.prevent="submitRefund" class="p-6 space-y-4">
                    <div class="p-4 bg-amber-50 rounded-lg border border-amber-200">
                        <div class="text-sm text-amber-700">
                            退款后，资金将退还给分销商。请确认操作无误。
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">退款金额 <span class="text-red-500">*</span></label>
                        <input v-model.number="refundForm.amount" type="number" step="0.01" min="0.01" required class="w-full px-3 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">退款原因 <span class="text-red-500">*</span></label>
                        <input v-model="refundForm.reason" type="text" required placeholder="请输入退款原因" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">备注</label>
                        <textarea v-model="refundForm.remark" rows="2" class="w-full px-3 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm resize-none"></textarea>
                    </div>
                    <div v-if="refundError" class="p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
                        {{ refundError }}
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" @click="closeRefundModal" class="px-4 py-2.5 rounded-lg border border-gray-300 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                            取消
                        </button>
                        <button type="submit" :disabled="refunding" class="px-4 py-2.5 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700 transition-colors disabled:opacity-50">
                            {{ refunding ? '处理中...' : '确认退款' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref, watch, onMounted, computed } from 'vue';
import { useAuthStore } from '../../stores/auth';
import paymentApi from '../../api/payment';
import RechargeModal from '../../components/RechargeModal.vue';
import InsufficientBalanceModal from '../../components/InsufficientBalanceModal.vue';

const auth = useAuthStore();

const payments = ref([]);
const loading = ref(false);
const search = ref('');
const filterType = ref('');
const filterStatus = ref('');
const page = ref(1);
const perPage = ref(15);
const total = ref(0);
const retryingId = ref(null);

const showCreateModal = ref(false);
const creating = ref(false);
const createError = ref('');

const showRechargeModal = ref(false);
const rechargeDistributorId = ref(null);
const rechargeDefaultAmount = ref(null);
const rechargeMinimumAmount = ref(0);

const showInsufficientModal = ref(false);
const insufficientData = reactive({
    current_balance: 0,
    required_amount: 0,
    deficit: 0,
    payment_id: null,
    distributor_id: null,
});

const defaultCreateForm = () => ({
    type: 'escrow_deposit',
    order_id: null,
    distributor_id: null,
    amount: 0,
    method: 'bank_transfer',
    payment_date: new Date().toISOString().split('T')[0],
    transaction_no: '',
    remark: '',
});

const createForm = reactive(defaultCreateForm());

const failedCount = computed(() => {
    return payments.value.filter(p => p.status === 'failed').length;
});

const typeLabel = (t) => {
    const map = {
        escrow_deposit: '托管存款',
        escrow_release: '托管释放',
        platform_fee: '平台费用',
        refund: '退款',
        recharge: '余额充值',
    };
    return map[t] || t;
};

const typeBadge = (t) => {
    const map = {
        escrow_deposit: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700',
        escrow_release: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700',
        platform_fee: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700',
        refund: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700',
        recharge: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700',
    };
    return map[t] || '';
};

const statusBadge = (s) => {
    const map = {
        pending: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700',
        completed: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700',
        failed: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700',
    };
    return map[s] || '';
};

const methodLabel = (m) => {
    const map = {
        cash: '现金',
        bank_transfer: '银行转账',
        alipay: '支付宝',
        wechat: '微信支付',
        credit: '赊账',
        other: '其他',
    };
    return map[m] || m;
};

const failReasonLabel = (r) => {
    const map = {
        INSUFFICIENT_BALANCE: '余额不足',
    };
    return map[r] || r;
};

const formatNumber = (num) => {
    if (num === null || num === undefined) return '0.00';
    return Number(num).toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatDate = (d) => d ? new Date(d).toLocaleString('zh-CN') : '-';

const loadPayments = async (p = 1) => {
    loading.value = true;
    page.value = p;
    try {
        const params = { page: p, per_page: perPage.value };
        if (search.value) params.search = search.value;
        if (filterType.value) params.type = filterType.value;
        if (filterStatus.value) params.status = filterStatus.value;
        const { data } = await paymentApi.list(params);
        payments.value = data.data;
        total.value = data.total;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

let debounceTimer = null;
watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => loadPayments(1), 300);
});
watch([filterType, filterStatus], () => loadPayments(1));

const openCreateModal = () => {
    Object.assign(createForm, defaultCreateForm());
    createError.value = '';
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
};

const submitCreate = async () => {
    creating.value = true;
    createError.value = '';
    try {
        await paymentApi.create(createForm);
        closeCreateModal();
        loadPayments(page.value);
    } catch (e) {
        if (e.response?.status === 402 && e.response?.data?.insufficient_balance) {
            const data = e.response.data;
            insufficientData.current_balance = data.current_balance;
            insufficientData.required_amount = createForm.amount;
            insufficientData.deficit = data.deficit;
            insufficientData.payment_id = data.data?.id;
            insufficientData.distributor_id = createForm.distributor_id;
            showInsufficientModal.value = true;
            closeCreateModal();
        } else {
            createError.value = e.response?.data?.message || '保存失败';
        }
    } finally {
        creating.value = false;
    }
};

const openRechargeModal = () => {
    rechargeDistributorId.value = null;
    rechargeDefaultAmount.value = null;
    rechargeMinimumAmount.value = 0;
    showRechargeModal.value = true;
};

const rechargeForPayment = (payment) => {
    rechargeDistributorId.value = payment.distributor_id;
    rechargeDefaultAmount.value = payment.amount;
    rechargeMinimumAmount.value = payment.amount;
    showRechargeModal.value = true;
};

const showInsufficientBalance = (payment) => {
    insufficientData.current_balance = payment.distributor?.balance || 0;
    insufficientData.required_amount = payment.amount;
    insufficientData.deficit = Math.max(0, payment.amount - (payment.distributor?.balance || 0));
    insufficientData.payment_id = payment.id;
    insufficientData.distributor_id = payment.distributor_id;
    showInsufficientModal.value = true;
};

const retryPayment = async (payment) => {
    if (retryingId.value) return;

    retryingId.value = payment.id;
    try {
        const { data } = await paymentApi.retry(payment.id);
        alert('支付重试成功');
        loadPayments(page.value);
    } catch (e) {
        if (e.response?.status === 402 && e.response?.data?.insufficient_balance) {
            const data = e.response.data;
            insufficientData.current_balance = data.current_balance;
            insufficientData.required_amount = payment.amount;
            insufficientData.deficit = data.deficit;
            insufficientData.payment_id = payment.id;
            insufficientData.distributor_id = payment.distributor_id;
            showInsufficientModal.value = true;
        } else {
            alert(e.response?.data?.message || '重试失败');
        }
    } finally {
        retryingId.value = null;
    }
};

const handleRechargeSuccess = (data) => {
    showInsufficientModal.value = false;
    loadPayments(page.value);
};

const handleRetrySuccess = (data) => {
    showInsufficientModal.value = false;
    loadPayments(page.value);
};

const showSettleModal = ref(false);
const settlingId = ref(null);
const settling = ref(false);
const settleError = ref('');
const settleForm = reactive({
    remark: '',
});

const showRefundModal = ref(false);
const refundingId = ref(null);
const refunding = ref(false);
const refundError = ref('');
const refundForm = reactive({
    amount: 0,
    reason: '',
    remark: '',
});

const openSettleModal = (payment) => {
    settlingId.value = payment.id;
    settleForm.remark = '';
    settleError.value = '';
    showSettleModal.value = true;
};

const closeSettleModal = () => {
    showSettleModal.value = false;
};

const submitSettle = async () => {
    settling.value = true;
    settleError.value = '';
    try {
        await paymentApi.settle(settlingId.value, settleForm);
        closeSettleModal();
        loadPayments(page.value);
    } catch (e) {
        settleError.value = e.response?.data?.message || '结算失败';
    } finally {
        settling.value = false;
    }
};

const openRefundModal = (payment) => {
    refundingId.value = payment.id;
    refundForm.amount = payment.amount;
    refundForm.reason = '';
    refundForm.remark = '';
    refundError.value = '';
    showRefundModal.value = true;
};

const closeRefundModal = () => {
    showRefundModal.value = false;
};

const submitRefund = async () => {
    refunding.value = true;
    refundError.value = '';
    try {
        await paymentApi.refund(refundingId.value, refundForm);
        closeRefundModal();
        loadPayments(page.value);
    } catch (e) {
        refundError.value = e.response?.data?.message || '退款失败';
    } finally {
        refunding.value = false;
    }
};

const deletePayment = async (payment) => {
    if (!confirm(`确定删除支付记录「${payment.payment_no}」吗？`)) return;
    try {
        await paymentApi.delete(payment.id);
        loadPayments(page.value);
    } catch (e) {
        alert(e.response?.data?.message || '删除失败');
    }
};

onMounted(() => {
    loadPayments();
});
</script>
