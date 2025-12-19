<?php
session_start();
include '../include/header.php';
?>

<style>
    .container-wrapper {
        max-width: 1280px;
        margin-left: auto;
        margin-right: auto;
        padding-left: 1rem;
        padding-right: 1rem;
        padding-top: 2rem;
        padding-bottom: 2rem;
    }

    .faq-title {
        font-size: 2.5rem;
        font-weight: 700;
        color: #1f2937;
        text-align: center;
        margin-bottom: 0.5rem;
    }

    .faq-subtitle {
        text-align: center;
        font-size: 1.125rem;
        color: #4b5563;
        margin-bottom: 4rem;
        max-width: 48rem;
        margin-left: auto;
        margin-right: auto;
    }

    .accordion-container {
        max-width: 64rem;
        margin: 0 auto;
    }

    .accordion-item {
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
        overflow: hidden;
        transition: box-shadow 0.3s ease;
    }

    .accordion-item:hover {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .accordion-header {
        background-color: #f9fafb;
        padding: 1.25rem 1.5rem;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        user-select: none;
    }

    .accordion-header:hover {
        background-color: #f3f4f6;
    }

    .accordion-header h3 {
        font-size: 1.125rem;
        font-weight: 600;
        color: #1f2937;
    }

    .accordion-icon {
        font-size: 1.5rem;
        color: #FFB774;
        transition: transform 0.3s ease;
        line-height: 1;
        margin-left: 1rem;
    }

    .accordion-header.active .accordion-icon {
        transform: rotate(45deg);
        color: #FFB774;
    }

    .accordion-content {
        background-color: #ffffff;
        padding: 0 1.5rem;
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.4s ease-in-out, padding 0.4s ease-in-out;
    }

    .accordion-content.active {
        padding: 1.5rem;
        max-height: 2000px;
    }

    .accordion-content p {
        font-size: 1rem;
        color: #4b5563;
        line-height: 1.6;
    }

    .divider {
        margin-top: 3rem;
        margin-bottom: 3rem;
        border-top: 2px solid #f3f4f6;
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

    .section-icon-img {
        width: 2rem;
        height: 2rem;
        object-fit: contain;
        margin-right: 0.5rem;
        vertical-align: middle;
    }
</style>

<div class="container-wrapper">
    <div class="faq-header">
        <h1 class="faq-title">PetBuddy Frequently Asked Questions (FAQ)</h1>
        <p class="faq-subtitle">
            Below are some of the questions most frequently asked by our customers. If you can't find the answer you need, please don't hesitate to reach out via our contact page.
        </p>
    </div>

    <div class="accordion-container">

        <h2>
            <img src="../images/store.png" class="section-icon-img">Store & Services
        </h2>

        <div class="accordion-item">
            <div class="accordion-header" onclick="toggleAccordion(this)">
                <h3>Where is the PetBuddy flagship store located?</h3>
                <span class="accordion-icon">+</span>
            </div>
            <div class="accordion-content">
                <p>Our flagship store is located in **Setapak, Kuala Lumpur**. We also offer extensive online services and nationwide delivery to ensure pets across Malaysia have access to our premium products and expert advice.</p>
            </div>
        </div>

        <div class="accordion-item">
            <div class="accordion-header" onclick="toggleAccordion(this)">
                <h3>What are PetBuddy's operation hours?</h3>
                <span class="accordion-icon">+</span>
            </div>
            <div class="accordion-content">
                <p>Our physical store and online customer service are open from **Monday to Sunday, 10:00 AM to 8:00 PM**. You can browse and place orders online at any time, which we will process during operation hours.</p>
            </div>
        </div>

        <div class="accordion-item">
            <div class="accordion-header" onclick="toggleAccordion(this)">
                <h3>Do you offer pet grooming services?</h3>
                <span class="accordion-icon">+</span>
            </div>
            <div class="accordion-content">
                <p>Yes, our Setapak flagship location features a professional grooming section. Our certified groomers are dedicated to providing a gentle, low-stress experience. We recommend booking an appointment in advance through our website or by phone.</p>
            </div>
        </div>

               <h2>
            <img src="../images/checkout.png" class="section-icon-img">Ordering & Shipping
        </h2>

        <div class="accordion-item">
            <div class="accordion-header" onclick="toggleAccordion(this)">
                <h3>How long does it take for an order to ship?</h3>
                <span class="accordion-icon">+</span>
            </div>
            <div class="accordion-content">
                <p>We typically process and dispatch orders within **1-2 working days** of receipt. For orders within the Klang Valley, delivery is expected within 2-4 working days; for other parts of Malaysia, please allow 4-7 working days. If you experience delays beyond these estimates, please contact our support team.</p>
            </div>
        </div>

        <div class="accordion-item">
            <div class="accordion-header" onclick="toggleAccordion(this)">
                <h3>How can I track my order?</h3>
                <span class="accordion-icon">+</span>
            </div>
            <div class="accordion-content">
                <p>Once your order has shipped, you will receive an email containing a tracking number and a link. You can use this tracking number to check the real-time status of your package on our partner courier's website. Please allow up to 24 hours for the tracking information to become active after receiving the email.</p>
            </div>
        </div>

        <div class="accordion-item">
            <div class="accordion-header" onclick="toggleAccordion(this)">
                <h3>What is your return and refund policy?</h3>
                <span class="accordion-icon">+</span>
            </div>
            <div class="accordion-content">
                <p>We offer a **7-day return period** (excluding certain consumables, personalized items, and perishable food), provided the item is unused, unopened, and in its original packaging. Refunds are processed within 5-10 working days after the returned item is received and inspected. Please refer to the "Return Policy" link at the bottom of our website for complete details and the full refund process steps.</p>
            </div>
        </div>

    </div>

    <hr class="divider">

    <section class="cta-section">
        <h2 class="cta-title">Can't find the answer you need?</h2>
        <p class="cta-text">Reach out directly to our dedicated team of specialists, and we'll be happy to assist you.</p>
        <div class="btn-primary-wrapper">
            <a href="contact.php" class="btn-primary">
                Contact Us
            </a>
        </div>
    </section>

</div>

<script>
    function toggleAccordion(header) {
        header.classList.toggle('active');

        const content = header.nextElementSibling;

        content.classList.toggle('active');
    }
</script>

<?php include '../include/footer.php'; ?>
<?php include '../include/chat_widget.php'; ?>