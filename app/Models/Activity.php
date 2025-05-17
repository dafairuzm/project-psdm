<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'type',
        'category_id',
        'speaker',
        'organizer',
        'location',
        'start_date',
        'finish_date',
        'duration',
        //'documentation'
    ];

    protected $casts = [
        'start_date' => 'date',
        'finish_date' => 'date',
        //'documentation' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ActivityCategory::class, 'category_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_activity')
            ->withPivot('attendance_status')
            ->withTimestamps();
    }

    public function userActivities()
    {
        return $this->hasMany(UserActivity::class);
    }

    public function activitydocs()
    {
        return $this->hasMany(ActivityDoc::class);
    }

    public function notes()
    {
        return $this->hasMany(Note::class);
    }

}
