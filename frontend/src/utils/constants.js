export const SUPPLIER_ACCOUNT_STATUS = {
  PENDING: 'pending',
  VERIFYING: 'verifying',
  ACTIVE: 'active',
  SUSPENDED: 'suspended',
  REJECTED: 'rejected',
  CANCELLED: 'cancelled'
}

export const SUPPLIER_STATUS_OPTIONS = [
  { value: SUPPLIER_ACCOUNT_STATUS.PENDING, label: '待审核', color: 'warning' },
  { value: SUPPLIER_ACCOUNT_STATUS.VERIFYING, label: '审核中', color: 'info' },
  { value: SUPPLIER_ACCOUNT_STATUS.ACTIVE, label: '已激活', color: 'success' },
  { value: SUPPLIER_ACCOUNT_STATUS.SUSPENDED, label: '已暂停', color: 'danger' },
  { value: SUPPLIER_ACCOUNT_STATUS.REJECTED, label: '已拒绝', color: 'danger' },
  { value: SUPPLIER_ACCOUNT_STATUS.CANCELLED, label: '已注销', color: 'secondary' }
]

export const SUPPLIER_STATUS_MAP = {
  [SUPPLIER_ACCOUNT_STATUS.PENDING]: { label: '待审核', color: 'warning' },
  [SUPPLIER_ACCOUNT_STATUS.VERIFYING]: { label: '审核中', color: 'info' },
  [SUPPLIER_ACCOUNT_STATUS.ACTIVE]: { label: '已激活', color: 'success' },
  [SUPPLIER_ACCOUNT_STATUS.SUSPENDED]: { label: '已暂停', color: 'danger' },
  [SUPPLIER_ACCOUNT_STATUS.REJECTED]: { label: '已拒绝', color: 'danger' },
  [SUPPLIER_ACCOUNT_STATUS.CANCELLED]: { label: '已注销', color: 'secondary' }
}

export const STATE_MACHINE_NODES = [
  { id: SUPPLIER_ACCOUNT_STATUS.PENDING, label: '待审核', x: 30, y: 150, color: '#e6a23c', isTerminal: false },
  { id: SUPPLIER_ACCOUNT_STATUS.VERIFYING, label: '审核中', x: 190, y: 60, color: '#409eff', isTerminal: false },
  { id: SUPPLIER_ACCOUNT_STATUS.ACTIVE, label: '已激活', x: 360, y: 60, color: '#67c23a', isTerminal: false },
  { id: SUPPLIER_ACCOUNT_STATUS.SUSPENDED, label: '已暂停', x: 360, y: 240, color: '#f56c6c', isTerminal: false },
  { id: SUPPLIER_ACCOUNT_STATUS.REJECTED, label: '已拒绝', x: 190, y: 240, color: '#f56c6c', isTerminal: true },
  { id: SUPPLIER_ACCOUNT_STATUS.CANCELLED, label: '已注销', x: 530, y: 150, color: '#909399', isTerminal: true }
]

export const STATE_MACHINE_EDGES = [
  { from: SUPPLIER_ACCOUNT_STATUS.PENDING, to: SUPPLIER_ACCOUNT_STATUS.VERIFYING, label: '提交审核' },
  { from: SUPPLIER_ACCOUNT_STATUS.PENDING, to: SUPPLIER_ACCOUNT_STATUS.REJECTED, label: '拒绝' },
  { from: SUPPLIER_ACCOUNT_STATUS.PENDING, to: SUPPLIER_ACCOUNT_STATUS.CANCELLED, label: '注销' },
  { from: SUPPLIER_ACCOUNT_STATUS.VERIFYING, to: SUPPLIER_ACCOUNT_STATUS.ACTIVE, label: '激活' },
  { from: SUPPLIER_ACCOUNT_STATUS.VERIFYING, to: SUPPLIER_ACCOUNT_STATUS.REJECTED, label: '拒绝' },
  { from: SUPPLIER_ACCOUNT_STATUS.VERIFYING, to: SUPPLIER_ACCOUNT_STATUS.SUSPENDED, label: '暂停' },
  { from: SUPPLIER_ACCOUNT_STATUS.VERIFYING, to: SUPPLIER_ACCOUNT_STATUS.PENDING, label: '退回' },
  { from: SUPPLIER_ACCOUNT_STATUS.ACTIVE, to: SUPPLIER_ACCOUNT_STATUS.SUSPENDED, label: '暂停' },
  { from: SUPPLIER_ACCOUNT_STATUS.ACTIVE, to: SUPPLIER_ACCOUNT_STATUS.CANCELLED, label: '注销' },
  { from: SUPPLIER_ACCOUNT_STATUS.SUSPENDED, to: SUPPLIER_ACCOUNT_STATUS.ACTIVE, label: '恢复' },
  { from: SUPPLIER_ACCOUNT_STATUS.SUSPENDED, to: SUPPLIER_ACCOUNT_STATUS.CANCELLED, label: '注销' }
]

export const TERMINAL_STATUSES = [
  SUPPLIER_ACCOUNT_STATUS.REJECTED,
  SUPPLIER_ACCOUNT_STATUS.CANCELLED
]

export function getStatusLabel(status) {
  return SUPPLIER_STATUS_MAP[status]?.label || status
}

export function getStatusColor(status) {
  return SUPPLIER_STATUS_MAP[status]?.color || 'info'
}

export function isTerminalStatus(status) {
  return TERMINAL_STATUSES.includes(status)
}
