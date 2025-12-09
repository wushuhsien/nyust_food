-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1
-- 產生時間： 2025-12-09 04:46:06
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
-- 資料表結構 `mealreviewreply`
--

CREATE TABLE `mealreviewreply` (
  `mealreviewreply_id` int(10) NOT NULL,
  `description` varchar(100) DEFAULT NULL,
  `time` datetime NOT NULL,
  `account` varchar(20) NOT NULL,
  `mealreview_id` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `mealreviewreply`
--

INSERT INTO `mealreviewreply` (`mealreviewreply_id`, `description`, `time`, `account`, `mealreview_id`) VALUES
(6, '謝謝惠顧，歡迎下次光臨', '2025-12-09 11:35:47', 'store001', 5);

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `mealreviewreply`
--
ALTER TABLE `mealreviewreply`
  ADD PRIMARY KEY (`mealreviewreply_id`),
  ADD KEY `account` (`account`),
  ADD KEY `order_id` (`mealreview_id`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `mealreviewreply`
--
ALTER TABLE `mealreviewreply`
  MODIFY `mealreviewreply_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- 已傾印資料表的限制式
--

--
-- 資料表的限制式 `mealreviewreply`
--
ALTER TABLE `mealreviewreply`
  ADD CONSTRAINT `mealreviewreply_ibfk_1` FOREIGN KEY (`account`) REFERENCES `account` (`account`),
  ADD CONSTRAINT `mealreviewreply_ibfk_2` FOREIGN KEY (`mealreview_id`) REFERENCES `mealreview` (`mealreview_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
