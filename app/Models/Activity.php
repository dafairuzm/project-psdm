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
        'duration'
    ];

    protected $casts = [
        'start_date' => 'date',
        'finish_date' => 'date',
    ];

    public function categories()
    {
        return $this->belongsToMany(ActivityCategory::class,'activity_activity_category');
    }


    public function users()
    {
        return $this->belongsToMany(User::class, 'user_activity');
    }

    public function userActivities()
    {
        return $this->hasMany(UserActivity::class);
    }
    public function activitydocs()
    {
        return $this->hasMany(Documentation::class);
    }
    public function notes()
    {
        return $this->hasMany(Note::class);
    }


}
