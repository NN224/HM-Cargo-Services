# توثيق صفحة الشحنات - نظام HM Cargo Services

**تاريخ التحقق:** 30 يوليو 2026  
**الحالة:** ✅ تم التحقق من جميع المعلومات المذكورة في خريطة البرمجة

---

## نظرة عامة

هذا المستند يوثق صفحة الشحنات في نظام HM Cargo Services، والتي تغطي دورة حياة الشحنات الكاملة من الإنشاء إلى التسليم. تم التحقق من جميع الملفات والوظائف المذكورة في خريطة البرمجة الأصلية.

---

## المسار 1: عرض قائمة الشحنات

### الملفات الرئيسية
- **صفحة القائمة:** `app/Filament/Resources/Shipments/Pages/ListShipments.php:22`
- **قالب العرض:** `resources/views/filament/resources/shipments/pages/list-shipments.blade.php:35`
- **خدمة إسقاط الرحلة:** `app/Services/PackageJourneyProjection.php:14`
- **تكوين الجدول:** `app/Filament/Resources/Shipments/Pages/ListShipments.php:271`

### الوظائف الم verified
1. **استعلام الشحنات:** جلب الشحنات مع العلاقات (العميل، الرحلة، المستودعات، الطرود) وتطبيق البحث والتصفية
2. **حساب حالة الرحلة:** استخدام `PackageJourneyProjection::forShipment()` لتوليد بيانات مراحل الرحلة لكل شحنة
3. **عرض الواجهة:** قالب Blade مخصص يعرض الشحنات في شبكة مع حالة الرحلة والمعلومات الأساسية

### الميزات
- البحث بالمرجع، اسم المستلم، أو اسم العميل
- تصفية بالحالة التشغيلية
- عرض الوزن الإجمالي وعدد الشحنات
- تحديد الشحنات للعمليات الجماعية

---

## المسار 2: إنشاء شحنة جديدة

### الملفات الرئيسية
- **صفحة الإنشاء:** `app/Filament/Resources/Shipments/Pages/CreateShipment.php:8`
- **نموذج الشحنة:** `app/Filament/Resources/Shipments/ShipmentResource.php:39`
- **توليد المرجع:** `app/Models/Shipment.php:113`
- **توليد الباركود:** `app/Models/Package.php:89`
- **تحديث الوزن:** `app/Models/Package.php:106`

### الوظائف الم verified
1. **تكوين النموذج:** إدخال بيانات العميل والمستلم وإضافة الطرود عبر `ShipmentForm::configure()`
2. **توليد المرجع:** `Shipment::generateReference()` يولد مرجع فريد بالصيغة `HM-YYYY-XXXXXX`
3. **توليد الباركود:** `Package::generateBarcode()` يولد باركود فريد بالصيغة `PKG-XXXXXXXXXX`
4. **تحديث الوزن:** تحديث تلقائي للوزن الإجمالي عند تغيير الطرود عبر `recalculateTotalWeight()`

### الميزات
- توليد تلقائي للمرجع والرمز العام للتتبع
- توليد تلقائي للباركود لكل طرد
- حساب الوزن الإجمالي بدقة عشرية (4 خانات)
- التحقق من صحة الوزن (يجب أن يكون أكبر من صفر)

---

## المسار 3: عرض تفاصيل الشحنة

### الملفات الرئيسية
- **صفحة العرض:** `app/Filament/Resources/Shipments/Pages/ViewShipment.php:29`
- **توليد رمز QR:** `app/Filament/Resources/Shipments/Pages/ViewShipment.php:99`
- **إشعار الاستلام:** `app/Filament/Resources/Shipments/Pages/ViewShipment.php:41`
- **رابط واتساب:** `app/Filament/Resources/Shipments/Pages/ViewShipment.php:42`
- **رابط التتبع:** `app/Models/Package.php:152`

### الوظائف الم verified
1. **عرض المعلومات:** عرض قائمة المعلومات (infolist) مع بيانات الشحنة والطرود
2. **توليد رمز QR:** استخدام `QrCode::svg()` لتوليد رمز QR لكل طرد مع رابط التتبع
3. **إجراءات واتساب:**
   - `markIntakeNotified()` لتسجيل إرسال إشعار الاستلام
   - `markArrivalNotified()` لتسجيل إرسال إشعار الوصول
   - `WhatsAppMessageService::intakeUrl()` و `arrivalUrl()` لتوليد الروابط
4. **رابط التتبع العام:** `Package::trackingUrl()` يولد رابط التتبع العام باستخدام الباركود

### الميزات
- عرض حالة الإشعارات (مرسلة/غير مرسلة)
- رموز QR قابلة للمسح لكل طرد
- روابط واتساب جاهزة مع رسائل مسبقة
- طباعة الملصقات مباشرة من الصفحة

---

## المسار 4: إدارة رحلة الطرود

### الملفات الرئيسية
- **إجراء الإدارة:** `app/Filament/Resources/Shipments/Actions/ManageJourneyAction.php:17`
- **تنفيذ العملية:** `app/Filament/Resources/Shipments/Actions/ManageJourneyAction.php:61`
- **تقديم الطرد:** `app/Services/PackageJourneyService.php:21`
- **تسجيل الحدث:** `app/Services/PackageJourneyService.php:47`
- **إعادة الحساب:** `app/Services/PackageJourneyService.php:62`

