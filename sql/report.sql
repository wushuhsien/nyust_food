-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1
-- 產生時間： 2025-12-09 04:46:25
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
-- 資料表結構 `report`
--

CREATE TABLE `report` (
  `report_id` int(10) NOT NULL,
  `description` varchar(100) NOT NULL,
  `time` datetime NOT NULL,
  `type` varchar(100) NOT NULL,
  `status` varchar(100) DEFAULT '待處理',
  `account_student` varchar(20) DEFAULT NULL,
  `account_store` varchar(20) DEFAULT NULL,
  `img_id` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `report`
--

INSERT INTO `report` (`report_id`, `description`, `time`, `type`, `status`, `account_student`, `account_store`, `img_id`) VALUES
(31, '系統壞掉了', '2025-12-09 11:02:15', '系統問題', '已完成', 'store001', NULL, '69379137349daf271d03abc8'),
(32, '有蟲啊!!!!', '2025-12-09 11:08:01', '投訴店家', '已完成', 'stu001', 'store001', '69379291349daf271d03abcd'),
(33, '系統異常', '2025-12-09 11:10:16', '系統問題', '未處理', 'stu001', NULL, '69379318349daf271d03abce'),
(34, '突然跳出這個', '2025-12-09 11:15:04', '系統問題', '處理中', 'stu002', NULL, '69379438349daf271d03abcf'),
(35, '食物不乾淨', '2025-12-09 11:17:06', '投訴店家', '處理中', 'stu003', 'store001', '693794b2349daf271d03abd0'),
(36, '食物不衛生', '2025-12-09 11:19:52', '投訴店家', '未處理', 'stu002', 'store001', '69379558349daf271d03abd1');

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `report`
--
ALTER TABLE `report`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `account_student` (`account_student`),
  ADD KEY `account_store` (`account_store`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `report`
--
ALTER TABLE `report`
  MODIFY `report_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- 已傾印資料表的限制式
--

--
-- 資料表的限制式 `report`
--
ALTER TABLE `report`
  ADD CONSTRAINT `report_ibfk_1` FOREIGN KEY (`account_student`) REFERENCES `account` (`account`),
  ADD CONSTRAINT `report_ibfk_2` FOREIGN KEY (`account_store`) REFERENCES `account` (`account`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
