CREATE TABLE `wx_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0: 正常, 1: 禁用',
  `type` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0: 订阅号 1: 服务号',
  `wx_id` varchar(128) COLLATE utf8_unicode_ci NOT NULL COMMENT '原始id',
  `token` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `app_id` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `app_secret` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `desc` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '描述',
  `menu` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wx_id` (`wx_id`),
  UNIQUE KEY `app_id` (`app_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `wx_connects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `time` datetime NOT NULL,
  `user_id` int(11) NOT NULL,
  `wx_openid` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `prj_subscribe` int(1) DEFAULT '0',
  `wx_subscribe` int(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `wx_openid` (`wx_openid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `wx_reply_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(16) COLLATE utf8_unicode_ci NOT NULL COMMENT 'text, news, mixed',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0: 正常, 1: 禁用',
  `content` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `wx_reply_keywords` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(16) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'equal' COMMENT 'equal, include, regex, event, click',
  `keyword` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `item_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `item_id` (`item_id`),
  KEY `keyword` (`keyword`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `wx_openids` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `wx_id` varchar(128) COLLATE utf8_unicode_ci NOT NULL COMMENT '服务号微信id',
  `wx_openid` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '微信openid',
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `source` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_wxid_openid` (`wx_id`,`wx_openid`),
  KEY `source` (`source`),
  KEY `wx_openid` (`wx_openid`),
  KEY `status` (`status`),
  KEY `wx_id` (`wx_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;