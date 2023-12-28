<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Category;
use App\Models\Course;
class Subject extends Model
{
    use HasFactory;

    protected $table = 'subjects';

    protected $guarded = [];

    public function categories()
    {
        return $this->belongsTo(Category::class);
    }
    public function courses(){
        return $this->hasMany(Course::class);
    }
}
