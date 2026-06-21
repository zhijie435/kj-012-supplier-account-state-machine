<template>
  <div class="dashboard-page">
    <div class="page-header">
      <div class="header-left">
        <h2 class="page-title">数据概览</h2>
        <p class="page-desc">电商订单库存业务数据统计</p>
      </div>
    </div>
    <el-row :gutter="20" class="stat-row">
      <el-col :span="6">
        <el-card class="stat-card">
          <div class="stat-label">今日订单</div>
          <div class="stat-number">{{ statistics.today_orders || 0 }}</div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card class="stat-card">
          <div class="stat-label">库存预警</div>
          <div class="stat-number" style="color: #e6a23c">{{ statistics.inventory_warnings || 0 }}</div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card class="stat-card">
          <div class="stat-label">待处理退款</div>
          <div class="stat-number" style="color: #f56c6c">{{ statistics.pending_refunds || 0 }}</div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card class="stat-card">
          <div class="stat-label">活跃SKU</div>
          <div class="stat-number" style="color: #67c23a">{{ statistics.active_skus || 0 }}</div>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script>
export default {
  name: 'Dashboard',
  data() {
    return {
      statistics: {}
    }
  },
  created() {
    this.fetchStatistics()
  },
  methods: {
    async fetchStatistics() {
      try {
        const res = await this.$http?.get('/dashboard/statistics')
        if (res?.data) {
          this.statistics = res.data
        }
      } catch (e) {
        console.error(e)
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.dashboard-page {
  .stat-row {
    margin-bottom: 20px;
  }

  .stat-card {
    text-align: center;

    .stat-label {
      font-size: 14px;
      color: #909399;
      margin-bottom: 8px;
    }

    .stat-number {
      font-size: 28px;
      font-weight: 600;
      color: #303133;
    }
  }
}
</style>
