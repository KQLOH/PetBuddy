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
        border-bottom: 2px solid #FFB774;
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
        padding: 2.5rem;
        border-radius: 0.75rem;
        border: 1px solid #fed7aa;
    }

    .cta-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1f2937;
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

    .section-icon-img {
    width: 2rem; 
    height: 2rem;
    object-fit: contain;
    margin-right: 0.5rem;
    vertical-align: middle;
}
</style>

<div class="container-wrapper">

    <div class="policy-header">
        <h1 class="policy-title">PetBuddy Privacy Notice</h1>
        <p class="policy-date">Effective Date: December 11, 2025</p>
    </div>

    <div class="policy-section">
        <h2><img src="../images/shield.png" class="section-icon-img"> 1. Introduction</h2>
        <p>PetBuddy is committed to protecting your privacy and personal data. This Privacy Notice explains how we collect, use, disclose, and safeguard your information when you visit our website, place an order, or interact with us.</p>
        <p>By using our services, you consent to the data practices described in this statement.</p>
    </div>

    <div class="policy-section">
        <h2><img src="../images/support.png" class="section-icon-img"> 2. Information We Collect</h2>
        <p>We collect personal information that you voluntarily provide to us when registering, placing an order, subscribing to our newsletter, or contacting us.</p>

        <h3>Types of Data Collected:</h3>
        <ul>
            <li>**Identification Data:** Name, email address, phone number.</li>
            <li>**Transaction Data:** Shipping address, billing address, purchase history, and payment information (processed by secure third-party gateways).</li>
            <li>**Technical Data:** IP address, browser type, device type, operating system, and website usage data collected via cookies.</li>
        </ul>
    </div>

    <div class="policy-section">
        <h2><img src="../images/hammer.png" class="section-icon-img"> 3. How We Use Your Information</h2>
        <p>We use the information we collect for various business purposes, including:</p>

        <ul>
            <li>**Order Fulfillment:** To process transactions, manage orders, and deliver products.</li>
            <li>**Communication:** To send you updates regarding your order status, policies, and customer service responses.</li>
            <li>**Marketing:** To send promotional emails, special offers, and personalized content (you can opt-out at any time).</li>
            <li>**Service Improvement:** To analyze website usage, troubleshoot problems, and improve our product offerings and site functionality.</li>
        </ul>
    </div>

    <div class="policy-section">
        <h2><img src="../images/disclosure.png" class="section-icon-img"> 4. Disclosure of Your Information</h2>
        <p>We only share your data with third parties necessary to operate our business or as required by law. These may include:</p>

        <ul>
            <li>**Service Providers:** Courier services (for delivery), payment processors (for secure transactions), and IT service providers.</li>
            <li>**Legal Obligations:** When required by law or to protect the rights, property, or safety of PetBuddy, our customers, or others.</li>
        </ul>
        <p>We do not sell your personal data to third parties for marketing purposes.</p>
    </div>

    <div class="policy-section">
        <h2><img src="../images/security.png" class="section-icon-img"> 5. Data Security</h2>
        <p>We implement technical and organizational security measures designed to protect your personal information from unauthorized access, loss, or misuse. These measures include data encryption, secured networks, and access control procedures.</p>
        <p>While we strive to use commercially acceptable means to protect your data, no method of transmission over the Internet or method of electronic storage is 100% secure.</p>
    </div>

    <div class="policy-section">
        <h2><img src="../images/people.png" class="section-icon-img"> 6. Your Rights</h2>
        <p>As a user, you have certain rights regarding your personal data:</p>
        <ul>
            <li>**Right to Access:** You can request a copy of the personal data we hold about you.</li>
            <li>**Right to Rectification:** You can request that we correct any inaccurate or incomplete data.</li>
            <li>**Right to Withdraw Consent:** You can withdraw your consent to data processing (e.g., opting out of marketing emails) at any time.</li>
        </ul>
    </div>

    <div class="policy-section">
        <h2><img src="../images/phone-chat.png" class="section-icon-img"> 7. Contact Us</h2>
        <p>If you have questions or concerns about this Privacy Notice or wish to exercise your data rights, please contact our Data Protection Officer:</p>
        <p><strong>Email:</strong> <a href="mailto:privacy@petbuddy.my">privacy@petbuddy.my</a></p>
        <p><strong>Address:</strong> PetBuddy Legal Department, 12A, Jalan Setiawangsa, Setapak, 53300 Kuala Lumpur, Malaysia.</p>
    </div>

    <hr style="margin-top: 3rem; margin-bottom: 3rem; border-top: 2px solid #f3f4f6;">

    <section class="cta-section">
        <h2 class="cta-title">Need to Talk to Support?</h2>
        <p class="cta-text">If you have questions about your order or our services, please reach out to our Customer Service team.</p>
        <div class="btn-primary-wrapper">
            <a href="contact.php" class="btn-primary">
                Contact Customer Service
            </a>
        </div>
    </section>

</div>

<?php include '../include/footer.php'; ?>
<?php include '../include/chat_widget.php'; ?>