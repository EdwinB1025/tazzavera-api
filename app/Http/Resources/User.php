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
            'businessName' => $this->business_name, //EDB 10/12/26: the coffee shop's business name; null for other roles
            'email'   => $this->email,
            'emailVerifiedAt' => $this->email_verified_at?->toIso8601String(),
            'role'    => $this->getRoleNames()->first(),
        ];
    }
}
