-- Create addresses table
CREATE TABLE IF NOT EXISTS `addresses` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `address_type` varchar(50) NOT NULL,
    `recipient_name` varchar(100) NOT NULL,
    `street` varchar(255) NOT NULL,
    `city` varchar(100) NOT NULL,
    `region` varchar(100) NOT NULL,
    `postal_code` varchar(20) NOT NULL,
    `phone_number` varchar(20) NOT NULL,
    `is_default` tinyint(1) NOT NULL DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `addresses_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create payment_methods table
CREATE TABLE IF NOT EXISTS `payment_methods` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `type` varchar(50) NOT NULL,
    `masked_number` varchar(50) NOT NULL,
    `is_default` tinyint(1) NOT NULL DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `payment_methods_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add new columns to orders table if they don't exist
ALTER TABLE `orders` 
ADD COLUMN IF NOT EXISTS `address_id` int(11) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `payment_method_id` int(11) DEFAULT NULL,
ADD CONSTRAINT `orders_address_fk` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`id`),
ADD CONSTRAINT `orders_payment_method_fk` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`); 