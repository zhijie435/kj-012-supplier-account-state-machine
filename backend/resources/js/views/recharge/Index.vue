<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">余额充值</h1>
                <p class="mt-1 text-sm text-gray-500">为分销商账户充值，用于后续订单支付</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">当前余额</h3>
                    <div v-if="balanceInfo" class="space-y-4">
                        <div class="text-center">
                            <div class="text-4xl font-bold text-blue-600">
                                ¥{{ formatNumber(balanceInfo.balance) }}
                            </div>
                            <div class="mt-1 text-sm text-gray-500">可用余额</div>
                        </div>
                        <div class="border-t border-gray-100 pt-4 space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">信用额度</span>
                                <span class="font-medium text-gray-900">¥{{ formatNumber(balanceInfo.credit_limit) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">剩余可用信用</span>
                                <span class="font-medium text-green-600">¥{{ formatNumber(balanceInfo.available_credit) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">账户名称</span>
                                <span class="font-medium text-gray-900">{{ balanceInfo.distributor_name }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-8">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-600 border-t-transparent"></div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 p-6 mt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">快捷充值金额</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <button
                            v-for="amount in quickAmounts"
                            :key="amount"
                            @click="form.amount = amount"
                            :class="[
                                'py-3 px-4 rounded-lg border-2 font-medium transition-all',
                                form.amount === amount
                                    ? 'border-blue-600 bg-blue-50 text-blue-700'
                                    : 'border-gray-200 hover:border-gray-300 text-gray-700'
                            ]"
                        >
                            ¥{{ formatNumber(amount) }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">充值信息</h3>

                    <form @submit.prevent="handleRecharge" class="space-y-6">
                        <div v-if="isPlatform && !form.distributor_id" class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <p class="text-sm text-yellow-800">
                                <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                平台管理员需要先选择要充值的分销商
                            </p>
                        </div>

                        <div v-if="isPlatform" class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                选择分销商 <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="form.distributor_id"
                                class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                required
                            >
                                <option value="">请选择分销商</option>
                                <option v-for="d in distributors" :key="d.id" :value="d.id">
                                    {{ d.name }} (当前余额: ¥{{ formatNumber(d.balance) }})
                                </option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                充值金额 <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-lg">¥</span>
                                <input
                                    v-model.number="form.amount"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    placeholder="请输入充值金额"
                                    class="w-full rounded-lg border-gray-300 border pl-10 pr-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg"
                                    required
                                />
                            </div>
                            <p v-if="form.amount > 0" class="text-sm text-gray-500">
                                充值后余额将变为: <span class="font-semibold text-green-600">¥{{ formatNumber((balanceInfo?.balance || 0) + form.amount) }}</span>
                            </p>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                支付方式 <span class="text-red-500">*</span>
                            </label>
                            <div class="grid grid-cols-3 gap-3">
                                <label
                                    v-for="method in paymentMethods"
                                    :key="method.value"
                                    :class="[
                                        'relative flex items-center justify-center p-4 rounded-lg border-2 cursor-pointer transition-all',
                                        form.method === method.value
                                            ? 'border-blue-600 bg-blue-50'
                                            : 'border-gray-200 hover:border-gray-300'
                                    ]"
                                >
                                    <input
                                        type="radio"
                                        v-model="form.method"
                                        :value="method.value"
                                        class="sr-only"
                                    />
                                    <div class="text-center">
                                        <div v-html="method.icon" class="w-6 h-6 mx-auto mb-1"></div>
                                        <span class="text-sm font-medium">{{ method.label }}</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">交易流水号</label>
                            <input
                                v-model="form.transaction_no"
                                type="text"
                                placeholder="请输入银行/支付平台流水号（选填）"
                                class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">备注</label>
                            <textarea
                                v-model="form.remark"
                                rows="3"
                                placeholder="充值备注信息（选填）"
                                class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            ></textarea>
                        </div>

                        <div class="flex items-center gap-4 pt-4">
                            <button
                                type="submit"
                                :disabled="loading"
                                class="flex-1 py-3 px-6 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-100 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                            >
                                <span v-if="loading" class="inline-flex items-center">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    处理中...
                                </span>
                                <span v-else>确认充值 ¥{{ formatNumber(form.amount || 0) }}</span>
                            </button>
                            <button
                                type="button"
                                @click="resetForm"
                                class="py-3 px-6 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition-all"
                            >
                                重置
                            </button>
                        </div>
                    </form>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 p-6 mt-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">最近充值记录</h3>
                        <router-link
                            :to="{ name: 'payments.index', query: { type: 'recharge' } }"
                            class="text-sm text-blue-600 hover:text-blue-700"
                        >
                            查看全部 →
                        </router-link>
                    </div>
                    <div v-if="recentRecharges.length > 0" class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-100">
                                    <th class="text-left py-3 px-4 text-xs font-medium text-gray-500 uppercase">充值单号</th>
                                    <th class="text-left py-3 px-4 text-xs font-medium text-gray-500 uppercase">金额</th>
                                    <th class="text-left py-3 px-4 text-xs font-medium text-gray-500 uppercase">方式</th>
                                    <th class="text-left py-3 px-4 text-xs font-medium text-gray-500 uppercase">时间</th>
                                    <th class="text-left py-3 px-4 text-xs font-medium text-gray-500 uppercase">状态</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in recentRecharges" :key="item.id" class="border-b border-gray-50 hover:bg-gray-50">
                                    <td class="py-3 px-4 text-sm font-mono text-gray-900">{{ item.payment_no }}</td>
                                    <td class="py-3 px-4 text-sm font-medium text-green-600">+¥{{ formatNumber(item.amount) }}</td>
                                    <td class="py-3 px-4 text-sm text-gray-600">{{ getMethodLabel(item.method) }}</td>
                                    <td class="py-3 px-4 text-sm text-gray-500">{{ formatDate(item.created_at) }}</td>
                                    <td class="py-3 px-4">
                                        <span :class="getStatusClass(item.status)">{{ item.status_label || item.status }}</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="text-center py-12 text-gray-500">
                        <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p>暂无充值记录</p>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="showSuccessModal"
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
            @click.self="showSuccessModal = false"
        >
            <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4 text-center">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-green-100 flex items-center justify-center">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">充值成功！</h3>
                <p class="text-gray-500 mb-4">{{ rechargeResult?.message || '您的账户已成功充值' }}</p>
                <div class="bg-gray-50 rounded-lg p-4 mb-6 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">充值金额</span>
                        <span class="font-semibold text-green-600">+¥{{ formatNumber(rechargeResult?.data?.amount || 0) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">新余额</span>
                        <span class="font-semibold text-blue-600">¥{{ formatNumber(rechargeResult?.new_balance || 0) }}</span>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button
                        @click="showSuccessModal = false; fetchBalance(); fetchRecentRecharges();"
                        class="flex-1 py-3 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition-all"
                    >
                        继续充值
                    </button>
                    <button
                        v-if="pendingRetryPaymentId"
                        @click="goToRetryPayment"
                        class="flex-1 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-all"
                    >
                        去重试支付
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from '../../api/axios';
import { useAuthStore } from '../../stores/auth';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

const loading = ref(false);
const balanceInfo = ref(null);
const distributors = ref([]);
const recentRecharges = ref([]);
const showSuccessModal = ref(false);
const rechargeResult = ref(null);
const pendingRetryPaymentId = ref(null);

const isPlatform = computed(() => auth.user?.user_type === 'platform');

const quickAmounts = [100, 500, 1000, 2000, 5000, 10000];

const paymentMethods = [
    { value: 'bank_transfer', label: '银行转账', icon: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>' },
    { value: 'alipay', label: '支付宝', icon: '<svg class="w-6 h-6 text-blue-500" viewBox="0 0 24 24" fill="currentColor"><path d="M20.422 20.787c-3.03-1.356-5.69-2.74-6.55-3.14-2.42 1.43-4.41 2.12-6.46 2.12-3.27 0-5.51-1.95-5.51-5.05 0-2.76 1.96-5.05 5.31-5.05 2.35 0 4.3.88 5.71 2.06.31.35.56.71.76 1.06l.06.09.36-1.4h-5.26v-.83h6.64l.28-.01.33-1.24h1.56l-.7 3.08c.65.96 1.17 1.98 1.55 2.99 1.15 3.07 1.54 5.04 1.96 5.31zm-2.33-1.6c-.34-.64-.9-2.32-1.92-4.94-1.01-.38-1.99-.7-2.8-.94-.27 1.01-.81 2.25-1.65 3.48 1.01.51 2.57 1.31 6.37 2.4zM7.31 13.04c-2.61 0-3.98 1.54-3.98 3.56 0 2.08 1.46 3.58 4.04 3.58 1.6 0 3.09-.5 4.47-1.5-.7-1.2-1.17-2.3-1.42-3.18-1 .25-1.95.38-3.11.38v-.07c.53 0 1.02-.04 1.48-.11-.56-.94-1.02-2.17-1.48-2.66z"/></svg>' },
    { value: 'wechat', label: '微信支付', icon: '<svg class="w-6 h-6 text-green-500" viewBox="0 0 24 24" fill="currentColor"><path d="M8.691 2.188C3.891 2.188 0 5.476 0 9.53c0 2.212 1.17 4.203 3.002 5.55a.59.59 0 0 1 .213.665l-.39 1.48c-.019.07-.048.141-.048.213 0 .163.13.295.29.295a.326.326 0 0 0 .167-.054l1.903-1.114a.864.864 0 0 1 .717-.098 10.16 10.16 0 0 0 2.837.403c.276 0 .543-.027.811-.05-.857-2.578.157-4.972 1.932-6.446 1.703-1.415 3.882-1.98 5.853-1.838-.576-3.583-4.196-6.348-8.596-6.348zM5.785 5.991c.642 0 1.162.529 1.162 1.18a1.17 1.17 0 0 1-1.162 1.178A1.17 1.17 0 0 1 4.623 7.17c0-.651.52-1.18 1.162-1.18zm5.813 0c.642 0 1.162.529 1.162 1.18a1.17 1.17 0 0 1-1.162 1.178 1.17 1.17 0 0 1-1.162-1.178c0-.651.52-1.18 1.162-1.18zm5.34 2.867c-1.797-.052-3.746.512-5.28 1.786-1.72 1.428-2.687 3.72-1.78 6.22.942 2.453 3.666 4.229 6.884 4.229.826 0 1.622-.12 2.361-.336a.722.722 0 0 1 .598.082l1.584.926a.272.272 0 0 0 .14.047c.134 0 .24-.111.24-.247 0-.06-.023-.12-.038-.177l-.327-1.233a.582.582 0 0 1-.023-.156.49.49 0 0 1 .201-.398C23.024 18.48 24 16.82 24 14.98c0-3.21-2.931-5.837-6.656-6.088V8.89l-.165-.016c-.082-.004-.164-.013-.246-.013zm-2.99 3.13c.535 0 .969.44.969.982a.976.976 0 0 1-.969.983.976.976 0 0 1-.969-.983c0-.542.434-.982.97-.982zm4.844 0c.535 0 .969.44.969.982a.976.976 0 0 1-.969.983.976.976 0 0 1-.969-.983c0-.542.434-.982.969-.982z"/></svg>' },
    { value: 'cash', label: '现金', icon: '<svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>' },
    { value: 'credit', label: '信用', icon: '<svg class="w-6 h-6 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>' },
    { value: 'other', label: '其他', icon: '<svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"/></svg>' },
];

const form = reactive({
    distributor_id: null,
    amount: null,
    method: 'bank_transfer',
    currency: 'CNY',
    transaction_no: '',
    remark: '',
});

onMounted(() => {
    if (route.query.retry_payment_id) {
        pendingRetryPaymentId.value = route.query.retry_payment_id;
    }
    if (route.query.amount) {
        form.amount = parseFloat(route.query.amount);
    }
    if (route.query.distributor_id) {
        form.distributor_id = parseInt(route.query.distributor_id);
    }
    fetchBalance();
    if (isPlatform.value) {
        fetchDistributors();
    }
    fetchRecentRecharges();
});

async function fetchBalance() {
    try {
        const params = {};
        if (form.distributor_id) {
            params.distributor_id = form.distributor_id;
        }
        const { data } = await axios.get('/payments/balance/info', { params });
        balanceInfo.value = data;
    } catch (e) {
        console.error('Failed to fetch balance:', e);
    }
}

async function fetchDistributors() {
    try {
        const { data } = await axios.get('/distributors', { params: { per_page: 100 } });
        distributors.value = data.data || [];
    } catch (e) {
        console.error('Failed to fetch distributors:', e);
    }
}

async function fetchRecentRecharges() {
    try {
        const params = { type: 'recharge', per_page: 5 };
        const { data } = await axios.get('/payments', { params });
        recentRecharges.value = data.data || [];
    } catch (e) {
        console.error('Failed to fetch recharges:', e);
    }
}

async function handleRecharge() {
    if (!form.amount || form.amount <= 0) {
        alert('请输入有效的充值金额');
        return;
    }
    if (isPlatform.value && !form.distributor_id) {
        alert('请先选择要充值的分销商');
        return;
    }

    loading.value = true;
    try {
        const { data } = await axios.post('/payments/recharge', {
            distributor_id: form.distributor_id || undefined,
            amount: form.amount,
            method: form.method,
            currency: form.currency,
            transaction_no: form.transaction_no || undefined,
            remark: form.remark || undefined,
        });
        rechargeResult.value = data;
        showSuccessModal.value = true;
        resetForm();
    } catch (e) {
        alert(e.response?.data?.message || '充值失败，请稍后重试');
    } finally {
        loading.value = false;
    }
}

function resetForm() {
    form.amount = null;
    form.method = 'bank_transfer';
    form.transaction_no = '';
    form.remark = '';
}

function goToRetryPayment() {
    if (pendingRetryPaymentId.value) {
        router.push({ name: 'payments.index' });
    }
}

function formatNumber(num) {
    return new Intl.NumberFormat('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(parseFloat(num || 0));
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')} ${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
}

function getMethodLabel(method) {
    const m = paymentMethods.find(p => p.value === method);
    return m ? m.label : method;
}

function getStatusClass(status) {
    const classes = {
        completed: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800',
        pending: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800',
        failed: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800',
    };
    return classes[status] || classes.pending;
}
</script>
