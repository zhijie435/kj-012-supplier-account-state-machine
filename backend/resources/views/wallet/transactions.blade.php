@extends('layouts.app')

@section('title', '交易记录')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">交易记录</h1>
            <p class="mt-1 text-sm text-gray-500">查看您的所有资金交易明细</p>
        </div>
    </div>

    <div id="wallet-transactions-app">
        <wallet-transactions></wallet-transactions>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Vue !== 'undefined') {
            const WalletTransactions = {
                data() {
                    return {
                        loading: true,
                        transactions: [],
                        filters: {
                            type: '',
                            start_date: '',
                            end_date: '',
                            keyword: '',
                            page: 1,
                            per_page: 20
                        },
                        pagination: {
                            current_page: 1,
                            last_page: 1,
                            total: 0
                        },
                        typeLabels: {
                            recharge: '充值',
                            withdraw: '提现',
                            order_payment: '订单支付',
                            order_refund: '订单退款',
                            commission: '佣金收入',
                            system_adjust: '系统调整',
                            transfer_in: '转入',
                            transfer_out: '转出'
                        },
                        selectedTransaction: null
                    };
                },
                mounted() {
                    this.loadTransactions();
                },
                methods: {
                    async loadTransactions() {
                        this.loading = true;
                        try {
                            const response = await axios.get('/api/wallet/transactions', { params: this.filters });
                            const result = response.data.data || response.data;
                            this.transactions = result.data || result;
                            if (result.current_page !== undefined) {
                                this.pagination.current_page = result.current_page;
                                this.pagination.last_page = result.last_page;
                                this.pagination.total = result.total;
                            }
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
                    },
                    getTypeClass(type) {
                        const incomeTypes = ['recharge', 'order_refund', 'commission', 'system_adjust', 'transfer_in'];
                        if (incomeTypes.includes(type)) {
                            return 'text-green-600';
                        }
                        return 'text-red-600';
                    },
                    getAmountClass(amount) {
                        return parseFloat(amount) >= 0 ? 'text-green-600' : 'text-red-600';
                    },
                    formatAmount(amount) {
                        const num = parseFloat(amount || 0);
                        return (num >= 0 ? '+' : '') + this.formatNumber(num);
                    },
                    getTypeIcon(type) {
                        const icons = {
                            recharge: '💳',
                            withdraw: '💰',
                            order_payment: '🛒',
                            order_refund: '↩️',
                            commission: '🎁',
                            system_adjust: '⚙️',
                            transfer_in: '📥',
                            transfer_out: '📤'
                        };
                        return icons[type] || '💵';
                    },
                    changePage(page) {
                        this.filters.page = page;
                        this.loadTransactions();
                    },
                    resetFilters() {
                        this.filters = {
                            type: '',
                            start_date: '',
                            end_date: '',
                            keyword: '',
                            page: 1,
                            per_page: 20
                        };
                        this.loadTransactions();
                    },
                    showDetail(tx) {
                        this.selectedTransaction = tx;
                    },
                    exportData() {
                        alert('导出功能开发中...');
                    }
                },
                template: `
                    <div>
                        <div class="bg-white rounded-2xl border border-gray-200 p-6 mb-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">交易类型</label>
                                    <select v-model="filters.type" class="w-full rounded-lg border-gray-300 border px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                        <option value="">全部类型</option>
                                        <option v-for="(label, key) in typeLabels" :key="key" :value="key">{{ label }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">开始日期</label>
                                    <input v-model="filters.start_date" type="date" class="w-full rounded-lg border-gray-300 border px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">结束日期</label>
                                    <input v-model="filters.end_date" type="date" class="w-full rounded-lg border-gray-300 border px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">关键词</label>
                                    <input v-model="filters.keyword" type="text" placeholder="搜索交易单号/备注" class="w-full rounded-lg border-gray-300 border px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm" @keyup.enter="loadTransactions" />
                                </div>
                            </div>
                            <div class="flex justify-end gap-3 mt-4">
                                <button @click="resetFilters" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">重置</button>
                                <button @click="loadTransactions" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium">查询</button>
                                <button @click="exportData" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium">导出</button>
                            </div>
                        </div>

                        <div v-if="loading" class="flex justify-center py-12">
                            <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-indigo-600 border-t-transparent"></div>
                        </div>

                        <div v-else class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                            <div v-if="transactions.length > 0">
                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">交易单号</th>
                                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">类型</th>
                                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">金额</th>
                                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">余额</th>
                                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">备注</th>
                                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">时间</th>
                                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">操作</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <tr v-for="tx in transactions" :key="tx.id" class="hover:bg-gray-50 transition-colors">
                                                <td class="px-6 py-4"><span class="font-mono text-sm text-gray-900">{{ tx.tx_no }}</span></td>
                                                <td class="px-6 py-4">
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-lg">{{ getTypeIcon(tx.type) }}</span>
                                                        <span class="text-sm text-gray-900">{{ typeLabels[tx.type] || tx.type }}</span>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <span :class="['text-sm font-semibold', getAmountClass(tx.amount)]">
                                                        {{ formatAmount(tx.amount) }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4"><span class="text-sm text-gray-600">¥{{ formatNumber(tx.balance_after) }}</span></td>
                                                <td class="px-6 py-4"><span class="text-sm text-gray-500 max-w-xs truncate">{{ tx.remark || '-' }}</span></td>
                                                <td class="px-6 py-4"><span class="text-sm text-gray-500">{{ formatDate(tx.created_at) }}</span></td>
                                                <td class="px-6 py-4">
                                                    <button @click="showDetail(tx)" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">详情</button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                                    <div class="text-sm text-gray-500">共 {{ pagination.total }} 条记录</div>
                                    <div class="flex items-center gap-2">
                                        <button @click="changePage(1)" :disabled="pagination.current_page === 1" class="px-3 py-1.5 rounded-lg border border-gray-300 text-sm disabled:opacity-50 hover:bg-gray-50">首页</button>
                                        <button @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page === 1" class="px-3 py-1.5 rounded-lg border border-gray-300 text-sm disabled:opacity-50 hover:bg-gray-50">上一页</button>
                                        <span class="px-3 py-1.5 text-sm text-gray-600">{{ pagination.current_page }} / {{ pagination.last_page }}</span>
                                        <button @click="changePage(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page" class="px-3 py-1.5 rounded-lg border border-gray-300 text-sm disabled:opacity-50 hover:bg-gray-50">下一页</button>
                                        <button @click="changePage(pagination.last_page)" :disabled="pagination.current_page === pagination.last_page" class="px-3 py-1.5 rounded-lg border border-gray-300 text-sm disabled:opacity-50 hover:bg-gray-50">末页</button>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="text-center py-16">
                                <p class="text-gray-500">暂无交易记录</p>
                            </div>
                        </div>

                        <div v-if="selectedTransaction" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="selectedTransaction = null">
                            <div class="bg-white rounded-2xl p-6 max-w-md w-full mx-4">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-lg font-semibold text-gray-900">交易详情</h3>
                                    <button @click="selectedTransaction = null" class="text-gray-400 hover:text-gray-600">✕</button>
                                </div>
                                <div class="space-y-4">
                                    <div class="flex justify-between py-2 border-b border-gray-100">
                                        <span class="text-gray-500">交易单号</span>
                                        <span class="font-mono text-sm text-gray-900">{{ selectedTransaction.tx_no }}</span>
                                    </div>
                                    <div class="flex justify-between py-2 border-b border-gray-100">
                                        <span class="text-gray-500">交易类型</span>
                                        <span class="text-gray-900">{{ typeLabels[selectedTransaction.type] || selectedTransaction.type }}</span>
                                    </div>
                                    <div class="flex justify-between py-2 border-b border-gray-100">
                                        <span class="text-gray-500">金额</span>
                                        <span :class="['font-semibold', getAmountClass(selectedTransaction.amount)]">{{ formatAmount(selectedTransaction.amount) }}</span>
                                    </div>
                                    <div class="flex justify-between py-2 border-b border-gray-100">
                                        <span class="text-gray-500">交易后余额</span>
                                        <span class="text-gray-900">¥{{ formatNumber(selectedTransaction.balance_after) }}</span>
                                    </div>
                                    <div class="flex justify-between py-2 border-b border-gray-100">
                                        <span class="text-gray-500">关联单号</span>
                                        <span class="font-mono text-sm text-gray-900">{{ selectedTransaction.related_no || '-' }}</span>
                                    </div>
                                    <div class="flex justify-between py-2 border-b border-gray-100">
                                        <span class="text-gray-500">备注</span>
                                        <span class="text-gray-900">{{ selectedTransaction.remark || '-' }}</span>
                                    </div>
                                    <div class="flex justify-between py-2">
                                        <span class="text-gray-500">交易时间</span>
                                        <span class="text-gray-900">{{ formatDate(selectedTransaction.created_at) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `
            };

            new Vue({
                el: '#wallet-transactions-app',
                components: {
                    'wallet-transactions': WalletTransactions
                }
            });
        }
    });
</script>
@endpush
