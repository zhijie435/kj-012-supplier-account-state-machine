<template>
  <div class="settlement-page">
    <el-card class="stats-card" shadow="never">
      <el-row :gutter="16">
        <el-col :span="3">
          <div class="stat-item">
            <div class="stat-label">结算单数</div>
            <div class="stat-value">{{ statistics.total_count || 0 }}</div>
          </div>
        </el-col>
        <el-col :span="3">
          <div class="stat-item">
            <div class="stat-label">待确认</div>
            <div class="stat-value stat-warning">{{ statistics.pending_count || 0 }}</div>
          </div>
        </el-col>
        <el-col :span="3">
          <div class="stat-item">
            <div class="stat-label">已确认</div>
            <div class="stat-value stat-primary">{{ statistics.confirmed_count || 0 }}</div>
          </div>
        </el-col>
        <el-col :span="3">
          <div class="stat-item">
            <div class="stat-label">已结算</div>
            <div class="stat-value stat-success">{{ statistics.settled_count || 0 }}</div>
          </div>
        </el-col>
        <el-col :span="3">
          <div class="stat-item">
            <div class="stat-label">订单总金额</div>
            <div class="stat-value">¥{{ formatMoney(statistics.total_amount) }}</div>
          </div>
        </el-col>
        <el-col :span="3">
          <div class="stat-item">
            <div class="stat-label">总成本</div>
            <div class="stat-value stat-danger">¥{{ formatMoney(statistics.total_cost) }}</div>
          </div>
        </el-col>
        <el-col :span="3">
          <div class="stat-item">
            <div class="stat-label">总毛利润</div>
            <div class="stat-value" :class="statistics.total_profit >= 0 ? 'stat-success' : 'stat-danger'">
              ¥{{ formatMoney(statistics.total_profit) }}
            </div>
          </div>
        </el-col>
        <el-col :span="3">
          <div class="stat-item">
            <div class="stat-label">平均毛利率</div>
            <div class="stat-value" :class="getProfitRateClass(statistics.avg_profit_rate)">
              {{ formatPercent(statistics.avg_profit_rate) }}
            </div>
          </div>
        </el-col>
      </el-row>
    </el-card>

    <el-card class="table-card" shadow="never">
      <div class="page-header">
        <h2 class="page-title">结算分账管理</h2>
      </div>

      <el-form :model="searchForm" inline class="search-form">
        <el-form-item label="关键词">
          <el-input
            v-model="searchForm.keyword"
            placeholder="单号/订单号"
            clearable
            style="width: 200px"
            @keyup.enter.native="handleSearch"
          />
        </el-form-item>
        <el-form-item label="类型">
          <el-select
            v-model="searchForm.type"
            placeholder="全部类型"
            clearable
            style="width: 140px"
            @change="handleSearch"
          >
            <el-option
              v-for="item in typeOptions"
              :key="item.value"
              :label="item.label"
              :value="item.value"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="状态">
          <el-select
            v-model="searchForm.status"
            placeholder="全部状态"
            clearable
            style="width: 140px"
            @change="handleSearch"
          >
            <el-option
              v-for="item in statusOptions"
              :key="item.value"
              :label="item.label"
              :value="item.value"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="日期范围">
          <el-date-picker
            v-model="searchForm.dateRange"
            type="daterange"
            range-separator="至"
            start-placeholder="开始日期"
            end-placeholder="结束日期"
            value-format="yyyy-MM-dd"
            style="width: 240px"
            @change="handleSearch"
          />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" icon="el-icon-search" @click="handleSearch">查询</el-button>
          <el-button icon="el-icon-refresh" @click="handleReset">重置</el-button>
          <el-button type="success" icon="el-icon-plus" @click="handleCreate">创建结算</el-button>
        </el-form-item>
      </el-form>

      <el-table
        v-loading="loading"
        :data="tableData"
        border
        stripe
        class="settlement-table"
      >
        <el-table-column label="结算单号" width="160">
          <template slot-scope="scope">
            <el-link type="primary" :underline="false" @click="handleViewDetail(scope.row)">
              {{ scope.row.settlement_no }}
            </el-link>
          </template>
        </el-table-column>
        <el-table-column label="类型" width="100" align="center">
          <template slot-scope="scope">
            <el-tag :type="getTypeTag(scope.row.type).type" size="small">
              {{ getTypeTag(scope.row.type).label }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="settlement_date" label="结算日期" width="120" />
        <el-table-column prop="order_count" label="关联订单" width="90" align="center">
          <template slot-scope="scope">
            <el-tag size="mini">{{ scope.row.order_count || 0 }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="订单金额" width="110" align="right">
          <template slot-scope="scope">
            ¥{{ formatMoney(scope.row.total_amount || scope.row.total_sales_amount) }}
          </template>
        </el-table-column>
        <el-table-column label="总成本" width="100" align="right">
          <template slot-scope="scope">
            <span class="text-danger">¥{{ formatMoney(scope.row.total_cost) }}</span>
          </template>
        </el-table-column>
        <el-table-column label="毛利润" width="100" align="right">
          <template slot-scope="scope">
            <span :class="scope.row.total_profit >= 0 ? 'text-success' : 'text-danger'">
              ¥{{ formatMoney(scope.row.total_profit) }}
            </span>
          </template>
        </el-table-column>
        <el-table-column label="毛利率" width="100" align="center">
          <template slot-scope="scope">
            <el-tag :type="getProfitRateTagType(scope.row.profit_rate)" size="mini">
              {{ formatPercent(scope.row.profit_rate) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="供应商分账" width="110" align="right">
          <template slot-scope="scope">
            ¥{{ formatMoney(scope.row.supplier_share) }}
          </template>
        </el-table-column>
        <el-table-column label="经销商分账" width="110" align="right">
          <template slot-scope="scope">
            ¥{{ formatMoney(scope.row.distributor_share) }}
          </template>
        </el-table-column>
        <el-table-column label="平台分账" width="110" align="right">
          <template slot-scope="scope">
            ¥{{ formatMoney(scope.row.platform_share) }}
          </template>
        </el-table-column>
        <el-table-column label="状态" width="100" align="center">
          <template slot-scope="scope">
            <el-tag :type="getStatusTag(scope.row.status).type" size="small">
              {{ getStatusTag(scope.row.status).label }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="260" fixed="right" align="center">
          <template slot-scope="scope">
            <el-button type="text" size="small" @click="handleViewDetail(scope.row)">详情</el-button>
            <el-button
              v-if="scope.row.status === 'pending'"
              type="text"
              size="small"
              @click="handleConfirm(scope.row)"
            >确认</el-button>
            <el-button
              v-if="scope.row.status === 'confirmed'"
              type="text"
              size="small"
              @click="handleSettle(scope.row)"
            >结算</el-button>
            <el-button
              v-if="scope.row.status === 'pending' || scope.row.status === 'confirmed'"
              type="text"
              size="small"
              @click="handleRecalculate(scope.row)"
            >重算</el-button>
            <el-button
              v-if="scope.row.status === 'pending' || scope.row.status === 'cancelled'"
              type="text"
              size="small"
              class="text-danger-btn"
              @click="handleDelete(scope.row)"
            >删除</el-button>
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
      :visible.sync="createDialogVisible"
      title="创建结算"
      width="960px"
      append-to-body
      :close-on-click-modal="false"
      :close-on-press-escape="!submitLoading"
      :show-close="!submitLoading"
    >
      <el-form :model="createForm" :rules="createRules" ref="createFormRef" label-width="110px">
        <el-row :gutter="20">
          <el-col :span="8">
            <el-form-item label="结算类型" prop="type">
              <el-select v-model="createForm.type" style="width: 100%" @change="handleTypeChange">
                <el-option label="订单结算" value="order" />
                <el-option label="月度结算" value="monthly" />
                <el-option label="手动结算" value="manual" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="结算日期" prop="settlement_date">
              <el-date-picker
                v-model="createForm.settlement_date"
                type="date"
                value-format="yyyy-MM-dd"
                style="width: 100%"
              />
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="关联订单号" prop="order_no">
              <el-input v-model="createForm.order_no" placeholder="请输入订单号" />
            </el-form-item>
          </el-col>
        </el-row>

        <el-divider content-position="left">分账比例设置</el-divider>
        <el-row :gutter="20">
          <el-col :span="8">
            <el-form-item label="供应商比例" prop="supplier_ratio">
              <el-input-number
                v-model="createForm.supplier_ratio"
                :min="0"
                :max="100"
                :precision="2"
                :step="1"
                style="width: 100%"
                @change="updateRatioPreview"
              />
              <span class="ratio-suffix">%</span>
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="经销商比例" prop="distributor_ratio">
              <el-input-number
                v-model="createForm.distributor_ratio"
                :min="0"
                :max="100"
                :precision="2"
                :step="1"
                style="width: 100%"
                @change="updateRatioPreview"
              />
              <span class="ratio-suffix">%</span>
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="平台比例" prop="platform_ratio">
              <el-input-number
                v-model="createForm.platform_ratio"
                :min="0"
                :max="100"
                :precision="2"
                :step="1"
                style="width: 100%"
                disabled
              />
              <span class="ratio-suffix">%</span>
            </el-form-item>
          </el-col>
        </el-row>
        <div v-if="ratioTotal !== 100" class="ratio-warning">
          <i class="el-icon-warning-outline"></i>
          分账比例总和必须等于100%，当前合计：{{ ratioTotal }}%
        </div>

        <el-divider content-position="left">商品明细</el-divider>
        <div class="items-wrapper">
          <el-table
            :data="createForm.items"
            border
            size="small"
            class="items-table"
          >
            <el-table-column label="商品" min-width="200">
              <template slot-scope="scope">
                <el-select
                  v-model="scope.row.product_id"
                  placeholder="请选择商品"
                  filterable
                  style="width: 100%"
                  @change="(val) => handleProductChange(val, scope.$index)"
                >
                  <el-option
                    v-for="p in productOptions"
                    :key="p.id"
                    :label="p.name"
                    :value="p.id"
                  />
                </el-select>
              </template>
            </el-table-column>
            <el-table-column label="数量" width="100" align="center">
              <template slot-scope="scope">
                <el-input-number
                  v-model="scope.row.quantity"
                  :min="1"
                  :precision="0"
                  size="small"
                  style="width: 100%"
                  @change="recalcRow(scope.$index)"
                />
              </template>
            </el-table-column>
            <el-table-column label="售价" width="100" align="right">
              <template slot-scope="scope">
                <span v-if="scope.row.sale_price">¥{{ formatMoney(scope.row.sale_price) }}</span>
                <span v-else>-</span>
              </template>
            </el-table-column>
            <el-table-column label="销售金额" width="110" align="right">
              <template slot-scope="scope">
                <span v-if="scope.row.total_sales">¥{{ formatMoney(scope.row.total_sales) }}</span>
                <span v-else>-</span>
              </template>
            </el-table-column>
            <el-table-column label="单位成本" width="100" align="right">
              <template slot-scope="scope">
                <span v-if="scope.row.unit_cost">¥{{ formatMoney(scope.row.unit_cost) }}</span>
                <span v-else>-</span>
              </template>
            </el-table-column>
            <el-table-column label="总成本" width="100" align="right">
              <template slot-scope="scope">
                <span class="text-danger" v-if="scope.row.total_cost">¥{{ formatMoney(scope.row.total_cost) }}</span>
                <span v-else>-</span>
              </template>
            </el-table-column>
            <el-table-column label="毛利" width="100" align="right">
              <template slot-scope="scope">
                <span
                  v-if="scope.row.profit !== undefined"
                  :class="scope.row.profit >= 0 ? 'text-success' : 'text-danger'"
                >
                  ¥{{ formatMoney(scope.row.profit) }}
                </span>
                <span v-else>-</span>
              </template>
            </el-table-column>
            <el-table-column label="操作" width="70" align="center">
              <template slot-scope="scope">
                <el-button
                  type="text"
                  size="small"
                  class="text-danger-btn"
                  @click="removeItem(scope.$index)"
                >删除</el-button>
              </template>
            </el-table-column>
          </el-table>
          <div class="items-actions">
            <el-button type="primary" icon="el-icon-plus" size="small" plain @click="addItem">
              添加商品
            </el-button>
          </div>
        </div>

        <el-divider content-position="left">费用与分账预览</el-divider>
        <div class="preview-wrapper">
          <el-descriptions :column="3" border size="small">
            <el-descriptions-item label="订单金额">
              ¥{{ formatMoney(preview.total_amount) }}
            </el-descriptions-item>
            <el-descriptions-item label="商品成本">
              <span class="text-danger">¥{{ formatMoney(preview.product_cost) }}</span>
            </el-descriptions-item>
            <el-descriptions-item label="其他成本">
              <span class="text-danger">¥{{ formatMoney(preview.other_cost) }}</span>
            </el-descriptions-item>
            <el-descriptions-item label="总成本">
              <span class="text-danger-bold">¥{{ formatMoney(preview.total_cost) }}</span>
            </el-descriptions-item>
            <el-descriptions-item label="毛利润">
              <span :class="preview.total_profit >= 0 ? 'text-success-bold' : 'text-danger-bold'">
                ¥{{ formatMoney(preview.total_profit) }}
              </span>
            </el-descriptions-item>
            <el-descriptions-item label="毛利率">
              <el-tag :type="getProfitRateTagType(preview.profit_rate)" size="mini">
                {{ formatPercent(preview.profit_rate) }}
              </el-tag>
            </el-descriptions-item>
            <el-descriptions-item label="供应商分账">
              ¥{{ formatMoney(preview.supplier_share) }}
            </el-descriptions-item>
            <el-descriptions-item label="经销商分账">
              ¥{{ formatMoney(preview.distributor_share) }}
            </el-descriptions-item>
            <el-descriptions-item label="平台分账">
              <span class="text-primary-bold">¥{{ formatMoney(preview.platform_share) }}</span>
            </el-descriptions-item>
          </el-descriptions>
        </div>
      </el-form>
      <template slot="footer">
        <el-button @click="closeCreateDialog" :disabled="submitLoading">取消</el-button>
        <el-button type="primary" :loading="submitLoading" @click="handleSubmitCreate">确定创建</el-button>
      </template>
    </el-dialog>

    <el-dialog
      :visible.sync="detailDialogVisible"
      title="结算单详情"
      width="900px"
      append-to-body
      :close-on-click-modal="false"
    >
      <div v-if="currentDetail" v-loading="detailLoading">
        <el-descriptions :column="2" border size="small" class="detail-section">
          <el-descriptions-item label="结算单号">{{ currentDetail.settlement_no }}</el-descriptions-item>
          <el-descriptions-item label="类型">
            <el-tag :type="getTypeTag(currentDetail.type).type" size="small">
              {{ getTypeTag(currentDetail.type).label }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="结算日期">{{ currentDetail.settlement_date }}</el-descriptions-item>
          <el-descriptions-item label="关联订单">{{ currentDetail.order_count || 0 }} 笔</el-descriptions-item>
          <el-descriptions-item label="状态">
            <el-tag :type="getStatusTag(currentDetail.status).type" size="small">
              {{ getStatusTag(currentDetail.status).label }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="创建时间">{{ formatTime(currentDetail.created_at) }}</el-descriptions-item>
        </el-descriptions>

        <h4 class="section-title">商品明细</h4>
        <el-table :data="currentDetail.items || []" border size="small" class="detail-table">
          <el-table-column prop="product_name" label="商品名称" min-width="160" />
          <el-table-column prop="product_sku" label="SKU" width="120" />
          <el-table-column prop="quantity" label="数量" width="70" align="center" />
          <el-table-column label="售价" width="90" align="right">
            <template slot-scope="scope">¥{{ formatMoney(scope.row.sale_price) }}</template>
          </el-table-column>
          <el-table-column label="销售金额" width="100" align="right">
            <template slot-scope="scope">¥{{ formatMoney(scope.row.total_sales) }}</template>
          </el-table-column>
          <el-table-column label="单位成本" width="90" align="right">
            <template slot-scope="scope">
              <span class="text-danger">¥{{ formatMoney(scope.row.purchase_price || scope.row.unit_cost) }}</span>
            </template>
          </el-table-column>
          <el-table-column label="总成本" width="100" align="right">
            <template slot-scope="scope">
              <span class="text-danger">¥{{ formatMoney(scope.row.total_cost) }}</span>
            </template>
          </el-table-column>
          <el-table-column label="毛利" width="100" align="right">
            <template slot-scope="scope">
              <span :class="(scope.row.profit || 0) >= 0 ? 'text-success' : 'text-danger'">
                ¥{{ formatMoney(scope.row.profit) }}
              </span>
            </template>
          </el-table-column>
        </el-table>

        <h4 class="section-title">费用汇总</h4>
        <el-descriptions :column="3" border size="small" class="detail-section">
          <el-descriptions-item label="订单金额">
            ¥{{ formatMoney(currentDetail.total_amount || currentDetail.total_sales_amount) }}
          </el-descriptions-item>
          <el-descriptions-item label="商品成本">
            <span class="text-danger">¥{{ formatMoney(currentDetail.product_cost || currentDetail.total_cost_amount) }}</span>
          </el-descriptions-item>
          <el-descriptions-item label="平台费用">
            <span class="text-danger">¥{{ formatMoney(currentDetail.platform_fee) }}</span>
          </el-descriptions-item>
          <el-descriptions-item label="其他成本">
            <span class="text-danger">¥{{ formatMoney(currentDetail.other_cost) }}</span>
          </el-descriptions-item>
          <el-descriptions-item label="总成本">
            <span class="text-danger-bold">¥{{ formatMoney(currentDetail.total_cost) }}</span>
          </el-descriptions-item>
          <el-descriptions-item label="毛利润">
            <span :class="(currentDetail.total_profit || 0) >= 0 ? 'text-success-bold' : 'text-danger-bold'">
              ¥{{ formatMoney(currentDetail.total_profit) }}
            </span>
          </el-descriptions-item>
          <el-descriptions-item label="毛利率" :span="3">
            <el-tag :type="getProfitRateTagType(currentDetail.profit_rate)" size="mini">
              {{ formatPercent(currentDetail.profit_rate) }}
            </el-tag>
          </el-descriptions-item>
        </el-descriptions>

        <h4 class="section-title">分账明细</h4>
        <el-descriptions :column="3" border size="small" class="detail-section">
          <el-descriptions-item label="供应商比例">
            {{ formatPercent(currentDetail.supplier_ratio) }}
          </el-descriptions-item>
          <el-descriptions-item label="经销商比例">
            {{ formatPercent(currentDetail.distributor_ratio) }}
          </el-descriptions-item>
          <el-descriptions-item label="平台比例">
            {{ formatPercent(currentDetail.platform_ratio) }}
          </el-descriptions-item>
          <el-descriptions-item label="供应商分账">
            ¥{{ formatMoney(currentDetail.supplier_share) }}
          </el-descriptions-item>
          <el-descriptions-item label="经销商分账">
            ¥{{ formatMoney(currentDetail.distributor_share) }}
          </el-descriptions-item>
          <el-descriptions-item label="平台分账">
            <span class="text-primary-bold">¥{{ formatMoney(currentDetail.platform_share) }}</span>
          </el-descriptions-item>
        </el-descriptions>
      </div>
      <template slot="footer">
        <el-button @click="detailDialogVisible = false">关闭</el-button>
        <el-button
          v-if="currentDetail && currentDetail.status === 'pending'"
          type="warning"
          icon="el-icon-check"
          @click="handleConfirm(currentDetail)"
        >确认</el-button>
        <el-button
          v-if="currentDetail && currentDetail.status === 'confirmed'"
          type="success"
          icon="el-icon-finished"
          @click="handleSettle(currentDetail)"
        >结算</el-button>
        <el-button
          v-if="currentDetail && (currentDetail.status === 'pending' || currentDetail.status === 'confirmed')"
          type="primary"
          icon="el-icon-refresh"
          @click="handleRecalculate(currentDetail)"
        >重算</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import {
  getSettlements,
  getSettlement,
  createSettlement,
  deleteSettlement,
  recalculateSettlement,
  confirmSettlement,
  settleSettlement,
  getSettlementStatistics,
  previewSettlement
} from '@/api/settlement'
import { getProducts } from '@/api/product'
import asyncTask from '@/mixins/asyncTask'

const STATUS_MAP = {
  pending: { label: '待确认', type: 'warning' },
  confirmed: { label: '已确认', type: '' },
  settled: { label: '已结算', type: 'success' },
  cancelled: { label: '已取消', type: 'info' }
}

const TYPE_MAP = {
  order: { label: '订单结算', type: '' },
  monthly: { label: '月度结算', type: 'primary' },
  manual: { label: '手动结算', type: 'info' }
}

export default {
  name: 'SettlementIndex',
  mixins: [asyncTask],
  data() {
    return {
      loading: false,
      detailLoading: false,
      submitLoading: false,
      statistics: {},
      tableData: [],
      productOptions: [],
      pagination: {
        total: 0,
        currentPage: 1,
        pageSize: 20
      },
      searchForm: {
        keyword: '',
        type: '',
        status: '',
        dateRange: []
      },
      statusOptions: [
        { value: 'pending', label: '待确认' },
        { value: 'confirmed', label: '已确认' },
        { value: 'settled', label: '已结算' },
        { value: 'cancelled', label: '已取消' }
      ],
      typeOptions: [
        { value: 'order', label: '订单结算' },
        { value: 'monthly', label: '月度结算' },
        { value: 'manual', label: '手动结算' }
      ],
      createDialogVisible: false,
      createForm: {
        type: 'manual',
        settlement_date: this.getToday(),
        order_no: '',
        supplier_ratio: 50,
        distributor_ratio: 20,
        platform_ratio: 30,
        items: []
      },
      createRules: {
        type: [{ required: true, message: '请选择结算类型', trigger: 'change' }],
        settlement_date: [{ required: true, message: '请选择结算日期', trigger: 'change' }],
        items: [{ required: true, message: '请至少添加一条商品明细', trigger: 'change' }]
      },
      preview: {
        total_amount: 0,
        product_cost: 0,
        other_cost: 0,
        total_cost: 0,
        total_profit: 0,
        profit_rate: 0,
        supplier_share: 0,
        distributor_share: 0,
        platform_share: 0
      },
      detailDialogVisible: false,
      currentDetail: null
    }
  },
  computed: {
    ratioTotal() {
      return Number(
        (Number(this.createForm.supplier_ratio || 0) +
          Number(this.createForm.distributor_ratio || 0) +
          Number(this.createForm.platform_ratio || 0)).toFixed(2)
      )
    }
  },
  created() {
    this.fetchData()
    this.fetchStatistics()
    this.fetchProducts()
  },
  methods: {
    getToday() {
      const d = new Date()
      return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
    },
    formatMoney(val) {
      const n = Number(val || 0)
      return n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')
    },
    formatPercent(val) {
      const n = Number(val || 0)
      return (n * 100).toFixed(2) + '%'
    },
    formatTime(dateStr) {
      if (!dateStr) return ''
      const date = new Date(dateStr)
      return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')} ${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`
    },
    getStatusTag(status) {
      return STATUS_MAP[status] || { label: status || '-', type: 'info' }
    },
    getTypeTag(type) {
      return TYPE_MAP[type] || { label: type || '-', type: 'info' }
    },
    getProfitRateClass(rate) {
      const r = Number(rate || 0)
      if (r >= 0.3) return 'stat-success'
      if (r >= 0.1) return 'stat-primary'
      if (r >= 0) return 'stat-warning'
      return 'stat-danger'
    },
    getProfitRateTagType(rate) {
      const r = Number(rate || 0)
      if (r >= 0.3) return 'success'
      if (r >= 0.1) return ''
      if (r >= 0) return 'warning'
      return 'danger'
    },
    async fetchStatistics() {
      await this.safeCall(() => getSettlementStatistics(this.cleanParams({
        type: this.searchForm.type,
        status: this.searchForm.status,
        start_date: this.searchForm.dateRange?.[0],
        end_date: this.searchForm.dateRange?.[1]
      })), {
        onSuccess: res => {
          this.statistics = res.data?.data || res.data || {}
        },
        silent: true
      })
    },
    async fetchProducts() {
      await this.safeCall(() => getProducts({ per_page: 500 }), {
        onSuccess: res => {
          const data = res.data?.data?.data || res.data?.data || res.data || []
          this.productOptions = Array.isArray(data) ? data : []
        },
        silent: true
      })
    },
    async fetchData() {
      await this.withLoading(async () => {
        const params = this.cleanParams({
          page: this.pagination.currentPage,
          per_page: this.pagination.pageSize,
          keyword: this.searchForm.keyword,
          type: this.searchForm.type,
          status: this.searchForm.status,
          start_date: this.searchForm.dateRange?.[0],
          end_date: this.searchForm.dateRange?.[1]
        })
        const res = await getSettlements(params)
        const list = res.data?.data?.data || res.data?.data || []
        this.tableData = this.enrichRows(list)
        this.pagination.total = res.data?.data?.total || res.data?.meta?.total || list.length || 0
      }, { silent: true })
    },
    enrichRows(rows) {
      return rows.map(r => {
        const totalAmount = Number(r.total_amount || r.total_sales_amount || 0)
        const totalCost = Number(r.total_cost || r.total_cost_amount || 0)
        const profit = totalAmount - totalCost
        const profitRate = totalAmount > 0 ? profit / totalAmount : 0
        const supplierRatio = Number(r.supplier_ratio || 0.5)
        const distributorRatio = Number(r.distributor_ratio || 0.2)
        const platformRatio = Number(r.platform_ratio || 0.3)
        return {
          ...r,
          total_amount: totalAmount,
          total_cost: totalCost,
          total_profit: profit,
          profit_rate: profitRate,
          supplier_share: totalAmount * supplierRatio,
          distributor_share: totalAmount * distributorRatio,
          platform_share: totalAmount * platformRatio
        }
      })
    },
    handleSearch() {
      this.pagination.currentPage = 1
      this.fetchData()
      this.fetchStatistics()
    },
    handleReset() {
      this.searchForm = {
        keyword: '',
        type: '',
        status: '',
        dateRange: []
      }
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
    handleCreate() {
      this.createForm = {
        type: 'manual',
        settlement_date: this.getToday(),
        order_no: '',
        supplier_ratio: 50,
        distributor_ratio: 20,
        platform_ratio: 30,
        items: []
      }
      this.recalcPreview()
      this.createDialogVisible = true
    },
    closeCreateDialog() {
      if (this.submitLoading) return
      this.createDialogVisible = false
    },
    handleTypeChange() {
      this.recalcPreview()
    },
    updateRatioPreview() {
      const s = Number(this.createForm.supplier_ratio || 0)
      const d = Number(this.createForm.distributor_ratio || 0)
      this.createForm.platform_ratio = Number((100 - s - d).toFixed(2))
      this.recalcPreview()
    },
    addItem() {
      this.createForm.items.push({
        product_id: null,
        product_name: '',
        quantity: 1,
        sale_price: 0,
        unit_cost: 0,
        total_sales: 0,
        total_cost: 0,
        profit: 0
      })
    },
    removeItem(index) {
      this.createForm.items.splice(index, 1)
      this.recalcPreview()
    },
    handleProductChange(productId, index) {
      const product = this.productOptions.find(p => p.id === productId)
      if (product) {
        const row = this.createForm.items[index]
        row.product_name = product.name
        row.sale_price = Number(product.price || product.sale_price || 0)
        row.unit_cost = Number(product.cost || product.purchase_price || 0)
        this.recalcRow(index)
      }
    },
    recalcRow(index) {
      const row = this.createForm.items[index]
      const qty = Number(row.quantity || 0)
      row.total_sales = Number((row.sale_price || 0) * qty).toFixed(2) * 1
      row.total_cost = Number((row.unit_cost || 0) * qty).toFixed(2) * 1
      row.profit = Number((row.total_sales - row.total_cost).toFixed(2)) * 1
      this.recalcPreview()
    },
    recalcPreview() {
      const items = this.createForm.items || []
      let totalAmount = 0
      let productCost = 0
      items.forEach(it => {
        totalAmount += Number(it.total_sales || 0)
        productCost += Number(it.total_cost || 0)
      })
      const otherCost = 0
      const totalCost = productCost + otherCost
      const totalProfit = totalAmount - totalCost
      const profitRate = totalAmount > 0 ? totalProfit / totalAmount : 0
      const sRatio = Number(this.createForm.supplier_ratio || 0) / 100
      const dRatio = Number(this.createForm.distributor_ratio || 0) / 100
      const pRatio = Number(this.createForm.platform_ratio || 0) / 100
      this.preview = {
        total_amount: totalAmount,
        product_cost: productCost,
        other_cost: otherCost,
        total_cost: totalCost,
        total_profit: totalProfit,
        profit_rate: profitRate,
        supplier_share: totalAmount * sRatio,
        distributor_share: totalAmount * dRatio,
        platform_share: totalAmount * pRatio
      }
    },
    async handleSubmitCreate() {
      if (this.ratioTotal !== 100) {
        this.$message.error('分账比例总和必须等于100%')
        return
      }
      if (!this.createForm.items || this.createForm.items.length === 0) {
        this.$message.error('请至少添加一条商品明细')
        return
      }
      const invalidIdx = this.createForm.items.findIndex(i => !i.product_id || !i.quantity)
      if (invalidIdx >= 0) {
        this.$message.error(`第 ${invalidIdx + 1} 行商品信息不完整`)
        return
      }
      this.submitLoading = true
      try {
        const payload = {
          ...this.createForm,
          start_date: this.createForm.settlement_date,
          end_date: this.createForm.settlement_date,
          supplier_ratio: this.createForm.supplier_ratio / 100,
          distributor_ratio: this.createForm.distributor_ratio / 100,
          platform_ratio: this.createForm.platform_ratio / 100,
          items: this.createForm.items
        }
        await createSettlement(payload)
        this.$message.success('结算单创建成功')
        this.createDialogVisible = false
        this.fetchData()
        this.fetchStatistics()
      } catch (e) {
        this.$message.error(e.response?.data?.message || e.message || '创建失败')
      } finally {
        this.submitLoading = false
      }
    },
    async handleViewDetail(row) {
      this.detailLoading = true
      this.currentDetail = null
      this.detailDialogVisible = true
      try {
        const res = await getSettlement(row.id)
        this.currentDetail = this.enrichRows([res.data?.data || res.data || row])[0]
      } catch (e) {
        this.$message.error('获取详情失败')
      } finally {
        this.detailLoading = false
      }
    },
    async handleConfirm(row) {
      this.$confirm('确定确认该结算单吗？', '提示', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      }).then(async () => {
        await this.withLoading(() => confirmSettlement(row.id), {
          successMessage: '确认成功',
          onSuccess: () => {
            this.fetchData()
            this.fetchStatistics()
            if (this.currentDetail && this.currentDetail.id === row.id) {
              this.currentDetail.status = 'confirmed'
            }
          }
        })
      }).catch(() => {})
    },
    async handleSettle(row) {
      this.$confirm('确定完成结算吗？结算后状态将不可更改。', '提示', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      }).then(async () => {
        await this.withLoading(() => settleSettlement(row.id), {
          successMessage: '结算成功',
          onSuccess: () => {
            this.fetchData()
            this.fetchStatistics()
            if (this.currentDetail && this.currentDetail.id === row.id) {
              this.currentDetail.status = 'settled'
            }
          }
        })
      }).catch(() => {})
    },
    async handleRecalculate(row) {
      this.$confirm('确定重新计算该结算单吗？', '提示', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      }).then(async () => {
        await this.withLoading(() => recalculateSettlement(row.id), {
          successMessage: '重算成功',
          onSuccess: () => {
            this.fetchData()
            this.fetchStatistics()
            this.handleViewDetail(row)
          }
        })
      }).catch(() => {})
    },
    handleDelete(row) {
      this.$confirm('确定删除该结算单吗？此操作不可恢复。', '提示', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      }).then(async () => {
        await this.withLoading(() => deleteSettlement(row.id), {
          successMessage: '删除成功',
          onSuccess: () => {
            this.fetchData()
            this.fetchStatistics()
            if (this.currentDetail && this.currentDetail.id === row.id) {
              this.detailDialogVisible = false
            }
          }
        })
      }).catch(() => {})
    }
  }
}
</script>

<style lang="scss" scoped>
.settlement-page {
  padding: 16px;

  .stats-card {
    margin-bottom: 16px;

    .stat-item {
      text-align: center;
      padding: 8px 4px;
      border-right: 1px solid #ebeef5;

      &:last-child {
        border-right: none;
      }

      .stat-label {
        font-size: 13px;
        color: #909399;
        margin-bottom: 6px;
      }

      .stat-value {
        font-size: 20px;
        font-weight: 600;
        color: #303133;

        &.stat-warning { color: #e6a23c; }
        &.stat-primary { color: #409eff; }
        &.stat-success { color: #67c23a; }
        &.stat-danger { color: #f56c6c; }
      }
    }
  }

  .table-card {
    .page-header {
      margin-bottom: 16px;
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

    .settlement-table {
      margin-bottom: 20px;
    }

    .pagination {
      display: flex;
      justify-content: flex-end;
    }
  }

  .text-danger { color: #f56c6c; }
  .text-danger-bold { color: #f56c6c; font-weight: 600; }
  .text-success { color: #67c23a; }
  .text-success-bold { color: #67c23a; font-weight: 600; }
  .text-primary-bold { color: #409eff; font-weight: 600; }
  .text-danger-btn { color: #f56c6c; }

  .ratio-suffix {
    margin-left: 4px;
    color: #606266;
    font-size: 13px;
  }

  .ratio-warning {
    margin-top: -10px;
    margin-bottom: 16px;
    padding: 8px 12px;
    background: #fdf6ec;
    border: 1px solid #faecd8;
    border-radius: 4px;
    color: #e6a23c;
    font-size: 13px;

    i {
      margin-right: 4px;
    }
  }

  .items-wrapper {
    .items-table {
      margin-bottom: 10px;
    }
    .items-actions {
      text-align: left;
      margin-bottom: 10px;
    }
  }

  .preview-wrapper {
    margin-bottom: 10px;
  }

  .detail-section {
    margin-bottom: 16px;
  }

  .section-title {
    margin: 20px 0 10px;
    padding-left: 8px;
    border-left: 3px solid #409eff;
    font-size: 15px;
    color: #303133;
    font-weight: 600;
  }

  .detail-table {
    margin-bottom: 16px;
  }
}
</style>
