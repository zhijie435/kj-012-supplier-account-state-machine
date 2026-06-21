<template>
  <div class="product-index-page">
    <el-card class="page-card" shadow="never">
      <div class="page-header">
        <div class="header-left">
          <h2 class="page-title">商品管理</h2>
          <p class="page-desc">管理商品信息、成本设置与利润分析</p>
        </div>
      </div>

      <el-form :model="searchForm" inline class="search-form">
        <el-form-item label="关键词">
          <el-input
            v-model="searchForm.keyword"
            placeholder="商品名称 / SKU / 条形码"
            clearable
            style="width: 260px"
            @keyup.enter.native="handleSearch"
          />
        </el-form-item>
        <el-form-item label="分类">
          <el-select
            v-model="searchForm.category"
            placeholder="全部分类"
            clearable
            filterable
            style="width: 160px"
          >
            <el-option
              v-for="cat in categoryOptions"
              :key="cat"
              :label="cat"
              :value="cat"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="状态">
          <el-select
            v-model="searchForm.status"
            placeholder="全部状态"
            clearable
            style="width: 140px"
          >
            <el-option label="上架" :value="1" />
            <el-option label="下架" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" icon="el-icon-search" @click="handleSearch">查询</el-button>
          <el-button icon="el-icon-refresh" @click="handleReset">重置</el-button>
          <el-button type="success" icon="el-icon-plus" @click="handleCreate">新增</el-button>
        </el-form-item>
      </el-form>

      <el-table
        v-loading="loading"
        :data="tableData"
        border
        stripe
        class="product-table"
      >
        <el-table-column prop="id" label="ID" width="80" align="center" />
        <el-table-column label="商品名称" min-width="200">
          <template slot-scope="scope">
            <el-link
              type="primary"
              :underline="false"
              @click="handleViewDetail(scope.row)"
            >
              {{ scope.row.name }}
            </el-link>
          </template>
        </el-table-column>
        <el-table-column prop="sku" label="SKU" width="140" />
        <el-table-column prop="category" label="分类" width="120">
          <template slot-scope="scope">
            <el-tag v-if="scope.row.category" size="small" type="info">
              {{ scope.row.category }}
            </el-tag>
            <span v-else>-</span>
          </template>
        </el-table-column>
        <el-table-column label="品牌" width="120">
          <template slot-scope="scope">
            {{ scope.row.supplier?.name || '-' }}
          </template>
        </el-table-column>
        <el-table-column label="售价" width="110" align="right">
          <template slot-scope="scope">
            <span class="sale-price">¥{{ formatNumber(scope.row.sale_price) }}</span>
          </template>
        </el-table-column>
        <el-table-column label="单位成本" width="110" align="right">
          <template slot-scope="scope">
            <span class="unit-cost">¥{{ formatNumber(getUnitCost(scope.row)) }}</span>
          </template>
        </el-table-column>
        <el-table-column label="单位毛利" width="110" align="right">
          <template slot-scope="scope">
            <span :class="getProfitClass(getUnitProfit(scope.row))">
              ¥{{ formatNumber(getUnitProfit(scope.row)) }}
            </span>
          </template>
        </el-table-column>
        <el-table-column label="毛利率" width="160" align="center">
          <template slot-scope="scope">
            <div class="margin-cell">
              <el-tag
                size="small"
                :type="getMarginTagType(getGrossMargin(scope.row))"
                effect="light"
              >
                {{ formatPercent(getGrossMargin(scope.row)) }}
              </el-tag>
              <el-progress
                :percentage="getMarginPercent(getGrossMargin(scope.row))"
                :stroke-width="6"
                :show-text="false"
                :color="getMarginProgressColor(getGrossMargin(scope.row))"
                style="margin-top: 4px"
              />
            </div>
          </template>
        </el-table-column>
        <el-table-column label="库存" width="100" align="center">
          <template slot-scope="scope">
            <el-tag
              size="small"
              :type="getStockTagType(scope.row.stock, scope.row.warning_stock)"
            >
              {{ scope.row.stock ?? 0 }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="100" align="center">
          <template slot-scope="scope">
            <el-tag
              size="small"
              :type="scope.row.status === 1 ? 'success' : 'info'"
              effect="dark"
            >
              {{ scope.row.status === 1 ? '上架' : '下架' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="220" fixed="right" align="center">
          <template slot-scope="scope">
            <el-button type="text" size="small" icon="el-icon-edit" @click="handleEdit(scope.row)">编辑</el-button>
            <el-button type="text" size="small" icon="el-icon-setting" @click="handleCostSetting(scope.row)">成本设置</el-button>
            <el-button type="text" size="small" icon="el-icon-delete" style="color: #f56c6c" @click="handleDelete(scope.row)">删除</el-button>
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
      :title="isEdit ? '编辑商品' : '新增商品'"
      width="720px"
      append-to-body
      :close-on-click-modal="false"
      :close-on-press-escape="!submitLoading"
      :show-close="!submitLoading"
      :before-close="handleDialogClose"
    >
      <el-form :model="formData" :rules="formRules" ref="formRef" label-width="100px">
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="商品名称" prop="name">
              <el-input v-model="formData.name" placeholder="请输入商品名称" maxlength="255" show-word-limit />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="SKU" prop="sku">
              <el-input v-model="formData.sku" placeholder="请输入SKU编码" maxlength="64" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="条形码" prop="barcode">
              <el-input v-model="formData.barcode" placeholder="请输入条形码" maxlength="64" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="分类" prop="category">
              <el-select
                v-model="formData.category"
                placeholder="请选择或输入分类"
                filterable
                allow-create
                default-first-option
                style="width: 100%"
              >
                <el-option
                  v-for="cat in categoryOptions"
                  :key="cat"
                  :label="cat"
                  :value="cat"
                />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="品牌/供应商" prop="supplier_id">
              <el-select
                v-model="formData.supplier_id"
                placeholder="请选择供应商"
                filterable
                clearable
                style="width: 100%"
              >
                <el-option
                  v-for="sup in supplierOptions"
                  :key="sup.id"
                  :label="sup.name"
                  :value="sup.id"
                />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="计量单位" prop="unit">
              <el-input v-model="formData.unit" placeholder="如：件、个、kg" maxlength="32" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="售价" prop="sale_price">
              <el-input-number
                v-model="formData.sale_price"
                :min="0"
                :precision="2"
                :step="1"
                style="width: 100%"
                placeholder="请输入售价"
              />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="建议售价" prop="suggested_price">
              <el-input-number
                v-model="formData.suggested_price"
                :min="0"
                :precision="2"
                :step="1"
                style="width: 100%"
                placeholder="请输入建议售价"
              />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="库存" prop="stock">
              <el-input-number
                v-model="formData.stock"
                :min="0"
                :precision="0"
                :step="1"
                style="width: 100%"
                placeholder="请输入库存数量"
              />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="状态" prop="status">
              <el-radio-group v-model="formData.status">
                <el-radio :label="1">上架</el-radio>
                <el-radio :label="0">下架</el-radio>
              </el-radio-group>
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="24">
            <el-form-item label="描述" prop="description">
              <el-input
                v-model="formData.description"
                type="textarea"
                :rows="3"
                placeholder="请输入商品描述"
                maxlength="1000"
                show-word-limit
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
                :rows="2"
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
      :visible.sync="detailDialogVisible"
      title="商品详情"
      width="760px"
      append-to-body
      :close-on-click-modal="false"
    >
      <div v-loading="detailLoading" v-if="currentProduct" class="product-detail">
        <el-descriptions :column="2" border size="small">
          <el-descriptions-item label="商品名称">{{ currentProduct.name }}</el-descriptions-item>
          <el-descriptions-item label="SKU">{{ currentProduct.sku || '-' }}</el-descriptions-item>
          <el-descriptions-item label="条形码">{{ currentProduct.barcode || '-' }}</el-descriptions-item>
          <el-descriptions-item label="分类">{{ currentProduct.category || '-' }}</el-descriptions-item>
          <el-descriptions-item label="品牌/供应商">{{ currentProduct.supplier?.name || '-' }}</el-descriptions-item>
          <el-descriptions-item label="计量单位">{{ currentProduct.unit || '-' }}</el-descriptions-item>
          <el-descriptions-item label="售价">
            <span class="sale-price">¥{{ formatNumber(currentProduct.sale_price) }}</span>
          </el-descriptions-item>
          <el-descriptions-item label="库存">{{ currentProduct.stock ?? 0 }}</el-descriptions-item>
          <el-descriptions-item label="状态">
            <el-tag size="small" :type="currentProduct.status === 1 ? 'success' : 'info'">
              {{ currentProduct.status === 1 ? '上架' : '下架' }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="创建人">{{ currentProduct.creator?.name || '-' }}</el-descriptions-item>
        </el-descriptions>

        <div v-if="currentProduct.description" class="detail-section">
          <div class="section-title">商品描述</div>
          <div class="section-content">{{ currentProduct.description }}</div>
        </div>

        <div class="detail-section">
          <div class="section-title">成本明细与利润计算</div>
          <el-descriptions :column="2" border size="small">
            <el-descriptions-item label="采购成本">¥{{ formatNumber(costDetail.purchase_price) }}</el-descriptions-item>
            <el-descriptions-item label="物流成本">¥{{ formatNumber(costDetail.shipping_cost) }}</el-descriptions-item>
            <el-descriptions-item label="包装成本">¥{{ formatNumber(costDetail.packaging_cost) }}</el-descriptions-item>
            <el-descriptions-item label="平台服务费">¥{{ formatNumber(costDetail.platform_fee) }}</el-descriptions-item>
            <el-descriptions-item label="佣金比例">
              {{ formatPercent(costDetail.commission_rate / 100) }}
            </el-descriptions-item>
            <el-descriptions-item label="佣金金额">¥{{ formatNumber(costDetail.commission_amount) }}</el-descriptions-item>
            <el-descriptions-item label="税率">
              {{ formatPercent(costDetail.tax_rate / 100) }}
            </el-descriptions-item>
            <el-descriptions-item label="税费金额">¥{{ formatNumber(costDetail.tax_amount) }}</el-descriptions-item>
            <el-descriptions-item label="其他费用">¥{{ formatNumber(costDetail.other_cost) }}</el-descriptions-item>
            <el-descriptions-item label="单位总成本">
              <span class="unit-cost">¥{{ formatNumber(costDetail.total_cost) }}</span>
            </el-descriptions-item>
          </el-descriptions>
        </div>

        <div class="detail-section profit-section">
          <div class="section-title">利润分析</div>
          <el-row :gutter="20">
            <el-col :span="8">
              <div class="profit-card">
                <div class="profit-label">单位毛利</div>
                <div class="profit-value" :class="getProfitClass(costDetail.profit)">
                  ¥{{ formatNumber(costDetail.profit) }}
                </div>
              </div>
            </el-col>
            <el-col :span="8">
              <div class="profit-card">
                <div class="profit-label">毛利率</div>
                <div class="profit-value" :class="getMarginTextClass(costDetail.gross_margin)">
                  {{ formatPercent(costDetail.gross_margin) }}
                </div>
              </div>
            </el-col>
            <el-col :span="8">
              <div class="profit-card">
                <div class="profit-label">售价</div>
                <div class="profit-value sale-price">
                  ¥{{ formatNumber(costDetail.sale_price) }}
                </div>
              </div>
            </el-col>
          </el-row>
          <div class="profit-progress">
            <el-progress
              :percentage="getMarginPercent(costDetail.gross_margin)"
              :stroke-width="18"
              :color="getMarginProgressColor(costDetail.gross_margin)"
            >
              <template slot="default">
                <span :style="{ color: getMarginProgressColor(costDetail.gross_margin) }">
                  {{ formatPercent(costDetail.gross_margin) }}
                </span>
              </template>
            </el-progress>
          </div>
        </div>
      </div>
      <template slot="footer">
        <el-button @click="detailDialogVisible = false">关闭</el-button>
        <el-button type="primary" @click="handleCostSetting(currentProduct)">成本设置</el-button>
      </template>
    </el-dialog>

    <el-dialog
      :visible.sync="costDialogVisible"
      title="成本设置"
      width="680px"
      append-to-body
      :close-on-click-modal="false"
      :close-on-press-escape="!costSubmitLoading"
      :show-close="!costSubmitLoading"
      :before-close="handleCostDialogClose"
    >
      <div v-if="currentProduct" class="cost-setting">
        <div class="cost-header">
          <span class="cost-product-name">{{ currentProduct.name }}</span>
          <el-tag size="small" type="info">{{ currentProduct.sku }}</el-tag>
        </div>
        <el-form :model="costForm" :rules="costFormRules" ref="costFormRef" label-width="110px">
          <el-row :gutter="20">
            <el-col :span="12">
              <el-form-item label="采购成本" prop="purchase_price">
                <el-input-number
                  v-model="costForm.purchase_price"
                  :min="0"
                  :precision="2"
                  :step="1"
                  style="width: 100%"
                  @change="recalculateCost"
                />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="物流成本" prop="shipping_cost">
                <el-input-number
                  v-model="costForm.shipping_cost"
                  :min="0"
                  :precision="2"
                  :step="1"
                  style="width: 100%"
                  @change="recalculateCost"
                />
              </el-form-item>
            </el-col>
          </el-row>
          <el-row :gutter="20">
            <el-col :span="12">
              <el-form-item label="包装成本" prop="packaging_cost">
                <el-input-number
                  v-model="costForm.packaging_cost"
                  :min="0"
                  :precision="2"
                  :step="1"
                  style="width: 100%"
                  @change="recalculateCost"
                />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="平台服务费" prop="platform_fee">
                <el-input-number
                  v-model="costForm.platform_fee"
                  :min="0"
                  :precision="2"
                  :step="1"
                  style="width: 100%"
                  @change="recalculateCost"
                />
              </el-form-item>
            </el-col>
          </el-row>
          <el-row :gutter="20">
            <el-col :span="12">
              <el-form-item label="佣金比例(%)" prop="commission_rate">
                <el-input-number
                  v-model="costForm.commission_rate"
                  :min="0"
                  :max="100"
                  :precision="2"
                  :step="1"
                  style="width: 100%"
                  @change="recalculateCost"
                />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="税率(%)" prop="tax_rate">
                <el-input-number
                  v-model="costForm.tax_rate"
                  :min="0"
                  :max="100"
                  :precision="2"
                  :step="1"
                  style="width: 100%"
                  @change="recalculateCost"
                />
              </el-form-item>
            </el-col>
          </el-row>
          <el-row :gutter="20">
            <el-col :span="12">
              <el-form-item label="其他费用" prop="other_cost">
                <el-input-number
                  v-model="costForm.other_cost"
                  :min="0"
                  :precision="2"
                  :step="1"
                  style="width: 100%"
                  @change="recalculateCost"
                />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="目标毛利率(%)" prop="profit_margin">
                <el-input-number
                  v-model="costForm.profit_margin"
                  :min="0"
                  :max="100"
                  :precision="2"
                  :step="1"
                  style="width: 100%"
                  @change="recalculateCost"
                />
              </el-form-item>
            </el-col>
          </el-row>
          <el-row :gutter="20">
            <el-col :span="12">
              <el-form-item label="生效日期" prop="effective_date">
                <el-date-picker
                  v-model="costForm.effective_date"
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
                  v-model="costForm.expiry_date"
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
                  v-model="costForm.is_active"
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
                  v-model="costForm.remark"
                  type="textarea"
                  :rows="2"
                  placeholder="请输入备注信息（可选）"
                  maxlength="500"
                  show-word-limit
                />
              </el-form-item>
            </el-col>
          </el-row>
        </el-form>

        <div class="cost-preview">
          <div class="preview-title">实时计算预览</div>
          <el-row :gutter="16">
            <el-col :span="8">
              <div class="preview-item">
                <div class="preview-label">佣金金额</div>
                <div class="preview-value">¥{{ formatNumber(costPreview.commission_amount) }}</div>
              </div>
            </el-col>
            <el-col :span="8">
              <div class="preview-item">
                <div class="preview-label">税费金额</div>
                <div class="preview-value">¥{{ formatNumber(costPreview.tax_amount) }}</div>
              </div>
            </el-col>
            <el-col :span="8">
              <div class="preview-item">
                <div class="preview-label">单位总成本</div>
                <div class="preview-value unit-cost">¥{{ formatNumber(costPreview.total_cost) }}</div>
              </div>
            </el-col>
          </el-row>
          <el-row :gutter="16">
            <el-col :span="8">
              <div class="preview-item">
                <div class="preview-label">单位毛利</div>
                <div class="preview-value" :class="getProfitClass(costPreview.profit)">
                  ¥{{ formatNumber(costPreview.profit) }}
                </div>
              </div>
            </el-col>
            <el-col :span="8">
              <div class="preview-item">
                <div class="preview-label">毛利率</div>
                <div class="preview-value" :class="getMarginTextClass(costPreview.gross_margin)">
                  {{ formatPercent(costPreview.gross_margin) }}
                </div>
              </div>
            </el-col>
            <el-col :span="8">
              <div class="preview-item">
                <div class="preview-label">建议售价</div>
                <div class="preview-value sale-price">
                  ¥{{ formatNumber(costPreview.suggested_sale_price) }}
                </div>
              </div>
            </el-col>
          </el-row>
        </div>
      </div>
      <template slot="footer">
        <el-button @click="closeCostDialog" :disabled="costSubmitLoading">取消</el-button>
        <el-button type="primary" :loading="costSubmitLoading" @click="handleCostSubmit">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import {
  getProducts,
  getProduct,
  createProduct,
  updateProduct,
  deleteProduct,
  calculateProductCost,
  addProductCost
} from '@/api/product'
import { getSupplierList } from '@/api/supplier'
import asyncTask from '@/mixins/asyncTask'
import formState from '@/mixins/formState'

export default {
  name: 'ProductIndex',
  mixins: [asyncTask, formState],
  data() {
    return {
      searchForm: {
        keyword: '',
        category: '',
        status: ''
      },
      tableData: [],
      categoryOptions: [],
      supplierOptions: [],
      pagination: {
        total: 0,
        currentPage: 1,
        pageSize: 20
      },
      detailDialogVisible: false,
      detailLoading: false,
      currentProduct: null,
      costDetail: {
        purchase_price: 0,
        shipping_cost: 0,
        packaging_cost: 0,
        platform_fee: 0,
        commission_rate: 0,
        commission_amount: 0,
        tax_rate: 0,
        tax_amount: 0,
        other_cost: 0,
        total_cost: 0,
        profit: 0,
        gross_margin: 0,
        sale_price: 0
      },
      costDialogVisible: false,
      costSubmitLoading: false,
      costForm: {
        purchase_price: 0,
        shipping_cost: 0,
        packaging_cost: 0,
        platform_fee: 0,
        commission_rate: 0,
        tax_rate: 0,
        other_cost: 0,
        profit_margin: 0,
        effective_date: '',
        expiry_date: '',
        is_active: 1,
        remark: ''
      },
      costPreview: {
        commission_amount: 0,
        tax_amount: 0,
        total_cost: 0,
        profit: 0,
        gross_margin: 0,
        suggested_sale_price: 0
      },
      costFormRules: {
        purchase_price: [{ required: true, message: '请输入采购成本', trigger: 'blur' }],
        effective_date: [{ required: true, message: '请选择生效日期', trigger: 'change' }]
      },
      formData: {
        name: '',
        sku: '',
        barcode: '',
        category: '',
        supplier_id: '',
        unit: '',
        sale_price: null,
        suggested_price: null,
        stock: 0,
        status: 1,
        description: '',
        remark: ''
      },
      formRules: {
        name: [{ required: true, message: '请输入商品名称', trigger: 'blur' }],
        sku: [{ required: true, message: '请输入SKU', trigger: 'blur' }]
      }
    }
  },
  created() {
    this.fetchData()
    this.fetchSuppliers()
    this.fetchCategories()
  },
  methods: {
    formatNumber(val) {
      if (val === null || val === undefined || val === '') return '0.00'
      return Number(val).toFixed(2)
    },
    formatPercent(val) {
      if (val === null || val === undefined || val === '') return '-'
      return (Number(val) * 100).toFixed(2) + '%'
    },
    getMarginPercent(val) {
      if (!val) return 0
      return Math.min(100, Math.max(0, Number(val) * 100))
    },
    getUnitCost(row) {
      return row.active_cost?.total_cost || 0
    },
    getUnitProfit(row) {
      const salePrice = Number(row.sale_price) || 0
      const unitCost = this.getUnitCost(row)
      return salePrice - unitCost
    },
    getGrossMargin(row) {
      const salePrice = Number(row.sale_price) || 0
      if (salePrice <= 0) return 0
      const unitProfit = this.getUnitProfit(row)
      return unitProfit / salePrice
    },
    getMarginTagType(margin) {
      if (margin >= 0.3) return 'success'
      if (margin >= 0.15) return 'warning'
      if (margin > 0) return ''
      return 'danger'
    },
    getMarginProgressColor(margin) {
      if (margin >= 0.3) return '#67c23a'
      if (margin >= 0.15) return '#e6a23c'
      if (margin > 0) return '#909399'
      return '#f56c6c'
    },
    getMarginTextClass(margin) {
      if (margin >= 0.3) return 'margin-high'
      if (margin >= 0.15) return 'margin-medium'
      if (margin > 0) return ''
      return 'margin-low'
    },
    getProfitClass(profit) {
      if (profit > 0) return 'profit-positive'
      if (profit < 0) return 'profit-negative'
      return ''
    },
    getStockTagType(stock, warningStock) {
      stock = stock ?? 0
      warningStock = warningStock ?? 10
      if (stock === 0) return 'danger'
      if (stock <= warningStock) return 'warning'
      return 'success'
    },
    async fetchData() {
      await this.withLoading(async () => {
        const params = this.cleanParams({
          page: this.pagination.currentPage,
          per_page: this.pagination.pageSize,
          keyword: this.searchForm.keyword,
          category: this.searchForm.category,
          status: this.searchForm.status
        })
        const res = await getProducts(params)
        const paginator = res.data?.data || res.data
        this.tableData = paginator?.data || paginator || []
        this.pagination.total = paginator?.total || this.tableData.length
      }, { silent: true })
    },
    async fetchSuppliers() {
      await this.safeCall(() => getSupplierList({ per_page: 100 }), {
        onSuccess: res => {
          this.supplierOptions = res.data?.data || res.data?.suppliers || res.data || []
        },
        silent: true
      })
    },
    async fetchCategories() {
      await this.safeCall(() => getProducts({ per_page: 200 }), {
        onSuccess: res => {
          const data = res.data?.data?.data || res.data?.data || []
          const categories = [...new Set(data.map(p => p.category).filter(Boolean))]
          this.categoryOptions = categories
        },
        silent: true
      })
    },
    handleSearch() {
      this.pagination.currentPage = 1
      this.fetchData()
    },
    handleReset() {
      this.searchForm.keyword = ''
      this.searchForm.category = ''
      this.searchForm.status = ''
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
        name: '',
        sku: '',
        barcode: '',
        category: '',
        supplier_id: '',
        unit: '',
        sale_price: null,
        suggested_price: null,
        stock: 0,
        status: 1,
        description: '',
        remark: '',
        ...defaultData
      }
    },
    handleCreate() {
      this.openCreateForm()
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
          name: row.name,
          sku: row.sku,
          barcode: row.barcode || '',
          category: row.category || '',
          supplier_id: row.supplier_id || '',
          unit: row.unit || '',
          sale_price: row.sale_price !== undefined ? row.sale_price : null,
          suggested_price: row.suggested_price || null,
          stock: row.stock ?? 0,
          status: row.status ?? 1,
          description: row.description || '',
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
      const submitData = {
        name: this.formData.name,
        sku: this.formData.sku,
        barcode: this.formData.barcode || null,
        category: this.formData.category || null,
        supplier_id: this.formData.supplier_id || null,
        unit: this.formData.unit || null,
        sale_price: this.formData.sale_price,
        stock: this.formData.stock,
        status: this.formData.status,
        description: this.formData.description || null
      }
      this.submitForm(
        this.isEdit
          ? () => updateProduct(this.formData.id, submitData)
          : () => createProduct(submitData),
        {
          formRef: 'formRef',
          onSuccess: () => {
            this.fetchData()
            this.fetchCategories()
          }
        }
      )
    },
    handleDelete(row) {
      this.confirmAndDelete({
        message: `确定要删除商品"${row.name}"吗？此操作不可恢复。`,
        apiCall: () => deleteProduct(row.id),
        onSuccess: () => this.fetchData()
      })
    },
    async handleViewDetail(row) {
      this.currentProduct = row
      this.detailDialogVisible = true
      this.detailLoading = true
      try {
        const [productRes, costRes] = await Promise.all([
          getProduct(row.id),
          calculateProductCost({ product_id: row.id })
        ])
        this.currentProduct = productRes.data?.data || productRes.data
        this.costDetail = {
          ...this.costDetail,
          ...(costRes.data?.data || costRes.data || {})
        }
      } catch (e) {
        // ignore
      } finally {
        this.detailLoading = false
      }
    },
    handleCostSetting(row) {
      this.currentProduct = row
      this.costForm = {
        purchase_price: row.active_cost?.purchase_price || 0,
        shipping_cost: row.active_cost?.shipping_cost || 0,
        packaging_cost: row.active_cost?.packaging_cost || 0,
        platform_fee: row.active_cost?.platform_fee || 0,
        commission_rate: row.active_cost?.commission_rate || 0,
        tax_rate: row.active_cost?.tax_rate || 0,
        other_cost: row.active_cost?.other_cost || 0,
        profit_margin: row.active_cost?.profit_margin || 0,
        effective_date: this.getDefaultDate(),
        expiry_date: '',
        is_active: 1,
        remark: ''
      }
      this.recalculateCost()
      this.costDialogVisible = true
      this.detailDialogVisible = false
      this.$nextTick(() => {
        if (this.$refs.costFormRef) {
          this.$refs.costFormRef.clearValidate()
        }
      })
    },
    getDefaultDate() {
      const today = new Date()
      return `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`
    },
    recalculateCost() {
      const salePrice = Number(this.currentProduct?.sale_price) || 0
      const purchasePrice = Number(this.costForm.purchase_price) || 0
      const shippingCost = Number(this.costForm.shipping_cost) || 0
      const packagingCost = Number(this.costForm.packaging_cost) || 0
      const platformFee = Number(this.costForm.platform_fee) || 0
      const commissionRate = Number(this.costForm.commission_rate) || 0
      const taxRate = Number(this.costForm.tax_rate) || 0
      const otherCost = Number(this.costForm.other_cost) || 0
      const targetProfitMargin = Number(this.costForm.profit_margin) || 0

      const commissionAmount = Number((salePrice * commissionRate / 100).toFixed(2))
      const taxAmount = Number((salePrice * taxRate / 100).toFixed(2))
      const totalCost = Number((purchasePrice + shippingCost + packagingCost + platformFee + commissionAmount + taxAmount + otherCost).toFixed(2))
      const profit = Number((salePrice - totalCost).toFixed(2))
      const grossMargin = salePrice > 0 ? profit / salePrice : 0
      const suggestedSalePrice = targetProfitMargin > 0
        ? Number((totalCost / (1 - targetProfitMargin / 100)).toFixed(2))
        : 0

      this.costPreview = {
        commission_amount: commissionAmount,
        tax_amount: taxAmount,
        total_cost: totalCost,
        profit: profit,
        gross_margin: grossMargin,
        suggested_sale_price: suggestedSalePrice
      }
    },
    closeCostDialog() {
      if (this.costSubmitLoading) return
      this.costDialogVisible = false
    },
    handleCostDialogClose(done) {
      if (this.costSubmitLoading) return
      done()
    },
    async handleCostSubmit() {
      const form = this.$refs.costFormRef
      if (!form) return
      const valid = await new Promise(resolve => {
        form.validate(resolve)
      })
      if (!valid) return

      this.costSubmitLoading = true
      try {
        const submitData = {
          purchase_price: this.costForm.purchase_price,
          shipping_cost: this.costForm.shipping_cost,
          packaging_cost: this.costForm.packaging_cost,
          platform_fee: this.costForm.platform_fee,
          commission_rate: this.costForm.commission_rate,
          tax_rate: this.costForm.tax_rate,
          other_cost: this.costForm.other_cost,
          profit_margin: this.costForm.profit_margin,
          effective_date: this.costForm.effective_date,
          expiry_date: this.costForm.expiry_date || null,
          is_active: this.costForm.is_active,
          remark: this.costForm.remark || null
        }
        await addProductCost(this.currentProduct.id, submitData)
        this.$message.success('成本设置保存成功')
        this.costDialogVisible = false
        this.fetchData()
      } catch (error) {
        const msg = error.response?.data?.message || error.message || '操作失败'
        this.$message.error(msg)
      } finally {
        this.costSubmitLoading = false
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.product-index-page {
  .page-card {
    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;

      .header-left {
        .page-title {
          font-size: 20px;
          color: #303133;
          margin-bottom: 4px;
        }

        .page-desc {
          font-size: 14px;
          color: #909399;
          margin: 0;
        }
      }
    }

    .search-form {
      margin-bottom: 20px;
      background: #fafafa;
      padding: 16px;
      border-radius: 4px;
    }

    .product-table {
      margin-bottom: 20px;

      .sale-price {
        color: #e6a23c;
        font-weight: 600;
      }

      .unit-cost {
        color: #f56c6c;
        font-weight: 600;
      }

      .profit-positive {
        color: #67c23a;
        font-weight: 600;
      }

      .profit-negative {
        color: #f56c6c;
        font-weight: 600;
      }

      .margin-cell {
        display: flex;
        flex-direction: column;
        align-items: center;
      }
    }

    .pagination {
      display: flex;
      justify-content: flex-end;
    }
  }

  .product-detail {
    .detail-section {
      margin-top: 20px;

      .section-title {
        font-size: 14px;
        font-weight: 600;
        color: #303133;
        margin-bottom: 12px;
        padding-left: 8px;
        border-left: 3px solid #409eff;
      }

      .section-content {
        background: #fafafa;
        padding: 12px;
        border-radius: 4px;
        color: #606266;
        line-height: 1.6;
      }
    }

    .sale-price {
      color: #e6a23c;
      font-weight: 600;
    }

    .unit-cost {
      color: #f56c6c;
      font-weight: 600;
    }

    .profit-section {
      .profit-card {
        background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);
        border-radius: 8px;
        padding: 16px;
        text-align: center;

        .profit-label {
          font-size: 13px;
          color: #909399;
          margin-bottom: 8px;
        }

        .profit-value {
          font-size: 22px;
          font-weight: 700;

          &.sale-price {
            color: #e6a23c;
          }
        }
      }

      .margin-high {
        color: #67c23a;
      }

      .margin-medium {
        color: #e6a23c;
      }

      .margin-low {
        color: #f56c6c;
      }

      .profit-positive {
        color: #67c23a;
      }

      .profit-negative {
        color: #f56c6c;
      }

      .profit-progress {
        margin-top: 16px;
        padding: 0 8px;
      }
    }
  }

  .cost-setting {
    .cost-header {
      margin-bottom: 20px;
      padding: 12px 16px;
      background: linear-gradient(135deg, #ecf5ff 0%, #d9ecff 100%);
      border-radius: 6px;
      display: flex;
      align-items: center;
      gap: 12px;

      .cost-product-name {
        font-size: 16px;
        font-weight: 600;
        color: #303133;
      }
    }

    .cost-preview {
      margin-top: 20px;
      padding: 16px;
      background: #f5f7fa;
      border-radius: 6px;

      .preview-title {
        font-size: 14px;
        font-weight: 600;
        color: #303133;
        margin-bottom: 12px;
      }

      .preview-item {
        background: #fff;
        padding: 12px;
        border-radius: 4px;
        text-align: center;
        margin-bottom: 12px;

        .preview-label {
          font-size: 12px;
          color: #909399;
          margin-bottom: 6px;
        }

        .preview-value {
          font-size: 18px;
          font-weight: 600;
          color: #303133;

          &.unit-cost {
            color: #f56c6c;
          }

          &.sale-price {
            color: #e6a23c;
          }
        }

        .profit-positive {
          color: #67c23a;
        }

        .profit-negative {
          color: #f56c6c;
        }

        .margin-high {
          color: #67c23a;
        }

        .margin-medium {
          color: #e6a23c;
        }

        .margin-low {
          color: #f56c6c;
        }
      }
    }
  }
}
</style>
