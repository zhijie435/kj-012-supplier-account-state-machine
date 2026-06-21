<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="relative">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="搜索商品名称、SKU、批次号..."
                        class="w-80 pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"
                    />
                    <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <select
                    v-model="filterSupplier"
                    class="px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white"
                >
                    <option value="">全部供应商</option>
                    <option v-for="sup in suppliers" :key="sup.id" :value="sup.id">{{ sup.name }}</option>
                </select>
                <select
                    v-model="filterWarehouse"
                    class="px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white"
                >
                    <option value="">全部仓库</option>
                    <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
                </select>
                <select
                    v-model="filterStock"
                    class="px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white"
                >
                    <option value="">全部库存状态</option>
                    <option value="low">库存预警</option>
                    <option value="out">缺货</option>
                    <option value="normal">正常</option>
                </select>
            </div>
            <button
                v-if="auth.can('inventory.adjust')"
                @click="openAdjustModal"
                class="px-4 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors flex items-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                </svg>
                库存调整
            </button>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">商品</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">供应商</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">仓库</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">批次号</th>
                        <th class="text-right text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">库存数量</th>
                        <th class="text-right text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">可用库存</th>
                        <th class="text-right text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">预留库存</th>
                        <th class="text-right text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">单位成本</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">库位</th>
                        <th class="text-right text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-if="loading" class="text-center">
                        <td colspan="10" class="px-6 py-12 text-gray-500">加载中...</td>
                    </tr>
                    <tr v-else-if="!inventoryList.length" class="text-center">
                        <td colspan="10" class="px-6 py-12 text-gray-500">暂无数据</td>
                    </tr>
                    <tr v-for="item in inventoryList" :key="item.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ item.product?.name || '-' }}</div>
                            <div class="text-xs text-gray-500 font-mono">{{ item.product?.sku || '-' }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ item.supplier?.name || '-' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ item.warehouse?.name || '-' }}</td>
                        <td class="px-6 py-4 font-mono text-sm text-gray-600">{{ item.batch_no || '-' }}</td>
                        <td class="px-6 py-4 text-right">
                            <div :class="['text-sm font-medium', item.quantity <= 10 ? 'text-red-600' : 'text-gray-900']">{{ item.quantity }}</div>
                            <div v-if="item.quantity <= 10" class="text-xs text-red-500">库存预警</div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div :class="['text-sm font-medium', item.available_quantity <= 0 ? 'text-red-600' : item.available_quantity <= 10 ? 'text-amber-600' : 'text-green-600']">
                                {{ item.available_quantity }}
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right text-sm text-gray-600">{{ item.reserved_quantity || 0 }}</td>
                        <td class="px-6 py-4 text-right text-sm text-gray-700">¥{{ formatNumber(item.unit_cost) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ item.location || '-' }}</td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button
                                    v-if="auth.can('inventory.adjust')"
                                    @click="openAdjustModal(item)"
                                    class="text-sm text-indigo-600 hover:text-indigo-700"
                                >
                                    调整
                                </button>
                                <button
                                    v-if="auth.can('inventory.view')"
                                    @click="viewHistory(item)"
                                    class="text-sm text-gray-600 hover:text-gray-700"
                                >
                                    历史
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
                        @click="page > 1 && loadInventory(page - 1)"
                        :disabled="page <= 1"
                        class="px-3 py-1.5 rounded border border-gray-300 text-sm hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        上一页
                    </button>
                    <span class="text-sm text-gray-700">第 {{ page }} 页</span>
                    <button
                        @click="page * perPage < total && loadInventory(page + 1)"
                        :disabled="page * perPage >= total"
                        class="px-3 py-1.5 rounded border border-gray-300 text-sm hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        下一页
                    </button>
                </div>
            </div>
        </div>

        <div v-if="showAdjustModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl w-full max-w-md max-h-[90vh] overflow-auto">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">{{ isAdjustEdit ? '调整库存' : '新建库存' }}</h3>
                    <button @click="closeAdjustModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <form @submit.prevent="submitAdjust" class="p-6 space-y-4">
                    <div v-if="!isAdjustEdit">
                        <label class="block text-sm font-medium text-gray-700 mb-1">商品 <span class="text-red-500">*</span></label>
                        <select v-model="adjustForm.product_id" required class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white">
                            <option value="">请选择商品</option>
                            <option v-for="prod in products" :key="prod.id" :value="prod.id">{{ prod.name }} ({{ prod.sku }})</option>
                        </select>
                    </div>
                    <div v-if="!isAdjustEdit">
                        <label class="block text-sm font-medium text-gray-700 mb-1">供应商 <span class="text-red-500">*</span></label>
                        <select v-model="adjustForm.supplier_id" required class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white">
                            <option value="">请选择供应商</option>
                            <option v-for="sup in suppliers" :key="sup.id" :value="sup.id">{{ sup.name }}</option>
                        </select>
                    </div>
                    <div v-if="!isAdjustEdit">
                        <label class="block text-sm font-medium text-gray-700 mb-1">仓库 <span class="text-red-500">*</span></label>
                        <select v-model="adjustForm.warehouse_id" required class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white">
                            <option value="">请选择仓库</option>
                            <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
                        </select>
                    </div>
                    <div v-if="isAdjustEdit">
                        <label class="block text-sm font-medium text-gray-700 mb-1">调整类型 <span class="text-red-500">*</span></label>
                        <select v-model="adjustForm.adjust_type" required class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white">
                            <option value="in">入库（增加）</option>
                            <option value="out">出库（减少）</option>
                            <option value="set">设置为</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ isAdjustEdit ? '调整数量' : '库存数量' }} <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model.number="adjustForm.quantity"
                            type="number"
                            :min="isAdjustEdit && adjustForm.adjust_type === 'out' ? 1 : 0"
                            required
                            class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"
                        />
                        <div v-if="isAdjustEdit" class="mt-1 text-xs text-gray-500">
                            当前库存: {{ currentInventory?.quantity || 0 }}
                            <span v-if="adjustForm.adjust_type === 'in'"> → 调整后: {{ (currentInventory?.quantity || 0) + (adjustForm.quantity || 0) }}</span>
                            <span v-else-if="adjustForm.adjust_type === 'out'"> → 调整后: {{ Math.max(0, (currentInventory?.quantity || 0) - (adjustForm.quantity || 0)) }}</span>
                            <span v-else> → 调整后: {{ adjustForm.quantity || 0 }}</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">单位成本</label>
                        <input v-model.number="adjustForm.unit_cost" type="number" step="0.01" min="0" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">批次号</label>
                        <input v-model="adjustForm.batch_no" type="text" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">库位</label>
                        <input v-model="adjustForm.location" type="text" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">备注</label>
                        <textarea v-model="adjustForm.remark" rows="2" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"></textarea>
                    </div>
                    <div v-if="submitError" class="p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
                        {{ submitError }}
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" @click="closeAdjustModal" class="px-4 py-2 rounded-lg border border-gray-300 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                            取消
                        </button>
                        <button type="submit" :disabled="submitting" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700 transition-colors disabled:opacity-50">
                            {{ submitting ? '保存中...' : '保存' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="showHistoryModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl w-full max-w-2xl max-h-[90vh] overflow-auto">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">库存变动历史</h3>
                    <button @click="closeHistoryModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-6">
                    <div class="text-sm text-gray-500 mb-4">
                        商品: {{ currentInventory?.product?.name }} | 当前库存: {{ currentInventory?.quantity }}
                    </div>
                    <div v-if="historyLoading" class="py-12 text-center text-gray-500">加载中...</div>
                    <div v-else-if="!historyList.length" class="py-12 text-center text-gray-500">暂无变动历史</div>
                    <div v-else class="space-y-3">
                        <div v-for="record in historyList" :key="record.id" class="flex items-start gap-4 p-3 bg-gray-50 rounded-lg">
                            <div :class="['w-2 h-2 rounded-full mt-2', record.change_type === 'in' ? 'bg-green-500' : 'bg-red-500']"></div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ record.change_type === 'in' ? '入库' : '出库' }}
                                        <span :class="['ml-2', record.change_type === 'in' ? 'text-green-600' : 'text-red-600']">
                                            {{ record.change_type === 'in' ? '+' : '-' }}{{ record.quantity }}
                                        </span>
                                    </div>
                                    <div class="text-xs text-gray-500">{{ formatDate(record.created_at) }}</div>
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    原因: {{ record.reason || '-' }}
                                    <span v-if="record.remark"> | 备注: {{ record.remark }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end mt-4">
                        <button type="button" @click="closeHistoryModal" class="px-4 py-2 rounded-lg border border-gray-300 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                            关闭
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch, reactive } from 'vue';
import { useAuthStore } from '../../stores/auth';
import api from '../../api/axios';

