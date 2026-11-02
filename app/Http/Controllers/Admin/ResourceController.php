<?php

namespace App\Http\Controllers\Admin;

use App\Admin\Registry;
use App\Admin\Resource;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/** Generic list / detail / form / action endpoints for every admin Resource. */
class ResourceController extends Controller
{
    public function __construct(private Registry $registry) {}

    public function index(Request $request, string $resource): Response
    {
        $res = $this->registry->get($resource);
        $query = $res->query();

        if ($term = trim((string) $request->query('q'))) {
            $query->where(fn ($q) => collect($res::$search)->each(fn ($col) => str_contains($col, '.')
                ? $q->orWhereHas(Str::beforeLast($col, '.'), fn ($r) => $r->where(Str::afterLast($col, '.'), 'like', "%{$term}%"))
                : $q->orWhere($col, 'like', "%{$term}%")));
        }
        foreach ($res->filters() as $key => $filter) {
            $value = $request->query($key);
            if ($value !== null && $value !== '' && array_key_exists($value, $filter['options'])) {
                isset($filter['scope']) ? $filter['scope']($query, $value) : $query->where($key, $value);
            }
        }

        $page = $query->paginate(20)->withQueryString();

        return Inertia::render('Resource/Index', [
            'resource' => $this->meta($res),
            'columns' => $res->columns(),
            'filters' => collect($res->filters())->map(fn ($f) => ['label' => $f['label'], 'options' => $f['options']]),
            'query' => $request->only(['q', ...array_keys($res->filters())]),
            'rows' => $page->through(fn (Model $m) => $res->row($m)),
        ]);
    }

    public function show(string $resource, string $id): Response
    {
        $res = $this->registry->get($resource);
        $model = $this->find($res, $id);

        return Inertia::render('Resource/Show', [
            'resource' => $this->meta($res),
            'record' => ['id' => $model->getKey(), 'title' => $res->title($model)],
            'sections' => $res->detail($model),
            'actions' => array_values(array_filter($res->actions($model))),
        ]);
    }

    public function create(string $resource): Response
    {
        $res = $this->registry->get($resource);
        abort_unless($res::$creatable, 404);

        return Inertia::render('Resource/Form', ['resource' => $this->meta($res), 'fields' => $res->fields(null), 'values' => (object) [], 'recordId' => null]);
    }

    public function store(Request $request, string $resource): RedirectResponse
    {
        $res = $this->registry->get($resource);
        abort_unless($res::$creatable, 404);
        $model = $res->save(new $res::$model, $this->normalize($res->fields(null), $request->validate($res->rules(null))));

        return redirect()->route('admin.resources.show', [$resource, $model->getKey()])->with('success', "{$res::$singular} created.");
    }

    public function edit(string $resource, string $id): Response
    {
        $res = $this->registry->get($resource);
        abort_unless($res::$editable, 404);
        $model = $this->find($res, $id);
        $fields = $res->fields($model);

        return Inertia::render('Resource/Form', [
            'resource' => $this->meta($res),
            'fields' => $fields,
            'values' => collect($fields)->mapWithKeys(fn ($f) => [$f['name'] => $this->formValue($res->value($model, $f['name']), $f['type'] ?? 'text')]),
            'recordId' => $model->getKey(),
        ]);
    }

    public function update(Request $request, string $resource, string $id): RedirectResponse
    {
        $res = $this->registry->get($resource);
        abort_unless($res::$editable, 404);
        $model = $this->find($res, $id);
        $res->save($model, $this->normalize($res->fields($model), $request->validate($res->rules($model))));

        return redirect()->route('admin.resources.show', [$resource, $model->getKey()])->with('success', 'Changes saved.');
    }

    public function destroy(string $resource, string $id): RedirectResponse
    {
        $res = $this->registry->get($resource);
        abort_unless($res::$deletable, 404);
        $this->find($res, $id)->delete();

        return redirect()->route('admin.resources.index', $resource)->with('success', "{$res::$singular} deleted.");
    }

    public function action(Request $request, string $resource, string $id, string $action): RedirectResponse
    {
        $res = $this->registry->get($resource);
        $model = $this->find($res, $id);
        $definition = collect(array_filter($res->actions($model)))->firstWhere('key', $action);
        if (! $definition) {
            return back()->with('error', 'This action is no longer available — the record has changed.');
        }
        $rules = collect($definition['fields'] ?? [])->mapWithKeys(fn ($f) => [$f['name'] => $f['rules'] ?? ['nullable']])->all();

        $message = $res->runAction($model, $action, $request->validate($rules));

        return back()->with('success', $message);
    }

    private function find(Resource $res, string $id): Model
    {
        return $res->query()->whereKey($id)->firstOrFail();
    }

    private function meta(Resource $res): array
    {
        return ['key' => $res::key(), 'label' => $res::$label, 'singular' => $res::$singular, 'creatable' => $res::$creatable, 'editable' => $res::$editable, 'deletable' => $res::$deletable];
    }

    /** Form strings → model values: "lines" become arrays, checkboxes booleans. */
    private function normalize(array $fields, array $data): array
    {
        foreach ($fields as $field) {
            $name = $field['name'];
            match ($field['type'] ?? 'text') {
                'lines' => $data[$name] = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) ($data[$name] ?? ''))))),
                'checkbox' => $data[$name] = (bool) ($data[$name] ?? false),
                default => null,
            };
        }

        return $data;
    }

    private function formValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'lines' => implode("\n", (array) $value),
            'date' => $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : $value,
            'checkbox' => (bool) $value,
            default => $value,
        };
    }
}
