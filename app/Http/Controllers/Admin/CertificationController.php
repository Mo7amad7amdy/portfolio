<?php

namespace App\Http\Controllers\Admin;

use App\Models\Certification;

class CertificationController extends CrudController
{
    protected string $model = Certification::class;

    protected string $resource = 'certifications';

    protected string $singular = 'Certification';

    protected string $plural = 'Certifications';

    protected function fields(): array
    {
        return [
            'title' => ['rules' => ['required', 'string', 'max:160']],
            'issuer' => [],
            'year' => ['rules' => ['nullable', 'string', 'max:10']],
            'sort_order' => ['label' => 'Sort order', 'type' => 'number', 'rules' => ['nullable', 'integer', 'min:0', 'max:65535']],
        ];
    }

    protected function columns(): array
    {
        return ['title' => 'Title', 'issuer' => 'Issuer', 'year' => 'Year'];
    }
}
