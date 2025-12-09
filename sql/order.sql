-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1
-- 產生時間： 2025-12-09 04:46:15
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
-- 資料表結構 `order`
--

CREATE TABLE `order` (
  `order_id` int(10) NOT NULL,
  `estimate_time` datetime DEFAULT NULL,
  `pick_time` datetime DEFAULT NULL,
  `status` varchar(100) NOT NULL,
  `note` varchar(100) DEFAULT NULL,
  `account` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `order`
--

INSERT INTO `order` (`order_id`, `estimate_time`, `pick_time`, `status`, `note`, `account`) VALUES
(11, '2025-11-30 03:45:00', '2025-11-30 03:25:10', '已取餐', '多醬', 'stu001'),
(12, '2025-11-30 03:46:00', NULL, '等待店家接單', '少冰', 'stu001'),
(13, '2025-12-02 21:13:00', NULL, '等待店家接單', '', 'stu001'),
(14, '2025-12-07 18:27:00', '2025-12-07 18:53:30', '已取餐', '', 'stu001'),
(15, '2025-12-09 14:09:00', NULL, '餐點製作中', '完全去冰', 'stu001'),
(16, '2025-12-09 12:19:00', NULL, '等待店家接單', '', 'stu001'),
(17, '2025-12-09 16:27:00', '2025-12-09 11:21:04', '已取餐', '', 'stu002'),
(18, '2025-12-09 12:37:00', NULL, '餐點製作中', '', 'stu002'),
(19, '2025-12-09 12:40:00', NULL, '等待取餐', '', 'stu003'),
(20, '2025-12-09 13:39:00', '2025-12-09 11:20:05', '已取餐', '', 'stu003');

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `order`
--
ALTER TABLE `order`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `account` (`account`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `order`
--
ALTER TABLE `order`
  MODIFY `order_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- 已傾印資料表的限制式
--

--
-- 資料表的限制式 `order`
--
ALTER TABLE `order`
  ADD CONSTRAINT `order_ibfk_1` FOREIGN KEY (`account`) REFERENCES `account` (`account`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
