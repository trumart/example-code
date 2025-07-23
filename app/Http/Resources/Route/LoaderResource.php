<?php



namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoaderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [
            'id'             => $this->id,
            'route_param_id' => $this->route_param_id,
            'store_id'       => $this->store_id,
            'price'          => $this->price,
            'worker'         => $this->worker,
            'hour'           => $this->hour,
        ];

    }
}
