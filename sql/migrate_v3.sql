-- Phase 3 Migration: Multi-Tenant Architecture
CREATE TABLE IF NOT EXISTS `garages` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `email` varchar(100) DEFAULT NULL,
    `phone` varchar(50) DEFAULT NULL,
    `status` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create default garage (id = 1)
INSERT IGNORE INTO `garages` (`id`, `name`) VALUES (1, 'AutoServ Main Garage');

-- Expand Role ENUM safely
ALTER TABLE `users` MODIFY COLUMN `role` enum('Superadmin','Admin','Employee','Accountant','Support Staff','Customer') NOT NULL;

-- Inject garage_id to tables
ALTER TABLE `users` ADD COLUMN `garage_id` int(11) DEFAULT NULL AFTER `id`;
ALTER TABLE `vehicles` ADD COLUMN `garage_id` int(11) DEFAULT NULL AFTER `id`;
ALTER TABLE `services` ADD COLUMN `garage_id` int(11) DEFAULT NULL AFTER `id`;
ALTER TABLE `suppliers` ADD COLUMN `garage_id` int(11) DEFAULT NULL AFTER `id`;
ALTER TABLE `products` ADD COLUMN `garage_id` int(11) DEFAULT NULL AFTER `id`;
ALTER TABLE `quotations` ADD COLUMN `garage_id` int(11) DEFAULT NULL AFTER `id`;
ALTER TABLE `job_cards` ADD COLUMN `garage_id` int(11) DEFAULT NULL AFTER `id`;
ALTER TABLE `sales` ADD COLUMN `garage_id` int(11) DEFAULT NULL AFTER `id`;
ALTER TABLE `invoices` ADD COLUMN `garage_id` int(11) DEFAULT NULL AFTER `id`;

-- Tie existing data to Garage 1
UPDATE `users` SET `garage_id` = 1 WHERE `role` != 'Superadmin';
UPDATE `vehicles` SET `garage_id` = 1;
UPDATE `services` SET `garage_id` = 1;
UPDATE `suppliers` SET `garage_id` = 1;
UPDATE `products` SET `garage_id` = 1;
UPDATE `quotations` SET `garage_id` = 1;
UPDATE `job_cards` SET `garage_id` = 1;
UPDATE `sales` SET `garage_id` = 1;
UPDATE `invoices` SET `garage_id` = 1;

-- Add FK constraints
ALTER TABLE `users` ADD CONSTRAINT `fk_usr_garage` FOREIGN KEY (`garage_id`) REFERENCES `garages` (`id`) ON DELETE CASCADE;
ALTER TABLE `vehicles` ADD CONSTRAINT `fk_veh_garage` FOREIGN KEY (`garage_id`) REFERENCES `garages` (`id`) ON DELETE CASCADE;
ALTER TABLE `services` ADD CONSTRAINT `fk_svc_garage` FOREIGN KEY (`garage_id`) REFERENCES `garages` (`id`) ON DELETE CASCADE;
ALTER TABLE `suppliers` ADD CONSTRAINT `fk_sup_garage` FOREIGN KEY (`garage_id`) REFERENCES `garages` (`id`) ON DELETE CASCADE;
ALTER TABLE `products` ADD CONSTRAINT `fk_prd_garage` FOREIGN KEY (`garage_id`) REFERENCES `garages` (`id`) ON DELETE CASCADE;
ALTER TABLE `quotations` ADD CONSTRAINT `fk_quo_garage` FOREIGN KEY (`garage_id`) REFERENCES `garages` (`id`) ON DELETE CASCADE;
ALTER TABLE `job_cards` ADD CONSTRAINT `fk_job_garage` FOREIGN KEY (`garage_id`) REFERENCES `garages` (`id`) ON DELETE CASCADE;
ALTER TABLE `sales` ADD CONSTRAINT `fk_sal_garage` FOREIGN KEY (`garage_id`) REFERENCES `garages` (`id`) ON DELETE CASCADE;
ALTER TABLE `invoices` ADD CONSTRAINT `fk_inv_garage` FOREIGN KEY (`garage_id`) REFERENCES `garages` (`id`) ON DELETE CASCADE;

-- Settings table restructure
ALTER TABLE `settings` ADD COLUMN `garage_id` int(11) NOT NULL DEFAULT 1 FIRST;
ALTER TABLE `settings` DROP PRIMARY KEY, ADD PRIMARY KEY (`garage_id`, `setting_key`);
ALTER TABLE `settings` ADD CONSTRAINT `fk_set_garage` FOREIGN KEY (`garage_id`) REFERENCES `garages` (`id`) ON DELETE CASCADE;

-- Seed Superadmin if not exists (password is admin123)
INSERT INTO `users` (`name`, `email`, `password`, `role`, `status`, `garage_id`) 
SELECT 'Super Admin', 'super@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Superadmin', 1, NULL
FROM DUAL WHERE NOT EXISTS (SELECT email FROM users WHERE email='super@example.com');
