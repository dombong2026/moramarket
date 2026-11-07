<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class TableQuery
{
    public const PAGE_SIZE = 10;

    /**
     * Centralized safe list search and enum filters.
     *
     * Use only developer-supplied column names/status mappings; never accept a
     * column, SQL direction or operator from an arbitrary request parameter.
     *
     * @param list<string> $searchColumns
     * @param array<string,string|bool|int> $statuses
     */
    public static function apply(
        Builder $query,
        Request $request,
        array $searchColumns,
        array $statuses = [],
        string $statusColumn = 'status'
    ): Builder {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(array_keys($statuses))],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $term = trim((string) $request->query('q', ''));
        if ($term !== '' && $searchColumns !== []) {
            $query->where(function (Builder $builder) use ($term, $searchColumns): void {
                foreach ($searchColumns as $column) {
                    $builder->orWhere($column, 'like', '%'.$term.'%');
                }
            });
        }

        $selection = $request->query('status');
        if (is_string($selection) && array_key_exists($selection, $statuses)) {
            $query->where($statusColumn, $statuses[$selection]);
        }

        return $query;
    }

    public static function paginate(Builder $query): LengthAwarePaginator
    {
        return $query->paginate(self::PAGE_SIZE)->withQueryString();
    }
}
