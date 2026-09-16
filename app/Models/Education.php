<?php

namespace App\Models;

use App\Models\Concerns\Ordered;
use Illuminate\Database\Eloquent\Model;

class Education extends Model
{
    use Ordered;

    protected $table = 'educations';

    protected $fillable = ['degree', 'institution', 'location', 'start_year', 'end_year', 'sort_order'];
}
