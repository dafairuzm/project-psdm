<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityDoc extends Model
{
    protected $fillable = [
        'activity_id',
        'documentation',
        'user_id'
    ];

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
