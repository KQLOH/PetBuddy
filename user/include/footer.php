<?php

?>
<footer class="site-footer">
    <div class="footer-container">

        <div class="footer-col">
            <div class="logo">
                <img src="../images/logo.png" alt="PetBuddy Logo" class="logo-img">
                <span>PetBuddy</span>
            </div>

            <h4 class="company-name">PetBuddy Online Shop</h4>

            <p class="footer-desc">
                Welcome to PetBuddy, your trusted source for premium pet supplies.
                We bring high-quality food, toys, and accessories to keep your pets happy and healthy.
            </p>

            <div class="footer-social">
                <a href="https://www.facebook.com">
                    <img src="../images/facebook.png" alt="Facebook">
                </a>
                <a href="https://www.twitter.com">
                    <img src="../images/twitter.png" alt="Twitter">
                </a>
                <a href="https://www.instagram.com">
                    <img src="../images/instagram.png" alt="Instagram">
                </a>
                <a href="https://www.pinterest.com">
                    <img src="../images/pinterest.png" alt="Pinterest">
                </a>
            </div>
        </div>

        <div class="footer-col">
            <h4 class="footer-title">MY ACCOUNT</h4>
            <ul class="footer-links">
                <li><a href="memberprofile.php">My Account</a></li>
                <li><a href="memberProfile.php?tab=orders">My Orders</a></li>
                <li><a href="wishlist.php">My Wishlist</a></li>
                <li><a href="memberProfile.php?tab=orders&status=paid">Order Tracking</a></li>
                <li><a href="cart.php">Shopping Cart</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4 class="footer-title">COMPANY</h4>
            <ul class="footer-links">
                <li><a href="about.php">About Us</a></li>
                <li><a href="product_listing.php">Shop</a></li>
                <li><a href="contact.php">Contact Us</a></li>
                <li><a href="faq.php">FAQs</a></li>
                <li><a href="../php/policy.php">Shipping Policy</a></li>
                <li><a href="privacy.php">Privacy Notice</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4 class="footer-title">GET IN TOUCH</h4>
            <ul class="footer-contact-info">
                <li>
                    <a href="tel:+60388881234">
                        <div class="contact-icon-box">
                            <img src="../images/telephone.png" alt="Phone">
                        </div>
                        <span>+60 3-8888 1234</span>
                    </a>
                </li>

                <li>
                    <a href="mailto:support@petbuddy.my">
                        <div class="contact-icon-box">
                            <img src="../images/mail.png" alt="Email">
                        </div>
                        <span>support@petbuddy.my</span>
                    </a>
                </li>

                <li>
                    <a href="https://www.google.com/maps/search/?api=1&query=Setapak,Kuala+Lumpur" target="_blank">
                        <div class="contact-icon-box">
                            <img src="../images/home-accessory.png" alt="Location">
                        </div>
                        <span>Setapak, Kuala Lumpur</span>
                    </a>
                </li>
            </ul>

            <h4 class="footer-title" style="margin-top: 30px;">SECURE PAYMENTS</h4>
            <div class="payment-icons">
                <img src="../images/payments.png" alt="Accepted Payment Methods">
            </div>
        </div>

    </div>

    <div class="footer-bottom">
        <p>Copyright © <?php echo date("Y"); ?> PetBuddy Online Shop. All rights reserved.</p>
    </div>
</footer>

