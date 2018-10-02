DROP TABLE IF EXISTS `amely_wards`;
CREATE TABLE `amely_wards` (
  `wardid` varchar(5) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` varchar(30) NOT NULL,
  `location` varchar(30) NOT NULL,
  `districtid` varchar(5) NOT NULL,
  PRIMARY KEY (`wardid`),
  UNIQUE KEY `wardid` (`wardid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `amely_usertokens`;
CREATE TABLE `amely_usertokens` (
  `id` bigint(255) unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(32) NOT NULL,
  `time_created` int(11) NOT NULL,
  `expired` bigint(20) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `session_id` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `amely_relationships`;
CREATE TABLE `amely_relationships` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `relation_from` bigint(20) NOT NULL,
  `relation_to` bigint(20) NOT NULL,
  `type` varchar(30) NOT NULL,
  `time_created` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `amely_redeem_code`;
CREATE TABLE `amely_redeem_code` (
  `id` bigint(255) NOT NULL AUTO_INCREMENT,
  `item_id` bigint(255) NOT NULL,
  `code` varchar(100) NOT NULL,
  `expired` bigint(20) NOT NULL,
  `quantity` double(22,0) NOT NULL,
  `type` varchar(20) NOT NULL,
  `guest_id` bigint(20) NOT NULL,
  `time_created` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `amely_provinces`;
CREATE TABLE `amely_provinces` (
  `provinceid` varchar(5) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` varchar(30) NOT NULL,
  PRIMARY KEY (`provinceid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `amely_notifications`;
CREATE TABLE `amely_notifications` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `type` text NOT NULL,
  `poster_id` bigint(20) NOT NULL,
  `owner_id` bigint(20) NOT NULL,
  `subject_id` bigint(20) NOT NULL,
  `viewed` varchar(1) DEFAULT NULL,
  `time_created` int(11) NOT NULL,
  `item_id` bigint(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `amely_messages`;
CREATE TABLE `amely_messages` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `message_from` bigint(20) NOT NULL,
  `message_to` bigint(20) NOT NULL,
  `message` text NOT NULL,
  `viewed` varchar(1) DEFAULT NULL,
  `time_created` int(11) NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'individual',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `amely_likes`;
CREATE TABLE `amely_likes` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `subject_id` bigint(20) NOT NULL,
  `owner_id` bigint(20) NOT NULL,
  `type` varchar(20) NOT NULL,
  `time_created` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `amely_districts`;
CREATE TABLE `amely_districts` (
  `districtid` varchar(5) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` varchar(30) NOT NULL,
  `location` varchar(30) NOT NULL,
  `provinceid` varchar(5) NOT NULL,
  PRIMARY KEY (`districtid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `amely_currency_rates`;
CREATE TABLE `amely_currency_rates` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `base_code` varchar(5) NOT NULL,
  `currency_code` varchar(5) NOT NULL,
  `rate` double(22,0) NOT NULL,
  `updated` bigint(11) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `amely_annotations`; 
CREATE TABLE `amely_annotations` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `owner_id` bigint(20) NOT NULL,
  `subject_id` bigint(20) NOT NULL,
  `type` varchar(20) NOT NULL,
  `time_created` int(11) NOT NULL,
  `content` text,
  `images` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `amely_product_group`; 
CREATE TABLE `amely_product_group` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  `owner_id` bigint(20) NOT NULL,
  `type` varchar(20) NOT NULL,
  `time_created` int(11) NOT NULL,
  `title` text NOT NULL,
  `description` longtext NOT NULL,
  `subtype` text NOT NULL,
  `percent` text,
  `price` text,
  `currency` text,
  `status` text
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `amely_groups`; 
CREATE TABLE `amely_groups` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  `owner_id` bigint(20) NOT NULL,
  `type` varchar(20) NOT NULL,
  `time_created` int(11) NOT NULL,
  `title` text NOT NULL,
  `description` longtext NOT NULL,
  `privacy` int DEFAULT 0,
  `rule` int DEFAULT 0,
  `owners` text,
  `avatar` text,
  `cover` text
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `amely_users`; 
CREATE TABLE `amely_users` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  `type` text NOT NULL,
  `username` text NOT NULL,
  `email` text NOT NULL,
  `password` text NOT NULL,
  `salt` varchar(8) NOT NULL,
  `first_name` text NOT NULL,
  `last_name` text NOT NULL,
  `last_login` int(11) NOT NULL,
  `last_activity` int(11) NOT NULL,
  `activation` text,
  `time_created` int(11) NOT NULL,
  `verification_code` varchar(20) DEFAULT NULL,
  `mobilelogin` text,
  `birthdate` text,
  `gender` text,
  `usercurrency` text,
  `province` text,
  `district` text,
  `ward` text,
  `address` text,
  `friends_hidden` int DEFAULT 0,
  `birthdate_hidden` int DEFAULT 0,
  `mobile_hidden` int DEFAULT 0,
  `language` text,
  `chain_store` text,
  `avatar` text,
  `cover` text,
  `gift_count` int DEFAULT 0,
  `offer_count` int DEFAULT 0,
  `blockedusers` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `amely_promotion_items`; 
CREATE TABLE `amely_promotion_items` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  `owner_id` bigint(20) NOT NULL,
  `type` varchar(20) NOT NULL,
  `time_created` int(11) NOT NULL,
  `title` text NOT NULL,
  `description` longtext NOT NULL,
  `subtype` text NOT NULL,
  `promotion_type` text,
  `promotion_percent` text,
  `promotion_price` text,
  `promotion_currency` text,
  `promotion_product` text

 ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `amely_promotions`; 
CREATE TABLE `amely_promotions` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  `owner_id` bigint(20) NOT NULL,
  `type` varchar(20) NOT NULL,
  `time_created` int(11) NOT NULL,
  `title` text NOT NULL,
  `description` longtext NOT NULL,
  `subtype` text NOT NULL,
  `time_start` text,
  `time_end` text,
  `status` text,
  `activated` text
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `amely_transactions`; 
CREATE TABLE `amely_transactions` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  `owner_id` bigint(20) NOT NULL,
  `type` varchar(20) NOT NULL,
  `time_created` int(11) NOT NULL,
  `title` text NOT NULL,
  `description` longtext NOT NULL,
  `subtype` text NOT NULL,
  `transaction_type` text,
  `status` text,
  `currency` text,
  `quantity` text,
  `order_id` text,
  `shipping_fee` text,
  `do_id` text,
  `tax` text,
  `sub_total` text,
  `seller` text,
  `shop_id` text,
  `item_id` text
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `amely_products`; 
CREATE TABLE `amely_products` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  `owner_id` bigint(20) NOT NULL,
  `type` varchar(20) NOT NULL,
  `time_created` int(11) NOT NULL,
  `title` text NOT NULL,
  `description` longtext NOT NULL,
  `number_sold` text,
  `tax` text,
  `friendly_url` text,
  `weight` text,
  `expiry_type` text,
  `currency` text,
  `origin` text,
  `product_order` text,
  `storage_duration` text,
  `is_special` text,
  `product_group` text,
  `creator_id` text,
  `custom_attributes` text,
  `download` text,
  `featured` text,
  `duration` text,
  `begin_day` text,
  `end_day` text,
  `manufacturer` text,
  `current_snapshot` text,
  `unit` text,
  `approved` text,
  `enabled` text,
  `voucher_category` text,
  `ticket_category` text,
  `shop_category` text,
  `market_category` text,
  `category` text,
  `adjourn_price` text,
  `images` text
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

`number_sold` text,
`friendly_url` text,
`product_order` text,
`download` text,
`featured` text,
`current_snapshot` text,
`approved` text,
`enabled` text,
`voucher_category` text,
`ticket_category` text,
`shop_category` text,
`market_category` text,
`category` text,
`time_created` int(11) NOT NULL,

DROP TABLE IF EXISTS `amely_products_snapshot`;
CREATE TABLE `amely_products_snapshot` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  `owner_id` bigint(20) NOT NULL,
  `type` varchar(20) NOT NULL,
  `title` text NOT NULL,
  `description` longtext NOT NULL,
  `tax` text,
  `weight` text,
  `expiry_type` text,
  `currency` text,
  `origin` text,
  `storage_duration` text,
  `is_special` text,
  `product_group` text,
  `creator_id` text,
  `custom_attributes` text,
  `duration` text,
  `begin_day` text,
  `end_day` text,
  `manufacturer` text,
  `unit` text,
  `images` text,
  `adjourn_price` text,
  `key` text
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

`time_created` int(11) NOT NULL,
`quantity` text,
`number_sold` text,
`approved` text,
`enabled` text,
`current_snapshot` text,

DROP TABLE IF EXISTS `amely_sub_products_snapshot`; 
CREATE TABLE `amely_sub_products_snapshot` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  `owner_id` bigint(20) NOT NULL,
  `type` varchar(20) NOT NULL,
  `title` text NOT NULL,
  `description` longtext NOT NULL,
  `price` text,
  `sku` text,
  `creator_id` text,
  `sale_price` text,
  `images` text,
  `key` text
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `amely_sub_products`; 
CREATE TABLE `amely_sub_products` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  `owner_id` bigint(20) NOT NULL,
  `type` varchar(20) NOT NULL,
  `time_created` int(11) NOT NULL,
  `title` text NOT NULL,
  `description` longtext NOT NULL,
  `price` text,
  `quantity` text,
  `sku` text,
  `creator_id` text,
  `number_sold` text,
  `sale_price` text,
  `current_snapshot` text,
  `approved` text,
  `enabled` text,
  `images` text
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `amely_delivery_order`; 
CREATE TABLE `amely_delivery_order` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  `owner_id` bigint(20) NOT NULL,
  `type` varchar(20) NOT NULL,
  `time_created` int(11) NOT NULL,
  `title` text NOT NULL,
  `description` longtext NOT NULL,
  `subtype` text NOT NULL,
  `so_id` text, 
  `status` text, 
  `product_snapshot` text, 
  `ghtk_result` text, 
  `ghtk_status` text, 
  `ghtk_status_text` text, 
  `item` text, 
  `quantity` text, 
  `order_item_snapshot` text, 
  `ghtk_success` text, 
  `shipping_fullname` text, 
  `shipping_phone` text, 
  `shipping_address` text, 
  `shipping_province` text, 
  `shipping_district` text, 
  `shipping_ward` text, 
  `shipping_note` text, 
  `shipping_method` text, 
  `shipping_fee` text
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `amely_supply_order`; 
CREATE TABLE `amely_supply_order` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  `owner_id` bigint(20) NOT NULL,
  `type` varchar(20) NOT NULL,
  `time_created` int(11) NOT NULL,
  `title` text NOT NULL,
  `description` longtext NOT NULL,
  `subtype` text NOT NULL,
  `process_status` text, 
  `shop_id` text, 
  `shipping_fee` text, 
  `order_item` text, 
  `order_item_snapshot` text
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `amely_purchase_order`; 
CREATE TABLE `amely_purchase_order` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  `owner_id` bigint(20) NOT NULL,
  `type` varchar(20) NOT NULL,
  `time_created` int(11) NOT NULL,
  `title` text NOT NULL,
  `description` longtext NOT NULL,
  `subtype` text NOT NULL,
  `process_status` text,
  `paymented` text,
  `fullname` text,
  `phone` text,
  `address` text,
  `province` text,
  `district` text,
  `ward` text,
  `note` text,
  `payment` text,
  `shipping_shop` text,
  `shipping_fullname` text,
  `shipping_phone` text,
  `shipping_address` text,
  `shipping_province` text,
  `shipping_district` text,
  `shipping_ward` text,
  `shipping_note` text,
  `shipping_method` text,
  `shipping_fee` text,
  `order_item` text,
  `order_item_snapshot` text
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `amely_products_snapshot`; 
CREATE TABLE `amely_products_snapshot` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  `owner_id` bigint(20) NOT NULL,
  `type` varchar(20) NOT NULL,
  `time_created` int(11) NOT NULL,
  `title` text NOT NULL,
  `description` longtext NOT NULL,
  `subtype` text NOT NULL,
  `tax` text,
  `price` text,
  `quantity` text,
  `weight` text,
  `expiry_type` text,
  `friendly_url` text,
  `currency` text,
  `origin` text,
  `product_order` text,
  `enabled` text,
  `sku` text,
  `storage_duration` text,
  `is_special` text,
  `product_group` text,
  `creator_id` text,
  `custom_attributes` text,
  `number_sold` text,
  `download` text,
  `featured` text,
  `sale_price` text,
  `duration` text,
  `begin_day` text,
  `end_day` text,
  `manufacturer` text,
  `approved` text,
  `current_snapshot` text,
  `voucher_category` text,
  `ticket_category` text,
  `shop_category` text,
  `market_category` text,
  `category` text,
  `images` text
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `amely_counter_offers`; 
CREATE TABLE `amely_counter_offers` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  `owner_id` bigint(20) NOT NULL,
  `type` varchar(20) NOT NULL,
  `time_created` int(11) NOT NULL,
  `title` text NOT NULL,
  `description` longtext NOT NULL,
  `subtype` text NOT NULL,
  `offer_id` text, 
  `status` text, 
  `product_snapshot` text, 
  `quantity` text, 
  `so_id` text
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `amely_business_pages`; 
CREATE TABLE `amely_business_pages` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  `owner_id` bigint(20) NOT NULL,
  `time_created` int(11) NOT NULL,
  `title` text NOT NULL,
  `description` longtext NOT NULL,
  `subtype` text NOT NULL,
  `category` text,
  `website` text,
  `phone` text,
  `address` text,
  `avatar` text,
  `cover` text
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `amely_shops`; 
CREATE TABLE `amely_shops` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  `owner_id` bigint(20) NOT NULL,
  `type` varchar(20) NOT NULL,
  `time_created` int(11) NOT NULL,
  `title` text NOT NULL,
  `description` longtext NOT NULL,
  `shop_bidn` text,
  `friendly_url` text,
  `shipping_method` text,
  `owner_name` text,
  `owner_phone` text,
  `owner_address` text,
  `owner_province` text,
  `owner_district` text,
  `owner_ward` text,
  `owner_ssn` text,
  `status` text,
  `introduce` text,
  `policy` text,
  `contact` text,
  `avatar` text,
  `cover` text,
  `files_scan` text
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `amely_stores`; 
CREATE TABLE `amely_stores` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  `owner_id` bigint(20) NOT NULL,
  `type` varchar(20) NOT NULL,
  `time_created` int(11) NOT NULL,
  `title` text NOT NULL,
  `description` longtext NOT NULL,
  `lat` text,
  `lng` text,
  `store_phone` text,
  `store_address` text,
  `store_province` text,
  `store_district` text,
  `store_ward` text
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `amely_offers`; 
CREATE TABLE `amely_offers` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  `owner_id` bigint(20) NOT NULL,
  `type` varchar(20) NOT NULL,
  `time_created` int(11) NOT NULL,
  `title` text NOT NULL,
  `description` longtext NOT NULL,
  `subtype` text NOT NULL,
  `target` text,
  `duration` text,
  `quantity` text,
  `offer_type` text,
  `location_lat` text,
  `location_lng` text,
  `expried` text,
  `status` text,
  `random_expiration` text,
  `limit_counter` text,
  `giveaway_approval` text,
  `product_snapshot` text,
  `so_id` text,
  `counter_number` text
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `amely_feeds`; 
CREATE TABLE `amely_feeds` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  `owner_id` bigint(20) NOT NULL,
  `type` varchar(20) NOT NULL,
  `time_created` int(11) NOT NULL,
  `title` text NOT NULL,
  `description` longtext NOT NULL,
  `location` text, 
  `tag` text, 
  `mood_id` text, 
  `poster_id` text, 
  `privacy` text, 
  `item_type` text, 
  `item_id` text, 
  `images` text
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `amely_manufacturers`; 
CREATE TABLE `amely_manufacturers` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  `owner_id` bigint(20) NOT NULL,
  `type` varchar(20) NOT NULL,
  `time_created` int(11) NOT NULL,
  `title` text NOT NULL,
  `description` longtext NOT NULL,
  `subtype` text NOT NULL,
  `friendly_url` text,
  `featured` text,
  `logo` text
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `amely_categories`; 
CREATE TABLE `amely_categories` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  `owner_id` bigint(20) NOT NULL,
  `type` varchar(20) NOT NULL,
  `time_created` int(11) NOT NULL,
  `title` text NOT NULL,
  `description` longtext NOT NULL,
  `subtype` text NOT NULL,
 `friendly_url` text,
  `sort_order` text,
  `enabled` text,
  `parent_id` text,
  `creator_id` text,
  `logo` text
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `amely_items`; 
CREATE TABLE `amely_items` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  `owner_id` bigint(20) NOT NULL,
  `type` varchar(20) NOT NULL,
  `time_created` int(11) NOT NULL,
  `title` text NOT NULL,
  `description` longtext NOT NULL,
  `subtype` text NOT NULL,
 `inventory_type`text,
  `quantity`text,
  `product_snapshot`text,
  `expiry_type`text,
  `is_special`text,
  `stored_end`text,
  `end_day`text,
  `so_id`text,
  `wishlist`text,
  `givelist`text,
  `display_price`text
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `amely_events`; 
CREATE TABLE `amely_events` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  `owner_id` bigint(20) NOT NULL,
  `type` varchar(20) NOT NULL,
  `time_created` int(11) NOT NULL,
  `title` text NOT NULL,
  `description` longtext NOT NULL,
  `subtype` text NOT NULL,
  `start_date` text,
  `end_date` text,
  `country` text,
  `location` text,
  `template` text,
  `has_inventory` text,
  `status` text,
  `creator_id` text,
  `members` text,
  `invites` text,
  `friendly_url` text,
  `published` text,
  `avatar` text,
  `cover` text
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `amely_moods`; 
CREATE TABLE `amely_moods` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  `owner_id` bigint(20) NOT NULL,
  `type` varchar(20) NOT NULL,
  `time_created` int(11) NOT NULL,
  `title` text NOT NULL,
  `description` longtext NOT NULL,
  `subtype` text NOT NULL,
  `mood_icon` text
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `amely_advertisements`; 
CREATE TABLE `amely_advertisements` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  `owner_id` bigint(20) NOT NULL,
  `type` varchar(20) NOT NULL,
  `time_created` int(11) NOT NULL,
  `title` text NOT NULL,
  `description` longtext NOT NULL,
  `subtype` text NOT NULL,
  `advertise_type` text,
  `item` text,
  `image` text,
  `start_date` text,
  `end_date` text,
  `budget` text,
  `cpc` text,
  `link` text,
  `start_time` text,
  `end_time` text,
  `enabled` text,
  `amount` text,
  `number_click` text,
  `approved` text,
  `admin_created` text
   
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `amely_feed_linkpreview`; 
CREATE TABLE `amely_feed_linkpreview` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  `owner_id` bigint(20) NOT NULL,
  `type` varchar(20) NOT NULL,
  `time_created` int(11) NOT NULL,
  `title` text NOT NULL,
  `description` longtext NOT NULL,
  `subtype` text NOT NULL,
  `link_full` text,
  `linkPreviewType` text,
  `linkPreviewImage` text
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP VIEW IF EXISTS `amely_current_ads`;
CREATE OR REPLACE VIEW `amely_current_ads` as SELECT *, (ad.budget*1 - ad.amount*1) as balance FROM amely_advertisements ad
    WHERE start_date < UNIX_TIMESTAMP() 
         AND end_date > UNIX_TIMESTAMP()
         AND concat(CURTIME()) > concat(start_time,":00")
      AND concat(CURTIME()) < concat(end_time,":00")
      AND (ad.budget*1 - cpc*1) > ad.amount*1
      AND approved not in ('new', 'suspended');



