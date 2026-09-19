-- ========================================================
-- Example Test SQL Database Dump for JRV CRM Auto-Mapping
-- Contains:
-- 1. property_listings (maps to properties CRM module)
-- 2. crm_leads_import (maps to crm_sales_leads CRM module)
-- 3. website_visitors (dynamic table without dedicated module)
-- ========================================================

-- Table 1: Property Listings
CREATE TABLE IF NOT EXISTS `property_listings` (
  `property_id` int(11) NOT NULL AUTO_INCREMENT,
  `property_title` varchar(255) NOT NULL,
  `owner_name` varchar(150) NOT NULL,
  `owner_phone` varchar(30) DEFAULT NULL,
  `owner_email` varchar(100) DEFAULT NULL,
  `property_type` varchar(50) DEFAULT 'Apartment',
  `rent_amount` decimal(12,2) DEFAULT '0.00',
  `city` varchar(100) DEFAULT 'Mumbai',
  `locality` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`property_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `property_listings` (`property_id`, `property_title`, `owner_name`, `owner_phone`, `owner_email`, `property_type`, `rent_amount`, `city`, `locality`, `address`, `created_at`) VALUES
(1, 'Luxury 3BHK Sea Facing Penthouse', 'Rajesh Sharma', '+91 98200 12345', 'rajesh.sharma@example.com', 'Penthouse', 85000.00, 'Mumbai', 'Bandra West', 'Carter Road, Bandra West', '2026-09-18 10:30:00'),
(2, 'Cozy 2BHK Furnished Apartment', 'Pooja Verma', '9820012345', 'pooja.verma@example.com', 'Apartment', 45000.00, 'Pune', 'Koregaon Park', 'Lane 5, Koregaon Park', '2026-09-19 14:15:00'),
(3, 'Modern Commercial Office Space', 'Vikram Malhotra', '+91-9988776655', 'vikram@malhotraestates.com', 'Commercial Office', 120000.00, 'Bengaluru', 'Indiranagar', '100ft Road, Indiranagar', '2026-09-19 16:45:00'),
(4, 'Studio Apartment near Tech Park', 'Ananya Iyer', '9876543210', 'ananya.iyer@example.com', 'Studio Flat', 28000.00, 'Hyderabad', 'HITEC City', 'Phase 2, Madhapur', '2026-09-20 09:00:00');

-- Table 2: Leads Import
CREATE TABLE IF NOT EXISTS `crm_leads_import` (
  `lead_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_name` varchar(255) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `whatsapp` varchar(30) DEFAULT NULL,
  `industry` varchar(100) DEFAULT 'Technology',
  `deal_stage` varchar(50) DEFAULT 'New',
  `estimated_mrr` decimal(10,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`lead_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `crm_leads_import` (`lead_id`, `customer_name`, `company_name`, `email`, `phone`, `whatsapp`, `industry`, `deal_stage`, `estimated_mrr`, `created_at`) VALUES
(1, 'Aarav Patel', 'Apex Cloud Innovations', 'aarav.patel@apexcloud.io', '+91 98765 00112', '+91 98765 00112', 'Technology', 'Qualified', 12500.00, '2026-09-18 11:20:00'),
(2, 'Neha Singhal', 'Singhal Logistics Pvt Ltd', 'neha@singhallogistics.com', '09811223344', '9811223344', 'Logistics', 'Proposal', 24000.00, '2026-09-19 12:00:00'),
(3, 'Dr. Sanjay Gupta', 'Gupta Healthcare Clinic', 'dr.gupta@guptaclinic.com', '9822334455', '9822334455', 'Healthcare', 'Won', 35000.00, '2026-09-19 17:30:00');

-- Table 3: Website Visitors (Dynamic Table without dedicated module)
CREATE TABLE IF NOT EXISTS `website_visitors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `landing_page` varchar(255) NOT NULL,
  `referrer_source` varchar(255) DEFAULT 'Direct',
  `browser` varchar(50) DEFAULT 'Chrome',
  `device_type` varchar(30) DEFAULT 'Desktop',
  `country` varchar(100) DEFAULT 'India',
  `session_duration_sec` int(11) DEFAULT '0',
  `visited_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `website_visitors` (`id`, `ip_address`, `landing_page`, `referrer_source`, `browser`, `device_type`, `country`, `session_duration_sec`, `visited_at`) VALUES
(1, '103.21.124.8', '/pricing', 'Google Search', 'Chrome', 'Desktop', 'India', 342, '2026-09-19 18:00:00'),
(2, '49.36.88.192', '/real-estate-crm', 'LinkedIn Ad', 'Safari', 'Mobile', 'India', 185, '2026-09-19 18:14:00'),
(3, '157.48.201.44', '/contact', 'Direct', 'Firefox', 'Desktop', 'United States', 512, '2026-09-19 19:22:00'),
(4, '27.59.130.65', '/features/dynamic-crm', 'Twitter / X', 'Chrome', 'Tablet', 'Germany', 94, '2026-09-20 00:10:00');
