<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Course;

class Video extends Model
{
    use HasFactory;

    protected $table = 'videos';

    protected $guarded = [];

    public function courses()
    {
        return $this->belongsTo(Course::class);
    }

    
}
