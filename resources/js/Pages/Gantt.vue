<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import GanttChart from '@/Components/GanttChart.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

// サンプルガントチャートデータ
const ganttData = ref([
  {
    TaskID: 1,
    TaskName: 'プロジェクト計画',
    StartDate: new Date('2024-01-01'),
    EndDate: new Date('2024-01-31'),
    Duration: 30,
    Progress: 80,
    subtasks: [
      {
        TaskID: 2,
        TaskName: '要件定義',
        StartDate: new Date('2024-01-01'),
        EndDate: new Date('2024-01-15'),
        Duration: 15,
        Progress: 100
      },
      {
        TaskID: 3,
        TaskName: '基本設計',
        StartDate: new Date('2024-01-16'),
        EndDate: new Date('2024-01-31'),
        Duration: 15,
        Progress: 60,
        Predecessor: '2'
      }
    ]
  },
  {
    TaskID: 4,
    TaskName: '開発フェーズ',
    StartDate: new Date('2024-02-01'),
    EndDate: new Date('2024-04-30'),
    Duration: 90,
    Progress: 30,
    Predecessor: '1',
    subtasks: [
      {
        TaskID: 5,
        TaskName: 'フロントエンド開発',
        StartDate: new Date('2024-02-01'),
        EndDate: new Date('2024-03-15'),
        Duration: 45,
        Progress: 50
      },
      {
        TaskID: 6,
        TaskName: 'バックエンド開発',
        StartDate: new Date('2024-02-01'),
        EndDate: new Date('2024-03-31'),
        Duration: 60,
        Progress: 40
      },
      {
        TaskID: 7,
        TaskName: 'データベース設計',
        StartDate: new Date('2024-02-01'),
        EndDate: new Date('2024-02-20'),
        Duration: 20,
        Progress: 80
      },
      {
        TaskID: 8,
        TaskName: 'API開発',
        StartDate: new Date('2024-02-21'),
        EndDate: new Date('2024-04-15'),
        Duration: 55,
        Progress: 20,
        Predecessor: '7'
      },
      {
        TaskID: 9,
        TaskName: '統合テスト',
        StartDate: new Date('2024-04-01'),
        EndDate: new Date('2024-04-30'),
        Duration: 30,
        Progress: 0,
        Predecessor: '5,6'
      }
    ]
  },
  {
    TaskID: 10,
    TaskName: 'テスト・リリース',
    StartDate: new Date('2024-05-01'),
    EndDate: new Date('2024-05-31'),
    Duration: 31,
    Progress: 0,
    Predecessor: '4',
    subtasks: [
      {
        TaskID: 11,
        TaskName: 'システムテスト',
        StartDate: new Date('2024-05-01'),
        EndDate: new Date('2024-05-15'),
        Duration: 15,
        Progress: 0
      },
      {
        TaskID: 12,
        TaskName: 'ユーザーテスト',
        StartDate: new Date('2024-05-16'),
        EndDate: new Date('2024-05-25'),
        Duration: 10,
        Progress: 0,
        Predecessor: '11'
      },
      {
        TaskID: 13,
        TaskName: '本番リリース',
        StartDate: new Date('2024-05-26'),
        EndDate: new Date('2024-05-31'),
        Duration: 5,
        Progress: 0,
        Predecessor: '12'
      }
    ]
  }
]);
</script>

<template>
    <Head title="ガントチャート" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                ガントチャート
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-full sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6">
                        <div class="mb-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                プロジェクト管理ガントチャート
                            </h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                タスクの進捗状況と依存関係を視覚的に管理できます
                            </p>
                        </div>
                        
                        <GanttChart :data="ganttData" />
                        
                        <div class="mt-6 text-sm text-gray-600 dark:text-gray-400">
                            <p><strong>操作方法:</strong></p>
                            <ul class="list-disc list-inside mt-2 space-y-1">
                                <li>タスクバーをドラッグして期間を調整</li>
                                <li>ツールバーの「Add」でタスクを追加</li>
                                <li>タスクを選択して「Edit」で詳細編集</li>
                                <li>「ZoomIn/ZoomOut」で表示倍率を調整</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>