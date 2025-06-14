<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Project;
use App\Models\Task;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 既存のテストユーザーを作成
        $testUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // 追加で4人のユーザーを作成（合計5人）
        $users = User::factory(4)->create();
        $allUsers = $users->prepend($testUser);

        // 5つのプロジェクトを作成
        $projects = collect();
        
        foreach ($allUsers as $index => $user) {
            $project = Project::factory()->create([
                'owner_id' => $user->id,
                'name' => [
                    'Webアプリケーション開発プロジェクト',
                    'モバイルアプリ刷新プロジェクト', 
                    'インフラ構築プロジェクト',
                    'データ分析システム構築',
                    'マーケティングサイト制作'
                ][$index],
                'status' => ['active', 'planning', 'active', 'completed', 'on_hold'][$index],
                'priority' => ['high', 'medium', 'critical', 'low', 'medium'][$index],
            ]);
            $projects->push($project);
        }

        // 各プロジェクトに10個のタスクを作成
        foreach ($projects as $project) {
            $parentTasks = collect();
            
            // まず親タスクを5個作成
            for ($i = 0; $i < 5; $i++) {
                $parentTask = Task::factory()->create([
                    'project_id' => $project->id,
                    'assigned_to' => $allUsers->random()->id,
                    'name' => [
                        '要件定義',
                        '設計',
                        '実装',
                        'テスト',
                        'リリース準備'
                    ][$i],
                    'sort_order' => $i + 1,
                ]);
                $parentTasks->push($parentTask);
            }
            
            // 各親タスクに子タスクを1個ずつ作成（合計10個）
            foreach ($parentTasks as $index => $parentTask) {
                Task::factory()->create([
                    'project_id' => $project->id,
                    'parent_id' => $parentTask->id,
                    'assigned_to' => $allUsers->random()->id,
                    'name' => $parentTask->name . ' - 詳細作業',
                    'sort_order' => $index + 6,
                ]);
            }
        }
    }
}
