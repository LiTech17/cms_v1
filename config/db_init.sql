SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ngo_cms`
--

-- --------------------------------------------------------
-- 1. SAFETY: DROP TABLES IF THEY EXIST AND DISABLE FOREIGN KEY CHECKS
-- --------------------------------------------------------
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `about_page`;
DROP TABLE IF EXISTS `admins`;
DROP TABLE IF EXISTS `donation_methods`;
DROP TABLE IF EXISTS `home_hero`;
DROP TABLE IF EXISTS `key_programs`;
DROP TABLE IF EXISTS `leadership_team`;
DROP TABLE IF EXISTS `operational_bases`;
DROP TABLE IF EXISTS `organization_info`;
DROP TABLE IF EXISTS `programme_media`;
DROP TABLE IF EXISTS `programme_statistics`;
DROP TABLE IF EXISTS `programme_team`;
DROP TABLE IF EXISTS `programme_updates`;
DROP TABLE IF EXISTS `programmes`;
DROP TABLE IF EXISTS `site_settings`;

-- --------------------------------------------------------
-- 2. TABLE STRUCTURES AND CREATE STATEMENTS
-- --------------------------------------------------------

CREATE TABLE `about_page` (
  `id` int(11) NOT NULL,
  `hero_image` varchar(255) DEFAULT NULL,
  `establishment_story` text DEFAULT NULL,
  `mission` text DEFAULT NULL,
  `vision` text DEFAULT NULL,
  `year_established` varchar(4) DEFAULT NULL,
  `lives_impacted` varchar(50) DEFAULT NULL,
  `communities_served` varchar(50) DEFAULT NULL,
  `active_programs` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `core_values` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `donation_methods` (
  `id` int(11) NOT NULL,
  `method_name` varchar(100) NOT NULL,
  `account_name` varchar(255) DEFAULT NULL,
  `account_number` varchar(100) DEFAULT NULL,
  `merchant_code` varchar(100) DEFAULT NULL,
  `qr_image` varchar(255) DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `home_hero` (
  `id` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `caption` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `display_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `key_programs` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `focus_area` varchar(255) DEFAULT NULL,
  `background_image` varchar(255) DEFAULT NULL,
  `impact_description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `leadership_team` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `qualification` varchar(255) DEFAULT NULL,
  `background` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `operational_bases` (
  `id` int(11) NOT NULL,
  `location_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `organization_info` (
  `id` int(11) NOT NULL,
  `goals` text DEFAULT NULL,
  `vision` text DEFAULT NULL,
  `mission` text DEFAULT NULL,
  `geographical_location` text DEFAULT NULL,
  `objectives` text DEFAULT NULL,
  `core_values` text DEFAULT NULL,
  `history` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `programmes` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `introduction` text DEFAULT NULL,
  `objectives` text DEFAULT NULL,
  `key_achievements` text DEFAULT NULL,
  `target_beneficiaries` varchar(255) DEFAULT NULL,
  `duration` varchar(100) DEFAULT NULL,
  `status` enum('active','completed','upcoming') DEFAULT 'active',
  `budget` decimal(12,2) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `partner_organizations` text DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `programme_media` (
  `id` int(11) NOT NULL,
  `programme_id` int(11) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `programme_statistics` (
  `id` int(11) NOT NULL,
  `programme_id` int(11) NOT NULL,
  `statistic_name` varchar(100) NOT NULL,
  `statistic_value` varchar(100) NOT NULL,
  `statistic_icon` varchar(50) DEFAULT 'fas fa-chart-line',
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `programme_team` (
  `id` int(11) NOT NULL,
  `programme_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `programme_updates` (
  `id` int(11) NOT NULL,
  `programme_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `update_date` date NOT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL,
  `site_title` varchar(150) DEFAULT NULL,
  `tagline` varchar(255) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `favicon` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `physical_address` text DEFAULT NULL,
  `postal_address` text DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `twitter` varchar(255) DEFAULT NULL,
  `linkedin` varchar(255) DEFAULT NULL,
  `youtube` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 3. DUMPING DATA FOR TABLES
-- --------------------------------------------------------

INSERT INTO `about_page` (`id`, `hero_image`, `establishment_story`, `mission`, `vision`, `year_established`, `lives_impacted`, `communities_served`, `active_programs`, `created_at`, `updated_at`, `core_values`) VALUES
(1, '1762348475_Gemini_Generated_Image_8al8ym8al8ym8al8.png', 'Our organization waEstablished in November 2004, CAPDIMW (Caring for Persons with Disabilities in Malawi) is a legally registered, inclusive non-governmental organization. Our core philosophy is promoting equality and full participation for marginalized community members. We are committed to being run by both persons with and without disabilities, ensuring our efforts are grounded in lived experience and mutual support.s established to advance inclusion and development in marginalized communities.', 'To serve humanityTo systematically promote inclusiveness among socially excluded groups to enhance full involvement at all levels of decision-making process.', 'To create a free socially excluded living environment to advance sustainable citizen rights.', NULL, NULL, NULL, NULL, '2025-11-05 13:06:15', '2025-11-06 13:26:02', NULL);

INSERT INTO `admins` (`id`, `username`, `password`, `created_at`) VALUES
(1, 'admin', '*01A6717B58FF5C7EAFFF6CB7C96F7428EA65FE4C', '2025-11-05 12:20:28');

INSERT INTO `donation_methods` (`id`, `method_name`, `account_name`, `account_number`, `merchant_code`, `qr_image`, `instructions`, `created_at`) VALUES
(1, 'Airtel Money', 'Capdimw_Airtel_Inlet', '', '154200', NULL, 'You can send money directly to our Airtel Money Account by withdrawing to the Merchant Code', '2025-11-05 13:40:48');

INSERT INTO `home_hero` (`id`, `image`, `caption`, `created_at`, `display_order`) VALUES
(6, '1762433799_IMG_20200615_103046_7.jpg', 'Welcome to Caring for people with disabilities', '2025-11-06 12:56:39', 0),
(7, '1762433893_IMG_20210628_101744_2.jpg', 'Learn How to make footwear at CAPDI', '2025-11-06 12:58:13', 0);

INSERT INTO `leadership_team` (`id`, `name`, `position`, `qualification`, `background`, `image`, `created_at`) VALUES
(2, 'Mr. Dalitso Banda', 'Secretary', 'Masters', NULL, '1762348841_Gemini_Generated_Image_s97h8bs97h8bs97h (1).png', '2025-11-05 13:20:41'),
(3, 'Dr. Alinane Mwale', 'Director', 'Phd', NULL, '1762348902_Gemini_Generated_Image_s97h8bs97h8bs97h.png', '2025-11-05 13:21:42'),
(4, 'Ms. Chimwemwe Phiri', 'Team Lead, Economic Empowerment Programs', 'Bachelors', NULL, '1762348961_Gemini_Generated_Image_s97h8bs97h8bs97h (2).png', '2025-11-05 13:22:41');

INSERT INTO `operational_bases` (`id`, `location_name`, `description`, `created_at`) VALUES
(1, 'Bangwe', 'The organization operates its secretariat in Bangwe Township, Blantyre district, and maintains a branch in Bangula, Nsanje district.', '2025-11-05 13:15:23');

INSERT INTO `organization_info` (`id`, `goals`, `vision`, `mission`, `geographical_location`, `objectives`, `core_values`, `history`, `updated_at`) VALUES
(1, 'Empower communities', 'To create a free socially excluded living environment to advance sustainable citizen rights.', 'To serve humanityTo systematically promote inclusiveness among socially excluded groups to enhance full involvement at all levels of decision-making process.', 'The organization has secretariat in Bangwe Township-Blantyre district, along Mvula road in old Samaritan Trust campus. Its branch is located at Bangula in Nsanje district.', 'Economic Empowerment Programmes\r\nHIV and AIDS and disability management\r\nEnvironment\r\nHuman right and gender', '', '', '2025-11-06 08:46:06');

INSERT INTO `programmes` (`id`, `title`, `introduction`, `objectives`, `key_achievements`, `target_beneficiaries`, `duration`, `status`, `budget`, `location`, `partner_organizations`, `is_featured`, `created_at`, `updated_at`) VALUES
(1, 'Braille Production', 'Inclusive HIV/AIDS Education\r\nImplemented with funding from the U.S. Embassy through PEPFAR, this project transcribes critical HIV/AIDS information into braille booklets to bridge information gaps for marginalized youth with and without disabilities.\r\n\r\nKey Objectives\r\nTranscribe HIV/AIDS educational materials into braille.\r\nPromote accessibility for visually impaired youth.\r\nSupport UNAIDS 95-95-95 strategic goals.', 'Provide inclusive HIV/AIDS education.\nProduce Braille materials for visually impaired.\nPromote health awareness in disabled communities.', 'Distributed 5000+ Braille educational materials.\nReached 1500+ people with HIV/AIDS awareness.', 'Visually impaired individuals and general community', '2 years', 'active', 120000.00, 'National coverage', NULL, 0, '2025-11-05 13:31:10', '2025-11-06 11:00:20'),
(2, 'COVID-19 Community Response', 'Empowering Communities Amid COVID-19\r\nFunded by the U.S. Embassy through PAS, this initiative trains relatives of persons with disabilities in mask-making, soap production, and sign language communication. It reached over 9,000 vulnerable people in Bangwe communities.\r\n\r\nKey Objectives\r\nTrain community members in COVID-19 preventive measures.\r\nProvide livelihood skills to vulnerable groups.\r\nEnhance disability-inclusive community support.', 'Distribute COVID-19 prevention supplies.\nProvide emergency relief to vulnerable families.\nConduct community awareness campaigns.', 'Distributed 5000+ vaccine doses.\nProvided relief to 1200+ families.\nConducted 50+ awareness sessions.', 'Vulnerable communities affected by COVID-19', '18 months', 'active', 200000.00, 'Urban and rural communities', 'UNDP, Ministry of Health', 0, '2025-11-05 13:32:43', '2025-11-06 11:00:20'),
(3, 'Flower, Shoes & Vases Production', 'conomic Empowerment of Women\r\nWith funding from USAID through World Connect Malawi, 50 women and girls were trained in production of shoes and flower vases, enabling them to earn sustainable livelihoods.\r\n\r\nKey Objectives\r\nTrain women and girls in craft production.\r\nSupport market-ready product creation.\r\nEmpower women economically.', 'Train women in flower, shoes, and vase production.\nCreate sustainable income opportunities.\nDevelop market linkages for products.', 'Trained 250+ women in production skills.\nEstablished 45+ small businesses.\nGenerated $50,000+ in sales.', 'Women entrepreneurs and artisans', 'Ongoing', 'active', 150000.00, 'Artisan communities', 'U.S. Embassy, Women Development Centers', 1, '2025-11-05 13:33:38', '2025-11-06 11:00:20'),
(4, 'Soap Making Training', 'Community Hygiene & Safety\r\nSupported by the U.S. Embassy and CDC guidance, this project trained communities in soap and mask production, improving hygiene and COVID-19 preparedness for 9,000 marginalized individuals.\r\n\r\nKey Objectives\r\nTrain communities in soap and mask production.\r\nImprove hygiene practices.\r\nReduce COVID-19 transmission risk.', 'Train community members in soap production.\nPromote hygiene and sanitation.\nCreate local business opportunities.', 'Trained 300+ people in soap making.\nProduced 10,000+ soap units.\nEstablished 15 small enterprises.', 'Community members interested in small business', '1 year', 'active', 60000.00, 'Local communities', 'U.S. Embassy, Community Development Committees', 0, '2025-11-05 13:34:16', '2025-11-06 11:00:20'),
(5, 'Flower Vase Making', 'Artisan Skills for Women\r\nThis initiative trains women in the production of flower vases, promoting entrepreneurship and sustainable income generation.\r\n\r\nKey Objectives\r\nTeach women vase-making skills.\r\nEnable micro-business creation.\r\nSupport local economic empowerment.', 'Teach women vase-making skills.\nEnable micro-business creation.\nSupport local economic empowerment.\nPromote sustainable livelihoods.', 'Trained 180+ women in artisan skills.\nHelped establish 25 small businesses.\nGenerated $35,000+ in community income.', 'Women from low-income households', 'Ongoing', 'active', 75000.00, 'Multiple communities in the region', 'U.S. Embassy, Local Women Associations', 1, '2025-11-05 13:35:17', '2025-11-06 11:00:20'),
(6, 'Digital Literacy for Rural Youth', 'Empowering young people in rural communities with essential digital skills for the 21st century. This initiative provides comprehensive computer training, internet literacy, and technology access to bridge the digital divide and create economic opportunities.', 'Provide basic computer skills training\r\nTeach internet safety and digital citizenship\r\nDevelop job-ready digital competencies\r\nPromote STEM education in rural areas\r\nSupport entrepreneurship through technology', 'Trained 850+ youth in digital skills\r\nEstablished 5 computer labs in rural schools\r\nProvided 300+ tablets to underserved communities\r\nConnected 15 villages to internet services\r\nHelped 120 youth secure tech-related jobs', 'Youth aged 15-25 from rural communities', '3 years', 'active', 150000.00, 'Rural communities across the region', 'Microsoft Foundation, Local Education Department, Community Development Centers', 1, '2025-11-06 11:26:08', '2025-11-06 11:26:08');

INSERT INTO `programme_media` (`id`, `programme_id`, `file_name`, `file_type`, `uploaded_at`) VALUES
(1, 5, '1762349775_IMG_20190917_084256_9.jpg', 'image/jpeg', '2025-11-05 13:36:15'),
(2, 5, '1762349785_IMG_20190928_105839_2.jpg', 'image/jpeg', '2025-11-05 13:36:25'),
(3, 6, '1762428517_Gemini_Generated_Image_9kl03l9kl03l9kl0.png', 'image/png', '2025-11-06 11:28:37');

INSERT INTO `programme_statistics` (`id`, `programme_id`, `statistic_name`, `statistic_value`, `statistic_icon`, `display_order`, `created_at`, `updated_at`) VALUES
(1, 1, 'Lives Impacted', '1500+', 'fas fa-users', 1, '2025-11-06 11:00:20', '2025-11-06 11:00:20'),
(2, 1, 'Communities Served', '12+', 'fas fa-map-marker-alt', 2, '2025-11-06 11:00:20', '2025-11-06 11:00:20'),
(3, 1, 'Success Rate', '95%', 'fas fa-chart-line', 3, '2025-11-06 11:00:20', '2025-11-06 11:00:20'),
(4, 1, 'Ongoing Projects', '24+', 'fas fa-tasks', 4, '2025-11-06 11:00:20', '2025-11-06 11:00:20'),
(5, 2, 'Lives Impacted', '1200+', 'fas fa-users', 1, '2025-11-06 11:00:20', '2025-11-06 11:00:20'),
(6, 2, 'Communities Served', '8+', 'fas fa-map-marker-alt', 2, '2025-11-06 11:00:20', '2025-11-06 11:00:20'),
(7, 2, 'Vaccines Distributed', '5000+', 'fas fa-syringe', 3, '2025-11-06 11:00:20', '2025-11-06 11:00:20'),
(8, 3, 'Women Trained', '250+', 'fas fa-female', 1, '2025-11-06 11:00:20', '2025-11-06 11:00:20'),
(9, 3, 'Businesses Started', '45+', 'fas fa-store', 2, '2025-11-06 11:00:20', '2025-11-06 11:00:20'),
(10, 3, 'Income Generated', '$50,000+', 'fas fa-dollar-sign', 3, '2025-11-06 11:00:20', '2025-11-06 11:00:20'),
(11, 4, 'People Trained', '300+', 'fas fa-graduation-cap', 1, '2025-11-06 11:00:20', '2025-11-06 11:00:20'),
(12, 4, 'Soap Units Produced', '10,000+', 'fas fa-pump-soap', 2, '2025-11-06 11:00:20', '2025-11-06 11:00:20'),
(13, 5, 'Women Empowered', '180+', 'fas fa-female', 1, '2025-11-06 11:00:20', '2025-11-06 11:00:20'),
(14, 5, 'Products Created', '2000+', 'fas fa-palette', 2, '2025-11-06 11:00:20', '2025-11-06 11:00:20'),
(15, 5, 'Income Generated', '$35,000+', 'fas fa-dollar-sign', 3, '2025-11-06 11:00:20', '2025-11-06 11:00:20'),
(16, 6, 'Lives Impacted', '200+', 'fas fa-users', 1, '2025-11-06 11:26:08', '2025-11-06 11:26:08'),
(17, 6, 'Communities Served', '4', 'fas fa-map-marker-alt', 2, '2025-11-06 11:26:08', '2025-11-06 11:26:08'),
(18, 6, 'Success Rate', '95%', 'fas fa-chart-line', 3, '2025-11-06 11:26:08', '2025-11-06 11:26:08');

INSERT INTO `site_settings` (`id`, `site_title`, `tagline`, `logo`, `favicon`, `phone`, `email`, `physical_address`, `postal_address`, `facebook`, `twitter`, `linkedin`, `youtube`, `updated_at`) VALUES
(1, 'CAPDIMW', 'Promoting inclusiveness among socially excluded groups for a better future.', '1762346705_logo.png', '1762346705_favicon-32x32.png', '+265 888 654 243', 'info@capdi.mw', 'Bangwe Township, Mvula road, old Samaritan Trust campus, Blantyre, Malawi\r\n\r\n+265 888 654 243', 'P. O. BOX 55645', 'https://facebook.com/capdimw', 'https://twitter.com/capdimw', 'https://linkdn.com/capdimw', 'https://youtube.com/capdimw', '2025-11-06 09:41:08');

-- --------------------------------------------------------
-- 4. PRIMARY KEYS, UNIQUE CONSTRAINTS, AND AUTO_INCREMENTS
-- --------------------------------------------------------

ALTER TABLE `about_page` ADD PRIMARY KEY (`id`);
ALTER TABLE `admins` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `username` (`username`);
ALTER TABLE `donation_methods` ADD PRIMARY KEY (`id`);
ALTER TABLE `home_hero` ADD PRIMARY KEY (`id`);
ALTER TABLE `key_programs` ADD PRIMARY KEY (`id`);
ALTER TABLE `leadership_team` ADD PRIMARY KEY (`id`);
ALTER TABLE `operational_bases` ADD PRIMARY KEY (`id`);
ALTER TABLE `organization_info` ADD PRIMARY KEY (`id`);
ALTER TABLE `programmes` ADD PRIMARY KEY (`id`);
ALTER TABLE `programme_media` ADD PRIMARY KEY (`id`), ADD KEY `programme_id` (`programme_id`);
ALTER TABLE `programme_statistics` ADD PRIMARY KEY (`id`), ADD KEY `programme_id` (`programme_id`);
ALTER TABLE `programme_team` ADD PRIMARY KEY (`id`), ADD KEY `programme_id` (`programme_id`);
ALTER TABLE `programme_updates` ADD PRIMARY KEY (`id`), ADD KEY `programme_id` (`programme_id`);
ALTER TABLE `site_settings` ADD PRIMARY KEY (`id`);

ALTER TABLE `about_page` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `admins` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `donation_methods` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `home_hero` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
ALTER TABLE `key_programs` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `leadership_team` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
ALTER TABLE `operational_bases` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `organization_info` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `programmes` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
ALTER TABLE `programme_media` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
ALTER TABLE `programme_statistics` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;
ALTER TABLE `programme_team` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `programme_updates` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `site_settings` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

-- --------------------------------------------------------
-- 5. FOREIGN KEY CONSTRAINTS
-- --------------------------------------------------------

ALTER TABLE `programme_media` ADD CONSTRAINT `programme_media_ibfk_1` FOREIGN KEY (`programme_id`) REFERENCES `programmes` (`id`) ON DELETE CASCADE;
ALTER TABLE `programme_statistics` ADD CONSTRAINT `programme_statistics_ibfk_1` FOREIGN KEY (`programme_id`) REFERENCES `programmes` (`id`) ON DELETE CASCADE;
ALTER TABLE `programme_team` ADD CONSTRAINT `programme_team_ibfk_1` FOREIGN KEY (`programme_id`) REFERENCES `programmes` (`id`) ON DELETE CASCADE;
ALTER TABLE `programme_updates` ADD CONSTRAINT `programme_updates_ibfk_1` FOREIGN KEY (`programme_id`) REFERENCES `programmes` (`id`) ON DELETE CASCADE;

-- --------------------------------------------------------
-- 6. SAFETY: RE-ENABLE FOREIGN KEY CHECKS
-- --------------------------------------------------------
SET FOREIGN_KEY_CHECKS = 1;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;