<?php

namespace App\Http\Controllers\Admin;

use App\Models\Experience;

class ExperienceController extends CrudController
{
    protected string $model = Experience::class;

    protected string $resource = 'experiences';

    protected string $singular = 'Experience';

    protected string $plural = 'Experience';

    protected function fields(): array
    {
        return [
            'role' => ['label' => 'Role / Job title', 'rules' => ['required', 'string', 'max:120']],
            'company' => ['rules' => ['required', 'string', 'max:120']],
            'company_type' => ['label' => 'Company type', 'help' => 'e.g. Gov. Company, Digital Agency'],
            'location' => ['help' => 'e.g. Riyadh, KSA (Remote)'],
            'start_date' => ['label' => 'Start', 'type' => 'month', 'rules' => ['required', 'date_format:Y-m']],
            'end_date' => ['label' => 'End', 'type' => 'month', 'rules' => ['nullable', 'date_format:Y-m', 'required_unless:is_current,1']],
            'is_current' => ['label' => 'I currently work here', 'type' => 'checkbox'],
            'sort_order' => ['label' => 'Sort order', 'type' => 'number', 'rules' => ['nullable', 'integer', 'min:0', 'max:65535'], 'help' => 'Lower shows first (after current job).'],
            'description' => ['label' => 'Achievements', 'type' => 'textarea', 'rules' => ['nullable', 'string', 'max:5000'], 'help' => 'One bullet per line.'],
            'tech' => ['label' => 'Tech stack', 'wide' => true, 'rules' => ['nullable', 'string', 'max:500'], 'help' => 'Comma separated.'],
        ];
    }

    protected function columns(): array
    {
        return ['role' => 'Role', 'company' => 'Company', 'period' => 'Period'];
    }

    protected function prepare(array $data): array
    {
        if ($data['is_current']) {
            $data['end_date'] = null;
        }

        return $data;
    }
}
