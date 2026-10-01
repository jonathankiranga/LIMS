alter table test_assignments
add column category enum('chemical','admin','microbiological');

alter table test_assignments
add column subcontractor INT;

alter table test_assignments
add column emailsent timestamp NULL DEFAULT NULL;

UPDATE `menu_items` SET `title` = 'Assign Test To Users' WHERE (`id` = '3');
UPDATE `menu_items` SET `title` = 'Sub-contract sample Tests', `icon` = 'fas fa-vial' WHERE (`id` = '7');
UPDATE `menu_items` SET `title` = 'Sub-contract Parameters' WHERE (`id` = '8');


CREATE TABLE subcontractors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL,
    address VARCHAR(50),
    address2 VARCHAR(100),
    city VARCHAR(50),
    country VARCHAR(50),
    phone VARCHAR(15),
    alt_contact VARCHAR(100),
    email VARCHAR(100),
    inactive TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   FOREIGN KEY (`id`) REFERENCES `test_assignments`(`subcontractor`)  ON DELETE RESTRICT,
 
);

ALTER TABLE `test_assignments` 
CHANGE COLUMN `user_id` `user_id` INT NULL ;
--27012025

INSERT INTO menu_items (title, icon, url, parent_id, security_id) VALUES
('Post Sub-contracted results', 'fas fa-vial','labresults_subcontract.php', 7, 0); 

INSERT INTO menu_items (title, icon, url, parent_id, security_id) VALUES
('Send Request for Test by Email', 'fas fa-vial','sendsubcontractoremail.php', 7, 0); 

INSERT INTO menu_items (title, icon, url, parent_id, security_id) VALUES
('Manage Contractors', 'fas fa-user-circle','ledgers.php',19, 0); 

INSERT INTO menu_items (title, icon, url, parent_id, security_id) VALUES
('Manage Customers', 'fas fa-user-circle','ledgerc.php',19, 0); 

INSERT INTO menu_items (title, icon, url, parent_id, security_id) VALUES
('Approve Results', 'fas fa-vial','approvetestresults.php',1, 0); 
---07022025
INSERT INTO `menu_items`(`title`,`icon`,`url`,`parent_id`,`security_id`)
VALUES('Neutralitycalculator','fas fa-balance-scale','neutralitycalculator.php',12,0);

INSERT INTO `menu_items`(`title`,`icon`,`url`,`parent_id`,`security_id`)
VALUES('tdscalculator','fas fa-balance-scale','tdscalculator.php',12,0);