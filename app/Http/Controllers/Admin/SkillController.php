<?php

namespace App\Http\Controllers\Admin;

use App\Models\Skill;
use Illuminate\Database\Eloquent\Builder;

class SkillController extends CrudController
{
    protected string $model = Skill::class;

    protected string $resource = 'skills';

    protected string $singular = 'Skill';

    protected string $plural = 'Skills';

    protected function fields(): array
    {
        return [
            'name' => ['rules' => ['required', 'string', 'max:80']],
            'category' => [
                'rules' => ['required', 'string', 'max:80'],
                'suggestions' => Skill::query()->distinct()->orderBy('category')->pluck('category')->all(),
                'help' => 'Skills are grouped by category on the site.',
            ],
            'level' => ['label' => 'Level (0–100)', 'type' => 'number', 'rules' => ['nullable', 'integer', 'min:0', 'max:100'], 'help' => '90+ is highlighted as a core skill.'],
            'sort_order' => ['label' => 'Sort order', 'type' => 'number', 'rules' => ['nullable', 'integer', 'min:0', 'max:65535']],
        ];
    }

    protected function columns(): array
    {
        return ['name' => 'Skill', 'category' => 'Category', 'level' => 'Level'];
    }

    protected function query(): Builder
    {
        return Skill::query()->orderBy('category')->orderBy('sort_order');
    }
}
