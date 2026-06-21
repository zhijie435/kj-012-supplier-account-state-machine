<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">提现方式管理</h1>
                <p class="mt-1 text-sm text-gray-500">管理平台支持的提现方式</p>
            </div>
            <button
                @click="showCreateModal"
                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium flex items-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                添加方式
            </button>
        </div>

        <div v-if="loading" class="flex justify-center py-12">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-indigo-600 border-t-transparent"></div>
        </div>

        <div v-else class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
            <div v-if="methods.length > 0">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">图标</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">名称</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">代码</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">币种</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">描述</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">排序</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">状态</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr
                                v-for="method in methods"
                                :key="method.id"
                                class="hover:bg-gray-50 transition-colors"
                            >
                                <td class="px-6 py-4">
                                    <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center">
                                        <div v-html="getMethodIcon(method.code)" class="w-5 h-5"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900">{{ method.name }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-mono text-sm text-gray-600 bg-gray-100 px-2 py-1 rounded">{{ method.code }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-gray-600">{{ method.currency || 'CNY' }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-gray-500">{{ method.description || '-' }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-gray-600">{{ method.sort || 0 }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <button
                                        @click="toggleStatus(method)"
                                        :class="[
                                            'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                                            method.status ? 'bg-indigo-600' : 'bg-gray-200'
                                        ]"
                                    >
                                        <span
                                            :class="[
                                                'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                                                method.status ? 'translate-x-6' : 'translate-x-1'
                                            ]"
                                        />
                                    </button>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <button
                                            @click="editMethod(method)"
                                            class="text-indigo-600 hover:text-indigo-700 text-sm font-medium"
                                        >
                                            编辑
                                        </button>
                                        <button
                                            @click="deleteMethod(method)"
                                            class="text-red-600 hover:text-red-700 text-sm font-medium"
                                        >
                                            删除
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-else class="text-center py-16">
                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-1">暂无提现方式</h3>
                <p class="text-gray-500 mb-4">添加平台支持的提现方式</p>
                <button
                    @click="showCreateModal"
                    class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium"
                >
                    添加方式
                </button>
            </div>
        </div>

        <div
            v-if="showFormModal"
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
            @click.self="showFormModal = false"
        >
            <div class="bg-white rounded-2xl p-6 max-w-lg w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">
                        {{ editingMethod ? '编辑提现方式' : '添加提现方式' }}
                    </h3>
                    <button
                        @click="showFormModal = false"
                        class="text-gray-400 hover:text-gray-600"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            方式名称 <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="例如：银行转账"
                            class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            required
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            方式代码 <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.code"
                            type="text"
                            placeholder="例如：bank_transfer"
                            class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-mono"
                            required
                            :disabled="!!editingMethod"
                        />
                        <p class="text-xs text-gray-500 mt-1">创建后不可修改</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            币种
                        </label>
                        <input
                            v-model="form.currency"
                            type="text"
                            placeholder="CNY"
                            class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            描述
                        </label>
                        <textarea
                            v-model="form.description"
                            rows="2"
                            placeholder="请输入描述信息"
                            class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 resize-none"
                        ></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                排序
                            </label>
                            <input
                                v-model.number="form.sort"
                                type="number"
                                step="1"
                                min="0"
                                placeholder="0"
                                class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            />
                            <p class="text-xs text-gray-500 mt-1">数字越小越靠前</p>
                        </div>
                        <div class="flex items-center gap-2 pt-7">
                            <input
                                v-model="form.status"
                                type="checkbox"
                                id="status"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <label for="status" class="text-sm text-gray-700">
                                启用该提现方式
                            </label>
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-4 space-y-4">
                        <h4 class="text-sm font-medium text-gray-700">配置参数（JSON格式，选填）</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">手续费率(%)</label>
                                <input
                                    v-model.number="formConfig.fee_rate"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="w-full rounded border-gray-300 border px-3 py-2 text-sm"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">最低手续费</label>
                                <input
                                    v-model.number="formConfig.fee_min"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="w-full rounded border-gray-300 border px-3 py-2 text-sm"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">最高手续费</label>
                                <input
                                    v-model.number="formConfig.fee_max"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="w-full rounded border-gray-300 border px-3 py-2 text-sm"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">最低金额</label>
                                <input
                                    v-model.number="formConfig.min_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="w-full rounded border-gray-300 border px-3 py-2 text-sm"
                                />
                            </div>
                        </div>
                        <p class="text-xs text-gray-500">注：详细的手续费和金额限制建议在提现规则中配置</p>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button
                            type="button"
                            @click="showFormModal = false"
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
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import withdrawMethodApi from '../../api/withdrawMethod';

const loading = ref(false);
const submitting = ref(false);
const methods = ref([]);
const showFormModal = ref(false);
const editingMethod = ref(null);

