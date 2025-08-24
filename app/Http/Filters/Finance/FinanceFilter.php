<?php

namespace App\Http\Filters\Finance;

use App\Http\Filters\AbstractFilter;
use App\Models\Finance\Cat;
use Illuminate\Database\Eloquent\Builder;

class FinanceFilter extends AbstractFilter
{
    public const ID = 'id';

    public const NO_ID = 'no_id';

    public const PARENT_ID = 'parent_id';

    public const UID = 'uid';

    public const CODE = 'code';

    public const NUM = 'num';

    public const TITLE = 'title';

    public const TEXT = 'text';

    public const TYPE = 'type';

    public const DATE = 'date';

    public const SUM = 'sum';

    public const CAT_ID = 'cat_id';

    public const NOCAT_ID = 'no_cat_id';

    public const STORE_ID = 'store_id';

    public const STORE_CASH_ID = 'store_cash_id';

    public const CASHBOX = 'cashbox';

    public const INN = 'inn';

    public const USER_ID = 'user_id';

    public const USER_PAY_ID = 'user_pay_id';

    public const VIEW = 'view';

    public const UPD = 'upd';

    public const DOC_NUM = 'doc_num';

    public const NDS = 'nds';

    public const PAYCASH = 'paycash';

    public const SETTING_ID = 'setting_id';

    public const SOURCE = 'source';

    public const MODER_MANAGER_STATUS = 'moder_manager_status';

    public const MODER_STORE_STATUS = 'moder_store_status';

    public const MODER_MANAGER = 'moder_manager';

    public const MODER_STORE = 'moder_store';

    public const NODISTRIBUTION = 'nodistribution';

    public const NOCONSIDER = 'noconsider';

    public const DATE_START = 'date_start';

    public const DATE_FINISH = 'date_finish';

    public const DATE_TYPE = 'date_type';

    protected function getCallbacks(): array
    {
        return [
            self::ID                   => [$this, 'id'],
            self::NO_ID                => [$this, 'noId'],
            self::PARENT_ID            => [$this, 'parentId'],
            self::UID                  => [$this, 'uid'],
            self::CODE                 => [$this, 'code'],
            self::NUM                  => [$this, 'num'],
            self::TITLE                => [$this, 'title'],
            self::TEXT                 => [$this, 'text'],
            self::TYPE                 => [$this, 'type'],
            self::DATE                 => [$this, 'date'],
            self::SUM                  => [$this, 'sum'],
            self::CAT_ID               => [$this, 'catId'],
            self::NOCAT_ID             => [$this, 'noCatId'],
            self::STORE_ID             => [$this, 'storeId'],
            self::STORE_CASH_ID        => [$this, 'storeCashId'],
            self::CASHBOX              => [$this, 'cashbox'],
            self::INN                  => [$this, 'inn'],
            self::USER_ID              => [$this, 'userId'],
            self::USER_PAY_ID          => [$this, 'userPayId'],
            self::VIEW                 => [$this, 'view'],
            self::UPD                  => [$this, 'upd'],
            self::DOC_NUM              => [$this, 'docNum'],
            self::NDS                  => [$this, 'nds'],
            self::PAYCASH              => [$this, 'payCash'],
            self::SETTING_ID           => [$this, 'settingId'],
            self::SOURCE               => [$this, 'source'],
            self::MODER_MANAGER_STATUS => [$this, 'moderManagerStatus'],
            self::MODER_STORE_STATUS   => [$this, 'moderStoreStatus'],
            self::MODER_MANAGER        => [$this, 'moderManager'],
            self::MODER_STORE          => [$this, 'moderStore'],
            self::NODISTRIBUTION       => [$this, 'nodistribution'],
            self::NOCONSIDER           => [$this, 'noconsider'],
            self::DATE_START           => [$this, 'dateStart'],
            self::DATE_FINISH          => [$this, 'dateFinish'],
        ];
    }

    public function id(Builder $builder, $value): void
    {
        if (is_array($value)) {
            $builder->whereIn(self::ID, $value);
        } else {
            $builder->where(self::ID, $value);
        }
    }

    public function noId(Builder $builder, $value): void
    {
        if (is_array($value)) {
            $builder->whereNotIn(self::ID, $value);
        } else {
            $builder->where(self::ID, '!=', $value);
        }
    }

    public function parentId(Builder $builder, $value): void
    {
        if (is_array($value)) {
            $builder->whereIn(self::PARENT_ID, $value);
        } else {
            $builder->where(self::PARENT_ID, $value);
        }
    }

    public function uid(Builder $builder, $value): void
    {
        $builder->where(self::UID, $value);
    }

    public function code(Builder $builder, $value): void
    {
        if (is_array($value)) {
            $builder->whereIn(self::CODE, $value);
        } else {
            $builder->where(self::CODE, $value);
        }
    }

    public function title(Builder $builder, $value): void
    {
        $builder->where(self::TITLE, 'LIKE', '%' . $value . '%');
    }

    public function text(Builder $builder, $value): void
    {
        $builder->where(function ($query) use ($value): void {
            $query
                ->where(self::TEXT, 'LIKE', '%' . $value . '%')
                ->orWhere('text_hide', 'LIKE', '%' . $value . '%')
                ->orWhere(self::TITLE, 'LIKE', '%' . $value . '%');
        });
    }

    public function type(Builder $builder, $value): void
    {
        if (is_array($value)) {
            $builder->whereIn(self::TYPE, $value);
        } else {
            $builder->where(self::TYPE, $value);
        }
    }

