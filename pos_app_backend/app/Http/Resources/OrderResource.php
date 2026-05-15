<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'table'    => $this->table ? $this->table->number : 'Takeaway',
            'type'     => $this->type,
            'status'   => $this->status,
            'total'    => $this->total,
            'items'    => $this->items->map(function ($item) {
                return [
                    'product'  => $item->variant->product->name,
                    'size'     => $item->variant->size_name,
                    'quantity' => $item->quantity,
                    'status'   => $item->status,
                ];
            }),
            'created_at' => $this->created_at->diffForHumans(),
        ];
    }
}
