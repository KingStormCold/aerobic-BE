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

    public $fillable = ['name', 'description', 'level', 'price', 'subject_id', 'created_by', 'promotional_price', 'updated_by'];

    public function subjects()
    {
        return $this->belongsTo(Subject::class);
    }
}
