-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1
<<<<<<< HEAD
-- 產生時間： 2025-06-15 09:09:57
=======
-- 產生時間： 2025-06-14 20:13:48
>>>>>>> dda7b7d75ef57f91dc78ca81a964087745e8af6a
-- 伺服器版本： 10.4.32-MariaDB
-- PHP 版本： 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 資料庫： `carbon_tracker`
--
CREATE DATABASE IF NOT EXISTS `carbon_tracker` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `carbon_tracker`;

-- --------------------------------------------------------

--
-- 資料表結構 `auth_tokens`
--

DROP TABLE IF EXISTS `auth_tokens`;
CREATE TABLE `auth_tokens` (
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `remember_me` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `friends`
--

DROP TABLE IF EXISTS `friends`;
CREATE TABLE `friends` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `friend_id` int(11) NOT NULL,
  `status` enum('pending','accepted','rejected') DEFAULT 'pending' COMMENT '好友狀態：待接受、已接受、已拒絕',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '建立時間',
  `initiator_id` int(11) NOT NULL COMMENT '發起請求的用戶 ID'
) ;

-- --------------------------------------------------------

--
-- 資料表結構 `personal_page`
--

DROP TABLE IF EXISTS `personal_page`;
CREATE TABLE `personal_page` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL COMMENT '關聯的使用者名稱',
  `bio` text DEFAULT NULL COMMENT '個人簡介',
  `country_code` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL COMMENT '國家名稱',
  `city` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL COMMENT '城市',
  `gender` enum('男','女','其他') DEFAULT NULL COMMENT '性別',
  `birthdate` date DEFAULT NULL COMMENT '生日',
  `activity_level` enum('低','中','高') DEFAULT NULL COMMENT '活躍程度',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '建立時間',
  `last_update` datetime DEFAULT NULL COMMENT '上次更新時間'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `personal_page`
--

INSERT INTO `personal_page` (`user_id`, `username`, `bio`, `country_code`, `city`, `gender`, `birthdate`, `activity_level`, `created_at`, `last_update`) VALUES
(1, 'aaa', NULL, NULL, NULL, NULL, NULL, NULL, '2025-06-09 01:31:02', NULL),
(2, 'bbb', NULL, NULL, NULL, NULL, NULL, NULL, '2025-06-09 01:31:03', NULL),
(3, 'ccc', NULL, NULL, NULL, NULL, NULL, NULL, '2025-06-09 01:31:04', NULL),
(4, 'ddd', NULL, NULL, NULL, NULL, NULL, NULL, '2025-06-09 01:31:05', NULL),
(5, 'eee', NULL, NULL, NULL, NULL, NULL, NULL, '2025-06-09 01:31:06', NULL),
(6, 'fff', NULL, NULL, NULL, NULL, NULL, NULL, '2025-06-09 01:31:07', NULL),
(7, 'ggg', NULL, NULL, NULL, NULL, NULL, NULL, '2025-06-09 01:31:08', NULL),
(8, 'hhh', NULL, NULL, NULL, NULL, NULL, NULL, '2025-06-09 01:31:09', NULL),
(9, 'iii', NULL, NULL, NULL, NULL, NULL, NULL, '2025-06-09 01:31:10', NULL),
(10, 'jjj', NULL, NULL, NULL, NULL, NULL, NULL, '2025-06-09 01:31:11', NULL),
(11, '123', NULL, NULL, NULL, NULL, NULL, NULL, '2025-06-15 00:49:11', NULL);

-- --------------------------------------------------------

--
-- 資料表結構 `redeem_history`
--

DROP TABLE IF EXISTS `redeem_history`;
CREATE TABLE `redeem_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reward_id` int(11) NOT NULL,
  `reward_name` varchar(255) NOT NULL,
  `points_used` int(11) NOT NULL,
  `redeem_time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `rewards`
--

DROP TABLE IF EXISTS `rewards`;
CREATE TABLE `rewards` (
  `reward_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `points_required` int(11) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `rewards`
--

