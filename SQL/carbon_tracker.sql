-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1
-- 產生時間： 2025-06-08 19:31:40
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
  `id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `remember_me` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `personal_page`
--

DROP TABLE IF EXISTS `personal_page`;
CREATE TABLE `personal_page` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL COMMENT '關聯的使用者名稱',
  `bio` text DEFAULT NULL COMMENT '個人簡介',
  `country_code` nvarchar(10) DEFAULT NULL COMMENT '國家名稱',
  `city` nvarchar(10) DEFAULT NULL COMMENT '城市',
  `gender` enum('男','女','其他') DEFAULT NULL COMMENT '性別',
  `birthdate` date DEFAULT NULL COMMENT '生日',
  `activity_level` enum('低','中','高') DEFAULT NULL COMMENT '活躍程度',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '建立時間',
  `last_update` datetime DEFAULT NULL COMMENT '上次更新時間'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `personal_page`
--

INSERT INTO `personal_page` (`id`, `username`, `bio`, `country_code`, `city`, `gender`, `birthdate`, `activity_level`, `created_at`, `last_update`) VALUES
(1, 'aaa', NULL, NULL, NULL, NULL, NULL, NULL, '2025-06-09 01:31:02', NULL);

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
(4, '環保袋', 50, '一個可重複使用的環保袋，減少塑膠袋使用'),
(5, '腳踏車租借券', 100, '免費租借腳踏車一天，享受低碳出行'),
(6, '綠色生活套裝', 200, '包含環保吸管、餐具和水壺的套裝');

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
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `total_points` int(11) DEFAULT 0,
  `total_footprint` float DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `total_points`, `total_footprint`) VALUES
(1, 'aaa', '$2y$10$Ri0qfQjiidUYdLiOFzfV9.aYtuz4Zj.uzTKjsl/U7Resj0rltUu5i', 0, 0);

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `auth_tokens`
--
ALTER TABLE `auth_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`);

--
-- 資料表索引 `personal_page`
--
ALTER TABLE `personal_page`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--
ALTER TABLE `travel_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

ALTER TABLE `redeem_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `reward_id` (`reward_id`);
--
-- 使用資料表自動遞增(AUTO_INCREMENT) `auth_tokens`
--
ALTER TABLE `rewards`
  ADD PRIMARY KEY (`reward_id`);
--
-- 使用資料表自動遞增(AUTO_INCREMENT) `personal_page`
--
ALTER TABLE `personal_page`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 已傾印資料表的限制式
--
ALTER TABLE `travel_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- 資料表的限制式 `auth_tokens`
--


/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
