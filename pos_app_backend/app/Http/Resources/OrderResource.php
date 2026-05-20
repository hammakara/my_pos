<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'user_id'        => $this->user_id,
            'user'           => $this->whenLoaded('user', fn() => [
                'id'   => $this->user->id,
                'name' => $this->user->name,
            ]),
            'table_id'       => $this->table_id,
            'table'          => $this->whenLoaded('table', fn() => new CafeTableResource($this->table)),
            'table_number'   => $this->table ? $this->table->number : 'Takeaway',
            'type'           => $this->type,
            'total'          => (float) $this->total,
            'tax'            => (float) ($this->tax ?? 0),
            'discount'       => (float) ($this->discount ?? 0),
            'status'         => $this->status,
            'payment_method' => $this->payment_method ?? 'cash',
            'payment_status' => $this->payment_status ?? 'pending',
            'paid_amount'    => $this->paid_amount ? (float) $this->paid_amount : null,
            'change_amount'  => $this->change_amount ? (float) $this->change_amount : null,
            'paid_at'        => $this->paid_at?->toISOString(),
            'items'          => $this->whenLoaded('items', fn() => $this->items->map(fn($item) => [
                'id'                 => $item->id,
                'product_variant_id' => $item->product_variant_id,
                'product_name'       => $item->variant->product->name ?? 'Unknown',
                'variant_name'       => $item->variant->size_name ?? 'Unknown',
                'category_name'      => $item->variant->product->category->name ?? '',
                'category_slug'      => $item->variant->product->category->slug ?? '',
                'quantity'           => $item->quantity,
                'unit_price'         => (float) $item->unit_price,
                'subtotal'           => (float) $item->subtotal,
                'status'             => $item->status ?? 'pending',
            ])),
            'created_at'     => $this->created_at->toISOString(),
            'updated_at'     => $this->updated_at->toISOString(),
            'created_at_human' => $this->created_at->diffForHumans(),
        ];
    }
}
