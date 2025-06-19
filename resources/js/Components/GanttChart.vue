<template>
  <div 
    ref="ganttContainer"
    :class="[
      'gantt-container',
      {
        'fullscreen-mode': isFullscreen
      }
    ]"
  >
    <!-- スケール変更コントロール -->
    <div class="scale-controls mb-4 p-3 bg-gray-50 rounded-lg border">
      <div class="flex items-center space-x-4">
        <label class="text-sm font-medium text-gray-700">表示スケール:</label>
        <select 
          v-model="selectedScale" 
          @change="changeTimelineScale"
          class="px-3 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="day">日単位</option>
          <option value="week">週単位</option>
          <option value="month">月単位</option>
          <option value="quarter">四半期単位</option>
          <option value="year">年単位</option>
        </select>
        
        <label class="text-sm font-medium text-gray-700">ズーム:</label>
        <select 
          v-model="selectedZoom" 
          @change="changeZoomLevel"
          class="px-3 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="50">50%</option>
          <option value="75">75%</option>
          <option value="100">100%</option>
          <option value="125">125%</option>
          <option value="150">150%</option>
          <option value="200">200%</option>
        </select>
        
        <button 
          @click="fitToScreen"
          class="px-3 py-1 bg-blue-500 text-white rounded-md text-sm hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          画面に合わせる
        </button>
        
        <button 
          @click="toggleFullscreen"
          class="px-3 py-1 bg-gray-500 text-white rounded-md text-sm hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500"
        >
          {{ isFullscreen ? '通常表示' : 'フルスクリーン' }}
        </button>
      </div>
      
      <!-- ラベル表示設定 -->
      <div class="flex items-center space-x-4 mt-3 pt-3 border-t border-gray-200">
        <label class="text-sm font-medium text-gray-700">バーラベル:</label>
        
        <div class="flex items-center space-x-2">
          <label class="text-xs text-gray-600">左側:</label>
          <select 
            v-model="labelSettings.leftLabel"
            class="px-2 py-1 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500"
          >
            <option value="">なし</option>
            <option value="TaskName">タスク名</option>
            <option value="StartDate">開始日</option>
            <option value="StartDateFormatted">開始日(MM/dd)</option>
            <option value="Duration">期間</option>
            <option value="Progress">進捗</option>
          </select>
        </div>
        
        <div class="flex items-center space-x-2">
          <label class="text-xs text-gray-600">バー内:</label>
          <select 
            v-model="labelSettings.taskLabel"
            class="px-2 py-1 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500"
          >
            <option value="">なし</option>
            <option value="TaskName">タスク名</option>
            <option value="Progress">進捗</option>
            <option value="Duration">期間</option>
          </select>
        </div>
        
        <div class="flex items-center space-x-2">
          <label class="text-xs text-gray-600">右側:</label>
          <select 
            v-model="labelSettings.rightLabel"
            class="px-2 py-1 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500"
          >
            <option value="">なし</option>
            <option value="TaskName">タスク名</option>
            <option value="EndDate">終了日</option>
            <option value="EndDateFormatted">終了日(MM/dd)</option>
            <option value="Duration">期間</option>
            <option value="Progress">進捗</option>
          </select>
        </div>
      </div>
    </div>
    
    <ejs-gantt
      ref="gantt"
      id="GanttContainer"
      :dataSource="processedData"
      :taskFields="taskFields"
      :editSettings="editSettings"
      :toolbar="toolbar"
      :columns="columns"
      :timelineSettings="timelineSettings"
      :labelSettings="labelSettings"
      :allowRowDragAndDrop="true"
      :allowSelection="true"
      :allowSorting="true"
      :allowReordering="true"
      :enableCollapseAll="true"
      :collapseAllParentTasks="false"
      :treeColumnIndex="1"
      :showColumnMenu="false"
      :allowFiltering="false"
      :rowHeight="36"
      :enableVirtualization="false"
      :projectStartDate="null"
      :projectEndDate="null"
      :dateFormat="'yyyy-MM-dd'"
      :durationUnit="'Day'"
      :includeWeekend="true"
      :enableContextMenu="true"
      :holidays="weekendHolidays"
      height="500px"
      @taskbarEditing="onTaskbarEditing"
      @taskbarEdited="onTaskbarEdited"
      @actionComplete="onActionComplete"
      @actionBegin="onActionBegin"
      @rowDrop="onRowDrop"
      @queryTaskbarInfo="onQueryTaskbarInfo"
    >
    </ejs-gantt>
  </div>
</template>

<style scoped>
/* ガントチャート階層表示の改善 */
:deep(.e-gantt .e-gridheader .e-table .e-columnheader:nth-child(2)) {
  padding-left: 8px;
}

/* 親タスクの強調表示 */
:deep(.e-gantt .e-gridcontent .e-table .e-row[aria-expanded="true"] .e-rowcell:nth-child(2)) {
  font-weight: 600;
}

/* 子タスクのインデント */
:deep(.e-gantt .e-gridcontent .e-table .e-row .e-rowcell .e-treecell) {
  padding-left: 4px;
}

/* 展開/折り畳み三角のスタイル */
:deep(.e-gantt .e-treegridexpand),
:deep(.e-gantt .e-treegridcollapse) {
  cursor: pointer;
  font-size: 12px;
  color: #666;
}

:deep(.e-gantt .e-treegridexpand:hover),
:deep(.e-gantt .e-treegridcollapse:hover) {
  color: #333;
}

/* 週末holidays（土日）のスタイル */
:deep(.e-gantt .weekend-saturday) {
  background-color: #f0f8ff !important; /* 薄い青色 */
}

:deep(.e-gantt .weekend-sunday) {
  background-color: #ffe6e6 !important; /* 薄い赤色 */
}
</style>

