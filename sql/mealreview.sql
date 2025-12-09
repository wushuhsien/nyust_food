-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1
-- 產生時間： 2025-12-09 04:46:01
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
-- 資料庫： `nyust_food`
--

-- --------------------------------------------------------

--
-- 資料表結構 `mealreview`
--

CREATE TABLE `mealreview` (
  `mealreview_id` int(10) NOT NULL,
  `description` varchar(100) DEFAULT NULL,
  `time` datetime NOT NULL,
  `rate` int(1) NOT NULL CHECK (`rate` between 1 and 5),
  `order_id` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `mealreview`
--

INSERT INTO `mealreview` (`mealreview_id`, `description`, `time`, `rate`, `order_id`) VALUES
(5, '超好吃~', '2025-11-30 03:26:36', 5, 11),
(6, '還行', '2025-12-07 19:17:48', 3, 14);

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `mealreview`
--
ALTER TABLE `mealreview`
  ADD PRIMARY KEY (`mealreview_id`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `mealreview`
--
ALTER TABLE `mealreview`
  MODIFY `mealreview_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
