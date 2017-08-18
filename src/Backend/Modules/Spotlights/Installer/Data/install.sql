CREATE TABLE IF NOT EXISTS `spotlights_categories` (
 `id` int(11) NOT NULL auto_increment,
 `meta_id` int(11) NOT NULL,
 `extra_id` int(4) NULL,
 `language` varchar(5) NOT NULL,
 `title` varchar(255) NOT NULL,
 `template` varchar(255) NULL,
 `sequence` int(11) NOT NULL,
 PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `spotlights` (
 `id` int(11) NOT NULL auto_increment,
 `meta_id` int(11) NOT NULL,
 `language` varchar(5) NOT NULL,
 `category_id` int(11) NOT NULL,
 `title` varchar(255) NOT NULL,
 `text` text,
 `data` text,
 `image` varchar(255) NULL,
 `hidden` tinyint(1) NOT NULL DEFAULT '0',
 `created_on` datetime NOT NULL,
 `edited_on` datetime NOT NULL,
 `sequence` int(11) NOT NULL,
 PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
