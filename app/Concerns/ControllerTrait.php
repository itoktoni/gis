<?php

namespace App\Concerns;

use App\Actions\CreateAction;
use App\Actions\DeleteAction;
use App\Actions\UpdateAction;
use App\Http\Requests\GeneralRequest;

trait ControllerTrait
{
    use PayloadTrait;

    public $model;

    public function index(GeneralRequest $request)
    {
        return redirect()->action([self::class, 'getTable']);
    }

    public function getShow(GeneralRequest $request, $id)
    {
        try {
            return $this->payload(TOAST_SUCCESS, $this->model->findOrFail($id));
        } catch (\Throwable $th) {
            return $this->payload(TOAST_FAILED, $th->getMessage());
        }
    }

    public function getUpdate(GeneralRequest $request, $id)
    {
        $data = $this->model->findOrFail($id);

        return $this->views($this->template(), [
            'model' => $data,
        ]);
    }

    public function postUpdate(GeneralRequest $request, $id)
    {
        $response = UpdateAction::run($request, $id, $this->model);

        return $this->response($response);
    }

    public function getDelete(GeneralRequest $request, $id)
    {
        $response = (new DeleteAction)->remove($id, $this->model);

        return $this->response($response);
    }

    public function getTable(GeneralRequest $request)
    {
        $data = $this->getData()->cursorPaginate($request->input('per_page', 25))->withQueryString();

        return $this->views($this->template(), [
            'data' => $data,
            'fields' => $this->getFields(),
        ]);
    }

    public function getCreate(GeneralRequest $request)
    {
        return $this->views($this->template());
    }

    public function postCreate(GeneralRequest $request)
    {
        $response = CreateAction::run($request, $this->model);

        return $this->response($response);
    }

    public function postDelete(GeneralRequest $request)
    {
        $count = DeleteAction::run($request, $this->model);

        return $this->response($count);
    }

    // END FUNCTION CONTROLLER

    protected function share($data = [])
    {
        $default = [
            'model' => $this->model,
        ];

        return array_merge($default, $data);
    }

    protected function getFields()
    {
        // Build fields for filter from model's $filterColumns
        $fields = [];
        if (property_exists($this->model, 'filterColumns') && ! empty($this->model::$filterColumns)) {
            foreach ($this->model::$filterColumns as $key => $value) {
                if ($value === false || $value === null || $value === '') {
                    continue;
                }

                if (is_numeric($key)) {
                    $fields[$value] = ucwords(str_replace('_', ' ', $value));
                } else {
                    $fields[$key] = $value;
                }
            }
        }

        return $fields;
    }

    protected function views(string $view, array $data = [], int $status = 200)
    {
        if (request()->expectsJson()) {
            return response()->json($data, $status);
        }

        return view($view, $this->share($data));
    }

    protected function template($file = null, $folder = null, $core = false)
    {
        // Get the class name (e.g., UserController)
        $className = class_basename(get_class($this));

        // Remove 'Controller' suffix and convert to lowercase
        $module = strtolower(str_replace('Controller', '', $className));

        // Get the method name (e.g., getCreate)
        $method = debug_backtrace()[1]['function'];

        // Remove 'get' or 'post' prefix and convert to lowercase
        $action = strtolower(preg_replace('/^(get|post)/', '', $method));

        if (in_array($action, ['update', 'create'])) {
            $action = 'form';
        }

        if ($file) {
            $action = $file;
        }

        if ($file === true) {
            return $module;
        }

        if ($folder) {
            $module = $folder;
        }

        $path = 'pages.';

        if ($core) {
            $path = 'core.';
        }

        return $path.$module.'.'.$action;
    }

    protected function isApi(): bool
    {
        if (request()->hasHeader('authorization')) {
            return true;
        }

        if (request()->wantsJson()) {
            return true;
        }

        return request()->expectsJson() || request()->is('api/*');
    }

    /**
     * Peta kolom FK ke relasi agar filter/sort bisa mencari NAMA, bukan ID.
     * Format: ['kolom_fk' => ['relasi', 'kolom'|'[kolom1, kolom2]']].
     * Contoh: ['voucher_id_vendor' => ['hasVendor', 'vendor_nama']]
     *
     * @var array<string, array{0: string, 1: string|array<int, string>}>
     */
    protected array $relationFilters = [];

    protected function baseQuery()
    {
        return $this->model->query();
    }

    protected function getData()
    {
        $query = $this->applyFilters($this->baseQuery());
        $this->applyDefaultOrder($query);

        return $query;
    }

    /** Urutan default bila request tidak membawa sort. Override bila perlu. */
    protected function applyDefaultOrder($query): void {}

