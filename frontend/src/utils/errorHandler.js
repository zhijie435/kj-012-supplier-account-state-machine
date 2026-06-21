import { MessageBox, Message } from 'element-ui'
import store from '@/store'
import router from '@/router'

const AUTH_CODES = [401, 40101, 40102]
const AUTH_STATUSES = [401]

let isRefreshing = false
let refreshSubscribers = []

function subscribeTokenRefresh(cb) {
  refreshSubscribers.push(cb)
}

function onTokenRefreshed() {
  refreshSubscribers.forEach(cb => cb())
  refreshSubscribers = []
}

function handleAuthExpired() {
  if (isRefreshing) {
    return new Promise(resolve => {
      subscribeTokenRefresh(resolve)
    })
  }

  isRefreshing = true

  return MessageBox.confirm('登录状态已过期，请重新登录', '提示', {
    confirmButtonText: '重新登录',
    cancelButtonText: '取消',
    type: 'warning',
    closeOnClickModal: false
  }).then(() => {
    store.dispatch('logout')
    router.push('/login')
  }).catch(() => {}).finally(() => {
    isRefreshing = false
    onTokenRefreshed()
  })
}

function extractErrorMessage(error) {
  if (error.response?.data?.message) return error.response.data.message
  if (error.response?.data?.msg) return error.response.data.msg
  if (error.message) return error.message
  return '网络错误'
}

function isAuthError(error) {
  if (error.response && AUTH_STATUSES.includes(error.response.status)) return true
  if (error.code && AUTH_CODES.includes(error.code)) return true
  return false
}

function isBusinessError(error) {
  return !error.response && error.code !== undefined
}

export function handleError(error, options = {}) {
  const { silent = false, showMessage = true } = options

  if (isAuthError(error)) {
    if (!silent) handleAuthExpired()
    return Promise.reject(error)
  }

  if (showMessage && !silent) {
    const message = isBusinessError(error)
      ? error.message || '操作失败'
      : extractErrorMessage(error)
    Message({
      message,
      type: 'error',
      duration: 3000
    })
  }

  return Promise.reject(error)
}

export function handleBusinessError(res) {
  if (res.code !== undefined && res.code !== 0 && res.code !== 200) {
    const error = new Error(res.message || '请求失败')
    error.code = res.code
    error.isBusiness = true

    if (AUTH_CODES.includes(res.code)) {
      error.isAuth = true
    }

    return error
  }

  return null
}

export { isAuthError, isBusinessError, extractErrorMessage }
