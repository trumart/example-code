<?php

namespace App\Models\Finance;

use App\Contracts\Finance\FinanceContract;
use App\Http\Filters\Finance\FinanceFilter;
use App\Models\ClientCompany;
use App\Models\Store;
use App\Models\Traits\Filterable;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasEvents;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Finance extends Model implements FinanceContract
{
    use SoftDeletes;
    use HasEvents;
    use Filterable;
    use HasFactory;

    protected $table = 'finance';

    protected $guarded = false;

    /**
     * Получить одну операцию
     *
     * @param array $inp
     * @return Finance|null
     */
    public function getOperation(array $inp = []): ?Finance
    {

        $filter = new FinanceFilter($inp);

        $item = Finance::select($this->table . '.*')
            ->filter($filter)
            ->first();

        return $item;

    }

    /**
     * Получить коллекцию операций
     *
     * @param array $inp
     * @return Collection Finance[]
     */
    public function getOperations(array $inp = []): Collection
    {

        $filter = new FinanceFilter($inp);

        $items = Finance::select($this->table . '.*')
            ->filter($filter)
            ->orderBy($this->table . '.date', 'DESC')
            ->get();

        return $items;

    }

    /**
     * Сумма операций
     *
     * @param array $inp
     * @return float
     */
    public function sumOperations(array $inp = []): float
    {

        $filter = new FinanceFilter($inp);

        $sum = Finance::select(DB::raw('SUM(' . $this->table . '.sum) AS sum'))
            ->filter($filter)
            ->value('sum');

        return (float) $sum ?? 0;

    }

    /**
     * Финансовые операции требующие закрывающих документов
     *
     * @param array $inp
     * @return Collection Finance[]
     */
    public function getFinancesClosingDocNotVerified(array $inp): Collection
    {

        $filter = new FinanceFilter($inp);

        $items = Finance::select($this->table . '.date', $this->table . '.inn', $this->table . '.sum AS amount', $this->table . '.cat_id', $this->table . '.title')
            ->filter($filter)
            ->orderBy($this->table . '.date', 'DESC')
            ->get();

        return $items;

    }

    /**
     * Добавление
     *
     * @param array $inp
     * @return Finance|bool
     */
    public function insert(array $inp = []): Finance|bool
    {

        try {

            return Finance::create([
                'parent_id'            => $inp['parent_id']  ?? null,
                'uid'                  => $inp['uid']        ?? null,
                'code'                 => $inp['code']       ?? null,
                'setting_id'           => $inp['setting_id'] ?? 1,
                'store_id'             => $inp['store_id'],
                'store_cash_id'        => $inp['store_cash_id'],
                'cashbox'              => $inp['cashbox'],
                'type'                 => $inp['type'],
                'paycash'              => $inp['paycash'],
                'date'                 => $inp['date'],
                'title'                => $inp['title'],
                'text'                 => $inp['text']      ?? null,
                'text_hide'            => $inp['text_hide'] ?? null,
                'cat_id'               => $inp['cat_id'],
                'num'                  => $inp['num'] ?? null,
                'inn'                  => $inp['inn'] ?? null,
                'kpp'                  => $inp['kpp'] ?? null,
                'sum'                  => $inp['sum'],
                'user_id'              => $inp['user_id']     ?? null,
                'user_pay_id'          => $inp['user_pay_id'] ?? null,
                'view'                 => $inp['view'],
                'doc_num'              => $inp['doc_num']      ?? null,
                'doc_date'             => $inp['doc_date']     ?? null,
                'doc_type'             => $inp['doc_type']     ?? null,
                'nds'                  => $inp['nds']          ?? null,
                'nds_val'              => $inp['nds_val']      ?? null,
                'distribution'         => $inp['distribution'] ?? null,
                'source'               => $inp['source']       ?? null,
                'moder_store_status'   => $inp['moder_store_status'],
                'moder_manager_status' => $inp['moder_manager_status'],
                'moder_store'          => $inp['moder_store'],
                'moder_manager'        => $inp['moder_manager'],
            ]);

        } catch (\Throwable $e) {

            Log::channel('error')->error('Ошибка при сохранении фин операции', [
                'exception' => $e,
                'input'     => $inp,
            ]);

            return false;

        }
    }

    /**
     * Удаление
     *
     * @param int $id
     * @return bool
     */
    public function remove(int $id): bool
    {

        $finance = Finance::findOrFail($id);

        return $finance->delete();

    }

    /**
     * Принять операцию
     *
     * @param int $operationId
     * @param array $inp
     * @return Finance|JsonResponse
     */
    public function accept(int $operationId, array $inp): Finance|bool
    {

        $finance = Finance::findOrFail($operationId);

        $update = $finance->update($inp);

        if (!$update) {
            return false;
        }

        return $finance;

    }

    /**
     * Редактирование записи
     *
     * @param array $inp
     * @return int Количество обновлённых строк
     */
    public function edit(array $inp): bool
    {

        return Finance::where('id', $inp['id'])->update($inp);

    }

    /**
     * Распределение
     *
     * @param array $inp
     * @return Finance|bool
     */
    public function distribution(array $inp): Finance|bool
    {

        $finance = Finance::findOrFail($inp['id']);

        $update = $finance->update([
            'distribution' => $inp['distribution'],
        ]);

        if (!$update) {
            return false;
        }

        return $finance;

    }

    /**
     * Обновляем идентификатор
     *
     * @param array $inp
     * @return Finance|bool
     */
    public function updateUid(array $inp): Finance|bool
    {

        $finance = Finance::findOrFail($inp['id']);

        $update = $finance->update([
            'uid' => $inp['uid'],
        ]);

        if (!$update) {
            return false;
        }

        return $finance;

    }

    /**
     * Связь с Setting
     *
     * @return BelongsTo
     */
    public function toSetting()
    {
        return $this->belongsTo(\App\Models\Setting::class, 'setting_id');
    }

    /**
     * Связь с Cat
     *
     * @return BelongsTo
     */
    public function toCat()
    {
        return $this->belongsTo(Cat::class, 'cat_id');
    }

    /**
     * Связь с Store
     *
     * @return BelongsTo
     */
    public function toStore()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    /**
     * Связь с StoreCash
     *
     * @return BelongsTo
     */
    public function toStoreCash()
    {
        return $this->belongsTo(Store::class, 'store_cash_id');
    }

    /**
     * Связь с User
     *
     * @return BelongsTo
     */
    public function toUser()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Связь с User
     *
     * @return BelongsTo
     */
    public function toUserPay()
    {
        return $this->belongsTo(User::class, 'user_pay_id');
    }

    /**
     * Связь с User
     *
     * @return BelongsTo
     */
    public function toUserAgreed()
    {
        return $this->belongsTo(User::class, 'moder_manager');
    }

    /**
     * Связь с ClientCompany
     *
     * @return BelongsTo
     */
    public function toCompany(): BelongsTo
    {
        return $this->belongsTo(ClientCompany::class, 'inn', 'inn');
    }
}
