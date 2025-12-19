<?php
session_start();
include '../include/header.php';
?>

<style>
    .container-wrapper {
        max-width: 1024px;
        margin-left: auto;
        margin-right: auto;
        padding-left: 1rem;
        padding-right: 1rem;
        padding-top: 2rem;
        padding-bottom: 2rem;
    }

    .policy-title {
        font-size: 2.5rem;
        font-weight: 700;
        color: #1f2937;
        text-align: center;
        margin-bottom: 0.5rem;
    }

    .policy-date {
        text-align: center;
        font-size: 0.875rem;
        color: #6b7280;
        margin-bottom: 3rem;
    }

    .policy-section {
        margin-bottom: 3.5rem;
        padding: 2rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        background-color: #ffffff;
    }

    .policy-section h2 {
        font-size: 1.875rem;
        font-weight: 700;
        color: #FFB774;
        margin-bottom: 1.5rem;
        border-bottom: 2px solid #fed7aa;
        padding-bottom: 0.5rem;
    }

    .policy-section h3 {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1f2937;
        margin-top: 1.5rem;
        margin-bottom: 0.75rem;
    }

    .policy-section p,
    .policy-section ul,
    .policy-section a {
        font-size: 1rem;
        color: #4b5563;
        line-height: 1.6;
        margin-bottom: 1rem;
        text-decoration: none;
    }

    .policy-section ul {
        list-style-type: disc;
        margin-left: 1.5rem;
        padding-left: 0;
    }

    .policy-section ul li {
        margin-bottom: 0.5rem;
    }

    .cta-section {
        text-align: center;
        background-color: #fff7ed;
        border: 1px solid #fed7aa;
        padding: 2.5rem;
        border-radius: 0.75rem;
    }

    .cta-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #FFB774;
        margin-bottom: 1rem;
    }

    .cta-text {
        color: #4b5563;
        margin-bottom: 1.5rem;
    }

    .btn-primary-wrapper {
        padding-top: 1rem;
    }

    .btn-primary {
        background-color: #FFB774;
        color: #fff;
        font-weight: 600;
        padding: 0.75rem 2rem;
        border-radius: 9999px;
        transition: background-color 0.3s ease;
        display: inline-block;
        font-size: 1.125rem;
        text-decoration: none;
    }

    .btn-primary:hover {
        background-color: #E89C55;
    }

    .policy-section-img {
        width: 2rem;
        height: 2rem;
        object-fit: contain;
        margin-right: 0.5rem;
        vertical-align: middle;
    }
</style>

<div class="container-wrapper">

    <div class="policy-header">
        <h1 class="policy-title">PetBuddy Shopping Policy</h1>
        <p class="policy-date">Last Updated: December 11, 2025</p>
    </div>

    <div class="policy-section">
        <h2><img src="../images/atm-card.png" class="policy-section-img"> 1. Payment Methods</h2>
        <p>PetBuddy accepts a variety of secure and convenient payment methods to ensure a smooth shopping experience.</p>

        <h3>Accepted Payment Options:</h3>
        <ul>
            <li>**Credit/Debit Cards:** We accept major cards including Visa and Mastercard.</li>
            <li>**Online Banking Transfer (FPX):** Instant bank transfers through secure gateways for all major Malaysian banks.</li>
            <li>**E-Wallets:** Support for major Malaysian e-wallets such as Touch 'n Go eWallet, Boost, and GrabPay.</li>
            <li>**Cash on Delivery (COD):** Available only for small orders within specific designated areas in the Klang Valley.</li>
        </ul>

        <h3>Security Guarantee:</h3>
        <p>All transactions are encrypted and processed through secure third-party payment platforms. PetBuddy does not store your credit card information.</p>
    </div>

    <div class="policy-section">
        <h2><img src="../images/delivery-truck.png" class="policy-section-img"> 2. Shipping and Delivery</h2>
        <p>We are committed to delivering your pet supplies as quickly and reliably as possible across Malaysia.</p>

        <h3>Order Processing Time:</h3>
        <ul>
            <li>All orders are typically processed and packed within **1-2 working days**.</li>
            <li>You will receive an email notification with a tracking number once processing is complete.</li>
        </ul>

        <h3>Estimated Delivery Time (Working Days):</h3>
        <ul>
            <li>**Peninsular Malaysia:** 2 - 5 days</li>
            <li>**East Malaysia:** 5 - 10 days</li>
        </ul>

        <h3>Shipping Fees:</h3>
        <p>Standard delivery is **FREE** for orders totaling over **RM 150** within Peninsular Malaysia. For orders below this amount, shipping costs will be automatically calculated at checkout based on package weight and delivery address.</p>
    </div>

    <div class="policy-section">
        <h2><img src="../images/return.png" class="policy-section-img"> 3. Returns, Refunds, and Exchanges</h2>
        <p>We want you and your pet to be completely satisfied with products from PetBuddy. If for any reason you are not satisfied, we offer a straightforward return process.</p>

        <h3>Return Window:</h3>
        <p>You may request a return or exchange within **7 days** of receiving your order.</p>

        <h3>Return Conditions:</h3>
        <ul>
            <li>Items must be in their **original, unused, and unopened** condition with all original packaging and tags intact.</li>
            <li>For hygiene reasons, pet food, grooming tools, bedding, and personalized items are non-returnable unless defective.</li>
        </ul>

        <h3>Refund Process:</h3>
        <p>Once we receive and inspect the returned item, refunds will be processed to your original payment account within **5-10 working days**. Shipping fees are non-refundable.</p>

        <h3>How to Initiate a Return:</h3>
        <p>Please contact our Customer Service team at <a href="mailto:support@petbuddy.my">support@petbuddy.my</a>, providing your order number and reason for the return.</p>
    </div>

    <div class="policy-section">
        <h2><img src="../images/customer-review.png" class="policy-section-img"> 4. Customer Service</h2>
        <p>If you have any questions regarding our Shopping Policy or need assistance with your order, please do not hesitate to contact us.</p>
        <p>Contact Information:</p>
        <ul>
            <li>**Email:** support@petbuddy.my</li>
            <li>**Phone:** +60 3-8888 1234 (Operating Hours: Mon - Sun, 10:00 AM - 8:00 PM)</li>
        </ul>
    </div>

    <hr style="margin-top: 3rem; margin-bottom: 3rem; border-top: 2px solid #f3f4f6;">

    <section class="cta-section">
        <h2 class="cta-title">Need Further Assistance?</h2>
        <p class="cta-text">If your query is about pet health or product usage, please check our Frequently Asked Questions (FAQ) page.</p>
        <div class="btn-primary-wrapper">
            <a href="faq.php" class="btn-primary">
                Go to FAQ
            </a>
        </div>
    </section>

</div>

<?php include '../include/footer.php'; ?>
<?php include '../include/chat_widget.php'; ?>