<?php



namespace App\Models\Finance;

use App\Contracts\Finance\CatContract;
use App\Http\Filters\Finance\CatFilter;
use App\Models\Traits\Filterable;
use Illuminate\Database\Eloquent\Concerns\HasEvents;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class Cat extends Model implements CatContract
{
    use SoftDeletes;
    use HasEvents;
    use HasFactory;
    use Filterable;

    protected $table = 'finance_cat';

    protected $guarded = false;

    // Категории финансовых операций
    public function getCat(array $inp): Cat|null
    {

        $filter = new CatFilter($inp);

        $item = Cat::select($this->table . '.*')
            ->filter($filter)
            ->with('children')
            ->first();

        return $item;

    }

    // Категории финансовых операций
    public function getCats(array $inp = []): Collection|Cat
    {

        $filter = new CatFilter($inp);

        $items = Cat::select($this->table . '.*')
            ->filter($filter)
            ->with('children')
            ->orderBy($this->table . '.num', 'asc')
            ->get();

        return $items;

    }

    // Добавление
    public function insert(array $inp): JsonResponse|Cat
    {

        $insert = Cat::create([
            'title'      => $inp['title'],
            'parent_id'  => $inp['parent_id'],
            'num'        => $inp['num']        ?? null,
            'operating'  => $inp['operating']  ?? null,
            'nochange'   => $inp['nochange']   ?? null,
            'noconsider' => $inp['noconsider'] ?? null,
            'document'   => $inp['document']   ?? null,
        ]);

        if (!$insert) {
            return apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
        }

        return $insert;

    }

    // Удаление записи
    public function remove(array $inp): JsonResponse|Cat
    {

        // Категория
        $cat = Cat::findOrFail($inp['id']);

        $delete = $cat->delete();

        if (!$delete) {
            return apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
        }

        return $cat;

    }

    // Редактирование
    public function edit(array $inp): JsonResponse|Cat
    {

        // Категория
        $cat = Cat::findOrFail($inp['id']);

        $update = $cat->update($inp);

        if (!$update) {
            return apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
        }

        return $cat;

    }

    // Редактирование
    public function updateNum(array $inp): JsonResponse|Cat
    {

        // Категория
        $cat = Cat::findOrFail($inp['id']);

        $update = $cat->update($inp);

        if (!$update) {
            return apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
        }

        return $cat;

    }

    // Дочерние группы
    public function children()
    {
        return $this->hasMany(Cat::class, 'parent_id')->orderBy('num');
    }
}
