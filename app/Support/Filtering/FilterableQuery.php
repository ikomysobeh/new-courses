<?php

namespace App\Support\Filtering;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Reusable global-filter helper for "get all" list endpoints.
 *
 * A model/service supplies a $config describing what may be searched, filtered,
 * and sorted; this trait applies the request params consistently (search term,
 * field filters, date ranges, sorting, pagination) with a per-model allow-list
 * so callers can never filter/sort on arbitrary columns.
 *
 * $config shape:
 *   [
 *     'searchable'  => ['name', 'email', 'creator.name'],       // LIKE (supports relation.column)
 *     'filters'     => ['status' => 'exact', 'title' => 'like', 'role' => 'in'],
 *     'dateColumn'  => 'created_at',   // used by date_from / date_to params
 *     'sortable'    => ['name', 'created_at'],
 *     'defaultSort' => ['created_at', 'desc'],
 *     'perPage'     => 15,
 *   ]
 */
trait FilterableQuery
{
    protected function applyFilters(Builder $query, array $params, array $config): LengthAwarePaginator
    {
        $this->applySearch($query, $params, $config);
        $this->applyFieldFilters($query, $params, $config);
        $this->applyDateRange($query, $params, $config);
        $this->applySorting($query, $params, $config);

        $perPage = (int) ($params['per_page'] ?? $config['perPage'] ?? 15);
        $perPage = max(1, min($perPage, 100));

        return $query->paginate($perPage);
    }

    private function applySearch(Builder $query, array $params, array $config): void
    {
        $term       = trim((string) ($params['search'] ?? ''));
        $searchable = $config['searchable'] ?? [];

        if ($term === '' || empty($searchable)) {
            return;
        }

        $like = '%' . $term . '%';

        $query->where(function (Builder $q) use ($searchable, $like) {
            foreach ($searchable as $column) {
                if (str_contains($column, '.')) {
                    [$relation, $col] = explode('.', $column, 2);
                    $q->orWhereHas($relation, fn ($sub) => $sub->where($col, 'like', $like));
                } else {
                    $q->orWhere($column, 'like', $like);
                }
            }
        });
    }

    private function applyFieldFilters(Builder $query, array $params, array $config): void
    {
        foreach (($config['filters'] ?? []) as $field => $type) {
            if (! array_key_exists($field, $params)) {
                continue;
            }

            $value = $params[$field];
            if ($value === null || $value === '') {
                continue;
            }

            match ($type) {
                'like'  => $query->where($field, 'like', '%' . $value . '%'),
                'in'    => $query->whereIn($field, (array) $value),
                'date'  => $query->whereDate($field, $value),
                default => $query->where($field, $value), // 'exact'
            };
        }
    }

    private function applyDateRange(Builder $query, array $params, array $config): void
    {
        $column = $config['dateColumn'] ?? null;
        if (! $column) {
            return;
        }

        if (! empty($params['date_from'])) {
            $query->whereDate($column, '>=', $params['date_from']);
        }
        if (! empty($params['date_to'])) {
            $query->whereDate($column, '<=', $params['date_to']);
        }
    }

    private function applySorting(Builder $query, array $params, array $config): void
    {
        $sortable = $config['sortable'] ?? [];
        $sort     = $params['sort'] ?? null;
        $dir      = strtolower((string) ($params['direction'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

        if ($sort && in_array($sort, $sortable, true)) {
            $query->orderBy($sort, $dir);
            return;
        }

        if (! empty($config['defaultSort'])) {
            [$col, $default] = $config['defaultSort'];
            $query->orderBy($col, $default);
        }
    }
}
