/*
 Navicat Premium Data Transfer

 Source Server         : localhost
 Source Server Type    : MariaDB
 Source Server Version : 100137
 Source Host           : localhost:3306
 Source Schema         : oa

 Target Server Type    : MariaDB
 Target Server Version : 100137
 File Encoding         : 65001

 Date: 29/10/2019 10:50:22
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for oa_admin_column
-- ----------------------------
DROP TABLE IF EXISTS `oa_admin_column`;
CREATE TABLE `oa_admin_column`  (
  `id` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '类型对应的数据库名称',
  `name` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '类型名称',
  `state` int(1) NOT NULL DEFAULT 1 COMMENT '启用状态',
  `column_sort` int(2) NULL DEFAULT NULL COMMENT '顺序',
  `default_width` int(4) NULL DEFAULT 300 COMMENT '默认宽度',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_admin_enum
-- ----------------------------
DROP TABLE IF EXISTS `oa_admin_enum`;
CREATE TABLE `oa_admin_enum`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `option_value` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '枚举值',
  `description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `state` int(1) NULL DEFAULT 1 COMMENT '启用状态',
  `option_sort` int(6) UNSIGNED NULL DEFAULT NULL COMMENT '顺序',
  `list_id` int(10) UNSIGNED NOT NULL COMMENT '所属枚举列表',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 77 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_admin_enumlist
-- ----------------------------
DROP TABLE IF EXISTS `oa_admin_enumlist`;
CREATE TABLE `oa_admin_enumlist`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '枚举名称',
  `description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `state` int(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '启用状态',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `create_user` int(10) NULL DEFAULT NULL COMMENT '创建人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `update_user` int(10) NULL DEFAULT NULL COMMENT '更新人',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 12 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_admin_flow
-- ----------------------------
DROP TABLE IF EXISTS `oa_admin_flow`;
CREATE TABLE `oa_admin_flow`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `state` int(1) NULL DEFAULT 1,
  `flow_name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `step_ids` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 24 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_admin_formattr
-- ----------------------------
DROP TABLE IF EXISTS `oa_admin_formattr`;
CREATE TABLE `oa_admin_formattr`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '字段名称',
  `type` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '字段类型',
  `length` int(6) NULL DEFAULT NULL COMMENT '字段长度',
  `nullable` int(1) UNSIGNED NULL DEFAULT 1 COMMENT '是否可以为空',
  `description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `create_user` int(10) NULL DEFAULT NULL COMMENT '创建人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `update_user` int(10) NULL DEFAULT NULL COMMENT '更新人',
  `state` int(1) NULL DEFAULT 1 COMMENT '启用状态',
  `enumlist` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '下拉框列表ID',
  `model_id` int(10) NOT NULL COMMENT '所属模块ID',
  `default_value` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '默认值',
  `multi` int(1) NULL DEFAULT 0 COMMENT '下拉框是否多选（1多选 0单选）',
  `form_sort` int(3) NULL DEFAULT NULL COMMENT '顺序',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 99 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_admin_formval
-- ----------------------------
DROP TABLE IF EXISTS `oa_admin_formval`;
CREATE TABLE `oa_admin_formval`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `attrid` int(10) UNSIGNED NOT NULL COMMENT 'oa_admin_formattr表主键',
  `value` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '值',
  `menu` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '所属区分',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 27543 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_admin_model
-- ----------------------------
DROP TABLE IF EXISTS `oa_admin_model`;
CREATE TABLE `oa_admin_model`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `parentid` int(10) UNSIGNED NULL DEFAULT 0 COMMENT '上级模块ID',
  `state` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态',
  `name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '名称',
  `description` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `create_user` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT '创建人',
  `update_user` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT '更新人',
  `menu` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '所在位置',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 16 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '智能表单模块' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_admin_step
-- ----------------------------
DROP TABLE IF EXISTS `oa_admin_step`;
CREATE TABLE `oa_admin_step`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `step_name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `user` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '参与人员',
  `dept` varchar(40) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '部门ID',
  `manager` tinyint(4) NULL DEFAULT 0 COMMENT '是否上级主管处理（0：否 1：是）',
  `method` int(2) NULL DEFAULT 2 COMMENT '处理方式（1所有人都处理 2任何一人处理即可）',
  `return` int(2) NULL DEFAULT 1 COMMENT '退回后的处理（1退到初始状态 2退到本阶段开始 3退到上一阶段 4作废）',
  `step_ename` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '状态名称',
  `model_id` int(10) NULL DEFAULT NULL COMMENT '自定义表单ID',
  `description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 27 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_attendance
-- ----------------------------
DROP TABLE IF EXISTS `oa_attendance`;
CREATE TABLE `oa_attendance`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `number` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '工号',
  `time` datetime(0) NULL DEFAULT NULL COMMENT '打卡时间',
  `type` tinyint(1) NULL DEFAULT NULL COMMENT '0：上班，1：下班',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `pc` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '电脑名称',
  `ip` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '打卡IP',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 43 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '考勤记录' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_attendance_overtime
-- ----------------------------
DROP TABLE IF EXISTS `oa_attendance_overtime`;
CREATE TABLE `oa_attendance_overtime`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '类别[1：工作日，2：休息日，3：法定假日]',
  `time_from` datetime(0) NULL DEFAULT NULL COMMENT '开始时间',
  `time_to` datetime(0) NULL DEFAULT NULL COMMENT '结束时间',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `active` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态',
  `reason` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '事由',
  `remark` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '加班' ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_attendance_params
-- ----------------------------
DROP TABLE IF EXISTS `oa_attendance_params`;
CREATE TABLE `oa_attendance_params`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `employment_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用工形式',
  `private` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '事假',
  `vacation` int(10) NOT NULL DEFAULT 0 COMMENT '年假',
  `sick` int(10) NOT NULL DEFAULT 0 COMMENT '病假',
  `marriage` int(10) NOT NULL DEFAULT 0 COMMENT '婚假',
  `funeral` int(10) NOT NULL DEFAULT 0 COMMENT '丧假',
  `maternity` int(10) NOT NULL DEFAULT 0 COMMENT '产假',
  `paternity` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '陪产假',
  `other` int(10) NOT NULL DEFAULT 0 COMMENT '其它',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 5 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '考勤参数' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_attendance_vacation
-- ----------------------------
DROP TABLE IF EXISTS `oa_attendance_vacation`;
CREATE TABLE `oa_attendance_vacation`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '类别[1：事假，2：病假，3：年假，4：调休，5：其它（婚假、产假、丧假等）]',
  `time_from` datetime(0) NULL DEFAULT NULL COMMENT '开始时间',
  `time_to` datetime(0) NULL DEFAULT NULL COMMENT '结束时间',
  `create_user` datetime(0) NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `active` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态',
  `reason` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '事由',
  `remark` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `agent` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '代理人',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '请假' ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_attendance_vacation_storage
-- ----------------------------
DROP TABLE IF EXISTS `oa_attendance_vacation_storage`;
CREATE TABLE `oa_attendance_vacation_storage`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `number` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '工号',
  `in_year_qty` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '入司年数',
  `qty` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '年假天数',
  `remark` varchar(300) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `qty_used` float NOT NULL DEFAULT 0 COMMENT '已使用年假天数',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 703 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '年假库' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_attendance_workday
-- ----------------------------
DROP TABLE IF EXISTS `oa_attendance_workday`;
CREATE TABLE `oa_attendance_workday`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `day` date NULL DEFAULT NULL COMMENT '日期',
  `type` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '类别[工作日1/休息日2/法定假日3]',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1097 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '工作日设置' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_attendance_worktime
-- ----------------------------
DROP TABLE IF EXISTS `oa_attendance_worktime`;
CREATE TABLE `oa_attendance_worktime`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `active_from` date NULL DEFAULT NULL COMMENT '生效开始日期',
  `active_to` date NULL DEFAULT NULL COMMENT '生效结束日期',
  `from_h` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '上班时间：小时',
  `from_m` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '上班时间：分钟',
  `to_h` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '下班时间：小时',
  `to_m` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '下班时间：分钟',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '类别[职员：0，工人：1]',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 8 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '工作时间设定' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_bpartner
-- ----------------------------
DROP TABLE IF EXISTS `oa_bpartner`;
CREATE TABLE `oa_bpartner`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态',
  `type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '类别[0：供应商，1：客户]',
  `code` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '代码',
  `group_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '组ID',
  `cname` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '中文名称',
  `ename` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '英文名称',
  `bank_payment_days` int(10) UNSIGNED NOT NULL DEFAULT 30 COMMENT '账期',
  `bank_country` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '中国' COMMENT '银行账号：国家',
  `bank_type` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '银行账号：开户行',
  `bank_account` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '银行账号：账号',
  `bank_name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '银行账号：开户名称',
  `bank_remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '银行账号：备注',
  `remark` varchar(1000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `bank_currency` tinyint(1) NOT NULL DEFAULT 1 COMMENT '币种',
  `tax_id` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT '税率ID',
  `tax_num` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '税号',
  `rsm` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '销售RSM',
  `terminal_customer` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '终端客户',
  `suffix` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '后缀',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `index_code`(`code`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 113 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '业务伙伴' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_bpartner_address
-- ----------------------------
DROP TABLE IF EXISTS `oa_bpartner_address`;
CREATE TABLE `oa_bpartner_address`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `partner_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '业务伙伴ID',
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态',
  `country` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '国家',
  `area` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '省、州、县',
  `name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '地址名称',
  `zip_code` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '邮政编码',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 188 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '业务伙伴地址' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_bpartner_contact
-- ----------------------------
DROP TABLE IF EXISTS `oa_bpartner_contact`;
CREATE TABLE `oa_bpartner_contact`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `partner_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '业务伙伴ID',
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态',
  `name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '联系人',
  `post` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '联系人职位',
  `tel` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '联系人电话',
  `fax` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '联系人传真',
  `email` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '联系人邮箱',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `country` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '国家',
  `area` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '地区',
  `address` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '地址',
  `zip_code` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '邮编',
  `default` tinyint(1) NOT NULL DEFAULT 1 COMMENT '默认',
  `area_city` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '城市',
  `area_code` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '地址简码',
  `person_liable` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '责任人',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `index_partner_id`(`partner_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 474 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '业务伙伴联系方式' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_bpartner_group
-- ----------------------------
DROP TABLE IF EXISTS `oa_bpartner_group`;
CREATE TABLE `oa_bpartner_group`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '类别',
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态',
  `code` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '名称',
  `description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `remark` varchar(300) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 7 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '业务伙伴组' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_bpartner_payment
-- ----------------------------
DROP TABLE IF EXISTS `oa_bpartner_payment`;
CREATE TABLE `oa_bpartner_payment`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态',
  `qty` int(10) NOT NULL DEFAULT 0,
  `name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '名称',
  `description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `remark` varchar(300) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 7 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '业务伙伴组' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_codemaster
-- ----------------------------
DROP TABLE IF EXISTS `oa_codemaster`;
CREATE TABLE `oa_codemaster`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` int(3) NOT NULL COMMENT '类型',
  `type_name` varchar(40) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '类型名称',
  `code` int(3) NOT NULL COMMENT '代码',
  `text` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '值',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 37 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = 'code master' ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_config
-- ----------------------------
DROP TABLE IF EXISTS `oa_config`;
CREATE TABLE `oa_config`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '名称',
  `value` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '值',
  `default` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '默认值',
  `description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `remark` varchar(300) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '系统设置' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_currency
-- ----------------------------
DROP TABLE IF EXISTS `oa_currency`;
CREATE TABLE `oa_currency`  (
  `code` varchar(3) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '币种缩写',
  `symbol` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '符号',
  `name` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '币种名称',
  PRIMARY KEY (`code`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_doc_auto
-- ----------------------------
DROP TABLE IF EXISTS `oa_doc_auto`;
CREATE TABLE `oa_doc_auto`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `automethod` varchar(11) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '编码方式',
  `description` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '描述',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 9 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '文件编码流水号产生方式' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_doc_code
-- ----------------------------
DROP TABLE IF EXISTS `oa_doc_code`;
CREATE TABLE `oa_doc_code`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `state` varchar(15) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '审核状态',
  `prefix` int(10) NULL DEFAULT NULL COMMENT '文件简号',
  `code` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '文件号',
  `project_no` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '产品型号',
  `project_standard_no` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '产品方案',
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否激活',
  `description` varchar(300) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `name` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `index_code`(`code`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 12218 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '文件号' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_doc_files
-- ----------------------------
DROP TABLE IF EXISTS `oa_doc_files`;
CREATE TABLE `oa_doc_files`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `state` varchar(15) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'active' COMMENT '状态',
  `code` varchar(400) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '编码',
  `ver` varchar(40) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '1.0' COMMENT '版本',
  `send_require` int(1) NULL DEFAULT 0 COMMENT '否是需要外发',
  `project_info` varchar(256) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '项目信息',
  `tag` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '关键字',
  `name` varchar(2000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '文件名',
  `file_ids` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '文件ID',
  `description` varchar(2048) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `archive_time` datetime(0) NULL DEFAULT NULL COMMENT '归档日期',
  `del_flg` int(1) NULL DEFAULT 0 COMMENT '删除状态',
  `add_flg` int(1) NULL DEFAULT 0 COMMENT '创建方式（0文件评审或升版 1直接新增）',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `index_code`(`code`(333)) USING BTREE,
  INDEX `index_files`(`file_ids`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 15039 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '文件号' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_doc_files_dev
-- ----------------------------
DROP TABLE IF EXISTS `oa_doc_files_dev`;
CREATE TABLE `oa_doc_files_dev`  (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `files_id` int(10) NULL DEFAULT NULL COMMENT 'oa_doc_files ID',
  `code` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `file_ids` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `file_names` varchar(2000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `ver` decimal(4, 1) NULL DEFAULT NULL,
  `project_no` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `description` varchar(300) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_doc_record
-- ----------------------------
DROP TABLE IF EXISTS `oa_doc_record`;
CREATE TABLE `oa_doc_record`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '类别',
  `table_name` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '对应表',
  `table_id` int(10) UNSIGNED NOT NULL COMMENT '对应表ID',
  `handle_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '操作者',
  `handle_time` datetime(0) NULL DEFAULT NULL COMMENT '操作时间',
  `action` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '动作（增、删、改、审核、下载、在线浏览）',
  `result` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '操作结果',
  `ip` varchar(15) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'IP地址',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_doc_review
-- ----------------------------
DROP TABLE IF EXISTS `oa_doc_review`;
CREATE TABLE `oa_doc_review`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '类型（新文件/升版）',
  `file_id` int(10) NULL DEFAULT NULL COMMENT '文件ID',
  `plan_user` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '预定审核人',
  `method` varchar(1) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '2' COMMENT '处理方式（1所有人都处理 2任何一人处理即可）',
  `return` int(2) NULL DEFAULT 1 COMMENT '退回后的处理（1退到初始状态 2退到本阶段开始 3退到上一阶段 4作废）',
  `actual_user` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '实际审核人',
  `finish_time` datetime(0) NULL DEFAULT NULL COMMENT '完成审核时间',
  `finish_flg` int(1) NULL DEFAULT 0 COMMENT '审核状态（1已完成 0未完成）',
  `step_name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '状态名称',
  `step_ename` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '状态名称',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_doc_send
-- ----------------------------
DROP TABLE IF EXISTS `oa_doc_send`;
CREATE TABLE `oa_doc_send`  (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `to` varchar(2000) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '收件人',
  `cc` varchar(2000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '抄送人',
  `subject` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '主题',
  `content` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '内容',
  `doc_names` varchar(2000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '文件编码',
  `doc_ids` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '文件id（编码）',
  `file_names` varchar(2000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '文件名',
  `file_ids` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '发送的文件ID（文件）',
  `send_date` datetime(0) NULL DEFAULT NULL COMMENT '定时发送时间（暂不用）',
  `handle_time` datetime(0) NULL DEFAULT NULL COMMENT '发送时间',
  `handle_user` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '发送者',
  `result` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '方法结果',
  `error_info` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '发送失败信息',
  `sendtype` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '发放类别',
  `outsendtype` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '外发类别',
  `remark` varchar(2000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `code` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `dept` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '内发部门',
  `partner` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '供应商/客户代码',
  `linkman` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '客户/供应商联系人邮件地址',
  `to_name` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '收件人称呼',
  `footer` varchar(400) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '签名',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 26 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '文件发放' ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_doc_share
-- ----------------------------
DROP TABLE IF EXISTS `oa_doc_share`;
CREATE TABLE `oa_doc_share`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '类型（新文件/升版/归档文件）',
  `shared_id` int(10) NOT NULL COMMENT '文件ID',
  `share_user` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '共享给',
  `share_dept` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '共享部门',
  `share_time_begin` date NULL DEFAULT '0000-00-00' COMMENT '共享开始日期',
  `share_time_end` date NULL DEFAULT '9999-12-31' COMMENT '共享结束日期',
  `create_user` int(10) NULL DEFAULT NULL COMMENT '创建者',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `index_shareid`(`shared_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 931 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_doc_template
-- ----------------------------
DROP TABLE IF EXISTS `oa_doc_template`;
CREATE TABLE `oa_doc_template`  (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `active` int(1) NULL DEFAULT 1,
  `name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '文件名',
  `path` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '文件路径',
  `description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `create_user` int(10) NULL DEFAULT NULL,
  `create_time` datetime(0) NULL DEFAULT NULL,
  `update_user` int(10) NULL DEFAULT NULL,
  `update_time` datetime(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '文件模板' ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_doc_type
-- ----------------------------
DROP TABLE IF EXISTS `oa_doc_type`;
CREATE TABLE `oa_doc_type`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '简号',
  `length` int(11) UNSIGNED NULL DEFAULT NULL COMMENT '流水号长度',
  `name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '名称',
  `category` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '分类',
  `fullname` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '全称',
  `duration` tinyint(3) NULL DEFAULT NULL COMMENT '审核时间(D)',
  `description` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `state` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态',
  `autocode` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT '自动编码',
  `modelrequire` int(1) NULL DEFAULT 0 COMMENT '产品型号是否必须（0：非必须 1：必须）',
  `autotype` int(2) NOT NULL DEFAULT 1 COMMENT '自动编号类型',
  `model_id` int(10) NULL DEFAULT NULL COMMENT '新文件归档时自定义表单ID',
  `dev_model_id` int(10) NULL DEFAULT NULL COMMENT '升版时自定义表单ID',
  `flow_id` int(10) NULL DEFAULT NULL COMMENT '新文件归档时工作流ID',
  `dev_flow_id` int(10) NULL DEFAULT NULL COMMENT '文件升版时工作流ID',
  `flow_flg` int(1) NULL DEFAULT 1 COMMENT '是否给予流程归档（1是 0否）',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `resp_emp_id` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '责任人',
  `resp_dept_id` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '责任部门',
  `grant_dept_id` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '发放部门',
  `template` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '模板编号',
  `filerequire` tinyint(1) NULL DEFAULT NULL COMMENT '是否必须',
  `secretlevel` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '密级',
  `apply_flow_id` int(10) NULL DEFAULT NULL COMMENT '文件号申请流程',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 106 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '文件类别' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_doc_upgrade
-- ----------------------------
DROP TABLE IF EXISTS `oa_doc_upgrade`;
CREATE TABLE `oa_doc_upgrade`  (
  `file_id` int(10) NOT NULL COMMENT '文件编码',
  `ver_original` float(3, 1) NULL DEFAULT NULL COMMENT '原来的版本号',
  `ver` float(3, 1) NULL DEFAULT NULL COMMENT '要升级到的版本号',
  `reason` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '升版原因',
  `project_no` int(11) NULL DEFAULT NULL COMMENT '产品型号',
  `description` varchar(300) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '文件描述',
  `create_user` int(10) NULL DEFAULT NULL COMMENT '创建者',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) NULL DEFAULT NULL,
  `update_time` datetime(0) NULL DEFAULT NULL,
  `reason_type` int(10) NULL DEFAULT NULL,
  PRIMARY KEY (`file_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_doc_upload
-- ----------------------------
DROP TABLE IF EXISTS `oa_doc_upload`;
CREATE TABLE `oa_doc_upload`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '文件名',
  `path` varchar(300) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '文件路径',
  `category` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `view_path` varchar(300) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '浏览路径',
  `size` int(10) NULL DEFAULT NULL COMMENT '大小',
  `type` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '文件类型',
  `description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_user` int(10) NULL DEFAULT NULL,
  `create_time` datetime(0) NULL DEFAULT NULL,
  `update_user` int(10) NULL DEFAULT NULL,
  `update_time` datetime(0) NULL DEFAULT NULL,
  `upload_time` datetime(0) NULL DEFAULT NULL COMMENT '上传日期',
  `archive_time` datetime(0) NULL DEFAULT NULL COMMENT '归档时间',
  `private` int(1) NULL DEFAULT 0 COMMENT '是否私有（0公开 1私有）',
  `archive` int(1) NULL DEFAULT 0 COMMENT '是否已归档（1是 0否）',
  `del` int(1) NULL DEFAULT 0 COMMENT '是否已删除（1是 0否）',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 16025 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_employee
-- ----------------------------
DROP TABLE IF EXISTS `oa_employee`;
CREATE TABLE `oa_employee`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '在职状态',
  `number` varchar(15) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '工号',
  `cname` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '中文名',
  `ename` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '英文名',
  `sex` tinyint(1) NOT NULL DEFAULT 1 COMMENT '性别',
  `birthday` date NULL DEFAULT NULL COMMENT '生日',
  `id_card` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '身份证号码',
  `dept_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '部门ID',
  `post_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '职位',
  `manager_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '上级主管ID',
  `salary` float NOT NULL DEFAULT 1 COMMENT '薪水',
  `bank` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '开户行',
  `bank_num` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '银行卡号',
  `email` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '邮箱',
  `tel` varchar(15) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '电话',
  `address` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '家庭地址',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `marital_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '婚否',
  `marry_day` date NULL DEFAULT NULL COMMENT '结婚纪念日',
  `children_birthday` date NULL DEFAULT NULL COMMENT '小孩生日',
  `insurcode` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '社保号',
  `accumulation_fund_code` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '公积金号',
  `education` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '学历',
  `school` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '毕业院校',
  `major` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '专业',
  `entry_date` date NULL DEFAULT NULL COMMENT '入职日期',
  `leave_date` date NULL DEFAULT NULL COMMENT '离职日期',
  `create_user` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '添加时间',
  `update_user` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `regularization_date` date NULL DEFAULT NULL COMMENT '转正日期',
  `labor_contract_start` date NULL DEFAULT NULL COMMENT '劳动合同起始日期',
  `labor_contract_end` date NULL DEFAULT NULL COMMENT '劳动合同截止日期',
  `offical_address` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '户口地址',
  `other_contact` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '紧急联系人',
  `other_relationship` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '双方关系',
  `other_contact_way` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '紧急联系人联系方式',
  `work_years` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '工作年限',
  `politics_status` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '政治面貌',
  `employment_type` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用工形式[0：弹性，1：非弹性]',
  `ext` varchar(25) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '分机号',
  `driving_license` tinyint(1) NOT NULL DEFAULT 0 COMMENT '驾照',
  `official_qq` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '企业QQ',
  `msn` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'MSN',
  `short_num` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '短号',
  `work_place` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '工作地点',
  `area_id` int(10) NULL DEFAULT NULL COMMENT '地区ID',
  `dept_manager_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '部门主管ID',
  `leader` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否公司领导',
  `professional_qualifications_id` int(10) NULL DEFAULT NULL COMMENT '技术职称等级',
  `hide` tinyint(1) NULL DEFAULT 0,
  `photo_path` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `index_id`(`id`) USING BTREE,
  INDEX `index_number`(`number`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 139 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '用户表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_employee_attendance
-- ----------------------------
DROP TABLE IF EXISTS `oa_employee_attendance`;
CREATE TABLE `oa_employee_attendance`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_num` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '工号',
  `type` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '打卡类别',
  `time` datetime(0) NULL DEFAULT NULL COMMENT '打卡时间',
  `ip` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '打卡IP',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '考勤记录' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_employee_dept
-- ----------------------------
DROP TABLE IF EXISTS `oa_employee_dept`;
CREATE TABLE `oa_employee_dept`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `parentid` int(10) UNSIGNED NULL DEFAULT 0 COMMENT '上级部门ID',
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态',
  `name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '名称',
  `description` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `create_user` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT '创建人',
  `update_user` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT '更新人',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 13 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '部门' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_employee_post
-- ----------------------------
DROP TABLE IF EXISTS `oa_employee_post`;
CREATE TABLE `oa_employee_post`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '名称',
  `description` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 47 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '职位' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_employee_type
-- ----------------------------
DROP TABLE IF EXISTS `oa_employee_type`;
CREATE TABLE `oa_employee_type`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '名称',
  `description` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 3 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '职位' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_employee_workday
-- ----------------------------
DROP TABLE IF EXISTS `oa_employee_workday`;
CREATE TABLE `oa_employee_workday`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `date` date NULL DEFAULT NULL COMMENT '日期',
  `type` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '类别(工作日/法定假日)',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '考勤日期' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_fin_account
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_fin_account`;
CREATE TABLE `oa_erp_fin_account`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `active` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '1' COMMENT '启用',
  `parentid` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '0' COMMENT '上级科目ID',
  `code` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '\r\n\r\n代码',
  `name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '名称',
  `description` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `remark` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '科目表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_pricelist
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_pricelist`;
CREATE TABLE `oa_erp_pricelist`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '类别[0：供应商，1：客户]',
  `supplier_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '供应商ID',
  `code` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料号',
  `product_code` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `price` decimal(15, 4) NULL DEFAULT NULL COMMENT '价格',
  `currency` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '币种',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`, `type`) USING BTREE,
  INDEX `index_id`(`id`) USING BTREE,
  INDEX `index_supplier_id`(`supplier_id`) USING BTREE,
  INDEX `index_code`(`code`) USING BTREE,
  INDEX `index_product_code`(`product_code`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 2 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '物料价格清单' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_pricelist_ladder
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_pricelist_ladder`;
CREATE TABLE `oa_erp_pricelist_ladder`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pricelist_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '价格清单ID',
  `date` date NULL DEFAULT NULL COMMENT '日期',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `price` decimal(15, 4) NULL DEFAULT NULL COMMENT '价格',
  `currency` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '币种',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `index_id`(`id`) USING BTREE,
  INDEX `index_pricelist_id`(`pricelist_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 3 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '价格清单-阶梯价' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_pricelist_ladder_qty
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_pricelist_ladder_qty`;
CREATE TABLE `oa_erp_pricelist_ladder_qty`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ladder_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '阶梯价ID',
  `qty` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '数量',
  `price` decimal(15, 4) NULL DEFAULT NULL COMMENT '价格',
  `currency` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '币种',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `index_id`(`id`) USING BTREE,
  INDEX `index_ladder_id`(`ladder_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '价格清单-数量阶梯价' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_pur
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_pur`;
CREATE TABLE `oa_erp_pur`  (
  `id` int(11) NULL DEFAULT NULL,
  `supplier_id` int(45) NULL DEFAULT NULL COMMENT '供应商ID',
  `type` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '采购类别（物料/服务/其它）',
  `description` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `remark` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `request_date` date NULL DEFAULT NULL,
  `create_user` int(10) NULL DEFAULT NULL,
  `create_time` datetime(0) NULL DEFAULT NULL,
  `update_user` int(10) NULL DEFAULT NULL,
  `update_time` datetime(0) NULL DEFAULT NULL
) ENGINE = MyISAM CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '采购订单' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_pur_buyer
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_pur_buyer`;
CREATE TABLE `oa_erp_pur_buyer`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '用户ID',
  `tel` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '电话',
  `fax` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '传真',
  `remark` varchar(300) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '启用',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 2 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '采购员' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_pur_buyer_work
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_pur_buyer_work`;
CREATE TABLE `oa_erp_pur_buyer_work`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `buyer_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '采购员ID',
  `type_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '物料类别ID',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 5 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '采购员分工' ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for oa_erp_pur_invoice
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_pur_invoice`;
CREATE TABLE `oa_erp_pur_invoice`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `number` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '发票号',
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '启用',
  `state` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态（0：未审核，1：拒绝，2：批准）',
  `supplier_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '供应商ID',
  `invoice_date` date NULL DEFAULT NULL COMMENT '发票日期',
  `total` decimal(15, 4) NOT NULL DEFAULT 0.0000 COMMENT '总计',
  `total_tax` decimal(15, 4) NOT NULL COMMENT '税总计',
  `total_no_tax` decimal(15, 4) NOT NULL DEFAULT 0.0000 COMMENT '不含税总计',
  `buyer_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '采购员',
  `description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `remark` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `review_info` longtext CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '审核信息',
  `attach_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '附件名称',
  `attach_path` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '附件路径',
  `release_time` datetime(0) NULL DEFAULT NULL COMMENT '发布时间',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `forein_total` decimal(15, 4) NOT NULL DEFAULT 0.0000 COMMENT '外币总计',
  `forein_total_tax` decimal(15, 4) NOT NULL DEFAULT 0.0000 COMMENT '外币税总计',
  `forein_total_no_tax` decimal(15, 4) NOT NULL DEFAULT 0.0000 COMMENT '外币不含税总计',
  `currency` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '币种',
  `flow_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '流程ID',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '采购发票' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_pur_invoice_items
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_pur_invoice_items`;
CREATE TABLE `oa_erp_pur_invoice_items`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '发票ID',
  `order_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '订单ID',
  `order_date` date NULL DEFAULT NULL COMMENT '订单日期',
  `order_number` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '订单号',
  `order_item_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '订单项ID',
  `currency` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'CNY' COMMENT '币种',
  `currency_rate` decimal(9, 2) NOT NULL DEFAULT 1.00 COMMENT '货币汇率',
  `qty` decimal(9, 2) NULL DEFAULT NULL COMMENT '数量',
  `price` decimal(15, 4) NULL DEFAULT NULL COMMENT '价格',
  `price_tax` tinyint(1) NOT NULL DEFAULT 1 COMMENT '价格是否含税',
  `code` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料号',
  `name` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料名称',
  `description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料描述',
  `remark` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `unit` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '个' COMMENT '单位',
  `tax_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '税ID',
  `tax_name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '税名称',
  `tax_rate` decimal(9, 3) NULL DEFAULT NULL COMMENT '税率',
  `total` decimal(15, 4) NOT NULL DEFAULT 0.0000 COMMENT '总计',
  `total_tax` decimal(15, 4) NOT NULL DEFAULT 0.0000 COMMENT '税总计',
  `total_no_tax` decimal(15, 4) NOT NULL DEFAULT 0.0000 COMMENT '不含税总计',
  `forein_total` decimal(15, 4) NOT NULL DEFAULT 0.0000 COMMENT '外币总计',
  `forein_total_tax` decimal(15, 4) NOT NULL DEFAULT 0.0000 COMMENT '外币税总计',
  `forein_total_no_tax` decimal(15, 4) NOT NULL DEFAULT 0.0000 COMMENT '外币不含税总计',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '采购发票 - 发票项' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_pur_items
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_pur_items`;
CREATE TABLE `oa_erp_pur_items`  (
  `id` int(11) NULL DEFAULT NULL,
  `purchase_id` int(11) NULL DEFAULT NULL COMMENT '订单ID',
  `code` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '料号',
  `version` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '版本',
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '名称',
  `description` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `qty` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '数量',
  `remark` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `price` decimal(10, 0) NULL DEFAULT NULL COMMENT '单价',
  `currency` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '币种',
  `line_total` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL
) ENGINE = MyISAM CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_pur_order
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_pur_order`;
CREATE TABLE `oa_erp_pur_order`  (
  `id` int(1) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company` tinyint(1) NOT NULL DEFAULT 0 COMMENT '下单方',
  `hand` tinyint(1) NOT NULL DEFAULT 0 COMMENT '手动补单',
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '启用',
  `state` tinyint(1) NOT NULL DEFAULT 0 COMMENT '订单状态（0：未审核，1：拒绝，2：批准）',
  `number` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '订单号',
  `type` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '类型',
  `delivery_state` tinyint(1) NOT NULL DEFAULT 0 COMMENT '交货状态（0：未交货，1：未清，2：已清）',
  `currency` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '币种',
  `total` decimal(15, 2) NOT NULL DEFAULT 0.00 COMMENT '总金额',
  `supplier_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '供应商ID',
  `payment_id` int(10) NOT NULL DEFAULT 3 COMMENT '付款方式ID（账期）',
  `payment_days` int(10) UNSIGNED NOT NULL DEFAULT 45 COMMENT '账期',
  `supplier_contact_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '供应商联系人ID',
  `receiver_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '收货人ID',
  `customer_address_code` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '客户地址简码',
  `order_date` date NULL DEFAULT NULL COMMENT '订单日期',
  `request_date` date NULL DEFAULT NULL COMMENT '要求日期',
  `delivery_date_from` date NULL DEFAULT NULL COMMENT '实际交货日期-从',
  `delivery_date_to` date NULL DEFAULT NULL COMMENT '实际交货日期-至',
  `description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `remark` varchar(300) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `manufacture` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '产地及品牌',
  `responsible` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '质保期',
  `tpl_id` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT '合同模板ID',
  `release_time` datetime(0) NULL DEFAULT NULL COMMENT '发布时间',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `review_info` longtext CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '审核日志',
  `type_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '采购类别ID',
  `currency_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '币种ID',
  `currency_rate` float NOT NULL DEFAULT 1 COMMENT '币种汇率',
  `buyer_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '采购员ID',
  `receive_flag` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否已收货完毕（已清）',
  `settle_way` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '结算方式',
  `delvery_clause` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '交货条款',
  `attach_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '附件文件名',
  `attach_path` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '附件文件路径',
  `tax_id` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT '税率ID',
  `tax_name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '增值税发票' COMMENT '税率名称',
  `tax_rate` decimal(5, 2) NOT NULL DEFAULT 0.00 COMMENT '税率',
  `total_tax` decimal(15, 2) NOT NULL DEFAULT 0.00 COMMENT '税金',
  `total_no_tax` decimal(15, 2) NOT NULL DEFAULT 0.00 COMMENT '不含税金额',
  `price_tax` tinyint(1) NOT NULL DEFAULT 0 COMMENT '价格含税',
  `forein_total` decimal(15, 2) NOT NULL DEFAULT 0.00 COMMENT '外币总金额',
  `forein_total_tax` decimal(15, 2) NOT NULL DEFAULT 0.00 COMMENT '外币税金',
  `forein_total_no_tax` decimal(15, 2) NOT NULL COMMENT '外币不含税金额',
  `transfer_description` varchar(1000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '变更说明',
  `transfer_id` int(10) NULL DEFAULT NULL,
  `submit_type` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'new',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `index_id`(`id`) USING BTREE,
  INDEX `index_company`(`company`) USING BTREE,
  INDEX `index_active`(`active`) USING BTREE,
  INDEX `index_state`(`state`) USING BTREE,
  INDEX `index_number`(`number`) USING BTREE,
  INDEX `index_supplier_id`(`supplier_id`) USING BTREE,
  INDEX `index_transfer_id`(`transfer_id`) USING BTREE,
  INDEX `index_submit_type`(`submit_type`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '采购订单' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_pur_order_items
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_pur_order_items`;
CREATE TABLE `oa_erp_pur_order_items`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '订单ID',
  `delivery_date` date NULL DEFAULT NULL COMMENT '预计交期',
  `request_date` date NULL DEFAULT NULL COMMENT '需求日期',
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '启用',
  `state` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态（0：未交货；1：未清；2：已清）',
  `code` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料号',
  `type` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料类别',
  `name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料名称',
  `description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料描述',
  `remark` varchar(300) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料备注',
  `supplier_code` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '供应商物料号',
  `supplier_codename` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '供应商物料名称',
  `supplier_description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '供应商物料描述',
  `warehouse_code` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '接收仓库号',
  `price` decimal(15, 4) NULL DEFAULT NULL COMMENT '单价',
  `qty` decimal(9, 2) NOT NULL DEFAULT 0.00 COMMENT '数量',
  `unit` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '单位',
  `total` decimal(15, 4) NULL DEFAULT NULL COMMENT '行总计',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` date NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` date NULL DEFAULT NULL COMMENT '更新时间',
  `project_info` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '项目信息',
  `dept_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '需求部门ID',
  `req_number` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '采购申请',
  `qty_receive` float NOT NULL DEFAULT 0 COMMENT '收货数量',
  `delivery_date_remark` longtext CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '交期备注',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '采购订单-订单项' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_pur_order_items_req
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_pur_order_items_req`;
CREATE TABLE `oa_erp_pur_order_items_req`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_item_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '订单ID',
  `req_item_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '申请ID',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `qty` decimal(9, 2) NULL DEFAULT NULL COMMENT '数量',
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '启用',
  `code` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料代码',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '订单项对应申请ID' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_pur_order_items_transfer
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_pur_order_items_transfer`;
CREATE TABLE `oa_erp_pur_order_items_transfer`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '订单ID',
  `delivery_date` date NULL DEFAULT NULL COMMENT '预计交期',
  `request_date` date NULL DEFAULT NULL COMMENT '需求日期',
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '启用',
  `state` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态（0：未交货；1：未清；2：已清）',
  `code` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料号',
  `type` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料类别',
  `name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料名称',
  `description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料描述',
  `remark` varchar(300) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料备注',
  `supplier_code` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '供应商物料号',
  `supplier_codename` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '供应商物料名称',
  `supplier_description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '供应商物料描述',
  `warehouse_code` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '接收仓库号',
  `price` decimal(15, 4) NULL DEFAULT NULL COMMENT '单价',
  `qty` decimal(9, 2) NOT NULL DEFAULT 0.00 COMMENT '数量',
  `unit` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '单位',
  `total` decimal(15, 4) NULL DEFAULT NULL COMMENT '行总计',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` date NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` date NULL DEFAULT NULL COMMENT '更新时间',
  `project_info` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '项目信息',
  `dept_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '需求部门ID',
  `req_number` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '采购申请',
  `qty_receive` float NOT NULL DEFAULT 0 COMMENT '收货数量',
  `delivery_date_remark` longtext CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '交期备注',
  `transfer_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '订单ID',
  `transfer_type` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'add' COMMENT '变更类别',
  `order_item_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '订单项ID',
  `req_item_id` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `req_qty` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '采购订单-订单项' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_pur_req
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_pur_req`;
CREATE TABLE `oa_erp_pur_req`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `hand` tinyint(1) NOT NULL DEFAULT 0 COMMENT '手动补单',
  `number` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '申购单号',
  `dept_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '部门ID',
  `total` decimal(15, 2) NOT NULL DEFAULT 0.00 COMMENT '总计',
  `state` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态（0：未审核；1：拒绝；2：已批准）',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `release_time` datetime(0) NULL DEFAULT NULL COMMENT '发布时间',
  `review_info` longtext CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '审核意见',
  `approved_user` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '审核人',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `type_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '采购类别ID',
  `reason` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '事由',
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '启用 / 取消',
  `apply_user` int(10) NULL DEFAULT NULL COMMENT '申请人',
  `order_flag` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否下单',
  `change_reason` longtext CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '变更说明',
  `transfer_description` varchar(1000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '变更说明',
  `transfer_id` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `submit_type` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'new',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 11 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '采购申请' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_pur_req_items
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_pur_req_items`;
CREATE TABLE `oa_erp_pur_req_items`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `req_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '申请单ID',
  `name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '名称',
  `description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `qty` float NULL DEFAULT NULL COMMENT '数量',
  `unit` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '单位',
  `price` decimal(15, 4) NULL DEFAULT NULL COMMENT '价格',
  `line_total` decimal(15, 2) NULL DEFAULT NULL COMMENT '行总计',
  `date_req` date NULL DEFAULT NULL COMMENT '需求日期',
  `supplier` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '品牌、供应商',
  `dept_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '需求部门',
  `remark` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态',
  `project_info` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '项目信息',
  `code` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料号',
  `model` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '型号',
  `order_flag` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否下单',
  `order_req_num` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '订货产品出库申请号',
  `customer_address` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '客户收件人地址简码',
  `customer_aggrement` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '客户合同号',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 23 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '采购申请-行' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_pur_req_items_received
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_pur_req_items_received`;
CREATE TABLE `oa_erp_pur_req_items_received`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `receive_item_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '收货单（转储单）ID',
  `qty` decimal(15, 2) NULL DEFAULT NULL COMMENT '数量',
  `req_item_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '申请项ID',
  `order_item_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '订单项ID',
  `receive_number` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '转储单号',
  `order_number` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '采购单号',
  `req_number` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '申请单号',
  `receive_time` datetime(0) NULL DEFAULT NULL COMMENT '转储时间',
  `order_time` datetime(0) NULL DEFAULT NULL COMMENT '采购时间',
  `req_time` datetime(0) NULL DEFAULT NULL COMMENT '申请时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1117 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '申请收货日志' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_pur_req_items_transfer
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_pur_req_items_transfer`;
CREATE TABLE `oa_erp_pur_req_items_transfer`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `req_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '申请单ID',
  `transfer_id` int(10) NULL DEFAULT NULL COMMENT '变更ID',
  `transfer_type` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'add' COMMENT '变更类别',
  `req_item_id` int(10) NULL DEFAULT NULL COMMENT '申请项ID',
  `name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '名称',
  `description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `qty` float NULL DEFAULT NULL COMMENT '数量',
  `unit` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '单位',
  `price` decimal(15, 4) NULL DEFAULT NULL COMMENT '价格',
  `line_total` decimal(15, 2) NULL DEFAULT NULL COMMENT '行总计',
  `date_req` date NULL DEFAULT NULL COMMENT '需求日期',
  `supplier` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '品牌、供应商',
  `dept_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '需求部门',
  `remark` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态',
  `project_info` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '项目信息',
  `code` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料号',
  `model` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '型号',
  `order_flag` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否下单',
  `order_req_num` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '订货产品出库申请号',
  `customer_address` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '客户收件人地址简码',
  `customer_aggrement` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '客户合同号',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 5 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '采购申请-行' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_pur_transfer
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_pur_transfer`;
CREATE TABLE `oa_erp_pur_transfer`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '类别（采购申请、采购订单）',
  `target_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '目标ID',
  `transfer_type` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '变更类别',
  `transfer_description` varchar(1000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '变更说明',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `state` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态（0：未审核,1：拒绝,2：批准）',
  `transfer_content` longtext CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '变更内容',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 3 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '采购申请、订单变更' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_pur_type
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_pur_type`;
CREATE TABLE `oa_erp_pur_type`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '启用',
  `code` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '代码',
  `name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '名称',
  `description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `remark` varchar(300) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `req_flow_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '申请流程ID',
  `tpl_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '模板ID',
  `order_flow_id` int(10) NOT NULL DEFAULT 0 COMMENT '单订流程ID',
  `chk_package_qty` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 8 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '采购类别' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_purchase
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_purchase`;
CREATE TABLE `oa_erp_purchase`  (
  `id` int(11) NULL DEFAULT NULL,
  `supplier_id` int(45) NULL DEFAULT NULL COMMENT '供应商ID',
  `type` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '采购类别（物料/服务/其它）',
  `description` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `remark` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `request_date` date NULL DEFAULT NULL,
  `create_user` int(10) NULL DEFAULT NULL,
  `create_time` datetime(0) NULL DEFAULT NULL,
  `update_user` int(10) NULL DEFAULT NULL,
  `update_time` datetime(0) NULL DEFAULT NULL
) ENGINE = MyISAM CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '采购订单' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_purchase_items
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_purchase_items`;
CREATE TABLE `oa_erp_purchase_items`  (
  `id` int(11) NULL DEFAULT NULL,
  `purchase_id` int(11) NULL DEFAULT NULL COMMENT '订单ID',
  `code` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '料号',
  `version` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '版本',
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '名称',
  `description` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `qty` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '数量',
  `remark` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `price` decimal(10, 0) NULL DEFAULT NULL COMMENT '单价',
  `currency` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '币种',
  `line_total` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL
) ENGINE = MyISAM CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_sale_invoice
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_sale_invoice`;
CREATE TABLE `oa_erp_sale_invoice`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `number` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '发票号',
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '启用',
  `state` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态（0：未审核，1：拒绝，2：批准）',
  `customer_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '客户ID',
  `invoice_date` date NULL DEFAULT NULL COMMENT '发票日期',
  `total` decimal(15, 4) NOT NULL DEFAULT 0.0000 COMMENT '总计',
  `total_tax` decimal(15, 4) NOT NULL COMMENT '税总计',
  `total_no_tax` decimal(15, 4) NOT NULL DEFAULT 0.0000 COMMENT '不含税总计',
  `sales_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '销售员',
  `description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `remark` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `review_info` longtext CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '审核信息',
  `attach_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '附件名称',
  `attach_path` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '附件路径',
  `release_time` datetime(0) NULL DEFAULT NULL COMMENT '发布时间',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `forein_total` decimal(15, 4) NOT NULL DEFAULT 0.0000 COMMENT '外币总计',
  `forein_total_tax` decimal(15, 4) NOT NULL DEFAULT 0.0000 COMMENT '外币税总计',
  `forein_total_no_tax` decimal(15, 4) NOT NULL DEFAULT 0.0000 COMMENT '外币不含税总计',
  `currency` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '币种',
  `flow_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '流程ID',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '销售发票' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_sale_invoice_items
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_sale_invoice_items`;
CREATE TABLE `oa_erp_sale_invoice_items`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '发票ID',
  `order_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '订单ID',
  `order_date` date NULL DEFAULT NULL COMMENT '订单日期',
  `order_number` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '订单号',
  `order_item_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '订单项ID',
  `currency` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'CNY' COMMENT '币种',
  `currency_rate` decimal(9, 2) NOT NULL DEFAULT 1.00 COMMENT '货币汇率',
  `qty` decimal(9, 2) NULL DEFAULT NULL COMMENT '数量',
  `price` decimal(15, 4) NULL DEFAULT NULL COMMENT '价格',
  `price_tax` tinyint(1) NOT NULL DEFAULT 1 COMMENT '价格是否含税',
  `code` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料号',
  `name` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料名称',
  `description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料描述',
  `remark` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `unit` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '个' COMMENT '单位',
  `tax_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '税ID',
  `tax_name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '税名称',
  `tax_rate` decimal(9, 3) NULL DEFAULT NULL COMMENT '税率',
  `total` decimal(15, 4) NOT NULL DEFAULT 0.0000 COMMENT '总计',
  `total_tax` decimal(15, 4) NOT NULL DEFAULT 0.0000 COMMENT '税总计',
  `total_no_tax` decimal(15, 4) NOT NULL DEFAULT 0.0000 COMMENT '不含税总计',
  `forein_total` decimal(15, 4) NOT NULL DEFAULT 0.0000 COMMENT '外币总计',
  `forein_total_tax` decimal(15, 4) NOT NULL DEFAULT 0.0000 COMMENT '外币税总计',
  `forein_total_no_tax` decimal(15, 4) NOT NULL DEFAULT 0.0000 COMMENT '外币不含税总计',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '销售发票 - 发票项' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_sale_order
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_sale_order`;
CREATE TABLE `oa_erp_sale_order`  (
  `id` int(1) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company` tinyint(1) NOT NULL DEFAULT 0 COMMENT '下单方',
  `hand` tinyint(1) NOT NULL DEFAULT 0 COMMENT '手动补单',
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '启用',
  `state` tinyint(1) NOT NULL DEFAULT 0 COMMENT '订单状态（0：未审核，1：拒绝，2：批准）',
  `number` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '订单号',
  `type` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '类型',
  `currency` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '币种',
  `total` decimal(15, 2) NOT NULL DEFAULT 0.00 COMMENT '总金额',
  `customer_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '客户ID',
  `payment_id` int(10) NOT NULL DEFAULT 3 COMMENT '付款方式ID（账期）',
  `payment_days` int(10) UNSIGNED NOT NULL DEFAULT 45 COMMENT '账期',
  `customer_contact_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '客户联系人ID',
  `customer_address_code` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '客户地址简码',
  `order_date` date NULL DEFAULT NULL COMMENT '订单日期',
  `request_date` date NULL DEFAULT NULL COMMENT '要求日期',
  `description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `remark` varchar(300) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `responsible` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '质保期',
  `tpl_id` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT '合同模板ID',
  `release_time` datetime(0) NULL DEFAULT NULL COMMENT '发布时间',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `review_info` longtext CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '审核日志',
  `type_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '销售类别ID',
  `currency_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '币种ID',
  `currency_rate` float NOT NULL DEFAULT 1 COMMENT '币种汇率',
  `sales_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '销售员ID',
  `settle_way` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '结算方式',
  `delvery_clause` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '交货条款',
  `attach_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '附件文件名',
  `attach_path` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '附件文件路径',
  `tax_id` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT '税率ID',
  `tax_name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '增值税发票' COMMENT '税率名称',
  `tax_rate` decimal(5, 2) NOT NULL DEFAULT 0.00 COMMENT '税率',
  `total_tax` decimal(15, 2) NOT NULL DEFAULT 0.00 COMMENT '税金',
  `total_no_tax` decimal(15, 2) NOT NULL DEFAULT 0.00 COMMENT '不含税金额',
  `price_tax` tinyint(1) NOT NULL DEFAULT 0 COMMENT '价格含税',
  `forein_total` decimal(15, 2) NOT NULL DEFAULT 0.00 COMMENT '外币总金额',
  `forein_total_tax` decimal(15, 2) NOT NULL DEFAULT 0.00 COMMENT '外币税金',
  `forein_total_no_tax` decimal(15, 2) NOT NULL COMMENT '外币不含税金额',
  `transfer_description` varchar(1000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '变更说明',
  `transfer_id` int(10) NULL DEFAULT NULL,
  `submit_type` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'new',
  `closed` tinyint(1) NOT NULL DEFAULT 0,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  `order_status` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '订单状态',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `index_id`(`id`) USING BTREE,
  INDEX `index_company`(`company`) USING BTREE,
  INDEX `index_active`(`active`) USING BTREE,
  INDEX `index_state`(`state`) USING BTREE,
  INDEX `index_number`(`number`) USING BTREE,
  INDEX `index_transfer_id`(`transfer_id`) USING BTREE,
  INDEX `index_submit_type`(`submit_type`) USING BTREE,
  INDEX `index_deleted`(`deleted`) USING BTREE,
  INDEX `index_closed`(`closed`) USING BTREE,
  INDEX `index_supplier_id`(`customer_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 6 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '销售订单' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_sale_order_items
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_sale_order_items`;
CREATE TABLE `oa_erp_sale_order_items`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '订单ID',
  `delivery_date` date NULL DEFAULT NULL COMMENT '预计交期',
  `request_date` date NULL DEFAULT NULL COMMENT '需求日期',
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '启用',
  `code` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料号',
  `type` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '类别（内部型号/物料号）',
  `name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料名称',
  `description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料描述',
  `remark` varchar(300) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料备注',
  `price` decimal(16, 8) NULL DEFAULT NULL COMMENT '单价',
  `qty` decimal(9, 2) NOT NULL DEFAULT 0.00 COMMENT '数量',
  `unit` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '单位',
  `total` decimal(15, 4) NULL DEFAULT NULL COMMENT '行总计',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` date NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` date NULL DEFAULT NULL COMMENT '更新时间',
  `delivery_date_remark` longtext CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '交期备注',
  `delivery_date_update_time` datetime(0) NULL DEFAULT NULL COMMENT '交期回复更新时间',
  `customer_code` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '客户产品型号',
  `customer_description` varchar(300) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '客户产品描述',
  `product_type` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '产品类别',
  `product_series` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '产品系列',
  `price_tax` tinyint(1) NOT NULL DEFAULT 0 COMMENT '价格含税',
  `code_internal` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '内部型号（产品中心型号需维护该型号获取库存）',
  `sales_remark` varchar(300) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '销售备注',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `index_id`(`id`) USING BTREE,
  INDEX `index_order_id`(`order_id`) USING BTREE,
  INDEX `index_active`(`active`) USING BTREE,
  INDEX `index_code`(`code`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 6 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '销售订单-订单项' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_sale_order_items_transfer
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_sale_order_items_transfer`;
CREATE TABLE `oa_erp_sale_order_items_transfer`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '订单ID',
  `delivery_date` date NULL DEFAULT NULL COMMENT '预计交期',
  `request_date` date NULL DEFAULT NULL COMMENT '需求日期',
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '启用',
  `code` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料号',
  `type` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料类别',
  `name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料名称',
  `description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料描述',
  `remark` varchar(300) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料备注',
  `price` decimal(16, 8) NULL DEFAULT NULL COMMENT '单价',
  `qty` decimal(9, 2) NOT NULL DEFAULT 0.00 COMMENT '数量',
  `unit` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '单位',
  `total` decimal(15, 4) NULL DEFAULT NULL COMMENT '行总计',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` date NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` date NULL DEFAULT NULL COMMENT '更新时间',
  `delivery_date_remark` longtext CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '交期备注',
  `transfer_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '订单ID',
  `transfer_type` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'add' COMMENT '变更类别',
  `order_item_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '订单项ID',
  `customer_code` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '客户产品型号',
  `customer_description` varchar(300) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '客户产品描述',
  `product_type` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '产品类别',
  `product_series` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '产品系列',
  `price_tax` tinyint(1) NOT NULL DEFAULT 0 COMMENT '价格含税',
  `code_internal` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '内部型号（产品中心型号需维护该型号获取库存）',
  `sales_remark` varchar(300) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '销售备注',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `index_id`(`id`) USING BTREE,
  INDEX `index_order_id`(`order_id`) USING BTREE,
  INDEX `index_active`(`active`) USING BTREE,
  INDEX `index_code`(`code`) USING BTREE,
  INDEX `index_transfer_id`(`transfer_id`) USING BTREE,
  INDEX `index_transfer_type`(`transfer_type`) USING BTREE,
  INDEX `index_order_item_id`(`order_item_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 2 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '销售订单-订单项' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_sale_order_status
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_sale_order_status`;
CREATE TABLE `oa_erp_sale_order_status`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `create_user` int(10) UNSIGNED NOT NULL,
  `create_time` datetime(0) NOT NULL,
  `update_user` int(10) UNSIGNED NOT NULL,
  `update_time` datetime(0) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '销售订单状态' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_sale_price
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_sale_price`;
CREATE TABLE `oa_erp_sale_price`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `number` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '编号',
  `customer_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '客户ID',
  `currency_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '币种ID',
  `currency` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '币种',
  `tax_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '税ID',
  `tax_rate` decimal(9, 2) NOT NULL DEFAULT 0.00 COMMENT '税率',
  `description` varchar(300) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `remark` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `review_info` longtext CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '审核日志',
  `reviewer` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '审核人',
  `release_time` datetime(0) NULL DEFAULT NULL COMMENT '发布时间',
  `attach_name` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '附件名称',
  `attach_path` varchar(300) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '附件路径',
  `state` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态',
  `price_tax` tinyint(1) NOT NULL DEFAULT 0 COMMENT '价格含税',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '销售价格申请' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_sale_price_items
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_sale_price_items`;
CREATE TABLE `oa_erp_sale_price_items`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `price_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '价格ID',
  `code` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '产品型号',
  `qty` decimal(9, 2) NULL DEFAULT NULL COMMENT '数量',
  `price_start` decimal(9, 2) NULL DEFAULT NULL COMMENT '初始价格',
  `price_final` decimal(9, 2) NULL DEFAULT NULL COMMENT '最终价格',
  `remark` varchar(300) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `customer_code` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '客户型号',
  `customer_codename` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '客户型号名称',
  `customer_description` varchar(300) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '客户型号描述',
  `name` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '名称',
  `description` varchar(300) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `active_date` date NULL DEFAULT NULL COMMENT '生效日期',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '销售价格-项目' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_sale_price_items_ladder
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_sale_price_items_ladder`;
CREATE TABLE `oa_erp_sale_price_items_ladder`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id` int(10) UNSIGNED NOT NULL,
  `qty` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '数量',
  `price_start` decimal(16, 8) NULL DEFAULT NULL COMMENT '初始价格',
  `remark` varchar(300) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `price_final` decimal(16, 8) NULL DEFAULT NULL COMMENT '最终价格',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 2 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '阶梯价' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_sale_sales
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_sale_sales`;
CREATE TABLE `oa_erp_sale_sales`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '用户ID',
  `tel` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '电话',
  `fax` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '传真',
  `remark` varchar(300) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '启用',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 10 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '销售员' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_sale_sales_work
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_sale_sales_work`;
CREATE TABLE `oa_erp_sale_sales_work`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sales_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '销售员ID',
  `type_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '销售类别ID',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 7 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '销售员分工' ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for oa_erp_sale_type
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_sale_type`;
CREATE TABLE `oa_erp_sale_type`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '启用',
  `code` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '代码',
  `name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '名称',
  `description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `remark` varchar(300) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `flow_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单流程ID',
  `tpl_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '模板ID',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 3 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '销售类别' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_setting_currency
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_setting_currency`;
CREATE TABLE `oa_erp_setting_currency`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `active` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '1' COMMENT '启用',
  `default` tinyint(1) NOT NULL DEFAULT 0 COMMENT '本币',
  `code` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '简写（RMB、CNY...）',
  `symbol` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '符号',
  `name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '名称',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 3 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '货币' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_setting_currency_rate
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_setting_currency_rate`;
CREATE TABLE `oa_erp_setting_currency_rate`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `currency_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '货币ID',
  `rate` float NULL DEFAULT NULL COMMENT '汇率',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `date` date NULL DEFAULT NULL COMMENT '日期从',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 3 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '货币汇率' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_setting_tax
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_setting_tax`;
CREATE TABLE `oa_erp_setting_tax`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `active` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '1' COMMENT '启用',
  `default` tinyint(1) NOT NULL DEFAULT 0 COMMENT '默认',
  `code` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '简写',
  `name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '名称',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 6 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '税' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_setting_tax_rate
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_setting_tax_rate`;
CREATE TABLE `oa_erp_setting_tax_rate`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tax_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '税ID',
  `rate` float NULL DEFAULT NULL COMMENT '汇率',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `date` date NULL DEFAULT NULL COMMENT '生效日期',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 11 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '税率' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_stock
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_stock`;
CREATE TABLE `oa_erp_stock`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料号',
  `warehouse_code` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '仓库号',
  `qty` decimal(9, 2) NOT NULL DEFAULT 0.00 COMMENT '数量',
  `total` decimal(9, 2) NOT NULL DEFAULT 0.00 COMMENT '金额',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `doc_number` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '单据号',
  `doc_type` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '单据类别',
  `transaction_type` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '库存' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_stock_receive
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_stock_receive`;
CREATE TABLE `oa_erp_stock_receive`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '采购收货' COMMENT '交易类别（采购收货/库存交易收货/生产收货）',
  `order_id` int(10) NULL DEFAULT NULL,
  `date` date NULL DEFAULT NULL COMMENT '日期',
  `total` decimal(15, 2) NOT NULL DEFAULT 0.00 COMMENT '金额',
  `description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `remark` varchar(300) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `number` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '收货单号',
  `review_info` longtext CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '日志',
  `transaction_type` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '收货记录' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_stock_receive_items
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_stock_receive_items`;
CREATE TABLE `oa_erp_stock_receive_items`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `receive_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '交易ID',
  `code` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料号',
  `qty` decimal(9, 2) NOT NULL DEFAULT 0.00 COMMENT '数量',
  `price` decimal(15, 4) NULL DEFAULT NULL COMMENT '价格',
  `total` decimal(15, 4) NULL DEFAULT NULL COMMENT '行总计',
  `description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料描述',
  `remark` varchar(300) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `name` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料名称',
  `warehouse_code` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '仓库代码',
  `warehouse_code_transfer` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '仓库代码至',
  `unit` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '单位',
  `order_number` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '采购订单号',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '收货-项' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_stock_receive_items_order
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_stock_receive_items_order`;
CREATE TABLE `oa_erp_stock_receive_items_order`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `receive_item_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '收货单项ID',
  `order_item_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '采购订单项ID',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `qty` decimal(9, 2) NULL DEFAULT NULL COMMENT '数量（负数用于调整未清数量）',
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '启用',
  `price` decimal(9, 2) NOT NULL DEFAULT 0.00 COMMENT '单价',
  `total` decimal(9, 2) NOT NULL DEFAULT 0.00 COMMENT '总计',
  `code` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料代码',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `order_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '订单ID',
  `locked` tinyint(1) NOT NULL DEFAULT 0 COMMENT '锁定',
  `order_number` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '订单号',
  `type` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '采购收货' COMMENT '单据类别（收货 / 退货）',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '订单项对应申请ID' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_stock_receive_items_order_sale
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_stock_receive_items_order_sale`;
CREATE TABLE `oa_erp_stock_receive_items_order_sale`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `receive_item_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '交货单项ID',
  `order_item_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '销售订单项ID',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `qty` decimal(9, 2) NULL DEFAULT NULL COMMENT '数量（负数用于调整未清数量）',
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '启用',
  `price` decimal(16, 8) NOT NULL DEFAULT 0.00000000 COMMENT '单价',
  `total` decimal(9, 2) NOT NULL DEFAULT 0.00 COMMENT '总计',
  `code` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料代码',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `order_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '订单ID',
  `locked` tinyint(1) NOT NULL DEFAULT 0 COMMENT '锁定',
  `order_number` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '订单号',
  `type` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '销售交货' COMMENT '单据类别（交货 / 退货）',
  `customer_code` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `customer_description` varchar(300) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `product_code` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '内部型号对应的销售产品型号',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `index_id`(`id`) USING BTREE,
  INDEX `index_order_item_id`(`order_item_id`) USING BTREE,
  INDEX `index_active`(`active`) USING BTREE,
  INDEX `index_code`(`code`) USING BTREE,
  INDEX `index_order_id`(`order_id`) USING BTREE,
  INDEX `index_order_number`(`order_number`) USING BTREE,
  INDEX `index_type`(`type`) USING BTREE,
  INDEX `index_receive_item_id`(`receive_item_id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 7 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '订单项对应申请ID' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_stock_transfer_item_batch
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_stock_transfer_item_batch`;
CREATE TABLE `oa_erp_stock_transfer_item_batch`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '交易ID',
  `number` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '批次号',
  `description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `remark` varchar(300) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `qty` float NOT NULL DEFAULT 0 COMMENT '数量',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '库存交易批次号' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_tpl
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_tpl`;
CREATE TABLE `oa_erp_tpl`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '启用',
  `type` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '类别',
  `name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '名称',
  `html` longtext CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '模板脚本',
  `description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `remark` varchar(300) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 13 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '模板' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_warehouse
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_warehouse`;
CREATE TABLE `oa_erp_warehouse`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `in_stock` tinyint(1) NOT NULL DEFAULT 1 COMMENT '库存',
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '启用',
  `locked` tinyint(1) NOT NULL DEFAULT 0 COMMENT '锁定',
  `type_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '类别ID',
  `code` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '代码',
  `name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '名称',
  `description` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建人',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新人',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '仓库管理' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_warehouse_receiver
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_warehouse_receiver`;
CREATE TABLE `oa_erp_warehouse_receiver`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `active` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '1' COMMENT '启用',
  `address` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '地址',
  `tel` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '电话',
  `fax` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '传真',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `user_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '收货人',
  `address_en` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '英文地址',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 2 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '库房收货地址' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_warehouse_transaction
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_warehouse_transaction`;
CREATE TABLE `oa_erp_warehouse_transaction`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `active` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '启用',
  `name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '名称',
  `description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `remark` varchar(300) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新人',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '仓库类别' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_erp_warehouse_type
-- ----------------------------
DROP TABLE IF EXISTS `oa_erp_warehouse_type`;
CREATE TABLE `oa_erp_warehouse_type`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `active` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '启用',
  `code` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '代码',
  `name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '名称',
  `description` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新人',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 4 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '仓库类别' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_form_item
-- ----------------------------
DROP TABLE IF EXISTS `oa_form_item`;
CREATE TABLE `oa_form_item`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '类别',
  `state` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态',
  `requisite` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '必需',
  `length` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT '长度',
  `default` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '默认值',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '智能表单内容' ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for oa_form_type
-- ----------------------------
DROP TABLE IF EXISTS `oa_form_type`;
CREATE TABLE `oa_form_type`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '名称',
  `description` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `state` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '智能表单' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_log_doc
-- ----------------------------
DROP TABLE IF EXISTS `oa_log_doc`;
CREATE TABLE `oa_log_doc`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '操作类别',
  `doc_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '文件ID',
  `time` datetime(0) NULL DEFAULT NULL COMMENT '时间',
  `user_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '用户ID',
  `ip` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'IP地址',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '文件查看/下载日志' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_log_mail
-- ----------------------------
DROP TABLE IF EXISTS `oa_log_mail`;
CREATE TABLE `oa_log_mail`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '类别',
  `subject` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '主题',
  `to` varchar(3000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '接收人',
  `cc` varchar(1000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '抄送人',
  `content` longtext CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '内容',
  `attachment_name` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '附件mc名称（多个逗号分隔）',
  `attachment_path` varchar(1000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '附件路径（多个逗号分隔）',
  `send_time` datetime(0) NULL DEFAULT NULL COMMENT '发送时间',
  `err_info` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '错误信息',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `state` tinyint(1) NOT NULL DEFAULT 0 COMMENT '发送状态',
  `add_date` date NULL DEFAULT NULL COMMENT '添加日期（用于判断是否过期）',
  `key` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '校验码',
  `user_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '用户ID',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 65695 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '系统邮件日志' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_log_msg
-- ----------------------------
DROP TABLE IF EXISTS `oa_log_msg`;
CREATE TABLE `oa_log_msg`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '标题',
  `priority` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '优先级',
  `content` longtext CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '内容',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `receivers` varchar(1000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '接收人',
  `email` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否发送邮件',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 10 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '消息日志' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_log_msg_reply
-- ----------------------------
DROP TABLE IF EXISTS `oa_log_msg_reply`;
CREATE TABLE `oa_log_msg_reply`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `msg_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '消息ID',
  `user_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '用户ID',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `content` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '回复内容',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '消息-回复' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_log_msg_send
-- ----------------------------
DROP TABLE IF EXISTS `oa_log_msg_send`;
CREATE TABLE `oa_log_msg_send`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `msg_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '消息ID',
  `user_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '发送对象',
  `view` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否查看',
  `view_time` datetime(0) NULL DEFAULT NULL COMMENT '查看时间',
  `email` tinyint(1) NOT NULL DEFAULT 0 COMMENT '发送邮件',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 10 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '消息日志-发送信息' ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for oa_log_operate
-- ----------------------------
DROP TABLE IF EXISTS `oa_log_operate`;
CREATE TABLE `oa_log_operate`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '用户ID',
  `operate` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '操作',
  `target` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '对象',
  `time` datetime(0) NULL DEFAULT NULL COMMENT '时间',
  `ip` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '客户端IP',
  `computer_name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '客户端计算机名称',
  `remark` varchar(1000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `params` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '参数',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 118846 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '操作日志' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_log_sql
-- ----------------------------
DROP TABLE IF EXISTS `oa_log_sql`;
CREATE TABLE `oa_log_sql`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sql` longtext CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL COMMENT '查询语句',
  `time` datetime(0) NULL DEFAULT NULL COMMENT '时间',
  `user_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '用户ID',
  `ip` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL COMMENT 'IP地址',
  `page_name` varchar(200) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL COMMENT '页面文件名称',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '查询日志' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_meeting
-- ----------------------------
DROP TABLE IF EXISTS `oa_meeting`;
CREATE TABLE `oa_meeting`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `state` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '0' COMMENT '状态（0：开启，1：结束,2：取消）',
  `number` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '会议编号',
  `public` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否公开',
  `room_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '会议室ID',
  `members_ename` varchar(5000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `members_cname` varchar(5000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `members` varchar(2000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '参会人员',
  `subject` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '主题',
  `moderator` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '主持人',
  `time_from` datetime(0) NULL DEFAULT NULL COMMENT '开始时间',
  `time_to` datetime(0) NULL DEFAULT NULL COMMENT '结束时间',
  `remark` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `mom` longtext CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '会议纪要',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 82 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '会议' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_meeting_room
-- ----------------------------
DROP TABLE IF EXISTS `oa_meeting_room`;
CREATE TABLE `oa_meeting_room`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '启用',
  `name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '名称',
  `projector` tinyint(1) NOT NULL DEFAULT 1 COMMENT '投影仪',
  `tel` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '电话',
  `qty` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT '容纳人数',
  `remark` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 4 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '会议室' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_menu
-- ----------------------------
DROP TABLE IF EXISTS `oa_menu`;
CREATE TABLE `oa_menu`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '上级菜单ID',
  `order` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '显示顺序',
  `iconCls` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '图标',
  `tooltip` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '提示',
  `text` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '文字',
  `handler` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '处理函数',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `disabled` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否禁用',
  `url` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '外部链接',
  `params` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '参数',
  `create_user` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT '创建人',
  `update_user` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT '更新人',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 131 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '菜单' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_menu_role
-- ----------------------------
DROP TABLE IF EXISTS `oa_menu_role`;
CREATE TABLE `oa_menu_role`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `menu_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '菜单ID',
  `role_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '角色ID',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 354 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '菜单权限设置（角色）' ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for oa_news
-- ----------------------------
DROP TABLE IF EXISTS `oa_news`;
CREATE TABLE `oa_news`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '类别ID',
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态',
  `public` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否公开',
  `title` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '标题',
  `subhead` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '副标题',
  `content` longtext CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '内容',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `summary` varchar(1000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '摘要',
  `keywords` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '关键词',
  `deleted` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否删除',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 44 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '新闻内容' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_news_comment
-- ----------------------------
DROP TABLE IF EXISTS `oa_news_comment`;
CREATE TABLE `oa_news_comment`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL COMMENT '用户ID',
  `comment` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '评论',
  `anonymity` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否匿名',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `news_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '公告ID',
  `public` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态（1：可见，0：不可见）',
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  `update_user` int(10) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 2 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '新闻评论' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_news_type
-- ----------------------------
DROP TABLE IF EXISTS `oa_news_type`;
CREATE TABLE `oa_news_type`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态',
  `name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '名称',
  `description` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `public` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否公开',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 7 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '新闻类别' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_product_bom_config
-- ----------------------------
DROP TABLE IF EXISTS `oa_product_bom_config`;
CREATE TABLE `oa_product_bom_config`  (
  `type` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '类型',
  `flow` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `form` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`type`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_product_bom_fa
-- ----------------------------
DROP TABLE IF EXISTS `oa_product_bom_fa`;
CREATE TABLE `oa_product_bom_fa`  (
  `sid` int(11) NOT NULL AUTO_INCREMENT,
  `nid` int(10) NULL DEFAULT NULL COMMENT '物料评审ID',
  `recordkey` int(10) NULL DEFAULT NULL COMMENT '标示key',
  `id` int(10) NULL DEFAULT NULL COMMENT '物料ID',
  `code` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料号',
  `project_no` int(10) NULL DEFAULT NULL COMMENT '产品型号',
  `bom_file` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '关联文件号',
  `qty` int(8) NULL DEFAULT NULL COMMENT '数量',
  `state` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '状态',
  `ver` varchar(4) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '版本',
  `type` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'BOM类型',
  `remark` varchar(2000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `bom_upd_time` datetime(0) NULL DEFAULT NULL,
  `create_user` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`sid`) USING BTREE,
  UNIQUE INDEX `index_recordkey_code`(`recordkey`, `code`) USING BTREE,
  INDEX `index_code`(`code`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 5397 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_product_bom_fa_copy
-- ----------------------------
DROP TABLE IF EXISTS `oa_product_bom_fa_copy`;
CREATE TABLE `oa_product_bom_fa_copy`  (
  `sid` int(11) NOT NULL AUTO_INCREMENT,
  `nid` int(10) NULL DEFAULT NULL COMMENT '物料评审ID',
  `recordkey` int(10) NULL DEFAULT NULL COMMENT '标示key',
  `id` int(10) NULL DEFAULT NULL COMMENT '物料ID',
  `code` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料号',
  `project_no` int(10) NULL DEFAULT NULL COMMENT '产品型号',
  `bom_file` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '关联文件号',
  `qty` int(8) NULL DEFAULT NULL COMMENT '数量',
  `state` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '状态',
  `ver` varchar(4) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '版本',
  `type` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'BOM类型',
  `remark` varchar(2000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `bom_upd_time` datetime(0) NULL DEFAULT NULL,
  PRIMARY KEY (`sid`) USING BTREE,
  UNIQUE INDEX `index_recordkey_code`(`recordkey`, `code`) USING BTREE,
  INDEX `index_code`(`code`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2218 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_product_bom_fa_copy1
-- ----------------------------
DROP TABLE IF EXISTS `oa_product_bom_fa_copy1`;
CREATE TABLE `oa_product_bom_fa_copy1`  (
  `sid` int(11) NOT NULL AUTO_INCREMENT,
  `nid` int(10) NULL DEFAULT NULL COMMENT '物料评审ID',
  `recordkey` int(10) NULL DEFAULT NULL COMMENT '标示key',
  `id` int(10) NULL DEFAULT NULL COMMENT '物料ID',
  `code` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料号',
  `project_no` int(10) NULL DEFAULT NULL COMMENT '产品型号',
  `bom_file` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '关联文件号',
  `qty` int(8) NULL DEFAULT NULL COMMENT '数量',
  `state` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '状态',
  `ver` varchar(4) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '版本',
  `type` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'BOM类型',
  `remark` varchar(2000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `bom_upd_time` datetime(0) NULL DEFAULT NULL,
  `create_user` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`sid`) USING BTREE,
  UNIQUE INDEX `index_recordkey_code`(`recordkey`, `code`) USING BTREE,
  INDEX `index_code`(`code`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4746 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_product_bom_fa_dev
-- ----------------------------
DROP TABLE IF EXISTS `oa_product_bom_fa_dev`;
CREATE TABLE `oa_product_bom_fa_dev`  (
  `sid` int(11) NOT NULL AUTO_INCREMENT,
  `nid` int(10) NULL DEFAULT NULL COMMENT '物料评审ID',
  `recordkey` int(10) NULL DEFAULT NULL COMMENT '标示key',
  `id` int(10) NULL DEFAULT NULL COMMENT '物料ID',
  `code` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料号',
  `project_no` int(10) NULL DEFAULT NULL COMMENT '产品型号',
  `bom_file` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '关联文件号',
  `qty` int(8) NULL DEFAULT NULL COMMENT '数量',
  `state` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '状态',
  `ver` varchar(4) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '版本',
  `type` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'BOM类型',
  `remark` varchar(2000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`sid`) USING BTREE,
  INDEX `index_nid`(`nid`) USING BTREE,
  INDEX `index_id`(`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 137583 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_product_bom_fa_dev_copy
-- ----------------------------
DROP TABLE IF EXISTS `oa_product_bom_fa_dev_copy`;
CREATE TABLE `oa_product_bom_fa_dev_copy`  (
  `sid` int(11) NOT NULL AUTO_INCREMENT,
  `nid` int(10) NULL DEFAULT NULL COMMENT '物料评审ID',
  `recordkey` int(10) NULL DEFAULT NULL COMMENT '标示key',
  `id` int(10) NULL DEFAULT NULL COMMENT '物料ID',
  `code` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料号',
  `project_no` int(10) NULL DEFAULT NULL COMMENT '产品型号',
  `bom_file` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '关联文件号',
  `qty` int(8) NULL DEFAULT NULL COMMENT '数量',
  `state` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '状态',
  `ver` varchar(4) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '版本',
  `type` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'BOM类型',
  `remark` varchar(2000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`sid`) USING BTREE,
  INDEX `index_nid`(`nid`) USING BTREE,
  INDEX `index_id`(`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 12335 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_product_bom_new
-- ----------------------------
DROP TABLE IF EXISTS `oa_product_bom_new`;
CREATE TABLE `oa_product_bom_new`  (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `ver` varchar(3) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '版本',
  `description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `state` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_time` datetime(0) NULL DEFAULT NULL,
  `create_user` int(10) NULL DEFAULT NULL,
  `update_time` datetime(0) NULL DEFAULT NULL,
  `update_user` int(11) NULL DEFAULT NULL,
  `archive_time` datetime(0) NULL DEFAULT NULL COMMENT '发布时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1738 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_product_bom_price
-- ----------------------------
DROP TABLE IF EXISTS `oa_product_bom_price`;
CREATE TABLE `oa_product_bom_price`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `recordkey` int(10) NULL DEFAULT NULL,
  `code` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `ver` varchar(5) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `low_cny` decimal(14, 4) NULL DEFAULT NULL,
  `low_usd` decimal(14, 4) NULL DEFAULT NULL,
  `high_cny` decimal(14, 4) NULL DEFAULT NULL,
  `high_usd` decimal(14, 4) NULL DEFAULT NULL,
  `average_cny` decimal(14, 4) NULL DEFAULT NULL,
  `average_usd` decimal(14, 4) NULL DEFAULT NULL,
  `mid` int(11) NULL DEFAULT NULL,
  `project_no` int(11) NULL DEFAULT NULL,
  `state` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `update_time` datetime(0) NULL DEFAULT NULL,
  `noprice` tinyint(1) NULL DEFAULT NULL COMMENT '是否有物料无价格（0 无 1 有）',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `bomprice_recordkey`(`recordkey`) USING BTREE,
  UNIQUE INDEX `bomprice_code_ver`(`code`, `ver`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 14204 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_product_bom_role
-- ----------------------------
DROP TABLE IF EXISTS `oa_product_bom_role`;
CREATE TABLE `oa_product_bom_role`  (
  `bom_id` int(10) NOT NULL COMMENT 'BOM ID',
  `employee_id` int(10) NOT NULL COMMENT '员工ID',
  `relation` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '关系（为什么有此权限）',
  `create_time` datetime(0) NULL DEFAULT NULL,
  `create_user` int(10) NULL DEFAULT NULL,
  PRIMARY KEY (`bom_id`, `employee_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = 'BOM权限' ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_product_bom_son
-- ----------------------------
DROP TABLE IF EXISTS `oa_product_bom_son`;
CREATE TABLE `oa_product_bom_son`  (
  `sid` int(10) NOT NULL AUTO_INCREMENT,
  `nid` int(11) NULL DEFAULT NULL,
  `recordkey` int(10) NULL DEFAULT NULL COMMENT '标示key',
  `pid` int(10) NULL DEFAULT NULL COMMENT '父BOM id',
  `id` int(10) NULL DEFAULT NULL COMMENT '物料ID',
  `code` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料编码',
  `qty` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '数量',
  `partposition` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '器件位置',
  `replace` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '替代料',
  `remark` varchar(2000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  PRIMARY KEY (`sid`) USING BTREE,
  INDEX `index_code`(`code`) USING BTREE,
  INDEX `index_recordkey_code`(`recordkey`, `code`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 76213 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_product_bom_son_copy
-- ----------------------------
DROP TABLE IF EXISTS `oa_product_bom_son_copy`;
CREATE TABLE `oa_product_bom_son_copy`  (
  `sid` int(10) NOT NULL AUTO_INCREMENT,
  `nid` int(11) NULL DEFAULT NULL,
  `recordkey` int(10) NULL DEFAULT NULL COMMENT '标示key',
  `pid` int(10) NULL DEFAULT NULL COMMENT '父BOM id',
  `id` int(10) NULL DEFAULT NULL COMMENT '物料ID',
  `code` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料编码',
  `qty` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '数量',
  `partposition` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '器件位置',
  `replace` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '替代料',
  `remark` varchar(2000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  PRIMARY KEY (`sid`) USING BTREE,
  INDEX `index_code`(`code`) USING BTREE,
  INDEX `index_recordkey_code`(`recordkey`, `code`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 35433 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_product_bom_son_copy1
-- ----------------------------
DROP TABLE IF EXISTS `oa_product_bom_son_copy1`;
CREATE TABLE `oa_product_bom_son_copy1`  (
  `sid` int(10) NOT NULL AUTO_INCREMENT,
  `nid` int(11) NULL DEFAULT NULL,
  `recordkey` int(10) NULL DEFAULT NULL COMMENT '标示key',
  `pid` int(10) NULL DEFAULT NULL COMMENT '父BOM id',
  `id` int(10) NULL DEFAULT NULL COMMENT '物料ID',
  `code` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料编码',
  `qty` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '数量',
  `partposition` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '器件位置',
  `replace` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '替代料',
  `remark` varchar(2000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  PRIMARY KEY (`sid`) USING BTREE,
  INDEX `index_code`(`code`) USING BTREE,
  INDEX `index_recordkey_code`(`recordkey`, `code`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 67075 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_product_bom_son_dev
-- ----------------------------
DROP TABLE IF EXISTS `oa_product_bom_son_dev`;
CREATE TABLE `oa_product_bom_son_dev`  (
  `sid` int(10) NOT NULL AUTO_INCREMENT,
  `nid` int(11) NULL DEFAULT NULL,
  `recordkey` int(10) NULL DEFAULT NULL COMMENT '标示key',
  `pid` int(10) NULL DEFAULT NULL COMMENT '父BOM id',
  `id` int(10) NULL DEFAULT NULL COMMENT '物料ID',
  `code` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料编码',
  `qty` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '数量',
  `partposition` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '器件位置',
  `replace` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '替代料',
  `remark` varchar(2000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  PRIMARY KEY (`sid`) USING BTREE,
  INDEX `index_nid`(`nid`) USING BTREE,
  INDEX `index_id`(`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 891232 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_product_bom_son_dev_copy
-- ----------------------------
DROP TABLE IF EXISTS `oa_product_bom_son_dev_copy`;
CREATE TABLE `oa_product_bom_son_dev_copy`  (
  `sid` int(10) NOT NULL AUTO_INCREMENT,
  `nid` int(11) NULL DEFAULT NULL,
  `recordkey` int(10) NULL DEFAULT NULL COMMENT '标示key',
  `pid` int(10) NULL DEFAULT NULL COMMENT '父BOM id',
  `id` int(10) NULL DEFAULT NULL COMMENT '物料ID',
  `code` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料编码',
  `qty` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '数量',
  `partposition` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '器件位置',
  `replace` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '替代料',
  `remark` varchar(2000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  PRIMARY KEY (`sid`) USING BTREE,
  INDEX `index_nid`(`nid`) USING BTREE,
  INDEX `index_id`(`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 84123 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_product_bom_type
-- ----------------------------
DROP TABLE IF EXISTS `oa_product_bom_type`;
CREATE TABLE `oa_product_bom_type`  (
  `id` int(10) NOT NULL,
  `type` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'BOM类型',
  `display` tinyint(4) NULL DEFAULT NULL COMMENT '是否显示（0：不显示 1：显示）',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_product_bom_upd
-- ----------------------------
DROP TABLE IF EXISTS `oa_product_bom_upd`;
CREATE TABLE `oa_product_bom_upd`  (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `ver` varchar(3) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '版本',
  `upd_type` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '升版类型',
  `description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '详细更改描述',
  `upd_reason` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '更改原因',
  `reason_type` int(10) NULL DEFAULT NULL COMMENT '更改原因分类',
  `replace_flg` tinyint(1) NULL DEFAULT 0 COMMENT '是否是主替代料替换(0 否 1 是)',
  `replace` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '替代物料',
  `replaced` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '被替代料',
  `state` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_time` datetime(0) NULL DEFAULT NULL,
  `create_user` int(10) NULL DEFAULT NULL,
  `update_time` datetime(0) NULL DEFAULT NULL,
  `update_user` int(11) NULL DEFAULT NULL,
  `archive_time` datetime(0) NULL DEFAULT NULL COMMENT '发布时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1795 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_product_catalog
-- ----------------------------
DROP TABLE IF EXISTS `oa_product_catalog`;
CREATE TABLE `oa_product_catalog`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `series_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '产品系列ID',
  `active` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '状态',
  `model_standard` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '标准产品型号',
  `model_internal` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '内部产品型号',
  `code` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '产品代码',
  `description` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '产品描述',
  `code_customer` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '客户代码',
  `developmode_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '产品开发模式ID',
  `stage_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '产品阶段ID',
  `date_dvt` date NULL DEFAULT NULL COMMENT 'DVT通过日期',
  `auditor_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '审核人',
  `remark` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `auditor_time` datetime(0) NULL DEFAULT NULL COMMENT '审核时间',
  `qa1_date` date NULL DEFAULT NULL,
  `qa2_date` date NULL DEFAULT NULL,
  `evt_date` date NULL DEFAULT NULL,
  `mass_production_date` date NULL DEFAULT NULL,
  `type_id` int(10) UNSIGNED NULL DEFAULT NULL,
  `delete` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否删除',
  `code_old` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '旧产品代码',
  `auditor_remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `review` tinyint(1) NOT NULL DEFAULT 0,
  `model_customer` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '客户产品型号',
  `description_customer` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '客户产品描述',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1482 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '产品目录' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_product_catalog_developmode
-- ----------------------------
DROP TABLE IF EXISTS `oa_product_catalog_developmode`;
CREATE TABLE `oa_product_catalog_developmode`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `active` tinyint(1) NULL DEFAULT NULL COMMENT '状态',
  `name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '名称',
  `description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 7 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '产品目录-开发模式' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_product_catalog_roleset
-- ----------------------------
DROP TABLE IF EXISTS `oa_product_catalog_roleset`;
CREATE TABLE `oa_product_catalog_roleset`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `catalog_id` int(10) UNSIGNED NULL DEFAULT NULL,
  `role_id` int(10) UNSIGNED NULL DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 11881 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '产品目录-角色' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_product_catalog_roleset_member
-- ----------------------------
DROP TABLE IF EXISTS `oa_product_catalog_roleset_member`;
CREATE TABLE `oa_product_catalog_roleset_member`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `roleset_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '角色ID',
  `user_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '用户ID',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 13319 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '产品目录-角色成员' ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for oa_product_catalog_series
-- ----------------------------
DROP TABLE IF EXISTS `oa_product_catalog_series`;
CREATE TABLE `oa_product_catalog_series`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `active` tinyint(1) NULL DEFAULT NULL COMMENT '状态',
  `code` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '代码',
  `name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '名称',
  `description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新用户',
  `remark` varchar(300) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 34 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '产品目录-产品系列' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_product_catalog_stage
-- ----------------------------
DROP TABLE IF EXISTS `oa_product_catalog_stage`;
CREATE TABLE `oa_product_catalog_stage`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `active` tinyint(1) NULL DEFAULT NULL COMMENT '状态',
  `name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '名称',
  `description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 10 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '产品目录-阶段' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_product_catalog_type
-- ----------------------------
DROP TABLE IF EXISTS `oa_product_catalog_type`;
CREATE TABLE `oa_product_catalog_type`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `active` tinyint(1) NULL DEFAULT NULL COMMENT '状态',
  `name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '名称',
  `description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新用户',
  `remark` varchar(300) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 6 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '产品目录-产品系列' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_product_file
-- ----------------------------
DROP TABLE IF EXISTS `oa_product_file`;
CREATE TABLE `oa_product_file`  (
  `code` varchar(45) CHARACTER SET gbk COLLATE gbk_chinese_ci NOT NULL COMMENT '物料编码',
  `file_type` varchar(20) CHARACTER SET gbk COLLATE gbk_chinese_ci NOT NULL COMMENT '文件类型',
  `file_id` int(10) NOT NULL COMMENT '文件ID',
  PRIMARY KEY (`code`, `file_type`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = gbk COLLATE = gbk_chinese_ci COMMENT = '物料相关文件' ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_product_filetype
-- ----------------------------
DROP TABLE IF EXISTS `oa_product_filetype`;
CREATE TABLE `oa_product_filetype`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `file_type` varchar(45) CHARACTER SET gbk COLLATE gbk_chinese_ci NOT NULL COMMENT '文件类型',
  `type_desc` varchar(200) CHARACTER SET gbk COLLATE gbk_chinese_ci NULL DEFAULT NULL COMMENT '类型描述',
  `show` enum('N','Y') CHARACTER SET gbk COLLATE gbk_chinese_ci NULL DEFAULT 'Y' COMMENT '是否显示',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = gbk COLLATE = gbk_chinese_ci COMMENT = '物料相关文件类型' ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_product_materiel
-- ----------------------------
DROP TABLE IF EXISTS `oa_product_materiel`;
CREATE TABLE `oa_product_materiel`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料号',
  `type` int(2) NULL DEFAULT NULL COMMENT '物料类别',
  `description` varchar(300) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料描述',
  `remark` varchar(2000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `ver` varchar(3) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '1.0' COMMENT '版本号',
  `unit` int(2) NOT NULL DEFAULT 8 COMMENT '单位',
  `state` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料状态',
  `manufacturers` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '制造商',
  `supply1` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '供应商1',
  `supply2` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '供应商2',
  `mpq` int(8) NULL DEFAULT NULL COMMENT 'MPQ 最小包装量',
  `moq` int(8) NULL DEFAULT NULL COMMENT 'MOQ 最小计划数量',
  `tod` int(8) NULL DEFAULT NULL COMMENT '标准货期',
  `data_file_id` int(10) NULL DEFAULT NULL COMMENT '数据手册',
  `tsr_id` int(10) NULL DEFAULT NULL COMMENT 'TSR',
  `first_report_id` int(10) NULL DEFAULT NULL COMMENT '首件检验报告',
  `edit_count` int(4) NULL DEFAULT NULL COMMENT '维护状态',
  `archive_time` datetime(0) NULL DEFAULT NULL COMMENT '归档日期',
  `create_user` int(10) NULL DEFAULT NULL,
  `create_time` datetime(0) NULL DEFAULT NULL,
  `update_time` datetime(0) NULL DEFAULT NULL,
  `standard_lt` int(11) NOT NULL DEFAULT 0 COMMENT '标准交期',
  `name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `type_01` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `type_02` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `apply_user` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `apply_date` date NULL DEFAULT NULL,
  `review_user` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `review_date` date NULL DEFAULT NULL,
  `release_user` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `release_date` date NULL DEFAULT NULL,
  `made_by` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `supply_by` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `update_user` int(10) NULL DEFAULT NULL COMMENT '更新用户',
  `project_no` int(10) NULL DEFAULT NULL COMMENT '产品型号ID',
  `hum_level` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `rosh` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `index_code`(`code`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 5217 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '物料编码' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_product_materiel_desc
-- ----------------------------
DROP TABLE IF EXISTS `oa_product_materiel_desc`;
CREATE TABLE `oa_product_materiel_desc`  (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `mid` int(10) NOT NULL COMMENT '物料id',
  `code` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `name_before` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '名称',
  `name_after` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `ver_before` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `ver_after` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `type_before` int(10) NULL DEFAULT NULL,
  `type_after` int(10) NULL DEFAULT NULL,
  `desc_before` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `desc_after` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `supply1_before` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `supply1_after` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `supply2_before` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `supply2_after` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `manufacturers_before` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `manufacturers_after` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `data_file_id_before` int(11) NULL DEFAULT NULL,
  `data_file_id_after` int(11) NULL DEFAULT NULL,
  `tsr_id_before` int(11) NULL DEFAULT NULL,
  `tsr_id_after` int(11) NULL DEFAULT NULL,
  `first_report_id_before` int(11) NULL DEFAULT NULL,
  `first_report_id_after` int(11) NULL DEFAULT NULL,
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `create_user` int(10) NULL DEFAULT NULL,
  `create_time` datetime(0) NULL DEFAULT NULL,
  `state` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `archive_time` datetime(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 648 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '物料变更' ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_product_materiel_price
-- ----------------------------
DROP TABLE IF EXISTS `oa_product_materiel_price`;
CREATE TABLE `oa_product_materiel_price`  (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `code` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料代码',
  `supply_code` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '供应商代码',
  `min_num` int(8) NULL DEFAULT 1 COMMENT '最小数量',
  `max_num` int(8) NULL DEFAULT 99999999 COMMENT '最大数量',
  `currency` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '币种',
  `price` decimal(8, 2) NULL DEFAULT NULL COMMENT '价格',
  `update_user` int(10) NULL DEFAULT NULL,
  `update_time` datetime(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 9 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_product_materiel_transfer
-- ----------------------------
DROP TABLE IF EXISTS `oa_product_materiel_transfer`;
CREATE TABLE `oa_product_materiel_transfer`  (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `mid` int(10) NULL DEFAULT NULL COMMENT '物料ID',
  `code` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `state` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `state_before` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '转化前状态',
  `state_after` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '转化后状态',
  `transfer_reason` varchar(2000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '转化原因',
  `remark` varchar(2000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `create_user` int(10) NULL DEFAULT NULL,
  `create_time` datetime(0) NULL DEFAULT NULL,
  `update_time` datetime(0) NULL DEFAULT NULL,
  `archive_time` datetime(0) NULL DEFAULT NULL COMMENT '转化完成时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 55 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_product_plist
-- ----------------------------
DROP TABLE IF EXISTS `oa_product_plist`;
CREATE TABLE `oa_product_plist`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sn` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'SN',
  `code` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '成品物料号',
  `step` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '状态',
  `description` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `is_bom_exists` varchar(4) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '是否有成品BOM',
  `bom_apply_time` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '成品代码申请日期',
  `bom_archive_time` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '成品BOM归档日期',
  `product_code` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'Product Code',
  `bosa` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'BOSA',
  `bosa_supply` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'bosa供应商',
  `tosa` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'TOSA',
  `tosa_supply` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'TOSA供应商',
  `rosa` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'ROSA',
  `rosa_supply` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'ROSA供应商',
  `pcb` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'PCB',
  `pcba` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'PCBA',
  `dg02` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'DG02',
  `dg01` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'DG01',
  `product_label` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '产品标签',
  `barcode_label` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '条码标签',
  `label_print_rule` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '标签打印规则',
  `tooling_model` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '工装夹具',
  `sample_send_time` varchar(512) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '送样情况',
  `pra` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'PRA',
  `trial_produce_qa1` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '试产记录',
  `pmr` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'PMR',
  `dl` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'DL',
  `ipa` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'IPA',
  `cri` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'CRI',
  `ds` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'DS',
  `dd` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'DD',
  `pl` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'PL',
  `pes` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'PES',
  `pcb_file` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'PCB加工文件',
  `icd` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'ICD',
  `smt` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'SMT贴片文件',
  `mp` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'MP',
  `sqr` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'SQR',
  `dvs` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'DVS',
  `dvp` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'DVP',
  `dvr` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'DVR',
  `dvt` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'DVT',
  `rtr` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'RTR',
  `emr` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'EMR',
  `mtb` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'MTB',
  `tsq` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'TSQ',
  `sqc` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'SQC',
  `ed` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'ED',
  `pts` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'PTS',
  `sp` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'SP',
  `ep` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'EP',
  `fep` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'FEP',
  `fsp` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'FSP',
  `ld` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'LD',
  `pd` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'PD',
  `pg` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'PG',
  `nfc` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'NFC',
  `frm` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'FRM',
  `pfc` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'PFC',
  `wi` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'WI',
  `other` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'Other',
  `create_time` datetime(0) NULL DEFAULT NULL,
  `create_user` int(11) NULL DEFAULT NULL,
  `update_time` datetime(0) NULL DEFAULT NULL,
  `update_user` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1941 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_product_record
-- ----------------------------
DROP TABLE IF EXISTS `oa_product_record`;
CREATE TABLE `oa_product_record`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '类别',
  `table_name` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '对应表',
  `table_id` int(10) UNSIGNED NOT NULL COMMENT '对应表ID',
  `handle_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '操作者',
  `handle_time` datetime(0) NULL DEFAULT NULL COMMENT '操作时间',
  `action` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '动作（增、删、改、审核、下载、在线浏览）',
  `result` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '操作结果',
  `ip` varchar(15) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'IP地址',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `source` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '来源',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_product_series
-- ----------------------------
DROP TABLE IF EXISTS `oa_product_series`;
CREATE TABLE `oa_product_series`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '名称',
  `description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '产品系列' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_product_type
-- ----------------------------
DROP TABLE IF EXISTS `oa_product_type`;
CREATE TABLE `oa_product_type`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '类别编码',
  `parent_id` int(10) NOT NULL DEFAULT 0 COMMENT '父类',
  `name` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '名称',
  `description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `sn_length` int(2) NULL DEFAULT 10 COMMENT '流水号长度',
  `active` int(1) NULL DEFAULT 1 COMMENT '是否启用（1启用 0不启用）',
  `auto` int(1) NULL DEFAULT 1 COMMENT '是否自动编码（1是 0否）',
  `bom` tinyint(3) UNSIGNED NULL DEFAULT 0 COMMENT '是否BOM（0：不是 1：是）',
  `new_flow_id` int(10) NULL DEFAULT 1 COMMENT '归档审批流程',
  `upd_flow_id` int(10) NULL DEFAULT NULL COMMENT '变更审批流程',
  `del_flow_id` int(10) NULL DEFAULT NULL COMMENT '作废审批流程',
  `example` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述规则(举例)',
  `datafile_flg` int(1) NULL DEFAULT 0 COMMENT '数据手册',
  `tsr_flg` int(1) NULL DEFAULT 0 COMMENT 'TSR',
  `checkreport_flg` int(1) NULL DEFAULT 0 COMMENT '样品验证报告',
  `create_user` int(10) NULL DEFAULT NULL,
  `default_status` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `update_user` int(10) NULL DEFAULT NULL,
  `create_time` datetime(0) NULL DEFAULT NULL,
  `update_time` datetime(0) NULL DEFAULT NULL,
  `hum_level` int(11) NOT NULL COMMENT '潮敏等级',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 148 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '物料类别' ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_public_options
-- ----------------------------
DROP TABLE IF EXISTS `oa_public_options`;
CREATE TABLE `oa_public_options`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '启用',
  `type` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '类别',
  `name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '值',
  `order` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '顺序',
  `description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 13 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '公共选项列表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_rate
-- ----------------------------
DROP TABLE IF EXISTS `oa_rate`;
CREATE TABLE `oa_rate`  (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `currency` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '币种缩写',
  `rate` decimal(10, 4) NULL DEFAULT NULL COMMENT '汇率（1外币=？人名币）',
  `start_time` datetime(0) NULL DEFAULT NULL,
  `end_time` datetime(0) NULL DEFAULT NULL,
  `update_user` int(10) NULL DEFAULT NULL,
  `update_time` datetime(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 9 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_record
-- ----------------------------
DROP TABLE IF EXISTS `oa_record`;
CREATE TABLE `oa_record`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '类别',
  `table_name` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '对应表',
  `table_id` int(10) UNSIGNED NOT NULL COMMENT '对应表ID',
  `handle_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '操作者',
  `handle_time` datetime(0) NULL DEFAULT NULL COMMENT '操作时间',
  `action` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '动作（增、删、改、审核、下载、在线浏览）',
  `result` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '操作结果',
  `ip` varchar(15) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'IP地址',
  `remark` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '备注',
  `source` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '来源',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 102797 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_review
-- ----------------------------
DROP TABLE IF EXISTS `oa_review`;
CREATE TABLE `oa_review`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '类型（新文件/升版）',
  `file_id` int(10) NULL DEFAULT NULL COMMENT '文件ID',
  `plan_dept` varchar(40) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '预定审核部门',
  `plan_user` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '预定审核人',
  `method` varchar(1) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '2' COMMENT '处理方式（1所有人都处理 2任何一人处理即可）',
  `return` int(2) NULL DEFAULT 1 COMMENT '退回后的处理（1退到初始状态 2退到本阶段开始 3退到上一阶段 4作废）',
  `actual_user` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '实际审核人',
  `finish_time` datetime(0) NULL DEFAULT NULL COMMENT '完成审核时间',
  `finish_flg` int(1) NULL DEFAULT 0 COMMENT '审核状态（1已完成 0未完成）',
  `step_name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '状态名称',
  `step_ename` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '状态名称',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_review_fileid`(`file_id`) USING BTREE,
  INDEX `idx_review_type`(`type`) USING BTREE,
  INDEX `idx_review_flag`(`finish_flg`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 51435 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_review_his
-- ----------------------------
DROP TABLE IF EXISTS `oa_review_his`;
CREATE TABLE `oa_review_his`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '类型（新文件/升版）',
  `file_id` int(10) NULL DEFAULT NULL COMMENT '文件ID',
  `plan_dept` varchar(40) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '预定审核部门',
  `plan_user` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '预定审核人',
  `method` varchar(1) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '2' COMMENT '处理方式（1所有人都处理 2任何一人处理即可）',
  `return` int(2) NULL DEFAULT 1 COMMENT '退回后的处理（1退到初始状态 2退到本阶段开始 3退到上一阶段 4作废）',
  `actual_user` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '实际审核人',
  `finish_time` datetime(0) NULL DEFAULT NULL COMMENT '完成审核时间',
  `finish_flg` int(1) NULL DEFAULT 0 COMMENT '审核状态（1已完成 0未完成）',
  `step_name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '状态名称',
  `step_ename` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '状态名称',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 14501 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_seal
-- ----------------------------
DROP TABLE IF EXISTS `oa_seal`;
CREATE TABLE `oa_seal`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '名称',
  `description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `remark` varchar(300) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `administrator` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '管理员',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 9 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '印章' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_seal_use
-- ----------------------------
DROP TABLE IF EXISTS `oa_seal_use`;
CREATE TABLE `oa_seal_use`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `seal_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '印章ID',
  `state` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态[0：归还，1：新申请，2：批准，3：拒绝，4：借出]',
  `apply_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '申请人',
  `apply_time` datetime(0) NULL DEFAULT NULL COMMENT '申请时间',
  `apply_reason` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '申请事由',
  `review_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '审核人',
  `review_time` datetime(0) NULL DEFAULT NULL COMMENT '审核时间',
  `review_state` tinyint(1) NOT NULL DEFAULT 0 COMMENT '审核结果[1：已审核，0：未审核]',
  `review_opinion` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '审核意见',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 524 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '印章使用记录' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_task
-- ----------------------------
DROP TABLE IF EXISTS `oa_task`;
CREATE TABLE `oa_task`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent` int(11) NULL DEFAULT NULL COMMENT '父任务ID',
  `start` datetime(0) NULL DEFAULT NULL COMMENT '开始时间',
  `end` datetime(0) NULL DEFAULT NULL COMMENT '结束时间',
  `title` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '任务标题',
  `notes` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `content` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '内容',
  `responsible_id` varchar(512) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '责任人',
  `follow_id` varchar(512) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '关注者',
  `priority` varchar(16) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '优先级',
  `important` varchar(16) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '重要度',
  `type` varchar(16) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '多个责任人时任务分配方式',
  `state` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '状态',
  `step` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '阶段',
  `create_user` int(11) NULL DEFAULT NULL,
  `create_time` datetime(0) NULL DEFAULT NULL,
  `update_user` int(11) NULL DEFAULT NULL,
  `update_time` datetime(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_task_process
-- ----------------------------
DROP TABLE IF EXISTS `oa_task_process`;
CREATE TABLE `oa_task_process`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NULL DEFAULT NULL,
  `employee_id` int(11) NULL DEFAULT NULL,
  `update_time` datetime(0) NULL DEFAULT NULL,
  `rate` int(11) NULL DEFAULT 0 COMMENT '进度百分比（0-100）',
  `remark` varchar(512) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注说明',
  `status` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '当前状态',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for oa_transfer
-- ----------------------------
DROP TABLE IF EXISTS `oa_transfer`;
CREATE TABLE `oa_transfer`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `active` tinyint(1) NULL DEFAULT 0 COMMENT '批准状态',
  `type` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '类别',
  `transfer_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '变更对象ID',
  `reason` varchar(1000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '变更说明',
  `content` longtext CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '变更内容',
  `create_user` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 10 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '变更信息' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_user
-- ----------------------------
DROP TABLE IF EXISTS `oa_user`;
CREATE TABLE `oa_user`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '开通状态',
  `employee_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '职员ID',
  `password` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '密码',
  `last_login_time` datetime(0) NULL DEFAULT NULL COMMENT '上次登录时间',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `create_user` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT '创建人',
  `update_user` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT '更新人',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 112 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '用户表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_user_access
-- ----------------------------
DROP TABLE IF EXISTS `oa_user_access`;
CREATE TABLE `oa_user_access`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '名称',
  `description` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `type` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '类别[用户/组/角色]',
  `user_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '用户ID',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '权限' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_user_rights
-- ----------------------------
DROP TABLE IF EXISTS `oa_user_rights`;
CREATE TABLE `oa_user_rights`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '名称',
  `description` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `type` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '类别[用户/组/角色]',
  `user_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '用户ID',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '权限' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_user_role
-- ----------------------------
DROP TABLE IF EXISTS `oa_user_role`;
CREATE TABLE `oa_user_role`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '启用',
  `parentid` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '上级角色ID',
  `name` varchar(45) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL COMMENT '名称',
  `description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `remark` varchar(300) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_user` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `lock` tinyint(1) NOT NULL DEFAULT 0 COMMENT '角色上锁（防止删除）',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 71 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '用户角色' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_user_role_member
-- ----------------------------
DROP TABLE IF EXISTS `oa_user_role_member`;
CREATE TABLE `oa_user_role_member`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '用户ID',
  `role_id` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT '角色ID',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 685 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '用户角色设置' ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for oa_user_roles
-- ----------------------------
DROP TABLE IF EXISTS `oa_user_roles`;
CREATE TABLE `oa_user_roles`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '上级角色ID',
  `name` varchar(45) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL COMMENT '名称',
  `description` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL COMMENT '描述',
  `remark` varchar(200) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL COMMENT '备注',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '用户角色' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_user_roleset
-- ----------------------------
DROP TABLE IF EXISTS `oa_user_roleset`;
CREATE TABLE `oa_user_roleset`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '用户ID',
  `role_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '角色ID',
  `description` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_user` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT '创建人',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_user` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT '更新人',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '用户角色设置' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_user_rule
-- ----------------------------
DROP TABLE IF EXISTS `oa_user_rule`;
CREATE TABLE `oa_user_rule`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '名称',
  `description` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述',
  `type` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '类别[用户/组/角色]',
  `user_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '用户ID',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '权限' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for oa_user_users
-- ----------------------------
DROP TABLE IF EXISTS `oa_user_users`;
CREATE TABLE `oa_user_users`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `state` int(10) UNSIGNED NULL DEFAULT 1 COMMENT '状态',
  `employee_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '职员ID',
  `password` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '密码',
  `last_login_time` datetime(0) NULL DEFAULT NULL COMMENT '上次登录时间',
  `remark` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '备注',
  `test` timestamp(0) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 3 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '用户表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for r15_tmp
-- ----------------------------
DROP TABLE IF EXISTS `r15_tmp`;
CREATE TABLE `r15_tmp`  (
  `id1` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `code1` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料号',
  `id2` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `code2` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物料号'
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for t_params
-- ----------------------------
DROP TABLE IF EXISTS `t_params`;
CREATE TABLE `t_params`  (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `code` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `value` varchar(256) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `description` varchar(512) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `remark` varchar(512) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

SET FOREIGN_KEY_CHECKS = 1;
