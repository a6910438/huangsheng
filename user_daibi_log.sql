-- phpMyAdmin SQL Dump
-- version 4.4.15.10
-- https://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: 2019-06-27 12:00:34
-- 服务器版本： 5.7.19-log
-- PHP Version: 7.1.5

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tessss`
--

-- --------------------------------------------------------

--
-- 表的结构 `user_daibi_log`
--

CREATE TABLE IF NOT EXISTS `user_daibi_log` (
  `id` int(11) unsigned NOT NULL COMMENT '主键',
  `user_id` int(11) NOT NULL COMMENT '用户id',
  `magic` decimal(20,8) NOT NULL COMMENT '变化的金额',
  `old` decimal(20,8) NOT NULL COMMENT '原来的金额',
  `new` decimal(20,8) NOT NULL COMMENT '变化后的金额',
  `eth_address` varchar(250) NOT NULL COMMENT '地址',
  `remark` varchar(255) NOT NULL COMMENT '备注',
  `hash` varchar(250) DEFAULT NULL COMMENT '交易hash',
  `types` int(1) NOT NULL DEFAULT '2' COMMENT '2：钱包代币充值',
  `timeStamp` int(11) DEFAULT '0' COMMENT '区块到账时间',
  `blockNumber` int(11) NOT NULL DEFAULT '0',
  `create_time` int(11) NOT NULL COMMENT '创建时间'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `user_daibi_log`
--
ALTER TABLE `user_daibi_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `type` (`types`),
  ADD KEY `orderid` (`hash`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `user_daibi_log`
--
ALTER TABLE `user_daibi_log`
  MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键';
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
