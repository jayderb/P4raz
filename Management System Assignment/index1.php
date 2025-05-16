<?php
// Sample data for dynamic content
$featured_cars = [
    ["name" => "Luxury Sedan", "price" => "$45,000", "image" => "https://via.placeholder.com/300x200?text=Sedan"],
    ["name" => "Sports SUV", "price" => "$60,000", "image" => "https://via.placeholder.com/300x200?text=SUV"],
    ["name" => "Electric Coupe", "price" => "$80,000", "image" => "https://via.placeholder.com/300x200?text=Coupe"]
];

$featured_parts = [
    ["name" => "Performance Tires", "price" => "$200", "image" => "https://via.placeholder.com/300x200?text=Tires"],
    ["name" => "Brake Kit", "price" => "$350", "image" => "https://via.placeholder.com/300x200?text=Brakes"],
    ["name" => "Exhaust System", "price" => "$500", "image" => "https://via.placeholder.com/300x200?text=Exhaust"]
];

$testimonials = [
    ["quote" => "Amazing quality parts, fast delivery!", "author" => "John D."],
    ["quote" => "Bought my dream car here, great service!", "author" => "Sarah M."],
    ["quote" => "Expert support made my purchase easy.", "author" => "Mike R."]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auto Company Homepage</title>
    <style>
        /* Global Styles */
        body {
            margin: 0;
            font-family: 'Roboto', sans-serif;
            color: #333;
            background-color: #f5f5f5;
        }

        /* Header and Navigation */
        header {
            background-color: #1a252f;
            position: sticky;
            top: 0;
            z-index: 100;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            color: #fff;
            font-size: 24px;
            font-weight: bold;
        }

        nav a {
            color: #fff;
            text-decoration: none;
            margin: 0 15px;
            font-size: 16px;
        }

        nav a:hover {
            color: #e63946;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('https://via.placeholder.com/1920x600?text=Car+Hero') center/cover;
            color: #fff;
            text-align: center;
            padding: 100px 20px;
        }

        .hero h1 {
            font-family: 'Oswald', sans-serif;
            font-size: 48px;
            margin: 0;
        }

        .hero p {
            font-size: 20px;
            margin: 10px 0 20px;
        }

        .cta-button {
            background-color: #e63946;
            color: #fff;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 5px;
            margin: 0 10px;
            transition: background-color 0.3s;
        }

        .cta-button:hover {
            background-color: #d00000;
        }

        /* Section Styles */
        section {
            padding: 50px 20px;
            text-align: center;
        }

        h2 {
            font-family: 'Oswald', sans-serif;
            font-size: 32px;
            color: #1a252f;
            margin-bottom: 30px;
        }

        /* Product Grid */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .product-card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .product-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .product-card h3 {
            font-size: 20px;
            margin: 10px 0;
        }

        .product-card p {
            color: #e63946;
            font-weight: bold;
            margin: 0 0 10px;
        }

        .product-card a {
            display: inline-block;
            color: #1a252f;
            text-decoration: none;
            padding: 8px 16px;
            border: 1px solid #1a252f;
            border-radius: 5px;
            margin-bottom: 10px;
        }

        .product-card a:hover {
            background-color: #1a252f;
            color: #fff;
        }

        /* Why Choose Us */
        .why-choose-us .features {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .feature {
            max-width: 300px;
        }

        .feature img {
            width: 50px;
            height: 50px;
        }

        /* Deal Section */
        .deal {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://via.placeholder.com/1200x300?text=Deal') center/cover;
            color: #fff;
            padding: 50px;
            border-radius: 10px;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Testimonials */
        .testimonials .testimonial-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .testimonial {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        /* Footer */
        footer {
            background-color: #1a252f;
            color: #fff;
            padding: 20px;
            text-align: center;
        }

        footer a {
            color: #fff;
            text-decoration: none;
            margin: 0 10px;
        }

        footer a:hover {
            color: #e63946;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 32px;
            }

            .hero p {
                font-size: 16px;
            }

            nav {
                display: none; /* Replace with hamburger menu in production */
            }

            .cta-button {
                display: block;
                margin: 10px auto;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="logo">Auto Company</div>
        <nav>
            <a href="#">Home</a>
            <a href="#">Cars</a>
            <a href="#">Parts</a>
            <a href="#">About</a>
            <a href="#">Contact</a>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <h1>Drive the Future: Premium Cars & Parts</h1>
        <p>Your one-stop shop for automotive excellence.</p>
        <a href="#" class="cta-button">Shop Cars</a>
        <a href="#" class="cta-button">Browse Parts</a>
    </section>

    <!-- Featured Cars -->
    <section>
        <h2>Featured Cars</h2>
        <div class="product-grid">
            <?php foreach ($featured_cars as $car): ?>
                <div class="product-card">
                    <img src="<?php echo htmlspecialchars($car['image']); ?>" alt="<?php echo htmlspecialchars($car['name']); ?>">
                    <h3><?php echo htmlspecialchars($car['name']); ?></h3>
                    <p><?php echo htmlspecialchars($car['price']); ?></p>
                    <a href="#">View Details</a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Featured Parts -->
    <section>
        <h2>Top Parts</h2>
        <div class="product-grid">
            <?php foreach ($featured_parts as $part): ?>
                <div class="product-card">
                    <img src="<?php echo htmlspecialchars($part['image']); ?>" alt="<?php echo htmlspecialchars($part['name']); ?>">
                    <h3><?php echo htmlspecialchars($part['name']); ?></h3>
                    <p><?php echo htmlspecialchars($part['price']); ?></p>
                    <a href="#">Shop Now</a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Why Choose Us -->
    <section class="why-choose-us">
        <h2>Why Choose Us</h2>
        <div class="features">
            <div class="feature">
                <img src="https://via.placeholder.com/50?text=Quality" alt="Quality Icon">
                <h3>Quality Guaranteed</h3>
                <p>Certified parts and vehicles you can trust.</p>
            </div>
            <div class="feature">
                <img src="https://via.placeholder.com/50?text=Shipping" alt="Shipping Icon">
                <h3>Fast Shipping</h3>
                <p>Get your parts delivered quickly.</p>
            </div>
            <div class="feature">
                <img src="https://via.placeholder.com/50?text=Support" alt="Support Icon">
                <h3>Expert Support</h3>
                <p>Our team is here to help you.</p>
            </div>
        </div>
    </section>

    <!-- Deal Section -->
    <section>
        <div class="deal">
            <h2>20% Off Parts This Week</h2>
            <p>Don't miss out on our limited-time offer!</p>
            <a href="#" class="cta-button">Shop Deals</a>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="testimonials">
        <h2>What Our Customers Say</h2>
        <div class="testimonial-grid">
            <?php foreach ($testimonials as $testimonial): ?>
                <div class="testimonial">
                    <p>"<?php echo htmlspecialchars($testimonial['quote']); ?>"</p>
                    <p><strong><?php echo htmlspecialchars($testimonial['author']); ?></strong></p>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <p>
            <a href="#">About</a> | <a href="#">Terms</a> | <a href="#">Privacy</a> | <a href="#">Contact</a>
        </p>
        <p>Follow us: <a href="#">Facebook</a> | <a href="#">Twitter</a> | <a href="#">Instagram</a></p>
        <p>&copy; 2025 Auto Company. All rights reserved.</p>
    </footer>
</body>
</html>