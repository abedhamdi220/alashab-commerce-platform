# الدليل التشغيلي والاستعادة

## 1. الغرض وحدود هذا الدليل

هذا الدليل يصف **كيف يستعاد ويشغّل المشروع بصورة صحيحة** من الحزمة الحالية. بعد دفعة الملفات الثانية، أصبحت ملفات Laravel الأساسية المتاحة (`composer.json` و`bootstrap/` و`config/` وmigrations وseeders و`public/` و`tests/`) وملفات Vite (`package.json` و`vite.config.js` وملف قفل pnpm) ضمن المستودع. ما زالت بعض ملفات الجذر غير متاحة، وعلى رأسها `artisan` وملف PHPUnit و`composer.lock`، لذلك يظل تشغيل Laravel الخلفي مشروطًا باستعادتها أو اعتماد بدائلها من المصدر الأصلي.

| متاح في الحزمة | ما يزال غير متاح | الأثر التشغيلي |
|---|---|---|
| متحكمات ونماذج وخدمات Laravel وRoutes وBlade و`composer.json` و`bootstrap/` و`config/` وmigrations وseeders وtests | `artisan` و`composer.lock` وملف PHPUnit | لا يمكن حسم تثبيت Composer أو تشغيل Artisan/PHPUnit من هذه النسخة وحدها. |
| مصدر React واختبارات Vitest و`package.json` و`vite.config.js` و`pnpm-lock.yaml` | لا توجد نواقص لازمة لبناء الواجهة | اجتاز `pnpm test` و`pnpm build` في بيئة الفحص. |
| Compose لخدمة Evolution وقالب `.env` | ملف Evolution `.env` ونسخة image موثقة/مقفلة | يجب اختيار نسخة معتمدة من Evolution وضبطها بقيم سرية خارج Git. |

> **قاعدة القرار:** لا تخمّن إصدارات Laravel أو PHP أو Node أو حزم Composer/Node. استعد ملفات الاعتماد من المصدر الأصلي أو من نسخة نشر ناجحة، ثم افحص توافقها مع الشفرة الموجودة قبل البدء في بيئة مشتركة أو إنتاجية.

## 2. ترتيب الاستعادة المقترح

ابدأ باستعادة `artisan` و`composer.lock` وملف PHPUnit من جذر Laravel الأصلي أو من artifact نشر ناجح، ثم ثبّت اعتماديات Composer وفق ملف القفل المعتمد. ملفات التهيئة وmigrations والواجهة أصبحت موجودة في المستودع. لا تستخدم `composer update` أو تثبيت نسخ عشوائية لاستبدال ملف القفل المفقود؛ ذلك قد ينتج توافقًا ظاهريًا فقط ويغير السلوك الأمني والتشغيلي.

| المرحلة | الإجراء | معيار الاكتمال |
|---|---|---|
| 1. استعادة الجذر | استعادة `artisan` و`composer.lock` وملف PHPUnit من المصدر الأصلي، مع مراجعة توافقها مع [`composer.json`](../composer.json). | يمكن لأوامر Composer وArtisan والتكوين اختراق دورة bootstrap بنجاح. |
| 2. تهيئة البيانات | استخدام migrations وseeders المرفوعة ومراجعة مخطط Media Library وطريقة إنشاء المستخدم/المتجر. | قاعدة التطوير تبنى نظيفًا ويظهر تاجر له `store_slug`. |
| 3. تهيئة Laravel | إنشاء `.env` من [`.env.example`](../.env.example) ثم ضبط قاعدة البيانات والطوابير والبث والتكاملات. | ينجح `config:clear` و`config:cache` في البيئة المستهدفة. |
| 4. تهيئة الواجهة | تنفيذ `pnpm install --frozen-lockfile` ثم إضافة متغيرات `VITE_*` وربط أصل API بمتجر اختبار. | تنجح اختبارات الواجهة والبناء وطلبات متجر الاختبار. |
| 5. تشغيل العمليات | تشغيل HTTP وqueue worker وReverb/خدمة البث وفق النسخة المستعادة. | تصل رسالة اختبار إلى الصندوق وتظهر في القناة الخاصة للتاجر. |
| 6. ربط Evolution | إنشاء `evolution/.env` من القالب وتشغيل الخدمات وربط QR عبر لوحة التاجر. | حالة instance تصبح `open` ويصل Webhook محمي إلى Laravel. |

## 3. اعتماديات يجب تثبيتها بعد استعادة manifests

