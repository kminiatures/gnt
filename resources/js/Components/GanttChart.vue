<template>
  <div class="gantt-container">
    <GanttComponent
      ref="gantt"
      :dataSource="data"
      :taskFields="taskFields"
      :editSettings="editSettings"
      :toolbar="toolbar"
      :columns="columns"
      :timelineSettings="timelineSettings"
      :projectStartDate="projectStartDate"
      :projectEndDate="projectEndDate"
      height="500px"
      :allowSelection="true"
      :allowSorting="true"
      :allowReordering="true"
      :allowResizing="true"
      :highlightWeekends="true"
      :gridLines="'Both'"
      :splitterSettings="splitterSettings"
      :provide="ganttServices"
    >
    </GanttComponent>
  </div>
</template>

<script setup>
import { ref, onMounted, provide } from 'vue'
import { GanttComponent, Edit, Toolbar, Selection, Sort, Reorder, Resize } from '@syncfusion/ej2-vue-gantt'
import { registerLicense } from '@syncfusion/ej2-base'

// Syncfusion license registration
const syncfusionLicense = import.meta.env.VITE_SYNCFUSION_LICENSE_KEY
if (syncfusionLicense) {
  registerLicense(syncfusionLicense)
}

// Define Gantt services for injection
const ganttServices = {
  gantt: [Edit, Toolbar, Selection, Sort, Reorder, Resize]
}

// Props
defineProps({
  data: {
    type: Array,
    default: () => []
  }
})

// Gantt configuration
const gantt = ref(null)

const taskFields = {
  id: 'TaskID',
  name: 'TaskName',
  startDate: 'StartDate',
  endDate: 'EndDate',
  duration: 'Duration',
  progress: 'Progress',
  dependency: 'Predecessor',
  child: 'subtasks'
}

const editSettings = {
  allowAdding: true,
  allowEditing: true,
  allowDeleting: true,
  allowTaskbarEditing: true,
  showDeleteConfirmDialog: true
}

const toolbar = [
  'Add', 'Edit', 'Update', 'Delete', 'Cancel', 'ExpandAll', 'CollapseAll', 'Search',
  'PrevTimeSpan', 'NextTimeSpan', 'ZoomIn', 'ZoomOut', 'ZoomToFit'
]

const columns = [
  { field: 'TaskID', visible: false },
  { field: 'TaskName', headerText: 'タスク名', width: '250' },
  { field: 'StartDate', headerText: '開始日', width: '100' },
  { field: 'EndDate', headerText: '終了日', width: '100' },
  { field: 'Duration', headerText: '期間', width: '80' },
  { field: 'Progress', headerText: '進捗', width: '80' }
]

const timelineSettings = {
  topTier: {
    unit: 'Month',
    format: 'yyyy年MM月'
  },
  bottomTier: {
    unit: 'Day',
    format: 'M/d'
  }
}

const projectStartDate = new Date('2024-01-01')
const projectEndDate = new Date('2024-12-31')

const splitterSettings = {
  columnIndex: 3
}

// Gantt modules are provided via the provide() function above

onMounted(() => {
  console.log('Gantt Chart component mounted')
})
</script>

<style scoped>
.gantt-container {
  width: 100%;
  margin: 20px 0;
}

/* Syncfusionのスタイルをカスタマイズ */
:deep(.e-gantt) {
  font-family: 'Figtree', sans-serif;
}

:deep(.e-gantt .e-header-cell-div) {
  font-weight: 600;
}

:deep(.e-gantt .e-timeline-header-container) {
  background: #f8fafc;
}
</style>