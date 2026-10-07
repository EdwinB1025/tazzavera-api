<?php

namespace App\Traits;

/**
 * @mixin \Illuminate\Foundation\Http\FormRequest
 */
trait HasPagination
{
    /**EDB 10/07/26: standard page size of every paginated list; the client may ask for another one up to the maximum */
    public const PER_PAGE = 15;

    public const MAX_PER_PAGE = 50;

    /** Validation rules of the page and the page size, merged into each list request */
    protected function paginationRules(): array
    {
        return [
            'page'    => ['sometimes', 'integer', 'min:1'],
            'perPage' => ['sometimes', 'integer', 'min:1', 'max:' . self::MAX_PER_PAGE],
        ];
    }

    /** Page size of the request: the validated perPage, or the standard one */
    public function perPage(): int
    {
        return (int) $this->validated('perPage', self::PER_PAGE);
    }
}
