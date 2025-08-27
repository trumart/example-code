<?php

namespace Finance;

use App\Services\Finance\FinanceService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FinanceTest extends TestCase
{
    use DatabaseTransactions;

    protected $auth;

    public function setUp(): void
    {

        parent::setUp();

        $this->testData = [
            'store_id'             => 77,
            'store_cash_id'        => 77,
            'cashbox'              => 1,
            'type'                 => 'расход',
            'paycash'              => 0,
            'date'                 => date('Y-m-d'),
            'title'                => 'Тестовый расход',
            'text'                 => 'Описание тестового расхода',
            'cat_id'               => 7,
            'inn'                  => 470303607216,
            'sum'                  => 20000,
            'user_id'              => 2,
            'user_pay_id'          => 0,
            'view'                 => 0,
            'distribution'         => null,
            'moder_store_status'   => 1,
            'moder_manager_status' => 1,
            'moder_store'          => null,
            'moder_manager'        => null,
        ];

    }

    public function test_insert(): void
    {

        $finance  = (new FinanceService())->insert($this->testData);
        $response = $finance->getData(true);

        $this->assertEquals('success', $response['status']);
        $this->assertEquals('OK', $response['message']);
        $this->assertNotEmpty($response['data']);

        // Проверяем, что запись реально появилась в базе
        $this->assertDatabaseHas('finance', [
            'title' => $this->testData['title'],
            'sum'   => $this->testData['sum'],
        ]);

    }

    public function test_edit(): void
    {

        // Сначала создаём запись
        $finance = (new FinanceService())->insert($this->testData)->getData(true)['data'];

        // Редактируем сумму
        $edited = (new FinanceService())->edit([
            'id'  => $finance['id'],
            'sum' => 30000,
        ]);

        $response = $edited->getData(true);

        $this->assertEquals('success', $response['status']);
        $this->assertEquals('OK', $response['message']);

        // Проверяем в базе
        $this->assertDatabaseHas('finance', [
            'id'  => $finance['id'],
            'sum' => 30000,
        ]);

    }

    public function test_remove(): void
    {

        // Сначала создаём запись
        $finance = (new FinanceService())->insert($this->testData)->getData(true)['data'];

        // Удаляем
        $removed  = (new FinanceService())->remove($finance['id']);
        $response = $removed->getData(true);

        $this->assertEquals('success', $response['status']);
        $this->assertEquals('OK', $response['message']);

        $this->assertSoftDeleted('finance', [
            'id' => $finance['id'],
        ]);

    }
}