يكشف [`composer.json`](../composer.json) عن قيود اعتماديات PHP، لكنه لا يغني عن `composer.lock` المعتمد الذي ما زال غير متاح. في المقابل، استُعيدت اعتماديات الواجهة من imports الفعلية في المصدر وثُبتت في [`pnpm-lock.yaml`](../pnpm-lock.yaml) بعد اجتياز الاختبارات والبناء. ينبغي مراجعة نسخ PHP النهائية عند استعادة ملف قفل Composer، لا استبدالها بتخمينات.

| المجال | الاعتماد المستنتج من الشفرة | الموضع الدال |
|---|---|---|
| PHP/Laravel | Laravel framework، Sanctum، Queue، Broadcasting، HTTP Client، Soft Deletes | [`app/`](../app/) و[`routes/api.php`](../routes/api.php) |
| وسائط PHP | `spatie/laravel-medialibrary` | [`Product`](../app/Models/Product.php) و[`Message`](../app/Models/Message.php) |
| الواجهة | React، React DOM، TanStack React Query، Axios، Lucide React، Framer Motion، React Router | [`package.json`](../package.json) و[`src/`](../src/) |
| البث في المتصفح | Laravel Echo وPusher JS، متوافق مع Reverb | [`src/services/echo.js`](../src/services/echo.js) |
| الاختبارات | Vitest وTesting Library و`jest-dom` وJSDOM | [`src/test/setup.js`](../src/test/setup.js) و[`vite.config.js`](../vite.config.js) والاختبارات المرفقة |

## 4. ملفات التهيئة التي يجب أن توجد في Laravel

تستدعي الشفرة مفاتيح `merchant_integrations.*` و`model-activity.*`، وقد استُعيدت ملفات التهيئة المقابلة داخل [`config/`](../config/). لا تعتبر [`.env.example`](../.env.example) بديلًا عن ملفات Laravel config؛ هو فقط مصدر قيم البيئة. راجع القيم وتوافقها عند استعادة `artisan` وملف قفل Composer قبل أي نشر.

| ملف/قسم متوقع | المفاتيح التي تستخدمها الشفرة | الاستهلاك |
|---|---|---|
| `config/merchant_integrations.php` | `default_country_code`، `meta.verify_token`، `meta.app_secret`، `meta.whatsapp_access_token`، `messenger.access_token` | Webhooks، WhatsApp Cloud API، Messenger، وإنشاء رابط checkout. |
| القسم `evolution` ضمن الملف نفسه | `base_url`، `api_key`، `webhook_url`، `webhook_secret`، `webhook_header` | توليد instance، QR، ضبط Webhook، والردود الصادرة. |
| `config/model-activity.php` | `enabled`، `level`، `days`، `include_ip`، `excluded_attributes` | Trait تسجيل تغييرات النماذج. |
| إعداد البث | Reverb/Pusher connection وتعريف قناة خاصة | [`MessageReceived`](../app/Events/MessageReceived.php) وواجهة Echo. |
| إعداد الصف | Queue connection وfailed jobs | [`ProcessMetaMessageJob`](../app/Jobs/ProcessMetaMessageJob.php) و[`SendDeliveryNotification`](../app/Listeners/SendDeliveryNotification.php). |

## 5. تهيئة الخلفية وقاعدة البيانات

بعد استعادة manifests وmigrations، أنشئ ملف `.env` محليًا من القالب، ثم ولّد `APP_KEY` ضمن بيئة Laravel. استخدم قاعدة بيانات Laravel مستقلة عن قاعدة Evolution. يجب أن تسجل migrations جداول النماذج المستخدمة، بما في ذلك المستخدمين والمنتجات والفئات والعملاء والرسائل والطلبات وعناصرها والإعدادات والزوار والمفضلة والمراجعات، وجداول Spatie Media Library، وأي جداول مطلوبة للطوابير والبث والجلسات وفق drivers المختارة.

| التحقق | النتيجة المتوقعة | عند الفشل |
|---|---|---|
| تاجر اختبار | مستخدم له `store_slug` فريد وبيانات API/WhatsApp المناسبة | راجع migration/seed أو حقل المستخدم؛ لا تمرر متجرًا غير موجود إلى المسارات العامة. |
| إعدادات المتجر | مفاتيح `currency` و`whatsapp_number` و`free_shipping_threshold` مرتبطة بـ`merchant_id` | استخدم لوحة الإعدادات أو Seeder؛ رقم WhatsApp يجب أن يكون بصيغة دولية قابلة للتطبيع. |
| وسائط المنتج | مجموعة `product_gallery` على disk `public` | تحقق من تهيئة Media Library والأذونات ووجهة التخزين. |
| صفوف العمل | Job للرسائل يعمل دون التأثير في استجابة Webhook | شغّل worker دائمًا، واضبط failed jobs والرصد قبل الإنتاج. |

