<?php

namespace App\Http\Controllers\Admin;

use App\Models\Education;

class EducationController extends CrudController
{
    protected string $model = Education::class;

    protected string $resource = 'educations';

    protected string $singular = 'Education';

    protected string $plural = 'Education';

    protected function fields(): array
    {
        return [
            'degree' => ['rules' => ['required', 'string', 'max:160']],
            'institution' => ['rules' => ['required', 'string', 'max:160']],
            'location' => [],
            'start_year' => ['label' => 'Start year', 'rules' => ['nullable', 'string', 'max:10']],
            'end_year' => ['label' => 'End year', 'rules' => ['nullable', 'string', 'max:10']],
            'sort_order' => ['label' => 'Sort order', 'type' => 'number', 'rules' => ['nullable', 'integer', 'min:0', 'max:65535']],
        ];
    }

    protected function columns(): array
    {
        return ['degree' => 'Degree', 'institution' => 'Institution', 'end_year' => 'Graduated'];
    }
}
