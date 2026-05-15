<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'status'      => $this->status,
            'image'       => $this->getFirstMediaUrl('product_images'),
            'category'    => new CategoryResource($this->whenLoaded('category')),
            'variants'    => $this->whenLoaded('variants'),
            'user_id'     => $this->user_id,
            'created_at'  => $this->created_at,
        ];
    }
}
