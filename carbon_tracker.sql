-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1
-- 產生時間： 2025-05-05 18:06:01
-- 伺服器版本： 10.4.27-MariaDB
-- PHP 版本： 8.2.0

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
-- 資料表結構 `redeem_history`
--

CREATE TABLE `redeem_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reward_id` int(11) NOT NULL,
  `reward_name` varchar(255) NOT NULL,
  `points_used` int(11) NOT NULL,
  `redeem_time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `redeem_history`
--

INSERT INTO `redeem_history` (`id`, `user_id`, `reward_id`, `reward_name`, `points_used`, `redeem_time`) VALUES
(1, 1, 2, '腳踏車租借券', 100, '2025-05-04 16:02:21');

-- --------------------------------------------------------

--
-- 資料表結構 `rewards`
--

CREATE TABLE `rewards` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `points_required` int(11) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `rewards`
--

INSERT INTO `rewards` (`id`, `name`, `points_required`, `description`) VALUES
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

CREATE TABLE `travel_records` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `transport` varchar(50) NOT NULL,
  `distance` float NOT NULL,
  `footprint` float NOT NULL,
  `points` int(11) NOT NULL DEFAULT 0,
  `record_time` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `travel_records`
--

INSERT INTO `travel_records` (`id`, `user_id`, `transport`, `distance`, `footprint`, `points`, `record_time`) VALUES
(1, 1, '腳踏車', 179.32, 0, 179, '2025-05-04 15:59:31'),
(2, 1, '步行', 5.702, 0, 11, '2025-05-04 16:00:05');

-- --------------------------------------------------------

--
-- 資料表結構 `users`
--

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
(1, 'aaa', '$2y$10$gZayMX6V2SX5oFJh7BCyC.WVNflyi5b/NQkQATWvQ6HJSDBNzvzPi', 90, 0.68493);

--
-- 已傾印資料表的索引
--

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
  ADD PRIMARY KEY (`id`);

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
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `redeem_history`
--
ALTER TABLE `redeem_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `rewards`
--
ALTER TABLE `rewards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `travel_records`
--
ALTER TABLE `travel_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 已傾印資料表的限制式
--

--
-- 資料表的限制式 `redeem_history`
--
ALTER TABLE `redeem_history`
  ADD CONSTRAINT `redeem_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `redeem_history_ibfk_2` FOREIGN KEY (`reward_id`) REFERENCES `rewards` (`id`);

--
-- 資料表的限制式 `travel_records`
--
ALTER TABLE `travel_records`
  ADD CONSTRAINT `travel_records_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
