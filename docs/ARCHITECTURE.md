# معمارية منصة العشّاب

## 1. النظرة العامة

تعمل المنصة كحل تجارة ومراسلات **متعدد المتاجر**. الخلفية هي Laravel، بينما توجد واجهة متجر مستقلة مكتوبة بـ React/Vite ولوحة تاجر بقوالب Blade وAlpine. لا تعتمد الواجهة العامة على مستخدم مسجل؛ بل يأتي سياق المتجر من `store_slug` داخل الرابط. أما لوحة التاجر فتستخدم جلسة ويب، وتستخدم واجهات الإدارة رمز Sanctum يحمل قدرة `dashboard:access`.

| الطبقة | الموقع | الدور |
|---|---|---|
| واجهة متجر العميل | [`src/`](../src/) | تصفح المنتجات، البحث، المفضلة، التقييمات، السلة، ورسالة الطلب عبر WhatsApp. |
| واجهة الإدارة | [`resources/views/admin/`](../resources/views/admin/) | إدارة المنتجات والفئات والعملاء والرسائل والإعدادات وربط WhatsApp والتقارير. |
| واجهة HTTP | [`routes/api.php`](../routes/api.php) و[`routes/web.php`](../routes/web.php) | الفصل بين API العامة وAPI التاجر وصفحات الويب وWebhooks. |
| النطاق التجاري | [`ResolveMerchant`](../app/Http/Middleware/ResolveMerchant.php) و[`MerchantScope`](../app/Models/Scopes/MerchantScope.php) | تحديد المتجر وفرض المرشح `merchant_id` تلقائيًا. |
| المنطق التجاري | [`app/Services/`](../app/Services/) | دورة الطلب، خصم المخزون، الردود الخارجية، وتشكيل صندوق الرسائل. |
| العمل الخلفي والبث | [`app/Jobs/`](../app/Jobs/) و[`app/Events/`](../app/Events/) | معالجة Webhooks خارج مسار الاستجابة وبث الرسائل لحظيًا. |
| تكامل WhatsApp | [`evolution/`](../evolution/) | Evolution API مع MySQL وRedis لحسابات WhatsApp المرتبطة بالتاجر. |

## 2. العزل متعدد المتاجر

المفتاح التنظيمي للمنصة هو `merchant_id`. النماذج التي تستخدم Trait [`BelongsToMerchant`](../app/Traits/BelongsToMerchant.php) تتلقى Global Scope. في طلبات العميل العامة، يقرأ [`ResolveMerchant`](../app/Http/Middleware/ResolveMerchant.php) اسم المتجر من `{merchant}`، ويضع المعرّف في الحاوية تحت `current_merchant_id`. في طلبات التاجر المحمية، يعتمد النطاق على المستخدم المصادق عليه. وعندما يغيب السياقان، يضيف النطاق الشرط `1 = 0`، أي أن النظام لا يعرض بيانات افتراضيًا.

| نوع الطلب | مصدر التاجر | نتيجة عزل البيانات |
|---|---|---|
| متجر عام | `/api/stores/{store_slug}/...` | يحدد `ResolveMerchant` التاجر من slug أو id احتياطيًا، ثم يفلتر النطاق كل نموذج معزول. |
| لوحة/واجهة API للتاجر | جلسة Laravel أو `auth:sanctum` | يستنتج النطاق التاجر من `Auth::id()` ويمنع الموارد التابعة لتاجر آخر. |
| Webhook خارجي | معرّف Meta أو اسم Evolution instance | تقوم مهمة المعالجة بتحديد التاجر صراحةً قبل إنشاء العميل أو الرسالة. |
| سياق غير معروف | لا يوجد | يفشل الاستعلام بصورة مغلقة، فلا يعيد بيانات مشتركة أو عامة. |

