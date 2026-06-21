import Vue from 'vue'

Vue.config.productionTip = false

jest.mock('element-ui', () => ({}), { virtual: true })
jest.mock('element-ui/lib/theme-chalk/index.css', () => ({}), { virtual: true })

class LocalStorageMock {
  constructor() {
    this.store = {}
  }
  clear() {
    this.store = {}
  }
  getItem(key) {
    return this.store[key] || null
  }
  setItem(key, value) {
    this.store[key] = String(value)
  }
  removeItem(key) {
    delete this.store[key]
  }
}

global.localStorage = new LocalStorageMock()
