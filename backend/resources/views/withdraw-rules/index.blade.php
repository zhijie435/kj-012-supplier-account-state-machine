@extends('layouts.app')

@section('title', '提现规则配置')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">提现规则配置</h1>
            <p class="mt-1 text-sm text-gray-500">管理不同用户等级和场景下的提现规则</p>
        </div>
    </div>

    <div id="withdraw-rules-app">
        <withdraw-rules></withdraw-rules>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Vue !== 'undefined') {
            const WithdrawRules = {
                data() {
                    return {
                        loading: true,
                        rules: [],
                        methods: [],
                        showForm: false,
                        editingRule: null,
                        form: {
                            name: '',
                            description: '',
                            method: '',
                            user_level: 0,
                            min_amount: 0,
                            max_amount: 0,
                            fee_type: 'percentage',
                            fee_value: 0,
                            min_fee: 0,
                            max_fee: 0,
                            daily_limit: 0,
                            monthly_limit: 0,
                            daily_quota: 0,
                            is_enabled: true,
                            start_time: '',
                            end_time: ''
                        },
                        submitting: false,
                        feeTypes: [
                            { value: 'percentage', label: '按比例' },
                            { value: 'fixed', label: '固定金额' },
                            { value: 'free', label: '免费' }
                        ],
                        userLevels: [
                            { value: 0, label: '普通用户' },
                            { value: 1, label: 'VIP1' },
                            { value: 2, label: 'VIP2' },
                            { value: 3, label: 'VIP3' },
                            { value: 4, label: 'VIP4' },
                            { value: 5, label: 'VIP5' }
                        ]
                    };
                },
                mounted() {
                    this.loadData();
                },
                methods: {
                    async loadData() {
                        this.loading = true;
                        try {
                            const [rulesRes, methodsRes] = await Promise.all([
                                axios.get('/api/withdraw-rules'),
                                axios.get('/api/withdraw-methods')
                            ]);
                            this.rules = (rulesRes.data.data || rulesRes.data).map(r => ({
                                ...r,
                                toggling: false
                            }));
                            this.methods = methodsRes.data.data || methodsRes.data;
                        } catch (e) {
                            console.error(e);
                        } finally {
                            this.loading = false;
                        }
                    },
                    showCreate() {
                        this.editingRule = null;
                        this.form = {
                            name: '',
                            description: '',
                            method: this.methods[0]?.code || '',
                            user_level: 0,
                            min_amount: 100,
                            max_amount: 50000,
                            fee_type: 'percentage',
                            fee_value: 0.5,
                            min_fee: 1,
                            max_fee: 50,
                            daily_limit: 20000,
                            monthly_limit: 100000,
                            daily_quota: 5,
                            is_enabled: true,
                            start_time: '',
                            end_time: ''
                        };
                        this.showForm = true;
                    },
                    editRule(rule) {
                        this.editingRule = rule;
                        this.form = {
                            name: rule.name,
                            description: rule.description || '',
                            method: rule.method,
                            user_level: rule.user_level || 0,
                            min_amount: rule.min_amount || 0,
                            max_amount: rule.max_amount || 0,
                            fee_type: rule.fee_type,
                            fee_value: rule.fee_value || 0,
                            min_fee: rule.min_fee || 0,
                            max_fee: rule.max_fee || 0,
                            daily_limit: rule.daily_limit || 0,
                            monthly_limit: rule.monthly_limit || 0,
                            daily_quota: rule.daily_quota || 0,
                            is_enabled: rule.is_enabled,
                            start_time: rule.start_time || '',
                            end_time: rule.end_time || ''
                        };
                        this.showForm = true;
                    },
                    async submitForm() {
                        this.submitting = true;
                        try {
                            if (this.editingRule) {
                                await axios.put(`/api/withdraw-rules/${this.editingRule.id}`, this.form);
                            } else {
                                await axios.post('/api/withdraw-rules', this.form);
                            }

                            this.showForm = false;
                            this.loadData();
                            alert('保存成功');
                        } catch (e) {
                            alert(e.response?.data?.message || '保存失败');
                        } finally {
                            this.submitting = false;
                        }
                    },
                    async toggleStatus(rule) {
                        rule.toggling = true;
                        try {
                            const newValue = !rule.is_enabled;
                            await axios.patch(`/api/withdraw-rules/${rule.id}/toggle`, { is_enabled: newValue });
                            rule.is_enabled = newValue;
                        } catch (e) {
                            alert(e.response?.data?.message || '操作失败');
                        } finally {
                            rule.toggling = false;
                        }
                    },
                    async deleteRule(rule) {
                        if (!confirm(`确认删除规则"${rule.name}"吗？`)) return;
                        try {
                            await axios.delete(`/api/withdraw-rules/${rule.id}`);
                            this.loadData();
                            alert('删除成功');
                        } catch (e) {
                            alert(e.response?.data?.message || '删除失败');
                        }
                    },
                    formatNumber(num) {
                        return new Intl.NumberFormat('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(parseFloat(num || 0));
                    },
                    getMethodLabel(method) {
                        const m = this.methods.find(x => x.code === method);
                        return m?.name || method;
                    },
                    getFeeDescription(rule) {
                        if (rule.fee_type === 'free') return '免费';
                        if (rule.fee_type === 'fixed') return `固定 ¥${this.formatNumber(rule.fee_value)}`;
                        return `${rule.fee_value}%`;
                    },
                    getUserLevelLabel(level) {
                        const l = this.userLevels.find(x => x.value === level);
                        return l?.label || `Level ${level}`;
                    }
                },
                template: `
                    <div>
                        <div class="mb-6">
                            <button @click="showCreate" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium flex items-center gap-2">
                                <span>+</span>
                                添加规则
                            </button>
                        </div>

                        <div v-if="loading" class="flex justify-center py-12">
                            <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-indigo-600 border-t-transparent"></div>
                        </div>

                        <div v-else-if="rules.length === 0" class="text-center py-16 bg-white rounded-2xl border border-gray-200">
                            <p class="text-gray-500 mb-4">暂无提现规则</p>
                            <button @click="showCreate" class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium">
                                添加规则
                            </button>
                        </div>

                        <div v-else class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">规则名称</th>
                                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">用户等级</th>
                                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">提现方式</th>
                                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">金额范围</th>
                                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">手续费</th>
                                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">限额</th>
                                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">状态</th>
                                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">操作</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <tr v-for="rule in rules" :key="rule.id" class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4">
                                                <div class="font-medium text-gray-900">{{ rule.name }}</div>
                                                <div v-if="rule.description" class="text-xs text-gray-500 mt-1">{{ rule.description }}</div>
                                            </td>
                                            <td class="px-6 py-4"><span class="text-sm text-gray-600">{{ getUserLevelLabel(rule.user_level) }}</span></td>
                                            <td class="px-6 py-4"><span class="text-sm text-gray-600">{{ getMethodLabel(rule.method) }}</span></td>
                                            <td class="px-6 py-4">
                                                <span class="text-sm text-gray-900">¥{{ formatNumber(rule.min_amount || 0) }} - ¥{{ rule.max_amount ? formatNumber(rule.max_amount) : '不限' }}</span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-medium text-gray-900">{{ getFeeDescription(rule) }}</div>
                                                <div v-if="rule.fee_type === 'percentage' && (rule.min_fee > 0 || rule.max_fee > 0)" class="text-xs text-gray-500 mt-1">
                                                    <span v-if="rule.min_fee > 0">最低 ¥{{ formatNumber(rule.min_fee) }}</span>
                                                    <span v-if="rule.min_fee > 0 && rule.max_fee > 0"> / </span>
                                                    <span v-if="rule.max_fee > 0">最高 ¥{{ formatNumber(rule.max_fee) }}</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm text-gray-600">
                                                    <div v-if="rule.daily_limit > 0">日限 ¥{{ formatNumber(rule.daily_limit) }}</div>
                                                    <div v-if="rule.monthly_limit > 0">月限 ¥{{ formatNumber(rule.monthly_limit) }}</div>
                                                    <div v-if="rule.daily_quota > 0">每日 {{ rule.daily_quota }} 笔</div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <label class="relative inline-flex items-center cursor-pointer">
                                                    <input type="checkbox" :checked="rule.is_enabled" @change="toggleStatus(rule)" :disabled="rule.toggling" class="sr-only peer" />
                                                    <div :class="['w-11 h-6 rounded-full peer peer-checked:bg-indigo-600 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-100 transition-colors', rule.toggling ? 'opacity-50' : '']">
                                                        <div :class="['absolute top-0.5 left-0.5 bg-white border border-gray-300 rounded-full h-5 w-5 transition-transform shadow-sm', rule.is_enabled ? 'translate-x-5 border-indigo-600' : '']"></div>
                                                    </div>
                                                </label>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-2">
                                                    <button @click="editRule(rule)" class="px-3 py-1.5 text-sm text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50 rounded transition-colors">编辑</button>
                                                    <button @click="deleteRule(rule)" class="px-3 py-1.5 text-sm text-red-600 hover:text-red-700 hover:bg-red-50 rounded transition-colors">删除</button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div v-if="showForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="showForm = false">
                            <div class="bg-white rounded-2xl p-6 max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-lg font-semibold text-gray-900">{{ editingRule ? '编辑规则' : '添加规则' }}</h3>
                                    <button @click="showForm = false" class="text-gray-400 hover:text-gray-600">✕</button>
                                </div>

                                <form @submit.prevent="submitForm" class="space-y-4">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">规则名称 <span class="text-red-500">*</span></label>
                                            <input v-model="form.name" type="text" placeholder="例如：普通用户提现规则" class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">用户等级 <span class="text-red-500">*</span></label>
                                            <select v-model="form.user_level" class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                                                <option v-for="level in userLevels" :key="level.value" :value="level.value">{{ level.label }}</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">描述</label>
                                        <textarea v-model="form.description" rows="2" placeholder="规则适用场景说明" class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">提现方式 <span class="text-red-500">*</span></label>
                                            <select v-model="form.method" class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                                                <option v-for="method in methods" :key="method.id" :value="method.code">{{ method.name }}</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">手续费类型 <span class="text-red-500">*</span></label>
                                            <select v-model="form.fee_type" class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                                                <option v-for="ft in feeTypes" :key="ft.value" :value="ft.value">{{ ft.label }}</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div v-if="form.fee_type !== 'free'" class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ form.fee_type === 'percentage' ? '费率 (%)' : '固定金额 (¥)' }}</label>
                                            <input v-model.number="form.fee_value" type="number" step="0.01" min="0" class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                                        </div>
                                        <div v-if="form.fee_type === 'percentage'">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">最低手续费 (¥)</label>
                                            <input v-model.number="form.min_fee" type="number" step="0.01" min="0" class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                                        </div>
                                    </div>

                                    <div v-if="form.fee_type === 'percentage'" class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">最高手续费 (¥)</label>
                                            <input v-model.number="form.max_fee" type="number" step="0.01" min="0" placeholder="0表示不限制" class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">最低提现金额 (¥)</label>
                                            <input v-model.number="form.min_amount" type="number" step="0.01" min="0" class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">最高提现金额 (¥)</label>
                                            <input v-model.number="form.max_amount" type="number" step="0.01" min="0" placeholder="0表示不限制" class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">单日限额 (¥)</label>
                                            <input v-model.number="form.daily_limit" type="number" step="0.01" min="0" placeholder="0表示不限制" class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">单月限额 (¥)</label>
                                            <input v-model.number="form.monthly_limit" type="number" step="0.01" min="0" placeholder="0表示不限制" class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">每日笔数</label>
                                            <input v-model.number="form.daily_quota" type="number" min="0" placeholder="0表示不限制" class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">生效开始时间</label>
                                            <input v-model="form.start_time" type="datetime-local" class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">生效结束时间</label>
                                            <input v-model="form.end_time" type="datetime-local" class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <input v-model="form.is_enabled" type="checkbox" id="is_enabled" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                        <label for="is_enabled" class="text-sm text-gray-700">启用该规则</label>
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
                el: '#withdraw-rules-app',
                components: {
                    'withdraw-rules': WithdrawRules
                }
            });
        }
    });
</script>
@endpush
