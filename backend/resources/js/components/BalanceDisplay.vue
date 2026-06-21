<template>
    <div class="relative" v-if="showBalance">
        <button
            @click="loadBalance"
            class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gradient-to-r from-indigo-50 to-purple-50 border border-indigo-200 hover:from-indigo-100 hover:to-purple-100 transition-colors"
        >
            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-sm font-medium text-indigo-700">
                ¥{{ formatNumber(balance) }}
            </span>
        </button>

        <RechargeModal
            v-model:visible="showRechargeModal"
            @success="handleRechargeSuccess"
        />
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useAuthStore } from '../stores/auth';
import paymentApi from '../api/payment';
import RechargeModal from './RechargeModal.vue';

const auth = useAuthStore();

const balance = ref(null);
const loading = ref(false);
const showRechargeModal = ref(false);
let refreshTimer = null;

const showBalance = computed(() => {
    return auth.isAuthenticated && (auth.hasRole('distributor') || auth.hasRole('regional_agent'));
});

const formatNumber = (num) => {
    if (num === null || num === undefined) return '0.00';
    return Number(num).toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const loadBalance = async (showModal = false) => {
    if (!showBalance.value) return;

    if (showModal && !loading.value) {
        showRechargeModal.value = true;
        return;
    }

    if (loading.value) return;

    loading.value = true;
    try {
        const { data } = await paymentApi.balance();
        balance.value = data.balance;
    } catch (e) {
        console.error('Failed to load balance:', e);
    } finally {
        loading.value = false;
    }
};

const handleRechargeSuccess = (data) => {
    balance.value = data?.new_balance ?? null;
    loadBalance();
};

const startAutoRefresh = () => {
    refreshTimer = setInterval(() => {
        loadBalance();
    }, 30000);
};

const stopAutoRefresh = () => {
    if (refreshTimer) {
        clearInterval(refreshTimer);
        refreshTimer = null;
    }
};

onMounted(() => {
    if (showBalance.value) {
        loadBalance();
        startAutoRefresh();
    }
});

onUnmounted(() => {
    stopAutoRefresh();
});

defineExpose({
    loadBalance,
    refresh: loadBalance,
});
</script>
