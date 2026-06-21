<template>
    <div v-if="visible" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="close">
        <div class="bg-white rounded-xl w-full max-w-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900">账户充值</h3>
                <button @click="close" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div v-if="currentBalance !== null" class="px-6 py-4 bg-gradient-to-r from-indigo-50 to-purple-50 border-b border-gray-200">
                <div class="text-sm text-gray-600 mb-1">当前账户余额</div>
                <div class="text-3xl font-bold text-indigo-600">¥{{ formatNumber(currentBalance) }}</div>
            </div>

            <form @submit.prevent="submit" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">充值金额 <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">¥</span>
                        <input
                            v-model.number="form.amount"
                            type="number"
                            step="0.01"
                            min="0.01"
                            required
                            placeholder="请输入充值金额"
                            class="w-full pl-8 pr-3 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"
                        />
                    </div>
                </div>

                <div class="flex gap-2 flex-wrap">
                    <button
                        type="button"
                        v-for="amount in quickAmounts"
                        :key="amount"
                        @click="form.amount = amount"
                        :class="['px-4 py-2 rounded-lg text-sm font-medium transition-colors', form.amount === amount ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200']"
                    >
                        ¥{{ amount }}
                    </button>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">支付方式 <span class="text-red-500">*</span></label>
                    <select v-model="form.method" required class="w-full px-3 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white">
                        <option value="alipay">支付宝</option>
                        <option value="wechat">微信支付</option>
                        <option value="bank_transfer">银行转账</option>
                        <option value="cash">现金</option>
                        <option value="credit">赊账</option>
                        <option value="other">其他</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">交易单号</label>
                    <input
                        v-model="form.transaction_no"
                        type="text"
                        placeholder="可选，用于对账"
                        class="w-full px-3 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">备注</label>
                    <textarea
                        v-model="form.remark"
                        rows="2"
                        placeholder="可选，填写充值说明"
                        class="w-full px-3 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm resize-none"
                    ></textarea>
                </div>

                <div v-if="minimumAmount > 0 && form.amount < minimumAmount" class="p-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-700 text-sm">
                    建议最低充值 ¥{{ formatNumber(minimumAmount) }}，以确保后续操作顺利进行
                </div>

                <div v-if="submitError" class="p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
                    {{ submitError }}
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" @click="close" class="px-4 py-2.5 rounded-lg border border-gray-300 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                        取消
                    </button>
                    <button type="submit" :disabled="submitting" class="px-4 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                        <svg v-if="submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ submitting ? '充值中...' : '确认充值' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref, watch, onMounted } from 'vue';
import paymentApi from '../api/payment';

const props = defineProps({
    visible: {
        type: Boolean,
        default: false,
    },
    distributorId: {
        type: Number,
        default: null,
    },
    minimumAmount: {
        type: Number,
        default: 0,
    },
    defaultAmount: {
        type: Number,
        default: null,
    },
});

const emit = defineEmits(['close', 'success']);

const quickAmounts = [100, 500, 1000, 2000, 5000];

const currentBalance = ref(null);
const submitting = ref(false);
const submitError = ref('');

const defaultForm = () => ({
    amount: props.defaultAmount || 0,
    method: 'alipay',
    transaction_no: '',
    remark: '',
});

const form = reactive(defaultForm());

const formatNumber = (num) => {
    if (num === null || num === undefined) return '0.00';
    return Number(num).toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const loadBalance = async () => {
    try {
        const { data } = await paymentApi.balance(props.distributorId);
        currentBalance.value = data.balance;
    } catch (e) {
        console.error(e);
    }
};

const close = () => {
    emit('close');
};

const resetForm = () => {
    Object.assign(form, defaultForm());
    submitError.value = '';
};

const submit = async () => {
    if (!form.amount || form.amount <= 0) {
        submitError.value = '请输入有效的充值金额';
        return;
    }

    submitting.value = true;
    submitError.value = '';

    try {
        const payload = {
            amount: form.amount,
            method: form.method,
            transaction_no: form.transaction_no || undefined,
            remark: form.remark || undefined,
        };

        if (props.distributorId) {
            payload.distributor_id = props.distributorId;
        }

        const { data } = await paymentApi.recharge(payload);
        emit('success', data);
        close();
    } catch (e) {
        submitError.value = e.response?.data?.message || '充值失败，请稍后重试';
    } finally {
        submitting.value = false;
    }
};

watch(() => props.visible, (val) => {
    if (val) {
        resetForm();
        loadBalance();
    }
});

onMounted(() => {
    if (props.visible) {
        loadBalance();
    }
});
</script>
