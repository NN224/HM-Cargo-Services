# ملفي الشخصي للفحوصات والأوامر

هذا الملف غير canonical. الغرض منه تذكير سريع بالأدوات والأوامر التي أستخدمها لفحص المشروع، وماذا يفحص كل أمر.

## فحوصات الاختبارات

| الأمر | ماذا يفحص |
|---|---|
| `php artisan test --compact` | يشغّل كل اختبارات Pest/PHPUnit في المشروع بشكل مختصر. هذا هو الفحص الأساسي قبل اعتبار أي تعديل آمن. |
| `php artisan test --compact tests/Feature/ShipmentJourneyUiTest.php` | يشغّل اختبارات واجهة ومسار الشحنات فقط. مناسب بعد تعديل `/shipments` أو مراحل الطرود. |
| `php artisan test --compact tests/Feature/ShipmentUiTest.php` | يشغّل اختبارات واجهة إنشاء/عرض الشحنات وقواعد الخصوصية الأساسية. |
| `php artisan test --compact tests/Feature/ShipmentJourneyUiTest.php tests/Feature/ShipmentUiTest.php` | فحص سريع مركّز للشحنات بعد تعديل كروت الشحنات أو التحديث الجماعي. |
| `php artisan test --compact --filter="اسم_الاختبار"` | يشغّل اختبارًا واحدًا أو مجموعة اختبارات مطابقة للاسم. مفيد أثناء إصلاح Bug محدد. |
| `composer test` | يمسح config cache ثم يشغّل `php artisan test`. مناسب عندما تشك أن إعدادات Laravel cached. |
| `php artisan test --coverage` | يشغّل الاختبارات مع تقرير تغطية Xdebug. يحتاج `xdebug.mode` يحتوي `coverage`. |

## فحوصات التنسيق والجودة

| الأمر | ماذا يفحص |
|---|---|
| `vendor/bin/pint --dirty --format agent` | ينسّق ملفات PHP المعدلة فقط حسب Laravel Pint. شغّله بعد أي تعديل PHP. |
| `vendor/bin/pint --format agent` | ينسّق كل ملفات PHP في المشروع. استخدمه فقط عند الحاجة لأنه أوسع من `--dirty`. |
| `php artisan config:clear` | يمسح كاش إعدادات Laravel. مفيد إذا تغيّرت `.env` أو إعدادات config ولم تظهر. |
| `php artisan view:clear` | يمسح Blade compiled views. مفيد عندما تظهر واجهة قديمة رغم تعديل Blade. |
| `php artisan cache:clear` | يمسح كاش التطبيق العام. مفيد عند مشاكل cache غير واضحة. |

## فحوصات الواجهة والبناء

| الأمر | ماذا يفحص |
|---|---|
| `npm run build` | يبني ملفات Vite للإنتاج ويتأكد أن assets وTailwind/Vite لا يوجد فيها خطأ بناء. |
| `npm run dev` | يشغّل Vite dev server لمشاهدة تغييرات CSS/JS مباشرة. |
| `composer run dev` | يشغّل بيئة التطوير كاملة: Laravel server، queue listener، logs عبر Pail، وVite. |

## فحوصات Laravel المفيدة

| الأمر | ماذا يفحص |
|---|---|
| `php artisan route:list --except-vendor` | يعرض routes الخاصة بالتطبيق فقط. مفيد للتأكد من أسماء ومسارات الصفحات. |
| `php artisan config:show app.name` | يعرض قيمة config محددة. بدّل المفتاح حسب الحاجة. |
| `php artisan about` | يعطي ملخصًا سريعًا عن Laravel والبيئة والحزم. |
| `php artisan migrate:status` | يعرض حالة migrations وهل هناك migrations غير مطبقة. |

## أدوات التشخيص من Laravel Boost

| الأداة | ماذا تفحص |
|---|---|
| `application-info` | يعرض إصدارات PHP، Laravel، Filament، Livewire، Pest، والحزم الأساسية. استخدمه قبل كتابة كود يعتمد على إصدار معيّن. |
| `search-docs` | يبحث في توثيق Laravel/Filament/Livewire/Pest حسب الإصدارات المثبتة في المشروع. استخدمه قبل تغييرات Laravel ecosystem. |
| `database-schema` | يعرض جداول وقواعد قاعدة البيانات. استخدمه قبل تعديل models أو migrations أو queries. |
| `database-query` | يشغّل استعلامات قراءة فقط. مفيد للتأكد من البيانات بدون تعديلها. |
| `read-log-entries` | يقرأ آخر سجلات Laravel backend. استخدمه بعد خطأ 500 أو Exception. |
| `browser-logs` | يقرأ أخطاء المتصفح الحديثة. استخدمه لمشاكل JavaScript/Livewire في الواجهة. |
| `last-error` | يعرض آخر خطأ backend سجله التطبيق. |

## أدوات المتصفح و1DevTool

