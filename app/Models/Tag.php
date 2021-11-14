<?php

namespace App\Models;

use App\Models\Office;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tag extends Model
{
    use HasFactory;

    public $timestamps = false;

    public function offices()
    {
        return $this->belongsToMany(Office::class, 'offices_tags');
    }
}
