# مرجع واجهات API وقنوات البث

## مقدمة

كل المسارات التالية معرفة في [`routes/api.php`](../routes/api.php)، ما لم يذكر غير ذلك. يستجيب التطبيق بصيغة المساعد [`ApiResponse`](../app/Support/ApiResponse.php) في غالبية الواجهات. يجب أن يثبت فريق الاستعادة مخطط الاستجابة الدقيق واختبارات التكامل بعد استرجاع مشروع Laravel الكامل، لأن الحزمة الحالية لا تحتوي بيئة تشغيل قابلة لتنفيذ اختبارات HTTP.

| فئة الواجهة | المصادقة/السياق | الاستخدام |
|---|---|---|
| Webhooks | توقيع Meta أو سر Evolution | استقبال رسائل منصات خارجية. |
| واجهة المتجر | `{merchant}` في المسار عبر `ResolveMerchant` | بيانات العميل العامة لتاجر محدد. |
| جلسة/رمز التاجر | `auth:sanctum` وقدرة `dashboard:access` | عمليات الإدارة وMini-CRM. |
| صفحات الويب | جلسة Laravel `auth` | عرض لوحة الإدارة ووسائط المنتجات. |

## 1. Webhooks والمصادقة

تدخل Webhooks مباشرة إلى طابور العمل لتقليل زمن استجابة الطرف الخارجي. لا تضف عليها مصادقة تاجر أو `{merchant}`؛ تحديد التاجر يأتي من البيانات الموثقة داخل الحمولة، كما هو موضح في [`ProcessMetaMessageJob`](../app/Jobs/ProcessMetaMessageJob.php).

| الطريقة والمسار | الحماية | المتحكم | النتيجة |
|---|---|---|---|
| `GET /api/meta/webhook` | `META_VERIFY_TOKEN` في query | `WebhookController@verifyWebhook` | استجابة challenge للتحقق من Meta. |
| `POST /api/meta/webhook` | `X-Hub-Signature-256` و`META_APP_SECRET` | `WebhookController@handleWebhook` | دفع الحمولة إلى job لمعالجة Meta. |
| `POST /api/webhook/evolution` | ترويسة سرية قابلة للضبط | `WebhookController@handleEvolutionWebhook` | قبول `messages.upsert` وربط instance بالتاجر ثم دفع job. |
| `POST /api/login` | محدد السرعة `6/دقيقة` | `AuthController@login` | إنشاء رمز Sanctum للتاجر. |
| `POST /api/logout` | `auth:sanctum` | `AuthController@logout` | إلغاء الرمز المستخدم. |

## 2. واجهة المتجر العامة

جميع المسارات التالية تحت البادئة `/api/stores/{merchant}`. يشير `merchant` إلى `store_slug` أو id احتياطيًا، ويطبّق middleware العزل قبل الوصول إلى المتحكم. لا تستخدم معرف منتج من متجر آخر؛ تقوم آليات النطاق والتحقق بإخفائه أو رفضه.

| الطريقة والمسار | المتحكم | الغرض | ضوابط بارزة |
|---|---|---|---|
| `GET /products` | `ProductController@index` | قائمة المنتجات النشطة مع بحث/فلاتر بحسب المتحكم | تحميل الفئة والوسائط والمراجعات المقبولة فقط. |
| `GET /products/bestsellers` | `ProductController@bestsellers` | المنتجات النشطة الموسومة كأكثر مبيعًا | يتطلب `is_bestseller=true`. |
| `GET /products/newest` | `ProductController@newest` | أحدث المنتجات النشطة | نقطة منفصلة عن الفلترة العامة. |
| `GET /categories` | `CategoryController@index` | الفئات النشطة | مرتبة بالاسم. |
| `GET /settings` | `SettingsController@index` | العملة ورقم استقبال الطلبات وحد الشحن المجاني | يعرض payload عامًا فقط، دون tokens. |
| `GET /products/{productId}/reviews` | `ReviewController@index` | التقييمات العامة للمنتج | يعرض المقبول فقط. |
| `POST /products/{productId}/reviews` | `ReviewController@store` | إرسال تقييم جديد | محدد إلى `10/دقيقة` ويخضع للمراجعة. |
| `GET /favorites` | `FavoriteController@index` | مفضلة زائر المتجر | محدد إلى `60/دقيقة`. |
| `POST /products/{productId}/favorite` | `FavoriteController@store` | إضافة إلى المفضلة | محدد إلى `30/دقيقة`. |
| `DELETE /products/{productId}/favorite` | `FavoriteController@destroy` | إزالة من المفضلة | محدد إلى `30/دقيقة`. |
| `POST /checkout/build-message` | `CheckoutController@buildMessage` | التحقق من السلة وتكوين رابط WhatsApp | محدد إلى `20/دقيقة`؛ لا ينشئ Order في قاعدة البيانات. |

