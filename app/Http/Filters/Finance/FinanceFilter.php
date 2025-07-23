<?php



namespace App\Http\Filters\Finance;

use App\Http\Filters\AbstractFilter;
use App\Models\Finance\Cat;
use Illuminate\Database\Eloquent\Builder;

class FinanceFilter extends AbstractFilter
{
    public const ID = 'id';

    public const UID = 'uid';

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

    public const INN = 'inn';

    public const USER_ID = 'user_id';

    public const USER_PAY_ID = 'user_pay_id';

    public const VIEW = 'view';

    public const NDS = 'nds';

    public const PAYCASH = 'paycash';

    public const SETTING = 'setting';

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
            self::UID                  => [$this, 'uid'],
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
            self::INN                  => [$this, 'inn'],
            self::USER_ID              => [$this, 'userId'],
            self::USER_PAY_ID          => [$this, 'userPayId'],
            self::VIEW                 => [$this, 'view'],
            self::NDS                  => [$this, 'nds'],
            self::PAYCASH              => [$this, 'payCash'],
            self::SETTING              => [$this, 'setting'],
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
        $builder->where('id', $value);
    }

    public function uid(Builder $builder, $value): void
    {
        $builder->where('uid', $value);
    }

    public function title(Builder $builder, $value): void
    {
        $builder->where('title', 'LIKE', '%' . $value . '%');
    }

    public function text(Builder $builder, $value): void
    {
        $builder
            ->where('text', 'LIKE', '%' . $value . '%')
            ->orWhere('text_hide', 'LIKE', '%' . $value . '%')
            ->orWhere('title', 'LIKE', '%' . $value . '%');
    }

    public function type(Builder $builder, $value): void
    {
        if (is_array($value)) {
            $builder->whereIn('type', $value);
        } else {
            $builder->where('type', $value);
        }
    }

    public function date(Builder $builder, $value): void
    {
        $builder->where('date', 'LIKE', '%' . $value . '%');
    }

    public function sum(Builder $builder, $value): void
    {
        $builder->where('sum', $value);
    }

    public function catId(Builder $builder, $value): void
    {
        if (is_array($value)) {
            $builder->whereIn('cat_id', $value);
        } else {
            $builder->where('cat_id', $value);
        }
    }

    public function noCatId(Builder $builder, $value): void
    {
        if (is_array($value)) {
            $builder->whereNotIn('cat_id', $value);
        } else {
            $builder->where('cat_id', '!=', $value);
        }
    }

    public function inn(Builder $builder, $value): void
    {
        if (is_array($value)) {
            $builder->whereIn('inn', $value);
        } else {
            $builder->where('inn', $value);
        }
    }

    public function storeId(Builder $builder, $value): void
    {
        if (is_array($value)) {
            $builder->whereIn('store_id', $value);
        } else {
            $builder->where('store_id', $value);
        }
    }

    public function storeCashId(Builder $builder, $value): void
    {
        if (is_array($value)) {
            $builder->whereIn('store_cash_id', $value);
        } else {
            $builder->where('store_cash_id', $value);
        }
    }

    public function userId(Builder $builder, $value): void
    {
        if (is_array($value)) {
            $builder->whereIn('user_id', $value);
        } else {
            $builder->where('user_id', $value);
        }
    }

    public function userPayId(Builder $builder, $value): void
    {
        if (is_array($value)) {
            $builder->whereIn('user_pay_id', $value);
        } else {
            $builder->where('user_pay_id', $value);
        }
    }

    public function view(Builder $builder, $value): void
    {
        $builder->where('view', $value);
    }

    public function nds(Builder $builder, $value): void
    {
        $builder->where('nds', $value);
    }

    public function payCash(Builder $builder, $value): void
    {
        $builder->where('paycash', $value);
    }

    public function setting(Builder $builder, $value): void
    {
        $builder->where('setting', $value);
    }

    public function moderManagerStatus(Builder $builder, $value): void
    {
        if ($value == 1) {
            $builder->where('moder_manager_status', $value);
        } else {
            $builder->where(function ($query) use ($value): void {
                $query
                    ->where('moder_manager_status', $value)
                    ->orWhereNull('moder_manager_status');
            });
        }
    }

    public function moderStoreStatus(Builder $builder, $value): void
    {
        if ($value == 1) {
            $builder->where('moder_store_status', $value);
        } else {
            $builder->where(function ($query) use ($value): void {
                $query
                    ->where('moder_store_status', $value)
                    ->orWhereNull('moder_store_status');
            });
        }

    }

    public function moderStore(Builder $builder, $value): void
    {
        $builder->where(function ($query) use ($value): void {
            $query
                ->whereIn('moder_store', [$value, 1])
                ->orWhereNull('moder_store');
        });
    }

    public function moderManager(Builder $builder, $value): void
    {
        $builder->where('moder_manager', $value);
    }

    public function nodistribution(Builder $builder, $value): void
    {
        $builder
            ->where('cat_id', 32)
            ->whereNull('distribution');
    }

    public function noconsider(Builder $builder, $value): void
    {

        $catsNoConside = (new Cat())->getCats([
            'noconsider' => true,
        ]);

        $arrCatNoConside = $catsNoConside->pluck('id')->toArray();

        if (!empty($arrCatNoConside)) {
            $builder->whereNotIn('cat_id', $arrCatNoConside);
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
