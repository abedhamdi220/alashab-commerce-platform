-- شغّل هذا القالب مرة واحدة فقط بحساب MySQL يملك صلاحية إنشاء قواعد ومستخدمين.
-- لا تستخدم قاعدة Laravel الحالية؛ Evolution يحتاج جداول Prisma مستقلة.
-- قبل التنفيذ، استبدل CHANGE_ME_WITH_THE_VALUE_OF_EVOLUTION_DB_PASSWORD
-- بالقيمة نفسها الموجودة في evolution/.env. لا ترفع النسخة المستبدلة إلى Git.

CREATE DATABASE IF NOT EXISTS evolution
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'evolution'@'%'
  IDENTIFIED BY 'CHANGE_ME_WITH_THE_VALUE_OF_EVOLUTION_DB_PASSWORD';

GRANT ALL PRIVILEGES ON evolution.* TO 'evolution'@'%';
FLUSH PRIVILEGES;
