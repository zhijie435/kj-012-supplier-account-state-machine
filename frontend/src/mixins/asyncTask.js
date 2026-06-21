import { Message } from 'element-ui'

export default {
  data() {
    return {
      loading: false
    }
  },
  methods: {
    async withLoading(asyncFn, options = {}) {
      const {
        loadingKey = 'loading',
        onSuccess,
        successMessage,
        errorMessage,
        silent = false
      } = options

      this[loadingKey] = true
      try {
        const result = await asyncFn()
        if (successMessage) {
          Message.success(successMessage)
        }
        if (onSuccess) {
          onSuccess(result)
        }
        return result
      } catch (error) {
        if (!silent && errorMessage) {
          Message.error(errorMessage)
        } else if (!silent) {
          const msg = error.response?.data?.message || error.message || '操作失败'
          Message.error(msg)
        }
        throw error
      } finally {
        this[loadingKey] = false
      }
    },

    async safeCall(asyncFn, options = {}) {
      const { onSuccess, errorMessage, silent = false } = options
      try {
        const result = await asyncFn()
        if (onSuccess) {
          onSuccess(result)
        }
        return result
      } catch (error) {
        if (!silent) {
          const msg = errorMessage || error.response?.data?.message || error.message || '操作失败'
          Message.error(msg)
        }
        return null
      }
    },

    cleanParams(params) {
      const cleaned = {}
      Object.keys(params).forEach(key => {
        const val = params[key]
        if (val !== '' && val !== null && val !== undefined) {
          cleaned[key] = val
        }
      })
      return cleaned
    }
  }
}