<script>
import { GanttComponent, Edit, Toolbar, Selection, RowDD, Sort, Reorder, ContextMenu, DayMarkers, Resize } from '@syncfusion/ej2-vue-gantt'
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
    },
    projectId: {
      type: [Number, String],
      required: false
    }
  },
  data() {
    return {
      processedData: [],
      saveTimeout: null,
      isFullscreen: false,
      pendingDropOperation: null,
      isCreatingTask: false,
      weekendHolidays: [], // 週末holidays用配列
      projectSettings: {
        dateFormat: 'yyyy-MM-dd',
        durationUnit: 'Day'
      },
      taskFields: {
        id: 'TaskID',
        name: 'TaskName',
        startDate: 'StartDate',
        endDate: 'EndDate',
        duration: 'Duration',
        progress: 'Progress',
        dependency: 'Predecessor',
        child: 'subtasks',
        parentID: 'ParentID',
        startDateFormatted: 'StartDateFormatted',
        endDateFormatted: 'EndDateFormatted',
        milestone: 'Milestone'
      },
      editSettings: {
        allowTaskbarEditing: true,
        allowAdding: true,
        allowEditing: true,
        allowDeleting: true,
        allowDragAndDrop: true,
        allowResizing: true,
        mode: 'Auto'
      },
      toolbar: ['Add', 'Edit', 'Update', 'Delete', 'Cancel', 'ExpandAll', 'CollapseAll', 'ZoomIn', 'ZoomOut', 'ZoomToFit', 'PrevTimeSpan', 'NextTimeSpan'],
      columns: [
        { field: 'TaskID', visible: false },
        { 
          field: 'TaskName', 
          headerText: 'タスク名', 
          width: '280',
          allowSorting: true,
          allowReordering: false,
          allowEditing: true
        },
        { 
          field: 'StartDate', 
          headerText: '開始日', 
          width: '100',
          format: { type: 'date', format: 'MM/dd' },
          allowEditing: true
        },
        { 
          field: 'EndDate', 
          headerText: '終了日', 
          width: '100',
          format: { type: 'date', format: 'MM/dd' },
          allowEditing: true
        },
        { 
          field: 'Duration', 
          headerText: '期間', 
          width: '80',
          allowEditing: true
        },
        { 
          field: 'Progress', 
          headerText: '進捗', 
          width: '80',
          allowEditing: true
        },
        { field: 'StartDateFormatted', visible: false },
        { field: 'EndDateFormatted', visible: false }
      ],
      timelineSettings: {
        topTier: {
          unit: 'Month',
          format: 'yy/MM'
        },
        bottomTier: {
          unit: 'Day',
          format: 'MM/dd'
        },
        timelineUnitSize: 60
      },
      labelSettings: {
        leftLabel: 'TaskName',
        rightLabel: 'EndDateFormatted',
        taskLabel: 'Progress'
      },
      selectedScale: 'day',
      selectedZoom: '100',
      scalePresets: {
        day: {
          topTier: { unit: 'Month', format: 'yy/MM' },
          bottomTier: { unit: 'Day', format: 'MM/dd' },
          timelineUnitSize: 60
        },
        week: {
          topTier: { unit: 'Month', format: 'yy/MM' },
          bottomTier: { unit: 'Week', format: 'MM/dd' },
          timelineUnitSize: 80
        },
        month: {
          topTier: { unit: 'Year', format: 'yyyy' },
          bottomTier: { unit: 'Month', format: 'yy/MM' },
          timelineUnitSize: 100
        },
        quarter: {
          topTier: { unit: 'Year', format: 'yyyy' },
          bottomTier: { unit: 'Quarter', format: 'Q' },
          timelineUnitSize: 120
        },
        year: {
          topTier: { unit: 'None' },
          bottomTier: { unit: 'Year', format: 'yyyy' },
          timelineUnitSize: 150
        }
      }
    }
  },
  provide: {
    gantt: [Edit, Toolbar, Selection, RowDD, Sort, Reorder, ContextMenu, DayMarkers, Resize]
  },
  watch: {
    data: {
      immediate: true,
      handler(newData) {
        if (newData && newData.length > 0) {
          this.processedData = this.buildHierarchicalGanttData(newData)
        }
      }
    },
    labelSettings: {
      deep: true,
      handler(newLabelSettings) {
        // ラベル設定変更時にもデータを再処理
        if (this.data && this.data.length > 0) {
          this.processedData = this.buildHierarchicalGanttData(this.data)
        }
        
        this.updateLabelsPreservingZoom()
        this.saveUserOptions()
      }
    }
  },
  methods: {
    // 階層データを正しくSyncfusion用のフラット構造に変換
    buildHierarchicalGanttData(data) {
      const flatData = []
      
      const processTask = (item, parentId = null) => {
        const startDate = item.StartDate ? new Date(item.StartDate) : null
        const endDate = item.EndDate ? new Date(item.EndDate) : null
        
        let duration = 1
        if (startDate && endDate) {
          const diffTime = endDate.getTime() - startDate.getTime()
          const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
          duration = Math.max(1, diffDays + 1)
        } else if (item.Duration) {
          duration = Math.max(1, parseInt(item.Duration))
        }
        
        // 子タスクの場合は「+ 」プレフィックスを追加
        const taskName = item.TaskName || 'Untitled Task'
        const displayTaskName = parentId ? `+ ${taskName}` : taskName

        const processedItem = {
          TaskID: item.TaskID,
          TaskName: displayTaskName,
          StartDate: startDate,
          EndDate: endDate,
          Duration: duration,
          Progress: Math.max(0, Math.min(100, parseInt(item.Progress) || 0)),
          Predecessor: item.Predecessor || null,
          ParentID: parentId,
          sort_order: item.sort_order || 0,
          StartDateFormatted: startDate ? 
            `${String(startDate.getMonth() + 1).padStart(2, '0')}/${String(startDate.getDate()).padStart(2, '0')}` : '',
          EndDateFormatted: endDate ? 
            `${String(endDate.getMonth() + 1).padStart(2, '0')}/${String(endDate.getDate()).padStart(2, '0')}` : '',
          Milestone: item.Milestone || item.is_milestone || false
        }

        // フラットリストに追加
        flatData.push(processedItem)

        // 子タスクがある場合は再帰的に処理
        if (item.subtasks && item.subtasks.length > 0) {
          // sort_orderでソート
          const sortedSubtasks = [...item.subtasks].sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0))
          sortedSubtasks.forEach(child => {
            processTask(child, item.TaskID)
          })
        }
      }
      
      // ルートレベルのタスクをsort_orderでソート
      const sortedRootTasks = [...data].sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0))
      sortedRootTasks.forEach(task => {
        processTask(task)
      })
      
      return flatData
    },
    
    processGanttData(data, parentId = null) {
      return data.map(item => {
        const startDate = item.StartDate ? new Date(item.StartDate) : null
        const endDate = item.EndDate ? new Date(item.EndDate) : null
        
        let duration = 1
        if (startDate && endDate) {
          // 日付の差を正確に計算（ミリ秒単位での計算を避ける）
          const diffTime = endDate.getTime() - startDate.getTime()
          const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
          duration = Math.max(1, diffDays + 1) // +1 for inclusive end date
        } else if (item.Duration) {
          duration = Math.max(1, parseInt(item.Duration))
        }
        
        // 子タスクの場合は「+ 」プレフィックスを追加
        const taskName = item.TaskName || 'Untitled Task'
        const displayTaskName = parentId ? `+ ${taskName}` : taskName

        const processedItem = {
          TaskID: item.TaskID,
          TaskName: displayTaskName,
          StartDate: startDate,
          EndDate: endDate,
          Duration: duration,
          Progress: Math.max(0, Math.min(100, parseInt(item.Progress) || 0)),
          Predecessor: item.Predecessor || null,
          ParentID: parentId,
          sort_order: item.sort_order || 0,
          // フォーマット済み日付フィールドを追加
          StartDateFormatted: startDate ? 
            `${String(startDate.getMonth() + 1).padStart(2, '0')}/${String(startDate.getDate()).padStart(2, '0')}` : '',
          EndDateFormatted: endDate ? 
            `${String(endDate.getMonth() + 1).padStart(2, '0')}/${String(endDate.getDate()).padStart(2, '0')}` : '',
          Milestone: item.Milestone || item.is_milestone || false
        }
        
        if (item.subtasks && item.subtasks.length > 0) {
          processedItem.subtasks = this.processGanttData(item.subtasks, item.TaskID)
        }
        
        return processedItem
      })
    },
    onTaskbarEditing(args) {
    },
    onTaskbarEdited(args) {
    },
    onActionBegin(args) {
      // console.log('ActionBegin:', args.requestType, args)
      
      // タスク追加の場合、重複を防ぐためにキャンセルして独自処理
      if (args.requestType === 'beforeAdd') {
        args.cancel = true
        if (!this.isCreatingTask) {
          this.isCreatingTask = true
          this.createTask(args.data)
        }
      }
      
      // タスク削除の場合
      if (args.requestType === 'beforeDelete') {
        args.cancel = true
        this.deleteTask(args.data[0])
      }
      
      // マイルストーン変換やその他の保存処理
      if (args.requestType === 'beforeSave') {
        this.handleTaskSave(args)
      }
    },
    async onActionComplete(args) {
      // console.log('ActionComplete:', args.requestType, args)
      
      if (args.requestType === 'rowDropped') {
        // console.log('Row drop completed:', args)
        
        // pendingDropOperationがある場合のみ処理
        if (this.pendingDropOperation) {
          const operation = this.pendingDropOperation
          
          // Processing pending drop operation
          
          // API呼び出しでタスクの情報を更新
          await this.updateTaskFromDrop(operation)
          
          // 処理完了後にpendingOperationをクリア
          this.pendingDropOperation = null
        }
      }
      
      // ツールバーのズーム操作後にドラッグ機能のイベントハンドラーを再アタッチ
      if (args.requestType === 'zoomIn' || args.requestType === 'zoomOut' || args.requestType === 'zoomToFit') {
        this.$nextTick(() => {
          const ganttInstance = this.$refs.gantt
          if (ganttInstance && ganttInstance.ej2Instances) {
            // 軽量な方法でドラッグ機能を再初期化
            this.initializeDragFeature(ganttInstance.ej2Instances)
          }
        })
      }
      
      // インライン編集（セル編集）時にDBに保存
      if (args.requestType === 'save' && args.action === 'CellEditing') {
        // console.log('Cell editing completed:', args)
        const task = args.data
        await this.updateTaskFromInlineEdit(task)
      }
      
      // タスクバーの編集（リサイズ、移動）時にAPIを呼び出す
      if (args.requestType === 'save' && (
        args.action === 'TaskbarEditing' || 
        args.taskBarEditAction === 'LeftResizing' ||
        args.taskBarEditAction === 'RightResizing' ||
        args.taskBarEditAction === 'ParentResizing' ||
        args.taskBarEditAction === 'ChildDrag'
      )) {
        const task = args.data
        
        // 終了日のドラッグかどうかを判定
        const isEndDateResize = args.taskBarEditAction === 'RightResizing'
        
        // API呼び出しを先に行い、成功後にラベルを更新
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
              start_date: task.StartDate ? this.formatDateToLocal(task.StartDate) : null,
              end_date: task.EndDate ? this.formatDateToLocal(task.EndDate) : null,
            }),
          })
          
          if (response.ok) {
            const result = await response.json()
            
            // API成功後に子タスクのラベルを更新
            this.updateTaskDateLabels(task)
            
            // 終了日のドラッグの場合、特別な処理でバー位置を保持
            if (isEndDateResize) {
              
              // processedData内のタスクを即座に更新
              this.updateTaskInProcessedData({
                TaskID: task.TaskID,
                StartDate: task.StartDate,
                EndDate: task.EndDate,
                Duration: task.Duration,
                StartDateFormatted: task.StartDate ? 
                  `${String(task.StartDate.getMonth() + 1).padStart(2, '0')}/${String(task.StartDate.getDate()).padStart(2, '0')}` : '',
                EndDateFormatted: task.EndDate ? 
                  `${String(task.EndDate.getMonth() + 1).padStart(2, '0')}/${String(task.EndDate.getDate()).padStart(2, '0')}` : ''
              })
            }
            
            // 更新された親タスクがある場合のみ、スムーズに部分更新
            if (result.updated_parents && result.updated_parents.length > 0) {
              
              // 終了日のドラッグの場合はより慎重に親タスクを更新
              if (isEndDateResize) {
                // 終了日ドラッグ時は親タスクの更新を更に遅延
                setTimeout(() => {
                  this.updateParentTasksFromAPI(result.updated_parents, true)
                }, 500)
              } else {
                // 親タスクのみを効率的に更新（子タスクの位置を保持）
                this.updateParentTasksFromAPI(result.updated_parents, false)
              }
            }
          } else {
            console.error('Failed to update task dates:', response.status)
          }
        } catch (error) {
          console.error('Error updating task dates:', error)
        }
      }
    },
    async createTask(taskData) {
      
      if (!this.projectId) {
        console.error('Project ID is required for creating tasks')
        return
      }

      // デフォルト値を設定
      const newTaskData = {
        project_id: this.projectId,
        name: taskData.TaskName || 'New Task',
        start_date: taskData.StartDate ? this.formatDateToLocal(taskData.StartDate) : this.formatDateToLocal(new Date()),
        end_date: taskData.EndDate ? this.formatDateToLocal(taskData.EndDate) : this.formatDateToLocal(new Date(Date.now() + 24*60*60*1000)),
        progress: taskData.Progress || 0,
        status: 'not_started',
        parent_id: taskData.ParentID || null
      }

      try {
        const response = await fetch('/api/tasks', {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          credentials: 'same-origin',
          body: JSON.stringify(newTaskData),
        })

        if (response.ok) {
          const createdTask = await response.json()
          // console.log('Task created successfully:', createdTask)
          
          // ガントチャートのデータを更新
          this.refreshGanttData()
        } else {
          console.error('Failed to create task:', response.status)
          const errorData = await response.json()
          console.error('Error details:', errorData)
        }
      } catch (error) {
        console.error('Error creating task:', error)
      } finally {
        this.isCreatingTask = false
      }
    },
    
    // タスク保存処理（マイルストーン変換等）
    async handleTaskSave(args) {
      // console.log('Handling task save:', args.modifiedRecords)
      
      // 変更されたタスクがある場合
      if (args.modifiedRecords && args.modifiedRecords.length > 0) {
        for (const record of args.modifiedRecords) {
          try {
            // マイルストーンフィールドの変更をチェック
            const isMilestone = record.Milestone || false
            const updateData = {
              name: record.TaskName,
              start_date: record.StartDate ? this.formatDateToLocal(new Date(record.StartDate)) : null,
              end_date: record.EndDate ? this.formatDateToLocal(new Date(record.EndDate)) : null,
              duration: isMilestone ? 0 : Math.max(1, record.Duration || 1), // マイルストーンの場合は0、通常タスクは最低1
              progress: record.Progress || 0,
              is_milestone: isMilestone
            }
            
            // console.log('Updating task:', record.TaskID, updateData)
            
            const response = await fetch(`/api/tasks/${record.TaskID}`, {
              method: 'PUT',
              headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
              },
              credentials: 'same-origin',
              body: JSON.stringify(updateData)
            })
            
            if (response.ok) {
              const updatedTask = await response.json()
              // console.log('Task updated successfully:', updatedTask)
            } else {
              console.error('Failed to update task:', response.status)
              const errorData = await response.json()
              console.error('Error details:', errorData)
            }
          } catch (error) {
            console.error('Error updating task:', error)
          }
        }
      }
    },
    async deleteTask(taskData) {
      // console.log('Deleting task:', taskData)

      try {
        const response = await fetch(`/api/tasks/${taskData.TaskID}`, {
          method: 'DELETE',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          credentials: 'same-origin',
        })

        if (response.ok) {
          // console.log('Task deleted successfully')
          
          // ガントチャートのデータを更新
          this.refreshGanttData()
        } else {
          console.error('Failed to delete task:', response.status)
        }
      } catch (error) {
        console.error('Error deleting task:', error)
      }
    },
    async updateTaskFromInlineEdit(task) {
      // console.log('Updating task from inline edit:', task)
      
      try {
        // タスク名から「+ 」プレフィックスを除去
        const cleanTaskName = task.TaskName ? task.TaskName.replace(/^\+ /, '') : ''
        
        const updateData = {
          name: cleanTaskName,
          progress: task.Progress || 0
        }
        
        // 日付フィールドが編集された場合は追加
        if (task.StartDate) {
          updateData.start_date = this.formatDateToLocal(task.StartDate)
        }
        if (task.EndDate) {
          updateData.end_date = this.formatDateToLocal(task.EndDate)
        }
        if (task.Duration) {
          updateData.duration = parseInt(task.Duration) || 1
        }
        
        // console.log('Sending update data:', updateData)
        
        const response = await fetch(`/api/tasks/${task.TaskID}`, {
          method: 'PUT',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          credentials: 'same-origin',
          body: JSON.stringify(updateData),
        })
        
        if (response.ok) {
          const result = await response.json()
          // console.log('Task updated successfully from inline edit:', result)
          
          // 親タスクの日付を更新（日付が変更された場合）
          if (updateData.start_date || updateData.end_date) {
            const updatedParents = result.updated_parents || []
            if (updatedParents.length > 0) {
              this.updateParentTasksFromAPI(updatedParents, false)
            }
          }
          
          // 日付ラベルを直接更新
          this.updateTaskDateLabels(task)
          
          // processedData内のタスクを即座に更新してUIに反映
          this.updateTaskInProcessedData({
            TaskID: task.TaskID,
            TaskName: task.TaskName, // プレフィックス付きのまま表示用に保持
            StartDate: task.StartDate,
            EndDate: task.EndDate,
            Duration: task.Duration,
            Progress: task.Progress,
            StartDateFormatted: task.StartDate ? 
              `${String(task.StartDate.getMonth() + 1).padStart(2, '0')}/${String(task.StartDate.getDate()).padStart(2, '0')}` : '',
            EndDateFormatted: task.EndDate ? 
              `${String(task.EndDate.getMonth() + 1).padStart(2, '0')}/${String(task.EndDate.getDate()).padStart(2, '0')}` : ''
          })
          
          // ガントチャートのラベルを滑らかに更新（チラつき防止）
          this.$nextTick(() => {
            this.updateTaskLabelsInGantt(task.TaskID)
          })
          
        } else {
          console.error('Failed to update task from inline edit:', response.status)
          const errorData = await response.json()
          console.error('Error details:', errorData)
          
          // エラーの場合は元の状態に戻す
          this.refreshGanttData()
        }
      } catch (error) {
        console.error('Error updating task from inline edit:', error)
        // エラーの場合は元の状態に戻す
        this.refreshGanttData()
      }
    },
    refreshGanttData() {
      // 親コンポーネントにデータの再読み込みを要求
      this.$emit('refresh-data')
    },
    onRowDrop(args) {
      // console.log('onRowDrop called with args:', args)
      
      const droppedTask = args.data[0]
      const targetTask = args.dropRecord
      const dropPosition = args.dropPosition
      
      // 自分自身にドロップしようとした場合は無視
      if (droppedTask.TaskID === targetTask?.TaskID) {
        // console.log('Cancelling drop: same task')
        args.cancel = true
        return
      }
      
      // 循環参照チェック：droppedTaskの子孫にtargetTaskが含まれている場合はキャンセル
      if (this.wouldCreateCircularReference(droppedTask, targetTask, dropPosition)) {
        // console.log('Cancelling drop: would create circular reference')
        args.cancel = true
        return
      }
      
      // ドロップを許可する場合、後でactionCompleteで処理するため情報を保存
      this.pendingDropOperation = {
        droppedTask,
        targetTask,
        dropPosition,
        fromIndex: args.fromIndex,
        dropIndex: args.dropIndex
      }
      
      // console.log('Drop allowed, will process in actionComplete')
    },
    
    
    // タスクが子タスクを持つかどうかをチェック
    hasChildren(task) {
      // Checking if task has children
      
      // processedDataから同じTaskIDを持つタスクを検索して子タスクの有無を確認
      const processedTask = this.findTaskInProcessedData(task.TaskID)
      const hasChildrenInProcessedData = processedTask && processedTask.subtasks && processedTask.subtasks.length > 0
      
      // console.log('processedTask found:', processedTask?.TaskName, 'hasSubtasks:', hasChildrenInProcessedData)
      
      // Syncfusionガントチャートでは、子タスクはsubtasksまたは別のプロパティに格納される
      const hasChildren = (task.subtasks && task.subtasks.length > 0) ||
                         (task.hasChildRecords) ||
                         (task.ganttProperties && task.ganttProperties.hasChildRecords) ||
                         (task.childRecords && task.childRecords.length > 0) ||
                         (task.taskData && task.taskData.subtasks && task.taskData.subtasks.length > 0) ||
                         hasChildrenInProcessedData
      
      // console.log('hasChildren result:', hasChildren)
      return Boolean(hasChildren)  // 明示的にbooleanに変換
    },
    
    // 循環参照チェック：ドロップ操作が循環参照を生成するかチェック
    wouldCreateCircularReference(droppedTask, targetTask, dropPosition) {
      if (!targetTask || !droppedTask) return false
      
      // 子として追加する場合のみチェック
      if (dropPosition === 'child' || 
          (dropPosition === 'middleSegment' && this.hasChildren(targetTask))) {
        
        // droppedTaskがtargetTaskの祖先である場合、循環参照になる
        const isAncestor = (task, ancestorId) => {
          if (task.TaskID === ancestorId) return true
          
          // 親を辿って確認
          if (task.ParentID) {
            const parent = this.findTaskInProcessedData(task.ParentID)
            if (parent) {
              return isAncestor(parent, ancestorId)
            }
          }
          
          return false
        }
        
        return isAncestor(targetTask, droppedTask.TaskID)
      }
      
      return false
    },
    
    // ドロップ操作後のタスク更新処理
    async updateTaskFromDrop(operation) {
      const { droppedTask, targetTask, dropPosition } = operation
      
      try {
        let newParentId = null
        let newSortOrder = 1
        
        // ドロップ位置に応じて新しい親IDとsort_orderを決定
        if (dropPosition === 'child' || 
            (dropPosition === 'middleSegment' && targetTask && this.hasChildren(targetTask))) {
          // 子タスクとして追加
          newParentId = targetTask.TaskID
          newSortOrder = this.getNextChildSortOrder(targetTask.TaskID)
          
        } else if (targetTask) {
          // 兄弟タスクとして追加
          newParentId = targetTask.ParentID || null
          
          // Syncfusionが既に並び替えを行っているため、現在の表示順序を取得
          const currentOrder = this.getCurrentTaskOrder(droppedTask.TaskID, newParentId)
          newSortOrder = currentOrder
          
        } else {
          // ルートレベルに移動
          newParentId = null
          const currentOrder = this.getCurrentTaskOrder(droppedTask.TaskID, null)
          newSortOrder = currentOrder
        }
        
        const oldParentId = droppedTask.ParentID
        
        // APIでタスクを更新
        const updateData = {
          parent_id: newParentId,
          sort_order: newSortOrder
        }
        
        // Updating dropped task with new hierarchy
        
        const response = await fetch(`/api/tasks/${droppedTask.TaskID}`, {
          method: 'PUT',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          credentials: 'same-origin',
          body: JSON.stringify(updateData),
        })
        
        if (response.ok) {
          const result = await response.json()
          // console.log('Task updated successfully:', result)
          
          // 同レベルの他のタスクのsort_orderも更新
          await this.updateSiblingsSortOrder(newParentId)
          
          // 新旧の親タスクの日付を更新
          await this.updateParentTasksAfterMove(oldParentId, newParentId)
          
          // ガントチャートのデータソースを完全に更新して表示を同期
          // 親子関係が変更される場合は階層構造の再構築が必要
          await this.updateGanttDataSource()
          
        } else {
          console.error('Failed to update task:', response.status)
          // エラーの場合は元の状態に戻す
          this.refreshGanttData()
        }
        
      } catch (error) {
        console.error('Error updating task from drop:', error)
        // エラーの場合は元の状態に戻す
        this.refreshGanttData()
      }
    },
    
    // 現在のガントチャート表示順序に基づいてsort_orderを取得
    getCurrentTaskOrder(taskId, parentId) {
      if (!this.$refs.gantt || !this.$refs.gantt.ej2Instances) {
        return 1
      }
      
      const ganttInstance = this.$refs.gantt.ej2Instances
      const currentData = ganttInstance.currentViewData || ganttInstance.dataSource
      
      let order = 1
      let foundIndex = -1
      
      if (parentId === null) {
        // ルートレベルタスクの順序
        const rootTasks = currentData.filter(task => !task.ParentID)
        foundIndex = rootTasks.findIndex(task => task.TaskID === taskId)
        if (foundIndex >= 0) {
          order = (foundIndex + 1) * 10
        }
      } else {
        // 子タスクの順序
        const siblingTasks = currentData.filter(task => task.ParentID === parentId)
        foundIndex = siblingTasks.findIndex(task => task.TaskID === taskId)
        if (foundIndex >= 0) {
          order = (foundIndex + 1) * 10
        }
      }
      
      return order
    },
    
    // 兄弟タスクのsort_orderを一括更新
    async updateSiblingsSortOrder(parentId) {
      if (!this.$refs.gantt || !this.$refs.gantt.ej2Instances) {
        return
      }
      
      const ganttInstance = this.$refs.gantt.ej2Instances
      const currentData = ganttInstance.currentViewData || ganttInstance.dataSource
      
      // 同じ親を持つタスクを現在の表示順序で取得
      const siblings = currentData
        .filter(task => task.ParentID === parentId)
        .map((task, index) => ({
          taskId: task.TaskID,
          newOrder: (index + 1) * 10
        }))
      
      // 順番に更新
      for (const sibling of siblings) {
        try {
          await fetch(`/api/tasks/${sibling.taskId}`, {
            method: 'PUT',
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ sort_order: sibling.newOrder }),
          })
        } catch (error) {
          console.error('Error updating sibling sort order:', error)
        }
      }
    },
    
    // processedDataからタスクを検索するヘルパーメソッド
    findTaskInProcessedData(taskId) {
      const findTask = (tasks) => {
        for (const task of tasks) {
          if (task.TaskID === taskId) {
            return task
          }
          if (task.subtasks && task.subtasks.length > 0) {
            const found = findTask(task.subtasks)
            if (found) return found
          }
        }
        return null
      }
      
      return findTask(this.processedData)
    },
    
    // 特定の親タスクの子タスクの次のsort_orderを取得
    getNextChildSortOrder(parentId) {
      if (!this.$refs.gantt || !this.$refs.gantt.ej2Instances) {
        return 10
      }
      
      const ganttInstance = this.$refs.gantt.ej2Instances
      const currentData = ganttInstance.currentViewData || ganttInstance.dataSource
      
      const childTasks = currentData.filter(task => task.ParentID === parentId)
      
      if (childTasks.length === 0) {
        return 10
      }
      
      const maxOrder = Math.max(...childTasks.map(task => task.sort_order || 0))
      return maxOrder + 10
    },
    
    // 効率的なガントチャートデータソース更新（特定タスクのみ）
    async updateGanttDataSourceEfficient(taskId, updatedTaskData) {
      if (!this.$refs.gantt || !this.$refs.gantt.ej2Instances) {
        return
      }
      
      try {
        const ganttInstance = this.$refs.gantt.ej2Instances
        
        // 現在の選択状態とスクロール位置を保存
        const selectedRowIndex = ganttInstance.selectedRowIndex
        const scrollLeft = ganttInstance.ganttChartModule?.chartBodyContainer?.scrollLeft || 0
        
        // SyncfusionのupdateRecordByIDメソッドを使用して特定のレコードを更新
        const updatedRecord = {
          TaskID: updatedTaskData.id,
          TaskName: updatedTaskData.name,
          ParentID: updatedTaskData.parent_id,
          sort_order: updatedTaskData.sort_order,
          StartDate: updatedTaskData.start_date ? new Date(updatedTaskData.start_date) : null,
          EndDate: updatedTaskData.end_date ? new Date(updatedTaskData.end_date) : null,
          Duration: updatedTaskData.duration,
          Progress: updatedTaskData.progress || 0
        }
        
        // フォーマット済み日付も更新
        if (updatedRecord.StartDate) {
          updatedRecord.StartDateFormatted = `${String(updatedRecord.StartDate.getMonth() + 1).padStart(2, '0')}/${String(updatedRecord.StartDate.getDate()).padStart(2, '0')}`
        }
        if (updatedRecord.EndDate) {
          updatedRecord.EndDateFormatted = `${String(updatedRecord.EndDate.getMonth() + 1).padStart(2, '0')}/${String(updatedRecord.EndDate.getDate()).padStart(2, '0')}`
        }
        
        // processedDataも更新
        this.updateTaskInProcessedData(updatedRecord)
        
        // SyncfusionのupdateRecordByIDを使用
        if (ganttInstance.updateRecordByID) {
          ganttInstance.updateRecordByID(updatedRecord)
        } else {
          // フォールバック：データソース全体を更新
          ganttInstance.dataSource = [...this.processedData]
        }
        
        // 少し遅延させて選択状態とスクロール位置を復元
        setTimeout(() => {
          try {
            if (selectedRowIndex >= 0) {
              ganttInstance.selectedRowIndex = selectedRowIndex
            }
            
            if (ganttInstance.ganttChartModule?.chartBodyContainer) {
              ganttInstance.ganttChartModule.chartBodyContainer.scrollLeft = scrollLeft
            }
          } catch (error) {
            console.warn('Failed to restore selection/scroll state:', error)
          }
        }, 100)
        
        // console.log('Gantt data source updated efficiently')
        
      } catch (error) {
        console.error('Error in efficient update, falling back to full refresh:', error)
        // エラーの場合は完全なデータソース更新にフォールバック
        await this.updateGanttDataSource()
      }
    },
    
    // ガントチャートのデータソースを更新
    async updateGanttDataSource() {
      if (!this.$refs.gantt || !this.$refs.gantt.ej2Instances) {
        return
      }
      
      try {
        // 最新のデータを取得
        const response = await fetch(`/api/projects/${this.projectId}/gantt`, {
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          credentials: 'same-origin',
        })
        
        if (response.ok) {
          const freshData = await response.json()
          
          // processedDataを更新（階層データを正しくフラット化）
          this.processedData = this.buildHierarchicalGanttData(freshData)
          
          // Syncfusionガントチャートのデータソースを更新
          const ganttInstance = this.$refs.gantt.ej2Instances
          
          // 現在の状態を保存
          const selectedRowIndex = ganttInstance.selectedRowIndex
          const scrollLeft = ganttInstance.ganttChartModule?.chartBodyContainer?.scrollLeft || 0
          
          // 展開・折り畳み状態を保存
          const expandedItems = []
          if (ganttInstance.currentViewData) {
            ganttInstance.currentViewData.forEach(item => {
              if (item.expanded) {
                expandedItems.push(item.TaskID)
              }
            })
          }
          
          // データソースを更新
          ganttInstance.dataSource = this.processedData
          
          // 少し遅延させて状態を復元
          setTimeout(() => {
            try {
              // 展開状態を復元
              expandedItems.forEach(taskId => {
                try {
                  ganttInstance.expandByID(taskId)
                } catch (err) {
                  console.warn('Failed to expand task:', taskId, err)
                }
              })
              
              // 選択状態を復元
              if (selectedRowIndex >= 0 && selectedRowIndex < this.processedData.length) {
                ganttInstance.selectedRowIndex = selectedRowIndex
              }
              
              // スクロール位置を復元
              if (ganttInstance.ganttChartModule?.chartBodyContainer) {
                ganttInstance.ganttChartModule.chartBodyContainer.scrollLeft = scrollLeft
              }
            } catch (error) {
              console.warn('Failed to restore UI state:', error)
            }
          }, 200)
          
          // console.log('Gantt data source updated successfully')
        } else {
          console.error('Failed to fetch fresh data:', response.status)
          // フォールバックとして完全リロード
          this.refreshGanttData()
        }
      } catch (error) {
        console.error('Error updating gantt data source:', error)
        // フォールバックとして完全リロード
        this.refreshGanttData()
      }
    },
    
    
    // ルートレベルタスクの次のsort_orderを取得
    getNextRootSortOrder() {
      let maxOrder = 0
      
      this.processedData.forEach(task => {
        if (!task.ParentID) {
          const currentOrder = task.sort_order || 0
          maxOrder = Math.max(maxOrder, currentOrder)
          // console.log('Root task:', task.TaskName, 'sort_order:', currentOrder)
        }
      })
      
      const nextOrder = maxOrder + 1
      // console.log('Next root sort order:', nextOrder, '(max was', maxOrder, ')')
      return nextOrder
    },
    
    // 階層構造のタスクリストをフラットなリストに変換
    getFlatTaskList(tasks) {
      const flatList = []
      
      const flatten = (taskList) => {
        taskList.forEach(task => {
          flatList.push(task)
          if (task.subtasks && task.subtasks.length > 0) {
            flatten(task.subtasks)
          }
        })
      }
      
      flatten(tasks)
      return flatList
    },
    
    // タスクバードラッグ時に日付ラベルを更新
    updateTaskDateLabels(task) {
      // console.log('Updating task date labels for:', task)
      // console.log('Current StartDate:', task.StartDate)
      // console.log('Current EndDate:', task.EndDate)
      
      // フォーマット済み日付を更新
      const startDate = task.StartDate
      const endDate = task.EndDate
      
      if (startDate) {
        task.StartDateFormatted = `${String(startDate.getMonth() + 1).padStart(2, '0')}/${String(startDate.getDate()).padStart(2, '0')}`
      }
      
      if (endDate) {
        task.EndDateFormatted = `${String(endDate.getMonth() + 1).padStart(2, '0')}/${String(endDate.getDate()).padStart(2, '0')}`
      }
      
      // console.log('New StartDateFormatted:', task.StartDateFormatted)
      // console.log('New EndDateFormatted:', task.EndDateFormatted)
      
      // processedData内の対応するタスクも更新
      const updateProcessedTask = (tasks) => {
        for (let i = 0; i < tasks.length; i++) {
          if (tasks[i].TaskID === task.TaskID) {
            tasks[i].StartDateFormatted = task.StartDateFormatted
            tasks[i].EndDateFormatted = task.EndDateFormatted
            tasks[i].StartDate = task.StartDate
            tasks[i].EndDate = task.EndDate
            // console.log('Updated processed task date labels:', tasks[i])
            return true
          }
          
          if (tasks[i].subtasks && updateProcessedTask(tasks[i].subtasks)) {
            return true
          }
        }
        return false
      }
      
      updateProcessedTask(this.processedData)
      
      // 子タスクのラベル更新時はdataSourceの再設定を避ける
      // ラベルのみの更新なので、データソースの再設定は不要
      // console.log('Task date labels updated in processedData')
    },
    
    // processedData内の特定タスクを更新
    updateTaskInProcessedData(updatedTask) {
      // console.log('Updating task in processed data:', updatedTask)
      
      const updateTask = (tasks) => {
        for (let i = 0; i < tasks.length; i++) {
          if (tasks[i].TaskID === updatedTask.TaskID) {
            // 既存のタスクデータを更新
            Object.assign(tasks[i], updatedTask)
            // console.log('Updated task in processed data:', tasks[i])
            return true
          }
          
          if (tasks[i].subtasks && updateTask(tasks[i].subtasks)) {
            return true
          }
        }
        return false
      }
      
      updateTask(this.processedData)
      
      // ガントチャートのデータソースを軽量更新（チラつき防止）
      this.$nextTick(() => {
        if (this.$refs.gantt && this.$refs.gantt.ej2Instances) {
          const ganttInstance = this.$refs.gantt.ej2Instances
          
          try {
            // 現在のデータソース内の該当タスクを直接更新
            if (ganttInstance.dataSource && Array.isArray(ganttInstance.dataSource)) {
              const targetRecord = ganttInstance.dataSource.find(record => record.TaskID === updatedTask.TaskID)
              if (targetRecord) {
                Object.assign(targetRecord, updatedTask)
                
                // チラつきを避けるため、refresh()は使わずラベルのみ更新
                this.updateTaskLabelsInGantt(updatedTask.TaskID)
              }
            }
          } catch (error) {
            console.warn('Failed to update specific record:', error)
          }
        }
      })
    },
    onQueryTaskbarInfo(args) {
      // タスクバーの描画時にラベルを最新のデータで設定
      const task = args.data
      if (task) {
        // 最新のフォーマット済み日付を設定
        if (task.StartDate) {
          task.StartDateFormatted = `${String(task.StartDate.getMonth() + 1).padStart(2, '0')}/${String(task.StartDate.getDate()).padStart(2, '0')}`
        }
        if (task.EndDate) {
          task.EndDateFormatted = `${String(task.EndDate.getMonth() + 1).padStart(2, '0')}/${String(task.EndDate.getDate()).padStart(2, '0')}`
        }
      }
    },
    
    // 特定タスクのラベルのみを更新（チラつき防止）
    updateTaskLabelsInGantt(taskId) {
      // console.log('Updating task labels in gantt for taskId:', taskId)
      
      if (!this.$refs.gantt || !this.$refs.gantt.ej2Instances) {
        return
      }
      
      const ganttInstance = this.$refs.gantt.ej2Instances
      
      try {
        // 該当タスクのデータを取得
        const taskData = ganttInstance.dataSource.find(record => record.TaskID === taskId)
        if (!taskData) {
          console.warn('Task data not found for ID:', taskId)
          return
        }
        
        // Syncfusionの updateRecordByID を使用してスムーズに更新
        if (ganttInstance.updateRecordByID) {
          ganttInstance.updateRecordByID(taskData)
          // console.log('Task updated using updateRecordByID')
        } else {
          // フォールバック：DOM要素を直接更新
          const taskBarElement = ganttInstance.element.querySelector(`[data-task-id="${taskId}"]`)
          if (taskBarElement) {
            // 左側ラベル（開始日）を更新
            const leftLabelElement = taskBarElement.querySelector('.e-taskbar-left-label')
            if (leftLabelElement && this.labelSettings.leftLabel === 'StartDateFormatted') {
              leftLabelElement.textContent = taskData.StartDateFormatted || ''
            }
            
            // 右側ラベル（終了日）を更新  
            const rightLabelElement = taskBarElement.querySelector('.e-taskbar-right-label')
            if (rightLabelElement && this.labelSettings.rightLabel === 'EndDateFormatted') {
              rightLabelElement.textContent = taskData.EndDateFormatted || ''
            }
            
            // console.log('Task labels updated via DOM manipulation')
          } else {
            // console.log('Task bar element not found, will update on next render')
          }
        }
        
      } catch (error) {
        console.warn('Error updating task labels:', error)
      }
    },
    
    // 親タスクの日付を子タスクに基づいて更新
    async updateParentTaskDates(childTask) {
      if (!childTask.ParentID) {
        // console.log('Task has no parent, skipping parent date update')
        return
      }
      
      // console.log('Updating parent task dates for child:', childTask)
      
      // 親タスクを見つける
      const findParentTask = (tasks, parentId) => {
        for (let task of tasks) {
          if (task.TaskID === parentId) {
            return task
          }
          if (task.subtasks) {
            const found = findParentTask(task.subtasks, parentId)
            if (found) return found
          }
        }
        return null
      }
      
      const parentTask = findParentTask(this.processedData, childTask.ParentID)
      if (!parentTask) {
        // console.log('Parent task not found')
        return
      }
      
      // console.log('Found parent task:', parentTask)
      
      // 親タスクの全子タスクを取得
      const getAllChildTasks = (parent) => {
        const children = []
        if (parent.subtasks && parent.subtasks.length > 0) {
          for (let child of parent.subtasks) {
            children.push(child)
            // 再帰的に孫タスクも取得
            children.push(...getAllChildTasks(child))
          }
        }
        return children
      }
      
      const allChildren = getAllChildTasks(parentTask)
      // console.log('All child tasks:', allChildren)
      
      if (allChildren.length === 0) {
        // console.log('No child tasks found')
        return
      }
      
      // 子タスクの最小開始日と最大終了日を計算
      let minStartDate = null
      let maxEndDate = null
      
      for (let child of allChildren) {
        if (child.StartDate) {
          if (!minStartDate || child.StartDate < minStartDate) {
            minStartDate = child.StartDate
          }
        }
        if (child.EndDate) {
          if (!maxEndDate || child.EndDate > maxEndDate) {
            maxEndDate = child.EndDate
          }
        }
      }
      
      // console.log('Calculated min start date:', minStartDate)
      // console.log('Calculated max end date:', maxEndDate)
      
      // 親タスクの日付を更新
      if (minStartDate || maxEndDate) {
        const updatedParent = {
          ...parentTask,
          StartDate: minStartDate || parentTask.StartDate,
          EndDate: maxEndDate || parentTask.EndDate
        }
        
        // フォーマット済み日付も更新
        if (minStartDate) {
          updatedParent.StartDateFormatted = `${String(minStartDate.getMonth() + 1).padStart(2, '0')}/${String(minStartDate.getDate()).padStart(2, '0')}`
        }
        if (maxEndDate) {
          updatedParent.EndDateFormatted = `${String(maxEndDate.getMonth() + 1).padStart(2, '0')}/${String(maxEndDate.getDate()).padStart(2, '0')}`
        }
        
        
        // processedData内の親タスクを更新
        this.updateTaskInProcessedData(updatedParent)
        
        // APIで親タスクの日付を更新
        try {
          const response = await fetch(`/api/tasks/${parentTask.TaskID}/dates`, {
            method: 'PUT',
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
              start_date: updatedParent.StartDate?.toISOString().split('T')[0],
              end_date: updatedParent.EndDate?.toISOString().split('T')[0],
            }),
          })
          
          if (response.ok) {
            
            // ガントチャートのデータソースを更新
            this.$nextTick(() => {
              if (this.$refs.gantt && this.$refs.gantt.ej2Instances) {
                const ganttInstance = this.$refs.gantt.ej2Instances
                ganttInstance.dataSource = [...this.processedData]
              }
            })
          } else {
            console.error('Failed to update parent task dates:', response.status)
          }
        } catch (error) {
          console.error('Error updating parent task dates:', error)
        }
      }
    },
    
    // processedData内のタスクを更新
    updateTaskInProcessedData(updatedTask) {
      const updateTask = (tasks) => {
        for (let i = 0; i < tasks.length; i++) {
          if (tasks[i].TaskID === updatedTask.TaskID) {
            tasks[i] = { ...tasks[i], ...updatedTask }
            // console.log('Updated task in processedData:', tasks[i])
            return true
          }
          
          if (tasks[i].subtasks && updateTask(tasks[i].subtasks)) {
            return true
          }
        }
        return false
      }
      
      updateTask(this.processedData)
    },
    
    // 複数の親タスクをprocessedDataで更新
    updateParentTasksInProcessedData(updatedParents) {
      for (const parent of updatedParents) {
        // API応答の日付をDateオブジェクトに変換
        const updatedParentTask = {
          TaskID: parent.id,
          TaskName: parent.name,
          StartDate: new Date(parent.start_date),
          EndDate: new Date(parent.end_date),
          Duration: parent.duration,
          Progress: parent.progress,
          ParentID: parent.parent_id,
          // フォーマット済み日付も更新
          StartDateFormatted: parent.start_date ? 
            `${String(new Date(parent.start_date).getMonth() + 1).padStart(2, '0')}/${String(new Date(parent.start_date).getDate()).padStart(2, '0')}` : '',
          EndDateFormatted: parent.end_date ? 
            `${String(new Date(parent.end_date).getMonth() + 1).padStart(2, '0')}/${String(new Date(parent.end_date).getDate()).padStart(2, '0')}` : ''
        }
        
        this.updateTaskInProcessedData(updatedParentTask)
      }
    },
    
    // APIから返された親タスクデータで更新
    async updateParentTasksFromAPI(updatedParents, isEndDateOperation = false) {
      if (!this.$refs.gantt || !this.$refs.gantt.ej2Instances) {
        return
      }
      
      const ganttInstance = this.$refs.gantt.ej2Instances
      
      // 終了日操作の場合はより長い遅延
      const delay = isEndDateOperation ? 200 : 100
      await new Promise(resolve => setTimeout(resolve, delay))
      
      for (const parent of updatedParents) {
        // API応答の日付をDateオブジェクトに変換
        const startDate = new Date(parent.start_date)
        const endDate = new Date(parent.end_date)
        
        const updatedParentTask = {
          TaskID: parent.id,
          TaskName: parent.name,
          StartDate: startDate,
          EndDate: endDate,
          Duration: parent.duration,
          Progress: parent.progress,
          ParentID: parent.parent_id,
          // フォーマット済み日付も更新
          StartDateFormatted: `${String(startDate.getMonth() + 1).padStart(2, '0')}/${String(startDate.getDate()).padStart(2, '0')}`,
          EndDateFormatted: `${String(endDate.getMonth() + 1).padStart(2, '0')}/${String(endDate.getDate()).padStart(2, '0')}`
        }
        
        
        // processedData内の親タスクを更新
        this.updateTaskInProcessedData(updatedParentTask)
      }
      
      // 親タスクの更新は遅延させて、子タスクのドラッグ操作を妨げない
      const updateDelay = isEndDateOperation ? 500 : 300
      setTimeout(() => {
        this.$nextTick(() => {
          try {
            // 既存のタスクバーの位置を保持しながら更新
            const currentSelection = ganttInstance.selectedRowIndex
            
            // 終了日操作の場合はより慎重に更新
            if (isEndDateOperation) {
              // console.log('End date operation - careful dataSource update')
              // 現在のスクロール位置も保持
              const scrollLeft = ganttInstance.ganttChartModule.chartBodyContainer.scrollLeft
              ganttInstance.dataSource = [...this.processedData]
              
              // スクロール位置を復元
              setTimeout(() => {
                if (ganttInstance.ganttChartModule.chartBodyContainer) {
                  ganttInstance.ganttChartModule.chartBodyContainer.scrollLeft = scrollLeft
                }
              }, 50)
            } else {
              ganttInstance.dataSource = [...this.processedData]
            }
            
            // 選択状態を復元
            if (currentSelection !== -1) {
              ganttInstance.selectedRowIndex = currentSelection
            }
            
          } catch (error) {
            console.error('Error updating parent tasks in Gantt:', error)
            // フォールバックとして完全再読み込み
            setTimeout(() => {
              this.refreshGanttData()
            }, 100)
          }
        })
      }, updateDelay)
    },
    
    // IDで親タスクの日付を更新
    async updateParentTaskDatesById(parentId) {
      
      // 親タスクを見つける
      const findParentTask = (tasks, targetId) => {
        for (let task of tasks) {
          if (task.TaskID === targetId) {
            return task
          }
          if (task.subtasks) {
            const found = findParentTask(task.subtasks, targetId)
            if (found) return found
          }
        }
        return null
      }
      
      const parentTask = findParentTask(this.processedData, parentId)
      if (parentTask) {
        // 仮の子タスクオブジェクトを作成して既存のメソッドを呼び出す
        const dummyChild = { ParentID: parentId }
        await this.updateParentTaskDates(dummyChild)
      }
    },
    
    // タスク移動後に新旧の親タスクの日付を更新
    async updateParentTasksAfterMove(oldParentId, newParentId) {
      
      const updatePromises = []
      
      // 元の親タスクを更新（子が削除されたため）
      if (oldParentId) {
        updatePromises.push(this.updateParentTaskDatesById(oldParentId))
      }
      
      // 新しい親タスクを更新（子が追加されたため）
      if (newParentId && newParentId !== oldParentId) {
        updatePromises.push(this.updateParentTaskDatesById(newParentId))
      }
      
      // 両方の親タスクを並行して更新
      if (updatePromises.length > 0) {
        try {
          await Promise.all(updatePromises)
        } catch (error) {
          console.error('Error updating parent tasks after move:', error)
        }
      }
    },
    
    // 日付をローカルタイムゾーンでYYYY-MM-DD形式にフォーマット
    formatDateToLocal(date) {
      if (!date) return null
      
      const year = date.getFullYear()
      const month = String(date.getMonth() + 1).padStart(2, '0')
      const day = String(date.getDate()).padStart(2, '0')
      
      return `${year}-${month}-${day}`
    },
    
    // スケール変更メソッド
    changeTimelineScale() {
      // console.log('Changing timeline scale to:', this.selectedScale)
      
      const preset = this.scalePresets[this.selectedScale]
      if (preset) {
        // タイムライン設定を更新
        this.timelineSettings = {
          ...preset,
          timelineUnitSize: Math.round(preset.timelineUnitSize * (parseInt(this.selectedZoom) / 100))
        }
        
        // ガントチャートインスタンスの設定を更新
        this.$nextTick(() => {
          if (this.$refs.gantt && this.$refs.gantt.ej2Instances) {
            const ganttInstance = this.$refs.gantt.ej2Instances
            ganttInstance.timelineSettings = this.timelineSettings
            ganttInstance.refresh()
          }
        })
        
        // ユーザー設定を保存
        this.saveUserOptions()
      }
    },
    
    changeZoomLevel() {
      // console.log('Changing zoom level to:', this.selectedZoom + '%')
      
      const preset = this.scalePresets[this.selectedScale]
      if (preset) {
        // ズームレベルに応じてtimelineUnitSizeを調整
        this.timelineSettings = {
          ...this.timelineSettings,
          timelineUnitSize: Math.round(preset.timelineUnitSize * (parseInt(this.selectedZoom) / 100))
        }
        
        // ガントチャートインスタンスの設定を更新
        this.$nextTick(() => {
          if (this.$refs.gantt && this.$refs.gantt.ej2Instances) {
            const ganttInstance = this.$refs.gantt.ej2Instances
            ganttInstance.timelineSettings = this.timelineSettings
            ganttInstance.refresh()
          }
        })
        
        // ユーザー設定を保存
        this.saveUserOptions()
      }
    },
    
    fitToScreen() {
      this.$nextTick(() => {
        if (this.$refs.gantt && this.$refs.gantt.ej2Instances) {
          const ganttInstance = this.$refs.gantt.ej2Instances
          ganttInstance.fitToProject()
        }
      })
    },
    
    // フルスクリーンモードの切り替え
    toggleFullscreen() {
      if (this.isFullscreen) {
        this.exitFullscreen()
      } else {
        this.enterFullscreen()
      }
    },
    
    // フルスクリーンモードに入る
    enterFullscreen() {
      const container = this.$refs.ganttContainer
      
      if (container.requestFullscreen) {
        container.requestFullscreen()
      } else if (container.mozRequestFullScreen) {
        container.mozRequestFullScreen()
      } else if (container.webkitRequestFullscreen) {
        container.webkitRequestFullscreen()
      } else if (container.msRequestFullscreen) {
        container.msRequestFullscreen()
      } else {
        // フルスクリーンAPIが利用できない場合は、CSSでの疑似フルスクリーン
        this.enterPseudoFullscreen()
      }
    },
    
    // フルスクリーンモードを終了
    exitFullscreen() {
      if (document.exitFullscreen) {
        document.exitFullscreen()
      } else if (document.mozCancelFullScreen) {
        document.mozCancelFullScreen()
      } else if (document.webkitExitFullscreen) {
        document.webkitExitFullscreen()
      } else if (document.msExitFullscreen) {
        document.msExitFullscreen()
      } else {
        // フルスクリーンAPIが利用できない場合は、疑似フルスクリーンを終了
        this.exitPseudoFullscreen()
      }
    },
    
    // 疑似フルスクリーンモードに入る（フルスクリーンAPIが利用できない場合）
    enterPseudoFullscreen() {
      this.isFullscreen = true
      document.body.classList.add('overflow-hidden')
      
      // ガントチャートのサイズを調整
      this.$nextTick(() => {
        if (this.$refs.gantt && this.$refs.gantt.ej2Instances) {
          const ganttInstance = this.$refs.gantt.ej2Instances
          ganttInstance.refresh()
        }
      })
    },
    
    // 疑似フルスクリーンモードを終了
    exitPseudoFullscreen() {
      this.isFullscreen = false
      document.body.classList.remove('overflow-hidden')
      
      // ガントチャートのサイズを調整
      this.$nextTick(() => {
        if (this.$refs.gantt && this.$refs.gantt.ej2Instances) {
          const ganttInstance = this.$refs.gantt.ej2Instances
          ganttInstance.refresh()
        }
      })
    },
    
    // フルスクリーン状態変更イベントのハンドリング
    handleFullscreenChange() {
      const isCurrentlyFullscreen = !!(
        document.fullscreenElement ||
        document.mozFullScreenElement ||
        document.webkitFullscreenElement ||
        document.msFullscreenElement
      )
      
      this.isFullscreen = isCurrentlyFullscreen
      
      // フルスクリーン状態変更時にガントチャートのサイズを調整
      this.$nextTick(() => {
        if (this.$refs.gantt && this.$refs.gantt.ej2Instances) {
          const ganttInstance = this.$refs.gantt.ej2Instances
          ganttInstance.refresh()
        }
      })
    },
    
    // 全ての親タスクを展開
    expandAll() {
      this.$nextTick(() => {
        if (this.$refs.gantt && this.$refs.gantt.ej2Instances) {
          const ganttInstance = this.$refs.gantt.ej2Instances
          ganttInstance.expandAll()
        }
      })
    },
    
    // 全ての親タスクを折り畳み
    collapseAll() {
      this.$nextTick(() => {
        if (this.$refs.gantt && this.$refs.gantt.ej2Instances) {
          const ganttInstance = this.$refs.gantt.ej2Instances
          ganttInstance.collapseAll()
        }
      })
    },
    
    // 特定のタスクを展開/折り畳み
    toggleExpand(taskId) {
      this.$nextTick(() => {
        if (this.$refs.gantt && this.$refs.gantt.ej2Instances) {
          const ganttInstance = this.$refs.gantt.ej2Instances
          const record = ganttInstance.getTaskByUniqueID(taskId)
          if (record) {
            if (record.expanded) {
              ganttInstance.collapseByID(taskId)
            } else {
              ganttInstance.expandByID(taskId)
            }
          }
        }
      })
    },
    
    updateLabelsPreservingZoom() {
      if (!this.$refs.gantt || !this.$refs.gantt.ej2Instances) {
        return
      }
      
      this.$nextTick(() => {
        try {
          const ganttInstance = this.$refs.gantt.ej2Instances
          
          // 現在の状態を保存
          const currentTimelineSettings = { ...ganttInstance.timelineSettings }
          const chartElement = ganttInstance.element.querySelector('.e-chart-scroll-container .e-content')
          const currentScrollLeft = chartElement ? chartElement.scrollLeft : 0
          const currentScrollTop = chartElement ? chartElement.scrollTop : 0
          
          // Preserving zoom state during scale change
          
          // ラベル設定を更新
          ganttInstance.labelSettings = { ...this.labelSettings }
          
          // ラベルのみを再描画（軽量な更新）
          if (ganttInstance.renderGantt) {
            ganttInstance.renderGantt()
          } else if (ganttInstance.refresh) {
            // renderGanttが利用できない場合はrefreshを使用
            ganttInstance.refresh()
          }
          
          // ズーム状態とスクロール位置を復元
          this.$nextTick(() => {
            // タイムライン設定を復元
            ganttInstance.timelineSettings = currentTimelineSettings
            
            // スクロール位置を復元
            const newChartElement = ganttInstance.element.querySelector('.e-chart-scroll-container .e-content')
            if (newChartElement) {
              newChartElement.scrollLeft = currentScrollLeft
              newChartElement.scrollTop = currentScrollTop
            }
            
          })
          
        } catch (error) {
          console.error('Error updating labels while preserving zoom:', error)
          // フォールバック：通常の更新
          const ganttInstance = this.$refs.gantt.ej2Instances
          ganttInstance.labelSettings = { ...this.labelSettings }
        }
      })
    },
    
    // ユーザー設定を保存（デバウンス付き）
    async saveUserOptions() {
      // 既存のタイマーをクリア
      if (this.saveTimeout) {
        clearTimeout(this.saveTimeout)
      }
      
      // 500ms後に保存を実行
      this.saveTimeout = setTimeout(async () => {
        try {
          const userOptions = {
            gantt: {
              scale: this.selectedScale,
              zoom: this.selectedZoom,
              labelSettings: this.labelSettings
            }
          }
          
          // console.log('Saving user options:', userOptions)
          
          const response = await fetch('/api/user-options', {
            method: 'PUT',
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ user_options: userOptions }),
          })
          
          if (response.ok) {
            // console.log('User options saved successfully')
          } else {
            console.error('Failed to save user options:', response.status)
          }
        } catch (error) {
          console.error('Error saving user options:', error)
        }
      }, 500)
    },
    
    // ユーザー設定を読み込み
    async loadUserOptions() {
      try {
        const response = await fetch('/api/user-options', {
          method: 'GET',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          credentials: 'same-origin',
        })
        
        if (response.ok) {
          const data = await response.json()
          // console.log('Loaded user options:', data.user_options)
          
          if (data.user_options && data.user_options.gantt) {
            const ganttOptions = data.user_options.gantt
            
            // 保存された設定を適用
            if (ganttOptions.scale) {
              this.selectedScale = ganttOptions.scale
            }
            if (ganttOptions.zoom) {
              this.selectedZoom = ganttOptions.zoom
            }
            if (ganttOptions.labelSettings) {
              this.labelSettings = { ...ganttOptions.labelSettings }
            }
            
            // スケールとズームを適用
            this.changeTimelineScale()
            
            // console.log('User options applied successfully')
          }
        } else {
          console.error('Failed to load user options:', response.status)
        }
      } catch (error) {
        console.error('Error loading user options:', error)
      }
    },
    
    // 日付をMM/dd形式でフォーマット
    formatDate(value, field) {
      if (!value) return ''
      
      let date
      if (typeof value === 'string') {
        date = new Date(value)
      } else if (value instanceof Date) {
        date = value
      } else {
        return value
      }
      
      if (isNaN(date.getTime())) return value
      
      // 日付フィールドの場合のみフォーマット
      if (field === 'StartDate' || field === 'EndDate') {
        const month = String(date.getMonth() + 1).padStart(2, '0')
        const day = String(date.getDate()).padStart(2, '0')
        return `${month}/${day}`
      }
      
      return value
    },
    
    // 日付を短縮形式でフォーマット
    formatDateShort(date) {
      if (!date) return ''
      const month = String(date.getMonth() + 1).padStart(2, '0')
      const day = String(date.getDate()).padStart(2, '0')
      return `${month}/${day}`
    },
    
    // データ処理用のインライン日付フォーマット
    formatDateShortInline(date) {
      if (!date) return ''
      const month = String(date.getMonth() + 1).padStart(2, '0')
      const day = String(date.getDate()).padStart(2, '0')
      return `${month}/${day}`
    },
    
    // タスクバー情報をカスタマイズ
    onQueryTaskbarInfo(args) {
      const data = args.data
      
      // 右ラベルが終了日の場合、MM/dd形式でフォーマット
      if (this.labelSettings.rightLabel === 'EndDate' && data.EndDate) {
        const endDate = new Date(data.EndDate)
        if (!isNaN(endDate.getTime())) {
          const month = String(endDate.getMonth() + 1).padStart(2, '0')
          const day = String(endDate.getDate()).padStart(2, '0')
          args.rightLabel = `${month}/${day}`
        }
      }
      
      // 左ラベルが開始日の場合、MM/dd形式でフォーマット
      if (this.labelSettings.leftLabel === 'StartDate' && data.StartDate) {
        const startDate = new Date(data.StartDate)
        if (!isNaN(startDate.getTime())) {
          const month = String(startDate.getMonth() + 1).padStart(2, '0')
          const day = String(startDate.getDate()).padStart(2, '0')
          args.leftLabel = `${month}/${day}`
        }
      }
    },

    // 週末holidays（土日の背景色）を生成するメソッド
    generateWeekendHolidays() {
      const today = new Date()
      const currentYear = today.getFullYear()
      const holidays = []
      
      // 今年と来年の週末を生成（1年分）
      for (let year = currentYear; year <= currentYear + 1; year++) {
        for (let month = 0; month < 12; month++) {
          const daysInMonth = new Date(year, month + 1, 0).getDate()
          
          for (let day = 1; day <= daysInMonth; day++) {
            const date = new Date(year, month, day)
            const dayOfWeek = date.getDay()
            
            // 土曜日(6)と日曜日(0)
            if (dayOfWeek === 0 || dayOfWeek === 6) {
              holidays.push({
                from: new Date(year, month, day),
                to: new Date(year, month, day),
                label: dayOfWeek === 0 ? '日' : '土',
                cssClass: dayOfWeek === 0 ? 'weekend-sunday' : 'weekend-saturday'
              })
            }
          }
        }
      }
      
      this.weekendHolidays = holidays
      // console.log('Weekend holidays generated:', holidays.length, 'days')
    },

    // ドラッグ機能を軽量に初期化（refresh()を使わない方法）
    initializeDragFeature(ganttObj) {
      try {
        // ドラッグ機能に関連するプロパティを確実に設定
        if (ganttObj.allowRowDragAndDrop) {
          // 既存のDOMイベントハンドラーを再アタッチ
          const treeGridElement = ganttObj.element.querySelector('.e-treegrid')
          if (treeGridElement) {
            // TreeGridのドラッグ機能を再初期化
            const treeGridInstance = ganttObj.treeGrid
            if (treeGridInstance && treeGridInstance.rowDragAndDropModule) {
              // 軽量な再初期化（refresh()なし）
              treeGridInstance.rowDragAndDropModule.destroy()
              treeGridInstance.rowDragAndDropModule.addEventListener()
            }
          }
        }
      } catch (error) {
        console.error('Error initializing drag feature:', error)
        // フォールバックとして最小限の操作のみ実行
        if (ganttObj.reorderRows) {
          ganttObj.reorderRows([])
        }
      }
    }
  },
  async mounted() {
    
    // ユーザー設定を読み込み
    await this.loadUserOptions()
    
    // 週末holidays生成
    this.generateWeekendHolidays()
    
    // フルスクリーンイベントリスナーを追加
    document.addEventListener('fullscreenchange', this.handleFullscreenChange)
    document.addEventListener('mozfullscreenchange', this.handleFullscreenChange)
    document.addEventListener('webkitfullscreenchange', this.handleFullscreenChange)
    document.addEventListener('msfullscreenchange', this.handleFullscreenChange)
    
    if (!this.data || this.data.length === 0) {
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
    
    // ガントチャートの初期化確認
    this.$nextTick(() => {
      setTimeout(() => {
        const ganttInstance = this.$refs.gantt
        if (ganttInstance && ganttInstance.ej2Instances) {
          // 軽量な方法でドラッグ機能を初期化
          this.initializeDragFeature(ganttInstance.ej2Instances)
        }
      }, 200)
    })
  },
  beforeUnmount() {
    // フルスクリーンイベントリスナーを削除
    document.removeEventListener('fullscreenchange', this.handleFullscreenChange)
    document.removeEventListener('mozfullscreenchange', this.handleFullscreenChange)
    document.removeEventListener('webkitfullscreenchange', this.handleFullscreenChange)
    document.removeEventListener('msfullscreenchange', this.handleFullscreenChange)
    
    // フルスクリーンモードの場合は終了
    if (this.isFullscreen) {
      this.exitFullscreen()
    }
  }
}
</script>

