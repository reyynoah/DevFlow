<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'name', 'description', 'category', 'priority',
        'start_date', 'deadline', 'created_by',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'project_members')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    // Persentase progres: task done / total task
    public function getProgressAttribute()
    {
        $total = $this->tasks()->count();
        if ($total === 0) return 0;

        $done = $this->tasks()->where('status', 'done')->count();
        return round(($done / $total) * 100);
    }
    public function activities()
{
    return $this->hasMany(ActivityLog::class)->latest();
}
}