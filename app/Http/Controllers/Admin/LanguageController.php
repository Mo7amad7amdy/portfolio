<?php

namespace App\Http\Controllers\Admin;

use App\Models\Language;

class LanguageController extends CrudController
{
    protected string $model = Language::class;

    protected string $resource = 'languages';

    protected string $singular = 'Language';

    protected string $plural = 'Languages';

    protected function fields(): array
    {
        return [
            'name' => ['rules' => ['required', 'string', 'max:60']],
            'level' => ['rules' => ['required', 'string', 'max:60'], 'suggestions' => ['Native', 'Fluent', 'Very good', 'Good', 'Basic']],
            'sort_order' => ['label' => 'Sort order', 'type' => 'number', 'rules' => ['nullable', 'integer', 'min:0', 'max:65535']],
        ];
    }

    protected function columns(): array
    {
        return ['name' => 'Language', 'level' => 'Level'];
    }
}
