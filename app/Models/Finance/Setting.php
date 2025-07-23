<?php



namespace App\Models\Finance;

use App\Contracts\Finance\SettingContract;
use App\Http\Filters\Finance\SettingFilter;
use App\Models\ClientCompany;
use App\Models\Traits\Filterable;
use Illuminate\Database\Eloquent\Concerns\HasEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class Setting extends Model implements SettingContract
{
    use SoftDeletes;
    use HasEvents;
    use Filterable;

    protected $table = 'finance_setting';

    protected $guarded = false;

    // Настройки фин операций
    public function getSetting(array $inp): Setting|null
    {

        $filter = new SettingFilter($inp);

        $item = Setting::select($this->table . '.*')
            ->filter($filter)
            ->first();

        return $item;

    }

    // Настройки фин операций
    public function getSettings(array $inp = []): Collection
    {

        $filter = new SettingFilter($inp);

        $items = Setting::select($this->table . '.*')
            ->filter($filter)
            ->get();

        return $items;

    }

    // Добавление
    public function insert(array $inp): Collection|Setting|JsonResponse
    {

        $insert = Setting::create([
            'inn'       => $inp['inn'],
            'cat_id'    => $inp['cat_id'],
            'text'      => $inp['text']      ?? null,
            'unloading' => $inp['unloading'] ?? false,
            'nds'       => $inp['nds']       ?? false,
            'nds_val'   => $inp['nds_val']   ?? null,
        ]);

        if (!$insert) {
            return apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
        }

        return $insert;

    }

    // Удаление
    public function remove(array $inp): JsonResponse|Setting
    {

        $setting = Setting::findOrFail($inp['id']);

        $delete = $setting->delete();

        if (!$delete) {

            return response()->json([
                'status'  => 'error',
                'message' => 'Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат'
            ], 422);

        }

        return response()->json([
            'status' => 'ok',
            'data'   => $setting,
        ]);

    }

    // Связь с категориями
    public function toCat(): BelongsTo
    {
        return $this->belongsTo(Cat::class, 'cat_id');
    }

    // Связь с категориями
    public function toCompany(): BelongsTo
    {
        return $this->belongsTo(ClientCompany::class, 'inn');
    }
}
