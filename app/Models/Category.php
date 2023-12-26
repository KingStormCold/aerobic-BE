<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Subject;

class Category extends Model
{
    use HasFactory;

    protected $table = 'categories';

    protected $guarded = [];
    public function subjects()
    {
        return $this->hasMany(Subject::class);
    }
}