const auth = useAuthStore();

const inventoryList = ref([]);
const suppliers = ref([]);
const warehouses = ref([]);
const products = ref([]);
const loading = ref(false);
const search = ref('');
const filterSupplier = ref('');
const filterWarehouse = ref('');
const filterStock = ref('');
const page = ref(1);
const perPage = ref(15);
const total = ref(0);

const showAdjustModal = ref(false);
const isAdjustEdit = ref(false);
const currentInventory = ref(null);
const submitting = ref(false);
const submitError = ref('');

const showHistoryModal = ref(false);
const historyList = ref([]);
const historyLoading = ref(false);

const adjustForm = reactive({
    product_id: null,
    supplier_id: null,
    warehouse_id: null,
    quantity: 0,
    unit_cost: 0,
    batch_no: '',
    location: '',
    remark: '',
    adjust_type: 'in',
});

const formatNumber = (num) => {
    if (num === null || num === undefined) return '0.00';
    return Number(num).toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatDate = (d) => d ? new Date(d).toLocaleString('zh-CN') : '-';

const loadInventory = async (p = 1) => {
    loading.value = true;
    page.value = p;
    try {
        const params = { page: p, per_page: perPage.value, include: 'product,supplier,warehouse' };
        if (search.value) params.search = search.value;
        if (filterSupplier.value) params.supplier_id = filterSupplier.value;
        if (filterWarehouse.value) params.warehouse_id = filterWarehouse.value;
        if (filterStock.value) params.stock_status = filterStock.value;
        const { data } = await api.get('/inventory', { params });
        inventoryList.value = data.data;
        total.value = data.total;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

const loadSuppliers = async () => {
    try {
        const { data } = await api.get('/suppliers', { params: { per_page: 100, status: 'active' } });
        suppliers.value = data.data || [];
    } catch (e) {
        console.error(e);
    }
};

const loadWarehouses = async () => {
    try {
        const { data } = await api.get('/warehouses', { params: { per_page: 100 } });
        warehouses.value = data.data || [];
    } catch (e) {
        console.error(e);
    }
};

const loadProducts = async () => {
    try {
        const { data } = await api.get('/products', { params: { per_page: 200 } });
        products.value = data.data || [];
    } catch (e) {
        console.error(e);
    }
};

let debounceTimer = null;
watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => loadInventory(1), 300);
});
watch([filterSupplier, filterWarehouse, filterStock], () => loadInventory(1));

