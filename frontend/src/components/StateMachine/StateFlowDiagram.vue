<template>
  <div class="state-flow-diagram">
    <svg
      :viewBox="`0 0 ${viewBoxWidth} ${viewBoxHeight}`"
      preserveAspectRatio="xMidYMid meet"
      class="diagram-svg"
    >
      <defs>
        <marker
          id="arrowhead"
          markerWidth="10"
          markerHeight="7"
          refX="9"
          refY="3.5"
          orient="auto"
        >
          <polygon points="0 0, 10 3.5, 0 7" fill="#c0c4cc" />
        </marker>
        <marker
          id="arrowhead-active"
          markerWidth="10"
          markerHeight="7"
          refX="9"
          refY="3.5"
          orient="auto"
        >
          <polygon points="0 0, 10 3.5, 0 7" fill="#409eff" />
        </marker>
      </defs>

      <g v-for="edge in visibleEdges" :key="`edge-${edge.from}-${edge.to}`">
        <path
          :d="getEdgePath(edge)"
          :stroke="getEdgeStroke(edge)"
          :stroke-width="getEdgeWidth(edge)"
          fill="none"
          :marker-end="getEdgeMarker(edge)"
          :class="{ 'edge-active': isEdgeActive(edge), 'edge-allowed': isEdgeAllowed(edge) }"
        />
        <text
          :x="getLabelX(edge)"
          :y="getLabelY(edge)"
          text-anchor="middle"
          class="edge-label"
          :class="{ 'edge-label-active': isEdgeActive(edge), 'edge-label-allowed': isEdgeAllowed(edge) }"
        >
          {{ edge.label }}
        </text>
      </g>

      <g v-for="node in nodes" :key="node.id" :transform="`translate(${node.x}, ${node.y})`">
        <rect
          :width="nodeWidth"
          :height="nodeHeight"
          :rx="6"
          :ry="6"
          :fill="getNodeFill(node)"
          :stroke="getNodeStroke(node)"
          :stroke-width="getNodeStrokeWidth(node)"
          class="node-rect"
          :class="{
            'node-current': node.id === currentStatus,
            'node-terminal': node.isTerminal,
            'node-reachable': isNodeReachable(node.id)
          }"
        />
        <text
          :x="nodeWidth / 2"
          :y="nodeHeight / 2 + 4"
          text-anchor="middle"
          class="node-label"
          :class="{ 'node-label-current': node.id === currentStatus }"
        >
          {{ node.label }}
        </text>
        <circle
          v-if="node.id === currentStatus"
          :cx="nodeWidth / 2"
          :cy="-6"
          r="5"
          fill="#67c23a"
          class="current-indicator"
        >
          <animate
            attributeName="r"
            values="5;7;5"
            dur="1.5s"
            repeatCount="indefinite"
          />
          <animate
            attributeName="opacity"
            values="1;0.6;1"
            dur="1.5s"
            repeatCount="indefinite"
          />
        </circle>
        <text
          v-if="node.isTerminal"
          :x="nodeWidth + 3"
          :y="4"
          text-anchor="start"
          class="terminal-badge"
        >
          终态
        </text>
      </g>
    </svg>

    <div class="legend">
      <div class="legend-item">
        <span class="legend-dot current"></span>
        <span>当前状态</span>
      </div>
      <div class="legend-item">
        <span class="legend-dot allowed"></span>
        <span>可转换路径</span>
      </div>
      <div class="legend-item">
        <span class="legend-dot other"></span>
        <span>其他状态</span>
      </div>
      <div class="legend-item">
        <span class="legend-dot terminal"></span>
        <span>终态</span>
      </div>
    </div>
  </div>
</template>

<script>
import { STATE_MACHINE_NODES, STATE_MACHINE_EDGES } from '@/utils/constants'

