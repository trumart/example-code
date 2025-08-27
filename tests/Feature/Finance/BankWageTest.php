<?php

namespace Tests\Feature\Finance;

use App\Models\Finance\Finance;
use App\Models\Role;
use App\Models\User;
use App\Services\Finance\BankService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BankWageTest extends TestCase
{
    use DatabaseTransactions;

    protected $auth;

    public function setUp(): void
    {

        parent::setUp();

        // Пользователь системы
        $this->auth = (new User())->getUser(['id' => 2]);

        // Доступы
        $this->auth->accesses = (new Role())->getAccess($this->auth->access);

    }

    // Тестирование распределения банковской операции выплаты Заработной платы
    public function test_distribution_worker_wage(): void
    {

        // Имитируем операцию с банка
        $finance = Finance::factory()->create([
            'code'                 => 11072025890,
            'setting_id'           => 1,
            'store_id'             => 77,
            'store_cash_id'        => 77,
            'cashbox'              => 1,
            'type'                 => 'расход',
            'paycash'              => 0,
            'date'                 => '2025-07-11',
            'title'                => 'Клюева Елена Николаевна',
            'text'                 => 'Для зачисления на счет Клюевой Елены Николаевны Заработная плата за Июнь 2025 г. Сумма 11745-00 Без налога (НДС)',
            'cat_id'               => 32,
            'num'                  => 890,
            'inn'                  => 470303607216,
            'sum'                  => 11745,
            'user_id'              => 2,
            'user_pay_id'          => 0,
            'view'                 => 0,
            'distribution'         => null,
            'moder_store_status'   => 1,
            'moder_manager_status' => 1,
            'moder_store'          => null,
            'moder_manager'        => null,
        ]);

        // Запускаем проверку распределения
        (new BankService())->distributeUserWorkerWagePay();

        $updatedFinance = Finance::find($finance->id);

        if (!empty($updatedFinance->distribution)) {
            $this->assertTrue(true);
        } else {
            $this->fail('Переменная пуста — тест не пройден');
        }

    }
}