    protected function applyFilters($query)
    {
        $request = request();

        // Filters: filters[field][operator] = value
        $filters = $request->input('filters', []);
        $availableColumns = $this->model::$filterColumns ?? [];
        $columnKeys = array_keys($availableColumns);
        // Normalize associative columns to plain list
        $allowedFields = array_map(fn ($k, $v) => is_numeric($k) ? $v : $k, $columnKeys, $availableColumns);

        foreach ($filters as $field => $conditions) {
            // Whitelist: skip fields not in $filterColumns
            if (! empty($allowedFields) && ! in_array($field, $allowedFields)) {
                continue;
            }
            $this->applyFieldFilter($query, $field, $conditions);
        }

        // Legacy _q + _field search
        $q = $request->input('_q');
        $searchField = $request->input('_field');
        if ($q && $searchField && in_array($searchField, $allowedFields)) {
            $this->applyFieldFilter($query, $searchField, ['$contains' => $q]);
        }

        // Sort: sort[0] = column:direction
        $sort = $request->input('sort.0');
        $sortColumns = $this->model::$sortColumns ?? [];
        if ($sort) {
            $parts = explode(':', $sort);
            $col = $parts[0] ?? null;
            $dir = ($parts[1] ?? 'asc') === 'desc' ? 'desc' : 'asc';
            if ($col && (empty($sortColumns) || in_array($col, $sortColumns))) {
                $query->orderBy($col, $dir);
            }
        }

        return $query;
    }

    protected function applyFieldFilter($query, string $field, $conditions): void
    {
        // Kolom FK: angka = cari ID persis, teks = cari NAMA via relasi
        if (isset($this->relationFilters[$field])) {
            [$relation, $columns] = $this->relationFilters[$field];
            $columns = (array) $columns;

            $apply = function ($value, string $operator) use ($query, $field, $relation, $columns) {
                if ($value === '' || $value === null) {
                    return;
                }
                if (is_numeric($value)) {
                    $query->where($this->model->getTable().'.'.$field, $operator === '$ne' ? '!=' : '=', $value);

                    return;
                }
                $query->whereHas($relation, function ($q) use ($columns, $value, $operator) {
                    $q->where(function ($qq) use ($columns, $value, $operator) {
                        foreach ($columns as $i => $col) {
                            $method = $i === 0 ? 'whereRaw' : 'orWhereRaw';
                            if ($operator === '$eq') {
                                $qq->$method('LOWER('.$col.') = ?', [strtolower($value)]);
                            } else {
                                $qq->$method('LOWER('.$col.') LIKE ?', ['%'.strtolower($value).'%']);
                            }
                        }
                    });
                });
            };

            if (is_array($conditions)) {
                foreach ($conditions as $operator => $value) {
                    $apply($value, $operator);
                }
            } elseif (is_string($conditions) && $conditions !== '') {
                $apply($conditions, '$contains');
            }

            return;
        }

        // Handle leftJoin if field contains dot (relation.column)
        if (str_contains($field, '.')) {
            $parts = explode('.', $field);
            $relation = $parts[0];
            $column = $parts[1];
            if (method_exists($this->model, $relation)) {
                $rel = $this->model->$relation();
                $relatedTable = $rel->getRelated()->getTable();
                $foreignKey = $rel->getQualifiedForeignKeyName();
                $ownerKey = $rel->getQualifiedOwnerKeyName();
                $query->leftJoin($relatedTable, $foreignKey, '=', $ownerKey);
                $field = $relatedTable.'.'.$column;
            }
        } elseif (! str_contains($field, '.')) {
            $field = $this->model->getTable().'.'.$field;
        }

        if (is_array($conditions)) {
            foreach ($conditions as $operator => $value) {
                if ($value === '' || $value === null) {
                    continue;
                }
                match ($operator) {
                    '$contains' => $query->whereRaw('LOWER('.$field.') LIKE ?', ['%'.strtolower($value).'%']),
                    '$notContains' => $query->whereRaw('LOWER('.$field.') NOT LIKE ?', ['%'.strtolower($value).'%']),
                    '$eq' => $query->whereRaw('LOWER('.$field.') = ?', [strtolower($value)]),
                    '$gt' => $query->where($field, '>', $value),
                    '$gte' => $query->where($field, '>=', $value),
                    '$lt' => $query->where($field, '<', $value),
                    '$lte' => $query->where($field, '<=', $value),
                    '$ne' => $query->whereRaw('LOWER('.$field.') != ?', [strtolower($value)]),
                    '$in' => $query->whereIn($field, (array) $value),
                    default => $query->whereRaw('LOWER('.$field.') LIKE ?', ['%'.strtolower($value).'%']),
                };
            }
        } elseif (is_string($conditions) && $conditions !== '') {
            $query->whereRaw('LOWER('.$field.') LIKE ?', ['%'.strtolower($conditions).'%']);
        }
    }

    protected function response(array $response, $redirect = null)
    {
        if ($this->isApi()) {
            return response()->json($response);
        } else {
            if ($response['status']) {
                flash()->success($response['message']);
            } else {
                flash()->error($response['data']);
            }
        }

        if ($redirect) {
            return $redirect;
        }

        return redirect()->back();
    }

    protected function respondView(string $view, array $data = [])
    {
        if ($this->isApi()) {
            return response()->json($data['model'] ?? $data[array_key_first($data)] ?? $data);
        }

        return view($view, $data);
    }
}