> **قاعدة صيانة:** أي نموذج جديد يحتفظ ببيانات تخص متجرًا يجب أن يملك `merchant_id` ويستخدم `BelongsToMerchant` أو بديلًا مكافئًا موثقًا. أي Job أو أمر CLI يعمل خارج جلسة HTTP يجب أن يمرر المعرّف صراحة أو يستعمل `forMerchant()` بحذر.

## 3. رحلة العميل والكتالوج

تنشئ [`src/services/api.js`](../src/services/api.js) عنوان API من `VITE_API_BASE_URL` و`VITE_STORE_SLUG` أو من الإعداد التشغيلي وقت التحميل. تحمل الصفحة [`Storefront`](../src/pages/Storefront.jsx) الفئات والمنتجات والإعدادات عبر React Query. يعتمد محتوى الواجهة على المنتجات النشطة فقط، ويعرض أحدث المنتجات والأكثر مبيعًا عبر نقاط نهاية مخصصة.

| المرحلة | التنفيذ | الضابط الرئيس |
|---|---|---|
| عرض الكتالوج | `GET /api/stores/{merchant}/products` و`categories` | تصفية التاجر و`is_active=true` على الجانب الخادمي. |
| العرض التفصيلي | [`ProductDetailsModal`](../src/components/ProductDetailsModal.jsx) | يحدّ الواجهة من الكمية حسب مخزون المنتج إن كان محددًا. |
| السلة | [`useCart`](../src/hooks/useCart.js) | تخزين محلي آمن نسبيًا وتطبيع للبيانات وحدود المخزون في الواجهة. |
| تأكيد الطلب | [`CartDrawer`](../src/components/CartDrawer.jsx) | جمع الاسم والهاتف والمنطقة والموافقة على الخصوصية. |
| بناء الرسالة | [`CheckoutController`](../app/Http/Controllers/Api/CheckoutController.php) | إعادة التحقق من هوية المنتجات ومخزونها ورقم WhatsApp للتاجر قبل تكوين رابط `wa.me`. |

لا ينشئ تدفق السلة العام طلبًا في قاعدة البيانات؛ بل يصنع رابط رسالة WhatsApp يحوي تفاصيل الطلب. هذا اختيار وظيفي مهم: المتابعة الفعلية للطلب تتم داخل WhatsApp أو من لوحة الرسائل، لا بوابة دفع أو تدفق طلب ويب تقليدي.

## 4. الطلبات والمخزون

تتولى [`OrderService`](../app/Services/OrderService.php) إنشاء طلبات لوحة الدردشة وتغيير حالتها. توثق الخدمة انتقالات الحالة وتستخدم معاملات قاعدة البيانات عند تغيير المخزون. تتيح [`OrderItemController`](../app/Http/Controllers/Api/OrderItemController.php) إضافة عناصر الطلب أو حذفها، وتُدخل مساراتها ضمن مجموعة التاجر المحمية.

| الكيان | علاقته | الغرض التشغيلي |
|---|---|---|
| `Order` | ينتمي إلى `Customer` و`User`، وله عناصر | تمثيل الطلب الذي ينشأ من المحادثة. |
| `OrderItem` | ينتمي إلى `Order` و`Product` | لقطة عنصر الطلب وكميته وسعره. |
| `Product` | ينتمي إلى فئة وتاجر | يحتوي `stock_quantity` الاختياري وخصائص النشاط والعناية الخاصة والأكثر مبيعًا. |
| `Customer` | ينتمي إلى تاجر وله رسائل وطلبات | يمثل جهة الاتصال على WhatsApp أو Messenger. |

عند تغيير حالة الطلب إلى حالة تتطلب التنفيذ أو الإلغاء، يجب أن يبقى أي تعديل للمخزون محصورًا في `OrderService`؛ تجنب التعديل المباشر من متحكم أو واجهة، لأن ذلك قد يتجاوز استعادة الكمية أو المعاملة الذرية.

## 5. الرسائل والاتصالات الخارجية

