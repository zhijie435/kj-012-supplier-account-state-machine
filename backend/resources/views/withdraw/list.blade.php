@extends('layouts.app')

@section('title', '提现记录')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">提现记录</h1>
            <p class="mt-1 text-sm text-gray-500">查看所有提现申请记录</p>
        </div>
        <a href="{{ url('/withdraw') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium">
            申请提现
        </a>
    </div>

    <div id="withdraw-list-app">
        <withdraw-list></withdraw-list>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Vue !== 'undefined') {
            const WithdrawList = {
                data() {
                    return {
                        loading: true,
                        withdrawals: [],
                        statistics: {
                            pending_count: 0,
                            processing_count: 0,
                            completed_count: 0,
                            failed_count: 0
                        },
                        filters: {
                            status: '',
                            method: '',
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
                        statusLabels: {
                            pending: '待审核',
                            approved: '已审核',
                            processing: '处理中',
                            completed: '已完成',
                            failed: '已失败',
                            rejected: '已拒绝',
                            cancelled: '已取消'
                        },
                        methodLabels: {
                            bank_transfer: '银行转账',
                            alipay: '支付宝',
                            wechat: '微信支付'
                        }
                    };
                },
                mounted() {
                    this.loadStatistics();
                    this.loadWithdrawals();
                },
                methods: {
                    async loadStatistics() {
                        try {
                            const response = await axios.get('/api/withdrawals/statistics');
                            this.statistics = response.data.data || response.data;
                        } catch (e) {
                            console.error(e);
                        }
                    },
                    async loadWithdrawals() {
                        this.loading = true;
                        try {
                            const response = await axios.get('/api/withdrawals', { params: this.filters });
                            const result = response.data.data || response.data;
                            this.withdrawals = result.data || result;
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
                    getStatusClass(status) {
                        const classes = {
                            pending: 'bg-yellow-100 text-yellow-800',
                            approved: 'bg-blue-100 text-blue-800',
                            processing: 'bg-indigo-100 text-indigo-800',
                            completed: 'bg-green-100 text-green-800',
                            failed: 'bg-red-100 text-red-800',
                            rejected: 'bg-red-100 text-red-800',
                            cancelled: 'bg-gray-100 text-gray-800'
                        };
                        return classes[status] || 'bg-gray-100 text-gray-800';
                    },
                    changePage(page) {
                        this.filters.page = page;
                        this.loadWithdrawals();
                    },
                    resetFilters() {
                        this.filters = {
                            status: '',
                            method: '',
                            start_date: '',
                            end_date: '',
                            keyword: '',
                            page: 1,
                            per_page: 20
                        };
                        this.loadWithdrawals();
                    }
                },
                template: `
                    <div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                            <div class="bg-white rounded-xl border border-gray-200 p-5">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-yellow-50 rounded-lg flex items-center justify-center">
                                        <span class="text-yellow-500">⏳</span>
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-500">待审核</div>
                                        <div class="text-2xl font-bold text-yellow-600">{{ statistics.pending_count }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-white rounded-xl border border-gray-200 p-5">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center">
                                        <span class="text-blue-500">⚡</span>
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-500">处理中</div>
                                        <div class="text-2xl font-bold text-blue-600">{{ statistics.processing_count }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-white rounded-xl border border-gray-200 p-5">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-green-50 rounded-lg flex items-center justify-center">
                                        <span class="text-green-500">✓</span>
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-500">已完成</div>
                                        <div class="text-2xl font-bold text-green-600">{{ statistics.completed_count }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-white rounded-xl border border-gray-200 p-5">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-red-50 rounded-lg flex items-center justify-center">
                                        <span class="text-red-500">✕</span>
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-500">已失败</div>
                                        <div class="text-2xl font-bold text-red-600">{{ statistics.failed_count }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-2xl border border-gray-200 p-6 mb-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">状态</label>
                                    <select v-model="filters.status" class="w-full rounded-lg border-gray-300 border px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                        <option value="">全部状态</option>
                                        <option v-for="(label, key) in statusLabels" :key="key" :value="key">{{ label }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">提现方式</label>
                                    <select v-model="filters.method" class="w-full rounded-lg border-gray-300 border px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                        <option value="">全部方式</option>
                                        <option v-for="(label, key) in methodLabels" :key="key" :value="key">{{ label }}</option>
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
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">关键词</label>
                                    <input v-model="filters.keyword" type="text" placeholder="搜索提现单号/申请人" class="w-full rounded-lg border-gray-300 border px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm" @keyup.enter="loadWithdrawals" />
                                </div>
                                <div class="flex items-end gap-3">
                                    <button @click="resetFilters" class="flex-1 px-4 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">重置</button>
                                    <button @click="loadWithdrawals" class="flex-1 px-4 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium">查询</button>
                                </div>
                            </div>
                        </div>

                        <div v-if="loading" class="flex justify-center py-12">
                            <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-indigo-600 border-t-transparent"></div>
                        </div>

                        <div v-else class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                            <div v-if="withdrawals.length > 0">
                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">提现单号</th>
                                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">金额</th>
                                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">手续费</th>
                                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">实际到账</th>
                                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">方式</th>
                                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">状态</th>
                                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">申请时间</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <tr v-for="withdraw in withdrawals" :key="withdraw.id" class="hover:bg-gray-50 transition-colors">
                                                <td class="px-6 py-4"><span class="font-mono text-sm text-gray-900">{{ withdraw.withdraw_no }}</span></td>
                                                <td class="px-6 py-4"><span class="text-sm font-semibold text-red-600">¥{{ formatNumber(withdraw.amount) }}</span></td>
                                                <td class="px-6 py-4"><span class="text-sm text-orange-600">¥{{ formatNumber(withdraw.fee_amount) }}</span></td>
                                                <td class="px-6 py-4"><span class="text-sm font-medium text-green-600">¥{{ formatNumber(withdraw.actual_amount) }}</span></td>
                                                <td class="px-6 py-4"><span class="text-sm text-gray-600">{{ methodLabels[withdraw.method] || withdraw.method }}</span></td>
                                                <td class="px-6 py-4">
                                                    <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', getStatusClass(withdraw.status)]">
                                                        {{ statusLabels[withdraw.status] || withdraw.status }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4"><span class="text-sm text-gray-500">{{ formatDate(withdraw.created_at) }}</span></td>
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
                                <p class="text-gray-500">暂无提现记录</p>
                            </div>
                        </div>
                    </div>
                `
            };

            new Vue({
                el: '#withdraw-list-app',
                components: {
                    'withdraw-list': WithdrawList
                }
            });
        }
    });
</script>
@endpush
