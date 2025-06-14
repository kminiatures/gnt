<template>
  <div class="gantt-container">
    <GanttComponent
      ref="gantt"
      :dataSource="processedData"
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
      :allowFiltering="true"
      :allowReordering="true"
      :allowResizing="true"
      :highlightWeekends="true"
      :gridLines="'Both'"
      :splitterSettings="splitterSettings"
      :enableContextMenu="true"
      :enableVirtualization="false"
      :provide="{ gantt: ganttServices }"
    >
    </GanttComponent>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { GanttComponent } from '@syncfusion/ej2-vue-gantt'
import { Gantt } from '@syncfusion/ej2-gantt'
import { registerLicense } from '@syncfusion/ej2-base'

// Syncfusion license registration
const syncfusionLicense = import.meta.env.VITE_SYNCFUSION_LICENSE_KEY
if (syncfusionLicense) {
  registerLicense(syncfusionLicense)
}

// Import and register all Gantt services
import { 
  Edit, 
  Toolbar, 
  Selection, 
  Sort, 
  Reorder, 
  Resize, 
  Filter, 
  ExcelExport, 
  PdfExport, 
  DayMarkers,
  ContextMenu,
  CriticalPath,
  VirtualScroll
} from '@syncfusion/ej2-gantt'

// Register Gantt services globally
Gantt.Inject(
  Edit, 
  Toolbar, 
  Selection, 
  Sort, 
  Reorder, 
  Resize, 
  Filter, 
  ExcelExport, 
  PdfExport, 
  DayMarkers,
  ContextMenu,
  CriticalPath,
  VirtualScroll
)

// Define Gantt services for injection
const ganttServices = [
  Edit, 
  Toolbar, 
  Selection, 
  Sort, 
  Reorder, 
  Resize, 
  Filter, 
  ExcelExport, 
  PdfExport, 
  DayMarkers,
  ContextMenu,
  CriticalPath,
  VirtualScroll
]

// Props
const props = defineProps({
  data: {
    type: Array,
    default: () => []
  }
})

// データを処理してDateオブジェクトに変換
const processedData = ref([])

const processGanttData = (data) => {
  return data.map(item => {
    const processedItem = {
      ...item,
      StartDate: item.StartDate ? new Date(item.StartDate) : null,
      EndDate: item.EndDate ? new Date(item.EndDate) : null,
      Progress: item.Progress || 0
    }
    
    if (item.subtasks && item.subtasks.length > 0) {
      processedItem.subtasks = processGanttData(item.subtasks)
    }
    
    return processedItem
  })
}

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

const projectStartDate = ref(new Date('2024-01-01'))
const projectEndDate = ref(new Date('2024-12-31'))

// プロジェクトの日付範囲を動的に設定
const updateProjectDates = (data) => {
  let minDate = null
  let maxDate = null
  
  const findDates = (items) => {
    items.forEach(item => {
      if (item.StartDate) {
        const startDate = new Date(item.StartDate)
        if (!minDate || startDate < minDate) minDate = startDate
      }
      if (item.EndDate) {
        const endDate = new Date(item.EndDate)
        if (!maxDate || endDate > maxDate) maxDate = endDate
      }
      if (item.subtasks && item.subtasks.length > 0) {
        findDates(item.subtasks)
      }
    })
  }
  
  findDates(data)
  
  if (minDate) projectStartDate.value = minDate
  if (maxDate) projectEndDate.value = maxDate
}

const splitterSettings = {
  columnIndex: 3
}

// Day markers configuration (optional)
const dayWorkingTime = [
  { from: 8, to: 12 },
  { from: 13, to: 17 }
]

const weekWorkingDays = [1, 2, 3, 4, 5] // Monday to Friday

// Gantt modules are provided via the provide() function above

// プロパティの変更を監視してデータを更新
watch(() => props.data, (newData) => {
  processedData.value = processGanttData(newData)
  updateProjectDates(newData)
}, { immediate: true })

onMounted(() => {
  console.log('Gantt Chart component mounted')
  processedData.value = processGanttData(props.data)
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