    public function date(Builder $builder, $value): void
    {
        $builder->where(self::DATE, 'LIKE', '%' . $value . '%');
    }

    public function sum(Builder $builder, $value): void
    {
        $builder->where(self::SUM, $value);
    }

    public function catId(Builder $builder, $value): void
    {
        if (is_array($value)) {
            $builder->whereIn(self::CAT_ID, $value);
        } else {
            $builder->where(self::CAT_ID, $value);
        }
    }

    public function noCatId(Builder $builder, $value): void
    {
        if (is_array($value)) {
            $builder->whereNotIn(self::CAT_ID, $value);
        } else {
            $builder->where(self::CAT_ID, '!=', $value);
        }
    }

    public function inn(Builder $builder, $value): void
    {
        if ($value === 'not null') {
            $builder->whereNotNull(self::INN);
        } else {
            if (is_array($value)) {
                $builder->whereIn(self::INN, $value);
            } else {
                $builder->where(self::INN, $value);
            }
        }
    }

    public function storeId(Builder $builder, $value): void
    {
        if (is_array($value)) {
            $builder->whereIn(self::STORE_ID, $value);
        } else {
            $builder->where(self::STORE_ID, $value);
        }
    }

    public function storeCashId(Builder $builder, $value): void
    {
        if (is_array($value)) {
            $builder->whereIn(self::STORE_CASH_ID, $value);
        } else {
            $builder->where(self::STORE_CASH_ID, $value);
        }
    }

    public function cashbox(Builder $builder, $value): void
    {
        $builder->where(self::CASHBOX, $value);
    }

    public function userId(Builder $builder, $value): void
    {
        if (is_array($value)) {
            $builder->whereIn(self::USER_ID, $value);
        } else {
            $builder->where(self::USER_ID, $value);
        }
    }

    public function userPayId(Builder $builder, $value): void
    {
        if (is_array($value)) {
            $builder->whereIn(self::USER_PAY_ID, $value);
        } else {
            $builder->where(self::USER_PAY_ID, $value);
        }
    }

    public function view(Builder $builder, $value): void
    {
        $builder->where(self::VIEW, $value);
    }

    public function nds(Builder $builder, $value): void
    {
        $builder->where(self::NDS, $value);
    }

    public function upd(Builder $builder, $value): void
    {
        if ($value === 'null') {
            $builder->whereNull(self::UPD);
        } elseif ($value === 'not null') {
            $builder->whereNotNull(self::UPD);
        } elseif (is_array($value)) {
            $builder->whereIn(self::UPD, $value);
        } else {
            $builder->where(self::UPD, $value);
        }
    }

    public function docNum(Builder $builder, $value): void
    {
        if ($value === 'null') {
            $builder->whereNull(self::DOC_NUM);
        } elseif ($value === 'not null') {
            $builder->whereNotNull(self::DOC_NUM);
        } elseif (is_array($value)) {
            $builder->whereIn(self::DOC_NUM, $value);
        } else {
            $builder->where(self::DOC_NUM, $value);
        }
    }

    public function payCash(Builder $builder, $value): void
    {
        $builder->where(self::PAYCASH, $value);
    }

    public function settingId(Builder $builder, $value): void
    {
        $builder->where(self::SETTING_ID, $value);
    }

    public function source(Builder $builder, $value): void
    {
        $builder->where(self::SOURCE, $value);
    }

    public function moderManagerStatus(Builder $builder, $value): void
    {
        if ($value == 1) {
            $builder->where(self::MODER_MANAGER_STATUS, $value);
        } else {
            $builder->where(function ($query) use ($value): void {
                $query
                    ->where(self::MODER_MANAGER_STATUS, $value)
                    ->orWhereNull(self::MODER_MANAGER_STATUS);
            });
        }
    }

    public function moderStoreStatus(Builder $builder, $value): void
    {
        if ($value == 1) {
            $builder->where(self::MODER_STORE_STATUS, $value);
        } else {
            $builder->where(function ($query) use ($value): void {
                $query
                    ->where(self::MODER_STORE_STATUS, $value)
                    ->orWhereNull(self::MODER_STORE_STATUS);
            });
        }

    }

    public function moderStore(Builder $builder, $value): void
    {
        $builder->where(function ($query) use ($value): void {
            $query
                ->whereIn(self::MODER_STORE, [$value, 1])
                ->orWhereNull(self::MODER_STORE);
        });
    }

    public function moderManager(Builder $builder, $value): void
    {
        $builder->where(self::MODER_MANAGER, $value);
    }

    public function nodistribution(Builder $builder, $value): void
    {
        $builder
            ->where(self::CAT_ID, 32)
            ->whereNull('distribution');
    }

    public function noconsider(Builder $builder, $value): void
    {

        $catsNoConside = (new Cat())->getCats([
            'noconsider' => true,
        ]);

        $arrCatNoConside = $catsNoConside->pluck('id')->toArray();

        if (!empty($arrCatNoConside)) {
            $builder->whereNotIn(self::CAT_ID, $arrCatNoConside);
        }

    }

    public function dateStart(Builder $builder, $value): void
    {

        $column = $this->getQueryParam(self::DATE_TYPE, 'date');

        $builder->where($column, '>=', $value);

    }

    public function dateFinish(Builder $builder, $value): void
    {

        $column = $this->getQueryParam(self::DATE_TYPE, 'date');

        $builder->where($column, '<=', $value);

    }
}
