<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = ['project_id', 'user_id', 'description'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper: ActivityLog::record($project->id, 'teks aktivitas');
    public static function record(int $projectId, string $description): void
    {
        static::create([
            'project_id'  => $projectId,
            'user_id'     => auth()->id(),
            'description' => $description,
        ]);
    }
}