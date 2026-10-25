-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 25, 2026 at 01:10 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `krishimitra`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `email`, `password`, `created_at`) VALUES
(1, 'Dev Solanki', 'dev@gmail.com', '123456', '2026-06-25 15:48:39');

-- --------------------------------------------------------

--
-- Table structure for table `crops`
--

CREATE TABLE `crops` (
  `id` int(11) NOT NULL,
  `crop_name` varchar(100) NOT NULL,
  `min_nitrogen` decimal(6,2) NOT NULL,
  `max_nitrogen` decimal(6,2) NOT NULL,
  `min_phosphorus` decimal(6,2) NOT NULL,
  `max_phosphorus` decimal(6,2) NOT NULL,
  `min_potassium` decimal(6,2) NOT NULL,
  `max_potassium` decimal(6,2) NOT NULL,
  `min_ph` decimal(4,2) NOT NULL,
  `max_ph` decimal(4,2) NOT NULL,
  `min_temperature` decimal(5,2) NOT NULL,
  `max_temperature` decimal(5,2) NOT NULL,
  `min_rainfall` decimal(7,2) NOT NULL,
  `max_rainfall` decimal(7,2) NOT NULL,
  `min_humidity` decimal(5,2) NOT NULL,
  `max_humidity` decimal(5,2) NOT NULL,
  `soil_type` varchar(100) DEFAULT NULL,
  `water_requirement` varchar(50) DEFAULT NULL,
  `crop_duration` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `crops`
--

INSERT INTO `crops` (`id`, `crop_name`, `min_nitrogen`, `max_nitrogen`, `min_phosphorus`, `max_phosphorus`, `min_potassium`, `max_potassium`, `min_ph`, `max_ph`, `min_temperature`, `max_temperature`, `min_rainfall`, `max_rainfall`, `min_humidity`, `max_humidity`, `soil_type`, `water_requirement`, `crop_duration`, `description`, `created_at`) VALUES
(1, 'Wheat', 40.00, 80.00, 20.00, 50.00, 30.00, 70.00, 6.00, 7.50, 15.00, 25.00, 300.00, 900.00, 40.00, 80.00, 'Loamy', 'Medium', '120-150 days', 'Suitable for cool weather and well-drained soil.', '2026-08-19 16:31:56'),
(2, 'Mustard', 30.00, 70.00, 15.00, 40.00, 25.00, 60.00, 6.00, 7.50, 10.00, 25.00, 250.00, 600.00, 40.00, 75.00, 'Loamy', 'Low', '110-140 days', 'Suitable for cool weather with moderate water requirements.', '2026-08-19 16:31:56'),
(3, 'Maize', 40.00, 100.00, 20.00, 50.00, 30.00, 80.00, 5.50, 7.50, 18.00, 30.00, 500.00, 800.00, 50.00, 80.00, 'Loamy', 'Medium', '90-120 days', 'Requires warm weather and adequate soil moisture.', '2026-08-19 16:31:56'),
(4, 'Chickpea', 20.00, 60.00, 15.00, 40.00, 20.00, 60.00, 6.00, 8.00, 15.00, 30.00, 300.00, 600.00, 40.00, 70.00, 'Loamy', 'Low', '100-120 days', 'Suitable for relatively dry conditions and well-drained soil.', '2026-08-19 16:31:56'),
(5, 'Groundnut', 30.00, 70.00, 15.00, 40.00, 25.00, 60.00, 6.00, 7.50, 20.00, 30.00, 500.00, 1000.00, 50.00, 80.00, 'Sandy Loam', 'Medium', '100-130 days', 'Requires warm conditions and well-drained soil.', '2026-08-19 16:31:56'),
(6, 'Cotton', 40.00, 100.00, 20.00, 50.00, 30.00, 80.00, 5.50, 8.00, 21.00, 35.00, 500.00, 1200.00, 50.00, 80.00, 'Black', 'Medium', '160-180 days', 'Requires warm conditions and is commonly associated with black soil.', '2026-08-19 16:31:56'),
(7, 'Bajra', 20.00, 60.00, 10.00, 35.00, 20.00, 50.00, 5.50, 7.50, 25.00, 35.00, 250.00, 600.00, 40.00, 70.00, 'Sandy', 'Low', '75-100 days', 'Suitable for warm and relatively dry conditions.', '2026-08-19 16:31:56'),
(8, 'Rice', 50.00, 100.00, 25.00, 60.00, 40.00, 90.00, 5.50, 7.00, 20.00, 35.00, 1000.00, 2000.00, 70.00, 95.00, 'Clay', 'High', '120-150 days', 'Requires warm conditions and high water availability.', '2026-08-19 16:31:56');

-- --------------------------------------------------------

--
-- Table structure for table `farmers`
--

CREATE TABLE `farmers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `location` varchar(100) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive') DEFAULT 'active',
  `approved` tinyint(1) DEFAULT 1,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `farmers`
--

INSERT INTO `farmers` (`id`, `name`, `location`, `phone`, `password`, `created_at`, `status`, `approved`, `latitude`, `longitude`, `district`) VALUES
(1, 'Test', 'Ahemdabad', '9999999999', '789456', '2026-06-25 16:03:58', 'active', 1, NULL, NULL, NULL),
(2, 'Mahesh Raval', 'Rajkot', '7485961236', '123123', '2026-08-17 17:18:17', 'active', 1, NULL, NULL, NULL),
(3, 'Ramesh  Shah', 'Pune', '4152634152', '963963', '2026-08-17 17:21:14', 'active', 1, NULL, NULL, NULL),
(4, 'Keya Patel', 'zundal', '9726250111', '789456', '2026-09-03 05:25:32', 'active', 1, NULL, NULL, NULL),
(5, 'Astha', 'Gandhinagar', '8585858585', '444444', '2026-09-05 15:19:34', 'active', 1, NULL, NULL, NULL),
(6, 'XYZ', 'Surat', '9966996699', '777777', '2026-09-11 17:39:46', 'active', 1, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `farms`
--

CREATE TABLE `farms` (
  `id` int(11) NOT NULL,
  `farmer_id` int(11) NOT NULL,
  `farm_name` varchar(100) NOT NULL,
  `location` varchar(100) NOT NULL,
  `farm_size` decimal(10,2) NOT NULL,
  `soil_type` varchar(50) DEFAULT NULL,
  `crop_history` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `farms`
--

INSERT INTO `farms` (`id`, `farmer_id`, `farm_name`, `location`, `farm_size`, `soil_type`, `crop_history`, `created_at`, `latitude`, `longitude`, `district`) VALUES
(1, 1, 'Raghav Farm', 'Ahmedabad', 5.00, 'Red', NULL, '2026-07-17 17:28:23', 23.02579000, 72.58727000, NULL),
(2, 1, 'Ganesh Farm', 'Gandhinagar', 6.00, 'Black', NULL, '2026-08-17 12:00:58', NULL, NULL, NULL),
(3, 4, 'Santavan', 'zundal', 10.50, 'Clay', NULL, '2026-09-03 05:27:42', NULL, NULL, NULL),
(4, 5, 'Shree Hari', 'Gandhinagar', 10.00, 'Loamy', NULL, '2026-09-05 15:32:49', 23.21667000, 72.68333000, NULL),
(5, 5, 'Jay Farm', 'Ahmedabad', 12.00, 'Clay', NULL, '2026-09-05 16:04:44', NULL, NULL, NULL),
(6, 6, 'bhole farm', 'mandavi', 4.00, 'Loamy', NULL, '2026-09-11 17:52:43', NULL, NULL, NULL),
(7, 5, 'K B Farm', 'Mehsana', 5.00, 'Clay', NULL, '2026-09-12 03:59:49', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pest_advisories`
--

CREATE TABLE `pest_advisories` (
  `advisory_id` int(11) NOT NULL,
  `crop` varchar(50) NOT NULL,
  `issue_name` varchar(150) NOT NULL,
  `cause` varchar(150) NOT NULL,
  `symptoms` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`symptoms`)),
  `first_steps` text NOT NULL,
  `prevention` text NOT NULL,
  `caution` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pest_advisories`
--

INSERT INTO `pest_advisories` (`advisory_id`, `crop`, `issue_name`, `cause`, `symptoms`, `first_steps`, `prevention`, `caution`, `created_at`) VALUES
(1, 'Rice', 'Rice blast', 'Possible fungal disease', '[\"leaf spots\",\"brown spots\",\"neck rot\",\"white lesions\"]', 'Inspect affected leaves and panicles; avoid moving infected plant material around the field; seek confirmation if symptoms spread.', 'Use locally recommended resistant varieties, balanced fertilizer, and field sanitation practices.', 'Visual symptoms can resemble other problems. Confirm with an agricultural specialist before choosing a fungicide.', '2026-10-22 11:22:00'),
(2, 'Potato', 'Late blight of potato', 'Possible oomycete disease', '[\"leaf spots\",\"brown spots\",\"wilting\",\"dark lesions\"]', 'Inspect leaves and stems, especially after humid or rainy weather; seek prompt local advice if symptoms spread.', 'Use healthy seed tubers, suitable spacing, field sanitation, and locally recommended varieties.', 'Late blight can spread quickly. Ask a local agriculture officer to confirm and advise on approved management.', '2026-10-22 11:22:00'),
(3, 'Tomato', 'Possible late blight', 'Possible oomycete disease', '[\"leaf spots\",\"brown spots\",\"wilting\",\"dark lesions\"]', 'Check whether lesions expand quickly and inspect stems and nearby plants; get expert confirmation promptly.', 'Use healthy seedlings, suitable spacing, avoid prolonged leaf wetness, and remove crop debris as locally advised.', 'Several tomato problems cause spots. Do not treat based on this symptom match alone.', '2026-10-22 11:22:00'),
(4, 'Wheat', 'Wheat rust', 'Possible fungal disease', '[\"yellow spots\",\"rusty pustules\",\"leaf discoloration\"]', 'Check leaves for raised rust-coloured pustules; note affected area and crop stage; contact local agricultural support.', 'Use locally recommended resistant varieties and follow regional sowing recommendations.', 'Confirm rust type and severity before any chemical control recommendation.', '2026-10-22 11:22:00'),
(5, 'Cotton', 'Cotton bollworm-like damage', 'Possible insect damage', '[\"holes in bolls\",\"chewed leaves\",\"frass\",\"dropped bolls\"]', 'Inspect damaged bolls and nearby plant parts for larvae; record affected plants; use field scouting and seek local advice.', 'Practice regular scouting, conserve beneficial insects, and use locally recommended integrated pest management.', 'Damage alone cannot confirm insect species. Any pesticide choice must be crop- and region-approved.', '2026-10-22 11:22:00'),
(6, 'Sugarcane', 'Sugarcane top-borer-like damage', 'Possible insect damage', '[\"dead heart\",\"shot holes\",\"stunted growth\",\"bore holes\"]', 'Inspect central shoots and affected stalks; mark affected patches and consult local crop-protection staff.', 'Use healthy planting material and locally recommended field sanitation.', 'Confirm pest identity and crop stage before treatment.', '2026-10-22 11:22:00'),
(7, 'Groundnut', 'Groundnut leaf-spot complex', 'Possible fungal disease', '[\"leaf spots\",\"brown spots\",\"yellow halos\",\"premature defoliation\"]', 'Inspect both sides of leaves and note whether spots are spreading; seek confirmation if defoliation increases.', 'Use healthy seed, crop rotation, suitable spacing, and field sanitation.', 'Different leaf diseases look similar. Ask an agricultural expert before selecting a treatment.', '2026-10-22 11:22:00'),
(8, 'Maize', 'Maize stem-borer-like damage', 'Possible insect damage', '[\"dead heart\",\"shot holes\",\"bore holes\",\"stunted growth\"]', 'Inspect whorl and stem for larvae or feeding signs; monitor affected plants and contact local extension support.', 'Use recommended planting practices, field scouting, and locally advised integrated pest management.', 'The symptom pattern is not a species confirmation; follow regional advice for any control measure.', '2026-10-22 11:22:00'),
(9, 'Bajra', 'Pearl millet shoot damage', 'Possible insect or other damage', '[\"dead heart\",\"shot holes\",\"bore holes\",\"stunted growth\"]', 'Inspect growing point and stems closely; photograph damage and ask local agricultural staff to identify the cause.', 'Use healthy seed and recommended crop-management practices; monitor fields regularly.', 'Several causes can produce similar damage; confirm before applying any pesticide.', '2026-10-22 11:22:00'),
(10, 'Mustard', 'Mustard aphid-like symptoms', 'Possible sap-sucking insect', '[\"honeydew\",\"sticky leaves\",\"curled leaves\",\"black sooty mold\"]', 'Check underside of leaves and tender shoots for small insect clusters; note whether beneficial insects are present.', 'Avoid unnecessary broad-spectrum spraying; monitor crop and use locally recommended integrated pest management.', 'Sticky leaves alone do not confirm aphids. Ask local experts about thresholds and approved controls.', '2026-10-22 11:22:00'),
(11, 'Any', 'Unidentified leaf spots', 'Could be disease, pest injury, or abiotic stress', '[\"leaf spots\",\"brown spots\",\"yellow spots\",\"leaf discoloration\"]', 'Take clear close-up photos of both leaf sides and the whole plant; note when symptoms began and whether they are spreading.', 'Maintain appropriate watering, avoid plant stress, and keep field observations for expert review.', 'This general entry cannot identify a specific disease. Obtain expert confirmation before treatment.', '2026-10-22 11:22:00');

-- --------------------------------------------------------

--
-- Table structure for table `soil_data`
--

CREATE TABLE `soil_data` (
  `id` int(11) NOT NULL,
  `farm_id` int(11) NOT NULL,
  `farmer_id` int(11) NOT NULL,
  `nitrogen` decimal(6,2) NOT NULL,
  `phosphorus` decimal(6,2) NOT NULL,
  `potassium` decimal(6,2) NOT NULL,
  `ph` decimal(4,1) NOT NULL,
  `temperature` decimal(5,1) NOT NULL,
  `humidity` decimal(5,1) NOT NULL,
  `rainfall` decimal(6,2) NOT NULL,
  `test_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `soil_data`
