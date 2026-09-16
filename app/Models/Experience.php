<?php

namespace App\Models;

use App\Models\Concerns\SplitsTech;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Experience extends Model
{
    use SplitsTech;

    protected $fillable = [
        'company', 'company_type', 'role', 'location', 'start_date', 'end_date',
        'is_current', 'description', 'tech', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_current' => 'boolean',
        ];
    }

    /** Current job first, then most recent. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('is_current')->orderBy('sort_order')->orderByDesc('start_date');
    }

    protected function period(): Attribute
    {
        return Attribute::get(function () {
            $end = ($this->is_current || ! $this->end_date) ? 'Present' : $this->end_date->format('M Y');

            return $this->start_date?->format('M Y').' — '.$end;
        });
    }

    /** Description lines rendered as bullet points. @return list<string> */
    public function bullets(): array
    {
        return array_values(array_filter(array_map(
            fn (string $line) => trim(ltrim(trim($line), '-•● ')),
            preg_split('/\R/', (string) $this->description)
        )));
    }
}
