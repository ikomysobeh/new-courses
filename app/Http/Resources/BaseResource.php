<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

abstract class BaseResource extends JsonResource
{
    /**
     * Fix PHP's default json_encode() behaviour of escaping forward-slashes.
     * Without this flag, every URL in the response appears as
     * "http:\/\/..." instead of "http://...".
     */
    public function jsonOptions(): int
    {
        return JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
    }
}
