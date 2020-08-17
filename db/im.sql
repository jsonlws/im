/*
 Navicat Premium Data Transfer

 Source Server         : 本地虚拟机
 Source Server Type    : MySQL
 Source Server Version : 50730
 Source Host           : 192.168.31.212:3306
 Source Schema         : im

 Target Server Type    : MySQL
 Target Server Version : 50730
 File Encoding         : 65001

 Date: 17/08/2020 16:09:53
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for im_group
-- ----------------------------
DROP TABLE IF EXISTS `im_group`;
CREATE TABLE `im_group`  (
  `group_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '群id',
  `group_name` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '群名称',
  `header_img` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '群头像',
  `describe` varchar(120) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '群描述',
  `create_time` timestamp(0) NOT NULL COMMENT '群创建时间',
  PRIMARY KEY (`group_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '群基础信息表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for im_group_msgs
-- ----------------------------
DROP TABLE IF EXISTS `im_group_msgs`;
CREATE TABLE `im_group_msgs`  (
  `msg_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '消息id',
  `group_id` int(10) NOT NULL COMMENT '群id',
  `send_uid` int(10) NOT NULL COMMENT '发送消息者id',
  `content` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '用户消息内容',
  `create_time` timestamp(0) NOT NULL COMMENT '消息发送时间',
  `msg_type` enum('text','voice','video','picture') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'text' COMMENT '消息类型',
  PRIMARY KEY (`msg_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '群消息记录表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for im_group_user
-- ----------------------------
DROP TABLE IF EXISTS `im_group_user`;
CREATE TABLE `im_group_user`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `group_id` int(10) NOT NULL COMMENT '群id',
  `user_token` int(10) NOT NULL COMMENT '群成员唯一标识',
  `user_type` enum('群主','副群主','普通成员') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '普通成员' COMMENT '成员类型',
  `create_time` timestamp(0) NOT NULL COMMENT '进群时间',
  `nickename` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '群昵称',
  `last_ack_msg_id` int(10) NOT NULL DEFAULT 0 COMMENT '用户在退出时最后收到消息的msgid',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '群成员表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for im_single_msg
-- ----------------------------
DROP TABLE IF EXISTS `im_single_msg`;
CREATE TABLE `im_single_msg`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `send_uid` int(10) NOT NULL COMMENT '发送消息者',
  `receive_uid` int(10) NOT NULL COMMENT '接受消息者',
  `content` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '消息内容',
  `msg_type` enum('text','voice','video','picture') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '消息类型',
  `create_time` timestamp(0) NOT NULL COMMENT '发送消息时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '单聊消息记录' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for im_single_offlinemsg
-- ----------------------------
DROP TABLE IF EXISTS `im_single_offlinemsg`;
CREATE TABLE `im_single_offlinemsg`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `msg_id` int(10) NOT NULL COMMENT '单聊消息id',
  `from_usertoken` int(10) NOT NULL COMMENT '发送消息者唯一标识',
  `receive_usertoken` int(10) NOT NULL COMMENT '接收消息者唯一标识',
  `msg_type` enum('text','voice','video','picture') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'text' COMMENT '消息类型',
  `content` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '消息内容',
  `create_time` timestamp(0) NOT NULL COMMENT '发送消息时间',
  PRIMARY KEY (`id`, `msg_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '单聊离线消息表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for im_user
-- ----------------------------
DROP TABLE IF EXISTS `im_user`;
CREATE TABLE `im_user`  (
  `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '用户id',
  `account` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '账号',
  `pwd` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '密码',
  `unickname` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '用户昵称',
  `head_img` varchar(120) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '头像',
  `user_autograph` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '用户签名',
  `user_token` int(10) NOT NULL COMMENT '用户唯一标识',
  `is_blacklist` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1是黑名单   0不是黑名单',
  `create_time` timestamp(0) NOT NULL COMMENT '注册时间',
  PRIMARY KEY (`uid`, `user_token`, `account`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '用户表' ROW_FORMAT = Dynamic;

SET FOREIGN_KEY_CHECKS = 1;
