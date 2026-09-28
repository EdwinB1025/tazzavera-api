<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class User extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $model): array
    {
        /** EDB: hidden attributes are not exposed*/
        return [
            'ulid'    => $this->ulid,
            'name'    => $this->name,
            'surname' => $this->surname,
            'email'   => $this->email,
            'role'    => $this->getRoleNames()->first(),
        ];
    }
}
