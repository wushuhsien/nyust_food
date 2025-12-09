-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1
-- 產生時間： 2025-12-09 04:45:40
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
-- 資料表結構 `account`
--

CREATE TABLE `account` (
  `account` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_time` datetime NOT NULL DEFAULT current_timestamp(),
  `role` int(1) NOT NULL DEFAULT 0,
  `permission` int(1) NOT NULL DEFAULT 0,
  `stop_reason` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `account`
--

INSERT INTO `account` (`account`, `password`, `created_time`, `role`, `permission`, `stop_reason`) VALUES
('admin', '$2y$10$osxwmLY.BAvpygzWbi73C.A.RbDfZP269vTVpZK3.RvDZFkxwlaDa', '2025-11-30 02:15:15', 2, 0, NULL),
('store001', '$2y$10$9c9XNQkxMdW6.IWQbj/rPuJ1UXI/Hnu3XT7vTICvP45V0kLHCCvUC', '2025-11-30 02:29:29', 1, 0, NULL),
('store002', '$2y$10$3NWAbO27hcNcLtXmd2CJHOjMVgc6HWgrqE29bHDmyWfusr2YqIGWu', '2025-11-30 02:32:55', 1, 0, NULL),
('store003', '$2y$10$TIOp0rO3yZVNEXYzGAcAfOUbdiEdbPy/8uhb.2in7okDxhLVlm0wK', '2025-11-30 02:34:23', 3, 0, NULL),
('stu001', '$2y$10$gZxUTbtRFoeYy/ev4Fc4LexOmHFFr3HrRt11udf.t/rMp1J0NxR3i', '2025-11-30 03:20:35', 0, 0, NULL),
('stu002', '$2y$10$2lcUd4pvBU.gytFJ9wDc9e2dAIIoTQcMd/H6CGFMkP.pT/spSkEJq', '2025-11-30 02:22:06', 0, 0, NULL),
('stu003', '$2y$10$W.SHjVhgNAn7XgnE9GhTSu8jaTlgEbXECyEaFzQUEk3rVdjNP/Vpu', '2025-12-06 01:33:39', 0, 1, '多次未取餐');

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `account`
--
ALTER TABLE `account`
  ADD PRIMARY KEY (`account`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
