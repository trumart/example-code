<?php



namespace App\Http\Filters\Route;

use App\Http\Filters\AbstractFilter;
use Illuminate\Database\Eloquent\Builder;

class RouteParamFilter extends AbstractFilter
{
    public const ID = 'id';

    public const ROUTE = 'route';

    public const HAND = 'hand';

    public const DATE = 'date';

    public const DRIVER = 'driver';

    public const CAR = 'car';

    public const TRANSFER = 'transfer';

    public const TRANSFER_DATE = 'transfer_date';

    public const STORE = 'store';

    public const DATE_START = 'date_start';

    public const DATE_FINISH = 'date_finish';

    protected function getCallbacks(): array
    {

        return [
            self::ID            => [$this, 'id'],
            self::ROUTE         => [$this, 'route'],
            self::HAND          => [$this, 'hand'],
            self::DATE          => [$this, 'date'],
            self::DRIVER        => [$this, 'driver'],
            self::CAR           => [$this, 'car'],
            self::TRANSFER      => [$this, 'transfer'],
            self::TRANSFER_DATE => [$this, 'transferDate'],
            self::STORE         => [$this, 'store'],
            self::DATE_START    => [$this, 'dateStart'],
            self::DATE_FINISH   => [$this, 'dateFinish'],
        ];
    }

    public function id(Builder $builder, $value): void
    {
        $builder->where('id', $value);
    }

    public function route(Builder $builder, $value): void
    {
        $builder->where('route', $value);
    }

    public function hand(Builder $builder, $value): void
    {
        $builder->where('hand', $value);
    }

    public function date(Builder $builder, $value): void
    {
        $builder->where('date', $value);
    }

    public function driver(Builder $builder, $value): void
    {
        $builder->where('driver', $value);
    }

    public function car(Builder $builder, $value): void
    {
        $builder->where('car', $value);
    }

    public function transfer(Builder $builder, $value): void
    {
        $builder->where('transfer', $value);
    }

    public function transferDate(Builder $builder, $value): void
    {
        $builder->where('transfer_date', $value);
    }

    public function store(Builder $builder, $value): void
    {
        $builder->where('store', 'LIKE', '%,' . $value . ',%')
            ->orWhere('store', 'LIKE', '' . $value . ',%')
            ->orWhere('store', 'LIKE', '%,' . $value . '')
            ->orWhere('store', $value);
    }

    public function dateStart(Builder $builder, $value): void
    {
        $builder->where('date', '>=', $value);
    }

    public function dateFinish(Builder $builder, $value): void
    {
        $builder->where('date', '<=', $value);
    }
}
