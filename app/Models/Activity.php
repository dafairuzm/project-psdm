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
        'reference',
        'type',
        'category_id',
        'organizer',
        'location',
        'start_date',
        'finish_date',
        'duration'
    ];

    protected $casts = [
        'reference' => 'array',
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
    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

}