### عقد Checkout المهم

يتوقع `POST /checkout/build-message` كائن `customer` يحوي `name` و`phone` الإلزاميين، و`address` و`note` اختياريين، ومصفوفة `items` من `id` و`quantity`. يتحقق الخادم من أن المنتج نشط وينتمي إلى المتجر، ثم من كمية المخزون إن كانت محددة. تتضمن الاستجابة الناجحة رابط `whatsappUrl` و`subtotal`؛ أما غياب رقم WhatsApp الصحيح فينتج رمزًا وظيفيًا `merchant_whatsapp_unavailable`.

| حقل | القيد المتحقق منه | ملاحظة للواجهة |
|---|---|---|
| `customer.name` | نص مطلوب حتى 255 حرفًا | لا تعتمد على التحقق في المتصفح وحده. |
| `customer.phone` | نص مطلوب حتى 30 حرفًا | يظهر ضمن رسالة WhatsApp ولا يحوّله هذا endpoint إلى حساب عميل. |
| `customer.address` | اختياري حتى 255 حرفًا | يعرض في الرسالة عند توفره. |
| `customer.note` | اختياري حتى 500 حرف | يعرض في الرسالة عند توفره. |
| `items[].id` | منتج قائم ونشط للتاجر | لا تقبل الواجهة رقمًا خارج البيانات المحمّلة. |
| `items[].quantity` | عدد صحيح موجب | يعاد فحص الحد مقابل مخزون المنتج على الخادم. |

## 3. واجهة التاجر المحمية

مسارات هذا القسم داخل مجموعة `auth:sanctum` مع قدرة `dashboard:access`، عدا `logout`. يحتاج عميل API إلى رمز صحيح وقدرة صادرة للمستخدم المناسب. لا تخلط هذه الآلية مع جلسة صفحات Blade إلا بعد اختبار إعداد Sanctum وCSRF في المشروع المستعاد.

| الطريقة والمسار | المتحكم | الغرض | حد الطلب |
|---|---|---|---|
| `GET /api/categories` | `CategoryController@merchantIndex` | جميع فئات التاجر، بما فيها غير النشطة، مع عدد المنتجات. | — |
| `POST /api/categories` | `CategoryController@store` | إنشاء فئة. | `30/دقيقة` |
| `PATCH /api/categories/{category}` | `CategoryController@update` | تعديل فئة. | `30/دقيقة` |
| `DELETE /api/categories/{category}` | `CategoryController@destroy` | حذف فئة. | `30/دقيقة` |
| `GET /api/products` | `ProductController@merchantIndex` | جميع منتجات التاجر إدارية. | — |
| `POST /api/products` | `ProductController@store` | إنشاء منتج ووسائط gallery. | `30/دقيقة` |
| `PATCH /api/products/{product}` | `ProductController@update` | تعديل المنتج أو استبدال وسائط gallery. | `30/دقيقة` |
| `DELETE /api/products/{product}` | `ProductController@destroy` | حذف لين للمنتج. | `30/دقيقة` |
| `GET /api/messages` | `MessageController@index` | صندوق الوارد الموحّد. | — |
| `POST /api/messages/{customer}/reply` | `MessageController@reply` | إرسال رد من المنصة المناسبة. | — |
| `GET /api/customers` | `CustomerController@index` | قوائم Mini-CRM. | — |
| `GET /api/customers/{customer}` | `CustomerController@show` | تفصيل العميل والبيانات المرتبطة. | — |
| `PATCH /api/customers/{customer}` | `CustomerController@update` | تحديث بيانات العميل. | `30/دقيقة` |
| `POST /api/orders/from-chat` | `OrderController@storeFromChat` | إنشاء طلب من محادثة. | `60/دقيقة` |
| `GET /api/orders/{order}` | `OrderController@show` | تفاصيل طلب. | — |
| `PATCH /api/orders/{order}/tag` | `OrderController@updateTag` | تعديل وسم/تصنيف الطلب. | — |
| `POST /api/orders/{order}/items` | `OrderItemController@store` | إضافة عنصر مع فحص مخزون ومعاملة. | `30/دقيقة` |
| `DELETE /api/order-items/{orderItem}` | `OrderItemController@destroy` | حذف عنصر طلب. | `30/دقيقة` |
| `GET /api/reports/sales` | `ReportController@index` | عدد طلبات اليوم والطلبات المشحونة. | — |

