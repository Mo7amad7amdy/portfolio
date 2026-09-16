<?php

namespace App\Http\Controllers\Admin;

use App\Models\Stat;

class StatController extends CrudController
{
    protected string $model = Stat::class;

    protected string $resource = 'stats';

    protected string $singular = 'Highlight';

    protected string $plural = 'Hero highlights';

    protected function fields(): array
    {
        return [
            'value' => ['help' => 'Short, e.g. 7+ or 35%', 'rules' => ['required', 'string', 'max:20']],
            'label' => ['rules' => ['required', 'string', 'max:80']],
            'sort_order' => ['label' => 'Sort order', 'type' => 'number', 'rules' => ['nullable', 'integer', 'min:0', 'max:65535']],
        ];
    }

    protected function columns(): array
    {
        return ['value' => 'Value', 'label' => 'Label', 'sort_order' => 'Order'];
    }
}
