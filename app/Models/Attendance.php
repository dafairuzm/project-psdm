<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'user_activity_id',
        'date',
        'status',
    ];

    public function userActivity(): BelongsTo
    {
        return $this->belongsTo(UserActivity::class);
    }
}
