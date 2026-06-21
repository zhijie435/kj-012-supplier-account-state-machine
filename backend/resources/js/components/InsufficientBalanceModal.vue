<template>
    <div v-if="visible" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="close">
        <div class="bg-white rounded-xl w-full max-w-md overflow-hidden">
            <div class="px-6 py-8 text-center">
                <div class="w-20 h-20 rounded-full bg-amber-100 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-10 h-10 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">余额不足</h3>
                <p class="text-gray-500 mb-6">您的账户余额不足以完成本次支付</p>

                <div class="bg-gray-50 rounded-xl p-4 mb-6 text-left">
                    <div class="flex justify-between items-center mb-3">
                        <span class="text-sm text-gray-500">当前余额</span>
                        <span class="text-sm font-medium text-gray-900">¥{{ formatNumber(currentBalance) }}</span>
                    </div>
                    <div class="flex justify-between items-center mb-3">
                        <span class="text-sm text-gray-500">需要支付</span>
                        <span class="text-sm font-medium text-gray-900">¥{{ formatNumber(requiredAmount) }}</span>
                    </div>
                    <div class="border-t border-gray-200 my-3"></div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500">还差</span>
                        <span class="text-lg font-bold text-red-600">¥{{ formatNumber(deficit) }}</span>
                    </div>
                </div>

                <div class="flex flex-col gap-3">
                    <button
                        @click="openRecharge"
                        class="w-full px-4 py-3 rounded-xl bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors flex items-center justify-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        立即充值 ¥{{ formatNumber(recommendAmount) }}
                    </button>
                    <button
                        v-if="failedPaymentId"
                        @click="retryPayment"
                        :disabled="retrying"
                        class="w-full px-4 py-3 rounded-xl border-2 border-indigo-600 text-indigo-600 text-sm font-medium hover:bg-indigo-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                    >
                        <svg v-if="retrying" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        {{ retrying ? '重试中...' : '直接重试支付' }}
                    </button>
                    <button
                        @click="close"
                        class="w-full px-4 py-3 rounded-xl text-gray-500 text-sm hover:text-gray-700 hover:bg-gray-50 transition-colors"
                    >
                        稍后再说
                    </button>
                </div>
            </div>
        </div>
    </div>

    <RechargeModal
        v-model:visible="showRechargeModal"
        :distributor-id="distributorId"
        :default-amount="recommendAmount"
        :minimum-amount="deficit"
        @success="handleRechargeSuccess"
    />
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import paymentApi from '../api/payment';
import RechargeModal from './RechargeModal.vue';

const props = defineProps({
    visible: {
        type: Boolean,
        default: false,
    },
    currentBalance: {
        type: Number,
        default: 0,
    },
    requiredAmount: {
        type: Number,
        default: 0,
    },
    deficit: {
        type: Number,
        default: 0,
    },
    failedPaymentId: {
        type: Number,
        default: null,
    },
    distributorId: {
        type: Number,
        default: null,
    },
});

const emit = defineEmits(['close', 'success', 'retry-success']);

const showRechargeModal = ref(false);
const retrying = ref(false);

const recommendAmount = computed(() => {
    const d = props.deficit || 100;
    const amounts = [100, 500, 1000, 2000, 5000];
    for (const amount of amounts) {
        if (amount >= d) {
            return amount;
        }
    }
    return Math.ceil(d / 100) * 100;
});

const formatNumber = (num) => {
    if (num === null || num === undefined) return '0.00';
    return Number(num).toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const close = () => {
    emit('close');
};

const openRecharge = () => {
    showRechargeModal.value = true;
};

const handleRechargeSuccess = (data) => {
    emit('success', data);
};

const retryPayment = async () => {
    if (!props.failedPaymentId || retrying.value) return;

    retrying.value = true;
    try {
        const { data } = await paymentApi.retry(props.failedPaymentId);
        emit('retry-success', data);
        emit('close');
    } catch (e) {
        if (e.response?.status === 402 && e.response?.data?.insufficient_balance) {
            alert('余额仍然不足，请先充值后再重试');
        } else {
            alert(e.response?.data?.message || '重试失败，请稍后再试');
        }
    } finally {
        retrying.value = false;
    }
};

watch(() => props.visible, (val) => {
    if (!val) {
        showRechargeModal.value = false;
        retrying.value = false;
    }
});
</script>