### الوظائف الم verified
1. **عرض النموذج:** نموذج اختيار العملية (تقديم/تأخير/تصحيح) والطرود
2. **تنفيذ العمليات:**
   - `advance()`: تقديم للمرحلة التالية
   - `delay()`: تسجيل تأخير مع سبب
   - `correct()`: تصحيح إداري (للمديرين فقط)
3. **تسجيل الأحداث:** إدراج في جدول `package_status_events` مع تفاصيل الحالة
4. **إعادة الحساب:** `recalculateOperationalStatus()` لتحديث حالة الشونة بناءً على حالات الطرود

### الميزات
- ثلاث عمليات: تقديم، تأخير، تصحيح إداري
- التحقق من الصلاحيات (التصحيح للمديرين فقط)
- تسجيل الأسباب وإمكانية إظهارها للعميل
- تحديث تلقائي لحالة الشونة

---

## المسار 5: تسليم الشحنة

### الملفات الرئيسية
- **تسليم كامل:** `app/Services/ShipmentCollectionService.php:16`
- **التحقق من الصلاحيات:** `app/Services/ShipmentCollectionService.php:18`
- **التحقق من الوصول:** `app/Services/ShipmentCollectionService.php:35`
- **تقديم الطرود:** `app/Services/ShipmentCollectionService.php:43`
- **تسليم جزئي:** `app/Services/ShipmentCollectionService.php:56`

### الوظائف الم verified
1. **التسليم الكامل:**
   - التحقق من صلاحيات المستخدم للمستودع
   - التحقق من وصول جميع الطرود النشطة
   - تقديم جميع الطرود لحالة التسليم
2. **التسليم الجزئي:**
   - التحقق من صلاحيات المستخدم
   - تسليم الطرود الواصلة فقط
   - بقاء الطرود المتبقية قيد المتابعة (D-029)

### الميزات
- استخدام المعاملات (DB::transaction) لضمان الاتساق
- قفل السجلات (lockForUpdate) لمنع التعارضات
- التحقق من صحة المستودع (يجب أن يكون مستودع الوجهة)
- رسائل خطأ واضحة بالعربية

---

## المسار 6: إشعارات واتساب

### الملفات الرئيسية
- **خدمة واتساب:** `app/Services/WhatsAppMessageService.php:21`
- **رابط الاستلام:** `app/Services/WhatsAppMessageService.php:26`
- **رابط الوصول:** `app/Services/WhatsAppMessageService.php:47`
- **توليد الرابط:** `app/Services/WhatsAppMessageService.php:72`
- **توحيد الهاتف:** `app/Services/WhatsAppMessageService.php:84`

### الوظائف الم verified
1. **إشعار الاستلام:**
   - `intakeUrl()`: رابط واتساب للعميل (المرسل)
   - `intakeMessage()`: رسالة تحتوي على رابط التتبع
2. **إشعار الوصول:**
   - `arrivalUrl()`: رابط واتساب للمستلم
   - `arrivalMessage()`: رسالة تحتوي على المبلغ المستحق
3. **توليد الرابط:** `urlFor()` يربط الرسالة برابط wa.me
4. **توحيد الهاتف:** `normalisePhone()` يضبط تنسيق رقم الهاتف لواتساب

### الميزات
- روابط واتساب بنقرة واحدة (wa.me)
- رسائل عربية مسبقة التجهيز
- توحيد تلقائي للأرقام اللبنانية (إضافة 961)
- تنسيق المبالغ بالدولار الأمريكي بدقة
- لا يتطلب API مدفوع (الموظف يرسل يدوياً)

---

## المسار 7: العمليات الجماعية

### الملفات الرئيسية
- **التحديث الجماعي:** `app/Filament/Resources/Shipments/Pages/ListShipments.php:101`
- **جلب الشحنات:** `app/Filament/Resources/Shipments/Pages/ListShipments.php:126`
- **التقديم الجماعي:** `app/Filament/Resources/Shipments/Pages/ListShipments.php:173`
- **إرسال الرسائل:** `app/Filament/Resources/Shipments/Pages/ListShipments.php:195`
- **إضافة الملاحظات:** `app/Filament/Resources/Shipments/Pages/ListShipments.php:252`

### الوظائف الم verified
1. **التحديث الجماعي للحالة:**
   - التحقق من المستخدم والشحنات المحددة
   - جلب الشحنات مع طرودها
   - تصفية الطرود القابلة للتحديث
   - استخدام `PackageJourneyService::advance()` أو `correct()`
2. **إرسال رسالة جماعية:**
   - التحقق من المستخدم والشحنات
   - إضافة ملاحظة/إشعار للطرود
   - استخدام `PackageJourneyService::addNote()`

### الميزات
- تحديد شحنات متعددة دفعة واحدة
- اختيار الحالة الجديدة أو التقديم التلقائي
- إرسال إشعارات جماعية للعملاء
- إحصائيات النجاح والفشل
- إشعارات Filament واضحة

---

## الخلاصة

✅ **جميع المعلومات المذكورة في خريطة البرمجة صحيحة وم verified**

تم التحقق من:
- 7 مسارات رئيسية
- 35 ملف وموقع
- جميع الوظائف والخدمات المذكورة
- التكامل بين المكونات المختلفة

النظام يعتمد على:
- Laravel مع Filament للواجهة الإدارية
- SQLite للتطوير و PostgreSQL للإنتاج
- واجهة عربية RTL متجاوبة مع الهواتف
- رموز QR وباركود تلقائي
- إشعارات واتساب بدون API مدفوع
- معاملات قاعدة بيانات لضمان الاتساق
- سياسات صلاحيات صارمة
