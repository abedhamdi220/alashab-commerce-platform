# ملاحظات الأمان وإدارة الأسرار

## 1. ملخص المراجعة

راجعت الملفات المستلمة قبل الرفع الخاص. كانت بيانات اعتماد MySQL لـEvolution مضمنة نصيًا في [`evolution/docker-compose.yml`](../evolution/docker-compose.yml) و[`evolution/create-evolution-database.sql`](../evolution/create-evolution-database.sql). حُذفت القيم القابلة للاستخدام من النسخة المرفوعة واستبدلت بمتغيرات بيئة إلزامية وقوالب غير سرية. لا توجد ملفات `.env` ضمن المستودع، ويمنع [`.gitignore`](../.gitignore) إضافتها مستقبلًا.

| الموقع | الحالة قبل التحصين | المعالجة في هذا المستودع |
|---|---|---|
| `evolution/docker-compose.yml` | كلمة مرور مستخدم MySQL وكلمة مرور root مضمنتان نصيًا. | استخدام `EVOLUTION_DB_PASSWORD` و`EVOLUTION_DB_ROOT_PASSWORD` من `evolution/.env` المحلي. |
| `evolution/create-evolution-database.sql` | كلمة مرور مستخدم Evolution مضمنة نصيًا. | تحويل النص إلى قالب placeholder يتطلب استبدالًا محليًا قبل التنفيذ. |
| إعداد Evolution | لا يوجد قالب محكوم لإعدادات البيئة. | إضافة [`evolution/.env.example`](../evolution/.env.example) بلا قيم حقيقية. |
| إعداد التطبيق | لا يوجد قالب متغيرات بيئة في الحزمة. | إضافة [`.env.example`](../.env.example) لعقد الإعداد المتوقع من الشفرة. |

> **إجراء مطلوب عند الاشتباه بأن القيم القديمة استُخدمت خارج بيئة محلية:** دوّر كلمات المرور والمفاتيح فورًا. إن كانت كلمة مرور MySQL أو مفتاح Evolution أو رمز Meta قد استُخدمت في خدمة متصلة بالإنترنت، فاعتبرها مكشوفة ولا تكتف بحذفها من Git.

## 2. ضوابط موجودة في الشفرة

تحتوي الشفرة على عدة ضوابط جيدة يجب الحفاظ عليها أثناء الاستعادة أو إعادة البناء. لا تعني هذه الضوابط أن التكوين المنتج آمن تلقائيًا؛ تبقى مفاتيح البيئة، الشبكات، العمال، وreverse proxy جزءًا من السطح الأمني.

| الضابط | موضعه | ما يحميه |
|---|---|---|
| عزل متعدد المتاجر مغلق افتراضيًا | [`MerchantScope`](../app/Models/Scopes/MerchantScope.php) | يمنع قراءة بيانات جميع التجار عند غياب سياق متجر أو مستخدم. |
| حماية Webhook Meta | [`VerifyMetaSignature`](../app/Http/Middleware/VerifyMetaSignature.php) | يتحقق HMAC SHA-256 من الحمولة قبل المعالجة. |
| حماية Webhook Evolution | [`VerifyEvolutionWebhook`](../app/Http/Middleware/VerifyEvolutionWebhook.php) | يطلب ترويسة secret قابلة للتكوين قبل القبول. |
| تقييد الطلبات | [`routes/api.php`](../routes/api.php) | يحد محاولات الدخول والكتابة الحساسة وcheckout والتقييمات. |
| إخفاء/تشفير tokens | [`User`](../app/Models/User.php) و[`SettingsController`](../app/Http/Controllers/Api/SettingsController.php) | يخفي حقول رمز الوصول ويستخدم cast `encrypted` للرموز الحساسة. |
| الوصول إلى قناة بث خاصة | [`routes/channels.php`](../routes/channels.php) | يقصر قناة التاجر على مالك id نفسه. |
| تنزيل وسائط مقيد | [`ProcessMetaMessageJob`](../app/Jobs/ProcessMetaMessageJob.php) | يقيد المضيفين لـHTTPS، نوع MIME، والحجم الأقصى 10 MiB. |
| حماية صفحات الإدارة | [`routes/web.php`](../routes/web.php) | تعتمد صفحات `/admin` على middleware `auth` الخادمي. |

## 3. إدارة الأسرار الصحيحة

تحتاج المنصة إلى أسرار تطبيقية ومتغيرات تكامل وقاعدة بيانات. احفظها في مدير أسرار المنصة أو آلية أسرار الاستضافة، ثم حقنها في عملية التشغيل. لا تضعها في ملف React عامة؛ كل قيمة تبدأ بـ`VITE_` تصبح قابلة للقراءة في المتصفح، ولذلك يجب أن تحتوي فقط عناوين عامة ومفتاح Reverb عام مخصص للبث، لا رموز وصول أو secrets.

