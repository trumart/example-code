<?php

namespace App\Http\Resources\Finance;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [
            'id'               => $this->id,
            'uid'              => $this->uid,
            'code'             => $this->code,
            'setting_id'       => $this->setting_id ,
            'setting_title'    => $this->toSetting->title,
            'store_id'         => $this->store_id,
            'store_title'      => $this->toStore->title,
            'store_cash_id'    => $this->store_cash_id,
            'store_cash_title' => $this->toStoreCash->title ?? null,
            'cashbox'          => $this->cashbox,
            'type'             => $this->type,
            'paycash'          => (bool) $this->paycash,
            'date'             => $this->date,
            'date_display'     => Carbon::parse($this->date)->translatedFormat('j F Y'),
            'title'            => $this->title,
            'text'             => $this->text,
            'text_hide'        => $this->text_hide,
            'cat_id'           => $this->cat_id,
            'cat_title'        => $this->toCat->title ?? null,
            'num'              => $this->num,
            'inn'              => $this->inn,
            'company_title'    => $this->toCompany->title ?? null,
            'sum'              => fmod($this->sum, 1) === 0.0
                                        ? number_format($this->sum, 0, '.', '')   // целое
                                        : number_format($this->sum, 2, '.', ''), // с копейками,
            'user_id'              => $this->user_id,
            'user_name'            => $this->toUser->name ?? null,
            'user_pay_id'          => $this->user_pay,
            'user_pay_name'        => $this->toUserPay->name ?? null,
            'view'                 => (bool) $this->view,
            'doc_num'              => $this->doc_num,
            'doc_date'             => $this->doc_date,
            'doc_type'             => $this->doc_type,
            'nds'                  => (bool) $this->nds,
            'distribution'         => $this->distribution,
            'moder_store_status'   => (bool) $this->moder_store_status,
            'moder_manager_status' => (bool) $this->moder_manager_status,
            'moder_store'          => $this->moder_store,
            'moder_manager'        => $this->moder_manager,
            'user_agreed_id'       => $this->moder_manager,
            'user_agreed_name'     => $this->toUserAgreed->name ?? null,
            'date_insert'          => Carbon::parse($this->created_at)->translatedFormat('j F Y H:i'),
            'date_update'          => Carbon::parse($this->updated_at)->translatedFormat('j F Y H:i'),
            'created_at'           => $this->created_at,
        ];

    }
}
