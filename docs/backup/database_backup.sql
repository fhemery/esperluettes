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
INSERT INTO `cache` VALUES ('le-jardin-des-esperluettes-cache-profile_by_user_id:1','O:42:\"App\\Domains\\Profile\\Private\\Models\\Profile\":35:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:16:\"profile_profiles\";s:13:\"\0*\0primaryKey\";s:7:\"user_id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:0;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:1;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:15:{s:7:\"user_id\";i:1;s:4:\"slug\";s:5:\"admin\";s:12:\"display_name\";s:5:\"Admin\";s:20:\"profile_picture_path\";N;s:15:\"facebook_handle\";N;s:8:\"x_handle\";N;s:16:\"instagram_handle\";N;s:14:\"youtube_handle\";N;s:13:\"tiktok_handle\";N;s:14:\"bluesky_handle\";N;s:15:\"mastodon_handle\";N;s:11:\"description\";s:13:\"Admin profile\";s:10:\"created_at\";s:19:\"2025-08-28 07:31:18\";s:10:\"updated_at\";s:19:\"2025-08-28 07:31:18\";s:10:\"deleted_at\";N;}s:11:\"\0*\0original\";a:15:{s:7:\"user_id\";i:1;s:4:\"slug\";s:5:\"admin\";s:12:\"display_name\";s:5:\"Admin\";s:20:\"profile_picture_path\";N;s:15:\"facebook_handle\";N;s:8:\"x_handle\";N;s:16:\"instagram_handle\";N;s:14:\"youtube_handle\";N;s:13:\"tiktok_handle\";N;s:14:\"bluesky_handle\";N;s:15:\"mastodon_handle\";N;s:11:\"description\";s:13:\"Admin profile\";s:10:\"created_at\";s:19:\"2025-08-28 07:31:18\";s:10:\"updated_at\";s:19:\"2025-08-28 07:31:18\";s:10:\"deleted_at\";N;}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:2:{s:7:\"user_id\";s:7:\"integer\";s:10:\"deleted_at\";s:8:\"datetime\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:12:{i:0;s:7:\"user_id\";i:1;s:4:\"slug\";i:2;s:12:\"display_name\";i:3;s:20:\"profile_picture_path\";i:4;s:15:\"facebook_handle\";i:5;s:8:\"x_handle\";i:6;s:16:\"instagram_handle\";i:7;s:14:\"youtube_handle\";i:8;s:13:\"tiktok_handle\";i:9;s:14:\"bluesky_handle\";i:10;s:15:\"mastodon_handle\";i:11;s:11:\"description\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}s:5:\"roles\";a:0:{}s:16:\"\0*\0forceDeleting\";b:0;}',1778009627),('le-jardin-des-esperluettes-cache-profile_by_user_id:2','O:42:\"App\\Domains\\Profile\\Private\\Models\\Profile\":35:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:16:\"profile_profiles\";s:13:\"\0*\0primaryKey\";s:7:\"user_id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:0;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:1;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:15:{s:7:\"user_id\";i:2;s:4:\"slug\";s:4:\"fred\";s:12:\"display_name\";s:4:\"Fred\";s:20:\"profile_picture_path\";s:33:\"profile_pictures/2_1756907799.jpg\";s:15:\"facebook_handle\";s:2:\"lx\";s:8:\"x_handle\";s:4:\"fred\";s:16:\"instagram_handle\";s:4:\"fred\";s:14:\"youtube_handle\";s:4:\"fred\";s:13:\"tiktok_handle\";s:4:\"fred\";s:14:\"bluesky_handle\";s:4:\"fred\";s:15:\"mastodon_handle\";s:13:\"fred@fred.com\";s:11:\"description\";N;s:10:\"created_at\";s:19:\"2025-08-28 07:33:07\";s:10:\"updated_at\";s:19:\"2026-05-05 19:16:04\";s:10:\"deleted_at\";N;}s:11:\"\0*\0original\";a:15:{s:7:\"user_id\";i:2;s:4:\"slug\";s:4:\"fred\";s:12:\"display_name\";s:4:\"Fred\";s:20:\"profile_picture_path\";s:33:\"profile_pictures/2_1756907799.jpg\";s:15:\"facebook_handle\";s:2:\"lx\";s:8:\"x_handle\";s:4:\"fred\";s:16:\"instagram_handle\";s:4:\"fred\";s:14:\"youtube_handle\";s:4:\"fred\";s:13:\"tiktok_handle\";s:4:\"fred\";s:14:\"bluesky_handle\";s:4:\"fred\";s:15:\"mastodon_handle\";s:13:\"fred@fred.com\";s:11:\"description\";N;s:10:\"created_at\";s:19:\"2025-08-28 07:33:07\";s:10:\"updated_at\";s:19:\"2026-05-05 19:16:04\";s:10:\"deleted_at\";N;}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:2:{s:7:\"user_id\";s:7:\"integer\";s:10:\"deleted_at\";s:8:\"datetime\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:12:{i:0;s:7:\"user_id\";i:1;s:4:\"slug\";i:2;s:12:\"display_name\";i:3;s:20:\"profile_picture_path\";i:4;s:15:\"facebook_handle\";i:5;s:8:\"x_handle\";i:6;s:16:\"instagram_handle\";i:7;s:14:\"youtube_handle\";i:8;s:13:\"tiktok_handle\";i:9;s:14:\"bluesky_handle\";i:10;s:15:\"mastodon_handle\";i:11;s:11:\"description\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}s:5:\"roles\";a:0:{}s:16:\"\0*\0forceDeleting\";b:0;}',1778009627),('le-jardin-des-esperluettes-cache-profile_by_user_id:3','O:42:\"App\\Domains\\Profile\\Private\\Models\\Profile\":35:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:16:\"profile_profiles\";s:13:\"\0*\0primaryKey\";s:7:\"user_id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:0;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:1;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:15:{s:7:\"user_id\";i:3;s:4:\"slug\";s:5:\"alice\";s:12:\"display_name\";s:5:\"Alice\";s:20:\"profile_picture_path\";s:33:\"profile_pictures/3_1756908205.jpg\";s:15:\"facebook_handle\";N;s:8:\"x_handle\";N;s:16:\"instagram_handle\";N;s:14:\"youtube_handle\";N;s:13:\"tiktok_handle\";N;s:14:\"bluesky_handle\";N;s:15:\"mastodon_handle\";N;s:11:\"description\";N;s:10:\"created_at\";s:19:\"2025-08-30 05:39:53\";s:10:\"updated_at\";s:19:\"2026-05-05 19:19:31\";s:10:\"deleted_at\";N;}s:11:\"\0*\0original\";a:15:{s:7:\"user_id\";i:3;s:4:\"slug\";s:5:\"alice\";s:12:\"display_name\";s:5:\"Alice\";s:20:\"profile_picture_path\";s:33:\"profile_pictures/3_1756908205.jpg\";s:15:\"facebook_handle\";N;s:8:\"x_handle\";N;s:16:\"instagram_handle\";N;s:14:\"youtube_handle\";N;s:13:\"tiktok_handle\";N;s:14:\"bluesky_handle\";N;s:15:\"mastodon_handle\";N;s:11:\"description\";N;s:10:\"created_at\";s:19:\"2025-08-30 05:39:53\";s:10:\"updated_at\";s:19:\"2026-05-05 19:19:31\";s:10:\"deleted_at\";N;}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:2:{s:7:\"user_id\";s:7:\"integer\";s:10:\"deleted_at\";s:8:\"datetime\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:12:{i:0;s:7:\"user_id\";i:1;s:4:\"slug\";i:2;s:12:\"display_name\";i:3;s:20:\"profile_picture_path\";i:4;s:15:\"facebook_handle\";i:5;s:8:\"x_handle\";i:6;s:16:\"instagram_handle\";i:7;s:14:\"youtube_handle\";i:8;s:13:\"tiktok_handle\";i:9;s:14:\"bluesky_handle\";i:10;s:15:\"mastodon_handle\";i:11;s:11:\"description\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}s:5:\"roles\";a:0:{}s:16:\"\0*\0forceDeleting\";b:0;}',1778009627),('le-jardin-des-esperluettes-cache-profile_by_user_id:4','O:42:\"App\\Domains\\Profile\\Private\\Models\\Profile\":35:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:16:\"profile_profiles\";s:13:\"\0*\0primaryKey\";s:7:\"user_id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:0;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:1;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:15:{s:7:\"user_id\";i:4;s:4:\"slug\";s:3:\"bob\";s:12:\"display_name\";s:3:\"Bob\";s:20:\"profile_picture_path\";s:22:\"profile_pictures/4.svg\";s:15:\"facebook_handle\";N;s:8:\"x_handle\";N;s:16:\"instagram_handle\";N;s:14:\"youtube_handle\";N;s:13:\"tiktok_handle\";N;s:14:\"bluesky_handle\";N;s:15:\"mastodon_handle\";N;s:11:\"description\";N;s:10:\"created_at\";s:19:\"2025-09-08 19:37:08\";s:10:\"updated_at\";s:19:\"2026-05-05 19:15:43\";s:10:\"deleted_at\";N;}s:11:\"\0*\0original\";a:15:{s:7:\"user_id\";i:4;s:4:\"slug\";s:3:\"bob\";s:12:\"display_name\";s:3:\"Bob\";s:20:\"profile_picture_path\";s:22:\"profile_pictures/4.svg\";s:15:\"facebook_handle\";N;s:8:\"x_handle\";N;s:16:\"instagram_handle\";N;s:14:\"youtube_handle\";N;s:13:\"tiktok_handle\";N;s:14:\"bluesky_handle\";N;s:15:\"mastodon_handle\";N;s:11:\"description\";N;s:10:\"created_at\";s:19:\"2025-09-08 19:37:08\";s:10:\"updated_at\";s:19:\"2026-05-05 19:15:43\";s:10:\"deleted_at\";N;}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:2:{s:7:\"user_id\";s:7:\"integer\";s:10:\"deleted_at\";s:8:\"datetime\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:12:{i:0;s:7:\"user_id\";i:1;s:4:\"slug\";i:2;s:12:\"display_name\";i:3;s:20:\"profile_picture_path\";i:4;s:15:\"facebook_handle\";i:5;s:8:\"x_handle\";i:6;s:16:\"instagram_handle\";i:7;s:14:\"youtube_handle\";i:8;s:13:\"tiktok_handle\";i:9;s:14:\"bluesky_handle\";i:10;s:15:\"mastodon_handle\";i:11;s:11:\"description\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}s:5:\"roles\";a:0:{}s:16:\"\0*\0forceDeleting\";b:0;}',1778009627),('le-jardin-des-esperluettes-cache-profile_by_user_id:5','O:42:\"App\\Domains\\Profile\\Private\\Models\\Profile\":35:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:16:\"profile_profiles\";s:13:\"\0*\0primaryKey\";s:7:\"user_id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:0;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:1;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:15:{s:7:\"user_id\";i:5;s:4:\"slug\";s:5:\"carol\";s:12:\"display_name\";s:5:\"Carol\";s:20:\"profile_picture_path\";s:22:\"profile_pictures/5.svg\";s:15:\"facebook_handle\";N;s:8:\"x_handle\";N;s:16:\"instagram_handle\";N;s:14:\"youtube_handle\";N;s:13:\"tiktok_handle\";N;s:14:\"bluesky_handle\";N;s:15:\"mastodon_handle\";N;s:11:\"description\";N;s:10:\"created_at\";s:19:\"2025-09-08 20:31:43\";s:10:\"updated_at\";s:19:\"2025-09-08 20:31:43\";s:10:\"deleted_at\";N;}s:11:\"\0*\0original\";a:15:{s:7:\"user_id\";i:5;s:4:\"slug\";s:5:\"carol\";s:12:\"display_name\";s:5:\"Carol\";s:20:\"profile_picture_path\";s:22:\"profile_pictures/5.svg\";s:15:\"facebook_handle\";N;s:8:\"x_handle\";N;s:16:\"instagram_handle\";N;s:14:\"youtube_handle\";N;s:13:\"tiktok_handle\";N;s:14:\"bluesky_handle\";N;s:15:\"mastodon_handle\";N;s:11:\"description\";N;s:10:\"created_at\";s:19:\"2025-09-08 20:31:43\";s:10:\"updated_at\";s:19:\"2025-09-08 20:31:43\";s:10:\"deleted_at\";N;}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:2:{s:7:\"user_id\";s:7:\"integer\";s:10:\"deleted_at\";s:8:\"datetime\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:12:{i:0;s:7:\"user_id\";i:1;s:4:\"slug\";i:2;s:12:\"display_name\";i:3;s:20:\"profile_picture_path\";i:4;s:15:\"facebook_handle\";i:5;s:8:\"x_handle\";i:6;s:16:\"instagram_handle\";i:7;s:14:\"youtube_handle\";i:8;s:13:\"tiktok_handle\";i:9;s:14:\"bluesky_handle\";i:10;s:15:\"mastodon_handle\";i:11;s:11:\"description\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}s:5:\"roles\";a:0:{}s:16:\"\0*\0forceDeleting\";b:0;}',1778009627),('le-jardin-des-esperluettes-cache-profile_by_user_id:6','O:42:\"App\\Domains\\Profile\\Private\\Models\\Profile\":35:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:16:\"profile_profiles\";s:13:\"\0*\0primaryKey\";s:7:\"user_id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:0;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:1;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:15:{s:7:\"user_id\";i:6;s:4:\"slug\";s:6:\"daniel\";s:12:\"display_name\";s:6:\"Daniel\";s:20:\"profile_picture_path\";N;s:15:\"facebook_handle\";N;s:8:\"x_handle\";N;s:16:\"instagram_handle\";N;s:14:\"youtube_handle\";N;s:13:\"tiktok_handle\";N;s:14:\"bluesky_handle\";N;s:15:\"mastodon_handle\";N;s:11:\"description\";N;s:10:\"created_at\";s:19:\"2025-09-12 15:01:38\";s:10:\"updated_at\";s:19:\"2026-01-28 05:09:59\";s:10:\"deleted_at\";N;}s:11:\"\0*\0original\";a:15:{s:7:\"user_id\";i:6;s:4:\"slug\";s:6:\"daniel\";s:12:\"display_name\";s:6:\"Daniel\";s:20:\"profile_picture_path\";N;s:15:\"facebook_handle\";N;s:8:\"x_handle\";N;s:16:\"instagram_handle\";N;s:14:\"youtube_handle\";N;s:13:\"tiktok_handle\";N;s:14:\"bluesky_handle\";N;s:15:\"mastodon_handle\";N;s:11:\"description\";N;s:10:\"created_at\";s:19:\"2025-09-12 15:01:38\";s:10:\"updated_at\";s:19:\"2026-01-28 05:09:59\";s:10:\"deleted_at\";N;}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:2:{s:7:\"user_id\";s:7:\"integer\";s:10:\"deleted_at\";s:8:\"datetime\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:12:{i:0;s:7:\"user_id\";i:1;s:4:\"slug\";i:2;s:12:\"display_name\";i:3;s:20:\"profile_picture_path\";i:4;s:15:\"facebook_handle\";i:5;s:8:\"x_handle\";i:6;s:16:\"instagram_handle\";i:7;s:14:\"youtube_handle\";i:8;s:13:\"tiktok_handle\";i:9;s:14:\"bluesky_handle\";i:10;s:15:\"mastodon_handle\";i:11;s:11:\"description\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}s:5:\"roles\";a:0:{}s:16:\"\0*\0forceDeleting\";b:0;}',1778009627),('le-jardin-des-esperluettes-cache-profile_by_user_id:7','O:42:\"App\\Domains\\Profile\\Private\\Models\\Profile\":35:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:16:\"profile_profiles\";s:13:\"\0*\0primaryKey\";s:7:\"user_id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:0;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:1;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:15:{s:7:\"user_id\";i:7;s:4:\"slug\";s:6:\"elliot\";s:12:\"display_name\";s:6:\"Elliot\";s:20:\"profile_picture_path\";s:22:\"profile_pictures/7.svg\";s:15:\"facebook_handle\";N;s:8:\"x_handle\";N;s:16:\"instagram_handle\";N;s:14:\"youtube_handle\";N;s:13:\"tiktok_handle\";N;s:14:\"bluesky_handle\";N;s:15:\"mastodon_handle\";N;s:11:\"description\";N;s:10:\"created_at\";s:19:\"2025-09-14 06:43:35\";s:10:\"updated_at\";s:19:\"2025-09-14 06:43:35\";s:10:\"deleted_at\";N;}s:11:\"\0*\0original\";a:15:{s:7:\"user_id\";i:7;s:4:\"slug\";s:6:\"elliot\";s:12:\"display_name\";s:6:\"Elliot\";s:20:\"profile_picture_path\";s:22:\"profile_pictures/7.svg\";s:15:\"facebook_handle\";N;s:8:\"x_handle\";N;s:16:\"instagram_handle\";N;s:14:\"youtube_handle\";N;s:13:\"tiktok_handle\";N;s:14:\"bluesky_handle\";N;s:15:\"mastodon_handle\";N;s:11:\"description\";N;s:10:\"created_at\";s:19:\"2025-09-14 06:43:35\";s:10:\"updated_at\";s:19:\"2025-09-14 06:43:35\";s:10:\"deleted_at\";N;}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:2:{s:7:\"user_id\";s:7:\"integer\";s:10:\"deleted_at\";s:8:\"datetime\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:12:{i:0;s:7:\"user_id\";i:1;s:4:\"slug\";i:2;s:12:\"display_name\";i:3;s:20:\"profile_picture_path\";i:4;s:15:\"facebook_handle\";i:5;s:8:\"x_handle\";i:6;s:16:\"instagram_handle\";i:7;s:14:\"youtube_handle\";i:8;s:13:\"tiktok_handle\";i:9;s:14:\"bluesky_handle\";i:10;s:15:\"mastodon_handle\";i:11;s:11:\"description\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}s:5:\"roles\";a:0:{}s:16:\"\0*\0forceDeleting\";b:0;}',1778009627),('le-jardin-des-esperluettes-cache-profile_by_user_id:8','O:42:\"App\\Domains\\Profile\\Private\\Models\\Profile\":35:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:16:\"profile_profiles\";s:13:\"\0*\0primaryKey\";s:7:\"user_id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:0;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:1;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:15:{s:7:\"user_id\";i:8;s:4:\"slug\";s:6:\"harold\";s:12:\"display_name\";s:6:\"harold\";s:20:\"profile_picture_path\";s:22:\"profile_pictures/8.svg\";s:15:\"facebook_handle\";N;s:8:\"x_handle\";N;s:16:\"instagram_handle\";N;s:14:\"youtube_handle\";N;s:13:\"tiktok_handle\";N;s:14:\"bluesky_handle\";N;s:15:\"mastodon_handle\";N;s:11:\"description\";N;s:10:\"created_at\";s:19:\"2025-12-03 19:32:24\";s:10:\"updated_at\";s:19:\"2025-12-03 19:32:24\";s:10:\"deleted_at\";N;}s:11:\"\0*\0original\";a:15:{s:7:\"user_id\";i:8;s:4:\"slug\";s:6:\"harold\";s:12:\"display_name\";s:6:\"harold\";s:20:\"profile_picture_path\";s:22:\"profile_pictures/8.svg\";s:15:\"facebook_handle\";N;s:8:\"x_handle\";N;s:16:\"instagram_handle\";N;s:14:\"youtube_handle\";N;s:13:\"tiktok_handle\";N;s:14:\"bluesky_handle\";N;s:15:\"mastodon_handle\";N;s:11:\"description\";N;s:10:\"created_at\";s:19:\"2025-12-03 19:32:24\";s:10:\"updated_at\";s:19:\"2025-12-03 19:32:24\";s:10:\"deleted_at\";N;}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:2:{s:7:\"user_id\";s:7:\"integer\";s:10:\"deleted_at\";s:8:\"datetime\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:12:{i:0;s:7:\"user_id\";i:1;s:4:\"slug\";i:2;s:12:\"display_name\";i:3;s:20:\"profile_picture_path\";i:4;s:15:\"facebook_handle\";i:5;s:8:\"x_handle\";i:6;s:16:\"instagram_handle\";i:7;s:14:\"youtube_handle\";i:8;s:13:\"tiktok_handle\";i:9;s:14:\"bluesky_handle\";i:10;s:15:\"mastodon_handle\";i:11;s:11:\"description\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}s:5:\"roles\";a:0:{}s:16:\"\0*\0forceDeleting\";b:0;}',1778009627),('le-jardin-des-esperluettes-cache-profile_by_user_id:9','O:42:\"App\\Domains\\Profile\\Private\\Models\\Profile\":35:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:16:\"profile_profiles\";s:13:\"\0*\0primaryKey\";s:7:\"user_id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:0;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:1;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:15:{s:7:\"user_id\";i:9;s:4:\"slug\";s:8:\"isabella\";s:12:\"display_name\";s:8:\"Isabella\";s:20:\"profile_picture_path\";s:22:\"profile_pictures/9.svg\";s:15:\"facebook_handle\";N;s:8:\"x_handle\";N;s:16:\"instagram_handle\";N;s:14:\"youtube_handle\";N;s:13:\"tiktok_handle\";N;s:14:\"bluesky_handle\";N;s:15:\"mastodon_handle\";N;s:11:\"description\";N;s:10:\"created_at\";s:19:\"2025-12-04 09:04:17\";s:10:\"updated_at\";s:19:\"2025-12-04 09:04:17\";s:10:\"deleted_at\";N;}s:11:\"\0*\0original\";a:15:{s:7:\"user_id\";i:9;s:4:\"slug\";s:8:\"isabella\";s:12:\"display_name\";s:8:\"Isabella\";s:20:\"profile_picture_path\";s:22:\"profile_pictures/9.svg\";s:15:\"facebook_handle\";N;s:8:\"x_handle\";N;s:16:\"instagram_handle\";N;s:14:\"youtube_handle\";N;s:13:\"tiktok_handle\";N;s:14:\"bluesky_handle\";N;s:15:\"mastodon_handle\";N;s:11:\"description\";N;s:10:\"created_at\";s:19:\"2025-12-04 09:04:17\";s:10:\"updated_at\";s:19:\"2025-12-04 09:04:17\";s:10:\"deleted_at\";N;}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:2:{s:7:\"user_id\";s:7:\"integer\";s:10:\"deleted_at\";s:8:\"datetime\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:12:{i:0;s:7:\"user_id\";i:1;s:4:\"slug\";i:2;s:12:\"display_name\";i:3;s:20:\"profile_picture_path\";i:4;s:15:\"facebook_handle\";i:5;s:8:\"x_handle\";i:6;s:16:\"instagram_handle\";i:7;s:14:\"youtube_handle\";i:8;s:13:\"tiktok_handle\";i:9;s:14:\"bluesky_handle\";i:10;s:15:\"mastodon_handle\";i:11;s:11:\"description\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}s:5:\"roles\";a:0:{}s:16:\"\0*\0forceDeleting\";b:0;}',1778009627),('le-jardin-des-esperluettes-cache-user_settings:2','a:2:{s:17:\"general.interline\";s:4:\"high\";s:24:\"readlist.hide-up-to-date\";s:1:\"1\";}',2093369025);
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
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `image_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activity_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calendar_activities`
--

LOCK TABLES `calendar_activities` WRITE;
/*!40000 ALTER TABLE `calendar_activities` DISABLE KEYS */;
INSERT INTO `calendar_activities` VALUES (1,'Secret Santa 2025','secret-santa-2025-1','<p>Participez au Secret Santa, et offrez ou recevez un texte dédié</p>\n',NULL,'secret-gift','[\"user\", \"user-confirmed\"]',1,NULL,'2025-12-12 00:00:00','2025-12-12 00:00:00','2025-12-16 00:00:00','2025-12-18 00:00:00',2,'2025-12-13 18:42:58','2025-12-13 18:53:00');
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
  `type` enum('flower','blocked') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `flower_image` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `planted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_cell_per_activity` (`activity_id`,`x`,`y`),
  KEY `calendar_jardino_garden_cells_activity_id_index` (`activity_id`),
  KEY `calendar_jardino_garden_cells_activity_id_user_id_index` (`activity_id`,`user_id`),
  CONSTRAINT `calendar_jardino_garden_cells_activity_id_foreign` FOREIGN KEY (`activity_id`) REFERENCES `calendar_activities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calendar_jardino_garden_cells`
--

LOCK TABLES `calendar_jardino_garden_cells` WRITE;
/*!40000 ALTER TABLE `calendar_jardino_garden_cells` DISABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calendar_jardino_goals`
--

LOCK TABLES `calendar_jardino_goals` WRITE;
/*!40000 ALTER TABLE `calendar_jardino_goals` DISABLE KEYS */;
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
  `story_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calendar_jardino_story_snapshots`
--