| السر أو القيمة | مكان التخزين المناسب | لا تضعه في |
|---|---|---|
| `APP_KEY` وبيانات Laravel DB | secrets manager أو `.env` خادم محمي | Git أو حزمة الواجهة. |
| `META_APP_SECRET` و`META_VERIFY_TOKEN` | secrets manager للخلفية | JavaScript أو إعداد عميل. |
| رمز WhatsApp/Messenger للتاجر | قاعدة بيانات مشفرة/خدمة أسرار وفق التصميم | سجل الطلبات أو response API أو صفحة Blade. |
| `EVOLUTION_API_KEY` وWebhook secret | `.env` Evolution/Laravel محميان | compose committed أو QR screen أو log. |
| كلمات مرور MySQL Evolution | `evolution/.env` أو خدمة أسرار | SQL template أو Compose أو وصف تذكرة. |
| `VITE_API_BASE_URL` و`VITE_BACKEND_ORIGIN` | build/runtime config عام | ليست أسرارًا، لكنها تتطلب قيمة البيئة الصحيحة. |

## 4. إجراءات النشر الآمن

ضع Evolution وقاعدة بياناته وRedis في شبكة خاصة. لا يجعل ربط المنفذ بـ`127.0.0.1` الخدمة آمنة وحده إن كان reverse proxy أو SSH tunneling أو إعداد الشبكة يعرّضها بلا ضوابط. استخدم HTTPS في كل نقطة علنية، وحدد origins المسموحة للواجهة، وأغلق منافذ قواعد البيانات وRedis عن الإنترنت العام.

| الإجراء | الأولوية | سبب التنفيذ |
|---|---|---|
| توليد أسرار فريدة وطويلة لكل بيئة وتدوير القيم المحتملة الانكشاف | حرجة | النسخة المستلمة تضمنت قيمًا نصية يجب عدم إعادة استخدامها. |
| ضبط URL webhook علني HTTPS وتثبيت secret | حرجة | وصول الرسائل لا يكفي دون تحقق صحيح من المصدر. |
| تشغيل queue workers وfailed-job monitoring | عالية | يمر مسار الرسائل عبر Jobs؛ غياب العامل يوقف وظيفة المنتج. |
| فرض TLS في Reverb/Proxy في الإنتاج ومراجعة `forceTLS` | عالية | ملف Echo الحالي مضبوط محليًا ولا ينبغي نسخه كما هو إلى الإنتاج. |
| ضبط CORS وSanctum وCSRF بناءً على نطاقات الإنتاج | عالية | يفصل الواجهة العامة ولوحة التاجر ويقلل إساءة استخدام الرموز والجلسات. |
| تقييد الوصول للشبكات الداخلية ونسخ قواعد البيانات احتياطيًا | عالية | MySQL وRedis وEvolution مكونات حساسة تشغيلًا وبيانات. |
| إضافة SAST وsecret scanning وdependency scanning إلى CI | متوسطة | يمنع إعادة إدخال مفاتيح أو dependencies ضعيفة مع التغييرات. |

## 5. نقاط مراجعة عند الاستعادة

لا يمكن تأكيد إعدادات الحماية غير الموجودة في الحزمة، مثل سياسة CORS، إعدادات Sanctum التفصيلية، CSRF، إعداد خادم الويب، نسخ PHP/Node، سياسة الكوكيز، ومحتوى migrations. يجب مراجعتها ضمن الجذر المستعاد قبل الإطلاق. كما يجب مراجعة compatibility لإصدار Evolution API؛ image `latest` لا تضمن ثباتًا إنتاجيًا، ويستحسن تثبيت tag/sha مجرّب بعد اختبار واجهات QR وwebhook.

| موضوع يحتاج قرارًا/فحصًا | سبب عدم حسمه هنا | الإجراء المطلوب |
|---|---|---|
| إصدارات الحزم | ملفات القفل غير مرفقة. | استرجاعها من المصدر الناجح وتحديثها وفق عملية مدروسة. |
| migrations وقيود DB | غير موجودة. | فحص فهارس uniqueness وforeign keys، خصوصًا `merchant_id` ومعرفات المنصات. |
| CORS/Sanctum | مجلد `config/` غير موجود. | تقييد origins وstateful domains، ثم اختبار API والـBlade. |
| تخزين الوسائط | تهيئة Media Library/disks غير مرفقة. | تحديد disk خاص، أذونات، CDN، سياسة حذف ونسخ احتياطي. |
| احتفاظ السجلات | config model activity غير مرفق. | ضبط مدة الاحتفاظ والخصوصية وحذف البيانات حسب السياسة القانونية. |

## 6. قائمة منع التسرب قبل كل Push

قبل اعتماد أي تغيير، افحص staged diff بحثًا عن `.env`، مفاتيح API، كلمات مرور، QR base64، ملفات session، سجلات HTTP، وتصديرات قواعد بيانات. تحقق أن تعديل Compose لا يعيد قيمة افتراضية قابلة للاستخدام، وأن كل token لا يظهر في responses أو Exceptions. لا تضع بيانات عميل أو رسائل حقيقية في fixtures أو اختبارات الواجهة.
