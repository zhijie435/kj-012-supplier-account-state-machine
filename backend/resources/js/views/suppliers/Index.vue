<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="relative">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="搜索供应商名称、公司、联系人、电话..."
                        class="w-80 pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"
                    />
                    <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <select
                    v-model="filterStatus"
                    class="px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white"
                >
                    <option value="">全部状态</option>
                    <option value="pending">待审核</option>
                    <option value="verifying">审核中</option>
                    <option value="active">已激活</option>
                    <option value="suspended">已暂停</option>
                    <option value="rejected">已拒绝</option>
                    <option value="cancelled">已注销</option>
                </select>
                <select
                    v-model="filterCrossBorder"
                    class="px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white"
                >
                    <option value="">全部类型</option>
                    <option value="1">跨境供应商</option>
                    <option value="0">国内供应商</option>
                </select>
            </div>
            <button
                v-if="auth.can('supplier.create')"
                @click="openCreateModal"
                class="px-4 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors flex items-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                新建供应商
            </button>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">供应商</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">联系人</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">产品数</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">信用额度</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">类型</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">状态</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">创建时间</th>
                        <th class="text-right text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-if="loading" class="text-center">
                        <td colspan="8" class="px-6 py-12 text-gray-500">加载中...</td>
                    </tr>
                    <tr v-else-if="!suppliers.length" class="text-center">
                        <td colspan="8" class="px-6 py-12 text-gray-500">暂无数据</td>
                    </tr>
                    <tr v-for="supplier in suppliers" :key="supplier.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center text-blue-600 font-medium text-sm">
                                    {{ supplier.name.charAt(0) }}
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900 text-sm">{{ supplier.name }}</div>
                                    <div class="text-sm text-gray-500">{{ supplier.company_name || '-' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">{{ supplier.contact_person }}</div>
                            <div class="text-sm text-gray-500">{{ supplier.phone }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            {{ supplier.products_count || 0 }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">¥{{ formatNumber(supplier.credit_limit) }}</div>
                            <div class="text-sm text-gray-500">余额: ¥{{ formatNumber(supplier.balance) }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span :class="supplier.is_cross_border ? 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700' : 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700'">
                                {{ supplier.is_cross_border ? '跨境' : '国内' }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span :class="statusBadge(supplier.status)">
                                {{ statusLabel(supplier.status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ formatDate(supplier.created_at) }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button
                                    v-if="auth.can('supplier.view')"
                                    @click="openDetailModal(supplier)"
                                    class="text-sm text-gray-600 hover:text-gray-700"
                                >
                                    详情
                                </button>
                                <button
                                    v-if="auth.can('supplier.edit')"
                                    @click="openEditModal(supplier)"
                                    class="text-sm text-indigo-600 hover:text-indigo-700"
                                >
                                    编辑
                                </button>
                                <button
                                    v-if="auth.can('supplier.approve') && hasAllowedTransitions(supplier)"
                                    @click="openStatusModal(supplier)"
                                    class="text-sm text-amber-600 hover:text-amber-700"
                                >
                                    状态变更
                                </button>
                                <button
                                    v-if="auth.can('supplier.delete')"
                                    @click="deleteSupplier(supplier)"
                                    class="text-sm text-red-600 hover:text-red-700"
                                >
                                    删除
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-if="total > perPage" class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                <div class="text-sm text-gray-500">
                    共 {{ total }} 条记录
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="page > 1 && loadSuppliers(page - 1)"
                        :disabled="page <= 1"
                        class="px-3 py-1.5 rounded border border-gray-300 text-sm hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        上一页
                    </button>
                    <span class="text-sm text-gray-700">第 {{ page }} 页</span>
                    <button
                        @click="page * perPage < total && loadSuppliers(page + 1)"
                        :disabled="page * perPage >= total"
                        class="px-3 py-1.5 rounded border border-gray-300 text-sm hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        下一页
                    </button>
                </div>
            </div>
        </div>

        <div v-if="showFormModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl w-full max-w-2xl max-h-[90vh] overflow-auto">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">{{ isEdit ? '编辑供应商' : '新建供应商' }}</h3>
                    <button @click="closeFormModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <form @submit.prevent="submitForm" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">供应商名称 <span class="text-red-500">*</span></label>
                            <input v-model="form.name" type="text" required class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">公司名称</label>
                            <input v-model="form.company_name" type="text" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">联系人 <span class="text-red-500">*</span></label>
                            <input v-model="form.contact_person" type="text" required class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">联系电话 <span class="text-red-500">*</span></label>
                            <input v-model="form.phone" type="text" required class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">邮箱</label>
                            <input v-model="form.email" type="email" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">营业执照</label>
                            <input v-model="form.business_license" type="text" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">地址</label>
                        <input v-model="form.address" type="text" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">开户银行</label>
                            <input v-model="form.bank_name" type="text" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">银行账号</label>
                            <input v-model="form.bank_account" type="text" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">信用额度</label>
                            <input v-model.number="form.credit_limit" type="number" step="0.01" min="0" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">账户余额</label>
                            <input v-model.number="form.balance" type="number" step="0.01" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">国家代码</label>
                            <input v-model="form.country_code" type="text" placeholder="如: CN, US" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">税号</label>
                            <input v-model="form.tax_id" type="text" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">出口许可证</label>
                            <input v-model="form.export_license" type="text" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">进出口代码</label>
                            <input v-model="form.import_export_code" type="text" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                    </div>
                    <div class="flex items-center gap-6">
                        <label class="flex items-center gap-2">
                            <input v-model="form.is_cross_border" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"/>
                            <span class="text-sm text-gray-700">跨境供应商</span>
                        </label>
                        <div v-if="!isEdit">
                            <label class="block text-sm font-medium text-gray-700 mb-1">初始状态</label>
                            <select v-model="form.status" class="px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white">
                                <option value="pending">待审核</option>
                                <option value="active">已激活</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">备注</label>
                        <textarea v-model="form.remark" rows="3" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"></textarea>
                    </div>
                    <div v-if="submitError" class="p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
                        {{ submitError }}
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" @click="closeFormModal" class="px-4 py-2 rounded-lg border border-gray-300 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                            取消
                        </button>
                        <button type="submit" :disabled="submitting" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700 transition-colors disabled:opacity-50">
                            {{ submitting ? '保存中...' : '保存' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="showStatusModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl w-full max-w-md">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">状态变更</h3>
                    <button @click="closeStatusModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <form @submit.prevent="submitStatusChange" class="p-6 space-y-4">
                    <div class="p-4 rounded-lg bg-gray-50 space-y-2">
                        <div class="font-medium text-gray-900">{{ statusSupplier?.name }}</div>
                        <div class="text-sm text-gray-500">{{ statusSupplier?.company_name || '-' }}</div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-500">当前状态：</span>
                            <span :class="statusBadge(statusSupplier?.status)">
                                {{ statusLabel(statusSupplier?.status) }}
                            </span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">目标状态 <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                v-for="transition in allowedTransitions"
                                :key="transition.value"
                                type="button"
                                @click="statusForm.status = transition.value"
                                :class="[
                                    'px-4 py-3 rounded-lg border-2 text-sm font-medium transition-all',
                                    statusForm.status === transition.value
                                        ? 'border-indigo-500 bg-indigo-50 text-indigo-700'
                                        : 'border-gray-200 hover:border-gray-300 text-gray-700'
                                ]"
                            >
                                <span :class="statusBadge(transition.value) + ' mb-1'">
                                    {{ transition.label }}
                                </span>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            变更备注
                            <span v-if="statusForm.status === 'rejected'" class="text-red-500">*</span>
                        </label>
                        <textarea
                            v-model="statusForm.remark"
                            :required="statusForm.status === 'rejected'"
                            rows="3"
                            class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"
                            :placeholder="statusForm.status === 'rejected' ? '请输入拒绝原因' : '请输入变更备注（可选）'"
                        ></textarea>
                    </div>
                    <div v-if="statusError" class="p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
                        {{ statusError }}
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" @click="closeStatusModal" class="px-4 py-2 rounded-lg border border-gray-300 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                            取消
                        </button>
                        <button type="submit" :disabled="statusSubmitting || !statusForm.status" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700 transition-colors disabled:opacity-50">
                            {{ statusSubmitting ? '提交中...' : '确认变更' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="showDetailModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl w-full max-w-3xl max-h-[90vh] overflow-hidden flex flex-col">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0">
                    <h3 class="font-semibold text-gray-900">供应商详情</h3>
                    <button @click="closeDetailModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div v-if="detailLoading" class="p-12 text-center text-gray-500">
                    加载中...
                </div>
                <div v-else-if="detailSupplier" class="overflow-auto flex-1">
                    <div class="p-6 space-y-6">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-4">
                                <div class="w-14 h-14 rounded-xl bg-blue-100 flex items-center justify-center text-blue-600 font-medium text-xl">
                                    {{ detailSupplier.name.charAt(0) }}
                                </div>
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900">{{ detailSupplier.name }}</h4>
                                    <p class="text-sm text-gray-500">{{ detailSupplier.company_name || '-' }}</p>
                                    <div class="mt-2">
                                        <span :class="statusBadge(detailSupplier.status)">
                                            {{ statusLabel(detailSupplier.status) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-gray-500">信用额度</div>
                                <div class="text-xl font-semibold text-gray-900">¥{{ formatNumber(detailSupplier.credit_limit) }}</div>
                                <div class="text-sm text-gray-500 mt-1">余额: ¥{{ formatNumber(detailSupplier.balance) }}</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <h5 class="text-sm font-semibold text-gray-900 border-b pb-2">基本信息</h5>
                                <div class="space-y-2 text-sm">
                                    <div class="flex">
                                        <span class="text-gray-500 w-20">联系人：</span>
                                        <span class="text-gray-900">{{ detailSupplier.contact_person }}</span>
                                    </div>
                                    <div class="flex">
                                        <span class="text-gray-500 w-20">电话：</span>
                                        <span class="text-gray-900">{{ detailSupplier.phone }}</span>
                                    </div>
                                    <div class="flex">
                                        <span class="text-gray-500 w-20">邮箱：</span>
                                        <span class="text-gray-900">{{ detailSupplier.email || '-' }}</span>
                                    </div>
                                    <div class="flex">
                                        <span class="text-gray-500 w-20">地址：</span>
                                        <span class="text-gray-900">{{ detailSupplier.address || '-' }}</span>
                                    </div>
                                    <div class="flex">
                                        <span class="text-gray-500 w-20">类型：</span>
                                        <span class="text-gray-900">{{ detailSupplier.is_cross_border ? '跨境供应商' : '国内供应商' }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <h5 class="text-sm font-semibold text-gray-900 border-b pb-2">资质信息</h5>
                                <div class="space-y-2 text-sm">
                                    <div class="flex">
                                        <span class="text-gray-500 w-24">营业执照：</span>
                                        <span class="text-gray-900">{{ detailSupplier.business_license || '-' }}</span>
                                    </div>
                                    <div class="flex">
                                        <span class="text-gray-500 w-24">税号：</span>
                                        <span class="text-gray-900">{{ detailSupplier.tax_id || '-' }}</span>
                                    </div>
                                    <div class="flex">
                                        <span class="text-gray-500 w-24">国家代码：</span>
                                        <span class="text-gray-900">{{ detailSupplier.country_code || '-' }}</span>
                                    </div>
                                    <div class="flex">
                                        <span class="text-gray-500 w-24">出口许可证：</span>
                                        <span class="text-gray-900">{{ detailSupplier.export_license || '-' }}</span>
                                    </div>
                                    <div class="flex">
                                        <span class="text-gray-500 w-24">进出口代码：</span>
                                        <span class="text-gray-900">{{ detailSupplier.import_export_code || '-' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <h5 class="text-sm font-semibold text-gray-900 border-b pb-2">状态变更日志</h5>
                            <div v-if="!detailSupplier.status_logs?.length" class="text-sm text-gray-500 py-4 text-center">
                                暂无状态变更记录
                            </div>
                            <div v-else class="space-y-3">
                                <div
                                    v-for="log in detailSupplier.status_logs"
                                    :key="log.id"
                                    class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg"
                                >
                                    <div class="w-8 h-8 rounded-full bg-white border border-gray-200 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span v-if="log.from_status" :class="statusBadge(log.from_status.value)">
                                                {{ log.from_status_label }}
                                            </span>
                                            <svg v-if="log.from_status" class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                            </svg>
                                            <span :class="statusBadge(log.to_status.value)">
                                                {{ log.to_status_label }}
                                            </span>
                                        </div>
                                        <div class="text-sm text-gray-500 mt-1">
                                            {{ log.remark || '无备注' }}
                                        </div>
                                        <div class="text-xs text-gray-400 mt-1">
                                            {{ log.operator?.name || '系统' }} · {{ formatDate(log.created_at) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3 flex-shrink-0">
                    <button
                        v-if="auth.can('supplier.edit')"
                        @click="closeDetailModal(); openEditModal(detailSupplier)"
                        class="px-4 py-2 rounded-lg border border-gray-300 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                    >
                        编辑
                    </button>
                    <button
                        v-if="auth.can('supplier.approve') && hasAllowedTransitions(detailSupplier)"
                        @click="closeDetailModal(); openStatusModal(detailSupplier)"
                        class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700 transition-colors"
                    >
                        状态变更
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref, onMounted, watch } from 'vue';
import { useAuthStore } from '../../stores/auth';
import api from '../../api/axios';

const auth = useAuthStore();

const suppliers = ref([]);
const loading = ref(false);
const search = ref('');
const filterStatus = ref('');
const filterCrossBorder = ref('');
const page = ref(1);
const perPage = ref(15);
const total = ref(0);

const showFormModal = ref(false);
const isEdit = ref(false);
const editingId = ref(null);
const submitting = ref(false);
const submitError = ref('');

const showStatusModal = ref(false);
const statusSupplier = ref(null);
const statusSubmitting = ref(false);
const statusError = ref('');
const allowedTransitions = ref([]);
const statusForm = reactive({ status: '', remark: '' });

const showDetailModal = ref(false);
const detailSupplier = ref(null);
const detailLoading = ref(false);

const defaultForm = () => ({
    name: '',
    company_name: '',
    business_license: '',
    contact_person: '',
    phone: '',
    email: '',
    address: '',
    bank_name: '',
    bank_account: '',
    credit_limit: 0,
    balance: 0,
    status: 'pending',
    remark: '',
    country_code: '',
    tax_id: '',
    export_license: '',
    import_export_code: '',
    is_cross_border: false,
});

const form = reactive(defaultForm());

const STATUS_MAP = {
    pending: { label: '待审核', badge: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700' },
    verifying: { label: '审核中', badge: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700' },
    active: { label: '已激活', badge: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700' },
    suspended: { label: '已暂停', badge: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-700' },
    rejected: { label: '已拒绝', badge: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700' },
    cancelled: { label: '已注销', badge: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700' },
};

const statusLabel = (s) => STATUS_MAP[s]?.label || s;
const statusBadge = (s) => STATUS_MAP[s]?.badge || '';

const formatNumber = (num) => {
    if (num === null || num === undefined) return '0.00';
    return Number(num).toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
const formatDate = (d) => d ? new Date(d).toLocaleString('zh-CN') : '-';

const hasAllowedTransitions = (supplier) => {
    return supplier?.allowed_transitions?.length > 0;
};

const loadSuppliers = async (p = 1) => {
    loading.value = true;
    page.value = p;
    try {
        const params = { page: p, per_page: perPage.value };
        if (search.value) params.search = search.value;
        if (filterStatus.value) params.status = filterStatus.value;
        if (filterCrossBorder.value !== '') params.is_cross_border = filterCrossBorder.value === '1';
        const { data } = await api.get('/suppliers', { params });
        suppliers.value = data.data;
        total.value = data.total;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

let debounceTimer = null;
watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => loadSuppliers(1), 300);
});
watch([filterStatus, filterCrossBorder], () => loadSuppliers(1));

const openCreateModal = () => {
    Object.assign(form, defaultForm());
    isEdit.value = false;
    editingId.value = null;
    submitError.value = '';
    showFormModal.value = true;
};

const openEditModal = (supplier) => {
    Object.assign(form, {
        name: supplier.name,
        company_name: supplier.company_name || '',
        business_license: supplier.business_license || '',
        contact_person: supplier.contact_person,
        phone: supplier.phone,
        email: supplier.email || '',
        address: supplier.address || '',
        bank_name: supplier.bank_name || '',
        bank_account: supplier.bank_account || '',
        credit_limit: supplier.credit_limit || 0,
        balance: supplier.balance || 0,
        status: supplier.status,
        remark: supplier.remark || '',
        country_code: supplier.country_code || '',
        tax_id: supplier.tax_id || '',
        export_license: supplier.export_license || '',
        import_export_code: supplier.import_export_code || '',
        is_cross_border: supplier.is_cross_border || false,
    });
    isEdit.value = true;
    editingId.value = supplier.id;
    submitError.value = '';
    showFormModal.value = true;
};

const closeFormModal = () => {
    showFormModal.value = false;
};

const submitForm = async () => {
    submitting.value = true;
    submitError.value = '';
    try {
        if (isEdit.value) {
            await api.put(`/suppliers/${editingId.value}`, form);
        } else {
            await api.post('/suppliers', form);
        }
        closeFormModal();
        loadSuppliers(page.value);
    } catch (e) {
        submitError.value = e.response?.data?.message || '保存失败';
    } finally {
        submitting.value = false;
    }
};

const openStatusModal = async (supplier) => {
    statusSupplier.value = supplier;
    statusForm.status = '';
    statusForm.remark = '';
    statusError.value = '';
    showStatusModal.value = true;

    try {
        const { data } = await api.get(`/suppliers/${supplier.id}/allowed-transitions`);
        allowedTransitions.value = data.data;
    } catch (e) {
        allowedTransitions.value = supplier.allowed_transitions || [];
    }
};

const closeStatusModal = () => {
    showStatusModal.value = false;
    statusSupplier.value = null;
    allowedTransitions.value = [];
};

const submitStatusChange = async () => {
    if (!statusForm.status) return;

    statusSubmitting.value = true;
    statusError.value = '';
    try {
        const endpoint = getStatusEndpoint(statusForm.status);
        await api.put(`/suppliers/${statusSupplier.value.id}/${endpoint}`, {
            remark: statusForm.remark,
        });
        closeStatusModal();
        loadSuppliers(page.value);
    } catch (e) {
        statusError.value = e.response?.data?.message || '状态变更失败';
    } finally {
        statusSubmitting.value = false;
    }
};

const getStatusEndpoint = (status) => {
    const map = {
        verifying: 'verify',
        active: 'activate',
        suspended: 'suspend',
        rejected: 'reject',
        cancelled: 'cancel',
    };
    return map[status] || 'status';
};

const openDetailModal = async (supplier) => {
    showDetailModal.value = true;
    detailSupplier.value = null;
    detailLoading.value = true;
    try {
        const { data } = await api.get(`/suppliers/${supplier.id}`);
        detailSupplier.value = data.data;
    } catch (e) {
        console.error(e);
    } finally {
        detailLoading.value = false;
    }
};

const closeDetailModal = () => {
    showDetailModal.value = false;
    detailSupplier.value = null;
};

const deleteSupplier = async (supplier) => {
    if (!confirm(`确定删除供应商「${supplier.name}」吗？`)) return;
    try {
        await api.delete(`/suppliers/${supplier.id}`);
        loadSuppliers(page.value);
    } catch (e) {
        alert(e.response?.data?.message || '删除失败');
    }
};

onMounted(() => {
    loadSuppliers();
});
</script>