<style scoped>
.gantt-container {
  position: relative;
}

/* フルスクリーンモード用のスタイル */
.fullscreen-mode {
  position: fixed !important;
  top: 0 !important;
  left: 0 !important;
  width: 100vw !important;
  height: 100vh !important;
  z-index: 9999 !important;
  background: white;
  padding: 1rem;
  box-sizing: border-box;
}

/* フルスクリーンモード時のガントチャートコンテナの調整 */
.fullscreen-mode :deep(.e-gantt) {
  height: calc(100vh - 200px) !important;
}

/* フルスクリーンAPI使用時のスタイル */
.gantt-container:fullscreen {
  padding: 1rem;
  background: white;
}

.gantt-container:fullscreen :deep(.e-gantt) {
  height: calc(100vh - 200px) !important;
}

/* webkit系ブラウザ対応 */
.gantt-container:-webkit-full-screen {
  padding: 1rem;
  background: white;
}

.gantt-container:-webkit-full-screen :deep(.e-gantt) {
  height: calc(100vh - 200px) !important;
}

/* Mozilla系ブラウザ対応 */
.gantt-container:-moz-full-screen {
  padding: 1rem;
  background: white;
}

.gantt-container:-moz-full-screen :deep(.e-gantt) {
  height: calc(100vh - 200px) !important;
}

