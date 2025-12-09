-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1
-- 產生時間： 2025-12-09 04:46:10
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
-- 資料表結構 `menu`
--

CREATE TABLE `menu` (
  `menu_id` int(10) NOT NULL,
  `type` varchar(10) NOT NULL,
  `name` varchar(10) NOT NULL,
  `description` varchar(50) DEFAULT NULL,
  `price` int(10) NOT NULL,
  `stock` int(10) DEFAULT 0,
  `sale_amount` int(10) DEFAULT 0,
  `note` varchar(100) DEFAULT NULL,
  `cook_time` time NOT NULL,
  `account` varchar(20) NOT NULL,
  `img_id` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `menu`
--

INSERT INTO `menu` (`menu_id`, `type`, `name`, `description`, `price`, `stock`, `sale_amount`, `note`, `cook_time`, `account`, `img_id`) VALUES
(12, '法國吐司', '有喜原味', '含蛋奶素', 40, 21, 9, '', '00:15:00', 'store001', '692b3ff4a5b28ecf4307f40a'),
(13, '法國吐司', '美味果醬', '請選擇巧克力、草莓、藍莓、奶油、奶酥、花生', 45, 0, 0, '', '00:10:00', 'store001', '692b3ff4a5b28ecf4307f40b'),
(14, '蛋餅', '切達起司', '雙蛋', 35, 8, 2, '', '00:15:00', 'store001', NULL),
(15, '蛋餅', '嫩煎雞腿', '辣味', 70, 18, 2, '', '00:20:00', 'store001', '692b4157a5b28ecf4307f40d'),
(16, '蛋餅', '香脆薯餅', '', 55, 20, 5, '', '00:20:00', 'store001', '692b4157a5b28ecf4307f40e'),
(17, '嚴選茗品', '玉露青茶', '人氣推薦', 35, 15, 5, '', '00:05:00', 'store002', '692b42f6a5b28ecf4307f411'),
(18, '嚴選茗品', '桂花青茶', '人氣推薦', 35, 0, 0, '', '00:05:00', 'store002', '692b42f6a5b28ecf4307f412'),
(19, '嚴選茗品', '炭燒青茶', '', 35, 23, 0, '', '00:05:00', 'store002', '692b42f6a5b28ecf4307f413'),
(20, '香醇濃郁', '玉露奶青', '人氣推薦', 55, 0, 0, '本產品含有鮮奶', '00:10:00', 'store002', '692b445aa5b28ecf4307f41b'),
(21, '香醇濃郁', '熟成蕎麥奶茶', '', 55, 18, 2, '', '00:10:00', 'store002', '692b440fa5b28ecf4307f415'),
(22, '香醇濃郁', '烤糖蕎麥凍奶青', '人氣推薦', 70, 17, 8, '本產品含有鮮奶', '00:10:00', 'store002', '692b440fa5b28ecf4307f416');

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`menu_id`),
  ADD KEY `account` (`account`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `menu`
--
ALTER TABLE `menu`
  MODIFY `menu_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- 已傾印資料表的限制式
--

--
-- 資料表的限制式 `menu`
--
ALTER TABLE `menu`
  ADD CONSTRAINT `menu_ibfk_1` FOREIGN KEY (`account`) REFERENCES `account` (`account`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
