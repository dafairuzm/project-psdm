<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'file_path',
        'generated_at',
        'generated_by',
    ];

    protected $dates = [
        'generated_at',
    ];

    // Relasi ke User
    public function user()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}