const openAdjustModal = (item = null) => {
    if (item) {
        isAdjustEdit.value = true;
        currentInventory.value = item;
        Object.assign(adjustForm, {
            product_id: item.product_id,
            supplier_id: item.supplier_id,
            warehouse_id: item.warehouse_id,
            quantity: 0,
            unit_cost: item.unit_cost || 0,
            batch_no: item.batch_no || '',
            location: item.location || '',
            remark: '',
            adjust_type: 'in',
        });
    } else {
        isAdjustEdit.value = false;
        currentInventory.value = null;
        Object.assign(adjustForm, {
            product_id: null,
            supplier_id: null,
            warehouse_id: null,
            quantity: 0,
            unit_cost: 0,
            batch_no: '',
            location: '',
            remark: '',
            adjust_type: 'in',
        });
        loadSuppliers();
        loadWarehouses();
        loadProducts();
    }
    submitError.value = '';
    showAdjustModal.value = true;
};

const closeAdjustModal = () => {
    showAdjustModal.value = false;
};

const submitAdjust = async () => {
    submitting.value = true;
    submitError.value = '';
    try {
        if (isAdjustEdit.value) {
            await api.post(`/inventory/${currentInventory.value.id}/adjust`, adjustForm);
        } else {
            await api.post('/inventory', adjustForm);
        }
        closeAdjustModal();
        loadInventory(page.value);
    } catch (e) {
        submitError.value = e.response?.data?.message || '保存失败';
    } finally {
        submitting.value = false;
    }
};

const viewHistory = async (item) => {
    currentInventory.value = item;
    historyLoading.value = true;
    showHistoryModal.value = true;
    try {
        const { data } = await api.get(`/inventory/${item.id}/history`);
        historyList.value = data.data || [];
    } catch (e) {
        console.error(e);
        historyList.value = [];
    } finally {
        historyLoading.value = false;
    }
};

const closeHistoryModal = () => {
    showHistoryModal.value = false;
    historyList.value = [];
};

onMounted(() => {
    loadInventory();
    loadSuppliers();
    loadWarehouses();
    loadProducts();
});
</script>
