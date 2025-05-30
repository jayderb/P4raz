<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZedAuto - Maintenance</title>
    <!-- Favicon -->
    <link rel="icon" type="image/ico" href="favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="./Static/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="./Static/images/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
    <!-- Tailwind CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-gray-100">
    <header class="navbar bg-gray-900 text-white">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <a href="RetailSytsem-Home.html" class="text-2xl font-bold">ZedAuto</a>
            <div class="hamburger md:hidden">
                <i class="fas fa-bars text-2xl"></i>
            </div>
            <nav class="main-nav hidden md:flex">
                <ul class="flex space-x-6 items-center">
                    <li><a href="RetailSytsem-Home.html" class="hover:text-yellow-400">HOME</a></li>
                    <li><a href="maintenance.php" class="hover:text-yellow-400">MAINTENANCE</a></li>
                    <li><a href="auto-repair.php" class="hover:text-yellow-400">AUTO REPAIR</a></li>
                    <li><a href="#" class="hover:text-yellow-400">PRICE LIST</a></li>
                    <li><a href="#" class="hover:text-yellow-400">REVIEWS</a></li>
                    <li><a href="Retail System-About.html" class="hover:text-yellow-400">ABOUT</a></li>
                    <li><a href="Retail System-Contact.html" class="hover:text-yellow-400">CONTACT</a></li>
                    <li>
                        <div class="flex">
                            <input type="text" class="search px-2 py-1 rounded-l-md" placeholder="Search...">
                            <button type="submit" class="search-button bg-yellow-400 text-gray-900 px-4 py-1 rounded-r-md hover:bg-yellow-500">SEARCH</button>
                        </div>
                    </li>
                </ul>
            </nav>
        </div>
        <div class="bg-gray-800 py-2">
            <div class="container mx-auto px-4 flex justify-between text-sm">
                <div class="flex space-x-4">
                    <div><i class="fas fa-map-marker-alt"></i> Lusaka, Zambia</div>
                    <div><i class="fas fa-phone"></i> + (260) 987654321</div>
                    <div><i class="fas fa-envelope"></i> <a href="mailto:info@zedauto.com">info@zedauto.com</a></div>
                </div>
                <div class="flex space-x-4">
                    <a href="sell.html" class="hover:text-yellow-400">Sell Car</a>
                    <a href="RetailSystem-LocalGarage-Login.php" class="hover:text-yellow-400">Buy Car</a>
                    <a href="RetailSystem-Signup.php" class="hover:text-yellow-400">Order Parts</a>
                </div>
            </div>
        </div>
    </header>

    <div class="video-container">
        <video autoplay muted loop id="video">
            <source src="./Static/Videos/2025 Toyota Land Cruiser - Toyota.com.mp4">
            Your browser does not support HTML5 video.
        </video>
        <div class="text-overlay">
            <h2 class="text-4xl md:text-6xl font-bold mb-4">Maintenance Services</h2>
            <p class="text-xl mb-6">Keep your vehicle in top condition with our expert maintenance services.</p>
            <a href="Retail System-Contact.html" class="bg-yellow-400 text-gray-900 px-6 py-3 rounded-md hover:bg-yellow-500 transition">Book Now</a>
        </div>
    </div>

    <section class="services-section py-16 bg-white">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-8">Our Maintenance Services</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="service-card bg-gray-50 p-6 rounded-lg shadow-md transition transform">
                    <i class="fas fa-oil-can text-4xl text-yellow-400 mb-4"></i>
                    <h3 class="text-xl font-semibold mb-2" style="color: rgb(24, 3, 24);">Oil Change</h3>
                    <img src="./Static/images/oilpaint.jpeg" alt="Oil Change" class="w-full h-40 object-cover rounded-md mb-4">
                    <p>Regular oil changes to keep your engine running smoothly.</p>
                    <a href="#" class="text-yellow-400 hover:underline mt-2 inline-block">Learn More</a>
                </div>
                <div class="service-card bg-gray-50 p-6 rounded-lg shadow-md transition transform">
                    <i class="fas fa-tools text-4xl text-yellow-400 mb-4"></i>
                    <h3 class="text-xl font-semibold mb-2" style="color: rgb(5, 5, 21);">Brake Inspection</h3>
                    <img src="./Static/images/brake-inspection.jpg" alt="Brake Inspection" class="w-full h-40 object-cover rounded-md mb-4">
                    <p>Comprehensive brake checks to ensure your safety on the road.</p>
                    <a href="#" class="text-yellow-400 hover:underline mt-2 inline-block">Learn More</a>
                </div>
                <div class="service-card bg-gray-50 p-6 rounded-lg shadow-md transition transform">
                    <i class="fas fa-tire text-4xl text-yellow-400 mb-4"></i>
                    <h3 class="text-xl font-semibold mb-2" style="color: rgb(5, 5, 21)">Tire Services</h3>
                    <img src="./Static/images/tireservices.jpg" alt="Tire Services" class="w-full h-40 object-cover rounded-md mb-4">
                    <p>Tire rotation, balancing, and replacement for optimal performance.</p>
                    <a href="#" class="text-yellow-400 hover:underline mt-2 inline-block">Learn More</a>
                </div>
            </div>
        </div>
    </section>

    <section class="maintenance-info py-16 bg-gray-100">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-8">Why Regular Maintenance?</h2>
            <p class="text-center mb-8 text-lg">Regular maintenance ensures your vehicle remains reliable, safe, and efficient. Our expert technicians at ZedAuto provide top-quality service to extend the life of your car.</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-gray-50 p-6 rounded-lg shadow-md">
                    <h3 class="text-xl font-semibold mb-2" style="color: rgb(24, 3, 24);">Preventative Care</h3>
                    <p>Identify and fix issues before they become costly repairs.</p>
                </div>
                <div class="bg-gray-50 p-6 rounded-lg shadow-md">
                    <h3 class="text-xl font-semibold mb-2" style="color: rgb(5, 5, 21);">Improved Performance</h3>
                    <p>Keep your vehicle running at its best with routine maintenance.</p>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-gray-900 text-white py-8">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-xl font-bold mb-4">ZedAuto</h3>
                    <p>Providing top-notch automotive services in Lusaka, Zambia.</p>
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="hover:text-yellow-400">Home</a></li>
                        <li><a href="#" class="hover:text-yellow-400">About Us</a></li>
                        <li><a href="#" class="hover:text-yellow-400">Services</a></li>
                        <li><a href="#" class="hover:text-yellow-400">Contact</a></li>
                        <li><a href="#" class="hover:text-yellow-400">Privacy Policy</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-4">Connect With Us</h3>
                    <div class="flex space-x-4">
                        <a href="#" class="hover:text-yellow-400"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="hover:text-yellow-400"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="hover:text-yellow-400"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            <div class="mt-8 text-center">
                © 2025 ZedAuto. All rights reserved.
            </div>
        </div>
    </footer>

    <script>
        // Sticky Navbar
        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('navbar-sticky');
            } else {
                navbar.classList.remove('navbar-sticky');
            }
        });

        // Hamburger Menu Toggle
        const hamburger = document.querySelector('.hamburger');
        const navMenu = document.querySelector('.main-nav ul');
        hamburger.addEventListener('click', () => {
            navMenu.classList.toggle('active');
        });
    </script>
</body>
</html>