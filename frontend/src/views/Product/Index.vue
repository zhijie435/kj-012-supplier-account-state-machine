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
              {{ formatPercent(costDetail.com