/* MS Edge対応 */
.gantt-container:-ms-fullscreen {
  padding: 1rem;
  background: white;
}

.gantt-container:-ms-fullscreen :deep(.e-gantt) {
  height: calc(100vh - 200px) !important;
}

/* スケールコントロールのスタイル */
.scale-controls {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  border: 1px solid #cbd5e1;
}

.scale-controls select {
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.scale-controls select:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.scale-controls button {
  transition: all 0.2s ease;
}

.scale-controls button:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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

/* 行ドラッグ&ドロップのスタイル */
:deep(.e-gantt .e-dragclone) {
  background: #e3f2fd;
  border: 1px solid #2196f3;
  opacity: 0.8;
  box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

:deep(.e-gantt .e-droparea) {
  background: #e8f5e8;
  border: 2px dashed #4caf50;
}

/* 子タスクとしてドロップする際のハイライト */
:deep(.e-gantt .e-childposition) {
  background: #fff3e0 !important;
  border-left: 3px solid #ff9800;
}

/* 上下に挿入する際のハイライト */
:deep(.e-gantt .e-topposition) {
  border-top: 2px solid #2196f3;
  background: #e3f2fd;
}

:deep(.e-gantt .e-bottomposition) {
  border-bottom: 2px solid #2196f3;
  background: #e3f2fd;
}

:deep(.e-gantt .e-row-dragicon) {
  color: #666;
  cursor: grab;
}

:deep(.e-gantt .e-row-dragicon:hover) {
  color: #2196f3;
  cursor: grabbing;
}

/* グループタスクのインデント表示を強調 */
:deep(.e-gantt .e-treegridexpand),
:deep(.e-gantt .e-treegridcollapse) {
  color: #2196f3;
  font-weight: bold;
}

/* 親タスクの行を少し強調 */
:deep(.e-gantt .e-summarytaskbar) {
  background: #f5f5f5;
  border-left: 3px solid #2196f3;
}

/* タスクバーラベルのスタイル */
:deep(.e-gantt .e-left-label-container) {
  font-size: 11px;
  font-weight: 500;
  color: #374151;
  background: rgba(255, 255, 255, 0.9);
  padding: 2px 6px;
  border-radius: 3px;
  margin-right: 4px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

:deep(.e-gantt .e-right-label-container) {
  font-size: 11px;
  font-weight: 500;
  color: #374151;
  background: rgba(255, 255, 255, 0.9);
  padding: 2px 6px;
  border-radius: 3px;
  margin-left: 4px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

:deep(.e-gantt .e-task-label) {
  font-size: 10px;
  font-weight: 600;
  color: #ffffff;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

/* プログレス表示の場合のスタイル */
:deep(.e-gantt .e-task-label[data-field="Progress"]) {
  background: rgba(0, 0, 0, 0.2);
  padding: 1px 4px;
  border-radius: 2px;
}

/* 日付フォーマットのスタイル調整 */
:deep(.e-gantt .e-right-label-container[data-field="EndDate"]) {
  background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
  color: #1565c0;
  border: 1px solid #90caf9;
}

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
