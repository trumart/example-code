<?php



namespace App\Http\Controllers\OrderBid;

use App\Http\Controllers\Controller;
use App\Http\Requests\BidProblemRequest;
use App\Http\Resources\OrderBid\ProblemResource;
use App\Models\OrderBid\Problem;
use App\Models\Store;
use App\Models\User;
use App\Services\OrderBid\ProblemService;
use Illuminate\Http\Request;

class ProblemController extends Controller
{
    protected $section = 'bid';

    public function __construct(Request $request)
    {

        parent::__construct($request);

    }

    public function page()
    {

        // Проверка доступа
        (new User())->checkAccess('page', $this->auth, $this->section, 'view');

        // Точки продаж
        $stores = (new Store())->getStores([
            'type'  => ['пункт выдачи', 'магазин', 'офис', 'склад'],
            'moder' => 1,
        ]);

        return view('page.bids_problem', [
            'auth'   => $this->auth,
            'stores' => $stores,
        ]);

    }

    // Список проблем
    public function problems(BidProblemRequest $request)
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'view');

        // Данные
        $inp = $request->validated();

        $items = (new Problem())->getProblems($inp, false);

        return ProblemResource::collection($items);

    }

    // Список проблем
    public function problemsPaginate(BidProblemRequest $request)
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'view');

        // Данные
        $inp = $request->validate([
            'store_id'        => ['nullable', 'numeric'],
            'store_sender_id' => ['nullable', 'numeric'],
            'type'            => ['nullable', 'string'],
            'check_active'    => ['nullable', 'boolean'],
            'check_close'     => ['nullable', 'boolean'],
            'date_start'      => ['nullable', 'date'],
            'date_finish'     => ['nullable', 'date'],
        ]);

        $items = (new ProblemService())->getProblems($inp, true);

        return ProblemResource::collection($items);

    }

    // Список типов проблем
    public function types(BidProblemRequest $request)
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'view');

        // Данные
        $inp = $request->validate([
            'store_id' => ['nullable', 'numeric'],
        ]);

        $items = (new ProblemService())->getTypesAndCountActiveProblem($inp, true);

        return apiSuccess($items);

    }

    // Список складов отправителей
    public function storesSender(BidProblemRequest $request)
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'view');

        // Данные
        $inp = $request->validate([
            'store_id' => ['nullable', 'numeric'],
        ]);

        $items = (new ProblemService())->getStoreSender($inp, true);

        return apiSuccess($items);

    }

    // Сохранение
    public function insert(BidProblemRequest $request)
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'addition');

        // Данные
        $inp = $request->validate([
            'bid_id' => ['required', 'numeric'],
            'type'   => ['required', 'string'],
            'text'   => ['required', 'string'],
        ]);

        $data = (new ProblemService())->insert($inp);

        return $data;

    }

    // Удаление
    public function remove(int $problemId)
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'remove');

        $data = (new Problem())->remove($problemId);

        return $data;

    }

    // Редактирование
    public function edit(BidProblemRequest $request)
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'edit');

        // Данные
        $inp = $request->validated();

        $data = (new Problem())->edit($this->auth, $inp);

        return $data;

    }

    // Пересорт в заказе
    public function regrading(BidProblemRequest $request)
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'edit');

        // Данные
        $inp = $request->validate([
            'id'   => ['required', 'numeric'],
            'item' => ['required', 'numeric'],
        ]);

        $data = (new ProblemService())->regrading($inp);

        return $data;

    }

    // Закрыть проблему
    public function close(BidProblemRequest $request)
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'edit');

        // Данные
        $inp = $request->validate([
            'id' => ['required', 'numeric'],
        ]);

        $data = (new ProblemService())->close($inp['id']);

        return $data;

    }
}