LOCK TABLES `calendar_jardino_story_snapshots` WRITE;
/*!40000 ALTER TABLE `calendar_jardino_story_snapshots` DISABLE KEYS */;
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
  `gift_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `gift_image_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gift_sound_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sg_assignments_giver_unique` (`activity_id`,`giver_user_id`),
  UNIQUE KEY `sg_assignments_recipient_unique` (`activity_id`,`recipient_user_id`),
  KEY `calendar_secret_gift_assignments_giver_user_id_index` (`giver_user_id`),
  KEY `calendar_secret_gift_assignments_recipient_user_id_index` (`recipient_user_id`),
  CONSTRAINT `calendar_secret_gift_assignments_activity_id_foreign` FOREIGN KEY (`activity_id`) REFERENCES `calendar_activities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calendar_secret_gift_assignments`
--

LOCK TABLES `calendar_secret_gift_assignments` WRITE;
/*!40000 ALTER TABLE `calendar_secret_gift_assignments` DISABLE KEYS */;
INSERT INTO `calendar_secret_gift_assignments` VALUES (1,1,2,6,'<p>Test 2</p>','calendar/secret-gift/1/2.png',NULL,'2025-12-13 18:52:28','2025-12-13 19:04:19'),(2,1,6,4,NULL,NULL,NULL,'2025-12-13 18:52:28','2025-12-13 18:52:28'),(3,1,4,2,NULL,NULL,NULL,'2025-12-13 18:52:28','2025-12-13 18:52:28');
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
  `preferences` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
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
INSERT INTO `calendar_secret_gift_participants` VALUES (1,1,2,'<p>J\'aime : les pommes</p><p>Je n\'aime pas: <strong>les poires</strong></p>','2025-12-13 18:51:17','2025-12-13 18:51:17'),(2,1,6,'<p>J\'aime : les romans de fantaisie</p><p>Je n\'aime pas: <strong>la poésie</strong></p>','2025-12-13 18:51:17','2025-12-13 18:51:17'),(3,1,4,'<p>J\'aime : les plumes</p><p>Je n\'aime pas: <strong>le plomb</strong></p>','2025-12-13 18:52:11','2025-12-13 18:52:11');
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
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comments`
--

LOCK TABLES `comments` WRITE;
/*!40000 ALTER TABLE `comments` DISABLE KEYS */;
INSERT INTO `comments` VALUES (1,'chapter',1,2,NULL,1,'<p>You mean I can post ?</p>',NULL,'2025-09-02 20:14:05','2025-09-02 20:14:05',NULL),(2,'chapter',1,2,NULL,1,'<p>Oh, I did not think about trying</p>',NULL,'2025-09-02 20:14:20','2025-09-02 20:14:20',NULL),(3,'chapter',1,2,NULL,1,'<p>This is really cool</p>',NULL,'2025-09-02 20:14:29','2025-09-02 20:14:29',NULL),(4,'chapter',1,2,NULL,1,'<p>Let\'s add a few more</p>',NULL,'2025-09-02 20:14:38','2025-09-02 20:14:38',NULL),(5,'chapter',1,2,NULL,1,'<p>And one more</p>',NULL,'2025-09-02 20:14:49','2025-09-02 20:14:49',NULL),(6,'chapter',1,2,NULL,1,'<p>Post?</p>',NULL,'2025-09-02 20:19:14','2025-09-02 20:19:14',NULL),(7,'chapter',1,2,NULL,1,'<p>More</p>',NULL,'2025-09-03 07:30:52','2025-09-03 07:30:52',NULL),(8,'chapter',1,2,NULL,1,'<p>MOAR</p>',NULL,'2025-09-03 07:31:23','2025-09-03 07:31:23',NULL),(9,'chapter',1,2,NULL,1,'<p>9</p>',NULL,'2025-09-03 07:31:33','2025-09-03 07:31:33',NULL),(10,'chapter',1,2,NULL,1,'<p>10</p>',NULL,'2025-09-03 07:31:37','2025-09-03 07:31:37',NULL),(11,'chapter',1,2,NULL,1,'<p>11</p>',NULL,'2025-09-03 07:31:41','2025-09-03 07:31:41',NULL),(12,'chapter',1,2,NULL,1,'<p>12</p>',NULL,'2025-09-03 13:35:59','2025-09-03 13:35:59',NULL),(13,'chapter',1,2,11,1,'<p>Blablabla</p>',NULL,'2025-09-03 13:40:00','2025-09-03 13:40:00',NULL),(14,'chapter',1,3,NULL,1,'<p>Quelle claque absolue ! La quintessence de la fantaisie moderne, rien de moins. Le style est fabuleux, d’une fluidité étincelante, avec des images si vives qu’elles crépitent à chaque ligne. Les personnages sont exceptionnels, <strong>inoubliables</strong>, taillés dans une matière rare où l’intime flirte avec le mythique. </p>\n\n<p><br></p>\n\n<p>On tourne les pages avec l’impression d’assister à une constellation qui se forme sous nos yeux : chaque mot devient étoile, chaque phrase, une orbite parfaite. L’intrigue fuse, serpente, éclate, puis se recompose avec une maîtrise insolente, comme si la gravité narrative obéissait au seul caprice de l’auteur. On rit, on frissonne, on s’émerveille — parfois tout cela en même temps. Les dialogues claquent, les silences parlent, les descriptions respirent une poésie luxuriante sans jamais étouffer le rythme. C’est un festin littéraire, un carnaval d’émotions, une cathédrale d’imaginaire où chaque chapiteau raconte sa légende. </p>\n\n<p><br></p>\n\n<p>Et quand on croit toucher au sommet, un nouveau panorama s’ouvre, plus vaste, plus lumineux. Je referme ce chapitre avec le cœur battant et la certitude ravie d’avoir trouvé un phare dans la brume de nos lectures quotidiennes. Magistral, généreux, irrésistible : que la suite arrive vite, je suis déjà conquis.</p>\n\n<p><br></p>\n\n<p><em>– Bien sûr que non, je suis sûre que tous ces animaux m\'adoreraient. »</em></p>\n\n<p>Ah la la, c\'est tellement drôle !</p>',NULL,'2025-09-03 14:03:08','2025-09-03 14:03:08',NULL),(15,'chapter',1,2,14,1,'<p>Merci pour ce commentaire. C\'est vrai que je suis un auteur d\'exception, et je n\'attendais rien de moi qu\'un retour aussi dithirambik (euh... ça s\'écrit comment déjà ? Y\'a un h quelque part, non ? et un y ? et le k est en trop ?)</p>',NULL,'2025-09-03 14:06:11','2025-09-08 19:02:26',NULL),(16,'chapter',3,3,NULL,1,'<p>Voyons voir</p>\n\n<blockquote>ddd</blockquote>\n\n<p>J\'ai rien compris...</p>',NULL,'2025-09-03 14:10:48','2025-09-03 14:10:48',NULL),(17,'chapter',1,2,13,1,'<p>blablablablabla même !</p>',NULL,'2025-09-03 14:38:13','2025-09-03 14:38:13',NULL),(18,'chapter',1,2,14,1,'<p>Au fait, je suis le meilleur!</p>',NULL,'2025-09-03 15:28:47','2025-09-07 21:09:18',NULL),(19,'chapter',1,2,12,1,'<p>Et ça, ça marche toujours ?</p>',NULL,'2025-09-03 15:29:03','2025-09-03 15:29:03',NULL),(20,'chapter',1,2,7,1,'<p>Ok now I need a very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very long comment</p>',NULL,'2025-09-04 10:11:02','2025-09-04 10:11:02',NULL),(21,'chapter',1,3,14,1,'<p>Sans aucun doute.</p>',NULL,'2025-09-06 19:09:23','2025-09-06 19:09:23',NULL),(22,'chapter',1,3,14,1,'<ul><li>Point 1</li><li>Point 2</li></ul>',NULL,'2025-09-07 05:25:32','2025-09-07 05:25:32',NULL),(23,'chapter',3,2,16,1,'<p><strong>dsadsa <em>dsadsa</em>dsadsa <em>dsadsa</em>dsadsa <em>dsadsa</em>dsadsa <em>dsadsa </em></strong></p>',NULL,'2025-09-07 05:51:23','2025-09-07 05:51:23',NULL),(24,'chapter',1,2,12,1,'<p>Je me réponds à moi même</p>',NULL,'2025-09-07 21:01:11','2025-09-07 21:09:36',NULL),(25,'chapter',1,2,10,1,'<p>Nouveau édité</p>',NULL,'2025-09-07 21:14:16','2025-09-07 21:14:23',NULL),(26,'chapter',1,4,14,1,'<p>Hum</p>',NULL,'2025-09-08 20:24:58','2025-09-08 20:24:58',NULL),(27,'chapter',1,2,10,1,'<p>Un nouveau test.</p>\n\n<p>Sur deux paragraphes.</p>',NULL,'2025-10-01 15:12:52','2025-10-01 15:12:52',NULL),(28,'chapter',1,8,NULL,1,'<p>Un premier chapitre ma foi très bien ficelé, avec un style que j\'aime beaucoup. On ne sait pas encore qui est cette voix qui parle, mais on sent bien qu\'elle sera au centre de l\'intrigue</p>',NULL,'2025-12-03 19:35:12','2025-12-03 19:35:12',NULL),(29,'chapter',14,8,NULL,1,'<p>C\'est un débutant haletant que tu nous offres là, une véritable pirouette artistique qui laisse clairement présager du meilleur. Même si j\'avoue que là, je reste un peu sur ma faim...</p>',NULL,'2025-12-03 20:00:25','2025-12-03 20:00:25',NULL),(30,'chapter',1,9,NULL,1,'<p>Voici un premier commentaire que j\'écris aussi vite que possible parce que je ne rêve que de devenir une esperluette et rien d\'autre alors je suis pressée vous ne pouvez pas savoir à quel point.</p>',NULL,'2025-12-04 09:05:43','2025-12-04 09:05:43',NULL),(31,'chapter',3,9,NULL,1,'<p>Voici un deuxième commentaire que j\'écris aussi vite que possible parce que je ne rêve que de devenir une esperluette et rien d\'autre alors je suis pressée vous ne pouvez pas savoir à quel point.</p>',NULL,'2025-12-04 09:05:55','2025-12-04 09:05:55',NULL),(32,'chapter',3,8,NULL,1,'<p>Oh tiens, je n\'ai pas commenté ici. Et bien essayons voir si les notifications peuvent marcher. Ce serait de la magie, parce que je ne sais même pas comment on a fait...</p>',NULL,'2025-12-04 15:33:58','2025-12-04 15:33:58',NULL),(33,'chapter',3,8,16,1,'<p>Wait, let me do it when app is closed.</p>',NULL,'2025-12-04 15:36:53','2025-12-04 15:36:53',NULL),(34,'chapter',15,6,NULL,1,'<p>Coucou,</p>\n\n<p>À l\'heure où j\'écris ces mots, j\'ai fini le roman (depuis plusieurs heures déjà... j\'ai tout lu en moins de 24 heures 😮), et je reviens pour poster des commentaires ici et là.</p>\n\n<p>Ici, je voulais juste dire que la journée de la Sainte Exubérance est une merveille, et le chapeau avec le funiculaire et la comptine associée sont vraiment hilarants. La fin de la scène est émouvante, et elle pose le décor pour la suite de façon très naturelle. </p>\n\n<p>Je garde mes petites remarques un peu plus pointilleuses pour le chapitre d\'après</p>\n\n<p>Merci pour le partage !</p>\n\n<p>LX</p>',NULL,'2025-12-07 19:44:48','2025-12-07 19:44:48',NULL),(35,'chapter',15,2,34,1,'<p>Test sans les espaces insécables pour voir si je me retrouve quand même avec de l’insécable à la fin (peut-être que j’ai cassé quelque chose sur mon navigateur ?)</p>\n\n<p>Voyons voir si ça corrige </p>',NULL,'2025-12-07 19:48:26','2025-12-07 20:02:12',NULL),(36,'chapter',19,6,NULL,1,'<p>I write a comment because I need to see the cover to see if it is really working or if I am being trolled by the others. Not exactly sure, I bet on the others</p>',NULL,'2026-03-16 19:03:55','2026-03-16 19:03:55',NULL),(37,'chapter',6,6,NULL,1,'<p>Ceci est un nouveau commentaire pour voir si le nouveau type de commentaire se gère bien. Ce serait cool, ça m\'éviterait de devoir tout casser</p>',NULL,'2026-04-26 11:45:48','2026-04-26 11:45:48',NULL),(38,'chapter',6,2,37,1,'<p>Réponse.</p>',NULL,'2026-04-26 11:46:02','2026-04-26 11:46:02',NULL),(39,'chapter',7,6,NULL,1,'<p>Voici un nouveau commentaire destiné à vérifier ce qu\'il se passe si l\'utilisateur cible a désactivé les notifications. En théorie, ça devrait bien se passer.</p>',NULL,'2026-04-26 20:18:50','2026-04-26 20:18:50',NULL),(40,'chapter',6,6,37,1,'<p>Pas terrible comme réponse quand même.</p>',NULL,'2026-04-26 20:19:12','2026-04-26 20:19:12',NULL),(41,'chapter',14,6,NULL,1,'<p>Ok, ceci est un nouvel essai de test, mais je ne veux pas trop en faire on plus, si  ça se trouve ça ne va pas marcher. Franchement, j\'ai pas confiance dans ce que fait claude...</p>',NULL,'2026-04-27 19:15:28','2026-04-27 19:15:28',NULL),(42,'chapter',3,6,16,1,'<p>Je suis un peu perdu là. Elle est où ma notif de site ? Et celle de Discord?</p>',NULL,'2026-04-27 19:17:41','2026-04-27 19:17:41',NULL),(43,'chapter',3,6,NULL,1,'<p>Mais... ça ne marche absolument pas, parce que les préférences qui sont vérifiées ne doivent pas être les bonnes. Ce qui ne fait aucun sens, mais bon on va vérifier quand même</p>',NULL,'2026-04-27 19:19:12','2026-04-27 19:19:12',NULL),(44,'chapter',6,2,37,1,'<p>Mais si.</p>',NULL,'2026-04-27 19:27:03','2026-04-27 19:27:03',NULL),(45,'chapter',6,6,37,1,'<p>Mais non</p>',NULL,'2026-04-27 19:28:40','2026-04-27 19:28:40',NULL),(46,'chapter',6,2,37,1,'<p>Mais si</p>',NULL,'2026-04-27 19:29:54','2026-04-27 19:29:54',NULL),(47,'chapter',6,2,37,1,'<p>Mais si</p>',NULL,'2026-04-27 19:31:09','2026-04-27 19:31:09',NULL),(48,'chapter',19,2,36,1,'<p>Let me reply</p>',NULL,'2026-04-28 19:43:43','2026-04-28 19:43:43',NULL);
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
  `domain` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `access` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `admin_visibility` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `config_feature_toggles`
--

