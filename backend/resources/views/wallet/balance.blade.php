@extends('layouts.app')

@section('title', '钱包余额')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">钱包余额</h1>
        <p class="mt-1 text-sm text-gray-500">查看账户余额和资金概况</p>
    </div>

    <div id="wallet-balance-app">
        <wallet-balance></wallet-balance>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Vue !== 'undefined') {
            const WalletBalance = {
                data() {
                    return {
                        loading: true,
                        balance: { available: 0, frozen: 0 },
                        statistics: {},
                        recentTransactions: []
                    };
                },
                mounted() {
                    this.loadData();
                },
                methods: {
                    async loadData() {
                        try {
                            const response = await axios.get('/api/wallet/balance');
                            this.balance = response.data.data || response.data;
                            const statsResponse = await axios.get('/api/wallet/statistics');
                            this.statistics = statsResponse.data.data || statsResponse.data;
                            const txResponse = await axios.get('/api/wallet/transactions', { params: { per_page: 5 } });
                            this.recentTransactions = txResponse.data.data?.data || txResponse.data.data || [];
                        } catch (e) {
                            console.error(e);
                        } finally {
                            this.loading = false;
                        }
                    },
                    formatNumber(num) {
                        return new Intl.NumberFormat('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(parseFloat(num || 0));
                    },
                    formatDate(dateStr) {
                        if (!dateStr) return '';
                        const d = new Date(dateStr);
                        return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')} ${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
                    }
                },
                template: `
                    <div v-if="loading" class="flex justify-center py-12">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-indigo-600 border-t-transparent"></div>
                    </div>
                    <div v-else>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                            <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl p-6 text-white">
                                <p class="text-indigo-100 text-sm">可用余额</p>
                                <p class="text-3xl font-bold mt-2">¥{{ formatNumber(balance.available) }}</p>
                            </div>
                            <div class="bg-white rounded-2xl border border-gray-200 p-6">
                                <p class="text-gray-500 text-sm">冻结余额</p>
                                <p class="text-2xl font-bold mt-2 text-amber-600">¥{{ formatNumber(balance.frozen) }}</p>
                            </div>
                            <div class="bg-white rounded-2xl border border-gray-200 p-6">
                                <p class="text-gray-500 text-sm">今日收入</p>
                                <p class="text-2xl font-bold mt-2 text-green-600">+¥{{ formatNumber(statistics.today_income) }}</p>
                            </div>
                            <div class="bg-white rounded-2xl border border-gray-200 p-6">
                                <p class="text-gray-500 text-sm">今日支出</p>
                                <p class="text-2xl font-bold mt-2 text-red-600">-¥{{ formatNumber(statistics.today_expense) }}</p>
                            </div>
                        </div>

                        <div class="bg-white rounded-2xl border border-gray-200 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">最近交易记录</h3>
                            <div v-if="recentTransactions.length > 0" class="space-y-3">
                                <div v-for="tx in recentTransactions" :key="tx.id" class="flex items-center justify-between p-3 rounded-xl hover:bg-gray-50">
                                    <div class="flex items-center gap-3">
                                        <div :class="['w-10 h-10 rounded-xl flex items-center justify-center', tx.type === 'income' ? 'bg-green-50' : 'bg-red-50']">
                                            <span :class="['text-lg', tx.type === 'income' ? 'text-green-500' : 'text-red-500']">{{ tx.type === 'income' ? '↑' : '↓' }}</span>
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900 text-sm">{{ tx.description }}</div>
                                            <div class="text-xs text-gray-500">{{ formatDate(tx.created_at) }}</div>
                                        </div>
                                    </div>
                                    <div :class="['font-semibold', tx.type === 'income' ? 'text-green-600' : 'text-red-600']">
                                        {{ tx.type === 'income' ? '+' : '-' }}¥{{ formatNumber(tx.amount) }}
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-12 text-gray-500">
                                <p>暂无交易记录</p>
                            </div>
                        </div>
                    </div>
                `
            };

            new Vue({
                el: '#wallet-balance-app',
                components: {
                    'wallet-balance': WalletBalance
                }
            });
        }
    });
</script>
@endpush
