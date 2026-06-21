import { Message } from 'element-ui'

export default {
  data() {
    return {
      dialogVisible: false,
      isEdit: false,
      submitLoading: false
    }
  },
  methods: {
    openCreateForm(defaultData = {}) {
      this.isEdit = false
      this.submitLoading = false
      if (typeof this.resetFormData === 'function') {
        this.resetFormData(defaultData)
      }
      this.dialogVisible = true
    },

    openEditForm(row, mapToForm) {
      this.isEdit = true
      this.submitLoading = false
      if (typeof mapToForm === 'function') {
        mapToForm(row)
      }
      this.dialogVisible = true
    },

    closeDialog() {
      if (this.submitLoading) return
      this.dialogVisible = false
    },

    handleDialogClose(done) {
      if (this.submitLoading) return
      done()
    },

    async submitForm(apiCall, options = {}) {
      const {
        formRef = 'formRef',
        successMessage,
        onSuccess
      } = options

      const form = this.$refs[formRef]
      if (!form) return

      const valid = await new Promise(resolve => {
        form.validate(resolve)
      })

      if (!valid) return

      this.submitLoading = true
      try {
        await apiCall()
        Message.success(successMessage || (this.isEdit ? '更新成功' : '创建成功'))
        this.dialogVisible = false
        if (onSuccess) onSuccess()
      } catch (error) {
        const msg = error.response?.data?.message || error.message || '操作失败'
        Message.error(msg)
      } finally {
        this.submitLoading = false
      }
    },

    async confirmAndDelete(options = {}) {
      const {
        message,
        apiCall,
        successMessage = '删除成功',
        onSuccess
      } = options

      try {
        await this.$confirm(message, '删除确认', {
          confirmButtonText: '删除',
          cancelButtonText: '取消',
          type: 'warning'
        })

        await apiCall()
        Message.success(successMessage)
        if (onSuccess) onSuccess()
      } catch (action) {
        if (action !== 'cancel') {
          const msg = action.response?.data?.message || action.message || '删除失败'
          Message.error(msg)
        }
      }
    }
  }
}
