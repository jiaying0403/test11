/*
 Navicat Premium Data Transfer

 Source Server         : localhost
 Source Server Type    : MySQL
 Source Server Version : 50553
 Source Host           : localhost:3306
 Source Schema         : test

 Target Server Type    : MySQL
 Target Server Version : 50553
 File Encoding         : 65001

 Date: 28/05/2019 01:10:47
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for yy_buycard_record
-- ----------------------------
DROP TABLE IF EXISTS `yy_buycard_record`;
CREATE TABLE `yy_buycard_record`  (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `pid` int(8) DEFAULT NULL,
  `sid` int(8) DEFAULT NULL,
  `sname` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `cardId` int(1) DEFAULT NULL,
  `num` int(5) DEFAULT NULL,
  `money` decimal(6, 2) DEFAULT NULL,
  `email` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '',
  `status` int(1) DEFAULT 0 COMMENT '0未发货,1已经发货',
  `authorid` int(8) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 74 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for yy_card_record
-- ----------------------------
DROP TABLE IF EXISTS `yy_card_record`;
CREATE TABLE `yy_card_record`  (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `sid` int(8) DEFAULT NULL,
  `sname` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `authorid` int(8) DEFAULT NULL,
  `card_no` varchar(36) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `type` int(1) DEFAULT NULL,
  `cardValue` int(8) DEFAULT NULL,
  `user_account` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `depositTime` int(10) DEFAULT NULL,
  `beforTime` int(10) DEFAULT NULL,
  `ofterTime` int(10) DEFAULT NULL,
  `beforPoint` int(10) DEFAULT NULL,
  `ofterPoint` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 73 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for yy_card_type
-- ----------------------------
DROP TABLE IF EXISTS `yy_card_type`;
CREATE TABLE `yy_card_type`  (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `sid` int(8) DEFAULT NULL,
  `sname` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `authorid` int(8) DEFAULT NULL,
  `type` int(1) DEFAULT NULL,
  `cardValue` int(8) DEFAULT NULL,
  `cardPrice` int(8) DEFAULT NULL,
  `remark` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `add_time` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 54 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for yy_cards
-- ----------------------------
DROP TABLE IF EXISTS `yy_cards`;
CREATE TABLE `yy_cards`  (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `sid` int(8) DEFAULT NULL,
  `sname` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `authorid` int(8) DEFAULT NULL,
  `card_value` int(8) DEFAULT NULL,
  `card_no` varchar(36) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `type` int(1) DEFAULT NULL,
  `status` int(1) DEFAULT NULL,
  `user_account` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `remark` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `add_time` int(10) DEFAULT NULL,
  `bid` int(8) DEFAULT 0 COMMENT '销售ID',
  `sell` int(1) DEFAULT 0 COMMENT '0系统可售1系统不可售',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2304 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for yy_forward_url
-- ----------------------------
DROP TABLE IF EXISTS `yy_forward_url`;
CREATE TABLE `yy_forward_url`  (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `uid` int(8) DEFAULT NULL,
  `sid` int(8) DEFAULT NULL,
  `name` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `url` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `type` int(255) DEFAULT NULL,
  `status` int(1) DEFAULT 0,
  `add_time` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 11 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for yy_login_record
-- ----------------------------
DROP TABLE IF EXISTS `yy_login_record`;
CREATE TABLE `yy_login_record`  (
  `id` int(6) NOT NULL AUTO_INCREMENT,
  `uid` int(6) DEFAULT NULL,
  `ip` varchar(15) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `login_time` int(10) DEFAULT NULL,
  `location` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 122 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for yy_money_list
-- ----------------------------
DROP TABLE IF EXISTS `yy_money_list`;
CREATE TABLE `yy_money_list`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `money` decimal(8, 3) DEFAULT NULL COMMENT '变动多少',
  `after` decimal(8, 3) DEFAULT NULL COMMENT '变动后余额',
  `uid` int(6) DEFAULT NULL,
  `type` int(1) DEFAULT NULL COMMENT '0支出1收入',
  `info` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL COMMENT '说明',
  `add_time` int(10) DEFAULT NULL,
  `charge` decimal(8, 3) DEFAULT NULL COMMENT '手续费',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 80 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for yy_msg_box
-- ----------------------------
DROP TABLE IF EXISTS `yy_msg_box`;
CREATE TABLE `yy_msg_box`  (
  `id` int(6) NOT NULL AUTO_INCREMENT,
  `uid` int(6) DEFAULT NULL,
  `type` int(1) DEFAULT 1,
  `msg` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `sendTime` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 150 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for yy_pay_record
-- ----------------------------
DROP TABLE IF EXISTS `yy_pay_record`;
CREATE TABLE `yy_pay_record`  (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `uid` int(8) NOT NULL,
  `orderno` varchar(22) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `payno` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '',
  `money` decimal(6, 2) NOT NULL,
  `pay_mode` int(1) NOT NULL COMMENT '支付方式1支付宝2qq钱包3微信',
  `status` int(1) NOT NULL COMMENT '支付状态0成功1未支付2未知',
  `type` int(1) NOT NULL COMMENT '0支出1收入',
  `order_time` int(10) NOT NULL,
  `pay_time` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 155 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for yy_remote_function
-- ----------------------------
DROP TABLE IF EXISTS `yy_remote_function`;
CREATE TABLE `yy_remote_function`  (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `sid` int(8) DEFAULT NULL,
  `uid` int(8) DEFAULT NULL,
  `name` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `create_time` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 22 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for yy_soft_list
-- ----------------------------
DROP TABLE IF EXISTS `yy_soft_list`;
CREATE TABLE `yy_soft_list`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(8) DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `key` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `version` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '',
  `count` int(6) DEFAULT 0,
  `openReg` int(1) DEFAULT 0,
  `notice` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `data` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `regFreePoint` int(5) DEFAULT NULL,
  `regFree` int(1) DEFAULT 0,
  `timeFreePointEnd` int(2) DEFAULT NULL,
  `timeFreePointStart` int(2) DEFAULT NULL,
  `timeFree` int(1) DEFAULT NULL,
  `freeChangeBundled` int(2) DEFAULT NULL,
  `verifyMode` int(1) DEFAULT 0,
  `pointStep` int(1) DEFAULT NULL,
  `topLoginType` int(1) DEFAULT 0,
  `multiType` int(1) DEFAULT 0,
  `isModifyMac` int(1) DEFAULT 0,
  `multiTypeValue` int(3) DEFAULT NULL COMMENT '多开上限',
  `encryptType` int(1) DEFAULT 0,
  `privateSalt` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `privateKey` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `sale_remark` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `status` int(1) DEFAULT 0 COMMENT '0免费1收费',
  `expireTime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 94 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for yy_soft_users
-- ----------------------------
DROP TABLE IF EXISTS `yy_soft_users`;
CREATE TABLE `yy_soft_users`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `password` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `phone` varchar(11) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `email` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `qq` varchar(12) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `authorid` int(8) DEFAULT NULL,
  `sid` int(8) DEFAULT NULL COMMENT '所属软件ID',
  `sname` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `maccode` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `out_time` int(10) DEFAULT NULL,
  `point` int(8) DEFAULT 0,
  `isblacklist` int(1) DEFAULT 0,
  `modif_num` int(5) DEFAULT 0,
  `heart_time` int(10) DEFAULT NULL,
  `isonline` int(1) DEFAULT 0,
  `status` int(1) DEFAULT 0,
  `remark` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `city` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `ip` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `createtime` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 252 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for yy_soft_ver
-- ----------------------------
DROP TABLE IF EXISTS `yy_soft_ver`;
CREATE TABLE `yy_soft_ver`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sid` int(11) DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `ver` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `checkUpdate` int(1) DEFAULT 0,
  `MD5` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `updateUrl` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `notice` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `reamrk` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `status` int(1) DEFAULT 0,
  `addTime` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 52 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for yy_su_login_record
-- ----------------------------
DROP TABLE IF EXISTS `yy_su_login_record`;
CREATE TABLE `yy_su_login_record`  (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `sid` int(8) DEFAULT NULL,
  `uid` int(8) DEFAULT NULL,
  `authorid` int(8) DEFAULT NULL,
  `sname` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `username` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `maccode` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `softMD5` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `ip` varchar(15) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `city` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `login_time` int(10) DEFAULT NULL,
  `heart_time` int(10) DEFAULT NULL,
  `status` int(1) DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 776 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for yy_users
-- ----------------------------
DROP TABLE IF EXISTS `yy_users`;
CREATE TABLE `yy_users`  (
  `id` int(6) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `email` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `phone` varchar(11) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `money` decimal(8, 2) DEFAULT 0.00,
  `user_id` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `regtime` int(10) DEFAULT NULL,
  `group_id` int(1) DEFAULT 1,
  `avatar` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '1.jpg',
  `qq` varchar(13) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '',
  `alipay` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '',
  `name` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `id`(`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 134 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

INSERT INTO `yy_users`(`username`, `password`, `group_id`) VALUES ('yz_admin_username', 'yz_admin_password', 2);
-- ----------------------------
-- Table structure for yy_variable
-- ----------------------------
DROP TABLE IF EXISTS `yy_variable`;
CREATE TABLE `yy_variable`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sid` int(11) DEFAULT NULL,
  `name` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `value` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `create_time` int(10) DEFAULT NULL,
  `authorid` int(8) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 18 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

SET FOREIGN_KEY_CHECKS = 1;
