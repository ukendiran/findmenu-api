ALTER TABLE `subscriptions` ADD `deleted_at` TIMESTAMP NULL AFTER `updated_at`;

ALTER TABLE `subscription_plans` ADD `deleted_at` TIMESTAMP NULL AFTER `updated_at`;

ALTER TABLE `payments` ADD `deleted_at` TIMESTAMP NULL AFTER `updated_at`;