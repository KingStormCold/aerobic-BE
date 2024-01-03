<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VideoUser extends Model
{
    use HasFactory;

    protected $table = 'videos_users';

    protected $guarded = [];

    public $timestamps = false;
}
