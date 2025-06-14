<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->string('name');
            $table->enum('priority', [
              'critical',
              'high',
              'medium',
              'low',
            ])->default('medium');
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('duration')->nullable(); // 期間（日数）
            $table->integer('progress')->default(0); // 0-100%
            $table->integer('estimated_hours')->default(0);
            $table->integer('actual_hours')->default(0);

            $table->enum('status', ['not_started', 'in_progress', 'completed', 'on_hold', 'pending', 'cancelled'])->default('not_started');
            $table->integer('sort_order')->default(0); // 表示順序
            $table->foreignId('parent_id')->nullable()->constrained('tasks')->onDelete('cascade'); // 親タスク（階層構造用）
            $table->string('predecessor')->nullable(); // 依存関係（カンマ区切りでタスクIDを格納）
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['project_id', 'sort_order']);
            $table->index(['status', 'start_date']);
            $table->index('parent_id');
            $table->index('assigned_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
