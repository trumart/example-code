<?php

namespace App\Models\Finance;

use App\Contracts\Finance\SettingContract;
use App\Http\Filters\Finance\SettingFilter;
use App\Models\Traits\Filterable;
use Illuminate\Database\Eloquent\Concerns\HasEvents;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class Setting extends Model implements SettingContract
{
    use SoftDeletes;
    use HasEvents;
    use Filterable;
    use HasFactory;

    protected $table = 'finance_setting';

    protected $guarded = false;

    /**
     * Настройка финансовой операции
     *
     * @param array $inp
     * @return Setting|null
     */
    public function getSetting(array $inp): Setting|null
    {

        $filter = new SettingFilter($inp);

        $item = Setting::select($this->table . '.*')
            ->filter($filter)
            ->first();

        return $item;

    }

    /**
     * Настройки финансовых операций
     *
     * @param array $inp
     * @return Collection
     */
    public function getSettings(array $inp = []): Collection
    {

        $filter = new SettingFilter($inp);

        $items = Setting::select($this->table . '.*')
            ->filter($filter)
            ->get();

        return $items;

    }

    /**
     * Добавление
     *
     * @param array $inp
     * @return Setting|bool
     */
    public function insert(array $inp): Setting|bool
    {

        try {

            return Setting::create([
                'inn'       => $inp['inn'],
                'cat_id'    => $inp['cat_id'],
                'text'      => $inp['text']      ?? null,
                'unloading' => $inp['unloading'] ?? false,
                'doc_close' => $inp['doc_close'] ?? false,
                'nds'       => $inp['nds']       ?? false,
                'nds_val'   => $inp['nds_val']   ?? null,
            ]);

        } catch (\Throwable $e) {

            Log::channel('error')->error('Ошибка при сохранении настроек', [
                'exception' => $e,
                'input'     => $inp,
            ]);

            return false;

        }
    }

    /**
     * Редактирование
     *
     * @param array $inp
     * @return Setting|bool
     */
    public function edit(array $inp): Setting|bool
    {

        $cat = Setting::findOrFail($inp['id']);

        $update = $cat->update($inp);

        if (!$update) {
            return $update;
        }

        return $cat;

    }

    /**
     * Удаление
     *
     * @param int $id
     * @return bool
     */
    public function remove(int $id): bool
    {

        $setting = Setting::findOrFail($id);

        return $setting->delete();

    }

    /**
     * Связь с категориями
     *
     * @return BelongsTo
     */
    public function toCat(): BelongsTo
    {
        return $this->belongsTo(Cat::class, 'cat_id');
    }

    /**
     * Связь с компанией
     *
     * @return BelongsTo
     */
    public function toCompany(): BelongsTo
    {
        return $this->belongsTo(Finance::class, 'inn', 'inn');
    }
}
