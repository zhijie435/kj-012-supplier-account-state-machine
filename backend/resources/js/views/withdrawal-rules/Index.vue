<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">提现规则管理</h1>
                <p class="mt-1 text-sm text-gray-500">完整配置提现规则：金额限制、手续费、审核、生效时间等</p>
            </div>
            <button
                @click="showCreateModal"
                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium flex items-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                添加规则
            </button>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex flex-wrap items-center gap-4">
                <div class="flex-1 min-w-[180px]">
                    <label class="block text-xs font-medium text-gray-500 mb-1">关键词</label>
                    <input
                        v-model="filters.keyword"
                        @keyup.enter="loadRules"
                        type="text"
                        placeholder="搜索规则名称/编码/描述"
                        class="w-full rounded-lg border-gray-300 border px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    />
                </div>
                <div class="flex-1 min-w-[150px]">
                    <label class="block text-xs font-medium text-gray-500 mb-1">用户等级</label>
                    <select
                        v-model="filters.user_level"
                        @change="loadRules"
                        class="w-full rounded-lg border-gray-300 border px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    >
                        <option value="">全部等级</option>
                        <option v-for="lv in levelOptions" :key="lv.value" :value="lv.value">
                            {{ lv.label }}
                        </option>
                    </select>
                </div>
                <div class="flex-1 min-w-[150px]">
                    <label class="block text-xs font-medium text-gray-500 mb-1">币种</label>
                    <select
                        v-model="filters.currency"
                        @change="loadRules"
                        class="w-full rounded-lg border-gray-300 border px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    >
                        <option value="">全部币种</option>
                        <option v-for="cur in currencyOptions" :key="cur.value" :value="cur.value">
                            {{ cur.label }}
                        </option>
                    </select>
                </div>
                <div class="flex-1 min-w-[150px]">
                    <label class="block text-xs font-medium text-gray-500 mb-1">提现方式</label>
                    <select
                        v-model="filters.withdrawal_method"
                        @change="loadRules"
                        class="w-full rounded-lg border-gray-300 border px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    >
                        <option value="">全部方式</option>
                        <option v-for="m in methodOptions" :key="m.value" :value="m.value">
                            {{ m.label }}
                        </option>
                    </select>
                </div>
                <div class="flex-1 min-w-[130px]">
                    <label class="block text-xs font-medium text-gray-500 mb-1">状态</label>
                    <select
                        v-model="filters.is_active"
                        @change="loadRules"
                        class="w-full rounded-lg border-gray-300 border px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    >
                        <option value="">全部</option>
                        <option :value="1">启用</option>
                        <option :value="0">禁用</option>
                    </select>
                </div>
                <div class="pt-5">
                    <button
                        @click="loadRules"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium"
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
            <div v-if="rules.length > 0">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">规则编码/名称</th>
                                <th class="px-4 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">等级/币种/方式</th>
                                <th class="px-4 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">金额范围</th>
                                <th class="px-4 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">日/月限额</th>
                                <th class="px-4 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">手续费</th>
                                <th class="px-4 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">审核/结算</th>
                                <th class="px-4 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">生效时间</th>
                                <th class="px-4 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">状态</th>
                                <th class="px-4 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr
                                v-for="rule in rules"
                                :key="rule.id"
                                class="hover:bg-gray-50 transition-colors"
                            >
                                <td class="px-4 py-3.5">
                                    <div class="font-medium text-gray-900">{{ rule.name }}</div>
                                    <div class="text-xs text-gray-500 mt-0.5 font-mono">
                                        {{ rule.code }}
                                        <span v-if="rule.sort_order > 0" class="text-purple-600 ml-2">
                                            排序#{{ rule.sort_order }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="text-sm space-y-0.5">
                                        <div>
                                            <span :class="getUserLevelClass(rule.user_level)">
                                                {{ getLevelLabel(rule.user_level) }}
                                            </span>
                                        </div>
                                        <div class="text-gray-600">
                                            <span class="text-blue-600">{{ rule.currency }}</span>
                                            <span class="mx-1 text-gray-300">·</span>
                                            <span>{{ getMethodLabel(rule.withdrawal_method) }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="text-sm text-gray-700">
                                        {{ formatMoney(rule.min_amount, rule.currency) }} ~ {{ formatMoney(rule.max_amount, rule.currency) }}
                                    </div>
                                    <div v-if="rule.daily_max_count > 0" class="text-xs text-gray-500 mt-0.5">
                                        每日最多 {{ rule.daily_max_count }} 次
                                    </div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="text-sm text-gray-700 space-y-0.5">
                                        <div>
                                            <span class="inline-block w-8 text-xs text-gray-500">日:</span>
                                            <span v-if="rule.daily_limit > 0">
                                                {{ formatMoney(rule.daily_limit, rule.currency) }}
                                            </span>
                                            <span v-else class="text-gray-400">不限</span>
                                        </div>
                                        <div>
                                            <span class="inline-block w-8 text-xs text-gray-500">月:</span>
                                            <span v-if="rule.monthly_limit > 0">
                                                {{ formatMoney(rule.monthly_limit, rule.currency) }}
                                            </span>
                                            <span v-else class="text-gray-400">不限</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="text-sm text-gray-700">
                                        <div v-if="rule.fee_rate > 0 || (rule.fixed_fee && rule.fixed_fee > 0)" class="font-medium">
                                            <span v-if="rule.fee_rate > 0">{{ (rule.fee_rate * 100).toFixed(2) }}%</span>
                                            <span v-if="rule.fee_rate > 0 && rule.fixed_fee > 0"> + </span>
                                            <span v-if="rule.fixed_fee > 0">{{ formatMoney(rule.fixed_fee, rule.currency) }}</span>
                                        </div>
                                        <div v-else class="text-green-600 font-medium">免费</div>
                                        <div class="text-xs text-gray-500 mt-0.5">
                                            <span v-if="rule.fee_min > 0 || rule.fee_max > 0">
                                                范围: {{ formatMoney(rule.fee_min, rule.currency) }}
                                                <span v-if="rule.fee_max > 0">
                                                    ~ {{ formatMoney(rule.fee_max, rule.currency) }}
                                                </span>
                                                <span v-else>以上</span>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="text-sm space-y-1">
                                        <span :class="[
                                            'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
                                            rule.require_approval
                                                ? 'bg-amber-100 text-amber-800'
                                                : 'bg-green-100 text-green-800'
                                        ]">
                                            {{ rule.require_approval ? '需人工审核' : '自动通过' }}
                                        </span>
                                        <div v-if="rule.require_approval && rule.approval_threshold > 0"
                                             class="text-xs text-amber-600">
                                            超 {{ formatMoney(rule.approval_threshold, rule.currency) }} 需审
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            结算: T+{{ rule.settlement_days }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="text-xs text-gray-600 space-y-0.5">
                                        <div v-if="rule.effective_from">
                                            <span class="text-gray-500">起:</span>
                                            {{ formatDate(rule.effective_from) }}
                                        </div>
                                        <div v-else class="text-green-600">
                                            长期有效
                                        </div>
                                        <div v-if="rule.effective_to">
                                            <span class="text-gray-500">止:</span>
                                            {{ formatDate(rule.effective_to) }}
                                        </div>
                                        <div v-if="(rule.allowed_regions && rule.allowed_regions.length) || (rule.denied_regions && rule.denied_regions.length)"
                                             class="text-purple-600 mt-1">
                                            <span v-if="rule.allowed_regions && rule.allowed_regions.length">
                                                允许 {{ rule.allowed_regions.length }} 区域
                                            </span>
                                            <span v-if="rule.denied_regions && rule.denied_regions.length"
                                                  class="text-red-600 ml-1">
                                                禁用 {{ rule.denied_regions.length }} 区域
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="space-y-1.5">
                                        <div v-if="rule.is_active" class="flex items-center gap-1.5">
                                            <span class="relative inline-flex h-2 w-2 rounded-full bg-green-500"></span>
                                            <span class="text-xs text-green-700 font-medium">已启用</span>
                                        </div>
                                        <div v-else class="flex items-center gap-1.5">
                                            <span class="relative inline-flex h-2 w-2 rounded-full bg-gray-400"></span>
                                            <span class="text-xs text-gray-600 font-medium">已禁用</span>
                                        </div>
                                        <button
                                            @click="toggleActive(rule)"
                                            :class="[
                                                'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                                                rule.is_active ? 'bg-indigo-600' : 'bg-gray-200'
                                            ]"
                                        >
                                            <span
                                                :class="[
                                                    'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                                                    rule.is_active ? 'translate-x-6' : 'translate-x-1'
                                                ]"
                                            />
                                        </button>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-1.5">
                                        <button
                                            @click="viewRule(rule)"
                                            class="p-1.5 text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-md"
                                            title="详情"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </button>
                                        <button
                                            @click="editRule(rule)"
                                            class="p-1.5 text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50 rounded-md"
                                            title="编辑"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                        <button
                                            @click="deleteRule(rule)"
                                            class="p-1.5 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-md"
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

                <div v-if="pagination && pagination.last_page > 1" class="px-4 py-4 border-t border-gray-100 flex items-center justify-between">
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
                <p class="text-gray-500 mb-4">创建完整的提现规则以管理用户提现</p>
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
            <div class="bg-white rounded-2xl p-6 max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto">
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
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                规则名称 <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                placeholder="例如：VIP用户银行转账规则"
                                class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                required
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                规则编码 <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.code"
                                type="text"
                                placeholder="例如：VIP_BANK_CNY_001"
                                :disabled="!!editingRule"
                                class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 disabled:bg-gray-100 disabled:cursor-not-allowed font-mono text-sm"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                用户等级
                            </label>
                            <select
                                v-model="form.user_level"
                                class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            >
                                <option v-for="lv in levelOptions" :key="lv.value" :value="lv.value">
                                    {{ lv.label }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                币种
                            </label>
                            <select
                                v-model="form.currency"
                                class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            >
                                <option v-for="cur in currencyOptions" :key="cur.value" :value="cur.value">
                                    {{ cur.label }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                提现方式
                            </label>
                            <select
                                v-model="form.withdrawal_method"
                                class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            >
                                <option v-for="m in methodOptions" :key="m.value" :value="m.value">
                                    {{ m.label }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                排序号（数值越小越靠前）
                            </label>
                            <input
                                v-model.number="form.sort_order"
                                type="number"
                                step="1"
                                class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            />
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-4 space-y-4">
                        <h4 class="text-sm font-medium text-gray-700">💰 金额限制</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">单笔最低金额 *</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm">{{ form.currency }}</span>
                                    <input
                                        v-model.number="form.min_amount"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="w-full rounded border-gray-300 border pl-14 pr-3 py-2 text-sm"
                                        required
                                    />
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">单笔最高金额 *</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm">{{ form.currency }}</span>
                                    <input
                                        v-model.number="form.max_amount"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="w-full rounded border-gray-300 border pl-14 pr-3 py-2 text-sm"
                                        required
                                    />
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">每日限额 (0=不限)</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm">{{ form.currency }}</span>
                                    <input
                                        v-model.number="form.daily_limit"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="w-full rounded border-gray-300 border pl-14 pr-3 py-2 text-sm"
                                    />
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">每月限额 (0=不限)</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm">{{ form.currency }}</span>
                                    <input
                                        v-model.number="form.monthly_limit"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="w-full rounded border-gray-300 border pl-14 pr-3 py-2 text-sm"
                                    />
                                </div>
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs font-medium text-gray-600 mb-1">每日最大提现次数 (0=不限)</label>
                                <input
                                    v-model.number="form.daily_max_count"
                                    type="number"
                                    step="1"
                                    min="0"
                                    class="w-full rounded border-gray-300 border px-4 py-2 text-sm"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-4 space-y-4">
                        <h4 class="text-sm font-medium text-gray-700">💸 手续费配置</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">手续费率 (%)</label>
                                <div class="relative">
                                    <input
                                        v-model.number="form.fee_rate"
                                        type="number"
                                        step="0.0001"
                                        min="0"
                                        max="1"
                                        class="w-full rounded border-gray-300 border px-4 py-2 pr-10 text-sm"
                                    />
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm">%</span>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">例：0.5% 填 0.005</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">固定手续费 (元)</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm">{{ form.currency }}</span>
                                    <input
                                        v-model.number="form.fixed_fee"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        placeholder="0 表示无固定费用"
                                        class="w-full rounded border-gray-300 border pl-14 pr-3 py-2 text-sm"
                                    />
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">最低手续费 (0=不限)</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm">{{ form.currency }}</span>
                                    <input
                                        v-model.number="form.fee_min"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="w-full rounded border-gray-300 border pl-14 pr-3 py-2 text-sm"
                                    />
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">最高手续费 (0=不限)</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm">{{ form.currency }}</span>
                                    <input
                                        v-model.number="form.fee_max"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="w-full rounded border-gray-300 border pl-14 pr-3 py-2 text-sm"
                                    />
                                </div>
                            </div>
                            <div class="col-span-2">
                                <div class="text-xs text-indigo-600 bg-indigo-50 p-2 rounded">
                                    <div class="grid grid-cols-3 gap-4">
                                        <div>提现 ¥1,000 预估手续费: <strong>{{ formatMoney(calcFee(1000), form.currency) }}</strong></div>
                                        <div>提现 ¥10,000 预估手续费: <strong>{{ formatMoney(calcFee(10000), form.currency) }}</strong></div>
                                        <div>提现 ¥50,000 预估手续费: <strong>{{ formatMoney(calcFee(50000), form.currency) }}</strong></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-4 space-y-4">
                        <h4 class="text-sm font-medium text-gray-700">✅ 审核与结算</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">结算工作日 (T+N)</label>
                                <input
                                    v-model.number="form.settlement_days"
                                    type="number"
                                    step="1"
                                    min="0"
                                    class="w-full rounded border-gray-300 border px-4 py-2 text-sm"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">审批阈值 (超此金额需审批)</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm">{{ form.currency }}</span>
                                    <input
                                        v-model.number="form.approval_threshold"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="w-full rounded border-gray-300 border pl-14 pr-3 py-2 text-sm"
                                    />
                                </div>
                            </div>
                            <div class="col-span-2 flex items-center gap-6 pt-2">
                                <label class="flex items-center gap-2">
                                    <input
                                        v-model="form.require_approval"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                    <span class="text-sm text-gray-700">需要人工审核</span>
                                </label>
                                <label class="flex items-center gap-2">
                                    <input
                                        v-model="form.is_active"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                    <span class="text-sm text-gray-700">启用规则</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-4 space-y-4">
                        <h4 class="text-sm font-medium text-gray-700">📅 生效时间 & 区域限制</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">生效起始时间</label>
                                <input
                                    v-model="form.effective_from"
                                    type="datetime-local"
                                    class="w-full rounded border-gray-300 border px-4 py-2 text-sm"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">生效截止时间</label>
                                <input
                                    v-model="form.effective_to"
                                    type="datetime-local"
                                    class="w-full rounded border-gray-300 border px-4 py-2 text-sm"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">允许的地区 (逗号分隔)</label>
                                <input
                                    :value="form.allowed_regions?.join(',') || ''"
                                    @input="e => form.allowed_regions = e.target.value.split(',').map(s => s.trim()).filter(Boolean)"
                                    type="text"
                                    placeholder="例如: CN,US,HK"
                                    class="w-full rounded border-gray-300 border px-4 py-2 text-sm"
                                />
                                <p class="text-xs text-gray-500 mt-1">留空表示不限制</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">禁止的地区 (逗号分隔)</label>
                                <input
                                    :value="form.denied_regions?.join(',') || ''"
                                    @input="e => form.denied_regions = e.target.value.split(',').map(s => s.trim()).filter(Boolean)"
                                    type="text"
                                    placeholder="例如: RU,Iran"
                                    class="w-full rounded border-gray-300 border px-4 py-2 text-sm"
                                />
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">规则描述</label>
                        <textarea
                            v-model="form.description"
                            rows="2"
                            placeholder="规则详细说明或备注"
                            class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 resize-none"
                        ></textarea>
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
                    <div class="grid grid-cols-2 gap-x-6 gap-y-1 text-sm">
                        <DetailItem label="规则名称" :value="detailData.name" />
                        <DetailItem label="规则编码" :value="detailData.code" mono />
                        <DetailItem label="用户等级" :value="getLevelLabel(detailData.user_level)" />
                        <DetailItem label="币种" :value="detailData.currency" />
                        <DetailItem label="提现方式" :value="getMethodLabel(detailData.withdrawal_method)" />
                        <DetailItem label="排序号" :value="detailData.sort_order || '-' " />
                        <DetailItem label="状态">
                            <template #value>
                                <span :class="detailData.is_active ? 'text-green-600' : 'text-gray-500'" class="font-medium">
                                    {{ detailData.is_active ? '已启用' : '已禁用' }}
                                </span>
                            </template>
                        </DetailItem>
                        <DetailItem label="提现次数">
                            <template #value>
                                <span class="text-indigo-600 font-semibold">{{ detailData.withdrawals_count || 0 }}</span>
                                <span class="text-gray-400 ml-1">笔</span>
                            </template>
                        </DetailItem>
                    </div>

                    <div class="border-t pt-4">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">💰 金额限制</h4>
                        <div class="grid grid-cols-2 gap-x-6 gap-y-1 text-sm">
                            <DetailItem label="单笔最低" :value="formatMoney(detailData.min_amount, detailData.currency)" />
                            <DetailItem label="单笔最高" :value="formatMoney(detailData.max_amount, detailData.currency)" />
                            <DetailItem label="每日限额" :value="detailData.daily_limit > 0 ? formatMoney(detailData.daily_limit, detailData.currency) : '不限'" />
                            <DetailItem label="每月限额" :value="detailData.monthly_limit > 0 ? formatMoney(detailData.monthly_limit, detailData.currency) : '不限'" />
                            <DetailItem label="每日次数" :value="detailData.daily_max_count > 0 ? detailData.daily_max_count + ' 次' : '不限'" />
                        </div>
                    </div>

                    <div class="border-t pt-4">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">💸 手续费配置</h4>
                        <div class="grid grid-cols-2 gap-x-6 gap-y-1 text-sm">
                            <DetailItem label="手续费率" :value="(detailData.fee_rate * 100).toFixed(2) + '%'" />
                            <DetailItem label="固定手续费" :value="formatMoney(detailData.fixed_fee || 0, detailData.currency)" />
                            <DetailItem label="最低手续费" :value="formatMoney(detailData.fee_min, detailData.currency)" />
                            <DetailItem label="最高手续费" :value="detailData.fee_max > 0 ? formatMoney(detailData.fee_max, detailData.currency) : '不限'" />
                        </div>
                    </div>

                    <div class="border-t pt-4">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">✅ 审核与结算</h4>
                        <div class="grid grid-cols-2 gap-x-6 gap-y-1 text-sm">
                            <DetailItem label="审核方式" :value="detailData.require_approval ? '人工审核' : '自动通过'" />
                            <DetailItem label="结算周期" :value="'T+' + detailData.settlement_days" />
                            <DetailItem v-if="detailData.require_approval && detailData.approval_threshold > 0"
                                        label="审批阈值"
                                        :value="formatMoney(detailData.approval_threshold, detailData.currency) + ' 以上需审批'" />
                        </div>
                    </div>

                    <div class="border-t pt-4">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">📅 生效与区域</h4>
                        <div class="grid grid-cols-2 gap-x-6 gap-y-1 text-sm">
                            <DetailItem label="生效起始" :value="detailData.effective_from ? formatDate(detailData.effective_from) : '立即生效'" />
                            <DetailItem label="生效截止" :value="detailData.effective_to ? formatDate(detailData.effective_to) : '长期有效'" />
                            <DetailItem label="允许区域" :value="detailData.allowed_regions?.length ? detailData.allowed_regions.join(', ') : '全部允许'" />
                            <DetailItem label="禁止区域" :value="detailData.denied_regions?.length ? detailData.denied_regions.join(', ') : '无'" />
                        </div>
                    </div>

                    <div v-if="detailData.description" class="border-t pt-4">
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">📝 规则描述</h4>
                        <div class="text-sm text-gray-700 bg-gray-50 p-3 rounded-lg whitespace-pre-wrap">
                            {{ detailData.description }}
                        </div>
                    </div>

                    <div class="border-t pt-4 text-xs text-gray-500">
                        <div class="grid grid-cols-2 gap-x-6 gap-y-1">
                            <div>创建人: {{ detailData.creator?.name || '-' }}</div>
                            <div>创建时间: {{ formatDate(detailData.created_at) }}</div>
                            <div>更新人: {{ detailData.updater?.name || '-' }}</div>
                            <div>更新时间: {{ formatDate(detailData.updated_at) }}</div>
                        </div>
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
import { ref, reactive, onMounted, h } from 'vue';
import withdrawalRuleApi from '../../api/withdrawalRule';

const DetailItem = {
    props: ['label', 'value', 'mono'],
    setup(props, { slots }) {
        return () => h('div', { class: 'py-2 flex justify-between border-b border-gray-50' }, [
            h('span', { class: 'text-gray-500' }, props.label),
            slots.value
                ? slots.value()
                : h('span', { class: ['font-medium text-gray-900', props.mono ? 'font-mono' : ''] }, props.value || '-')
        ]);
    }
};

const loading = ref(false);
const submitting = ref(false);
const rules = ref([]);
const pagination = ref(null);
const showFormModal = ref(false);
const showDetailModal = ref(false);
const editingRule = ref(null);
const detailData = ref(null);

const filters = reactive({
    keyword: '',
    user_level: '',
    currency: '',
    withdrawal_method: '',
    is_active: '',
    page: 1,
    per_page: 15,
});

const levelOptions = ref([]);
const currencyOptions = ref([]);
const methodOptions = ref([]);

const form = reactive({
    name: '',
    code: '',
    user_level: 'all',
    currency: 'CNY',
    withdrawal_method: 'bank_transfer',
    min_amount: 0,
    max_amount: 0,
    daily_limit: 0,
    monthly_limit: 0,
    fee_rate: 0,
    fixed_fee: 0,
    fee_min: 0,
    fee_max: 0,
    settlement_days: 1,
    daily_max_count: 0,
    require_approval: true,
    approval_threshold: 0,
    allowed_regions: [],
    denied_regions: [],
    description: '',
    is_active: true,
    sort_order: 0,
    effective_from: '',
    effective_to: '',
});

const getLevelLabel = (lv) => {
    const found = levelOptions.value.find(x => x.value === lv);
    return found ? found.label : lv;
};

const getMethodLabel = (m) => {
    const found = methodOptions.value.find(x => x.value === m);
    return found ? found.label : m;
};

const getUserLevelClass = (lv) => {
    const cls = {
        all: 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700',
        super: 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800',
        vip: 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800',
        normal: 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800',
    };
    return cls[lv] || cls.normal;
};

const formatMoney = (num, currency = 'CNY') => {
    const n = parseFloat(num || 0);
    const str = new Intl.NumberFormat('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n);
    return currency + ' ' + str;
};

const formatDate = (str) => {
    if (!str) return '-';
    const d = new Date(str);
    if (isNaN(d)) return str;
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
};

const calcFee = (amount) => {
    if (!amount) return 0;
    let fee = amount * (form.fee_rate || 0) + (form.fixed_fee || 0);
    if (form.fee_min > 0 && fee < form.fee_min) fee = form.fee_min;
    if (form.fee_max > 0 && fee > form.fee_max) fee = form.fee_max;
    return Math.round(fee * 100) / 100;
};

const loadOptions = async () => {
    try {
        const [lv, cur, m] = await Promise.all([
            withdrawalRuleApi.getLevelOptions(),
            withdrawalRuleApi.getCurrencyOptions(),
            withdrawalRuleApi.getMethodOptions(),
        ]);
        levelOptions.value = lv.data?.data || lv.data || [];
        currencyOptions.value = cur.data?.data || cur.data || [];
        methodOptions.value = m.data?.data || m.data || [];
    } catch (e) {
        console.error(e);
    }
};

const loadRules = async () => {
    loading.value = true;
    try {
        const params = { ...filters };
        if (!params.keyword) delete params.keyword;
        if (!params.user_level) delete params.user_level;
        if (!params.currency) delete params.currency;
        if (!params.withdrawal_method) delete params.withdrawal_method;
        if (params.is_active === '') delete params.is_active;

        const { data } = await withdrawalRuleApi.getList(params);
        const result = data;
        if (result.success) {
            if (result.pagination) {
                rules.value = result.data;
                pagination.value = {
                    current_page: result.pagination.current_page,
                    last_page: result.pagination.last_page,
                    total: result.pagination.total,
                    per_page: result.pagination.per_page,
                };
            } else if (result.data?.data) {
                rules.value = result.data.data;
                pagination.value = {
                    current_page: result.data.current_page,
                    last_page: result.data.last_page,
                    total: result.data.total,
                    per_page: result.data.per_page,
                };
            } else {
                rules.value = result.data;
            }
        }
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
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
        code: '',
        user_level: 'all',
        currency: 'CNY',
        withdrawal_method: 'bank_transfer',
        min_amount: 0,
        max_amount: 0,
        daily_limit: 0,
        monthly_limit: 0,
        fee_rate: 0,
        fixed_fee: 0,
        fee_min: 0,
        fee_max: 0,
        settlement_days: 1,
        daily_max_count: 0,
        require_approval: true,
        approval_threshold: 0,
        allowed_regions: [],
        denied_regions: [],
        description: '',
        is_active: true,
        sort_order: 0,
        effective_from: '',
        effective_to: '',
    });
};

const toLocal = (utcStr) => {
    if (!utcStr) return '';
    const d = new Date(utcStr);
    if (isNaN(d)) return '';
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
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
        code: rule.code,
        user_level: rule.user_level,
        currency: rule.currency,
        withdrawal_method: rule.withdrawal_method,
        min_amount: rule.min_amount,
        max_amount: rule.max_amount,
        daily_limit: rule.daily_limit,
        monthly_limit: rule.monthly_limit,
        fee_rate: rule.fee_rate,
        fixed_fee: rule.fixed_fee || 0,
        fee_min: rule.fee_min,
        fee_max: rule.fee_max,
        settlement_days: rule.settlement_days,
        daily_max_count: rule.daily_max_count,
        require_approval: rule.require_approval,
        approval_threshold: rule.approval_threshold,
        allowed_regions: rule.allowed_regions ? [...rule.allowed_regions] : [],
        denied_regions: rule.denied_regions ? [...rule.denied_regions] : [],
        description: rule.description || '',
        is_active: rule.is_active,
        sort_order: rule.sort_order,
        effective_from: toLocal(rule.effective_from),
        effective_to: toLocal(rule.effective_to),
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
        if (data.effective_from) {
            data.effective_from = new Date(data.effective_from).toISOString();
        }
        if (data.effective_to) {
            data.effective_to = new Date(data.effective_to).toISOString();
        }
        if (editingRule.value) {
            await withdrawalRuleApi.update(editingRule.value.id, data);
        } else {
            await withdrawalRuleApi.create(data);
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

const toggleActive = async (rule) => {
    try {
        const { data } = await withdrawalRuleApi.toggleActive(rule.id);
        const result = data;
        if (result.success && result.data) {
            Object.assign(rule, result.data);
        } else {
            rule.is_active = !rule.is_active;
        }
    } catch (e) {
        alert(e.response?.data?.message || '操作失败');
    }
};

const deleteRule = async (rule) => {
    if (!confirm(`确认删除提现规则"${rule.name}"吗？\n存在提现记录的规则无法删除。`)) return;
    try {
        await withdrawalRuleApi.delete(rule.id);
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
    loadOptions();
    loadRules();
});
</script>
