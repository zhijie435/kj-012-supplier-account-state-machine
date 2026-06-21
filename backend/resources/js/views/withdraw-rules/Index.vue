<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">提现规则管理</h1>
                <p class="mt-1 text-sm text-gray-500">配置不同用户等级和提现方式的提现规则</p>
            </div>
            <button
                @click="showCreateModal"
                :disabled="!globallyEnabled"
                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                添加规则
            </button>
        </div>

        <div
            v-if="!globallyEnabled"
            class="rounded-xl border border-red-200 bg-red-50 p-4 flex items-start gap-3"
        >
            <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div class="flex-1">
                <div class="font-medium text-red-800">提现功能已全局关闭</div>
                <div class="text-sm text-red-700 mt-1">
                    所有提现规则当前均不生效。请前往「
                    <router-link :to="{ name: 'withdraw-config.index' }" class="underline font-medium hover:text-red-900">
                        提现规则配置
                    </router-link>
                    」页面开启「启用提现功能」开关。
                </div>
            </div>
        </div>

        <div
            v-else
            class="rounded-xl border border-green-200 bg-green-50 p-4 flex items-start gap-3"
        >
            <svg class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="flex-1">
                <div class="font-medium text-green-800">提现功能运行正常</div>
                <div class="text-sm text-green-700 mt-1">
                    全局配置与规则列表同步生效。修改全局参数会自动同步到所有提现规则，
                    <router-link :to="{ name: 'withdraw-config' }" class="underline font-medium hover:text-green-900">
                        点击查看全局配置 →
                    </router-link>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex flex-wrap items-center gap-4">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-medium text-gray-500 mb-1">用户等级</label>
                    <select
                        v-model="filters.user_level"
                        @change="loadRules"
                        class="w-full rounded-lg border-gray-300 border px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    >
                        <option value="">全部等级</option>
                        <option v-for="level in userLevels" :key="level.value" :value="level.value">
                            {{ level.label }}
                        </option>
                    </select>
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-medium text-gray-500 mb-1">提现方式</label>
                    <select
                        v-model="filters.withdraw_method_id"
                        @change="loadRules"
                        class="w-full rounded-lg border-gray-300 border px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    >
                        <option value="">全部方式</option>
                        <option v-for="method in methods" :key="method.id" :value="method.id">
                            {{ method.name }}
                        </option>
                    </select>
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-medium text-gray-500 mb-1">状态</label>
                    <select
                        v-model="filters.status"
                        @change="loadRules"
                        class="w-full rounded-lg border-gray-300 border px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    >
                        <option value="">全部</option>
                        <option :value="true">启用</option>
                        <option :value="false">禁用</option>
                    </select>
                </div>
            </div>
        </div>

        <div v-if="loading" class="flex justify-center py-12">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-indigo-600 border-t-transparent"></div>
        </div>

        <div v-else class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
            <div v-if="rules.length > 0">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">规则名称</th>
                                <th class="px-5 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">用户等级</th>
                                <th class="px-5 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">提现方式</th>
                                <th class="px-5 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">金额范围</th>
                                <th class="px-5 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">手续费</th>
                                <th class="px-5 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">日/月限额</th>
                                <th class="px-5 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">需审核</th>
                                <th class="px-5 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">处理天数</th>
                                <th class="px-5 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">状态</th>
                                <th class="px-5 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr
                                v-for="rule in rules"
                                :key="rule.id"
                                class="hover:bg-gray-50 transition-colors"
                            >
                                <td class="px-5 py-4">
                                    <div class="font-medium text-gray-900">{{ rule.name }}</div>
                                    <div v-if="rule.remark" class="text-xs text-gray-500 mt-0.5 max-w-[200px] truncate" :title="rule.remark">
                                        {{ rule.remark }}
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span :class="getUserLevelClass(rule.user_level)">
                                        {{ getUserLevelLabel(rule.user_level) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2">
                                        <div v-html="getMethodIcon(rule.method?.code)" class="w-5 h-5"></div>
                                        <span class="text-sm text-gray-700">{{ rule.method?.name || '-' }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="text-sm text-gray-700">
                                        ¥{{ formatNumber(rule.min_amount) }} ~ ¥{{ formatNumber(rule.max_amount) }}
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="text-sm text-gray-700">
                                        <div v-if="rule.fee_rate > 0">
                                            {{ (rule.fee_rate * 100).toFixed(2) }}%
                                            <span v-if="rule.fixed_fee > 0" class="text-xs"> + ¥{{ formatNumber(rule.fixed_fee) }}</span>
                                        </div>
                                        <div v-else-if="rule.fixed_fee > 0">
                                            ¥{{ formatNumber(rule.fixed_fee) }}
                                        </div>
                                        <div v-else class="text-green-600 font-medium">免费</div>
                                        <div v-if="rule.min_fee > 0 || rule.max_fee > 0" class="text-xs text-gray-500 mt-0.5">
                                            <span v-if="rule.min_fee > 0">最低 ¥{{ formatNumber(rule.min_fee) }}</span>
                                            <span v-if="rule.min_fee > 0 && rule.max_fee > 0"> / </span>
                                            <span v-if="rule.max_fee > 0">最高 ¥{{ formatNumber(rule.max_fee) }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="text-sm text-gray-700 space-y-1">
                                        <div class="flex items-center gap-1">
                                            <span class="inline-block w-8 text-xs text-gray-500">日:</span>
                                            <span v-if="rule.daily_max_amount > 0">¥{{ formatNumber(rule.daily_max_amount) }}</span>
                                            <span v-else class="text-gray-400">不限</span>
                                            <span v-if="rule.daily_max_count > 0" class="text-xs text-gray-500">
                                                ({{ rule.daily_max_count }}次)
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <span class="inline-block w-8 text-xs text-gray-500">月:</span>
                                            <span v-if="rule.monthly_max_amount > 0">¥{{ formatNumber(rule.monthly_max_amount) }}</span>
                                            <span v-else class="text-gray-400">不限</span>
                                            <span v-if="rule.monthly_max_count > 0" class="text-xs text-gray-500">
                                                ({{ rule.monthly_max_count }}次)
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span :class="[
                                        'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
                                        rule.requires_audit
                                            ? 'bg-amber-100 text-amber-800'
                                            : 'bg-green-100 text-green-800'
                                    ]">
                                        {{ rule.requires_audit ? '需要审核' : '自动通过' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="text-sm text-gray-700">T+{{ rule.processing_days }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="space-y-1.5">
                                        <div v-if="rule.is_effectively_enabled && globallyEnabled" class="flex items-center gap-1.5">
                                            <span class="relative inline-flex h-2 w-2 rounded-full bg-green-500"></span>
                                            <span class="text-xs text-green-700 font-medium">生效中</span>
                                        </div>
                                        <div v-else class="flex items-center gap-1.5">
                                            <span class="relative inline-flex h-2 w-2 rounded-full bg-gray-400"></span>
                                            <span class="text-xs text-gray-600 font-medium">
                                                {{ !globallyEnabled ? '全局关闭' : (rule.status ? '方式禁用' : '规则禁用') }}
                                            </span>
                                        </div>
                                        <button
                                            @click="toggleStatus(rule)"
                                            :disabled="!globallyEnabled"
                                            :class="[
                                                'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                                                rule.status ? 'bg-indigo-600' : 'bg-gray-200',
                                                !globallyEnabled ? 'opacity-50 cursor-not-allowed' : ''
                                            ]"
                                        >
                                            <span
                                                :class="[
                                                    'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                                                    rule.status ? 'translate-x-6' : 'translate-x-1'
                                                ]"
                                            />
                                        </button>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2">
                                        <button
                                            @click="viewRule(rule)"
                                            class="text-gray-600 hover:text-gray-800 text-sm font-medium"
                                            title="详情"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </button>
                                        <button
                                            @click="editRule(rule)"
                                            class="text-indigo-600 hover:text-indigo-700 text-sm font-medium"
                                            title="编辑"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                        <button
                                            @click="deleteRule(rule)"
                                            class="text-red-600 hover:text-red-700 text-sm font-medium"
                                            title="删除"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="pagination && pagination.last_page > 1" class="px-5 py-4 border-t border-gray-100 flex items-center justify-between">
                    <div class="text-sm text-gray-600">
                        共 {{ pagination.total }} 条，第 {{ pagination.current_page }}/{{ pagination.last_page }} 页
                    </div>
                    <div class="flex gap-2">
                        <button
                            @click="prevPage"
                            :disabled="pagination.current_page <= 1"
                            class="px-3 py-1.5 border border-gray-300 rounded text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                        >
                            上一页
                        </button>
                        <button
                            @click="nextPage"
                            :disabled="pagination.current_page >= pagination.last_page"
                            class="px-3 py-1.5 border border-gray-300 rounded text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                        >
                            下一页
                        </button>
                    </div>
                </div>
            </div>

            <div v-else class="text-center py-16">
                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-1">暂无提现规则</h3>
                <p class="text-gray-500 mb-4">创建提现规则以管理用户提现</p>
                <button
                    @click="showCreateModal"
                    class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium"
                >
                    添加规则
                </button>
            </div>
        </div>

        <div
            v-if="showFormModal || showDetailModal"
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
            @click.self="closeModals"
        >
            <div class="bg-white rounded-2xl p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">
                        {{ showDetailModal ? '规则详情' : (editingRule ? '编辑提现规则' : '添加提现规则') }}
                    </h3>
                    <button
                        @click="closeModals"
                        class="text-gray-400 hover:text-gray-600"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form v-if="showFormModal" @submit.prevent="submitForm" class="space-y-5">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                规则名称 <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                placeholder="例如：普通用户银行转账规则"
                                class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                用户等级 <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="form.user_level"
                                class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                required
                            >
                                <option v-for="level in userLevels" :key="level.value" :value="level.value">
                                    {{ level.label }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                提现方式 <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="form.withdraw_method_id"
                                class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                required
                            >
                                <option value="">请选择提现方式</option>
                                <option v-for="method in methods" :key="method.id" :value="method.id">
                                    {{ method.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-4 space-y-4">
                        <h4 class="text-sm font-medium text-gray-700">金额限制</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">单笔最低金额（元）*</label>
                                <input
                                    v-model.number="form.min_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="w-full rounded border-gray-300 border px-3 py-2 text-sm"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">单笔最高金额（元）*</label>
                                <input
                                    v-model.number="form.max_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="w-full rounded border-gray-300 border px-3 py-2 text-sm"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">每日最高金额（元）</label>
                                <input
                                    v-model.number="form.daily_max_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="0 表示不限"
                                    class="w-full rounded border-gray-300 border px-3 py-2 text-sm"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">每日最大次数</label>
                                <input
                                    v-model.number="form.daily_max_count"
                                    type="number"
                                    step="1"
                                    min="0"
                                    placeholder="0 表示不限"
                                    class="w-full rounded border-gray-300 border px-3 py-2 text-sm"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">每月最高金额（元）</label>
                                <input
                                    v-model.number="form.monthly_max_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="0 表示不限"
                                    class="w-full rounded border-gray-300 border px-3 py-2 text-sm"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">每月最大次数</label>
                                <input
                                    v-model.number="form.monthly_max_count"
                                    type="number"
                                    step="1"
                                    min="0"
                                    placeholder="0 表示不限"
                                    class="w-full rounded border-gray-300 border px-3 py-2 text-sm"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-4 space-y-4">
                        <h4 class="text-sm font-medium text-gray-700">手续费配置</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">手续费率（%）</label>
                                <input
                                    v-model.number="form.fee_rate"
                                    type="number"
                                    step="0.0001"
                                    min="0"
                                    max="100"
                                    placeholder="0.00 表示免费"
                                    class="w-full rounded border-gray-300 border px-3 py-2 text-sm"
                                />
                                <p class="text-xs text-gray-500 mt-1">例：0.5% 填 0.005</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">固定手续费（元）</label>
                                <input
                                    v-model.number="form.fixed_fee"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="0 表示无固定费用"
                                    class="w-full rounded border-gray-300 border px-3 py-2 text-sm"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">最低手续费（元）</label>
                                <input
                                    v-model.number="form.min_fee"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="0 表示不限"
                                    class="w-full rounded border-gray-300 border px-3 py-2 text-sm"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">最高手续费（元）</label>
                                <input
                                    v-model.number="form.max_fee"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="0 表示不限"
                                    class="w-full rounded border-gray-300 border px-3 py-2 text-sm"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-4 space-y-4">
                        <h4 class="text-sm font-medium text-gray-700">审核与处理</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">处理工作日（T+N）</label>
                                <input
                                    v-model.number="form.processing_days"
                                    type="number"
                                    step="1"
                                    min="0"
                                    class="w-full rounded border-gray-300 border px-3 py-2 text-sm"
                                />
                            </div>
                            <div class="flex items-center gap-2 pt-6">
                                <input
                                    v-model="form.requires_audit"
                                    type="checkbox"
                                    id="requires_audit"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                <label for="requires_audit" class="text-sm text-gray-700">
                                    需要人工审核
                                </label>
                            </div>
                            <div class="flex items-center gap-2">
                                <input
                                    v-model="form.status"
                                    type="checkbox"
                                    id="rule_status"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                <label for="rule_status" class="text-sm text-gray-700">
                                    启用该规则
                                </label>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">备注</label>
                        <textarea
                            v-model="form.remark"
                            rows="2"
                            placeholder="规则说明或备注信息"
                            class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 resize-none"
                        ></textarea>
                    </div>

                    <div class="bg-indigo-50 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-indigo-800 mb-2">费用预览</h4>
                        <div class="grid grid-cols-3 gap-4 text-sm">
                            <div>
                                <span class="text-indigo-600">提现 ¥1000：</span>
                                <span class="font-medium text-indigo-900">¥{{ formatNumber(calcFee(1000)) }}</span>
                            </div>
                            <div>
                                <span class="text-indigo-600">提现 ¥10000：</span>
                                <span class="font-medium text-indigo-900">¥{{ formatNumber(calcFee(10000)) }}</span>
                            </div>
                            <div>
                                <span class="text-indigo-600">提现 ¥50000：</span>
                                <span class="font-medium text-indigo-900">¥{{ formatNumber(calcFee(50000)) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button
                            type="button"
                            @click="closeModals"
                            class="px-4 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium"
                        >
                            取消
                        </button>
                        <button
                            type="submit"
                            :disabled="submitting"
                            class="px-4 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {{ submitting ? '保存中...' : '保存' }}
                        </button>
                    </div>
                </form>

                <div v-if="showDetailModal && detailData" class="space-y-5">
                    <div class="grid grid-cols-1 gap-4 text-sm">
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-500">规则名称</span>
                            <span class="font-medium text-gray-900">{{ detailData.name }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-500">用户等级</span>
                            <span class="font-medium text-gray-900">{{ getUserLevelLabel(detailData.user_level) }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-500">提现方式</span>
                            <span class="font-medium text-gray-900">{{ detailData.method?.name || '-' }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-500">状态</span>
                            <span :class="detailData.status ? 'text-green-600' : 'text-gray-500'" class="font-medium">
                                {{ detailData.status ? '已启用' : '已禁用' }}
                            </span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-500">金额范围</span>
                            <span class="font-medium text-gray-900">
                                ¥{{ formatNumber(detailData.min_amount) }} ~ ¥{{ formatNumber(detailData.max_amount) }}
                            </span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-500">日限额</span>
                            <span class="font-medium text-gray-900">
                                {{ detailData.daily_max_amount > 0 ? '¥' + formatNumber(detailData.daily_max_amount) : '不限' }}
                            </span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-500">日次数</span>
                            <span class="font-medium text-gray-900">
                                {{ detailData.daily_max_count > 0 ? detailData.daily_max_count + ' 次' : '不限' }}
                            </span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-500">月限额</span>
                            <span class="font-medium text-gray-900">
                                {{ detailData.monthly_max_amount > 0 ? '¥' + formatNumber(detailData.monthly_max_amount) : '不限' }}
                            </span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-500">月次数</span>
                            <span class="font-medium text-gray-900">
                                {{ detailData.monthly_max_count > 0 ? detailData.monthly_max_count + ' 次' : '不限' }}
                            </span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-500">手续费率</span>
                            <span class="font-medium text-gray-900">{{ (detailData.fee_rate * 100).toFixed(2) }}%</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-500">固定费用</span>
                            <span class="font-medium text-gray-900">¥{{ formatNumber(detailData.fixed_fee) }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-500">手续费范围</span>
                            <span class="font-medium text-gray-900">
                                ¥{{ formatNumber(detailData.min_fee) }} ~ {{ detailData.max_fee > 0 ? '¥' + formatNumber(detailData.max_fee) : '不限' }}
                            </span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-500">审核</span>
                            <span class="font-medium text-gray-900">{{ detailData.requires_audit ? '需要审核' : '自动通过' }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-500">处理时效</span>
                            <span class="font-medium text-gray-900">T+{{ detailData.processing_days }}</span>
                        </div>
                    </div>
                    <div v-if="detailData.remark" class="pt-3 border-t border-gray-100">
                        <div class="text-xs text-gray-500 mb-1">备注</div>
                        <div class="text-sm text-gray-700 bg-gray-50 p-3 rounded-lg">{{ detailData.remark }}</div>
                    </div>
                    <div class="flex justify-end pt-4">
                        <button
                            @click="closeModals"
                            class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium"
                        >
                            关闭
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import withdrawRuleApi from '../../api/withdrawRule';
import withdrawMethodApi from '../../api/withdrawMethod';

const loading = ref(false);
const submitting = ref(false);
const rules = ref([]);
const methods = ref([]);
const pagination = ref(null);
const showFormModal = ref(false);
const showDetailModal = ref(false);
const editingRule = ref(null);
const detailData = ref(null);
const globallyEnabled = ref(true);

const filters = reactive({
    user_level: '',
    withdraw_method_id: '',
    status: '',
    page: 1,
    per_page: 20,
});

const userLevels = [
    { value: 'normal', label: '普通用户' },
    { value: 'vip', label: 'VIP用户' },
    { value: 'svip', label: 'SVIP用户' },
    { value: 'supplier', label: '供应商' },
    { value: 'admin', label: '平台管理员' },
];

const form = reactive({
    name: '',
    user_level: 'normal',
    withdraw_method_id: '',
    min_amount: 0,
    max_amount: 0,
    daily_max_amount: 0,
    daily_max_count: 0,
    monthly_max_amount: 0,
    monthly_max_count: 0,
    fee_rate: 0,
    fixed_fee: 0,
    min_fee: 0,
    max_fee: 0,
    processing_days: 1,
    requires_audit: true,
    status: true,
    remark: '',
});

const methodIcons = {
    bank_transfer: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>',
    alipay: '<svg class="w-5 h-5 text-blue-500" viewBox="0 0 24 24" fill="currentColor"><path d="M20.422 20.787c-3.03-1.356-5.69-2.74-6.55-3.14-2.42 1.43-4.41 2.12-6.46 2.12-3.27 0-5.51-1.95-5.51-5.05 0-2.76 1.96-5.05 5.31-5.05 2.35 0 4.3.88 5.71 2.06.31.35.56.71.76 1.06l.06.09.36-1.4h-5.26v-.83h6.64l.28-.01.33-1.24h1.56l-.7 3.08c.65.96 1.17 1.98 1.55 2.99 1.15 3.07 1.54 5.04 1.96 5.31z"/></svg>',
    wechat: '<svg class="w-5 h-5 text-green-500" viewBox="0 0 24 24" fill="currentColor"><path d="M8.691 2.188C3.891 2.188 0 5.476 0 9.53c0 2.212 1.17 4.203 3.002 5.55a.59.59 0 0 1 .213.665l-.39 1.48c-.019.07-.048.141-.048.213 0 .163.13.295.29.295a.326.326 0 0 0 .167-.054l1.903-1.114a.864.864 0 0 1 .717-.098 10.16 10.16 0 0 0 2.837.403c.276 0 .543-.027.811-.05-.857-2.578.157-4.972 1.932-6.446 1.703-1.415 3.882-1.98 5.853-1.838-.576-3.583-4.196-6.348-8.596-6.348z"/></svg>',
};

const getUserLevelLabel = (level) => {
    const found = userLevels.find(l => l.value === level);
    return found ? found.label : level;
};

const getUserLevelClass = (level) => {
    const classes = {
        normal: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800',
        vip: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800',
        svip: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800',
        supplier: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800',
        admin: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800',
    };
    return classes[level] || classes.normal;
};

const getMethodIcon = (code) => {
    return methodIcons[code] || '<svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>';
};

const formatNumber = (num) => {
    return new Intl.NumberFormat('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(parseFloat(num || 0));
};

const calcFee = (amount) => {
    if (!amount) return 0;
    let fee = amount * (form.fee_rate || 0) + (form.fixed_fee || 0);
    if (form.min_fee > 0 && fee < form.min_fee) fee = form.min_fee;
    if (form.max_fee > 0 && fee > form.max_fee) fee = form.max_fee;
    return Math.round(fee * 100) / 100;
};

const loadRules = async () => {
    loading.value = true;
    try {
        const params = { ...filters };
        if (!params.user_level) delete params.user_level;
        if (!params.withdraw_method_id) delete params.withdraw_method_id;
        if (params.status === '') delete params.status;

        const { data } = await withdrawRuleApi.getList(params);
        const result = data.data || data;
        if (result.data) {
            rules.value = result.data;
            if (result.data.length > 0 && result.data[0].globally_enabled !== undefined) {
                globallyEnabled.value = !!result.data[0].globally_enabled;
            }
            pagination.value = {
                current_page: result.current_page,
                last_page: result.last_page,
                total: result.total,
                per_page: result.per_page,
            };
        } else {
            rules.value = result;
            if (result.length > 0 && result[0].globally_enabled !== undefined) {
                globallyEnabled.value = !!result[0].globally_enabled;
            }
        }
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

const loadMethods = async () => {
    try {
        const { data } = await withdrawMethodApi.getList();
        methods.value = data.data || data;
    } catch (e) {
        console.error(e);
    }
};

const prevPage = () => {
    if (filters.page > 1) {
        filters.page--;
        loadRules();
    }
};

const nextPage = () => {
    if (pagination.value && filters.page < pagination.value.last_page) {
        filters.page++;
        loadRules();
    }
};

const resetForm = () => {
    Object.assign(form, {
        name: '',
        user_level: 'normal',
        withdraw_method_id: '',
        min_amount: 0,
        max_amount: 0,
        daily_max_amount: 0,
        daily_max_count: 0,
        monthly_max_amount: 0,
        monthly_max_count: 0,
        fee_rate: 0,
        fixed_fee: 0,
        min_fee: 0,
        max_fee: 0,
        processing_days: 1,
        requires_audit: true,
        status: true,
        remark: '',
    });
};

const showCreateModal = () => {
    editingRule.value = null;
    resetForm();
    showDetailModal.value = false;
    showFormModal.value = true;
};

const editRule = (rule) => {
    editingRule.value = rule;
    Object.assign(form, {
        name: rule.name,
        user_level: rule.user_level,
        withdraw_method_id: rule.withdraw_method_id,
        min_amount: rule.min_amount,
        max_amount: rule.max_amount,
        daily_max_amount: rule.daily_max_amount,
        daily_max_count: rule.daily_max_count,
        monthly_max_amount: rule.monthly_max_amount,
        monthly_max_count: rule.monthly_max_count,
        fee_rate: rule.fee_rate,
        fixed_fee: rule.fixed_fee,
        min_fee: rule.min_fee,
        max_fee: rule.max_fee,
        processing_days: rule.processing_days,
        requires_audit: rule.requires_audit,
        status: rule.status,
        remark: rule.remark || '',
    });
    showDetailModal.value = false;
    showFormModal.value = true;
};

const viewRule = (rule) => {
    detailData.value = rule;
    showFormModal.value = false;
    showDetailModal.value = true;
};

const submitForm = async () => {
    submitting.value = true;
    try {
        const data = { ...form };
        if (editingRule.value) {
            await withdrawRuleApi.update(editingRule.value.id, data);
        } else {
            await withdrawRuleApi.create(data);
        }
        closeModals();
        loadRules();
        alert('保存成功');
    } catch (e) {
        alert(e.response?.data?.message || '保存失败');
    } finally {
        submitting.value = false;
    }
};

const toggleStatus = async (rule) => {
    const newStatus = !rule.status;
    try {
        await withdrawRuleApi.toggleStatus(rule.id, newStatus);
        rule.status = newStatus;
    } catch (e) {
        alert(e.response?.data?.message || '操作失败');
    }
};

const deleteRule = async (rule) => {
    if (!confirm(`确认删除提现规则"${rule.name}"吗？`)) return;
    try {
        await withdrawRuleApi.delete(rule.id);
        loadRules();
        alert('删除成功');
    } catch (e) {
        alert(e.response?.data?.message || '删除失败');
    }
};

const closeModals = () => {
    showFormModal.value = false;
    showDetailModal.value = false;
    editingRule.value = null;
    detailData.value = null;
};

onMounted(() => {
    loadRules();
    loadMethods();
});
</script>
