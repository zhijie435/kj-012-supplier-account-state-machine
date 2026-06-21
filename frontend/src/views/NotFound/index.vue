<template>
  <div class="not-found-page">
    <h1>404</h1>
    <p>页面不存在</p>
    <el-button type="primary" @click="$router.push('/')">返回首页</el-button>
  </div>
</template>

<script>
export default {
  name: 'NotFound'
}
</script>

<style lang="scss" scoped>
.not-found-page {
  text-align: center;
  padding-top: 120px;

  h1 {
    font-size: 72px;
    color: #c0c4cc;
  }

  p {
    font-size: 18px;
    color: #909399;
    margin: 16px 0 32px;
  }
}
</style>
