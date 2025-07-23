<?php



namespace App\Http\Resources\OrderBid;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProblemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [
            'id'          => $this->id,
            'bid_id'      => $this->bid_id,
            'item_id'     => $this->toBid->toOrderItem->item,
            'item_title'  => $this->toBid->toOrderItem->item_title,
            'order_code'  => $this->toBid->toOrderItem->code,
            'user_id'     => $this->user_id,
            'user_name'   => $this->toUser->name,
            'status'      => $this->status,
            'type'        => $this->type,
            'text'        => $this->text,
            'date_insert' => Carbon::parse($this->created_at)->translatedFormat('j F Y H:i'),
            'date_update' => Carbon::parse($this->updated_at)->translatedFormat('j F Y H:i'),
        ];

    }
}
