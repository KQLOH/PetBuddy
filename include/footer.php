<?php
// footer.php
?>
<style>
    :root {
        --primary-color: #FFB774;
        --primary-dark: #E89C55;
        --text-dark: #2F2F2F;
        --border-color: #e8e8e8;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: "Inter", system-ui, sans-serif;
    }

    body {
        background: #fff;
    }

    .site-footer {
        padding: 80px 0 40px;
        background: #fff;
        color: var(--text-dark);
        border-top: 1px solid #eee;
    }

    .footer-container {
        max-width: 1300px;
        margin: auto;
        padding: 0 40px;
        display: grid;
        grid-template-columns: 1.7fr 1fr 1fr 1.3fr;
        gap: 60px;
    }

    .footer-col {
        display: flex;
        flex-direction: column;
    }

    .company-name {
        margin: 20px 0 10px;
        font-weight: 600;
        font-size: 18px;
    }

    .footer-desc {
        color: #555;
        line-height: 1.7;
        font-size: 14px;
        max-width: 260px;
    }

    .footer-social {
        margin-top: 20px;
        display: flex;
        gap: 20px;
    }

    .footer-social a {
        font-size: 18px;
        color: #333;
        transition: 0.2s;
    }

    .footer-social a:hover {
        color: var(--primary-dark);
    }

    .footer-title {
        font-size: 15px;
        font-weight: 700;
        letter-spacing: 1px;
        margin-bottom: 20px;
        color: var(--primary-color);
    }

    .footer-links {
        list-style: none;
        padding: 0;
    }

    .footer-links li {
        margin-bottom: 12px;
    }

    .footer-links a {
        text-decoration: none;
        font-size: 14px;
        color: #555;
        transition: 0.2s;
    }

    .footer-links a:hover {
        color: var(--primary-dark);
    }

    .subscribe-form input {
        border: none;
        outline: none;
        width: 100%;
        padding: 8px 0;
        font-size: 14px;
    }

    .underline {
        width: 100%;
        height: 2px;
        background: var(--primary-color);
        margin-top: 5px;
    }

    .payment-icons img {
        width: 50px;
        margin-right: 10px;
        opacity: 0.8;
        transition: 0.2s;
    }

    .payment-icons img:hover {
        opacity: 1;
    }

    .footer-bottom {
        margin-top: 40px;
        text-align: center;
        color: #777;
        font-size: 14px;
        padding-top: 20px;
        border-top: 1px solid #eee;
    }

    .logo {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 22px;
        font-weight: 700;
        color: var(--text-dark);
    }

    .logo-circle {
        width: 30px;
        height: 30px;
        background: var(--primary-color);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .payment-icons img {
        width: 250px;
        height: auto;
        display: block;
        padding-top: 10px;
    }
</style>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<footer class="site-footer">
    <div class="footer-container">

        <!-- Brand Section -->
        <div class="footer-col">
            <div class="logo">
                <div class="logo-circle">🐾</div>
                <span>PetBuddy</span>
            </div>

            <h4 class="company-name">PetBuddy Online Shop</h4>

            <p class="footer-desc">
                Welcome to PetBuddy, your trusted source for premium pet supplies.
                We bring high-quality food, toys, and accessories to keep your pets happy and healthy.
            </p>

            <div class="footer-social">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-pinterest"></i></a>
            </div>
        </div>

        <!-- My Account -->
        <div class="footer-col">
            <h4 class="footer-title">MY ACCOUNT</h4>
            <ul class="footer-links">
                <li><a href="account.php">My Account</a></li>
                <li><a href="orders.php">My Orders</a></li>
                <li><a href="wishlist.php">My Wishlist</a></li>
                <li><a href="tracking.php">Order Tracking</a></li>
                <li><a href="cart.php">Shopping Cart</a></li>
            </ul>
        </div>

        <!-- Company -->
        <div class="footer-col">
            <h4 class="footer-title">COMPANY</h4>
            <ul class="footer-links">
                <li><a href="about.php">About Us</a></li>
                <li><a href="products.php">Shop</a></li>
                <li><a href="contact.php">Contact Us</a></li>
                <li><a href="faq.php">FAQs</a></li>
                <li><a href="shipping.php">Shipping Policy</a></li>
                <li><a href="privacy.php">Privacy Notice</a></li>
            </ul>
        </div>

        <!-- Subscribe / Payment -->
        <div class="footer-col">
            <h4 class="footer-title">SUBSCRIBE TO OUR EMAIL</h4>

            <form class="subscribe-form">
                <input type="email" placeholder="Your email address" required />
                <div class="underline"></div>
            </form>

            <div class="payment-icons">
                <img src="images/payments.png" alt="Visa">
            </div>
        </div>

    </div>

    <div class="footer-bottom">
        <p>Copyright © <?php echo date("Y"); ?> PetBuddy Online Shop. All rights reserved.</p>
    </div>
</footer>
</body>

</html>