<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="relative">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="搜索分销商名称、公司、联系人、电话..."
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
                    <option value="active">已启用</option>
                    <option value="suspended">已停用</option>
                    <option value="rejected">已拒绝</option>
                </select>
                <select
                    v-model="filterType"
                    class="px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white"
                >
                    <option value="">全部类型</option>
                    <option value="regional_agent">区域代理</option>
                    <option value="wholesaler">普通批发商</option>
                </select>
                <button
                    @click="viewMode = viewMode === 'tree' ? 'list' : 'tree'"
                    class="px-4 py-2.5 rounded-lg border border-gray-300 text-sm hover:bg-gray-50 transition-colors flex items-center gap-2"
                >
                    <svg v-if="viewMode === 'tree'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                    {{ viewMode === 'tree' ? '列表视图' : '树形视图' }}
                </button>
            </div>
            <button
                v-if="auth.can('distributor.create')"
                @click="openCreateModal"
                class="px-4 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors flex items-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                新建分销商
            </button>
        </div>

        <div v-if="viewMode === 'list'" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">分销商</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">类型</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">联系人</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">上级</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">信用额度</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">折扣率</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">状态</th>
                        <th class="text-right text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-if="loading" class="text-center">
                        <td colspan="8" class="px-6 py-12 text-gray-500">加载中...</td>
                    </tr>
                    <tr v-else-if="!distributors.length" class="text-center">
                        <td colspan="8" class="px-6 py-12 text-gray-500">暂无数据</td>
                    </tr>
                    <tr v-for="distributor in distributors" :key="distributor.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center text-green-600 font-medium text-sm">
                                    {{ distributor.name.charAt(0) }}
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900 text-sm">{{ distributor.name }}</div>
                                    <div class="text-sm text-gray-500">{{ distributor.company_name || '-' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span :class="typeBadge(distributor.type)">
                                {{ typeLabel(distributor.type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">{{ distributor.contact_person }}</div>
                            <div class="text-sm text-gray-500">{{ distributor.phone }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            {{ distributor.parent?.name || '-' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            ¥{{ formatNumber(distributor.credit_limit) }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            {{ distributor.discount_rate || 0 }}%
                        </td>
                        <td class="px-6 py-4">
                            <span :class="statusBadge(distributor.status)">
                                {{ statusLabel(distributor.status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button
                                    v-if="auth.can('distributor.approve') && distributor.status === 'pending'"
                                    @click="openApproveModal(distributor)"
                                    class="text-sm text-green-600 hover:text-green-700"
                                >
                                    审核
                                </button>
                                <button
                                    v-if="auth.can('distributor.edit')"
                                    @click="openEditModal(distributor)"
                                    class="text-sm text-indigo-600 hover:text-indigo-700"
                                >
                                    编辑
                                </button>
                                <button
                                    v-if="auth.can('distributor.edit')"
                                    @click="toggleStatus(distributor)"
                                    class="text-sm text-amber-600 hover:text-amber-700"
                                >
                                    {{ distributor.status === 'active' ? '停用' : '启用' }}
                                </button>
                                <button
                                    v-if="auth.can('distributor.delete')"
                                    @click="deleteDistributor(distributor)"
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
                        @click="page > 1 && loadDistributors(page - 1)"
                        :disabled="page <= 1"
                        class="px-3 py-1.5 rounded border border-gray-300 text-sm hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        上一页
                    </button>
                    <span class="text-sm text-gray-700">第 {{ page }} 页</span>
                    <button
                        @click="page * perPage < total && loadDistributors(page + 1)"
                        :disabled="page * perPage >= total"
                        class="px-3 py-1.5 rounded border border-gray-300 text-sm hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        下一页
                    </button>
                </div>
            </div>
        </div>

        <div v-else class="bg-white rounded-xl border border-gray-200 p-6">
            <div v-if="loading" class="text-center py-12 text-gray-500">加载中...</div>
            <div v-else-if="!distributorTree.length" class="text-center py-12 text-gray-500">暂无数据</div>
            <div v-else class="space-y-2">
                <template v-for="item in distributorTree" :key="item.id">
                    <TreeNode :node="item" :level="0" @edit="openEditModal" @delete="deleteDistributor" @approve="openApproveModal" @toggle-status="toggleStatus"/>
                </template>
            </div>
        </div>

        <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl w-full max-w-2xl max-h-[90vh] overflow-auto">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">{{ isEdit ? '编辑分销商' : '新建分销商' }}</h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <form @submit.prevent="submitForm" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">分销商名称 <span class="text-red-500">*</span></label>
                            <input v-model="form.name" type="text" required class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">公司名称</label>
                            <input v-model="form.company_name" type="text" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">分销商类型 <span class="text-red-500">*</span></label>
                            <select v-model="form.type" required class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white">
                                <option value="regional_agent">区域代理</option>
                                <option value="wholesaler">普通批发商</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">上级分销商</label>
                            <select v-model="form.parent_id" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white">
                                <option value="">无</option>
                                <option v-for="d in parentCandidates" :key="d.id" :value="d.id">{{ d.name }}</option>
                            </select>
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
                            <label class="block text-sm font-medium text-gray-700 mb-1">所在区域</label>
                            <input v-model="form.region" type="text" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
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
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">信用额度</label>
                            <input v-model.number="form.credit_limit" type="number" step="0.01" min="0" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">账户余额</label>
                            <input v-model.number="form.balance" type="number" step="0.01" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">折扣率 (%)</label>
                            <input v-model.number="form.discount_rate" type="number" min="0" max="100" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">营业执照</label>
                            <input v-model="form.business_license" type="text" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                        <div v-if="!isEdit">
                            <label class="block text-sm font-medium text-gray-700 mb-1">状态</label>
                            <select v-model="form.status" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white">
                                <option value="pending">待审核</option>
                                <option value="active">已启用</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <input v-model="form.is_cross_border" type="checkbox" id="is_cross_border" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"/>
                        <label for="is_cross_border" class="text-sm text-gray-700">跨境分销商</label>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">备注</label>
                        <textarea v-model="form.remark" rows="3" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"></textarea>
                    </div>
                    <div v-if="submitError" class="p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
                        {{ submitError }}
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" @click="closeModal" class="px-4 py-2 rounded-lg border border-gray-300 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                            取消
                        </button>
                        <button type="submit" :disabled="submitting" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700 transition-colors disabled:opacity-50">
                            {{ submitting ? '保存中...' : '保存' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="showApproveModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl w-full max-w-md">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">审核分销商</h3>
                    <button @click="closeApproveModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <form @submit.prevent="submitApprove" class="p-6 space-y-4">
                    <div class="p-4 rounded-lg bg-gray-50">
                        <div class="font-medium text-gray-900">{{ approvingDistributor?.name }}</div>
                        <div class="text-sm text-gray-500 mt-1">{{ approvingDistributor?.company_name }}</div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">审核结果 <span class="text-red-500">*</span></label>
                        <select v-model="approveForm.status" required class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white">
                            <option value="active">通过</option>
                            <option value="rejected">拒绝</option>
                            <option value="suspended">暂停</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">审核备注</label>
                        <textarea v-model="approveForm.remark" rows="3" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"></textarea>
                    </div>
                    <div v-if="approveError" class="p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
                        {{ approveError }}
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" @click="closeApproveModal" class="px-4 py-2 rounded-lg border border-gray-300 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                            取消
                        </button>
                        <button type="submit" :disabled="approving" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700 transition-colors disabled:opacity-50">
                            {{ approving ? '提交中...' : '提交' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref, onMounted, watch, computed, h } from 'vue';
import { useAuthStore } from '../../stores/auth';
import api from '../../api/axios';

const auth = useAuthStore();

const distributors = ref([]);
const distributorTree = ref([]);
const parentCandidates = ref([]);
const loading = ref(false);
const search = ref('');
const filterStatus = ref('');
const filterType = ref('');
const viewMode = ref('list');
const page = ref(1);
const perPage = ref(15);
const total = ref(0);

const showModal = ref(false);
const isEdit = ref(false);
const editingId = ref(null);
const submitting = ref(false);
const submitError = ref('');

const showApproveModal = ref(false);
const approvingDistributor = ref(null);
const approving = ref(false);
const approveError = ref('');
const approveForm = reactive({ status: 'active', remark: '' });

const defaultForm = () => ({
    name: '',
    company_name: '',
    business_license: '',
    type: 'wholesaler',
    region: '',
    contact_person: '',
    phone: '',
    email: '',
    address: '',
    bank_name: '',
    bank_account: '',
    credit_limit: 0,
    balance: 0,
    discount_rate: 0,
    status: 'pending',
    parent_id: null,
    remark: '',
    is_cross_border: false,
});

const form = reactive(defaultForm());

const TreeNode = {
    props: ['node', 'level'],
    emits: ['edit', 'delete', 'approve', 'toggle-status'],
    setup(props, { emit }) {
        const expanded = ref(true);
        const hasChildren = computed(() => props.node.children && props.node.children.length > 0);

        const statusLabel = (s) => ({ pending: '待审核', active: '已启用', suspended: '已停用', rejected: '已拒绝' }[s] || s);
        const statusBadge = (s) => {
            const map = {
                pending: 'bg-amber-100 text-amber-700',
                active: 'bg-green-100 text-green-700',
                suspended: 'bg-gray-100 text-gray-700',
                rejected: 'bg-red-100 text-red-700',
            };
            return map[s] || '';
        };
        const typeLabel = (t) => t === 'regional_agent' ? '区域代理' : '普通批发商';
        const typeBadge = (t) => t === 'regional_agent' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700';

        return () => h('div', { key: props.node.id }, [
            h('div', {
                class: 'flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 border border-transparent hover:border-gray-200',
                style: { paddingLeft: `${props.level * 24 + 12}px` }
            }, [
                hasChildren.value
                    ? h('button', {
                        onClick: () => expanded.value = !expanded.value,
                        class: 'p-1 hover:bg-gray-200 rounded'
                    }, [
                        h('svg', {
                            class: `w-4 h-4 text-gray-500 transition-transform ${expanded.value ? 'rotate-90' : ''}`,
                            fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24'
                        }, [
                            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 5l7 7-7 7' })
                        ])
                    ])
                    : h('div', { class: 'w-6' }),
                h('div', { class: 'w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center text-green-600 font-medium text-sm' }, props.node.name.charAt(0)),
                h('div', { class: 'flex-1 min-w-0' }, [
                    h('div', { class: 'flex items-center gap-2' }, [
                        h('span', { class: 'font-medium text-gray-900 text-sm' }, props.node.name),
                        h('span', { class: `inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${typeBadge(props.node.type)}` }, typeLabel(props.node.type)),
                        h('span', { class: `inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${statusBadge(props.node.status)}` }, statusLabel(props.node.status)),
                    ]),
                    h('div', { class: 'text-xs text-gray-500 mt-0.5' }, `${props.node.contact_person} · ${props.node.phone}`)
                ]),
                h('div', { class: 'flex items-center gap-2' }, [
                    auth.can('distributor.approve') && props.node.status === 'pending'
                        ? h('button', {
                            onClick: () => emit('approve', props.node),
                            class: 'text-sm text-green-600 hover:text-green-700'
                        }, '审核')
                        : null,
                    auth.can('distributor.edit')
                        ? h('button', {
                            onClick: () => emit('edit', props.node),
                            class: 'text-sm text-indigo-600 hover:text-indigo-700'
                        }, '编辑')
                        : null,
                    auth.can('distributor.edit')
                        ? h('button', {
                            onClick: () => emit('toggle-status', props.node),
                            class: 'text-sm text-amber-600 hover:text-amber-700'
                        }, props.node.status === 'active' ? '停用' : '启用')
                        : null,
                    auth.can('distributor.delete')
                        ? h('button', {
                            onClick: () => emit('delete', props.node),
                            class: 'text-sm text-red-600 hover:text-red-700'
                        }, '删除')
                        : null,
                ])
            ]),
            hasChildren.value && expanded.value
                ? h('div', { class: 'space-y-1' },
                    props.node.children.map(child =>
                        h(TreeNode, {
                            key: child.id,
                            node: child,
                            level: props.level + 1,
                            onEdit: (n) => emit('edit', n),
                            onDelete: (n) => emit('delete', n),
                            onApprove: (n) => emit('approve', n),
                            onToggleStatus: (n) => emit('toggle-status', n)
                        })
                    )
                )
                : null
        ]);
    }
};

const typeLabel = (t) => t === 'regional_agent' ? '区域代理' : '普通批发商';
const typeBadge = (t) => {
    const map = {
        regional_agent: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700',
        wholesaler: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700',
    };
    return map[t] || '';
};
const statusLabel = (s) => ({ pending: '待审核', active: '已启用', suspended: '已停用', rejected: '已拒绝' }[s] || s);
const statusBadge = (s) => {
    const map = {
        pending: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700',
        active: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700',
        suspended: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700',
        rejected: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700',
    };
    return map[s] || '';
};
const formatNumber = (num) => {
    if (num === null || num === undefined) return '0.00';
    return Number(num).toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
const formatDate = (d) => d ? new Date(d).toLocaleString('zh-CN') : '-';

const loadDistributors = async (p = 1) => {
    loading.value = true;
    page.value = p;
    try {
        const params = { page: p, per_page: perPage.value };
        if (search.value) params.search = search.value;
        if (filterStatus.value) params.status = filterStatus.value;
        if (filterType.value) params.type = filterType.value;
        const { data } = await api.get('/distributors', { params });
        distributors.value = data.data;
        total.value = data.total;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

const loadDistributorTree = async () => {
    loading.value = true;
    try {
        const { data } = await api.get('/distributors/tree');
        distributorTree.value = data.data || data;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

const loadParentCandidates = async () => {
    try {
        const { data } = await api.get('/distributors', { params: { per_page: 100 } });
        parentCandidates.value = data.data || [];
    } catch (e) {
        console.error(e);
    }
};

let debounceTimer = null;
watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        if (viewMode.value === 'list') loadDistributors(1);
        else loadDistributorTree();
    }, 300);
});
watch([filterStatus, filterType], () => {
    if (viewMode.value === 'list') loadDistributors(1);
    else loadDistributorTree();
});
watch(viewMode, (newVal) => {
    if (newVal === 'list') loadDistributors(1);
    else loadDistributorTree();
});

const openCreateModal = () => {
    Object.assign(form, defaultForm());
    isEdit.value = false;
    editingId.value = null;
    submitError.value = '';
    loadParentCandidates();
    showModal.value = true;
};

const openEditModal = (distributor) => {
    Object.assign(form, {
        name: distributor.name,
        company_name: distributor.company_name || '',
        business_license: distributor.business_license || '',
        type: distributor.type,
        region: distributor.region || '',
        contact_person: distributor.contact_person,
        phone: distributor.phone,
        email: distributor.email || '',
        address: distributor.address || '',
        bank_name: distributor.bank_name || '',
        bank_account: distributor.bank_account || '',
        credit_limit: distributor.credit_limit || 0,
        balance: distributor.balance || 0,
        discount_rate: distributor.discount_rate || 0,
        status: distributor.status,
        parent_id: distributor.parent_id,
        remark: distributor.remark || '',
        is_cross_border: distributor.is_cross_border || false,
    });
    isEdit.value = true;
    editingId.value = distributor.id;
    submitError.value = '';
    loadParentCandidates();
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

const submitForm = async () => {
    submitting.value = true;
    submitError.value = '';
    try {
        if (isEdit.value) {
            await api.put(`/distributors/${editingId.value}`, form);
        } else {
            await api.post('/distributors', form);
        }
        closeModal();
        if (viewMode.value === 'list') loadDistributors(page.value);
        else loadDistributorTree();
    } catch (e) {
        submitError.value = e.response?.data?.message || '保存失败';
    } finally {
        submitting.value = false;
    }
};

const openApproveModal = (distributor) => {
    approvingDistributor.value = distributor;
    approveForm.status = 'active';
    approveForm.remark = '';
    approveError.value = '';
    showApproveModal.value = true;
};

const closeApproveModal = () => {
    showApproveModal.value = false;
    approvingDistributor.value = null;
};

const submitApprove = async () => {
    approving.value = true;
    approveError.value = '';
    try {
        await api.put(`/distributors/${approvingDistributor.value.id}/approve`, approveForm);
        closeApproveModal();
        if (viewMode.value === 'list') loadDistributors(page.value);
        else loadDistributorTree();
    } catch (e) {
        approveError.value = e.response?.data?.message || '审核失败';
    } finally {
        approving.value = false;
    }
};

const toggleStatus = async (distributor) => {
    try {
        await api.put(`/distributors/${distributor.id}/toggle-status`);
        if (viewMode.value === 'list') loadDistributors(page.value);
        else loadDistributorTree();
    } catch (e) {
        alert(e.response?.data?.message || '操作失败');
    }
};

const deleteDistributor = async (distributor) => {
    if (!confirm(`确定删除分销商「${distributor.name}」吗？`)) return;
    try {
        await api.delete(`/distributors/${distributor.id}`);
        if (viewMode.value === 'list') loadDistributors(page.value);
        else loadDistributorTree();
    } catch (e) {
        alert(e.response?.data?.message || '删除失败');
    }
};

onMounted(() => {
    loadDistributors();
});
</script>
