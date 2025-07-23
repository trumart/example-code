<?php



namespace App\Http\Filters\Route;

use App\Http\Filters\AbstractFilter;
use Illuminate\Database\Eloquent\Builder;

class LoaderFilter extends AbstractFilter
{
    public const ID = 'id';

    public const ROUTE_PARAM_ID = 'route_param_id';

    public const STORE_ID = 'store_id';

    protected function getCallbacks(): array
    {
        return [
            self::ID             => [$this, 'id'],
            self::ROUTE_PARAM_ID => [$this, 'routeParamId'],
            self::STORE_ID       => [$this, 'storeId'],
        ];
    }

    public function id(Builder $builder, $value): void
    {
        $builder->where('id', $value);
    }

    public function routeParamId(Builder $builder, $value): void
    {
        $builder->where('route_param_id', $value);
    }

    public function storeId(Builder $builder, $value): void
    {
        $builder->where('store_id', $value);
    }
}