<style>
    :root {
        --primary-color: #FFB774;
        --primary-dark: #E89C55;
        --text-dark: #1A1A1A;
        --text-muted: #666;
        --bg-light: #fdfdfd;
        --border-color: #f0f0f0;
        --transition: all 0.3s ease;
    }

    .site-footer {
        padding: 80px 0 30px;
        background: var(--bg-light);
        color: var(--text-dark);
        border-top: 1px solid var(--border-color);
        font-family: "Inter", system-ui, -apple-system, sans-serif;
    }

    .footer-container {
        max-width: 1200px;
        margin: auto;
        padding: 0 30px;
        display: grid;
        grid-template-columns: 1.5fr 0.8fr 0.8fr 1.2fr;
        gap: 50px;
    }

    .footer-col .logo {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 24px;
        font-weight: 800;
        color: var(--text-dark);
        margin-bottom: 20px;
    }

    .logo-img {
        width: 45px;
        height: 45px;
        transition: transform 0.5s ease;
    }

    .footer-col:hover .logo-img {
        transform: rotate(15deg);
    }

    .company-name {
        font-size: 16px;
        font-weight: 700;
        margin-bottom: 12px;
        color: var(--text-dark);
    }

    .footer-desc {
        color: var(--text-muted);
        line-height: 1.8;
        font-size: 14px;
        margin-bottom: 25px;
    }

    .footer-title {
        font-size: 14px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        margin-bottom: 25px;
        color: var(--primary-dark);
        position: relative;
    }

    .footer-title::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: -8px;
        width: 30px;
        height: 2px;
        background: var(--primary-color);
    }

    .footer-links {
        list-style: none;
    }

    .footer-links li {
        margin-bottom: 14px;
    }

    .footer-links a {
        text-decoration: none;
        font-size: 14px;
        color: var(--text-muted);
        transition: var(--transition);
        display: inline-block;
    }

    .footer-links a:hover {
        color: var(--primary-dark);
        transform: translateX(5px);
    }

    .footer-contact-info {
        list-style: none;
    }

    .footer-contact-info li {
        margin-bottom: 18px;
    }

    .footer-contact-info a {
        text-decoration: none;
        font-size: 14px;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        gap: 15px;
        transition: var(--transition);
    }

    .contact-icon-box {
        background: #fff3e6;
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: var(--transition);
    }

    .contact-icon-box img {
        width: 20px;
        height: 20px;
        object-fit: contain;
    }

    .footer-contact-info a:hover .contact-icon-box {
        background: var(--primary-color);
    }

    .footer-contact-info a:hover .contact-icon-box img {
        filter: brightness(0) invert(1);
    }

    .payment-icons img {
        max-width: 220px;
        height: auto;
        opacity: 0.9;
    }

    .footer-bottom {
        margin-top: 60px;
        padding-top: 30px;
        text-align: center;
        border-top: 1px solid var(--border-color);
    }

    .footer-bottom p {
        color: #999;
        font-size: 13px;
    }

    @media (max-width: 1024px) {
        .footer-container {
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }
    }

    @media (max-width: 650px) {
        .footer-container {
            grid-template-columns: 1fr;
        }

        .site-footer {
            padding: 50px 0 30px;
        }
    }

    .footer-social {
        display: flex;
        gap: 12px;
    }

    .footer-social a {
        background: #fff3e6;
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: var(--transition);
        text-decoration: none;
    }

    .footer-social a img {
        width: 20px;
        height: 20px;
        object-fit: contain;
    }

    .footer-social a:hover {
        background: var(--primary-color);
        transform: translateY(-3px);
    }

    .footer-social a:hover img {
        filter: brightness(0) invert(1);
    }
</style>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


<script>
    function refreshCartSidebar() {
        $.ajax({
            url: "fetch_cart.php",
            type: "GET",
            success: function(data) {

                $("#cartBody").html(data);


                var newTotal = $("#ajax-new-total").val();


                if (newTotal && newTotal !== "0.00") {
                    $("#cartSidebarTotal").text(newTotal);
                    $("#cartFooter").show();
                } else {
                    $("#cartFooter").hide();
                }

                setTimeout(function() {
                    if (typeof updateCartBadge === 'function') {
                        updateCartBadge();
                    }
                }, 200);
            },
            error: function() {
                console.error("Failed to refresh cart sidebar.");
            }
        });
    }


    $(document).off("click", ".add-btn").on("click", ".add-btn", function(e) {
        e.preventDefault();

        let $btn = $(this);
        let pid = $btn.data("id");


        if ($btn.data('loading')) return;
        $btn.data('loading', true);

        $.ajax({
            url: "add_to_cart.php",
            type: "POST",
            data: {
                product_id: pid
            },
            success: function(response) {
                $btn.data('loading', false);
                let res = response.trim();

                if (res.includes("added") || res.includes("increased") || res.includes("success")) {

                    refreshCartSidebar();


                    if (typeof openCart === "function") openCart();


                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Added to cart',
                        showConfirmButton: false,
                        timer: 1500,
                        timerProgressBar: true
                    });

                } else if (res.includes("login") || res.includes("required")) {

                    Swal.fire("Please Login", "You need to login to add items.", "warning");
                    if (typeof openLogin === "function") openLogin();
                } else {

                    console.error("Add cart error:", res);
                    Swal.fire("Error", "Could not add item. Check console.", "error");
                }
            },
            error: function() {
                $btn.data('loading', false);
                Swal.fire("Error", "Connection failed.", "error");
            }
        });
    });


    $(document).off("click", ".qty-btn").on("click", ".qty-btn", function() {
        let $btn = $(this);
        let pid = $btn.data("id");
        let action = $btn.hasClass("increase") ? "increase" : "decrease";


        let $qtySpan = $btn.siblings(".qty-display");
        let currentQty = parseInt($qtySpan.text());


        if (action === "decrease" && currentQty <= 1) return;


        if ($btn.prop('disabled')) return;
        $btn.prop('disabled', true);

        $.ajax({
            url: "update_cart_quantity.php",
            type: "POST",
            data: {
                product_id: pid,
                action: action
            },
            success: function(response) {
                refreshCartSidebar();
                setTimeout(function() {
                    $btn.prop('disabled', false);
                }, 300);
            },
            error: function() {
                $btn.prop('disabled', false);
            }
        });
    });


    $(document).off("click", ".remove-btn").on("click", ".remove-btn", function() {
        let pid = $(this).data("id");


        Swal.fire({
            title: 'Remove item?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, remove'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "remove_cart.php",
                    type: "POST",
                    data: {
                        product_id: pid
                    },
                    success: function() {
                        refreshCartSidebar();
                    }
                });
            }
        });
    });
</script>