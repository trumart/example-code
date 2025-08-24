<?php

namespace App\Http\Filters\Finance;

use App\Http\Filters\AbstractFilter;
use Illuminate\Database\Eloquent\Builder;

class CatFilter extends AbstractFilter
{
    public const ID = 'id';

    public const PARENT_ID = 'parent_id';

    public const NUM = 'num';

    public const TITLE = 'title';

    public const OPERATING = 'operating';

    public const NOCHANGE = 'nochange';

    public const NOCONDSIDER = 'noconsider';

    protected function getCallbacks(): array
    {
        return [
            self::ID          => [$this, 'id'],
            self::PARENT_ID   => [$this, 'parentId'],
            self::NUM         => [$this, 'num'],
            self::TITLE       => [$this, 'title'],
            self::OPERATING   => [$this, 'operating'],
            self::NOCHANGE    => [$this, 'noChange'],
            self::NOCONDSIDER => [$this, 'noConsider'],
        ];
    }

    public function id(Builder $builder, $value): void
    {
        $builder->where(self::ID, $value);
    }

    public function parentId(Builder $builder, $value): void
    {
        $builder->where(self::PARENT_ID, $value);
    }

    public function num(Builder $builder, $value): void
    {
        $builder->where(self::NUM, $value);
    }

    public function title(Builder $builder, $value): void
    {
        $builder->where(self::TITLE, 'LIKE', '%' . $value . '%');
    }

    public function operating(Builder $builder, $value): void
    {
        if ($value === true) {
            $builder->where(self::OPERATING, $value);
        }
    }

    public function noChange(Builder $builder, $value): void
    {
        if ($value === true) {
            $builder->where(self::NOCHANGE, $value);
        }
    }

    public function noConsider(Builder $builder, $value): void
    {
        if ($value === true) {
            $builder->where(self::NOCONDSIDER, $value);
        }
    }
}