export default {
  name: 'StateFlowDiagram',
  props: {
    currentStatus: {
      type: String,
      required: true
    },
    allowedTransitions: {
      type: Array,
      default: () => []
    }
  },
  data() {
    return {
      nodes: STATE_MACHINE_NODES,
      edges: STATE_MACHINE_EDGES,
      nodeWidth: 85,
      nodeHeight: 40,
      viewBoxWidth: 670,
      viewBoxHeight: 360
    }
  },
  computed: {
    visibleEdges() {
      return this.edges
    },
    allowedTransitionValues() {
      return this.allowedTransitions.map(t => t.value)
    }
  },
  methods: {
    getNodeFill(node) {
      if (node.id === this.currentStatus) {
        return node.color + '20'
      }
      if (this.isNodeReachable(node.id)) {
        return node.color + '10'
      }
      return '#f5f7fa'
    },
    getNodeStroke(node) {
      if (node.id === this.currentStatus) {
        return node.color
      }
      if (this.isNodeReachable(node.id)) {
        return node.color
      }
      return '#e4e7ed'
    },
    getNodeStrokeWidth(node) {
      if (node.id === this.currentStatus) {
        return 2.5
      }
      return 1.5
    },
    isNodeReachable(nodeId) {
      if (nodeId === this.currentStatus) return true
      return this.allowedTransitionValues.includes(nodeId)
    },
    isEdgeActive(edge) {
      return edge.from === this.currentStatus && this.allowedTransitionValues.includes(edge.to)
    },
    isEdgeAllowed(edge) {
      return this.isEdgeActive(edge)
    },
    getEdgeStroke(edge) {
      if (this.isEdgeActive(edge)) {
        return '#409eff'
      }
      return '#e4e7ed'
    },
    getEdgeWidth(edge) {
      if (this.isEdgeActive(edge)) {
        return 2
      }
      return 1
    },
    getEdgeMarker(edge) {
      if (this.isEdgeActive(edge)) {
        return 'url(#arrowhead-active)'
      }
      return 'url(#arrowhead)'
    },
    getNodeCenter(nodeId) {
      const node = this.nodes.find(n => n.id === nodeId)
      if (!node) return { x: 0, y: 0 }
      return {
        x: node.x + this.nodeWidth / 2,
        y: node.y + this.nodeHeight / 2
      }
    },
    getEdgePath(edge) {
      const from = this.getNodeCenter(edge.from)
      const to = this.getNodeCenter(edge.to)

      const dx = to.x - from.x
      const dy = to.y - from.y
      const dist = Math.sqrt(dx * dx + dy * dy)
      if (dist === 0) return ''

      const nodeRadius = Math.min(this.nodeWidth, this.nodeHeight) / 2
      const offsetX = (dx / dist) * nodeRadius
      const offsetY = (dy / dist) * nodeRadius

      const startX = from.x + offsetX
      const startY = from.y + offsetY
      const endX = to.x - offsetX
      const endY = to.y - offsetY

      const midX = (startX + endX) / 2
      const midY = (startY + endY) / 2

      const perpX = -dy / dist
      const perpY = dx / dist
      const curveOffset = dist > 180 ? 25 : 18

      const ctrlX = midX + perpX * curveOffset
      const ctrlY = midY + perpY * curveOffset

      return `M ${startX} ${startY} Q ${ctrlX} ${ctrlY} ${endX} ${endY}`
    },
    getLabelX(edge) {
      const from = this.getNodeCenter(edge.from)
      const to = this.getNodeCenter(edge.to)
      return (from.x + to.x) / 2
    },
    getLabelY(edge) {
      const from = this.getNodeCenter(edge.from)
      const to = this.getNodeCenter(edge.to)
      const dx = to.x - from.x
      const dy = to.y - from.y
      const dist = Math.sqrt(dx * dx + dy * dy)
      if (dist === 0) return 0
      const perpX = -dy / dist
      const perpY = dx / dist
      const curveOffset = dist > 180 ? 40 : 30
      return (from.y + to.y) / 2 + perpY * curveOffset - 3
    }
  }
}
</script>

<style lang="scss" scoped>
.state-flow-diagram {
  background: #fff;
  border: 1px solid #e4e7ed;
  border-radius: 8px;
  padding: 16px;
  position: relative;
  width: 100%;

  .diagram-svg {
    display: block;
    width: 100%;
    height: auto;
    min-height: 300px;

    .node-rect {
      transition: all 0.3s ease;
      cursor: pointer;

      &:hover {
        filter: brightness(0.95);
      }

      &.node-current {
        filter: drop-shadow(0 0 6px rgba(103, 194, 58, 0.5));
      }
    }

    .node-label {
      font-size: 12px;
      fill: #606266;
      font-weight: 500;

      &.node-label-current {
        fill: #303133;
        font-weight: 600;
      }
    }

    .terminal-badge {
      font-size: 9px;
      fill: #909399;
    }

    .edge-active {
      filter: drop-shadow(0 0 3px rgba(64, 158, 255, 0.5));
    }

    .edge-label {
      font-size: 10px;
      fill: #909399;

      &.edge-label-active {
        fill: #409eff;
        font-weight: 600;
      }

      &.edge-label-allowed {
        fill: #409eff;
      }
    }

    .current-indicator {
      filter: drop-shadow(0 0 3px rgba(103, 194, 58, 0.8));
    }
  }

  .legend {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 16px;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #f0f2f5;

    .legend-item {
      display: flex;
      align-items: center;
      gap: 4px;
      font-size: 11px;
      color: #606266;

      .legend-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        border: 2px solid;
        flex-shrink: 0;

        &.current {
          background: rgba(103, 194, 58, 0.2);
          border-color: #67c23a;
        }

        &.allowed {
          background: rgba(64, 158, 255, 0.1);
          border-color: #409eff;
        }

        &.other {
          background: #f5f7fa;
          border-color: #e4e7ed;
        }

        &.terminal {
          background: rgba(144, 147, 153, 0.1);
          border-color: #909399;
        }
      }
    }
  }
}
</style>
