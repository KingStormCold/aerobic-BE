<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Video;

class Test extends Model
{
    use HasFactory;

    protected $table = 'tests';

    protected $guarded = [];

    public function videos()
    {
        return $this->belongsTo(Video::class);
    }
}
