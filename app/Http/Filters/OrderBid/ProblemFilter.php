<?php



namespace App\Http\Filters\OrderBid;

use App\Http\Filters\AbstractFilter;
use Illuminate\Database\Eloquent\Builder;

class ProblemFilter extends AbstractFilter
{
    protected string $table = 'orders_bid_problem';

    public const ID = 'id';

    public const BID_ID = 'bid_id';

    public const USER_ID = 'user_id';

    public const STORE_ID = 'store_id';

    public const STORE_SENDER_ID = 'store_sender_id';

    public const STATUS = 'status';

    public const TYPE = 'type';

    public const TEXT = 'text';

    public const DATE_START = 'date_start';

    public const DATE_FINISH = 'date_finish';

    protected function getCallbacks(): array
    {
        return [
            self::ID              => [$this, 'id'],
            self::BID_ID          => [$this, 'bidId'],
            self::USER_ID         => [$this, 'userId'],
            self::STORE_ID        => [$this, 'storeId'],
            self::STORE_SENDER_ID => [$this, 'storeSenderId'],
            self::STATUS          => [$this, 'status'],
            self::TYPE            => [$this, 'type'],
            self::TEXT            => [$this, 'text'],
            self::DATE_START      => [$this, 'dateStart'],
            self::DATE_FINISH     => [$this, 'dateFinish'],
        ];
    }

    public function id(Builder $builder, $value): void
    {
        $builder->where($this->table . '.id', $value);
    }

    public function bidId(Builder $builder, $value): void
    {
        $builder->where($this->table . '.bid_id', $value);
    }

    public function userId(Builder $builder, $value): void
    {
        $builder->where($this->table . '.user_id', $value);
    }

    public function storeId(Builder $builder, $value): void
    {
        $builder
            ->join('orders_bid', 'orders_bid.id', '=', $this->table . '.bid_id')
            ->join('orders_item', 'orders_item.id', '=', 'orders_bid.id_orders_item')
            ->join('orders', function ($join) use ($value): void {
                $join->on('orders.code', '=', 'orders_item.code')
                    ->where(function ($query) use ($value): void {
                        $query->where('orders.store', $value)
                            ->orWhere('orders.store_report', $value);
                    });
            });

    }

    public function storeSenderId(Builder $builder, $value): void
    {
        $builder
            ->join('orders_bid', function ($join) use ($value): void {
                $join->on('orders_bid.id', '=', $this->table . '.bid_id')
                    ->where('orders_bid.store_sender', $value);
            });

    }

    public function status(Builder $builder, $value): void
    {
        if (is_array($value)) {
            $builder->whereIn($this->table . '.status', $value);
        } else {
            $builder->where($this->table . '.status', $value);
        }
    }

    public function type(Builder $builder, $value): void
    {
        $builder->where($this->table . '.type', $value);
    }

    public function text(Builder $builder, $value): void
    {
        $builder->where($this->table . '.text', 'LIKE', '%' . $value . '%');
    }

    public function dateStart(Builder $builder, $value): void
    {

        $builder->where($this->table . '.created_at', '>=', $value);
    }

    public function dateFinish(Builder $builder, $value): void
    {
        $builder->where($this->table . '.created_at', '<=', $value);
    }
}