INSERT INTO `rewards` (`reward_id`, `name`, `points_required`, `description`) VALUES
(1, '環保袋', 50, '一個可重複使用的環保袋，減少塑膠袋使用'),
(2, '腳踏車租借券', 100, '免費租借腳踏車一天，享受低碳出行'),
(3, '綠色生活套裝', 200, '包含環保吸管、餐具和水壺的套裝'),
(4, '樹木種植券', 300, '一張種植樹木的券，為地球增添綠意'),
(5, '可持續旅遊指南', 150, '一本介紹可持續旅遊的電子書'),
(6, '碳足跡計算器', 80, '一個幫助你計算日常活動碳足跡的應用程式'),
(7, '環保手冊', 120, '一本介紹如何在日常生活中實踐環保的手冊'),
(8, '綠色餐廳優惠券', 70, '可在指定綠色餐廳使用的優惠券'),
(9, '可持續時尚折扣券', 90, '在可持續時尚品牌購物時使用的折扣券'),
(10, '生態旅遊體驗券', 250, '一張生態旅遊體驗券，享受自然之美'),
(11, '環保清潔產品套裝', 180, '一套環保清潔產品，安全無害'),
(12, '綠色科技產品折扣券', 110, '在綠色科技產品購物時使用的折扣券'),
(13, '可持續農業體驗券', 220, '參加可持續農業體驗活動的券'),
(14, '環保藝術品', 400, '一件由回收材料製作的藝術品'),
(15, '綠色出行指南', 130, '一本介紹低碳出行方式的指南'),
(16, '環保教育課程', 300, '參加線上環保教育課程的券'),
(17, '可持續生活工作坊', 350, '參加可持續生活工作坊的券'),
(18, '綠色科技產品', 500, '一件最新的綠色科技產品'),
(19, '環保旅行套裝', 600, '包含環保旅行用品的套裝'),
(20, '碳中和計劃參與券', 700, '參與碳中和計劃的券'),
(21, '綠色社區活動參與券', 800, '參加當地綠色社區活動的券'),
(22, '可持續生活指南', 900, '一本介紹如何實踐可持續生活的指南'),
(23, '環保科技產品', 1000, '一件最新的環保科技產品'),
(24, '綠色出行體驗券', 1100, '體驗綠色出行方式的券'),
(25, '碳足跡減少計劃參與券', 1200, '參與碳足跡減少計劃的券');

-- --------------------------------------------------------

--
-- 資料表結構 `travel_records`
--

DROP TABLE IF EXISTS `travel_records`;
CREATE TABLE `travel_records` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `transport` varchar(50) NOT NULL,
  `distance` float NOT NULL,
  `footprint` float NOT NULL,
  `points` int(11) NOT NULL DEFAULT 0,
  `record_time` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`) VALUES
(1, 'aaa', '$2y$10$Ri0qfQjiidUYdLiOFzfV9.aYtuz4Zj.uzTKjsl/U7Resj0rltUu5i'),
(2, 'bbb', '$2y$10$Ri0qfQjiidUYdLiOFzfV9.aYtuz4Zj.uzTKjsl/U7Resj0rltUu5i'),
(3, 'ccc', '$2y$10$Ri0qfQjiidUYdLiOFzfV9.aYtuz4Zj.uzTKjsl/U7Resj0rltUu5i'),
(4, 'ddd', '$2y$10$Ri0qfQjiidUYdLiOFzfV9.aYtuz4Zj.uzTKjsl/U7Resj0rltUu5i'),
(5, 'eee', '$2y$10$Ri0qfQjiidUYdLiOFzfV9.aYtuz4Zj.uzTKjsl/U7Resj0rltUu5i'),
(6, 'fff', '$2y$10$Ri0qfQjiidUYdLiOFzfV9.aYtuz4Zj.uzTKjsl/U7Resj0rltUu5i'),
(7, 'ggg', '$2y$10$Ri0qfQjiidUYdLiOFzfV9.aYtuz4Zj.uzTKjsl/U7Resj0rltUu5i'),
(8, 'hhh', '$2y$10$Ri0qfQjiidUYdLiOFzfV9.aYtuz4Zj.uzTKjsl/U7Resj0rltUu5i'),
(9, 'iii', '$2y$10$Ri0qfQjiidUYdLiOFzfV9.aYtuz4Zj.uzTKjsl/U7Resj0rltUu5i'),
(10, 'jjj', '$2y$10$Ri0qfQjiidUYdLiOFzfV9.aYtuz4Zj.uzTKjsl/U7Resj0rltUu5i'),
(11, '123', '$2y$10$Z5mh3wcm0SrIFKx4r3z9KeMfA1peZdXKboKR6nuIxiP1s6GnVo2Ly');

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `auth_tokens`
--
ALTER TABLE `auth_tokens`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `token` (`token`);

--
-- 資料表索引 `friends`
--
ALTER TABLE `friends`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_friendship` (`user_id`,`friend_id`),
  ADD KEY `friend_id` (`friend_id`),
  ADD KEY `initiator_id` (`initiator_id`);

--
-- 資料表索引 `personal_page`
--
ALTER TABLE `personal_page`
  ADD PRIMARY KEY (`user_id`);

--
-- 資料表索引 `redeem_history`
--
ALTER TABLE `redeem_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `reward_id` (`reward_id`);

--
-- 資料表索引 `rewards`
--
ALTER TABLE `rewards`
  ADD PRIMARY KEY (`reward_id`);

--
-- 資料表索引 `travel_records`
--
ALTER TABLE `travel_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- 資料表索引 `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `friends`
--
ALTER TABLE `friends`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `personal_page`
--
ALTER TABLE `personal_page`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `redeem_history`
--
ALTER TABLE `redeem_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `travel_records`
--
ALTER TABLE `travel_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- 已傾印資料表的限制式
--

--
-- 資料表的限制式 `friends`
--
ALTER TABLE `friends`
  ADD CONSTRAINT `friends_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `friends_ibfk_2` FOREIGN KEY (`friend_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `friends_ibfk_3` FOREIGN KEY (`initiator_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