| الأداة | ماذا تفحص |
|---|---|
| `onedevtool browser snapshot` | يأخذ snapshot accessibility للصفحة الحالية. مفيد لمعرفة النصوص والأزرار الموجودة فعليًا. |
| `onedevtool browser click` | يضغط عنصرًا من snapshot. مفيد لاختبار الأزرار والروابط. |
| `onedevtool browser type` | يكتب في input أو textarea. مفيد لاختبار البحث والفلاتر. |
| `onedevtool browser get_console_logs` | يعرض console logs للصفحة. تجاهل رسائل إضافات المتصفح مثل `chrome-extension`, `ranksense`, و`ERR_BLOCKED_BY_CLIENT`. |
| `onedevtool browser reload` | يعيد تحميل الصفحة ويعطي snapshot جديد. |

## Xdebug

| الأمر | ماذا يفحص |
|---|---|
| `php --ri xdebug` | يعرض إعدادات Xdebug الحالية: `xdebug.mode`, debugger, coverage, port. |
| `XDEBUG_TRIGGER=1 php artisan test --filter="اسم_الاختبار"` | يشغّل اختبارًا مع محاولة اتصال debugger إذا IDE يستمع على port 9003. |
| `php artisan test --coverage` | يستخدم Xdebug coverage لإظهار تغطية الاختبارات. |

الإعداد الحالي على الجهاز:

```ini
xdebug.mode=develop,debug,coverage
xdebug.start_with_request=trigger
xdebug.client_host=localhost
xdebug.client_port=9003
```

مكان الإعداد المحلي:

```text
/opt/homebrew/etc/php/8.5/php.ini
```

## Laravel Debugbar

Laravel Debugbar مركّب كحزمة تطوير فقط. لا تستخدمه في production لأنه يعرض معلومات حساسة عن الطلبات، الاستعلامات، الجلسات، والبيئة.

الحزمة المثبتة:

```bash
composer require --dev barryvdh/laravel-debugbar
```

ملف الإعداد المنشور:

```text
config/debugbar.php
```

### تشغيل وإيقاف Debugbar

| الأمر / الإعداد | ماذا يفعل |
|---|---|
| `APP_DEBUG=true` | Debugbar يعمل تلقائيًا عندما يكون `debugbar.enabled` غير مضبوط و`APP_DEBUG=true`. |
| `DEBUGBAR_ENABLED=true` | يجبر Debugbar أن يعمل محليًا حتى لو أردت التحكم به من `.env`. لا تستخدمه في production. |
| `DEBUGBAR_ENABLED=false` | يطفئ Debugbar حتى لو `APP_DEBUG=true`. مفيد إذا صار يزعجك أثناء التطوير. |
| `DEBUGBAR_STORAGE_ENABLED=false` | يوقف تخزين طلبات Debugbar. مستخدم في `phpunit.xml` حتى لا تستهلك الاختبارات ذاكرة إضافية. |
| `php artisan config:clear` | طبّقه بعد تغيير `.env` حتى يقرأ Laravel إعداد Debugbar الجديد. |
| `php artisan optimize:clear` | يمسح config/cache/routes/views دفعة واحدة إذا بقي Debugbar لا يظهر أو لا يختفي. |

مثال إعداد محلي في `.env`:

```ini
APP_DEBUG=true
DEBUGBAR_ENABLED=true
```

لإطفائه مؤقتًا:

```ini
DEBUGBAR_ENABLED=false
```

بعد أي تغيير:

```bash
php artisan config:clear
```

في الاختبارات، Debugbar مطفأ داخل `phpunit.xml`:

```xml
<env name="DEBUGBAR_ENABLED" value="false"/>
<env name="DEBUGBAR_STORAGE_ENABLED" value="false"/>
```

هذا يمنع أخطاء memory exhausted عند تشغيل اختبارات Filament/Livewire الكبيرة.

### أوامر النشر والإعداد

| الأمر | ماذا يفعل |
|---|---|
| `php artisan vendor:publish --provider="Fruitcake\LaravelDebugbar\ServiceProvider" --tag="config" --no-interaction` | ينشر إعدادات Debugbar إلى `config/debugbar.php`. تم تشغيله بالفعل. |
| `php artisan vendor:publish --provider="Fruitcake\LaravelDebugbar\ServiceProvider" --tag="config" --force --no-interaction` | يعيد نشر config فوق الموجود. استخدمه فقط إذا أردت استبدال التعديلات المحلية. |

ملاحظة: رغم أن Composer package اسمه `barryvdh/laravel-debugbar`، الإصدار 4 يستخدم namespace `Fruitcake\LaravelDebugbar` في ServiceProvider.

### ماذا يفحص Debugbar في الصفحة

