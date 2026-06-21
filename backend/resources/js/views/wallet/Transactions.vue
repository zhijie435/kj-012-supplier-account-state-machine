<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">交易记录</h1>
                <p class="mt-1 text-sm text-gray-500">查看所有资金交易明细</p>
            </div>
            <div class="flex gap-3">
                <button
                    @click="exportData"
                    class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors text-sm font-medium flex items-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    导出
                </button>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">交易类型</label>
                    <select
                        v-model="filters.type"
                        class="w-full rounded-lg border-gray-300 border px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                    >
                        <option value="">全部类型</option>
                        <option value="income">收入</option>
                        <option value="expense">支出</option>
                        <option value="recharge">充值</option>
                        <option value="withdraw">提现</option>
                        <option value="withdraw_fee">提现手续费</option>
                        <option value="refund">退款</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">开始日期</label>
                    <input
                        v-model="filters.start_date"
                        type="date"
                        class="w-full rounded-lg border-gray-300 border px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">结束日期</label>
                    <input
                        v-model="filters.end_date"
                        type="date"
                        class="w-full rounded-lg border-gray-300 border px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">关键词</label>
                    <div class="relative">
                        <input
                            v-model="filters.keyword"
                            type="text"
                            placeholder="搜索交易单号/描述"
                            class="w-full rounded-lg border-gray-300 border pl-10 pr-3 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                            @keyup.enter="loadTransactions"
                        />
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-4">
                <button
                    @click="resetFilters"
                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium"
                >
                    重置
                </button>
                <button
                    @click="loadTransactions"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium"
                >
                    查询
                </button>
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
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <input
                                        type="checkbox"
                                        v-model="selectAll"
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">交易单号</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">类型</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">描述</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">金额</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">余额</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">状态</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">时间</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr
                                v-for="tx in transactions"
                                :key="tx.id"
                                class="hover:bg-gray-50 transition-colors"
                            >
                                <td class="px-6 py-4">
                                    <input
                                        type="checkbox"
                                        :value="tx.id"
                                        v-model="selectedIds"
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-mono text-sm text-gray-900">{{ tx.transaction_no }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="getTypeClass(tx.type)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                        {{ getTypeLabel(tx.type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">{{ tx.description }}</div>
                                    <div v-if="tx.remark" class="text-xs text-gray-500 mt-0.5">备注: {{ tx.remark }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="tx.type === 'income' || tx.type === 'recharge' || tx.type === 'refund' ? 'text-green-600' : 'text-red-600'" class="text-sm font-semibold">
                                        {{ tx.type === 'income' || tx.type === 'recharge' || tx.type === 'refund' ? '+' : '-' }}¥{{ formatNumber(tx.amount) }}
                                    </span>
                                    <div v-if="tx.fee_amount > 0" class="text-xs text-orange-600 mt-0.5">
                                        手续费: ¥{{ formatNumber(tx.fee_amount) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-gray-600">¥{{ formatNumber(tx.balance_after) }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="getStatusClass(tx.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                        {{ getStatusLabel(tx.status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-gray-500">{{ formatDate(tx.created_at) }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <button
                                        @click="showDetail(tx)"
                                        class="text-indigo-600 hover:text-indigo-700 text-sm font-medium"
                                    >
                                        详情
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                    <div class="text-sm text-gray-500">
                        共 {{ pagination.total }} 条记录，已选择 {{ selectedIds.length }} 条
                    </div>
                    <div class="flex items-center gap-2">
                        <select
                            v-model="filters.per_page"
                            @change="loadTransactions"
                            class="rounded-lg border-gray-300 border px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        >
                            <option :value="10">10条/页</option>
                            <option :value="20">20条/页</option>
                            <option :value="50">50条/页</option>
                            <option :value="100">100条/页</option>
                        </select>
                        <nav class="flex items-center gap-1">
                            <button
                                @click="changePage(1)"
                                :disabled="pagination.current_page === 1"
                                class="px-3 py-1.5 rounded-lg border border-gray-300 text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 transition-colors"
                            >
                                首页
                            </button>
                            <button
                                @click="changePage(pagination.current_page - 1)"
                                :disabled="pagination.current_page === 1"
                                class="px-3 py-1.5 rounded-lg border border-gray-300 text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 transition-colors"
                            >
                                上一页
                            </button>
                            <span class="px-3 py-1.5 text-sm text-gray-600">
                                {{ pagination.current_page }} / {{ pagination.last_page }}
                            </span>
                            <button
                                @click="changePage(pagination.current_page + 1)"
                                :disabled="pagination.current_page === pagination.last_page"
                                class="px-3 py-1.5 rounded-lg border border-gray-300 text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 transition-colors"
                            >
                                下一页
                            </button>
                            <button
                                @click="changePage(pagination.last_page)"
                                :disabled="pagination.current_page === pagination.last_page"
                                class="px-3 py-1.5 rounded-lg border border-gray-300 text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 transition-colors"
                            >
                                末页
                            </button>
                        </nav>
                    </div>
                </div>
            </div>

            <div v-else class="text-center py-16">
                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-1">暂无交易记录</h3>
                <p class="text-gray-500">当前筛选条件下没有找到交易记录</p>
            </div>
        </div>

        <div
            v-if="showDetailModal"
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
            @click.self="showDetailModal = false"
        >
            <div class="bg-white rounded-2xl p-6 max-w-lg w-full mx-4">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">交易详情</h3>
                    <button
                        @click="showDetailModal = false"
                        class="text-gray-400 hover:text-gray-600"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div v-if="currentTx" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs text-gray-500">交易单号</label>
                            <p class="font-mono text-sm font-medium text-gray-900">{{ currentTx.transaction_no }}</p>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">交易类型</label>
                            <p>
                                <span :class="getTypeClass(currentTx.type)" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium">
                                    {{ getTypeLabel(currentTx.type) }}
                                </span>
                            </p>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">交易金额</label>
                            <p :class="currentTx.type === 'income' || currentTx.type === 'recharge' || currentTx.type === 'refund' ? 'text-green-600' : 'text-red-600'" class="text-lg font-bold">
                                {{ currentTx.type === 'income' || currentTx.type === 'recharge' || currentTx.type === 'refund' ? '+' : '-' }}¥{{ formatNumber(currentTx.amount) }}
                            </p>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">手续费</label>
                            <p class="text-orange-600 font-medium">¥{{ formatNumber(currentTx.fee_amount || 0) }}</p>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">交易前余额</label>
                            <p class="text-sm font-medium text-gray-900">¥{{ formatNumber(currentTx.balance_before) }}</p>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">交易后余额</label>
                            <p class="text-sm font-medium text-gray-900">¥{{ formatNumber(currentTx.balance_after) }}</p>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">状态</label>
                            <p>
                                <span :class="getStatusClass(currentTx.status)" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium">
                                    {{ getStatusLabel(currentTx.status) }}
                                </span>
                            </p>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">交易时间</label>
                            <p class="text-sm text-gray-900">{{ formatDate(currentTx.created_at) }}</p>
                        </div>
                    </div>
                    <div class="pt-4 border-t border-gray-100">
                        <label class="text-xs text-gray-500">描述</label>
                        <p class="text-sm text-gray-900 mt-1">{{ currentTx.description }}</p>
                    </div>
                    <div v-if="currentTx.remark">
                        <label class="text-xs text-gray-500">备注</label>
                        <p class="text-sm text-gray-900 mt-1">{{ currentTx.remark }}</p>
                    </div>
                    <div v-if="currentTx.metadata" class="pt-4 border-t border-gray-100">
                        <label class="text-xs text-gray-500">关联信息</label>
                        <div class="mt-1 p-3 bg-gray-50 rounded-lg text-xs font-mono text-gray-600">
                            {{ JSON.stringify(currentTx.metadata, null, 2) }}
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
                    <button
                        @click="showDetailModal = false"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium"
                    >
                        关闭
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue';
import walletApi from '../../api/wallet';

const loading = ref(false);
const transactions = ref([]);
const selectedIds = ref([]);
const selectAll = ref(false);
const showDetailModal = ref(false);
const currentTx = ref(null);

const filters = reactive({
    type: '',
    start_date: '',
    end_date: '',
    keyword: '',
    page: 1,
    per_page: 20,
});

const pagination = reactive({
    current_page: 1,
    last_page: 1,
    total: 0,
});

const formatNumber = (num) => {
    return new Intl.NumberFormat('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(parseFloat(num || 0));
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')} ${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}:${String(d.getSeconds()).padStart(2, '0')}`;
};

const typeLabels = {
    income: '收入',
    expense: '支出',
    recharge: '充值',
    withdraw: '提现',
    withdraw_fee: '提现手续费',
    refund: '退款',
    transfer_in: '转入',
    transfer_out: '转出',
};

const getTypeLabel = (type) => typeLabels[type] || type;

const getTypeClass = (type) => {
    const classes = {
        income: 'bg-green-100 text-green-800',
        expense: 'bg-red-100 text-red-800',
        recharge: 'bg-blue-100 text-blue-800',
        withdraw: 'bg-orange-100 text-orange-800',
        withdraw_fee: 'bg-amber-100 text-amber-800',
        refund: 'bg-purple-100 text-purple-800',
    };
    return classes[type] || 'bg-gray-100 text-gray-800';
};

const statusLabels = {
    completed: '已完成',
    pending: '处理中',
    failed: '失败',
    cancelled: '已取消',
};

const getStatusLabel = (status) => statusLabels[status] || status;

const getStatusClass = (status) => {
    const classes = {
        completed: 'bg-green-100 text-green-800',
        pending: 'bg-yellow-100 text-yellow-800',
        failed: 'bg-red-100 text-red-800',
        cancelled: 'bg-gray-100 text-gray-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const loadTransactions = async () => {
    loading.value = true;
    try {
        const params = { ...filters };
        const { data } = await walletApi.getTransactions(params);
        const result = data.data || data;
        transactions.value = result.data || result;
        if (result.current_page !== undefined) {
            pagination.current_page = result.current_page;
            pagination.last_page = result.last_page;
            pagination.total = result.total;
        }
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

const changePage = (page) => {
    filters.page = page;
    loadTransactions();
};

const resetFilters = () => {
    filters.type = '';
    filters.start_date = '';
    filters.end_date = '';
    filters.keyword = '';
    filters.page = 1;
    loadTransactions();
};

const showDetail = (tx) => {
    currentTx.value = tx;
    showDetailModal.value = true;
};

const exportData = () => {
    alert('导出功能开发中');
};

watch(selectAll, (val) => {
    if (val) {
        selectedIds.value = transactions.value.map(tx => tx.id);
    } else {
        selectedIds.value = [];
    }
});

watch(selectedIds, () => {
    if (transactions.value.length > 0 && selectedIds.value.length === transactions.value.length) {
        selectAll.value = true;
    } else {
        selectAll.value = false;
    }
}, { deep: true });

onMounted(() => {
    loadTransactions();
});
</script>
