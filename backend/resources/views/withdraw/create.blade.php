@extends('layouts.app')

@section('title', '申请提现')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">申请提现</h1>
            <p class="mt-1 text-sm text-gray-500">将账户余额提现至您的收款账户</p>
        </div>
        <a href="{{ url('/withdraw/list') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium flex items-center gap-2">
            ← 返回提现记录
        </a>
    </div>

    <div id="withdraw-create-app">
        <withdraw-create></withdraw-create>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Vue !== 'undefined') {
            const WithdrawCreate = {
                data() {
                    return {
                        loading: true,
                        submitting: false,
                        balance: {
                            available: 0,
                            frozen: 0,
                            currency: 'CNY'
                        },
                        accounts: [],
                        methods: [],
                        selectedAccountId: null,
                        form: {
                            amount: '',
                            account_id: '',
                            method: '',
                            remark: ''
                        },
                        currentRule: null,
                        feeAmount: 0,
                        actualAmount: 0
                    };
                },
                mounted() {
                    this.loadInitialData();
                },
                watch: {
                    'form.amount': 'calculateFee',
                    'form.method': 'onMethodChange'
                },
                methods: {
                    async loadInitialData() {
                        this.loading = true;
                        try {
                            const [balanceRes, accountsRes, methodsRes] = await Promise.all([
                                axios.get('/api/wallet/balance'),
                                axios.get('/api/withdraw-accounts'),
                                axios.get('/api/withdraw-methods/enabled')
                            ]);

                            this.balance = balanceRes.data.data?.balance || balanceRes.data.balance || balanceRes.data;
                            this.accounts = accountsRes.data.data || accountsRes.data;
                            this.methods = methodsRes.data.data || methodsRes.data;

                            if (this.accounts.length > 0) {
                                const defaultAccount = this.accounts.find(a => a.is_default) || this.accounts[0];
                                this.form.account_id = defaultAccount.id;
                                this.form.method = defaultAccount.withdraw_method?.code || defaultAccount.method || this.methods[0]?.code;
                            }

                            if (this.methods.length > 0 && !this.form.method) {
                                this.form.method = this.methods[0].code;
                            }
                        } catch (e) {
                            alert(e.response?.data?.message || '加载数据失败');
                        } finally {
                            this.loading = false;
                        }
                    },
                    async onMethodChange() {
                        if (!this.form.method) return;

                        try {
                            const response = await axios.get('/api/withdraw-rules/applicable', {
                                params: {
                                    method: this.form.method,
                                    amount: parseFloat(this.form.amount) || 0
                                }
                            });
                            this.currentRule = response.data.data || response.data;
                        } catch (e) {
                            this.currentRule = null;
                        }

                        this.calculateFee();
                    },
                    calculateFee() {
                        const amount = parseFloat(this.form.amount) || 0;
                        if (amount <= 0) {
                            this.feeAmount = 0;
                            this.actualAmount = 0;
                            return;
                        }

                        if (this.currentRule) {
                            this.feeAmount = this.calculateFeeByRule(amount, this.currentRule);
                        } else {
                            const method = this.methods.find(m => m.code === this.form.method);
                            if (method) {
                                this.feeAmount = this.calculateFeeByMethod(amount, method);
                            } else {
                                this.feeAmount = 0;
                            }
                        }

                        this.actualAmount = Math.max(0, amount - this.feeAmount);
                    },
                    calculateFeeByRule(amount, rule) {
                        let fee = 0;
                        if (rule.fee_type === 'free') {
                            fee = 0;
                        } else if (rule.fee_type === 'fixed') {
                            fee = parseFloat(rule.fee_value) || 0;
                        } else if (rule.fee_type === 'percentage') {
                            fee = amount * (parseFloat(rule.fee_value) || 0) / 100;
                            if (rule.min_fee > 0 && fee < rule.min_fee) fee = rule.min_fee;
                            if (rule.max_fee > 0 && fee > rule.max_fee) fee = rule.max_fee;
                        }
                        return parseFloat(fee.toFixed(2));
                    },
                    calculateFeeByMethod(amount, method) {
                        let fee = 0;
                        if (method.fee_type === 'free') {
                            fee = 0;
                        } else if (method.fee_type === 'fixed') {
                            fee = parseFloat(method.fee_value) || 0;
                        } else if (method.fee_type === 'percentage') {
                            fee = amount * (parseFloat(method.fee_value) || 0) / 100;
                            if (method.min_fee > 0 && fee < method.min_fee) fee = method.min_fee;
                            if (method.max_fee > 0 && fee > method.max_fee) fee = method.max_fee;
                        }
                        return parseFloat(fee.toFixed(2));
                    },
                    async selectAccount(account) {
                        this.form.account_id = account.id;
                        this.form.method = account.withdraw_method?.code || account.method;
                    },
                    async setMaxAmount() {
                        this.form.amount = this.balance.available?.toString() || '0';
                    },
                    async submitWithdraw() {
                        if (!this.form.account_id) {
                            alert('请先添加并选择一个提现账户');
                            return;
                        }

                        const amount = parseFloat(this.form.amount);
                        if (amount <= 0) {
                            alert('请输入正确的提现金额');
                            return;
                        }

                        if (amount > this.balance.available) {
                            alert('提现金额不能超过可用余额');
                            return;
                        }

                        if (!confirm(`确认提现 ¥${this.formatNumber(amount)}？\n手续费：¥${this.formatNumber(this.feeAmount)}\n实际到账：¥${this.formatNumber(this.actualAmount)}`)) {
                            return;
                        }

                        this.submitting = true;
                        try {
                            const response = await axios.post('/api/withdrawals', {
                                amount: amount,
                                method: this.form.method,
                                account_id: this.form.account_id,
                                remark: this.form.remark
                            });

                            alert('提现申请提交成功！');
                            window.location.href = '/withdraw/list';
                        } catch (e) {
                            alert(e.response?.data?.message || '提现申请提交失败');
                        } finally {
                            this.submitting = false;
                        }
                    },
                    formatNumber(num) {
                        return new Intl.NumberFormat('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(parseFloat(num || 0));
                    },
                    getMethodLabel(method) {
                        const labels = {
                            bank_transfer: '银行转账',
                            alipay: '支付宝',
                            wechat: '微信支付'
                        };
                        return labels[method] || method;
                    },
                    maskAccount(account) {
                        if (!account) return '';
                        if (account.length <= 8) return account;
                        return account.slice(0, 4) + '****' + account.slice(-4);
                    },
                    getAccountDisplay(account) {
                        const method = account.withdraw_method?.code || account.method;
                        if (method === 'bank_transfer') {
                            return `${account.bank_name} · ${this.maskAccount(account.bank_account)}`;
                        } else if (method === 'alipay') {
                            return `支付宝 · ${this.maskAccount(account.alipay_account)}`;
                        } else if (method === 'wechat') {
                            return `微信 · ${this.maskAccount(account.wechat_account)}`;
                        }
                        return this.getMethodLabel(method);
                    }
                },
                template: `
                    <div>
                        <div v-if="loading" class="flex justify-center py-12">
                            <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-indigo-600 border-t-transparent"></div>
                        </div>

                        <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <div class="lg:col-span-2 space-y-6">
                                <div class="bg-white rounded-2xl border border-gray-200 p-6">
                                    <h2 class="text-lg font-semibold text-gray-900 mb-4">可用余额</h2>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="bg-gradient-to-br from-indigo-50 to-blue-50 rounded-xl p-5">
                                            <div class="text-sm text-indigo-600 mb-1">可用余额</div>
                                            <div class="text-3xl font-bold text-indigo-600">¥{{ formatNumber(balance.available) }}</div>
                                        </div>
                                        <div class="bg-gradient-to-br from-orange-50 to-yellow-50 rounded-xl p-5">
                                            <div class="text-sm text-orange-600 mb-1">冻结余额</div>
                                            <div class="text-3xl font-bold text-orange-600">¥{{ formatNumber(balance.frozen) }}</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-white rounded-2xl border border-gray-200 p-6">
                                    <h2 class="text-lg font-semibold text-gray-900 mb-4">选择提现账户</h2>

                                    <div v-if="accounts.length === 0" class="text-center py-8 border-2 border-dashed border-gray-200 rounded-xl">
                                        <p class="text-gray-500 mb-3">您还没有添加提现账户</p>
                                        <a href="/withdraw-accounts" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium inline-flex items-center gap-2">
                                            + 添加账户
                                        </a>
                                    </div>

                                    <div v-else class="space-y-3">
                                        <div v-for="account in accounts" :key="account.id" @click="selectAccount(account)" :class="['border-2 rounded-xl p-4 cursor-pointer transition-all', form.account_id === account.id ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50']">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center">
                                                        <span>{{ getMethodLabel(account.withdraw_method?.code || account.method) === '支付宝' ? '💳' : getMethodLabel(account.withdraw_method?.code || account.method) === '微信支付' ? '💬' : '🏦' }}</span>
                                                    </div>
                                                    <div>
                                                        <div class="font-medium text-gray-900">{{ getAccountDisplay(account) }}</div>
                                                        <div v-if="account.is_default" class="text-xs text-indigo-600">默认账户</div>
                                                    </div>
                                                </div>
                                                <div :class="['w-5 h-5 rounded-full border-2 flex items-center justify-center', form.account_id === account.id ? 'border-indigo-500 bg-indigo-500' : 'border-gray-300']">
                                                    <svg v-if="form.account_id === account.id" class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-white rounded-2xl border border-gray-200 p-6">
                                    <h2 class="text-lg font-semibold text-gray-900 mb-4">提现金额</h2>
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">提现方式</label>
                                            <select v-model="form.method" class="w-full rounded-lg border-gray-300 border px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-base">
                                                <option v-for="method in methods" :key="method.id" :value="method.code">{{ method.name }}</option>
                                            </select>
                                        </div>
                                        <div>
                                            <div class="flex items-center justify-between mb-2">
                                                <label class="block text-sm font-medium text-gray-700">提现金额</label>
                                                <button @click="setMaxAmount" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">全部提现</button>
                                            </div>
                                            <div class="relative">
                                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-xl font-semibold text-gray-900">¥</span>
                                                <input v-model="form.amount" type="number" step="0.01" min="0" :max="balance.available" placeholder="请输入提现金额" class="w-full rounded-lg border-gray-300 border pl-10 pr-4 py-4 text-xl font-semibold focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                                            </div>
                                            <div v-if="currentRule" class="mt-3 p-3 bg-blue-50 rounded-lg">
                                                <div class="text-sm text-blue-700">
                                                    <span class="font-medium">适用规则：</span>{{ currentRule.name }}
                                                    <span v-if="currentRule.min_amount > 0" class="ml-2">最低 ¥{{ formatNumber(currentRule.min_amount) }}</span>
                                                    <span v-if="currentRule.max_amount > 0" class="ml-2">最高 ¥{{ formatNumber(currentRule.max_amount) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">备注（选填）</label>
                                            <textarea v-model="form.remark" rows="2" placeholder="请输入备注信息" class="w-full rounded-lg border-gray-300 border px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-6">
                                <div class="bg-white rounded-2xl border border-gray-200 p-6 sticky top-6">
                                    <h2 class="text-lg font-semibold text-gray-900 mb-4">费用明细</h2>
                                    <div class="space-y-4">
                                        <div class="flex items-center justify-between">
                                            <span class="text-gray-600">提现金额</span>
                                            <span class="text-gray-900 font-medium">¥{{ formatNumber(form.amount) }}</span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-gray-600">手续费</span>
                                            <span class="text-orange-600 font-medium">- ¥{{ formatNumber(feeAmount) }}</span>
                                        </div>
                                        <div class="border-t border-gray-200 pt-4 mt-4">
                                            <div class="flex items-center justify-between">
                                                <span class="text-lg font-semibold text-gray-900">预计到账</span>
                                                <span class="text-2xl font-bold text-green-600">¥{{ formatNumber(actualAmount) }}</span>
                                            </div>
                                        </div>
                                        <div class="text-xs text-gray-500 mt-2">
                                            到账时间：1-3个工作日
                                        </div>
                                    </div>

                                    <button @click="submitWithdraw" :disabled="submitting || !form.account_id || parseFloat(form.amount) <= 0 || parseFloat(form.amount) > balance.available" class="w-full mt-6 px-6 py-3.5 bg-gradient-to-r from-indigo-600 to-blue-600 text-white rounded-xl hover:from-indigo-700 hover:to-blue-700 transition-all text-base font-semibold disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-indigo-200">
                                        {{ submitting ? '提交中...' : '提交提现申请' }}
                                    </button>

                                    <div class="mt-6 p-4 bg-gray-50 rounded-xl">
                                        <h3 class="text-sm font-medium text-gray-900 mb-2">温馨提示</h3>
                                        <ul class="text-xs text-gray-500 space-y-1">
                                            <li>• 工作日15:00前提现，当日处理</li>
                                            <li>• 节假日提现，顺延至下个工作日</li>
                                            <li>• 请确保收款账户信息正确</li>
                                            <li>• 到账时间以银行实际处理为准</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `
            };

            new Vue({
                el: '#withdraw-create-app',
                components: {
                    'withdraw-create': WithdrawCreate
                }
            });
        }
    });
</script>
@endpush
