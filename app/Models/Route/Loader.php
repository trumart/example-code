<?php



namespace App\Models\Route;

use App\Contracts\Route\LoaderContract;
use App\Http\Filters\Route\LoaderFilter;
use App\Models\Traits\Filterable;
use Illuminate\Database\Eloquent\Concerns\HasEvents;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class Loader extends Model implements LoaderContract
{
    use SoftDeletes;
    use HasEvents;
    use HasFactory;
    use Filterable;

    protected $table = 'routes_loader';

    protected $guarded = false;

    // Категории финансовых операций
    public function getLoader(array $inp): Loader|null
    {

        $filter = new LoaderFilter($inp);

        $item = Loader::select($this->table . '.*')
            ->filter($filter)
            ->first();

        return $item;

    }

    // Категории финансовых операций
    public function getLoaders(array $inp = []): Collection|Loader
    {

        $filter = new LoaderFilter($inp);

        $items = Loader::select($this->table . '.*')
            ->filter($filter)
            ->get();

        return $items;

    }

    // Добавление
    public function insert(array $inp): JsonResponse|Loader
    {

        $insert = Loader::create([
            'route_param_id' => $inp['route_param_id'],
            'store_id'       => $inp['store_id'],
            'price'          => $inp['price'],
            'worker'         => $inp['worker'],
            'hour'           => $inp['hour'],
        ]);

        if (!$insert) {
            return apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
        }

        return $insert;

    }

    // Удаление записи
    public function remove(array $inp): JsonResponse|Loader
    {

        // Категория
        $cat = Loader::findOrFail($inp['id']);

        $delete = $cat->delete();

        if (!$delete) {
            return apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
        }

        return $cat;

    }
}
