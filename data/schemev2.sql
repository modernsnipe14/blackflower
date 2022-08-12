-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 12, 2022 at 09:35 AM
-- Server version: 10.4.24-MariaDB
-- PHP Version: 8.1.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `template`
--

-- --------------------------------------------------------

--
-- Table structure for table `archive_master`
--

CREATE TABLE `archive_master` (
  `tskey` varchar(30) NOT NULL,
  `ts` datetime NOT NULL,
  `comment` varchar(80) DEFAULT NULL,
  `database_version` varchar(20) DEFAULT NULL,
  `requires_code_ver` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `bulletins`
--

CREATE TABLE `bulletins` (
  `bulletin_id` int(11) NOT NULL,
  `bulletin_subject` varchar(160) DEFAULT NULL,
  `bulletin_text` text DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `access_level` int(11) DEFAULT NULL,
  `closed` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `bulletin_history`
--

CREATE TABLE `bulletin_history` (
  `id` int(11) NOT NULL,
  `bulletin_id` int(11) DEFAULT NULL,
  `action` enum('Created','Edited','Closed','Reopened') DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `bulletin_views`
--

CREATE TABLE `bulletin_views` (
  `id` int(11) NOT NULL,
  `bulletin_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `last_read` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `channels`
--

CREATE TABLE `channels` (
  `channel_id` int(11) NOT NULL,
  `channel_name` varchar(40) NOT NULL,
  `repeater` tinyint(1) NOT NULL DEFAULT 0,
  `available` tinyint(1) NOT NULL DEFAULT 1,
  `precedence` int(11) NOT NULL DEFAULT 50,
  `incident_id` int(11) DEFAULT NULL,
  `staging_id` int(11) DEFAULT NULL,
  `notes` varchar(160) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `channels`
--

INSERT INTO `channels` (`channel_id`, `channel_name`, `repeater`, `available`, `precedence`, `incident_id`, `staging_id`, `notes`) VALUES
(1, 'Tac 11', 0, 1, 10, NULL, NULL, NULL),
(2, 'Tac 12', 1, 1, 10, NULL, NULL, NULL),
(3, 'Tac 13', 0, 1, 10, NULL, NULL, NULL),
(4, 'Fire Ground 1', 0, 1, 20, NULL, NULL, NULL),
(5, 'Fire Ground 2', 0, 1, 20, NULL, NULL, NULL),
(6, '911', 1, 0, 97, NULL, NULL, NULL),
(7, 'Operations', 1, 0, 98, NULL, NULL, NULL),
(8, 'Admin', 1, 0, 99, NULL, NULL, NULL),
(9, 'Tac 11', 0, 1, 10, NULL, NULL, NULL),
(10, 'Tac 12', 1, 1, 10, NULL, NULL, NULL),
(11, 'Tac 13', 0, 1, 10, NULL, NULL, NULL),
(12, 'Fire Ground 1', 0, 1, 20, NULL, NULL, NULL),
(13, 'Fire Ground 2', 0, 1, 20, NULL, NULL, NULL),
(14, '911', 1, 0, 97, NULL, NULL, NULL),
(15, 'Operations', 1, 0, 98, NULL, NULL, NULL),
(16, 'Admin', 1, 0, 99, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `deployment_history`
--

CREATE TABLE `deployment_history` (
  `idx` int(11) NOT NULL,
  `schema_load_ts` datetime NOT NULL,
  `update_ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `database_version` varchar(20) NOT NULL,
  `requires_code_ver` varchar(20) NOT NULL,
  `mysql_user` varchar(255) DEFAULT NULL,
  `host` varchar(255) DEFAULT NULL,
  `uid` int(11) DEFAULT NULL,
  `user` varchar(8) DEFAULT NULL,
  `cwd` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `deployment_history`
--

INSERT INTO `deployment_history` (`idx`, `schema_load_ts`, `update_ts`, `database_version`, `requires_code_ver`, `mysql_user`, `host`, `uid`, `user`, `cwd`) VALUES
(1, '2022-08-08 17:00:23', '2022-08-08 21:00:23', '1.9.0.0', '1.9.0', 'root@localhost', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `incidents`
--

CREATE TABLE `incidents` (
  `incident_id` int(11) NOT NULL,
  `call_number` varchar(40) DEFAULT NULL,
  `call_type` varchar(40) DEFAULT NULL,
  `call_details` varchar(80) DEFAULT NULL,
  `ts_opened` datetime NOT NULL,
  `ts_dispatch` datetime DEFAULT NULL,
  `ts_arrival` datetime DEFAULT NULL,
  `ts_complete` datetime DEFAULT NULL,
  `location` varchar(80) DEFAULT NULL,
  `location_num` varchar(15) DEFAULT NULL,
  `reporting_pty` varchar(80) DEFAULT NULL,
  `contact_at` varchar(80) DEFAULT NULL,
  `disposition` varchar(80) DEFAULT NULL,
  `primary_unit` varchar(20) DEFAULT NULL,
  `updated` datetime NOT NULL,
  `duplicate_of_incident_id` int(11) DEFAULT NULL,
  `incident_status` enum('New','Open','Dispositioned','Closed') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `incident_disposition_types`
--

CREATE TABLE `incident_disposition_types` (
  `disposition` varchar(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `incident_disposition_types`
--

INSERT INTO `incident_disposition_types` (`disposition`) VALUES
('Completed'),
('Duplicate'),
('Medical Transported'),
('Other'),
('Released AMA'),
('Transferred to Agency'),
('Transferred to Rangers'),
('Treated And Released'),
('Unable To Locate'),
('Unfounded');

-- --------------------------------------------------------

--
-- Table structure for table `incident_locks`
--

CREATE TABLE `incident_locks` (
  `lock_id` int(11) NOT NULL,
  `incident_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `timestamp` datetime NOT NULL,
  `ipaddr` varchar(80) NOT NULL,
  `takeover_by_userid` int(11) DEFAULT NULL,
  `takeover_timestamp` datetime DEFAULT NULL,
  `takeover_ipaddr` varchar(80) DEFAULT NULL,
  `session_id` varchar(128) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `incident_notes`
--

CREATE TABLE `incident_notes` (
  `note_id` int(11) NOT NULL,
  `incident_id` int(11) NOT NULL,
  `ts` datetime NOT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `message` varchar(255) NOT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  `creator` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `incident_types`
--

CREATE TABLE `incident_types` (
  `call_type` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `incident_types`
--

INSERT INTO `incident_types` (`call_type`) VALUES
('COURTESY TRANSPORT'),
('FIRE'),
('ILLNESS'),
('INJURY'),
('LAW ENFORCEMENT'),
('MENTAL HEALTH'),
('OTHER'),
('PUBLIC ASSIST'),
('RANGERS'),
('TRAFFIC CONTROL'),
('TRAINING');

-- --------------------------------------------------------

--
-- Table structure for table `incident_units`
--

CREATE TABLE `incident_units` (
  `uid` int(11) NOT NULL,
  `incident_id` int(11) NOT NULL,
  `unit` varchar(20) NOT NULL,
  `dispatch_time` datetime DEFAULT NULL,
  `arrival_time` datetime DEFAULT NULL,
  `transport_time` datetime DEFAULT NULL,
  `transportdone_time` datetime DEFAULT NULL,
  `cleared_time` datetime DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT NULL,
  `is_generic` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `oid` int(11) NOT NULL,
  `ts` datetime NOT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `message` varchar(255) NOT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  `creator` varchar(20) DEFAULT NULL,
  `message_type` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `message_types`
--

CREATE TABLE `message_types` (
  `message_type` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `message_types`
--

INSERT INTO `message_types` (`message_type`) VALUES
('Bike'),
('DNF'),
('DQ'),
('Other'),
('Run'),
('Swim');

-- --------------------------------------------------------

--
-- Table structure for table `staging_locations`
--

CREATE TABLE `staging_locations` (
  `staging_id` int(11) NOT NULL,
  `location` varchar(80) DEFAULT NULL,
  `created_by` varchar(80) DEFAULT NULL,
  `time_created` datetime NOT NULL,
  `time_released` datetime DEFAULT NULL,
  `staging_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `status_options`
--

CREATE TABLE `status_options` (
  `status` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `status_options`
--

INSERT INTO `status_options` (`status`) VALUES
('Attached to Incident'),
('Available On Pager'),
('Busy'),
('In Service'),
('Off Comm'),
('Off Duty'),
('Off Duty; On Pager'),
('Out Of Service'),
('Staged At Location');

-- --------------------------------------------------------

--
-- Table structure for table `units`
--

CREATE TABLE `units` (
  `unit` varchar(20) NOT NULL,
  `status` varchar(30) DEFAULT NULL,
  `status_comment` varchar(255) DEFAULT NULL,
  `update_ts` datetime DEFAULT NULL,
  `role` varchar(20) DEFAULT NULL,
  `type` set('Unit','Individual','Generic') DEFAULT NULL,
  `personnel` varchar(100) DEFAULT NULL,
  `assignment` varchar(20) DEFAULT NULL,
  `personnel_ts` datetime DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `location_ts` datetime DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `notes_ts` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `unit_assignments`
--

CREATE TABLE `unit_assignments` (
  `assignment` varchar(20) NOT NULL,
  `description` varchar(40) DEFAULT NULL,
  `display_class` varchar(80) DEFAULT NULL,
  `display_style` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `unit_assignments`
--

INSERT INTO `unit_assignments` (`assignment`, `description`, `display_class`, `display_style`) VALUES
('ADC', 'Assistant Medical Duty Chief', 'iconblue', NULL),
('BC', 'Battalion Chief', 'iconyellow', NULL),
('CDC', 'Comm Duty Chief', 'iconpurple', NULL),
('CRC', 'Child Respite Center On-Call', 'icongreen', NULL),
('FDC', 'Fire Duty Chief', 'iconred', NULL),
('FS', 'Field Supervisor', 'icongray', NULL),
('IC', 'Incident Commander', 'iconwhite', NULL),
('L2000', 'Legal 2000 On-Call', 'icongreen', NULL),
('MDC', 'Medical Duty Chief', 'iconblue', NULL),
('MHDC', 'Mental Health Duty Chief', 'icongreen', NULL),
('OC', 'On-Call', 'icongray', NULL),
('ODC', 'Operations Duty Chief', 'iconwhite', NULL),
('S', 'Supervisor', 'icongray', NULL),
('SDC', 'Support Duty Chief', 'icongray', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `unit_filter_sets`
--

CREATE TABLE `unit_filter_sets` (
  `idx` int(11) NOT NULL,
  `filter_set_name` varchar(80) NOT NULL,
  `row_description` varchar(80) NOT NULL,
  `row_regexp` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `unit_incident_paging`
--

CREATE TABLE `unit_incident_paging` (
  `row_id` int(11) NOT NULL,
  `unit` varchar(20) NOT NULL,
  `to_pager_id` int(11) NOT NULL,
  `to_person_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `unit_roles`
--

CREATE TABLE `unit_roles` (
  `role` varchar(20) NOT NULL,
  `color_name` varchar(20) DEFAULT NULL,
  `color_html` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `unit_roles`
--

INSERT INTO `unit_roles` (`role`, `color_name`, `color_html`) VALUES
('Admin', 'Orange', 'darkorange'),
('Comm', 'Purple', 'Purple'),
('Fire', 'Red', 'Red'),
('Law Enforcement', 'Brown', 'brown'),
('Medical', 'Blue', 'Blue'),
('MHB', 'Green', 'Green'),
('Other', 'Black', 'Black');

-- --------------------------------------------------------

--
-- Table structure for table `unit_staging_assignments`
--

CREATE TABLE `unit_staging_assignments` (
  `staging_assignment_id` int(11) NOT NULL,
  `staged_at_location_id` int(11) NOT NULL,
  `unit_name` varchar(20) DEFAULT NULL,
  `time_staged` datetime NOT NULL,
  `time_reassigned` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(20) NOT NULL,
  `password` varchar(64) NOT NULL,
  `name` varchar(40) DEFAULT NULL,
  `access_level` int(11) NOT NULL DEFAULT 1,
  `access_acl` varchar(20) DEFAULT NULL,
  `timeout` int(11) NOT NULL DEFAULT 300,
  `preferences` text DEFAULT NULL,
  `change_password` tinyint(1) NOT NULL DEFAULT 0,
  `locked_out` tinyint(1) NOT NULL DEFAULT 0,
  `failed_login_count` int(11) NOT NULL DEFAULT 0,
  `last_login_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `name`, `access_level`, `access_acl`, `timeout`, `preferences`, `change_password`, `locked_out`, `failed_login_count`, `last_login_time`) VALUES
(1, 'Administrator', '$2a$08$73o6Rvpk8jjfeC3jZj2uV..2MKBJ3Esbv2g4JtZes9JrSFlaRPdUa', 'System Admin', 10, '10', 300, NULL, 0, 0, 0, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `archive_master`
--
ALTER TABLE `archive_master`
  ADD PRIMARY KEY (`tskey`);

--
-- Indexes for table `bulletins`
--
ALTER TABLE `bulletins`
  ADD PRIMARY KEY (`bulletin_id`),
  ADD KEY `updated` (`updated`),
  ADD KEY `access_level` (`access_level`),
  ADD KEY `closed` (`closed`);

--
-- Indexes for table `bulletin_history`
--
ALTER TABLE `bulletin_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bulletin_id` (`bulletin_id`,`updated`);

--
-- Indexes for table `bulletin_views`
--
ALTER TABLE `bulletin_views`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`,`bulletin_id`),
  ADD KEY `last_read` (`last_read`);

--
-- Indexes for table `channels`
--
ALTER TABLE `channels`
  ADD PRIMARY KEY (`channel_id`),
  ADD KEY `precedence` (`precedence`,`channel_name`),
  ADD KEY `incident_id` (`incident_id`),
  ADD KEY `staging_id` (`staging_id`);

--
-- Indexes for table `deployment_history`
--
ALTER TABLE `deployment_history`
  ADD PRIMARY KEY (`idx`);

--
-- Indexes for table `incidents`
--
ALTER TABLE `incidents`
  ADD PRIMARY KEY (`incident_id`),
  ADD KEY `incident_status` (`incident_status`),
  ADD KEY `ts_opened` (`ts_opened`);

--
-- Indexes for table `incident_disposition_types`
--
ALTER TABLE `incident_disposition_types`
  ADD PRIMARY KEY (`disposition`);

--
-- Indexes for table `incident_locks`
--
ALTER TABLE `incident_locks`
  ADD PRIMARY KEY (`lock_id`),
  ADD KEY `incident_id` (`incident_id`);

--
-- Indexes for table `incident_notes`
--
ALTER TABLE `incident_notes`
  ADD PRIMARY KEY (`note_id`),
  ADD KEY `incident_id` (`incident_id`,`deleted`);

--
-- Indexes for table `incident_types`
--
ALTER TABLE `incident_types`
  ADD PRIMARY KEY (`call_type`);

--
-- Indexes for table `incident_units`
--
ALTER TABLE `incident_units`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `incident_id` (`incident_id`,`cleared_time`),
  ADD KEY `dispatch_time` (`dispatch_time`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`oid`),
  ADD KEY `deleted` (`deleted`),
  ADD KEY `unit` (`unit`);

--
-- Indexes for table `message_types`
--
ALTER TABLE `message_types`
  ADD PRIMARY KEY (`message_type`);

--
-- Indexes for table `staging_locations`
--
ALTER TABLE `staging_locations`
  ADD PRIMARY KEY (`staging_id`);

--
-- Indexes for table `status_options`
--
ALTER TABLE `status_options`
  ADD PRIMARY KEY (`status`);

--
-- Indexes for table `units`
--
ALTER TABLE `units`
  ADD PRIMARY KEY (`unit`),
  ADD KEY `status` (`status`,`type`);

--
-- Indexes for table `unit_assignments`
--
ALTER TABLE `unit_assignments`
  ADD PRIMARY KEY (`assignment`);

--
-- Indexes for table `unit_filter_sets`
--
ALTER TABLE `unit_filter_sets`
  ADD PRIMARY KEY (`idx`),
  ADD KEY `filter_set_name` (`filter_set_name`);

--
-- Indexes for table `unit_incident_paging`
--
ALTER TABLE `unit_incident_paging`
  ADD PRIMARY KEY (`row_id`),
  ADD KEY `unit` (`unit`);

--
-- Indexes for table `unit_roles`
--
ALTER TABLE `unit_roles`
  ADD PRIMARY KEY (`role`);

--
-- Indexes for table `unit_staging_assignments`
--
ALTER TABLE `unit_staging_assignments`
  ADD PRIMARY KEY (`staging_assignment_id`),
  ADD KEY `staged_at_location_id` (`staged_at_location_id`),
  ADD KEY `unit_name` (`unit_name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bulletins`
--
ALTER TABLE `bulletins`
  MODIFY `bulletin_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bulletin_history`
--
ALTER TABLE `bulletin_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bulletin_views`
--
ALTER TABLE `bulletin_views`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `channels`
--
ALTER TABLE `channels`
  MODIFY `channel_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `deployment_history`
--
ALTER TABLE `deployment_history`
  MODIFY `idx` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `incidents`
--
ALTER TABLE `incidents`
  MODIFY `incident_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `incident_locks`
--
ALTER TABLE `incident_locks`
  MODIFY `lock_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `incident_notes`
--
ALTER TABLE `incident_notes`
  MODIFY `note_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `incident_units`
--
ALTER TABLE `incident_units`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `oid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staging_locations`
--
ALTER TABLE `staging_locations`
  MODIFY `staging_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `unit_filter_sets`
--
ALTER TABLE `unit_filter_sets`
  MODIFY `idx` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `unit_incident_paging`
--
ALTER TABLE `unit_incident_paging`
  MODIFY `row_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `unit_staging_assignments`
--
ALTER TABLE `unit_staging_assignments`
  MODIFY `staging_assignment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
