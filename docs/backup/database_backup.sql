-- MySQL dump 10.13  Distrib 8.0.32, for Linux (x86_64)
--
-- Host: mysql    Database: laravel
-- ------------------------------------------------------
-- Server version	8.0.32

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('esperluettes-cache-0ade7c2cf97f75d009975f4d720d1fa6c19f4897','i:3;',1768129858),('esperluettes-cache-0ade7c2cf97f75d009975f4d720d1fa6c19f4897:timer','i:1768129858;',1768129858),('esperluettes-cache-1b6453892473a467d07372d45eb05abc2031647a','i:1;',1768129650),('esperluettes-cache-1b6453892473a467d07372d45eb05abc2031647a:timer','i:1768129650;',1768129650),('esperluettes-cache-app_version','N;',1768131689),('esperluettes-cache-auth:user_roles:2','a:3:{i:0;O:36:\"App\\Domains\\Auth\\Private\\Models\\Role\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:5:\"roles\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:6:{s:2:\"id\";i:1;s:4:\"name\";s:5:\"admin\";s:4:\"slug\";s:5:\"admin\";s:11:\"description\";s:18:\"Administrator role\";s:10:\"created_at\";s:19:\"2025-08-28 07:31:18\";s:10:\"updated_at\";s:19:\"2025-08-28 07:31:18\";}s:11:\"\0*\0original\";a:8:{s:2:\"id\";i:1;s:4:\"name\";s:5:\"admin\";s:4:\"slug\";s:5:\"admin\";s:11:\"description\";s:18:\"Administrator role\";s:10:\"created_at\";s:19:\"2025-08-28 07:31:18\";s:10:\"updated_at\";s:19:\"2025-08-28 07:31:18\";s:13:\"pivot_user_id\";i:2;s:13:\"pivot_role_id\";i:1;}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:0:{}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:1:{s:5:\"pivot\";O:44:\"Illuminate\\Database\\Eloquent\\Relations\\Pivot\":37:{s:13:\"\0*\0connection\";N;s:8:\"\0*\0table\";s:9:\"role_user\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:0;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:2:{s:7:\"user_id\";i:2;s:7:\"role_id\";i:1;}s:11:\"\0*\0original\";a:2:{s:7:\"user_id\";i:2;s:7:\"role_id\";i:1;}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:0:{}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:0;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:0:{}s:10:\"\0*\0guarded\";a:0:{}s:11:\"pivotParent\";O:36:\"App\\Domains\\Auth\\Private\\Models\\User\":35:{s:13:\"\0*\0connection\";N;s:8:\"\0*\0table\";s:5:\"users\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:1:{i:0;s:5:\"roles\";}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:0;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:0:{}s:11:\"\0*\0original\";a:0:{}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:6:{s:17:\"email_verified_at\";s:8:\"datetime\";s:8:\"password\";s:6:\"hashed\";s:9:\"is_active\";s:7:\"boolean\";s:17:\"terms_accepted_at\";s:8:\"datetime\";s:11:\"is_under_15\";s:7:\"boolean\";s:34:\"parental_authorization_verified_at\";s:8:\"datetime\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:2:{i:0;s:8:\"password\";i:1;s:14:\"remember_token\";}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:6:{i:0;s:5:\"email\";i:1;s:8:\"password\";i:2;s:9:\"is_active\";i:3;s:17:\"terms_accepted_at\";i:4;s:11:\"is_under_15\";i:5;s:34:\"parental_authorization_verified_at\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}s:19:\"\0*\0authPasswordName\";s:8:\"password\";s:20:\"\0*\0rememberTokenName\";s:14:\"remember_token\";}s:12:\"pivotRelated\";O:36:\"App\\Domains\\Auth\\Private\\Models\\Role\":33:{s:13:\"\0*\0connection\";N;s:8:\"\0*\0table\";N;s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:0;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:0:{}s:11:\"\0*\0original\";a:0:{}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:0:{}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:3:{i:0;s:4:\"name\";i:1;s:4:\"slug\";i:2;s:11:\"description\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}s:13:\"\0*\0foreignKey\";s:7:\"user_id\";s:13:\"\0*\0relatedKey\";s:7:\"role_id\";}}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:3:{i:0;s:4:\"name\";i:1;s:4:\"slug\";i:2;s:11:\"description\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:1;O:36:\"App\\Domains\\Auth\\Private\\Models\\Role\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:5:\"roles\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:6:{s:2:\"id\";i:2;s:4:\"name\";s:4:\"user\";s:4:\"slug\";s:4:\"user\";s:11:\"description\";s:21:\"Unconfirmed user role\";s:10:\"created_at\";s:19:\"2025-08-28 07:31:18\";s:10:\"updated_at\";s:19:\"2025-08-28 07:31:18\";}s:11:\"\0*\0original\";a:8:{s:2:\"id\";i:2;s:4:\"name\";s:4:\"user\";s:4:\"slug\";s:4:\"user\";s:11:\"description\";s:21:\"Unconfirmed user role\";s:10:\"created_at\";s:19:\"2025-08-28 07:31:18\";s:10:\"updated_at\";s:19:\"2025-08-28 07:31:18\";s:13:\"pivot_user_id\";i:2;s:13:\"pivot_role_id\";i:2;}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:0:{}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:1:{s:5:\"pivot\";O:44:\"Illuminate\\Database\\Eloquent\\Relations\\Pivot\":37:{s:13:\"\0*\0connection\";N;s:8:\"\0*\0table\";s:9:\"role_user\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:0;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:2:{s:7:\"user_id\";i:2;s:7:\"role_id\";i:2;}s:11:\"\0*\0original\";a:2:{s:7:\"user_id\";i:2;s:7:\"role_id\";i:2;}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:0:{}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:0;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:0:{}s:10:\"\0*\0guarded\";a:0:{}s:11:\"pivotParent\";r:79;s:12:\"pivotRelated\";r:131;s:13:\"\0*\0foreignKey\";s:7:\"user_id\";s:13:\"\0*\0relatedKey\";s:7:\"role_id\";}}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:3:{i:0;s:4:\"name\";i:1;s:4:\"slug\";i:2;s:11:\"description\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:2;O:36:\"App\\Domains\\Auth\\Private\\Models\\Role\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:5:\"roles\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:6:{s:2:\"id\";i:4;s:4:\"name\";s:10:\"Tech admin\";s:4:\"slug\";s:10:\"tech-admin\";s:11:\"description\";N;s:10:\"created_at\";s:19:\"2025-09-26 19:48:19\";s:10:\"updated_at\";s:19:\"2025-09-26 19:48:19\";}s:11:\"\0*\0original\";a:8:{s:2:\"id\";i:4;s:4:\"name\";s:10:\"Tech admin\";s:4:\"slug\";s:10:\"tech-admin\";s:11:\"description\";N;s:10:\"created_at\";s:19:\"2025-09-26 19:48:19\";s:10:\"updated_at\";s:19:\"2025-09-26 19:48:19\";s:13:\"pivot_user_id\";i:2;s:13:\"pivot_role_id\";i:4;}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:0:{}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:1:{s:5:\"pivot\";O:44:\"Illuminate\\Database\\Eloquent\\Relations\\Pivot\":37:{s:13:\"\0*\0connection\";N;s:8:\"\0*\0table\";s:9:\"role_user\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:0;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:2:{s:7:\"user_id\";i:2;s:7:\"role_id\";i:4;}s:11:\"\0*\0original\";a:2:{s:7:\"user_id\";i:2;s:7:\"role_id\";i:4;}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:0:{}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:0;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:0:{}s:10:\"\0*\0guarded\";a:0:{}s:11:\"pivotParent\";r:79;s:12:\"pivotRelated\";r:131;s:13:\"\0*\0foreignKey\";s:7:\"user_id\";s:13:\"\0*\0relatedKey\";s:7:\"role_id\";}}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:3:{i:0;s:4:\"name\";i:1;s:4:\"slug\";i:2;s:11:\"description\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}}',1768128232),('esperluettes-cache-auth:user_roles:4','a:1:{i:0;O:36:\"App\\Domains\\Auth\\Private\\Models\\Role\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:5:\"roles\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:6:{s:2:\"id\";i:2;s:4:\"name\";s:20:\"Graine d\'Esperluette\";s:4:\"slug\";s:4:\"user\";s:11:\"description\";s:77:\"Nous ont rejoint récemment, en train de prendre leurs racines dans le Jardin\";s:10:\"created_at\";s:19:\"2025-08-28 07:31:18\";s:10:\"updated_at\";s:19:\"2026-01-11 10:37:36\";}s:11:\"\0*\0original\";a:8:{s:2:\"id\";i:2;s:4:\"name\";s:20:\"Graine d\'Esperluette\";s:4:\"slug\";s:4:\"user\";s:11:\"description\";s:77:\"Nous ont rejoint récemment, en train de prendre leurs racines dans le Jardin\";s:10:\"created_at\";s:19:\"2025-08-28 07:31:18\";s:10:\"updated_at\";s:19:\"2026-01-11 10:37:36\";s:13:\"pivot_user_id\";i:4;s:13:\"pivot_role_id\";i:2;}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:0:{}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:1:{s:5:\"pivot\";O:44:\"Illuminate\\Database\\Eloquent\\Relations\\Pivot\":37:{s:13:\"\0*\0connection\";N;s:8:\"\0*\0table\";s:9:\"role_user\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:0;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:2:{s:7:\"user_id\";i:4;s:7:\"role_id\";i:2;}s:11:\"\0*\0original\";a:2:{s:7:\"user_id\";i:4;s:7:\"role_id\";i:2;}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:0:{}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:0;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:0:{}s:10:\"\0*\0guarded\";a:0:{}s:11:\"pivotParent\";O:36:\"App\\Domains\\Auth\\Private\\Models\\User\":35:{s:13:\"\0*\0connection\";N;s:8:\"\0*\0table\";s:5:\"users\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:1:{i:0;s:5:\"roles\";}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:0;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:0:{}s:11:\"\0*\0original\";a:0:{}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:6:{s:17:\"email_verified_at\";s:8:\"datetime\";s:8:\"password\";s:6:\"hashed\";s:9:\"is_active\";s:7:\"boolean\";s:17:\"terms_accepted_at\";s:8:\"datetime\";s:11:\"is_under_15\";s:7:\"boolean\";s:34:\"parental_authorization_verified_at\";s:8:\"datetime\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:2:{i:0;s:8:\"password\";i:1;s:14:\"remember_token\";}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:6:{i:0;s:5:\"email\";i:1;s:8:\"password\";i:2;s:9:\"is_active\";i:3;s:17:\"terms_accepted_at\";i:4;s:11:\"is_under_15\";i:5;s:34:\"parental_authorization_verified_at\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}s:19:\"\0*\0authPasswordName\";s:8:\"password\";s:20:\"\0*\0rememberTokenName\";s:14:\"remember_token\";}s:12:\"pivotRelated\";O:36:\"App\\Domains\\Auth\\Private\\Models\\Role\":33:{s:13:\"\0*\0connection\";N;s:8:\"\0*\0table\";N;s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:0;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:0:{}s:11:\"\0*\0original\";a:0:{}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:0:{}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:3:{i:0;s:4:\"name\";i:1;s:4:\"slug\";i:2;s:11:\"description\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}s:13:\"\0*\0foreignKey\";s:7:\"user_id\";s:13:\"\0*\0relatedKey\";s:7:\"role_id\";}}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:3:{i:0;s:4:\"name\";i:1;s:4:\"slug\";i:2;s:11:\"description\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}}',1768128599),('esperluettes-cache-config_parameters:values','a:1:{s:8:\"byDomain\";a:1:{s:4:\"auth\";a:1:{s:23:\"require_activation_code\";s:1:\"0\";}}}',1768131036),('esperluettes-cache-da4b9237bacccdf19c0760cab7aec4a8359010b0','i:2;',1768128032),('esperluettes-cache-da4b9237bacccdf19c0760cab7aec4a8359010b0:timer','i:1768128032;',1768128032),('esperluettes-cache-fe5dbbcea5ce7e2988b8c69bcfdfde8904aabc1f','i:1;',1768127736),('esperluettes-cache-fe5dbbcea5ce7e2988b8c69bcfdfde8904aabc1f:timer','i:1768127736;',1768127736),('esperluettes-cache-feature_toggles:all','a:2:{s:4:\"list\";a:2:{i:0;a:5:{s:6:\"domain\";s:8:\"calendar\";s:4:\"name\";s:7:\"enabled\";s:6:\"access\";s:2:\"on\";s:16:\"admin_visibility\";s:16:\"tech_admins_only\";s:5:\"roles\";a:0:{}}i:1;a:5:{s:6:\"domain\";s:10:\"moderation\";s:4:\"name\";s:9:\"reporting\";s:6:\"access\";s:2:\"on\";s:16:\"admin_visibility\";s:16:\"tech_admins_only\";s:5:\"roles\";a:0:{}}}s:8:\"byDomain\";a:2:{s:8:\"calendar\";a:1:{s:7:\"enabled\";a:5:{s:6:\"domain\";s:8:\"calendar\";s:4:\"name\";s:7:\"enabled\";s:6:\"access\";s:2:\"on\";s:16:\"admin_visibility\";s:16:\"tech_admins_only\";s:5:\"roles\";a:0:{}}}s:10:\"moderation\";a:1:{s:9:\"reporting\";a:5:{s:6:\"domain\";s:10:\"moderation\";s:4:\"name\";s:9:\"reporting\";s:6:\"access\";s:2:\"on\";s:16:\"admin_visibility\";s:16:\"tech_admins_only\";s:5:\"roles\";a:0:{}}}}}',1768129392),('esperluettes-cache-moderation.pending_reports_count','i:0;',2083485793),('esperluettes-cache-profile_by_user_id:2','O:42:\"App\\Domains\\Profile\\Private\\Models\\Profile\":35:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:16:\"profile_profiles\";s:13:\"\0*\0primaryKey\";s:7:\"user_id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:0;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:12:{s:7:\"user_id\";i:2;s:4:\"slug\";s:49:\"tech-admin-ou-plus-communement-le-grand-jardinier\";s:12:\"display_name\";s:50:\"Tech Admin ou plus communément le Grand Jardinier\";s:20:\"profile_picture_path\";s:33:\"profile_pictures/2_1756907799.jpg\";s:12:\"facebook_url\";s:23:\"https://facebook.com/lx\";s:5:\"x_url\";N;s:13:\"instagram_url\";N;s:11:\"youtube_url\";N;s:11:\"description\";N;s:10:\"created_at\";s:19:\"2025-08-28 07:33:07\";s:10:\"updated_at\";s:19:\"2026-01-11 10:04:04\";s:10:\"deleted_at\";N;}s:11:\"\0*\0original\";a:12:{s:7:\"user_id\";i:2;s:4:\"slug\";s:49:\"tech-admin-ou-plus-communement-le-grand-jardinier\";s:12:\"display_name\";s:50:\"Tech Admin ou plus communément le Grand Jardinier\";s:20:\"profile_picture_path\";s:33:\"profile_pictures/2_1756907799.jpg\";s:12:\"facebook_url\";s:23:\"https://facebook.com/lx\";s:5:\"x_url\";N;s:13:\"instagram_url\";N;s:11:\"youtube_url\";N;s:11:\"description\";N;s:10:\"created_at\";s:19:\"2025-08-28 07:33:07\";s:10:\"updated_at\";s:19:\"2026-01-11 10:04:04\";s:10:\"deleted_at\";N;}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:2:{s:7:\"user_id\";s:7:\"integer\";s:10:\"deleted_at\";s:8:\"datetime\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:9:{i:0;s:7:\"user_id\";i:1;s:4:\"slug\";i:2;s:12:\"display_name\";i:3;s:20:\"profile_picture_path\";i:4;s:12:\"facebook_url\";i:5;s:5:\"x_url\";i:6;s:13:\"instagram_url\";i:7;s:11:\"youtube_url\";i:8;s:11:\"description\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}s:5:\"roles\";a:0:{}s:16:\"\0*\0forceDeleting\";b:0;}',1768128232),('esperluettes-cache-profile_by_user_id:3','O:42:\"App\\Domains\\Profile\\Private\\Models\\Profile\":35:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:16:\"profile_profiles\";s:13:\"\0*\0primaryKey\";s:7:\"user_id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:0;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:12:{s:7:\"user_id\";i:3;s:4:\"slug\";s:5:\"alice\";s:12:\"display_name\";s:5:\"Alice\";s:20:\"profile_picture_path\";s:33:\"profile_pictures/3_1756908205.jpg\";s:12:\"facebook_url\";N;s:5:\"x_url\";N;s:13:\"instagram_url\";N;s:11:\"youtube_url\";N;s:11:\"description\";N;s:10:\"created_at\";s:19:\"2025-08-30 05:39:53\";s:10:\"updated_at\";s:19:\"2025-12-25 15:19:58\";s:10:\"deleted_at\";N;}s:11:\"\0*\0original\";a:12:{s:7:\"user_id\";i:3;s:4:\"slug\";s:5:\"alice\";s:12:\"display_name\";s:5:\"Alice\";s:20:\"profile_picture_path\";s:33:\"profile_pictures/3_1756908205.jpg\";s:12:\"facebook_url\";N;s:5:\"x_url\";N;s:13:\"instagram_url\";N;s:11:\"youtube_url\";N;s:11:\"description\";N;s:10:\"created_at\";s:19:\"2025-08-30 05:39:53\";s:10:\"updated_at\";s:19:\"2025-12-25 15:19:58\";s:10:\"deleted_at\";N;}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:2:{s:7:\"user_id\";s:7:\"integer\";s:10:\"deleted_at\";s:8:\"datetime\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:9:{i:0;s:7:\"user_id\";i:1;s:4:\"slug\";i:2;s:12:\"display_name\";i:3;s:20:\"profile_picture_path\";i:4;s:12:\"facebook_url\";i:5;s:5:\"x_url\";i:6;s:13:\"instagram_url\";i:7;s:11:\"youtube_url\";i:8;s:11:\"description\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}s:5:\"roles\";a:0:{}s:16:\"\0*\0forceDeleting\";b:0;}',1768128081),('esperluettes-cache-profile_by_user_id:4','O:42:\"App\\Domains\\Profile\\Private\\Models\\Profile\":35:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:16:\"profile_profiles\";s:13:\"\0*\0primaryKey\";s:7:\"user_id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:0;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:12:{s:7:\"user_id\";i:4;s:4:\"slug\";s:3:\"bob\";s:12:\"display_name\";s:3:\"Bob\";s:20:\"profile_picture_path\";s:22:\"profile_pictures/4.svg\";s:12:\"facebook_url\";N;s:5:\"x_url\";N;s:13:\"instagram_url\";N;s:11:\"youtube_url\";N;s:11:\"description\";N;s:10:\"created_at\";s:19:\"2025-09-08 19:37:08\";s:10:\"updated_at\";s:19:\"2025-09-08 19:37:08\";s:10:\"deleted_at\";N;}s:11:\"\0*\0original\";a:12:{s:7:\"user_id\";i:4;s:4:\"slug\";s:3:\"bob\";s:12:\"display_name\";s:3:\"Bob\";s:20:\"profile_picture_path\";s:22:\"profile_pictures/4.svg\";s:12:\"facebook_url\";N;s:5:\"x_url\";N;s:13:\"instagram_url\";N;s:11:\"youtube_url\";N;s:11:\"description\";N;s:10:\"created_at\";s:19:\"2025-09-08 19:37:08\";s:10:\"updated_at\";s:19:\"2025-09-08 19:37:08\";s:10:\"deleted_at\";N;}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:2:{s:7:\"user_id\";s:7:\"integer\";s:10:\"deleted_at\";s:8:\"datetime\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:9:{i:0;s:7:\"user_id\";i:1;s:4:\"slug\";i:2;s:12:\"display_name\";i:3;s:20:\"profile_picture_path\";i:4;s:12:\"facebook_url\";i:5;s:5:\"x_url\";i:6;s:13:\"instagram_url\";i:7;s:11:\"youtube_url\";i:8;s:11:\"description\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}s:5:\"roles\";a:0:{}s:16:\"\0*\0forceDeleting\";b:0;}',1768128105),('esperluettes-cache-profile_by_user_id:5','O:42:\"App\\Domains\\Profile\\Private\\Models\\Profile\":35:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:16:\"profile_profiles\";s:13:\"\0*\0primaryKey\";s:7:\"user_id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:0;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:12:{s:7:\"user_id\";i:5;s:4:\"slug\";s:5:\"carol\";s:12:\"display_name\";s:5:\"Carol\";s:20:\"profile_picture_path\";s:22:\"profile_pictures/5.svg\";s:12:\"facebook_url\";N;s:5:\"x_url\";N;s:13:\"instagram_url\";N;s:11:\"youtube_url\";N;s:11:\"description\";N;s:10:\"created_at\";s:19:\"2025-09-08 20:31:43\";s:10:\"updated_at\";s:19:\"2025-09-08 20:31:43\";s:10:\"deleted_at\";N;}s:11:\"\0*\0original\";a:12:{s:7:\"user_id\";i:5;s:4:\"slug\";s:5:\"carol\";s:12:\"display_name\";s:5:\"Carol\";s:20:\"profile_picture_path\";s:22:\"profile_pictures/5.svg\";s:12:\"facebook_url\";N;s:5:\"x_url\";N;s:13:\"instagram_url\";N;s:11:\"youtube_url\";N;s:11:\"description\";N;s:10:\"created_at\";s:19:\"2025-09-08 20:31:43\";s:10:\"updated_at\";s:19:\"2025-09-08 20:31:43\";s:10:\"deleted_at\";N;}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:2:{s:7:\"user_id\";s:7:\"integer\";s:10:\"deleted_at\";s:8:\"datetime\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:9:{i:0;s:7:\"user_id\";i:1;s:4:\"slug\";i:2;s:12:\"display_name\";i:3;s:20:\"profile_picture_path\";i:4;s:12:\"facebook_url\";i:5;s:5:\"x_url\";i:6;s:13:\"instagram_url\";i:7;s:11:\"youtube_url\";i:8;s:11:\"description\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}s:5:\"roles\";a:0:{}s:16:\"\0*\0forceDeleting\";b:0;}',1768128105),('esperluettes-cache-profile_by_user_id:6','O:42:\"App\\Domains\\Profile\\Private\\Models\\Profile\":35:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:16:\"profile_profiles\";s:13:\"\0*\0primaryKey\";s:7:\"user_id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:0;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:12:{s:7:\"user_id\";i:6;s:4:\"slug\";s:6:\"daniel\";s:12:\"display_name\";s:6:\"Daniel\";s:20:\"profile_picture_path\";N;s:12:\"facebook_url\";N;s:5:\"x_url\";N;s:13:\"instagram_url\";N;s:11:\"youtube_url\";N;s:11:\"description\";N;s:10:\"created_at\";s:19:\"2025-09-12 15:01:38\";s:10:\"updated_at\";s:19:\"2025-12-25 15:13:41\";s:10:\"deleted_at\";N;}s:11:\"\0*\0original\";a:12:{s:7:\"user_id\";i:6;s:4:\"slug\";s:6:\"daniel\";s:12:\"display_name\";s:6:\"Daniel\";s:20:\"profile_picture_path\";N;s:12:\"facebook_url\";N;s:5:\"x_url\";N;s:13:\"instagram_url\";N;s:11:\"youtube_url\";N;s:11:\"description\";N;s:10:\"created_at\";s:19:\"2025-09-12 15:01:38\";s:10:\"updated_at\";s:19:\"2025-12-25 15:13:41\";s:10:\"deleted_at\";N;}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:2:{s:7:\"user_id\";s:7:\"integer\";s:10:\"deleted_at\";s:8:\"datetime\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:9:{i:0;s:7:\"user_id\";i:1;s:4:\"slug\";i:2;s:12:\"display_name\";i:3;s:20:\"profile_picture_path\";i:4;s:12:\"facebook_url\";i:5;s:5:\"x_url\";i:6;s:13:\"instagram_url\";i:7;s:11:\"youtube_url\";i:8;s:11:\"description\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}s:5:\"roles\";a:0:{}s:16:\"\0*\0forceDeleting\";b:0;}',1768128685),('esperluettes-cache-profile_by_user_id:7','O:42:\"App\\Domains\\Profile\\Private\\Models\\Profile\":35:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:16:\"profile_profiles\";s:13:\"\0*\0primaryKey\";s:7:\"user_id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:0;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:12:{s:7:\"user_id\";i:7;s:4:\"slug\";s:5:\"test1\";s:12:\"display_name\";s:5:\"Test1\";s:20:\"profile_picture_path\";s:22:\"profile_pictures/7.svg\";s:12:\"facebook_url\";N;s:5:\"x_url\";N;s:13:\"instagram_url\";N;s:11:\"youtube_url\";N;s:11:\"description\";N;s:10:\"created_at\";s:19:\"2025-09-14 06:43:35\";s:10:\"updated_at\";s:19:\"2025-09-14 06:43:35\";s:10:\"deleted_at\";N;}s:11:\"\0*\0original\";a:12:{s:7:\"user_id\";i:7;s:4:\"slug\";s:5:\"test1\";s:12:\"display_name\";s:5:\"Test1\";s:20:\"profile_picture_path\";s:22:\"profile_pictures/7.svg\";s:12:\"facebook_url\";N;s:5:\"x_url\";N;s:13:\"instagram_url\";N;s:11:\"youtube_url\";N;s:11:\"description\";N;s:10:\"created_at\";s:19:\"2025-09-14 06:43:35\";s:10:\"updated_at\";s:19:\"2025-09-14 06:43:35\";s:10:\"deleted_at\";N;}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:2:{s:7:\"user_id\";s:7:\"integer\";s:10:\"deleted_at\";s:8:\"datetime\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:9:{i:0;s:7:\"user_id\";i:1;s:4:\"slug\";i:2;s:12:\"display_name\";i:3;s:20:\"profile_picture_path\";i:4;s:12:\"facebook_url\";i:5;s:5:\"x_url\";i:6;s:13:\"instagram_url\";i:7;s:11:\"youtube_url\";i:8;s:11:\"description\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}s:5:\"roles\";a:0:{}s:16:\"\0*\0forceDeleting\";b:0;}',1768128105),('esperluettes-cache-profile_by_user_id:8','O:42:\"App\\Domains\\Profile\\Private\\Models\\Profile\":35:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:16:\"profile_profiles\";s:13:\"\0*\0primaryKey\";s:7:\"user_id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:0;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:12:{s:7:\"user_id\";i:8;s:4:\"slug\";s:5:\"emily\";s:12:\"display_name\";s:6:\"Émily\";s:20:\"profile_picture_path\";s:22:\"profile_pictures/8.svg\";s:12:\"facebook_url\";N;s:5:\"x_url\";N;s:13:\"instagram_url\";N;s:11:\"youtube_url\";N;s:11:\"description\";N;s:10:\"created_at\";s:19:\"2026-01-11 10:30:56\";s:10:\"updated_at\";s:19:\"2026-01-11 10:30:56\";s:10:\"deleted_at\";N;}s:11:\"\0*\0original\";a:12:{s:7:\"user_id\";i:8;s:4:\"slug\";s:5:\"emily\";s:12:\"display_name\";s:6:\"Émily\";s:20:\"profile_picture_path\";s:22:\"profile_pictures/8.svg\";s:12:\"facebook_url\";N;s:5:\"x_url\";N;s:13:\"instagram_url\";N;s:11:\"youtube_url\";N;s:11:\"description\";N;s:10:\"created_at\";s:19:\"2026-01-11 10:30:56\";s:10:\"updated_at\";s:19:\"2026-01-11 10:30:56\";s:10:\"deleted_at\";N;}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:2:{s:7:\"user_id\";s:7:\"integer\";s:10:\"deleted_at\";s:8:\"datetime\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:9:{i:0;s:7:\"user_id\";i:1;s:4:\"slug\";i:2;s:12:\"display_name\";i:3;s:20:\"profile_picture_path\";i:4;s:12:\"facebook_url\";i:5;s:5:\"x_url\";i:6;s:13:\"instagram_url\";i:7;s:11:\"youtube_url\";i:8;s:11:\"description\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}s:5:\"roles\";a:0:{}s:16:\"\0*\0forceDeleting\";b:0;}',1768128081),('esperluettes-cache-profile_by_user_id:9','O:42:\"App\\Domains\\Profile\\Private\\Models\\Profile\":35:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:16:\"profile_profiles\";s:13:\"\0*\0primaryKey\";s:7:\"user_id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:0;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:12:{s:7:\"user_id\";i:9;s:4:\"slug\";s:4:\"gina\";s:12:\"display_name\";s:4:\"Gina\";s:20:\"profile_picture_path\";s:22:\"profile_pictures/9.svg\";s:12:\"facebook_url\";N;s:5:\"x_url\";N;s:13:\"instagram_url\";N;s:11:\"youtube_url\";N;s:11:\"description\";N;s:10:\"created_at\";s:19:\"2026-01-11 10:34:57\";s:10:\"updated_at\";s:19:\"2026-01-11 10:34:57\";s:10:\"deleted_at\";N;}s:11:\"\0*\0original\";a:12:{s:7:\"user_id\";i:9;s:4:\"slug\";s:4:\"gina\";s:12:\"display_name\";s:4:\"Gina\";s:20:\"profile_picture_path\";s:22:\"profile_pictures/9.svg\";s:12:\"facebook_url\";N;s:5:\"x_url\";N;s:13:\"instagram_url\";N;s:11:\"youtube_url\";N;s:11:\"description\";N;s:10:\"created_at\";s:19:\"2026-01-11 10:34:57\";s:10:\"updated_at\";s:19:\"2026-01-11 10:34:57\";s:10:\"deleted_at\";N;}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:2:{s:7:\"user_id\";s:7:\"integer\";s:10:\"deleted_at\";s:8:\"datetime\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:9:{i:0;s:7:\"user_id\";i:1;s:4:\"slug\";i:2;s:12:\"display_name\";i:3;s:20:\"profile_picture_path\";i:4;s:12:\"facebook_url\";i:5;s:5:\"x_url\";i:6;s:13:\"instagram_url\";i:7;s:11:\"youtube_url\";i:8;s:11:\"description\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}s:5:\"roles\";a:0:{}s:16:\"\0*\0forceDeleting\";b:0;}',1768128316),('esperluettes-cache-static_pages:slug_map','a:1:{s:15:\"qui-sommes-nous\";i:1;}',1768129393),('esperluettes-cache-storyref:audiences:public:list','O:39:\"Illuminate\\Database\\Eloquent\\Collection\":2:{s:8:\"\0*\0items\";a:2:{i:0;O:52:\"App\\Domains\\StoryRef\\Private\\Models\\StoryRefAudience\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:19:\"story_ref_audiences\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:9:{s:2:\"id\";i:1;s:4:\"name\";s:13:\"All audiences\";s:4:\"slug\";s:13:\"all-audiences\";s:5:\"order\";i:1;s:13:\"threshold_age\";N;s:18:\"is_mature_audience\";i:0;s:9:\"is_active\";i:1;s:10:\"created_at\";s:19:\"2025-08-28 07:31:18\";s:10:\"updated_at\";s:19:\"2025-08-28 07:31:18\";}s:11:\"\0*\0original\";a:9:{s:2:\"id\";i:1;s:4:\"name\";s:13:\"All audiences\";s:4:\"slug\";s:13:\"all-audiences\";s:5:\"order\";i:1;s:13:\"threshold_age\";N;s:18:\"is_mature_audience\";i:0;s:9:\"is_active\";i:1;s:10:\"created_at\";s:19:\"2025-08-28 07:31:18\";s:10:\"updated_at\";s:19:\"2025-08-28 07:31:18\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:4:{s:9:\"is_active\";s:7:\"boolean\";s:5:\"order\";s:7:\"integer\";s:13:\"threshold_age\";s:7:\"integer\";s:18:\"is_mature_audience\";s:7:\"boolean\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:6:{i:0;s:4:\"name\";i:1;s:4:\"slug\";i:2;s:5:\"order\";i:3;s:9:\"is_active\";i:4;s:13:\"threshold_age\";i:5;s:18:\"is_mature_audience\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:1;O:52:\"App\\Domains\\StoryRef\\Private\\Models\\StoryRefAudience\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:19:\"story_ref_audiences\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:9:{s:2:\"id\";i:2;s:4:\"name\";s:3:\"12+\";s:4:\"slug\";s:2:\"12\";s:5:\"order\";i:2;s:13:\"threshold_age\";N;s:18:\"is_mature_audience\";i:0;s:9:\"is_active\";i:1;s:10:\"created_at\";s:19:\"2025-09-23 19:27:42\";s:10:\"updated_at\";s:19:\"2025-09-23 19:27:42\";}s:11:\"\0*\0original\";a:9:{s:2:\"id\";i:2;s:4:\"name\";s:3:\"12+\";s:4:\"slug\";s:2:\"12\";s:5:\"order\";i:2;s:13:\"threshold_age\";N;s:18:\"is_mature_audience\";i:0;s:9:\"is_active\";i:1;s:10:\"created_at\";s:19:\"2025-09-23 19:27:42\";s:10:\"updated_at\";s:19:\"2025-09-23 19:27:42\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:4:{s:9:\"is_active\";s:7:\"boolean\";s:5:\"order\";s:7:\"integer\";s:13:\"threshold_age\";s:7:\"integer\";s:18:\"is_mature_audience\";s:7:\"boolean\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:6:{i:0;s:4:\"name\";i:1;s:4:\"slug\";i:2;s:5:\"order\";i:3;s:9:\"is_active\";i:4;s:13:\"threshold_age\";i:5;s:18:\"is_mature_audience\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}',1768214405),('esperluettes-cache-storyref:copyrights:public:list','O:39:\"Illuminate\\Database\\Eloquent\\Collection\":2:{s:8:\"\0*\0items\";a:2:{i:0;O:53:\"App\\Domains\\StoryRef\\Private\\Models\\StoryRefCopyright\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:20:\"story_ref_copyrights\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:8:{s:2:\"id\";i:1;s:4:\"name\";s:19:\"All rights reserved\";s:11:\"description\";s:41:\"THIS STORY IS MINE AND NOT YOURS, GET IT?\";s:4:\"slug\";s:19:\"all-rights-reserved\";s:5:\"order\";i:1;s:9:\"is_active\";i:1;s:10:\"created_at\";s:19:\"2025-08-28 07:31:18\";s:10:\"updated_at\";s:19:\"2025-09-27 19:32:54\";}s:11:\"\0*\0original\";a:8:{s:2:\"id\";i:1;s:4:\"name\";s:19:\"All rights reserved\";s:11:\"description\";s:41:\"THIS STORY IS MINE AND NOT YOURS, GET IT?\";s:4:\"slug\";s:19:\"all-rights-reserved\";s:5:\"order\";i:1;s:9:\"is_active\";i:1;s:10:\"created_at\";s:19:\"2025-08-28 07:31:18\";s:10:\"updated_at\";s:19:\"2025-09-27 19:32:54\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:2:{s:9:\"is_active\";s:7:\"boolean\";s:5:\"order\";s:7:\"integer\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:5:{i:0;s:4:\"name\";i:1;s:4:\"slug\";i:2;s:11:\"description\";i:3;s:5:\"order\";i:4;s:9:\"is_active\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:1;O:53:\"App\\Domains\\StoryRef\\Private\\Models\\StoryRefCopyright\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:20:\"story_ref_copyrights\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:8:{s:2:\"id\";i:2;s:4:\"name\";s:7:\"Limited\";s:11:\"description\";N;s:4:\"slug\";s:7:\"limited\";s:5:\"order\";i:2;s:9:\"is_active\";i:1;s:10:\"created_at\";s:19:\"2025-10-02 11:30:03\";s:10:\"updated_at\";s:19:\"2025-10-02 12:07:55\";}s:11:\"\0*\0original\";a:8:{s:2:\"id\";i:2;s:4:\"name\";s:7:\"Limited\";s:11:\"description\";N;s:4:\"slug\";s:7:\"limited\";s:5:\"order\";i:2;s:9:\"is_active\";i:1;s:10:\"created_at\";s:19:\"2025-10-02 11:30:03\";s:10:\"updated_at\";s:19:\"2025-10-02 12:07:55\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:2:{s:9:\"is_active\";s:7:\"boolean\";s:5:\"order\";s:7:\"integer\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:5:{i:0;s:4:\"name\";i:1;s:4:\"slug\";i:2;s:11:\"description\";i:3;s:5:\"order\";i:4;s:9:\"is_active\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}',1768214405),('esperluettes-cache-storyref:feedbacks:public:list','O:39:\"Illuminate\\Database\\Eloquent\\Collection\":2:{s:8:\"\0*\0items\";a:5:{i:0;O:52:\"App\\Domains\\StoryRef\\Private\\Models\\StoryRefFeedback\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:19:\"story_ref_feedbacks\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:8:{s:2:\"id\";i:1;s:4:\"name\";s:13:\"Gentle please\";s:4:\"slug\";s:13:\"gentle-please\";s:11:\"description\";N;s:5:\"order\";i:1;s:9:\"is_active\";i:1;s:10:\"created_at\";s:19:\"2025-08-28 07:31:18\";s:10:\"updated_at\";s:19:\"2025-08-28 07:31:18\";}s:11:\"\0*\0original\";a:8:{s:2:\"id\";i:1;s:4:\"name\";s:13:\"Gentle please\";s:4:\"slug\";s:13:\"gentle-please\";s:11:\"description\";N;s:5:\"order\";i:1;s:9:\"is_active\";i:1;s:10:\"created_at\";s:19:\"2025-08-28 07:31:18\";s:10:\"updated_at\";s:19:\"2025-08-28 07:31:18\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:2:{s:9:\"is_active\";s:7:\"boolean\";s:5:\"order\";s:7:\"integer\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:5:{i:0;s:4:\"name\";i:1;s:4:\"slug\";i:2;s:11:\"description\";i:3;s:5:\"order\";i:4;s:9:\"is_active\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:1;O:52:\"App\\Domains\\StoryRef\\Private\\Models\\StoryRefFeedback\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:19:\"story_ref_feedbacks\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:8:{s:2:\"id\";i:2;s:4:\"name\";s:11:\"Hit me hard\";s:4:\"slug\";s:11:\"hit-me-hard\";s:11:\"description\";N;s:5:\"order\";i:2;s:9:\"is_active\";i:1;s:10:\"created_at\";s:19:\"2025-10-05 19:05:36\";s:10:\"updated_at\";s:19:\"2025-10-05 19:05:36\";}s:11:\"\0*\0original\";a:8:{s:2:\"id\";i:2;s:4:\"name\";s:11:\"Hit me hard\";s:4:\"slug\";s:11:\"hit-me-hard\";s:11:\"description\";N;s:5:\"order\";i:2;s:9:\"is_active\";i:1;s:10:\"created_at\";s:19:\"2025-10-05 19:05:36\";s:10:\"updated_at\";s:19:\"2025-10-05 19:05:36\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:2:{s:9:\"is_active\";s:7:\"boolean\";s:5:\"order\";s:7:\"integer\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:5:{i:0;s:4:\"name\";i:1;s:4:\"slug\";i:2;s:11:\"description\";i:3;s:5:\"order\";i:4;s:9:\"is_active\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:2;O:52:\"App\\Domains\\StoryRef\\Private\\Models\\StoryRefFeedback\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:19:\"story_ref_feedbacks\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:8:{s:2:\"id\";i:4;s:4:\"name\";s:20:\"Only congratulations\";s:4:\"slug\";s:20:\"only-congratulations\";s:11:\"description\";N;s:5:\"order\";i:4;s:9:\"is_active\";i:1;s:10:\"created_at\";s:19:\"2025-10-05 19:07:55\";s:10:\"updated_at\";s:19:\"2025-10-05 19:07:55\";}s:11:\"\0*\0original\";a:8:{s:2:\"id\";i:4;s:4:\"name\";s:20:\"Only congratulations\";s:4:\"slug\";s:20:\"only-congratulations\";s:11:\"description\";N;s:5:\"order\";i:4;s:9:\"is_active\";i:1;s:10:\"created_at\";s:19:\"2025-10-05 19:07:55\";s:10:\"updated_at\";s:19:\"2025-10-05 19:07:55\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:2:{s:9:\"is_active\";s:7:\"boolean\";s:5:\"order\";s:7:\"integer\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:5:{i:0;s:4:\"name\";i:1;s:4:\"slug\";i:2;s:11:\"description\";i:3;s:5:\"order\";i:4;s:9:\"is_active\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:3;O:52:\"App\\Domains\\StoryRef\\Private\\Models\\StoryRefFeedback\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:19:\"story_ref_feedbacks\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:8:{s:2:\"id\";i:5;s:4:\"name\";s:29:\"Only grateful congratulations\";s:4:\"slug\";s:29:\"only-grateful-congratulations\";s:11:\"description\";N;s:5:\"order\";i:5;s:9:\"is_active\";i:1;s:10:\"created_at\";s:19:\"2025-10-05 19:08:03\";s:10:\"updated_at\";s:19:\"2025-10-05 19:08:03\";}s:11:\"\0*\0original\";a:8:{s:2:\"id\";i:5;s:4:\"name\";s:29:\"Only grateful congratulations\";s:4:\"slug\";s:29:\"only-grateful-congratulations\";s:11:\"description\";N;s:5:\"order\";i:5;s:9:\"is_active\";i:1;s:10:\"created_at\";s:19:\"2025-10-05 19:08:03\";s:10:\"updated_at\";s:19:\"2025-10-05 19:08:03\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:2:{s:9:\"is_active\";s:7:\"boolean\";s:5:\"order\";s:7:\"integer\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:5:{i:0;s:4:\"name\";i:1;s:4:\"slug\";i:2;s:11:\"description\";i:3;s:5:\"order\";i:4;s:9:\"is_active\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:4;O:52:\"App\\Domains\\StoryRef\\Private\\Models\\StoryRefFeedback\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:19:\"story_ref_feedbacks\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:8:{s:2:\"id\";i:6;s:4:\"name\";s:20:\"Don\'t. Talk. To. Me.\";s:4:\"slug\";s:15:\"dont-talk-to-me\";s:11:\"description\";N;s:5:\"order\";i:6;s:9:\"is_active\";i:1;s:10:\"created_at\";s:19:\"2025-10-05 19:08:11\";s:10:\"updated_at\";s:19:\"2025-10-05 19:08:11\";}s:11:\"\0*\0original\";a:8:{s:2:\"id\";i:6;s:4:\"name\";s:20:\"Don\'t. Talk. To. Me.\";s:4:\"slug\";s:15:\"dont-talk-to-me\";s:11:\"description\";N;s:5:\"order\";i:6;s:9:\"is_active\";i:1;s:10:\"created_at\";s:19:\"2025-10-05 19:08:11\";s:10:\"updated_at\";s:19:\"2025-10-05 19:08:11\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:2:{s:9:\"is_active\";s:7:\"boolean\";s:5:\"order\";s:7:\"integer\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:5:{i:0;s:4:\"name\";i:1;s:4:\"slug\";i:2;s:11:\"description\";i:3;s:5:\"order\";i:4;s:9:\"is_active\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}',1768214405),('esperluettes-cache-storyref:genres:public:list','O:39:\"Illuminate\\Database\\Eloquent\\Collection\":2:{s:8:\"\0*\0items\";a:3:{i:0;O:49:\"App\\Domains\\StoryRef\\Private\\Models\\StoryRefGenre\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:16:\"story_ref_genres\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:8:{s:2:\"id\";i:1;s:4:\"name\";s:7:\"Fantasy\";s:4:\"slug\";s:7:\"fantasy\";s:5:\"order\";i:1;s:11:\"description\";s:36:\"Imaginary worlds filled with dragons\";s:9:\"is_active\";i:1;s:10:\"created_at\";s:19:\"2025-08-28 07:31:18\";s:10:\"updated_at\";s:19:\"2025-08-28 07:31:18\";}s:11:\"\0*\0original\";a:8:{s:2:\"id\";i:1;s:4:\"name\";s:7:\"Fantasy\";s:4:\"slug\";s:7:\"fantasy\";s:5:\"order\";i:1;s:11:\"description\";s:36:\"Imaginary worlds filled with dragons\";s:9:\"is_active\";i:1;s:10:\"created_at\";s:19:\"2025-08-28 07:31:18\";s:10:\"updated_at\";s:19:\"2025-08-28 07:31:18\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:2:{s:9:\"is_active\";s:7:\"boolean\";s:5:\"order\";s:7:\"integer\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:5:{i:0;s:4:\"name\";i:1;s:4:\"slug\";i:2;s:11:\"description\";i:3;s:5:\"order\";i:4;s:9:\"is_active\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:1;O:49:\"App\\Domains\\StoryRef\\Private\\Models\\StoryRefGenre\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:16:\"story_ref_genres\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:8:{s:2:\"id\";i:2;s:4:\"name\";s:26:\"Épistolaire vraiment long\";s:4:\"slug\";s:25:\"epistolaire-vraiment-long\";s:5:\"order\";i:2;s:11:\"description\";N;s:9:\"is_active\";i:1;s:10:\"created_at\";s:19:\"2025-09-14 19:02:08\";s:10:\"updated_at\";s:19:\"2025-09-14 19:02:08\";}s:11:\"\0*\0original\";a:8:{s:2:\"id\";i:2;s:4:\"name\";s:26:\"Épistolaire vraiment long\";s:4:\"slug\";s:25:\"epistolaire-vraiment-long\";s:5:\"order\";i:2;s:11:\"description\";N;s:9:\"is_active\";i:1;s:10:\"created_at\";s:19:\"2025-09-14 19:02:08\";s:10:\"updated_at\";s:19:\"2025-09-14 19:02:08\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:2:{s:9:\"is_active\";s:7:\"boolean\";s:5:\"order\";s:7:\"integer\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:5:{i:0;s:4:\"name\";i:1;s:4:\"slug\";i:2;s:11:\"description\";i:3;s:5:\"order\";i:4;s:9:\"is_active\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:2;O:49:\"App\\Domains\\StoryRef\\Private\\Models\\StoryRefGenre\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:16:\"story_ref_genres\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:8:{s:2:\"id\";i:3;s:4:\"name\";s:14:\"Autobiographie\";s:4:\"slug\";s:14:\"autobiographie\";s:5:\"order\";i:3;s:11:\"description\";N;s:9:\"is_active\";i:1;s:10:\"created_at\";s:19:\"2025-09-14 19:02:22\";s:10:\"updated_at\";s:19:\"2025-09-14 19:02:22\";}s:11:\"\0*\0original\";a:8:{s:2:\"id\";i:3;s:4:\"name\";s:14:\"Autobiographie\";s:4:\"slug\";s:14:\"autobiographie\";s:5:\"order\";i:3;s:11:\"description\";N;s:9:\"is_active\";i:1;s:10:\"created_at\";s:19:\"2025-09-14 19:02:22\";s:10:\"updated_at\";s:19:\"2025-09-14 19:02:22\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:2:{s:9:\"is_active\";s:7:\"boolean\";s:5:\"order\";s:7:\"integer\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:5:{i:0;s:4:\"name\";i:1;s:4:\"slug\";i:2;s:11:\"description\";i:3;s:5:\"order\";i:4;s:9:\"is_active\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}',1768212192),('esperluettes-cache-storyref:statuses:public:list','O:39:\"Illuminate\\Database\\Eloquent\\Collection\":2:{s:8:\"\0*\0items\";a:1:{i:0;O:50:\"App\\Domains\\StoryRef\\Private\\Models\\StoryRefStatus\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:18:\"story_ref_statuses\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:8:{s:2:\"id\";i:1;s:4:\"name\";s:35:\"First draft but quite long actually\";s:11:\"description\";s:29:\"Now I need to write some more\";s:4:\"slug\";s:11:\"first-draft\";s:5:\"order\";i:1;s:9:\"is_active\";i:1;s:10:\"created_at\";s:19:\"2025-08-28 07:31:18\";s:10:\"updated_at\";s:19:\"2025-10-02 11:38:40\";}s:11:\"\0*\0original\";a:8:{s:2:\"id\";i:1;s:4:\"name\";s:35:\"First draft but quite long actually\";s:11:\"description\";s:29:\"Now I need to write some more\";s:4:\"slug\";s:11:\"first-draft\";s:5:\"order\";i:1;s:9:\"is_active\";i:1;s:10:\"created_at\";s:19:\"2025-08-28 07:31:18\";s:10:\"updated_at\";s:19:\"2025-10-02 11:38:40\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:2:{s:9:\"is_active\";s:7:\"boolean\";s:5:\"order\";s:7:\"integer\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:5:{i:0;s:4:\"name\";i:1;s:4:\"slug\";i:2;s:11:\"description\";i:3;s:5:\"order\";i:4;s:9:\"is_active\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}',1768214405),('esperluettes-cache-storyref:trigger_warnings:public:list','O:39:\"Illuminate\\Database\\Eloquent\\Collection\":2:{s:8:\"\0*\0items\";a:2:{i:0;O:58:\"App\\Domains\\StoryRef\\Private\\Models\\StoryRefTriggerWarning\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:26:\"story_ref_trigger_warnings\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:8:{s:2:\"id\";i:1;s:4:\"name\";s:17:\"Physical Violence\";s:4:\"slug\";s:17:\"physical-violence\";s:11:\"description\";s:54:\"People are getting hurt, be it with punches or weapons\";s:5:\"order\";i:1;s:9:\"is_active\";i:1;s:10:\"created_at\";s:19:\"2025-08-28 07:31:18\";s:10:\"updated_at\";s:19:\"2025-08-28 07:31:18\";}s:11:\"\0*\0original\";a:8:{s:2:\"id\";i:1;s:4:\"name\";s:17:\"Physical Violence\";s:4:\"slug\";s:17:\"physical-violence\";s:11:\"description\";s:54:\"People are getting hurt, be it with punches or weapons\";s:5:\"order\";i:1;s:9:\"is_active\";i:1;s:10:\"created_at\";s:19:\"2025-08-28 07:31:18\";s:10:\"updated_at\";s:19:\"2025-08-28 07:31:18\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:2:{s:9:\"is_active\";s:7:\"boolean\";s:5:\"order\";s:7:\"integer\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:5:{i:0;s:4:\"name\";i:1;s:4:\"slug\";i:2;s:11:\"description\";i:3;s:5:\"order\";i:4;s:9:\"is_active\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:1;O:58:\"App\\Domains\\StoryRef\\Private\\Models\\StoryRefTriggerWarning\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:26:\"story_ref_trigger_warnings\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:8:{s:2:\"id\";i:2;s:4:\"name\";s:10:\"Milkshakes\";s:4:\"slug\";s:10:\"milkshakes\";s:11:\"description\";s:82:\"There are milkshakes in this story. That\'s disgusting! (unless it\'s a vanilla one)\";s:5:\"order\";i:2;s:9:\"is_active\";i:1;s:10:\"created_at\";s:19:\"2025-09-12 10:22:41\";s:10:\"updated_at\";s:19:\"2025-09-12 10:22:41\";}s:11:\"\0*\0original\";a:8:{s:2:\"id\";i:2;s:4:\"name\";s:10:\"Milkshakes\";s:4:\"slug\";s:10:\"milkshakes\";s:11:\"description\";s:82:\"There are milkshakes in this story. That\'s disgusting! (unless it\'s a vanilla one)\";s:5:\"order\";i:2;s:9:\"is_active\";i:1;s:10:\"created_at\";s:19:\"2025-09-12 10:22:41\";s:10:\"updated_at\";s:19:\"2025-09-12 10:22:41\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:2:{s:9:\"is_active\";s:7:\"boolean\";s:5:\"order\";s:7:\"integer\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:5:{i:0;s:4:\"name\";i:1;s:4:\"slug\";i:2;s:11:\"description\";i:3;s:5:\"order\";i:4;s:9:\"is_active\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}',1768212193),('esperluettes-cache-storyref:types:public:list','O:39:\"Illuminate\\Database\\Eloquent\\Collection\":2:{s:8:\"\0*\0items\";a:1:{i:0;O:48:\"App\\Domains\\StoryRef\\Private\\Models\\StoryRefType\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:15:\"story_ref_types\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:7:{s:2:\"id\";i:1;s:4:\"name\";s:5:\"Novel\";s:4:\"slug\";s:5:\"novel\";s:5:\"order\";i:1;s:9:\"is_active\";i:1;s:10:\"created_at\";s:19:\"2025-08-28 07:31:18\";s:10:\"updated_at\";s:19:\"2025-08-28 07:31:18\";}s:11:\"\0*\0original\";a:7:{s:2:\"id\";i:1;s:4:\"name\";s:5:\"Novel\";s:4:\"slug\";s:5:\"novel\";s:5:\"order\";i:1;s:9:\"is_active\";i:1;s:10:\"created_at\";s:19:\"2025-08-28 07:31:18\";s:10:\"updated_at\";s:19:\"2025-08-28 07:31:18\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:2:{s:9:\"is_active\";s:7:\"boolean\";s:5:\"order\";s:7:\"integer\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:4:{i:0;s:4:\"name\";i:1;s:4:\"slug\";i:2;s:5:\"order\";i:3;s:9:\"is_active\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}',1768214405),('esperluettes-cache-user_settings:2','a:2:{s:12:\"general.font\";s:5:\"times\";s:13:\"general.theme\";s:6:\"autumn\";}',2083485792),('esperluettes-cache-user_settings:4','a:0:{}',2083487994),('esperluettes-cache-user_settings:8','a:0:{}',2083487456),('esperluettes-cache-user_settings:9','a:0:{}',2083487697);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `calendar_activities`
--

