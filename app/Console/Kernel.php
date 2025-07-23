<?php



namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        'App\Console\Commands\CronTest',
        'App\Console\Commands\CronRest',
        'App\Console\Commands\CronContr',
        'App\Console\Commands\CronContrFile',
        'App\Console\Commands\CronVendor',
        'App\Console\Commands\CronContrOrion',
        'App\Console\Commands\CronReportRest',
        'App\Console\Commands\CronReportMssql',
        'App\Console\Commands\CronReportOrder',
        'App\Console\Commands\CronUserWage',
        'App\Console\Commands\CronCalcPriceOpt',
        'App\Console\Commands\CronCalcPrice',
        'App\Console\Commands\CronCalcRest',
        'App\Console\Commands\CronCalcNom',
        'App\Console\Commands\CronFinance',
        'App\Console\Commands\CronExportSale',
        'App\Console\Commands\CronDivanRu',
        'App\Console\Commands\CronBonus',
        'App\Console\Commands\CronTask',
        'App\Console\Commands\CronPay',
        'App\Console\Commands\CronAction',
        'App\Console\Commands\CronActionSms',
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule): void
    {

        // Тестовые механизмы
        $schedule->command('cron:test')
            ->cron('05 0 1 1 1')
            ->sendOutputTo('/var/log/test.log');

        $schedule->command('cron:action')
            ->cron('10 0 * * *')
            ->sendOutputTo('/var/log/action.log');

        $schedule->command('cron:actionsms')
            ->cron('05 0 1 1 1')
            ->sendOutputTo('/var/log/actionsms.log');

        // Начисляем и списываем бонусы
        $schedule->command('user:bonus')
            ->cron('05 0 * * *')
            ->sendOutputTo('/var/log/bonus.log');

        // Диван.РУ
        $schedule->command('divanru:task')
            ->cron('*/10 9-20 * * *')
            ->sendOutputTo('/var/log/divanru.log')
            ->runInBackground();

        // Обработка и создание заказов ОРИОН
        $schedule->command('contr:orion')
            ->cron('05 09 * * *')
            ->sendOutputTo('/var/log/orion.log');

        // Контрагенты - обновление статистики
        $schedule->command('contr:main')
            ->cron('30 00 * * *')
            ->sendOutputTo('/var/log/contr.log')
            ->runInBackground();

        // Контрагенты - обновление статистики
        $schedule->command('contr:file')
            ->cron('30 10-23 * * *')
            ->sendOutputTo('/var/log/contrFile.log')
            ->runInBackground();

        // Производители - обновление статистики
        $schedule->command('vendor:main')
            ->cron('15 00 * * *')
            ->sendOutputTo('/var/log/vendor.log')
            ->runInBackground();

        // Товары по автозаказам на остатки
        $schedule->command('rest:itemwait')
            ->cron('05 11 * * *')
            ->sendOutputTo('/var/log/rest.log');

        // Утренние дополнительные обработки
        $schedule->command('cron:morning')
            ->cron('10 06 * * *')
            ->sendOutputTo('/var/log/cronMorning.log')
            ->runInBackground();

        // Отчет по заказам
        $schedule->command('report:order')
            ->cron('10 12,15,19,23 * * *')
            ->sendOutputTo('/var/log/reportOrder.log')
            ->runInBackground();

        // Выгрузка данных в mssql
        $schedule->command('report:mssql')
            ->cron('00 20 * * *')
            ->sendOutputTo('/var/log/reportMssql.log')
            ->runInBackground();

        // Отчет по оборачиваемости минимального ассортимента
        $schedule->command('report:restmin')
            ->cron('05 23 * * *')
            ->sendOutputTo('/var/log/restMin.log')
            ->runInBackground();

        // Расчет заработной платы сотрудников
        $schedule->command('user:wage')
            ->cron('20 23 * * *')
            ->sendOutputTo('/var/log/userWage.log')
            ->runInBackground();

        // Расчет цен
        $schedule->command('calc:price')
            ->cron('10 02 * * *')
            ->sendOutputTo('/var/log/calcPrice.log')
            ->runInBackground();

        // Расчет оптовых цен
        $schedule->command('calc:priceopt')
            ->cron('0 09 * * *')
            ->sendOutputTo('/var/log/calcPriceOpt.log')
            ->runInBackground();

        // Расчет цен
        $schedule->command('calc:rest')
            ->cron('30 02 * * *')
            ->sendOutputTo('/var/log/calcRest.log')
            ->runInBackground();

        // Распределение оплат по банку
        $schedule->command('cron:finance')
            ->cron('0 10,12,15,17,20,22 * * *')
            ->sendOutputTo('/var/log/cron-finance.log');

        // Распределение оплат по банку
        $schedule->command('cron:pay')
            ->cron('*/1 09-20 * * *')
            ->sendOutputTo('/var/log/pay.log');

        // Выгрузка отчетов о розничных продажах
        $schedule->command('export:sales')
            ->cron('0 23 * * *')
            ->sendOutputTo('/var/log/exportSales.log');

        // Обновление данных по номенклатуре
        $schedule->command('cron:task')
            ->cron('17 12-16 * * *')
            ->sendOutputTo('/var/log/cronTask.log')
            ->runInBackground();

    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands(): void
    {
        require base_path('routes/console.php');
    }
}
