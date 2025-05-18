<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class UserActivity extends Model
{
    protected $table = 'user_activity';

    protected $fillable = [
        'user_id',
        'activity_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'user_activity_id');
    }


}

