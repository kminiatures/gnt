<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'status',
        'progress',
        'owner_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'progress' => 'integer',
    ];

    // リレーション
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function rootTasks(): HasMany
    {
        return $this->hasMany(Task::class)->whereNull('parent_id')->orderBy('sort_order');
    }

    // スコープ
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByOwner($query, $userId)
    {
        return $query->where('owner_id', $userId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // アクセサ
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'planning' => '計画中',
            'active' => '進行中',
            'completed' => '完了',
            'on_hold' => '保留',
            'cancelled' => 'キャンセル',
            default => '不明',
        };
    }

    // ミューテータ
    public function setProgressAttribute($value)
    {
        $this->attributes['progress'] = max(0, min(100, (int)$value));
    }
}
