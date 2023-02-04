<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonPositions extends Model
{
    use HasFactory;

    protected $fillable = ['person', 'pos_x', 'pos_y', 'raw_data', 'timestamp'];
}