لا تضع رمز وصول WhatsApp أو Messenger داخل جدول إعدادات عام. تحفظ الشفرة رموز التاجر في حقول المستخدم مع Cast `encrypted`، بينما تعرض واجهة الإعدادات مؤشر وجود للرمز بدل القيمة. حافظ على هذا الفصل عند استعادة schema وواجهات الإدارة.

## 6. إعداد وبناء واجهة React

تدعم الواجهة إعدادًا وقت البناء ومتغيرات إعداد وقت التشغيل عبر `window.__APP_CONFIG__` أو `window.CONFIG`. تحتاج قيمة API إلى متجر محدد؛ مثل `http://127.0.0.1:8000/api/stores/demo-store`. تأكد أن قيمة `VITE_STORE_SLUG` تتوافق مع `store_slug` الفعلي حين لا يكون slug مضمّنًا داخل العنوان الأساسي.

| المتغير | مثال آمن للتطوير | ملاحظات |
|---|---|---|
| `VITE_API_BASE_URL` | `http://127.0.0.1:8000/api/stores/demo-store` | يجب أن ينتهي بجذر API المتجر، لا بجذر `/api` العام فقط. |
| `VITE_STORE_SLUG` | `demo-store` | يستخدم لاستكمال العنوان عند الحاجة. |
| `VITE_BACKEND_ORIGIN` | `http://127.0.0.1:8000` | يستخدم لتكوين عناوين الوسائط النسبية. |
| `VITE_MEDIA_BASE_URL` | `http://127.0.0.1:8000` | يمكن توجيهه إلى CDN عند توفره. |
| `VITE_REVERB_*` | بيئة Reverb المحلية أو الإنتاجية | اضبط TLS وhost وport بما يتوافق مع النشر. |

ينشئ [`src/services/echo.js`](../src/services/echo.js) نقطة مصادقة بث محلية بصورة صريحة. قبل الإنتاج، راجع origin وTLS و`authEndpoint` لضمان أنه لا يبقى مرتبطًا بـ`localhost:8000` في build مرفوع، وراجع سياسة CORS ومسارات المصادقة بحسب شكل نشر الواجهة والخلفية.

## 7. تشغيل Evolution API

توجد خدمة Evolution في مجلد منفصل. انسخ [`evolution/.env.example`](../evolution/.env.example) إلى `evolution/.env`، وأدخل كلمات مرور طويلة ومختلفة وAPI key فريدًا. يجبر ملف Compose الحالي إدخال كلمتي مرور قاعدة البيانات بدل تمرير قيم افتراضية. حُدد منفذ Evolution على `127.0.0.1:8080`، لذلك يحتاج نشر خارجي إلى reverse proxy آمن أو شبكة داخلية مناسبة؛ لا تفتح واجهة الإدارة مباشرة للعامة بلا تحكم بالوصول.

| خطوة Evolution | التنفيذ المطلوب | تحقق النجاح |
|---|---|---|
| إنشاء الأسرار | ضبط `EVOLUTION_DB_PASSWORD` و`EVOLUTION_DB_ROOT_PASSWORD` و`AUTHENTICATION_API_KEY` في `evolution/.env` | لا توجد أي قيمة سرية في Git أو سجل الأوامر. |
| تشغيل الحاويات | تشغيل Compose من مجلد `evolution/` بعد تثبيت Docker Compose | تصبح MySQL وRedis healthy ثم يبدأ Evolution API. |
| تهيئة Laravel | ضبط `EVOLUTION_BASE_URL` و`EVOLUTION_API_KEY` وURL webhook عام وsecret مطابق | تعيد واجهة الحالة حالة يمكن قراءتها بدل 503. |
| ربط التاجر | فتح `/admin/whatsapp` كتاجر، إنشاء/استعادة instance ومسح QR | تعيد الحالة `open` واسم instance بصيغة `merchant_{id}`. |
| اختبار الرسالة | إرسال رسالة إلى الرقم المربوط | يصل `messages.upsert` بترويسة السر، ثم تظهر الرسالة في صندوق التاجر فقط. |

ملف [`create-evolution-database.sql`](../evolution/create-evolution-database.sql) صار قالبًا يدويًا. لا تنفذه قبل استبدال placeholder محليًا بالقيمة المطابقة لملف `evolution/.env`، ولا تحفظ النسخة المستبدلة. عند استخدام Compose كما هو، عادةً لا تحتاج هذا النص لأن MySQL ينشئ القاعدة والمستخدم من متغيرات البيئة؛ احتفظ به فقط لحالات التهيئة اليدوية.

## 8. Webhooks وMeta