| التبويب / collector | ماذا يفحص |
|---|---|
| Messages | رسائل `debug()`, `debugbar()->info()`, وأي رسائل تضيفها أثناء التطوير. |
| Timeline / Time | زمن تنفيذ الطلب وأجزاء من lifecycle. مفيد لمعرفة البطء العام. |
| Memory | استهلاك الذاكرة للطلب. |
| Exceptions | الاستثناءات التي حصلت أثناء الطلب. |
| Logs | رسائل Laravel log إذا كان collector مفعل. |
| DB / Queries | استعلامات قاعدة البيانات، الوقت، bindings، وأحيانًا EXPLAIN حسب الإعداد. مهم لاكتشاف N+1. |
| Views | ملفات Blade التي تم render لها. مفيد إذا ظهرت view قديمة أو غير متوقعة. |
| Route | معلومات route الحالي إذا فعّلت collector. |
| Gate | فحوصات authorization/policies. |
| Cache | عمليات cache التي حصلت في الطلب. |
| Models | النماذج التي تم تحميلها. |
| Livewire | معلومات Livewire عند توفرها. مفيد لصفحات Filament. |
| Mail | الرسائل البريدية الملتقطة أثناء الطلب. |
| Jobs | jobs المرسلة أو المعالجة حسب الإعدادات. |
| HTTP Client | طلبات Laravel HTTP Client الخارجية. |

### أوامر Debugbar من Artisan

| الأمر | ماذا يفعل |
|---|---|
| `php artisan list debugbar` | يعرض كل أوامر Debugbar المتاحة. |
| `php artisan debugbar:clear` | يمسح تخزين Debugbar للطلبات السابقة. استخدمه إذا كبرت ملفات التخزين أو اختلطت عليك الطلبات. |
| `php artisan debugbar:find` | يعرض الطلبات المخزنة في Debugbar. يحتاج التخزين مفعّل. |
| `php artisan debugbar:get {id}` | يعرض تفاصيل طلب مخزن حسب id من `debugbar:find`. |
| `php artisan debugbar:queries {id}` | يعرض استعلامات طلب محدد من التخزين. مفيد لتحليل queries بدون فتح المتصفح. |
| `php artisan debugbar:queries {id} --statement=N` | يعرض تفاصيل statement محدد من استعلامات ذلك الطلب. |

### التخزين وفتح الطلبات السابقة

الإعدادات المهمة في `config/debugbar.php`:

```php
'storage' => [
    'enabled' => env('DEBUGBAR_STORAGE_ENABLED', true),
    'open' => env('DEBUGBAR_OPEN_STORAGE'),
    'driver' => env('DEBUGBAR_STORAGE_DRIVER', 'file'),
    'path' => env('DEBUGBAR_STORAGE_PATH', storage_path('debugbar')),
],
```

إعدادات `.env` مفيدة محليًا:

```ini
DEBUGBAR_STORAGE_ENABLED=true
DEBUGBAR_OPEN_STORAGE=true
DEBUGBAR_STORAGE_DRIVER=file
```

تحذير: لا تفعّل `DEBUGBAR_OPEN_STORAGE=true` على موقع عام، لأنه يسمح بقراءة طلبات سابقة قد تحتوي بيانات حساسة.

### استخدام Debugbar داخل الكود أثناء التطوير

```php
debug('وصلنا لهون');
debug($shipment);

debugbar()->info('تم اختيار الشحنة');
debugbar()->warning('انتبه: لا يوجد batch');
debugbar()->error('فشل التحديث');
```

قياس وقت جزء معيّن:

```php
debugbar()->startMeasure('bulk-update', 'Bulk shipment update');

// الكود الذي تريد قياسه

debugbar()->stopMeasure('bulk-update');
```

تشغيل/إيقاف أثناء الطلب:

```php
debugbar()->enable();
debugbar()->disable();
```

### أوامر مفيدة مع Debugbar في هذا المشروع

| الحالة | ماذا تشغّل |
|---|---|
| Debugbar لا يظهر | تأكد من `APP_DEBUG=true` و`DEBUGBAR_ENABLED=true` ثم شغّل `php artisan config:clear`. |
| Debugbar يظهر في صفحات لا تريدها | أضف المسار إلى `debugbar.except` في `config/debugbar.php`. |
| تريد تحليل استعلامات صفحة `/shipments` | افتح `/shipments`، ثم راجع تبويب DB في أسفل الصفحة. |
| تريد مسح طلبات Debugbar القديمة | `php artisan debugbar:clear` |
| تريد التأكد أن الحزمة مسجلة | `php artisan package:discover` أو `php artisan list debugbar` |

## أوامر أستخدمها غالبًا حسب نوع التعديل

| نوع التعديل | الأوامر المقترحة |
|---|---|
| تعديل PHP عادي | `vendor/bin/pint --dirty --format agent` ثم `php artisan test --compact --filter="اسم_الاختبار"` |
| تعديل صفحة الشحنات `/shipments` | `php artisan test --compact tests/Feature/ShipmentJourneyUiTest.php tests/Feature/ShipmentUiTest.php` |
| تعديل Blade أو CSS | `php artisan view:clear` ثم افتح الصفحة وتحقق بالمتصفح، وبعدها شغّل الاختبارات المركّزة. |
| تعديل أصول frontend | `npm run build` |
| قبل تسليم تغيير كبير | `vendor/bin/pint --dirty --format agent` ثم `php artisan test --compact` |
