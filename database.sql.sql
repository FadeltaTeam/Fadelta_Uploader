
--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int NOT NULL,
  `user_id` bigint NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `upload_state` varchar(50) DEFAULT NULL,
  `current_file` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `user_id`, `username`, `full_name`, `upload_state`, `current_file`, `created_at`) VALUES
(1, 1853272172, 'admin1', 'Admin One', NULL, NULL, '2025-08-20 20:24:09'),
(2, 987654321, 'admin2', 'Admin Two', NULL, NULL, '2025-08-20 20:24:09');

-- --------------------------------------------------------

--
-- Table structure for table `auto_delete_settings`
--

CREATE TABLE `auto_delete_settings` (
  `id` int NOT NULL,
  `user_id` bigint NOT NULL,
  `delete_after` int DEFAULT NULL COMMENT 'زمان حذف به ثانیه (NULL به معنی غیرفعال)',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- --------------------------------------------------------

--
-- Table structure for table `channels`
--

CREATE TABLE `channels` (
  `id` int NOT NULL,
  `channel_id` bigint NOT NULL,
  `channel_username` varchar(255) DEFAULT NULL,
  `channel_title` varchar(255) DEFAULT NULL,
  `is_required` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `channels`
--

INSERT INTO `channels` (`id`, `channel_id`, `channel_username`, `channel_title`, `is_required`) VALUES
(1, -1001226659198, '@fadelta_source', 'کانال اصلی', 1);

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

CREATE TABLE `files` (
  `id` int NOT NULL,
  `file_id` varchar(255) NOT NULL,
  `file_unique_id` varchar(255) NOT NULL,
  `type` enum('photo','video','document','audio','voice') NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_size` bigint DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `caption` text,
  `uploaded_by` bigint NOT NULL,
  `upload_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `download_count` int DEFAULT '0',
  `average_rating` decimal(3,2) DEFAULT '0.00',
  `total_reviews` int DEFAULT '0',
  `total_likes` int DEFAULT '0',
  `sent_message_id` bigint DEFAULT NULL,
  `delete_after` int DEFAULT NULL COMMENT 'زمان حذف به ثانیه'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `files_backup`
--

CREATE TABLE `files_backup` (
  `id` int NOT NULL,
  `file_id` varchar(255) NOT NULL,
  `file_unique_id` varchar(255) NOT NULL,
  `type` enum('photo','video','document','audio','voice') NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_size` bigint DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `caption` text,
  `uploaded_by` bigint NOT NULL,
  `upload_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `download_count` int DEFAULT '0',
  `average_rating` decimal(3,2) DEFAULT '0.00',
  `total_reviews` int DEFAULT '0',
  `total_likes` int DEFAULT '0',
  `sent_message_id` bigint DEFAULT NULL,
  `delete_after` int DEFAULT NULL COMMENT 'زمان حذف به ثانیه'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int NOT NULL,
  `user_id` bigint NOT NULL,
  `file_id` int NOT NULL,
  `rating` tinyint NOT NULL,
  `comment` text,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `review_likes`
--

CREATE TABLE `review_likes` (
  `id` int NOT NULL,
  `review_id` int NOT NULL,
  `user_id` bigint NOT NULL,
  `is_like` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `user_id` bigint NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `joined_channel` tinyint(1) DEFAULT '0',
  `joined_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_active` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_library`
--

CREATE TABLE `user_library` (
  `id` int NOT NULL,
  `user_id` bigint NOT NULL,
  `file_id` int NOT NULL,
  `downloaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `download_count` int DEFAULT '1',
  `last_downloaded` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_states`
--

CREATE TABLE `user_states` (
  `user_id` bigint NOT NULL,
  `upload_state` varchar(50) DEFAULT NULL,
  `current_file` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `auto_delete_settings`
--
ALTER TABLE `auto_delete_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `file_unique_id` (`file_unique_id`);
