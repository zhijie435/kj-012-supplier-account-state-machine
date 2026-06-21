<template>
  <el-container class="app-container">
    <el-header v-if="isLogin" class="app-header">
      <div class="header-left">
        <h1 class="logo">内容审核标注平台</h1>
        <el-tag size="mini" :type="guardTagType" class="guard-tag">{{ guardLabel }}</el-tag>
      </div>
      <div class="header-right">
        <el-dropdown @command="handleCommand">
          <span class="user-info">
            <i class="el-icon-user"></i>
            {{ userInfo?.name || '用户' }}
            <i class="el-icon-arrow-down el-icon--right"></i>
          </span>
          <el-dropdown-menu slot="dropdown">
            <el-dropdown-item command="profile">个人中心</el-dropdown-item>
            <el-dropdown-item command="logout" divided>退出登录</el-dropdown-item>
          </el-dropdown-menu>
        </el-dropdown>
      </div>
    </el-header>
    <el-container>
      <el-aside v-if="isLogin" width="220px" class="app-aside">
        <el-menu
          :default-active="$route.path"
          router
          background-color="#304156"
          text-color="#bfcbd9"
          active-text-color="#ffd04b"
        >
          <el-menu-item index="/dashboard">
            <i class="el-icon-s-home"></i>
            <span slot="title">数据概览</span>
          </el-menu-item>

          <el-menu-item index="/suppliers" v-permission="'supplier.view'">
            <i class="el-icon-s-cooperation"></i>
            <span slot="title">供应商管理</span>
          </el-menu-item>

          <el-submenu index="/product" v-if="$hasPermission('product.view') || $hasPermission('product.cost.view')">
            <template slot="title">
              <i class="el-icon-goods"></i>
              <span>商品管理</span>
            </template>
            <el-menu-item index="/products" v-permission="'product.view'">商品列表</el-menu-item>
            <el-menu-item index="/product-costs" v-permission="'product.cost.view'">成本管理</el-menu-item>
          </el-submenu>

          <el-menu-item index="/settlements" v-permission="'settlement.view'">
            <i class="el-icon-money"></i>
            <span slot="title">结算分账</span>
          </el-menu-item>

          <el-submenu index="/system" v-if="$hasPermission('role.view') || $hasPermission('user.view')">
            <template slot="title">
              <i class="el-icon-s-custom"></i>
              <span>系统管理</span>
            </template>
            <el-menu-item index="/roles" v-permission="'role.view'">角色管理</el-menu-item>
            <el-menu-item index="/system/users" v-permission="'user.view'">用户管理</el-menu-item>
          </el-submenu>
        </el-menu>
      </el-aside>
      <el-main class="app-main">
        <router-view />
      </el-main>
    </el-container>
  </el-container>
</template>

<script>
import { mapGetters } from 'vuex'

const guardLabels = {
  platform: '平台端',
  supplier: '供应商端',
  distributor: '经销商端'
}

const guardTagTypes = {
  platform: '',
  supplier: 'success',
  distributor: 'warning'
}

export default {
  name: 'App',
  computed: {
    ...mapGetters(['isLogin', 'userInfo']),
    currentGuard() {
      return this.userInfo?.guard_name || 'platform'
    },
    guardLabel() {
      return guardLabels[this.currentGuard] || '平台端'
    },
    guardTagType() {
      return guardTagTypes[this.currentGuard] || ''
    }
  },
  methods: {
    handleCommand(command) {
      if (command === 'logout') {
        this.$confirm('确定要退出登录吗？', '提示', {
          confirmButtonText: '确定',
          cancelButtonText: '取消',
          type: 'warning'
        }).then(() => {
          this.$store.dispatch('logout')
          this.$router.push('/login')
          this.$message.success('已退出登录')
        }).catch(() => {})
      } else if (command === 'profile') {
        this.$message.info('个人中心功能开发中')
      }
    }
  }
}
</script>

<style lang="scss">
.app-container {
  height: 100vh;
}

.app-header {
  background: #fff;
  border-bottom: 1px solid #e6e6e6;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 24px;

  .header-left {
    display: flex;
    align-items: center;
    gap: 12px;

    .logo {
      font-size: 18px;
      margin: 0;
      color: #303133;
    }

    .guard-tag {
      vertical-align: middle;
    }
  }

  .header-right .user-info {
    cursor: pointer;
    color: #606266;
  }
}

.app-aside {
  background: #304156;
  overflow-x: hidden;

  .el-menu {
    border-right: none;
  }
}

.app-main {
  background: #f0f2f5;
  padding: 20px;
  overflow-y: auto;
}
</style>
