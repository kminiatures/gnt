<template>
  <div class="gantt-container">
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

<script>
import { GanttComponent, Edit, Toolbar, Selection, RowDD } from '@syncfusion/ej2-vue-gantt'
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
        endDateFormatted: 'EndDateFormatted'
      },
      editSettings: {
        allowTaskbarEditing: true,
        allowAdding: true,
        allowEditing: true,
        allowDeleting: true,
        allowDragAndDrop: true,
        mode: 'Auto'
      },
      toolbar: ['Add', 'Edit', 'Update', 'Delete', 'Cancel', 'ZoomIn', 'ZoomOut', 'ZoomToFit', 'PrevTimeSpan', 'NextTimeSpan'],
      columns: [
        { field: 'TaskID', visible: false },
        { field: 'TaskName', headerText: 'タスク名', width: '250' },
        { 
          field: 'StartDate', 
          headerText: '開始日', 
          width: '100',
          format: { type: 'date', format: 'MM/dd' }
        },
        { 
          field: 'EndDate', 
          headerText: '終了日', 
          width: '100',
          format: { type: 'date', format: 'MM/dd' }
        },
        { field: 'Duration', headerText: '期間', width: '80' },
        { field: 'Progress', headerText: '進捗', width: '80' },
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
    gantt: [Edit, Toolbar, Selection, RowDD]
  },
  watch: {
    data: {
      immediate: true,
      handler(newData) {
        console.log('Data watcher triggered with:', newData)
        if (newData && newData.length > 0) {
          console.log('Processing gantt data...')
          this.processedData = this.processGanttData(newData)
          console.log('Processed gantt data:', this.processedData)
        } else {
          console.log('No data to process')
        }
      }
    },
    labelSettings: {
      deep: true,
      handler(newLabelSettings) {
        console.log('Label settings changed:', newLabelSettings)
        
        // ラベル設定変更時にもデータを再処理
        if (this.data && this.data.length > 0) {
          console.log('Re-processing data due to label change...')
          this.processedData = this.processGanttData(this.data)
          console.log('Re-processed data:', this.processedData)
        }
        
        this.updateLabelsPreservingZoom()
        this.saveUserOptions()
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
          ParentID: item.ParentID || null,
          // フォーマット済み日付フィールドを追加
          StartDateFormatted: startDate ? 
            `${String(startDate.getMonth() + 1).padStart(2, '0')}/${String(startDate.getDate()).padStart(2, '0')}` : '',
          EndDateFormatted: endDate ? 
            `${String(endDate.getMonth() + 1).padStart(2, '0')}/${String(endDate.getDate()).padStart(2, '0')}` : ''
        }
        
        if (item.subtasks && item.subtasks.length > 0) {
          processedItem.subtasks = this.processGanttData(item.subtasks)
        }
        
        console.log('Processed item:', processedItem) // デバッグ用
        console.log('StartDateFormatted:', processedItem.StartDateFormatted)
        console.log('EndDateFormatted:', processedItem.EndDateFormatted)
        return processedItem
      })
    },
    onTaskbarEditing(args) {
      console.log('Taskbar editing:', args)
    },
    onTaskbarEdited(args) {
      console.log('Taskbar edited:', args)
    },
    onActionBegin(args) {
      console.log('Action begin:', args)
      
      // タスク追加の場合、一旦キャンセルしてAPIで作成後に反映
      if (args.requestType === 'beforeAdd') {
        args.cancel = true
        this.createTask(args.data)
      }
      
      // タスク削除の場合
      if (args.requestType === 'beforeDelete') {
        args.cancel = true
        this.deleteTask(args.data[0])
      }
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
    },
    async createTask(taskData) {
      console.log('Creating new task:', taskData)
      
      if (!this.projectId) {
        console.error('Project ID is required for creating tasks')
        return
      }

      // デフォルト値を設定
      const newTaskData = {
        project_id: this.projectId,
        name: taskData.TaskName || 'New Task',
        start_date: taskData.StartDate ? taskData.StartDate.toISOString().split('T')[0] : new Date().toISOString().split('T')[0],
        end_date: taskData.EndDate ? taskData.EndDate.toISOString().split('T')[0] : new Date(Date.now() + 24*60*60*1000).toISOString().split('T')[0],
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
          console.log('Task created successfully:', createdTask)
          
          // ガントチャートのデータを更新
          this.refreshGanttData()
        } else {
          console.error('Failed to create task:', response.status)
          const errorData = await response.json()
          console.error('Error details:', errorData)
        }
      } catch (error) {
        console.error('Error creating task:', error)
      }
    },
    async deleteTask(taskData) {
      console.log('Deleting task:', taskData)

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
          console.log('Task deleted successfully')
          
          // ガントチャートのデータを更新
          this.refreshGanttData()
        } else {
          console.error('Failed to delete task:', response.status)
        }
      } catch (error) {
        console.error('Error deleting task:', error)
      }
    },
    refreshGanttData() {
      // 親コンポーネントにデータの再読み込みを要求
      this.$emit('refresh-data')
    },
    async onRowDrop(args) {
      console.log('Row drop event:', args)
      
      // ドロップされたタスクの情報を取得
      const droppedTask = args.data[0] // ドラッグされたタスク
      const targetTask = args.dropRecord // ドロップ先のタスク
      const dropPosition = args.dropPosition // 'topSegment', 'bottomSegment', または 'child'
      
      console.log('Dropped task:', droppedTask)
      console.log('Target task:', targetTask)
      console.log('Drop position:', dropPosition)
      
      // 自分自身にドロップしようとした場合は無視
      if (droppedTask.TaskID === targetTask?.TaskID) {
        console.log('Cannot drop task onto itself')
        return
      }
      
      // 子タスクを親タスクにドロップしようとした場合は無視（循環参照防止）
      if (this.isDescendant(targetTask, droppedTask)) {
        console.log('Cannot drop parent task into its own descendant')
        return
      }
      
      // API呼び出しでタスクの並び順を更新
      await this.updateTaskOrder(droppedTask, targetTask, dropPosition)
    },
    async updateTaskOrder(droppedTask, targetTask, dropPosition) {
      try {
        let newParentId = null
        let newSortOrder = 1
        
        console.log(`Moving task "${droppedTask.TaskName}" to ${dropPosition} of "${targetTask?.TaskName || 'root'}"`)
        
        // ドロップ位置に応じて親IDと並び順を決定
        if (dropPosition === 'child' && targetTask) {
          // 子タスクとして追加（グループの下に移動）
          newParentId = targetTask.TaskID
          newSortOrder = this.getNextChildSortOrder(targetTask.TaskID)
          console.log(`Setting as child of task ${targetTask.TaskID} with sort order ${newSortOrder}`)
          
        } else if (targetTask) {
          // 同レベルに挿入
          newParentId = targetTask.ParentID || null
          
          if (dropPosition === 'topSegment') {
            // ターゲットの上に挿入
            newSortOrder = Math.max(1, (targetTask.sort_order || 1) - 0.5)
            console.log(`Inserting above task ${targetTask.TaskID} with sort order ${newSortOrder}`)
          } else {
            // ターゲットの下に挿入
            newSortOrder = (targetTask.sort_order || 1) + 0.5
            console.log(`Inserting below task ${targetTask.TaskID} with sort order ${newSortOrder}`)
          }
        } else {
          // ルートレベルに移動
          newParentId = null
          newSortOrder = this.getNextRootSortOrder()
          console.log(`Moving to root level with sort order ${newSortOrder}`)
        }
        
        // APIでタスクを更新
        const updateData = {
          parent_id: newParentId,
          sort_order: Math.round(newSortOrder * 10) / 10 // 小数点第1位まで
        }
        
        console.log('Sending update:', updateData)
        
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
          console.log('Task order updated successfully')
          
          // ガントチャートのデータを更新
          setTimeout(() => {
            this.refreshGanttData()
          }, 100) // 少し遅延させてUI更新を確実にする
        } else {
          console.error('Failed to update task order:', response.status)
          const errorData = await response.json()
          console.error('Error details:', errorData)
        }
      } catch (error) {
        console.error('Error updating task order:', error)
      }
    },
    
    // 循環参照チェック：targetTaskがdroppedTaskの子孫かどうか
    isDescendant(targetTask, droppedTask) {
      if (!targetTask || !droppedTask) return false
      
      // 現在のデータから子孫関係をチェック
      const checkDescendants = (task, ancestorId) => {
        if (task.TaskID === ancestorId) return true
        
        // 子タスクを再帰的にチェック
        if (task.subtasks && task.subtasks.length > 0) {
          return task.subtasks.some(child => checkDescendants(child, ancestorId))
        }
        
        return false
      }
      
      return this.processedData.some(rootTask => 
        checkDescendants(rootTask, droppedTask.TaskID) && 
        checkDescendants(rootTask, targetTask.TaskID)
      )
    },
    
    // 特定の親タスクの子タスクの次のsort_orderを取得
    getNextChildSortOrder(parentId) {
      let maxOrder = 0
      
      const findChildMaxOrder = (tasks) => {
        tasks.forEach(task => {
          if (task.ParentID === parentId) {
            maxOrder = Math.max(maxOrder, task.sort_order || 0)
          }
          if (task.subtasks && task.subtasks.length > 0) {
            findChildMaxOrder(task.subtasks)
          }
        })
      }
      
      findChildMaxOrder(this.processedData)
      return maxOrder + 1
    },
    
    // ルートレベルタスクの次のsort_orderを取得
    getNextRootSortOrder() {
      let maxOrder = 0
      
      this.processedData.forEach(task => {
        if (!task.ParentID) {
          maxOrder = Math.max(maxOrder, task.sort_order || 0)
        }
      })
      
      return maxOrder + 1
    },
    
    // スケール変更メソッド
    changeTimelineScale() {
      console.log('Changing timeline scale to:', this.selectedScale)
      
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
      console.log('Changing zoom level to:', this.selectedZoom + '%')
      
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
      console.log('Fitting gantt chart to screen')
      
      this.$nextTick(() => {
        if (this.$refs.gantt && this.$refs.gantt.ej2Instances) {
          const ganttInstance = this.$refs.gantt.ej2Instances
          ganttInstance.fitToProject()
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
          
          console.log('Preserving zoom state:', {
            timelineUnitSize: currentTimelineSettings.timelineUnitSize,
            scrollLeft: currentScrollLeft,
            scrollTop: currentScrollTop
          })
          
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
            
            console.log('Zoom state and scroll position restored')
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
          
          console.log('Saving user options:', userOptions)
          
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
            console.log('User options saved successfully')
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
          console.log('Loaded user options:', data.user_options)
          
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
            
            console.log('User options applied successfully')
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
      console.log("onQueryTaskbarInfo");
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
    }
  },
  async mounted() {
    console.log('Gantt Chart component mounted')
    
    // ユーザー設定を読み込み
    await this.loadUserOptions()
    
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
