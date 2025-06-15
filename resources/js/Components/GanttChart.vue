<template>
  <div class="gantt-container">
    <ejs-gantt
      ref="gantt"
      id="GanttContainer"
      :dataSource="processedData"
      :taskFields="taskFields"
      :editSettings="editSettings"
      height="500px"
      @taskbarEditing="onTaskbarEditing"
      @taskbarEdited="onTaskbarEdited"
      @actionComplete="onActionComplete"
    >
    </ejs-gantt>
  </div>
</template>

<script>
import { GanttComponent, Edit } from '@syncfusion/ej2-vue-gantt'
import { registerLicense } from '@syncfusion/ej2-base'

// Syncfusion license registration
const syncfusionLicense = import.meta.env.VITE_SYNCFUSION_LICENSE_KEY
if (syncfusionLicense) {
  registerLicense(syncfusionLicense)
  console.log('Syncfusion license registered')
} else {
  console.warn('No Syncfusion license found - some features may be limited')
}

export default {
  name: "GanttChart",
  components: {
    "ejs-gantt": GanttComponent
  },
  props: {
    data: {
      type: Array,
      default: () => []
    }
  },
  data() {
    return {
      processedData: [],
      taskFields: {
        id: 'TaskID',
        name: 'TaskName',
        startDate: 'StartDate',
        endDate: 'EndDate',
        duration: 'Duration',
        progress: 'Progress',
        dependency: 'Predecessor',
        child: 'subtasks',
        parentID: 'ParentID'
      },
      editSettings: {
        allowTaskbarEditing: true
      }
    }
  },
  provide: {
    gantt: [Edit]
  },
  watch: {
    data: {
      immediate: true,
      handler(newData) {
        console.log('Raw gantt data:', newData)
        this.processedData = this.processGanttData(newData)
        console.log('Processed gantt data:', this.processedData)
      }
    }
  },
  methods: {
    processGanttData(data) {
      return data.map(item => {
        const startDate = item.StartDate ? new Date(item.StartDate) : null
        const endDate = item.EndDate ? new Date(item.EndDate) : null
        
        let duration = 1
        if (startDate && endDate) {
          duration = Math.max(1, Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24)) + 1)
        } else if (item.Duration) {
          duration = Math.max(1, parseInt(item.Duration))
        }
        
        const processedItem = {
          TaskID: item.TaskID,
          TaskName: item.TaskName || 'Untitled Task',
          StartDate: startDate,
          EndDate: endDate,
          Duration: duration,
          Progress: Math.max(0, Math.min(100, parseInt(item.Progress) || 0)),
          Predecessor: item.Predecessor || null,
          ParentID: item.ParentID || null
        }
        
        if (item.subtasks && item.subtasks.length > 0) {
          processedItem.subtasks = this.processGanttData(item.subtasks)
        }
        
        return processedItem
      })
    },
    onTaskbarEditing(args) {
      console.log('Taskbar editing:', args)
    },
    onTaskbarEdited(args) {
      console.log('Taskbar edited:', args)
    },
    async onActionComplete(args) {
      console.log('Action complete:', args)
      console.log('Request type:', args.requestType)
      console.log('Task bar edit action:', args.taskBarEditAction)
      
      // タスクバーの編集（リサイズ、移動）時にAPIを呼び出す
      if (args.requestType === 'save' && (
        args.action === 'TaskbarEditing' || 
        args.taskBarEditAction === 'LeftResizing' ||
        args.taskBarEditAction === 'RightResizing' ||
        args.taskBarEditAction === 'ParentResizing' ||
        args.taskBarEditAction === 'ChildDrag'
      )) {
        const task = args.data
        console.log('Updating task dates:', task)
        
        try {
          const response = await fetch(`/api/tasks/${task.TaskID}/dates`, {
            method: 'PUT',
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
              start_date: task.StartDate?.toISOString().split('T')[0],
              end_date: task.EndDate?.toISOString().split('T')[0],
            }),
          })
          
          if (response.ok) {
            console.log('Task dates updated successfully')
          } else {
            console.error('Failed to update task dates:', response.status)
          }
        } catch (error) {
          console.error('Error updating task dates:', error)
        }
      }
    }
  },
  mounted() {
    console.log('Gantt Chart component mounted')
    
    if (!this.data || this.data.length === 0) {
      console.log('No data provided, using sample data')
      this.processedData = [
        {
          TaskID: 1,
          TaskName: 'サンプルタスク1',
          StartDate: new Date('2024-01-01'),
          EndDate: new Date('2024-01-05'),
          Duration: 5,
          Progress: 50
        },
        {
          TaskID: 2,
          TaskName: 'サンプルタスク2',
          StartDate: new Date('2024-01-06'),
          EndDate: new Date('2024-01-10'),
          Duration: 5,
          Progress: 25
        }
      ]
    }
  }
}
</script>

<style scoped>
.gantt-container {
  width: 100%;
  margin: 20px 0;
}

/* Syncfusionのスタイルをカスタマイズ */
/*
:deep(.e-gantt) {
  font-family: 'Figtree', sans-serif;
}

:deep(.e-gantt .e-header-cell-div) {
  font-weight: 600;
}

:deep(.e-gantt .e-timeline-header-container) {
  background: #f8fafc;
}
*/

/* タスクバーのドラッグ可能スタイル */
/*
:deep(.e-gantt .e-taskbar-main) {
  cursor: move;
  transition: box-shadow 0.2s ease;
  pointer-events: auto !important;
}

:deep(.e-gantt .e-taskbar-main:hover) {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}
*/

/* チャート領域のポインターイベントを有効化 */
/*
:deep(.e-gantt .e-chart-scroll-container) {
  pointer-events: auto !important;
}

:deep(.e-gantt .e-gantt-chart) {
  pointer-events: auto !important;
}

/* タスクバー要素のポインターイベント */
/*
:deep(.e-gantt .e-gantt-child-taskbar),
:deep(.e-gantt .e-gantt-parent-taskbar) {
  pointer-events: auto !important;
}

/* リサイズハンドルのスタイル */
/*
:deep(.e-gantt .e-taskbar-left-resizer),
:deep(.e-gantt .e-taskbar-right-resizer) {
  background: #2563eb !important;
  width: 8px !important;
  height: 100% !important;
  cursor: ew-resize !important;
  opacity: 0.8;
  border-radius: 2px;
  transition: all 0.2s ease;
}

:deep(.e-gantt .e-taskbar-left-resizer:hover),
:deep(.e-gantt .e-taskbar-right-resizer:hover) {
  background: #1d4ed8 !important;
  opacity: 1;
  width: 10px !important;
}

/* タスクバー全体のスタイル */
/*
:deep(.e-gantt .e-gantt-child-taskbar),
:deep(.e-gantt .e-gantt-parent-taskbar) {
  border-radius: 4px;
  border: 1px solid #e5e7eb;
}

/* タスクバーにホバー時にリサイズハンドルを表示 */
/*
:deep(.e-gantt .e-taskbar-main:hover .e-taskbar-left-resizer),
:deep(.e-gantt .e-taskbar-main:hover .e-taskbar-right-resizer) {
  opacity: 1;
  visibility: visible;
}

/* プログレスバーのリサイズハンドル */
/*
:deep(.e-gantt .e-child-progress-resizer) {
  background: #10b981;
  cursor: ew-resize;
}

:deep(.e-gantt .e-child-progress-resizer:hover) {
  background: #059669;
}

/* ドラッグ中のフィードバック */
/*
:deep(.e-gantt .e-drag-item) {
  opacity: 0.8;
  transform: scale(1.05);
}
*/
</style>