LOCK TABLES `config_feature_toggles` WRITE;
/*!40000 ALTER TABLE `config_feature_toggles` DISABLE KEYS */;
INSERT INTO `config_feature_toggles` VALUES (1,'calendar','enabled','on','all_admins','[]',2,'2025-12-13 18:46:50','2025-12-13 18:46:50'),(5,'discord','discord_notifications','on','all_admins','[]',2,'2026-04-27 19:12:47','2026-04-29 20:14:17');
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
  `domain` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `config_parameter_values_domain_key_unique` (`domain`,`key`),
  KEY `config_parameter_values_domain_index` (`domain`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `config_parameter_values`
--

LOCK TABLES `config_parameter_values` WRITE;
/*!40000 ALTER TABLE `config_parameter_values` DISABLE KEYS */;
INSERT INTO `config_parameter_values` VALUES (1,'auth','require_activation_code','0',2,'2025-12-03 18:52:41','2025-12-03 18:52:41'),(2,'auth','non_confirmed_timespan','86400',2,'2025-12-03 18:52:53','2025-12-04 14:00:38'),(3,'auth','non_confirmed_comment_threshold','2',2,'2025-12-03 19:33:49','2025-12-03 19:33:49'),(4,'story','theme_covers_enabled','1',2,'2026-02-16 07:26:30','2026-02-16 07:26:30'),(5,'story','custom_covers_enabled','1',2,'2026-02-21 06:54:03','2026-02-21 06:54:03');
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
-- Table structure for table `discord_pending_notifications`
--

DROP TABLE IF EXISTS `discord_pending_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `discord_pending_notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `notification_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `discord_pending_notifications_notification_id_foreign` (`notification_id`),
  KEY `discord_pending_notifications_created_at_index` (`created_at`),
  CONSTRAINT `discord_pending_notifications_notification_id_foreign` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `discord_pending_notifications`
--

LOCK TABLES `discord_pending_notifications` WRITE;
/*!40000 ALTER TABLE `discord_pending_notifications` DISABLE KEYS */;
INSERT INTO `discord_pending_notifications` VALUES (1,34,'2026-04-27 19:31:09','2026-04-27 19:31:09');
/*!40000 ALTER TABLE `discord_pending_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `discord_pending_recipients`
--

DROP TABLE IF EXISTS `discord_pending_recipients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `discord_pending_recipients` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pending_notification_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `discord_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `discord_pending_recipients_pending_notification_id_sent_at_index` (`pending_notification_id`,`sent_at`),
  KEY `discord_pending_recipients_user_id_index` (`user_id`),
  CONSTRAINT `discord_pending_recipients_pending_notification_id_foreign` FOREIGN KEY (`pending_notification_id`) REFERENCES `discord_pending_notifications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `discord_pending_recipients`
--

LOCK TABLES `discord_pending_recipients` WRITE;
/*!40000 ALTER TABLE `discord_pending_recipients` DISABLE KEYS */;
INSERT INTO `discord_pending_recipients` VALUES (1,1,6,'1234',NULL,'2026-04-27 19:31:09','2026-04-27 19:31:09');
/*!40000 ALTER TABLE `discord_pending_recipients` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=341 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `events_domain`
--

LOCK TABLES `events_domain` WRITE;
/*!40000 ALTER TABLE `events_domain` DISABLE KEYS */;
INSERT INTO `events_domain` VALUES (1,'Auth.UserRegistered','{\"userId\": 6, \"displayName\": \"Fredo\"}',NULL,NULL,NULL,NULL,NULL,'2025-09-12 15:01:38'),(2,'Profile.DisplayNameChanged','{\"userId\": 6, \"newDisplayName\": \"Fredounet\", \"oldDisplayName\": \"Fredo\"}',6,NULL,NULL,NULL,NULL,'2025-09-12 19:32:24'),(3,'Auth.PasswordResetRequested','{\"email\": \"fredo@hemit.fr\", \"userId\": 6}',NULL,NULL,NULL,NULL,NULL,'2025-09-13 06:34:07'),(4,'Auth.PasswordChanged','{\"userId\": 6}',NULL,NULL,NULL,NULL,NULL,'2025-09-13 06:34:45'),(5,'Auth.UserLoggedIn','{\"userId\": 6}',6,NULL,NULL,NULL,NULL,'2025-09-13 06:34:55'),(6,'Profile.AvatarChanged','{\"userId\": 6, \"profilePicturePath\": \"profile_pictures/6_1757745458.jpg\"}',6,NULL,NULL,NULL,NULL,'2025-09-13 06:37:38'),(7,'Profile.AvatarChanged','{\"userId\": 6, \"profilePicturePath\": null}',6,NULL,NULL,NULL,NULL,'2025-09-13 06:37:42'),(8,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-14 06:43:14'),(9,'Auth.UserRegistered','{\"userId\": 7, \"displayName\": \"Test1\"}',NULL,NULL,NULL,NULL,NULL,'2025-09-14 06:43:35'),(10,'Auth.UserLoggedOut','{\"userId\": 7}',7,NULL,NULL,NULL,NULL,'2025-09-14 06:43:43'),(11,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-14 12:03:15'),(12,'Auth.UserRoleGranted','{\"role\": \"user\", \"userId\": 7, \"actorUserId\": 2, \"targetIsAdmin\": false}',2,NULL,NULL,NULL,NULL,'2025-09-14 12:26:26'),(13,'Auth.UserRoleRevoked','{\"role\": \"user-confirmed\", \"userId\": 7, \"actorUserId\": 2, \"targetIsAdmin\": false}',2,NULL,NULL,NULL,NULL,'2025-09-14 12:26:26'),(14,'Auth.UserRoleRevoked','{\"role\": \"user\", \"userId\": 7, \"actorUserId\": 2, \"targetIsAdmin\": false}',2,NULL,NULL,NULL,NULL,'2025-09-14 12:26:40'),(15,'Auth.UserRoleGranted','{\"role\": \"user-confirmed\", \"userId\": 7, \"actorUserId\": 2, \"targetIsAdmin\": false}',2,NULL,NULL,NULL,NULL,'2025-09-14 12:26:40'),(16,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-14 18:32:34'),(17,'Story.Updated','{\"after\": {\"slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-pas-2\", \"title\": \"Immortelle, le roman dont le titre n\'en finit pas\", \"typeId\": 1, \"storyId\": 2, \"genreIds\": [1], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"community\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 129, \"summaryWordCount\": 23, \"triggerWarningIds\": []}, \"before\": {\"slug\": \"immortelle-2\", \"title\": \"Immortelle\", \"typeId\": 1, \"storyId\": 2, \"genreIds\": [1], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"community\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 129, \"summaryWordCount\": 23, \"triggerWarningIds\": []}}',2,NULL,NULL,NULL,NULL,'2025-09-14 19:02:40'),(18,'Story.Updated','{\"after\": {\"slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-pas-2\", \"title\": \"Immortelle, le roman dont le titre n\'en finit pas\", \"typeId\": 1, \"storyId\": 2, \"genreIds\": [1, 2, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"community\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 129, \"summaryWordCount\": 23, \"triggerWarningIds\": []}, \"before\": {\"slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-pas-2\", \"title\": \"Immortelle, le roman dont le titre n\'en finit pas\", \"typeId\": 1, \"storyId\": 2, \"genreIds\": [1], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"community\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 129, \"summaryWordCount\": 23, \"triggerWarningIds\": []}}',2,NULL,NULL,NULL,NULL,'2025-09-14 19:02:48'),(19,'Story.Updated','{\"after\": {\"slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-pas-2\", \"title\": \"Immortelle, le roman dont le titre n\'en finit pas\", \"typeId\": 1, \"storyId\": 2, \"genreIds\": [1, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"community\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 129, \"summaryWordCount\": 23, \"triggerWarningIds\": []}, \"before\": {\"slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-pas-2\", \"title\": \"Immortelle, le roman dont le titre n\'en finit pas\", \"typeId\": 1, \"storyId\": 2, \"genreIds\": [1, 2, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"community\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 129, \"summaryWordCount\": 23, \"triggerWarningIds\": []}}',2,NULL,NULL,NULL,NULL,'2025-09-14 19:13:34'),(20,'Story.Updated','{\"after\": {\"slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-vraiment-vraiment-pas-2\", \"title\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\", \"typeId\": 1, \"storyId\": 2, \"genreIds\": [1, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"community\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 129, \"summaryWordCount\": 23, \"triggerWarningIds\": []}, \"before\": {\"slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-pas-2\", \"title\": \"Immortelle, le roman dont le titre n\'en finit pas\", \"typeId\": 1, \"storyId\": 2, \"genreIds\": [1, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"community\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 129, \"summaryWordCount\": 23, \"triggerWarningIds\": []}}',2,NULL,NULL,NULL,NULL,'2025-09-14 19:15:18'),(21,'Story.Updated','{\"after\": {\"slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-vraiment-vraiment-pas-2\", \"title\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\", \"typeId\": 1, \"storyId\": 2, \"genreIds\": [1, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"community\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 129, \"summaryWordCount\": 23, \"triggerWarningIds\": [1, 2]}, \"before\": {\"slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-vraiment-vraiment-pas-2\", \"title\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\", \"typeId\": 1, \"storyId\": 2, \"genreIds\": [1, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"community\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 129, \"summaryWordCount\": 23, \"triggerWarningIds\": []}}',2,NULL,NULL,NULL,NULL,'2025-09-14 19:19:49'),(22,'Story.Updated','{\"after\": {\"slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-vraiment-vraiment-pas-2\", \"title\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\", \"typeId\": 1, \"storyId\": 2, \"genreIds\": [1, 2, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"community\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 129, \"summaryWordCount\": 23, \"triggerWarningIds\": [1, 2]}, \"before\": {\"slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-vraiment-vraiment-pas-2\", \"title\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\", \"typeId\": 1, \"storyId\": 2, \"genreIds\": [1, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"community\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 129, \"summaryWordCount\": 23, \"triggerWarningIds\": [1, 2]}}',2,NULL,NULL,NULL,NULL,'2025-09-14 19:20:33'),(23,'Story.Updated','{\"after\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 767, \"summaryWordCount\": 126, \"triggerWarningIds\": []}, \"before\": {\"slug\": \"le-crepuscule-des-armes-1\", \"title\": \"Le Crépuscule des Ârmes\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 767, \"summaryWordCount\": 126, \"triggerWarningIds\": []}}',2,NULL,NULL,NULL,NULL,'2025-09-14 19:55:01'),(24,'Story.Created','{\"story\": {\"slug\": \"je-connais-une-histoire-5\", \"title\": \"Je connais une histoire...\", \"typeId\": 1, \"storyId\": 5, \"genreIds\": [1, 2], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 107, \"summaryWordCount\": 19, \"triggerWarningIds\": []}}',2,NULL,NULL,NULL,NULL,'2025-09-14 19:57:32'),(25,'Chapter.Created','{\"chapter\": {\"id\": 14, \"slug\": \"chapitre-1-14\", \"title\": \"Chapitre 1\", \"status\": \"published\", \"charCount\": 15, \"sortOrder\": 100, \"wordCount\": 3}, \"storyId\": 5}',2,NULL,NULL,NULL,NULL,'2025-09-14 19:58:02'),(26,'Profile.DisplayNameChanged','{\"userId\": 2, \"newDisplayName\": \"LogistiX le seigneur des loutres de la grande colline\", \"oldDisplayName\": \"LogistiX\"}',2,NULL,NULL,NULL,NULL,'2025-09-14 20:37:07'),(27,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-15 20:16:25'),(28,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-16 19:45:32'),(29,'Auth.UserLoggedIn','{\"userId\": 3}',3,NULL,NULL,NULL,NULL,'2025-09-16 21:33:35'),(30,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-17 05:16:13'),(31,'Story.Created','{\"story\": {\"slug\": \"limit-test-with-a-long-long-long-long-long-long-long-long-long-long-long-title-6\", \"title\": \"Limit test with a long long long long long long long long long long long title\", \"typeId\": 1, \"storyId\": 6, \"genreIds\": [1, 2, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 175, \"summaryWordCount\": 1, \"triggerWarningIds\": []}}',2,NULL,NULL,NULL,NULL,'2025-09-17 05:24:13'),(32,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-17 18:50:48'),(33,'Story.Updated','{\"after\": {\"slug\": \"limit-test-with-a-long-long-long-long-long-long-long-long-long-long-long-title-6\", \"title\": \"Limit test with a long long long long long long long long long long long title\", \"typeId\": 1, \"storyId\": 6, \"genreIds\": [1, 2], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 175, \"summaryWordCount\": 1, \"triggerWarningIds\": []}, \"before\": {\"slug\": \"limit-test-with-a-long-long-long-long-long-long-long-long-long-long-long-title-6\", \"title\": \"Limit test with a long long long long long long long long long long long title\", \"typeId\": 1, \"storyId\": 6, \"genreIds\": [1, 2, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 175, \"summaryWordCount\": 1, \"triggerWarningIds\": []}}',2,NULL,NULL,NULL,NULL,'2025-09-17 19:11:27'),(34,'Story.Updated','{\"after\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 767, \"summaryWordCount\": 126, \"triggerWarningIds\": []}, \"before\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 767, \"summaryWordCount\": 126, \"triggerWarningIds\": []}}',2,NULL,NULL,NULL,NULL,'2025-09-17 20:23:16'),(35,'Story.Updated','{\"after\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 767, \"summaryWordCount\": 126, \"triggerWarningIds\": []}, \"before\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 767, \"summaryWordCount\": 126, \"triggerWarningIds\": []}}',2,NULL,NULL,NULL,NULL,'2025-09-17 20:24:13'),(36,'Story.Updated','{\"after\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 767, \"summaryWordCount\": 126, \"triggerWarningIds\": []}, \"before\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 767, \"summaryWordCount\": 126, \"triggerWarningIds\": []}}',2,NULL,NULL,NULL,NULL,'2025-09-17 20:51:11'),(37,'Story.Updated','{\"after\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 767, \"summaryWordCount\": 126, \"triggerWarningIds\": []}, \"before\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 767, \"summaryWordCount\": 126, \"triggerWarningIds\": []}}',2,NULL,NULL,NULL,NULL,'2025-09-17 20:51:17'),(38,'Story.Updated','{\"after\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 767, \"summaryWordCount\": 126, \"triggerWarningIds\": [2]}, \"before\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 767, \"summaryWordCount\": 126, \"triggerWarningIds\": []}}',2,NULL,NULL,NULL,NULL,'2025-09-17 20:51:27'),(39,'Story.Updated','{\"after\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 767, \"summaryWordCount\": 126, \"triggerWarningIds\": []}, \"before\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 767, \"summaryWordCount\": 126, \"triggerWarningIds\": [2]}}',2,NULL,NULL,NULL,NULL,'2025-09-17 21:00:06'),(40,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-18 18:43:37'),(41,'Story.Updated','{\"after\": {\"slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-vraiment-vraiment-pas-2\", \"title\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\", \"typeId\": 1, \"storyId\": 2, \"genreIds\": [1, 2, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"private\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 129, \"summaryWordCount\": 23, \"triggerWarningIds\": []}, \"before\": {\"slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-vraiment-vraiment-pas-2\", \"title\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\", \"typeId\": 1, \"storyId\": 2, \"genreIds\": [1, 2, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"community\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 129, \"summaryWordCount\": 23, \"triggerWarningIds\": [1, 2]}}',2,NULL,NULL,NULL,NULL,'2025-09-18 18:45:47'),(42,'Story.VisibilityChanged','{\"title\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\", \"story_id\": 2, \"new_visibility\": \"private\", \"old_visibility\": \"community\"}',2,NULL,NULL,NULL,NULL,'2025-09-18 18:45:47'),(43,'Story.Updated','{\"after\": {\"slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-vraiment-vraiment-pas-2\", \"title\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\", \"typeId\": 1, \"storyId\": 2, \"genreIds\": [1, 2, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"community\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 129, \"summaryWordCount\": 23, \"triggerWarningIds\": []}, \"before\": {\"slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-vraiment-vraiment-pas-2\", \"title\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\", \"typeId\": 1, \"storyId\": 2, \"genreIds\": [1, 2, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"private\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 129, \"summaryWordCount\": 23, \"triggerWarningIds\": []}}',2,NULL,NULL,NULL,NULL,'2025-09-18 19:23:37'),(44,'Story.VisibilityChanged','{\"title\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\", \"story_id\": 2, \"new_visibility\": \"community\", \"old_visibility\": \"private\"}',2,NULL,NULL,NULL,NULL,'2025-09-18 19:23:37'),(45,'Story.Updated','{\"after\": {\"slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-vraiment-vraiment-pas-2\", \"title\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\", \"typeId\": 1, \"storyId\": 2, \"genreIds\": [1, 2, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 129, \"summaryWordCount\": 23, \"triggerWarningIds\": [2]}, \"before\": {\"slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-vraiment-vraiment-pas-2\", \"title\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\", \"typeId\": 1, \"storyId\": 2, \"genreIds\": [1, 2, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"community\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 129, \"summaryWordCount\": 23, \"triggerWarningIds\": []}}',2,NULL,NULL,NULL,NULL,'2025-09-18 19:24:04'),(46,'Story.VisibilityChanged','{\"title\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\", \"story_id\": 2, \"new_visibility\": \"public\", \"old_visibility\": \"community\"}',2,NULL,NULL,NULL,NULL,'2025-09-18 19:24:04'),(47,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-19 11:48:15'),(48,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-19 11:52:55'),(49,'Auth.UserLoggedOut','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-19 11:53:04'),(50,'Auth.UserLoggedIn','{\"userId\": 3}',3,NULL,NULL,NULL,NULL,'2025-09-19 11:53:12'),(51,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-19 19:29:32'),(52,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-20 05:46:52'),(53,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-20 18:44:38'),(54,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-21 06:27:57'),(55,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-21 18:00:51'),(56,'Auth.UserLoggedOut','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-21 19:16:23'),(57,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-21 19:18:13'),(58,'Auth.UserLoggedOut','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-21 19:18:20'),(59,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-22 18:45:23'),(60,'Story.Updated','{\"after\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 767, \"summaryWordCount\": 126, \"triggerWarningIds\": [1, 2]}, \"before\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 767, \"summaryWordCount\": 126, \"triggerWarningIds\": []}}',2,NULL,NULL,NULL,NULL,'2025-09-22 18:59:46'),(61,'Chapter.Updated','{\"after\": {\"id\": 2, \"slug\": \"chapitre-2-tres-tres-long-2\", \"title\": \"Chapitre 2 très très long\", \"status\": \"not_published\", \"charCount\": 26, \"sortOrder\": 6, \"wordCount\": 4}, \"before\": {\"id\": 2, \"slug\": \"chapitre-2\", \"title\": \"Chapitre 2\", \"status\": \"not_published\", \"charCount\": 26, \"sortOrder\": 6, \"wordCount\": 4}, \"storyId\": 1}',2,NULL,NULL,NULL,NULL,'2025-09-22 19:21:17'),(62,'Chapter.Updated','{\"after\": {\"id\": 3, \"slug\": \"chapitre-3-tres-tres-long-aussi-pour-tester-3\", \"title\": \"Chapitre 3 très très long aussi pour tester\", \"status\": \"published\", \"charCount\": 3, \"sortOrder\": 12, \"wordCount\": 1}, \"before\": {\"id\": 3, \"slug\": \"chapitre-3\", \"title\": \"Chapitre 3\", \"status\": \"published\", \"charCount\": 3, \"sortOrder\": 12, \"wordCount\": 1}, \"storyId\": 1}',2,NULL,NULL,NULL,NULL,'2025-09-22 19:28:00'),(63,'Auth.UserLoggedIn','{\"userId\": 3}',3,NULL,NULL,NULL,NULL,'2025-09-22 19:29:04'),(64,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-23 19:13:19'),(65,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-24 05:23:39'),(66,'Auth.UserLoggedOut','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-24 05:24:30'),(67,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-25 18:33:21'),(68,'Story.Updated','{\"after\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 995, \"summaryWordCount\": 162, \"triggerWarningIds\": [1, 2]}, \"before\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 767, \"summaryWordCount\": 126, \"triggerWarningIds\": [1, 2]}}',2,NULL,NULL,NULL,NULL,'2025-09-25 18:35:09'),(69,'Story.Updated','{\"after\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 492, \"summaryWordCount\": 78, \"triggerWarningIds\": [1, 2]}, \"before\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 995, \"summaryWordCount\": 162, \"triggerWarningIds\": [1, 2]}}',2,NULL,NULL,NULL,NULL,'2025-09-25 19:10:53'),(70,'Auth.UserLoggedIn','{\"userId\": 3}',3,NULL,NULL,NULL,NULL,'2025-09-25 19:49:21'),(71,'Story.Updated','{\"after\": {\"slug\": \"lhistoire-sans-debut-3\", \"title\": \"L\'histoire sans début\", \"typeId\": 1, \"storyId\": 3, \"genreIds\": [1], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 117, \"summaryWordCount\": 23, \"triggerWarningIds\": []}, \"before\": {\"slug\": \"lhistoire-sans-debut-3\", \"title\": \"L\'histoire sans début\", \"typeId\": 1, \"storyId\": 3, \"genreIds\": [1], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 0, \"summaryWordCount\": 0, \"triggerWarningIds\": []}}',2,NULL,NULL,NULL,NULL,'2025-09-25 20:50:11'),(72,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-26 19:09:30'),(73,'Auth.UserRoleGranted','{\"role\": \"tech-admin\", \"userId\": 2, \"actorUserId\": 2, \"targetIsAdmin\": true}',2,NULL,NULL,NULL,NULL,'2025-09-26 19:48:39'),(74,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-27 05:44:21'),(75,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-27 18:40:26'),(76,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-27 18:51:35'),(77,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-28 11:54:52'),(78,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-28 17:52:23'),(79,'Story.Updated','{\"after\": {\"slug\": \"je-connais-une-histoire-5\", \"title\": \"Je connais une histoire...\", \"typeId\": 1, \"storyId\": 5, \"genreIds\": [1, 2], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 240, \"summaryWordCount\": 20, \"triggerWarningIds\": []}, \"before\": {\"slug\": \"je-connais-une-histoire-5\", \"title\": \"Je connais une histoire...\", \"typeId\": 1, \"storyId\": 5, \"genreIds\": [1, 2], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 107, \"summaryWordCount\": 19, \"triggerWarningIds\": []}}',2,NULL,NULL,NULL,NULL,'2025-09-28 17:52:39'),(80,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-29 06:39:16'),(81,'Auth.UserLoggedIn','{\"userId\": 3}',3,NULL,NULL,NULL,NULL,'2025-09-29 12:03:17'),(82,'Auth.UserLoggedIn','{\"userId\": 4}',4,NULL,NULL,NULL,NULL,'2025-09-29 13:03:33'),(83,'Auth.UserRoleGranted','{\"role\": \"user\", \"userId\": 4, \"actorUserId\": 2, \"targetIsAdmin\": false}',2,NULL,NULL,NULL,NULL,'2025-09-29 13:03:50'),(84,'Auth.UserRoleRevoked','{\"role\": \"user-confirmed\", \"userId\": 4, \"actorUserId\": 2, \"targetIsAdmin\": false}',2,NULL,NULL,NULL,NULL,'2025-09-29 13:03:50'),(85,'Auth.UserLoggedIn','{\"userId\": 3}',3,NULL,NULL,NULL,NULL,'2025-09-29 18:32:00'),(86,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-09-30 19:12:26'),(87,'Auth.UserLoggedIn','{\"userId\": 3}',3,NULL,NULL,NULL,NULL,'2025-09-30 19:14:34'),(88,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-01 11:40:32'),(89,'Chapter.Updated','{\"after\": {\"id\": 1, \"slug\": \"chapitre-1\", \"title\": \"Chapitre 1\", \"status\": \"published\", \"charCount\": 6852, \"sortOrder\": 0, \"wordCount\": 1225}, \"before\": {\"id\": 1, \"slug\": \"chapitre-1\", \"title\": \"Chapitre 1\", \"status\": \"published\", \"charCount\": 6852, \"sortOrder\": 0, \"wordCount\": 1225}, \"storyId\": 1}',2,NULL,NULL,NULL,NULL,'2025-10-01 12:12:47'),(90,'Chapter.Updated','{\"after\": {\"id\": 1, \"slug\": \"chapitre-1\", \"title\": \"Chapitre 1\", \"status\": \"published\", \"charCount\": 6852, \"sortOrder\": 0, \"wordCount\": 1225}, \"before\": {\"id\": 1, \"slug\": \"chapitre-1\", \"title\": \"Chapitre 1\", \"status\": \"published\", \"charCount\": 6852, \"sortOrder\": 0, \"wordCount\": 1225}, \"storyId\": 1}',2,NULL,NULL,NULL,NULL,'2025-10-01 12:12:55'),(91,'Chapter.Updated','{\"after\": {\"id\": 1, \"slug\": \"chapitre-1\", \"title\": \"Chapitre 1\", \"status\": \"published\", \"charCount\": 6803, \"sortOrder\": 0, \"wordCount\": 1225}, \"before\": {\"id\": 1, \"slug\": \"chapitre-1\", \"title\": \"Chapitre 1\", \"status\": \"published\", \"charCount\": 6852, \"sortOrder\": 0, \"wordCount\": 1225}, \"storyId\": 1}',2,NULL,NULL,NULL,NULL,'2025-10-01 12:20:16'),(92,'Auth.UserLoggedIn','{\"userId\": 3}',3,NULL,NULL,NULL,NULL,'2025-10-01 12:55:30'),(93,'Chapter.Updated','{\"after\": {\"id\": 1, \"slug\": \"chapitre-1\", \"title\": \"Chapitre 1\", \"status\": \"published\", \"charCount\": 6803, \"sortOrder\": 0, \"wordCount\": 1225}, \"before\": {\"id\": 1, \"slug\": \"chapitre-1\", \"title\": \"Chapitre 1\", \"status\": \"published\", \"charCount\": 6803, \"sortOrder\": 0, \"wordCount\": 1225}, \"storyId\": 1}',2,NULL,NULL,NULL,NULL,'2025-10-01 12:58:40'),(94,'Comment.Posted','{\"comment\": {\"is_reply\": true, \"author_id\": 2, \"entity_id\": 1, \"char_count\": 39, \"comment_id\": 27, \"word_count\": 6, \"entity_type\": \"chapter\", \"parent_comment_id\": 10}}',2,NULL,NULL,NULL,NULL,'2025-10-01 15:12:52'),(95,'Auth.UserLoggedOut','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-01 15:29:39'),(96,'Auth.PasswordResetRequested','{\"email\": \"fhemery@hemit.fr\", \"userId\": 2}',NULL,NULL,NULL,NULL,NULL,'2025-10-01 15:36:06'),(97,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-01 15:56:35'),(98,'Auth.UserLoggedOut','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-01 15:57:11'),(99,'Auth.UserLoggedIn','{\"userId\": 3}',3,NULL,NULL,NULL,NULL,'2025-10-01 17:57:11'),(100,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-01 18:28:23'),(101,'Auth.UserLoggedOut','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-01 18:47:18'),(102,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-01 18:48:00'),(103,'Auth.UserLoggedOut','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-01 18:48:22'),(104,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-01 18:48:32'),(105,'Auth.UserLoggedOut','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-01 19:16:24'),(106,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-01 19:16:35'),(107,'Story.Created','{\"story\": {\"slug\": \"test-7\", \"title\": \"Test\", \"typeId\": 1, \"storyId\": 7, \"genreIds\": [1], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 167, \"summaryWordCount\": 18, \"triggerWarningIds\": []}}',2,NULL,NULL,NULL,NULL,'2025-10-01 19:17:21'),(108,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-02 07:09:51'),(109,'Chapter.Unpublished','{\"chapter\": {\"id\": 1, \"slug\": \"chapitre-1\", \"title\": \"Chapitre 1\", \"status\": \"not_published\", \"charCount\": 6803, \"sortOrder\": 0, \"wordCount\": 1225}, \"storyId\": 1}',2,NULL,NULL,NULL,NULL,'2025-10-02 08:09:40'),(110,'Chapter.Updated','{\"after\": {\"id\": 1, \"slug\": \"chapitre-1\", \"title\": \"Chapitre 1\", \"status\": \"not_published\", \"charCount\": 6803, \"sortOrder\": 0, \"wordCount\": 1225}, \"before\": {\"id\": 1, \"slug\": \"chapitre-1\", \"title\": \"Chapitre 1\", \"status\": \"published\", \"charCount\": 6803, \"sortOrder\": 0, \"wordCount\": 1225}, \"storyId\": 1}',2,NULL,NULL,NULL,NULL,'2025-10-02 08:09:40'),(111,'Chapter.Updated','{\"after\": {\"id\": 1, \"slug\": \"chapitre-1\", \"title\": \"Chapitre 1\", \"status\": \"not_published\", \"charCount\": 6803, \"sortOrder\": 0, \"wordCount\": 1225}, \"before\": {\"id\": 1, \"slug\": \"chapitre-1\", \"title\": \"Chapitre 1\", \"status\": \"not_published\", \"charCount\": 6803, \"sortOrder\": 0, \"wordCount\": 1225}, \"storyId\": 1}',2,NULL,NULL,NULL,NULL,'2025-10-02 08:09:47'),(112,'Chapter.Published','{\"chapter\": {\"id\": 1, \"slug\": \"chapitre-1\", \"title\": \"Chapitre 1\", \"status\": \"published\", \"charCount\": 6803, \"sortOrder\": 0, \"wordCount\": 1225}, \"storyId\": 1}',2,NULL,NULL,NULL,NULL,'2025-10-02 08:09:52'),(113,'Chapter.Updated','{\"after\": {\"id\": 1, \"slug\": \"chapitre-1\", \"title\": \"Chapitre 1\", \"status\": \"published\", \"charCount\": 6803, \"sortOrder\": 0, \"wordCount\": 1225}, \"before\": {\"id\": 1, \"slug\": \"chapitre-1\", \"title\": \"Chapitre 1\", \"status\": \"not_published\", \"charCount\": 6803, \"sortOrder\": 0, \"wordCount\": 1225}, \"storyId\": 1}',2,NULL,NULL,NULL,NULL,'2025-10-02 08:09:52'),(114,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-02 14:05:26'),(115,'Auth.UserLoggedIn','{\"userId\": 3}',3,NULL,NULL,NULL,NULL,'2025-10-02 18:24:34'),(116,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-04 13:14:18'),(117,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-04 18:44:46'),(118,'Discord.Connected','{\"userId\": 2, \"discordId\": \"123456789012345678\"}',NULL,NULL,NULL,NULL,NULL,'2025-10-04 19:02:00'),(119,'Discord.Disconnected','{\"userId\": 2, \"discordId\": \"123456789012345678\"}',NULL,NULL,NULL,NULL,NULL,'2025-10-04 19:12:31'),(120,'Discord.Connected','{\"userId\": 2, \"discordId\": \"123456789012345678\"}',NULL,NULL,NULL,NULL,NULL,'2025-10-04 19:14:19'),(121,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-05 17:56:42'),(122,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-06 18:55:26'),(123,'Discord.Disconnected','{\"userId\": 2, \"discordId\": \"123456789012345678\"}',NULL,NULL,NULL,NULL,NULL,'2025-10-06 18:57:56'),(124,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-10-07 19:29:01'),(125,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-12-03 18:52:33'),(126,'Config.ConfigParameterUpdated','{\"parameter\": {\"key\": \"require_activation_code\", \"type\": \"bool\", \"value\": false, \"domain\": \"auth\", \"previousValue\": true}}',2,NULL,NULL,NULL,NULL,'2025-12-03 18:52:41'),(127,'Config.ConfigParameterUpdated','{\"parameter\": {\"key\": \"non_confirmed_timespan\", \"type\": \"time\", \"value\": 3600, \"domain\": \"auth\", \"previousValue\": 604800}}',2,NULL,NULL,NULL,NULL,'2025-12-03 18:52:53'),(128,'Auth.UserRegistered','{\"userId\": 8, \"displayName\": \"harold\"}',NULL,NULL,NULL,NULL,NULL,'2025-12-03 19:32:24'),(129,'Auth.UserRoleGranted','{\"role\": \"user\", \"userId\": 8, \"actorUserId\": 8, \"targetIsAdmin\": false}',8,NULL,NULL,NULL,NULL,'2025-12-03 19:32:58'),(130,'Auth.EmailVerified','{\"userId\": 8}',8,NULL,NULL,NULL,NULL,'2025-12-03 19:32:58'),(131,'Config.ConfigParameterUpdated','{\"parameter\": {\"key\": \"non_confirmed_comment_threshold\", \"type\": \"int\", \"value\": 2, \"domain\": \"auth\", \"previousValue\": 5}}',2,NULL,NULL,NULL,NULL,'2025-12-03 19:33:49'),(132,'Story.Updated','{\"after\": {\"slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-vraiment-vraiment-pas-2\", \"title\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\", \"typeId\": 1, \"storyId\": 2, \"genreIds\": [1, 2, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"community\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 148, \"summaryWordCount\": 25, \"triggerWarningIds\": [2]}, \"before\": {\"slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-vraiment-vraiment-pas-2\", \"title\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\", \"typeId\": 1, \"storyId\": 2, \"genreIds\": [1, 2, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 129, \"summaryWordCount\": 23, \"triggerWarningIds\": [2]}}',2,NULL,NULL,NULL,NULL,'2025-12-03 19:34:24'),(133,'Story.VisibilityChanged','{\"title\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\", \"story_id\": 2, \"new_visibility\": \"community\", \"old_visibility\": \"public\"}',2,NULL,NULL,NULL,NULL,'2025-12-03 19:34:24'),(134,'Comment.Posted','{\"comment\": {\"is_reply\": false, \"author_id\": 8, \"entity_id\": 1, \"char_count\": 186, \"comment_id\": 28, \"word_count\": 38, \"entity_type\": \"chapter\", \"parent_comment_id\": null}}',8,NULL,NULL,NULL,NULL,'2025-12-03 19:35:12'),(135,'Config.ConfigParameterUpdated','{\"parameter\": {\"key\": \"non_confirmed_timespan\", \"type\": \"time\", \"value\": 30, \"domain\": \"auth\", \"previousValue\": 3600}}',2,NULL,NULL,NULL,NULL,'2025-12-03 19:56:53'),(136,'Comment.Posted','{\"comment\": {\"is_reply\": false, \"author_id\": 8, \"entity_id\": 14, \"char_count\": 183, \"comment_id\": 29, \"word_count\": 33, \"entity_type\": \"chapter\", \"parent_comment_id\": null}}',8,NULL,NULL,NULL,NULL,'2025-12-03 20:00:25'),(137,'Auth.UserLoggedOut','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-12-03 21:27:57'),(138,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-12-03 21:28:11'),(139,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-12-04 08:19:44'),(140,'Auth.PromotionRejected','{\"reason\": \"Quand on poste des commentaires, on aime qu\'il y ait un petit quelque chose dedans. Là, c\'est creux, on sent bien que tu ne fais ça que pour passer ta période d\'essai. Désolé, mais la bienveillance ne suffit pas. C\'est aussi pour ça qu\'il y a un ABC du commentaire.\\r\\nEt non, ce commentaire n\'est pas trop long. Il est instillé de l\'essence fondamentale de la Baronne, dont tu ferais bien de t\'inspirer afin que tes commentaires paraissent moins lapidaires.\", \"userId\": 8, \"decidedBy\": 2}',2,NULL,NULL,NULL,NULL,'2025-12-04 08:22:18'),(141,'Auth.UserLoggedIn','{\"userId\": 8}',8,NULL,NULL,NULL,NULL,'2025-12-04 08:22:34'),(142,'Auth.PromotionRequested','{\"userId\": 8}',8,NULL,NULL,NULL,NULL,'2025-12-04 08:41:34'),(143,'Auth.PromotionRejected','{\"reason\": \"Nul.\", \"userId\": 8, \"decidedBy\": 2}',2,NULL,NULL,NULL,NULL,'2025-12-04 08:41:54'),(144,'Auth.PromotionRequested','{\"userId\": 8}',8,NULL,NULL,NULL,NULL,'2025-12-04 08:42:51'),(145,'Auth.PromotionRejected','{\"reason\": \"C\'est la troisième fois que je te refuse, force est de constater que tu ne fais aucun effort. Et pourtant, je m\'échine à te demande de t\'inspirer de l\'ABC du commentaire et des commentaires de notre membres Esperluettes aguerri·e·s.\\r\\nNon, tu n\'es pas obligé·e d\'écrire un roman comme Isapass à chaque fois que tu commentes, mais un peu de substance ne ferait quand même pas de mal, qu\'en penses tu ?\", \"userId\": 8, \"decidedBy\": 2}',2,NULL,NULL,NULL,NULL,'2025-12-04 08:44:11'),(146,'Auth.PromotionRequested','{\"userId\": 8}',8,NULL,NULL,NULL,NULL,'2025-12-04 08:45:13'),(147,'Auth.UserRoleRevoked','{\"role\": \"user\", \"userId\": 8, \"actorUserId\": 2, \"targetIsAdmin\": false}',2,NULL,NULL,NULL,NULL,'2025-12-04 08:45:19'),(148,'Auth.UserRoleGranted','{\"role\": \"user-confirmed\", \"userId\": 8, \"actorUserId\": 2, \"targetIsAdmin\": false}',2,NULL,NULL,NULL,NULL,'2025-12-04 08:45:19'),(149,'Auth.PromotionAccepted','{\"userId\": 8, \"decidedBy\": 2}',2,NULL,NULL,NULL,NULL,'2025-12-04 08:45:19'),(150,'Auth.UserLoggedOut','{\"userId\": 8}',8,NULL,NULL,NULL,NULL,'2025-12-04 09:03:56'),(151,'Auth.UserRegistered','{\"userId\": 9, \"displayName\": \"Isabella\"}',NULL,NULL,NULL,NULL,NULL,'2025-12-04 09:04:17'),(152,'Auth.UserRoleGranted','{\"role\": \"user\", \"userId\": 9, \"actorUserId\": 9, \"targetIsAdmin\": false}',9,NULL,NULL,NULL,NULL,'2025-12-04 09:05:00'),(153,'Auth.EmailVerified','{\"userId\": 9}',9,NULL,NULL,NULL,NULL,'2025-12-04 09:05:00'),(154,'Comment.Posted','{\"comment\": {\"is_reply\": false, \"author_id\": 9, \"entity_id\": 1, \"char_count\": 194, \"comment_id\": 30, \"word_count\": 37, \"entity_type\": \"chapter\", \"parent_comment_id\": null}}',9,NULL,NULL,NULL,NULL,'2025-12-04 09:05:43'),(155,'Comment.Posted','{\"comment\": {\"is_reply\": false, \"author_id\": 9, \"entity_id\": 3, \"char_count\": 195, \"comment_id\": 31, \"word_count\": 37, \"entity_type\": \"chapter\", \"parent_comment_id\": null}}',9,NULL,NULL,NULL,NULL,'2025-12-04 09:05:55'),(156,'Auth.PromotionRequested','{\"userId\": 9}',9,NULL,NULL,NULL,NULL,'2025-12-04 09:06:03'),(157,'Auth.UserLoggedOut','{\"userId\": 9}',9,NULL,NULL,NULL,NULL,'2025-12-04 09:14:46'),(158,'Auth.PasswordResetRequested','{\"email\": \"isabella@hemit.fr\", \"userId\": 9}',NULL,NULL,NULL,NULL,NULL,'2025-12-04 09:14:54'),(159,'Auth.PasswordResetRequested','{\"email\": \"isabella@hemit.fr\", \"userId\": 9}',NULL,NULL,NULL,NULL,NULL,'2025-12-04 09:18:07'),(160,'Auth.PasswordResetRequested','{\"email\": \"isabella@hemit.fr\", \"userId\": 9}',NULL,NULL,NULL,NULL,NULL,'2025-12-04 09:19:27'),(161,'Auth.PasswordResetRequested','{\"email\": \"harold@hemit.fr\", \"userId\": 8}',NULL,NULL,NULL,NULL,NULL,'2025-12-04 09:20:40'),(162,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-12-04 14:00:22'),(163,'Config.ConfigParameterUpdated','{\"parameter\": {\"key\": \"non_confirmed_timespan\", \"type\": \"time\", \"value\": 86400, \"domain\": \"auth\", \"previousValue\": 30}}',2,NULL,NULL,NULL,NULL,'2025-12-04 14:00:38'),(164,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-12-04 15:13:07'),(165,'Auth.UserLoggedOut','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-12-04 15:33:10'),(166,'Auth.UserLoggedIn','{\"userId\": 8}',8,NULL,NULL,NULL,NULL,'2025-12-04 15:33:18'),(167,'Comment.Posted','{\"comment\": {\"is_reply\": false, \"author_id\": 8, \"entity_id\": 3, \"char_count\": 169, \"comment_id\": 32, \"word_count\": 33, \"entity_type\": \"chapter\", \"parent_comment_id\": null}}',8,NULL,NULL,NULL,NULL,'2025-12-04 15:33:58'),(168,'Comment.Posted','{\"comment\": {\"is_reply\": true, \"author_id\": 8, \"entity_id\": 3, \"char_count\": 38, \"comment_id\": 33, \"word_count\": 9, \"entity_type\": \"chapter\", \"parent_comment_id\": 16}}',8,NULL,NULL,NULL,NULL,'2025-12-04 15:36:53'),(169,'Auth.UserLoggedOut','{\"userId\": 8}',8,NULL,NULL,NULL,NULL,'2025-12-04 17:06:33'),(170,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-12-04 17:06:40'),(171,'Story.Updated','{\"after\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 617, \"summaryWordCount\": 91, \"triggerWarningIds\": [1, 2]}, \"before\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 492, \"summaryWordCount\": 78, \"triggerWarningIds\": [1, 2]}}',2,NULL,NULL,NULL,NULL,'2025-12-04 18:49:42'),(172,'Story.ExcludedFromEvents','{\"title\": \"Le Crépuscule des Âs\", \"story_id\": 1}',2,NULL,NULL,NULL,NULL,'2025-12-04 18:49:42'),(173,'Story.Updated','{\"after\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 486, \"summaryWordCount\": 78, \"triggerWarningIds\": [1, 2]}, \"before\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 617, \"summaryWordCount\": 91, \"triggerWarningIds\": [1, 2]}}',2,NULL,NULL,NULL,NULL,'2025-12-04 18:52:35'),(174,'Story.Updated','{\"after\": {\"slug\": \"je-connais-une-histoire-5\", \"title\": \"Je connais une histoire...\", \"typeId\": 1, \"storyId\": 5, \"genreIds\": [1, 2], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 238, \"summaryWordCount\": 20, \"triggerWarningIds\": []}, \"before\": {\"slug\": \"je-connais-une-histoire-5\", \"title\": \"Je connais une histoire...\", \"typeId\": 1, \"storyId\": 5, \"genreIds\": [1, 2], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 240, \"summaryWordCount\": 20, \"triggerWarningIds\": []}}',2,NULL,NULL,NULL,NULL,'2025-12-04 19:03:49'),(175,'Story.Updated','{\"after\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 486, \"summaryWordCount\": 78, \"triggerWarningIds\": [1, 2]}, \"before\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 486, \"summaryWordCount\": 78, \"triggerWarningIds\": [1, 2]}}',2,NULL,NULL,NULL,NULL,'2025-12-04 19:18:09'),(176,'News.Updated','{\"slug\": \"test\", \"title\": \"Test\", \"newsId\": 1, \"changedFields\": [\"content\", \"updated_at\"]}',2,NULL,NULL,NULL,NULL,'2025-12-04 19:29:28'),(177,'News.Updated','{\"slug\": \"test\", \"title\": \"Test\", \"newsId\": 1, \"changedFields\": [\"content\", \"updated_at\"]}',2,NULL,NULL,NULL,NULL,'2025-12-04 19:48:51'),(178,'News.Updated','{\"slug\": \"test\", \"title\": \"Test\", \"newsId\": 1, \"changedFields\": [\"content\", \"updated_at\"]}',2,NULL,NULL,NULL,NULL,'2025-12-04 19:49:15'),(179,'News.Updated','{\"slug\": \"test\", \"title\": \"Test\", \"newsId\": 1, \"changedFields\": [\"content\", \"updated_at\"]}',2,NULL,NULL,NULL,NULL,'2025-12-04 19:49:47'),(180,'Story.Updated','{\"after\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 486, \"summaryWordCount\": 78, \"triggerWarningIds\": [1, 2]}, \"before\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 486, \"summaryWordCount\": 78, \"triggerWarningIds\": [1, 2]}}',2,NULL,NULL,NULL,NULL,'2025-12-04 19:50:46'),(181,'Chapter.Updated','{\"after\": {\"id\": 1, \"slug\": \"chapitre-1\", \"title\": \"Chapitre 1\", \"status\": \"published\", \"charCount\": 6803, \"sortOrder\": 0, \"wordCount\": 1225}, \"before\": {\"id\": 1, \"slug\": \"chapitre-1\", \"title\": \"Chapitre 1\", \"status\": \"published\", \"charCount\": 6803, \"sortOrder\": 0, \"wordCount\": 1225}, \"storyId\": 1}',2,NULL,NULL,NULL,NULL,'2025-12-04 19:50:55'),(182,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-12-05 10:39:38'),(183,'Auth.UserLoggedIn','{\"userId\": 6}',6,NULL,NULL,NULL,NULL,'2025-12-05 10:39:58'),(184,'ReadList.Added','{\"userId\": 6, \"storyId\": 1}',6,NULL,NULL,NULL,NULL,'2025-12-05 10:40:08'),(185,'Story.Updated','{\"after\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"isComplete\": false, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 486, \"summaryWordCount\": 78, \"triggerWarningIds\": [1, 2], \"isExcludedFromEvents\": true}, \"before\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"isComplete\": true, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 486, \"summaryWordCount\": 78, \"triggerWarningIds\": [1, 2], \"isExcludedFromEvents\": true}}',2,NULL,NULL,NULL,NULL,'2025-12-05 10:40:24'),(186,'Story.Updated','{\"after\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"isComplete\": true, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 486, \"summaryWordCount\": 78, \"triggerWarningIds\": [1, 2], \"isExcludedFromEvents\": true}, \"before\": {\"slug\": \"le-crepuscule-des-as-1\", \"title\": \"Le Crépuscule des Âs\", \"typeId\": 1, \"storyId\": 1, \"genreIds\": [1], \"statusId\": 1, \"audienceId\": 1, \"feedbackId\": null, \"isComplete\": false, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 486, \"summaryWordCount\": 78, \"triggerWarningIds\": [1, 2], \"isExcludedFromEvents\": true}}',2,NULL,NULL,NULL,NULL,'2025-12-05 10:40:29'),(187,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-12-07 19:44:02'),(188,'Chapter.Created','{\"chapter\": {\"id\": 15, \"slug\": \"chapitre-1-15\", \"title\": \"Chapitre 1\", \"status\": \"published\", \"charCount\": 4, \"sortOrder\": 100, \"wordCount\": 1}, \"storyId\": 7}',2,NULL,NULL,NULL,NULL,'2025-12-07 19:44:17'),(189,'Auth.UserLoggedIn','{\"userId\": 6}',6,NULL,NULL,NULL,NULL,'2025-12-07 19:44:36'),(190,'Comment.Posted','{\"comment\": {\"is_reply\": false, \"author_id\": 6, \"entity_id\": 15, \"char_count\": 561, \"comment_id\": 34, \"word_count\": 103, \"entity_type\": \"chapter\", \"parent_comment_id\": null}}',6,NULL,NULL,NULL,NULL,'2025-12-07 19:44:48'),(191,'Comment.Posted','{\"comment\": {\"is_reply\": true, \"author_id\": 2, \"entity_id\": 15, \"char_count\": 162, \"comment_id\": 35, \"word_count\": 31, \"entity_type\": \"chapter\", \"parent_comment_id\": 34}}',2,NULL,NULL,NULL,NULL,'2025-12-07 19:48:26'),(192,'Comment.Edited','{\"after\": {\"is_reply\": true, \"author_id\": 2, \"entity_id\": 15, \"char_count\": 162, \"comment_id\": 35, \"word_count\": 31, \"entity_type\": \"chapter\", \"parent_comment_id\": 34}, \"before\": {\"is_reply\": true, \"author_id\": 2, \"entity_id\": 15, \"char_count\": 162, \"comment_id\": 35, \"word_count\": 31, \"entity_type\": \"chapter\", \"parent_comment_id\": 34}}',2,NULL,NULL,NULL,NULL,'2025-12-07 19:53:40'),(193,'Comment.Edited','{\"after\": {\"is_reply\": true, \"author_id\": 2, \"entity_id\": 15, \"char_count\": 189, \"comment_id\": 35, \"word_count\": 36, \"entity_type\": \"chapter\", \"parent_comment_id\": 34}, \"before\": {\"is_reply\": true, \"author_id\": 2, \"entity_id\": 15, \"char_count\": 162, \"comment_id\": 35, \"word_count\": 31, \"entity_type\": \"chapter\", \"parent_comment_id\": 34}}',2,NULL,NULL,NULL,NULL,'2025-12-07 20:00:37'),(194,'Comment.Edited','{\"after\": {\"is_reply\": true, \"author_id\": 2, \"entity_id\": 15, \"char_count\": 190, \"comment_id\": 35, \"word_count\": 36, \"entity_type\": \"chapter\", \"parent_comment_id\": 34}, \"before\": {\"is_reply\": true, \"author_id\": 2, \"entity_id\": 15, \"char_count\": 189, \"comment_id\": 35, \"word_count\": 36, \"entity_type\": \"chapter\", \"parent_comment_id\": 34}}',2,NULL,NULL,NULL,NULL,'2025-12-07 20:02:12'),(195,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-12-13 18:41:14'),(196,'Config.FeatureToggleAdded','{\"featureToggle\": {\"name\": \"enabled\", \"roles\": [], \"access\": \"on\", \"domain\": \"calendar\", \"admin_visibility\": \"all_admins\"}}',2,NULL,NULL,NULL,NULL,'2025-12-13 18:46:50'),(197,'Auth.UserLoggedIn','{\"userId\": 6}',6,NULL,NULL,NULL,NULL,'2025-12-13 18:58:10'),(198,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-12-14 19:33:01'),(199,'Auth.UserLoggedOut','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-12-14 19:54:59'),(200,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-12-14 20:41:59'),(201,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-12-17 13:56:20'),(202,'Auth.UserLoggedOut','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-12-17 13:57:24'),(203,'Auth.UserLoggedIn','{\"userId\": 8}',8,NULL,NULL,NULL,NULL,'2025-12-17 14:23:49'),(204,'Auth.UserLoggedOut','{\"userId\": 8}',8,NULL,NULL,NULL,NULL,'2025-12-17 14:24:00'),(205,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2025-12-17 14:24:06'),(206,'Auth.UserRoleGranted','{\"role\": \"user\", \"userId\": 8, \"actorUserId\": 2, \"targetIsAdmin\": false}',2,NULL,NULL,NULL,NULL,'2025-12-17 14:24:23'),(207,'Auth.UserRoleRevoked','{\"role\": \"user-confirmed\", \"userId\": 8, \"actorUserId\": 2, \"targetIsAdmin\": false}',2,NULL,NULL,NULL,NULL,'2025-12-17 14:24:23'),(208,'Auth.UserLoggedIn','{\"userId\": 8}',8,NULL,NULL,NULL,NULL,'2025-12-17 14:24:40'),(209,'Auth.UserLoggedOut','{\"userId\": 8}',8,NULL,NULL,NULL,NULL,'2025-12-17 14:35:54'),(210,'Auth.UserLoggedIn','{\"userId\": 6}',6,NULL,NULL,NULL,NULL,'2025-12-17 14:36:01'),(211,'ReadList.Added','{\"userId\": 6, \"storyId\": 2}',6,NULL,NULL,NULL,NULL,'2025-12-17 14:36:07'),(212,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2026-01-08 14:52:20'),(213,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2026-01-28 05:07:23'),(214,'Auth.UserLoggedIn','{\"userId\": 6}',6,NULL,NULL,NULL,NULL,'2026-01-28 05:08:15'),(215,'Story.Created','{\"story\": {\"slug\": \"une-histoire-de-daniel-8\", \"title\": \"Une histoire de Daniel\", \"typeId\": 1, \"storyId\": 8, \"genreIds\": [1], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"isComplete\": false, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 6, \"summaryCharCount\": 103, \"summaryWordCount\": 19, \"triggerWarningIds\": [], \"isExcludedFromEvents\": false}}',6,NULL,NULL,NULL,NULL,'2026-01-28 05:09:14'),(216,'Chapter.Created','{\"chapter\": {\"id\": 16, \"slug\": \"chapitre-1-16\", \"title\": \"Chapitre 1\", \"status\": \"published\", \"charCount\": 22, \"sortOrder\": 100, \"wordCount\": 5}, \"storyId\": 8}',6,NULL,NULL,NULL,NULL,'2026-01-28 05:09:30'),(217,'Profile.DisplayNameChanged','{\"userId\": 6, \"newDisplayName\": \"Daniel\", \"oldDisplayName\": \"Fredounet\"}',6,NULL,NULL,NULL,NULL,'2026-01-28 05:09:59'),(218,'ReadList.Added','{\"userId\": 2, \"storyId\": 8}',2,NULL,NULL,NULL,NULL,'2026-01-28 05:10:03'),(219,'Story.Created','{\"story\": {\"slug\": \"une-autre-histoire-9\", \"title\": \"Une autre histoire\", \"typeId\": 1, \"storyId\": 9, \"genreIds\": [1], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"isComplete\": false, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 6, \"summaryCharCount\": 118, \"summaryWordCount\": 20, \"triggerWarningIds\": [], \"isExcludedFromEvents\": false}}',6,NULL,NULL,NULL,NULL,'2026-01-28 05:10:51'),(220,'Chapter.Created','{\"chapter\": {\"id\": 17, \"slug\": \"chapitre-1-17\", \"title\": \"Chapitre 1\", \"status\": \"published\", \"charCount\": 4, \"sortOrder\": 100, \"wordCount\": 1}, \"storyId\": 9}',6,NULL,NULL,NULL,NULL,'2026-01-28 05:11:01'),(221,'Chapter.Created','{\"chapter\": {\"id\": 18, \"slug\": \"chapitre-2-18\", \"title\": \"Chapitre 2\", \"status\": \"published\", \"charCount\": 4, \"sortOrder\": 200, \"wordCount\": 1}, \"storyId\": 9}',6,NULL,NULL,NULL,NULL,'2026-01-28 05:11:08'),(222,'ReadList.Added','{\"userId\": 2, \"storyId\": 9}',2,NULL,NULL,NULL,NULL,'2026-01-28 05:11:18'),(223,'Auth.UserLoggedOut','{\"userId\": 6}',6,NULL,NULL,NULL,NULL,'2026-01-28 05:38:07'),(224,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2026-01-28 05:38:14'),(225,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2026-02-16 07:25:58'),(226,'Config.ConfigParameterUpdated','{\"parameter\": {\"key\": \"theme_covers_enabled\", \"type\": \"bool\", \"value\": true, \"domain\": \"story\", \"previousValue\": false}}',2,NULL,NULL,NULL,NULL,'2026-02-16 07:26:30'),(227,'StoryRef.Updated','{\"refId\": 1, \"refKind\": \"genre\", \"refName\": \"Fantasy\", \"refSlug\": \"fantasy\", \"changedFields\": [\"has_cover\"]}',2,NULL,NULL,NULL,NULL,'2026-02-16 07:27:19'),(228,'StoryRef.Updated','{\"refId\": 3, \"refKind\": \"genre\", \"refName\": \"Autobiographie\", \"refSlug\": \"autres\", \"changedFields\": [\"slug\"]}',2,NULL,NULL,NULL,NULL,'2026-02-16 07:28:02'),(229,'StoryRef.Updated','{\"refId\": 3, \"refKind\": \"genre\", \"refName\": \"Autobiographie\", \"refSlug\": \"autres\", \"changedFields\": [\"has_cover\"]}',2,NULL,NULL,NULL,NULL,'2026-02-16 07:28:09'),(230,'Story.Created','{\"story\": {\"slug\": \"reine-du-rift-10\", \"title\": \"Reine du rift\", \"typeId\": 1, \"storyId\": 10, \"genreIds\": [1, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"isComplete\": false, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 153, \"summaryWordCount\": 31, \"triggerWarningIds\": [], \"isExcludedFromEvents\": false}}',2,NULL,NULL,NULL,NULL,'2026-02-16 07:29:19'),(231,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2026-02-16 12:29:04'),(232,'Story.Updated','{\"after\": {\"slug\": \"reine-du-rift-un-thriller-fantastique-fantasy-au-rythme-haletant-et-sans-la-moindre-violence-envers-les-hamsters-10\", \"title\": \"Reine du rift, un thriller fantastique fantasy au rythme haletant et sans la moindre violence envers les hamsters\", \"typeId\": 1, \"storyId\": 10, \"genreIds\": [1, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"isComplete\": false, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 153, \"summaryWordCount\": 31, \"triggerWarningIds\": [], \"isExcludedFromEvents\": false}, \"before\": {\"slug\": \"reine-du-rift-10\", \"title\": \"Reine du rift\", \"typeId\": 1, \"storyId\": 10, \"genreIds\": [1, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"isComplete\": false, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 153, \"summaryWordCount\": 31, \"triggerWarningIds\": [], \"isExcludedFromEvents\": false}}',2,NULL,NULL,NULL,NULL,'2026-02-16 12:29:39'),(233,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2026-02-17 19:37:43'),(234,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2026-02-21 06:33:32'),(235,'Config.ConfigParameterUpdated','{\"parameter\": {\"key\": \"custom_covers_enabled\", \"type\": \"bool\", \"value\": true, \"domain\": \"story\", \"previousValue\": false}}',2,NULL,NULL,NULL,NULL,'2026-02-21 06:54:03'),(236,'Story.Updated','{\"after\": {\"slug\": \"reine-du-rift-un-thriller-fantastique-fantasy-au-rythme-haletant-et-sans-la-moindre-violence-envers-les-hamsters-10\", \"title\": \"Reine du rift, un thriller fantastique fantasy au rythme haletant et sans la moindre violence envers les hamsters\", \"typeId\": 1, \"storyId\": 10, \"genreIds\": [1, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"isComplete\": false, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 153, \"summaryWordCount\": 31, \"triggerWarningIds\": [], \"isExcludedFromEvents\": false}, \"before\": {\"slug\": \"reine-du-rift-un-thriller-fantastique-fantasy-au-rythme-haletant-et-sans-la-moindre-violence-envers-les-hamsters-10\", \"title\": \"Reine du rift, un thriller fantastique fantasy au rythme haletant et sans la moindre violence envers les hamsters\", \"typeId\": 1, \"storyId\": 10, \"genreIds\": [1, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"isComplete\": false, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 153, \"summaryWordCount\": 31, \"triggerWarningIds\": [], \"isExcludedFromEvents\": false}}',2,NULL,NULL,NULL,NULL,'2026-02-21 06:54:44'),(237,'Chapter.Created','{\"chapter\": {\"id\": 19, \"slug\": \"ch1-19\", \"title\": \"Ch1\", \"status\": \"published\", \"charCount\": 11, \"sortOrder\": 100, \"wordCount\": 2}, \"storyId\": 10}',2,NULL,NULL,NULL,NULL,'2026-02-21 06:55:16'),(238,'Story.Updated','{\"after\": {\"slug\": \"reine-du-rift-un-thriller-fantastique-fantasy-au-rythme-haletant-et-sans-la-moindre-violence-envers-les-hamsters-10\", \"title\": \"Reine du rift, un thriller fantastique fantasy au rythme haletant et sans la moindre violence envers les hamsters\", \"typeId\": 1, \"storyId\": 10, \"genreIds\": [1, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"isComplete\": false, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 153, \"summaryWordCount\": 31, \"triggerWarningIds\": [], \"isExcludedFromEvents\": false}, \"before\": {\"slug\": \"reine-du-rift-un-thriller-fantastique-fantasy-au-rythme-haletant-et-sans-la-moindre-violence-envers-les-hamsters-10\", \"title\": \"Reine du rift, un thriller fantastique fantasy au rythme haletant et sans la moindre violence envers les hamsters\", \"typeId\": 1, \"storyId\": 10, \"genreIds\": [1, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"isComplete\": false, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 153, \"summaryWordCount\": 31, \"triggerWarningIds\": [], \"isExcludedFromEvents\": false}}',2,NULL,NULL,NULL,NULL,'2026-02-21 07:01:25'),(239,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2026-02-23 07:39:37'),(240,'Auth.UserRoleGranted','{\"role\": \"moderator\", \"userId\": 6, \"actorUserId\": 2, \"targetIsAdmin\": false}',2,NULL,NULL,NULL,NULL,'2026-02-23 07:41:50'),(241,'Auth.UserLoggedOut','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2026-02-23 07:41:54'),(242,'Auth.UserLoggedIn','{\"userId\": 6}',6,NULL,NULL,NULL,NULL,'2026-02-23 07:42:01'),(243,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2026-02-23 19:06:03'),(244,'Story.Updated','{\"after\": {\"slug\": \"limit-test-with-a-long-long-long-long-long-long-long-long-long-long-long-title-6\", \"title\": \"Limit test with a long long long long long long long long long long long title\", \"typeId\": 1, \"storyId\": 6, \"genreIds\": [1, 2], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"isComplete\": false, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 175, \"summaryWordCount\": 1, \"triggerWarningIds\": [], \"isExcludedFromEvents\": false}, \"before\": {\"slug\": \"limit-test-with-a-long-long-long-long-long-long-long-long-long-long-long-title-6\", \"title\": \"Limit test with a long long long long long long long long long long long title\", \"typeId\": 1, \"storyId\": 6, \"genreIds\": [1, 2], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"isComplete\": false, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 175, \"summaryWordCount\": 1, \"triggerWarningIds\": [], \"isExcludedFromEvents\": false}}',2,NULL,NULL,NULL,NULL,'2026-02-23 19:06:27'),(245,'Story.Updated','{\"after\": {\"slug\": \"limit-test-with-a-long-long-long-long-long-long-long-long-long-long-long-title-6\", \"title\": \"Limit test with a long long long long long long long long long long long title\", \"typeId\": 1, \"storyId\": 6, \"genreIds\": [1, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"isComplete\": false, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 175, \"summaryWordCount\": 1, \"triggerWarningIds\": [], \"isExcludedFromEvents\": false}, \"before\": {\"slug\": \"limit-test-with-a-long-long-long-long-long-long-long-long-long-long-long-title-6\", \"title\": \"Limit test with a long long long long long long long long long long long title\", \"typeId\": 1, \"storyId\": 6, \"genreIds\": [1, 2], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"isComplete\": false, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 175, \"summaryWordCount\": 1, \"triggerWarningIds\": [], \"isExcludedFromEvents\": false}}',2,NULL,NULL,NULL,NULL,'2026-02-23 19:14:04'),(246,'Story.Updated','{\"after\": {\"slug\": \"limit-test-with-a-supersupersupersupersuperlong-long-long-long-long-long-long-long-long-title-6\", \"title\": \"Limit test with a supersupersupersupersuperlong long long long long long long long long title\", \"typeId\": 1, \"storyId\": 6, \"genreIds\": [1, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"isComplete\": false, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 175, \"summaryWordCount\": 1, \"triggerWarningIds\": [], \"isExcludedFromEvents\": false}, \"before\": {\"slug\": \"limit-test-with-a-long-long-long-long-long-long-long-long-long-long-long-title-6\", \"title\": \"Limit test with a long long long long long long long long long long long title\", \"typeId\": 1, \"storyId\": 6, \"genreIds\": [1, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"isComplete\": false, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 175, \"summaryWordCount\": 1, \"triggerWarningIds\": [], \"isExcludedFromEvents\": false}}',2,NULL,NULL,NULL,NULL,'2026-02-23 19:17:06'),(247,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2026-02-24 20:55:16'),(248,'Chapter.Updated','{\"after\": {\"id\": 19, \"slug\": \"ch1-19\", \"title\": \"Ch1\", \"status\": \"published\", \"charCount\": 11, \"sortOrder\": 100, \"wordCount\": 2}, \"before\": {\"id\": 19, \"slug\": \"ch1-19\", \"title\": \"Ch1\", \"status\": \"published\", \"charCount\": 11, \"sortOrder\": 100, \"wordCount\": 2}, \"storyId\": 10}',2,NULL,NULL,NULL,NULL,'2026-02-24 20:57:05'),(249,'Chapter.Updated','{\"after\": {\"id\": 19, \"slug\": \"ch1-19\", \"title\": \"Ch1\", \"status\": \"published\", \"charCount\": 11, \"sortOrder\": 100, \"wordCount\": 2}, \"before\": {\"id\": 19, \"slug\": \"ch1-19\", \"title\": \"Ch1\", \"status\": \"published\", \"charCount\": 11, \"sortOrder\": 100, \"wordCount\": 2}, \"storyId\": 10}',2,NULL,NULL,NULL,NULL,'2026-02-24 21:05:01'),(250,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2026-02-27 21:01:26'),(251,'Auth.UserLoggedOut','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2026-02-27 21:03:53'),(252,'Auth.UserLoggedIn','{\"userId\": 6}',6,NULL,NULL,NULL,NULL,'2026-02-27 21:04:02'),(253,'Auth.UserLoggedIn','{\"userId\": 6}',6,NULL,NULL,NULL,NULL,'2026-02-28 21:05:27'),(254,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2026-03-01 20:04:18'),(255,'Profile.BioUpdated','{\"userId\": 2, \"xHandle\": \"fred\", \"description\": null, \"tiktokHandle\": \"fred\", \"blueskyHandle\": \"fred\", \"youtubeHandle\": \"fred\", \"facebookHandle\": \"lx\", \"mastodonHandle\": \"fred@fred.com\", \"instagramHandle\": \"fred\"}',2,NULL,NULL,NULL,NULL,'2026-03-01 20:09:35'),(256,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2026-03-08 20:31:13'),(257,'Auth.UserLoggedIn','{\"userId\": 6}',6,NULL,NULL,NULL,NULL,'2026-03-16 18:58:15'),(258,'Comment.Posted','{\"comment\": {\"is_reply\": false, \"author_id\": 6, \"entity_id\": 19, \"char_count\": 158, \"comment_id\": 36, \"word_count\": 35, \"entity_type\": \"chapter\", \"parent_comment_id\": null}}',6,NULL,NULL,NULL,NULL,'2026-03-16 19:03:55'),(259,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2026-03-17 19:18:50'),(260,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2026-03-20 20:19:22'),(261,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2026-03-21 08:17:56'),(262,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2026-04-18 05:13:13'),(263,'Story.Updated','{\"after\": {\"slug\": \"reine-du-rift-un-thriller-fantastique-fantasy-au-rythme-haletant-et-sans-la-moindre-violence-envers-les-hamsters-10\", \"title\": \"Reine du rift, un thriller fantastique fantasy au rythme haletant et sans la moindre violence envers les hamsters\", \"typeId\": 1, \"storyId\": 10, \"genreIds\": [1, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"isComplete\": false, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 154, \"summaryWordCount\": 31, \"triggerWarningIds\": [], \"isExcludedFromEvents\": false}, \"before\": {\"slug\": \"reine-du-rift-un-thriller-fantastique-fantasy-au-rythme-haletant-et-sans-la-moindre-violence-envers-les-hamsters-10\", \"title\": \"Reine du rift, un thriller fantastique fantasy au rythme haletant et sans la moindre violence envers les hamsters\", \"typeId\": 1, \"storyId\": 10, \"genreIds\": [1, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"isComplete\": false, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 153, \"summaryWordCount\": 31, \"triggerWarningIds\": [], \"isExcludedFromEvents\": false}}',2,NULL,NULL,NULL,NULL,'2026-04-18 05:13:52'),(264,'Story.Updated','{\"after\": {\"slug\": \"reine-du-rift-un-thriller-fantastique-fantasy-au-rythme-haletant-et-sans-la-moindre-violence-envers-les-hamsters-10\", \"title\": \"Reine du rift, un thriller fantastique fantasy au rythme haletant et sans la moindre violence envers les hamsters\", \"typeId\": 1, \"storyId\": 10, \"genreIds\": [1, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"isComplete\": false, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 156, \"summaryWordCount\": 31, \"triggerWarningIds\": [], \"isExcludedFromEvents\": false}, \"before\": {\"slug\": \"reine-du-rift-un-thriller-fantastique-fantasy-au-rythme-haletant-et-sans-la-moindre-violence-envers-les-hamsters-10\", \"title\": \"Reine du rift, un thriller fantastique fantasy au rythme haletant et sans la moindre violence envers les hamsters\", \"typeId\": 1, \"storyId\": 10, \"genreIds\": [1, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"isComplete\": false, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 154, \"summaryWordCount\": 31, \"triggerWarningIds\": [], \"isExcludedFromEvents\": false}}',2,NULL,NULL,NULL,NULL,'2026-04-18 05:18:57'),(265,'Story.Updated','{\"after\": {\"slug\": \"reine-du-rift-un-thriller-fantastique-fantasy-au-rythme-haletant-et-sans-la-moindre-violence-envers-les-hamsters-10\", \"title\": \"Reine du rift, un thriller fantastique fantasy au rythme haletant et sans la moindre violence envers les hamsters\", \"typeId\": 1, \"storyId\": 10, \"genreIds\": [1, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"isComplete\": false, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 181, \"summaryWordCount\": 35, \"triggerWarningIds\": [], \"isExcludedFromEvents\": false}, \"before\": {\"slug\": \"reine-du-rift-un-thriller-fantastique-fantasy-au-rythme-haletant-et-sans-la-moindre-violence-envers-les-hamsters-10\", \"title\": \"Reine du rift, un thriller fantastique fantasy au rythme haletant et sans la moindre violence envers les hamsters\", \"typeId\": 1, \"storyId\": 10, \"genreIds\": [1, 3], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"isComplete\": false, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 156, \"summaryWordCount\": 31, \"triggerWarningIds\": [], \"isExcludedFromEvents\": false}}',2,NULL,NULL,NULL,NULL,'2026-04-18 05:21:54'),(266,'Auth.UserLoggedIn','{\"userId\": 6}',6,NULL,NULL,NULL,NULL,'2026-04-18 05:37:15'),(267,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2026-04-19 19:29:01'),(268,'Auth.UserLoggedOut','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2026-04-19 19:43:08'),(269,'Auth.UserLoggedIn','{\"userId\": 6}',6,NULL,NULL,NULL,NULL,'2026-04-19 19:43:15'),(270,'Story.ModeratorAccessedPrivateChapter','{\"title\": \"Chapitre 3\", \"storyId\": 2, \"chapterId\": 13}',6,NULL,NULL,NULL,NULL,'2026-04-19 19:43:27'),(271,'Auth.UserLoggedOut','{\"userId\": 6}',6,NULL,NULL,NULL,NULL,'2026-04-19 19:43:43'),(272,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2026-04-19 19:43:51'),(273,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2026-04-23 18:53:21'),(274,'Auth.UserLoggedIn','{\"userId\": 6}',6,NULL,NULL,NULL,NULL,'2026-04-23 18:53:46'),(275,'Story.Created','{\"story\": {\"slug\": \"mon-histoire-privee-11\", \"title\": \"Mon histoire privée\", \"typeId\": 1, \"storyId\": 11, \"genreIds\": [1], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"isComplete\": false, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 6, \"summaryCharCount\": 126, \"summaryWordCount\": 24, \"triggerWarningIds\": [], \"isExcludedFromEvents\": false}}',6,NULL,NULL,NULL,NULL,'2026-04-23 18:54:50'),(276,'Chapter.Created','{\"chapter\": {\"id\": 20, \"slug\": \"chapitre-non-publie-20\", \"title\": \"Chapitre non publié\", \"status\": \"not_published\", \"charCount\": 54, \"sortOrder\": 300, \"wordCount\": 11}, \"storyId\": 9}',6,NULL,NULL,NULL,NULL,'2026-04-23 18:55:20'),(277,'Story.Updated','{\"after\": {\"slug\": \"mon-histoire-privee-11\", \"title\": \"Mon histoire privée\", \"typeId\": 1, \"storyId\": 11, \"genreIds\": [1], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"isComplete\": false, \"visibility\": \"private\", \"copyrightId\": 1, \"createdByUserId\": 6, \"summaryCharCount\": 126, \"summaryWordCount\": 24, \"triggerWarningIds\": [], \"isExcludedFromEvents\": false}, \"before\": {\"slug\": \"mon-histoire-privee-11\", \"title\": \"Mon histoire privée\", \"typeId\": 1, \"storyId\": 11, \"genreIds\": [1], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"isComplete\": false, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 6, \"summaryCharCount\": 126, \"summaryWordCount\": 24, \"triggerWarningIds\": [], \"isExcludedFromEvents\": false}}',6,NULL,NULL,NULL,NULL,'2026-04-23 18:56:04'),(278,'Story.VisibilityChanged','{\"title\": \"Mon histoire privée\", \"story_id\": 11, \"new_visibility\": \"private\", \"old_visibility\": \"public\"}',6,NULL,NULL,NULL,NULL,'2026-04-23 18:56:04'),(279,'Story.ModeratorAccessedPrivate','{\"title\": \"Mon histoire privée\", \"storyId\": 11}',2,NULL,NULL,NULL,NULL,'2026-04-23 18:56:14'),(280,'Auth.UserLoggedIn','{\"userId\": 4}',4,NULL,NULL,NULL,NULL,'2026-04-23 19:15:33'),(281,'Auth.UserRoleRevoked','{\"role\": \"user\", \"userId\": 4, \"actorUserId\": 2, \"targetIsAdmin\": false}',2,NULL,NULL,NULL,NULL,'2026-04-23 19:16:00'),(282,'Auth.UserRoleGranted','{\"role\": \"user-confirmed\", \"userId\": 4, \"actorUserId\": 2, \"targetIsAdmin\": false}',2,NULL,NULL,NULL,NULL,'2026-04-23 19:16:00'),(283,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2026-04-25 12:10:37'),(284,'Auth.UserLoggedIn','{\"userId\": 6}',6,NULL,NULL,NULL,NULL,'2026-04-25 12:12:08'),(285,'Story.ModeratorAccessedPrivate','{\"title\": \"Mon histoire privée\", \"storyId\": 11}',2,NULL,NULL,NULL,NULL,'2026-04-25 12:12:53'),(286,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2026-04-26 07:44:34'),(287,'Auth.UserLoggedIn','{\"userId\": 6}',6,NULL,NULL,NULL,NULL,'2026-04-26 11:45:21'),(288,'Comment.Posted','{\"comment\": {\"is_reply\": false, \"author_id\": 6, \"entity_id\": 6, \"char_count\": 142, \"comment_id\": 37, \"word_count\": 26, \"entity_type\": \"chapter\", \"parent_comment_id\": null}}',6,NULL,NULL,NULL,NULL,'2026-04-26 11:45:48'),(289,'Comment.Posted','{\"comment\": {\"is_reply\": true, \"author_id\": 2, \"entity_id\": 6, \"char_count\": 8, \"comment_id\": 38, \"word_count\": 1, \"entity_type\": \"chapter\", \"parent_comment_id\": 37}}',2,NULL,NULL,NULL,NULL,'2026-04-26 11:46:02'),(290,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2026-04-26 18:36:04'),(291,'Auth.UserLoggedIn','{\"userId\": 6}',6,NULL,NULL,NULL,NULL,'2026-04-26 20:18:16'),(292,'Comment.Posted','{\"comment\": {\"is_reply\": false, \"author_id\": 6, \"entity_id\": 7, \"char_count\": 158, \"comment_id\": 39, \"word_count\": 27, \"entity_type\": \"chapter\", \"parent_comment_id\": null}}',6,NULL,NULL,NULL,NULL,'2026-04-26 20:18:50'),(293,'Comment.Posted','{\"comment\": {\"is_reply\": true, \"author_id\": 6, \"entity_id\": 6, \"char_count\": 38, \"comment_id\": 40, \"word_count\": 6, \"entity_type\": \"chapter\", \"parent_comment_id\": 37}}',6,NULL,NULL,NULL,NULL,'2026-04-26 20:19:12'),(294,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2026-04-27 18:18:31'),(295,'Config.FeatureToggleAdded','{\"featureToggle\": {\"name\": \"discord_notifications\", \"roles\": [], \"access\": \"on\", \"domain\": \"features\", \"admin_visibility\": \"all_admins\"}}',2,NULL,NULL,NULL,NULL,'2026-04-27 18:26:11'),(296,'Config.FeatureToggleDeleted','{\"featureToggle\": {\"name\": \"discord_notifications\", \"roles\": [], \"access\": \"on\", \"domain\": \"features\", \"admin_visibility\": \"all_admins\"}}',2,NULL,NULL,NULL,NULL,'2026-04-27 18:27:24'),(297,'Config.FeatureToggleAdded','{\"featureToggle\": {\"name\": \"features.discord_notifications\", \"roles\": [], \"access\": \"on\", \"domain\": \"notifications\", \"admin_visibility\": \"all_admins\"}}',2,NULL,NULL,NULL,NULL,'2026-04-27 18:27:33'),(298,'Chapter.Updated','{\"after\": {\"id\": 19, \"slug\": \"ch1-19\", \"title\": \"Ch1\", \"status\": \"published\", \"charCount\": 426, \"sortOrder\": 100, \"wordCount\": 53}, \"before\": {\"id\": 19, \"slug\": \"ch1-19\", \"title\": \"Ch1\", \"status\": \"published\", \"charCount\": 11, \"sortOrder\": 100, \"wordCount\": 2}, \"storyId\": 10}',2,NULL,NULL,NULL,NULL,'2026-04-27 18:46:42'),(299,'Chapter.Updated','{\"after\": {\"id\": 19, \"slug\": \"ch1-19\", \"title\": \"Ch1\", \"status\": \"published\", \"charCount\": 426, \"sortOrder\": 100, \"wordCount\": 53}, \"before\": {\"id\": 19, \"slug\": \"ch1-19\", \"title\": \"Ch1\", \"status\": \"published\", \"charCount\": 426, \"sortOrder\": 100, \"wordCount\": 53}, \"storyId\": 10}',2,NULL,NULL,NULL,NULL,'2026-04-27 18:47:50'),(300,'Chapter.Updated','{\"after\": {\"id\": 19, \"slug\": \"ch1-19\", \"title\": \"Ch1\", \"status\": \"published\", \"charCount\": 427, \"sortOrder\": 100, \"wordCount\": 53}, \"before\": {\"id\": 19, \"slug\": \"ch1-19\", \"title\": \"Ch1\", \"status\": \"published\", \"charCount\": 426, \"sortOrder\": 100, \"wordCount\": 53}, \"storyId\": 10}',2,NULL,NULL,NULL,NULL,'2026-04-27 18:48:25'),(301,'Chapter.Updated','{\"after\": {\"id\": 19, \"slug\": \"ch1-19\", \"title\": \"Ch1\", \"status\": \"published\", \"charCount\": 437, \"sortOrder\": 100, \"wordCount\": 55}, \"before\": {\"id\": 19, \"slug\": \"ch1-19\", \"title\": \"Ch1\", \"status\": \"published\", \"charCount\": 427, \"sortOrder\": 100, \"wordCount\": 53}, \"storyId\": 10}',2,NULL,NULL,NULL,NULL,'2026-04-27 18:50:58'),(302,'Chapter.Updated','{\"after\": {\"id\": 19, \"slug\": \"ch1-19\", \"title\": \"Ch1\", \"status\": \"published\", \"charCount\": 441, \"sortOrder\": 100, \"wordCount\": 56}, \"before\": {\"id\": 19, \"slug\": \"ch1-19\", \"title\": \"Ch1\", \"status\": \"published\", \"charCount\": 437, \"sortOrder\": 100, \"wordCount\": 55}, \"storyId\": 10}',2,NULL,NULL,NULL,NULL,'2026-04-27 18:54:18'),(303,'Chapter.Updated','{\"after\": {\"id\": 19, \"slug\": \"ch1-19\", \"title\": \"Ch1\", \"status\": \"published\", \"charCount\": 441, \"sortOrder\": 100, \"wordCount\": 56}, \"before\": {\"id\": 19, \"slug\": \"ch1-19\", \"title\": \"Ch1\", \"status\": \"published\", \"charCount\": 441, \"sortOrder\": 100, \"wordCount\": 56}, \"storyId\": 10}',2,NULL,NULL,NULL,NULL,'2026-04-27 19:08:11'),(304,'Chapter.Updated','{\"after\": {\"id\": 19, \"slug\": \"ch1-19\", \"title\": \"Ch1\", \"status\": \"published\", \"charCount\": 449, \"sortOrder\": 100, \"wordCount\": 56}, \"before\": {\"id\": 19, \"slug\": \"ch1-19\", \"title\": \"Ch1\", \"status\": \"published\", \"charCount\": 441, \"sortOrder\": 100, \"wordCount\": 56}, \"storyId\": 10}',2,NULL,NULL,NULL,NULL,'2026-04-27 19:08:26'),(305,'Config.FeatureToggleAdded','{\"featureToggle\": {\"name\": \"discord\", \"roles\": [], \"access\": \"on\", \"domain\": \"discord_notifications\", \"admin_visibility\": \"all_admins\"}}',2,NULL,NULL,NULL,NULL,'2026-04-27 19:12:07'),(306,'Config.FeatureToggleDeleted','{\"featureToggle\": {\"name\": \"discord\", \"roles\": [], \"access\": \"on\", \"domain\": \"discord_notifications\", \"admin_visibility\": \"all_admins\"}}',2,NULL,NULL,NULL,NULL,'2026-04-27 19:12:38'),(307,'Config.FeatureToggleDeleted','{\"featureToggle\": {\"name\": \"features.discord_notifications\", \"roles\": [], \"access\": \"on\", \"domain\": \"notifications\", \"admin_visibility\": \"all_admins\"}}',2,NULL,NULL,NULL,NULL,'2026-04-27 19:12:40'),(308,'Config.FeatureToggleAdded','{\"featureToggle\": {\"name\": \"discord_notifications\", \"roles\": [], \"access\": \"on\", \"domain\": \"discord\", \"admin_visibility\": \"all_admins\"}}',2,NULL,NULL,NULL,NULL,'2026-04-27 19:12:47'),(309,'Auth.UserLoggedIn','{\"userId\": 6}',6,NULL,NULL,NULL,NULL,'2026-04-27 19:14:38'),(310,'Comment.Posted','{\"comment\": {\"is_reply\": false, \"author_id\": 6, \"entity_id\": 14, \"char_count\": 178, \"comment_id\": 41, \"word_count\": 37, \"entity_type\": \"chapter\", \"parent_comment_id\": null}}',6,NULL,NULL,NULL,NULL,'2026-04-27 19:15:28'),(311,'Comment.Posted','{\"comment\": {\"is_reply\": true, \"author_id\": 6, \"entity_id\": 3, \"char_count\": 76, \"comment_id\": 42, \"word_count\": 17, \"entity_type\": \"chapter\", \"parent_comment_id\": 16}}',6,NULL,NULL,NULL,NULL,'2026-04-27 19:17:41'),(312,'Comment.Posted','{\"comment\": {\"is_reply\": false, \"author_id\": 6, \"entity_id\": 3, \"char_count\": 175, \"comment_id\": 43, \"word_count\": 32, \"entity_type\": \"chapter\", \"parent_comment_id\": null}}',6,NULL,NULL,NULL,NULL,'2026-04-27 19:19:12'),(313,'Comment.Posted','{\"comment\": {\"is_reply\": true, \"author_id\": 2, \"entity_id\": 6, \"char_count\": 8, \"comment_id\": 44, \"word_count\": 2, \"entity_type\": \"chapter\", \"parent_comment_id\": 37}}',2,NULL,NULL,NULL,NULL,'2026-04-27 19:27:03'),(314,'Comment.Posted','{\"comment\": {\"is_reply\": true, \"author_id\": 6, \"entity_id\": 6, \"char_count\": 8, \"comment_id\": 45, \"word_count\": 2, \"entity_type\": \"chapter\", \"parent_comment_id\": 37}}',6,NULL,NULL,NULL,NULL,'2026-04-27 19:28:40'),(315,'Comment.Posted','{\"comment\": {\"is_reply\": true, \"author_id\": 2, \"entity_id\": 6, \"char_count\": 7, \"comment_id\": 46, \"word_count\": 2, \"entity_type\": \"chapter\", \"parent_comment_id\": 37}}',2,NULL,NULL,NULL,NULL,'2026-04-27 19:29:54'),(316,'Comment.Posted','{\"comment\": {\"is_reply\": true, \"author_id\": 2, \"entity_id\": 6, \"char_count\": 7, \"comment_id\": 47, \"word_count\": 2, \"entity_type\": \"chapter\", \"parent_comment_id\": 37}}',2,NULL,NULL,NULL,NULL,'2026-04-27 19:31:09'),(317,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2026-04-28 18:49:26'),(318,'Comment.Posted','{\"comment\": {\"is_reply\": true, \"author_id\": 2, \"entity_id\": 19, \"char_count\": 12, \"comment_id\": 48, \"word_count\": 3, \"entity_type\": \"chapter\", \"parent_comment_id\": 36}}',2,NULL,NULL,NULL,NULL,'2026-04-28 19:43:43'),(319,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2026-04-29 19:48:36'),(320,'Config.FeatureToggleUpdated','{\"featureToggle\": {\"name\": \"discord_notifications\", \"roles\": [], \"access\": \"off\", \"domain\": \"discord\", \"admin_visibility\": \"all_admins\"}}',2,NULL,NULL,NULL,NULL,'2026-04-29 20:14:03'),(321,'Config.FeatureToggleUpdated','{\"featureToggle\": {\"name\": \"discord_notifications\", \"roles\": [], \"access\": \"on\", \"domain\": \"discord\", \"admin_visibility\": \"all_admins\"}}',2,NULL,NULL,NULL,NULL,'2026-04-29 20:14:17'),(322,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2026-05-01 05:40:05'),(323,'Story.Deleted','{\"story\": {\"slug\": \"lhistoire-sans-debut-3\", \"title\": \"L\'histoire sans début\", \"typeId\": 1, \"storyId\": 3, \"genreIds\": [1], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"isComplete\": false, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 2, \"summaryCharCount\": 117, \"summaryWordCount\": 23, \"triggerWarningIds\": [], \"isExcludedFromEvents\": false}, \"chapters\": []}',2,NULL,NULL,NULL,NULL,'2026-05-01 06:08:14'),(324,'Follow.UserFollowed','{\"followed_id\": 6, \"follower_id\": 2}',2,NULL,NULL,NULL,NULL,'2026-05-01 06:47:41'),(325,'Auth.UserLoggedIn','{\"userId\": 6}',6,NULL,NULL,NULL,NULL,'2026-05-01 06:48:02'),(326,'Story.Created','{\"story\": {\"slug\": \"cest-pour-toi-lx-12\", \"title\": \"C\'est pour toi, LX\", \"typeId\": 1, \"storyId\": 12, \"genreIds\": [2], \"statusId\": null, \"audienceId\": 2, \"feedbackId\": null, \"isComplete\": false, \"visibility\": \"public\", \"copyrightId\": 2, \"createdByUserId\": 6, \"summaryCharCount\": 119, \"summaryWordCount\": 23, \"triggerWarningIds\": [], \"isExcludedFromEvents\": false}}',6,NULL,NULL,NULL,NULL,'2026-05-01 06:48:53'),(327,'Story.Created','{\"story\": {\"slug\": \"encore-une-histoire-13\", \"title\": \"Encore une histoire\", \"typeId\": 1, \"storyId\": 13, \"genreIds\": [2], \"statusId\": null, \"audienceId\": 1, \"feedbackId\": null, \"isComplete\": false, \"visibility\": \"public\", \"copyrightId\": 1, \"createdByUserId\": 6, \"summaryCharCount\": 116, \"summaryWordCount\": 19, \"triggerWarningIds\": [], \"isExcludedFromEvents\": false}}',6,NULL,NULL,NULL,NULL,'2026-05-01 06:49:53'),(328,'Follow.UserFollowed','{\"followed_id\": 6, \"follower_id\": 2}',2,NULL,NULL,NULL,NULL,'2026-05-01 06:50:01'),(329,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2026-05-05 19:02:00'),(330,'Auth.UserLoggedOut','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2026-05-05 19:14:46'),(331,'Auth.UserLoggedIn','{\"userId\": 4}',4,NULL,NULL,NULL,NULL,'2026-05-05 19:14:54'),(332,'Profile.DisplayNameChanged','{\"userId\": 4, \"newDisplayName\": \"Bob\", \"oldDisplayName\": \"Fred\"}',4,NULL,NULL,NULL,NULL,'2026-05-05 19:15:43'),(333,'Auth.UserLoggedOut','{\"userId\": 4}',4,NULL,NULL,NULL,NULL,'2026-05-05 19:15:49'),(334,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2026-05-05 19:15:55'),(335,'Profile.DisplayNameChanged','{\"userId\": 2, \"newDisplayName\": \"Fred\", \"oldDisplayName\": \"LogistiX le seigneur des loutres de la grande colline\"}',2,NULL,NULL,NULL,NULL,'2026-05-05 19:16:04'),(336,'Auth.UserLoggedOut','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2026-05-05 19:19:11'),(337,'Auth.UserLoggedIn','{\"userId\": 3}',3,NULL,NULL,NULL,NULL,'2026-05-05 19:19:17'),(338,'Profile.DisplayNameChanged','{\"userId\": 3, \"newDisplayName\": \"Alice\", \"oldDisplayName\": \"LX\"}',3,NULL,NULL,NULL,NULL,'2026-05-05 19:19:31'),(339,'Auth.UserLoggedOut','{\"userId\": 3}',3,NULL,NULL,NULL,NULL,'2026-05-05 19:19:34'),(340,'Auth.UserLoggedIn','{\"userId\": 2}',2,NULL,NULL,NULL,NULL,'2026-05-05 19:19:41');
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
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
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
INSERT INTO `faq_categories` VALUES (1,'COMPTE','compte','Tout ce qui concerne votre compte sur le Jardin des Esperluettes',1,1,2,2,'2025-10-22 18:19:20','2025-10-22 18:50:15'),(2,'STATUTS','statuts','Pour comprendre qui est qui et qui fait quoi sur le site ',2,1,2,2,'2025-10-22 18:20:22','2025-10-22 18:50:15'),(4,'PROFIL','profil','Tout ce qui concerne les pages profil, le vôtre où celui d\'un autre membre',5,1,2,2,'2025-10-22 18:28:46','2025-10-22 18:51:37'),(5,'LECTURE','lecture','Pour savoir où trouver les histoires et comment les choisir',6,1,2,2,'2025-10-22 18:28:56','2025-10-22 18:52:41'),(6,'PUBLIER UN COMMENTAIRE','publier-un-commentaire','Pour pouvoir laisser des commentaires et aider les auteurices',7,1,2,2,'2025-10-22 18:29:10','2025-10-22 18:54:01'),(7,'PUBLIER UNE HISTOIRE','publier-une-histoire','Tout ce qu\'il y a à savoir pour mettre une histoire en ligne',8,1,2,2,'2025-10-22 18:29:20','2025-10-22 18:54:33'),(8,'PUBLIER UN CHAPITRE','publier-un-chapitre','Tout ce qu\'il y a à savoir pour ajouter un chapitre à une de ses histoires',9,1,2,2,'2025-10-22 18:29:31','2025-10-22 18:55:39'),(9,'MODÉRATION SIGNALEMENT','moderation-signalement','Tout ce qui concerne le respect du règlement ',10,1,2,2,'2025-10-22 18:29:42','2025-10-22 19:26:31'),(10,'MENUS ','menus','Pour savoir où aller',3,1,2,2,'2025-10-22 18:49:06','2025-10-22 18:50:15'),(11,'TABLEAU DE BORD','tableau-de-bord','Pour comprendre les éléments du tableau de bord',4,1,2,2,'2025-10-22 18:49:51','2025-10-22 18:50:15');
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
  `question` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `answer` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_alt_text` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
-- Table structure for table `follow_follows`
--

DROP TABLE IF EXISTS `follow_follows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `follow_follows` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `follower_id` bigint unsigned NOT NULL,
  `followed_id` bigint unsigned NOT NULL,
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `follow_follows_follower_id_followed_id_unique` (`follower_id`,`followed_id`),
  KEY `follow_follows_followed_id_index` (`followed_id`),
  KEY `follow_follows_follower_id_index` (`follower_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `follow_follows`
--

LOCK TABLES `follow_follows` WRITE;
/*!40000 ALTER TABLE `follow_follows` DISABLE KEYS */;
INSERT INTO `follow_follows` VALUES (2,2,6,'2026-05-01 06:50:01');
/*!40000 ALTER TABLE `follow_follows` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=106 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2025_08_02_192800_create_roles_tables',1),(5,'2025_08_08_073600_create_activation_codes_table',1),(6,'2025_08_08_081605_add_is_active_to_users_table',1),(7,'2025_08_08_153258_create_profile_profiles_table',1),(8,'2025_08_09_205300_add_slug_to_profile_profiles_table',1),(9,'2025_08_10_000000_create_domain_events_table',1),(10,'2025_08_10_000001_create_story_ref_genres_table',1),(11,'2025_08_10_000002_create_story_ref_audiences_table',1),(12,'2025_08_10_000003_create_story_ref_types_table',1),(13,'2025_08_10_000004_create_story_ref_statuses_table',1),(14,'2025_08_10_000005_create_story_ref_copyrights_table',1),(15,'2025_08_10_000006_create_story_ref_trigger_warnings_table',1),(16,'2025_08_10_000007_create_story_ref_feedbacks_table',1),(17,'2025_08_10_080900_add_slug_to_roles_table',1),(18,'2025_08_12_131047_create_announcements_table',1),(19,'2025_08_13_231800_create_news_and_copy_from_announcements',1),(20,'2025_08_14_141500_create_static_pages_table',1),(21,'2025_08_15_074113_add_order_to_story_ref_genres_table',1),(22,'2025_08_17_000000_create_stories_table',1),(23,'2025_08_17_000001_create_story_collaborators_table',1),(24,'2025_08_18_000000_add_display_name_to_profile_profiles',1),(25,'2025_08_18_000001_backfill_display_name_and_make_not_null',1),(26,'2025_08_18_000002_drop_name_from_users_table',1),(27,'2025_08_24_000000_create_story_trigger_warnings_table',1),(28,'2025_08_25_000000_create_story_genres_table',1),(29,'2025_08_26_000000_make_story_refs_not_nullable_and_add_fks',1),(30,'2025_08_26_081455_backfill_profile_profile_slugs',1),(33,'2025_08_28_000002_create_chapters_table',2),(34,'2025_08_28_000003_create_reading_progress_table',2),(35,'2025_08_30_000000_drop_reads_guest_count_from_story_chapters',3),(36,'2025_08_30_000002_add_reads_logged_total_to_stories_table',4),(37,'2025_09_02_000000_create_comments_table',5),(38,'2025_09_12_000000_create_events_domain_table',6),(39,'2025_09_12_000001_drop_domain_events_table',7),(43,'2025_09_16_000001_add_word_and_character_count_to_story_chapters_table',8),(45,'2025_09_17_000100_add_tw_disclosure_to_stories_table',9),(46,'2025_09_19_000001_create_story_chapter_credits_table',10),(47,'2025_09_19_000002_backfill_story_chapter_credits',10),(50,'2025_09_25_221500_add_last_edited_at_to_story_chapters_table',11),(53,'2025_10_02_000000_create_messages_table',12),(54,'2025_10_02_000001_create_message_deliveries_table',12),(59,'2025_10_02_204800_drop_cross_domain_fks_in_story',13),(60,'2025_10_02_204900_drop_cross_domain_fks_in_news',13),(61,'2025_10_02_205000_drop_cross_domain_fks_in_profile',13),(62,'2025_10_02_205100_drop_cross_domain_fks_in_static_pages',13),(63,'2025_10_04_000000_create_discord_connection_codes_table',14),(64,'2025_10_04_000001_create_discord_users_table',15),(65,'2025_10_08_000001_allow_null_author_id_on_comments',16),(66,'2025_10_08_000002_make_created_by_nullable_on_static_pages',16),(67,'2025_10_08_000003_make_news_created_by_nullable',16),(68,'2025_10_14_000000_create_feature_toggles_table',16),(69,'2025_10_14_000001_create_moderation_reasons_table',16),(70,'2025_10_14_000002_create_moderation_reports_table',16),(71,'2025_10_15_000002_remove_is_answered_from_comments',16),(72,'2025_10_15_104700_add_core_roles_data',16),(73,'2025_10_17_153500_add_soft_deletes_to_profile_profiles_table',16),(74,'2025_10_17_161000_add_soft_deletes_to_stories_table',16),(75,'2025_10_17_161100_add_soft_deletes_to_story_chapters_table',16),(76,'2025_10_20_000000_create_activities_table',16),(77,'2025_10_22_062442_create_faq_categories_table',16),(78,'2025_10_22_062453_create_faq_questions_table',16),(79,'2025_10_23_000000_create_calendar_jardino_goals_table',16),(80,'2025_10_23_000001_create_calendar_jardino_story_snapshots_table',16),(81,'2025_10_23_000002_create_calendar_jardino_garden_cells_table',16),(82,'2025_11_03_000000_create_notifications_table',16),(83,'2025_11_03_000001_create_notification_reads_table',16),(84,'2025_11_05_063004_create_read_list_entries_table',16),(85,'2025_11_23_204700_add_compliance_fields_to_users_table',16),(86,'2025_12_01_112800_add_maturity_fields_to_story_ref_audiences',16),(87,'2025_12_03_091815_create_config_parameter_values_table',16),(88,'2025_12_03_160000_create_user_promotion_request_table',16),(89,'2025_12_04_000000_create_push_subscriptions_table',17),(90,'2025_12_04_174900_add_is_complete_and_is_excluded_from_events_to_stories_table',18),(92,'2024_12_13_140000_create_calendar_secret_gift_participants_table',19),(93,'2024_12_13_140001_create_calendar_secret_gift_assignments_table',20),(94,'2026_01_08_153420_create_statistic_snapshots_table',21),(95,'2026_01_08_153421_create_statistic_time_series_table',21),(96,'2024_12_28_172900_create_settings_table',22),(97,'2025_12_23_074614_add_gift_sound_path_to_calendar_secret_gift_assignments_table',22),(98,'2026_02_14_100000_add_cover_type_to_stories_table',23),(99,'2026_02_14_123400_add_has_cover_to_story_ref_genres',23),(100,'2026_03_01_000001_rename_social_url_to_handle_and_add_new_networks',24),(101,'2026_03_01_000002_backfill_strip_base_urls_from_social_handles',24),(102,'2026_04_26_000000_create_notification_preferences_table',25),(103,'2026_04_27_000000_create_discord_pending_notifications_table',26),(104,'2026_04_27_000001_create_discord_pending_recipients_table',26),(105,'2026_05_01_000001_create_follow_follows_table',27);
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
  `topic_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `moderation_reasons_topic_key_is_active_index` (`topic_key`,`is_active`),
  KEY `moderation_reasons_topic_key_sort_order_index` (`topic_key`,`sort_order`),
  KEY `moderation_reasons_topic_key_index` (`topic_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `moderation_reasons`
--

LOCK TABLES `moderation_reasons` WRITE;
/*!40000 ALTER TABLE `moderation_reasons` DISABLE KEYS */;
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
  `topic_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` bigint unsigned NOT NULL,
  `reported_user_id` bigint unsigned DEFAULT NULL,
  `reported_by_user_id` bigint unsigned NOT NULL,
  `reason_id` bigint unsigned NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `content_snapshot` json DEFAULT NULL,
  `content_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','confirmed','dismissed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reviewed_by_user_id` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `review_comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `news`
--

LOCK TABLES `news` WRITE;
/*!40000 ALTER TABLE `news` DISABLE KEYS */;
INSERT INTO `news` VALUES (1,'Test','test','a small test','<p>News&nbsp;avec&nbsp;un&nbsp;<u><a href=\"http://localhost/\" rel=\"noopener noreferrer\" target=\"_blank\">lien&nbsp;interne</a></u>&nbsp;</p><p>Et&nbsp;un&nbsp;<a href=\"https://discord.com\" rel=\"noopener noreferrer\" target=\"_blank\">lien&nbsp;externe</a></p><h2>Test&nbsp;d\'actualit&eacute;&nbsp;qui&nbsp;plante</h2><p>Avec&nbsp;l\'<strong>apostr</strong>ophe,&nbsp;et&nbsp;les&nbsp;\"guillemets\",&nbsp;c\'est&nbsp;plantage&nbsp;garanti&nbsp;!&nbsp;Hello?</p>\n','news/2025/09/01K4QY18AGQ1PTDYJ43VVKHGFA.jpg',1,1,'published',NULL,'2025-09-09 19:08:16',2,'2025-09-09 19:07:54','2025-12-04 19:49:47');
/*!40000 ALTER TABLE `news` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_preferences`
--

DROP TABLE IF EXISTS `notification_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_preferences` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `channel` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `enabled` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notification_preferences_user_id_type_channel_unique` (`user_id`,`type`,`channel`),
  KEY `notification_preferences_user_id_index` (`user_id`),
  KEY `notification_preferences_type_channel_enabled_index` (`type`,`channel`,`enabled`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_preferences`
--

LOCK TABLES `notification_preferences` WRITE;
/*!40000 ALTER TABLE `notification_preferences` DISABLE KEYS */;
INSERT INTO `notification_preferences` VALUES (1,2,'readlist.story.added','website',0,'2026-04-26 18:36:30','2026-04-26 18:36:30'),(2,2,'readlist.chapter.published','website',0,'2026-04-26 18:36:30','2026-04-26 18:36:30'),(3,2,'readlist.chapter.unpublished','website',0,'2026-04-26 18:36:30','2026-04-26 18:36:30'),(4,2,'story.chapter.root_comment','website',0,'2026-04-26 20:17:59','2026-04-26 20:17:59'),(5,2,'story.chapter.root_comment','discord',1,'2026-04-27 19:14:21','2026-04-27 19:14:21'),(6,2,'story.chapter.reply_comment','discord',1,'2026-04-27 19:15:50','2026-04-27 19:15:50'),(7,6,'story.chapter.root_comment','discord',1,'2026-04-27 19:18:58','2026-04-27 19:18:58'),(8,6,'story.chapter.reply_comment','discord',1,'2026-04-27 19:18:58','2026-04-27 19:18:58');
/*!40000 ALTER TABLE `notification_preferences` ENABLE KEYS */;
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
INSERT INTO `notification_reads` VALUES (1,2,'2026-04-26 20:18:09','2025-12-03 19:35:12','2025-12-03 19:35:12'),(2,2,'2026-04-26 20:18:09','2025-12-03 20:00:25','2025-12-03 20:00:25'),(3,8,'2025-12-04 08:22:39','2025-12-04 08:22:18','2025-12-04 08:22:18'),(4,8,NULL,'2025-12-04 08:41:54','2025-12-04 08:41:54'),(5,8,NULL,'2025-12-04 08:44:11','2025-12-04 08:44:11'),(6,8,NULL,'2025-12-04 08:45:19','2025-12-04 08:45:19'),(7,2,'2026-04-26 20:18:09','2025-12-04 09:05:43','2025-12-04 09:05:43'),(8,2,'2026-04-26 20:18:09','2025-12-04 09:05:55','2025-12-04 09:05:55'),(9,2,'2026-04-26 20:18:09','2025-12-04 15:33:58','2025-12-04 15:33:58'),(10,2,'2026-04-26 20:18:09','2025-12-04 15:36:53','2025-12-04 15:36:53'),(10,3,NULL,'2025-12-04 15:36:53','2025-12-04 15:36:53'),(11,2,'2026-04-26 20:18:09','2025-12-05 10:40:08','2025-12-05 10:40:08'),(12,6,'2025-12-05 10:40:41','2025-12-05 10:40:29','2025-12-05 10:40:29'),(13,2,'2026-04-26 20:18:09','2025-12-07 19:44:48','2025-12-07 19:44:48'),(14,6,NULL,'2025-12-07 19:48:26','2025-12-07 19:48:26'),(15,2,'2026-04-26 20:18:09','2025-12-17 14:36:07','2025-12-17 14:36:07'),(16,6,NULL,'2026-01-28 05:10:03','2026-01-28 05:10:03'),(17,6,NULL,'2026-01-28 05:11:18','2026-01-28 05:11:18'),(18,2,'2026-04-26 20:18:09','2026-03-16 19:03:55','2026-03-16 19:03:55'),(19,4,NULL,'2026-04-23 19:14:30','2026-04-23 19:14:30'),(20,4,NULL,'2026-04-23 19:16:39','2026-04-23 19:16:39'),(21,2,'2026-04-26 20:18:09','2026-04-23 19:17:18','2026-04-23 19:17:18'),(22,6,NULL,'2026-04-23 19:17:50','2026-04-23 19:17:50'),(23,4,NULL,'2026-04-25 12:12:35','2026-04-25 12:12:35'),(24,2,'2026-04-26 20:18:09','2026-04-26 11:45:48','2026-04-26 11:45:48'),(25,6,NULL,'2026-04-26 11:46:02','2026-04-26 11:46:02'),(27,2,NULL,'2026-04-26 20:19:12','2026-04-26 20:19:12'),(29,2,NULL,'2026-04-27 19:17:41','2026-04-27 19:17:41'),(29,3,NULL,'2026-04-27 19:17:41','2026-04-27 19:17:41'),(29,8,NULL,'2026-04-27 19:17:41','2026-04-27 19:17:41'),(31,6,NULL,'2026-04-27 19:27:03','2026-04-27 19:27:03'),(32,2,NULL,'2026-04-27 19:28:40','2026-04-27 19:28:40'),(33,6,NULL,'2026-04-27 19:29:54','2026-04-27 19:29:54'),(34,6,NULL,'2026-04-27 19:31:09','2026-04-27 19:31:09'),(35,6,NULL,'2026-04-28 19:43:43','2026-04-28 19:43:43'),(36,6,NULL,'2026-05-01 06:47:41','2026-05-01 06:47:41'),(37,2,NULL,'2026-05-01 06:48:53','2026-05-01 06:48:53'),(38,6,NULL,'2026-05-01 06:50:01','2026-05-01 06:50:01');
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
  `content_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_data` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_created_at_index` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,8,'story.chapter.comment','{\"is_reply\": false, \"comment_id\": 28, \"story_name\": \"Le Crépuscule des Âs\", \"story_slug\": \"le-crepuscule-des-as-1\", \"author_name\": \"harold\", \"author_slug\": \"harold\", \"chapter_slug\": \"chapitre-1\", \"chapter_title\": \"Chapitre 1\"}','2025-12-03 19:35:12','2025-12-03 19:35:12'),(2,8,'story.chapter.comment','{\"is_reply\": false, \"comment_id\": 29, \"story_name\": \"Je connais une histoire...\", \"story_slug\": \"je-connais-une-histoire-5\", \"author_name\": \"harold\", \"author_slug\": \"harold\", \"chapter_slug\": \"chapitre-1-14\", \"chapter_title\": \"Chapitre 1\"}','2025-12-03 20:00:25','2025-12-03 20:00:25'),(3,NULL,'auth.promotion.rejected','{\"user_name\": \"harold\"}','2025-12-04 08:22:18','2025-12-04 08:22:18'),(4,NULL,'auth.promotion.rejected','{\"user_name\": \"harold\"}','2025-12-04 08:41:54','2025-12-04 08:41:54'),(5,NULL,'auth.promotion.rejected','{\"user_name\": \"harold\"}','2025-12-04 08:44:11','2025-12-04 08:44:11'),(6,NULL,'auth.promotion.accepted','{\"user_name\": \"harold\"}','2025-12-04 08:45:19','2025-12-04 08:45:19'),(7,9,'story.chapter.comment','{\"is_reply\": false, \"comment_id\": 30, \"story_name\": \"Le Crépuscule des Âs\", \"story_slug\": \"le-crepuscule-des-as-1\", \"author_name\": \"Isabella\", \"author_slug\": \"isabella\", \"chapter_slug\": \"chapitre-1\", \"chapter_title\": \"Chapitre 1\"}','2025-12-04 09:05:43','2025-12-04 09:05:43'),(8,9,'story.chapter.comment','{\"is_reply\": false, \"comment_id\": 31, \"story_name\": \"Le Crépuscule des Âs\", \"story_slug\": \"le-crepuscule-des-as-1\", \"author_name\": \"Isabella\", \"author_slug\": \"isabella\", \"chapter_slug\": \"chapitre-3-tres-tres-long-aussi-pour-tester-3\", \"chapter_title\": \"Chapitre 3 très très long aussi pour tester\"}','2025-12-04 09:05:55','2025-12-04 09:05:55'),(9,8,'story.chapter.comment','{\"is_reply\": false, \"comment_id\": 32, \"story_name\": \"Le Crépuscule des Âs\", \"story_slug\": \"le-crepuscule-des-as-1\", \"author_name\": \"harold\", \"author_slug\": \"harold\", \"chapter_slug\": \"chapitre-3-tres-tres-long-aussi-pour-tester-3\", \"chapter_title\": \"Chapitre 3 très très long aussi pour tester\"}','2025-12-04 15:33:58','2025-12-04 15:33:58'),(10,8,'story.chapter.comment','{\"is_reply\": true, \"comment_id\": 33, \"story_name\": \"Le Crépuscule des Âs\", \"story_slug\": \"le-crepuscule-des-as-1\", \"author_name\": \"harold\", \"author_slug\": \"harold\", \"chapter_slug\": \"chapitre-3-tres-tres-long-aussi-pour-tester-3\", \"chapter_title\": \"Chapitre 3 très très long aussi pour tester\"}','2025-12-04 15:36:53','2025-12-04 15:36:53'),(11,6,'readlist.story.added','{\"story_slug\": \"le-crepuscule-des-as-1\", \"reader_name\": \"Fredounet\", \"reader_slug\": \"fredounet\", \"story_title\": \"Le Crépuscule des Âs\"}','2025-12-05 10:40:08','2025-12-05 10:40:08'),(12,2,'readlist.story.completed','{\"story_slug\": \"le-crepuscule-des-as-1\", \"author_name\": \"LogistiX le seigneur des loutres de la grande colline\", \"author_slug\": \"logistix-le-seigneur-des-loutres-de-la-grande-colline\", \"story_title\": \"Le Crépuscule des Âs\"}','2025-12-05 10:40:29','2025-12-05 10:40:29'),(13,6,'story.chapter.comment','{\"is_reply\": false, \"comment_id\": 34, \"story_name\": \"Test\", \"story_slug\": \"test-7\", \"author_name\": \"Fredounet\", \"author_slug\": \"fredounet\", \"chapter_slug\": \"chapitre-1-15\", \"chapter_title\": \"Chapitre 1\"}','2025-12-07 19:44:48','2025-12-07 19:44:48'),(14,2,'story.chapter.comment','{\"is_reply\": true, \"comment_id\": 35, \"story_name\": \"Test\", \"story_slug\": \"test-7\", \"author_name\": \"LogistiX le seigneur des loutres de la grande colline\", \"author_slug\": \"logistix-le-seigneur-des-loutres-de-la-grande-colline\", \"chapter_slug\": \"chapitre-1-15\", \"chapter_title\": \"Chapitre 1\"}','2025-12-07 19:48:26','2025-12-07 19:48:26'),(15,6,'readlist.story.added','{\"story_slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-vraiment-vraiment-pas-2\", \"reader_name\": \"Fredounet\", \"reader_slug\": \"fredounet\", \"story_title\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\"}','2025-12-17 14:36:07','2025-12-17 14:36:07'),(16,2,'readlist.story.added','{\"story_slug\": \"une-histoire-de-daniel-8\", \"reader_name\": \"LogistiX le seigneur des loutres de la grande colline\", \"reader_slug\": \"logistix-le-seigneur-des-loutres-de-la-grande-colline\", \"story_title\": \"Une histoire de Daniel\"}','2026-01-28 05:10:03','2026-01-28 05:10:03'),(17,2,'readlist.story.added','{\"story_slug\": \"une-autre-histoire-9\", \"reader_name\": \"LogistiX le seigneur des loutres de la grande colline\", \"reader_slug\": \"logistix-le-seigneur-des-loutres-de-la-grande-colline\", \"story_title\": \"Une autre histoire\"}','2026-01-28 05:11:18','2026-01-28 05:11:18'),(18,6,'story.chapter.comment','{\"is_reply\": false, \"comment_id\": 36, \"story_name\": \"Reine du rift, un thriller fantastique fantasy au rythme haletant et sans la moindre violence envers les hamsters\", \"story_slug\": \"reine-du-rift-un-thriller-fantastique-fantasy-au-rythme-haletant-et-sans-la-moindre-violence-envers-les-hamsters-10\", \"author_name\": \"Daniel\", \"author_slug\": \"daniel\", \"chapter_slug\": \"ch1-19\", \"chapter_title\": \"Ch1\"}','2026-03-16 19:03:55','2026-03-16 19:03:55'),(19,6,'story.collaborator.role_given','{\"role\": \"beta-reader\", \"user_name\": \"Daniel\", \"user_slug\": \"daniel\", \"story_slug\": \"mon-histoire-privee-11\", \"story_title\": \"Mon histoire privée\"}','2026-04-23 19:14:30','2026-04-23 19:14:30'),(20,6,'story.collaborator.removed','{\"user_name\": \"Daniel\", \"user_slug\": \"daniel\", \"story_slug\": \"mon-histoire-privee-11\", \"story_title\": \"Mon histoire privée\"}','2026-04-23 19:16:39','2026-04-23 19:16:39'),(21,6,'story.collaborator.role_given','{\"role\": \"author\", \"user_name\": \"Daniel\", \"user_slug\": \"daniel\", \"story_slug\": \"une-autre-histoire-9\", \"story_title\": \"Une autre histoire\"}','2026-04-23 19:17:18','2026-04-23 19:17:18'),(22,2,'story.collaborator.left','{\"user_name\": \"LogistiX le seigneur des loutres de la grande colline\", \"user_slug\": \"logistix-le-seigneur-des-loutres-de-la-grande-colline\", \"story_slug\": \"une-autre-histoire-9\", \"story_title\": \"Une autre histoire\"}','2026-04-23 19:17:50','2026-04-23 19:17:50'),(23,6,'story.collaborator.role_given','{\"role\": \"beta-reader\", \"user_name\": \"Daniel\", \"user_slug\": \"daniel\", \"story_slug\": \"mon-histoire-privee-11\", \"story_title\": \"Mon histoire privée\"}','2026-04-25 12:12:35','2026-04-25 12:12:35'),(24,6,'story.chapter.root_comment','{\"comment_id\": 37, \"story_name\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\", \"story_slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-vraiment-vraiment-pas-2\", \"author_name\": \"Daniel\", \"author_slug\": \"daniel\", \"chapter_slug\": \"chapitre-1-6\", \"chapter_title\": \"Chapitre-1\"}','2026-04-26 11:45:48','2026-04-26 11:45:48'),(25,2,'story.chapter.reply_comment','{\"comment_id\": 38, \"story_name\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\", \"story_slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-vraiment-vraiment-pas-2\", \"author_name\": \"LogistiX le seigneur des loutres de la grande colline\", \"author_slug\": \"logistix-le-seigneur-des-loutres-de-la-grande-colline\", \"chapter_slug\": \"chapitre-1-6\", \"chapter_title\": \"Chapitre-1\"}','2026-04-26 11:46:02','2026-04-26 11:46:02'),(26,6,'story.chapter.root_comment','{\"comment_id\": 39, \"story_name\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\", \"story_slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-vraiment-vraiment-pas-2\", \"author_name\": \"Daniel\", \"author_slug\": \"daniel\", \"chapter_slug\": \"chapitre-21-7\", \"chapter_title\": \"Chapitre 2.1\"}','2026-04-26 20:18:50','2026-04-26 20:18:50'),(27,6,'story.chapter.reply_comment','{\"comment_id\": 40, \"story_name\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\", \"story_slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-vraiment-vraiment-pas-2\", \"author_name\": \"Daniel\", \"author_slug\": \"daniel\", \"chapter_slug\": \"chapitre-1-6\", \"chapter_title\": \"Chapitre-1\"}','2026-04-26 20:19:12','2026-04-26 20:19:12'),(28,6,'story.chapter.root_comment','{\"comment_id\": 41, \"story_name\": \"Je connais une histoire...\", \"story_slug\": \"je-connais-une-histoire-5\", \"author_name\": \"Daniel\", \"author_slug\": \"daniel\", \"chapter_slug\": \"chapitre-1-14\", \"chapter_title\": \"Chapitre 1\"}','2026-04-27 19:15:28','2026-04-27 19:15:28'),(29,6,'story.chapter.reply_comment','{\"comment_id\": 42, \"story_name\": \"Le Crépuscule des Âs\", \"story_slug\": \"le-crepuscule-des-as-1\", \"author_name\": \"Daniel\", \"author_slug\": \"daniel\", \"chapter_slug\": \"chapitre-3-tres-tres-long-aussi-pour-tester-3\", \"chapter_title\": \"Chapitre 3 très très long aussi pour tester\"}','2026-04-27 19:17:41','2026-04-27 19:17:41'),(30,6,'story.chapter.root_comment','{\"comment_id\": 43, \"story_name\": \"Le Crépuscule des Âs\", \"story_slug\": \"le-crepuscule-des-as-1\", \"author_name\": \"Daniel\", \"author_slug\": \"daniel\", \"chapter_slug\": \"chapitre-3-tres-tres-long-aussi-pour-tester-3\", \"chapter_title\": \"Chapitre 3 très très long aussi pour tester\"}','2026-04-27 19:19:12','2026-04-27 19:19:12'),(31,2,'story.chapter.reply_comment','{\"comment_id\": 44, \"story_name\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\", \"story_slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-vraiment-vraiment-pas-2\", \"author_name\": \"LogistiX le seigneur des loutres de la grande colline\", \"author_slug\": \"logistix-le-seigneur-des-loutres-de-la-grande-colline\", \"chapter_slug\": \"chapitre-1-6\", \"chapter_title\": \"Chapitre-1\"}','2026-04-27 19:27:03','2026-04-27 19:27:03'),(32,6,'story.chapter.reply_comment','{\"comment_id\": 45, \"story_name\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\", \"story_slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-vraiment-vraiment-pas-2\", \"author_name\": \"Daniel\", \"author_slug\": \"daniel\", \"chapter_slug\": \"chapitre-1-6\", \"chapter_title\": \"Chapitre-1\"}','2026-04-27 19:28:40','2026-04-27 19:28:40'),(33,2,'story.chapter.reply_comment','{\"comment_id\": 46, \"story_name\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\", \"story_slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-vraiment-vraiment-pas-2\", \"author_name\": \"LogistiX le seigneur des loutres de la grande colline\", \"author_slug\": \"logistix-le-seigneur-des-loutres-de-la-grande-colline\", \"chapter_slug\": \"chapitre-1-6\", \"chapter_title\": \"Chapitre-1\"}','2026-04-27 19:29:54','2026-04-27 19:29:54'),(34,2,'story.chapter.reply_comment','{\"comment_id\": 47, \"story_name\": \"Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas\", \"story_slug\": \"immortelle-le-roman-dont-le-titre-nen-finit-vraiment-vraiment-pas-2\", \"author_name\": \"LogistiX le seigneur des loutres de la grande colline\", \"author_slug\": \"logistix-le-seigneur-des-loutres-de-la-grande-colline\", \"chapter_slug\": \"chapitre-1-6\", \"chapter_title\": \"Chapitre-1\"}','2026-04-27 19:31:09','2026-04-27 19:31:09'),(35,2,'story.chapter.reply_comment','{\"comment_id\": 48, \"story_name\": \"Reine du rift, un thriller fantastique fantasy au rythme haletant et sans la moindre violence envers les hamsters\", \"story_slug\": \"reine-du-rift-un-thriller-fantastique-fantasy-au-rythme-haletant-et-sans-la-moindre-violence-envers-les-hamsters-10\", \"author_name\": \"LogistiX le seigneur des loutres de la grande colline\", \"author_slug\": \"logistix-le-seigneur-des-loutres-de-la-grande-colline\", \"chapter_slug\": \"ch1-19\", \"chapter_title\": \"Ch1\"}','2026-04-28 19:43:43','2026-04-28 19:43:43'),(36,2,'follow.new_follower','{\"follower_id\": 2, \"follower_name\": \"LogistiX le seigneur des loutres de la grande colline\", \"follower_slug\": \"logistix-le-seigneur-des-loutres-de-la-grande-colline\"}','2026-05-01 06:47:41','2026-05-01 06:47:41'),(37,6,'follow.new_story','{\"story_id\": 12, \"author_id\": 6, \"story_slug\": \"cest-pour-toi-lx-12\", \"author_name\": \"Daniel\", \"author_slug\": \"daniel\", \"story_title\": \"C\'est pour toi, LX\"}','2026-05-01 06:48:53','2026-05-01 06:48:53'),(38,2,'follow.new_follower','{\"follower_id\": 2, \"follower_name\": \"LogistiX le seigneur des loutres de la grande colline\", \"follower_slug\": \"logistix-le-seigneur-des-loutres-de-la-grande-colline\"}','2026-05-01 06:50:01','2026-05-01 06:50:01');
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
INSERT INTO `password_reset_tokens` VALUES ('fhemery@hemit.fr','$2y$12$67Xo0VrSGFnEFCfd6geI8OsiY6cLGPqcs1i9ARgiyc8ULTMSPnWbO','2025-10-01 15:36:06'),('harold@hemit.fr','$2y$12$AVxu9ZAHxh8DsubBWXa9r.9M3b5dZL/HwffuuSS8tBz8oAfj4Y/Z2','2025-12-04 09:20:40'),('isabella@hemit.fr','$2y$12$PJcy9XoePSdAKIMKD2MtCuzKQy7Fd5QZMZTpZadKxCIVgprI3Jnne','2025-12-04 09:19:27');
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
  `facebook_handle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `x_handle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `instagram_handle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `youtube_handle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tiktok_handle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bluesky_handle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mastodon_handle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
INSERT INTO `profile_profiles` VALUES (1,'admin','Admin',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Admin profile','2025-08-28 07:31:18','2025-08-28 07:31:18',NULL),(2,'fred','Fred','profile_pictures/2_1756907799.jpg','lx','fred','fred','fred','fred','fred','fred@fred.com',NULL,'2025-08-28 07:33:07','2026-05-05 19:16:04',NULL),(3,'alice','Alice','profile_pictures/3_1756908205.jpg',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-30 05:39:53','2026-05-05 19:19:31',NULL),(4,'bob','Bob','profile_pictures/4.svg',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-09-08 19:37:08','2026-05-05 19:15:43',NULL),(5,'carol','Carol','profile_pictures/5.svg',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-09-08 20:31:43','2025-09-08 20:31:43',NULL),(6,'daniel','Daniel',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-09-12 15:01:38','2026-01-28 05:09:59',NULL),(7,'elliot','Elliot','profile_pictures/7.svg',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-09-14 06:43:35','2025-09-14 06:43:35',NULL),(8,'harold','harold','profile_pictures/8.svg',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-12-03 19:32:24','2025-12-03 19:32:24',NULL),(9,'isabella','Isabella','profile_pictures/9.svg',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-12-04 09:04:17','2025-12-04 09:04:17',NULL);
/*!40000 ALTER TABLE `profile_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `push_subscriptions`
--

DROP TABLE IF EXISTS `push_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `push_subscriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `endpoint` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `p256dh_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `auth_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `push_subscriptions_endpoint_unique` (`endpoint`),
  KEY `push_subscriptions_user_id_index` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `push_subscriptions`
--

LOCK TABLES `push_subscriptions` WRITE;
/*!40000 ALTER TABLE `push_subscriptions` DISABLE KEYS */;
INSERT INTO `push_subscriptions` VALUES (1,2,'https://jmt17.google.com/fcm/send/eQtsgIJDpkA:APA91bGrXPIV0Wu5h2AfqvtgIZGspFRVjJsZR3P9XZwsYSGpEElCW7uZqnNPj-VCXgpJ-pYS6BwAt0IwuCNHbHeRsCxbF6lNJU1FPdsqgEwp1tnxnwfSCuOc8lnGrSLZVRhbgI5D_CP8','BHM-CQwdCAV_XgtZ2RW76TRfQF2XgVDsrcc0fAQr2BUcNEWVeEkqOxBOz8O1vq_BhkpMFBam3ILE5RrINrK6PEw','tsyqhBS7OIhwMc3mT7XunQ','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-12-04 15:33:04','2025-12-04 15:33:04');
/*!40000 ALTER TABLE `push_subscriptions` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `read_list_entries`
--

LOCK TABLES `read_list_entries` WRITE;
/*!40000 ALTER TABLE `read_list_entries` DISABLE KEYS */;
INSERT INTO `read_list_entries` VALUES (1,6,1,'2025-12-05 10:40:08','2025-12-05 10:40:08'),(2,6,2,'2025-12-17 14:36:06','2025-12-17 14:36:06'),(3,2,8,'2026-01-28 05:10:03','2026-01-28 05:10:03'),(4,2,9,'2026-01-28 05:11:18','2026-01-28 05:11:18');
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
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_user`
--

LOCK TABLES `role_user` WRITE;
/*!40000 ALTER TABLE `role_user` DISABLE KEYS */;
INSERT INTO `role_user` VALUES (1,1,1,NULL,NULL),(2,2,3,NULL,NULL),(3,2,1,NULL,NULL),(4,3,3,NULL,NULL),(6,6,3,NULL,NULL),(18,7,3,NULL,NULL),(19,2,4,NULL,NULL),(23,9,2,NULL,NULL),(24,8,2,NULL,NULL),(25,6,9,NULL,NULL),(26,4,3,NULL,NULL);
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
INSERT INTO `roles` VALUES (1,'admin','admin','Administrator role','2025-08-28 07:31:18','2025-08-28 07:31:18'),(2,'user','user','Unconfirmed user role','2025-08-28 07:31:18','2025-08-28 07:31:18'),(3,'user-confirmed','user-confirmed','Confirmed user role','2025-08-28 07:31:18','2025-08-28 07:31:18'),(4,'Tech admin','tech-admin',NULL,'2025-09-26 19:48:19','2025-09-26 19:48:19'),(9,'Moderator','moderator','Responsible for moderation','2025-12-03 18:47:57','2025-12-03 18:47:57');
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
INSERT INTO `sessions` VALUES ('slt6KNiQNuGxz96lIYl6DohVcc3p2BE0GxAnry2w',2,'172.18.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:150.0) Gecko/20100101 Firefox/150.0','YTo1OntzOjY6Il90b2tlbiI7czo0MDoicXp1V1pqazVreVBNUWFkZThrcWl1VGtVQkh5eUlVUjF6N2R2eXZjdiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzM6Imh0dHA6Ly9sb2NhbGhvc3QvYWRtaW4vYXV0aC91c2VycyI7fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjI7czoyNToidXNlcl9jb21wbGlhbmNlX2NoZWNrZWRfMiI7YjoxO30=',1778009027);
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
  `domain` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_user_id_domain_key_unique` (`user_id`,`domain`,`key`),
  KEY `settings_user_id_index` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (2,2,'readlist','hide-up-to-date','1','2026-01-28 05:19:16','2026-01-28 05:19:16'),(4,6,'general','theme','spring','2026-02-27 21:04:12','2026-02-27 21:04:12'),(5,2,'general','interline','high','2026-04-18 06:31:45','2026-04-18 06:32:10');
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
-- Table structure for table `statistic_snapshots`
--

DROP TABLE IF EXISTS `statistic_snapshots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `statistic_snapshots` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `statistic_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `scope_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'global',
  `scope_id` bigint unsigned DEFAULT NULL,
  `value` decimal(20,4) NOT NULL,
  `metadata` json DEFAULT NULL,
  `computed_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stat_snapshot_unique` (`statistic_key`,`scope_type`,`scope_id`),
  KEY `statistic_snapshots_scope_type_scope_id_index` (`scope_type`,`scope_id`),
  KEY `statistic_snapshots_statistic_key_index` (`statistic_key`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `statistic_snapshots`
--

LOCK TABLES `statistic_snapshots` WRITE;
/*!40000 ALTER TABLE `statistic_snapshots` DISABLE KEYS */;
INSERT INTO `statistic_snapshots` VALUES (2,'global.total_users','global',NULL,4.0000,NULL,'2026-01-08 15:22:32','2026-01-08 15:22:32','2026-01-08 15:22:32');
/*!40000 ALTER TABLE `statistic_snapshots` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `statistic_time_series`
--

DROP TABLE IF EXISTS `statistic_time_series`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `statistic_time_series` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `statistic_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `scope_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'global',
  `scope_id` bigint unsigned DEFAULT NULL,
  `granularity` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `period_start` date NOT NULL,
  `value` decimal(20,4) NOT NULL,
  `cumulative_value` decimal(20,4) DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stat_ts_unique` (`statistic_key`,`scope_type`,`scope_id`,`granularity`,`period_start`),
  KEY `stat_ts_query` (`statistic_key`,`scope_type`,`scope_id`,`granularity`),
  KEY `statistic_time_series_period_start_index` (`period_start`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `statistic_time_series`
--

LOCK TABLES `statistic_time_series` WRITE;
/*!40000 ALTER TABLE `statistic_time_series` DISABLE KEYS */;
INSERT INTO `statistic_time_series` VALUES (5,'global.total_users','global',NULL,'daily','2025-09-12',1.0000,1.0000,NULL,'2026-01-08 15:22:32','2026-01-08 15:22:32'),(6,'global.total_users','global',NULL,'daily','2025-09-14',1.0000,2.0000,NULL,'2026-01-08 15:22:32','2026-01-08 15:22:32'),(7,'global.total_users','global',NULL,'daily','2025-12-03',1.0000,3.0000,NULL,'2026-01-08 15:22:32','2026-01-08 15:22:32'),(8,'global.total_users','global',NULL,'daily','2025-12-04',1.0000,4.0000,NULL,'2026-01-08 15:22:32','2026-01-08 15:22:32');
/*!40000 ALTER TABLE `statistic_time_series` ENABLE KEYS */;
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
  `cover_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'default',
  `cover_data` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stories`
--

LOCK TABLES `stories` WRITE;
/*!40000 ALTER TABLE `stories` DISABLE KEYS */;
INSERT INTO `stories` VALUES (1,2,'Le Crépuscule des Âs','le-crepuscule-des-as-1','<p>Dans le Royaume de Darkal, déchiré par les conflits depuis des temps immémoriaux, Cél, une épée douée de conscience, se cache avec Élias, son ultime porteur, dans une vallée oubliée.</p>\n\n<p>Tandis qu\'Élias transcrit l\'histoire séculaire de Cél, l\'arme dévoile son parcours tumultueux : sa création mystérieuse, ses années sanglantes d\'assassin, son rôle de protectrice pour des figures corrompues, et ses relations complexes avec un Porteur humain et une autre arme consciente qui l\'a trahie.</p>','public',1,1,1,1,NULL,1,1,'default',NULL,'listed','2025-09-04 10:06:16',7,'2025-08-28 20:27:18','2026-04-27 19:19:12',NULL),(2,2,'Immortelle, le roman dont le titre n\'en finit vraiment vraiment pas','immortelle-le-roman-dont-le-titre-nen-finit-vraiment-vraiment-pas-2','<p>&lt;p&gt;Test d\'une description suffisamment longue parce que bien sûr Isapass est passée par là et maintenant y\'a plus rien qui marche...&lt;/p&gt;</p>','community',1,1,1,NULL,NULL,0,0,'default',NULL,'listed','2025-08-30 14:12:06',2,'2025-08-29 07:42:34','2026-04-26 20:18:50',NULL),(5,2,'Je connais une histoire...','je-connais-une-histoire-5','<p>...qui énerve les gens. Mais alors vraiment, qui les énerve au-dela de toute limite. C\'est limite indécent.</p>\n\n<p>aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa</p>','public',1,1,1,NULL,NULL,0,0,'default',NULL,'unspoiled','2025-09-14 19:58:02',2,'2025-09-14 19:57:32','2026-04-27 19:15:28',NULL),(6,2,'Limit test with a supersupersupersupersuperlong long long long long long long long long title','limit-test-with-a-supersupersupersupersuperlong-long-long-long-long-long-long-long-long-title-6','<p>aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa</p>','public',1,1,1,NULL,NULL,0,0,'themed','autres','unspoiled',NULL,0,'2025-09-17 05:24:13','2026-02-23 19:17:06',NULL),(7,2,'Test','test-7','<p>Test deconexcion Test deconexcionTest deconexcion Test deconexcion Test deconexcion Test deconexcion Test deconexcionTest deconexcion Test deconexcion Test deconexcion</p>','public',1,1,1,NULL,NULL,0,0,'default',NULL,'no_tw','2025-12-07 19:44:17',1,'2025-10-01 19:17:21','2025-12-07 19:44:48',NULL),(8,6,'Une histoire de Daniel','une-histoire-de-daniel-8','<p>Il était une fois dans une contrée reculée un petit garçon qui passait son temps à regarder les nuages.</p>','public',1,1,1,NULL,NULL,0,0,'default',NULL,'no_tw','2026-01-28 05:09:30',1,'2026-01-28 05:09:14','2026-01-28 05:11:25',NULL),(9,6,'Une autre histoire','une-autre-histoire-9','<p>Une autre histoire de Daniel</p>\n\n<p>Une autre histoire de Daniel</p>\n\n<p>Une autre histoire de Daniel</p>\n\n<p>Une autre histoire de Daniel</p>','public',1,1,1,NULL,NULL,0,0,'default',NULL,'no_tw','2026-01-28 05:11:08',0,'2026-01-28 05:10:51','2026-01-28 05:11:08',NULL),(10,2,'Reine du rift, un thriller fantastique fantasy au rythme haletant et sans la moindre violence envers les hamsters','reine-du-rift-un-thriller-fantastique-fantasy-au-rythme-haletant-et-sans-la-moindre-violence-envers-les-hamsters-10','<p>Quand Marie rencontre Liem, dans un bar à jeu, elle est loin de se douter que sa vie va changer. Qu\'elle va décid<span class=\"ql-custom-emoji ql-custom-emoji-esperlunettes\">﻿<span></span>﻿</span>er de mettre ses études en pause pour... <span class=\"ql-custom-emoji ql-custom-emoji-espercolere\">﻿<span></span>﻿</span> ça fonctionne presque.</p>','public',1,1,1,NULL,NULL,0,0,'custom',NULL,'no_tw','2026-02-21 06:55:16',1,'2026-02-16 07:29:19','2026-04-18 05:21:54',NULL),(11,6,'Mon histoire privée','mon-histoire-privee-11','<p>Ceci est un résumé d\'au moins 100 caractères. On ne dirait pas comme ça, mais c\'est plutôt long 100 caractères. Non, vraiment.</p>','private',1,1,1,NULL,NULL,0,0,'default',NULL,'no_tw',NULL,0,'2026-04-23 18:54:50','2026-04-23 18:56:04',NULL),(12,6,'C\'est pour toi, LX','cest-pour-toi-lx-12','<p>Une histoire sans réel but si ce n\'est de montrer que la fonctionnalité de suivi d\'une esperluette marche bel et bien !</p>','public',1,2,2,NULL,NULL,0,0,'default',NULL,'no_tw',NULL,0,'2026-05-01 06:48:53','2026-05-01 06:48:53',NULL),(13,6,'Encore une histoire','encore-une-histoire-13','<p>Nouvelle histoire qui a pour but de vérifier que le unfollow fonctionne bel et bien (ce serait tellement magnifique)</p>','public',1,1,1,NULL,NULL,0,0,'default',NULL,'no_tw',NULL,0,'2026-05-01 06:49:53','2026-05-01 06:49:53',NULL);
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
INSERT INTO `story_chapter_credits` VALUES (2,200,10,'2025-09-19 11:48:00','2025-09-19 11:48:00'),(3,7,0,'2025-09-19 11:48:00','2025-09-19 11:48:00'),(6,11,4,'2025-12-07 19:44:48','2026-04-27 19:19:12'),(8,8,0,'2025-12-03 19:32:24','2025-12-04 15:33:58'),(9,7,0,'2025-12-04 09:04:17','2025-12-04 09:05:55');
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
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `story_chapters`
--

LOCK TABLES `story_chapters` WRITE;
/*!40000 ALTER TABLE `story_chapters` DISABLE KEYS */;
INSERT INTO `story_chapters` VALUES (1,1,'Chapitre 1','chapitre-1','<p>Quand j\'ai écrit ce texte, j\'avais de grandes ambitions pour cette histoire. J\'y croyais dur comme fer. Mais bon, faut croire que le fer, ça rouille... et moi, je dérouille.</p>\n\n<p>Bonne lecture quand même !</p>','<p>Le soleil se couchait paresseusement sur une vallée luxuriante, éclairant les falaises de grès d\'une lueur mordorée qui leur donnait une splendeur sans pareil. Les arbres bruissaient lentement au gré du vent qui filait à travers l\'étroite vallée. La nature faisait ses derniers préparatifs, sur le point d\'aller se coucher. Une bonne partie de la vallée était d\'ailleurs déjà dans la pénombre. Toute personne qui se serait tenue à l\'entrée de la vallée aurait gravé dans sa mémoire un moment magnifique comme seule la nature pouvait en offrir. Mais il n\'y avait personne pour admirer cette vue.</p>\n\n<p>– *Tu comptes vraiment y passer la soirée ? On a autre chose à faire, tu le sais ! »* </p>\n\n<p>Il n\'y avait personne pour admirer la vue, mais il y avait quelqu\'un, dans les arbres, qui marchait d\'un pas lent, faisant attention où il mettait les pieds. L\'homme était habillé simplement, d\'une tenue qui lui seyait au corps, une sorte de cuir souple qui lui permettait de se déplacer sans bruit et sans perturber la vie de la nature. Un exploit vue la musculature et la corpulence de l\'homme aux tempes grisonnantes qui se frayait un chemin parmi les bruyères. Il regardait autour de lui, consciencieusement. Il cherchait quelque chose. </p>\n\n<p>– <em>Tu n\'as pas la moindre once de curiosité alors ? »</em></p>\n\n<p>L\'homme ne réagit pas, mais son regard tomba visiblement sur ce qu\'il cherchait. Il s\'approcha lentement d\'un arbre, et inspecta un mince fil de chanvre qui traînait au sol, presque négligemment. Un collet. Vide de tout occupant. Il ne s\'en formalisa pas. Il l\'ajusta légèrement, puis repartit de ce même pas silencieux, comme s\'il faisait corps avec la nature.</p>\n\n<p>*– La cabane va s\'écrouler de vieillesse si tu ne te dépêches pas ! »* </p>\n\n<p>Il fronça les sourcils. Il s\'arrêta, le temps de prendre une grande inspiration, et de regarder, à travers les feuillages denses, serpenter une sente que seule lui semblait voir. Ses sens étaient aux aguets, comme s\'il essayait de percevoir un bruit en particulier. Il n\'y avait rien que le pépiement des oiseaux, les bruits de fourrés qui bougent au passage d\'un rongeur, et ce bruit de fond caractéristique et hypnotisant d\'une rivière qui coule.</p>\n\n<p>*– Elle va s\'écrouler, et je serai coincée dessous à jamais. Je te dirai bien que tu auras ma mort sur ma conscience, mais je suppose que tu t\'en moques de toute façons. Tu es bien trop occupé à gambader. »* </p>\n\n<p>Cette fois, son regard était ennuyé. Il reprit sa marche d\'un pas résolu, un peu moins discret. Comme si d\'un coup, il s\'était souvenu de quelque chose d\'urgent. Qu\'il avait laissé une casserole sur le feu, ou qu\'il avait oublié de fermer la barrière du champ dans lequel se repassait un troupeau. </p>\n\n<p>*– C\'est incroyable comme l\'être humain est capable de procrastination. Avec une telle paresse, je reste quand même ébahie que vous ayez réussi à conquérir autant de territoire. Je vous imaginais bien plus dire “Non, tu sais quoi, j\'irai mettre une clôture autour de ce champ demain. Ou le mois prochain. Ou tiens, jamais, c\'est bien aussi, jamais.”»*</p>\n\n<p>Il s\'arrêta à nouveau. Il était arrivé en bas d\'une petite sente, bien plus marquée celle-ci. Il regarda en haut. Fondue dans la nature, une cabane de belle taille trônait dans un renfoncement. Entourée par les arbres et la végétation, elle prenait paresseusement les derniers rayons du soleil. Un léger murmure sortit de sa bouche, à peine plus qu\'un souffle, clairement inaudible pour quiconque aurait été à proximité.</p>\n\n<p>– C\'est peut-être cette même procrastination qui m\'empêche de trouver l\'énergie de m\'éloigner suffisamment pour ne plus t\'entendre, alors tu devrais t\'en réjouir »</p>\n\n<p>*– Hé, c\'est bon. Tu ne vas pas repartir alors que tu es si proche. Mais tu pourrais comprendre mon impatience et avoir un peu d\'empathie quand même. »*</p>\n\n<p>– Les journées sont courtes et l\'hiver est là. Je sais que tu n\'as ni besoin de manger, ni de te chauffer, mais moi si. »</p>\n\n<p>*– Même si tu sais bien que je ne suis jamais contre un petit feu. Je ne ressens pas le froid, mais j\'aime m\'imprégner de cette sensation de chaleur ! »*</p>\n\n<p>L\'homme était arrivé à l\'entrée de la cabane pendant cette étrange discussion. Il monta les trois marches qui permettaient l\'accès à la terrasse, protégée par une avancée de toit, où trônait un fauteuil à bascule particulièrement ouvragé. Puis il poussa lentement la lourde porte qui grinça légèrement en s\'ouvrant. La pièce était sombre, et il attrapa machinalement une lanterne. Il appuya sur un bouton d\'un geste habitué. Dans un « clac » retentissant, une flamme apparut, et la pénombre se dissipa quelque peu. C\'était une salle de vie prévue pour peu d\'occupants. La petite table qui trônait au milieu n\'était entourée que de deux chaises, et l\'une d\'entre elle était couverte d\'un film de poussière. Une commode trônait, imposante, taillée dans un bois brut, contre le mur du fond. Et sur le côté, un deuxième exemplaire d\'un fauteuil à bascule trônait à côté d\'une cheminée.</p>\n\n<p>Il ressortit prestement, attrapa quelques bûches dans un appentis sur le côté de la maison, et retourna à l\'intérieur préparer un feu.</p>\n\n<p>*– Tu sais que j\'apprécierai que tu m\'emmènes avec toi de temps en temps. Elle est sympa cette bicoque, mais j\'aime bien les grands espaces. »*</p>\n\n<p>Soupirant, l\'homme regarda dans un coin de la pièce, dans une sorte d\'alcôve tapissée d\'un tissu épais. Posée à la verticale sur un piédestal de facture modeste, une épée semblait luire légèrement dans la pénombre. Son fourreau bleu nuit strié de fils d\'argent était d\'une facture remarquable, tout comme la garde et le pommeau, d\'un bleu azur parsemé de ces mêmes fils argentés. Clairement, cette épée jurait sur le reste de la cabane, par sa richesse et la finesse de l\'ouvrage. La voix de l\'homme prit un peu de volume alors qu\'il apostropha l\'épée.</p>\n\n<p>– Et depuis quand as-tu besoin de sortir pour ressentir les grands espaces, Cél ? »</p>\n\n<p>*– Élias, si tu avais mon âge et mon passé, tu saurais que toute occasion est bonne pour profiter des grands espaces. »*</p>\n\n<p>Élias, agenouillé près du feu, marqua un temps d\'arrêt. Il semblait contrit, sur le point de s\'excuser. Quelque chose sembla s\'insinuer en lui, et l\'instant d\'après il souriait.</p>\n\n<p>– C\'est probablement vrai. Mais même si tu ne me parles qu\'à moi, et par télépathie, tu réussirais probablement à faire fuir tous les animaux de la forêt. »</p>\n\n<p>L\'épée sembla émettre un petit rire hautain, alors qu\'Élias prenait précautionneusement une branche qu\'il passa dans l\'ouverture de la lanterne, puis qu\'il reposa dans la cheminée, donnant vie à un nouveau feu, plus fourni cette fois. Puis il se dirigea d\'un pas lent vers une pièce attenante, qui semblait servir d\'entrepôt, et entreprit de récupérer de quoi dîner, pendant que la voix de Cél, dans sa tête, continuait.</p>\n\n<p><em>– Bien sûr que non, je suis sûre que tous ces animaux m\'adoreraient. »</em></p>',0,'published','2025-08-29 10:16:08','2025-12-04 19:50:55',2,1225,6803,'2025-08-29 07:41:13','2025-12-04 19:50:55',NULL),(2,1,'Chapitre 2 très très long','chapitre-2-tres-tres-long-2','<ol><li>1</li><li>2</li><li>3</li></ol>','<p>dsadasda</p>\n\n<p><br></p>\n\n<ul><li>point 1</li><li>Point 2</li></ul>',100,'not_published','2025-08-29 07:41:22','2025-09-22 19:21:17',0,4,26,'2025-08-29 07:41:22','2025-09-25 21:07:27',NULL),(3,1,'Chapitre 3 très très long aussi pour tester','chapitre-3-tres-tres-long-aussi-pour-tester-3',NULL,'<p>ddd</p>',50,'published','2025-08-29 19:54:46','2025-09-22 19:28:00',4,1,3,'2025-08-29 19:54:46','2026-04-27 19:19:12',NULL),(6,2,'Chapitre-1','chapitre-1-6',NULL,'<p>dasdad</p>',100,'published','2025-08-30 14:09:47','2025-09-09 20:29:45',1,1,6,'2025-08-30 14:09:47','2026-04-26 11:45:48',NULL),(7,2,'Chapitre 2.1','chapitre-21-7',NULL,'<p>dasdas</p>',200,'published','2025-08-30 14:12:06','2025-09-09 20:27:18',1,1,6,'2025-08-30 14:12:06','2026-04-26 20:18:50',NULL),(12,1,'Chapitre 4','chapitre-4-12',NULL,'<p>dsadsa</p>',300,'published','2025-09-04 10:06:16','2025-09-25 20:05:26',1,1,6,'2025-09-04 10:06:16','2025-09-25 21:07:27',NULL),(13,2,'Chapitre 3','chapitre-3-13',NULL,'<p>dasdasda</p>',300,'not_published','2025-09-09 20:26:40','2025-09-09 20:26:48',0,1,8,'2025-09-09 20:26:40','2025-09-09 20:26:48',NULL),(14,5,'Chapitre 1','chapitre-1-14',NULL,'<p>Où tout débute.</p>',100,'published','2025-09-14 19:58:02','2025-09-14 19:58:02',2,3,15,'2025-09-14 19:58:02','2026-04-27 19:15:28',NULL),(15,7,'Chapitre 1','chapitre-1-15',NULL,'<p>Test</p>',100,'published','2025-12-07 19:44:17','2025-12-07 19:44:17',1,1,4,'2025-12-07 19:44:17','2025-12-07 19:44:48',NULL),(16,8,'Chapitre 1','chapitre-1-16',NULL,'<p>Et voici le chapitre 1</p>',100,'published','2026-01-28 05:09:30','2026-01-28 05:09:30',1,5,22,'2026-01-28 05:09:30','2026-01-28 05:11:25',NULL),(17,9,'Chapitre 1','chapitre-1-17',NULL,'<p>test</p>',100,'published','2026-01-28 05:11:01','2026-01-28 05:11:01',0,1,4,'2026-01-28 05:11:01','2026-01-28 05:11:01',NULL),(18,9,'Chapitre 2','chapitre-2-18',NULL,'<p>Test</p>',200,'published','2026-01-28 05:11:08','2026-01-28 05:11:08',0,1,4,'2026-01-28 05:11:08','2026-01-28 05:11:08',NULL),(19,10,'Ch1','ch1-19','<p>Ceci est un texte <span class=\"ql-spoiler\">avec un spoiler dedans</span>. Un vrai spoiler, s&eacute;rieux : <span class=\"ql-spoiler\">pouet</span>. Voil&agrave;, c\'est tout.</p>\n','<p>Chapitre 1</p><p>Ok, alors soyons clairs vraiment tr&egrave;s clairs&nbsp;: il vaudrait mieux que &ccedil;a marche&nbsp;!</p><p>&mdash;&nbsp;Bon. On va dire que &ccedil;a marche, parce que je vous entends d&eacute;j&agrave; dire&nbsp;: &laquo;&nbsp;Non, mais LogistiX, il fait expr&egrave;s de nous enlever nos espaces ins&eacute;cables juste pour nous emb&ecirc;ter. C\'est le probl&egrave;me, avec des sites &agrave; 2&nbsp;&euro;&nbsp;&raquo;. Je crois. Bon...</p>\n',100,'published','2026-02-21 06:55:16','2026-04-27 19:08:26',1,56,322,'2026-02-21 06:55:16','2026-04-27 19:08:26',NULL),(20,9,'Chapitre non publié','chapitre-non-publie-20',NULL,'<p>Parce que pour l\'instant, je n\'ai rien &agrave; dire.</p>\n',300,'not_published',NULL,'2026-04-23 18:55:20',0,11,47,'2026-04-23 18:55:20','2026-04-23 18:55:20',NULL);
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
INSERT INTO `story_collaborators` VALUES (1,2,'author',2,'2025-08-28 20:27:18','2025-08-28 20:27:18'),(2,2,'author',2,'2025-08-29 07:42:34','2025-08-29 07:42:34'),(5,2,'author',2,'2025-09-14 19:57:32','2025-09-14 19:57:32'),(6,2,'author',2,'2025-09-17 05:24:13','2025-09-17 05:24:13'),(7,2,'author',2,'2025-10-01 19:17:21','2025-10-01 19:17:21'),(8,6,'author',6,'2026-01-28 05:09:14','2026-01-28 05:09:14'),(9,6,'author',6,'2026-01-28 05:10:51','2026-01-28 05:10:51'),(10,2,'author',2,'2026-02-16 07:29:19','2026-02-16 07:29:19'),(11,4,'beta-reader',6,'2026-04-25 12:12:35','2026-04-25 12:12:35'),(11,6,'author',6,'2026-04-23 18:54:50','2026-04-23 18:54:50'),(12,6,'author',6,'2026-05-01 06:48:53','2026-05-01 06:48:53'),(13,6,'author',6,'2026-05-01 06:49:53','2026-05-01 06:49:53');
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
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `story_genres`
--

LOCK TABLES `story_genres` WRITE;
/*!40000 ALTER TABLE `story_genres` DISABLE KEYS */;
INSERT INTO `story_genres` VALUES (13,7,1,'2025-10-01 19:17:21','2025-10-01 19:17:21'),(14,2,1,'2025-12-03 19:34:24','2025-12-03 19:34:24'),(15,2,2,'2025-12-03 19:34:24','2025-12-03 19:34:24'),(16,2,3,'2025-12-03 19:34:24','2025-12-03 19:34:24'),(20,5,1,'2025-12-04 19:03:49','2025-12-04 19:03:49'),(21,5,2,'2025-12-04 19:03:49','2025-12-04 19:03:49'),(25,1,1,'2025-12-05 10:40:29','2025-12-05 10:40:29'),(26,8,1,'2026-01-28 05:09:14','2026-01-28 05:09:14'),(27,9,1,'2026-01-28 05:10:51','2026-01-28 05:10:51'),(40,6,1,'2026-02-23 19:17:06','2026-02-23 19:17:06'),(41,6,3,'2026-02-23 19:17:06','2026-02-23 19:17:06'),(46,10,1,'2026-04-18 05:21:54','2026-04-18 05:21:54'),(47,10,3,'2026-04-18 05:21:54','2026-04-18 05:21:54'),(49,11,1,'2026-04-23 18:56:04','2026-04-23 18:56:04'),(50,12,2,'2026-05-01 06:48:53','2026-05-01 06:48:53'),(51,13,2,'2026-05-01 06:49:53','2026-05-01 06:49:53');
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
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `story_reading_progress`
--

LOCK TABLES `story_reading_progress` WRITE;
/*!40000 ALTER TABLE `story_reading_progress` DISABLE KEYS */;
INSERT INTO `story_reading_progress` VALUES (8,3,1,3,'2025-09-03 14:19:52',NULL,NULL),(11,3,1,12,'2025-09-25 20:05:26',NULL,NULL),(17,8,1,1,'2025-12-03 19:35:12',NULL,NULL),(18,8,5,14,'2025-12-03 20:00:25',NULL,NULL),(19,9,1,1,'2025-12-04 09:05:43',NULL,NULL),(20,9,1,3,'2025-12-04 09:05:55',NULL,NULL),(21,8,1,3,'2025-12-04 15:33:58',NULL,NULL),(22,6,7,15,'2025-12-07 19:44:48',NULL,NULL),(23,2,8,16,'2026-01-28 05:11:25',NULL,NULL),(24,6,10,19,'2026-03-16 19:03:55',NULL,NULL),(25,6,2,6,'2026-04-26 11:45:48',NULL,NULL),(26,6,2,7,'2026-04-26 20:18:50',NULL,NULL),(27,6,5,14,'2026-04-27 19:15:28',NULL,NULL),(28,6,1,3,'2026-04-27 19:19:12',NULL,NULL);
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
  `has_cover` tinyint(1) NOT NULL DEFAULT '0',
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
INSERT INTO `story_ref_genres` VALUES (1,'Fantasy','fantasy',1,'Imaginary worlds filled with dragons',1,1,'2025-08-28 07:31:18','2026-02-16 07:27:19'),(2,'Épistolaire vraiment long','epistolaire-vraiment-long',2,NULL,1,0,'2025-09-14 19:02:08','2025-09-14 19:02:08'),(3,'Autobiographie','autres',3,NULL,1,1,'2025-09-14 19:02:22','2026-02-16 07:28:08');
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
INSERT INTO `story_trigger_warnings` VALUES (1,1,'2025-12-05 10:40:29','2025-12-05 10:40:29'),(1,2,'2025-12-05 10:40:29','2025-12-05 10:40:29'),(2,2,'2025-12-03 19:34:24','2025-12-03 19:34:24');
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
  `status` enum('pending','accepted','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `comment_count` int unsigned NOT NULL,
  `requested_at` timestamp NOT NULL,
  `decided_at` timestamp NULL DEFAULT NULL,
  `decided_by` bigint unsigned DEFAULT NULL,
  `rejection_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_promotion_request_decided_by_foreign` (`decided_by`),
  KEY `user_promotion_request_user_id_requested_at_index` (`user_id`,`requested_at`),
  KEY `user_promotion_request_status_index` (`status`),
  CONSTRAINT `user_promotion_request_decided_by_foreign` FOREIGN KEY (`decided_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `user_promotion_request_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_promotion_request`
--

LOCK TABLES `user_promotion_request` WRITE;
/*!40000 ALTER TABLE `user_promotion_request` DISABLE KEYS */;
INSERT INTO `user_promotion_request` VALUES (1,8,'rejected',2,'2025-12-03 20:13:32','2025-12-04 08:22:18',2,'Quand on poste des commentaires, on aime qu\'il y ait un petit quelque chose dedans. Là, c\'est creux, on sent bien que tu ne fais ça que pour passer ta période d\'essai. Désolé, mais la bienveillance ne suffit pas. C\'est aussi pour ça qu\'il y a un ABC du commentaire.\r\nEt non, ce commentaire n\'est pas trop long. Il est instillé de l\'essence fondamentale de la Baronne, dont tu ferais bien de t\'inspirer afin que tes commentaires paraissent moins lapidaires.','2025-12-03 20:13:32','2025-12-04 08:22:18'),(2,8,'rejected',2,'2025-12-04 08:41:34','2025-12-04 08:41:54',2,'Nul.','2025-12-04 08:41:34','2025-12-04 08:41:54'),(3,8,'rejected',2,'2025-12-04 08:42:51','2025-12-04 08:44:11',2,'C\'est la troisième fois que je te refuse, force est de constater que tu ne fais aucun effort. Et pourtant, je m\'échine à te demande de t\'inspirer de l\'ABC du commentaire et des commentaires de notre membres Esperluettes aguerri·e·s.\r\nNon, tu n\'es pas obligé·e d\'écrire un roman comme Isapass à chaque fois que tu commentes, mais un peu de substance ne ferait quand même pas de mal, qu\'en penses tu ?','2025-12-04 08:42:51','2025-12-04 08:44:11'),(4,8,'accepted',2,'2025-12-04 08:45:13','2025-12-04 08:45:19',2,NULL,'2025-12-04 08:45:13','2025-12-04 08:45:19'),(5,9,'pending',2,'2025-12-04 09:06:03',NULL,NULL,NULL,'2025-12-04 09:06:03','2025-12-04 09:06:03');
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
INSERT INTO `users` VALUES (1,'admin@example.com','2025-08-28 07:31:18',NULL,0,NULL,1,'$2y$12$HZZdt7HY1WjNpDoRX/LDOuEte8NyCQcrIbP9HhVsuc7un7ltjNPQ2',NULL,'2025-08-28 07:31:18','2025-08-28 07:31:18'),(2,'fred@hemit.fr','2025-08-28 07:35:45','2025-12-03 19:33:33',0,NULL,1,'$2y$12$VBThRTLI9APCLcHR.K0kB.HTc2CoIdIiR5Q3whQXBelY3nULdwIfm','UbDxAV0i2apdo4tQ8qLXZgfZh9rcUNyWim7pXD7PoDwPsedviKVXBTH7jbWM','2025-08-28 07:33:07','2025-12-03 19:33:33'),(3,'alice@hemit.fr','2025-08-30 05:41:30','2026-05-05 19:19:20',0,NULL,1,'$2y$12$B0xdAV.dvD.lI8jJ/upBmukeXJI5abIFVAEjzWkG4wm7gPNJ5faQm',NULL,'2025-08-30 05:39:53','2026-05-05 19:19:20'),(4,'bob@hemit.fr','2025-09-08 19:49:44','2026-04-23 19:15:36',0,NULL,1,'$2y$12$ElVqHb2K9fW.Vu3ghkF6yO8yalGdG4wG4zal5lJEOqHObWH6nFDuK',NULL,'2025-09-08 19:37:08','2026-04-23 19:15:36'),(5,'carol@hemit.fr',NULL,NULL,0,NULL,1,'$2y$12$SfyV5ElsS5UCTdNnKh1ZsO.jMe9LDXPbbCYWOeUCoqPiVhbQCwtn.',NULL,'2025-09-08 20:31:43','2025-09-08 20:31:43'),(6,'daniel@hemit.fr','2025-09-12 15:06:05','2025-12-05 10:40:02',0,NULL,1,'$2y$12$KBcqvMWrUJmwXVXfbvbrD.vvlZ9DZ1YcHdfSI0/G5zmtUHFd8z00K','NGwYCgGK5JlWRgSlKGW0cCM2kSIRS3lJ8P9aZc0zxNbVzpKHIzXGsYDGJfvV','2025-09-12 15:01:38','2025-12-05 10:40:02'),(7,'elliot@hemit.fr',NULL,NULL,0,NULL,1,'$2y$12$EJBWGd43JdY19meaDrodi.7xkQwP7wPajiFvW5PIoGdmsoA88zNsO',NULL,'2025-09-14 06:43:35','2025-09-14 06:43:35'),(8,'harold@hemit.fr','2025-12-03 19:32:58','2025-12-03 19:32:24',0,NULL,1,'$2y$12$nGuK4tKp/vp81v3W/F2H1ebpxFEQ71.QBTcmhxJcg4EprGfkNMzQ2',NULL,'2025-12-03 19:32:24','2025-12-03 19:32:58'),(9,'isabella@hemit.fr','2025-12-04 09:05:00','2025-12-04 09:04:17',0,NULL,1,'$2y$12$YCt.ZeJmMrXk..ClNW61hOeHdpAF.5cXdF5T4SVzwNkCPc5qKl0j.',NULL,'2025-12-04 09:04:17','2025-12-04 09:05:00');
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

-- Dump completed on 2026-05-05 19:24:01
