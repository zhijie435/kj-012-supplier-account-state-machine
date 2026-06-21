<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">余额提现</h1>
                <p class="mt-1 text-sm text-gray-500">申请将账户余额提现到指定账户</p>
            </div>
        </div>

        <div v-if="loading" class="flex justify-center py-12">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-indigo-600 border-t-transparent"></div>
        </div>

        <template v-else-if="!rules?.enabled">
            <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-8 text-center">
                <svg class="w-16 h-16 mx-auto text-yellow-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <h3 class="text-lg font-semibold text-yellow-800">提现功能暂未开放</h3>
                <p class="text-sm text-yellow-600 mt-1">请联系平台管理员或稍后再试</p>
            </div>
        </template>

        <template v-else>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">账户信息</h3>
                        <div class="space-y-4">
                            <div class="text-center py-4 bg-gradient-to-r from-indigo-50 to-purple-50 rounded-xl">
                                <div class="text-sm text-gray-600 mb-1">可提现余额</div>
                                <div class="text-4xl font-bold text-indigo-600">¥{{ formatNumber(rules?.max_withdrawable || 0) }}</div>
                                <div v-if="rules?.min_balance_keep > 0" class="text-xs text-gray-500 mt-1">
                                    （已扣除最低保留余额 ¥{{ formatNumber(rules.min_balance_keep) }}）
                                </div>
                            </div>

                            <div class="border-t border-gray-100 pt-4 space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">当前余额</span>
                                    <span class="font-medium text-gray-900">¥{{ formatNumber(rules?.available_balance || 0) }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">单笔最低</span>
                                    <span class="font-medium text-gray-900">¥{{ formatNumber(rules?.min_amount || 0) }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">单笔最高</span>
                                    <span class="font-medium text-gray-900">¥{{ formatNumber(rules?.max_amount || 0) }}</span>
                                </div>
                                <div v-if="rules?.daily_limit > 0" class="flex justify-between text-sm">
                                    <span class="text-gray-500">每日限额</span>
                                    <span class="font-medium text-gray-900">¥{{ formatNumber(rules.daily_limit) }}</span>
                                </div>
                                <div v-if="rules?.monthly_limit > 0" class="flex justify-between text-sm">
                                    <span class="text-gray-500">每月限额</span>
                                    <span class="font-medium text-gray-900">¥{{ formatNumber(rules.monthly_limit) }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">手续费率</span>
                                    <span class="font-medium text-gray-900">{{ rules?.fee_rate || 0 }}%</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">处理时间</span>
                                    <span class="font-medium text-gray-900">{{ rules?.processing_days || 0 }} 个工作日</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">快捷提现金额</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <button
                                v-for="amount in quickAmounts"
                                :key="amount"
                                type="button"
                                @click="form.amount = amount"
                                :disabled="amount > (rules?.max_withdrawable || 0)"
                                :class="[
                                    'py-3 px-4 rounded-lg border-2 font-medium transition-all text-sm',
                                    form.amount === amount
                                        ? 'border-indigo-600 bg-indigo-50 text-indigo-700'
                                        : 'border-gray-200 hover:border-gray-300 text-gray-700 disabled:opacity-40 disabled:cursor-not-allowed'
                                ]"
                            >
                                ¥{{ formatNumber(amount) }}
                            </button>
                        </div>
                        <button
                            type="button"
                            @click="form.amount = rules?.max_withdrawable || 0"
                            :disabled="(rules?.max_withdrawable || 0) <= 0"
                            class="w-full mt-3 py-3 px-4 rounded-lg border-2 border-dashed border-gray-300 text-sm text-gray-600 hover:border-indigo-400 hover:text-indigo-600 transition-all disabled:opacity-40 disabled:cursor-not-allowed"
                        >
                            全部提现（¥{{ formatNumber(rules?.max_withdrawable || 0) }}）
                        </button>
                    </div>
                </div>

                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-6">提现信息</h3>

                        <form @submit.prevent="handleWithdraw" class="space-y-6">
                            <div v-if="isPlatform" class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">
                                    选择分销商 <span class="text-red-500">*</span>
                                </label>
                                <select
                                    v-model="form.distributor_id"
                                    class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                    required
                                >
                                    <option value="">请选择分销商</option>
                                    <option v-for="d in distributors" :key="d.id" :value="d.id">
                                        {{ d.name }} (当前余额: ¥{{ formatNumber(d.balance) }})
                                    </option>
                                </select>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">
                                    提现金额 <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-lg">¥</span>
                                    <input
                                        v-model.number="form.amount"
                                        type="number"
                                        step="0.01"
                                        :min="(rules?.min_amount ?? defaults.min_amount ?? 0) || 0.01"
                                        :max="rules?.max_withdrawable ?? rules?.max_amount ?? defaults.max_amount ?? null"
                                        placeholder="请输入提现金额"
                                        class="w-full rounded-lg border-gray-300 border pl-10 pr-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-lg"
                                        required
                                    />
                                </div>
                                <div v-if="form.amount > 0" class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500">预计手续费</span>
                                    <span class="font-medium text-orange-600">¥{{ formatNumber(calculateFee(form.amount)) }}</span>
                                </div>
                                <div v-if="form.amount > 0" class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500">实际到账金额</span>
                                    <span class="font-medium text-green-600 text-lg">¥{{ formatNumber(Math.max(0, form.amount - calculateFee(form.amount))) }}</span>
                                </div>
                                <div v-if="amountError" class="text-sm text-red-600">{{ amountError }}</div>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">
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
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-700">开户银行 <span class="text-red-500">*</span></label>
                                        <input
                                            v-model="form.bank_name"
                                            type="text"
                                            placeholder="例如：中国工商银行"
                                            class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                            required
                                        />
                                    </div>
                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-700">银行账号 <span class="text-red-500">*</span></label>
                                        <input
                                            v-model="form.bank_account"
                                            type="text"
                                            placeholder="请输入银行卡号"
                                            class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                            required
                                        />
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">开户姓名 <span class="text-red-500">*</span></label>
                                    <input
                                        v-model="form.account_name"
                                        type="text"
                                        placeholder="请输入开户人姓名"
                                        class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        required
                                    />
                                </div>
                            </template>

                            <div v-else-if="form.method === 'alipay'" class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">支付宝账号 <span class="text-red-500">*</span></label>
                                <input
                                    v-model="form.alipay_account"
                                    type="text"
                                    placeholder="请输入支付宝账号"
                                    class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                    required
                                />
                            </div>

                            <div v-else-if="form.method === 'wechat'" class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">微信账号 <span class="text-red-500">*</span></label>
                                <input
                                    v-model="form.wechat_account"
                                    type="text"
                                    placeholder="请输入微信账号"
                                    class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                    required
                                />
                            </div>

                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">备注</label>
                                <textarea
                                    v-model="form.remark"
                                    rows="3"
                                    placeholder="提现备注信息（选填）"
                                    class="w-full rounded-lg border-gray-300 border px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 resize-none"
                                ></textarea>
                            </div>

                            <div v-if="rules?.require_audit" class="p-4 bg-amber-50 rounded-lg border border-amber-200">
                                <div class="flex items-start gap-3">
                                    <svg class="w-5 h-5 text-amber-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <div class="text-sm text-amber-800">
                                        <div class="font-medium">需要平台审核</div>
                                        <div class="mt-1">
                                            提现金额超过 ¥{{ formatNumber(rules?.audit_threshold || 0) }} 的申请需要平台审核后处理，
                                            预计 {{ rules?.processing_days || 0 }} 个工作日内到账。
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="submitError" class="p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
                                {{ submitError }}
                            </div>

                            <div class="flex items-center gap-4 pt-4 border-t border-gray-100">
                                <button
                                    type="submit"
                                    :disabled="submitting || !canSubmit"
                                    class="flex-1 py-3 px-6 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-100 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                                >
                                    <span v-if="submitting" class="inline-flex items-center">
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        处理中...
                                    </span>
                                    <span v-else>确认提现 ¥{{ formatNumber(form.amount || 0) }}</span>
                                </button>
                                <button
                                    type="button"
                                    @click="resetForm"
                                    class="py-3 px-6 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition-all"
                                >
                                    重置
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="bg-white rounded-xl border border-gray-200 p-6 mt-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">最近提现记录</h3>
                            <router-link
                                :to="{ name: 'payments.index', query: { type: 'withdraw' } }"
                                class="text-sm text-indigo-600 hover:text-indigo-700"
                            >
                                查看全部 →
                            </router-link>
                        </div>
                        <div v-if="recentWithdraws.length > 0" class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b border-gray-100">
                                        <th class="text-left py-3 px-4 text-xs font-medium text-gray-500 uppercase">提现单号</th>
                                        <th class="text-left py-3 px-4 text-xs font-medium text-gray-500 uppercase">金额</th>
                                        <th class="text-left py-3 px-4 text-xs font-medium text-gray-500 uppercase">方式</th>
                                        <th class="text-left py-3 px-4 text-xs font-medium text-gray-500 uppercase">时间</th>
                                        <th class="text-left py-3 px-4 text-xs font-medium text-gray-500 uppercase">状态</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in recentWithdraws" :key="item.id" class="border-b border-gray-50 hover:bg-gray-50">
                                        <td class="py-3 px-4 text-sm font-mono text-gray-900">{{ item.payment_no }}</td>
                                        <td class="py-3 px-4 text-sm font-medium text-red-600">-¥{{ formatNumber(item.amount) }}</td>
                                        <td class="py-3 px-4 text-sm text-gray-600">{{ getMethodLabel(item.method) }}</td>
                                        <td class="py-3 px-4 text-sm text-gray-500">{{ formatDate(item.created_at) }}</td>
                                        <td class="py-3 px-4">
                                            <span :class="getStatusClass(item.status)">{{ item.status_label || item.status }}</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="text-center py-12 text-gray-500">
                            <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p>暂无提现记录</p>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <div
            v-if="showSuccessModal"
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
            @click.self="showSuccessModal = false"
        >
            <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4 text-center">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-green-100 flex items-center justify-center">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">
                    {{ withdrawResult?.need_audit ? '申请已提交' : '提现成功' }}
                </h3>
                <p class="text-gray-500 mb-4">
                    {{ withdrawResult?.need_audit ? '您的提现申请已提交，等待平台审核' : '您的提现已成功处理' }}
                </p>
                <div class="bg-gray-50 rounded-lg p-4 mb-6 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">提现金额</span>
                        <span class="font-semibold text-red-600">-¥{{ formatNumber(withdrawResult?.data?.amount || 0) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">手续费</span>
                        <span class="font-semibold text-orange-600">-¥{{ formatNumber(withdrawResult?.fee_amount || 0) }}</span>
                    </div>
                    <div class="flex justify-between text-sm border-t border-gray-200 pt-2">
                        <span class="text-gray-500">实际到账</span>
                        <span class="font-semibold text-green-600">¥{{ formatNumber(withdrawResult?.actual_amount || 0) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">账户余额</span>
                        <span class="font-semibold text-blue-600">¥{{ formatNumber(withdrawResult?.new_balance || 0) }}</span>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button
                        @click="showSuccessModal = false; loadRules(); loadRecentWithdraws();"
                        class="flex-1 py-3 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition-all"
                    >
                        继续提现
                    </button>
                    <button
                        @click="goToPayments"
                        class="flex-1 py-3 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-all"
                    >
                        查看记录
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import paymentApi from '../../api/payment';
import axios from '../../api/axios';
import { useAuthStore } from '../../stores/auth';

const router = useRouter();
const auth = useAuthStore();

const loading = ref(false);
const submitting = ref(false);
const rules = ref(null);
const defaults = ref({});
const distributors = ref([]);
const recentWithdraws = ref([]);
const showSuccessModal = ref(false);
const withdrawResult = ref(null);
const submitError = ref('');
const amountError = ref('');

const isPlatform = computed(() => auth.user?.user_type === 'platform');

const quickAmounts = computed(() => {
    return rules.value?.quick_amounts ?? defaults.value.quick_amounts ?? [];
});

const allPaymentMethods = [
    { value: 'bank_transfer', label: '银行转账', icon: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>' },
    { value: 'alipay', label: '支付宝', icon: '<svg class="w-6 h-6 text-blue-500" viewBox="0 0 24 24" fill="currentColor"><path d="M20.422 20.787c-3.03-1.356-5.69-2.74-6.55-3.14-2.42 1.43-4.41 2.12-6.46 2.12-3.27 0-5.51-1.95-5.51-5.05 0-2.76 1.96-5.05 5.31-5.05 2.35 0 4.3.88 5.71 2.06.31.35.56.71.76 1.06l.06.09.36-1.4h-5.26v-.83h6.64l.28-.01.33-1.24h1.56l-.7 3.08c.65.96 1.17 1.98 1.55 2.99 1.15 3.07 1.54 5.04 1.96 5.31zm-2.33-1.6c-.34-.64-.9-2.32-1.92-4.94-1.01-.38-1.99-.7-2.8-.94-.27 1.01-.81 2.25-1.65 3.48 1.01.51 2.57 1.31 6.37 2.4zM7.31 13.04c-2.61 0-3.98 1.54-3.98 3.56 0 2.08 1.46 3.58 4.04 3.58 1.6 0 3.09-.5 4.47-1.5-.7-1.2-1.17-2.3-1.42-3.18-1 .25-1.95.38-3.11.38v-.07c.53 0 1.02-.04 1.48-.11-.56-.94-1.02-2.17-1.48-2.66z"/></svg>' },
    { value: 'wechat', label: '微信支付', icon: '<svg class="w-6 h-6 text-green-500" viewBox="0 0 24 24" fill="currentColor"><path d="M8.691 2.188C3.891 2.188 0 5.476 0 9.53c0 2.212 1.17 4.203 3.002 5.55a.59.59 0 0 1 .213.665l-.39 1.48c-.019.07-.048.141-.048.213 0 .163.13.295.29.295a.326.326 0 0 0 .167-.054l1.903-1.114a.864.864 0 0 1 .717-.098 10.16 10.16 0 0 0 2.837.403c.276 0 .543-.027.811-.05-.857-2.578.157-4.972 1.932-6.446 1.703-1.415 3.882-1.98 5.853-1.838-.576-3.583-4.196-6.348-8.596-6.348zM5.785 5.991c.642 0 1.162.529 1.162 1.18a1.17 1.17 0 0 1-1.162 1.178A1.17 1.17 0 0 1 4.623 7.17c0-.651.52-1.18 1.162-1.18zm5.813 0c.642 0 1.162.529 1.162 1.18a1.17 1.17 0 0 1-1.162 1.178 1.17 1.17 0 0 1-1.162-1.178c0-.651.52-1.18 1.162-1.18zm5.34 2.867c-1.797-.052-3.746.512-5.28 1.786-1.72 1.428-2.687 3.72-1.78 6.22.942 2.453 3.666 4.229 6.884 4.229.826 0 1.622-.12 2.361-.336a.722.722 0 0 1 .598.082l1.584.926a.272.272 0 0 0 .14.047c.134 0 .24-.111.24-.247 0-.06-.023-.12-.038-.177l-.327-1.233a.582.582 0 0 1-.023-.156.49.49 0 0 1 .201-.398C23.024 18.48 24 16.82 24 14.98c0-3.21-2.931-5.837-6.656-6.088V8.89l-.165-.016c-.082-.004-.164-.013-.246-.013zm-2.99 3.13c.535 0 .969.44.969.982a.976.976 0 0 1-.969.983.976.976 0 0 1-.969-.983c0-.542.434-.982.97-.982zm4.844 0c.535 0 .969.44.969.982a.976.976 0 0 1-.969.983.976.976 0 0 1-.969-.983c0-.542.434-.982.969-.982z"/></svg>' },
    { value: 'cash', label: '现金', icon: '<svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>' },
];

const availableMethods = computed(() => {
    const allowed = rules.value?.allow_methods ?? defaults.value.allow_methods ?? [];
    return allPaymentMethods.filter(m => allowed.includes(m.value));
});

const defaultForm = () => ({
    distributor_id: null,
    amount: null,
    method: availableMethods.value[0]?.value ?? defaults.value.allow_methods?.[0] ?? 'bank_transfer',
    bank_account: '',
    bank_name: '',
    account_name: '',
    alipay_account: '',
    wechat_account: '',
    remark: '',
});

const form = reactive(defaultForm());

const canSubmit = computed(() => {
    const minAmount = rules.value?.min_amount ?? defaults.value.min_amount ?? 0;
    const maxWithdrawable = rules.value?.max_withdrawable ?? rules.value?.max_amount ?? defaults.value.max_amount ?? 0;

    if (!form.amount || form.amount <= 0) return false;
    if (!form.method) return false;
    if (form.amount < minAmount) return false;
    if (maxWithdrawable > 0 && form.amount > maxWithdrawable) return false;
    if (form.method === 'bank_transfer' && (!form.bank_account || !form.bank_name || !form.account_name)) return false;
    if (form.method === 'alipay' && !form.alipay_account) return false;
    if (form.method === 'wechat' && !form.wechat_account) return false;
    if (isPlatform.value && !form.distributor_id) return false;
    return true;
});

const formatNumber = (num) => {
    return new Intl.NumberFormat('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(parseFloat(num || 0));
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')} ${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
};

const calculateFee = (amount) => {
    if (!amount) return 0;
    const rate = parseFloat(rules.value?.fee_rate ?? defaults.value.fee_rate ?? 0);
    const minFee = parseFloat(rules.value?.fee_min ?? defaults.value.fee_min ?? 0);
    const maxFee = parseFloat(rules.value?.fee_max ?? defaults.value.fee_max ?? 0);

    let fee = amount * (rate / 100);
    if (fee < minFee) fee = minFee;
    if (maxFee > 0 && fee > maxFee) fee = maxFee;

    return Math.round(fee * 100) / 100;
};

const getMethodLabel = (method) => {
    const m = allPaymentMethods.find(p => p.value === method);
    return m ? m.label : method;
};

const getStatusClass = (status) => {
    const classes = {
        completed: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800',
        pending: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800',
        failed: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800',
    };
    return classes[status] || classes.pending;
};

const loadRules = async () => {
    loading.value = true;
    try {
        const distributorId = isPlatform.value ? form.distributor_id : null;
        const { data } = await paymentApi.withdrawRules(distributorId);
        rules.value = data.data || data;
        defaults.value = data.defaults || {};
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

const loadDistributors = async () => {
    if (!isPlatform.value) return;
    try {
        const { data } = await axios.get('/distributors', { params: { per_page: 100 } });
        distributors.value = data.data || [];
    } catch (e) {
        console.error(e);
    }
};

const loadRecentWithdraws = async () => {
    try {
        const params = { type: 'withdraw', per_page: 5 };
        const { data } = await axios.get('/payments', { params });
        recentWithdraws.value = (data.data || []).filter(p => p.type === 'withdraw' || p.type === 'withdraw_fee');
    } catch (e) {
        console.error(e);
    }
};

const validateAmount = () => {
    amountError.value = '';
    if (!form.amount || form.amount <= 0) return;

    const minAmount = rules.value?.min_amount ?? defaults.value.min_amount ?? 0;
    const maxWithdrawable = rules.value?.max_withdrawable ?? rules.value?.max_amount ?? defaults.value.max_amount ?? 0;

    if (form.amount < minAmount) {
        amountError.value = `提现金额不能低于最低限额 ¥${formatNumber(minAmount)}`;
    } else if (maxWithdrawable > 0 && form.amount > maxWithdrawable) {
        amountError.value = `提现金额不能超过最高限额 ¥${formatNumber(maxWithdrawable)}`;
    }
};

const handleWithdraw = async () => {
    if (!canSubmit.value) return;

    submitError.value = '';
    submitting.value = true;

    try {
        const { data } = await paymentApi.withdraw({
            distributor_id: form.distributor_id || undefined,
            amount: form.amount,
            method: form.method,
            bank_account: form.bank_account || undefined,
            bank_name: form.bank_name || undefined,
            account_name: form.account_name || undefined,
            alipay_account: form.alipay_account || undefined,
            wechat_account: form.wechat_account || undefined,
            remark: form.remark || undefined,
        });
        withdrawResult.value = data;
        showSuccessModal.value = true;
        resetForm();
    } catch (e) {
        submitError.value = e.response?.data?.message || '提现申请失败，请稍后重试';
    } finally {
        submitting.value = false;
    }
};

const resetForm = () => {
    Object.assign(form, defaultForm());
    submitError.value = '';
    amountError.value = '';
};

const goToPayments = () => {
    showSuccessModal.value = false;
    router.push({ name: 'payments.index', query: { type: 'withdraw' } });
};

onMounted(() => {
    loadRules();
    loadDistributors();
    loadRecentWithdraws();
});
</script>
