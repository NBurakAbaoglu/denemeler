-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 18 Eyl 2025, 14:15:24
-- Sunucu sürümü: 10.4.32-MariaDB
-- PHP Sürümü: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `kurumsal`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `event_name` varchar(255) NOT NULL,
  `event_date` date NOT NULL,
  `end_date` date NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `teacher_training` varchar(255) DEFAULT NULL,
  `lesson_id` int(11) DEFAULT NULL,
  `course_title` varchar(255) DEFAULT NULL,
  `capacity` int(11) NOT NULL DEFAULT 0,
  `enrolled_count` int(11) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive','cancelled','completed') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `events`
--

INSERT INTO `events` (`id`, `event_name`, `event_date`, `end_date`, `teacher_id`, `teacher_training`, `lesson_id`, `course_title`, `capacity`, `enrolled_count`, `description`, `status`, `created_at`, `updated_at`) VALUES
(7, 'deneme2', '2025-09-18', '2025-09-19', 3, '', 1, 'DERS2', 5, 0, '', 'active', '2025-09-17 08:10:41', '2025-09-17 08:10:41'),
(8, 'deneme5', '2025-09-19', '2025-09-20', 9, '', 2, 'DERS3', 10, 0, '', 'active', '2025-09-18 12:09:06', '2025-09-18 12:09:06');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `multi_skills`
--