## 4. التفاعل والإعدادات وربط WhatsApp

تعالج واجهات التفاعل المراجعات والمفضلة، فيما تحفظ واجهات الإعدادات القيم العامة في `settings` والقيم التكاملية الحساسة على المستخدم. تملك لوحة التاجر صفحة QR مستقلة عن رقم WhatsApp الذي يستخدمه checkout؛ الأول جلسة Evolution للرسائل، والثاني رقم استقبال طلبات السلة.

| الطريقة والمسار | المتحكم | النتيجة |
|---|---|---|
| `GET /api/admin/reviews` | `ReviewController@adminIndex` | قائمة مراجعات بحسب الحالة. |
| `GET /api/admin/reviews/pending` | `ReviewController@pendingReviews` | المراجعات المعلقة. |
| `PATCH /api/admin/reviews/{review}/approve` | `ReviewController@approve` | اعتماد مراجعة. |
| `PATCH /api/admin/reviews/{review}/reject` | `ReviewController@reject` | رفض مراجعة مع سبب إن أرسل. |
| `DELETE /api/admin/reviews/{review}` | `ReviewController@destroy` | حذف مراجعة. |
| `GET /api/admin/favorites` | `FavoriteController@adminIndex` | رؤية التفضيلات إدارياً. |
| `GET /api/settings/internal` | `SettingsController@internal` | إعدادات التاجر والربط دون كشف token. |
| `PUT /api/settings` | `SettingsController@update` | تحديث العملة، أرقام WhatsApp، الشحن، ومعرفات/رموز Meta. |
| `POST /api/whatsapp/connect` | `WhatsAppConnectionController@connect` | إنشاء/استعادة instance وضبط Webhook وإعادة QR/الحالة. |
| `GET /api/whatsapp/status` | `WhatsAppConnectionController@status` | حالة جلسة Evolution الحالية. |

## 5. الصفحات وقنوات البث

تعرّف [`routes/web.php`](../routes/web.php) مسارات واجهة Blade. `GET /media/{media}` يعرض وسائط المنتج العامة، فيما تبقى صفحات `/admin/*` خلف جلسة `auth`. يمكن استخدام `/login` للدخول و`POST /logout` للخروج، ويوجد `/dashboard` المختصر المتوافق مع التوجيه بعد الدخول.

| القناة | تعريف الوصول | الحدث المتوقع |
|---|---|---|
| `private-merchant.{merchantId}` | المستخدم الحالي يجب أن يملك id نفسه | `MessageReceived` بعد معالجة رسالة واردة. |

تحتاج الواجهة إلى مصادقة صحيحة على نقطة البث وإعداد Reverb/Pusher متناسق. لا تعد القناة عامة، ولا تفتحها بدعوى تسهيل اختبار الإنتاج.

## 6. ملاحظات التكامل

يجب اعتبار هذا المرجع خريطة مصدرية لا مواصفة إصدارية. قبل بناء عميل مغاير أو تطبيق جوال، شغّل اختبارات تكامل ضد النسخة المستعادة، وثّق HTTP status codes وschema الفعليين، واختبر مسارات الخطأ خصوصًا: متجر غير موجود، منتج خارج نطاق التاجر، مخزون غير كافٍ، رقم WhatsApp غير مضبوط، ومفتاح Webhook غير مطابق.
