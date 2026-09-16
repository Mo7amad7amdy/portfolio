<?php

namespace App\Models;

use App\Models\Concerns\Ordered;
use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    use Ordered;

    protected $fillable = ['category', 'name', 'level', 'sort_order'];

    protected function casts(): array
    {
        return ['level' => 'integer'];
    }
}
