<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CafeTableResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'number'   => $this->number,
            'capacity' => $this->capacity,
            'floor'    => (int) ($this->floor ?? 1),
            'status'   => $this->status,
            'qr_code'  => $this->qr_code,
        ];
    }
}
