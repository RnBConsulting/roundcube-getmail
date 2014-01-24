CREATE TABLE IF NOT EXISTS `getmail` (
  `id` varchar(13) COLLATE utf8_unicode_ci NOT NULL,
  `user_id` int(10) NOT NULL,
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `active` int(1) unsigned NOT NULL DEFAULT '1',
  `type` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `server` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `mailboxes` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `port` int(5) DEFAULT NULL,
  `user` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `pass` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `ssl` int(1) unsigned NOT NULL DEFAULT '1',
  `delete` int(1) unsigned NOT NULL DEFAULT '0',
  `only_new` int(1) unsigned NOT NULL DEFAULT '1',
  `poll` int(10) unsigned NOT NULL DEFAULT '300',
  `lastpoll` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;