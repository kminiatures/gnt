<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import GanttChart from '@/Components/GanttChart.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

// propsからプロジェクトキーを受け取る
const props = defineProps({
    projectKey: {
        type: String,
        required: true,
    },
});

const ganttData = ref([]);
const project = ref(null);
const loading = ref(true);

// プロジェクト情報とガントデータを取得
const fetchProjectData = async () => {
    try {
        // プロジェクト情報を取得（project_keyで検索）
        const projectResponse = await fetch(`/api/projects?project_key=${props.projectKey}`, {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });
        
        if (projectResponse.ok) {
            const projects = await projectResponse.json();
            const projectData = projects.data || projects;
            project.value = Array.isArray(projectData) ? projectData[0] : projectData;
        }

        // プロジェクトが見つかった場合、ガントチャートデータを取得
        if (project.value) {
            const ganttResponse = await fetch(`/api/projects/${project.value.id}/gantt`, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });
            
            if (ganttResponse.ok) {
                ganttData.value = await ganttResponse.json();
            }
        }
    } catch (error) {
        console.error('プロジェクトデータの取得に失敗しました:', error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchProjectData();
});
</script>

<template>
    <Head :title="`${project?.name || 'プロジェクト'} - ガントチャート`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <nav class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
                        <Link :href="route('projects.index')" class="hover:text-gray-700 dark:hover:text-gray-300">
                            プロジェクト一覧
                        </Link>
                        <span>›</span>
                        <Link 
                            :href="route('projects.show', projectKey)" 
                            class="hover:text-gray-700 dark:hover:text-gray-300"
                        >
                            {{ project?.name || 'プロジェクト詳細' }}
                        </Link>
                        <span>›</span>
                        <span class="text-gray-900 dark:text-gray-100">ガントチャート</span>
                    </nav>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        {{ project?.name || 'プロジェクト' }} - ガントチャート
                    </h2>
                </div>
                <div class="flex space-x-3">
                    <Link
                        :href="route('projects.show', projectKey)"
                        class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                    >
                        プロジェクト詳細
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-full sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <!-- ローディング状態 -->
                    <div v-if="loading" class="p-6 text-center">
                        <div class="text-gray-500 dark:text-gray-400">
                            ガントチャートを読み込み中...
                        </div>
                    </div>

                    <!-- プロジェクト情報 -->
                    <div v-else-if="project" class="p-6">
                        <div class="mb-6 bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                                {{ project.name }}
                            </h3>
                            <p v-if="project.description" class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                {{ project.description }}
                            </p>
                            <div class="flex flex-wrap gap-4 text-sm">
                                <span class="flex items-center">
                                    <strong class="mr-1">ステータス:</strong>
                                    <span 
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                        :class="{
                                            'bg-gray-100 text-gray-800': project.status === 'planning',
                                            'bg-blue-100 text-blue-800': project.status === 'active',
                                            'bg-green-100 text-green-800': project.status === 'completed',
                                            'bg-yellow-100 text-yellow-800': project.status === 'on_hold',
                                            'bg-red-100 text-red-800': project.status === 'cancelled',
                                        }"
                                    >
                                        {{ 
                                            project.status === 'planning' ? '計画中' :
                                            project.status === 'active' ? '進行中' :
                                            project.status === 'completed' ? '完了' :
                                            project.status === 'on_hold' ? '保留' :
                                            project.status === 'cancelled' ? 'キャンセル' : '不明'
                                        }}
                                    </span>
                                </span>
                                <span v-if="project.start_date">
                                    <strong>開始日:</strong> {{ new Date(project.start_date).toLocaleDateString('ja-JP') }}
                                </span>
                                <span v-if="project.end_date">
                                    <strong>終了日:</strong> {{ new Date(project.end_date).toLocaleDateString('ja-JP') }}
                                </span>
                                <span v-if="project.progress !== null">
                                    <strong>進捗:</strong> {{ project.progress }}%
                                </span>
                            </div>
                        </div>
                        
                        <!-- ガントチャート表示 -->
                        <GanttChart 
                            :data="ganttData" 
                            :projectId="project.id"
                            @refresh-data="fetchProjectData"
                        />
                        
                        <!-- タスクがない場合のメッセージ -->
                        <div v-if="ganttData.length === 0" class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-center">
                            <div class="text-blue-700 dark:text-blue-300 mb-2">
                                このプロジェクトにはまだタスクがありません
                            </div>
                            <p class="text-sm text-blue-600 dark:text-blue-400">
                                上のツールバーの「Add」ボタンでタスクを追加してください
                            </p>
                        </div>
                        
                        <div class="mt-6 text-sm text-gray-600 dark:text-gray-400">
                            <p><strong>操作方法:</strong></p>
                            <ul class="list-disc list-inside mt-2 space-y-1">
                                <li>タスクバーをドラッグして期間を調整</li>
                                <li>左側のタスク行をドラッグして並び順を変更</li>
                                <li>タスクを他のタスクの中央にドロップしてグループの下に移動</li>
                                <li>タスクを他のタスクの上下端にドロップして同レベルに挿入</li>
                                <li>ツールバーの「Add」でタスクを追加</li>
                                <li>タスクを選択して「Edit」で詳細編集</li>
                                <li>「Delete」でタスクを削除</li>
                            </ul>
                        </div>
                    </div>

                    <!-- プロジェクトが見つからない場合 -->
                    <div v-else class="p-6 text-center">
                        <div class="text-gray-500 dark:text-gray-400 mb-4">
                            プロジェクトが見つかりません
                        </div>
                        <Link
                            :href="route('projects.index')"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                        >
                            プロジェクト一覧に戻る
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>