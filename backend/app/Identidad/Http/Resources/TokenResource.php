<?php

namespace App\Identidad\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Sanctum\NewAccessToken;

/**
 * @property NewAccessToken $resource
 */
class TokenResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'token' => $this->resource->plainTextToken,
            'tipo_token' => 'Bearer',
            'expira_en' => $this->resource->accessToken->expires_at?->toIso8601ZuluString(),
        ];
    }
}
