<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

// propsからプロジェクトデータを受け取る
const props = defineProps({
    projects: {
        type: Array,
        default: () => [],
    },
});

const projects = ref(props.projects);
const loading = ref(false);

const getStatusClass = (status) => {
    const statusClasses = {
        'planning': 'bg-gray-100 text-gray-800',
        'active': 'bg-blue-100 text-blue-800',
        'completed': 'bg-green-100 text-green-800',
        'on_hold': 'bg-yellow-100 text-yellow-800',
        'cancelled': 'bg-red-100 text-red-800',
    };
    return statusClasses[status] || 'bg-gray-100 text-gray-800';
};

const getStatusLabel = (status) => {
    const statusLabels = {
        'planning': '計画中',
        'active': '進行中',
        'completed': '完了',
        'on_hold': '保留',
        'cancelled': 'キャンセル',
    };
    return statusLabels[status] || '不明';
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('ja-JP');
};

const navigateToProject = (project) => {
    router.visit(`/projects/${project.project_key}`);
};

const navigateToGantt = (project) => {
    router.visit(`/projects/${project.project_key}/gantt`);
};
</script>

<template>
    <Head title="プロジェクト一覧" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    プロジェクト一覧
                </h2>
                <Link
                    href="/projects/create"
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                >
                    新規プロジェクト
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <!-- ローディング状態 -->
                    <div v-if="loading" class="p-6 text-center">
                        <div class="text-gray-500 dark:text-gray-400">
                            プロジェクトを読み込み中...
                        </div>
                    </div>

                    <!-- プロジェクトが空の場合 -->
                    <div v-else-if="projects.length === 0" class="p-6 text-center">
                        <div class="text-gray-500 dark:text-gray-400 mb-4">
                            プロジェクトがありません
                        </div>
                        <Link
                            href="/projects/create"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                        >
                            最初のプロジェクトを作成
                        </Link>
                    </div>

                    <!-- プロジェクト一覧テーブル -->
                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        プロジェクト名
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        ステータス
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        進捗
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        開始日
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        終了日
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        オーナー
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        アクション
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr 
                                    v-for="project in projects" 
                                    :key="project.id"
                                    class="hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
                                    @click="navigateToProject(project)"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ project.name }}
                                        </div>
                                        <div class="text-xs text-blue-600 dark:text-blue-400 font-mono mb-1">
                                            {{ project.project_key }}
                                        </div>
                                        <div v-if="project.description" class="text-sm text-gray-500 dark:text-gray-400 truncate max-w-xs">
                                            {{ project.description }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span 
                                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                            :class="getStatusClass(project.status)"
                                        >
                                            {{ getStatusLabel(project.status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                                <div 
                                                    class="bg-blue-600 h-2 rounded-full"
                                                    :style="{ width: `${project.progress || 0}%` }"
                                                ></div>
                                            </div>
                                            <span class="text-sm text-gray-600 dark:text-gray-300">
                                                {{ project.progress || 0 }}%
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ formatDate(project.start_date) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ formatDate(project.end_date) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ project.owner?.name || '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                        <button
                                            @click.stop="navigateToProject(project)"
                                            class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                        >
                                            詳細
                                        </button>
                                        <button
                                            @click.stop="navigateToGantt(project)"
                                            class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                                        >
                                            ガント
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>