<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Tymon\JWTAuth\Claims\Subject as JwtSubject;
use App\Models\Subject;

class Course extends Model
{
    use HasFactory;

    protected $table = 'courses';

    protected $guarded = [];

    public function videos()
    {
        return $this->hasMany(Video::class);
    }
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