--

INSERT INTO `soil_data` (`id`, `farm_id`, `farmer_id`, `nitrogen`, `phosphorus`, `potassium`, `ph`, `temperature`, `humidity`, `rainfall`, `test_date`, `created_at`) VALUES
(1, 1, 1, 50.00, 30.00, 40.00, 6.5, 28.0, 65.0, 1200.00, '2026-07-17', '2026-07-17 17:36:52'),
(2, 2, 1, 60.00, 50.00, 40.00, 6.5, 5.5, 50.0, 200.00, '2026-08-19', '2026-08-19 16:55:49'),
(3, 3, 4, 119.00, 120.00, 199.99, 13.0, 40.0, 50.0, 0.02, '2026-09-03', '2026-09-03 05:29:20'),
(4, 4, 5, 120.00, 60.00, 40.00, 6.0, 25.0, 60.0, 600.00, '2026-09-05', '2026-09-05 15:39:08'),
(5, 5, 5, 180.00, 80.00, 40.00, 8.0, 28.0, 60.0, 700.00, '2026-09-05', '2026-09-05 16:05:29'),
(6, 6, 6, 150.00, 100.00, 95.00, 8.0, 15.0, 70.0, 500.00, '2026-09-11', '2026-09-11 17:53:42'),
(7, 7, 5, 150.00, 100.00, 80.00, 9.0, 40.0, 40.0, 400.00, '2026-09-12', '2026-09-12 04:00:47');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `crops`
--
ALTER TABLE `crops`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `farmers`
--
ALTER TABLE `farmers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`);

