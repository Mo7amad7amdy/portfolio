<?php

namespace App\Models;

use App\Models\Concerns\Ordered;
use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    use Ordered;

    protected $fillable = ['name', 'level', 'sort_order'];
}
