<template>
  <div class="product-cost-page">
    <el-card class="page-card" shadow="never">
      <div class="page-header">
        <h2 class="page-title">商品成本管理</h2>
      </div>

      <el-form :model="searchForm" inline class="search-form">
        <el-form-item label="商品">
          <el-select
            v-model="searchForm.product_id"
            placeholder="选择商品"
            clearable
            filterable
            remote
            reserve-keyword
            style="width: 260px"
            :remote-method="remoteSearchProducts"
            :loading="productsLoading"
            @change="handleProductChange"
          >
            <el-option
              v-for="product in productOptions"
              :key="product.id"
              :label="`${product.name}${product.sku ? ' (' + product.sku + ')' : ''}`"
              :value="product.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="成本类型">
          <el-select
            v-model="searchForm.cost_type"
            placeholder="全部类型"
            clearable
            style="width: 160px"
          >
            <el-option
              v-for="option in costTypeOptions"
              :key="option.value"
              :label="option.label"
              :value="option.value"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="启用状态">
          <el-select
            v-model="searchForm.is_active"
            placeholder="全部状态"
            clearable
            style="width: 140px"
          >
            <el-option label="已启用" :value="1" />
            <el-option label="已禁用" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" icon="el-icon-search" @click="handleSearch">查询</el-button>
          <el-button icon="el-icon-refresh" @click="handleReset">重置</el-button>
          <el-button type="success" icon="el-icon-edit" @click="handleBatchSet">批量设置成本</el-button>
        </el-form-item>
      </el-form>

      <div v-if="selectedProductSummary" class="summary-card">
        <el-row :gutter="20">
          <el-col :span="6">
            <div class="summary-item">
              <div class="summary-label">商品名称</div>
              <div class="summary-value product-name">{{ selectedProductSummary.name }}</div>
            </div>
          </el-col>
          <el-col :span="6">
            <div class="summary-item">
              <div class="summary-label">售价</div>
              <div class="summary-value price">¥{{ formatNumber(selectedProductSummary.price) }}</div>
            </div>
          </el-col>
          <el-col :span="6">
            <div class="summary-item">
              <div class="summary-label">单位总成本</div>
              <div class="summary-value cost">¥{{ formatNumber(selectedProductSummary.total_cost) }}</div>
            </div>
          </el-col>
          <el-col :span="6">
            <div class="summary-item">
              <div class="summary-label">毛利率</div>
              <div class="summary-value" :class="getGrossMarginClass(selectedProductSummary.gross_margin)">
                {{ formatPercent(selectedProductSummary.gross_margin) }}
              </div>
            </div>
          </el-col>
        </el-row>
      </div>

      <div class="table-actions">
        <el-button
          type="primary"
          icon="el-icon-plus"
          @click="handleCreate"
        >
          新增成本项
        </el-button>
      </div>

      <el-table
        v-loading="loading"
        :data="tableData"
        border
        stripe
        class="cost-table"
      >
        <el-table-column prop="id" label="ID" width="80" align="center" />
        <el-table-column label="商品" min-width="200">
          <template slot-scope="scope">
            <div class="product-cell">
              <span class="product-name">{{ scope.row.product?.name || '-' }}</span>
              <span v-if="scope.row.product?.sku" class="product-sku">SKU: {{ scope.row.product.sku }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="成本类型" width="120" align="center">
          <template slot-scope="scope">
            <el-tag :type="getCostTypeTagType(scope.row.cost_type)" size="small">
              {{ getCostTypeLabel(scope.row.cost_type) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="cost_name" label="成本项目" min-width="140" show-overflow-tooltip />
        <el-table-column label="单位成本" width="120" align="right">
          <template slot-scope="scope">
            ¥{{ formatNumber(scope.row.unit_cost) }}
          </template>
        </el-table-column>
        <el-table-column label="数量/基数" width="110" align="center">
          <template slot-scope="scope">
            {{ scope.row.quantity || 1 }}
          </template>
        </el-table-column>
        <el-table-column label="总成本" width="120" align="right">
          <template slot-scope="scope">
            <span class="total-cost">¥{{ formatNumber(scope.row.total_cost) }}</span>
          </template>
        </el-table-column>
        <el-table-column label="生效日期范围" width="200" align="center">
          <template slot-scope="scope">
            <div class="date-range">
              <span>{{ formatDate(scope.row.effective_date) }}</span>
              <span class="date-separator">~</span>
              <span>{{ scope.row.expiry_date ? formatDate(scope.row.expiry_date) : '永久' }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="100" align="center">
          <template slot-scope="scope">
            <el-switch
              v-model="scope.row.is_active"
              :active-value="1"
              :inactive-value="0"
              @change="(val) => handleToggleActive(scope.row, val)"
            />
          </template>
        </el-table-column>
        <el-table-column label="操作" width="140" fixed="right" align="center">
          <template slot-scope="scope">
            <el-button
              type="text"
              size="small"
              icon="el-icon-edit"
              @click="handleEdit(scope.row)"
            >
              编辑
            </el-button>
            <el-button
              type="text"
              size="small"
              icon="el-icon-delete"
              style="color: #f56c6c"
              @click="handleDelete(scope.row)"
            >
              删除
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <el-pagination
        class="pagination"
        background
        layout="total, sizes, prev, pager, next, jumper"
        :total="pagination.total"
        :page-size="pagination.pageSize"
        :page-sizes="[10, 20, 50, 100]"
        :current-page="pagination.currentPage"
        @size-change="handleSizeChange"
        @current-change="handlePageChange"
      />
    </el-card>

    <el-dialog
      :visible.sync="dialogVisible"
      :title="isEdit ? '编辑成本项' : '新增成本项'"
      width="600px"
      append-to-body
      :close-on-click-modal="false"
      :close-on-press-escape="!submitLoading"
      :show-close="!submitLoading"
      :before-close="handleDialogClose"
    >
      <el-form :model="formData" :rules="formRules" ref="formRef" label-width="100px">
        <el-row :gutter="20">
          <el-col :span="24">
            <el-form-item label="商品" prop="product_id">
              <el-select
                v-model="formData.product_id"
                placeholder="选择商品"
                filterable
                remote
                reserve-keyword
                style="width: 100%"
                :disabled="isEdit"
                :remote-method="remoteSearchProducts"
                :loading="productsLoading"
              >
                <el-option
                  v-for="product in productOptions"
                  :key="product.id"
                  :label="`${product.name}${product.sku ? ' (' + product.sku + ')' : ''}`"
                  :value="product.id"
                />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="成本类型" prop="cost_type">
              <el-select v-model="formData.cost_type" placeholder="请选择成本类型" style="width: 100%">
                <el-option
                  v-for="option in costTypeOptions"
                  :key="option.value"
                  :label="option.label"
                  :value="option.value"
                />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="成本项目" prop="cost_name">
              <el-input v-model="formData.cost_name" placeholder="如：采购价、快递费等" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="单位成本" prop="unit_cost">
              <el-input-number
                v-model="formData.unit_cost"
                :min="0"
                :precision="2"
                :step="1"
                style="width: 100%"
                placeholder="请输入单位成本"
              />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="数量" prop="quantity">
              <el-input-number
                v-model="formData.quantity"
                :min="1"
                :precision="0"
                :step="1"
                style="width: 100%"
                placeholder="默认1"
              />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="生效日期" prop="effective_date">
              <el-date-picker
                v-model="formData.effective_date"
                type="date"
                placeholder="选择生效日期"
                value-format="yyyy-MM-dd"
                style="width: 100%"
              />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="失效日期" prop="expiry_date">
              <el-date-picker
                v-model="formData.expiry_date"
                type="date"
                placeholder="留空表示永久有效"
                value-format="yyyy-MM-dd"
                style="width: 100%"
              />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="是否启用" prop="is_active">
              <el-switch
                v-model="formData.is_active"
                :active-value="1"
                :inactive-value="0"
                active-text="启用"
                inactive-text="禁用"
              />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="24">
            <el-form-item label="备注" prop="remark">
              <el-input
                v-model="formData.remark"
                type="textarea"
                :rows="3"
                placeholder="请输入备注信息（可选）"
                maxlength="500"
                show-word-limit
              />
            </el-form-item>
          </el-col>
        </el-row>
      </el-form>
      <template slot="footer">
        <el-button @click="closeDialog" :disabled="submitLoading">取消</el-button>
        <el-button type="primary" :loading="submitLoading" @click="handleSubmit">确定</el-button>
      </template>
    </el-dialog>

    <el-dialog
      :visible.sync="batchDialogVisible"
      title="批量设置成本"
      width="800px"
      append-to-body
      :close-on-click-modal="false"
      :close-on-press-escape="!batchSubmitLoading"
      :show-close="!batchSubmitLoading"
      :before-close="handleBatchDialogClose"
    >
      <el-form :model="batchForm" :rules="batchFormRules" ref="batchFormRef" label-width="100px">
        <el-form-item label="选择商品" prop="product_id">
          <el-select
            v-model="batchForm.product_id"
            placeholder="选择需要设置成本的商品"
            filterable
            remote
            reserve-keyword
            style="width: 100%"
            :remote-method="remoteSearchProducts"
            :loading="productsLoading"
          >
            <el-option
              v-for="product in productOptions"
              :key="product.id"
              :label="`${product.name}${product.sku ? ' (' + product.sku + ')' : ''}`"
              :value="product.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="成本项列表" prop="cost_items">
          <div class="batch-cost-items">
            <el-table
              :data="batchForm.cost_items"
              border
              size="small"
              class="batch-table"
            >
              <el-table-column label="序号" type="index" width="60" align="center" />
              <el-table-column label="成本类型" min-width="140">
                <template slot-scope="scope">
                  <el-select
                    v-model="scope.row.cost_type"
                    placeholder="选择类型"
                    style="width: 100%"
                    size="small"
                  >
                    <el-option
                      v-for="option in costTypeOptions"
                      :key="option.value"
                      :label="option.label"
                      :value="option.value"
                    />
                  </el-select>
                </template>
              </el-table-column>
              <el-table-column label="成本项目名称" min-width="160">
                <template slot-scope="scope">
                  <el-input
                    v-model="scope.row.cost_name"
                    placeholder="成本项目名称"
                    size="small"
                  />
                </template>
              </el-table-column>
              <el-table-column label="单位成本" width="160">
                <template slot-scope="scope">
                  <el-input-number
                    v-model="scope.row.unit_cost"
                    :min="0"
                    :precision="2"
                    :step="1"
                    size="small"
                    controls-position="right"
                    style="width: 100%"
                    placeholder="单位成本"
                  />
                </template>
              </el-table-column>
              <el-table-column label="操作" width="80" align="center">
                <template slot-scope="scope">
                  <el-button
                    type="text"
                    size="small"
                    icon="el-icon-delete"
                    style="color: #f56c6c"
                    :disabled="batchForm.cost_items.length <= 1"
                    @click="removeBatchCostItem(scope.$index)"
                  >
                    删除
                  </el-button>
                </template>
              </el-table-column>
            </el-table>
            <el-button
              type="primary"
              plain
              size="small"
              icon="el-icon-plus"
              style="margin-top: 12px"
              @click="addBatchCostItem"
            >
              添加成本项
            </el-button>
          </div>
        </el-form-item>
      </el-form>
      <template slot="footer">
        <el-button @click="closeBatchDialog" :disabled="batchSubmitLoading">取消</el-button>
        <el-button type="primary" :loading="batchSubmitLoading" @click="handleBatchSubmit">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import {
  getProductCosts,
  createProductCost,
  updateProductCost,
  deleteProductCost,
  toggleProductCostActive,
  batchCreateProductCost
} from '@/api/productCost'
import { getProducts, calculateProductCost } from '@/api/product'
import asyncTask from '@/mixins/asyncTask'
import formState from '@/mixins/formState'

const COST_TYPE_MAP = {
  purchase: { label: '采购成本', type: '' },
  shipping: { label: '物流成本', type: 'success' },
  packaging: { label: '包装成本', type: 'warning' },
  platform_fee: { label: '平台服务费', type: 'info' },
  marketing: { label: '营销推广费', type: 'danger' },
  tax: { label: '税费', type: '' },
  other: { label: '其他费用', type: 'info' }
}

const COST_TYPE_OPTIONS = Object.keys(COST_TYPE_MAP).map(key => ({
  value: key,
  label: COST_TYPE_MAP[key].label
}))

export default {
  name: 'ProductCostIndex',
  mixins: [asyncTask, formState],
  data() {
    return {
      costTypeOptions: COST_TYPE_OPTIONS,
      productOptions: [],
      productsLoading: false,
      searchForm: {
        product_id: '',
        cost_type: '',
        is_active: ''
      },
      tableData: [],
      pagination: {
        total: 0,
        currentPage: 1,
        pageSize: 20
      },
      selectedProductSummary: null,
      formData: {
        product_id: '',
        cost_type: '',
        cost_name: '',
        unit_cost: null,
        quantity: 1,
        effective_date: '',
        expiry_date: '',
        is_active: 1,
        remark: ''
      },
      formRules: {
        product_id: [{ required: true, message: '请选择商品', trigger: 'change' }],
        cost_type: [{ required: true, message: '请选择成本类型', trigger: 'change' }],
        cost_name: [{ required: true, message: '请输入成本项目名称', trigger: 'blur' }],
        unit_cost: [{ required: true, message: '请输入单位成本', trigger: 'blur' }],
        effective_date: [{ required: true, message: '请选择生效日期', trigger: 'change' }]
      },
      batchDialogVisible: false,
      batchSubmitLoading: false,
      batchForm: {
        product_id: '',
        cost_items: [
          { cost_type: '', cost_name: '', unit_cost: null }
        ]
      },
      batchFormRules: {
        product_id: [{ required: true, message: '请选择商品', trigger: 'change' }]
      }
    }
  },
  created() {
    this.fetchData()
    this.fetchProducts()
  },
  methods: {
    getCostTypeLabel(type) {
      return COST_TYPE_MAP[type]?.label || type
    },
    getCostTypeTagType(type) {
      return COST_TYPE_MAP[type]?.type || 'info'
    },
    getGrossMarginClass(margin) {
      if (margin >= 0.3) return 'margin-high'
      if (margin >= 0.1) return 'margin-medium'
      return 'margin-low'
    },
    formatNumber(val) {
      if (val === null || val === undefined || val === '') return '0.00'
      return Number(val).toFixed(2)
    },
    formatPercent(val) {
      if (val === null || val === undefined || val === '') return '-'
      return (Number(val) * 100).toFixed(2) + '%'
    },
    formatDate(dateStr) {
      if (!dateStr) return ''
      const date = new Date(dateStr)
      return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
    },
    async fetchProducts() {
      await this.safeCall(() => getProducts({ per_page: 50 }), {
        onSuccess: res => {
          this.productOptions = res.data?.data || res.data?.products || res.data || []
        },
        silent: true
      })
    },
    async remoteSearchProducts(query) {
      if (!query) {
        this.fetchProducts()
        return
      }
      this.productsLoading = true
      try {
        const res = await getProducts({ search: query, per_page: 50 })
        this.productOptions = res.data?.data || res.data?.products || res.data || []
      } catch (e) {
        // ignore
      } finally {
        this.productsLoading = false
      }
    },
    async fetchData() {
      await this.withLoading(async () => {
        const params = this.cleanParams({
          page: this.pagination.currentPage,
          per_page: this.pagination.pageSize,
          product_id: this.searchForm.product_id,
          cost_type: this.searchForm.cost_type,
          is_active: this.searchForm.is_active
        })
        const res = await getProductCosts(params)
        this.tableData = res.data?.data || res.data?.product_costs || res.data || []
        this.pagination.total = res.data.meta?.total || this.tableData.length
      }, { silent: true })
    },
    async fetchProductSummary(productId) {
      if (!productId) {
        this.selectedProductSummary = null
        return
      }
      await this.safeCall(() => calculateProductCost({ product_id: productId }), {
        onSuccess: res => {
          const data = res.data?.data || res.data || {}
          const product = this.productOptions.find(p => p.id === productId)
          this.selectedProductSummary = {
            name: product?.name || data.product_name || '-',
            price: data.price || product?.price || 0,
            total_cost: data.total_cost || 0,
            gross_margin: data.gross_margin !== undefined ? data.gross_margin : 0
          }
        },
        silent: true
      })
    },
    handleProductChange(val) {
      this.fetchProductSummary(val)
    },
    handleSearch() {
      this.pagination.currentPage = 1
      this.fetchData()
    },
    handleReset() {
      this.searchForm.product_id = ''
      this.searchForm.cost_type = ''
      this.searchForm.is_active = ''
      this.selectedProductSummary = null
      this.handleSearch()
    },
    handleSizeChange(size) {
      this.pagination.pageSize = size
      this.pagination.currentPage = 1
      this.fetchData()
    },
    handlePageChange(page) {
      this.pagination.currentPage = page
      this.fetchData()
    },
    resetFormData(defaultData = {}) {
      this.formData = {
        product_id: defaultData.product_id || '',
        cost_type: '',
        cost_name: '',
        unit_cost: null,
        quantity: 1,
        effective_date: this.getDefaultEffectiveDate(),
        expiry_date: '',
        is_active: 1,
        remark: ''
      }
    },
    getDefaultEffectiveDate() {
      const today = new Date()
      return `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`
    },
    handleCreate() {
      const defaultData = {}
      if (this.searchForm.product_id) {
        defaultData.product_id = this.searchForm.product_id
      }
      this.openCreateForm(defaultData)
      this.$nextTick(() => {
        if (this.$refs.formRef) {
          this.$refs.formRef.clearValidate()
        }
      })
    },
    handleEdit(row) {
      this.openEditForm(row, () => {
        this.formData = {
          id: row.id,
          product_id: row.product_id,
          cost_type: row.cost_type,
          cost_name: row.cost_name,
          unit_cost: row.unit_cost,
          quantity: row.quantity || 1,
          effective_date: row.effective_date,
          expiry_date: row.expiry_date,
          is_active: row.is_active,
          remark: row.remark || ''
        }
      })
      this.$nextTick(() => {
        if (this.$refs.formRef) {
          this.$refs.formRef.clearValidate()
        }
      })
    },
    handleSubmit() {
      this.submitForm(
        this.isEdit
          ? () => updateProductCost(this.formData.id, this.formData)
          : () => createProductCost(this.formData),
        {
          formRef: 'formRef',
          onSuccess: () => {
            this.fetchData()
            this.fetchProductSummary(this.searchForm.product_id)
          }
        }
      )
    },
    handleDelete(row) {
      this.confirmAndDelete({
        message: `确定要删除成本项"${row.cost_name}"吗？`,
        apiCall: () => deleteProductCost(row.id),
        onSuccess: () => {
          this.fetchData()
          this.fetchProductSummary(this.searchForm.product_id)
        }
      })
    },
    async handleToggleActive(row, val) {
      try {
        await toggleProductCostActive(row.id)
        this.$message.success(val ? '已启用' : '已禁用')
        this.fetchProductSummary(this.searchForm.product_id)
      } catch (error) {
        row.is_active = val ? 0 : 1
        const msg = error.response?.data?.message || error.message || '操作失败'
        this.$message.error(msg)
      }
    },
    handleBatchSet() {
      this.batchForm = {
        product_id: this.searchForm.product_id || '',
        cost_items: [
          { cost_type: '', cost_name: '', unit_cost: null }
        ]
      }
      this.batchSubmitLoading = false
      this.batchDialogVisible = true
      this.$nextTick(() => {
        if (this.$refs.batchFormRef) {
          this.$refs.batchFormRef.clearValidate()
        }
      })
    },
    addBatchCostItem() {
      this.batchForm.cost_items.push({
        cost_type: '',
        cost_name: '',
        unit_cost: null
      })
    },
    removeBatchCostItem(index) {
      this.batchForm.cost_items.splice(index, 1)
    },
    closeBatchDialog() {
      if (this.batchSubmitLoading) return
      this.batchDialogVisible = false
    },
    handleBatchDialogClose(done) {
      if (this.batchSubmitLoading) return
      done()
    },
    validateBatchForm() {
      const form = this.$refs.batchFormRef
      if (!form) return false

      const productValid = new Promise(resolve => {
        form.validateField('product_id', errorMsg => {
          resolve(!errorMsg)
        })
      })

      let itemsValid = true
      for (const item of this.batchForm.cost_items) {
        if (!item.cost_type || !item.cost_name || item.unit_cost === null || item.unit_cost === undefined || item.unit_cost === '') {
          itemsValid = false
          break
        }
      }

      if (!itemsValid) {
        this.$message.warning('请完整填写所有成本项信息')
        return false
      }

      return productValid
    },
    async handleBatchSubmit() {
      const valid = await this.validateBatchForm()
      if (!valid) return

      this.batchSubmitLoading = true
      try {
        await batchCreateProductCost({
          product_id: this.batchForm.product_id,
          cost_items: this.batchForm.cost_items
        })
        this.$message.success('批量创建成功')
        this.batchDialogVisible = false
        this.fetchData()
        if (this.searchForm.product_id === this.batchForm.product_id) {
          this.fetchProductSummary(this.searchForm.product_id)
        }
      } catch (error) {
        const msg = error.response?.data?.message || error.message || '操作失败'
        this.$message.error(msg)
      } finally {
        this.batchSubmitLoading = false
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.product-cost-page {
  .page-card {
    .page-header {
      margin-bottom: 20px;

      .page-title {
        margin: 0;
        font-size: 18px;
        color: #303133;
      }
    }

    .search-form {
      margin-bottom: 20px;
      background: #fafafa;
      padding: 16px;
      border-radius: 4px;
    }

    .summary-card {
      margin-bottom: 20px;
      padding: 20px;
      background: linear-gradient(135deg, #f5f7fa 0%, #e4e7ed 100%);
      border-radius: 6px;

      .summary-item {
        .summary-label {
          font-size: 13px;
          color: #909399;
          margin-bottom: 6px;
        }

        .summary-value {
          font-size: 20px;
          font-weight: 600;
          color: #303133;

          &.product-name {
            font-size: 16px;
          }

          &.price {
            color: #e6a23c;
          }

          &.cost {
            color: #f56c6c;
          }

          &.margin-high {
            color: #67c23a;
          }

          &.margin-medium {
            color: #e6a23c;
          }

          &.margin-low {
            color: #f56c6c;
          }
        }
      }
    }

    .table-actions {
      margin-bottom: 16px;
    }

    .cost-table {
      margin-bottom: 20px;

      .product-cell {
        display: flex;
        flex-direction: column;

        .product-name {
          font-weight: 500;
          color: #303133;
        }

        .product-sku {
          font-size: 12px;
          color: #909399;
          margin-top: 2px;
        }
      }

      .total-cost {
        font-weight: 600;
        color: #f56c6c;
      }

      .date-range {
        font-size: 12px;
        color: #606266;

        .date-separator {
          margin: 0 4px;
          color: #c0c4cc;
        }
      }
    }

    .pagination {
      display: flex;
      justify-content: flex-end;
    }

    .batch-cost-items {
      width: 100%;

      .batch-table {
        width: 100%;
      }
    }
  }
}
</style>
