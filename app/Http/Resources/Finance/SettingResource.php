<?php



namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [
            'id'            => $this->id,
            'inn'           => $this->inn,
            'cat_id'        => $this->cat_id,
            'cat_title'     => $this->toCat->title,
            'text'          => $this->text,
            'unloading'     => (bool) ($this->unloading ?? false),
            'nds'           => (bool) ($this->nds ?? false),
            'nds_val'       => $this->nds_val,
            'company_title' => $this->toCompany->title ?? null,
        ];

    }
}
