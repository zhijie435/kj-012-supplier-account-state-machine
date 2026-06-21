<template>
  <el-dialog
    :title="dialogTitle"
    :visible.sync="visible"
    width="500px"
    :close-on-click-modal="false"
    :close-on-press-escape="!loading"
    @closed="handleClosed"
  >
    <div class="transition-modal-content">
      <div class="transition-info">
        <div class="info-row">
          <span class="info-label">当前状态：</span>
          <StatusBadge :status="currentStatus" />
        </div>
        <div class="info-row">
          <span class="info-label">目标状态：</span>
          <StatusBadge :status="targetStatus" />
        </div>
        <div class="info-row transition-arrow-row">
          <i class="el-icon-right transition-arrow"></i>
        </div>
      </div>

      <el-divider v-if="validationResult && !validationResult.valid" class="error-divider" />

      <el-alert
        v-if="validationResult && !validationResult.valid"
        :title="validationResult.message || '状态转换验证失败'"
        type="error"
        :closable="false"
        class="validation-alert"
      >
        <div v-if="validationResult.errors && validationResult.errors.length > 0" class="error-list">
          <div v-for="(error, index) in validationResult.errors" :key="index" class="error-item">
            <i class="el-icon-error"></i>
            {{ error }}
          </div>
        </div>
      </el-alert>

      <el-form
        :model="form"
        :rules="formRules"
        ref="formRef"
        label-position="top"
        class="transition-form"
      >
        <el-form-item label="操作备注" prop="remark">
          <el-input
            type="textarea"
            v-model="form.remark"
            :rows="4"
            :placeholder="remarkPlaceholder"
            :disabled="loading"
            maxlength="500"
            show-word-limit
          />
        </el-form-item>
      </el-form>

      <div class="warning-tip" v-if="isTargetTerminal">
        <el-alert
          title="注意：此状态为终态，一旦变更将无法回退"
          type="warning"
          :closable="false"
          show-icon
        />
      </div>
    </div>

    <div slot="footer" class="dialog-footer">
      <el-button :disabled="loading" @click="handleCancel">取消</el-button>
      <el-button
        type="primary"
        :loading="loading"
        :disabled="!canSubmit"
        @click="handleConfirm"
      >
        确认转换
      </el-button>
    </div>
  </el-dialog>
</template>

<script>
import StatusBadge from './StatusBadge.vue'
import { getStatusLabel, isTerminalStatus } from '@/utils/constants'
import { validateTransition, updateSupplierStatus } from '@/api/supplier'

export default {
  name: 'StateTransitionModal',
  components: { StatusBadge },
  props: {
    value: {
      type: Boolean,
      default: false
    },
    supplierId: {
      type: [Number, String],
      required: true
    },
    currentStatus: {
      type: String,
      required: true
    },
    targetStatus: {
      type: String,
      required: true
    },
    requireRemark: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      loading: false,
      validating: false,
      validationResult: null,
      form: {
        remark: ''
      },
      formRules: {
        remark: [
          {
            required: this.requireRemark,
            message: '请输入操作备注',
            trigger: 'blur'
          },
          {
            min: 2,
            message: '备注长度不能少于2个字符',
            trigger: 'blur'
          }
        ]
      }
    }
  },
  computed: {
    visible: {
      get() {
        return this.value
      },
      set(val) {
        this.$emit('input', val)
      }
    },
    dialogTitle() {
      const targetLabel = getStatusLabel(this.targetStatus)
      return `状态转换：${targetLabel}`
    },
    remarkPlaceholder() {
      const targetLabel = getStatusLabel(this.targetStatus)
      return this.requireRemark
        ? `请输入变更为「${targetLabel}」的原因（必填）`
        : `请输入变更为「${targetLabel}」的备注说明（选填）`
    },
    isTargetTerminal() {
      return isTerminalStatus(this.targetStatus)
    },
    canSubmit() {
      return !this.loading && !this.validating && this.validationResult?.valid !== false
    }
  },
  watch: {
    visible: {
      handler(val) {
        if (val) {
          this.resetForm()
          this.validateTransition()
        }
      },
      immediate: true
    },
    targetStatus() {
      if (this.visible) {
        this.validateTransition()
      }
    }
  },
  methods: {
    resetForm() {
      this.form.remark = ''
      this.validationResult = null
      this.loading = false
      this.validating = false
      this.$nextTick(() => {
        if (this.$refs.formRef) {
          this.$refs.formRef.clearValidate()
        }
      })
    },
    async validateTransition() {
      if (!this.supplierId || !this.targetStatus) return

      this.validating = true
      this.validationResult = null

      try {
        const res = await validateTransition(this.supplierId, {
          status: this.targetStatus
        })
        this.validationResult = {
          valid: res.data.valid,
          message: res.data.message,
          errors: res.data.errors || []
        }
      } catch (error) {
        this.validationResult = {
          valid: false,
          message: error.response?.data?.message || error.message || '验证失败'
        }
      } finally {
        this.validating = false
      }
    },
    handleCancel() {
      if (this.loading) return
      this.visible = false
    },
    handleClosed() {
      this.resetForm()
    },
    async handleConfirm() {
      const valid = await new Promise(resolve => {
        this.$refs.formRef.validate(resolve)
      })

      if (!valid) return

      if (this.validationResult && !this.validationResult.valid) {
        this.$message.error('状态转换验证未通过，无法执行操作')
        return
      }

      this.loading = true

      try {
        await updateSupplierStatus(this.supplierId, {
          status: this.targetStatus,
          remark: this.form.remark
        })

        this.$message.success('状态转换成功')
        this.$emit('success')
        this.visible = false
      } catch (error) {
        const msg = error.response?.data?.message || error.message || '操作失败'
        this.$message.error(msg)
      } finally {
        this.loading = false
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.transition-modal-content {
  .transition-info {
    background: #f5f7fa;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 16px;

    .info-row {
      display: flex;
      align-items: center;
      margin-bottom: 8px;

      &:last-child {
        margin-bottom: 0;
      }

      .info-label {
        color: #909399;
        font-size: 13px;
        min-width: 80px;
      }
    }

    .transition-arrow-row {
      justify-content: center;
      margin-top: 4px;

      .transition-arrow {
        font-size: 20px;
        color: #c0c4cc;
      }
    }
  }

  .error-divider {
    margin: 16px 0;
  }

  .validation-alert {
    margin-bottom: 16px;

    .error-list {
      margin-top: 8px;

      .error-item {
        display: flex;
        align-items: flex-start;
        gap: 4px;
        font-size: 12px;
        color: #f56c6c;
        margin-bottom: 4px;

        &:last-child {
          margin-bottom: 0;
        }

        i {
          margin-top: 2px;
        }
      }
    }
  }

  .transition-form {
    margin-bottom: 16px;
  }

  .warning-tip {
    margin-top: 16px;
  }
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}
</style>
