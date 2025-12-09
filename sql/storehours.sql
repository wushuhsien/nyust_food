-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1
-- 產生時間： 2025-12-09 04:46:35
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
-- 資料表結構 `storehours`
--

CREATE TABLE `storehours` (
  `storehours_id` int(10) NOT NULL,
  `weekday` tinyint(4) NOT NULL,
  `open_time` time NOT NULL,
  `close_time` time NOT NULL,
  `account` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `storehours`
--

INSERT INTO `storehours` (`storehours_id`, `weekday`, `open_time`, `close_time`, `account`) VALUES
(151, 1, '10:30:00', '00:00:00', 'store003'),
(152, 2, '10:30:00', '20:00:00', 'store003'),
(153, 4, '10:30:00', '20:00:00', 'store003'),
(154, 5, '10:30:00', '20:00:00', 'store003'),
(166, 1, '10:00:00', '22:00:00', 'store002'),
(167, 2, '10:00:00', '22:00:00', 'store002'),
(168, 3, '10:00:00', '22:00:00', 'store002'),
(169, 4, '10:00:00', '22:00:00', 'store002'),
(170, 5, '00:38:06', '16:49:06', 'store002'),
(171, 5, '10:00:00', '22:00:00', 'store002'),
(172, 6, '10:00:00', '22:00:00', 'store002'),
(173, 7, '03:30:00', '22:00:00', 'store002'),
(174, 1, '06:00:00', '14:00:00', 'store001'),
(175, 1, '10:30:00', '23:34:00', 'store001'),
(176, 2, '06:00:00', '14:00:00', 'store001'),
(177, 3, '06:00:00', '14:00:00', 'store001'),
(178, 3, '10:30:00', '20:00:00', 'store001'),
(179, 4, '06:00:00', '14:00:00', 'store001'),
(180, 5, '06:00:00', '14:00:00', 'store001'),
(181, 6, '06:00:00', '14:00:00', 'store001'),
(182, 6, '10:30:00', '20:00:00', 'store001'),
(183, 7, '03:00:00', '14:00:00', 'store001');

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `storehours`
--
ALTER TABLE `storehours`
  ADD PRIMARY KEY (`storehours_id`),
  ADD KEY `account` (`account`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `storehours`
--
ALTER TABLE `storehours`
  MODIFY `storehours_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=184;

--
-- 已傾印資料表的限制式
--

--
-- 資料表的限制式 `storehours`
--
ALTER TABLE `storehours`
  ADD CONSTRAINT `storehours_ibfk_1` FOREIGN KEY (`account`) REFERENCES `account` (`account`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
