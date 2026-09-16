<?php

namespace App\Models;

use App\Models\Concerns\Ordered;
use Illuminate\Database\Eloquent\Model;

class Certification extends Model
{
    use Ordered;

    protected $fillable = ['title', 'issuer', 'year', 'sort_order'];
}
