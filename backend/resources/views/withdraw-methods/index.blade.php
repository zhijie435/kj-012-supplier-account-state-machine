@extends('layouts.app')

@section('title', '提现方式管理')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">提现方式管理</h1>
            <p class="mt-1 text-sm text-gray-500">管理系统支持的提现方式和手续费配置</p>
        </div>
    </div>

    <div id="withdraw-methods-app">
        <withdraw-methods></withdraw-methods>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Vue !== 'undefined') {
            const WithdrawMethods = {
                data() {
                    return {
                        loading: true,
                        methods: [],
                        showForm: false,
                        editingMethod: null,
                        form: {
                            name: '',
                            code: '',
                            description: '',
                            fee_type: 'percentage',
                            fee_value: 0,
                            min_fee: 0,
                            max_fee: 0,
                            min_amount: 0,
                            max_amount: 0,
                            daily_limit: 0,
                            monthly_limit: 0,
                            sort_order: 0,
                            is_enabled: true
                        },
                        submitting: false,
                        feeTypes: [
                            { value: 'percentage', label: '按比例' },
                            { value: 'fixed', label: '固定金额' },
                            { value: 'free', label: '免费' }
                        ]
                    };
                },
                mounted() {
                    this.loadMethods();
                },
                methods: {
                    async loadMethods() {
                        this.loading = true;
                        try {
                            const response = await axios.get('/api/withdraw-methods');
                            this.methods = (response.data.data || response.data).map(m => ({
                                ...m,
                                toggling: false
                            }));
                        } catch (e) {
                            console.error(e);
                        } finally {
                            this.loading = false;
                        }
                    },
                    showCreate() {
                        this.editingMethod = null;
                        this.form = {
                            name: '',
                            code: '',
                            description: '',
                            fee_type: 'percentage',
                            fee_value: 0,
                            min_fee: 0,
                            max_fee: 0,
                            min_amount: 0,
                            max_amount: 0,
                            daily_limit: 0,
                            monthly_limit: 0,
                            sort_order: this.methods.length,
                            is_enabled: true
                        };
                        this.showForm = true;
                    },
                    editMethod(method) {
                        this.editingMethod = method;
                        this.form = {
                            name: method.name,
                            code: method.code,
                            description: method.description || '',
                            fee_type: method.fee_type,
                            fee_value: method.fee_value || 0,
                            min_fee: method.min_fee || 0,
                            max_fee: method.max_fee || 0,
                            min_amount: method.min_amount || 0,
                            max_amount: method.max_amount || 0,
                            daily_limit: method.daily_limit || 0,
                            monthly_limit: method.monthly_limit || 0,
                            sort_order: method.sort_order || 0,
                            is_enabled: method.is_enabled
                        };
                        this.showForm = true;
                    },
                    async submitForm() {
                        this.submitting = true;
                        try {
                            if (this.editingMethod) {
                                await axios.put(`/api/withdraw-methods/${this.editingMethod.id}`, this.form);
                            } else {
                                await axios.post('/api/withdraw-methods', this.form);
                            }

                            this.showForm = false;
                            this.loadMethods();
                            alert('保存成功');
                        } catch (e) {
                            alert(e.response?.data?.message || '保存失败');
                        } finally {
                            this.submitting = false;
                        }
                    },
                    async toggleStatus(method) {
                        method.toggling = true;
                        try {
                            const newValue = !method.is_enabled;
                            await axios.patch(`/api/withdraw-methods/${method.id}/toggle`, { is_enabled: newValue });
                            method.is_enabled = newValue;
                        } catch (e) {
                            alert(e.response?.data?.message || '操作失败');
                        } finally {
                            method.toggling = false;
                        }
                    },
                    async moveUp(index) {
                        if (index === 0) return;
                        const prev = this.methods[index - 1];
                        const curr = this.methods[index];
                        try {
                            await axios.patch(`/api/withdraw-methods/${curr.id}/sort`, { sort_order: prev.sort_order });
                            await axios.patch(`/api/withdraw-methods/${prev.id}/sort`, { sort_order: curr.sort_order });
                            [this.methods[index - 1], this.methods[index]] = [this.methods[index], this.methods[index - 1]];
                        } catch (e) {
                            alert(e.response?.data?.message || '操作失败');
                        }
                    },
                    async moveDown(index) {
                        if (index === this.methods.length - 1) return;
                        const next = this.methods[index + 1];
                        const curr = this.methods[index];
                        try {
                            await axios.patch(`/api/withdraw-methods/${curr.id}/sort`, { sort_order: next.sort_order });
                            await axios.patch(`/api/withdraw-methods/${next.id}/sort`, { sort_order: curr.sort_order });
                            [this.methods[index + 1], this.methods[index]] = [this.methods[index], this.methods[index + 1]];
                        } catch (e) {
                            alert(e.response?.data?.message || '操作失败');
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
                    getFeeDescription(method) {
                        if (method.fee_type === 'free') return '免费';
                        if (method.fee_type === 'fixed') return `固定 ¥${this.formatNumber(method.fee_value)}`;
                        return `${method.fee_value}%`;
                    }
                },
                template: `
                    <div>
                        <div class="mb-6">
                            <button @click="showCreate" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium flex items-center gap-2">
                                <span>+</span>
                                添加提现方式
                            </button>
                        </div>

                        <div v-if="loading" class="flex justify-center py-12">
                            <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-indigo-600 border-t-transparent"></div>
                        </div>

                        <div v-else-if="methods.length === 0" class="text-center py-16 bg-white rounded-2xl border border-gray-200">
                            <p class="text-gray-500 mb-4">暂无提现方式</p>
                            <button @click="showCreate" class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium">
                                添加提现方式
                            </button>
                        </div>

                        <div v-else class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">排序</th>
                                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">名称</th>
                                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">代码</th>
                                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">手续费</th>
                                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">金额范围</th>
                                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">状态</th>
                                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">操作</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <tr v-for="(method, index) in methods" :key="method.id" class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-1">
                                                    <button @click="moveUp(index)" :disabled="index === 0" class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded disabled:opacity-30 disabled:cursor-not-allowed">
                                                        ↑
                                                    </button>
                                                    <button @click="moveDown(index)" :disabled="index === methods.length - 1" class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded disabled:opacity-30 disabled:cursor-not-allowed">
                                                        ↓
                                                    </button>
                                                    <span class="text-sm text-gray-500 w-6 text-center">{{ index + 1 }}</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="font-medium text-gray-900">{{ method.name }}</div>
                                                <div v-if="method.description" class="text-xs text-gray-500 mt-1">{{ method.description }}</div>
                                            </td>
                                            <td class="px-6 py-4"><span class="font-mono text-sm text-gray-600">{{ method.code }}</span></td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-medium text-gray-900">{{ getFeeDescription(method) }}</div>
                                                <div v-if="method.fee_type === 'percentage' && (method.min_fee > 0 || method.max_fee > 0)" class="text-xs text-gray-500 mt-1">
                                                    <span v-if="method.min_fee > 0">最低 ¥{{ formatNumber(method.min_fee) }}</span>
                                                    <span v-if="method.min_fee > 0 && method.max_fee > 0"> / </span>
                                                    <span v-if="method.max_fee > 0">最高 ¥{{ formatNumber(method.max_fee) }}</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm text-gray-900">¥{{ formatNumber(method.min_amount || 0) }} - ¥{{ method.max_amount ? formatNumber(method.max_amount) : '不限' }}</div>
                                                <div v-if="method.daily_limit > 0 || method.monthly_limit > 0" class="text-xs text-gray-500 mt-1">
                                                    <span v-if="method.daily_limit > 0">日限 ¥{{ formatNumber(method.daily_limit) }}</span>
                                                    <span v-if="method.daily_limit > 0 && method.monthly_limit > 0"> / </span>
                                                    <span v-if="method.monthly_limit > 0">月限 ¥{{ formatNumber(method.monthly_limit) }}</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <label class="relative inline-flex items-center cursor-pointer">
                                                    <input type="checkbox" :checked="method.is_enabled" @change="toggleStatus(method)" :disabled="method.toggling" class="sr-only peer" />
                                                    <div :class="['w-11 h-6 rounded-full peer peer-checked:bg-indigo-600 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-100 transition-colors', method.toggling ? 'opacity-50' : '']">
                                                        <div :class="['absolute top-0.5 left-0.5 bg-white border border-gray-300 rounded-full h-5 w-5 transition-transform shadow-sm', method.is_enabled ? 'translate-x-5 border-indigo-600' : '']"></div>
                                                    </div>
                                                </label>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-2">
                                                    <button @click="editMethod(method)" class="px-3 py-1.5 text-sm text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50 rounded transition-colors">
                                                        编辑
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div v-if="showForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="showForm = false">
                            <div class="bg-white rounded-2xl p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-lg font-semibold text-gray-900">{{ editingMethod ? '编辑提现方式' : '添加提现方式' }}</h3>
                                    <button @click="showForm = false" class="text-gray-400 hover:text-gray-600">✕</button>
                                </div>

                                <form @submit.prevent="submitForm" class="space-y-4">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">提现方式名称 <span class="text-red-500">*</span></label>
                                            <input v-model="form.name" type="text" placeholder="例如：银行转账" class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">代码 <span class="text-red-500">*</span></label>
                                            <input v-model="form.code" type="text" placeholder="例如：bank_transfer" class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-mono" required />
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">描述</label>
                                        <textarea v-model="form.description" rows="2" placeholder="描述该提现方式的适用场景" class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">手续费类型 <span class="text-red-500">*</span></label>
                                            <select v-model="form.fee_type" class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                                                <option v-for="ft in feeTypes" :key="ft.value" :value="ft.value">{{ ft.label }}</option>
                                            </select>
                                        </div>
                                        <div v-if="form.fee_type !== 'free'">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ form.fee_type === 'percentage' ? '费率 (%)' : '固定金额 (¥)' }}</label>
                                            <input v-model.number="form.fee_value" type="number" step="0.01" min="0" class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                                        </div>
                                    </div>

                                    <div v-if="form.fee_type === 'percentage'" class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">最低手续费 (¥)</label>
                                            <input v-model.number="form.min_fee" type="number" step="0.01" min="0" class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                                        </div>
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

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">单日限额 (¥)</label>
                                            <input v-model.number="form.daily_limit" type="number" step="0.01" min="0" placeholder="0表示不限制" class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">单月限额 (¥)</label>
                                            <input v-model.number="form.monthly_limit" type="number" step="0.01" min="0" placeholder="0表示不限制" class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">排序</label>
                                            <input v-model.number="form.sort_order" type="number" min="0" class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                                        </div>
                                        <div class="flex items-center">
                                            <div class="flex items-center gap-2">
                                                <input v-model="form.is_enabled" type="checkbox" id="is_enabled" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                                <label for="is_enabled" class="text-sm text-gray-700">启用该提现方式</label>
                                            </div>
                                        </div>
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
                el: '#withdraw-methods-app',
                components: {
                    'withdraw-methods': WithdrawMethods
                }
            });
        }
    });
</script>
@endpush
