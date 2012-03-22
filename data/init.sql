-- Adminer 3.2.2 MySQL dump

SET NAMES utf8;
SET foreign_key_checks = 0;
SET time_zone = 'SYSTEM';
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `pages`;
CREATE TABLE `pages` (
  `id_page` int(10) unsigned NOT NULL auto_increment,
  `lang` varchar(2) collate utf8_czech_ci NOT NULL,
  `id_parent` int(10) unsigned NOT NULL,
  `name` varchar(255) collate utf8_czech_ci NOT NULL default '',
  `seoname` varchar(255) collate utf8_czech_ci NOT NULL default '',
  `heading` varchar(255) collate utf8_czech_ci NOT NULL default '',
  `text` text collate utf8_czech_ci NOT NULL,
  `ord` int(10) unsigned NOT NULL,
  `published` tinyint(1) NOT NULL,
  `deleted` tinyint(1) NOT NULL,
  PRIMARY KEY  (`id_page`,`lang`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `pages_files`;
CREATE TABLE `pages_files` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `id_page` int(10) unsigned NOT NULL,
  `filename` varchar(255) collate utf8_czech_ci NOT NULL default '',
  `suffix` varchar(255) collate utf8_czech_ci NOT NULL,
  `filesize` int(10) unsigned NOT NULL,
  `description` text collate utf8_czech_ci NOT NULL,
  `keywords` text collate utf8_czech_ci NOT NULL,
  `info` text collate utf8_czech_ci NOT NULL,
  `dimensions` varchar(255) collate utf8_czech_ci NOT NULL default '',
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `origpath` varchar(255) collate utf8_czech_ci default NULL,
  `deleted` tinyint(1) unsigned NOT NULL,
  `gallerynum` tinyint(3) unsigned NOT NULL,
  `ord` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `pages_meta`;
CREATE TABLE `pages_meta` (
  `id_page` int(10) unsigned NOT NULL,
  `key` varchar(255) collate utf8_czech_ci NOT NULL default '',
  `value` mediumtext collate utf8_czech_ci NOT NULL,
  `ord` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`id_page`,`key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `redirect`;
CREATE TABLE `redirect` (
  `id` int(11) NOT NULL auto_increment,
  `oldurl` varchar(255) collate utf8_czech_ci NOT NULL,
  `newurl` varchar(255) collate utf8_czech_ci NOT NULL,
  `status` smallint(3) NOT NULL,
  `hits` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `translations`;
CREATE TABLE `translations` (
  `id` int(11) NOT NULL auto_increment,
  `key` varchar(255) collate utf8_czech_ci NOT NULL,
  `file` varchar(255) collate utf8_czech_ci NOT NULL,
  `cs` text collate utf8_czech_ci NOT NULL,
  `en` text collate utf8_czech_ci NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


-- 2012-02-19 20:32:24
