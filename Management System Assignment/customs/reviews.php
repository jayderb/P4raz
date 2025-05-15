<?php
// Start the session
session_start();

// Check if the user is logged in and has the customs role
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "customs") {
    header("location: ../../RetailSystem-LocalGarage-Login.php");
    exit;
}

// Include config file
try {
    require_once "../config.php";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Initialize variables for messages and filters
$success_msg = '';
$error_msg = '';
$rating_filter = isset($_GET['rating']) ? intval($_GET['rating']) : 'all';
$sort_order = isset($_GET['sort']) ? $_GET['sort'] : 'desc';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Validate sort order
$sort_order = in_array($sort_order, ['asc', 'desc']) ? $sort_order : 'desc';

// Fetch reviews with optional filtering and searching
$sql = "SELECT r.id, r.customer_id, u.first_name, u.last_name, r.rating, r.comment, r.created_at 
        FROM reviews r 
        LEFT JOIN users u ON r.customer_id = u.id 
        WHERE 1=1";
$params = [];
if ($rating_filter !== 'all' && $rating_filter >= 1 && $rating_filter <= 5) {
    $sql .= " AND r.rating = :rating";
    $params[':rating'] = $rating_filter;
}
if (!empty($search_query)) {
    $sql .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR r.comment LIKE :search)";
    $params[':search'] = "%$search_query%";
}
$sql .= " ORDER BY r.created_at " . ($sort_order === 'asc' ? 'ASC' : 'DESC');
$stmt = $conn->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submissions for deleting reviews
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_review'])) {
    $review_id = intval($_POST['review_id']);
    try {
        $sql = "DELETE FROM reviews WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":id", $review_id, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $success_msg = "Review deleted successfully!";
        } else {
            $error_msg = "Review not found.";
        }
        // Refresh reviews
        $sql = "SELECT r.id, r.customer_id, u.first_name, u.last_name, r.rating, r.comment, r.created_at 
                FROM reviews r 
                LEFT JOIN users u ON r.customer_id = u.id 
                WHERE 1=1";
        $params = [];
        if ($rating_filter !== 'all' && $rating_filter >= 1 && $rating_filter <= 5) {
            $sql .= " AND r.rating = :rating";
            $params[':rating'] = $rating_filter;
        }
        if (!empty($search_query)) {
            $sql .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR r.comment LIKE :search)";
            $params[':search'] = "%$search_query%";
        }
        $sql .= " ORDER BY r.created_at " . ($sort_order === 'asc' ? 'ASC' : 'DESC');
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_msg = "Error deleting review: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZedAuto - Review Management</title>
    <!-- Favicon -->
    <link rel="icon" type="image/ico" href="../favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="../apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../Static/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../Static/images/favicon-16x16.png">
    <link rel="manifest" href="../site.webmanifest">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Google Fonts (Inter) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #ecf0f1;
            color: #2c3e50;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .navbar {
            background: #2c2f33;
            transition: background 0.3s ease;
        }
        .navbar.sticky {
            background: #23272a;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        .topbar {
            background: #2c2f33;
            color: #ffffff;
            padding: 0.75rem 0;
            z-index: 900;
        }
        .topbar .container {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .topbar nav {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 1rem;
        }
        .topbar a {
            color: #ffffff;
            text-decoration: none;
            font-size: 1rem;
            padding: 0.5rem 1rem;
            transition: background 0.2s ease;
        }
        .topbar a:hover {
            background: #34495e;
            border-radius: 4px;
        }
        .content {
            padding: 2rem;
            flex: 1;
        }
        .table-container {
            background: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
        }
        .table th {
            background: #2c3e50;
            color: #ffffff;
        }
        .table tr:hover {
            background: #ecf0f1;
        }
        .action-btn {
            transition: background 0.2s ease;
        }
        .action-btn:hover {
            background: #c0392b;
        }
        .card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
        }
        #topbar-mobile {
            display: none;
            background: #2c2f33;
        }
        #topbar-toggle {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1001;
            background: #2c2f33;
            color: #ffffff;
            padding: 0.5rem;
            border-radius: 4px;
        }
        @media (max-width: 768px) {
            .topbar nav {
                display: none;
            }
            #topbar-mobile {
                display: none;
            }
            #topbar-mobile.active {
                display: block;
            }
            #topbar-toggle {
                display: block;
            }
            .content {
                padding: 1rem;
            }
        }
        .message {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .message.success {
            background: #2ecc71;
            color: #fff;
        }
        .message.error {
            background: #e74c3c;
            color: #fff;
        }
        .rating-stars {
            color: #f1c40f;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <header class="navbar sticky top-0 z-50">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <a href="../RetailSytsem-Home.html" class="text-2xl font-bold text-white">ZedAuto</a>
            <button id="hamburger" class="md:hidden text-white text-2xl focus:outline-none" aria-label="Toggle menu">
                <i class="fas fa-bars"></i>
            </button>
            <nav id="nav-menu" class="hidden md:flex items-center space-x-6">
                <a href="reviews.php" class="text-white hover:text-[#f1c40f] transition">Reviews</a>
                <div class="flex items-center">
                    <input type="text" class="px-3 py-2 rounded-l-md bg-[#40444b] text-white placeholder-gray-300 focus:outline-none" placeholder="Search...">
                    <button class="px-4 py-2 bg-[#ffcc00] text-[#2c3e50] rounded-r-md hover:bg-[#e6b800] transition">Search</button>
                </div>
                <a href="../logout.php" class="text-red-400 hover:text-red-500 transition">Logout</a>
            </nav>
        </div>
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden bg-[#2c2f33]">
            <div class="container mx-auto px-4 py-4 flex flex-col space-y-4">
                <a href="reviews.php" class="text-white hover:text-[#f1c40f] transition">Reviews</a>
                <div class="flex">
                    <input type="text" class="px-3 py-2 rounded-l-md bg-[#40444b] text-white placeholder-gray-300 focus:outline-none w-full" placeholder="Search...">
                    <button class="px-4 py-2 bg-[#ffcc00] text-[#2c3e50] rounded-r-md hover:bg-[#e6b800] transition">Search</button>
                </div>
                <a href="../logout.php" class="text-red-400 hover:text-red-500 transition">Logout</a>
            </div>
        </div>
        <!-- Top Bar -->
        <nav id="topbar" class="topbar">
            <div class="container mx-auto px-4">
                <h2 class="sr-only">Customs Navigation</h2>
                <nav>
                    <a href="dashboard.php">Dashboard</a>
                    <a href="customs.php">Delivery Management</a>
                    <a href="reviews.php">Reviews</a>
                    <a href="../logout.php">Logout</a>
                </nav>
            </div>
        </nav>
        <!-- Mobile Top Bar Menu -->
        <div id="topbar-mobile" class="md:hidden">
            <div class="container mx-auto px-4 py-4 flex flex-col space-y-2">
                <a href="dashboard.php" class="text-white hover:text-[#f1c40f] transition px-4 py-2">Dashboard</a>
                <a href="customs.php" class="text-white hover:text-[#f1c40f] transition px-4 py-2">Delivery Management</a>
                <a href="reviews.php" class="text-white hover:text-[#f1c40f] transition px-4 py-2">Reviews</a>
                <a href="../logout.php" class="text-white hover:text-[#f1c40f] transition px-4 py-2">Logout</a>
            </div>
        </div>
    </header>

    <!-- Toggle Button for Mobile Top Bar -->
    <button id="topbar-toggle" class="md:hidden" aria-label="Toggle top bar menu">☰</button>

    <!-- Main Content -->
    <main class="content">
        <section class="container mx-auto px-4">
            <h1 class="text-3xl md:text-4xl font-semibold text-[#2c3e50] mb-6 text-center">
                Review Management
            </h1>

            <!-- Success/Error Messages -->
            <?php if (!empty($success_msg)): ?>
                <div class="message success"><?php echo $success_msg; ?></div>
            <?php endif; ?>
            <?php if (!empty($error_msg)): ?>
                <div class="message error"><?php echo $error_msg; ?></div>
            <?php endif; ?>

            <!-- Filter and Search -->
            <div class="mb-6 flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <form method="get" class="flex">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search by Customer Name or Comment..." class="px-3 py-2 rounded-l-md bg-[#f9f9f9] text-[#2c3e50] border border-[#ecf0f1] focus:outline-none flex-1">
                        <button type="submit" class="px-4 py-2 bg-[#ffcc00] text-[#2c3e50] rounded-r-md hover:bg-[#e6b800] transition">Search</button>
                    </form>
                </div>
                <div class="flex gap-4">
                    <select name="rating" onchange="this.form.submit()" form="filterForm" class="px-3 py-2 rounded-md bg-[#f9f9f9] text-[#2c3e50] border border-[#ecf0f1] focus:outline-none">
                        <option value="all" <?php echo $rating_filter == 'all' ? 'selected' : ''; ?>>All Ratings</option>
                        <option value="5" <?php echo $rating_filter == 5 ? 'selected' : ''; ?>>5 Stars</option>
                        <option value="4" <?php echo $rating_filter == 4 ? 'selected' : ''; ?>>4 Stars</option>
                        <option value="3" <?php echo $rating_filter == 3 ? 'selected' : ''; ?>>3 Stars</option>
                        <option value="2" <?php echo $rating_filter == 2 ? 'selected' : ''; ?>>2 Stars</option>
                        <option value="1" <?php echo $rating_filter == 1 ? 'selected' : ''; ?>>1 Star</option>
                    </select>
                    <select name="sort" onchange="this.form.submit()" form="filterForm" class="px-3 py-2 rounded-md bg-[#f9f9f9] text-[#2c3e50] border border-[#ecf0f1] focus:outline-none">
                        <option value="desc" <?php echo $sort_order == 'desc' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="asc" <?php echo $sort_order == 'asc' ? 'selected' : ''; ?>>Oldest First</option>
                    </select>
                    <form id="filterForm" method="get" class="hidden">
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                    </form>
                </div>
            </div>

            <!-- Reviews Table -->
            <div class="table-container card">
                <h2 class="text-2xl font-medium text-[#2c3e50] mb-4">Customer Reviews</h2>
                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 rounded-tl-md">Review ID</th>
                                <th class="px-4 py-2">Customer</th>
                                <th class="px-4 py-2">Rating</th>
                                <th class="px-4 py-2">Comment</th>
                                <th class="px-4 py-2">Date</th>
                                <th class="px-4 py-2 rounded-tr-md">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reviews)): ?>
                                <tr><td colspan="6" class="text-center py-4 text-[#2c3e50]">No reviews found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($reviews as $review): ?>
                                    <tr>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($review['id']); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></td>
                                        <td class="px-4 py-2 rating-stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'text-[#f1c40f]' : 'text-gray-300'; ?>"></i>
                                            <?php endfor; ?>
                                        </td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($review['comment']); ?></td>
                                        <td class="px-4 py-2"><?php echo date('Y-m-d H:i:s', strtotime($review['created_at'])); ?></td>
                                        <td class="px-4 py-2">
                                            <form method="post" class="delete-review-form" data-review-id="<?php echo $review['id']; ?>">
                                                <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                                <button type="submit" name="delete_review" class="action-btn px-4 py-2 text-white rounded-md bg-[#e74c3c] hover:bg-[#c0392b]" onclick="return confirm('Are you sure you want to delete this review?');">
                                                    Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-[#2c3e50] text-[#ecf0f1] py-10">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-xl font-semibold mb-4">ZedAuto</h3>
                    <p class="text-[#7f8c8d]">Providing top-notch automotive services in Lusaka, Zambia.</p>
                </div>
                <div>
                    <h3 class="text-xl font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="../RetailSytsem-Home.html" class="hover:text-[#f1c40f] transition">Home</a></li>
                        <li><a href="../about.php" class="hover:text-[#f1c40f] transition">About Us</a></li>
                        <li><a href="#" class="hover:text-[#f1c40f] transition">Services</a></li>
                        <li><a href="../contact.php" class="hover:text-[#f1c40f] transition">Contact</a></li>
                        <li><a href="#" class="hover:text-[#f1c40f] transition">Privacy Policy</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xl font-semibold mb-4">Connect With Us</h3>
                    <div class="flex space-x-4">
                        <a href="#" class="text-2xl hover:text-[#f1c40f] transition"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-2xl hover:text-[#f1c40f] transition"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-2xl hover:text-[#f1c40f] transition"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            <div class="text-center mt-8 text-[#7f8c8d]">
                <span id="copyright">© 2025 ZedAuto. All rights reserved.</span>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script>
        // Navbar Hamburger Menu Toggle
        const hamburger = document.getElementById('hamburger');
        const mobileMenu = document.getElementById('mobile-menu');
        hamburger.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });

        // Close navbar mobile menu when a link is clicked
        const mobileLinks = mobileMenu.querySelectorAll('a');
        mobileLinks.forEach(link => {
            link.addEventListener('click', () => {
                mobileMenu.classList.add('hidden');
            });
        });

        // Top Bar Toggle
        const topbarToggle = document.getElementById('topbar-toggle');
        const topbarMobile = document.getElementById('topbar-mobile');
        topbarToggle.addEventListener('click', () => {
            topbarMobile.classList.toggle('active');
        });

        // Close top bar mobile menu when a link is clicked
        const topbarLinks = topbarMobile.querySelectorAll('a');
        topbarLinks.forEach(link => {
            link.addEventListener('click', () => {
                topbarMobile.classList.remove('active');
            });
        });

        // Sticky Navbar
        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('.navbar');
            navbar.classList.toggle('sticky', window.scrollY > 50);
        });

        // Update Copyright Year
        function updateCopyright() {
            const copyrightElement = document.getElementById('copyright');
            const currentYear = new Date().getFullYear();
            const baseYear = 2025;
            if (currentYear > baseYear) {
                copyrightElement.textContent = `© ${baseYear}-${currentYear} ZedAuto. All rights reserved.`;
            }
        }
        updateCopyright();
    </script>
<?php
// Close connection
unset($conn);
?>
</body>
</html>