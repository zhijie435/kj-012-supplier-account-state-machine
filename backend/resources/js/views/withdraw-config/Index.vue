<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">提现规则配置</h1>
                        <p class="mt-1 text-sm text-gray-500">管理系统提现功能的各项参数配置，所有提现申请将按此规则执行</p>
                    </div>
                </div>

                <div
                    class="rounded-xl border border-amber-200 bg-amber-50 p-4 flex items-start gap-3"
                >
                    <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="flex-1">
                        <div class="font-medium text-amber-800">配置会自动同步至所有提现规则</div>
                        <div class="text-sm text-amber-700 mt-1">
                            修改以下参数会同步更新全部提现规则（金额范围、手续费、审核设置、处理时效、允许的提现方式）。
                            如需针对特定用户等级设置差异化规则，请前往
                            <router-link :to="{ name: 'withdraw-rules.index' }" class="underline font-medium hover:text-amber-900">
                                提现规则管理
                            </router-link>
                            页面。
                        </div>
                    </div>
                </div>

                <div v-if="loading" class="flex justify-center py-12">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-indigo-600 border-t-transparent"></div>
        </div>

        <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="space-y-6">
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        基础设置
                    </h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between py-3 border-b border-gray-100">
                            <div>
                                <div class="font-medium text-gray-900">启用提现功能</div>
                                <div class="text-sm text-gray-500">关闭后所有用户将无法发起提现申请</div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input v-model="form.enabled" type="checkbox" class="sr-only peer"/>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">单笔最低提现金额（元）</label>
                                <input
                                    v-model.number="form.min_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="w-full px-3 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">单笔最高提现金额（元）</label>
                                <input
                                    v-model.number="form.max_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="w-full px-3 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">每日提现总额上限（元）</label>
                                <input
                                    v-model.number="form.daily_limit"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="w-full px-3 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"
                                />
                                <p class="text-xs text-gray-500 mt-1">0 表示不限制</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">每月提现总额上限（元）</label>
                                <input
                                    v-model.number="form.monthly_limit"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="w-full px-3 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"
                                />
                                <p class="text-xs text-gray-500 mt-1">0 表示不限制</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">账户最低保留余额（元）</label>
                            <input
                                v-model.number="form.min_balance_keep"
                                type="number"
                                step="0.01"
                                min="0"
                                class="w-full px-3 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"
                            />
                            <p class="text-xs text-gray-500 mt-1">提现后账户需保留的最低余额</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        手续费设置
                    </h3>
                    <div class="space-y-4">
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">手续费率（%）</label>
                                <input
                                    v-model.number="form.fee_rate"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    class="w-full px-3 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">最低手续费（元）</label>
                                <input
                                    v-model.number="form.fee_min"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="w-full px-3 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">最高手续费（元）</label>
                                <input
                                    v-model.number="form.fee_max"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="w-full px-3 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"
                                />
                                <p class="text-xs text-gray-500 mt-1">0 表示不限制</p>
                            </div>
                        </div>

                        <div class="p-4 bg-blue-50 rounded-lg border border-blue-100">
                            <div class="text-sm text-blue-800">
                                <div class="font-medium">手续费计算示例</div>
                                <div class="mt-2 space-y-1 text-blue-700">
                                    <div>• 提现 ¥100：手续费 ¥{{ formatNumber(calculateFee(100)) }}，实际到账 ¥{{ formatNumber(100 - calculateFee(100)) }}</div>
                                    <div>• 提现 ¥1000：手续费 ¥{{ formatNumber(calculateFee(1000)) }}，实际到账 ¥{{ formatNumber(1000 - calculateFee(1000)) }}</div>
                                    <div>• 提现 ¥10000：手续费 ¥{{ formatNumber(calculateFee(10000)) }}，实际到账 ¥{{ formatNumber(10000 - calculateFee(10000)) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        审核与处理
                    </h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between py-3 border-b border-gray-100">
                            <div>
                                <div class="font-medium text-gray-900">提现需要审核</div>
                                <div class="text-sm text-gray-500">开启后所有提现申请需平台审核后处理</div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input v-model="form.require_audit" type="checkbox" class="sr-only peer"/>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">人工审核阈值（元）</label>
                            <input
                                v-model.number="form.audit_threshold"
                                type="number"
                                step="0.01"
                                min="0"
                                :disabled="!form.require_audit"
                                class="w-full px-3 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm disabled:bg-gray-100 disabled:cursor-not-allowed"
                            />
                            <p class="text-xs text-gray-500 mt-1">超过该金额需要人工审核，低于此金额自动通过</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">处理工作日</label>
                            <input
                                v-model.number="form.processing_days"
                                type="number"
                                step="1"
                                min="0"
                                class="w-full px-3 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"
                            />
                            <p class="text-xs text-gray-500 mt-1">提现申请后的预计处理工作日</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">资金冻结天数</label>
                            <input
                                v-model.number="form.freeze_days"
                                type="number"
                                step="1"
                                min="0"
                                class="w-full px-3 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"
                            />
                            <p class="text-xs text-gray-500 mt-1">提现后资金冻结的天数，0 表示不冻结</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        允许的提现方式
                    </h3>
                    <div class="space-y-3">
                        <label v-for="method in paymentMethods" :key="method.value" class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
                            <input
                                v-model="form.allow_methods"
                                type="checkbox"
                                :value="method.value"
                                class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                            />
                            <div class="flex-1">
                                <div class="font-medium text-gray-900">{{ method.label }}</div>
                                <div class="text-xs text-gray-500">{{ method.desc }}</div>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        快捷提现金额
                    </h3>
                    <p class="text-sm text-gray-500 mb-4">设置提现页面的快捷金额按钮，每行一个</p>
                    <div class="space-y-2">
                        <div v-for="(amount, idx) in form.quick_amounts" :key="idx" class="flex items-center gap-2">
                            <input
                                v-model.number="form.quick_amounts[idx]"
                                type="number"
                                step="0.01"
                                min="0"
                                class="flex-1 px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"
                                placeholder="金额（元）"
                            />
                            <button
                                v-if="form.quick_amounts.length > 1"
                                type="button"
                                @click="form.quick_amounts.splice(idx, 1)"
                                class="px-3 py-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="form.quick_amounts.push(0)"
                        class="mt-3 w-full py-2 px-4 border-2 border-dashed border-gray-300 rounded-lg text-sm text-gray-600 hover:border-indigo-400 hover:text-indigo-600 transition-colors"
                    >
                        + 添加快捷金额
                    </button>
                </div>
            </div>
        </div>

        <div v-if="!loading" class="flex justify-end gap-3">
            <button
                @click="resetForm"
                class="px-6 py-2.5 rounded-lg border border-gray-300 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
            >
                重置
            </button>
            <button
                @click="saveConfig"
                :disabled="saving"
                class="px-6 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
            >
                <svg v-if="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ saving ? '保存中...' : '保存配置' }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref, onMounted, computed } from 'vue';
import configApi from '../../api/config';

const loading = ref(false);
const saving = ref(false);
const defaults = ref({});

const allPaymentMethods = [
    { value: 'bank_transfer', label: '银行转账', desc: '提现到银行卡账户' },
    { value: 'alipay', label: '支付宝', desc: '提现到支付宝账户' },
    { value: 'wechat', label: '微信支付', desc: '提现到微信账户' },
    { value: 'cash', label: '现金', desc: '线下现金提现' },
];

const paymentMethods = computed(() => {
    return allPaymentMethods;
});

const form = reactive({});

const formatNumber = (num) => {
    return new Intl.NumberFormat('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(parseFloat(num || 0));
};

const calculateFee = (amount) => {
    const rate = parseFloat(form.fee_rate ?? defaults.value.fee_rate ?? 0);
    const minFee = parseFloat(form.fee_min ?? defaults.value.fee_min ?? 0);
    const maxFee = parseFloat(form.fee_max ?? defaults.value.fee_max ?? 0);

    let fee = amount * (rate / 100);
    if (fee < minFee) fee = minFee;
    if (maxFee > 0 && fee > maxFee) fee = maxFee;

    return Math.round(fee * 100) / 100;
};

const applyDefaults = () => {
    Object.keys(defaults.value).forEach(key => {
        form[key] = defaults.value[key];
    });
};

const loadConfig = async () => {
    loading.value = true;
    try {
        const { data } = await configApi.getWithdrawConfig();
        const configData = data.data || {};
        defaults.value = data.defaults || {};

        applyDefaults();

        Object.keys(configData).forEach(key => {
            if (configData[key] !== undefined && configData[key] !== null) {
                form[key] = configData[key];
            }
        });
    } catch (e) {
        console.error(e);
        alert('加载配置失败');
    } finally {
        loading.value = false;
    }
};

const resetForm = () => {
    applyDefaults();
};

const saveConfig = async () => {
    saving.value = true;
    try {
        await configApi.updateWithdrawConfig({ ...form });
        alert('配置保存成功');
    } catch (e) {
        alert(e.response?.data?.message || '保存失败');
    } finally {
        saving.value = false;
    }
};

onMounted(() => {
    loadConfig();
});
</script>
