<?php



namespace App\Models\OrderBid;

use App\Http\Filters\OrderBid\ProblemFilter;
use App\Models\OrderBids;
use App\Models\Traits\Filterable;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Problem extends Model
{
    use Filterable;

    protected $table = 'orders_bid_problem';

    protected $guarded = false;

    public function getProblem(array $inp): Problem
    {

        $filter = new ProblemFilter($inp);

        $item = Problem::select($this->table . '.*')
            ->filter($filter)
            ->first();

        return $item;

    }

    public function getProblems(array $inp = [], bool $paginate = false): Collection|LengthAwarePaginator
    {

        $filter = new ProblemFilter($inp);

        $query = Problem::select($this->table . '.*')->filter($filter);

        return $paginate ? $query->paginate(30) : $query->get();

    }

    public function getBidsId(array $inp)
    {

        $filter = new ProblemFilter($inp);

        $item = Problem::select($this->table . '.bid_id')
            ->distinct()
            ->filter($filter)
            ->get();

        return $item;

    }

    public function count(array $inp)
    {

        $filter = new ProblemFilter($inp);

        $count = Problem::select($this->table . '.id')
            ->filter($filter)
            ->count();

        return $count;

    }

    /**
     * Типы проблем и их кол-во, только активные
     *
     * @param array $inp склад
     * @return Collection
     */
    public function getDistinctTypesAndCountActiveProblem(array $inp): Collection
    {

        $filter = new ProblemFilter($inp);

        $types = Problem::select('type', DB::raw('COUNT(id) as count'))
            ->filter($filter)
            ->where('status', '!=', 'закрыта')
            ->groupBy('type')
            ->get();

        return $types;

    }

    // Добавление записи
    public function insert(array $inp)
    {

        $data = Problem::updateOrCreate([
            'bid_id' => $inp['bid_id'],
            'status' => 'новая',
        ], [
            'bid_id'  => $inp['bid_id'],
            'user_id' => $inp['user_id'],
            'status'  => 'новая',
            'type'    => $inp['type'],
            'text'    => $inp['text'],
        ]);

        if (!$data) {
            return apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
        }

        return $data;

    }

    // Удаление проблемы
    public function remove(int $problemId)
    {

        $type = Problem::find($problemId);

        $delete = $type->delete();

        if (!$delete) {
            apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
        }

        return $delete;

    }

    // Закрытие проблемы
    public function close(int $problemId): Problem|JsonResponse
    {

        $problem = Problem::find($problemId);

        $update = $problem->update([
            'status' => 'закрыта',
        ]);

        if (!$update) {
            apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
        }

        return $problem;

    }

    // Связь с заявками
    public function toBid(): BelongsTo
    {
        return $this->belongsTo(OrderBids::class, 'bid_id');
    }

    // Связь с пользователем
    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
