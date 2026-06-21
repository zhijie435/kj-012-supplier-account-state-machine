@extends('layouts.app')

@section('title', '提现账户管理')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">提现账户管理</h1>
            <p class="mt-1 text-sm text-gray-500">管理您的提现收款账户信息</p>
        </div>
    </div>

    <div id="withdraw-accounts-app">
        <withdraw-accounts></withdraw-accounts>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Vue !== 'undefined') {
            const WithdrawAccounts = {
                data() {
                    return {
                        loading: true,
                        accounts: [],
                        methods: [],
                        showForm: false,
                        editingAccount: null,
                        form: {
                            method: 'bank_transfer',
                            bank_name: '',
                            bank_account: '',
                            account_name: '',
                            alipay_account: '',
                            wechat_account: '',
                            real_name: '',
                            is_default: false
                        },
                        submitting: false
                    };
                },
                mounted() {
                    this.loadMethods();
                    this.loadAccounts();
                },
                methods: {
                    async loadMethods() {
                        try {
                            const response = await axios.get('/api/withdraw-methods/enabled');
                            this.methods = response.data.data || response.data;
                        } catch (e) {
                            console.error(e);
                        }
                    },
                    async loadAccounts() {
                        this.loading = true;
                        try {
                            const response = await axios.get('/api/withdraw-accounts');
                            this.accounts = response.data.data || response.data;
                        } catch (e) {
                            console.error(e);
                        } finally {
                            this.loading = false;
                        }
                    },
                    showCreate() {
                        this.editingAccount = null;
                        this.form = {
                            method: this.methods[0]?.code || 'bank_transfer',
                            bank_name: '',
                            bank_account: '',
                            account_name: '',
                            alipay_account: '',
                            wechat_account: '',
                            real_name: '',
                            is_default: this.accounts.length === 0
                        };
                        this.showForm = true;
                    },
                    editAccount(account) {
                        this.editingAccount = account;
                        this.form = {
                            method: account.withdraw_method?.code || account.method,
                            bank_name: account.bank_name || '',
                            bank_account: account.bank_account || '',
                            account_name: account.account_name || '',
                            alipay_account: account.alipay_account || '',
                            wechat_account: account.wechat_account || '',
                            real_name: account.real_name || '',
                            is_default: account.is_default || false
                        };
                        this.showForm = true;
                    },
                    async submitForm() {
                        this.submitting = true;
                        try {
                            const formData = {
                                method: this.form.method,
                                withdraw_method_id: this.methods.find(m => m.code === this.form.method)?.id,
                                is_default: this.form.is_default
                            };

                            if (this.form.method === 'bank_transfer') {
                                formData.bank_name = this.form.bank_name;
                                formData.bank_account = this.form.bank_account;
                                formData.account_name = this.form.account_name;
                            } else if (this.form.method === 'alipay') {
                                formData.alipay_account = this.form.alipay_account;
                                formData.real_name = this.form.real_name;
                            } else if (this.form.method === 'wechat') {
                                formData.wechat_account = this.form.wechat_account;
                                formData.real_name = this.form.real_name;
                            }

                            if (this.editingAccount) {
                                await axios.put(`/api/withdraw-accounts/${this.editingAccount.id}`, formData);
                            } else {
                                await axios.post('/api/withdraw-accounts', formData);
                            }

                            this.showForm = false;
                            this.loadAccounts();
                            alert('保存成功');
                        } catch (e) {
                            alert(e.response?.data?.message || '保存失败');
                        } finally {
                            this.submitting = false;
                        }
                    },
                    async setDefault(account) {
                        if (!confirm('确认将此账户设为默认提现账户吗？')) return;
                        try {
                            await axios.post(`/api/withdraw-accounts/${account.id}/set-default`);
                            this.loadAccounts();
                            alert('设置成功');
                        } catch (e) {
                            alert(e.response?.data?.message || '设置失败');
                        }
                    },
                    async deleteAccount(account) {
                        if (!confirm('确认删除此提现账户吗？')) return;
                        try {
                            await axios.delete(`/api/withdraw-accounts/${account.id}`);
                            this.loadAccounts();
                            alert('删除成功');
                        } catch (e) {
                            alert(e.response?.data?.message || '删除失败');
                        }
                    },
                    formatNumber(num) {
                        return new Intl.NumberFormat('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(parseFloat(num || 0));
                    },
                    formatDate(dateStr) {
                        if (!dateStr) return '';
                        const d = new Date(dateStr);
                        return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
                    },
                    maskAccount(account) {
                        if (!account) return '';
                        if (account.length <= 8) return account;
                        return account.slice(0, 4) + '****' + account.slice(-4);
                    },
                    getMethodLabel(method) {
                        const labels = {
                            bank_transfer: '银行转账',
                            alipay: '支付宝',
                            wechat: '微信支付'
                        };
                        return labels[method] || method;
                    }
                },
                template: `
                    <div>
                        <div class="mb-6">
                            <button @click="showCreate" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium flex items-center gap-2">
                                <span>+</span>
                                添加账户
                            </button>
                        </div>

                        <div v-if="loading" class="flex justify-center py-12">
                            <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-indigo-600 border-t-transparent"></div>
                        </div>

                        <div v-else-if="accounts.length === 0" class="text-center py-16 bg-white rounded-2xl border border-gray-200">
                            <p class="text-gray-500 mb-4">暂无提现账户</p>
                            <button @click="showCreate" class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium">
                                添加账户
                            </button>
                        </div>

                        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div v-for="account in accounts" :key="account.id" :class="['bg-white rounded-2xl border-2 p-6 transition-all hover:shadow-lg', account.is_default ? 'border-indigo-500 shadow-md' : 'border-gray-200']">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-xl bg-gray-100 flex items-center justify-center">
                                            <span class="text-lg">{{ getMethodLabel(account.withdraw_method?.code || account.method) === '支付宝' ? '💳' : getMethodLabel(account.withdraw_method?.code || account.method) === '微信支付' ? '💬' : '🏦' }}</span>
                                        </div>
                                        <div>
                                            <div class="font-semibold text-gray-900">{{ getMethodLabel(account.withdraw_method?.code || account.method) }}</div>
                                            <div v-if="account.is_default" class="text-xs text-indigo-600 font-medium">默认账户</div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <button @click="editAccount(account)" class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors">
                                            ✏️
                                        </button>
                                        <button @click="deleteAccount(account)" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                            🗑️
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
                                    <div class="text-xs text-gray-400">添加时间: {{ formatDate(account.created_at) }}</div>
                                    <button v-if="!account.is_default" @click="setDefault(account)" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                                        设为默认
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-if="showForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="showForm = false">
                            <div class="bg-white rounded-2xl p-6 max-w-lg w-full mx-4">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-lg font-semibold text-gray-900">{{ editingAccount ? '编辑账户' : '添加账户' }}</h3>
                                    <button @click="showForm = false" class="text-gray-400 hover:text-gray-600">✕</button>
                                </div>

                                <form @submit.prevent="submitForm" class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">提现方式 <span class="text-red-500">*</span></label>
                                        <select v-model="form.method" class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                                            <option v-for="method in methods" :key="method.id" :value="method.code">{{ method.name }}</option>
                                        </select>
                                    </div>

                                    <template v-if="form.method === 'bank_transfer'">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">开户银行 <span class="text-red-500">*</span></label>
                                            <input v-model="form.bank_name" type="text" placeholder="例如：中国工商银行" class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">银行账号 <span class="text-red-500">*</span></label>
                                            <input v-model="form.bank_account" type="text" placeholder="请输入银行卡号" class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-mono" required />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">开户姓名 <span class="text-red-500">*</span></label>
                                            <input v-model="form.account_name" type="text" placeholder="请输入开户人姓名" class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required />
                                        </div>
                                    </template>

                                    <template v-else-if="form.method === 'alipay'">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">支付宝账号 <span class="text-red-500">*</span></label>
                                            <input v-model="form.alipay_account" type="text" placeholder="请输入支付宝账号" class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">真实姓名 <span class="text-gray-400 font-normal">（选填）</span></label>
                                            <input v-model="form.real_name" type="text" placeholder="请输入真实姓名" class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                                        </div>
                                    </template>

                                    <template v-else-if="form.method === 'wechat'">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">微信账号 <span class="text-red-500">*</span></label>
                                            <input v-model="form.wechat_account" type="text" placeholder="请输入微信账号" class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">真实姓名 <span class="text-gray-400 font-normal">（选填）</span></label>
                                            <input v-model="form.real_name" type="text" placeholder="请输入真实姓名" class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                                        </div>
                                    </template>

                                    <div class="flex items-center gap-2">
                                        <input v-model="form.is_default" type="checkbox" id="is_default" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                        <label for="is_default" class="text-sm text-gray-700">设为默认提现账户</label>
                                    </div>

                                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                                        <button type="button" @click="showForm = false" class="px-4 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">取消</button>
                                        <button type="submit" :disabled="submitting" class="px-4 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                                            {{ submitting ? '保存中...' : '保存' }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                `
            };

            new Vue({
                el: '#withdraw-accounts-app',
                components: {
                    'withdraw-accounts': WithdrawAccounts
                }
            });
        }
    });
</script>
@endpush