const form = reactive({
    name: '',
    code: '',
    description: '',
    currency: 'CNY',
    sort: 0,
    status: true,
});

const formConfig = reactive({
    fee_rate: null,
    fee_min: null,
    fee_max: null,
    min_amount: null,
});

const methodIcons = {
    bank_transfer: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>',
    alipay: '<svg class="w-5 h-5 text-blue-500" viewBox="0 0 24 24" fill="currentColor"><path d="M20.422 20.787c-3.03-1.356-5.69-2.74-6.55-3.14-2.42 1.43-4.41 2.12-6.46 2.12-3.27 0-5.51-1.95-5.51-5.05 0-2.76 1.96-5.05 5.31-5.05 2.35 0 4.3.88 5.71 2.06.31.35.56.71.76 1.06l.06.09.36-1.4h-5.26v-.83h6.64l.28-.01.33-1.24h1.56l-.7 3.08c.65.96 1.17 1.98 1.55 2.99 1.15 3.07 1.54 5.04 1.96 5.31z"/></svg>',
    wechat: '<svg class="w-5 h-5 text-green-500" viewBox="0 0 24 24" fill="currentColor"><path d="M8.691 2.188C3.891 2.188 0 5.476 0 9.53c0 2.212 1.17 4.203 3.002 5.55a.59.59 0 0 1 .213.665l-.39 1.48c-.019.07-.048.141-.048.213 0 .163.13.295.29.295a.326.326 0 0 0 .167-.054l1.903-1.114a.864.864 0 0 1 .717-.098 10.16 10.16 0 0 0 2.837.403c.276 0 .543-.027.811-.05-.857-2.578.157-4.972 1.932-6.446 1.703-1.415 3.882-1.98 5.853-1.838-.576-3.583-4.196-6.348-8.596-6.348z"/></svg>',
    cash: '<svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
};

const getMethodIcon = (code) => {
    return methodIcons[code] || '<svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>';
};

const loadMethods = async () => {
    loading.value = true;
    try {
        const { data } = await withdrawMethodApi.getList();
        methods.value = data.data || data;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

const resetForm = () => {
    Object.assign(form, {
        name: '',
        code: '',
        description: '',
        currency: 'CNY',
        sort: 0,
        status: true,
    });
    Object.assign(formConfig, {
        fee_rate: null,
        fee_min: null,
        fee_max: null,
        min_amount: null,
    });
};

const showCreateModal = () => {
    editingMethod.value = null;
    resetForm();
    showFormModal.value = true;
};

const editMethod = (method) => {
    editingMethod.value = method;
    Object.assign(form, {
        name: method.name,
        code: method.code,
        description: method.description || '',
        currency: method.currency || 'CNY',
        sort: method.sort ?? 0,
        status: method.status ?? true,
    });

    const cfg = method.config || {};
    Object.assign(formConfig, {
        fee_rate: cfg.fee_rate ?? null,
        fee_min: cfg.fee_min ?? null,
        fee_max: cfg.fee_max ?? null,
        min_amount: cfg.min_amount ?? null,
    });
    showFormModal.value = true;
};

const submitForm = async () => {
    submitting.value = true;
    try {
        const formData = { ...form };

        const configData = {};
        if (formConfig.fee_rate !== null) configData.fee_rate = formConfig.fee_rate;
        if (formConfig.fee_min !== null) configData.fee_min = formConfig.fee_min;
        if (formConfig.fee_max !== null) configData.fee_max = formConfig.fee_max;
        if (formConfig.min_amount !== null) configData.min_amount = formConfig.min_amount;

        if (Object.keys(configData).length > 0) {
            formData.config = configData;
        }

        if (editingMethod.value) {
            await withdrawMethodApi.update(editingMethod.value.id, formData);
        } else {
            await withdrawMethodApi.create(formData);
        }

        showFormModal.value = false;
        loadMethods();
        alert('保存成功');
    } catch (e) {
        alert(e.response?.data?.message || '保存失败');
    } finally {
        submitting.value = false;
    }
};

const toggleStatus = async (method) => {
    const newStatus = !method.status;
    try {
        await withdrawMethodApi.toggleStatus(method.id, newStatus);
        method.status = newStatus;
    } catch (e) {
        alert(e.response?.data?.message || '操作失败');
    }
};

const deleteMethod = async (method) => {
    if (!confirm(`确认删除提现方式"${method.name}"吗？`)) return;
    try {
        await withdrawMethodApi.delete(method.id);
        loadMethods();
        alert('删除成功');
    } catch (e) {
        alert(e.response?.data?.message || '删除失败');
    }
};

onMounted(() => {
    loadMethods();
});
</script>
