-- Adminer 4.2.5 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `pages`;
CREATE TABLE `pages` (
                         `id_page` int(10) unsigned NOT NULL AUTO_INCREMENT,
                         `lang` varchar(2) COLLATE utf8_czech_ci NOT NULL,
                         `id_parent` int(10) unsigned NOT NULL,
                         `name` varchar(255) COLLATE utf8_czech_ci NOT NULL DEFAULT '',
                         `seoname` varchar(255) COLLATE utf8_czech_ci NOT NULL DEFAULT '',
                         `heading` varchar(255) COLLATE utf8_czech_ci NOT NULL DEFAULT '',
                         `text` mediumtext COLLATE utf8_czech_ci NOT NULL,
                         `ord` int(10) unsigned NOT NULL,
                         `published` tinyint(1) NOT NULL,
                         `deleted` tinyint(1) NOT NULL,
                         PRIMARY KEY (`id_page`,`lang`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `pages_files`;
CREATE TABLE `pages_files` (
                               `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                               `id_page` int(10) unsigned NOT NULL,
                               `filename` varchar(255) COLLATE utf8_czech_ci NOT NULL DEFAULT '',
                               `suffix` varchar(255) COLLATE utf8_czech_ci NOT NULL,
                               `filesize` int(10) unsigned NOT NULL,
                               `description` text COLLATE utf8_czech_ci NOT NULL,
                               `keywords` text COLLATE utf8_czech_ci NOT NULL,
                               `info` text COLLATE utf8_czech_ci NOT NULL,
                               `dimensions` varchar(255) COLLATE utf8_czech_ci NOT NULL DEFAULT '',
                               `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
                               `origpath` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
                               `deleted` tinyint(1) unsigned NOT NULL,
                               `gallerynum` tinyint(3) unsigned NOT NULL,
                               `ord` int(10) unsigned NOT NULL,
                               PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `pages_history`;
CREATE TABLE `pages_history` (
                                 `id_version` int(10) NOT NULL AUTO_INCREMENT,
                                 `id_page` int(10) NOT NULL,
                                 `lang` varchar(2) NOT NULL,
                                 `id_parent` int(10) NOT NULL,
                                 `name` varchar(255) NOT NULL DEFAULT '',
                                 `seoname` varchar(255) NOT NULL DEFAULT '',
                                 `heading` varchar(255) NOT NULL DEFAULT '',
                                 `text` mediumtext NOT NULL,
                                 `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
                                 PRIMARY KEY (`id_version`) USING BTREE,
                                 KEY `Key` (`id_page`,`lang`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `pages_meta`;
CREATE TABLE `pages_meta` (
                              `id_page` int(10) unsigned NOT NULL,
                              `key` varchar(255) COLLATE utf8_czech_ci NOT NULL DEFAULT '',
                              `value` mediumtext COLLATE utf8_czech_ci NOT NULL,
                              `ord` int(10) unsigned NOT NULL,
                              PRIMARY KEY (`id_page`,`key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `redirect`;
CREATE TABLE `redirect` (
                            `id` int(11) NOT NULL AUTO_INCREMENT,
                            `oldurl` varchar(255) COLLATE utf8_czech_ci NOT NULL,
                            `newurl` varchar(255) COLLATE utf8_czech_ci NOT NULL,
                            `status` smallint(3) NOT NULL,
                            `hits` int(11) NOT NULL,
                            PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `translations`;
CREATE TABLE `translations` (
                                `id` int(11) NOT NULL AUTO_INCREMENT,
                                `key` varchar(255) COLLATE utf8_czech_ci NOT NULL,
                                `file` varchar(255) COLLATE utf8_czech_ci NOT NULL,
                                `cs` text COLLATE utf8_czech_ci NOT NULL,
                                `en` text COLLATE utf8_czech_ci NOT NULL,
                                PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


-- 2021-05-08 18:04:27
