<?php

namespace App\Models\Finance;

use App\Contracts\Finance\CatContract;
use App\Http\Filters\Finance\CatFilter;
use App\Models\Traits\Filterable;
use Illuminate\Database\Eloquent\Concerns\HasEvents;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Модель категорий финансовых операций.
 *
 */
class Cat extends Model implements CatContract
{
    use SoftDeletes;
    use HasEvents;
    use HasFactory;
    use Filterable;

    protected $table = 'finance_cat';

    protected $guarded = false;

    /**
     * Получить категорию финансовых операций по фильтру.
     *
     * @param array<string, mixed> $inp
     * @return Cat|null
     */
    public function getCat(array $inp): ?Cat
    {
        $filter = new CatFilter($inp);

        return self::select($this->table . '.*')
            ->filter($filter)
            ->with('children')
            ->first();
    }

    /**
     * Получить список категорий финансовых операций.
     *
     * @param array<string, mixed> $inp
     * @return Collection
     */
    public function getCats(array $inp = []): Collection
    {
        $filter = new CatFilter($inp);

        return self::select($this->table . '.*')
            ->filter($filter)
            ->with('children')
            ->orderBy('num', 'asc')
            ->get();

    }

    // Добавление
    public function insert(array $inp): Cat|bool
    {

        try {

            return self::create([
                'title'      => $inp['title'],
                'parent_id'  => $inp['parent_id']  ?? 0,
                'num'        => $inp['num']        ?? null,
                'operating'  => $inp['operating']  ?? null,
                'nochange'   => $inp['nochange']   ?? null,
                'noconsider' => $inp['noconsider'] ?? null,
                'document'   => $inp['document']   ?? null,
            ]);

        } catch (\Throwable $e) {

            Log::channel('error')->error('Ошибка при сохранении категории фин операции', [
                'exception' => $e,
                'input'     => $inp,
            ]);

            return false;

        }
    }

    // Удаление записи
    public function remove(int $id): bool
    {

        // Категория
        $cat = self::findOrFail($id);

        return $cat->delete();

    }

    /**
     * Редактирование
     *
     * @param array $inp
     * @return bool|Cat
     */
    public function edit(array $inp): bool|Cat
    {

        // Категория
        $cat = self::findOrFail($inp['id']);

        $update = $cat->update($inp);

        if (!$update) {
            return false;
        }

        return $cat;

    }

    /**
     * Обновление порядкового номера
     *
     * @param array $inp
     * @return bool|Cat
     */
    public function updateNum(array $inp): bool|Cat
    {

        // Категория
        $cat = self::findOrFail($inp['id']);

        $update = $cat->update($inp);

        if (!$update) {
            return false;
        }

        return $cat;

    }

    /**
     * Дочерние категории (связь hasMany).
     *
     * @return HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(Cat::class, 'parent_id', 'id');
    }
}
