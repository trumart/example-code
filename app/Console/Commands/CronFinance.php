<?php



namespace App\Console\Commands;

use App\Models\OrderBidsPay;
use App\Models\Pay;
use App\Models\Role;
use App\Models\User;
use App\Services\Finance\BankService;
use App\Services\Finance\SettingService;
use App\Services\Route\FinanceService;
use Illuminate\Console\Command;

class CronFinance extends Command
{
    protected $signature = 'cron:finance';

    protected $description = 'Распределение оплат по банку';

    private $auth;

    public function __construct()
    {

        parent::__construct();

        // Пользователь система
        $this->auth = (new User())->getUser(['id' => 2]);

        // Доступы
        if (!empty($this->auth->access)) {
            $this->auth->accesses = (new Role())->getAccess($this->auth->access);
        }

    }

    public function handle(): void
    {

        echo "\r\n";
        echo 'Проверка оплат поступивших по банковским терминалам' . "\r\n";
        (new BankService())->distributeSberTerminalPay($this->auth);

        echo "\r\n";
        echo 'Распределение оплат по заказам юридических лиц - безнал' . "\r\n";
        (new BankService())->distributeOrderPay($this->auth);

        echo "\r\n";
        echo 'Распределение оплат по кредитным заказам' . "\r\n";
        (new BankService())->distributeOrderCreditPay($this->auth);

        echo "\r\n";
        echo 'Распределение оплат по контрагентам - шаг 1' . "\r\n";
        (new BankService())->distributeContrPay($this->auth);

        echo "\r\n";
        echo 'Распределение оплат по контрагентам - шаг 2' . "\r\n";
        (new OrderBidsPay())->distributionAuto($this->auth);

        echo "\r\n";
        echo 'Распределение оплат' . "\r\n";
        (new SettingService())->distributionFinOperations();

        echo "\r\n";
        echo 'ЮКасса' . "\r\n";
        (new Pay())->yooKassa($this->auth);

        echo "\r\n";
        echo 'Генерация фин. операций доли стоимости логистики на магазин' . "\r\n";
        (new FinanceService())->generateFinanceCostFromRoute($this->auth);

    }
}
