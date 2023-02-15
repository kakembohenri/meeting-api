<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuestPreacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'topic',
        'invitation_status',
        'meeting_id',
        'email',
        'phone',
        'church_from',
        'created_by',
        'updated_by'
    ];
}
