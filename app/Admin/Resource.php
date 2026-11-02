<?php

namespace App\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * One admin section (list, detail, optional create/edit, custom actions), rendered by the
 * generic Inertia pages in resources/js/admin/Pages/Resource. Subclasses describe the data;
 * business rules stay in the app services they call from runAction().
 *
 * Column / field arrays: ['key' => 'status', 'label' => 'Status', 'type' => 'pill', …]
 *  column types: text, date, datetime, money, pill, bool
 *  field types: text, email, number, date, textarea, select, checkbox, lines (one item per line)
 */
abstract class Resource
{
    /** @var class-string<Model> */
    public static string $model;

    public static string $label;

    public static string $singular;

    public static string $group;

    /** @var string[] columns matched by the search box */
    public static array $search = [];

    public static bool $creatable = false;

    public static bool $editable = false;

    public static bool $deletable = false;

    public static function key(): string
    {
        return Str::kebab(Str::beforeLast(class_basename(static::class), 'Resource'));
    }

    public function query(): Builder
    {
        return static::$model::query()->latest('id');
    }

    /** @return array<int, array{key: string, label: string, type?: string}> */
    abstract public function columns(): array;

    /** @return array<string, array{label: string, options: array<string, string>}> */
    public function filters(): array
    {
        return [];
    }

    /** Form fields for create / edit. */
    public function fields(?Model $model): array
    {
        return [];
    }

    /** Current value of a form field on the edit page. */
    public function value(Model $model, string $field): mixed
    {
        return data_get($model, $field);
    }

    public function rules(?Model $model): array
    {
        return [];
    }

    /** Persist validated form data. */
    public function save(Model $model, array $data): Model
    {
        $model->fill($data)->save();

        return $model;
    }

    /** Detail page: sections of label/value rows and related tables. */
    public function detail(Model $model): array
    {
        return [['title' => static::$singular, 'rows' => collect($this->columns())->map(fn ($c) => [$c['label'], $this->format(data_get($model, $c['key']), $c['type'] ?? 'text')])->all()]];
    }

    /** Buttons on the detail page: ['key', 'label', 'style' => 'copper|dark|line|danger', 'confirm', 'fields' => []] */
    public function actions(Model $model): array
    {
        return [];
    }

    /** Run an action and return the success message. */
    public function runAction(Model $model, string $action, array $data): string
    {
        abort(404);
    }

    /** Title shown on the detail page. */
    public function title(Model $model): string
    {
        return (string) ($model->reference ?? $model->name ?? $model->title ?? '#'.$model->getKey());
    }

    public function row(Model $model): array
    {
        $row = ['id' => $model->getKey()];
        foreach ($this->columns() as $column) {
            $row[$column['key']] = $this->format(data_get($model, $column['key']), $column['type'] ?? 'text');
        }

        return $row;
    }

    /** Values sent to the browser are display-ready strings. */
    public function format(mixed $value, string $type = 'text'): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match ($type) {
            'date' => $value instanceof \DateTimeInterface ? $value->format('M j, Y') : $value,
            'datetime' => $value instanceof \DateTimeInterface ? $value->format('M j, Y g:i A') : $value,
            'money' => '$'.number_format((float) $value, 2),
            'pill' => Str::title(str_replace('_', ' ', (string) $value)),
            'bool' => $value ? 'Yes' : 'No',
            default => is_array($value) ? implode(', ', $value) : $value,
        };
    }

    /** Table block for detail(): rows of display values. */
    protected function table(string $title, array $headings, iterable $rows): array
    {
        return ['title' => $title, 'table' => ['headings' => $headings, 'rows' => collect($rows)->values()->all()]];
    }

    /**
     * Detail sections for a form's extra answers: form codes get readable labels (config/field_names.php)
     * and lists of items (drivers, vehicles) become their own table, one row per item.
     */
    protected function answers(string $title, ?array $answers, array $labels = []): array
    {
        $label = fn (string $key) => ucfirst($labels[$key] ?? config("field_names.{$key}") ?? str_replace('_', ' ', Str::snake($key)));
        $value = fn (mixed $v) => match (true) {
            is_bool($v) => $v ? 'Yes' : 'No',
            is_array($v) => implode(', ', array_map(fn ($x) => is_array($x) ? implode(' ', $x) : $x, $v)),
            is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) => Carbon::parse($v)->format('M j, Y'),
            default => (string) $v,
        };

        $rows = [];
        $tables = [];
        foreach ($answers ?? [] as $key => $answer) {
            if (is_array($answer) && $answer && is_array(reset($answer))) {
                $keys = collect($answer)->flatMap(fn ($item) => array_keys($item))->unique()->sortBy(fn ($k) => $k === 'name' ? 0 : 1)->values();
                $tables[] = $this->table($label($key), ['#', ...$keys->map($label)],
                    collect($answer)->values()->map(fn ($item, $i) => [$i + 1, ...$keys->map(fn ($k) => isset($item[$k]) ? $value($item[$k]) : '—')]));
            } else {
                $rows[] = [$label($key), $value($answer)];
            }
        }

        return [...($rows ? [['title' => $title, 'rows' => $rows]] : []), ...$tables];
    }

    /** @param array<string, string>|string[] $options */
    protected static function options(array $options): array
    {
        return array_is_list($options) ? array_combine($options, array_map(fn ($o) => Str::title(str_replace('_', ' ', $o)), $options)) : $options;
    }
}
