<template>
  <div class="login-page">
    <div class="login-card">
      <h2 class="login-title">电商订单库存后台</h2>
      <el-form :model="loginForm" :rules="loginRules" ref="loginFormRef" @submit.native.prevent="handleLogin">
        <el-form-item prop="guard_name">
          <el-select v-model="loginForm.guard_name" placeholder="选择登录端" style="width: 100%">
            <el-option label="平台端" value="platform" />
            <el-option label="供应商端" value="supplier" />
            <el-option label="经销商端" value="distributor" />
          </el-select>
        </el-form-item>
        <el-form-item prop="email">
          <el-input v-model="loginForm.email" placeholder="邮箱" prefix-icon="el-icon-message" />
        </el-form-item>
        <el-form-item prop="password">
          <el-input v-model="loginForm.password" type="password" placeholder="密码" prefix-icon="el-icon-lock" show-password />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" :loading="loading" style="width: 100%" native-type="submit">登录</el-button>
        </el-form-item>
      </el-form>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Login',
  data() {
    return {
      loading: false,
      loginForm: {
        guard_name: 'platform',
        email: '',
        password: ''
      },
      loginRules: {
        guard_name: [{ required: true, message: '请选择登录端', trigger: 'change' }],
        email: [
          { required: true, message: '请输入邮箱', trigger: 'blur' },
          { type: 'email', message: '请输入正确的邮箱地址', trigger: 'blur' }
        ],
        password: [{ required: true, message: '请输入密码', trigger: 'blur' }]
      }
    }
  },
  methods: {
    handleLogin() {
      this.$refs.loginFormRef.validate(async valid => {
        if (!valid) return
        this.loading = true
        try {
          await this.$store.dispatch('login', this.loginForm)
          this.$message.success('登录成功')
          const redirect = this.$route.query.redirect || '/dashboard'
          this.$router.push(redirect)
        } catch (e) {
          console.error(e)
        } finally {
          this.loading = false
        }
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.login-page {
  height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.login-card {
  width: 400px;
  padding: 40px;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
}

.login-title {
  text-align: center;
  margin-bottom: 30px;
  color: #303133;
  font-size: 22px;
}
</style>
