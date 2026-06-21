<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">提现账户管理</h1>
                <p class="mt-1 text-sm text-gray-500">管理您的提现收款账户信息</p>
            </div>
            <button
                @click="showCreateModal"
                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium flex items-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                添加账户
            </button>
        </div>

        <div v-if="loading" class="flex justify-center py-12">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-indigo-600 border-t-transparent"></div>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div
                v-for="account in accounts"
                :key="account.id"
                :class="[
                    'bg-white rounded-2xl border-2 p-6 transition-all hover:shadow-lg',
                    account.is_default ? 'border-indigo-500 shadow-md' : 'border-gray-200'
                ]"
            >
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div
                            :class="[
                                'w-12 h-12 rounded-xl flex items-center justify-center',
                                getMethodBgClass(account.withdraw_method?.code || account.method)
                            ]"
                        >
                            <div v-html="getMethodIcon(account.withdraw_method?.code || account.method)" class="w-6 h-6"></div>
                        </div>
                        <div>
                            <div class="font-semibold text-gray-900">{{ getMethodLabel(account.withdraw_method?.code || account.method) }}</div>
                            <div v-if="account.is_default" class="text-xs text-indigo-600 font-medium">
                                默认账户
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-1">
                        <button
                            @click="editAccount(account)"
                            class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
                            title="编辑"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                        <button
                            @click="deleteAccount(account)"
                            class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                            title="删除"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="space-y-3">
                    <template v-if="account.withdraw_method?.code === 'bank_transfer' || account.method === 'bank_transfer'">
                        <div>
                            <div class="text-xs text-gray-500">开户银行</div>
                            <div class="font-medium text-gray-900">{{ account.bank_name }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">银行账号</div>
                            <div class="font-mono font-medium text-gray-900">{{ maskAccount(account.bank_account) }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">开户姓名</div>
                            <div class="font-medium text-gray-900">{{ account.account_name }}</div>
                        </div>
                    </template>
                    <template v-else-if="account.withdraw_method?.code === 'alipay' || account.method === 'alipay'">
                        <div>
                            <div class="text-xs text-gray-500">支付宝账号</div>
                            <div class="font-mono font-medium text-gray-900">{{ maskAccount(account.alipay_account) }}</div>
                        </div>
                        <div v-if="account.real_name">
                            <div class="text-xs text-gray-500">真实姓名</div>
                            <div class="font-medium text-gray-900">{{ account.real_name }}</div>
                        </div>
                    </template>
                    <template v-else-if="account.withdraw_method?.code === 'wechat' || account.method === 'wechat'">
                        <div>
                            <div class="text-xs text-gray-500">微信账号</div>
                            <div class="font-mono font-medium text-gray-900">{{ maskAccount(account.wechat_account) }}</div>
                        </div>
                        <div v-if="account.real_name">
                            <div class="text-xs text-gray-500">真实姓名</div>
                            <div class="font-medium text-gray-900">{{ account.real_name }}</div>
                        </div>
                    </template>
                </div>

                <div class="flex items-center justify-between mt-6 pt-4 border-t border-gray-100">
                    <div class="text-xs text-gray-400">
                        添加时间: {{ formatDate(account.created_at) }}
                    </div>
                    <button
                        v-if="!account.is_default"
                        @click="setDefault(account)"
                        class="text-sm text-indigo-600 hover:text-indigo-700 font-medium"
                    >
                        设为默认
                    </button>
                </div>
            </div>

            <div
                @click="showCreateModal"
                class="bg-gray-50 rounded-2xl border-2 border-dashed border-gray-300 p-6 flex flex-col items-center justify-center min-h-[240px] cursor-pointer hover:border-indigo-400 hover:bg-indigo-50 transition-all"
            >
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </div>
                <div class="text-gray-600 font-medium">添加新账户</div>
                <div class="text-xs text-gray-400 mt-1">支持银行卡、支付宝、微信</div>
            </div>
        </div>

        <div v-if="accounts.length === 0 && !loading" class="text-center py-16 bg-white rounded-2xl border border-gray-200">
            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-1">暂无提现账户</h3>
            <p class="text-gray-500 mb-4">添加一个提现账户以便快速提现</p>
            <button
                @click="showCreateModal"
                class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium"
            >
                添加账户
            </button>
        </div>

        <div
            v-if="showFormModal"
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
            @click.self="showFormModal = false"
        >
            <div class="bg-white rounded-2xl p-6 max-w-lg w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">
                        {{ editingAccount ? '编辑账户' : '添加账户' }}
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
                            提现方式 <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-3 gap-3">
                            <label
                                v-for="method in availableMethods"
                                :key="method.value"
                                :class="[
                                    'relative flex items-center justify-center p-4 rounded-lg border-2 cursor-pointer transition-all',
                                    form.method === method.value
                                        ? 'border-indigo-600 bg-indigo-50'
                                        : 'border-gray-200 hover:border-gray-300'
                                ]"
                            >
                                <input
                                    type="radio"
                                    v-model="form.method"
                                    :value="method.value"
                                    class="sr-only"
                                />
                                <div class="text-center">
                                    <div v-html="method.icon" class="w-6 h-6 mx-auto mb-1"></div>
                                    <span class="text-sm font-medium">{{ method.label }}</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <template v-if="form.method === 'bank_transfer'">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                开户银行 <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.bank_name"
                                type="text"
                                placeholder="例如：中国工商银行"
                                class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                required
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                银行账号 <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.bank_account"
                                type="text"
                                placeholder="请输入银行卡号"
                                class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-mono"
                                required
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                开户姓名 <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.account_name"
                                type="text"
                                placeholder="请输入开户人姓名"
                                class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                required
                            />
                        </div>
                    </template>

                    <template v-else-if="form.method === 'alipay'">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                支付宝账号 <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.alipay_account"
                                type="text"
                                placeholder="请输入支付宝账号"
                                class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                required
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                真实姓名 <span class="text-gray-400 font-normal">（选填）</span>
                            </label>
                            <input
                                v-model="form.real_name"
                                type="text"
                                placeholder="请输入真实姓名"
                                class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            />
                        </div>
                    </template>

                    <template v-else-if="form.method === 'wechat'">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                微信账号 <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.wechat_account"
                                type="text"
                                placeholder="请输入微信账号"
                                class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                required
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                真实姓名 <span class="text-gray-400 font-normal">（选填）</span>
                            </label>
                            <input
                                v-model="form.real_name"
                                type="text"
                                placeholder="请输入真实姓名"
                                class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            />
                        </div>
                    </template>

                    <div class="flex items-center gap-2">
                        <input
                            v-model="form.is_default"
                            type="checkbox"
                            id="is_default"
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        <label for="is_default" class="text-sm text-gray-700">
                            设为默认提现账户
                        </label>
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
import withdrawAccountApi from '../../api/withdrawAccount';
import withdrawMethodApi from '../../api/withdrawMethod';

const loading = ref(false);
const submitting = ref(false);
const accounts = ref([]);
const methods = ref([]);
const showFormModal = ref(false);
const editingAccount = ref(null);

const form = reactive({
    method: 'bank_transfer',
    bank_name: '',
    bank_account: '',
    account_name: '',
    alipay_account: '',
    wechat_account: '',
    real_name: '',
    is_default: false,
});

const allPaymentMethods = [
    { value: 'bank_transfer', label: '银行转账', icon: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>' },
    { value: 'alipay', label: '支付宝', icon: '<svg class="w-6 h-6 text-blue-500" viewBox="0 0 24 24" fill="currentColor"><path d="M20.422 20.787c-3.03-1.356-5.69-2.74-6.55-3.14-2.42 1.43-4.41 2.12-6.46 2.12-3.27 0-5.51-1.95-5.51-5.05 0-2.76 1.96-5.05 5.31-5.05 2.35 0 4.3.88 5.71 2.06.31.35.56.71.76 1.06l.06.09.36-1.4h-5.26v-.83h6.64l.28-.01.33-1.24h1.56l-.7 3.08c.65.96 1.17 1.98 1.55 2.99 1.15 3.07 1.54 5.04 1.96 5.31z"/></svg>' },
    { value: 'wechat', label: '微信支付', icon: '<svg class="w-6 h-6 text-green-500" viewBox="0 0 24 24" fill="currentColor"><path d="M8.691 2.188C3.891 2.188 0 5.476 0 9.53c0 2.212 1.17 4.203 3.002 5.55a.59.59 0 0 1 .213.665l-.39 1.48c-.019.07-.048.141-.048.213 0 .163.13.295.29.295a.326.326 0 0 0 .167-.054l1.903-1.114a.864.864 0 0 1 .717-.098 10.16 10.16 0 0 0 2.837.403c.276 0 .543-.027.811-.05-.857-2.578.157-4.972 1.932-6.446 1.703-1.415 3.882-1.98 5.853-1.838-.576-3.583-4.196-6.348-8.596-6.348z"/></svg>' },
];

const availableMethods = ref(allPaymentMethods);

const getMethodLabel = (method) => {
    const m = allPaymentMethods.find(p => p.value === method);
    return m ? m.label : method;
};

const getMethodIcon = (method) => {
    const m = allPaymentMethods.find(p => p.value === method);
    return m ? m.icon : '';
};

const getMethodBgClass = (method) => {
    const classes = {
        bank_transfer: 'bg-gray-100',
        alipay: 'bg-blue-50',
        wechat: 'bg-green-50',
    };
    return classes[method] || 'bg-gray-100';
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
};

const maskAccount = (account) => {
    if (!account) return '';
    if (account.length <= 8) return account;
    const start = account.slice(0, 4);
    const end = account.slice(-4);
    return `${start}****${end}`;
};

const loadAccounts = async () => {
    loading.value = true;
    try {
        const { data } = await withdrawAccountApi.getList();
        accounts.value = data.data || data;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

const loadMethods = async () => {
    try {
        const { data } = await withdrawMethodApi.getEnabled();
        methods.value = data.data || data;
    } catch (e) {
        console.error(e);
    }
};

const showCreateModal = () => {
    editingAccount.value = null;
    Object.assign(form, {
        method: methods.value[0]?.code || 'bank_transfer',
        bank_name: '',
        bank_account: '',
        account_name: '',
        alipay_account: '',
        wechat_account: '',
        real_name: '',
        is_default: accounts.value.length === 0,
    });
    showFormModal.value = true;
};

const editAccount = (account) => {
    editingAccount.value = account;
    Object.assign(form, {
        method: account.withdraw_method?.code || account.method,
        bank_name: account.bank_name || '',
        bank_account: account.bank_account || '',
        account_name: account.account_name || '',
        alipay_account: account.alipay_account || '',
        wechat_account: account.wechat_account || '',
        real_name: account.real_name || '',
        is_default: account.is_default || false,
    });
    showFormModal.value = true;
};

const submitForm = async () => {
    submitting.value = true;
    try {
        const formData = {
            method: form.method,
            withdraw_method_id: methods.value.find(m => m.code === form.method)?.id,
            is_default: form.is_default,
        };

        if (form.method === 'bank_transfer') {
            formData.bank_name = form.bank_name;
            formData.bank_account = form.bank_account;
            formData.account_name = form.account_name;
        } else if (form.method === 'alipay') {
            formData.alipay_account = form.alipay_account;
            formData.real_name = form.real_name;
        } else if (form.method === 'wechat') {
            formData.wechat_account = form.wechat_account;
            formData.real_name = form.real_name;
        }

        if (editingAccount.value) {
            await withdrawAccountApi.update(editingAccount.value.id, formData);
        } else {
            await withdrawAccountApi.create(formData);
        }

        showFormModal.value = false;
        loadAccounts();
        alert('保存成功');
    } catch (e) {
        alert(e.response?.data?.message || '保存失败');
    } finally {
        submitting.value = false;
    }
};

const setDefault = async (account) => {
    if (!confirm('确认将此账户设为默认提现账户吗？')) return;
    try {
        await withdrawAccountApi.setDefault(account.id);
        loadAccounts();
        alert('设置成功');
    } catch (e) {
        alert(e.response?.data?.message || '设置失败');
    }
};

const deleteAccount = async (account) => {
    if (!confirm('确认删除此提现账户吗？')) return;
    try {
        await withdrawAccountApi.delete(account.id);
        loadAccounts();
        alert('删除成功');
    } catch (e) {
        alert(e.response?.data?.message || '删除失败');
    }
};

onMounted(() => {
    loadMethods();
    loadAccounts();
});
</script>
