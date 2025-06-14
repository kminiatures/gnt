<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'description',
        'start_date',
        'end_date',
        'duration',
        'progress',
        'status',
        'sort_order',
        'parent_id',
        'predecessor',
        'assigned_to',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'duration' => 'integer',
        'progress' => 'integer',
        'sort_order' => 'integer',
    ];

    // リレーション
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_id')->orderBy('sort_order');
    }

    // 再帰的に全ての子タスクを取得
    public function descendants(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_id')->with('descendants');
    }

    // スコープ
    public function scopeRootTasks($query)
    {
        return $query->whereNull('parent_id')->orderBy('sort_order');
    }

    public function scopeByProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // アクセサ
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'not_started' => '未開始',
            'in_progress' => '進行中',
            'completed' => '完了',
            'on_hold' => '保留',
            'cancelled' => 'キャンセル',
            default => '不明',
        };
    }

    public function getPredecessorTasksAttribute()
    {
        if (empty($this->predecessor)) {
            return collect();
        }

        $predecessorIds = explode(',', $this->predecessor);
        return Task::whereIn('id', $predecessorIds)->get();
    }

    // ミューテータ
    public function setProgressAttribute($value)
    {
        $this->attributes['progress'] = max(0, min(100, (int)$value));
    }

    public function setPredecessorAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['predecessor'] = implode(',', array_filter($value));
        } else {
            $this->attributes['predecessor'] = $value;
        }
    }

    // カスタムメソッド
    public function isRootTask(): bool
    {
        return is_null($this->parent_id);
    }

    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    public function calculateDuration(): int
    {
        if ($this->start_date && $this->end_date) {
            return $this->start_date->diffInDays($this->end_date) + 1;
        }
        return $this->duration ?? 0;
    }

    // Syncfusion Gantt Chart用のデータ形式に変換
    public function toGanttFormat(): array
    {
        return [
            'TaskID' => $this->id,
            'TaskName' => $this->name,
            'StartDate' => $this->start_date?->format('Y-m-d\TH:i:s'),
            'EndDate' => $this->end_date?->format('Y-m-d\TH:i:s'),
            'Duration' => $this->calculateDuration(),
            'Progress' => $this->progress ?? 0,
            'Predecessor' => $this->predecessor,
            'subtasks' => $this->children->map(fn($child) => $child->toGanttFormat())->toArray(),
        ];
    }
}
