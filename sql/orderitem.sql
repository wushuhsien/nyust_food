-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1
-- 產生時間： 2025-12-09 04:46:19
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
-- 資料表結構 `orderitem`
--

CREATE TABLE `orderitem` (
  `orderitem_id` int(10) NOT NULL,
  `quantity` int(10) NOT NULL,
  `note` varchar(100) DEFAULT NULL,
  `menu_id` int(10) NOT NULL,
  `order_id` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `orderitem`
--

INSERT INTO `orderitem` (`orderitem_id`, `quantity`, `note`, `menu_id`, `order_id`) VALUES
(19, 1, '', 12, 11),
(20, 1, '', 16, 11),
(21, 1, '', 17, 12),
(22, 2, '', 22, 12),
(23, 1, '', 17, 13),
(24, 1, '', 21, 14),
(25, 1, '', 17, 15),
(26, 2, '', 12, 16),
(27, 1, '', 14, 16),
(28, 1, '', 17, 17),
(29, 1, '', 21, 17),
(30, 1, '', 22, 17),
(31, 2, '', 12, 18),
(32, 1, '', 15, 18),
(33, 2, '', 16, 18),
(34, 1, '', 12, 19),
(35, 1, '', 14, 19),
(36, 1, '', 15, 19),
(37, 1, '', 16, 19),
(38, 2, '', 12, 20);

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `orderitem`
--
ALTER TABLE `orderitem`
  ADD PRIMARY KEY (`orderitem_id`),
  ADD KEY `menu_id` (`menu_id`),
  ADD KEY `order_id` (`order_id`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `orderitem`
--
ALTER TABLE `orderitem`
  MODIFY `orderitem_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- 已傾印資料表的限制式
--

--
-- 資料表的限制式 `orderitem`
--
ALTER TABLE `orderitem`
  ADD CONSTRAINT `orderitem_ibfk_1` FOREIGN KEY (`menu_id`) REFERENCES `menu` (`menu_id`),
  ADD CONSTRAINT `orderitem_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `order` (`order_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
