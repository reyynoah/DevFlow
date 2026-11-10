<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'project_id', 'title', 'description',
        'status', 'priority', 'deadline', 'assigned_to',
    ];
    protected $casts = [
        'deadline' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
    public function comments()
{
    return $this->hasMany(Comment::class)->latest();
}
}