CREATE TABLE `multi_skills` (
  `id` int(11) NOT NULL,
  `person_id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `skill_id` int(11) NOT NULL,
  `skill_name` varchar(255) NOT NULL,
  `source_organization_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `organizations`
--

CREATE TABLE `organizations` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `column_position` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `organizations`
--

INSERT INTO `organizations` (`id`, `name`, `column_position`, `created_at`, `updated_at`) VALUES
(1, 'deneme1', 4, '2025-09-17 05:51:59', '2025-09-17 05:51:59'),
(2, 'deneme2', 5, '2025-09-17 05:53:44', '2025-09-17 05:53:44'),
(5, 'deneme3', 6, '2025-09-17 06:16:00', '2025-09-17 06:16:00'),
(6, 'deneme4', 7, '2025-09-17 10:57:07', '2025-09-17 10:57:07'),
(7, 'deneme5', 8, '2025-09-18 04:39:25', '2025-09-18 04:39:25');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `organization_images`
--

CREATE TABLE `organization_images` (
  `id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `row_name` varchar(255) DEFAULT NULL,
  `image_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `organization_images`
--

INSERT INTO `organization_images` (`id`, `organization_id`, `row_name`, `image_name`, `created_at`, `updated_at`) VALUES
(1, 1, 'HAKAN KOR', 'pie (3).png', '2025-09-17 05:53:02', '2025-09-18 12:01:21'),
(2, 2, 'HAKAN KOR', 'pie (2).png', '2025-09-17 05:59:37', '2025-09-18 12:01:21'),
(3, 1, '', 'pie (3).png', '2025-09-17 06:00:31', '2025-09-18 12:01:21'),
(4, 1, 'HAKAN FİDAN', 'pie (3).png', '2025-09-17 06:14:01', '2025-09-18 04:38:19'),
(5, 2, 'HAKAN FİDAN', 'pie (2).png', '2025-09-17 06:16:00', '2025-09-18 04:38:19'),
(6, 5, 'HAKAN KOR', 'pie (2).png', '2025-09-17 10:57:07', '2025-09-18 12:01:21'),
(7, 5, 'HAKAN FİDAN', 'pie (2).png', '2025-09-17 10:57:07', '2025-09-18 04:38:19'),
(8, 6, 'HAKAN KOR', 'pie (2).png', '2025-09-18 04:39:25', '2025-09-18 12:01:21'),
(9, 6, 'HAKAN FİDAN', 'pie (2).png', '2025-09-18 04:39:25', '2025-09-18 04:39:25'),
(10, 2, '', 'pie (2).png', '2025-09-18 04:39:39', '2025-09-18 12:01:21'),
(11, 1, 'ALEYNA TİLKİ', 'pie (3).png', '2025-09-18 04:42:21', '2025-09-18 12:01:21'),
(12, 2, 'ALEYNA TİLKİ', 'pie (2).png', '2025-09-18 04:42:21', '2025-09-18 12:01:21'),
(13, 5, 'ALEYNA TİLKİ', 'pie (2).png', '2025-09-18 04:42:21', '2025-09-18 12:01:21'),
(14, 6, 'ALEYNA TİLKİ', 'pie (2).png', '2025-09-18 04:42:21', '2025-09-18 12:01:21');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `organization_skills`
--

CREATE TABLE `organization_skills` (
  `id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `skill_id` int(11) NOT NULL,
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `organization_skills`
--

INSERT INTO `organization_skills` (`id`, `organization_id`, `skill_id`, `priority`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'medium', '2025-09-17 05:52:15', '2025-09-17 05:52:15'),
(2, 2, 1, 'medium', '2025-09-17 05:53:52', '2025-09-17 05:53:52'),
(3, 1, 2, 'medium', '2025-09-17 06:14:19', '2025-09-17 06:14:19'),
(4, 5, 3, 'medium', '2025-09-17 06:16:05', '2025-09-17 06:16:05'),
(5, 6, 4, 'medium', '2025-09-17 10:57:17', '2025-09-17 10:57:17'),
(6, 7, 2, 'medium', '2025-09-18 04:39:34', '2025-09-18 04:39:34');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `organization_skills_backup`
--

CREATE TABLE `organization_skills_backup` (
  `id` int(11) NOT NULL DEFAULT 0,
  `organization_id` int(11) NOT NULL,
  `skill_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `skill_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `persons`
--

CREATE TABLE `persons` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `registration_no` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `persons`
--

INSERT INTO `persons` (`id`, `name`, `company_name`, `title`, `registration_no`, `created_at`, `updated_at`) VALUES
(1, 'HAKAN KOR', 'ASELSAN', 'PERSONEL', '504657', '2025-09-17 05:52:53', '2025-09-18 04:54:50'),
(2, 'ALEYNA TİLKİ', NULL, NULL, NULL, '2025-09-17 06:13:09', '2025-09-18 04:42:13');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `person_organization_images`
--

CREATE TABLE `person_organization_images` (
  `id` int(11) NOT NULL,
  `person_id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `image_name` varchar(255) NOT NULL DEFAULT 'pie (2).png',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `planlandi`
--

CREATE TABLE `planlandi` (
  `id` int(11) NOT NULL,
  `person_id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `skill_id` int(11) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `event_id` int(11) DEFAULT NULL,
  `target_level` int(11) DEFAULT 3,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('planned','in_progress','completed','cancelled') DEFAULT 'planned',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `notes` text DEFAULT NULL,
  `created_by` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `success_status` enum('pending','completed') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `planlandi`
--

INSERT INTO `planlandi` (`id`, `person_id`, `organization_id`, `skill_id`, `teacher_id`, `event_id`, `target_level`, `start_date`, `end_date`, `status`, `priority`, `notes`, `created_by`, `created_at`, `updated_at`, `success_status`) VALUES
(7, 1, 2, 2, NULL, NULL, 3, NULL, NULL, '', 'medium', NULL, NULL, '2025-09-18 05:45:49', '2025-09-18 11:52:40', 'completed'),
(20, 1, 1, 1, 3, NULL, 1, '2025-09-18', '2025-09-18', '', 'low', '', '1', '2025-09-18 08:29:50', '2025-09-18 11:52:40', 'completed');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `planned_skills`
--

CREATE TABLE `planned_skills` (
  `id` int(11) NOT NULL,
  `person_id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `skill_id` int(11) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `registration_no` varchar(100) DEFAULT NULL,
  `target_level` int(11) DEFAULT 3,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('istek_gonderildi','planlandi','tamamlandi','iptal') DEFAULT 'istek_gonderildi',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `planned_skills`
--

INSERT INTO `planned_skills` (`id`, `person_id`, `organization_id`, `skill_id`, `company_name`, `title`, `registration_no`, `target_level`, `start_date`, `end_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, NULL, NULL, NULL, 3, NULL, NULL, 'tamamlandi', '2025-09-17 06:04:51', '2025-09-18 11:52:40'),
(2, 2, 1, 3, NULL, NULL, NULL, 3, NULL, NULL, 'tamamlandi', '2025-09-17 06:14:34', '2025-09-17 06:15:50'),
(3, 2, 5, 4, NULL, NULL, NULL, 3, NULL, NULL, 'istek_gonderildi', '2025-09-17 06:16:47', '2025-09-17 06:16:47'),
(4, 1, 5, 4, NULL, NULL, NULL, 3, NULL, NULL, 'istek_gonderildi', '2025-09-17 11:10:03', '2025-09-17 11:10:03'),
(5, 1, 6, 5, NULL, NULL, NULL, 3, NULL, NULL, 'istek_gonderildi', '2025-09-17 11:12:20', '2025-09-17 11:12:20'),
(6, 2, 6, 5, NULL, NULL, NULL, 3, NULL, NULL, 'istek_gonderildi', '2025-09-18 12:01:38', '2025-09-18 12:01:38');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `skills`
--

CREATE TABLE `skills` (
  `id` int(11) NOT NULL,
  `skill_name` varchar(255) NOT NULL,
  `skill_description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `skills`
--

INSERT INTO `skills` (`id`, `skill_name`, `skill_description`, `category`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'DERS2', 'ders1', 'Temel Beceri', 1, '2025-09-17 05:52:15', '2025-09-17 05:53:57'),
(2, 'DERS3', 'ders3', 'Temel Beceri', 1, '2025-09-17 06:14:19', '2025-09-17 06:14:19'),
(3, 'DERS4', 'ders4', 'Temel Beceri', 1, '2025-09-17 06:16:05', '2025-09-17 06:16:05'),
(4, 'MURAT', 'murat', 'Temel Beceri', 1, '2025-09-17 10:57:17', '2025-09-17 10:57:17');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `specialization` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `teachers`
--

INSERT INTO `teachers` (`id`, `first_name`, `last_name`, `specialization`, `created_at`, `updated_at`) VALUES
(2, 'fatih', 'bey', 'Kalite Kontrol', '2025-09-17 06:04:29', '2025-09-17 06:04:29'),
(3, 'eda', 'hanım', 'Yazılım geliştirme', '2025-09-17 06:14:59', '2025-09-17 06:14:59'),
(4, 'yıldız', 'tilbe', 'dertli başım', '2025-09-17 06:15:26', '2025-09-17 06:15:26'),
(5, 'müslüm', 'gürses', 'sen ağlama', '2025-09-17 06:16:39', '2025-09-17 06:16:39'),
(6, 'hüseyin', 'yılmaz', 'proje yönetimi', '2025-09-17 11:46:49', '2025-09-17 11:46:49'),
(7, 'SABRİ', 'GÜNVER', 'KONTROL', '2025-09-18 12:02:10', '2025-09-18 12:02:10'),
(8, 'ŞÜKRÜ', 'SARAÇOĞLU', 'FUTBOL', '2025-09-18 12:03:04', '2025-09-18 12:03:04'),
(9, 'ALİ SAMİ', 'YEN', 'Futbol', '2025-09-18 12:03:41', '2025-09-18 12:03:41');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `tep_teachers`
--

CREATE TABLE `tep_teachers` (
  `id` int(11) NOT NULL,
  `person_name` varchar(255) NOT NULL,
  `organization_name` varchar(255) NOT NULL,
  `skill_name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `tep_teachers`
--

INSERT INTO `tep_teachers` (`id`, `person_name`, `organization_name`, `skill_name`, `created_at`, `updated_at`) VALUES
(2, 'fatih bey', 'deneme1', 'DERS2', '2025-09-17 06:04:29', '2025-09-17 06:04:29'),
(3, 'eda hanım', 'deneme2', 'DERS2', '2025-09-17 06:14:59', '2025-09-17 06:14:59'),
(4, 'yıldız tilbe', 'deneme1', 'DERS3', '2025-09-17 06:15:26', '2025-09-17 06:15:26'),
(5, 'yıldız tilbe', 'deneme1', 'DERS2', '2025-09-17 06:15:26', '2025-09-17 06:15:26'),
(6, 'müslüm gürses', 'deneme3', 'DERS4', '2025-09-17 06:16:39', '2025-09-17 06:16:39'),
(7, 'hüseyin yılmaz', 'deneme4', 'MURAT', '2025-09-17 11:46:49', '2025-09-17 11:46:49'),
(8, 'SABRİ GÜNVER', 'deneme4', 'MURAT', '2025-09-18 12:02:10', '2025-09-18 12:02:10'),
(9, 'ŞÜKRÜ SARAÇOĞLU', 'deneme5', 'DERS3', '2025-09-18 12:03:04', '2025-09-18 12:03:04'),
(10, 'ŞÜKRÜ SARAÇOĞLU', 'deneme5', 'DERS3', '2025-09-18 12:03:04', '2025-09-18 12:03:04'),
(11, 'ALİ SAMİ YEN', 'deneme4', 'DERS4', '2025-09-18 12:03:41', '2025-09-18 12:03:41'),
(12, 'ALİ SAMİ YEN', 'deneme4', 'MURAT', '2025-09-18 12:03:41', '2025-09-18 12:03:41');

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_event_date` (`event_date`),
  ADD KEY `idx_teacher_id` (`teacher_id`),
  ADD KEY `idx_lesson_id` (`lesson_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_events_dates` (`event_date`,`end_date`);

--
-- Tablo için indeksler `multi_skills`
--
ALTER TABLE `multi_skills`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_person_org_skill` (`person_id`,`organization_id`,`skill_id`),
  ADD KEY `idx_person_id` (`person_id`),
  ADD KEY `idx_organization_id` (`organization_id`),
  ADD KEY `idx_skill_id` (`skill_id`);

--
-- Tablo için indeksler `organizations`
--
ALTER TABLE `organizations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `idx_column_position` (`column_position`),
  ADD KEY `idx_organizations_name` (`name`);

--
-- Tablo için indeksler `organization_images`
--
ALTER TABLE `organization_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_organization_id` (`organization_id`);

--
-- Tablo için indeksler `organization_skills`
--
ALTER TABLE `organization_skills`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_org_skill` (`organization_id`,`skill_id`),
  ADD KEY `idx_organization_id` (`organization_id`),
  ADD KEY `idx_skill_id` (`skill_id`),
  ADD KEY `idx_priority` (`priority`);

--
-- Tablo için indeksler `persons`
--
ALTER TABLE `persons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_name` (`name`),
  ADD KEY `idx_registration_no` (`registration_no`),
  ADD KEY `idx_persons_company` (`company_name`);

--
-- Tablo için indeksler `person_organization_images`
--
ALTER TABLE `person_organization_images`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_person_org_image` (`person_id`,`organization_id`),
  ADD KEY `idx_person_id` (`person_id`),
  ADD KEY `idx_organization_id` (`organization_id`);

--
-- Tablo için indeksler `planlandi`
--
ALTER TABLE `planlandi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_person_id` (`person_id`),
  ADD KEY `idx_organization_id` (`organization_id`),
  ADD KEY `idx_skill_id` (`skill_id`),
  ADD KEY `idx_teacher_id` (`teacher_id`),
  ADD KEY `idx_event_id` (`event_id`),
  ADD KEY `idx_status` (`status`);

--
-- Tablo için indeksler `planned_skills`
--
ALTER TABLE `planned_skills`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_person_org_skill` (`person_id`,`organization_id`,`skill_id`),
  ADD KEY `idx_person_id` (`person_id`),
  ADD KEY `idx_organization_id` (`organization_id`),
  ADD KEY `idx_skill_id` (`skill_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_planned_skills_dates` (`start_date`,`end_date`);

--
-- Tablo için indeksler `skills`
--
ALTER TABLE `skills`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_skill_name` (`skill_name`),
  ADD KEY `idx_skill_name` (`skill_name`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Tablo için indeksler `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_name` (`first_name`,`last_name`);

--
-- Tablo için indeksler `tep_teachers`
--
ALTER TABLE `tep_teachers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_person_name` (`person_name`),
  ADD KEY `idx_organization_name` (`organization_name`),
  ADD KEY `idx_skill_name` (`skill_name`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Tablo için AUTO_INCREMENT değeri `multi_skills`
--
ALTER TABLE `multi_skills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `organizations`
--
ALTER TABLE `organizations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Tablo için AUTO_INCREMENT değeri `organization_images`
--
ALTER TABLE `organization_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Tablo için AUTO_INCREMENT değeri `organization_skills`
--
ALTER TABLE `organization_skills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Tablo için AUTO_INCREMENT değeri `persons`
--
ALTER TABLE `persons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `person_organization_images`
--
ALTER TABLE `person_organization_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `planlandi`
--
ALTER TABLE `planlandi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Tablo için AUTO_INCREMENT değeri `planned_skills`
--
ALTER TABLE `planned_skills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Tablo için AUTO_INCREMENT değeri `skills`
--
ALTER TABLE `skills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Tablo için AUTO_INCREMENT değeri `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Tablo için AUTO_INCREMENT değeri `tep_teachers`
--
ALTER TABLE `tep_teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_2` FOREIGN KEY (`lesson_id`) REFERENCES `organization_skills` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_events_teacher_id_tep` FOREIGN KEY (`teacher_id`) REFERENCES `tep_teachers` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `organization_images`
--
ALTER TABLE `organization_images`
  ADD CONSTRAINT `organization_images_ibfk_1` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `organization_skills`
--
ALTER TABLE `organization_skills`
  ADD CONSTRAINT `organization_skills_ibfk_1` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `organization_skills_ibfk_2` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `person_organization_images`
--
ALTER TABLE `person_organization_images`
  ADD CONSTRAINT `person_organization_images_ibfk_1` FOREIGN KEY (`person_id`) REFERENCES `persons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `person_organization_images_ibfk_2` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `planlandi`
--
ALTER TABLE `planlandi`
  ADD CONSTRAINT `planlandi_ibfk_1` FOREIGN KEY (`person_id`) REFERENCES `persons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `planlandi_ibfk_3` FOREIGN KEY (`skill_id`) REFERENCES `organization_skills` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `planlandi_ibfk_5` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `planned_skills`
--
ALTER TABLE `planned_skills`
  ADD CONSTRAINT `planned_skills_ibfk_1` FOREIGN KEY (`person_id`) REFERENCES `persons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `planned_skills_ibfk_2` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `planned_skills_ibfk_3` FOREIGN KEY (`skill_id`) REFERENCES `organization_skills` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
