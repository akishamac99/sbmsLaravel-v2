
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


--
-- Database: `sbms`
--

-- --------------------------------------------------------

--
-- Table structure for table `booking`
--

CREATE TABLE `booking` (
  `bookingID` int(11) NOT NULL,
  `bookingRef` varchar(20) NOT NULL,
  `customerID` int(11) NOT NULL,
  `serviceID` int(11) NOT NULL,
  `service` text DEFAULT NULL,
  `staffID` int(11) DEFAULT NULL,
  `bookingDate` date NOT NULL,
  `bookingTime` time NOT NULL,
  `bookingStatus` enum('pending','approved','completed','declined','cancelled','no-show') DEFAULT 'pending',
  `paymentStatus` enum('pending','proof-submitted','confirmed','paid','rejected','cancelled') DEFAULT 'pending',
  `payment_type` enum('full','partial') DEFAULT 'full',
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `downpayment_amount` decimal(10,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `discount_reason` varchar(255) DEFAULT NULL,
  `service_type` enum('salon','home_service') DEFAULT 'salon',
  `home_address` text DEFAULT NULL,
  `special_request` text DEFAULT NULL,
  `attendance_status` enum('pending','present','no-show') DEFAULT 'pending',
  `reschedule_count` int(11) DEFAULT 0,
  `cancellation_reason` text DEFAULT NULL,
  `payment_proof` varchar(500) DEFAULT NULL,
  `payment_proof_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `booking`
--

INSERT INTO `booking` (`bookingID`, `bookingRef`, `customerID`, `serviceID`, `service`, `staffID`, `bookingDate`, `bookingTime`, `bookingStatus`, `paymentStatus`, `payment_type`, `total_amount`, `downpayment_amount`, `discount_amount`, `discount_reason`, `service_type`, `home_address`, `special_request`, `attendance_status`, `reschedule_count`, `cancellation_reason`, `payment_proof`, `payment_proof_name`, `created_at`, `updated_at`) VALUES
(1, 'BK1777716376975', 4, 3, 'Hair Cut Premium - Women', 6, '2026-05-03', '14:00:00', 'approved', 'confirmed', 'full', 500.00, 100.00, 0.00, NULL, 'salon', '', '', 'pending', 0, NULL, '../uploads/payments/1777716386_1162094.jpg', '1777716386_1162094.jpg', '2026-05-02 10:06:16', '2026-05-02 10:36:00');

-- --------------------------------------------------------

--
-- Table structure for table `booking_cart`
--

CREATE TABLE `booking_cart` (
  `cartID` int(11) NOT NULL,
  `customerID` int(11) NOT NULL,
  `session_id` varchar(100) NOT NULL,
  `serviceID` int(11) NOT NULL,
  `staffID` int(11) DEFAULT NULL,
  `bookingDate` date DEFAULT NULL,
  `bookingTime` time DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `special_request` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `booking_cart`
--

INSERT INTO `booking_cart` (`cartID`, `customerID`, `session_id`, `serviceID`, `staffID`, `bookingDate`, `bookingTime`, `notes`, `special_request`, `created_at`) VALUES
(2, 1, 'h28hnroe5g5d7oqglc4ihr2eh6', 65, NULL, '2026-05-05', '15:00:00', '', '', '2026-05-05 00:49:51');

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `customerID` int(11) NOT NULL,
  `firstName` varchar(50) NOT NULL,
  `middleName` varchar(50) DEFAULT NULL,
  `lastName` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `contactNumber` varchar(20) NOT NULL,
  `home_address` text DEFAULT NULL,
  `reset_token` varchar(100) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL,
  `otp_code` varchar(10) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL,
  `accountStatus` enum('active','inactive','blocked','pending') DEFAULT 'pending',
  `is_verified` tinyint(4) DEFAULT 0,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`customerID`, `firstName`, `middleName`, `lastName`, `email`, `password`, `contactNumber`, `home_address`, `reset_token`, `reset_token_expiry`, `otp_code`, `otp_expiry`, `accountStatus`, `is_verified`, `createdAt`) VALUES
(1, 'Maria', NULL, 'Santos', 'maria@gmail.com', 'customer123', '09171234569', NULL, NULL, NULL, NULL, NULL, 'active', 1, '2026-05-02 08:07:07'),
(2, 'John', NULL, 'Dela Cruz', 'john@gmail.com', 'customer123', '09171234570', NULL, NULL, NULL, NULL, NULL, 'active', 1, '2026-05-02 08:07:07'),
(3, 'Lara', NULL, 'Mendoza', 'lara@gmail.com', 'customer123', '09171234571', NULL, NULL, NULL, NULL, NULL, 'active', 1, '2026-05-02 08:07:07'),
(4, 'Kisha Naysa', NULL, 'Macario', 'xiushanaysa@gmail.com', 'kisha123', '09568030040', NULL, NULL, NULL, NULL, NULL, 'active', 1, '2026-05-02 09:51:30');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `feedbackID` int(11) NOT NULL,
  `bookingID` int(11) NOT NULL,
  `customerID` int(11) NOT NULL,
  `staffID` int(11) NOT NULL,
  `serviceID` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `service_rating` int(11) DEFAULT NULL CHECK (`service_rating` >= 1 and `service_rating` <= 5),
  `staff_rating` int(11) DEFAULT NULL CHECK (`staff_rating` >= 1 and `staff_rating` <= 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notifID` int(11) NOT NULL,
  `user_type` enum('customer','staff','owner','manager') NOT NULL,
  `userID` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notifID`, `user_type`, `userID`, `title`, `message`, `is_read`, `created_at`) VALUES
(1, 'customer', 4, 'Booking Submitted', 'Your booking #BK1777716376975 has been submitted for approval.', 0, '2026-05-02 10:06:17'),
(2, 'staff', 1, 'New Booking', 'New booking #BK1777716376975 from Kisha Naysa Macario needs approval.', 0, '2026-05-02 10:06:17'),
(3, 'staff', 3, 'New Booking', 'New booking #BK1777716376975 from Kisha Naysa Macario needs approval.', 0, '2026-05-02 10:06:17'),
(4, 'customer', 4, 'Payment Proof Uploaded', 'Your payment proof has been uploaded. Our team will verify it shortly.', 0, '2026-05-02 10:06:26'),
(5, 'customer', 4, 'Payment Confirmed', 'Your payment has been confirmed!', 0, '2026-05-02 10:36:00');

-- --------------------------------------------------------

--
-- Table structure for table `otp_storage`
--

CREATE TABLE `otp_storage` (
  `id` int(11) NOT NULL,
  `contact_number` varchar(255) NOT NULL,
  `otp_code` varchar(10) NOT NULL,
  `otp_type` varchar(50) NOT NULL COMMENT 'registration, password_reset',
  `created_at` datetime DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `used_at` datetime DEFAULT NULL,
  `attempts` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `otp_storage`
--

INSERT INTO `otp_storage` (`id`, `contact_number`, `otp_code`, `otp_type`, `created_at`, `expires_at`, `is_used`, `used_at`, `attempts`) VALUES
(2, 'xiushanaysa@gmail.com', '713073', 'registration', '2026-05-02 17:51:02', '2026-05-02 12:06:02', 1, '2026-05-02 11:51:30', 0);

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `payID` int(11) NOT NULL,
  `bookingID` int(11) NOT NULL,
  `payType` enum('cash','gcash','bank_transfer') DEFAULT 'cash',
  `amount` decimal(10,2) NOT NULL,
  `payStatus` enum('pending','proof-submitted','confirmed','paid','failed','cancelled','refunded') DEFAULT 'pending',
  `referenceNo` varchar(50) DEFAULT NULL,
  `payment_proof` varchar(500) DEFAULT NULL,
  `payDate` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`payID`, `bookingID`, `payType`, `amount`, `payStatus`, `referenceNo`, `payment_proof`, `payDate`) VALUES
(1, 1, 'gcash', 100.00, 'confirmed', 'REF1777716386', '../uploads/payments/1777716386_1162094.jpg', '2026-05-02 18:06:26');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `permID` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `permission_key` varchar(100) NOT NULL,
  `can_view` tinyint(4) DEFAULT 0,
  `can_create` tinyint(4) DEFAULT 0,
  `can_edit` tinyint(4) DEFAULT 0,
  `can_delete` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`permID`, `role_name`, `permission_key`, `can_view`, `can_create`, `can_edit`, `can_delete`) VALUES
(1, 'Manager', 'bookings', 1, 1, 1, 0),
(2, 'Manager', 'payments', 1, 0, 1, 0),
(3, 'Manager', 'reports', 1, 0, 0, 0),
(4, 'Manager', 'attendance', 1, 1, 1, 0),
(5, 'Manager', 'staff', 1, 0, 0, 0),
(6, 'Manager', 'services', 1, 0, 0, 0),
(7, 'Manager', 'walkin', 1, 1, 0, 0),
(8, 'Manager', 'customers', 1, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `promos`
--

CREATE TABLE `promos` (
  `promoID` int(11) NOT NULL,
  `promo_code` varchar(50) NOT NULL,
  `promo_name` varchar(100) NOT NULL,
  `discount_type` enum('percentage','fixed') DEFAULT 'percentage',
  `discount_value` decimal(10,2) NOT NULL,
  `min_spend` decimal(10,2) DEFAULT 0.00,
  `max_discount` decimal(10,2) DEFAULT NULL,
  `valid_from` date NOT NULL,
  `valid_until` date NOT NULL,
  `is_active` tinyint(4) DEFAULT 1,
  `usage_limit` int(11) DEFAULT NULL,
  `used_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `promos`
--

INSERT INTO `promos` (`promoID`, `promo_code`, `promo_name`, `discount_type`, `discount_value`, `min_spend`, `max_discount`, `valid_from`, `valid_until`, `is_active`, `usage_limit`, `used_count`, `created_at`) VALUES
(1, 'WELCOME10', 'Welcome 10% Off', 'percentage', 10.00, 500.00, NULL, '2026-05-02', '2026-06-01', 1, NULL, 0, '2026-05-02 08:07:08'),
(2, 'NEWYEAR20', 'New Year Special', 'percentage', 20.00, 1000.00, NULL, '2026-05-02', '2026-06-01', 1, NULL, 0, '2026-05-02 08:07:08');

-- --------------------------------------------------------

--
-- Table structure for table `salon_staff`
--

CREATE TABLE `salon_staff` (
  `staffID` int(11) NOT NULL,
  `firstName` varchar(50) NOT NULL,
  `middleName` varchar(50) DEFAULT NULL,
  `lastName` varchar(50) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('Owner','Manager','Staff') DEFAULT 'Staff',
  `specialization` varchar(100) DEFAULT NULL,
  `contactNumber` varchar(20) DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_number` varchar(20) DEFAULT NULL,
  `schedule` text DEFAULT NULL,
  `is_active` tinyint(4) DEFAULT 1,
  `is_archived` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `salon_staff`
--

INSERT INTO `salon_staff` (`staffID`, `firstName`, `middleName`, `lastName`, `username`, `password`, `email`, `role`, `specialization`, `contactNumber`, `emergency_contact_name`, `emergency_contact_number`, `schedule`, `is_active`, `is_archived`, `created_at`) VALUES
(1, 'Janet', NULL, 'Arkias', 'janet', 'owner123', 'janet@salon.com', 'Owner', 'Owner / Physical Logbook Records', NULL, NULL, NULL, NULL, 1, 0, '2026-05-02 08:07:07'),
(2, 'Janah', NULL, 'Arkias', 'janah', 'manager123', 'janah@salon.com', 'Manager', 'Customer Booking Messages / Appointment Coordinator', NULL, NULL, NULL, NULL, 1, 0, '2026-05-02 08:07:07'),
(3, 'Hace Mae', NULL, 'Mendoza', 'hace', 'manager123', 'hace@salon.com', 'Manager', 'Booking Records / Operational Reports Coordinator', NULL, NULL, NULL, NULL, 1, 0, '2026-05-02 08:07:07'),
(4, 'Aurelia', NULL, 'Alcantara', 'aurelia', 'staff123', 'aurelia@salon.com', 'Staff', 'All-Around Salon Services / Operations', NULL, NULL, NULL, NULL, 1, 0, '2026-05-02 08:07:07'),
(5, 'Pau', NULL, 'Badilla', 'pau', 'staff123', 'pau@salon.com', 'Staff', 'All-Around Salon Services / Operations', NULL, NULL, NULL, NULL, 1, 0, '2026-05-02 08:07:07'),
(6, 'Charls', NULL, 'Baguitan', 'charls', 'staff123', 'charls@salon.com', 'Staff', 'Barbering Services', NULL, NULL, NULL, NULL, 1, 0, '2026-05-02 08:07:07'),
(7, 'Robert', NULL, 'Ramos', 'robert', 'staff123', 'robert@salon.com', 'Staff', 'Barbering Services', NULL, NULL, NULL, NULL, 1, 0, '2026-05-02 08:07:07'),
(8, 'Jean', NULL, 'Abrera', 'jean', 'staff123', 'jean@salon.com', 'Staff', 'Hair and Makeup Services', NULL, NULL, NULL, NULL, 1, 0, '2026-05-02 08:07:07'),
(9, 'Krishna', NULL, 'Arkias', 'krishna', 'staff123', 'krishna@salon.com', 'Staff', 'Hair and Makeup Services', NULL, NULL, NULL, NULL, 1, 0, '2026-05-02 08:07:07'),
(10, 'Xena', NULL, 'Arkias', 'xena', 'staff123', 'xena@salon.com', 'Staff', 'Hair and Makeup Services', NULL, NULL, NULL, NULL, 1, 0, '2026-05-02 08:07:07');

-- --------------------------------------------------------

--
-- Table structure for table `service`
--

CREATE TABLE `service` (
  `serviceID` int(11) NOT NULL,
  `serviceName` varchar(100) NOT NULL,
  `catID` int(11) DEFAULT NULL,
  `servicePrice` decimal(10,2) NOT NULL,
  `duration` time NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(500) DEFAULT NULL,
  `is_home_service` tinyint(4) DEFAULT 0,
  `is_active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `service`
--

INSERT INTO `service` (`serviceID`, `serviceName`, `catID`, `servicePrice`, `duration`, `description`, `image`, `is_home_service`, `is_active`, `created_at`) VALUES
(1, 'Hair Cut - Men', 1, 150.00, '00:30:00', 'Professional haircut for men', 'https://www.upprmgmt.com/wp-content/uploads/2025/02/image.webp', 0, 1, '2026-05-02 08:07:07'),
(2, 'Hair Cut - Women', 1, 350.00, '01:00:00', 'Professional haircut for women', 'https://plus.unsplash.com/premium_photo-1669675935927-0ed8935e6600?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8NXx8aGFpciUyMGN1dHxlbnwwfHwwfHx8MA%3D%3D', 0, 1, '2026-05-02 08:07:07'),
(3, 'Hair Cut Premium - Women', 1, 500.00, '01:30:00', 'Premium haircut with styling', 'https://plus.unsplash.com/premium_photo-1669675936121-6d3d42244ab5?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MzN8fGhhaXIlMjBjdXR8ZW58MHx8MHx8fDA%3D', 0, 1, '2026-05-02 08:07:07'),
(4, 'Hair Rebond - Short', 1, 2500.00, '04:00:00', 'Chemical straightening for short hair', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQhIjG5EFPxQ7o0Sv1YaZfxCP7r0MhP7zTheg&s', 0, 1, '2026-05-02 08:07:07'),
(5, 'Hair Rebond - Medium', 1, 4000.00, '06:00:00', 'Chemical straightening for medium hair', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQrXNPbm7XnnY58a6o0QOrANRN4GYY8mqbymg&s', 0, 1, '2026-05-02 08:07:07'),
(6, 'Hair Rebond - Long', 1, 6000.00, '08:00:00', 'Chemical straightening for long hair', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSToWf2OqPahnF4hZ8-rgTwXOxamotAS3k71w&s', 0, 1, '2026-05-02 08:07:07'),
(7, 'Hair Color - Basic', 1, 1500.00, '02:00:00', 'Basic hair color application', 'https://images.unsplash.com/photo-1593980966860-3b2382e54a29?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MTh8fEhhaXIlMjBDb2xvcnxlbnwwfHwwfHx8MA%3D%3D', 0, 1, '2026-05-02 08:07:07'),
(8, 'Hair Color - Premium', 1, 3000.00, '03:00:00', 'Premium hair color with treatment', 'https://media.istockphoto.com/id/1089535418/photo/hair-colors-palette-as-background-dyed-samples.webp?a=1&b=1&s=612x612&w=0&k=20&c=5hdBsAI9gnp3Z2BNt0IuMN-nu8k2G84j_VXwJ4mdaVg=', 0, 1, '2026-05-02 08:07:07'),
(9, 'Hair Color - Full', 1, 5000.00, '05:00:00', 'Full hair color with highlights', 'https://images.unsplash.com/photo-1574773004910-1eeaabb62b55?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MTF8fEhhaXIlMjBDb2xvciUyMC0lMjBGdWxsfGVufDB8fDB8fHww', 0, 1, '2026-05-02 08:07:07'),
(10, 'Hair Curl - Digital Perm', 1, 2500.00, '03:00:00', 'Digital perm for soft curls', 'https://images.unsplash.com/photo-1604427779224-09124a8f1029?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MTB8fEhhaXIlMjBDdXJsJTIwLSUyMERpZ2l0YWwlMjBQZXJtfGVufDB8fDB8fHww', 0, 1, '2026-05-02 08:07:07'),
(11, 'Hair Curl - Cold Perm', 1, 1500.00, '02:00:00', 'Cold perm for defined curls', 'https://images.unsplash.com/photo-1560264641-1b5191cc63e2?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MTF8fEhhaXIlMjBDdXJsJTIwLSUyMENvbGQlMjBQZXJtfGVufDB8fDB8fHww', 0, 1, '2026-05-02 08:07:07'),
(12, 'Hair Curl - Premium', 1, 4000.00, '04:00:00', 'Premium curling service', 'https://media.istockphoto.com/id/1294339577/photo/young-beautiful-woman.webp?a=1&b=1&s=612x612&w=0&k=20&c=IILOqvl3raePMkHI6h1iDJaeepdC26tAF6bJ3y4-XGw=', 0, 1, '2026-05-02 08:07:07'),
(13, 'Hair Brazilian', 1, 3500.00, '04:00:00', 'Brazilian blowout smoothing treatment', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSXzWGBmIDrJBcg7O1pSHDNXddVgEkkE-xGJg&s', 0, 1, '2026-05-02 08:07:07'),
(14, 'Hair Brazilian Premium', 1, 5500.00, '05:00:00', 'Premium Brazilian treatment', 'https://m.media-amazon.com/images/I/61O1++vepCL.jpg', 0, 1, '2026-05-02 08:07:07'),
(15, 'Hair Cellophane', 1, 2000.00, '02:30:00', 'Cellophane color treatment', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQDEW-z-TEhiMteCla7V8TDiWdCr84kQbeY7w&s', 0, 1, '2026-05-02 08:07:07'),
(16, 'Hair Cellophane Premium', 1, 3500.00, '03:00:00', 'Premium cellophane treatment', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRdrU7tYv3ElhYN-z-jFhjBT-_cn1fE6rFajQ&s', 0, 1, '2026-05-02 08:07:07'),
(17, 'Hair Celloshine', 1, 2000.00, '02:30:00', 'Celloshine gloss treatment', 'https://weshoppeonline.wordpress.com/wp-content/uploads/2018/04/fb_img_1524184670891-2098130075.jpg?w=620', 0, 1, '2026-05-02 08:07:07'),
(18, 'Hair Celloshine Premium', 1, 3500.00, '03:00:00', 'Premium celloshine treatment', 'https://media.istockphoto.com/id/2260830463/photo/woman-with-beautiful-shiny-smooth-hair-on-black-background.webp?a=1&b=1&s=612x612&w=0&k=20&c=LjpIDaSzJQAUTyXEM3nTcY9JSytWE0OLWhyevOM1Lkw=', 0, 1, '2026-05-02 08:07:07'),
(19, 'Hair Highlights - Partial', 1, 2000.00, '03:00:00', 'Partial hair highlights', 'https://plus.unsplash.com/premium_photo-1669675935800-c14104107358?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MXx8UGFydGlhbCUyMGhhaXIlMjBoaWdobGlnaHRzfGVufDB8fDB8fHww', 0, 1, '2026-05-02 08:07:07'),
(20, 'Hair Highlights - Full', 1, 4000.00, '05:00:00', 'Full hair highlights', 'https://images.unsplash.com/photo-1588238587906-5cbf2617fa85?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MTB8fEhhaXIlMjBIaWdobGlnaHRzJTIwLSUyMEZ1bGx8ZW58MHx8MHx8fDA%3D', 0, 1, '2026-05-02 08:07:07'),
(21, 'Hair Highlights - Premium', 1, 6000.00, '06:00:00', 'Premium highlighting service', 'https://images.unsplash.com/photo-1554519934-e32b1629d9ee?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8Mnx8SGFpciUyMEhpZ2hsaWdodHMlMjAtJTIwUHJlbWl1bXxlbnwwfHwwfHx8MA%3D%3D', 0, 1, '2026-05-02 08:07:07'),
(22, 'Hair Treatment - Basic', 1, 500.00, '00:45:00', 'Basic hair treatment', 'https://media.istockphoto.com/id/2241450625/photo/female-stylist-rinsing-womans-hair-at-a-salon.webp?a=1&b=1&s=612x612&w=0&k=20&c=IANV_ZSr3ZlLSr8FnySkcTk7axv9u80cfqperGzr3HU=', 0, 1, '2026-05-02 08:07:07'),
(23, 'Hair Treatment - Premium', 1, 1000.00, '01:15:00', 'Premium deep conditioning treatment', 'https://media.istockphoto.com/id/2243762569/photo/warm-water-and-soft-touches-make-her-feel-completely-pampered-in-the-elegant-salon.webp?a=1&b=1&s=612x612&w=0&k=20&c=S-kq_L1u_HJLRmOx8GYIIg2uQ78mUIeUKLnrB6QqNaE=', 0, 1, '2026-05-02 08:07:07'),
(24, 'Hair Treatment - Luxury', 1, 1500.00, '01:30:00', 'Luxury hair spa treatment', 'https://media.istockphoto.com/id/2207575603/photo/woman-combing-long-wet-hair-for-beauty-and-haircare-routine.webp?a=1&b=1&s=612x612&w=0&k=20&c=WkEcF7ZOPRNs091iAGhbgaM-ajMp6byTdAAuxjdppWM=', 0, 1, '2026-05-02 08:07:07'),
(25, 'Hair Mask - Basic', 1, 300.00, '00:30:00', 'Basic hair mask application', 'https://plus.unsplash.com/premium_photo-1661587319908-3949b1c10345?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8Mjl8fGhhaXIlMjBtYXNrJTIwYmFzaWN8ZW58MHx8MHx8fDA%3D', 0, 1, '2026-05-02 08:07:07'),
(26, 'Hair Mask - Premium', 1, 600.00, '00:45:00', 'Premium nourishing hair mask', 'https://plus.unsplash.com/premium_photo-1661964345090-12eaa5abde29?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MXx8aGFpciUyMG1hc2slMjBwcmVtaXVtfGVufDB8fDB8fHww', 0, 1, '2026-05-02 08:07:07'),
(27, 'Hair Mask - Intensive', 1, 800.00, '01:00:00', 'Intensive repair hair mask', 'https://media.istockphoto.com/id/946986848/photo/hair-masks-woman-applying-mask-with-brush-on-wet-long-hair.jpg?s=612x612&w=0&k=20&c=DJz9T8YoA89_Oe1vy3uMdoKA8kk6Djbm7T5E8RPh6Cg=', 0, 1, '2026-05-02 08:07:07'),
(28, 'Hair Keratin - Basic', 1, 2000.00, '02:30:00', 'Basic keratin smoothing', 'https://media.istockphoto.com/id/1572200858/photo/luxury-texture-and-woman-with-hair-care-cosmetics-and-wellness-against-a-blue-studio.webp?a=1&b=1&s=612x612&w=0&k=20&c=w55oeSQW-88m0AU2SJCuwT-bMAK13Ej0AzE4XVSgY5M=', 0, 1, '2026-05-02 08:07:07'),
(29, 'Hair Keratin - Premium', 1, 3500.00, '03:30:00', 'Premium keratin treatment', 'https://media.istockphoto.com/id/1511087250/photo/hair-care-beauty-and-back-of-woman-in-studio-with-shiny-clean-and-messy-dirty-hairstyle.webp?a=1&b=1&s=612x612&w=0&k=20&c=gBGr5NYr3LJfbu_PWGS3lKezh8vrh4cnVIv6A1zr7P8=', 0, 1, '2026-05-02 08:07:07'),
(30, 'Hair Keratin - Luxury', 1, 5000.00, '04:00:00', 'Luxury keratin smoothing', 'https://media.istockphoto.com/id/2265238910/photo/woman-getting-professional-shiny-hair-care-treatment.webp?a=1&b=1&s=612x612&w=0&k=20&c=ss2tiMt77kYos7Y1hfQ3OVIFz_6yksO2MyJeOc25bGw=', 0, 1, '2026-05-02 08:07:07'),
(31, 'Signature Massage - 1 Hour', 2, 600.00, '01:00:00', 'Relaxing full body massage', 'https://images.unsplash.com/photo-1639162906614-0603b0ae95fd?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8Nnx8U2lnbmF0dXJlJTIwTWFzc2FnZSUyMC0lMjAxJTIwSG91cnxlbnwwfHwwfHx8MA%3D%3D', 0, 1, '2026-05-02 08:07:07'),
(32, 'Signature Massage - 1.5 Hours', 2, 850.00, '01:30:00', 'Extended relaxing massage', 'https://plus.unsplash.com/premium_photo-1681873744075-cb398a509f51?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MXx8U2lnbmF0dXJlJTIwTWFzc2FnZSUyMC0lMjAxLjUlMjBIb3Vyc3xlbnwwfHwwfHx8MA%3D%3D', 0, 1, '2026-05-02 08:07:07'),
(33, 'Natural Makeup Look', 3, 1399.00, '01:15:00', 'Natural everyday makeup / Graduation makeup', 'https://plus.unsplash.com/premium_photo-1678217495241-6d596cc7a65c?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MXx8TmF0dXJhbCUyME1ha2V1cCUyMExvb2t8ZW58MHx8MHx8fDA%3D', 1, 1, '2026-05-02 08:07:07'),
(34, 'Glam Makeup Look', 3, 1499.00, '01:45:00', 'Glamorous party makeup / Acquaintance makeup', 'https://images.unsplash.com/photo-1630084775816-7abb7383ded5?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8Mnx8R2xhbSUyME1ha2V1cCUyMExvb2t8ZW58MHx8MHx8fDA%3D', 1, 1, '2026-05-02 08:07:07'),
(35, 'Pageant Makeup Look', 3, 2899.00, '02:30:00', 'Professional pageant makeup (1 look)', 'https://images.unsplash.com/photo-1617655345937-95fafc9013f3?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MTR8fFBhZ2VhbnQlMjBNYWtldXAlMjBMb29rfGVufDB8fDB8fHww', 1, 1, '2026-05-02 08:07:07'),
(36, 'Debut Makeup Look', 3, 1899.00, '02:30:00', 'Special debut makeup (1 look)', 'https://images.unsplash.com/photo-1737514941661-95874ec8e3de?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MTB8fERlYnV0JTIwTWFrZXVwJTIwTG9va3xlbnwwfHwwfHx8MA%3D%3D', 1, 1, '2026-05-02 08:07:07'),
(37, 'Makeup with Contact Lenses', 3, 2399.00, '02:30:00', 'Makeup application with contact lens insertion', 'https://images.unsplash.com/photo-1648249969490-44f3052a3721?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8M3x8TWFrZXVwJTIwd2l0aCUyMENvbnRhY3QlMjBMZW5zZXN8ZW58MHx8MHx8fDA%3D', 1, 1, '2026-05-02 08:07:07'),
(38, 'Wedding Makeup Trial - Traditional', 4, 1899.00, '02:30:00', 'Wedding makeup trial with traditional products', 'https://images.unsplash.com/photo-1764269717552-92a941a197f7?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8M3x8V2VkZGluZyUyME1ha2V1cCUyMFRyaWFsJTIwLSUyMFRyYWRpdGlvbmFsfGVufDB8fDB8fHww', 1, 1, '2026-05-02 08:07:07'),
(39, 'Wedding Makeup Trial - Airbrush', 4, 2499.00, '03:00:00', 'Wedding makeup trial with airbrush', 'https://plus.unsplash.com/premium_photo-1723651208583-8478a4b3d133?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MXx8V2VkZGluZyUyME1ha2V1cCUyMFRyaWFsJTIwLSUyMEFpcmJydXNofGVufDB8fDB8fHww', 1, 1, '2026-05-02 08:07:07'),
(40, 'Prenup Makeup - Traditional', 4, 1899.00, '02:30:00', 'Pre-nuptial photoshoot makeup (traditional)', 'https://media.istockphoto.com/id/1790318870/photo/a-woman-wearing-makeup-close-up-shot.webp?a=1&b=1&s=612x612&w=0&k=20&c=5AO6VjnmXs150O5iOJWJgK7N_A_SLMwVTuAn2nAOvDA=', 1, 1, '2026-05-02 08:07:07'),
(41, 'Prenup Makeup - Airbrush', 4, 2899.00, '03:30:00', 'Pre-nuptial photoshoot makeup (airbrush)', 'https://media.istockphoto.com/id/898396928/photo/makeup-artist-using-airbrusher.webp?a=1&b=1&s=612x612&w=0&k=20&c=r0w01c5KzFI0BqrW_pGuz15s3n8dPpQ8KyzGDrsW7L8=', 1, 1, '2026-05-02 08:07:07'),
(42, 'Wedding Ceremony Makeup - 2 Looks', 4, 5699.00, '05:00:00', 'Complete wedding day makeup (2 looks)', 'https://media.istockphoto.com/id/1174367741/photo/the-perfect-shade-for-a-special-day.webp?a=1&b=1&s=612x612&w=0&k=20&c=nOERs7pa312NAi2vx-F5fGxcHPca_D7nTSYJt8SVqY4=', 1, 1, '2026-05-02 08:07:07'),
(43, 'Classic Cleansing Facial', 5, 399.00, '00:50:00', 'Deep cleansing facial treatment', 'https://images.unsplash.com/photo-1761718210089-ba3bb5ccb54f?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8Mnx8Q2xhc3NpYyUyMENsZWFuc2luZyUyMEZhY2lhbHxlbnwwfHwwfHx8MA%3D%3D', 0, 1, '2026-05-02 08:07:07'),
(44, 'LED Light Therapy', 5, 599.00, '00:35:00', 'LED light therapy treatment', 'https://plus.unsplash.com/premium_photo-1732836188008-6b4414000d99?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MXx8TEVEJTIwTGlnaHQlMjBUaGVyYXB5fGVufDB8fDB8fHww', 0, 1, '2026-05-02 08:07:07'),
(45, 'Collagen Therapy', 5, 599.00, '00:50:00', 'Collagen boosting facial', 'https://images.unsplash.com/photo-1643402305704-474b129161a5?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MTh8fENvbGxhZ2VuJTIwVGhlcmFweXxlbnwwfHwwfHx8MA%3D%3D', 0, 1, '2026-05-02 08:07:07'),
(46, 'Brightening Facial', 5, 699.00, '01:15:00', 'Skin brightening facial treatment', 'https://plus.unsplash.com/premium_photo-1663011276992-6e52adb20dd3?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MXx8QnJpZ2h0ZW5pbmclMjBGYWNpYWx8ZW58MHx8MHx8fDA%3D', 0, 1, '2026-05-02 08:07:07'),
(47, 'Anti-Aging Facial', 5, 899.00, '01:15:00', 'Anti-aging and wrinkle treatment', 'https://images.unsplash.com/photo-1647004692483-c5d942fe1137?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8OHx8QW50aS1BZ2luZyUyMEZhY2lhbHxlbnwwfHwwfHx8MA%3D%3D', 0, 1, '2026-05-02 08:07:07'),
(48, 'Eyelash Perm & Tint', 6, 299.00, '00:50:00', 'Eyelash lift with tint', 'https://media.istockphoto.com/id/1443199747/photo/make-up-artist-makes-the-procedure-of-lamination-and-dyeing-of-eyelashes-to-a-beautiful-woman.webp?a=1&b=1&s=612x612&w=0&k=20&c=aqHEMec7aXVaYegAPZrNGwZ94a7zj_7NL6smDrhxJgo=', 0, 1, '2026-05-02 08:07:07'),
(49, 'Eyelash Extension - Classic', 6, 499.00, '01:45:00', 'Classic individual lash extensions', 'https://plus.unsplash.com/premium_photo-1661763916562-20e0314ba95f?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MTd8fEV5ZWxhc2glMjBFeHRlbnNpb24lMjAtJTIwQ2xhc3NpY3xlbnwwfHwwfHx8MA%3D%3D', 0, 1, '2026-05-02 08:07:07'),
(50, 'Eyelash Extension - Hybrid', 6, 699.00, '02:30:00', 'Hybrid lash extensions (classic + volume)', 'https://plus.unsplash.com/premium_photo-1661502931069-1d0078d82f4d?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8NXx8RXllbGFzaCUyMEV4dGVuc2lvbiUyMC0lMjBIeWJyaWR8ZW58MHx8MHx8fDA%3D', 0, 1, '2026-05-02 08:07:07'),
(51, 'Eyelash Extension - Volume', 6, 899.00, '02:30:00', 'Full volume lash extensions', 'https://images.unsplash.com/photo-1657271516922-6eab2182f17d?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8OHx8RXllbGFzaCUyMEV4dGVuc2lvbiUyMC0lMjBWb2x1bWV8ZW58MHx8MHx8fDA%3D', 0, 1, '2026-05-02 08:07:07'),
(52, 'Doll Eye Lashes', 6, 799.00, '02:30:00', 'Doll eye lash extension style', 'https://images.unsplash.com/photo-1639629509821-c54cdd984227?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8NHx8RG9sbCUyMEV5ZSUyMGV5ZWxhc2glMjBleHRlbnRpb25zfGVufDB8fDB8fHww', 0, 1, '2026-05-02 08:07:07'),
(53, 'Whispy Lashes', 6, 899.00, '02:30:00', 'Whispy natural lash style', 'https://images.unsplash.com/photo-1683719312734-e31de63957ab?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8Mnx8V2hpc3B5JTIwZXllbGFzaCUyMGV4dGVudGlvbnN8ZW58MHx8MHx8fDA%3D', 0, 1, '2026-05-02 08:07:07'),
(54, 'Cat Eye Lashes', 6, 899.00, '02:30:00', 'Cat eye lash extension style', 'https://images.unsplash.com/photo-1567629307995-b9f33097bd30?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8Nnx8Q2F0JTIwRXllJTIwZXllbGFzaCUyMGV4dGVudGlvbnN8ZW58MHx8MHx8fDA%3D', 0, 1, '2026-05-02 08:07:07'),
(55, 'Manhua Anime Lashes', 6, 1299.00, '02:30:00', 'Anime-style Manhua lash extensions', 'https://media.istockphoto.com/id/860165150/photo/womans-eyes-expression-front-view.webp?a=1&b=1&s=612x612&w=0&k=20&c=Dxoybg8J7fF7h4IhaJqCfPUuqaQIOh39M0IXVzS4B0c=', 0, 1, '2026-05-02 08:07:07'),
(56, 'Eyebrow Shaping - Shaving', 7, 99.00, '00:20:00', 'Quick eyebrow shaping with razor', 'https://media.istockphoto.com/id/636982026/photo/eyebrows-treatment-in-a-wellness-center.webp?a=1&b=1&s=612x612&w=0&k=20&c=TrwJXXyOIXng5fOrUg1JVPKZRm1Yy-j83Xb_71FSPRU=', 0, 1, '2026-05-02 08:07:07'),
(57, 'Eyebrow Threading', 7, 249.00, '00:25:00', 'Precise eyebrow threading', 'https://images.unsplash.com/photo-1519415387722-a1c3bbef716c?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8Mnx8RXllYnJvdyUyMFRocmVhZGluZ3xlbnwwfHwwfHx8MA%3D%3D', 0, 1, '2026-05-02 08:07:07'),
(58, 'Eyebrow Lamination & Tint', 7, 299.00, '00:50:00', 'Brow lamination with tint', 'https://plus.unsplash.com/premium_photo-1718626727310-59a0929862b6?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MXx8RXllYnJvdyUyMExhbWluYXRpb24lMjAlMjYlMjBUaW50fGVufDB8fDB8fHww', 0, 1, '2026-05-02 08:07:07'),
(59, 'Microshading', 7, 2999.00, '02:30:00', 'Semi-permanent brow shading', 'https://images.unsplash.com/photo-1668044020880-42a2cb52953a?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MTl8fE1pY3Jvc2hhZGluZ2V5ZWJyb3dzfGVufDB8fDB8fHww', 0, 1, '2026-05-02 08:07:07'),
(60, 'Microblading', 7, 2999.00, '02:30:00', 'Semi-permanent brow strokes', 'https://plus.unsplash.com/premium_photo-1718626726420-3ced722e0353?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MXx8TWljcm9ibGFkaW5nJTIwZXllYnJvd3N8ZW58MHx8MHx8fDA%3D', 0, 1, '2026-05-02 08:07:07'),
(61, 'Combo Shade & Blade', 7, 3999.00, '03:30:00', 'Combination microblading and shading', 'https://images.unsplash.com/photo-1698335107935-e2c8287c4947?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8Mnx8QmxhZGUlMjBleWVicm93c3xlbnwwfHwwfHx8MA%3D%3D', 0, 1, '2026-05-02 08:07:07'),
(62, 'Henna Brows with Lash Curl', 7, 799.00, '01:15:00', 'Henna brow tint with eyelash curl', 'https://images.unsplash.com/photo-1666798122039-3aebe8e153da?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8Mnx8SGVubmElMjBCcm93cyUyMHdpdGglMjBMYXNoJTIwQ3VybHxlbnwwfHwwfHx8MA%3D%3D', 0, 1, '2026-05-02 08:07:07'),
(63, 'Underarm Wax', 8, 349.00, '00:25:00', 'Underarm hair removal', 'https://media.istockphoto.com/id/1300175220/photo/armpit-waxing-procedure-in-a-beauty-salon-master-applies-wax-to-womans-armpit.webp?a=1&b=1&s=612x612&w=0&k=20&c=7_6ocCzu06R-2gzHtAbTXVjZ0CA1lu418KQN_6IDkwg=', 0, 1, '2026-05-02 08:07:07'),
(64, 'Arms Wax', 8, 399.00, '00:35:00', 'Full arms hair removal', 'https://media.istockphoto.com/id/2235571630/photo/young-woman-getting-her-arms-waxed-by-a-female-wax-technician-with-wax-strips-in-a-beauty.webp?a=1&b=1&s=612x612&w=0&k=20&c=kkOaki_P1ULJjvdpmBYFnOD8oCpoVi8b7KmYplfCMCU=', 0, 1, '2026-05-02 08:07:07'),
(65, 'Legs Wax - Half', 8, 299.00, '00:35:00', 'Half legs hair removal', 'https://plus.unsplash.com/premium_photo-1661340963346-9bcf4a864571?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MXx8TGVncyUyMFdheCUyMC1oYWxmfGVufDB8fDB8fHww', 0, 1, '2026-05-02 08:07:07'),
(66, 'Legs Wax - Full', 8, 499.00, '01:00:00', 'Full legs hair removal', 'https://plus.unsplash.com/premium_photo-1664375246533-472ff98fdd1e?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8OXx8TGVncyUyMFdheCUyMC1mdWxsfGVufDB8fDB8fHww', 0, 1, '2026-05-02 08:07:07'),
(67, 'Brazilian Wax', 8, 999.00, '00:50:00', 'Full Brazilian waxing', 'https://innovitalespa.com/cdn/shop/articles/4365527e-87fc-41a3-a90b-35e6632785bd_e766da44-fc38-46f6-8b0b-22e75b7ca2a0.webp?v=1773023816', 0, 1, '2026-05-02 08:07:07'),
(68, 'Whole Body Wax', 8, 1999.00, '02:30:00', 'Complete body waxing', 'https://plus.unsplash.com/premium_photo-1664298513225-e4f369816d4d?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MTd8fFdob2xlJTIwQm9keSUyMFdheHxlbnwwfHwwfHx8MA%3D%3D', 0, 1, '2026-05-02 08:07:07'),
(69, 'Foot Spa & Pedicure', 9, 799.00, '01:15:00', 'Relaxing foot spa with pedicure', 'https://images.unsplash.com/photo-1519419451778-14599a49ec41?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8NHx8Rm9vdCUyMFNwYSUyMCUyNiUyMFBlZGljdXJlfGVufDB8fDB8fHww', 0, 1, '2026-05-02 08:07:07'),
(70, 'Hand Spa & Manicure', 9, 499.00, '00:50:00', 'Rejuvenating hand spa with manicure', 'https://plus.unsplash.com/premium_photo-1723568625524-4101dee5991c?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8NXx8SGFuZCUyMFNwYSUyMCUyNiUyME1hbmljdXJlfGVufDB8fDB8fHww', 0, 1, '2026-05-02 08:07:07'),
(71, 'Foot & Hand Spa Combo', 9, 1199.00, '02:00:00', 'Complete foot and hand spa treatment', 'https://media.istockphoto.com/id/2236363751/photo/man-indulges-in-blissful-foot-massage-at-luxurious-spa-salon-quiescent.webp?a=1&b=1&s=612x612&w=0&k=20&c=bu5u0gX9uvcKDrgz8V560EahyxWvMG47QrdqzyNi7Zk=', 0, 1, '2026-05-02 08:07:07'),
(72, 'Regular Manicure', 10, 199.00, '00:35:00', 'Basic manicure with polish', 'https://images.unsplash.com/photo-1643648854897-7b5845b4c04c?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MTB8fFJlZ3VsYXIlMjBNYW5pY3VyZXxlbnwwfHwwfHx8MA%3D%3D', 0, 1, '2026-05-02 08:07:07'),
(73, 'Gel Manicure', 10, 349.00, '01:15:00', 'Gel polish manicure', 'https://images.unsplash.com/photo-1659391542239-9648f307c0b1?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8NHx8R2VsJTIwTWFuaWN1cmV8ZW58MHx8MHx8fDA%3D', 0, 1, '2026-05-02 08:07:07'),
(74, 'Regular Pedicure', 10, 249.00, '00:50:00', 'Basic pedicure with polish', 'https://plus.unsplash.com/premium_photo-1661499249417-c20d6b668469?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MTN8fFJlZ3VsYXIlMjBQZWRpY3VyZXxlbnwwfHwwfHx8MA%3D%3D', 0, 1, '2026-05-02 08:07:07'),
(75, 'Gel Pedicure', 10, 399.00, '01:15:00', 'Gel polish pedicure', 'https://plus.unsplash.com/premium_photo-1770613318466-ea6455476ba6?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MTN8fEdlbCUyMFBlZGljdXJlLXxlbnwwfHwwfHx8MA%3D%3D', 0, 1, '2026-05-02 08:07:07'),
(76, 'Nail Extensions - Minimalist', 10, 699.00, '02:30:00', 'Simple nail extension design', 'https://images.unsplash.com/photo-1667207229735-266c430cca15?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8OHx8TmFpbCUyMEV4dGVuc2lvbnMlMjAtJTIwTWluaW1hbGlzdHxlbnwwfHwwfHx8MA%3D%3D', 0, 1, '2026-05-02 08:07:07'),
(77, 'Nail Extensions - Basic', 10, 799.00, '02:30:00', 'Basic nail extension and design', 'https://plus.unsplash.com/premium_photo-1671719428436-806b92f38a50?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MTd8fE5haWwlMjBFeHRlbnNpb25zJTIwLSUyMEJhc2ljLXxlbnwwfHwwfHx8MA%3D%3D', 0, 1, '2026-05-02 08:07:07'),
(78, 'Nail Extensions - Premium', 10, 999.00, '03:00:00', 'Premium nail extension service', 'https://media.istockphoto.com/id/2255431770/photo/wedding-manicure.jpg?s=612x612&w=0&k=20&c=gaHr6BP645Jn3zF-D7cGJdDvxeIZib-Ixg-ne9nH74M=', 0, 1, '2026-05-02 08:07:07'),
(79, 'Premium Nail Design', 10, 999.00, '04:00:00', 'Intricate premium nail art design', 'https://images.unsplash.com/photo-1777288390469-828f8816231c?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8Nnx8UHJlbWl1bSUyME5haWwlMjBEZXNpZ258ZW58MHx8MHx8fDA%3D', 0, 1, '2026-05-02 08:07:07'),
(80, 'Luxury Nail Design', 10, 1499.00, '05:00:00', 'Luxury custom nail art design', 'https://images.unsplash.com/photo-1777287216954-2b4b22bb6bf2?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8N3x8bHV4dXJ5JTIwbmFpbCUyMGRlc2lnbnxlbnwwfHwwfHx8MA%3D%3D', 0, 1, '2026-05-02 08:07:07'),
(81, 'Home Service Fee', 1, 500.00, '00:00:00', 'Additional fee for home service', 'https://media.istockphoto.com/id/2271915700/photo/asian-female-spa-guests-welcomed-by-asian-female-staff-at-luxury-wellness-entrance.webp?a=1&b=1&s=612x612&w=0&k=20&c=JSWgMV0s_xv_I1Heaq_0Jq8Sp2YgXKk7nzmcmPDEKXw=', 0, 1, '2026-05-02 08:07:07'),
(82, 'Graveyard Fee (10PM-6AM)', 1, 1000.00, '00:00:00', 'Additional fee for late night service', 'https://images.unsplash.com/photo-1521590832167-7bcbfaa6381f?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MTV8fEdyYXZleWFyZCUyMEZlZSUyMGluJTIwc2Fsb258ZW58MHx8MHx8fDA%3D', 0, 1, '2026-05-02 08:07:07');

-- --------------------------------------------------------

--
-- Table structure for table `service_categories`
--

CREATE TABLE `service_categories` (
  `catID` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_event_type` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `service_categories`
--

INSERT INTO `service_categories` (`catID`, `category_name`, `description`, `is_event_type`, `created_at`) VALUES
(1, 'Hair Services', 'Haircut, color, rebond, treatment, and styling services', 0, '2026-05-02 08:07:07'),
(2, 'Massage Services', 'Relaxing and therapeutic massage services', 0, '2026-05-02 08:07:07'),
(3, 'Makeup Services', 'Professional makeup for events and special occasions', 1, '2026-05-02 08:07:07'),
(4, 'Wedding Makeup', 'Specialized wedding makeup packages', 1, '2026-05-02 08:07:07'),
(5, 'Facial Services', 'Skin care and facial treatments', 0, '2026-05-02 08:07:07'),
(6, 'Eyelash Services', 'Eyelash extensions, perm, and tint', 0, '2026-05-02 08:07:07'),
(7, 'Brow Services', 'Eyebrow shaping, threading, lamination, and microblading', 0, '2026-05-02 08:07:07'),
(8, 'Waxing Services', 'Hair removal services', 0, '2026-05-02 08:07:07'),
(9, 'Hand & Foot Spa', 'Spa treatments for hands and feet', 0, '2026-05-02 08:07:07'),
(10, 'Nail Services', 'Manicure, pedicure, and nail art', 0, '2026-05-02 08:07:07');

-- --------------------------------------------------------

--
-- Table structure for table `staff_availability`
--

CREATE TABLE `staff_availability` (
  `availID` int(11) NOT NULL,
  `staffID` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_available` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff_availability`
--

INSERT INTO `staff_availability` (`availID`, `staffID`, `day_of_week`, `start_time`, `end_time`, `is_available`) VALUES
(1, 4, 'Monday', '09:00:00', '18:00:00', 1),
(2, 4, 'Tuesday', '09:00:00', '18:00:00', 1),
(3, 4, 'Wednesday', '09:00:00', '18:00:00', 1),
(4, 4, 'Thursday', '09:00:00', '18:00:00', 1),
(5, 4, 'Friday', '09:00:00', '18:00:00', 1),
(6, 5, 'Tuesday', '10:00:00', '19:00:00', 1),
(7, 5, 'Wednesday', '10:00:00', '19:00:00', 1),
(8, 5, 'Thursday', '10:00:00', '19:00:00', 1),
(9, 5, 'Friday', '10:00:00', '19:00:00', 1),
(10, 5, 'Saturday', '10:00:00', '19:00:00', 1);

-- --------------------------------------------------------

--
-- Table structure for table `staff_unavailability`
--

CREATE TABLE `staff_unavailability` (
  `unavailID` int(11) NOT NULL,
  `staffID` int(11) NOT NULL,
  `unavailable_date` date NOT NULL,
  `reason` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `logID` int(11) NOT NULL,
  `user_type` enum('customer','staff','owner','manager') NOT NULL,
  `userID` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_logs`
--

INSERT INTO `system_logs` (`logID`, `user_type`, `userID`, `action`, `details`, `ip_address`, `created_at`) VALUES
(1, 'owner', 1, 'login', 'Owner logged in', '::1', '2026-05-02 08:20:09'),
(2, 'owner', 1, 'logout', 'User logged out', '::1', '2026-05-02 08:21:45'),
(3, 'owner', 1, 'login', 'Owner logged in', '::1', '2026-05-02 08:30:16'),
(4, 'owner', 1, 'logout', 'User logged out', '::1', '2026-05-02 09:20:40'),
(5, 'customer', 4, 'register', 'Customer registered via email OTP', '::1', '2026-05-02 09:51:30'),
(6, 'customer', 4, 'login', 'Customer logged in', '::1', '2026-05-02 09:51:39'),
(7, 'customer', 4, 'logout', 'User logged out', '::1', '2026-05-02 09:51:51'),
(8, 'owner', 1, 'login', 'Owner logged in', '::1', '2026-05-02 10:03:31'),
(9, 'owner', 1, 'logout', 'User logged out', '::1', '2026-05-02 10:05:37'),
(10, 'customer', 4, 'login', 'Customer logged in', '::1', '2026-05-02 10:05:43'),
(11, 'customer', 4, 'logout', 'User logged out', '::1', '2026-05-02 10:09:09'),
(12, 'staff', 6, 'login', 'Staff logged in', '::1', '2026-05-02 10:09:31'),
(13, 'staff', 6, 'logout', 'User logged out', '::1', '2026-05-02 10:35:32'),
(14, 'owner', 1, 'login', 'Owner logged in', '::1', '2026-05-02 10:35:38'),
(15, 'owner', 1, 'logout', 'User logged out', '::1', '2026-05-02 10:49:45'),
(16, 'customer', 1, 'login', 'Customer logged in', '::1', '2026-05-05 00:46:06');

-- --------------------------------------------------------

--
-- Table structure for table `system_policies`
--

CREATE TABLE `system_policies` (
  `id` int(11) NOT NULL,
  `policy_key` varchar(100) NOT NULL,
  `policy_value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_policies`
--

INSERT INTO `system_policies` (`id`, `policy_key`, `policy_value`, `description`, `updated_at`) VALUES
(1, 'downpayment_percent', '20', 'Percentage of total amount required as downpayment', '2026-05-02 08:07:08'),
(2, 'cancellation_window_hours', '24', 'Hours before appointment to allow cancellation', '2026-05-02 08:07:08'),
(3, 'max_reschedule_count', '2', 'Maximum number of times a customer can reschedule', '2026-05-02 08:07:08'),
(4, 'max_bookings_per_slot', '10', 'Maximum number of bookings per time slot', '2026-05-02 08:07:08'),
(5, 'walkins_allowed', '1', 'Whether walk-in customers are allowed (1=yes, 0=no)', '2026-05-02 08:07:08');

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_booking_summary`
-- (See below for the actual view)
--
CREATE TABLE `view_booking_summary` (
`booking_date` date
,`total_bookings` bigint(21)
,`pending` decimal(22,0)
,`approved` decimal(22,0)
,`completed` decimal(22,0)
,`cancelled` decimal(22,0)
,`total_downpayment` decimal(32,2)
,`total_amount` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_customer_summary`
-- (See below for the actual view)
--
CREATE TABLE `view_customer_summary` (
`customerID` int(11)
,`customer_name` varchar(101)
,`contactNumber` varchar(20)
,`email` varchar(100)
,`total_bookings` bigint(21)
,`completed` decimal(22,0)
,`cancelled` decimal(22,0)
,`total_spent` decimal(32,2)
,`last_visit` date
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_revenue_by_service`
-- (See below for the actual view)
--
CREATE TABLE `view_revenue_by_service` (
`serviceName` varchar(100)
,`category` varchar(100)
,`bookings_count` bigint(21)
,`total_downpayment` decimal(32,2)
,`total_revenue` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_staff_performance`
-- (See below for the actual view)
--
CREATE TABLE `view_staff_performance` (
`staffID` int(11)
,`staff_name` varchar(101)
,`role` enum('Owner','Manager','Staff')
,`specialization` varchar(100)
,`total_bookings` bigint(21)
,`completed` decimal(22,0)
,`avg_rating` decimal(14,4)
,`feedback_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Structure for view `view_booking_summary`
--
DROP TABLE IF EXISTS `view_booking_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_booking_summary`  AS SELECT cast(`booking`.`created_at` as date) AS `booking_date`, count(0) AS `total_bookings`, sum(case when `booking`.`bookingStatus` = 'pending' then 1 else 0 end) AS `pending`, sum(case when `booking`.`bookingStatus` = 'approved' then 1 else 0 end) AS `approved`, sum(case when `booking`.`bookingStatus` = 'completed' then 1 else 0 end) AS `completed`, sum(case when `booking`.`bookingStatus` = 'cancelled' then 1 else 0 end) AS `cancelled`, sum(`booking`.`downpayment_amount`) AS `total_downpayment`, sum(`booking`.`total_amount`) AS `total_amount` FROM `booking` GROUP BY cast(`booking`.`created_at` as date) ;

-- --------------------------------------------------------

--
-- Structure for view `view_customer_summary`
--
DROP TABLE IF EXISTS `view_customer_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_customer_summary`  AS SELECT `c`.`customerID` AS `customerID`, concat(`c`.`firstName`,' ',`c`.`lastName`) AS `customer_name`, `c`.`contactNumber` AS `contactNumber`, `c`.`email` AS `email`, count(`b`.`bookingID`) AS `total_bookings`, sum(case when `b`.`bookingStatus` = 'completed' then 1 else 0 end) AS `completed`, sum(case when `b`.`bookingStatus` = 'cancelled' then 1 else 0 end) AS `cancelled`, sum(`b`.`total_amount`) AS `total_spent`, max(`b`.`bookingDate`) AS `last_visit` FROM (`customer` `c` left join `booking` `b` on(`c`.`customerID` = `b`.`customerID`)) WHERE `c`.`accountStatus` = 'active' GROUP BY `c`.`customerID` ORDER BY sum(`b`.`total_amount`) DESC ;

-- --------------------------------------------------------

--
-- Structure for view `view_revenue_by_service`
--
DROP TABLE IF EXISTS `view_revenue_by_service`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_revenue_by_service`  AS SELECT `s`.`serviceName` AS `serviceName`, `c`.`category_name` AS `category`, count(`b`.`bookingID`) AS `bookings_count`, sum(`b`.`downpayment_amount`) AS `total_downpayment`, sum(`b`.`total_amount`) AS `total_revenue` FROM ((`booking` `b` join `service` `s` on(`b`.`serviceID` = `s`.`serviceID`)) left join `service_categories` `c` on(`s`.`catID` = `c`.`catID`)) WHERE `b`.`bookingStatus` <> 'cancelled' GROUP BY `s`.`serviceID` ORDER BY sum(`b`.`total_amount`) DESC ;

-- --------------------------------------------------------

--
-- Structure for view `view_staff_performance`
--
DROP TABLE IF EXISTS `view_staff_performance`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_staff_performance`  AS SELECT `s`.`staffID` AS `staffID`, concat(`s`.`firstName`,' ',`s`.`lastName`) AS `staff_name`, `s`.`role` AS `role`, `s`.`specialization` AS `specialization`, count(`b`.`bookingID`) AS `total_bookings`, sum(case when `b`.`bookingStatus` = 'completed' then 1 else 0 end) AS `completed`, avg(`f`.`rating`) AS `avg_rating`, count(`f`.`feedbackID`) AS `feedback_count` FROM ((`salon_staff` `s` left join `booking` `b` on(`s`.`staffID` = `b`.`staffID`)) left join `feedback` `f` on(`s`.`staffID` = `f`.`staffID`)) WHERE `s`.`is_active` = 1 GROUP BY `s`.`staffID` ORDER BY sum(case when `b`.`bookingStatus` = 'completed' then 1 else 0 end) DESC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `booking`
--
ALTER TABLE `booking`
  ADD PRIMARY KEY (`bookingID`),
  ADD UNIQUE KEY `bookingRef` (`bookingRef`),
  ADD KEY `idx_customer` (`customerID`),
  ADD KEY `idx_staff` (`staffID`),
  ADD KEY `idx_date` (`bookingDate`),
  ADD KEY `idx_status` (`bookingStatus`),
  ADD KEY `idx_payment` (`paymentStatus`),
  ADD KEY `serviceID` (`serviceID`),
  ADD KEY `idx_booking_customer` (`customerID`),
  ADD KEY `idx_booking_date` (`bookingDate`),
  ADD KEY `idx_booking_status` (`bookingStatus`),
  ADD KEY `idx_booking_staff` (`staffID`);

--
-- Indexes for table `booking_cart`
--
ALTER TABLE `booking_cart`
  ADD PRIMARY KEY (`cartID`),
  ADD KEY `idx_customer` (`customerID`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `serviceID` (`serviceID`),
  ADD KEY `staffID` (`staffID`),
  ADD KEY `idx_cart_customer` (`customerID`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`customerID`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `contactNumber` (`contactNumber`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_contact` (`contactNumber`),
  ADD KEY `idx_status` (`accountStatus`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`feedbackID`),
  ADD KEY `idx_booking` (`bookingID`),
  ADD KEY `idx_customer` (`customerID`),
  ADD KEY `idx_staff` (`staffID`),
  ADD KEY `idx_service` (`serviceID`),
  ADD KEY `idx_feedback_staff` (`staffID`),
  ADD KEY `idx_feedback_booking` (`bookingID`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notifID`),
  ADD KEY `idx_user` (`user_type`,`userID`),
  ADD KEY `idx_read` (`is_read`),
  ADD KEY `idx_notifications_user` (`userID`,`user_type`);

--
-- Indexes for table `otp_storage`
--
ALTER TABLE `otp_storage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contact` (`contact_number`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`payID`),
  ADD KEY `idx_booking` (`bookingID`),
  ADD KEY `idx_status` (`payStatus`),
  ADD KEY `idx_payment_booking` (`bookingID`),
  ADD KEY `idx_payment_status` (`payStatus`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`permID`),
  ADD UNIQUE KEY `unique_role_permission` (`role_name`,`permission_key`);

--
-- Indexes for table `promos`
--
ALTER TABLE `promos`
  ADD PRIMARY KEY (`promoID`),
  ADD UNIQUE KEY `promo_code` (`promo_code`),
  ADD KEY `idx_code` (`promo_code`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `salon_staff`
--
ALTER TABLE `salon_staff`
  ADD PRIMARY KEY (`staffID`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `service`
--
ALTER TABLE `service`
  ADD PRIMARY KEY (`serviceID`),
  ADD KEY `idx_serviceName` (`serviceName`),
  ADD KEY `idx_category` (`catID`),
  ADD KEY `idx_home_service` (`is_home_service`);

--
-- Indexes for table `service_categories`
--
ALTER TABLE `service_categories`
  ADD PRIMARY KEY (`catID`),
  ADD KEY `idx_category` (`category_name`);

--
-- Indexes for table `staff_availability`
--
ALTER TABLE `staff_availability`
  ADD PRIMARY KEY (`availID`),
  ADD UNIQUE KEY `unique_staff_day` (`staffID`,`day_of_week`);

--
-- Indexes for table `staff_unavailability`
--
ALTER TABLE `staff_unavailability`
  ADD PRIMARY KEY (`unavailID`),
  ADD UNIQUE KEY `unique_staff_date` (`staffID`,`unavailable_date`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`logID`),
  ADD KEY `idx_user` (`user_type`,`userID`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_logs_created` (`created_at`),
  ADD KEY `idx_logs_action` (`action`);

--
-- Indexes for table `system_policies`
--
ALTER TABLE `system_policies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `policy_key` (`policy_key`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `booking`
--
ALTER TABLE `booking`
  MODIFY `bookingID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `booking_cart`
--
ALTER TABLE `booking_cart`
  MODIFY `cartID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `customerID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `feedbackID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notifID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `otp_storage`
--
ALTER TABLE `otp_storage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `payID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `permID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `promos`
--
ALTER TABLE `promos`
  MODIFY `promoID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `salon_staff`
--
ALTER TABLE `salon_staff`
  MODIFY `staffID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `service`
--
ALTER TABLE `service`
  MODIFY `serviceID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT for table `service_categories`
--
ALTER TABLE `service_categories`
  MODIFY `catID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `staff_availability`
--
ALTER TABLE `staff_availability`
  MODIFY `availID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `staff_unavailability`
--
ALTER TABLE `staff_unavailability`
  MODIFY `unavailID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `logID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `system_policies`
--
ALTER TABLE `system_policies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `booking`
--
ALTER TABLE `booking`
  ADD CONSTRAINT `booking_ibfk_1` FOREIGN KEY (`customerID`) REFERENCES `customer` (`customerID`) ON DELETE CASCADE,
  ADD CONSTRAINT `booking_ibfk_2` FOREIGN KEY (`staffID`) REFERENCES `salon_staff` (`staffID`) ON DELETE SET NULL,
  ADD CONSTRAINT `booking_ibfk_3` FOREIGN KEY (`serviceID`) REFERENCES `service` (`serviceID`) ON DELETE CASCADE;

--
-- Constraints for table `booking_cart`
--
ALTER TABLE `booking_cart`
  ADD CONSTRAINT `booking_cart_ibfk_1` FOREIGN KEY (`customerID`) REFERENCES `customer` (`customerID`) ON DELETE CASCADE,
  ADD CONSTRAINT `booking_cart_ibfk_2` FOREIGN KEY (`serviceID`) REFERENCES `service` (`serviceID`) ON DELETE CASCADE,
  ADD CONSTRAINT `booking_cart_ibfk_3` FOREIGN KEY (`staffID`) REFERENCES `salon_staff` (`staffID`) ON DELETE SET NULL;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`bookingID`) REFERENCES `booking` (`bookingID`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`customerID`) REFERENCES `customer` (`customerID`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_ibfk_3` FOREIGN KEY (`staffID`) REFERENCES `salon_staff` (`staffID`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_ibfk_4` FOREIGN KEY (`serviceID`) REFERENCES `service` (`serviceID`) ON DELETE CASCADE;

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`bookingID`) REFERENCES `booking` (`bookingID`) ON DELETE CASCADE;

--
-- Constraints for table `service`
--
ALTER TABLE `service`
  ADD CONSTRAINT `service_ibfk_1` FOREIGN KEY (`catID`) REFERENCES `service_categories` (`catID`) ON DELETE SET NULL;

--
-- Constraints for table `staff_availability`
--
ALTER TABLE `staff_availability`
  ADD CONSTRAINT `staff_availability_ibfk_1` FOREIGN KEY (`staffID`) REFERENCES `salon_staff` (`staffID`) ON DELETE CASCADE;

--
-- Constraints for table `staff_unavailability`
--
ALTER TABLE `staff_unavailability`
  ADD CONSTRAINT `staff_unavailability_ibfk_1` FOREIGN KEY (`staffID`) REFERENCES `salon_staff` (`staffID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
