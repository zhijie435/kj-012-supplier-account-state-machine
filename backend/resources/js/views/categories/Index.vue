<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="relative">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="搜索分类名称、编码..."
                        class="w-80 pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"
                    />
                    <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <select
                    v-model="filterActive"
                    class="px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white"
                >
                    <option value="">全部状态</option>
                    <option :value="true">启用</option>
                    <option :value="false">禁用</option>
                </select>
                <div class="flex items-center gap-2">
                    <button
                        @click="viewMode = 'list'"
                        :class="['px-3 py-2 rounded-lg border text-sm font-medium transition-colors', viewMode === 'list' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50']"
                    >
                        列表视图
                    </button>
                    <button
                        @click="viewMode = 'tree'"
                        :class="['px-3 py-2 rounded-lg border text-sm font-medium transition-colors', viewMode === 'tree' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50']"
                    >
                        树形视图
                    </button>
                </div>
            </div>
            <button
                v-if="auth.can('category.create')"
                @click="openCreateModal"
                class="px-4 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors flex items-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                新建分类
            </button>
        </div>

        <div v-if="viewMode === 'list'" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">分类名称</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">编码</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">父级分类</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">排序</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">商品数量</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">状态</th>
                        <th class="text-right text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-if="loading" class="text-center">
                        <td colspan="7" class="px-6 py-12 text-gray-500">加载中...</td>
                    </tr>
                    <tr v-else-if="!categories.length" class="text-center">
                        <td colspan="7" class="px-6 py-12 text-gray-500">暂无数据</td>
                    </tr>
                    <tr v-for="category in categories" :key="category.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ category.name }}</div>
                        </td>
                        <td class="px-6 py-4 font-mono text-sm text-gray-600">{{ category.code }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ category.parent?.name || '-' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ category.sort }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ category.products_count || 0 }}</td>
                        <td class="px-6 py-4">
                            <span :class="category.is_active ? 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700' : 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700'">
                                {{ category.is_active ? '启用' : '禁用' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button
                                    v-if="auth.can('category.edit')"
                                    @click="openEditModal(category)"
                                    class="text-sm text-indigo-600 hover:text-indigo-700"
                                >
                                    编辑
                                </button>
                                <button
                                    v-if="auth.can('category.delete')"
                                    @click="deleteCategory(category)"
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
                        @click="page > 1 && loadCategories(page - 1)"
                        :disabled="page <= 1"
                        class="px-3 py-1.5 rounded border border-gray-300 text-sm hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        上一页
                    </button>
                    <span class="text-sm text-gray-700">第 {{ page }} 页</span>
                    <button
                        @click="page * perPage < total && loadCategories(page + 1)"
                        :disabled="page * perPage >= total"
                        class="px-3 py-1.5 rounded border border-gray-300 text-sm hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        下一页
                    </button>
                </div>
            </div>
        </div>

        <div v-if="viewMode === 'tree'" class="bg-white rounded-xl border border-gray-200 p-6">
            <div v-if="loading" class="py-12 text-center text-gray-500">加载中...</div>
            <div v-else-if="!treeCategories.length" class="py-12 text-center text-gray-500">暂无数据</div>
            <div v-else class="space-y-2">
                <CategoryTreeNode v-for="node in treeCategories" :key="node.id" :node="node" :level="0" @edit="openEditModal" @delete="deleteCategory" />
            </div>
        </div>

        <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl w-full max-w-md max-h-[90vh] overflow-auto">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">{{ isEdit ? '编辑分类' : '新建分类' }}</h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <form @submit.prevent="submitForm" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">分类名称 <span class="text-red-500">*</span></label>
                        <input v-model="form.name" type="text" required class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">分类编码 <span class="text-red-500">*</span></label>
                        <input v-model="form.code" type="text" required class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm" :disabled="isEdit"/>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">父级分类</label>
                        <select v-model="form.parent_id" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white">
                            <option :value="null">无（顶级分类）</option>
                            <option v-for="cat in allCategories" :key="cat.id" :value="cat.id" :disabled="isEdit && cat.id === editingId">
                                {{ cat.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">排序</label>
                        <input v-model.number="form.sort" type="number" min="0" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                    </div>
                    <div class="flex items-center gap-2">
                        <input v-model="form.is_active" type="checkbox" id="is_active" class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500"/>
                        <label for="is_active" class="text-sm font-medium text-gray-700">启用</label>
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
import { ref, onMounted, watch, defineComponent, h } from 'vue';
import { useAuthStore } from '../../stores/auth';
import api from '../../api/axios';

const auth = useAuthStore();

const categories = ref([]);
const allCategories = ref([]);
const treeCategories = ref([]);
const loading = ref(false);
const search = ref('');
const filterActive = ref('');
const viewMode = ref('list');
const page = ref(1);
const perPage = ref(15);
const total = ref(0);

const showModal = ref(false);
const isEdit = ref(false);
const editingId = ref(null);
const submitting = ref(false);
const submitError = ref('');

const form = ref({
    name: '',
    code: '',
    parent_id: null,
    sort: 0,
    is_active: true,
});

const CategoryTreeNode = defineComponent({
    name: 'CategoryTreeNode',
    props: {
        node: { type: Object, required: true },
        level: { type: Number, default: 0 },
    },
    emits: ['edit', 'delete'],
    setup(props, { emit }) {
        const expanded = ref(true);
        const hasChildren = () => props.node.children && props.node.children.length > 0;

        return () => h('div', { key: props.node.id }, [
            h('div', {
                class: 'flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-50 transition-colors',
                style: { paddingLeft: `${props.level * 24 + 12}px` }
            }, [
                hasChildren()
                    ? h('button', {
                        onClick: () => expanded.value = !expanded.value,
                        class: 'w-5 h-5 flex items-center justify-center text-gray-400 hover:text-gray-600'
                    }, [
                        h('svg', {
                            class: `w-4 h-4 transition-transform ${expanded.value ? 'rotate-90' : ''}`,
                            fill: 'none',
                            stroke: 'currentColor',
                            viewBox: '0 0 24 24'
                        }, [
                            h('path', {
                                'stroke-linecap': 'round',
                                'stroke-linejoin': 'round',
                                'stroke-width': '2',
                                d: 'M9 5l7 7-7 7'
                            })
                        ])
                    ])
                    : h('span', { class: 'w-5 h-5' }),
                h('svg', {
                    class: 'w-5 h-5 text-gray-400',
                    fill: 'none',
                    stroke: 'currentColor',
                    viewBox: '0 0 24 24'
                }, [
                    h('path', {
                        'stroke-linecap': 'round',
                        'stroke-linejoin': 'round',
                        'stroke-width': '2',
                        d: 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z'
                    })
                ]),
                h('span', { class: 'text-sm font-medium text-gray-900 flex-1' }, props.node.name),
                h('span', { class: 'font-mono text-xs text-gray-500' }, props.node.code),
                h('span', {
                    class: `inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${props.node.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'}`
                }, props.node.is_active ? '启用' : '禁用'),
                h('div', { class: 'flex items-center gap-2' }, [
                    h('button', {
                        onClick: () => emit('edit', props.node),
                        class: 'text-sm text-indigo-600 hover:text-indigo-700'
                    }, '编辑'),
                    h('button', {
                        onClick: () => emit('delete', props.node),
                        class: 'text-sm text-red-600 hover:text-red-700'
                    }, '删除'),
                ])
            ]),
            hasChildren() && expanded.value
                ? h('div', { class: 'ml-2' },
                    props.node.children.map(child =>
                        h(CategoryTreeNode, {
                            key: child.id,
                            node: child,
                            level: props.level + 1,
                            onEdit: (n) => emit('edit', n),
                            onDelete: (n) => emit('delete', n)
                        })
                    )
                )
                : null
        ]);
    }
});

const buildTree = (items) => {
    const map = new Map();
    const roots = [];
    items.forEach(item => {
        map.set(item.id, { ...item, children: [] });
    });
    items.forEach(item => {
        const node = map.get(item.id);
        if (item.parent_id && map.has(item.parent_id)) {
            map.get(item.parent_id).children.push(node);
        } else {
            roots.push(node);
        }
    });
    return roots;
};

const loadCategories = async (p = 1) => {
    loading.value = true;
    page.value = p;
    try {
        const params = { page: p, per_page: perPage.value, include: 'parent,children' };
        if (search.value) params.search = search.value;
        if (filterActive.value !== '') params.is_active = filterActive.value;
        const { data } = await api.get('/categories', { params });
        categories.value = data.data;
        total.value = data.total;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

const loadAllCategories = async () => {
    try {
        const { data } = await api.get('/categories', { params: { per_page: 500, include: 'children' } });
        allCategories.value = data.data || [];
        treeCategories.value = buildTree(data.data || []);
    } catch (e) {
        console.error(e);
    }
};

let debounceTimer = null;
watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => loadCategories(1), 300);
});
watch(filterActive, () => loadCategories(1));
watch(viewMode, (mode) => {
    if (mode === 'tree') {
        loadAllCategories();
    }
});

const openCreateModal = () => {
    form.value = { name: '', code: '', parent_id: null, sort: 0, is_active: true };
    isEdit.value = false;
    editingId.value = null;
    submitError.value = '';
    loadAllCategories();
    showModal.value = true;
};

const openEditModal = (category) => {
    form.value = {
        name: category.name,
        code: category.code,
        parent_id: category.parent_id,
        sort: category.sort || 0,
        is_active: category.is_active,
    };
    isEdit.value = true;
    editingId.value = category.id;
    submitError.value = '';
    loadAllCategories();
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
            await api.put(`/categories/${editingId.value}`, form.value);
        } else {
            await api.post('/categories', form.value);
        }
        closeModal();
        if (viewMode.value === 'list') {
            loadCategories(page.value);
        } else {
            loadAllCategories();
        }
    } catch (e) {
        submitError.value = e.response?.data?.message || '保存失败';
    } finally {
        submitting.value = false;
    }
};

const deleteCategory = async (category) => {
    if (!confirm(`确定删除分类「${category.name}」吗？`)) return;
    try {
        await api.delete(`/categories/${category.id}`);
        if (viewMode.value === 'list') {
            loadCategories(page.value);
        } else {
            loadAllCategories();
        }
    } catch (e) {
        alert(e.response?.data?.message || '删除失败');
    }
};

onMounted(() => {
    loadCategories();
});
</script>
