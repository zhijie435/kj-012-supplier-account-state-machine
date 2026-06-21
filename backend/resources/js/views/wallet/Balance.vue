<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">钱包余额</h1>
                <p class="mt-1 text-sm text-gray-500">查看账户余额和资金概况</p>
            </div>
            <div class="flex gap-3">
                <router-link
                    :to="{ name: 'recharge.index' }"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium"
                >
                    充值
                </router-link>
                <router-link
                    :to="{ name: 'withdraw.index' }"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium"
                >
                    提现
                </router-link>
            </div>
        </div>

        <div v-if="loading" class="flex justify-center py-12">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-indigo-600 border-t-transparent"></div>
        </div>

        <template v-else>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-indigo-100 text-sm">可用余额</p>
                            <p class="text-3xl font-bold mt-2">¥{{ formatNumber(balance?.available || 0) }}</p>
                        </div>
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-white/20">
                        <div class="flex items-center gap-2 text-sm text-indigo-100">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>实时更新</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-gray-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">冻结余额</p>
                            <p class="text-2xl font-bold mt-2 text-amber-600">¥{{ formatNumber(balance?.frozen || 0) }}</p>
                        </div>
                        <div class="w-12 h-12 bg-amber-50 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 text-xs text-gray-400">
                        提现处理中的资金
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-gray-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">今日收入</p>
                            <p class="text-2xl font-bold mt-2 text-green-600">+¥{{ formatNumber(statistics?.today_income || 0) }}</p>
                        </div>
                        <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 text-xs text-gray-400">
                        较昨日 {{ statistics?.yesterday_income_diff >= 0 ? '+' : '' }}{{ formatNumber(statistics?.yesterday_income_diff || 0) }}
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-gray-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">今日支出</p>
                            <p class="text-2xl font-bold mt-2 text-red-600">-¥{{ formatNumber(statistics?.today_expense || 0) }}</p>
                        </div>
                        <div class="w-12 h-12 bg-red-50 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 text-xs text-gray-400">
                        较昨日 {{ statistics?.yesterday_expense_diff >= 0 ? '+' : '' }}{{ formatNumber(statistics?.yesterday_expense_diff || 0) }}
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-2xl border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">近30天收支趋势</h3>
                        <div class="flex gap-4 text-sm">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-green-500"></span>
                                <span class="text-gray-600">收入</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-red-500"></span>
                                <span class="text-gray-600">支出</span>
                            </div>
                        </div>
                    </div>
                    <div class="h-64 flex items-end justify-between gap-1">
                        <div
                            v-for="(item, idx) in chartData"
                            :key="idx"
                            class="flex-1 flex flex-col items-center gap-2"
                        >
                            <div class="w-full flex flex-col-reverse items-center gap-0.5 h-48">
                                <div
                                    class="w-full bg-green-400 rounded-t transition-all hover:bg-green-500"
                                    :style="{ height: getChartHeight(item.income) + '%' }"
                                    :title="'收入: ¥' + formatNumber(item.income)"
                                ></div>
                                <div
                                    class="w-full bg-red-400 rounded-t transition-all hover:bg-red-500"
                                    :style="{ height: getChartHeight(item.expense) + '%' }"
                                    :title="'支出: ¥' + formatNumber(item.expense)"
                                ></div>
                            </div>
                            <span class="text-xs text-gray-400">{{ item.date }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">最近交易记录</h3>
                        <router-link
                            :to="{ name: 'wallet.transactions' }"
                            class="text-sm text-indigo-600 hover:text-indigo-700"
                        >
                            查看全部 →
                        </router-link>
                    </div>
                    <div v-if="recentTransactions.length > 0" class="space-y-3">
                        <div
                            v-for="tx in recentTransactions"
                            :key="tx.id"
                            class="flex items-center justify-between p-3 rounded-xl hover:bg-gray-50 transition-colors"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    :class="[
                                        'w-10 h-10 rounded-xl flex items-center justify-center',
                                        tx.type === 'income' ? 'bg-green-50' : 'bg-red-50'
                                    ]"
                                >
                                    <svg
                                        :class="['w-5 h-5', tx.type === 'income' ? 'text-green-500' : 'text-red-500']"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            :d="tx.type === 'income'
                                                ? 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6'
                                                : 'M13 17h8m0 0V9m0 8l-8-8-4 4-6-6'"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900 text-sm">{{ tx.description }}</div>
                                    <div class="text-xs text-gray-500">{{ formatDate(tx.created_at) }}</div>
                                </div>
                            </div>
                            <div
                                :class="[
                                    'font-semibold',
                                    tx.type === 'income' ? 'text-green-600' : 'text-red-600'
                                ]"
                            >
                                {{ tx.type === 'income' ? '+' : '-' }}¥{{ formatNumber(tx.amount) }}
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-12 text-gray-500">
                        <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p>暂无交易记录</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-6">资金统计</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div class="text-center p-4 bg-gray-50 rounded-xl">
                        <div class="text-2xl font-bold text-gray-900">¥{{ formatNumber(statistics?.total_income || 0) }}</div>
                        <div class="text-sm text-gray-500 mt-1">累计收入</div>
                    </div>
                    <div class="text-center p-4 bg-gray-50 rounded-xl">
                        <div class="text-2xl font-bold text-gray-900">¥{{ formatNumber(statistics?.total_expense || 0) }}</div>
                        <div class="text-sm text-gray-500 mt-1">累计支出</div>
                    </div>
                    <div class="text-center p-4 bg-gray-50 rounded-xl">
                        <div class="text-2xl font-bold text-indigo-600">{{ statistics?.total_withdraw_count || 0 }}</div>
                        <div class="text-sm text-gray-500 mt-1">提现次数</div>
                    </div>
                    <div class="text-center p-4 bg-gray-50 rounded-xl">
                        <div class="text-2xl font-bold text-green-600">{{ statistics?.total_recharge_count || 0 }}</div>
                        <div class="text-sm text-gray-500 mt-1">充值次数</div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import walletApi from '../../api/wallet';

const loading = ref(false);
const balance = ref(null);
const statistics = ref(null);
const recentTransactions = ref([]);

const formatNumber = (num) => {
    return new Intl.NumberFormat('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(parseFloat(num || 0));
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')} ${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
};

const chartData = computed(() => {
    const data = [];
    const today = new Date();
    for (let i = 29; i >= 0; i--) {
        const date = new Date(today);
        date.setDate(date.getDate() - i);
        data.push({
            date: `${date.getMonth() + 1}/${date.getDate()}`,
            income: Math.random() * 10000 + 1000,
            expense: Math.random() * 5000 + 500,
        });
    }
    return data;
});

const maxChartValue = computed(() => {
    let max = 0;
    chartData.value.forEach(item => {
        max = Math.max(max, item.income, item.expense);
    });
    return max || 1;
});

const getChartHeight = (value) => {
    return (value / maxChartValue.value) * 100;
};

const loadData = async () => {
    loading.value = true;
    try {
        const [balanceRes, statsRes, txRes] = await Promise.all([
            walletApi.getBalance(),
            walletApi.getStatistics(),
            walletApi.getTransactions({ per_page: 5 }),
        ]);

        balance.value = balanceRes.data?.data || balanceRes.data;
        statistics.value = statsRes.data?.data || statsRes.data;
        recentTransactions.value = txRes.data?.data?.slice(0, 5) || (Array.isArray(txRes.data) ? txRes.data.slice(0, 5) : []);
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    loadData();
});
</script>
