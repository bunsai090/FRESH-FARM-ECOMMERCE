-- First, drop the existing foreign key constraints
ALTER TABLE `orders` 
DROP FOREIGN KEY IF EXISTS `orders_address_fk`,
DROP FOREIGN KEY IF EXISTS `orders_payment_method_fk`;

-- Rename addresses table to delivery_addresses if it exists
RENAME TABLE IF EXISTS `addresses` TO `delivery_addresses`;

-- Update the column name in delivery_addresses if needed
ALTER TABLE `delivery_addresses` 
CHANGE COLUMN IF EXISTS `street` `street_address` varchar(255) NOT NULL;

-- Add new foreign key constraint with correct table name
ALTER TABLE `orders` 
ADD CONSTRAINT `orders_address_fk` FOREIGN KEY (`address_id`) REFERENCES `delivery_addresses` (`id`),
ADD CONSTRAINT `orders_payment_method_fk` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`); 