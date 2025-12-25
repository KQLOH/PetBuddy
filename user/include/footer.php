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
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-pinterest"></i></a>
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
            <h4 class="footer-title">SUBSCRIBE TO OUR EMAIL</h4>

            <form class="subscribe-form">
                <input type="email" placeholder="Your email address" required />
                <div class="underline"></div>
            </form>

            <div class="payment-icons">
                <img src="../images/payments.png" alt="Accepted Payments" onerror="this.style.display='none'">
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

    .footer-title {
        font-size: 15px;
        font-weight: 700;
        letter-spacing: 1px;
        margin-bottom: 20px;
        color: var(--primary-color);
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
        width: 250px;
        height: auto;
        display: block;
        padding-top: 10px;
        opacity: 0.9;
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


    @media (max-width: 900px) {
        .footer-container {
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }
    }

    @media (max-width: 600px) {
        .footer-container {
            grid-template-columns: 1fr;
        }
    }

    .logo-img {
        width: 40px;
        height: 40px;
        object-fit: contain;
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