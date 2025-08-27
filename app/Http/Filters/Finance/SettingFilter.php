<?php

namespace App\Http\Filters\Finance;

use App\Http\Filters\AbstractFilter;
use Illuminate\Database\Eloquent\Builder;

class SettingFilter extends AbstractFilter
{
    public const ID = 'id';

    public const INN = 'inn';

    protected function getCallbacks(): array
    {
        return [
            self::ID  => [$this, 'id'],
            self::INN => [$this, 'inn'],
        ];
    }

    public function id(Builder $builder, $value): void
    {
        $builder->where(self::ID, $value);
    }

    public function inn(Builder $builder, $value): void
    {
        $builder->where(self::INN, $value);
    }
}
