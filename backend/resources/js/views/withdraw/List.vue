<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">提现记录</h1>
                <p class="mt-1 text-sm text-gray-500">查看所有提现申请记录</p>
            </div>
            <div class="flex gap-3">
                <button
                    v-if="canAudit"
                    @click="showBatchAuditModal('approve')"
                    :disabled="selectedIds.length === 0"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    批量通过
                </button>
                <button
                    v-if="canAudit"
                    @click="showBatchAuditModal('reject')"
                    :disabled="selectedIds.length === 0"
                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    批量拒绝
                </button>
                <router-link
                    :to="{ name: 'withdraw.index' }"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium"
                >
                    申请提现
                </router-link>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-yellow-50 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">待审核</div>
                        <div class="text-2xl font-bold text-yellow-600">{{ statistics?.pending_count || 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">处理中</div>
                        <div class="text-2xl font-bold text-blue-600">{{ statistics?.processing_count || 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-green-50 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">已完成</div>
                        <div class="text-2xl font-bold text-green-600">{{ statistics?.completed_count || 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-red-50 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">已失败</div>
                        <div class="text-2xl font-bold text-red-600">{{ statistics?.failed_count || 0 }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">状态</label>
                    <select
                        v-model="filters.status"
                        class="w-full rounded-lg border-gray-300 border px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                    >
                        <option value="">全部状态</option>
                        <option value="pending">待审核</option>
                        <option value="approved">已审核</option>
                        <option value="processing">处理中</option>
                        <option value="completed">已完成</option>
                        <option value="failed">已失败</option>
                        <option value="rejected">已拒绝</option>
                        <option value="cancelled">已取消</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">提现方式</label>
                    <select
                        v-model="filters.method"
                        class="w-full rounded-lg border-gray-300 border px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                    >
                        <option value="">全部方式</option>
                        <option value="bank_transfer">银行转账</option>
                        <option value="alipay">支付宝</option>
                        <option value="wechat">微信支付</option>
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
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">关键词</label>
                    <div class="relative">
                        <input
                            v-model="filters.keyword"
                            type="text"
                            placeholder="搜索提现单号/申请人"
                            class="w-full rounded-lg border-gray-300 border pl-10 pr-3 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                            @keyup.enter="loadWithdrawals"
                        />
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="flex items-end gap-3">
                    <button
                        @click="resetFilters"
                        class="flex-1 px-4 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium"
                    >
                        重置
                    </button>
                    <button
                        @click="loadWithdrawals"
                        class="flex-1 px-4 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium"
                    >
                        查询
                    </button>
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
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <input
                                        type="checkbox"
                                        v-model="selectAll"
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">提现单号</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">申请人</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">金额</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">手续费</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">实际到账</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">方式</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">状态</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">申请时间</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr
                                v-for="withdraw in withdrawals"
                                :key="withdraw.id"
                                class="hover:bg-gray-50 transition-colors"
                            >
                                <td class="px-6 py-4">
                                    <input
                                        type="checkbox"
                                        :value="withdraw.id"
                                        v-model="selectedIds"
                                        :disabled="!canAuditItem(withdraw)"
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 disabled:opacity-40"
                                    />
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-mono text-sm text-gray-900">{{ withdraw.withdraw_no }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">{{ withdraw.user?.name || '-' }}</div>
                                    <div class="text-xs text-gray-500">{{ withdraw.user?.email || '' }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm font-semibold text-red-600">¥{{ formatNumber(withdraw.amount) }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-orange-600">¥{{ formatNumber(withdraw.fee_amount) }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm font-medium text-green-600">¥{{ formatNumber(withdraw.actual_amount) }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-gray-600">{{ getMethodLabel(withdraw.method) }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="getStatusClass(withdraw.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                        {{ getStatusLabel(withdraw.status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-gray-500">{{ formatDate(withdraw.created_at) }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <button
                                            @click="showDetail(withdraw)"
                                            class="text-indigo-600 hover:text-indigo-700 text-sm font-medium"
                                        >
                                            详情
                                        </button>
                                        <template v-if="canAudit && withdraw.status === 'pending'">
                                            <button
                                                @click="openAuditModal(withdraw, 'approve')"
                                                class="text-green-600 hover:text-green-700 text-sm font-medium"
                                            >
                                                通过
                                            </button>
                                            <button
                                                @click="openAuditModal(withdraw, 'reject')"
                                                class="text-red-600 hover:text-red-700 text-sm font-medium"
                                            >
                                                拒绝
                                            </button>
                                        </template>
                                        <template v-if="canProcess && withdraw.status === 'approved'">
                                            <button
                                                @click="processWithdraw(withdraw)"
                                                class="text-blue-600 hover:text-blue-700 text-sm font-medium"
                                            >
                                                处理
                                            </button>
                                        </template>
                                        <template v-if="withdraw.status === 'pending' && !canAudit">
                                            <button
                                                @click="cancelWithdraw(withdraw)"
                                                class="text-gray-600 hover:text-gray-700 text-sm font-medium"
                                            >
                                                取消
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                    <div class="text-sm text-gray-500">
                        共 {{ pagination.total }} 条记录
                    </div>
                    <div class="flex items-center gap-2">
                        <select
                            v-model="filters.per_page"
                            @change="loadWithdrawals"
                            class="rounded-lg border-gray-300 border px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        >
                            <option :value="10">10条/页</option>
                            <option :value="20">20条/页</option>
                            <option :value="50">50条/页</option>
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
                <h3 class="text-lg font-medium text-gray-900 mb-1">暂无提现记录</h3>
                <p class="text-gray-500">当前筛选条件下没有找到提现记录</p>
            </div>
        </div>

        <div
            v-if="showDetailModal"
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
            @click.self="showDetailModal = false"
        >
            <div class="bg-white rounded-2xl p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">提现详情</h3>
                    <button
                        @click="showDetailModal = false"
                        class="text-gray-400 hover:text-gray-600"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div v-if="currentWithdraw" class="space-y-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs text-gray-500">提现单号</label>
                            <p class="font-mono text-sm font-medium text-gray-900">{{ currentWithdraw.withdraw_no }}</p>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">状态</label>
                            <p>
                                <span :class="getStatusClass(currentWithdraw.status)" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium">
                                    {{ getStatusLabel(currentWithdraw.status) }}
                                </span>
                            </p>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">申请人</label>
                            <p class="text-sm font-medium text-gray-900">{{ currentWithdraw.user?.name || '-' }}</p>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">申请时间</label>
                            <p class="text-sm text-gray-900">{{ formatDate(currentWithdraw.created_at) }}</p>
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-xl p-4">
                        <div class="grid grid-cols-3 gap-4 text-center">
                            <div>
                                <div class="text-xs text-gray-500">提现金额</div>
                                <div class="text-xl font-bold text-red-600">¥{{ formatNumber(currentWithdraw.amount) }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500">手续费</div>
                                <div class="text-xl font-bold text-orange-600">¥{{ formatNumber(currentWithdraw.fee_amount) }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500">实际到账</div>
                                <div class="text-xl font-bold text-green-600">¥{{ formatNumber(currentWithdraw.actual_amount) }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs text-gray-500">提现方式</label>
                            <p class="text-sm font-medium text-gray-900">{{ getMethodLabel(currentWithdraw.method) }}</p>
                        </div>
                        <div v-if="currentWithdraw.method === 'bank_transfer'">
                            <label class="text-xs text-gray-500">开户银行</label>
                            <p class="text-sm text-gray-900">{{ currentWithdraw.bank_name }}</p>
                        </div>
                        <div v-if="currentWithdraw.method === 'bank_transfer'">
                            <label class="text-xs text-gray-500">银行账号</label>
                            <p class="text-sm font-mono text-gray-900">{{ currentWithdraw.bank_account }}</p>
                        </div>
                        <div v-if="currentWithdraw.method === 'bank_transfer'">
                            <label class="text-xs text-gray-500">开户姓名</label>
                            <p class="text-sm text-gray-900">{{ currentWithdraw.account_name }}</p>
                        </div>
                        <div v-if="currentWithdraw.method === 'alipay'">
                            <label class="text-xs text-gray-500">支付宝账号</label>
                            <p class="text-sm text-gray-900">{{ currentWithdraw.alipay_account }}</p>
                        </div>
                        <div v-if="currentWithdraw.method === 'wechat'">
                            <label class="text-xs text-gray-500">微信账号</label>
                            <p class="text-sm text-gray-900">{{ currentWithdraw.wechat_account }}</p>
                        </div>
                    </div>

                    <div v-if="currentWithdraw.remark">
                        <label class="text-xs text-gray-500">备注</label>
                        <p class="text-sm text-gray-900 mt-1">{{ currentWithdraw.remark }}</p>
                    </div>

                    <div v-if="currentWithdraw.audit_records && currentWithdraw.audit_records.length > 0">
                        <label class="text-xs text-gray-500">审核记录</label>
                        <div class="mt-2 space-y-2">
                            <div
                                v-for="record in currentWithdraw.audit_records"
                                :key="record.id"
                                class="p-3 bg-gray-50 rounded-lg"
                            >
                                <div class="flex items-center justify-between">
                                    <span :class="record.action === 'approve' ? 'text-green-600' : 'text-red-600'" class="text-sm font-medium">
                                        {{ record.action === 'approve' ? '审核通过' : '审核拒绝' }}
                                    </span>
                                    <span class="text-xs text-gray-500">{{ formatDate(record.created_at) }}</span>
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    操作人: {{ record.auditor?.name || '-' }}
                                </div>
                                <div v-if="record.remark" class="text-sm text-gray-600 mt-1">
                                    备注: {{ record.remark }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
                    <template v-if="canAudit && currentWithdraw?.status === 'pending'">
                        <button
                            @click="openAuditModal(currentWithdraw, 'approve')"
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium"
                        >
                            审核通过
                        </button>
                        <button
                            @click="openAuditModal(currentWithdraw, 'reject')"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-sm font-medium"
                        >
                            审核拒绝
                        </button>
                    </template>
                    <button
                        @click="showDetailModal = false"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium"
                    >
                        关闭
                    </button>
                </div>
            </div>
        </div>

        <div
            v-if="showAuditModal"
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
            @click.self="showAuditModal = false"
        >
            <div class="bg-white rounded-2xl p-6 max-w-md w-full mx-4">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">
                        {{ auditAction === 'approve' ? '审核通过' : '审核拒绝' }}
                    </h3>
                    <button
                        @click="showAuditModal = false"
                        class="text-gray-400 hover:text-gray-600"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="space-y-4">
                    <div v-if="auditAction === 'reject'">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            拒绝原因 <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            v-model="auditRemark"
                            rows="3"
                            placeholder="请输入拒绝原因"
                            class="w-full rounded-lg border-gray-300 border px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm resize-none"
                            required
                        ></textarea>
                    </div>
                    <div v-else>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            备注（选填）
                        </label>
                        <textarea
                            v-model="auditRemark"
                            rows="3"
                            placeholder="请输入审核备注"
                            class="w-full rounded-lg border-gray-300 border px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm resize-none"
                        ></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
                    <button
                        @click="showAuditModal = false"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium"
                    >
                        取消
                    </button>
                    <button
                        @click="submitAudit"
                        :disabled="submitting || (auditAction === 'reject' && !auditRemark)"
                        class="px-4 py-2 rounded-lg text-white text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        :class="auditAction === 'approve' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700'"
                    >
                        {{ submitting ? '处理中...' : '确认' + (auditAction === 'approve' ? '通过' : '拒绝') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import withdrawApi from '../../api/withdraw';
import { useAuthStore } from '../../stores/auth';

const router = useRouter();
const auth = useAuthStore();

const loading = ref(false);
const submitting = ref(false);
const withdrawals = ref([]);
const statistics = ref(null);
const selectedIds = ref([]);
const selectAll = ref(false);
const showDetailModal = ref(false);
const showAuditModal = ref(false);
const showBatchAuditModalFlag = ref(false);
const currentWithdraw = ref(null);
const auditAction = ref('');
const auditRemark = ref('');

const filters = reactive({
    status: '',
    method: '',
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

const canAudit = computed(() => auth.can('withdraw.audit'));
const canProcess = computed(() => auth.can('withdraw.process'));

const canAuditItem = (withdraw) => {
    return canAudit.value && withdraw.status === 'pending';
};

const formatNumber = (num) => {
    return new Intl.NumberFormat('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(parseFloat(num || 0));
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')} ${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
};

const methodLabels = {
    bank_transfer: '银行转账',
    alipay: '支付宝',
    wechat: '微信支付',
    cash: '现金',
};

const getMethodLabel = (method) => methodLabels[method] || method;

const statusLabels = {
    pending: '待审核',
    approved: '已审核',
    processing: '处理中',
    completed: '已完成',
    failed: '已失败',
    rejected: '已拒绝',
    cancelled: '已取消',
};

const getStatusLabel = (status) => statusLabels[status] || status;

const getStatusClass = (status) => {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-800',
        approved: 'bg-blue-100 text-blue-800',
        processing: 'bg-indigo-100 text-indigo-800',
        completed: 'bg-green-100 text-green-800',
        failed: 'bg-red-100 text-red-800',
        rejected: 'bg-red-100 text-red-800',
        cancelled: 'bg-gray-100 text-gray-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const loadStatistics = async () => {
    try {
        const { data } = await withdrawApi.getStatistics();
        statistics.value = data.data || data;
    } catch (e) {
        console.error(e);
    }
};

const loadWithdrawals = async () => {
    loading.value = true;
    try {
        const params = { ...filters };
        const { data } = await withdrawApi.getList(params);
        const result = data.data || data;
        withdrawals.value = result.data || result;
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
    loadWithdrawals();
};

const resetFilters = () => {
    filters.status = '';
    filters.method = '';
    filters.start_date = '';
    filters.end_date = '';
    filters.keyword = '';
    filters.page = 1;
    loadWithdrawals();
};

const showDetail = (withdraw) => {
    currentWithdraw.value = withdraw;
    showDetailModal.value = true;
};

const openAuditModal = (withdraw, action) => {
    currentWithdraw.value = withdraw;
    auditAction.value = action;
    auditRemark.value = '';
    showAuditModal.value = true;
};

const showBatchAuditModal = (action) => {
    auditAction.value = action;
    auditRemark.value = '';
    showAuditModal.value = true;
};

const submitAudit = async () => {
    if (auditAction.value === 'reject' && !auditRemark.value) {
        alert('请输入拒绝原因');
        return;
    }

    submitting.value = true;
    try {
        if (selectedIds.value.length > 0) {
            if (auditAction.value === 'approve') {
                await withdrawApi.batchApprove({ ids: selectedIds.value, remark: auditRemark.value });
            } else {
                await withdrawApi.batchReject({ ids: selectedIds.value, remark: auditRemark.value });
            }
            selectedIds.value = [];
            selectAll.value = false;
        } else if (currentWithdraw.value) {
            if (auditAction.value === 'approve') {
                await withdrawApi.approve(currentWithdraw.value.id, { remark: auditRemark.value });
            } else {
                await withdrawApi.reject(currentWithdraw.value.id, { remark: auditRemark.value });
            }
        }

        showAuditModal.value = false;
        showDetailModal.value = false;
        loadWithdrawals();
        loadStatistics();
        alert('操作成功');
    } catch (e) {
        alert(e.response?.data?.message || '操作失败');
    } finally {
        submitting.value = false;
    }
};

const processWithdraw = async (withdraw) => {
    if (!confirm('确认开始处理该提现申请吗？')) return;
    try {
        await withdrawApi.process(withdraw.id);
        loadWithdrawals();
        loadStatistics();
        alert('处理成功');
    } catch (e) {
        alert(e.response?.data?.message || '操作失败');
    }
};

const cancelWithdraw = async (withdraw) => {
    if (!confirm('确认取消该提现申请吗？')) return;
    try {
        await withdrawApi.cancel(withdraw.id);
        loadWithdrawals();
        loadStatistics();
        alert('取消成功');
    } catch (e) {
        alert(e.response?.data?.message || '操作失败');
    }
};

watch(selectAll, (val) => {
    if (val) {
        selectedIds.value = withdrawals.value.filter(w => canAuditItem(w)).map(w => w.id);
    } else {
        selectedIds.value = [];
    }
});

onMounted(() => {
    loadStatistics();
    loadWithdrawals();
});
</script>
