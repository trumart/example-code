<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CatResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [
            'id'         => $this->id,
            'parent_id'  => $this->parent_id,
            'num'        => $this->num,
            'title'      => $this->title,
            'operating'  => (bool) ($this->operating ?? false),
            'nochange'   => (bool) ($this->nochange ?? false),
            'noconsider' => (bool) ($this->noconsider ?? false),
            'document'   => (bool) ($this->document ?? false),
            'subcats'    => CatResource::collection(
                $this->whenLoaded('children')
            ),
        ];

    }
}
