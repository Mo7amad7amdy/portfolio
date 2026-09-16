<?php

namespace App\Http\Controllers\Admin;

use App\Models\Project;

class ProjectController extends CrudController
{
    protected string $model = Project::class;

    protected string $resource = 'projects';

    protected string $singular = 'Project';

    protected string $plural = 'Projects';

    protected function fields(): array
    {
        return [
            'title' => ['rules' => ['required', 'string', 'max:120']],
            'client' => ['label' => 'Client / Company'],
            'url' => ['label' => 'Link', 'type' => 'url', 'rules' => ['nullable', 'url', 'max:255']],
            'sort_order' => ['label' => 'Sort order', 'type' => 'number', 'rules' => ['nullable', 'integer', 'min:0', 'max:65535']],
            'summary' => ['type' => 'textarea', 'rules' => ['required', 'string', 'max:2000']],
            'tech' => ['label' => 'Tech stack', 'wide' => true, 'rules' => ['nullable', 'string', 'max:500'], 'help' => 'Comma separated.'],
            'is_featured' => ['label' => 'Featured (shown large, first)', 'type' => 'checkbox'],
        ];
    }

    protected function columns(): array
    {
        return ['title' => 'Title', 'client' => 'Client', 'is_featured' => 'Featured'];
    }
}
