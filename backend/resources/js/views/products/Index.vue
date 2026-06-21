<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="relative">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="搜索产品名称、SKU、条码..."
                        class="w-80 pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"
                    />
                    <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <select
                    v-model="filterCategory"
                    class="px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white"
                >
                    <option value="">全部分类</option>
                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                </select>
                <select
                    v-model="filterSupplier"
                    class="px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white"
                >
                    <option value="">全部供应商</option>
                    <option v-for="sup in suppliers" :key="sup.id" :value="sup.id">{{ sup.name }}</option>
                </select>
                <select
                    v-model="filterStatus"
                    class="px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white"
                >
                    <option value="">全部状态</option>
                    <option value="draft">草稿</option>
                    <option value="on_sale">在售</option>
                    <option value="off_sale">下架</option>
                    <option value="discontinued">停产</option>
                </select>
                <select
                    v-model="filterStock"
                    class="px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white"
                >
                    <option value="">全部库存</option>
                    <option value="low">库存预警</option>
                    <option value="out">已售罄</option>
                    <option value="normal">库存正常</option>
                </select>
            </div>
            <button
                v-if="auth.can('product.create')"
                @click="openCreateModal"
                class="px-4 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors flex items-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                新建产品
            </button>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">产品</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">SKU</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">分类</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">供应商</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">价格</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">库存</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">状态</th>
                        <th class="text-right text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-if="loading" class="text-center">
                        <td colspan="8" class="px-6 py-12 text-gray-500">加载中...</td>
                    </tr>
                    <tr v-else-if="!products.length" class="text-center">
                        <td colspan="8" class="px-6 py-12 text-gray-500">暂无数据</td>
                    </tr>
                    <tr v-for="product in products" :key="product.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-lg bg-amber-50 flex items-center justify-center text-amber-600 font-medium text-sm border border-amber-100">
                                    {{ product.name.charAt(0) }}
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900 text-sm">{{ product.name }}</div>
                                    <div class="text-sm text-gray-500">{{ product.specification || '-' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-mono text-sm text-gray-900">{{ product.sku }}</div>
                            <div class="text-sm text-gray-500">{{ product.barcode || '-' }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            {{ product.category?.name || '-' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            {{ product.supplier?.name || '-' }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">¥{{ formatNumber(product.wholesale_price) }}</div>
                            <div class="text-xs text-gray-500">成本: ¥{{ formatNumber(product.cost_price) }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <span :class="stockClass(product)">{{ product.stock_quantity || 0 }}</span>
                                <span class="text-sm text-gray-500">{{ product.unit || '件' }}</span>
                            </div>
                            <div v-if="product.is_low_stock" class="text-xs text-amber-600 mt-0.5">
                                安全库存: {{ product.safety_stock }}
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span :class="statusBadge(product.status)">
                                {{ statusLabel(product.status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button
                                    v-if="auth.can('product.edit')"
                                    @click="openEditModal(product)"
                                    class="text-sm text-indigo-600 hover:text-indigo-700"
                                >
                                    编辑
                                </button>
                                <button
                                    v-if="auth.can('product.edit')"
                                    @click="toggleStatus(product)"
                                    class="text-sm text-amber-600 hover:text-amber-700"
                                >
                                    {{ product.status === 'on_sale' ? '下架' : '上架' }}
                                </button>
                                <button
                                    v-if="auth.can('product.delete')"
                                    @click="deleteProduct(product)"
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
                        @click="page > 1 && loadProducts(page - 1)"
                        :disabled="page <= 1"
                        class="px-3 py-1.5 rounded border border-gray-300 text-sm hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        上一页
                    </button>
                    <span class="text-sm text-gray-700">第 {{ page }} 页</span>
                    <button
                        @click="page * perPage < total && loadProducts(page + 1)"
                        :disabled="page * perPage >= total"
                        class="px-3 py-1.5 rounded border border-gray-300 text-sm hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        下一页
                    </button>
                </div>
            </div>
        </div>

        <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl w-full max-w-3xl max-h-[90vh] overflow-auto">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">{{ isEdit ? '编辑产品' : '新建产品' }}</h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <form @submit.prevent="submitForm" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">产品名称 <span class="text-red-500">*</span></label>
                            <input v-model="form.name" type="text" required class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">SKU <span class="text-red-500">*</span></label>
                            <input v-model="form.sku" type="text" required class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">条码</label>
                            <input v-model="form.barcode" type="text" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">规格</label>
                            <input v-model="form.specification" type="text" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">分类</label>
                            <select v-model="form.category_id" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white">
                                <option value="">请选择</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">供应商 <span class="text-red-500">*</span></label>
                            <select v-model="form.supplier_id" required class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white">
                                <option value="">请选择</option>
                                <option v-for="sup in suppliers" :key="sup.id" :value="sup.id">{{ sup.name }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">计量单位</label>
                            <input v-model="form.unit" type="text" placeholder="如: 件、箱、个" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">品牌</label>
                            <input v-model="form.brand" type="text" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                    </div>
                    <div class="grid grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">成本价</label>
                            <input v-model.number="form.cost_price" type="number" step="0.01" min="0" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">批发价</label>
                            <input v-model.number="form.wholesale_price" type="number" step="0.01" min="0" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">零售价</label>
                            <input v-model.number="form.retail_price" type="number" step="0.01" min="0" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">代理价</label>
                            <input v-model.number="form.agent_price" type="number" step="0.01" min="0" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">库存数量</label>
                            <input v-model.number="form.stock_quantity" type="number" min="0" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">安全库存</label>
                            <input v-model.number="form.safety_stock" type="number" min="0" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">HS编码</label>
                            <input v-model="form.hs_code" type="text" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">原产国</label>
                            <input v-model="form.country_of_origin" type="text" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">重量 (kg)</label>
                            <input v-model.number="form.weight" type="number" step="0.001" min="0" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">体积 (m³)</label>
                            <input v-model.number="form.volume" type="number" step="0.001" min="0" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">产品描述</label>
                        <textarea v-model="form.description" rows="3" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div v-if="!isEdit">
                            <label class="block text-sm font-medium text-gray-700 mb-1">状态</label>
                            <select v-model="form.status" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white">
                                <option value="draft">草稿</option>
                                <option value="on_sale">在售</option>
                                <option value="off_sale">下架</option>
                            </select>
                        </div>
                        <div>
                            <label class="flex items-center gap-2 pt-6">
                                <input v-model="form.is_cross_border" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"/>
                                <span class="text-sm text-gray-700">跨境产品</span>
                            </label>
                        </div>
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
    </div>
</template>

<script setup>
import { reactive, ref, onMounted, watch } from 'vue';
import { useAuthStore } from '../../stores/auth';
import api from '../../api/axios';

const auth = useAuthStore();

const products = ref([]);
const categories = ref([]);
const suppliers = ref([]);
const loading = ref(false);
const search = ref('');
const filterCategory = ref('');
const filterSupplier = ref('');
const filterStatus = ref('');
const filterStock = ref('');
const page = ref(1);
const perPage = ref(15);
const total = ref(0);

const showModal = ref(false);
const isEdit = ref(false);
const editingId = ref(null);
const submitting = ref(false);
const submitError = ref('');

const defaultForm = () => ({
    name: '',
    sku: '',
    barcode: '',
    category_id: null,
    supplier_id: null,
    specification: '',
    unit: '件',
    cost_price: 0,
    wholesale_price: 0,
    retail_price: 0,
    agent_price: 0,
    stock_quantity: 0,
    safety_stock: 10,
    description: '',
    status: 'draft',
    hs_code: '',
    country_of_origin: '',
    weight: 0,
    volume: 0,
    is_cross_border: false,
    brand: '',
});

const form = reactive(defaultForm());

const statusLabel = (s) => ({ draft: '草稿', on_sale: '在售', off_sale: '下架', discontinued: '停产' }[s] || s);
const statusBadge = (s) => {
    const map = {
        draft: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700',
        on_sale: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700',
        off_sale: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700',
        discontinued: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700',
    };
    return map[s] || '';
};
const stockClass = (p) => {
    if (!p.stock_quantity || p.stock_quantity <= 0) return 'text-sm font-medium text-red-600';
    if (p.stock_quantity <= p.safety_stock) return 'text-sm font-medium text-amber-600';
    return 'text-sm font-medium text-gray-900';
};
const formatNumber = (num) => {
    if (num === null || num === undefined) return '0.00';
    return Number(num).toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const loadProducts = async (p = 1) => {
    loading.value = true;
    page.value = p;
    try {
        const params = { page: p, per_page: perPage.value };
        if (search.value) params.search = search.value;
        if (filterCategory.value) params.category_id = filterCategory.value;
        if (filterSupplier.value) params.supplier_id = filterSupplier.value;
        if (filterStatus.value) params.status = filterStatus.value;
        if (filterStock.value) params.stock_filter = filterStock.value;
        const { data } = await api.get('/products', { params });
        products.value = data.data;
        total.value = data.total;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

const loadCategories = async () => {
    try {
        const { data } = await api.get('/categories', { params: { per_page: 100 } });
        categories.value = data.data || [];
    } catch (e) {
        console.error(e);
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

let debounceTimer = null;
watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => loadProducts(1), 300);
});
watch([filterCategory, filterSupplier, filterStatus, filterStock], () => loadProducts(1));

const openCreateModal = () => {
    Object.assign(form, defaultForm());
    isEdit.value = false;
    editingId.value = null;
    submitError.value = '';
    showModal.value = true;
};

const openEditModal = (product) => {
    Object.assign(form, {
        name: product.name,
        sku: product.sku,
        barcode: product.barcode || '',
        category_id: product.category_id,
        supplier_id: product.supplier_id,
        specification: product.specification || '',
        unit: product.unit || '件',
        cost_price: product.cost_price || 0,
        wholesale_price: product.wholesale_price || 0,
        retail_price: product.retail_price || 0,
        agent_price: product.agent_price || 0,
        stock_quantity: product.stock_quantity || 0,
        safety_stock: product.safety_stock || 10,
        description: product.description || '',
        status: product.status,
        hs_code: product.hs_code || '',
        country_of_origin: product.country_of_origin || '',
        weight: product.weight || 0,
        volume: product.volume || 0,
        is_cross_border: product.is_cross_border || false,
        brand: product.brand || '',
    });
    isEdit.value = true;
    editingId.value = product.id;
    submitError.value = '';
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
            await api.put(`/products/${editingId.value}`, form);
        } else {
            await api.post('/products', form);
        }
        closeModal();
        loadProducts(page.value);
    } catch (e) {
        submitError.value = e.response?.data?.message || '保存失败';
    } finally {
        submitting.value = false;
    }
};

const toggleStatus = async (product) => {
    const newStatus = product.status === 'on_sale' ? 'off_sale' : 'on_sale';
    try {
        await api.put(`/products/${product.id}`, { status: newStatus });
        loadProducts(page.value);
    } catch (e) {
        alert(e.response?.data?.message || '操作失败');
    }
};

const deleteProduct = async (product) => {
    if (!confirm(`确定删除产品「${product.name}」吗？`)) return;
    try {
        await api.delete(`/products/${product.id}`);
        loadProducts(page.value);
    } catch (e) {
        alert(e.response?.data?.message || '删除失败');
    }
};

onMounted(() => {
    loadProducts();
    loadCategories();
    loadSuppliers();
});
</script>