تستقبل المنصة Webhooks من Meta على `/api/meta/webhook` ومن Evolution على `/api/webhook/evolution`. يتحقق [`VerifyMetaSignature`](../app/Http/Middleware/VerifyMetaSignature.php) من `X-Hub-Signature-256` بمفتاح تطبيق Meta، ويتحقق [`VerifyEvolutionWebhook`](../app/Http/Middleware/VerifyEvolutionWebhook.php) من ترويسة سرية قابلة للضبط. تستجيب نقطة النهاية سريعًا ثم تدفع المعالجة إلى [`ProcessMetaMessageJob`](../app/Jobs/ProcessMetaMessageJob.php).

| المصدر | ربط الرسالة بالتاجر | أثر المعالجة |
|---|---|---|
| WhatsApp Cloud API | `meta_phone_id` على مستخدم التاجر | إنشاء/تحديث العميل والرسالة، وتنزيل وسائط مسموحة عند توفر الرمز. |
| Messenger | `meta_page_id` على مستخدم التاجر | إنشاء/تحديث العميل والرسالة في صندوق موحد. |
| Evolution API | اسم instance بالصيغة `merchant_{id}` | يقرأ معرف التاجر من الاسم ويعالج حدث `messages.upsert`. |
| الرد الصادر | منصة العميل المسجلة | يرسل [`MessageService`](../app/Services/MessageService.php) إلى Evolution أو Graph API ثم يحفظ الرسالة الصادرة. |

يعالج Job وسائط WhatsApp عبر قائمة مضيفين مسموحين، واتصال HTTPS، وحد حجم 10 MiB، وأنواع MIME محددة. يبث الحدث [`MessageReceived`](../app/Events/MessageReceived.php) فورًا على القناة الخاصة `merchant.{merchantId}`، ويقصر تعريف القناة في [`routes/channels.php`](../routes/channels.php) الاستماع على صاحب الحساب نفسه.

## 6. لوحة التاجر

تغطي [`AdminWebController`](../app/Http/Controllers/AdminWebController.php) صفحات الإدارة، وتحميها مجموعة `auth` في [`routes/web.php`](../routes/web.php). تستدعي صفحات Blade واجهات API الإدارية. يظل فصل الصفحات عن API واضحًا: حماية الصفحة جلسية على الخادم، بينما حماية API تاجرية عبر Sanctum وقدرة dashboard.

| الصفحة | المسار | المهمة |
|---|---|---|
| لوحة المتابعة | `/admin/dashboard` | ملخص تشغيلي للتاجر. |
| صندوق وارد موحد | `/admin/inbox` | مشاهدة المحادثات، الرد، إنشاء طلب من دردشة، وتعديل العناصر/الوسوم. |
| العملاء | `/admin/customers` | عرض وإدارة Mini-CRM المرتبط بمنصات المحادثة. |
| الفئات والمنتجات | `/admin/categories` و`/admin/products` | إدارة الكتالوج وحالة العناصر ووسائط المنتجات. |
| التفاعل | `/admin/engagement` | متابعة التفضيلات والمراجعات والمراجعات المعلقة. |
| الإعدادات | `/admin/settings` | العملة، رقم استقبال الطلبات، الشحن، ومعلومات Meta والتوصيل. |
| WhatsApp | `/admin/whatsapp` | طلب QR والتحقق من حالة جلسة Evolution للتاجر. |

## 7. ملاحظات الاستعادة المعمارية

الحزمة لا تحتوي على migrations ولا تعريفات تهيئة Laravel ولا ملفات اعتماد PHP/Node. لذلك لا يمكن استنتاج مخطط قاعدة البيانات أو نسخ الحزم أو صيغة إعداد Evolution النهائية بصورة موثوقة من الشفرة وحدها. تفاصيل الأصول المفقودة والخطوات الترتيبية لاستعادتها موثقة في [`OPERATIONS.md`](OPERATIONS.md) و[`REPOSITORY-AUDIT.md`](REPOSITORY-AUDIT.md).