يحتاج Meta إلى endpoint عام HTTPS. يستقبل النظام طلب التحقق من `GET /api/meta/webhook` باستخدام `META_VERIFY_TOKEN`، ويتحقق من طلبات `POST` عبر HMAC SHA-256 ومفتاح التطبيق. يسجل `ProcessMetaMessageJob` الرسائل بعد العثور على تاجر مطابق لـ`meta_phone_id` أو `meta_page_id`. لذلك لا تعِد توجيه webhooks إلى بيئة قبل ضبط هذه المعرفات على حساب التاجر الصحيح.

| endpoint | الحماية | شرط التشغيل |
|---|---|---|
| `GET /api/meta/webhook` | `META_VERIFY_TOKEN` | متاح فقط لإجراء تحقق Meta. |
| `POST /api/meta/webhook` | `X-Hub-Signature-256` و`META_APP_SECRET` | Queue worker نشط ومعرفات Meta محفوظة للتاجر. |
| `POST /api/webhook/evolution` | ترويسة `EVOLUTION_WEBHOOK_HEADER` وقيمة secret مطابقة | instance Evolution معدة على URL عام HTTPS. |
| `/api/broadcasting/auth` | مصادقة التاجر | Reverb وبروتوكول القناة الخاصة مهيآن. |

## 9. فحص ما قبل الإنتاج

تحتاج بيئة الإنتاج إلى خادم HTTP دائم وworker صف دائم وخدمة بث دائمة أو بديل مدعوم. استخدم supervisor أو systemd أو منصة حاويات أو خدمة مدارة بحسب بنية الاستضافة. لا يكفي تشغيل عملية worker في جلسة terminal عابرة.

| المجال | فحص إلزامي قبل الإطلاق |
|---|---|
| الأسرار | لا توجد ملفات `.env` أو tokens أو كلمات مرور في Git، وجميع مفاتيح الإنتاج مولدة حديثًا. |
| الحماية | HTTPS مفعل، CORS مقيد بأصول الواجهة، rate limits عاملة، والتحقق من Webhooks مفعل. |
| العزل | اختبار متجرين مختلفين والتأكد أن المورد التابع لأحدهما يعيد 404/نتيجة فارغة للآخر. |
| الطلبات | اختبار مخزون محدود، إنشاء طلب من الدردشة، إلغاء/تغيير حالة، واستعادة الكمية عند الحاجة. |
| الرسائل | اختبار وصول WhatsApp وMessenger إن فُعلا، ورد صادر، وبث حي إلى التاجر الصحيح فقط. |
| النسخ الاحتياطي | نسخ دورية لقاعدة Laravel ووسائط Media Library وبيانات Evolution، واختبار استعادة فعلي. |
| الرصد | سجلات مركزية، تنبيه failed jobs، ومراقبة HTTP/queue/Redis/MySQL وإعادة تشغيل الخدمة. |

## 10. التشغيل اليومي والاستجابة للحوادث

راقب فشل jobs قبل أن يصبح صندوق الرسائل صامتًا. إذا تعذر وصول الرسائل، ابدأ بالتحقق من توقيع Webhook أو ترويسة Evolution ثم من worker ثم من مطابقة `meta_phone_id` أو اسم instance. إذا بقيت رسائل العميل في حالة خارجية فقط، لا تعِد إرسالها تلقائيًا من دون تشخيص idempotency؛ المهمة تستخدم `platform_message_id` لمنع التكرار.

| العرض | نقاط التشخيص الأولى | إجراء آمن |
|---|---|---|
| صفحة المتجر لا تحمل منتجات | عنوان `VITE_API_BASE_URL` وslug وCORS واستجابة `/products` | استخدم متجر اختبار وتحقق من أن المنتج نشط. |
| Checkout لا يعطي رابط WhatsApp | إعداد `whatsapp_number` وصيغة الرقم ومخزون العناصر | اضبط الرقم من لوحة التاجر ولا تضعه في كود الواجهة. |
| لوحة WhatsApp تعيد 503 أو 502 | Evolution health و`EVOLUTION_*` وURL Webhook | لا تعطل تحقق السر لتجاوز المشكلة؛ أصلح الإعداد أو الشبكة. |
| الرسائل الواردة لا تظهر | توقيع/secret، worker، user mapping، بث Reverb | افحص سجلات التطبيق والـqueue قبل إعادة ربط QR. |
| فشل تخفيض المخزون | صلاحية إنشاء order item وانتقالات `OrderService` وtransaction | لا تعدّل `stock_quantity` يدويًا إلا ضمن إجراء تصحيحي موثق. |

لإطار مراجعة الحزمة وحدود التحقق الحالية، راجع [`REPOSITORY-AUDIT.md`](REPOSITORY-AUDIT.md). ولتفاصيل المسارات، راجع [`API.md`](API.md).
