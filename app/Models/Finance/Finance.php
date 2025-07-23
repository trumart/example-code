<?php

namespace App\Models\Finance;

use App\Http\Filters\Finance\FinanceFilter;
use App\Models\Store;
use App\Models\Traits\Filterable;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Finance extends Model
{
    use SoftDeletes;
    use Filterable;
    use HasFactory;

    protected $table = 'finance';

    protected $guarded = false;

    protected $path = '/var/www/ai.trumart.ru/public/files/finance/';

    // Операция
    public function getOperation(array $inp = []): Finance|null
    {

        $filter = new FinanceFilter($inp);

        $item = Finance::select($this->table . '.*')
            ->filter($filter)
            ->first();

        return $item;

    }

    // Операции
    public function getOperations(array $inp): Collection
    {

        $filter = new FinanceFilter($inp);

        $items = Finance::select($this->table . '.*')
            ->filter($filter)
            ->orderBy($this->table . '.date', 'DESC')
            ->get();

        return $items;

    }

    // Сумма операций
    public function sumOperations(array $inp = []): float
    {

        $filter = new FinanceFilter($inp);

        $sum = Finance::select(DB::raw('SUM(' . $this->table . '.sum) AS sum'))
            ->filter($filter)
            ->value('sum');

        return (float) $sum ?? 0;

    }

    // Добавление записи
    public function insert(array $inp = []): Finance|JsonResponse
    {

        $finance = Finance::create([
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
            'sum'                  => $inp['sum'],
            'user_id'              => $inp['user_id']     ?? null,
            'user_pay_id'          => $inp['user_pay_id'] ?? null,
            'view'                 => $inp['view'],
            'nds'                  => $inp['nds']          ?? null,
            'nds_val'              => $inp['nds_val']      ?? null,
            'distribution'         => $inp['distribution'] ?? null,
            'moder_store_status'   => $inp['moder_store_status'],
            'moder_manager_status' => $inp['moder_manager_status'],
            'moder_store'          => $inp['moder_store'],
            'moder_manager'        => $inp['moder_manager'],
        ]);

        if (!$finance) {
            return apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
        }

        return $finance;

    }

    // Удаление записи
    public function remove(int $operationId): bool|JsonResponse
    {

        $finance = Finance::findOrFail($operationId);

        $delete = $finance->delete();

        if (!$delete) {
            return apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
        }

        return $delete;

    }

    // Принял операцию
    public function accept(int $operationId, array $inp): Finance|JsonResponse
    {

        $finance = Finance::findOrFail($operationId);

        $update = $finance->update($inp);

        if (!$update) {

            return response()->json([
                'status'  => 'error',
                'message' => 'Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат'
            ], 422);

        }

        return $finance;

    }

    // Редактирование
    public function edit(array $inp = [])
    {

        // Фин. операция
        $finance = Finance::findOrFail($inp['id']);

        $update = $finance->update($inp);

        return $update;

    }

    // Распределение
    public function distribution(array $inp)
    {

        $finance = Finance::findOrFail($inp['id']);

        $update = $finance->update([
            'distribution' => $inp['distribution'],
        ]);

        if (!$update) {
            return apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
        }

        return $update;

    }

    // Добавление записи
    public function load($auth, array $inp = [])
    {

        // Создаем директорию
        if (!is_dir($this->path)) {
            mkdir($this->path);
        }

        // Загрузка файла
        if ($inp['file']->move($this->path, $inp['file']->getClientOriginalName())) {

            return 'TRUE';
        } else {
            return 'Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат';
        }

    }

    // Обновляем идентификатор
    public function updateUid(array $inp)
    {

        $finance = Finance::findOrFail($inp['id']);

        $update = $finance->update([
            'uid' => $inp['uid'],
        ]);

        if (!$update) {

            return response()->json([
                'status'  => 'error',
                'message' => 'Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат'
            ], 422);

        }

        return response()->json([
            'status' => 'ok',
            'data'   => $finance,
        ]);

    }

    // Связь с организацией
    public function toSetting()
    {
        return $this->belongsTo(\App\Models\Setting::class, 'setting_id');
    }

    // Связь с категориями
    public function toCat()
    {
        return $this->belongsTo(Cat::class, 'cat_id');
    }

    // Связь с подразделением
    public function toStore()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    // Связь с подразделением - кассой
    public function toStoreCash()
    {
        return $this->belongsTo(Store::class, 'store_cash_id');
    }

    // Связь с сотрудником
    public function toUser()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Связь с сотрудником
    public function toUserPay()
    {
        return $this->belongsTo(User::class, 'user_pay_id');
    }

    // Связь с сотрудником
    public function toUserAgreed()
    {
        return $this->belongsTo(User::class, 'moder_manager');
    }
}