DROP TABLE IF EXISTS `calendar_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendar_activities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activity_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_restrictions` json NOT NULL,
  `requires_subscription` tinyint(1) NOT NULL DEFAULT '0',
  `max_participants` int DEFAULT NULL,
  `preview_starts_at` timestamp NULL DEFAULT NULL,
  `active_starts_at` timestamp NULL DEFAULT NULL,
  `active_ends_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `created_by_user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `calendar_activities_slug_unique` (`slug`),
  KEY `ca_type_active_idx` (`activity_type`,`active_starts_at`,`active_ends_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calendar_activities`
--

LOCK TABLES `calendar_activities` WRITE;
/*!40000 ALTER TABLE `calendar_activities` DISABLE KEYS */;
INSERT INTO `calendar_activities` VALUES (1,'JardiNo 2024','jardino-2024-1','<p>Le JardiNo 2024 est là !</p>\n','activities/01K89655ERC600HERP9ESK1Q5Q.jpg','jardino','[\"user-confirmed\"]',0,NULL,'2025-10-15 00:00:00','2025-10-24 00:00:00','2025-10-30 00:00:00','2025-12-31 00:00:00',2,'2025-10-23 18:44:02','2025-10-23 18:44:02'),(2,'Secret Santa 2025','secret-santa-2025-2','<p>Partagez un texte, une image ou un son avec quelqu\'un.</p>\n',NULL,'secret-gift','[\"user-confirmed\"]',0,NULL,'2025-12-22 00:00:00','2025-12-22 00:00:00','2025-12-29 00:00:00','2025-12-31 00:00:00',2,'2025-12-23 11:27:56','2025-12-28 15:13:16');
/*!40000 ALTER TABLE `calendar_activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `calendar_jardino_garden_cells`
--

DROP TABLE IF EXISTS `calendar_jardino_garden_cells`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendar_jardino_garden_cells` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `activity_id` bigint unsigned NOT NULL,
  `x` smallint unsigned NOT NULL,
  `y` smallint unsigned NOT NULL,
  `type` enum('flower','blocked') COLLATE utf8mb4_unicode_ci NOT NULL,
  `flower_image` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `planted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_cell_per_activity` (`activity_id`,`x`,`y`),
  KEY `calendar_jardino_garden_cells_activity_id_index` (`activity_id`),
  KEY `calendar_jardino_garden_cells_activity_id_user_id_index` (`activity_id`,`user_id`),
  CONSTRAINT `calendar_jardino_garden_cells_activity_id_foreign` FOREIGN KEY (`activity_id`) REFERENCES `calendar_activities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calendar_jardino_garden_cells`
--

LOCK TABLES `calendar_jardino_garden_cells` WRITE;
/*!40000 ALTER TABLE `calendar_jardino_garden_cells` DISABLE KEYS */;
INSERT INTO `calendar_jardino_garden_cells` VALUES (1,1,8,3,'flower','01.png',2,'2025-10-27 12:36:37','2025-10-27 12:36:37','2025-10-27 12:36:37'),(3,1,14,2,'flower','14.png',2,'2025-10-28 12:15:20','2025-10-28 12:15:20','2025-10-28 12:15:20'),(4,1,13,3,'flower','16.png',2,'2025-10-28 12:15:25','2025-10-28 12:15:25','2025-10-28 12:15:25'),(8,1,22,2,'blocked',NULL,NULL,NULL,'2025-10-28 12:25:25','2025-10-28 12:25:25'),(9,1,15,3,'blocked',NULL,NULL,NULL,'2025-10-28 12:34:27','2025-10-28 12:34:27'),(10,1,18,4,'blocked',NULL,NULL,NULL,'2025-10-28 12:34:32','2025-10-28 12:34:32'),(13,1,10,44,'flower','14.png',2,'2025-10-28 12:46:59','2025-10-28 12:46:59','2025-10-28 12:46:59');
/*!40000 ALTER TABLE `calendar_jardino_garden_cells` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `calendar_jardino_goals`
--

DROP TABLE IF EXISTS `calendar_jardino_goals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendar_jardino_goals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `activity_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `story_id` bigint unsigned NOT NULL,
  `target_word_count` int unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_per_activity` (`activity_id`,`user_id`),
  KEY `calendar_jardino_goals_activity_id_index` (`activity_id`),
  CONSTRAINT `calendar_jardino_goals_activity_id_foreign` FOREIGN KEY (`activity_id`) REFERENCES `calendar_activities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calendar_jardino_goals`
--

LOCK TABLES `calendar_jardino_goals` WRITE;
/*!40000 ALTER TABLE `calendar_jardino_goals` DISABLE KEYS */;
INSERT INTO `calendar_jardino_goals` VALUES (2,1,2,1,10000,'2025-10-24 19:48:24','2025-10-24 19:48:24');
/*!40000 ALTER TABLE `calendar_jardino_goals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `calendar_jardino_story_snapshots`
--

DROP TABLE IF EXISTS `calendar_jardino_story_snapshots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendar_jardino_story_snapshots` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `goal_id` bigint unsigned NOT NULL,
  `story_id` bigint unsigned NOT NULL,
  `story_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `initial_word_count` int unsigned NOT NULL,
  `current_word_count` int unsigned NOT NULL,
  `biggest_word_count` int unsigned NOT NULL,
  `selected_at` timestamp NOT NULL,
  `deselected_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `calendar_jardino_story_snapshots_goal_id_index` (`goal_id`),
  KEY `calendar_jardino_story_snapshots_story_id_index` (`story_id`),
  CONSTRAINT `calendar_jardino_story_snapshots_goal_id_foreign` FOREIGN KEY (`goal_id`) REFERENCES `calendar_jardino_goals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calendar_jardino_story_snapshots`
--

LOCK TABLES `calendar_jardino_story_snapshots` WRITE;
/*!40000 ALTER TABLE `calendar_jardino_story_snapshots` DISABLE KEYS */;
INSERT INTO `calendar_jardino_story_snapshots` VALUES (1,2,1,'Le Crépuscule des Âs',1919,4864,4864,'2025-10-24 19:48:24',NULL,'2025-10-24 19:48:24','2025-10-24 19:54:21');
/*!40000 ALTER TABLE `calendar_jardino_story_snapshots` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `calendar_secret_gift_assignments`
--

DROP TABLE IF EXISTS `calendar_secret_gift_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendar_secret_gift_assignments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `activity_id` bigint unsigned NOT NULL,
  `giver_user_id` bigint unsigned NOT NULL,
  `recipient_user_id` bigint unsigned NOT NULL,
  `gift_text` text COLLATE utf8mb4_unicode_ci,
  `gift_image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gift_sound_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sg_assignments_giver_unique` (`activity_id`,`giver_user_id`),
  UNIQUE KEY `sg_assignments_recipient_unique` (`activity_id`,`recipient_user_id`),
  KEY `calendar_secret_gift_assignments_giver_user_id_index` (`giver_user_id`),
  KEY `calendar_secret_gift_assignments_recipient_user_id_index` (`recipient_user_id`),
  CONSTRAINT `calendar_secret_gift_assignments_activity_id_foreign` FOREIGN KEY (`activity_id`) REFERENCES `calendar_activities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calendar_secret_gift_assignments`
--

LOCK TABLES `calendar_secret_gift_assignments` WRITE;
/*!40000 ALTER TABLE `calendar_secret_gift_assignments` DISABLE KEYS */;
INSERT INTO `calendar_secret_gift_assignments` VALUES (1,2,6,2,'<p>Avec un petit texte aussi</p>','calendar/secret-gift/2/6.jpg',NULL,'2025-12-23 19:03:08','2025-12-23 19:06:59'),(2,2,2,6,'<p>Avec un petit texte</p>','calendar/secret-gift/2/2.jpg','calendar/secret-gift/2/sound-2-1766517399.mp3','2025-12-23 19:03:08','2025-12-28 15:29:26');
/*!40000 ALTER TABLE `calendar_secret_gift_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `calendar_secret_gift_participants`
--

DROP TABLE IF EXISTS `calendar_secret_gift_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendar_secret_gift_participants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `activity_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `preferences` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sg_participants_unique` (`activity_id`,`user_id`),
  KEY `calendar_secret_gift_participants_user_id_index` (`user_id`),
  CONSTRAINT `calendar_secret_gift_participants_activity_id_foreign` FOREIGN KEY (`activity_id`) REFERENCES `calendar_activities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calendar_secret_gift_participants`
--

LOCK TABLES `calendar_secret_gift_participants` WRITE;
/*!40000 ALTER TABLE `calendar_secret_gift_participants` DISABLE KEYS */;
INSERT INTO `calendar_secret_gift_participants` VALUES (1,2,2,'Test',NULL,NULL),(2,2,6,'Test 2',NULL,NULL);
/*!40000 ALTER TABLE `calendar_secret_gift_participants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comments`
--

DROP TABLE IF EXISTS `comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `commentable_type` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `commentable_id` bigint unsigned NOT NULL,
  `author_id` bigint unsigned DEFAULT NULL,
  `parent_comment_id` bigint unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `body` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `edited_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `comments_commentable_type_commentable_id_created_at_index` (`commentable_type`,`commentable_id`,`created_at`),
  KEY `comments_commentable_type_index` (`commentable_type`),
  KEY `comments_commentable_id_index` (`commentable_id`),
  KEY `comments_author_id_index` (`author_id`),
  KEY `comments_parent_comment_id_index` (`parent_comment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comments`
--

LOCK TABLES `comments` WRITE;
/*!40000 ALTER TABLE `comments` DISABLE KEYS */;
INSERT INTO `comments` VALUES (1,'chapter',1,2,NULL,1,'<p>You mean I can post ?</p>',NULL,'2025-09-02 20:14:05','2025-09-02 20:14:05',NULL),(2,'chapter',1,2,NULL,1,'<p>Oh, I did not think about trying</p>',NULL,'2025-09-02 20:14:20','2025-09-02 20:14:20',NULL),(3,'chapter',1,2,NULL,1,'<p>This is really cool</p>',NULL,'2025-09-02 20:14:29','2025-09-02 20:14:29',NULL),(4,'chapter',1,2,NULL,1,'<p>Let\'s add a few more</p>',NULL,'2025-09-02 20:14:38','2025-09-02 20:14:38',NULL),(5,'chapter',1,2,NULL,1,'<p>And one more</p>',NULL,'2025-09-02 20:14:49','2025-09-02 20:14:49',NULL),(6,'chapter',1,2,NULL,1,'<p>Post?</p>',NULL,'2025-09-02 20:19:14','2025-09-02 20:19:14',NULL),(7,'chapter',1,2,NULL,1,'<p>More</p>',NULL,'2025-09-03 07:30:52','2025-09-03 07:30:52',NULL),(8,'chapter',1,2,NULL,1,'<p>MOAR</p>',NULL,'2025-09-03 07:31:23','2025-09-03 07:31:23',NULL),(9,'chapter',1,2,NULL,1,'<p>9</p>',NULL,'2025-09-03 07:31:33','2025-09-03 07:31:33',NULL),(10,'chapter',1,2,NULL,1,'<p>10</p>',NULL,'2025-09-03 07:31:37','2025-09-03 07:31:37',NULL),(11,'chapter',1,2,NULL,1,'<p>11</p>',NULL,'2025-09-03 07:31:41','2025-09-03 07:31:41',NULL),(12,'chapter',1,2,NULL,1,'<p>12</p>',NULL,'2025-09-03 13:35:59','2025-09-03 13:35:59',NULL),(13,'chapter',1,2,11,1,'<p>Blablabla</p>',NULL,'2025-09-03 13:40:00','2025-09-03 13:40:00',NULL),(14,'chapter',1,3,NULL,1,'<p>Quelle claque absolue ! La quintessence de la fantaisie moderne, rien de moins. Le style est fabuleux, d’une fluidité étincelante, avec des images si vives qu’elles crépitent à chaque ligne. Les personnages sont exceptionnels, <strong>inoubliables</strong>, taillés dans une matière rare où l’intime flirte avec le mythique. </p>\n\n<p><br></p>\n\n<p>On tourne les pages avec l’impression d’assister à une constellation qui se forme sous nos yeux : chaque mot devient étoile, chaque phrase, une orbite parfaite. L’intrigue fuse, serpente, éclate, puis se recompose avec une maîtrise insolente, comme si la gravité narrative obéissait au seul caprice de l’auteur. On rit, on frissonne, on s’émerveille — parfois tout cela en même temps. Les dialogues claquent, les silences parlent, les descriptions respirent une poésie luxuriante sans jamais étouffer le rythme. C’est un festin littéraire, un carnaval d’émotions, une cathédrale d’imaginaire où chaque chapiteau raconte sa légende. </p>\n\n<p><br></p>\n\n<p>Et quand on croit toucher au sommet, un nouveau panorama s’ouvre, plus vaste, plus lumineux. Je referme ce chapitre avec le cœur battant et la certitude ravie d’avoir trouvé un phare dans la brume de nos lectures quotidiennes. Magistral, généreux, irrésistible : que la suite arrive vite, je suis déjà conquis.</p>\n\n<p><br></p>\n\n<p><em>– Bien sûr que non, je suis sûre que tous ces animaux m\'adoreraient. »</em></p>\n\n<p>Ah la la, c\'est tellement drôle !</p>',NULL,'2025-09-03 14:03:08','2025-09-03 14:03:08',NULL),(15,'chapter',1,2,14,1,'<p>Merci pour ce commentaire. C\'est vrai que je suis un auteur d\'exception, et je n\'attendais rien de moi qu\'un retour aussi dithirambik (euh... ça s\'écrit comment déjà ? Y\'a un h quelque part, non ? et un y ? et le k est en trop ?)</p>',NULL,'2025-09-03 14:06:11','2025-09-08 19:02:26',NULL),(17,'chapter',1,2,13,1,'<p>blablablablabla même !</p>',NULL,'2025-09-03 14:38:13','2025-09-03 14:38:13',NULL),(18,'chapter',1,2,14,1,'<p>Au fait, je suis le meilleur!</p>',NULL,'2025-09-03 15:28:47','2025-09-07 21:09:18',NULL),(19,'chapter',1,2,12,1,'<p>Et ça, ça marche toujours ?</p>',NULL,'2025-09-03 15:29:03','2025-09-03 15:29:03',NULL),(20,'chapter',1,2,7,1,'<p>Ok now I need a very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very long comment</p>',NULL,'2025-09-04 10:11:02','2025-09-04 10:11:02',NULL),(21,'chapter',1,3,14,1,'<p>Sans aucun doute.</p>',NULL,'2025-09-06 19:09:23','2025-09-06 19:09:23',NULL),(22,'chapter',1,3,14,1,'<ul><li>Point 1</li><li>Point 2</li></ul>',NULL,'2025-09-07 05:25:32','2025-09-07 05:25:32',NULL),(24,'chapter',1,2,12,1,'<p>Je me réponds à moi même</p>',NULL,'2025-09-07 21:01:11','2025-09-07 21:09:36',NULL),(25,'chapter',1,2,10,1,'<p>Nouveau édité</p>',NULL,'2025-09-07 21:14:16','2025-09-07 21:14:23',NULL),(26,'chapter',1,4,14,1,'<p>Hum</p>',NULL,'2025-09-08 20:24:58','2025-09-08 20:24:58',NULL),(27,'chapter',1,2,10,1,'<p>Un nouveau test.</p>\n\n<p>Sur deux paragraphes.</p>',NULL,'2025-10-01 15:12:52','2025-10-01 15:12:52',NULL),(28,'chapter',14,6,NULL,1,'<p>Je ne suis pas sûr de bien avoir compris ce que tu voulais dire dans ce chapitre. J\'avoue que dans ce cas précis la concision n\'est pas ton alliée</p>',NULL,'2025-12-27 15:25:56','2025-12-27 15:25:56',NULL),(29,'chapter',6,6,NULL,1,'<p>Encore un chapitre démesurément concis avec lequel on ne sait que penser. Franchement, si c\'est juste pour taper du texte au hasard, demande à un singe de le faire, non ?</p>',NULL,'2025-12-27 15:27:02','2025-12-27 15:27:02',NULL),(30,'chapter',7,6,NULL,1,'<p>Toujours pas. Vraiment, toujour rien de valide dans ce chapitre et c\'est agaçant de devoir écrire un commentaire plus long que le texte. Franchement, c\'est de l\'abus.</p>',NULL,'2025-12-27 15:27:38','2025-12-27 15:27:38',NULL),(31,'chapter',1,4,NULL,1,'<p>J\'aime beaucoup ce chapitre, il est vraiment très réussi ! Même si je ne suis pas sûr de comprendre quelle est la voix dans l\'esprit d\'Élias, mais je suppose que tu nous expliqueras ça ensuite !</p>',NULL,'2026-01-11 10:41:04','2026-01-11 10:41:04',NULL);
/*!40000 ALTER TABLE `comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `config_feature_toggles`
--

DROP TABLE IF EXISTS `config_feature_toggles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `config_feature_toggles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `domain` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `access` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `admin_visibility` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `roles` json DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `config_feature_toggles_domain_name_unique` (`domain`,`name`),
  KEY `config_feature_toggles_domain_index` (`domain`),
  KEY `config_feature_toggles_access_index` (`access`),
  KEY `config_feature_toggles_admin_visibility_index` (`admin_visibility`),
  KEY `config_feature_toggles_updated_by_index` (`updated_by`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `config_feature_toggles`
--

LOCK TABLES `config_feature_toggles` WRITE;
/*!40000 ALTER TABLE `config_feature_toggles` DISABLE KEYS */;
INSERT INTO `config_feature_toggles` VALUES (1,'moderation','reporting','on','tech_admins_only','[]',2,'2025-10-23 06:53:41','2025-10-23 06:53:41'),(2,'calendar','enabled','on','tech_admins_only','[]',2,'2025-10-23 18:44:33','2025-10-23 18:44:33');
/*!40000 ALTER TABLE `config_feature_toggles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `config_parameter_values`
--

DROP TABLE IF EXISTS `config_parameter_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `config_parameter_values` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `domain` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `config_parameter_values_domain_key_unique` (`domain`,`key`),
  KEY `config_parameter_values_domain_index` (`domain`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `config_parameter_values`
--

LOCK TABLES `config_parameter_values` WRITE;
/*!40000 ALTER TABLE `config_parameter_values` DISABLE KEYS */;
INSERT INTO `config_parameter_values` VALUES (1,'auth','require_activation_code','0',2,'2026-01-11 10:30:31','2026-01-11 10:30:31');
/*!40000 ALTER TABLE `config_parameter_values` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `discord_connection_codes`
--

DROP TABLE IF EXISTS `discord_connection_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `discord_connection_codes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `code` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` timestamp NOT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `discord_connection_codes_code_unique` (`code`),
  KEY `discord_connection_codes_user_id_index` (`user_id`),
  KEY `discord_connection_codes_user_id_expires_at_index` (`user_id`,`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `discord_connection_codes`
--

LOCK TABLES `discord_connection_codes` WRITE;
/*!40000 ALTER TABLE `discord_connection_codes` DISABLE KEYS */;
INSERT INTO `discord_connection_codes` VALUES (3,2,'baa72b30','2025-10-04 19:04:06','2025-10-04 19:02:00','2025-10-04 18:59:06','2025-10-04 19:02:00'),(5,2,'6d15489a','2025-10-04 19:19:08','2025-10-04 19:14:19','2025-10-04 19:14:08','2025-10-04 19:14:19'),(8,2,'bcc75a8c','2025-10-06 19:03:18',NULL,'2025-10-06 18:58:18','2025-10-06 18:58:18');
/*!40000 ALTER TABLE `discord_connection_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `discord_users`
--

DROP TABLE IF EXISTS `discord_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `discord_users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `discord_user_id` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `discord_username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `discord_users_discord_user_id_unique` (`discord_user_id`),
  KEY `discord_users_user_id_index` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `discord_users`
--

LOCK TABLES `discord_users` WRITE;
/*!40000 ALTER TABLE `discord_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `discord_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `events_domain`
--

DROP TABLE IF EXISTS `events_domain`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `events_domain` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` json NOT NULL,
  `triggered_by_user_id` bigint unsigned DEFAULT NULL,
  `context_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `context_user_agent` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `context_url` varchar(2048) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `occurred_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `events_domain_name_index` (`name`),
  KEY `events_domain_triggered_by_user_id_index` (`triggered_by_user_id`),
  KEY `events_domain_occurred_at_index` (`occurred_at`)
) ENGINE=InnoDB AUTO_INCREMENT=227 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `events_domain`
--

LOCK TABLES `events_domain` WRITE;
/*!40000 ALTER TABLE `events_domain` DISABLE KEYS */;
INSERT INTO `events_domain` VALUES (1,'Auth.UserRegistered','{\"userId\": 6, \"displayName\": \"Fredo\"}',NULL,NULL,NULL,NULL,NULL,'2025-09-12 15:01:38'),(2,'Profile.DisplayNameChanged','{\"userId\": 6, \"newDisplayName\": \"Fredounet\", \"oldDisplayName\": \"Fredo\"}',6,NULL,NULL,NULL,NULL,'2025-09-12 19:32:24'),(3,'Auth.PasswordResetRequested','{\"email\": \"fredo@hemit.fr\", \"userId\": 6}',NULL,NULL,NULL,NULL,NULL,'2025-09-13 06:34:07'),(4,'Auth.PasswordChanged','{\"userId\": 6}',NULL,NULL,NULL,NULL,NULL,'2025-09-13 06:34:45'),(5,'Auth.UserLoggedIn','{\"userId\": 6}',6,NULL,NULL,NULL,NULL,'2025-09-13 06:34:55'),(6,'Profile.AvatarChanged','{\"userId\": 6, \"profilePicturePath\": \"profile_pictures/6_1757745458.jpg\"}',6,NULL,NULL,NULL,NULL,'2025-09-13 06:37:38'),(7,'Profile.AvatarChanged','{\"userId\": 6, \"profilePicturePath\": null}',6,NULL,NULL,NULL,NULL,'2025-09-13 06:37:42'),(8,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-14 06:43:14'),(9,'Auth.UserRegistered','{\"userId\": 7, \"displayName\": \"Test1\"}',NULL,NULL,NULL,NULL,NULL,'2025-09-14 06:43:35'),(10,'Auth.UserLoggedOut','{\"userId\": 7}',7,NULL,NULL,NULL,NULL,'2025-09-14 06:43:43'),(11,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-14 12:03:15'),(12,'Auth.UserRoleGranted','{\"role\": \"user\", \"userId\": 7, \"actorUserId\": 2, \"targetIsAdmin\": false}',2,NULL,NULL,NULL,NULL,'2025-09-14 12:26:26'),(13,'Auth.UserRoleRevoked','{\"role\": \"user-confirmed\", \"userId\": 7, \"actorUserId\": 2, \"targetIsAdmin\": false}',2,NULL,NULL,NULL,NULL,'2025-09-14 12:26:26'),(14,'Auth.UserRoleRevoked','{\"role\": \"user\", \"userId\": 7, \"actorUserId\": 2, \"targetIsAdmin\": false}',2,NULL,NULL,NULL,NULL,'2025-09-14 12:26:40'),(15,'Auth.UserRoleGranted','{\"role\": \"user-confirmed\", \"userId\": 7, \"actorUserId\": 2, \"targetIsAdmin\": false}',2,NULL,NULL,NULL,NULL,'2025-09-14 12:26:40'),(16,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-14 18:32:34'),(17,'Story.Updated','{\"after\": {\"slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-pas-2\", \"title\": \"Immortelle, le roman dont le titre n\'en finit pas\", \"typeId\": 1, \"storyId\": 2, \"genreIds\": [1], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"community\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 129, \"summaryWordCount\": 23, \"triggerWarningIds\": []}, \"before\": {\"slug\": \"immortelle-2\", \"title\": \"Immortelle\", \"typeId\": 1, \"storyId\": 2, \"genreIds\": [1], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"community\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 129, \"summaryWordCount\": 23, \"triggerWarningIds\": []}}',2,NULL,NULL,NULL,NULL,'2025-09-14 19:02:40'),(18,'Story.Updated','{\"after\": {\"slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-pas-2\", \"title\": \"Immortelle, le roman dont le titre n\'en finit pas\", \"typeId\": 1, \"storyId\": 2, \"genreIds\": [1, 2, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"community\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 129, \"summaryWordCount\": 23, \"triggerWarningIds\": []}, \"before\": {\"slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-pas-2\", \"title\": \"Immortelle, le roman dont le titre n\'en finit pas\", \"typeId\": 1, \"storyId\": 2, \"genreIds\": [1], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"community\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 129, \"summaryWordCount\": 23, \"triggerWarningIds\": []}}',2,NULL,NULL,NULL,NULL,'2025-09-14 19:02:48'),(19,'Story.Updated','{\"after\": {\"slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-pas-2\", \"title\": \"Immortelle, le roman dont le titre n\'en finit pas\", \"typeId\": 1, \"storyId\": 2, \"genreIds\": [1, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"community\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 129, \"summaryWordCount\": 23, \"triggerWarningIds\": []}, \"before\": {\"slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-pas-2\", \"title\": \"Immortelle, le roman dont le titre n\'en finit pas\", \"typeId\": 1, \"storyId\": 2, \"genreIds\": [1, 2, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"community\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 129, \"summaryWordCount\": 23, \"triggerWarningIds\": []}}',2,NULL,NULL,NULL,NULL,'2025-09-14 19:13:34'),(20,'Story.Updated','{\"after\": {\"slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-vraiment-vraiment-pas-2\", \"title\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\", \"typeId\": 1, \"storyId\": 2, \"genreIds\": [1, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"community\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 129, \"summaryWordCount\": 23, \"triggerWarningIds\": []}, \"before\": {\"slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-pas-2\", \"title\": \"Immortelle, le roman dont le titre n\'en finit pas\", \"typeId\": 1, \"storyId\": 2, \"genreIds\": [1, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"community\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 129, \"summaryWordCount\": 23, \"triggerWarningIds\": []}}',2,NULL,NULL,NULL,NULL,'2025-09-14 19:15:18'),(21,'Story.Updated','{\"after\": {\"slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-vraiment-vraiment-pas-2\", \"title\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\", \"typeId\": 1, \"storyId\": 2, \"genreIds\": [1, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"community\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 129, \"summaryWordCount\": 23, \"triggerWarningIds\": [1, 2]}, \"before\": {\"slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-vraiment-vraiment-pas-2\", \"title\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\", \"typeId\": 1, \"storyId\": 2, \"genreIds\": [1, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"community\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 129, \"summaryWordCount\": 23, \"triggerWarningIds\": []}}',2,NULL,NULL,NULL,NULL,'2025-09-14 19:19:49'),(22,'Story.Updated','{\"after\": {\"slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-vraiment-vraiment-pas-2\", \"title\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\", \"typeId\": 1, \"storyId\": 2, \"genreIds\": [1, 2, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"community\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 129, \"summaryWordCount\": 23, \"triggerWarningIds\": [1, 2]}, \"before\": {\"slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-vraiment-vraiment-pas-2\", \"title\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\", \"typeId\": 1, \"storyId\": 2, \"genreIds\": [1, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"community\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 129, \"summaryWordCount\": 23, \"triggerWarningIds\": [1, 2]}}',2,NULL,NULL,NULL,NULL,'2025-09-14 19:20:33'),(23,'Story.Updated','{\"after\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 767, \"summaryWordCount\": 126, \"triggerWarningIds\": []}, \"before\": {\"slug\": \"le-crepuscule-des-armes-1\", \"title\": \"Le Crépuscule des Ârmes\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 767, \"summaryWordCount\": 126, \"triggerWarningIds\": []}}',2,NULL,NULL,NULL,NULL,'2025-09-14 19:55:01'),(24,'Story.Created','{\"story\": {\"slug\": \"je-connais-une-histoire-5\", \"title\": \"Je connais une histoire...\", \"typeId\": 1, \"storyId\": 5, \"genreIds\": [1, 2], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 107, \"summaryWordCount\": 19, \"triggerWarningIds\": []}}',2,NULL,NULL,NULL,NULL,'2025-09-14 19:57:32'),(25,'Chapter.Created','{\"chapter\": {\"id\": 14, \"slug\": \"chapitre-1-14\", \"title\": \"Chapitre 1\", \"status\": \"published\", \"charCount\": 15, \"sortOrder\": 100, \"wordCount\": 3}, \"storyId\": 5}',2,NULL,NULL,NULL,NULL,'2025-09-14 19:58:02'),(26,'Profile.DisplayNameChanged','{\"userId\": 2, \"newDisplayName\": \"LogistiX le seigneur des loutres de la grande colline\", \"oldDisplayName\": \"LogistiX\"}',2,NULL,NULL,NULL,NULL,'2025-09-14 20:37:07'),(27,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-15 20:16:25'),(28,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-16 19:45:32'),(29,'Auth.UserLoggedIn','{\"userId\": 3}',3,NULL,NULL,NULL,NULL,'2025-09-16 21:33:35'),(30,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-17 05:16:13'),(31,'Story.Created','{\"story\": {\"slug\": \"limit-test-with-a-long-long-long-long-long-long-long-long-long-long-long-title-6\", \"title\": \"Limit test with a long long long long long long long long long long long title\", \"typeId\": 1, \"storyId\": 6, \"genreIds\": [1, 2, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 175, \"summaryWordCount\": 1, \"triggerWarningIds\": []}}',2,NULL,NULL,NULL,NULL,'2025-09-17 05:24:13'),(32,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-17 18:50:48'),(33,'Story.Updated','{\"after\": {\"slug\": \"limit-test-with-a-long-long-long-long-long-long-long-long-long-long-long-title-6\", \"title\": \"Limit test with a long long long long long long long long long long long title\", \"typeId\": 1, \"storyId\": 6, \"genreIds\": [1, 2], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 175, \"summaryWordCount\": 1, \"triggerWarningIds\": []}, \"before\": {\"slug\": \"limit-test-with-a-long-long-long-long-long-long-long-long-long-long-long-title-6\", \"title\": \"Limit test with a long long long long long long long long long long long title\", \"typeId\": 1, \"storyId\": 6, \"genreIds\": [1, 2, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 175, \"summaryWordCount\": 1, \"triggerWarningIds\": []}}',2,NULL,NULL,NULL,NULL,'2025-09-17 19:11:27'),(34,'Story.Updated','{\"after\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 767, \"summaryWordCount\": 126, \"triggerWarningIds\": []}, \"before\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 767, \"summaryWordCount\": 126, \"triggerWarningIds\": []}}',2,NULL,NULL,NULL,NULL,'2025-09-17 20:23:16'),(35,'Story.Updated','{\"after\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 767, \"summaryWordCount\": 126, \"triggerWarningIds\": []}, \"before\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 767, \"summaryWordCount\": 126, \"triggerWarningIds\": []}}',2,NULL,NULL,NULL,NULL,'2025-09-17 20:24:13'),(36,'Story.Updated','{\"after\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 767, \"summaryWordCount\": 126, \"triggerWarningIds\": []}, \"before\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 767, \"summaryWordCount\": 126, \"triggerWarningIds\": []}}',2,NULL,NULL,NULL,NULL,'2025-09-17 20:51:11'),(37,'Story.Updated','{\"after\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 767, \"summaryWordCount\": 126, \"triggerWarningIds\": []}, \"before\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 767, \"summaryWordCount\": 126, \"triggerWarningIds\": []}}',2,NULL,NULL,NULL,NULL,'2025-09-17 20:51:17'),(38,'Story.Updated','{\"after\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 767, \"summaryWordCount\": 126, \"triggerWarningIds\": [2]}, \"before\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 767, \"summaryWordCount\": 126, \"triggerWarningIds\": []}}',2,NULL,NULL,NULL,NULL,'2025-09-17 20:51:27'),(39,'Story.Updated','{\"after\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 767, \"summaryWordCount\": 126, \"triggerWarningIds\": []}, \"before\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 767, \"summaryWordCount\": 126, \"triggerWarningIds\": [2]}}',2,NULL,NULL,NULL,NULL,'2025-09-17 21:00:06'),(40,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-18 18:43:37'),(41,'Story.Updated','{\"after\": {\"slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-vraiment-vraiment-pas-2\", \"title\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\", \"typeId\": 1, \"storyId\": 2, \"genreIds\": [1, 2, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"private\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 129, \"summaryWordCount\": 23, \"triggerWarningIds\": []}, \"before\": {\"slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-vraiment-vraiment-pas-2\", \"title\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\", \"typeId\": 1, \"storyId\": 2, \"genreIds\": [1, 2, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"community\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 129, \"summaryWordCount\": 23, \"triggerWarningIds\": [1, 2]}}',2,NULL,NULL,NULL,NULL,'2025-09-18 18:45:47'),(42,'Story.VisibilityChanged','{\"title\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\", \"story_id\": 2, \"new_visibility\": \"private\", \"old_visibility\": \"community\"}',2,NULL,NULL,NULL,NULL,'2025-09-18 18:45:47'),(43,'Story.Updated','{\"after\": {\"slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-vraiment-vraiment-pas-2\", \"title\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\", \"typeId\": 1, \"storyId\": 2, \"genreIds\": [1, 2, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"community\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 129, \"summaryWordCount\": 23, \"triggerWarningIds\": []}, \"before\": {\"slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-vraiment-vraiment-pas-2\", \"title\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\", \"typeId\": 1, \"storyId\": 2, \"genreIds\": [1, 2, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"private\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 129, \"summaryWordCount\": 23, \"triggerWarningIds\": []}}',2,NULL,NULL,NULL,NULL,'2025-09-18 19:23:37'),(44,'Story.VisibilityChanged','{\"title\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\", \"story_id\": 2, \"new_visibility\": \"community\", \"old_visibility\": \"private\"}',2,NULL,NULL,NULL,NULL,'2025-09-18 19:23:37'),(45,'Story.Updated','{\"after\": {\"slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-vraiment-vraiment-pas-2\", \"title\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\", \"typeId\": 1, \"storyId\": 2, \"genreIds\": [1, 2, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 129, \"summaryWordCount\": 23, \"triggerWarningIds\": [2]}, \"before\": {\"slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-vraiment-vraiment-pas-2\", \"title\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\", \"typeId\": 1, \"storyId\": 2, \"genreIds\": [1, 2, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"community\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 129, \"summaryWordCount\": 23, \"triggerWarningIds\": []}}',2,NULL,NULL,NULL,NULL,'2025-09-18 19:24:04'),(46,'Story.VisibilityChanged','{\"title\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\", \"story_id\": 2, \"new_visibility\": \"public\", \"old_visibility\": \"community\"}',2,NULL,NULL,NULL,NULL,'2025-09-18 19:24:04'),(47,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-19 11:48:15'),(48,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-19 11:52:55'),(49,'Auth.UserLoggedOut','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-19 11:53:04'),(50,'Auth.UserLoggedIn','{\"userId\": 3}',3,NULL,NULL,NULL,NULL,'2025-09-19 11:53:12'),(51,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-19 19:29:32'),(52,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-20 05:46:52'),(53,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-20 18:44:38'),(54,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-21 06:27:57'),(55,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-21 18:00:51'),(56,'Auth.UserLoggedOut','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-21 19:16:23'),(57,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-21 19:18:13'),(58,'Auth.UserLoggedOut','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-21 19:18:20'),(59,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-22 18:45:23'),(60,'Story.Updated','{\"after\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 767, \"summaryWordCount\": 126, \"triggerWarningIds\": [1, 2]}, \"before\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 767, \"summaryWordCount\": 126, \"triggerWarningIds\": []}}',2,NULL,NULL,NULL,NULL,'2025-09-22 18:59:46'),(61,'Chapter.Updated','{\"after\": {\"id\": 2, \"slug\": \"chapitre-2-tres-tres-long-2\", \"title\": \"Chapitre 2 très très long\", \"status\": \"not_published\", \"charCount\": 26, \"sortOrder\": 6, \"wordCount\": 4}, \"before\": {\"id\": 2, \"slug\": \"chapitre-2\", \"title\": \"Chapitre 2\", \"status\": \"not_published\", \"charCount\": 26, \"sortOrder\": 6, \"wordCount\": 4}, \"storyId\": 1}',2,NULL,NULL,NULL,NULL,'2025-09-22 19:21:17'),(62,'Chapter.Updated','{\"after\": {\"id\": 3, \"slug\": \"chapitre-3-tres-tres-long-aussi-pour-tester-3\", \"title\": \"Chapitre 3 très très long aussi pour tester\", \"status\": \"published\", \"charCount\": 3, \"sortOrder\": 12, \"wordCount\": 1}, \"before\": {\"id\": 3, \"slug\": \"chapitre-3\", \"title\": \"Chapitre 3\", \"status\": \"published\", \"charCount\": 3, \"sortOrder\": 12, \"wordCount\": 1}, \"storyId\": 1}',2,NULL,NULL,NULL,NULL,'2025-09-22 19:28:00'),(63,'Auth.UserLoggedIn','{\"userId\": 3}',3,NULL,NULL,NULL,NULL,'2025-09-22 19:29:04'),(64,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-23 19:13:19'),(65,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-24 05:23:39'),(66,'Auth.UserLoggedOut','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-24 05:24:30'),(67,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-25 18:33:21'),(68,'Story.Updated','{\"after\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 995, \"summaryWordCount\": 162, \"triggerWarningIds\": [1, 2]}, \"before\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 767, \"summaryWordCount\": 126, \"triggerWarningIds\": [1, 2]}}',2,NULL,NULL,NULL,NULL,'2025-09-25 18:35:09'),(69,'Story.Updated','{\"after\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 492, \"summaryWordCount\": 78, \"triggerWarningIds\": [1, 2]}, \"before\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 995, \"summaryWordCount\": 162, \"triggerWarningIds\": [1, 2]}}',2,NULL,NULL,NULL,NULL,'2025-09-25 19:10:53'),(70,'Auth.UserLoggedIn','{\"userId\": 3}',3,NULL,NULL,NULL,NULL,'2025-09-25 19:49:21'),(71,'Story.Updated','{\"after\": {\"slug\": \"lhistoire-sans-debut-3\", \"title\": \"L\'histoire sans début\", \"typeId\": 1, \"storyId\": 3, \"genreIds\": [1], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 117, \"summaryWordCount\": 23, \"triggerWarningIds\": []}, \"before\": {\"slug\": \"lhistoire-sans-debut-3\", \"title\": \"L\'histoire sans début\", \"typeId\": 1, \"storyId\": 3, \"genreIds\": [1], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 0, \"summaryWordCount\": 0, \"triggerWarningIds\": []}}',2,NULL,NULL,NULL,NULL,'2025-09-25 20:50:11'),(72,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-26 19:09:30'),(73,'Auth.UserRoleGranted','{\"role\": \"tech-admin\", \"userId\": 2, \"actorUserId\": 2, \"targetIsAdmin\": true}',2,NULL,NULL,NULL,NULL,'2025-09-26 19:48:39'),(74,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-27 05:44:21'),(75,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-27 18:40:26'),(76,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-27 18:51:35'),(77,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-28 11:54:52'),(78,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-28 17:52:23'),(79,'Story.Updated','{\"after\": {\"slug\": \"je-connais-une-histoire-5\", \"title\": \"Je connais une histoire...\", \"typeId\": 1, \"storyId\": 5, \"genreIds\": [1, 2], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 240, \"summaryWordCount\": 20, \"triggerWarningIds\": []}, \"before\": {\"slug\": \"je-connais-une-histoire-5\", \"title\": \"Je connais une histoire...\", \"typeId\": 1, \"storyId\": 5, \"genreIds\": [1, 2], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 107, \"summaryWordCount\": 19, \"triggerWarningIds\": []}}',2,NULL,NULL,NULL,NULL,'2025-09-28 17:52:39'),(80,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-29 06:39:16'),(81,'Auth.UserLoggedIn','{\"userId\": 3}',3,NULL,NULL,NULL,NULL,'2025-09-29 12:03:17'),(82,'Auth.UserLoggedIn','{\"userId\": 4}',4,NULL,NULL,NULL,NULL,'2025-09-29 13:03:33'),(83,'Auth.UserRoleGranted','{\"role\": \"user\", \"userId\": 4, \"actorUserId\": 2, \"targetIsAdmin\": false}',2,NULL,NULL,NULL,NULL,'2025-09-29 13:03:50'),(84,'Auth.UserRoleRevoked','{\"role\": \"user-confirmed\", \"userId\": 4, \"actorUserId\": 2, \"targetIsAdmin\": false}',2,NULL,NULL,NULL,NULL,'2025-09-29 13:03:50'),(85,'Auth.UserLoggedIn','{\"userId\": 3}',3,NULL,NULL,NULL,NULL,'2025-09-29 18:32:00'),(86,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-30 19:12:26'),(87,'Auth.UserLoggedIn','{\"userId\": 3}',3,NULL,NULL,NULL,NULL,'2025-09-30 19:14:34'),(88,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-01 11:40:32'),(89,'Chapter.Updated','{\"after\": {\"id\": 1, \"slug\": \"chapitre-1\", \"title\": \"Chapitre 1\", \"status\": \"published\", \"charCount\": 6852, \"sortOrder\": 0, \"wordCount\": 1225}, \"before\": {\"id\": 1, \"slug\": \"chapitre-1\", \"title\": \"Chapitre 1\", \"status\": \"published\", \"charCount\": 6852, \"sortOrder\": 0, \"wordCount\": 1225}, \"storyId\": 1}',2,NULL,NULL,NULL,NULL,'2025-10-01 12:12:47'),(90,'Chapter.Updated','{\"after\": {\"id\": 1, \"slug\": \"chapitre-1\", \"title\": \"Chapitre 1\", \"status\": \"published\", \"charCount\": 6852, \"sortOrder\": 0, \"wordCount\": 1225}, \"before\": {\"id\": 1, \"slug\": \"chapitre-1\", \"title\": \"Chapitre 1\", \"status\": \"published\", \"charCount\": 6852, \"sortOrder\": 0, \"wordCount\": 1225}, \"storyId\": 1}',2,NULL,NULL,NULL,NULL,'2025-10-01 12:12:55'),(91,'Chapter.Updated','{\"after\": {\"id\": 1, \"slug\": \"chapitre-1\", \"title\": \"Chapitre 1\", \"status\": \"published\", \"charCount\": 6803, \"sortOrder\": 0, \"wordCount\": 1225}, \"before\": {\"id\": 1, \"slug\": \"chapitre-1\", \"title\": \"Chapitre 1\", \"status\": \"published\", \"charCount\": 6852, \"sortOrder\": 0, \"wordCount\": 1225}, \"storyId\": 1}',2,NULL,NULL,NULL,NULL,'2025-10-01 12:20:16'),(92,'Auth.UserLoggedIn','{\"userId\": 3}',3,NULL,NULL,NULL,NULL,'2025-10-01 12:55:30'),(93,'Chapter.Updated','{\"after\": {\"id\": 1, \"slug\": \"chapitre-1\", \"title\": \"Chapitre 1\", \"status\": \"published\", \"charCount\": 6803, \"sortOrder\": 0, \"wordCount\": 1225}, \"before\": {\"id\": 1, \"slug\": \"chapitre-1\", \"title\": \"Chapitre 1\", \"status\": \"published\", \"charCount\": 6803, \"sortOrder\": 0, \"wordCount\": 1225}, \"storyId\": 1}',2,NULL,NULL,NULL,NULL,'2025-10-01 12:58:40'),(94,'Comment.Posted','{\"comment\": {\"is_reply\": true, \"author_id\": 2, \"entity_id\": 1, \"char_count\": 39, \"comment_id\": 27, \"word_count\": 6, \"entity_type\": \"chapter\", \"parent_comment_id\": 10}}',2,NULL,NULL,NULL,NULL,'2025-10-01 15:12:52'),(95,'Auth.UserLoggedOut','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-01 15:29:39'),(96,'Auth.PasswordResetRequested','{\"email\": \"fhemery@hemit.fr\", \"userId\": 2}',NULL,NULL,NULL,NULL,NULL,'2025-10-01 15:36:06'),(97,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-01 15:56:35'),(98,'Auth.UserLoggedOut','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-01 15:57:11'),(99,'Auth.UserLoggedIn','{\"userId\": 3}',3,NULL,NULL,NULL,NULL,'2025-10-01 17:57:11'),(100,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-01 18:28:23'),(101,'Auth.UserLoggedOut','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-01 18:47:18'),(102,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-01 18:48:00'),(103,'Auth.UserLoggedOut','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-01 18:48:22'),(104,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-01 18:48:32'),(105,'Auth.UserLoggedOut','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-01 19:16:24'),(106,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-01 19:16:35'),(107,'Story.Created','{\"story\": {\"slug\": \"test-7\", \"title\": \"Test\", \"typeId\": 1, \"storyId\": 7, \"genreIds\": [1], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 167, \"summaryWordCount\": 18, \"triggerWarningIds\": []}}',2,NULL,NULL,NULL,NULL,'2025-10-01 19:17:21'),(108,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-02 07:09:51'),(109,'Chapter.Unpublished','{\"chapter\": {\"id\": 1, \"slug\": \"chapitre-1\", \"title\": \"Chapitre 1\", \"status\": \"not_published\", \"charCount\": 6803, \"sortOrder\": 0, \"wordCount\": 1225}, \"storyId\": 1}',2,NULL,NULL,NULL,NULL,'2025-10-02 08:09:40'),(110,'Chapter.Updated','{\"after\": {\"id\": 1, \"slug\": \"chapitre-1\", \"title\": \"Chapitre 1\", \"status\": \"not_published\", \"charCount\": 6803, \"sortOrder\": 0, \"wordCount\": 1225}, \"before\": {\"id\": 1, \"slug\": \"chapitre-1\", \"title\": \"Chapitre 1\", \"status\": \"published\", \"charCount\": 6803, \"sortOrder\": 0, \"wordCount\": 1225}, \"storyId\": 1}',2,NULL,NULL,NULL,NULL,'2025-10-02 08:09:40'),(111,'Chapter.Updated','{\"after\": {\"id\": 1, \"slug\": \"chapitre-1\", \"title\": \"Chapitre 1\", \"status\": \"not_published\", \"charCount\": 6803, \"sortOrder\": 0, \"wordCount\": 1225}, \"before\": {\"id\": 1, \"slug\": \"chapitre-1\", \"title\": \"Chapitre 1\", \"status\": \"not_published\", \"charCount\": 6803, \"sortOrder\": 0, \"wordCount\": 1225}, \"storyId\": 1}',2,NULL,NULL,NULL,NULL,'2025-10-02 08:09:47'),(112,'Chapter.Published','{\"chapter\": {\"id\": 1, \"slug\": \"chapitre-1\", \"title\": \"Chapitre 1\", \"status\": \"published\", \"charCount\": 6803, \"sortOrder\": 0, \"wordCount\": 1225}, \"storyId\": 1}',2,NULL,NULL,NULL,NULL,'2025-10-02 08:09:52'),(113,'Chapter.Updated','{\"after\": {\"id\": 1, \"slug\": \"chapitre-1\", \"title\": \"Chapitre 1\", \"status\": \"published\", \"charCount\": 6803, \"sortOrder\": 0, \"wordCount\": 1225}, \"before\": {\"id\": 1, \"slug\": \"chapitre-1\", \"title\": \"Chapitre 1\", \"status\": \"not_published\", \"charCount\": 6803, \"sortOrder\": 0, \"wordCount\": 1225}, \"storyId\": 1}',2,NULL,NULL,NULL,NULL,'2025-10-02 08:09:52'),(114,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-02 14:05:26'),(115,'Auth.UserLoggedIn','{\"userId\": 3}',3,NULL,NULL,NULL,NULL,'2025-10-02 18:24:34'),(116,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-04 13:14:18'),(117,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-04 18:44:46'),(118,'Discord.Connected','{\"userId\": 2, \"discordId\": \"123456789012345678\"}',NULL,NULL,NULL,NULL,NULL,'2025-10-04 19:02:00'),(119,'Discord.Disconnected','{\"userId\": 2, \"discordId\": \"123456789012345678\"}',NULL,NULL,NULL,NULL,NULL,'2025-10-04 19:12:31'),(120,'Discord.Connected','{\"userId\": 2, \"discordId\": \"123456789012345678\"}',NULL,NULL,NULL,NULL,NULL,'2025-10-04 19:14:19'),(121,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-05 17:56:42'),(122,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-06 18:55:26'),(123,'Discord.Disconnected','{\"userId\": 2, \"discordId\": \"123456789012345678\"}',NULL,NULL,NULL,NULL,NULL,'2025-10-06 18:57:56'),(124,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-07 19:29:01'),(125,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-22 13:29:20'),(126,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-22 18:25:20'),(127,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-23 06:09:28'),(128,'Auth.UserLoggedIn','{\"userId\": 6}',6,NULL,NULL,NULL,NULL,'2025-10-23 06:53:05'),(129,'Config.FeatureToggleAdded','{\"featureToggle\": {\"name\": \"reporting\", \"roles\": [], \"access\": \"on\", \"domain\": \"moderation\", \"admin_visibility\": \"tech_admins_only\"}}',2,NULL,NULL,NULL,NULL,'2025-10-23 06:53:41'),(130,'Moderation.ReportSubmitted','{\"entityId\": 2, \"reasonId\": 1, \"reportId\": 1, \"topicKey\": \"profile\", \"reasonLabel\": \"Autre\", \"reportedByUserId\": 6}',6,NULL,NULL,NULL,NULL,'2025-10-23 06:54:54'),(131,'Moderation.ReportSubmitted','{\"entityId\": 2, \"reasonId\": 1, \"reportId\": 2, \"topicKey\": \"profile\", \"reasonLabel\": \"Autre\", \"reportedByUserId\": 6}',6,NULL,NULL,NULL,NULL,'2025-10-23 06:58:19'),(132,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-23 18:41:49'),(133,'Config.FeatureToggleAdded','{\"featureToggle\": {\"name\": \"enabled\", \"roles\": [], \"access\": \"on\", \"domain\": \"calendar\", \"admin_visibility\": \"tech_admins_only\"}}',2,NULL,NULL,NULL,NULL,'2025-10-23 18:44:33'),(134,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-24 11:54:58'),(135,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-24 19:08:04'),(136,'Chapter.Created','{\"chapter\": {\"id\": 15, \"slug\": \"chapitre-2-15\", \"title\": \"Chapitre 2\", \"status\": \"published\", \"charCount\": 3836, \"sortOrder\": 400, \"wordCount\": 688}, \"storyId\": 1}',2,NULL,NULL,NULL,NULL,'2025-10-24 19:47:13'),(137,'Chapter.Deleted','{\"chapter\": {\"id\": 3, \"slug\": \"chapitre-3-tres-tres-long-aussi-pour-tester-3\", \"title\": \"Chapitre 3 très très long aussi pour tester\", \"status\": \"published\", \"charCount\": 3, \"sortOrder\": 50, \"wordCount\": 1}, \"storyId\": 1}',2,NULL,NULL,NULL,NULL,'2025-10-24 19:48:41'),(138,'Chapter.Deleted','{\"chapter\": {\"id\": 2, \"slug\": \"chapitre-2-tres-tres-long-2\", \"title\": \"Chapitre 2 très très long\", \"status\": \"not_published\", \"charCount\": 26, \"sortOrder\": 100, \"wordCount\": 4}, \"storyId\": 1}',2,NULL,NULL,NULL,NULL,'2025-10-24 19:48:45'),(139,'Chapter.Deleted','{\"chapter\": {\"id\": 12, \"slug\": \"chapitre-4-12\", \"title\": \"Chapitre 4\", \"status\": \"published\", \"charCount\": 6, \"sortOrder\": 300, \"wordCount\": 1}, \"storyId\": 1}',2,NULL,NULL,NULL,NULL,'2025-10-24 19:48:50'),(140,'Chapter.Created','{\"chapter\": {\"id\": 16, \"slug\": \"chapitre-3-16\", \"title\": \"Chapitre 3\", \"status\": \"published\", \"charCount\": 6235, \"sortOrder\": 500, \"wordCount\": 1152}, \"storyId\": 1}',2,NULL,NULL,NULL,NULL,'2025-10-24 19:49:07'),(141,'Chapter.Updated','{\"after\": {\"id\": 16, \"slug\": \"chapitre-3-16\", \"title\": \"Chapitre 3\", \"status\": \"published\", \"charCount\": 507, \"sortOrder\": 500, \"wordCount\": 96}, \"before\": {\"id\": 16, \"slug\": \"chapitre-3-16\", \"title\": \"Chapitre 3\", \"status\": \"published\", \"charCount\": 6235, \"sortOrder\": 500, \"wordCount\": 1152}, \"storyId\": 1}',2,NULL,NULL,NULL,NULL,'2025-10-24 19:52:41'),(142,'Chapter.Deleted','{\"chapter\": {\"id\": 16, \"slug\": \"chapitre-3-16\", \"title\": \"Chapitre 3\", \"status\": \"published\", \"charCount\": 507, \"sortOrder\": 500, \"wordCount\": 96}, \"storyId\": 1}',2,NULL,NULL,NULL,NULL,'2025-10-24 19:53:37'),(143,'Chapter.Created','{\"chapter\": {\"id\": 17, \"slug\": \"chapitre-3-17\", \"title\": \"Chapitre 3\", \"status\": \"published\", \"charCount\": 6235, \"sortOrder\": 500, \"wordCount\": 1152}, \"storyId\": 1}',2,NULL,NULL,NULL,NULL,'2025-10-24 19:53:57'),(144,'Chapter.Created','{\"chapter\": {\"id\": 18, \"slug\": \"chapitre-4-18\", \"title\": \"Chapitre 4\", \"status\": \"published\", \"charCount\": 9621, \"sortOrder\": 600, \"wordCount\": 1799}, \"storyId\": 1}',2,NULL,NULL,NULL,NULL,'2025-10-24 19:54:21'),(145,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-25 05:43:55'),(146,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-25 18:15:53'),(147,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-27 12:35:45'),(148,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-28 12:07:44'),(149,'Auth.UserLoggedIn','{\"userId\": 6}',6,NULL,NULL,NULL,NULL,'2025-10-28 12:39:28'),(150,'Story.Created','{\"story\": {\"slug\": \"test-8\", \"title\": \"Test\", \"typeId\": 1, \"storyId\": 8, \"genreIds\": [1, 2], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 6, \"summaryCharCount\": 113, \"summaryWordCount\": 20, \"triggerWarningIds\": []}}',6,NULL,NULL,NULL,NULL,'2025-10-28 12:43:41'),(151,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-29 08:49:42'),(152,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-12-20 19:04:22'),(153,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-12-21 14:25:17'),(154,'News.Published','{\"slug\": \"new-news\", \"title\": \"New news\", \"newsId\": 2, \"publishedAt\": \"2025-12-21T14:40:01.000000Z\"}',2,NULL,NULL,NULL,NULL,'2025-12-21 14:40:01'),(155,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-12-23 11:20:48'),(156,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-12-23 18:59:29'),(157,'Auth.UserLoggedIn','{\"userId\": 6}',6,NULL,NULL,NULL,NULL,'2025-12-23 19:05:43'),(158,'Auth.UserLoggedOut','{\"userId\": 6}',6,NULL,NULL,NULL,NULL,'2025-12-23 19:17:07'),(159,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-12-23 19:17:15'),(160,'Auth.UserLoggedOut','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-12-23 19:22:14'),(161,'Auth.UserLoggedIn','{\"userId\": 6}',6,NULL,NULL,NULL,NULL,'2025-12-23 19:22:21'),(162,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-12-25 15:05:48'),(163,'Auth.UserLoggedIn','{\"userId\": 6}',6,NULL,NULL,NULL,NULL,'2025-12-25 15:13:25'),(164,'Profile.DisplayNameChanged','{\"userId\": 6, \"newDisplayName\": \"Daniel\", \"oldDisplayName\": \"Fredounet\"}',6,NULL,NULL,NULL,NULL,'2025-12-25 15:13:41'),(165,'Auth.UserLoggedOut','{\"userId\": 6}',6,NULL,NULL,NULL,NULL,'2025-12-25 15:19:18'),(166,'Auth.UserLoggedIn','{\"userId\": 3}',3,NULL,NULL,NULL,NULL,'2025-12-25 15:19:27'),(167,'Profile.DisplayNameChanged','{\"userId\": 3, \"newDisplayName\": \"Alice\", \"oldDisplayName\": \"LX\"}',3,NULL,NULL,NULL,NULL,'2025-12-25 15:19:59'),(168,'Story.Updated','{\"after\": {\"slug\": \"test-7\", \"title\": \"Test\", \"typeId\": 1, \"storyId\": 7, \"genreIds\": [1], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"isComplete\": false, \"visibility\": \"private\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 167, \"summaryWordCount\": 18, \"triggerWarningIds\": [], \"isExcludedFromEvents\": false}, \"before\": {\"slug\": \"test-7\", \"title\": \"Test\", \"typeId\": 1, \"storyId\": 7, \"genreIds\": [1], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"isComplete\": false, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 167, \"summaryWordCount\": 18, \"triggerWarningIds\": [], \"isExcludedFromEvents\": false}}',2,NULL,NULL,NULL,NULL,'2025-12-25 15:21:08'),(169,'Story.VisibilityChanged','{\"title\": \"Test\", \"story_id\": 7, \"new_visibility\": \"private\", \"old_visibility\": \"public\"}',2,NULL,NULL,NULL,NULL,'2025-12-25 15:21:08'),(170,'ReadList.Added','{\"userId\": 3, \"storyId\": 7}',3,NULL,NULL,NULL,NULL,'2025-12-25 15:22:25'),(171,'Chapter.Unpublished','{\"chapter\": {\"id\": 15, \"slug\": \"chapitre-2-15\", \"title\": \"Chapitre 2\", \"status\": \"not_published\", \"charCount\": 3836, \"sortOrder\": 400, \"wordCount\": 688}, \"storyId\": 1}',2,NULL,NULL,NULL,NULL,'2025-12-25 15:57:09'),(172,'Chapter.Updated','{\"after\": {\"id\": 15, \"slug\": \"chapitre-2-15\", \"title\": \"Chapitre 2\", \"status\": \"not_published\", \"charCount\": 3836, \"sortOrder\": 400, \"wordCount\": 688}, \"before\": {\"id\": 15, \"slug\": \"chapitre-2-15\", \"title\": \"Chapitre 2\", \"status\": \"published\", \"charCount\": 3836, \"sortOrder\": 400, \"wordCount\": 688}, \"storyId\": 1}',2,NULL,NULL,NULL,NULL,'2025-12-25 15:57:09'),(173,'Chapter.Created','{\"chapter\": {\"id\": 19, \"slug\": \"publie-19\", \"title\": \"Publié\", \"status\": \"published\", \"charCount\": 4, \"sortOrder\": 100, \"wordCount\": 1}, \"storyId\": 7}',2,NULL,NULL,NULL,NULL,'2025-12-25 16:09:16'),(174,'Chapter.Created','{\"chapter\": {\"id\": 20, \"slug\": \"non-publie-20\", \"title\": \"Non publié\", \"status\": \"not_published\", \"charCount\": 6, \"sortOrder\": 200, \"wordCount\": 2}, \"storyId\": 7}',2,NULL,NULL,NULL,NULL,'2025-12-25 16:09:30'),(175,'Auth.UserLoggedIn','{\"userId\": 6}',6,NULL,NULL,NULL,NULL,'2025-12-25 16:10:18'),(176,'Story.Updated','{\"after\": {\"slug\": \"test-partage-7\", \"title\": \"Test partage\", \"typeId\": 1, \"storyId\": 7, \"genreIds\": [1], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"isComplete\": false, \"visibility\": \"private\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 167, \"summaryWordCount\": 18, \"triggerWarningIds\": [], \"isExcludedFromEvents\": false}, \"before\": {\"slug\": \"test-7\", \"title\": \"Test\", \"typeId\": 1, \"storyId\": 7, \"genreIds\": [1], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"isComplete\": false, \"visibility\": \"private\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 167, \"summaryWordCount\": 18, \"triggerWarningIds\": [], \"isExcludedFromEvents\": false}}',2,NULL,NULL,NULL,NULL,'2025-12-25 16:12:06'),(177,'Chapter.Updated','{\"after\": {\"id\": 20, \"slug\": \"non-publie-20\", \"title\": \"Non publié\", \"status\": \"not_published\", \"charCount\": 19, \"sortOrder\": 200, \"wordCount\": 5}, \"before\": {\"id\": 20, \"slug\": \"non-publie-20\", \"title\": \"Non publié\", \"status\": \"not_published\", \"charCount\": 6, \"sortOrder\": 200, \"wordCount\": 2}, \"storyId\": 7}',6,NULL,NULL,NULL,NULL,'2025-12-25 16:12:49'),(178,'Story.Updated','{\"after\": {\"slug\": \"limit-test-with-a-long-long-long-long-long-long-long-long-long-long-long-title-6\", \"title\": \"Limit test with a long long long long long long long long long long long title\", \"typeId\": 1, \"storyId\": 6, \"genreIds\": [1, 2], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"isComplete\": false, \"visibility\": \"private\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 175, \"summaryWordCount\": 1, \"triggerWarningIds\": [], \"isExcludedFromEvents\": false}, \"before\": {\"slug\": \"limit-test-with-a-long-long-long-long-long-long-long-long-long-long-long-title-6\", \"title\": \"Limit test with a long long long long long long long long long long long title\", \"typeId\": 1, \"storyId\": 6, \"genreIds\": [1, 2], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"isComplete\": false, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 175, \"summaryWordCount\": 1, \"triggerWarningIds\": [], \"isExcludedFromEvents\": false}}',2,NULL,NULL,NULL,NULL,'2025-12-25 16:21:52'),(179,'Story.VisibilityChanged','{\"title\": \"Limit test with a long long long long long long long long long long long title\", \"story_id\": 6, \"new_visibility\": \"private\", \"old_visibility\": \"public\"}',2,NULL,NULL,NULL,NULL,'2025-12-25 16:21:52'),(180,'Auth.UserLoggedIn','{\"userId\": 6}',6,NULL,NULL,NULL,NULL,'2025-12-25 20:43:49'),(181,'Chapter.Created','{\"chapter\": {\"id\": 21, \"slug\": \"chapitre-3-21\", \"title\": \"Chapitre 3\", \"status\": \"not_published\", \"charCount\": 5, \"sortOrder\": 300, \"wordCount\": 1}, \"storyId\": 7}',2,NULL,NULL,NULL,NULL,'2025-12-25 20:45:07'),(182,'Chapter.Published','{\"chapter\": {\"id\": 21, \"slug\": \"chapitre-3-21\", \"title\": \"Chapitre 3\", \"status\": \"published\", \"charCount\": 5, \"sortOrder\": 300, \"wordCount\": 1}, \"storyId\": 7}',6,NULL,NULL,NULL,NULL,'2025-12-25 20:45:38'),(183,'Chapter.Updated','{\"after\": {\"id\": 21, \"slug\": \"chapitre-3-21\", \"title\": \"Chapitre 3\", \"status\": \"published\", \"charCount\": 5, \"sortOrder\": 300, \"wordCount\": 1}, \"before\": {\"id\": 21, \"slug\": \"chapitre-3-21\", \"title\": \"Chapitre 3\", \"status\": \"not_published\", \"charCount\": 5, \"sortOrder\": 300, \"wordCount\": 1}, \"storyId\": 7}',6,NULL,NULL,NULL,NULL,'2025-12-25 20:45:38'),(184,'Chapter.Deleted','{\"chapter\": {\"id\": 21, \"slug\": \"chapitre-3-21\", \"title\": \"Chapitre 3\", \"status\": \"published\", \"charCount\": 5, \"sortOrder\": 300, \"wordCount\": 1}, \"storyId\": 7}',6,NULL,NULL,NULL,NULL,'2025-12-25 20:45:42'),(185,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-12-26 09:32:28'),(186,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-12-26 14:28:34'),(187,'Auth.UserLoggedIn','{\"userId\": 6}',6,NULL,NULL,NULL,NULL,'2025-12-26 16:36:04'),(188,'Comment.Posted','{\"comment\": {\"is_reply\": false, \"author_id\": 6, \"entity_id\": 14, \"char_count\": 146, \"comment_id\": 28, \"word_count\": 31, \"entity_type\": \"chapter\", \"parent_comment_id\": null}}',6,NULL,NULL,NULL,NULL,'2025-12-27 15:25:56'),(189,'Comment.Posted','{\"comment\": {\"is_reply\": false, \"author_id\": 6, \"entity_id\": 6, \"char_count\": 170, \"comment_id\": 29, \"word_count\": 31, \"entity_type\": \"chapter\", \"parent_comment_id\": null}}',6,NULL,NULL,NULL,NULL,'2025-12-27 15:27:02'),(190,'Comment.Posted','{\"comment\": {\"is_reply\": false, \"author_id\": 6, \"entity_id\": 7, \"char_count\": 166, \"comment_id\": 30, \"word_count\": 30, \"entity_type\": \"chapter\", \"parent_comment_id\": null}}',6,NULL,NULL,NULL,NULL,'2025-12-27 15:27:38'),(191,'Auth.UserLoggedOut','{\"userId\": 6}',6,NULL,NULL,NULL,NULL,'2025-12-27 19:08:30'),(192,'Auth.UserLoggedIn','{\"userId\": 6}',6,NULL,NULL,NULL,NULL,'2025-12-27 19:32:31'),(193,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-12-28 15:12:48'),(194,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-12-28 19:05:19'),(195,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-12-29 06:27:49'),(196,'Auth.UserLoggedIn','{\"userId\": 6}',6,NULL,NULL,NULL,NULL,'2025-12-29 07:05:01'),(197,'ReadList.Added','{\"userId\": 6, \"storyId\": 2}',6,NULL,NULL,NULL,NULL,'2025-12-29 07:05:22'),(198,'Auth.UserLoggedIn','{\"userId\": 3}',3,NULL,NULL,NULL,NULL,'2025-12-29 07:23:48'),(199,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-12-30 12:20:19'),(200,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2026-01-03 10:50:28'),(201,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2026-01-11 10:03:12'),(202,'Profile.DisplayNameChanged','{\"userId\": 2, \"newDisplayName\": \"Tech Admin ou plus communément le Grand Jardinier\", \"oldDisplayName\": \"LogistiX le seigneur des loutres de la grande colline\"}',2,NULL,NULL,NULL,NULL,'2026-01-11 10:04:04'),(203,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2026-01-11 10:09:53'),(204,'Auth.EmailVerified','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2026-01-11 10:10:45'),(205,'Auth.UserDeleted','{\"userId\": 1}',2,NULL,NULL,NULL,NULL,'2026-01-11 10:14:07'),(206,'Auth.UserLoggedOut','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2026-01-11 10:29:52'),(207,'Config.ConfigParameterUpdated','{\"parameter\": {\"key\": \"require_activation_code\", \"type\": \"bool\", \"value\": false, \"domain\": \"auth\", \"previousValue\": true}}',2,NULL,NULL,NULL,NULL,'2026-01-11 10:30:31'),(208,'Auth.UserRegistered','{\"userId\": 8, \"displayName\": \"Émily\"}',NULL,NULL,NULL,NULL,NULL,'2026-01-11 10:30:56'),(209,'Auth.UserRoleGranted','{\"role\": \"user\", \"userId\": 8, \"actorUserId\": 8, \"targetIsAdmin\": false}',8,NULL,NULL,NULL,NULL,'2026-01-11 10:31:20'),(210,'Auth.EmailVerified','{\"userId\": 8}',8,NULL,NULL,NULL,NULL,'2026-01-11 10:31:20'),(211,'Auth.UserRoleRevoked','{\"role\": \"user\", \"userId\": 8, \"actorUserId\": 2, \"targetIsAdmin\": false}',2,NULL,NULL,NULL,NULL,'2026-01-11 10:31:51'),(212,'Auth.UserRoleGranted','{\"role\": \"user-confirmed\", \"userId\": 8, \"actorUserId\": 2, \"targetIsAdmin\": false}',2,NULL,NULL,NULL,NULL,'2026-01-11 10:31:51'),(213,'Auth.UserRoleGranted','{\"role\": \"moderator\", \"userId\": 8, \"actorUserId\": 2, \"targetIsAdmin\": false}',2,NULL,NULL,NULL,NULL,'2026-01-11 10:32:00'),(214,'Auth.UserRoleRevoked','{\"role\": \"user-confirmed\", \"userId\": 2, \"actorUserId\": 2, \"targetIsAdmin\": true}',2,NULL,NULL,NULL,NULL,'2026-01-11 10:33:51'),(215,'Auth.UserRoleGranted','{\"role\": \"user\", \"userId\": 2, \"actorUserId\": 2, \"targetIsAdmin\": true}',2,NULL,NULL,NULL,NULL,'2026-01-11 10:33:51'),(216,'Auth.EmailVerified','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2026-01-11 10:33:51'),(217,'Auth.UserLoggedOut','{\"userId\": 8}',8,NULL,NULL,NULL,NULL,'2026-01-11 10:34:39'),(218,'Auth.UserRegistered','{\"userId\": 9, \"displayName\": \"Gina\"}',NULL,NULL,NULL,NULL,NULL,'2026-01-11 10:34:57'),(219,'Auth.UserRoleGranted','{\"role\": \"user\", \"userId\": 9, \"actorUserId\": 9, \"targetIsAdmin\": false}',9,NULL,NULL,NULL,NULL,'2026-01-11 10:35:16'),(220,'Auth.EmailVerified','{\"userId\": 9}',9,NULL,NULL,NULL,NULL,'2026-01-11 10:35:16'),(221,'Auth.UserRoleRevoked','{\"role\": \"user\", \"userId\": 9, \"actorUserId\": 2, \"targetIsAdmin\": false}',2,NULL,NULL,NULL,NULL,'2026-01-11 10:36:25'),(222,'Auth.UserRoleGranted','{\"role\": \"user-confirmed\", \"userId\": 9, \"actorUserId\": 2, \"targetIsAdmin\": false}',2,NULL,NULL,NULL,NULL,'2026-01-11 10:36:25'),(223,'Auth.UserRoleGranted','{\"role\": \"admin\", \"userId\": 9, \"actorUserId\": 2, \"targetIsAdmin\": false}',2,NULL,NULL,NULL,NULL,'2026-01-11 10:36:35'),(224,'Auth.UserLoggedOut','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2026-01-11 10:39:37'),(225,'Auth.UserLoggedIn','{\"userId\": 4}',4,NULL,NULL,NULL,NULL,'2026-01-11 10:39:54'),(226,'Comment.Posted','{\"comment\": {\"is_reply\": false, \"author_id\": 4, \"entity_id\": 1, \"char_count\": 194, \"comment_id\": 31, \"word_count\": 37, \"entity_type\": \"chapter\", \"parent_comment_id\": null}}',4,NULL,NULL,NULL,NULL,'2026-01-11 10:41:04');
/*!40000 ALTER TABLE `events_domain` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `faq_categories`
--

DROP TABLE IF EXISTS `faq_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `faq_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `sort_order` int NOT NULL DEFAULT '999',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by_user_id` bigint unsigned NOT NULL,
  `updated_by_user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `faq_categories_slug_unique` (`slug`),
  KEY `faq_categories_slug_index` (`slug`),
  KEY `faq_categories_sort_order_index` (`sort_order`),
  KEY `faq_categories_is_active_index` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `faq_categories`
--

LOCK TABLES `faq_categories` WRITE;
/*!40000 ALTER TABLE `faq_categories` DISABLE KEYS */;
INSERT INTO `faq_categories` VALUES (1,'Compte','compte','Tout ce qui concerne votre compte sur le Jardin des Esperluettes',1,1,2,2,'2025-10-22 18:19:20','2025-10-22 18:50:15'),(2,'STATUTS','statuts','Pour comprendre qui est qui et qui fait quoi sur le site ',2,1,2,2,'2025-10-22 18:20:22','2025-10-22 18:50:15'),(4,'PROFIL','profil','Tout ce qui concerne les pages profil, le vôtre où celui d\'un autre membre',5,1,2,2,'2025-10-22 18:28:46','2025-10-22 18:51:37'),(5,'LECTURE','lecture','Pour savoir où trouver les histoires et comment les choisir',6,1,2,2,'2025-10-22 18:28:56','2025-10-22 18:52:41'),(6,'PUBLIER UN COMMENTAIRE','publier-un-commentaire','Pour pouvoir laisser des commentaires et aider les auteurices',7,1,2,2,'2025-10-22 18:29:10','2025-10-22 18:54:01'),(7,'PUBLIER UNE HISTOIRE','publier-une-histoire','Tout ce qu\'il y a à savoir pour mettre une histoire en ligne',8,1,2,2,'2025-10-22 18:29:20','2025-10-22 18:54:33'),(8,'PUBLIER UN CHAPITRE','publier-un-chapitre','Tout ce qu\'il y a à savoir pour ajouter un chapitre à une de ses histoires',9,1,2,2,'2025-10-22 18:29:31','2025-10-22 18:55:39'),(9,'MODÉRATION SIGNALEMENT','moderation-signalement','Tout ce qui concerne le respect du règlement ',10,1,2,2,'2025-10-22 18:29:42','2025-10-22 19:26:31'),(10,'MENUS ','menus','Pour savoir où aller',3,1,2,2,'2025-10-22 18:49:06','2025-10-22 18:50:15'),(11,'TABLEAU DE BORD','tableau-de-bord','Pour comprendre les éléments du tableau de bord',4,1,2,2,'2025-10-22 18:49:51','2025-10-22 18:50:15');
/*!40000 ALTER TABLE `faq_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `faq_questions`
--

DROP TABLE IF EXISTS `faq_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `faq_questions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `faq_category_id` bigint unsigned NOT NULL,
  `question` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `answer` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_alt_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '999',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by_user_id` bigint unsigned NOT NULL,
  `updated_by_user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `faq_questions_slug_unique` (`slug`),
  KEY `faq_questions_slug_index` (`slug`),
  KEY `faq_questions_faq_category_id_sort_order_index` (`faq_category_id`,`sort_order`),
  KEY `faq_questions_is_active_index` (`is_active`),
  CONSTRAINT `faq_questions_faq_category_id_foreign` FOREIGN KEY (`faq_category_id`) REFERENCES `faq_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `faq_questions`
--

LOCK TABLES `faq_questions` WRITE;
/*!40000 ALTER TABLE `faq_questions` DISABLE KEYS */;
INSERT INTO `faq_questions` VALUES (1,1,'J’ai perdu mon mot de passe, comment faire ?','jai-perdu-mon-mot-de-passe-comment-faire','<p>Sur la page de connexion, cliquez sur “mot de passe oublié ?”. Vous recevrez un email de validation pour réinitialiser votre mot de passe.</p>\n\n<p><br><br></p>',NULL,NULL,1,1,2,2,'2025-10-22 18:21:18','2025-10-22 18:24:38'),(2,1,'Je veux modifier mon mot de passe, comment faire ?','je-veux-modifier-mon-mot-de-passe-comment-faire','<p>Si vous n’êtes pas connecté·e, cliquez sur “mot de passe oublié ?” sur la page de connexion, ce qui vous permettra de réinitialiser votre mot de passe. </p>\n\n<p>Si vous êtes déjà connecté·e, rendez-vous dans “Gestion du compte” dans le menu déroulant qui s’affiche lorsque vous cliquez sur votre icône de profil dans la top bar (en haut à droite). </p>\n\n<p><br><br></p>','faq/2025/10/01K86SM9HCD0HEWNZFBTYW2CVV.png',NULL,2,1,2,2,'2025-10-22 18:22:04','2025-10-22 18:26:37'),(3,2,'À quoi correspondent les différents statuts (Majuscules, Cadratins, Arobases, Esperluettes, Graines d’Esperluettes) ?','a-quoi-correspondent-les-differents-statuts-majuscules-cadratins-arobases-esperluettes-graines-desperluettes','<p>Les <strong>Majuscules</strong> sont nos administrateurices, et les créateurices officiel·le·s du Jardin des Esperluettes. Ce sont les Majuscules qui prennent les décisions importantes ici, et grâce à qui nous somme là, mais elles sont aussi des membres de la communauté comme vous, qui écrivent, lisent, commentent, et elles sont adorables, promis (sauf les jeudi matin entre 9h12 et 9h13).</p>\n\n<p>Nos <strong>Cadratins</strong> sont les modérateurices. Il y en a trois pour le Jardin (le site) et trois pour le Manoir (le serveur Discord associé). Vous pouvez les repérer par la mention “Cadratin” sur leur profil, et leur pseudonyme apparaît en doré sur Discord. Leur rôle est de veiller à ce que les règles de la communauté soient respectées. Vous pourrez aussi les retrouver dans l’organisation d’évènements, et vous adresser à eux si vous avez des questions ou rencontrez des difficultés avec le site.</p>\n\n<p>Les <strong>Arobases</strong> sont nos developpeurs, qui codent le site, posent les pierres et entretiennent notre beau jardin. Notre Arobase en chef, LogistiX, est le créateur original du site depuis 2025.</p>\n\n<p>Les <strong>Esperluettes</strong>, aussi abrégé en “&amp;”… c’est nous ! Tout membre de la communauté, après avoir été officiellement validé, est une Esperluette à part entière. Être une &amp;, c’est simplement s’être installé·e dans le Jardin.</p>\n\n<p>Les <strong>Graines d’Esperluettes</strong> sont nos &amp; en devenir, elles n’ont pas encore été officiellement validées par la modération pour passer Esperluettes.</p>\n\n<p><br><br></p>',NULL,NULL,3,1,2,2,'2025-10-22 18:23:00','2025-10-22 18:24:38'),(4,1,'Je veux modifier mon pseudonyme, comment faire ?','je-veux-modifier-mon-pseudonyme-comment-faire','<p>Allez sur votre page de profil et cliquez sur le bouton “modifier le profil” (un petit crayon dans un rond). </p>\n\n<p>Il est recommandé de ne pas trop souvent changer de pseudonyme sur le Jardin afin de continuer à identifier sans difficulté les Esperluettes. </p>\n\n<p>Si vous êtes également sur le Discord, il vous sera demandé d’accorder votre pseudonyme sur le serveur (modifier le profil &gt; modifier le profil par serveur) pour que l’on sache qui vous êtes aussi bien quand vous vous promenez dans le Jardin, que quand vous discutez confortablement dans le Manoir !</p>\n\n<p><br><br></p>',NULL,NULL,3,1,2,2,'2025-10-22 19:00:08','2025-10-22 19:00:08'),(5,1,'Si je supprime mon compte, est-ce que mes histoires vont bien disparaître du site ?','si-je-supprime-mon-compte-est-ce-que-mes-histoires-vont-bien-disparaitre-du-site','<p>Oui, si vous supprimez votre compte, vos plantations ne seront plus dans le Jardin.</p>\n\n<p><br><br></p>',NULL,NULL,4,1,2,2,'2025-10-22 19:01:04','2025-10-22 19:01:04'),(6,1,'Si je supprime mon compte, est-ce que mes commentaires vont disparaître du site ? ','si-je-supprime-mon-compte-est-ce-que-mes-commentaires-vont-disparaitre-du-site','<p>Les commentaires que vous avez laissés sur les histoires des autres Esperluettes seront encore là, pour éviter de devoir supprimer les autres commentaires-réponses, mais ils seront anonymisés.</p>\n\n<p><br><br></p>',NULL,NULL,5,1,2,2,'2025-10-22 19:01:33','2025-10-22 19:01:33'),(7,1,'Pourquoi je ne reçois pas mon mail d’activation après l’inscription ?','pourquoi-je-ne-recois-pas-mon-mail-dactivation-apres-linscription','<p>Le mail d’activation peut mettre un certain temps à arriver. S’il vous ne l’avez pas reçu au bout de quelques minutes, vérifiez dans vos spams. Assurez-vous également que vous avez renseigné la bonne adresse mail, sans faute de frappe.</p>\n\n<p><br><br></p>',NULL,NULL,6,1,2,2,'2025-10-22 19:02:10','2025-10-22 19:02:10'),(8,2,'Quelle est la différence entre le statut Esperluette et celui de Graine d’Esperluette ?','quelle-est-la-difference-entre-le-statut-esperluette-et-celui-de-graine-desperluette','<p>Les Graines d’Esperluettes peuvent lire et commenter les histoires du site, mais n’ont pas encore la possibilité d’en poster, et n’ont pas tous les accès au serveur Discord. Si vous êtes une graine et que cette restriction vous frustre, pas d’inquiétude ! Devenir une &amp; n’est pas un parcours du combattant, cela prend seulement quelques étapes que vous retrouverez dans la question suivante.</p>',NULL,NULL,2,1,2,8,'2025-10-22 19:03:44','2025-10-22 20:16:35'),(9,2,'Comment passe-t-on de Graine d’Esperluette à Esperluette ?','comment-passe-t-on-de-graine-desperluette-a-esperluette','<p>Pour devenir officiellement une Esperluette, il faudra avoir posté au moins cinq commentaires (respectueux du règlement) sur les histoires que vous voulez. Suite à cela, vous pourrez faire la demande de devenir une Esperluette à part entière. Lorsque cette demande sera validée, voilà ! Vous êtes définitivement installé·e au Jardin (et vous n’en partirez jamais).</p>\n\n<p><br><br></p>',NULL,NULL,3,1,2,2,'2025-10-22 19:04:12','2025-10-22 19:04:12'),(10,2,'Y a-t-il des Esperluettes qui n’ont jamais été Graines ?','y-a-t-il-des-esperluettes-qui-nont-jamais-ete-graines','<p>Oui ! A l’ouverture du site, lorsque que tous les réfugiés de la communauté Plume d’Argent ont posé leurs valises sur le Jardin, il a bien fallu qu’on puisse poster des histoires ! Donc toutes les &amp; arrivées à cette époque ne sont pas passées par le statut de graines. C’est aussi le cas de chaque &amp; qui a été invitée par une ancienne Esperluette, nous estimons alors cette “cooptation” comme un gage de confiance suffisant.</p>',NULL,NULL,4,1,2,2,'2025-10-22 19:05:35','2025-10-22 19:05:35'),(11,10,'Y a-t-il des menus/liens qui ne sont pas visibles ? (sur PC, sur téléphone)','y-a-t-il-des-menusliens-qui-ne-sont-pas-visibles-sur-pc-sur-telephone','<p>Tous les liens et menus du site sont accessibles aussi bien sur votre ordinateur que sur votre téléphone.</p>\n\n<p><br><br></p>',NULL,NULL,1,1,2,2,'2025-10-22 19:06:03','2025-10-22 19:06:03'),(12,10,'Le plus souvent, comment puis-je comprendre les éléments du site ?','le-plus-souvent-comment-puis-je-comprendre-les-elements-du-site','<p>Si vous vous demandez à quoi sert un bouton ou un élément du site, regardez s’il y a un petit symbole “information” (point d’interrogation dans un rond). Passez le pointeur de la souris dessus (sur ordinateur) ou cliquez dessus (sur mobile), et vous verrez apparaître une petite bulle d’information expliquant son utilité.</p>\n\n<p><br><br></p>',NULL,NULL,2,1,2,2,'2025-10-22 19:07:05','2025-10-22 19:07:05'),(13,11,'Quelle histoire s’affiche dans “continuer à lire” ? Pourquoi je n’ai pas d’histoire dans “continuer à lire” ?','quelle-histoire-saffiche-dans-continuer-a-lire-pourquoi-je-nai-pas-dhistoire-dans-continuer-a-lire','<p>Dans cet encadré, vous verrez s’afficher la dernière histoire que vous avez lues et dont il vous reste encore des chapitres à lire. Si vous n’avez aucune histoire affichée à cet endroit, c’est soit parce que vous n’avez encore marqué aucun chapitre comme lu dans le Jardin, soit parce que vous avez terminé toutes vos lectures.</p>\n\n<p><br></p>',NULL,NULL,1,1,2,2,'2025-10-22 19:07:52','2025-10-22 19:07:52'),(14,11,'Quelle histoire s’affiche dans “continuer à écrire” ? Pourquoi je n’ai pas d’histoire dans “continuer à écrire” ?','quelle-histoire-saffiche-dans-continuer-a-ecrire-pourquoi-je-nai-pas-dhistoire-dans-continuer-a-ecrire','<p>Dans cet encadré, vous verrez s’afficher la dernière histoire que vous avez créée ou modifiée. Si vous n’avez aucune histoire affichée à cet endroit, cela signifie que vous n’avez publié aucune histoire sur le Jardin ou qu’elles sont toutes marquées terminées.</p>',NULL,NULL,2,1,2,2,'2025-10-22 19:08:20','2025-10-22 19:08:20'),(15,4,'Comment accéder à son profil (sur PC/sur téléphone) ?','comment-acceder-a-son-profil-sur-pcsur-telephone','<p>Sur ordinateur, cliquez sur votre photo de profil en haut à droite de l’écran pour afficher le menu déroulant. Cliquez sur “profil”. Idem sur téléphone.</p>\n\n<p><br><br></p>',NULL,NULL,1,1,2,2,'2025-10-22 19:09:02','2025-10-22 19:09:02'),(16,4,'Pourquoi l’image que je veux mettre comme image de profil ne se charge pas ?','pourquoi-limage-que-je-veux-mettre-comme-image-de-profil-ne-se-charge-pas','<p>Votre image est probablement trop lourde ou ne correspond pas aux dimensions demandées. Veillez à ce que le fichier pèse moins de 2 Go et mesure plus de 100x100 pixels.</p>\n\n<p><br><br></p>',NULL,NULL,2,1,2,2,'2025-10-22 19:10:14','2025-10-22 19:10:14'),(17,4,'Que faut-il mettre dans sa présentation ?','que-faut-il-mettre-dans-sa-presentation','<p>Votre présentation est votre espace à vous, pour vous présenter auprès du reste de la communauté ! Vous pouvez simplement dire qui vous êtes, ce qui vous a attirée sur le JdE, ce que vous aimez écrire, lire… libre à vous, dans le respect des règles de la communauté !</p>\n\n<p><br><br></p>',NULL,NULL,3,1,2,2,'2025-10-22 19:10:38','2025-10-22 19:10:38'),(18,4,'Est-ce que je peux mettre un lien vers mon site internet ? ','est-ce-que-je-peux-mettre-un-lien-vers-mon-site-internet','<p>Non, vous ne pouvez actuellement pas mettre de lien vers votre site internet, mais vous pouvez mettre ceux vers vos réseaux sociaux.</p>\n\n<p><br><br></p>',NULL,NULL,4,1,2,2,'2025-10-22 19:11:05','2025-10-22 19:11:05'),(19,4,'Comment sont classées les histoires dans l’onglet Mes histoires ?','comment-sont-classees-les-histoires-dans-longlet-mes-histoires','<p>Vos histoires sont classées par visibilité, puis par date de dernière modification, en commençant par la plus récente.</p>',NULL,NULL,5,1,2,2,'2025-10-22 19:11:31','2025-10-22 19:11:31'),(20,4,'Est-ce que les autres utilisateurs voient la même chose que moi quand ils consultent mon profil ? ','est-ce-que-les-autres-utilisateurs-voient-la-meme-chose-que-moi-quand-ils-consultent-mon-profil','<p>Vous voyez quelques éléments que les autres n’ont pas sur votre profil ! En particulier, les boutons “modifier”, “copier le lien” et “Discord” à droite de votre profil, vos histoires en visibilité privée, et le bouton “nouvelle histoire”. Les autres &amp; ont par ailleurs la possibilité de signaler votre profil. Sinon, pas de différence.</p>',NULL,NULL,6,1,2,2,'2025-10-22 19:12:07','2025-10-22 19:12:07'),(21,4,'À quoi sert le bouton avec l’icône Discord et le lien ?','a-quoi-sert-le-bouton-avec-licone-discord-et-le-lien','<p>Le bouton Discord sert à relier votre compte sur le site au serveur Discord (aussi dit Manoir) de la communauté, c’est le seul moyen pour vous d’accéder à tous les salons du serveur. Pour ce faire, vous devrez cliquer dessus, et copier le code qui vous sera donné. Vous renseignerez ensuite ce code au bot du serveur, Hestia, dans le salon “conciergerie.” Vous pourrez couper ce lien quand vous le souhaitez à partir du même bouton. Si vous ne souhaitez pas rejoindre le Manoir, ce bouton ne vous est pas utile.</p>\n\n<p>Vous pouvez copier le lien vers votre profil grâce au bouton dédié, ce qui vous permettra de le coller dans votre présentation sur le Manoir.</p>\n\n<p><br><br></p>',NULL,NULL,7,1,2,2,'2025-10-22 19:14:43','2025-10-22 19:14:43'),(22,4,'Qu’est-ce que c’est qu’un crédit de chapitre ? Comment est-ce calculé ?','quest-ce-que-cest-quun-credit-de-chapitre-comment-est-ce-calcule','<p>Un crédit de chapitre est ce qui vous permet de poster un chapitre sur le Jardin (un chapitre d’une nouvelle histoire ne compte pas plus de crédits qu’un chapitre d’une histoire déjà commencée.) </p>\n\n<p>Pour gagner un crédit de chapitre, il suffit de poster un commentaire sur l’histoire de votre choix dans le respect des valeurs de la communauté.<br><br></p>',NULL,NULL,8,1,2,2,'2025-10-22 19:15:45','2025-10-22 19:15:45'),(23,4,'Pourquoi mon crédit de chapitres n’est pas égal au nombre de commentaires affiché dans le tableau de bord ?','pourquoi-mon-credit-de-chapitres-nest-pas-egal-au-nombre-de-commentaires-affiche-dans-le-tableau-de-bord','<p>Si vous avez moins de crédits que de commentaires postés, c’est normal ! C’est parce que vous avez probablement déjà utilisé une partie de vos crédits pour poster vos histoires.</p>\n\n<p><br><br></p>',NULL,NULL,9,1,2,2,'2025-10-22 19:16:45','2025-10-22 19:16:45'),(24,4,'Qu’est-ce qui se passe si je supprime un chapitre ou une histoire que j’ai écrit ? Et si quelqu’un supprime un chapitre ou une histoire que j’ai commentée ? ','quest-ce-qui-se-passe-si-je-supprime-un-chapitre-ou-une-histoire-que-jai-ecrit-et-si-quelquun-supprime-un-chapitre-ou-une-histoire-que-jai-commentee','<p>Si vous supprimez une histoire ou un chapitre, vous ne récupérez pas de crédit de chapitre. </p>\n\n<p>Si quelqu\'un supprime un chapitre que vous aviez commenté, vous ne perdez pas de crédit de chapitre, malgré la disparition du commentaire en question. </p>\n\n<p><br><br></p>',NULL,NULL,10,1,2,2,'2025-10-22 19:20:36','2025-10-22 19:20:36'),(25,5,'Où trouver les histoires à lire ?','ou-trouver-les-histoires-a-lire','<p>Envie de découvrir les plus belles plantations du Jardin ? Rendez-vous dans la bibliothèque pour y trouver votre bonheur !</p>\n\n<p>Les histoires les plus récentes vous sont aussi présentées sur votre tableau de bord. Et vous pouvez trouver les histoires de chaque membre dans son profil/histoires.</p>\n\n<p><br><br></p>',NULL,NULL,1,1,2,2,'2025-10-22 19:29:33','2025-10-22 19:29:33'),(26,5,'Comment choisir une histoire ? (Filtres, résumés…)','comment-choisir-une-histoire-filtres-resumes','<p>Le Jardin met en avant les dernières histoires plantées. Vous pouvez consulter les résumés afin de dénicher LE texte qui vous fera vibrer. Nous avons également un système de filtres qui vous permet de trier les histoires de la bibliothèque (par genre, par tranche d’âge etc.) et d’en exclure certaines si vous ne désirez pas lire certains contenus.</p>\n\n<p><br><br></p>',NULL,NULL,2,1,2,2,'2025-10-22 19:30:34','2025-10-22 19:30:34'),(27,5,'Comment trouver une histoire en particulier ? (Zone recherche)','comment-trouver-une-histoire-en-particulier-zone-recherche','<p>Une esperluette vous a parlé de cette histoire trop trop bien mais vous ne la trouvez pas dans les premières histoires ? Il vous suffit d’utiliser la zone de recherche. Avec le titre ou le nom de son auteurice, ce texte fabuleux sera sous vos yeux en un rien de temps !</p>\n\n<p><br><br></p>',NULL,NULL,3,1,2,2,'2025-10-22 19:30:55','2025-10-22 19:30:55'),(28,5,'Si je trouve une histoire qui me plaît mais que je veux la lire plus tard, comment faire ?','si-je-trouve-une-histoire-qui-me-plait-mais-que-je-veux-la-lire-plus-tard-comment-faire','<p>Il vous suffit de l’ajouter dans votre Pile à lire (PAL) - attention, ces petites choses ont tendance à devenir très dodues rapidement ! </p>\n\n<p><br>ATTENTION : Cette fonctionnalité n\'est pas encore active, mais le sera très très bientôt</p>',NULL,NULL,4,1,2,2,'2025-10-22 19:32:30','2025-10-22 19:32:30'),(29,5,'Pourquoi certaines histoires ont la même couverture ?','pourquoi-certaines-histoires-ont-la-meme-couverture','<p>Parce que le Jardin offre aux personnes qui en ont besoin la possibilité d’avoir une couverture de base selon le genre littéraire. Ces couvertures ont été réalisées par Artichaut et Itchane, merci à elleux ! </p>',NULL,NULL,5,1,2,2,'2025-10-22 19:33:20','2025-10-22 19:33:20'),(30,5,'À quoi correspondent les différents pictogrammes et badges à côté des histoires ? ','a-quoi-correspondent-les-differents-pictogrammes-et-badges-a-cote-des-histoires','<p>Vous retrouverez, à droite de la couverture et en dessous du nom de l’auteurice (de haut en bas) : </p>\n\n<ul><li>Le(s) genre(s) littéraire(s) d’une histoire.</li><li>Le nombre de vue (représenté par un œil) ainsi que la longueur de l’histoire (il vous suffit de passer sur le pictogramme pour en savoir plus) </li><li>Les avertissements de contenus (soyez attentifs à eux si vous ne voulez pas lire quelque chose en particulier !) </li><li>Le type d’histoire (roman, nouvelle, <em>etc</em>), </li><li>l’âge minimum pour lire l’histoire - si cela à lieu d’être), </li><li>les copyrights afin de protéger les auteurices du Jardin, </li><li>le statut d’avancement de l’histoire </li><li>et enfin le type de retour attendu. </li></ul>',NULL,NULL,6,1,2,2,'2025-10-22 19:35:06','2025-10-22 19:35:06'),(31,5,'Est-ce qu’on est obligé·e d’aller sur la page de l’histoire pour lire son résumé ?','est-ce-quon-est-obligee-daller-sur-la-page-de-lhistoire-pour-lire-son-resume','<p>Non ! Depuis la bibliothèque il vous suffit de survoler sur le petit “i” juste à côté du titre d’une histoire pour lire son résumé. Tout comme il vous suffit de survoler le petit panneau attention pour consulter les avertissements de contenus. </p>\n\n<p><br><br></p>',NULL,NULL,7,1,2,2,'2025-10-22 19:35:41','2025-10-22 19:35:41'),(32,5,' Comment sont classées les histoires dans la bibliothèque ?','comment-sont-classees-les-histoires-dans-la-bibliotheque','<p>Dernière histoire plantée ou mise à jour, première histoire affichée, tout simplement !</p>\n\n<p><br><br></p>\n\n<p><br><br></p>',NULL,NULL,8,1,2,2,'2025-10-22 19:37:16','2025-10-22 19:38:22'),(33,5,'C’est quoi le drapeau bleu / le bouton “signaler” ?','cest-quoi-le-drapeau-bleu-le-bouton-signaler','<p>Il s’agit d’un bouton à utiliser en cas de pépin afin de nous faire remonter un comportement ou du contenu inapproprié, conformément au règlement du Jardin (voir la section Modération et signalement pour plus de précisions).</p>\n\n<p><br><br></p>',NULL,NULL,9,1,2,2,'2025-10-22 19:39:17','2025-10-22 19:39:17'),(34,5,'Comment puis-je me rappeler que j’ai déjà lu cette histoire ou ce chapitre ?','comment-puis-je-me-rappeler-que-jai-deja-lu-cette-histoire-ou-ce-chapitre','<p>C’est très simple, il vous suffit de cocher le bouton “marquer comme lu” et le site se souviendra de votre progression.</p>\n\n<p><br><br></p>',NULL,NULL,10,1,2,2,'2025-10-22 19:39:40','2025-10-22 19:39:40'),(35,5,'Pourquoi l’espace de lecture du chapitre est-il limité au milieu de la page sur ordinateur ? ','pourquoi-lespace-de-lecture-du-chapitre-est-il-limite-au-milieu-de-la-page-sur-ordinateur','<p>Nous avons fait ce choix pour le confort de lecture de toustes et pour que le site puisse être également utilisé sur mobile.</p>',NULL,NULL,11,1,2,2,'2025-10-22 19:40:54','2025-10-22 19:40:54'),(36,6,'Pourquoi y a-t-il un nombre de caractères minimum pour pouvoir valider mon commentaire ?','pourquoi-y-a-t-il-un-nombre-de-caracteres-minimum-pour-pouvoir-valider-mon-commentaire','<p>C’est une garantie parmi d’autres de la qualité des retours sur les textes, parce que l’esprit d’entraide, ça fait bien entendu partie du terreau du Jardin ! </p>\n\n<p>Pour rappel, le minimum est fixé à 140 caractères, ce qui correspond à 25 mots… et cette réponse est deux fois plus longue.</p>',NULL,NULL,1,1,2,2,'2025-10-22 19:42:06','2025-10-22 19:42:06'),(37,6,'À quoi servent les boutons “répondre” sous les commentaires ?','a-quoi-servent-les-boutons-repondre-sous-les-commentaires','<p>Ils permettent à l’auteurice de répondre aux commentaires laissés sur son texte, mais également à toute esperluette qui souhaiterait réagir au commentaire d’une autre &amp; ! Eh oui, dans le Jardin, on partage parcelles et tuteurs pour mieux s’épanouir toustes ensemble.</p>\n\n<p><br><br></p>',NULL,NULL,2,1,2,2,'2025-10-22 19:42:53','2025-10-22 19:42:53'),(38,6,'Tous les types de commentaires ajoutent-ils +1 à mon compteur de commentaire et à mes crédits ?','tous-les-types-de-commentaires-ajoutent-ils-1-a-mon-compteur-de-commentaire-et-a-mes-credits','<p>Non, seuls les commentaires racine comptent : ce sont les commentaires que vous postez sans cliquer sur le bouton “répondre” sous le commentaire d’une autre esperluette.</p>\n\n<p><br><br></p>',NULL,NULL,3,1,2,2,'2025-10-22 19:43:38','2025-10-22 19:43:38'),(39,6,'Qu’est-ce qu’on doit mettre dans un commentaire ? Ou ne pas mettre ? ','quest-ce-quon-doit-mettre-dans-un-commentaire-ou-ne-pas-mettre','<p>Nous vous invitons à consulter d’une part le règlement si vous ne l’avez pas déjà fait, et d’autre part le guide <strong>Cultiver un Commentaire</strong> pour vous donner des pistes sur la rédaction d’un commentaire.</p>\n\n<p><br><br></p>',NULL,NULL,4,1,2,2,'2025-10-22 19:44:55','2025-10-22 19:44:55'),(40,6,'Est-ce que je peux modifier un de mes commentaires ?','est-ce-que-je-peux-modifier-un-de-mes-commentaires','<p>Oui ! Vous trouverez l’icône crayon à droite de votre pseudo pour éditer votre commentaire</p>',NULL,NULL,5,1,2,2,'2025-10-22 19:45:19','2025-10-22 19:45:19'),(41,6,'Est-ce que je peux supprimer un de mes commentaires ?','est-ce-que-je-peux-supprimer-un-de-mes-commentaires','<p>Non : cela permet de conserver des comptes fiables pour le nombre de commentaires et les crédits de chapitres, en plus de ne pas risquer de supprimer les commentaires-réponses des autres esperluettes. Vous pouvez toutefois éditer vos commentaires (voir la question précédente).</p>\n\n<p><br><br></p>',NULL,NULL,6,1,2,2,'2025-10-22 19:45:56','2025-10-22 19:45:56'),(42,7,'À quel endroit dois-je aller pour publier une histoire ? ','a-quel-endroit-dois-je-aller-pour-publier-une-histoire','<p>Vous pouvez aller directement sur la page “Mes histoires” depuis la top bar, ou via l’onglet éponyme sur la page “Profil”.</p>',NULL,NULL,1,1,2,2,'2025-10-22 19:46:24','2025-10-22 19:46:24'),(43,7,'Pourquoi je ne peux pas publier d’histoire ?','pourquoi-je-ne-peux-pas-publier-dhistoire','<p>Soit vous n’avez pas du tout de compte (pourquoi ? rejoignez-nous !), soit vous êtes encore une graine d’esperluette. Si vous ne savez pas de quoi il retourne, nous vous invitons à consulter les questions correspondantes dans la section “STATUTS”.</p>',NULL,NULL,2,1,2,2,'2025-10-22 19:47:12','2025-10-22 19:47:12'),(44,7,'Qu’est-ce que je dois faire si je ne trouve pas exactement le type ou le genre de mon histoire dans les listes déroulantes ?','quest-ce-que-je-dois-faire-si-je-ne-trouve-pas-exactement-le-type-ou-le-genre-de-mon-histoire-dans-les-listes-deroulantes','<p>Vous devez sélectionner le type ou les genres qui s’appliquent le mieux à votre texte ou, à défaut, utiliser l’option “Autres” (pour les genres). S’il vous semble vraiment pertinent d’ajouter une option, vous pouvez le proposer à l’équipe.</p>\n\n<p><br><br></p>',NULL,NULL,3,1,2,2,'2025-10-22 19:49:16','2025-10-22 19:49:16'),(45,7,'C’est quoi, la visibilité ?','cest-quoi-la-visibilite','<p>Cela correspond au public qui aura accès à vos textes : tout le monde, y compris des personnes non loggées, avec la visibilité “Publique” ; seulement les esperluettes, avec l’option “Communauté” ; uniquement vous, l’auteurice du texte, avec la visibilité “Privée”.</p>',NULL,NULL,4,1,2,2,'2025-10-22 19:50:07','2025-10-22 19:50:07'),(46,7,'Que sont les avertissements de contenu ? Est-ce que je suis obligé·e d’en mettre ?','que-sont-les-avertissements-de-contenu-est-ce-que-je-suis-obligee-den-mettre','<p>Ils permettent d’avertir les esperluettes si vos textes abordent des sujets potentiellement choquants (violence, relations abusives, <em>etc</em>). Si ce n’est pas le cas, sélectionnez “Aucun”. Sinon, vous pouvez soit les renseigner aussi fidèlement que possible, soit choisir l’option “Non dévoilés” si pour une raison ou une autre vous ne voulez pas les divulguer. Notez que mentir sciemment sur les avertissements de contenu sera sanctionné. </p>\n\n<p><br><br></p>',NULL,NULL,5,1,2,2,'2025-10-22 19:51:13','2025-10-22 19:51:13'),(47,7,'Et si je ne trouve pas l’avertissement correspondant à mon histoire, dans la liste déroulante, comment dois-je faire ?','et-si-je-ne-trouve-pas-lavertissement-correspondant-a-mon-histoire-dans-la-liste-deroulante-comment-dois-je-faire','<p>Vous pouvez avoir recours à l’option “Non dévoilés”, ou simplement sélectionner l’avertissement le plus similaire dans la liste déroulante. Vous pouvez également donner des détails supplémentaires dans les Notes de l’esperluette au début du texte ou du chapitre concerné.</p>\n\n<p><br><br></p>',NULL,NULL,6,1,2,2,'2025-10-22 19:51:56','2025-10-22 19:51:56'),(48,7,'À quoi servent les indications de retour souhaité ?','a-quoi-servent-les-indications-de-retour-souhaite','<p>Elles vous permettent de signaler aux autres esperluettes quel type de retour vous attendez sur vos textes : par exemple, si vous allez tout réécrire incessamment, vous n’avez pas besoin que quelqu’un vous fasse un relevé exhaustif des fautes de frappe.</p>\n\n<p>Pour plus de précisions sur la signification de chaque option, nous vous renvoyons au guide <strong>Cultiver un Commentaire</strong></p>',NULL,NULL,7,1,2,2,'2025-10-22 19:53:12','2025-10-22 19:53:12'),(49,7,'À quel moment pourrai-je publier les chapitres de mon histoire ?','a-quel-moment-pourrai-je-publier-les-chapitres-de-mon-histoire','<p>Une fois votre histoire créée (titre, résumé, avertissements de contenu, et autres champs à remplir), vous pourrez y ajouter des chapitres.</p>\n\n<p>Vous aurez cinq crédits de chapitre disponibles pour commencer à mettre en ligne vos textes et, au-delà, il vous faudra observer la règle de “1 chapitre = 1 commentaire” (voir la question correspondante de la section “Publier un chapitre”).</p>\n\n<p><br><br></p>',NULL,NULL,8,1,2,2,'2025-10-22 19:54:11','2025-10-22 19:54:11'),(50,7,'Est-ce que je peux mettre une histoire sur le site et être seul·e à la voir, comme un brouillon qui sera (re)publié plus tard par exemple ?','est-ce-que-je-peux-mettre-une-histoire-sur-le-site-et-etre-seule-a-la-voir-comme-un-brouillon-qui-sera-republie-plus-tard-par-exemple','<p>Oui ! Pour cela, vous devez renseigner la visibilité comme “Privée” sur la page de création ou d’édition de l’histoire. Vous pouvez également mettre seulement certains chapitres en mode “privé” (voir la section suivante).</p>\n\n<p><br><br></p>',NULL,NULL,9,1,2,2,'2025-10-22 19:54:50','2025-10-22 19:54:50'),(51,7,'Est-ce que je peux mettre une couverture de mon choix à mon histoire ?','est-ce-que-je-peux-mettre-une-couverture-de-mon-choix-a-mon-histoire','<p>Pas encore, mais le Jardin continue de pousser !</p>\n\n<p><br><br></p>',NULL,NULL,10,1,2,2,'2025-10-22 19:55:17','2025-10-22 19:55:17'),(52,7,' Est-ce que je peux changer de couverture parmi les couvertures du site ?','est-ce-que-je-peux-changer-de-couverture-parmi-les-couvertures-du-site','<p>Non, elles seront assignées automatiquement par rapport à un des genres que vous avez sélectionnés pour votre texte.</p>',NULL,NULL,11,1,2,2,'2025-10-22 19:55:58','2025-10-22 19:55:58'),(53,7,'Est-ce que je peux déterminer l’ordre d’affichage des genres que j’ai sélectionnés ?','est-ce-que-je-peux-determiner-lordre-daffichage-des-genres-que-jai-selectionnes','<p>Non, l’ordre des genres est déterminé par leur longueur pour des raisons de mise en page du site ; sinon, nos plantations dépasseraient des parcelles !</p>',NULL,NULL,12,1,2,2,'2025-10-22 19:56:30','2025-10-22 19:56:30'),(54,8,'Pourquoi je ne peux pas publier de chapitre ?','pourquoi-je-ne-peux-pas-publier-de-chapitre','<p>Êtes-vous bien une Esperluette et non pas une Graine d’Esperluette ? Ma foi, c’est que l’équilibre entre vos commentaires et les chapitres que vous avez postés est dans le négatif. Vous pouvez voir votre crédit dans votre profil, juste à côté du bouton “nouvelle histoire”.</p>\n\n<p>Le Jardin fonctionne sur un système très simple : 1 commentaire = un crédit pour publier un chapitre.</p>\n\n<p>Cependant, faites attention : les Majuscules ainsi que les Cadratins veillent et sévissent en cas de “faux commentaire”.<br><br></p>',NULL,NULL,1,1,2,2,'2025-10-22 19:58:03','2025-10-22 19:58:03'),(55,8,'Si le bouton coulissant est sur “publié” quand je valide mon chapitre, mais que mon histoire est en privé, que se passe-t-il ? ','si-le-bouton-coulissant-est-sur-publie-quand-je-valide-mon-chapitre-mais-que-mon-histoire-est-en-prive-que-se-passe-t-il','<p>Votre chapitre ne sera visible que par vous et vos co-auteurices tant que l\'histoire est en \"privé\". Quand vous modifierez la visibilité de l\'histoire, le chapitre sera visible lui aussi.</p>\n\n<p>En terme de crédit, tout chapitre publié sera déduit de vos crédits, qu\'il soit \"publié\" ou non. </p>\n\n<p>N’hésitez pas à modifier à votre convenance la visibilité de votre histoire (publique, privée ou communauté) au moment de sa création. Vous pouvez changer à tout moment ce paramètre en éditant votre histoire. </p>\n\n<p><br><br></p>',NULL,NULL,2,1,2,2,'2025-10-22 20:04:47','2025-10-22 20:04:47'),(56,8,'Est-ce que je peux mettre des liens hypertextes dans mes chapitres ?','est-ce-que-je-peux-mettre-des-liens-hypertextes-dans-mes-chapitres','<p>Comme indiqué dans le règlement, il est interdit d’ajouter à vos chapitres des liens hypertextes externes (ne pointant pas vers une autre page du Jardin).</p>',NULL,NULL,3,1,2,2,'2025-10-22 20:06:14','2025-10-22 20:06:14'),(57,8,'Quelqu’un m’a laissé un commentaire, qu’est-ce que je dois faire ?','quelquun-ma-laisse-un-commentaire-quest-ce-que-je-dois-faire','<p>Mais quelle bonne nouvelle ! Ça veut dire que cette personne a lu mais a également pris le temps de vous faire un retour. Bien que nous n\'imposions pas aux auteurices de répondre aux commentaires, la politesse veut que vous essayez de le faire. Même un simple “merci” est important.<br>Dans le Jardin, on encourage à cultiver ses liens avec autrui autant que ses histoires !</p>\n\n<p><br><br></p>',NULL,NULL,4,1,2,2,'2025-10-22 20:07:22','2025-10-22 20:07:22'),(58,9,'Que dois-je faire quand je trouve un contenu non conforme au règlement (lien) ou qui me pose problème ?','que-dois-je-faire-quand-je-trouve-un-contenu-non-conforme-au-reglement-lien-ou-qui-me-pose-probleme','<p>Tout d’abord, nous sommes désolé·e·s si cela vous arrive. En cas de besoin, vous pouvez utiliser l’option de signalement. Essayez de nous fournir l’explication la plus précise possible à votre problème et nous ferons au plus vite pour y remédier.</p>\n\n<p>Attention cependant à ne pas faire des signalements abusifs.</p>\n\n<p><br><br></p>',NULL,NULL,1,1,2,2,'2025-10-22 20:08:13','2025-10-22 20:08:13'),(59,9,'Pourquoi la couverture de mon histoire a été remplacée par une illustration avec un panneau ?','pourquoi-la-couverture-de-mon-histoire-a-ete-remplacee-par-une-illustration-avec-un-panneau','<p>C’est tout simplement parce que la couverture de votre histoire n’est pas conforme au règlement du Jardin, ce qui veut dire que soit : elle affiche quelque chose qui a été jugé inapproprié, soit elle utilise en partie ou en totalité de l’IA, soit il s’agit d’une image non-libre de droit. Vous trouverez le motif du remplacement sur le panneau et nous vous demandons de faire le nécessaire au plus vite. </p>\n\n<p>Notez qu’il existe des couvertures pré-établies dans le Jardin et elles sont très très belles et garanties conformes.</p>\n\n<p><br><br></p>',NULL,NULL,2,1,2,2,'2025-10-22 20:09:25','2025-10-22 20:09:25'),(60,9,'Pourquoi mon histoire / mon chapitre / mon commentaire / ma présentation a disparu ?','pourquoi-mon-histoire-mon-chapitre-mon-commentaire-ma-presentation-a-disparu','<p>Cela veut dire que suite à un signalement, votre histoire / chapitre / commentaire / présentation contient un ou des éléments qui contreviennent au règlement. Mais, pas de panique : ce contenu a été désactivé le temps que vous puissiez faire des corrections. Une fois que tout a été rectifié, tout sera de nouveau actif !</p>\n\n<p><br><br></p>',NULL,NULL,3,1,2,2,'2025-10-22 20:10:22','2025-10-22 20:10:22'),(61,9,'Si un élément de mon contenu est désactivé par la modération, comment est-ce que je serai prévenu·e ?','si-un-element-de-mon-contenu-est-desactive-par-la-moderation-comment-est-ce-que-je-serai-prevenue','<p>Le contenu concerné sera désherbé : soit désactivé (dans le cas d’un texte par exemple, il sera passé en visibilité “privée”), soit remplacé par un contenu signalant une intervention de la modération. Vous recevrez également un mail de la part de l’équipe, sur l’adresse email utilisée pour vous connecter au Jardin des Esperluettes, vous rappelant l’infraction commise (puisque le contenu concerné aura été enlevé du site).</p>\n\n<p><br><br></p>',NULL,NULL,4,1,2,2,'2025-10-22 20:11:27','2025-10-22 20:11:27'),(62,9,'Si un élément de mon contenu est désactivé par la modération, que dois-je faire ?','si-un-element-de-mon-contenu-est-desactive-par-la-moderation-que-dois-je-faire','<p>Prenez tout d’abord un moment pour considérer la raison de l’intervention de la modération, et pour refaire un détour par le règlement si besoin est. Vous pouvez également, par retour de mail, demander des précisions – toutefois, notez bien que toute décision de l’équipe de modération est irrévocable : il ne servira à rien de discuter.</p>\n\n<p>Ensuite, vous devez corriger par vous-même votre erreur, c’est-à-dire éditer et remplacer l’intervention de la modération (phrase ou image, selon le contenu concerné) en observant cette fois les règles du Jardin des Esperluettes. Notez qu’en faisant preuve de mauvaise foi et en remettant à l’identique (ou presque) le contenu fautif, vous vous exposerez à de plus lourdes sanctions.</p>\n\n<p><br><br></p>',NULL,NULL,5,1,2,2,'2025-10-22 20:12:47','2025-10-22 20:12:47');
/*!40000 ALTER TABLE `faq_questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `message_deliveries`
--

DROP TABLE IF EXISTS `message_deliveries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `message_deliveries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `message_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `message_deliveries_message_id_user_id_unique` (`message_id`,`user_id`),
  KEY `message_deliveries_user_id_is_read_index` (`user_id`,`is_read`),
  KEY `message_deliveries_message_id_index` (`message_id`),
  KEY `message_deliveries_user_id_index` (`user_id`),
  CONSTRAINT `message_deliveries_message_id_foreign` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `message_deliveries`
--

LOCK TABLES `message_deliveries` WRITE;
/*!40000 ALTER TABLE `message_deliveries` DISABLE KEYS */;
/*!40000 ALTER TABLE `message_deliveries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sent_by_id` bigint unsigned NOT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `reply_to_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `messages_reply_to_id_foreign` (`reply_to_id`),
  KEY `messages_title_index` (`title`),
  KEY `messages_sent_by_id_index` (`sent_by_id`),
  CONSTRAINT `messages_reply_to_id_foreign` FOREIGN KEY (`reply_to_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=96 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2025_08_02_192800_create_roles_tables',1),(5,'2025_08_08_073600_create_activation_codes_table',1),(6,'2025_08_08_081605_add_is_active_to_users_table',1),(7,'2025_08_08_153258_create_profile_profiles_table',1),(8,'2025_08_09_205300_add_slug_to_profile_profiles_table',1),(9,'2025_08_10_000000_create_domain_events_table',1),(10,'2025_08_10_000001_create_story_ref_genres_table',1),(11,'2025_08_10_000002_create_story_ref_audiences_table',1),(12,'2025_08_10_000003_create_story_ref_types_table',1),(13,'2025_08_10_000004_create_story_ref_statuses_table',1),(14,'2025_08_10_000005_create_story_ref_copyrights_table',1),(15,'2025_08_10_000006_create_story_ref_trigger_warnings_table',1),(16,'2025_08_10_000007_create_story_ref_feedbacks_table',1),(17,'2025_08_10_080900_add_slug_to_roles_table',1),(18,'2025_08_12_131047_create_announcements_table',1),(19,'2025_08_13_231800_create_news_and_copy_from_announcements',1),(20,'2025_08_14_141500_create_static_pages_table',1),(21,'2025_08_15_074113_add_order_to_story_ref_genres_table',1),(22,'2025_08_17_000000_create_stories_table',1),(23,'2025_08_17_000001_create_story_collaborators_table',1),(24,'2025_08_18_000000_add_display_name_to_profile_profiles',1),(25,'2025_08_18_000001_backfill_display_name_and_make_not_null',1),(26,'2025_08_18_000002_drop_name_from_users_table',1),(27,'2025_08_24_000000_create_story_trigger_warnings_table',1),(28,'2025_08_25_000000_create_story_genres_table',1),(29,'2025_08_26_000000_make_story_refs_not_nullable_and_add_fks',1),(30,'2025_08_26_081455_backfill_profile_profile_slugs',1),(33,'2025_08_28_000002_create_chapters_table',2),(34,'2025_08_28_000003_create_reading_progress_table',2),(35,'2025_08_30_000000_drop_reads_guest_count_from_story_chapters',3),(36,'2025_08_30_000002_add_reads_logged_total_to_stories_table',4),(37,'2025_09_02_000000_create_comments_table',5),(38,'2025_09_12_000000_create_events_domain_table',6),(39,'2025_09_12_000001_drop_domain_events_table',7),(43,'2025_09_16_000001_add_word_and_character_count_to_story_chapters_table',8),(45,'2025_09_17_000100_add_tw_disclosure_to_stories_table',9),(46,'2025_09_19_000001_create_story_chapter_credits_table',10),(47,'2025_09_19_000002_backfill_story_chapter_credits',10),(50,'2025_09_25_221500_add_last_edited_at_to_story_chapters_table',11),(53,'2025_10_02_000000_create_messages_table',12),(54,'2025_10_02_000001_create_message_deliveries_table',12),(59,'2025_10_02_204800_drop_cross_domain_fks_in_story',13),(60,'2025_10_02_204900_drop_cross_domain_fks_in_news',13),(61,'2025_10_02_205000_drop_cross_domain_fks_in_profile',13),(62,'2025_10_02_205100_drop_cross_domain_fks_in_static_pages',13),(63,'2025_10_04_000000_create_discord_connection_codes_table',14),(64,'2025_10_04_000001_create_discord_users_table',15),(65,'2025_10_08_000001_allow_null_author_id_on_comments',16),(66,'2025_10_08_000002_make_created_by_nullable_on_static_pages',16),(67,'2025_10_08_000003_make_news_created_by_nullable',16),(68,'2025_10_14_000000_create_feature_toggles_table',16),(69,'2025_10_14_000001_create_moderation_reasons_table',16),(70,'2025_10_14_000002_create_moderation_reports_table',16),(71,'2025_10_15_000002_remove_is_answered_from_comments',16),(72,'2025_10_15_104700_add_core_roles_data',16),(73,'2025_10_17_153500_add_soft_deletes_to_profile_profiles_table',16),(74,'2025_10_17_161000_add_soft_deletes_to_stories_table',16),(75,'2025_10_17_161100_add_soft_deletes_to_story_chapters_table',16),(76,'2025_10_22_062442_create_faq_categories_table',16),(77,'2025_10_22_062453_create_faq_questions_table',16),(78,'2025_10_20_000000_create_activities_table',17),(79,'2025_10_23_000000_create_calendar_jardino_goals_table',18),(80,'2025_10_23_000001_create_calendar_jardino_story_snapshots_table',19),(81,'2025_10_23_000002_create_calendar_jardino_garden_cells_table',19),(82,'2025_11_03_000000_create_notifications_table',20),(83,'2025_11_03_000001_create_notification_reads_table',20),(84,'2025_11_05_063004_create_read_list_entries_table',20),(85,'2025_11_23_204700_add_compliance_fields_to_users_table',20),(86,'2025_12_01_112800_add_maturity_fields_to_story_ref_audiences',20),(87,'2025_12_03_091815_create_config_parameter_values_table',20),(88,'2025_12_03_160000_create_user_promotion_request_table',20),(89,'2025_12_04_174900_add_is_complete_and_is_excluded_from_events_to_stories_table',20),(90,'2024_12_13_140000_create_calendar_secret_gift_participants_table',21),(91,'2024_12_13_140001_create_calendar_secret_gift_assignments_table',21),(92,'2025_12_23_074614_add_gift_sound_path_to_calendar_secret_gift_assignments_table',21),(93,'2024_12_28_172900_create_settings_table',22),(94,'2026_01_03_141635_add_cover_type_to_stories_table',23),(95,'2026_01_03_142151_add_cover_genre_slug_to_stories_table',24);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `moderation_reasons`
--

DROP TABLE IF EXISTS `moderation_reasons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `moderation_reasons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `topic_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `moderation_reasons_topic_key_is_active_index` (`topic_key`,`is_active`),
  KEY `moderation_reasons_topic_key_sort_order_index` (`topic_key`,`sort_order`),
  KEY `moderation_reasons_topic_key_index` (`topic_key`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `moderation_reasons`
--

LOCK TABLES `moderation_reasons` WRITE;
/*!40000 ALTER TABLE `moderation_reasons` DISABLE KEYS */;
INSERT INTO `moderation_reasons` VALUES (1,'profile','Autre',0,1,'2025-10-23 06:54:04','2025-10-23 06:54:04');
/*!40000 ALTER TABLE `moderation_reasons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `moderation_reports`
--

DROP TABLE IF EXISTS `moderation_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `moderation_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `topic_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` bigint unsigned NOT NULL,
  `reported_user_id` bigint unsigned DEFAULT NULL,
  `reported_by_user_id` bigint unsigned NOT NULL,
  `reason_id` bigint unsigned NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `content_snapshot` json DEFAULT NULL,
  `content_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','confirmed','dismissed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reviewed_by_user_id` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `review_comment` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `moderation_reports_reason_id_foreign` (`reason_id`),
  KEY `moderation_reports_topic_key_entity_id_index` (`topic_key`,`entity_id`),
  KEY `moderation_reports_reported_user_id_index` (`reported_user_id`),
  KEY `moderation_reports_reported_by_user_id_index` (`reported_by_user_id`),
  KEY `moderation_reports_status_created_at_index` (`status`,`created_at`),
  KEY `moderation_reports_topic_key_index` (`topic_key`),
  CONSTRAINT `moderation_reports_reason_id_foreign` FOREIGN KEY (`reason_id`) REFERENCES `moderation_reasons` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `moderation_reports`
--

LOCK TABLES `moderation_reports` WRITE;
/*!40000 ALTER TABLE `moderation_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `moderation_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `news`
--

DROP TABLE IF EXISTS `news`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `news` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `summary` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `header_image_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_pinned` tinyint(1) NOT NULL DEFAULT '0',
  `display_order` int unsigned DEFAULT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta_description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `news_slug_unique` (`slug`),
  KEY `news_status_published_at_index` (`status`,`published_at`),
  KEY `news_is_pinned_display_order_published_at_index` (`is_pinned`,`display_order`,`published_at`),
  KEY `news_is_pinned_index` (`is_pinned`),
  KEY `news_display_order_index` (`display_order`),
  KEY `news_published_at_index` (`published_at`),
  KEY `idx_news_created_by` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `news`
--

LOCK TABLES `news` WRITE;
/*!40000 ALTER TABLE `news` DISABLE KEYS */;
INSERT INTO `news` VALUES (1,'Test','test','a small test','<p>News avec un <a href=\"http://localhost/\"><span style=\"text-decoration:underline;\">lien interne</span></a> </p>\n\n<p>Et un <a href=\"https://discord.com\" target=\"_blank\" rel=\"noopener noreferrer\">lien externe</a></p>\n','news/2025/09/01K4QY18AGQ1PTDYJ43VVKHGFA.jpg',1,1,'published',NULL,'2025-09-09 19:08:16',2,'2025-09-09 19:07:54','2025-09-09 19:08:16'),(2,'New news','new-news','Coin','<p>Coin coin</p>\n',NULL,0,NULL,'published',NULL,'2025-12-21 14:40:01',2,'2025-12-21 14:40:01','2025-12-21 14:40:01');
/*!40000 ALTER TABLE `news` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_reads`
--

DROP TABLE IF EXISTS `notification_reads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_reads` (
  `notification_id` bigint unsigned NOT NULL,
  `user_id` int NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`notification_id`,`user_id`),
  KEY `notification_reads_user_id_read_at_index` (`user_id`,`read_at`),
  CONSTRAINT `notification_reads_notification_id_foreign` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_reads`
--

LOCK TABLES `notification_reads` WRITE;
/*!40000 ALTER TABLE `notification_reads` DISABLE KEYS */;
INSERT INTO `notification_reads` VALUES (1,2,NULL,'2025-12-21 14:40:01','2025-12-21 14:40:01'),(1,3,NULL,'2025-12-21 14:40:01','2025-12-21 14:40:01'),(1,4,NULL,'2025-12-21 14:40:01','2025-12-21 14:40:01'),(1,6,NULL,'2025-12-21 14:40:01','2025-12-21 14:40:01'),(1,7,NULL,'2025-12-21 14:40:01','2025-12-21 14:40:01'),(2,2,NULL,'2025-12-25 15:22:25','2025-12-25 15:22:25'),(3,3,NULL,'2025-12-25 16:09:16','2025-12-25 16:09:16'),(4,3,NULL,'2025-12-25 20:43:04','2025-12-25 20:43:04'),(5,3,NULL,'2025-12-25 20:43:13','2025-12-25 20:43:13'),(6,2,NULL,'2025-12-25 20:44:07','2025-12-25 20:44:07'),(7,6,NULL,'2025-12-25 20:44:36','2025-12-25 20:44:36'),(8,6,NULL,'2025-12-25 20:45:07','2025-12-25 20:45:07'),(9,3,NULL,'2025-12-25 20:45:38','2025-12-25 20:45:38'),(10,2,NULL,'2025-12-25 20:45:38','2025-12-25 20:45:38'),(11,3,NULL,'2025-12-25 20:45:42','2025-12-25 20:45:42'),(12,2,NULL,'2025-12-25 20:45:42','2025-12-25 20:45:42'),(13,2,NULL,'2025-12-27 15:25:56','2025-12-27 15:25:56'),(14,2,NULL,'2025-12-27 15:27:02','2025-12-27 15:27:02'),(15,2,NULL,'2025-12-27 15:27:38','2025-12-27 15:27:38'),(16,3,NULL,'2025-12-27 19:32:16','2025-12-27 19:32:16'),(17,2,NULL,'2025-12-29 07:05:22','2025-12-29 07:05:22'),(17,3,NULL,'2025-12-29 07:05:22','2025-12-29 07:05:22'),(18,2,NULL,'2026-01-11 10:41:04','2026-01-11 10:41:04'),(18,6,NULL,'2026-01-11 10:41:04','2026-01-11 10:41:04');
/*!40000 ALTER TABLE `notification_reads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `source_user_id` int DEFAULT NULL,
  `content_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_data` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_created_at_index` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,NULL,'news.published','{\"news_slug\": \"new-news\", \"news_title\": \"New news\"}','2025-12-21 14:40:01','2025-12-21 14:40:01'),(2,3,'readlist.story.added','{\"story_slug\": \"test-7\", \"reader_name\": \"Alice\", \"reader_slug\": \"alice\", \"story_title\": \"Test\"}','2025-12-25 15:22:25','2025-12-25 15:22:25'),(3,2,'readlist.chapter.published','{\"story_slug\": \"test-7\", \"author_name\": \"LogistiX le seigneur des loutres de la grande colline\", \"author_slug\": \"logistix-le-seigneur-des-loutres-de-la-grande-colline\", \"story_title\": \"Test\", \"chapter_slug\": \"publie-19\", \"chapter_title\": \"Publié\"}','2025-12-25 16:09:16','2025-12-25 16:09:16'),(4,2,'story.collaborator.removed','{\"user_name\": \"LogistiX le seigneur des loutres de la grande colline\", \"user_slug\": \"logistix-le-seigneur-des-loutres-de-la-grande-colline\", \"story_slug\": \"test-partage-7\", \"story_title\": \"Test partage\"}','2025-12-25 20:43:04','2025-12-25 20:43:04'),(5,2,'story.collaborator.role_given','{\"role\": \"beta-reader\", \"user_name\": \"LogistiX le seigneur des loutres de la grande colline\", \"user_slug\": \"logistix-le-seigneur-des-loutres-de-la-grande-colline\", \"story_slug\": \"test-partage-7\", \"story_title\": \"Test partage\"}','2025-12-25 20:43:13','2025-12-25 20:43:13'),(6,6,'story.collaborator.left','{\"user_name\": \"Daniel\", \"user_slug\": \"daniel\", \"story_slug\": \"test-partage-7\", \"story_title\": \"Test partage\"}','2025-12-25 20:44:07','2025-12-25 20:44:07'),(7,2,'story.collaborator.role_given','{\"role\": \"author\", \"user_name\": \"LogistiX le seigneur des loutres de la grande colline\", \"user_slug\": \"logistix-le-seigneur-des-loutres-de-la-grande-colline\", \"story_slug\": \"test-partage-7\", \"story_title\": \"Test partage\"}','2025-12-25 20:44:36','2025-12-25 20:44:36'),(8,2,'story.coauthor.chapter.created','{\"user_name\": \"LogistiX le seigneur des loutres de la grande colline\", \"user_slug\": \"logistix-le-seigneur-des-loutres-de-la-grande-colline\", \"story_slug\": \"test-partage-7\", \"story_title\": \"Test partage\", \"chapter_slug\": \"chapitre-3-21\", \"chapter_title\": \"Chapitre 3\"}','2025-12-25 20:45:07','2025-12-25 20:45:07'),(9,2,'readlist.chapter.published','{\"story_slug\": \"test-partage-7\", \"author_name\": \"LogistiX le seigneur des loutres de la grande colline\", \"author_slug\": \"logistix-le-seigneur-des-loutres-de-la-grande-colline\", \"story_title\": \"Test partage\", \"chapter_slug\": \"chapitre-3-21\", \"chapter_title\": \"Chapitre 3\"}','2025-12-25 20:45:38','2025-12-25 20:45:38'),(10,6,'story.coauthor.chapter.updated','{\"user_name\": \"Daniel\", \"user_slug\": \"daniel\", \"story_slug\": \"test-partage-7\", \"story_title\": \"Test partage\", \"chapter_slug\": \"chapitre-3-21\", \"chapter_title\": \"Chapitre 3\"}','2025-12-25 20:45:38','2025-12-25 20:45:38'),(11,2,'readlist.chapter.unpublished','{\"story_slug\": \"test-partage-7\", \"author_name\": \"LogistiX le seigneur des loutres de la grande colline\", \"author_slug\": \"logistix-le-seigneur-des-loutres-de-la-grande-colline\", \"story_title\": \"Test partage\", \"chapter_slug\": \"chapitre-3-21\", \"chapter_title\": \"Chapitre 3\"}','2025-12-25 20:45:42','2025-12-25 20:45:42'),(12,6,'story.coauthor.chapter.deleted','{\"user_name\": \"Daniel\", \"user_slug\": \"daniel\", \"story_slug\": \"test-partage-7\", \"story_title\": \"Test partage\", \"chapter_title\": \"Chapitre 3\"}','2025-12-25 20:45:42','2025-12-25 20:45:42'),(13,6,'story.chapter.comment','{\"is_reply\": false, \"comment_id\": 28, \"story_name\": \"Je connais une histoire...\", \"story_slug\": \"je-connais-une-histoire-5\", \"author_name\": \"Daniel\", \"author_slug\": \"daniel\", \"chapter_slug\": \"chapitre-1-14\", \"chapter_title\": \"Chapitre 1\"}','2025-12-27 15:25:56','2025-12-27 15:25:56'),(14,6,'story.chapter.comment','{\"is_reply\": false, \"comment_id\": 29, \"story_name\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\", \"story_slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-vraiment-vraiment-pas-2\", \"author_name\": \"Daniel\", \"author_slug\": \"daniel\", \"chapter_slug\": \"chapitre-1-6\", \"chapter_title\": \"Chapitre-1\"}','2025-12-27 15:27:02','2025-12-27 15:27:02'),(15,6,'story.chapter.comment','{\"is_reply\": false, \"comment_id\": 30, \"story_name\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\", \"story_slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-vraiment-vraiment-pas-2\", \"author_name\": \"Daniel\", \"author_slug\": \"daniel\", \"chapter_slug\": \"chapitre-21-7\", \"chapter_title\": \"Chapitre 2.1\"}','2025-12-27 15:27:38','2025-12-27 15:27:38'),(16,2,'story.collaborator.role_given','{\"role\": \"author\", \"user_name\": \"LogistiX le seigneur des loutres de la grande colline\", \"user_slug\": \"logistix-le-seigneur-des-loutres-de-la-grande-colline\", \"story_slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-vraiment-vraiment-pas-2\", \"story_title\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\"}','2025-12-27 19:32:16','2025-12-27 19:32:16'),(17,6,'readlist.story.added','{\"story_slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-vraiment-vraiment-pas-2\", \"reader_name\": \"Daniel\", \"reader_slug\": \"daniel\", \"story_title\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\"}','2025-12-29 07:05:22','2025-12-29 07:05:22'),(18,4,'story.chapter.comment','{\"is_reply\": false, \"comment_id\": 31, \"story_name\": \"Le Crépuscule des Âs\", \"story_slug\": \"le-crepuscule-des-as-1\", \"author_name\": \"Bob\", \"author_slug\": \"bob\", \"chapter_slug\": \"chapitre-1\", \"chapter_title\": \"Chapitre 1\"}','2026-01-11 10:41:04','2026-01-11 10:41:04');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
INSERT INTO `password_reset_tokens` VALUES ('fhemery@hemit.fr','$2y$12$67Xo0VrSGFnEFCfd6geI8OsiY6cLGPqcs1i9ARgiyc8ULTMSPnWbO','2025-10-01 15:36:06');
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `profile_profiles`
--

DROP TABLE IF EXISTS `profile_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `profile_profiles` (
  `user_id` bigint unsigned NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `profile_picture_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `facebook_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `x_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `instagram_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `youtube_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `profile_profiles_slug_unique` (`slug`),
  KEY `profile_profiles_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `profile_profiles`
--

LOCK TABLES `profile_profiles` WRITE;
/*!40000 ALTER TABLE `profile_profiles` DISABLE KEYS */;
INSERT INTO `profile_profiles` VALUES (2,'tech-admin-ou-plus-communement-le-grand-jardinier','Tech Admin ou plus communément le Grand Jardinier','profile_pictures/2_1756907799.jpg','https://facebook.com/lx',NULL,NULL,NULL,NULL,'2025-08-28 07:33:07','2026-01-11 10:04:04',NULL),(3,'alice','Alice','profile_pictures/3_1756908205.jpg',NULL,NULL,NULL,NULL,NULL,'2025-08-30 05:39:53','2025-12-25 15:19:58',NULL),(4,'bob','Bob','profile_pictures/4.svg',NULL,NULL,NULL,NULL,NULL,'2025-09-08 19:37:08','2025-09-08 19:37:08',NULL),(5,'carol','Carol','profile_pictures/5.svg',NULL,NULL,NULL,NULL,NULL,'2025-09-08 20:31:43','2025-09-08 20:31:43',NULL),(6,'daniel','Daniel',NULL,NULL,NULL,NULL,NULL,NULL,'2025-09-12 15:01:38','2025-12-25 15:13:41',NULL),(7,'test1','Test1','profile_pictures/7.svg',NULL,NULL,NULL,NULL,NULL,'2025-09-14 06:43:35','2025-09-14 06:43:35',NULL),(8,'emily','Émily','profile_pictures/8.svg',NULL,NULL,NULL,NULL,NULL,'2026-01-11 10:30:56','2026-01-11 10:30:56',NULL),(9,'gina','Gina','profile_pictures/9.svg',NULL,NULL,NULL,NULL,NULL,'2026-01-11 10:34:57','2026-01-11 10:34:57',NULL);
/*!40000 ALTER TABLE `profile_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `read_list_entries`
--

DROP TABLE IF EXISTS `read_list_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `read_list_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `story_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `read_list_entries_user_id_story_id_unique` (`user_id`,`story_id`),
  KEY `read_list_entries_user_id_index` (`user_id`),
  KEY `read_list_entries_story_id_index` (`story_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `read_list_entries`
--

LOCK TABLES `read_list_entries` WRITE;
/*!40000 ALTER TABLE `read_list_entries` DISABLE KEYS */;
INSERT INTO `read_list_entries` VALUES (1,3,7,'2025-12-25 15:22:25','2025-12-25 15:22:25'),(2,6,2,'2025-12-29 07:05:22','2025-12-29 07:05:22');
/*!40000 ALTER TABLE `read_list_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_user`
--

DROP TABLE IF EXISTS `role_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_user` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_user_user_id_role_id_unique` (`user_id`,`role_id`),
  KEY `role_user_role_id_foreign` (`role_id`),
  CONSTRAINT `role_user_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_user`
--

LOCK TABLES `role_user` WRITE;
/*!40000 ALTER TABLE `role_user` DISABLE KEYS */;
INSERT INTO `role_user` VALUES (3,2,1,NULL,NULL),(4,3,3,NULL,NULL),(6,6,3,NULL,NULL),(18,7,3,NULL,NULL),(19,2,4,NULL,NULL),(20,4,2,NULL,NULL),(22,8,3,NULL,NULL),(23,8,9,NULL,NULL),(24,2,2,NULL,NULL),(26,9,3,NULL,NULL),(27,9,1,NULL,NULL);
/*!40000 ALTER TABLE `role_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`),
  UNIQUE KEY `roles_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Majuscule','admin','Les administrateurices de ce Jardin','2025-08-28 07:31:18','2026-01-11 10:37:07'),(2,'Graine d\'Esperluette','user','Nous ont rejoint récemment, en train de prendre leurs racines dans le Jardin','2025-08-28 07:31:18','2026-01-11 10:37:36'),(3,'Esperluette','user-confirmed','Bien établies dans le Jardin, elles font pousser les histoires','2025-08-28 07:31:18','2026-01-11 10:38:25'),(4,'Arobase','tech-admin','Responsable de la solidité et de l\'évolution du Jardin','2025-09-26 19:48:19','2026-01-11 10:38:57'),(9,'Cadratin','moderator','Dans le Jardin, ils sont l\'autorité','2025-10-22 13:29:04','2026-01-11 10:39:19');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('FoUHFykmcBBYGNWbHz0nFka45lSTwC7q7FrsGrjp',4,'172.23.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:146.0) Gecko/20100101 Firefox/146.0','YTo2OntzOjY6Il90b2tlbiI7czo0MDoiUWkwVmxNTVhieWQ0Qkhab1BSdzc0dGxCc211aVE3UE5sV1NWaDNicyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly9sb2NhbGhvc3Qvc3Rvcmllcy9sZS1jcmVwdXNjdWxlLWRlcy1hcy0xIjt9czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6NDtzOjM6InVybCI7YTowOnt9czoyNToidXNlcl9jb21wbGlhbmNlX2NoZWNrZWRfNCI7YjoxO30=',1768129590),('nMt0n3ca3wJNPbOuSExy4ROHZmBszbYqquUuDCl0',NULL,'172.23.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiTUs0elBTQjdnTkpUTElvZmJzWjlLeEtJSnpXRTBDU2JNdVB6RExoeSI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czoxNjY6Imh0dHA6Ly9sb2NhbGhvc3QvdmVyaWZ5LWVtYWlsLzIvNzUzOWIzMDQ1MjEwNTU0NmU3NTg3NWNiMTA5YjI3MmIxOGNmOTUzYz9leHBpcmVzPTE3NjgxMjk3NTkmc2lnbmF0dXJlPWUwYzIyOGM5MDJkZDRlOWM0ZDI1MDFjNTBhNmE4OGRkOWFhNTVlNzBhZDM4Y2JhMTJhMmIxMTVkODY4MTU5YTAiO31zOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czoyMjoiaHR0cDovL2xvY2FsaG9zdC9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1768126184),('rL5HBW2KzNnSnlUZQLPtL4D2gkkzzFhXbAJiTOgx',9,'172.23.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','YTo2OntzOjY6Il90b2tlbiI7czo0MDoianpPZzJpU2FNbllxcjBZY1ZnTWZ3YU4wWXBIWHVJNHl0QU1UWkJ5YyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NzQ6Imh0dHA6Ly9sb2NhbGhvc3Qvc3RvcmFnZS9uZXdzLzIwMjUvMDkvMDFLNFFZMThBR1ExUFREWUo0M1ZWS0hHRkEtODAwdy53ZWJwIjt9czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6OTtzOjM6InVybCI7YTowOnt9czoyNToidXNlcl9jb21wbGlhbmNlX2NoZWNrZWRfOSI7YjoxO30=',1768129857);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `domain` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_user_id_domain_key_unique` (`user_id`,`domain`,`key`),
  KEY `settings_user_id_index` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (2,2,'general','theme','autumn','2025-12-29 06:37:33','2025-12-29 06:37:33'),(3,2,'general','font','times','2025-12-29 06:51:42','2025-12-29 06:51:42'),(4,6,'readlist','hide-up-to-date','1','2025-12-29 07:06:24','2025-12-29 07:06:24'),(5,6,'profile','hide-comments-section','1','2025-12-29 07:23:14','2025-12-29 07:23:14');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `static_pages`
--

DROP TABLE IF EXISTS `static_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `static_pages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `summary` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `header_image_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('draft','published') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `meta_description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `static_pages_slug_unique` (`slug`),
  KEY `idx_static_pages_created_by` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `static_pages`
--

LOCK TABLES `static_pages` WRITE;
/*!40000 ALTER TABLE `static_pages` DISABLE KEYS */;
INSERT INTO `static_pages` VALUES (1,'Qui sommes nous ?','qui-sommes-nous',NULL,'<p>Nous sommes...</p>\n\n<h2>Le grand méchant loup</h2>\n\n<p>mais aussi...</p>\n\n<h3>Les petits lutins</h3>\n',NULL,'published',NULL,'2025-10-05 18:56:10','2025-10-05 18:56:10','2025-10-05 18:56:10',2);
/*!40000 ALTER TABLE `static_pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stories`
--

DROP TABLE IF EXISTS `stories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_by_user_id` bigint unsigned NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `visibility` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `story_ref_type_id` bigint unsigned NOT NULL,
  `story_ref_audience_id` bigint unsigned NOT NULL,
  `story_ref_copyright_id` bigint unsigned NOT NULL,
  `story_ref_status_id` bigint unsigned DEFAULT NULL,
  `story_ref_feedback_id` bigint unsigned DEFAULT NULL,
  `is_complete` tinyint(1) NOT NULL DEFAULT '0',
  `is_excluded_from_events` tinyint(1) NOT NULL DEFAULT '0',
  `cover_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'default',
  `cover_genre_slug` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tw_disclosure` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unspoiled',
  `last_chapter_published_at` timestamp NULL DEFAULT NULL,
  `reads_logged_total` bigint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stories_slug_unique` (`slug`),
  KEY `stories_visibility_index` (`visibility`),
  KEY `stories_last_chapter_published_at_index` (`last_chapter_published_at`),
  KEY `fk_stories_ref_type` (`story_ref_type_id`),
  KEY `fk_stories_ref_audience` (`story_ref_audience_id`),
  KEY `fk_stories_ref_copyright` (`story_ref_copyright_id`),
  KEY `stories_tw_disclosure_index` (`tw_disclosure`),
  KEY `idx_stories_created_by_user_id` (`created_by_user_id`),
  CONSTRAINT `fk_stories_ref_audience` FOREIGN KEY (`story_ref_audience_id`) REFERENCES `story_ref_audiences` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_stories_ref_copyright` FOREIGN KEY (`story_ref_copyright_id`) REFERENCES `story_ref_copyrights` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_stories_ref_type` FOREIGN KEY (`story_ref_type_id`) REFERENCES `story_ref_types` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stories`
--

LOCK TABLES `stories` WRITE;
/*!40000 ALTER TABLE `stories` DISABLE KEYS */;
INSERT INTO `stories` VALUES (1,2,'Le Crépuscule des Âs','le-crepuscule-des-as-1','<p>Dans le Royaume de Darkal, déchiré par les conflits depuis des temps immémoriaux, Cél, une épée douée de conscience, se cache avec Élias, son ultime porteur, dans une vallée oubliée.</p>\n\n<p><br></p>\n\n<p>Tandis qu\'Élias transcrit l\'histoire séculaire de Cél, l\'arme dévoile son parcours tumultueux : sa création mystérieuse, ses années sanglantes d\'assassin, son rôle de protectrice pour des figures corrompues, et ses relations complexes avec un Porteur humain et une autre arme consciente qui l\'a trahie.</p>\n\n<p><br></p>\n\n<p><br></p>','public',1,1,1,1,NULL,0,0,'default',NULL,'listed','2025-10-24 19:54:21',3,'2025-08-28 20:27:18','2026-01-11 10:41:28',NULL),(2,2,'Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas','immortelle-le-roman-dont-le-titre-nen-finit-vraiment-vraiment-pas-2','<p>Test d\'une description suffisamment longue parce que bien sûr Isapass est passée par là et maintenant y\'a plus rien qui marche...</p>','public',1,1,1,NULL,NULL,0,0,'default',NULL,'listed','2025-08-30 14:12:06',2,'2025-08-29 07:42:34','2025-12-27 15:27:38',NULL),(3,2,'L\'histoire sans début','lhistoire-sans-debut-3','<p>How did I pass the test with no description? This must be a very old story, a lucky one that bypassed the exercise...</p>','public',1,1,1,NULL,NULL,0,0,'default',NULL,'no_tw',NULL,0,'2025-08-31 16:50:43','2025-09-25 20:50:11',NULL),(5,2,'Je connais une histoire...','je-connais-une-histoire-5','<p>...qui énerve les gens. Mais alors vraiment, qui les énerve au-dela de toute limite. C\'est limite indécent.</p>\n\n<p><br></p>\n\n<p>aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa</p>','public',1,1,1,NULL,NULL,0,0,'default',NULL,'unspoiled','2025-09-14 19:58:02',1,'2025-09-14 19:57:32','2025-12-27 15:25:56',NULL),(6,2,'Limit test with a long long long long long long long long long long long title','limit-test-with-a-long-long-long-long-long-long-long-long-long-long-long-title-6','<p>aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa</p>','private',1,1,1,NULL,NULL,0,0,'default',NULL,'unspoiled',NULL,0,'2025-09-17 05:24:13','2025-12-25 16:21:52',NULL),(7,2,'Test partage','test-partage-7','<p>Test deconexcion Test deconexcionTest deconexcion Test deconexcion Test deconexcion Test deconexcion Test deconexcionTest deconexcion Test deconexcion Test deconexcion</p>','private',1,1,1,NULL,NULL,0,0,'default',NULL,'no_tw','2025-12-25 16:09:16',0,'2025-10-01 19:17:21','2025-12-25 20:45:42',NULL),(8,6,'Test','test-8','<p>da asj;dojasd;o jaiojd oasjd;ioas jad;iojd;io sjaiodj qiojdio \\sj uiquwuid nainscd yuavdya oajsdk	wpoq kdpoawk d </p>','public',1,1,1,NULL,NULL,0,0,'default',NULL,'no_tw',NULL,0,'2025-10-28 12:43:41','2025-10-28 12:43:41',NULL);
/*!40000 ALTER TABLE `stories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `story_chapter_credits`
--

DROP TABLE IF EXISTS `story_chapter_credits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `story_chapter_credits` (
  `user_id` bigint unsigned NOT NULL,
  `credits_gained` int NOT NULL DEFAULT '0',
  `credits_spent` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `story_chapter_credits`
--

LOCK TABLES `story_chapter_credits` WRITE;
/*!40000 ALTER TABLE `story_chapter_credits` DISABLE KEYS */;
INSERT INTO `story_chapter_credits` VALUES (2,200,15,'2025-09-19 11:48:00','2025-09-19 11:48:00'),(3,7,0,'2025-09-19 11:48:00','2025-09-19 11:48:00'),(4,6,0,'2026-01-11 10:41:04','2026-01-11 10:41:04'),(6,8,0,'2025-12-27 15:25:56','2025-12-27 15:27:38'),(8,5,0,'2026-01-11 10:30:56','2026-01-11 10:30:56'),(9,5,0,'2026-01-11 10:34:57','2026-01-11 10:34:57');
/*!40000 ALTER TABLE `story_chapter_credits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `story_chapters`
--

DROP TABLE IF EXISTS `story_chapters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `story_chapters` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `story_id` bigint unsigned NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `author_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int NOT NULL,
  `status` enum('not_published','published') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_published_at` timestamp NULL DEFAULT NULL,
  `last_edited_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reads_logged_count` int unsigned NOT NULL DEFAULT '0',
  `word_count` int unsigned NOT NULL DEFAULT '0',
  `character_count` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `story_chapters_slug_unique` (`slug`),
  KEY `story_chapters_story_id_sort_order_index` (`story_id`,`sort_order`),
  KEY `story_chapters_sort_order_index` (`sort_order`),
  KEY `story_chapters_status_index` (`status`),
  KEY `story_chapters_first_published_at_index` (`first_published_at`),
  CONSTRAINT `story_chapters_story_id_foreign` FOREIGN KEY (`story_id`) REFERENCES `stories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `story_chapters`
--

LOCK TABLES `story_chapters` WRITE;
/*!40000 ALTER TABLE `story_chapters` DISABLE KEYS */;
INSERT INTO `story_chapters` VALUES (1,1,'Chapitre 1','chapitre-1','<p>Quand j\'ai écrit ce texte, j\'avais de grandes ambitions pour cette histoire. J\'y croyais dur comme fer. Mais bon, faut croire que le fer, ça rouille... et moi, je dérouille.</p>\n\n<p>Bonne lecture quand même !</p>','<p>Le soleil se couchait paresseusement sur une vallée luxuriante, éclairant les falaises de grès d\'une lueur mordorée qui leur donnait une splendeur sans pareil. Les arbres bruissaient lentement au gré du vent qui filait à travers l\'étroite vallée. La nature faisait ses derniers préparatifs, sur le point d\'aller se coucher. Une bonne partie de la vallée était d\'ailleurs déjà dans la pénombre. Toute personne qui se serait tenue à l\'entrée de la vallée aurait gravé dans sa mémoire un moment magnifique comme seule la nature pouvait en offrir. Mais il n\'y avait personne pour admirer cette vue.</p>\n\n<p>– *Tu comptes vraiment y passer la soirée ? On a autre chose à faire, tu le sais ! »* </p>\n\n<p>Il n\'y avait personne pour admirer la vue, mais il y avait quelqu\'un, dans les arbres, qui marchait d\'un pas lent, faisant attention où il mettait les pieds. L\'homme était habillé simplement, d\'une tenue qui lui seyait au corps, une sorte de cuir souple qui lui permettait de se déplacer sans bruit et sans perturber la vie de la nature. Un exploit vue la musculature et la corpulence de l\'homme aux tempes grisonnantes qui se frayait un chemin parmi les bruyères. Il regardait autour de lui, consciencieusement. Il cherchait quelque chose. </p>\n\n<p>– <em>Tu n\'as pas la moindre once de curiosité alors ? »</em></p>\n\n<p>L\'homme ne réagit pas, mais son regard tomba visiblement sur ce qu\'il cherchait. Il s\'approcha lentement d\'un arbre, et inspecta un mince fil de chanvre qui traînait au sol, presque négligemment. Un collet. Vide de tout occupant. Il ne s\'en formalisa pas. Il l\'ajusta légèrement, puis repartit de ce même pas silencieux, comme s\'il faisait corps avec la nature.</p>\n\n<p>*– La cabane va s\'écrouler de vieillesse si tu ne te dépêches pas ! »* </p>\n\n<p>Il fronça les sourcils. Il s\'arrêta, le temps de prendre une grande inspiration, et de regarder, à travers les feuillages denses, serpenter une sente que seule lui semblait voir. Ses sens étaient aux aguets, comme s\'il essayait de percevoir un bruit en particulier. Il n\'y avait rien que le pépiement des oiseaux, les bruits de fourrés qui bougent au passage d\'un rongeur, et ce bruit de fond caractéristique et hypnotisant d\'une rivière qui coule.</p>\n\n<p>*– Elle va s\'écrouler, et je serai coincée dessous à jamais. Je te dirai bien que tu auras ma mort sur ma conscience, mais je suppose que tu t\'en moques de toute façons. Tu es bien trop occupé à gambader. »* </p>\n\n<p>Cette fois, son regard était ennuyé. Il reprit sa marche d\'un pas résolu, un peu moins discret. Comme si d\'un coup, il s\'était souvenu de quelque chose d\'urgent. Qu\'il avait laissé une casserole sur le feu, ou qu\'il avait oublié de fermer la barrière du champ dans lequel se repassait un troupeau. </p>\n\n<p>*– C\'est incroyable comme l\'être humain est capable de procrastination. Avec une telle paresse, je reste quand même ébahie que vous ayez réussi à conquérir autant de territoire. Je vous imaginais bien plus dire “Non, tu sais quoi, j\'irai mettre une clôture autour de ce champ demain. Ou le mois prochain. Ou tiens, jamais, c\'est bien aussi, jamais.”»*</p>\n\n<p>Il s\'arrêta à nouveau. Il était arrivé en bas d\'une petite sente, bien plus marquée celle-ci. Il regarda en haut. Fondue dans la nature, une cabane de belle taille trônait dans un renfoncement. Entourée par les arbres et la végétation, elle prenait paresseusement les derniers rayons du soleil. Un léger murmure sortit de sa bouche, à peine plus qu\'un souffle, clairement inaudible pour quiconque aurait été à proximité.</p>\n\n<p>– C\'est peut-être cette même procrastination qui m\'empêche de trouver l\'énergie de m\'éloigner suffisamment pour ne plus t\'entendre, alors tu devrais t\'en réjouir »</p>\n\n<p>*– Hé, c\'est bon. Tu ne vas pas repartir alors que tu es si proche. Mais tu pourrais comprendre mon impatience et avoir un peu d\'empathie quand même. »*</p>\n\n<p>– Les journées sont courtes et l\'hiver est là. Je sais que tu n\'as ni besoin de manger, ni de te chauffer, mais moi si. »</p>\n\n<p>*– Même si tu sais bien que je ne suis jamais contre un petit feu. Je ne ressens pas le froid, mais j\'aime m\'imprégner de cette sensation de chaleur ! »*</p>\n\n<p>L\'homme était arrivé à l\'entrée de la cabane pendant cette étrange discussion. Il monta les trois marches qui permettaient l\'accès à la terrasse, protégée par une avancée de toit, où trônait un fauteuil à bascule particulièrement ouvragé. Puis il poussa lentement la lourde porte qui grinça légèrement en s\'ouvrant. La pièce était sombre, et il attrapa machinalement une lanterne. Il appuya sur un bouton d\'un geste habitué. Dans un « clac » retentissant, une flamme apparut, et la pénombre se dissipa quelque peu. C\'était une salle de vie prévue pour peu d\'occupants. La petite table qui trônait au milieu n\'était entourée que de deux chaises, et l\'une d\'entre elle était couverte d\'un film de poussière. Une commode trônait, imposante, taillée dans un bois brut, contre le mur du fond. Et sur le côté, un deuxième exemplaire d\'un fauteuil à bascule trônait à côté d\'une cheminée.</p>\n\n<p>Il ressortit prestement, attrapa quelques bûches dans un appentis sur le côté de la maison, et retourna à l\'intérieur préparer un feu.</p>\n\n<p>*– Tu sais que j\'apprécierai que tu m\'emmènes avec toi de temps en temps. Elle est sympa cette bicoque, mais j\'aime bien les grands espaces. »*</p>\n\n<p>Soupirant, l\'homme regarda dans un coin de la pièce, dans une sorte d\'alcôve tapissée d\'un tissu épais. Posée à la verticale sur un piédestal de facture modeste, une épée semblait luire légèrement dans la pénombre. Son fourreau bleu nuit strié de fils d\'argent était d\'une facture remarquable, tout comme la garde et le pommeau, d\'un bleu azur parsemé de ces mêmes fils argentés. Clairement, cette épée jurait sur le reste de la cabane, par sa richesse et la finesse de l\'ouvrage. La voix de l\'homme prit un peu de volume alors qu\'il apostropha l\'épée.</p>\n\n<p>– Et depuis quand as-tu besoin de sortir pour ressentir les grands espaces, Cél ? »</p>\n\n<p>*– Élias, si tu avais mon âge et mon passé, tu saurais que toute occasion est bonne pour profiter des grands espaces. »*</p>\n\n<p>Élias, agenouillé près du feu, marqua un temps d\'arrêt. Il semblait contrit, sur le point de s\'excuser. Quelque chose sembla s\'insinuer en lui, et l\'instant d\'après il souriait.</p>\n\n<p>– C\'est probablement vrai. Mais même si tu ne me parles qu\'à moi, et par télépathie, tu réussirais probablement à faire fuir tous les animaux de la forêt. »</p>\n\n<p>L\'épée sembla émettre un petit rire hautain, alors qu\'Élias prenait précautionneusement une branche qu\'il passa dans l\'ouverture de la lanterne, puis qu\'il reposa dans la cheminée, donnant vie à un nouveau feu, plus fourni cette fois. Puis il se dirigea d\'un pas lent vers une pièce attenante, qui semblait servir d\'entrepôt, et entreprit de récupérer de quoi dîner, pendant que la voix de Cél, dans sa tête, continuait.</p>\n\n<p><em>– Bien sûr que non, je suis sûre que tous ces animaux m\'adoreraient. »</em></p>',0,'published','2025-08-29 10:16:08','2025-10-02 08:09:52',1,1225,6803,'2025-08-29 07:41:13','2026-01-11 10:40:11',NULL),(6,2,'Chapitre-1','chapitre-1-6',NULL,'<p>dasdad</p>',100,'published','2025-08-30 14:09:47','2025-09-09 20:29:45',1,1,6,'2025-08-30 14:09:47','2025-12-27 15:27:02',NULL),(7,2,'Chapitre 2.1','chapitre-21-7',NULL,'<p>dasdas</p>',200,'published','2025-08-30 14:12:06','2025-09-09 20:27:18',1,1,6,'2025-08-30 14:12:06','2025-12-27 15:27:38',NULL),(13,2,'Chapitre 3','chapitre-3-13',NULL,'<p>dasdasda</p>',300,'not_published','2025-09-09 20:26:40','2025-09-09 20:26:48',0,1,8,'2025-09-09 20:26:40','2025-09-09 20:26:48',NULL),(14,5,'Chapitre 1','chapitre-1-14',NULL,'<p>Où tout débute.</p>',100,'published','2025-09-14 19:58:02','2025-09-14 19:58:02',1,3,15,'2025-09-14 19:58:02','2025-12-27 15:25:56',NULL),(15,1,'Chapitre 2','chapitre-2-15',NULL,'<p>Élias termina sa tranche de pain, la venaison et le fromage qu\'il avait déposé dessus, et prit une gorgée à l\'outre qui était attachée à sa ceinture. Il poussa un soupir satisfait, et resta un instant à contempler le feu, dans une quiétude qu\'il n\'avait pas ressenti depuis longtemps. Et qui se retrouva brisée aussi vite qu\'elle était apparue :</p>\n\n<p><br></p>\n\n<p>*– Ça y est ? Monsieur a assouvi tous ses instincts primaires, on peut passer à des choses plus sérieuses ? »*</p>\n\n<p><br></p>\n\n<p>– Sérieusement, Cél, tu es âgée de quoi, six cents ans ? »</p>\n\n<p><br></p>\n\n<p>*– Sept cents ! »*</p>\n\n<p><br></p>\n\n<p>– Sept cents ans, et tu ne peux pas me laisser tranquille cinq minutes ? »</p>\n\n<p><br></p>\n\n<p>La voix d\'Élias était à nouveau à peine plus qu\'un murmure. De l\'extérieur, on aurait dit qu\'il soliloquait. Il n\'en avait pas vraiment besoin, il pouvait parler à Cél juste en formulant la pensée dans sa tête. Mais sa voix était la seule qu\'il avait entendu pendant des semaines, la seule qu\'il entendrait probablement pendant encore plusieurs semaines. Alors il parlait à haute voix, pour être sûr qu\'elle soit encore là quand il retournerait à la civilisation et qu\'il en ait besoin. Et puis parce qu\'un peu d\'humanité dans cette région isolée lui faisait du bien.</p>\n\n<p><br></p>\n\n<p>La voix dans sa tête s\'était tue. Élias jeta un regard vers l\'alcôve où trônait l\'épée, dans son fourreau. Il sonda mentalement la présence de l\'âme qui s\'y trouvait. Cél. Elle était là, dans l\'expectative. Elle avait décidé d\'accéder à sa demande de calme. C\'était davantage dans son style. Elle n\'avait pas l\'habitude de verser dans le sarcasme et l\'ironie. Depuis trente ans qu\'il la connaissait, les rares fois où il avait ressenti cela de sa part, c\'est parce qu\'elle était stressée ou excitée à l\'idée d\'un évènement. Le reste du temps, elle était plutôt pragmatique, factuelle, posée. </p>\n\n<p><br></p>\n\n<p>Il décida donc qu\'il n\'avait pas besoin des cinq minutes qu\'il demandait, et se leva lentement, dans un gémissement. La dernière blessure qu\'il avait reçu à l\'aine tirait encore. Il doutait que la douleur ne disparaisse jamais. Mais il ressentit au même moment dans sa tête une vague de soulagement. Cél avait compris. Il se dirigea d\'un pas vif vers la commode, ouvrit le tiroir du haut, et attrapa précautionneusement une pile de feuillets vierges, un encrier et une plume d\'excellente facture. Il les posa sur la table, puis entreprit de préparer le matériel. Dans sa tête, un sentiment d\'excitation remplaça le soulagement. Il sourit alors qu\'il plaçait un feuillet précautionneusement sur une tablette bien lisse qui lui servirait d\'écritoire.</p>\n\n<p><br></p>\n\n<p>– Bien. Tu es prête ? »</p>\n\n<p><br></p>\n\n<p>*– Tu me demandes si je suis prête ? J\'attends ce moment depuis qu\'on en a discuté et que tu as accepté. Ça fait quoi ? Six semaines ? »*</p>\n\n<p><br></p>\n\n<p>– Figure toi que le papier ne pousse pas directement sur les arbres. »</p>\n\n<p><br></p>\n\n<p>Nouveau soupir. Élias reprit : </p>\n\n<p><br></p>\n\n<p>– Donc je résume les règles : j\'écris ce que tu me dis, sans chercher à en altérer le sens. Ce sera plus facile si ne t\'adresse pas directement à moi, ça m\'évitera de devoir changer les tournures. On fait des pauses quand j\'en ai besoin, parce que ça fait des années que je n\'ai pas manié la plume. Je ne te pose pas de questions en dehors de ces pauses, pour ne pas interrompre tes pensées. J\'ai oublié quelque chose ? »</p>\n\n<p><br></p>\n\n<p>Léger silence.</p>\n\n<p><br></p>\n\n<p>*– L\'histoire que je vais te confier est… ». Nouvelle hésitation. « …trouble. Il y a de nombreuses facettes de moi, de nombreuses phases de mon histoire que tu ne connais pas. Je te demanderai de tenter au maximum de murer tes pensées pendant que tu écris, car cela risquerait d\'altérer mon récit. T\'en sens-tu capable ? »*</p>\n\n<p><br></p>\n\n<p>Élias hocha la tête lentement. Et mura l\'appréhension qui commençait à s\'installer suite à cette remarque.</p>\n\n<p><br></p>\n\n<p>*– Alors allons-y, Élias. Voici l\'histoire de Céleste, une histoire séculaire faite de violence et de trahisons. Voici mon histoire. »*</p>',400,'not_published','2025-10-24 19:47:13','2025-12-25 15:57:09',0,688,3836,'2025-10-24 19:47:13','2025-12-25 15:57:09',NULL),(17,1,'Chapitre 3','chapitre-3-17',NULL,'<p>Je suis « née » il y a environ sept cents ans dans les plaines arides de Bérégoth. Si on devait se reporter à une carte moderne, on dirait probablement du côté de la petite ville de Nassour, au Nord-Ouest de la capitale, à quelques lieues seulement des contreforts du Mansour. Je dis il y a environ sept cents ans, car à l\'époque il n\'existait pas de calendrier précis. Le Darkal était encore à des siècles de voir le jour, et la région de Bérégoth n\'était composée que de tribus, pour la plupart nomades.</p>\n\n<p><br></p>\n\n<p>Enfin, je dis « née » car c\'est le terme qui semble se rapprocher le plus de la vérité, encore que peut-être qu\'apparue serait plus exact. Quand on dit qu\'un être naît, il sort d\'un autre organisme vivant. Ou d\'un œuf, éventuellement. Moi j\'ai pris conscience dans le feu brûlant d\'une forge. C\'était un moment étrange. Mes pensées se sont imposées à moi tout de suite. Et les sensations ont commencé à affluer. La première sensation a été la chaleur. J\'avais chaud, très chaud. Et puis, je vis distinctement une épée, que j\'étais occupé à marteler méthodiquement sur une enclume pour lui donner juste la bonne forme, la bonne finesse, la bonne rigidité. Elle était belle, cette lame qui prenait forme. J\'en étais fière. Je souhaitais faire une pause pour faire le point suite à mon apparition, mais mon corps semblait bouger tout seul, mu par sa propre volonté. J\'étais curieuse. Je pensais savoir comment lui dire de bouger, mais rien n\'y faisait. Et surtout, je sentais, à la limite de mes pensées, des idées, des sentiments qui gravitaient autour de ma conscience, mais qui n\'étaient pas à moi. Des pensées qui ne faisaient pas de sens : il fallait que je pense à réparer la canne à pêche du vieux Fern. Que je passe chercher une miche de pain à la tente de Mijote avant de rentrer à la maison. Et surtout, que cette épée était de loin ma plus belle création, que j\'allais l\'offrir au chef de la tribu, Sarek. Ça ne faisait pas de sens, parce que ma mémoire ne se souvenait d\'aucun de ces noms.</p>\n\n<p><br></p>\n\n<p>C\'est à ce moment-là que j\'ai commencé à comprendre. Enfin, à paniquer. Je ne comprenais pas ce qu\'il se passait. D\'où venaient ces pensées parasites, comment allais-je m\'en débarrasser ? Alors je me suis roulé en boule mentalement, et j\'ai crié en pense : </p>\n\n<p><br></p>\n\n<p>*– Allez-vous en !*</p>\n\n<p><br></p>\n\n<p>Le martèlement s\'est arrêté. Ma vision s\'est tourné dans tous les sens. Et une nouvelle pensée parasite est venue se coller à ma conscience. D\'où venait cette voix ? Quelqu\'un avait-il crié, avait-il besoin d\'aide ? Devais-je arrêter la forge pour m\'en occuper ? </p>\n\n<p><br></p>\n\n<p>Alors j\'ai compris. J\'ai réalisé que les pensées que je percevais étaient celles du forgeron. Mais cela me laissait une question en suspens : où était mon corps dans ce cas ?</p>\n\n<p>Il ne m\'a pas fallu longtemps pour avoir la réponse. L\'homme a lâché la pince avec laquelle il tenait l\'épée rougeoyante et s\'est dirigé dehors un instant, pour jeter un œil. Sitôt qu\'il s\'est éloigné, ses pensées sont devenues plus vagues, la vision et les sons plus flous, et j\'ai perdu la sensation de chaleur que j\'avais jusque là. Dès qu\'il est revenu et a repris la pince, j\'ai retrouvé la clarté de son et d\'image que j\'avais auparavant.</p>\n\n<p><br></p>\n\n<p>Je ne sais pas comment j\'ai réalisé le miracle de ne pas hurler de surprise et envahir son esprit à nouveau. L\'instinct de survie, peut-être ? J\'ai décidé de ne pas bouger, de ne rien dire, de le laisser finir son œuvre. Il était tellement passionné par ce qu\'il forgeait, et pourtant il n\'était pas imbu de lui-même. Il voyait chaque défaut dans la lame – dans mon corps, devrais-je dire –, et il corrigeait patiemment. </p>\n\n<p>Je laissais ses pensées vagabonder et en appris un peu plus sur l\'environnement de ma naissance. Il était forgeron, mais appartenait à une tribu nomade, les Inja. Ils allaient de village en village, et pratiquaient le commerce, principalement avec les tribus sédentaires. L\'homme qui m\'a donné naissance avait noué des amitiés avec les forgerons de chaque tribu, et il utilisait leur forge en échange de menus services. C\'est ainsi qu\'en croisant l\'expertise des différentes tribus, il avait inventé une technique qui lui était propre, dont il était fier, et qui donnait à mon corps une qualité unique. </p>\n\n<p><br></p>\n\n<p>Il travailla pendant des heures. Ce que je découvris initialement être un lien ténu entre nos deux esprits se renforça à un point que j\'eus tout le mal du monde à ne pas laisser filtrer mes pensées vers lui. C\'était quelque chose qui m\'était venu naturellement, mais je savais que lui, parfois, captait mes pensées parasites, même s\'il n\'arrivait pas à déterminer leur origine. Ma foi, c\'était logique : dans le lot, c\'était moi l\'aberration, il ne pouvait pas soupçonner mon existence. Une épée qui pense, ça ne paraissait pas une possibilité de son point de vue. Alors que moi, ses pensées m\'ont inondé d\'être humains comme lui.</p>\n\n<p>Avec le recul, ce n\'est pas tant que notre lien se renforça, mais que son travail acharné continuait à me façonner, à me faire gagner en force. Mes pensées semblèrent se former bien plus rapidement. Ma conscience augmentait avec la qualité du travail de la lame. Je devenais plus entière, je voyais plus nettement, je percevais plus clairement l\'environnement que le forgeron voyait. Et je finis même par attraper son nom, au détour d\'une pensée fantasque : Akrim.</p>\n\n<p><br></p>\n\n<p>Il travailla près d\'une journée et d\'une nuit. Je compris qu\'il avait fait chauffer la forge à des températures rarement atteintes, et qu\'il ne pouvait pas juste faire cela plusieurs jours d\'affilée. C\'est pourquoi il s\'épuisa au travail, sa passion pour son art inextinguible. Et moi, je prenais de l\'ampleur dans mon coin, m\'abreuvant de ses pensées parasites pour comprendre le monde dans lequel je venais de débarquer malgré moi. </p>\n\n<p>Cependant au bout d\'un moment, il décida qu\'il avait terminé. En quelques minutes à peine, il fit refroidir la forge, me posa dans un coin, et décida d\'aller se coucher. Quand il s\'éloigna, mon monde se brouilla petit à petit et je pris peur. J\'eus peur de disparaître, que mon existence dépende uniquement de ma proximité avec mon créateur. Alors j\'eus la seule réaction possible. Je lui criais : </p>\n\n<p><br></p>\n\n<p>*– Attends ! Ne pars pas ! »*</p>\n\n<p><br></p>\n\n<p>Depuis, j\'ai appris à soigner mes introductions…</p>',500,'published','2025-10-24 19:53:57','2025-10-24 19:53:57',0,1152,6235,'2025-10-24 19:53:57','2026-01-11 10:41:28',NULL),(18,1,'Chapitre 4','chapitre-4-18',NULL,'<p>Je n\'oublierai jamais la réaction de terreur qui s\'est emparé d\'Akrim quand j\'ai brutalement fait irruption dans sa tête. Oui, parce que quand je dis que j\'ai crié, c\'était très certainement un euphémisme. Imaginez un instant un nourrisson crier juste devant votre oreille. Non, imaginez que vous avez la tête juste à la sortie d\'un cor ou d\'un tuba. Et bien là, c\'était à peu près l\'idée, sauf que je lui ai martelé cela directement dans la tête, sans l\'atténuation que les oreilles peuvent produire. J\'ai à peu près eu la subtilité d\'un troupeau d\'orignaux. Et Akrim, lui, était épuisé.</p>\n\n<p><br></p>\n\n<p>Il est tombé à genoux sous l\'impact de ma pensée. Sa douleur est venue faire écho à ma peur, et je me suis mis à paniquer encore davantage. Je ne me souviens pas de ce que je lui ai dit, mais ça n\'avait probablement aucun sens. Aucun qu\'il puisse faire en tous cas, pris de panique, ne sachant pas ce qui lui est arrivé. </p>\n\n<p><br></p>\n\n<p>Mais il a fait quelque chose d\'inattendu. Il s\'est pris la tête à deux mains, et s\'est mis à hurler : </p>\n\n<p><br></p>\n\n<p>– Je ne sais pas ce qu\'il se passe, mais sortez de ma tête ! »</p>\n\n<p><br></p>\n\n<p>La violence de la pensée a sectionné notre lien net. Je voyais toujours à travers ses yeux, même si pour l\'heure il les maintenait fermés. Mais je ne ressentais plus ses pensées, et les miennes ne l\'atteignaient visiblement plus. Alors il a rouvert les yeux, s\'est relevé, et à fait ce que je craignais qu\'il fasse. Il est parti, me laissant seule.</p>\n\n<p>Ma perception s\'est brouillée. Tous mes sens sombrèrent. Je ne vis, n\'entendis, ne ressentis plus rien. C\'est une sensation extrêmement perturbante. J\'étais désormais seule avec mes pensées, dans une gangue de noirceur, une absence de bruit et de sensation totales. J\'ai voulu crier, mais je n\'avais pas de voix, et personne vers qui diriger mes pensées. Je ne ressentais rien n\'y personne. Pendant de longues heures, je crus devenir folle. </p>\n\n<p><br></p>\n\n<p>Mais mon esprit était jeune. Il n\'avait pas eu le temps de s\'habituer à la richesse des interactions. Alors peu à peu, je me calmai. Je passais de longs moments à me convaincre que quelqu\'un finirait pas passer, que je pourrais capter son attention avec un peu plus de subtilité que lors de mon échange précédent. Je tournai dans ma tête des phrases qui pourraient amener la discussion sur un terrain favorable. Et quand le jour pointa à nouveau – un jour que je ne pouvais pas ressentir, et que la forge se remplit à nouveau, je fus emplie d\'effroi.</p>\n\n<p><br></p>\n\n<p>Il y avait du monde dans la forge. Je le savais. Je les sentais, ils étaient là, petites lucioles dans l\'obscurité de mes sensations. Mais ils brillaient d\'une lueur diffuse et lointaine. Je ne trouvais pas comment établir de lien avec ces lumières. Le noir était moins absolu que quand ils n\'étaient pas là, mais il existe beaucoup de teintes de sombre avant qu\'on ne commence à voir quelque chose. Les sons m\'arrivaient déformés, assourdis, je ne reconnaissais rien. J\'étais terrifiée, car je compris rapidement que si jamais Akrim ne revenait pas, ou s\'il avait fermé son esprit définitivement, j\'étais condamné à vivre dans la nuit totale, seule avec mes pensées, pour… je ne savais pas combien de temps ? J\'avais capté dans les pensées d\'Akrim que les gens naissaient et mouraient selon un cycle plus ou moins défini, même si je n\'arrivais pas à saisir pour le moment le concept d\'année. Mais moi ? Était-je mortelle ? Et dans le cas contraire, combien de temps avais-je devant moi ? </p>\n\n<p><br></p>\n\n<p>Je ne savais rien de tout cela. Et la peur me prit au point que quand Akrim revint à la forge, longtemps après – j\'appris qu\'il avait dormi 16 heures d\'affilée – je faillis déverser tout mon soulagement dans ses pensées, sans le moindre filtre.</p>\n\n<p>Mon soulagement, car la lumière et le son étaient revenus. Ainsi que mon accès à ses pensées qui flottaient librement. Il venait me récupérer pour me fixer sur la garde qu\'il avait choisi. Il avait soigneusement réussi à incorporer des fils d\'argents dans ma lame, et il en était très fier, mais le pommeau et la garde semblaient être une autre œuvre d\'art qu\'il chérissait. Je tâtais les morceaux pour savoir s\'ils étaient eux aussi doués de pensée mais n\'eût aucun résultat. Je ne savais même pas si je devais m\'en montrer soulagée ou attristée.</p>\n\n<p><br></p>\n\n<p>Toujours est-il que quand il eut fini de river mon corps dans le pommeau, et qu\'il me plaça dans un fourreau tout aussi magnifique, il exultait d\'une fierté qui me fit frissonner jusqu\'aux tréfonds de mon âme. Et c\'est ce qui m\'aida probablement à entamer la conversation d\'une façon bien plus posée, alors qu\'il me tenait dans sa main.</p>\n\n<p><br></p>\n\n<p>*– Je sais que tu te demandes d\'où vient cette voix, mais je te promets que je ne te veux aucun mal, je veux juste discuter. »*</p>\n\n<p><br></p>\n\n<p>Je le sentis tressaillir. Il regarda partout autour de lui, alpagua un autre homme à côté de lui :</p>\n\n<p><br></p>\n\n<p>– Tu as dit quelque chose ? »</p>\n\n<p><br></p>\n\n<p>L\'homme secoua la tête latéralement.</p>\n\n<p><br></p>\n\n<p>*– Ils ne m\'entendent pas. Tu es le seul que je puisse atteindre. Va te mettre dans un coin tranquille et nous pourrons en discuter. »*</p>\n\n<p><br></p>\n\n<p>Il semblait sur le point de craquer, mais au moins n\'avait-il pas fermé ses pensées. Je sentais quelques unes des siennes aux frontières de ma conscience. Il avait peur. Quand il avait cru entendre les voix hier, il avait mis ça sur une fatigue extrême. Mais là, de suite, il se croyait possédé. Pour l\'heure, je ne pouvais rien dire sans aggraver son cas. Alors j\'essayais d\'émettre une sorte de réconfort, à dose homéopathique dans un premier temps. Puis, quand je vis qu\'il ne rejetait pas la sensation, qu\'il ne la remettait pas en cause, j\'augmentai la dose. Après quelques minutes à errer dans l\'atelier, il décida de sortir. Par chance, il m\'avait gardé en main. Il marchait d\'un pas résolu vers un petit bosquet. Il s\'assit sur une large pierre, ferma les yeux un bref instant, inspirant un grand coup. </p>\n\n<p><br></p>\n\n<p>– Finissons-en, démon. Qu\'est-ce que tu me veux ? Comment puis-je me libérer de ton emprise ? »</p>\n\n<p><br></p>\n\n<p>*– Je ne suis pas un démon. Enfin je ne crois pas. J\'ai pris vie hier. Je réside dans cette épée. Je… j\'ai besoin de ton aide. »*</p>\n\n<p><br></p>\n\n<p>Il me regarda d\'un air effaré, me tenant à bout de bras. Puis il me lâcha et s\'écarta. Je savais que je ne mourrais pas, mais j\'étais quand même blessée. Je tentai quand même de prendre sur moi. </p>\n\n<p><br></p>\n\n<p>*– Si tu t\'éloignes, ou si tu fermes mon esprit, notre liaison se dissipera. Je ne verrai plus rien, ne ressentirai plus rien. Je serai plongé dans l\'obscurité, seule avec mes pensées. »*</p>\n\n<p><br></p>\n\n<p>Il s\'arrêta, et se retourna brutalement, me scrutant. Je me voyais, à travers ses yeux, bien qu\'un peu moins nettement maintenant qu\'il s\'était écarté. Je l\'avais ébranlé. Il se demandait s\'il était malade, s\'il devait se faire exorciser, ou partir en pèlerinage. Et en même temps, il avait été touché par mes mots, et il hésitait à mettre de la distance.</p>\n\n<p><br></p>\n\n<p>– Qu\'est-ce qui me dit que tu n\'es pas en train de me manipuler ? »</p>\n\n<p><br></p>\n\n<p>J\'émis un éclat de rire triste qui le fit reculer spontanément.</p>\n\n<p><br></p>\n\n<p>*– Akrim, si je savais comment te convaincre, je l\'aurais déjà fait. Tu m\'as donné vie hier dans ce brasier. Je ne sais pas si c\'est courant. Je ne connais rien de ce monde. Alors nous pouvons tâchons de comprendre ensemble ce que je suis et notre relation… »*</p>\n\n<p><br></p>\n\n<p>J\'étouffai un sanglot avant de reprendre : </p>\n\n<p><br></p>\n\n<p>*– … ou alors tu peux m\'abandonner là. Me laisser et ne jamais revenir. Mais sache que tu es le seul dont je puisse partager les sens et ainsi profiter de la lumière, de ces paysages enchanteurs et ces odeurs enivrantes. »*</p>\n\n<p><br></p>\n\n<p>Je laissai passer un court instant avant de reprendre :</p>\n\n<p><br></p>\n\n<p>*– Nous sommes liés Akrim. Je ne sais pas ce que je suis, je ne sais pas pourquoi je suis ici. Mais tu es mon Créateur. Je m\'en remets à toi. »*</p>\n\n<p><br></p>\n\n<p>C\'était vrai. J\'avais besoin de lui. J\'étais dépendante de lui, pas pour survivre, mais pour vivre. Alors je laissais couler dans les pensées que je lui adressais toute ma sincérité, tous mes doutes, et une partie de mes peurs, et j\'attendis. </p>\n\n<p><br></p>\n\n<p>Il se passa plusieurs minutes. Nous étions tous les deux perdus dans nos pensées. J\'avais décidé de ne pas épier les siennes, et j\'avais fermé les miennes lentement, pour le laisser réfléchir sans le parasiter. Je le savais, ce moment était décisif. J\'avais peur, et en même temps j\'avais confiance. Cet homme était pieu, et sa réaction instinctive concernant le fait qu\'il puisse être possédé était irrationnelle, mais cohérente. Cependant, il avait décidé de braver ses peurs pour m\'affronter, et il m\'avait écouté, je le savais.</p>\n\n<p><br></p>\n\n<p>Je ne fus donc pas surprise quand il s\'approcha à nouveau lentement et qu\'il s\'agenouilla. Qu\'il tendit une main vers moi, guettant les sensations au moment où il me toucherait. J\'ouvris à nouveau mon esprit et l\'encourageai silencieusement. Quand il m\'attrapa fermement et se releva, je lus dans ses pensées une immense douceur.</p>\n\n<p><br></p>\n\n<p>– Comment t\'appelles-tu ? », me demanda-t-il d\'une voix douce.</p>\n\n<p><br></p>\n\n<p>*– Je n\'ai pas de nom pour le moment. Je suppose que c\'est à toi qu\'il revient de me nommer. »*</p>\n\n<p><br></p>\n\n<p>Il sembla décontenancé un moment, mais il se reprit rapidement :</p>\n\n<p><br></p>\n\n<p>– Tu es liée à cette épée, n\'est-ce pas ? »</p>\n\n<p><br></p>\n\n<p>*– Je le crois, oui. À la lame, pour être précise. »*</p>\n\n<p><br></p>\n\n<p>Il sortit la lame du fourreau lentement, et je me mis à luire sous le soleil au zénith. Par ses yeux, je vis le bleu nuit profond de mon fourreau, les reflets du ciel sur ma lame scintillante aux traits d\'argent, et ce pommeau d\'un bleu azur si envoûtant. Il n\'eut pas l\'ombre d\'une hésitation. Je compris qu\'il avait déjà choisi un nom pour cette épée, et qu\'il lui semblait naturel que ce nom m\'appartienne désormais.</p>\n\n<p><br></p>\n\n<p>– Céleste », murmura-t-il. « Tu t\'appelleras Céleste »</p>',600,'published','2025-10-24 19:54:21','2025-10-24 19:54:21',0,1799,9621,'2025-10-24 19:54:21','2025-10-24 19:54:21',NULL),(19,7,'Publié','publie-19',NULL,'<p>Test</p>',100,'published','2025-12-25 16:09:16','2025-12-25 16:09:16',0,1,4,'2025-12-25 16:09:16','2025-12-25 16:09:16',NULL),(20,7,'Non publié','non-publie-20',NULL,'<p>test 2 - mis à jour</p>',200,'not_published',NULL,'2025-12-25 16:12:49',0,5,19,'2025-12-25 16:09:30','2025-12-25 16:12:49',NULL);
/*!40000 ALTER TABLE `story_chapters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `story_collaborators`
--

DROP TABLE IF EXISTS `story_collaborators`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `story_collaborators` (
  `story_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `role` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `invited_by_user_id` bigint unsigned NOT NULL,
  `invited_at` timestamp NOT NULL,
  `accepted_at` timestamp NULL DEFAULT NULL,
  UNIQUE KEY `story_collaborators_story_id_user_id_unique` (`story_id`,`user_id`),
  KEY `story_collaborators_story_id_role_index` (`story_id`,`role`),
  KEY `story_collaborators_user_id_role_index` (`user_id`,`role`),
  KEY `story_collaborators_role_index` (`role`),
  KEY `idx_story_collab_user_id` (`user_id`),
  KEY `idx_story_collab_invited_by_user_id` (`invited_by_user_id`),
  CONSTRAINT `story_collaborators_story_id_foreign` FOREIGN KEY (`story_id`) REFERENCES `stories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `story_collaborators`
--

LOCK TABLES `story_collaborators` WRITE;
/*!40000 ALTER TABLE `story_collaborators` DISABLE KEYS */;
INSERT INTO `story_collaborators` VALUES (1,2,'author',2,'2025-08-28 20:27:18','2025-08-28 20:27:18'),(1,3,'beta-reader',2,'2025-12-25 15:56:43','2025-12-25 15:56:43'),(1,6,'author',2,'2025-12-25 15:15:45','2025-12-25 15:15:45'),(2,2,'author',2,'2025-08-29 07:42:34','2025-08-29 07:42:34'),(2,3,'author',2,'2025-12-27 19:32:16','2025-12-27 19:32:16'),(3,2,'author',2,'2025-08-31 16:50:43','2025-08-31 16:50:43'),(5,2,'author',2,'2025-09-14 19:57:32','2025-09-14 19:57:32'),(6,2,'author',2,'2025-09-17 05:24:13','2025-09-17 05:24:13'),(7,2,'author',2,'2025-10-01 19:17:21','2025-10-01 19:17:21'),(7,3,'beta-reader',2,'2025-12-25 20:43:13','2025-12-25 20:43:13'),(7,6,'author',2,'2025-12-25 20:44:36','2025-12-25 20:44:36'),(8,6,'author',6,'2025-10-28 12:43:41','2025-10-28 12:43:41');
/*!40000 ALTER TABLE `story_collaborators` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `story_genres`
--

DROP TABLE IF EXISTS `story_genres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `story_genres` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `story_id` bigint unsigned NOT NULL,
  `story_ref_genre_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `story_genres_story_id_story_ref_genre_id_unique` (`story_id`,`story_ref_genre_id`),
  KEY `story_genres_story_ref_genre_id_foreign` (`story_ref_genre_id`),
  CONSTRAINT `story_genres_story_id_foreign` FOREIGN KEY (`story_id`) REFERENCES `stories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `story_genres_story_ref_genre_id_foreign` FOREIGN KEY (`story_ref_genre_id`) REFERENCES `story_ref_genres` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `story_genres`
--

LOCK TABLES `story_genres` WRITE;
/*!40000 ALTER TABLE `story_genres` DISABLE KEYS */;
INSERT INTO `story_genres` VALUES (1,1,1,'2025-08-28 20:27:18','2025-08-28 20:27:18'),(2,2,1,'2025-08-29 07:42:34','2025-08-29 07:42:34'),(3,3,1,'2025-08-31 16:50:43','2025-08-31 16:50:43'),(6,2,3,'2025-09-14 19:02:48','2025-09-14 19:02:48'),(7,2,2,'2025-09-14 19:20:33','2025-09-14 19:20:33'),(8,5,2,'2025-09-14 19:57:32','2025-09-14 19:57:32'),(9,5,1,'2025-09-14 19:57:32','2025-09-14 19:57:32'),(14,8,1,'2025-10-28 12:43:41','2025-10-28 12:43:41'),(15,8,2,'2025-10-28 12:43:41','2025-10-28 12:43:41'),(17,7,1,'2025-12-25 16:12:06','2025-12-25 16:12:06'),(18,6,1,'2025-12-25 16:21:52','2025-12-25 16:21:52'),(19,6,2,'2025-12-25 16:21:52','2025-12-25 16:21:52');
/*!40000 ALTER TABLE `story_genres` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `story_reading_progress`
--

DROP TABLE IF EXISTS `story_reading_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `story_reading_progress` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `story_id` bigint unsigned NOT NULL,
  `chapter_id` bigint unsigned NOT NULL,
  `read_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `story_reading_progress_user_id_chapter_id_unique` (`user_id`,`chapter_id`),
  KEY `story_reading_progress_story_id_foreign` (`story_id`),
  KEY `story_reading_progress_chapter_id_foreign` (`chapter_id`),
  KEY `story_reading_progress_user_id_story_id_index` (`user_id`,`story_id`),
  KEY `idx_story_reading_progress_user_id` (`user_id`),
  CONSTRAINT `story_reading_progress_chapter_id_foreign` FOREIGN KEY (`chapter_id`) REFERENCES `story_chapters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `story_reading_progress_story_id_foreign` FOREIGN KEY (`story_id`) REFERENCES `stories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `story_reading_progress`
--

LOCK TABLES `story_reading_progress` WRITE;
/*!40000 ALTER TABLE `story_reading_progress` DISABLE KEYS */;
INSERT INTO `story_reading_progress` VALUES (17,6,5,14,'2025-12-27 15:25:56',NULL,NULL),(18,6,2,6,'2025-12-27 15:27:02',NULL,NULL),(19,6,2,7,'2025-12-27 15:27:38',NULL,NULL),(20,4,1,1,'2026-01-11 10:40:11',NULL,NULL);
/*!40000 ALTER TABLE `story_reading_progress` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `story_ref_audiences`
--

DROP TABLE IF EXISTS `story_ref_audiences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `story_ref_audiences` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `order` int unsigned NOT NULL,
  `threshold_age` tinyint unsigned DEFAULT NULL,
  `is_mature_audience` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `story_ref_audiences_slug_unique` (`slug`),
  KEY `story_ref_audiences_order_index` (`order`),
  KEY `story_ref_audiences_is_active_index` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `story_ref_audiences`
--

LOCK TABLES `story_ref_audiences` WRITE;
/*!40000 ALTER TABLE `story_ref_audiences` DISABLE KEYS */;
INSERT INTO `story_ref_audiences` VALUES (1,'All audiences','all-audiences',1,NULL,0,1,'2025-08-28 07:31:18','2025-08-28 07:31:18'),(2,'12+','12',2,NULL,0,1,'2025-09-23 19:27:42','2025-09-23 19:27:42');
/*!40000 ALTER TABLE `story_ref_audiences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `story_ref_copyrights`
--

DROP TABLE IF EXISTS `story_ref_copyrights`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `story_ref_copyrights` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `order` int unsigned NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `story_ref_copyrights_slug_unique` (`slug`),
  KEY `story_ref_copyrights_order_index` (`order`),
  KEY `story_ref_copyrights_is_active_index` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `story_ref_copyrights`
--

LOCK TABLES `story_ref_copyrights` WRITE;
/*!40000 ALTER TABLE `story_ref_copyrights` DISABLE KEYS */;
INSERT INTO `story_ref_copyrights` VALUES (1,'All rights reserved','THIS STORY IS MINE AND NOT YOURS, GET IT?','all-rights-reserved',1,1,'2025-08-28 07:31:18','2025-09-27 19:32:54'),(2,'Limited',NULL,'limited',2,1,'2025-10-02 11:30:03','2025-10-02 12:07:55');
/*!40000 ALTER TABLE `story_ref_copyrights` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `story_ref_feedbacks`
--

DROP TABLE IF EXISTS `story_ref_feedbacks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `story_ref_feedbacks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `order` int unsigned NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `story_ref_feedbacks_slug_unique` (`slug`),
  KEY `story_ref_feedbacks_order_index` (`order`),
  KEY `story_ref_feedbacks_is_active_index` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `story_ref_feedbacks`
--

LOCK TABLES `story_ref_feedbacks` WRITE;
/*!40000 ALTER TABLE `story_ref_feedbacks` DISABLE KEYS */;
INSERT INTO `story_ref_feedbacks` VALUES (1,'Gentle please','gentle-please',NULL,1,1,'2025-08-28 07:31:18','2025-08-28 07:31:18'),(2,'Hit me hard','hit-me-hard',NULL,2,1,'2025-10-05 19:05:36','2025-10-05 19:05:36'),(4,'Only congratulations','only-congratulations',NULL,4,1,'2025-10-05 19:07:55','2025-10-05 19:07:55'),(5,'Only grateful congratulations','only-grateful-congratulations',NULL,5,1,'2025-10-05 19:08:03','2025-10-05 19:08:03'),(6,'Don\'t. Talk. To. Me.','dont-talk-to-me',NULL,6,1,'2025-10-05 19:08:11','2025-10-05 19:08:11');
/*!40000 ALTER TABLE `story_ref_feedbacks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `story_ref_genres`
--

DROP TABLE IF EXISTS `story_ref_genres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `story_ref_genres` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `order` int unsigned NOT NULL DEFAULT '0',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `story_ref_genres_slug_unique` (`slug`),
  KEY `story_ref_genres_is_active_index` (`is_active`),
  KEY `story_ref_genres_order_index` (`order`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `story_ref_genres`
--

LOCK TABLES `story_ref_genres` WRITE;
/*!40000 ALTER TABLE `story_ref_genres` DISABLE KEYS */;
INSERT INTO `story_ref_genres` VALUES (1,'Fantasy','fantasy',1,'Imaginary worlds filled with dragons',1,'2025-08-28 07:31:18','2025-08-28 07:31:18'),(2,'Épistolaire vraiment long','epistolaire-vraiment-long',2,NULL,1,'2025-09-14 19:02:08','2025-09-14 19:02:08'),(3,'Autobiographie','autobiographie',3,NULL,1,'2025-09-14 19:02:22','2025-09-14 19:02:22');
/*!40000 ALTER TABLE `story_ref_genres` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `story_ref_statuses`
--

DROP TABLE IF EXISTS `story_ref_statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `story_ref_statuses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `order` int unsigned NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `story_ref_statuses_slug_unique` (`slug`),
  KEY `story_ref_statuses_order_index` (`order`),
  KEY `story_ref_statuses_is_active_index` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `story_ref_statuses`
--

LOCK TABLES `story_ref_statuses` WRITE;
/*!40000 ALTER TABLE `story_ref_statuses` DISABLE KEYS */;
INSERT INTO `story_ref_statuses` VALUES (1,'First draft but quite long actually','Now I need to write some more','first-draft',1,1,'2025-08-28 07:31:18','2025-10-02 11:38:40');
/*!40000 ALTER TABLE `story_ref_statuses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `story_ref_trigger_warnings`
--

DROP TABLE IF EXISTS `story_ref_trigger_warnings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `story_ref_trigger_warnings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `order` int unsigned NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `story_ref_trigger_warnings_slug_unique` (`slug`),
  KEY `story_ref_trigger_warnings_order_index` (`order`),
  KEY `story_ref_trigger_warnings_is_active_index` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `story_ref_trigger_warnings`
--

LOCK TABLES `story_ref_trigger_warnings` WRITE;
/*!40000 ALTER TABLE `story_ref_trigger_warnings` DISABLE KEYS */;
INSERT INTO `story_ref_trigger_warnings` VALUES (1,'Physical Violence','physical-violence','People are getting hurt, be it with punches or weapons',1,1,'2025-08-28 07:31:18','2025-08-28 07:31:18'),(2,'Milkshakes','milkshakes','There are milkshakes in this story. That\'s disgusting! (unless it\'s a vanilla one)',2,1,'2025-09-12 10:22:41','2025-09-12 10:22:41');
/*!40000 ALTER TABLE `story_ref_trigger_warnings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `story_ref_types`
--

DROP TABLE IF EXISTS `story_ref_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `story_ref_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `order` int unsigned NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `story_ref_types_slug_unique` (`slug`),
  KEY `story_ref_types_order_index` (`order`),
  KEY `story_ref_types_is_active_index` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `story_ref_types`
--

LOCK TABLES `story_ref_types` WRITE;
/*!40000 ALTER TABLE `story_ref_types` DISABLE KEYS */;
INSERT INTO `story_ref_types` VALUES (1,'Novel','novel',1,1,'2025-08-28 07:31:18','2025-08-28 07:31:18');
/*!40000 ALTER TABLE `story_ref_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `story_trigger_warnings`
--

DROP TABLE IF EXISTS `story_trigger_warnings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `story_trigger_warnings` (
  `story_id` bigint unsigned NOT NULL,
  `story_ref_trigger_warning_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`story_id`,`story_ref_trigger_warning_id`),
  KEY `story_trigger_warnings_story_id_index` (`story_id`),
  KEY `idx_story_trigwarn_ref_id` (`story_ref_trigger_warning_id`),
  CONSTRAINT `fk_story_trigwarn_ref_id` FOREIGN KEY (`story_ref_trigger_warning_id`) REFERENCES `story_ref_trigger_warnings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `story_trigger_warnings_story_id_foreign` FOREIGN KEY (`story_id`) REFERENCES `stories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `story_trigger_warnings`
--

LOCK TABLES `story_trigger_warnings` WRITE;
/*!40000 ALTER TABLE `story_trigger_warnings` DISABLE KEYS */;
INSERT INTO `story_trigger_warnings` VALUES (1,1,'2025-09-22 18:59:46','2025-09-22 18:59:46'),(1,2,'2025-09-22 18:59:46','2025-09-22 18:59:46'),(2,2,'2025-09-18 19:24:04','2025-09-18 19:24:04');
/*!40000 ALTER TABLE `story_trigger_warnings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_activation_codes`
--

DROP TABLE IF EXISTS `user_activation_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_activation_codes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(18) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sponsor_user_id` bigint unsigned DEFAULT NULL,
  `used_by_user_id` bigint unsigned DEFAULT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `expires_at` timestamp NULL DEFAULT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_activation_codes_code_unique` (`code`),
  KEY `user_activation_codes_sponsor_user_id_foreign` (`sponsor_user_id`),
  KEY `user_activation_codes_used_by_user_id_foreign` (`used_by_user_id`),
  KEY `user_activation_codes_code_index` (`code`),
  KEY `user_activation_codes_expires_at_index` (`expires_at`),
  KEY `user_activation_codes_used_at_index` (`used_at`),
  CONSTRAINT `user_activation_codes_sponsor_user_id_foreign` FOREIGN KEY (`sponsor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `user_activation_codes_used_by_user_id_foreign` FOREIGN KEY (`used_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_activation_codes`
--

LOCK TABLES `user_activation_codes` WRITE;
/*!40000 ALTER TABLE `user_activation_codes` DISABLE KEYS */;
INSERT INTO `user_activation_codes` VALUES (1,'VZVH-1PPE7AHN-XUFH',NULL,4,NULL,NULL,'2025-09-08 19:37:08','2025-08-28 07:32:36','2025-09-08 19:37:08'),(2,'ASQ1-KKLHIM0K-UTJO',NULL,5,NULL,NULL,'2025-09-08 20:31:43','2025-08-30 05:39:28','2025-09-08 20:31:43'),(3,'0ZT4-QJTGRMCN-PZSN',NULL,6,NULL,NULL,'2025-09-12 15:01:38','2025-09-08 19:35:58','2025-09-12 15:01:38'),(4,'D94Z-XC36ITYL-N9A6',NULL,7,NULL,NULL,'2025-09-14 06:43:35','2025-09-14 06:43:19','2025-09-14 06:43:35');
/*!40000 ALTER TABLE `user_activation_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_promotion_request`
--

DROP TABLE IF EXISTS `user_promotion_request`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_promotion_request` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `status` enum('pending','accepted','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `comment_count` int unsigned NOT NULL,
  `requested_at` timestamp NOT NULL,
  `decided_at` timestamp NULL DEFAULT NULL,
  `decided_by` bigint unsigned DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_promotion_request_decided_by_foreign` (`decided_by`),
  KEY `user_promotion_request_user_id_requested_at_index` (`user_id`,`requested_at`),
  KEY `user_promotion_request_status_index` (`status`),
  CONSTRAINT `user_promotion_request_decided_by_foreign` FOREIGN KEY (`decided_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `user_promotion_request_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_promotion_request`
--

LOCK TABLES `user_promotion_request` WRITE;
/*!40000 ALTER TABLE `user_promotion_request` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_promotion_request` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `terms_accepted_at` timestamp NULL DEFAULT NULL,
  `is_under_15` tinyint(1) NOT NULL DEFAULT '0',
  `parental_authorization_verified_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (2,'fred@hemit.fr','2026-01-11 10:33:51','2025-12-20 19:04:28',0,NULL,1,'$2y$12$VBThRTLI9APCLcHR.K0kB.HTc2CoIdIiR5Q3whQXBelY3nULdwIfm','5bNXnU2FP0doaebwlS82VMYIC5TKPm9PTXfm9MtGD5Sitk2B5nJXDzln66Xq','2025-08-28 07:33:07','2026-01-11 10:33:51'),(3,'alice@hemit.fr','2025-08-30 05:41:30','2025-12-25 15:19:46',0,NULL,1,'$2y$12$B0xdAV.dvD.lI8jJ/upBmukeXJI5abIFVAEjzWkG4wm7gPNJ5faQm',NULL,'2025-08-30 05:39:53','2025-12-25 15:19:46'),(4,'bob@hemit.fr','2025-09-08 19:49:44','2026-01-11 10:39:58',0,NULL,1,'$2y$12$ElVqHb2K9fW.Vu3ghkF6yO8yalGdG4wG4zal5lJEOqHObWH6nFDuK',NULL,'2025-09-08 19:37:08','2026-01-11 10:39:58'),(5,'carol@hemit.fr',NULL,NULL,0,NULL,1,'$2y$12$SfyV5ElsS5UCTdNnKh1ZsO.jMe9LDXPbbCYWOeUCoqPiVhbQCwtn.',NULL,'2025-09-08 20:31:43','2025-09-08 20:31:43'),(6,'daniel@hemit.fr','2025-09-12 15:06:05','2025-12-23 19:05:47',0,NULL,1,'$2y$12$KBcqvMWrUJmwXVXfbvbrD.vvlZ9DZ1YcHdfSI0/G5zmtUHFd8z00K','ydwf4nOjiJG8kb1xYhXwMAn1kNcztlF7JgCsSNSBMYlrgRhaxNkt2h6Iiekd','2025-09-12 15:01:38','2025-12-23 19:05:47'),(7,'elliot@hemit.fr',NULL,NULL,0,NULL,1,'$2y$12$EJBWGd43JdY19meaDrodi.7xkQwP7wPajiFvW5PIoGdmsoA88zNsO',NULL,'2025-09-14 06:43:35','2025-09-14 06:43:35'),(8,'emily@hemit.fr','2026-01-11 10:31:20','2026-01-11 10:30:56',0,NULL,1,'$2y$12$grS90iYJdRNZTFmvLK10pOwBT4B/KU73xsoLuT07Mf8ddlChOaos2',NULL,'2026-01-11 10:30:56','2026-01-11 10:31:20'),(9,'gina@hemit.fr','2026-01-11 10:35:16','2026-01-11 10:34:57',0,NULL,1,'$2y$12$mZ.kQCBa87lo7zD8jFkZA.E1RFwDohf/WQpO.jkg4gsnTIWP5Emzm',NULL,'2026-01-11 10:34:57','2026-01-11 10:35:16');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-01-11 11:10:59
