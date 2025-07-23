<?php



namespace App\Services\OrderBid;

use App\Models\Comment;
use App\Models\Item;
use App\Models\OrderBid\Problem;
use App\Models\OrderBids;
use App\Models\OrderItems;
use App\Models\Tasks;
use App\Models\User;
use App\Services\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ProblemService extends Service
{
    /**
     * Получает список зафиксированных проблем
     *
     * @param array $inp
     * @param bool $paginate требуется ли постраничная навигация
     * @return Collection
     */
    public function getProblems(array $inp, bool $paginate = false): Collection|LengthAwarePaginator
    {

        if (!empty($inp['check_active']) && $inp['check_active'] == true) {
            $inp['status'][] = 'новая';
        }

        if (!empty($inp['check_close']) && $inp['check_close'] == true) {
            $inp['status'][] = 'закрыта';
        }

        $items = (new Problem())->getProblems($inp, $paginate);

        foreach ($items as $item) {

            // Последний комментарий
            $item->comment = (new Comment())->getLastComment('problem', $item->id);

        }

        return $items;

    }

    /**
     * Получает список складов отправителей по проблемам
     *
     * @param array $inp
     * @return Collection
     */
    public function getStoreSender(array $inp): Collection
    {

        // Id заявок
        $arrBids = (new Problem())->getBidsId($inp)->pluck('bid_id')->toArray();

        // Склады отправители
        $storesSender = (new OrderBids())->getBidsStoreSenderDistinct(auth()->user(), [
            'id'    => $arrBids,
            'moder' => 1,
        ]);

        return $storesSender;

    }

    /**
     * Получает список складов отправителей по проблемам
     *
     * @param array $inp
     * @return Collection
     */
    public function getTypesAndCountActiveProblem(array $inp): Collection
    {

        $auth = auth()->user();

        if (!empty($auth->store)) {
            $inp['store_id'] = $auth->store;
        }

        $types = (new Problem())->getDistinctTypesAndCountActiveProblem($inp);

        return $types;

    }

    /**
     * Фиксируем проблему
     *
     * @param array $inp
     * @return Collection
     */
    public function insert(array $inp): Problem|JsonResponse
    {

        $auth = auth()->user();

        // Заявка
        $bid = (new OrderBids())->getBid($auth, [
            'id' => $inp['bid_id']
        ]);

        $insert = (new Problem())->insert([
            'bid_id'  => $inp['bid_id'],
            'user_id' => $auth->id,
            'type'    => $inp['type'],
            'text'    => $inp['text'],
        ]);

        if (!$insert) {
            return apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
        }

        // Если склад, то задача закупу
        if ($auth->access == 'sklad') {

            // Менеджеры по закупу
            $users = (new User())->getUsers(['access' => 'purchase']);

            foreach ($users as $user) {

                $task          = [];
                $task['title'] = 'Проблема - ' . $inp['type'];
                $task['text']  = 'По заказу  ' . $bid->code . ', товар ' . $bid->item . ' ' . $bid->item_title . ' создана проблема - ' . $inp['text'];

                (new Tasks())->insert($auth, [
                    'store'       => null,
                    'user'        => $user->id,
                    'cat'         => 'task',
                    'date'        => null,
                    'date_finish' => null,
                    'type'        => 'Проблема',
                    'title'       => $task['title'],
                    'text'        => $task['text'],
                    'code'        => $bid->code,
                    'item'        => $bid->item,
                    'url'         => null,
                    'url_name'    => null,
                    'price'       => null,
                    'moder_user'  => self::USER_SYSTEM_ID,
                ]);

            }

            // Фиксируем - не отгрузили
            (new OrderBids())->picklater($auth, [
                'id' => $bid->id,
            ]);

        }

        // Если магазины
        if (in_array($auth->access, ['shop','shopartner'])) {

            $task          = [];
            $task['title'] = 'Проблема - ' . $inp['type'];
            $task['text']  = 'По заказу  ' . $bid->code . ', товар ' . $bid->item . ' ' . $bid->item_title . ' создана проблема - ' . $inp['text'];

            (new Tasks())->insert($auth, [
                'store'       => 83, // Склад
                'user'        => null,
                'cat'         => 'task',
                'date'        => null,
                'date_finish' => null,
                'type'        => 'Проблема',
                'title'       => $task['title'],
                'text'        => $task['text'],
                'code'        => $bid->code,
                'item'        => $bid->item,
                'url'         => null,
                'url_name'    => null,
                'price'       => null,
                'moder_user'  => self::USER_SYSTEM_ID,
            ]);

        }

        return $insert;

    }

    /**
     * Пересорт товара в заказе без закрытия проблемы
     *
     * @param array $inp
     * @return Collection
     */
    public function regrading(array $inp): Problem|JsonResponse
    {

        $auth = auth()->user();

        // Проблема
        $problem = (new Problem())->getProblem(['id' => $inp['id']]);

        // Заявка
        $bid = (new OrderBids())->getBidFull($auth, ['id' => $problem->bid]);

        // Проверка статуса заказа
        if (in_array($bid->status, ['Отменен', 'На отмену', 'Закрыт'])) {
            return apiError('Я не могу сделать пересорт в заказе со статусом «' . $bid->status . '»');
        }

        // Проверка кол-во товара
        if (empty($bid->item_kolvo)) {
            return apiError('Товар удален из заказа, я не могу сделать по нему пересорт');
        }

        // Товар
        $item = (new Item())->getItem($auth, ['id' => $inp['item']], true);

        // Меняем товар в заказе
        $update = (new OrderItems())->editItem($auth, [
            'id'         => $bid->id_item,
            'item'       => $inp['item'],
            'item_title' => $item->title,
        ]);

        if (!$update) {
            return apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
        }

        // Добавляем комментарий
        (new Comment())->add([
            'user' => $auth->id,
            'type' => 'order',
            'post' => $bid->id_order,
            'text' => 'На основании проблемы ' . $problem->id . ', сделан пересорт товара в заказе с ' . $bid->item . ' ' . $bid->item_title . ' на ' . $inp['item'] . ' ' . $item->title,
        ]);

        // Добавляем комментарий
        (new Comment())->add([
            'user' => $auth->id,
            'type' => 'problem',
            'post' => $problem->id,
            'text' => 'Cделан пересорт товара в заказе с ' . $bid->item . ' ' . $bid->item_title . ' на ' . $inp['item'] . ' ' . $item->title,
        ]);

        // Задача магазину на приемку товара
        $task          = [];
        $task['title'] = 'Примите товар';
        $task['text']  = 'По проблеме с товаром  ' . $item->id . ' ' . $item->title . ' сделан пересорт в заказе ' . $bid->code . ' - осуществите приемку товара в системе';

        (new Tasks())->insert($auth, [
            'store'       => $bid->store->id,
            'user'        => null,
            'cat'         => 'task',
            'date'        => null,
            'date_finish' => null,
            'type'        => 'Проблема',
            'title'       => $task['title'],
            'text'        => $task['text'],
            'code'        => $bid->code,
            'item'        => $bid->item,
            'url'         => route('bids.scan'),
            'url_name'    => 'Приемка товара',
            'price'       => null,
            'moder_user'  => self::USER_SYSTEM_ID,
        ]);

        return $problem;

    }

    /**
     * Закрытие проблемы
     *
     * @param array $inp
     * @return Collection
     */
    public function close(int $problemId): Problem|JsonResponse
    {

        $update = (new Problem())->close($problemId);

        if (!$update) {
            apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
        }

        // Добавляем комментарий
        (new Comment())->add([
            'user' => auth()->user()->id,
            'type' => 'problem',
            'post' => $update->id,
            'text' => 'Проблема закрыта',
        ]);

        return $update;

    }
}
