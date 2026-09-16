<?php

namespace App\Models;

use App\Models\Concerns\Ordered;
use Illuminate\Database\Eloquent\Model;

class Stat extends Model
{
    use Ordered;

    protected $fillable = ['value', 'label', 'sort_order'];
}