--
-- Indexes for table `farms`
--
ALTER TABLE `farms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_farm` (`farmer_id`,`farm_name`);

--
-- Indexes for table `pest_advisories`
--
ALTER TABLE `pest_advisories`
  ADD PRIMARY KEY (`advisory_id`),
  ADD KEY `idx_crop` (`crop`);

--
-- Indexes for table `soil_data`
--
ALTER TABLE `soil_data`
  ADD PRIMARY KEY (`id`),
  ADD KEY `farm_id` (`farm_id`),
  ADD KEY `farmer_id` (`farmer_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `crops`
--
ALTER TABLE `crops`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `farmers`
--
ALTER TABLE `farmers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `farms`
--
ALTER TABLE `farms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `pest_advisories`
--
ALTER TABLE `pest_advisories`
  MODIFY `advisory_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `soil_data`
--
ALTER TABLE `soil_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `farms`
--
ALTER TABLE `farms`
  ADD CONSTRAINT `farms_ibfk_1` FOREIGN KEY (`farmer_id`) REFERENCES `farmers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `soil_data`
--
ALTER TABLE `soil_data`
  ADD CONSTRAINT `soil_data_ibfk_1` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `soil_data_ibfk_2` FOREIGN KEY (`farmer_id`) REFERENCES `farmers` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
