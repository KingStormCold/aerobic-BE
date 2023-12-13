<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Tymon\JWTAuth\Claims\Subject as JwtSubject;

class Course extends Model
{
    use HasFactory;

    protected $table = 'courses';

    protected $guarded = [];
    
}
