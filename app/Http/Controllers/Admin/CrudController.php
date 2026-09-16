<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Small config-driven CRUD base for the portfolio sections.
 * Each section declares its model, fields (label/type/rules) and list columns;
 * index/form views are shared, so adding a section is ~30 lines.
 */
abstract class CrudController extends Controller
{
    /** @var class-string<Model> */
    protected string $model;

    /** Route name segment, e.g. "experiences" → admin.experiences.* */
    protected string $resource;

    protected string $singular;

    protected string $plural;

    /**
     * @return array<string, array{label?: string, type?: string, rules?: array, help?: string, wide?: bool, options?: array, suggestions?: array}>
     */
    abstract protected function fields(): array;

    /** @return array<string, string> attribute => column heading */
    abstract protected function columns(): array;

    protected function query(): Builder
    {
        return $this->model::query()->ordered();
    }

    public function index(): View
    {
        return $this->view('admin.crud.index', ['items' => $this->query()->paginate(25)]);
    }

    public function create(): View
    {
        return $this->view('admin.crud.form', ['item' => new $this->model]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->model::create($this->validated($request));

        return redirect()->route("admin.{$this->resource}.index")->with('status', "{$this->singular} created.");
    }

    public function edit(string $id): View
    {
        return $this->view('admin.crud.form', ['item' => $this->model::findOrFail($id)]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $this->model::findOrFail($id)->update($this->validated($request));

        return redirect()->route("admin.{$this->resource}.index")->with('status', "{$this->singular} updated.");
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->model::findOrFail($id)->delete();

        return redirect()->route("admin.{$this->resource}.index")->with('status', "{$this->singular} deleted.");
    }

    /** Hook for per-section data shaping after validation. */
    protected function prepare(array $data): array
    {
        return $data;
    }

    protected function validated(Request $request): array
    {
        $fields = $this->normalizedFields();

        $data = $request->validate(array_map(fn (array $f) => $f['rules'], $fields));

        foreach ($fields as $name => $field) {
            match ($field['type']) {
                'checkbox' => $data[$name] = $request->boolean($name),
                'month' => $data[$name] = filled($data[$name] ?? null) ? $data[$name].'-01' : null,
                'number' => $data[$name] = ($data[$name] ?? null) === null ? null : (int) $data[$name],
                default => null,
            };
        }

        if (array_key_exists('sort_order', $data)) {
            $data['sort_order'] ??= 0;
        }

        return $this->prepare($data);
    }

    /** Fill defaults so views and validation never deal with missing keys. */
    protected function normalizedFields(): array
    {
        $out = [];
        foreach ($this->fields() as $name => $field) {
            $type = $field['type'] ?? 'text';
            $out[$name] = $field + [
                'label' => str($name)->replace('_', ' ')->ucfirst()->toString(),
                'type' => $type,
                'rules' => $type === 'checkbox' ? ['nullable', 'boolean'] : ['nullable', 'string', 'max:255'],
                'help' => null,
                'wide' => in_array($type, ['textarea'], true),
                'options' => [],
                'suggestions' => [],
            ];
        }

        return $out;
    }

    protected function view(string $view, array $data = []): View
    {
        return view($view, $data + [
            'resource' => $this->resource,
            'singular' => $this->singular,
            'plural' => $this->plural,
            'fields' => $this->normalizedFields(),
            'columns' => $this->columns(),
        ]);
    }
}
