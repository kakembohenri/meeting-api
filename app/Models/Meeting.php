<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'date',
        'start_time',
        'end_time',
        'location',
        'created_by',
        'updated_at',
        'updated_by'
    ];

    public function createdBy()
    {
        return $this->hasOne(User::class);
    }
}
