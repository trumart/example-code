### Пример кода - Блок Финансовых операций, на базе Laravel

* [/app/Contracts/Finance](/app/Contracts/Finance) - контракты
* [/app/Http/Controllers/Finance](/app/Http/Controllers/Finance) - контроллеры
* [/app/Http/Resources/Finance](/app/Http/Resources/Finance) - базовое описание свойств модели используемые для ответов

### Trait
* [/app/Models/Traits](/app/Models/Traits) - trait для фильтрации 
* [/app/Services/Traits](/app/Services/Traits) - trait для генерации ключа кеширования на основе входящих данных
* [/app/Http/Filters](/app/Http/Filters) - блок для фильтрации работает в моделях через trait

### CRUD
* [/app/Models/Finance](/app/Models/Finance) - модели, общие методы и CRUD

### Observers && Jobs
* [/app/Jobs/Finance](/app/Jobs/Finance) - очереди через RabbitMQ
* [/app/Observers/Finance](/app/Observers/Finance) - наблюдатель, запись действий пользователей и запуск очередей, а так же сброс кэша для Redis

### WebSocket
* [/app/Observers/Notice](/app/Observers/Notice) - наблюдатель, для передачи на websocket новых уведомлений для конкретных пользователей
* [/app/Events/NoticeCreated](/app/Events/NoticeCreated.php) - канал websocket
* [/app/Services/Notice](/app/Services/Notice) - метод отправки сообщений на websocket и ручное добавление из формы

### Паттерн Стратегия
* [/app/Services/Finance/CostStrategy](/app/Services/Finance/CostStrategy) - подсчет различных расходов (чистые, операционные, оборотные)
* [/app/Services/Finance/EncashmentStrategy](/app/Services/Finance/EncashmentStrategy) - инкассации (из магазина в магазин, от сотрудника к сотруднику, от магазина к сотруднику)
* [/app/Services/Finance/RoleStrategy](/app/Services/Finance/RoleStrategy) - отображение финансовых операций в зависимости от доступ сотрудника

### Паттерн Шаблонный метод и Декоратор
* [/app/Services/Rest](/app/Services/Rest) - создание заказа с остатков (клиент / на ассортимент со склада)

### Прочие сервисы
* [/app/Services/Finance/ExportService](/app/Services/Finance/ExportService.php) - выгрузка XML 1C EnterpriceData, и выгрузка Excel для Bi системы
* [/app/Services/Finance/BankService](/app/Services/Finance/BankService.php) - загрузки и обработки выписки банка, а так же прочие методы для распределения финансовых операций
* [/app/Services/Finance/____Service](/app/Services/Finance/) - прочие сервисы блока Финансов
* [/app/Services/Pay/ImportCashReceiptService](/app/Services/Pay/ImportCashReceiptService.php) - работа с почтовым сервером, работа с dom, разбор html 

### Транзакции
[/app/Services/Upd/UpdService](/app/Services/Upd/UpdService.php) - пример реализации транзакций

### Тесты
[/tests/Feature/Finance](/tests/Feature/Finance) - примеры тестов
