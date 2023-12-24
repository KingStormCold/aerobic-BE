<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Test;

class Answer extends Model
{
    use HasFactory;

    protected $table = 'answers';

    protected $guarded = [];

    public function tests()
    {
        return $this->belongsTo(Test::class);
    }
}
