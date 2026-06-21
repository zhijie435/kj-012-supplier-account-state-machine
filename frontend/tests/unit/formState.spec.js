import Vue from 'vue'
import formState from '@/mixins/formState'
import { Message } from 'element-ui'

jest.mock('element-ui', () => ({
  Message: {
    success: jest.fn(),
    error: jest.fn(),
    warning: jest.fn()
  }
}))

function createComponent() {
  const vm = new Vue({
    mixins: [formState],
    data() {
      return {
        dialogVisible: false,
        isEdit: false,
        submitLoading: false,
        formData: {
          name: '',
          display_name: ''
        }
      }
    },
    methods: {
      resetFormData(defaultData) {
        this.formData = { ...this.formData, ...defaultData }
      }
    }
  })

  vm.$refs = {
    formRef: {
      validate: jest.fn(cb => cb(true))
    }
  }

  vm.$confirm = jest.fn().mockResolvedValue('confirm')

  return vm
}

describe('formState mixin', () => {
  beforeEach(() => {
    jest.clearAllMocks()
  })

  describe('openCreateForm', () => {
    it('sets isEdit to false and opens dialog', () => {
      const vm = createComponent()
      vm.isEdit = true

      vm.openCreateForm()

      expect(vm.isEdit).toBe(false)
      expect(vm.dialogVisible).toBe(true)
      expect(vm.submitLoading).toBe(false)
    })

    it('calls resetFormData with defaultData', () => {
      const vm = createComponent()

      vm.openCreateForm({ guard_name: 'supplier' })

      expect(vm.formData.guard_name).toBe('supplier')
    })

    it('does not call resetFormData when not defined', () => {
      const vm = new Vue({
        mixins: [formState],
        data() {
          return { dialogVisible: false, isEdit: false, submitLoading: false }
        }
      })

      expect(() => vm.openCreateForm()).not.toThrow()
      expect(vm.dialogVisible).toBe(true)
    })
  })

  describe('openEditForm', () => {
    it('sets isEdit to true and opens dialog', () => {
      const vm = createComponent()
      const row = { id: 1, name: 'admin', display_name: '管理员' }

      vm.openEditForm(row, (r) => {
        vm.formData = { ...vm.formData, id: r.id, name: r.name }
      })

      expect(vm.isEdit).toBe(true)
      expect(vm.dialogVisible).toBe(true)
      expect(vm.formData.id).toBe(1)
      expect(vm.formData.name).toBe('admin')
      expect(vm.submitLoading).toBe(false)
    })

    it('does not call mapToForm when not a function', () => {
      const vm = createComponent()

      vm.openEditForm({ id: 1 }, null)

      expect(vm.isEdit).toBe(true)
      expect(vm.dialogVisible).toBe(true)
    })
  })

  describe('closeDialog', () => {
    it('closes dialog when not submitting', () => {
      const vm = createComponent()
      vm.dialogVisible = true
      vm.submitLoading = false

      vm.closeDialog()

      expect(vm.dialogVisible).toBe(false)
    })

    it('does not close dialog when submitLoading', () => {
      const vm = createComponent()
      vm.dialogVisible = true
      vm.submitLoading = true

      vm.closeDialog()

      expect(vm.dialogVisible).toBe(true)
    })
  })

  describe('handleDialogClose', () => {
    it('calls done when not submitting', () => {
      const vm = createComponent()
      const done = jest.fn()

      vm.handleDialogClose(done)

      expect(done).toHaveBeenCalled()
    })

    it('does not call done when submitLoading', () => {
      const vm = createComponent()
      vm.submitLoading = true
      const done = jest.fn()

      vm.handleDialogClose(done)

      expect(done).not.toHaveBeenCalled()
    })
  })

  describe('submitForm', () => {
    it('returns early when form ref not found', async () => {
      const vm = createComponent()
      vm.$refs = {}
      const apiCall = jest.fn()

      await vm.submitForm(apiCall)

      expect(apiCall).not.toHaveBeenCalled()
    })

    it('returns early when validation fails', async () => {
      const vm = createComponent()
      vm.$refs.formRef.validate = jest.fn(cb => cb(false))
      const apiCall = jest.fn()

      await vm.submitForm(apiCall)

      expect(apiCall).not.toHaveBeenCalled()
    })

    it('calls apiCall and shows success message on create', async () => {
      const vm = createComponent()
      vm.isEdit = false
      const apiCall = jest.fn().mockResolvedValue({})

      await vm.submitForm(apiCall)

      expect(apiCall).toHaveBeenCalled()
      expect(Message.success).toHaveBeenCalledWith('创建成功')
      expect(vm.dialogVisible).toBe(false)
    })

    it('shows update success message on edit', async () => {
      const vm = createComponent()
      vm.isEdit = true
      const apiCall = jest.fn().mockResolvedValue({})

      await vm.submitForm(apiCall)

      expect(Message.success).toHaveBeenCalledWith('更新成功')
    })

    it('uses custom successMessage', async () => {
      const vm = createComponent()
      const apiCall = jest.fn().mockResolvedValue({})

      await vm.submitForm(apiCall, { successMessage: '保存成功' })

      expect(Message.success).toHaveBeenCalledWith('保存成功')
    })

    it('calls onSuccess callback', async () => {
      const vm = createComponent()
      const apiCall = jest.fn().mockResolvedValue({})
      const onSuccess = jest.fn()

      await vm.submitForm(apiCall, { onSuccess })

      expect(onSuccess).toHaveBeenCalled()
    })

    it('shows error message on API failure', async () => {
      const vm = createComponent()
      vm.dialogVisible = true
      const err = { response: { data: { message: '角色已存在' } } }
      const apiCall = jest.fn().mockRejectedValue(err)

      await vm.submitForm(apiCall)

      expect(Message.error).toHaveBeenCalledWith('角色已存在')
      expect(vm.dialogVisible).toBe(true)
    })

    it('shows error.message as fallback', async () => {
      const vm = createComponent()
      const apiCall = jest.fn().mockRejectedValue(new Error('网络错误'))

      await vm.submitForm(apiCall)

      expect(Message.error).toHaveBeenCalledWith('网络错误')
    })

    it('shows "操作失败" when no message available', async () => {
      const vm = createComponent()
      const apiCall = jest.fn().mockRejectedValue({})

      await vm.submitForm(apiCall)

      expect(Message.error).toHaveBeenCalledWith('操作失败')
    })

    it('resets submitLoading after success', async () => {
      const vm = createComponent()
      const apiCall = jest.fn().mockResolvedValue({})

      await vm.submitForm(apiCall)

      expect(vm.submitLoading).toBe(false)
    })

    it('resets submitLoading after failure', async () => {
      const vm = createComponent()
      const apiCall = jest.fn().mockRejectedValue(new Error('fail'))

      await vm.submitForm(apiCall)

      expect(vm.submitLoading).toBe(false)
    })

    it('uses custom formRef', async () => {
      const vm = createComponent()
      vm.$refs.customRef = { validate: jest.fn(cb => cb(true)) }
      const apiCall = jest.fn().mockResolvedValue({})

      await vm.submitForm(apiCall, { formRef: 'customRef' })

      expect(vm.$refs.customRef.validate).toHaveBeenCalled()
    })
  })

  describe('confirmAndDelete', () => {
    it('shows confirmation dialog', async () => {
      const vm = createComponent()
      const apiCall = jest.fn().mockResolvedValue({})

      await vm.confirmAndDelete({
        message: '确定删除？',
        apiCall
      })

      expect(vm.$confirm).toHaveBeenCalledWith('确定删除？', '删除确认', expect.any(Object))
    })

    it('calls apiCall after confirm and shows success', async () => {
      const vm = createComponent()
      const apiCall = jest.fn().mockResolvedValue({})

      await vm.confirmAndDelete({
        message: '确定删除？',
        apiCall,
        successMessage: '已删除'
      })

      expect(apiCall).toHaveBeenCalled()
      expect(Message.success).toHaveBeenCalledWith('已删除')
    })

    it('uses default success message', async () => {
      const vm = createComponent()
      const apiCall = jest.fn().mockResolvedValue({})

      await vm.confirmAndDelete({ message: 'ok?', apiCall })

      expect(Message.success).toHaveBeenCalledWith('删除成功')
    })

    it('calls onSuccess after delete', async () => {
      const vm = createComponent()
      const apiCall = jest.fn().mockResolvedValue({})
      const onSuccess = jest.fn()

      await vm.confirmAndDelete({ message: 'ok?', apiCall, onSuccess })

      expect(onSuccess).toHaveBeenCalled()
    })

    it('does not call apiCall when user cancels', async () => {
      const vm = createComponent()
      vm.$confirm = jest.fn().mockRejectedValue('cancel')
      const apiCall = jest.fn()

      await vm.confirmAndDelete({ message: 'ok?', apiCall })

      expect(apiCall).not.toHaveBeenCalled()
      expect(Message.error).not.toHaveBeenCalled()
    })

    it('shows error when apiCall fails', async () => {
      const vm = createComponent()
      const err = { response: { data: { message: '无法删除' } } }
      const apiCall = jest.fn().mockRejectedValue(err)

      await vm.confirmAndDelete({ message: 'ok?', apiCall })

      expect(Message.error).toHaveBeenCalledWith('无法删除')
    })

    it('shows error.message as fallback on apiCall failure', async () => {
      const vm = createComponent()
      const apiCall = jest.fn().mockRejectedValue(new Error('error'))

      await vm.confirmAndDelete({ message: 'ok?', apiCall })

      expect(Message.error).toHaveBeenCalledWith('error')
    })

    it('shows "删除失败" when no message on failure', async () => {
      const vm = createComponent()
      const apiCall = jest.fn().mockRejectedValue({})

      await vm.confirmAndDelete({ message: 'ok?', apiCall })

      expect(Message.error).toHaveBeenCalledWith('删除失败')
    })
  })
})
