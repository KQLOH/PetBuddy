<?php
session_start();
include '../include/header.php';

$status_message = "";
$status_type = "";
$error_field = "";

if (isset($_GET['status']) && isset($_GET['msg'])) {
    $status_type = $_GET['status'];
    $status_message = htmlspecialchars($_GET['msg']);
}

if (isset($_GET['field'])) {
    $error_field = $_GET['field'];
}
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

    .contact-title {
        font-size: 2.25rem;
        font-weight: 700;
        color: #1f2937;
        text-align: center;
        margin-bottom: 1rem;
    }

    .contact-subtitle {
        text-align: center;
        font-size: 1.125rem;
        color: #4b5563;
        margin-bottom: 3rem;
        max-width: 56rem;
        margin-left: auto;
        margin-right: auto;
    }

    .main-layout {
        display: flex;
        flex-direction: column;
        gap: 3rem;
        margin-bottom: 4rem;
    }

    @media (min-width: 768px) {
        .main-layout {
            flex-direction: row;
        }

        .info-panel {
            width: 40%;
        }

        .form-panel {
            width: 60%;
        }
    }

    .info-panel {
        background-color: #fff7ed;
        padding: 2.5rem;
        border-radius: 0.75rem;
        border: 1px solid #FFB774;
        height: fit-content;
    }

    .info-panel h2 {
        font-size: 1.5rem;
        font-weight: 700;
        color: #E89C55;
        margin-bottom: 1.5rem;
    }

    .info-item {
        display: flex;
        align-items: center;
        margin-bottom: 1.5rem;
    }


    .contact-icon-img {
        width: 1.5rem;
        height: 1.5rem;
        object-fit: contain;
        margin-right: 1rem;
    }

    .info-text strong {
        display: block;
        font-weight: 600;
        color: #1f2937;
    }

    .info-text span {
        font-size: 0.95rem;
        color: #4b5563;
    }

    .info-text a {
        color: #4b5563;
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .info-text a:hover {
        color: #E89C55;
        text-decoration: underline;
    }

    .form-panel {
        padding: 2rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .form-panel h2 {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 1.5rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        font-weight: 500;
        color: #1f2937;
        margin-bottom: 0.5rem;
    }

    .form-group input,
    .form-group textarea {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        font-size: 1rem;
        color: #1f2937;
    }

    .form-group textarea {
        resize: vertical;
        min-height: 120px;
    }

    .btn-submit {
        background-color: #FFB774;
        color: #fff;
        font-weight: 600;
        padding: 0.75rem 2rem;
        border-radius: 0.5rem;
        transition: background-color 0.3s ease;
        display: inline-block;
        font-size: 1.125rem;
        border: none;
        cursor: pointer;
        width: 100%;
    }

    .btn-submit:hover {
        background-color: #E89C55;
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

    .alert-success {
        background-color: #FFB774;
        border-left: 4px solid #E89C55;
        color: white;
        padding: 1rem;
        border-radius: 0.5rem;
        margin-bottom: 2rem;
        font-size: 1rem;
    }

    .alert-error-status {
        background-color: #fee2e2;
        border-left: 4px solid #ef4444;
        color: #b91c1c;
        padding: 1rem;
        border-radius: 0.5rem;
        margin-bottom: 2rem;
        font-size: 1rem;
    }

    .field-error-msg {
        color: #ef4444;
        font-size: 0.85rem;
        margin-top: 0.25rem;
        display: block;
        font-weight: 500;
    }

    .input-error {
        border-color: #ef4444 !important;
        background-color: #fffafb;
    }
</style>

<div class="container-wrapper">
    <?php if ($status_type === 'success'): ?>
        <div class="alert-success"><?= htmlspecialchars($status_message) ?></div>
    <?php endif; ?>

    <div class="main-layout">
        <div class="info-panel">
            <h2>PetBuddy Contact Details</h2>
            <div class="info-item">
                <img src="../images/telephone.png" alt="Phone" class="contact-icon-img">
                <div class="info-text">
                    <strong>Phone Number</strong>
                    <span><a href="tel:+60388881234">+60 3-8888 1234 (Customer Service)</a></span>
                </div>
            </div>

            <div class="info-item">
                <img src="../images/mail.png" alt="Email" class="contact-icon-img">
                <div class="info-text">
                    <strong>Email Address</strong>
                    <span><a href="mailto:support@petbuddy.my">support@petbuddy.my</a></span>
                </div>
            </div>

            <div class="info-item">
                <img src="../images/store.png" alt="Location" class="contact-icon-img">
                <div class="info-text">
                    <strong>Flagship Store Address</strong>
                    <span>12A, Jalan Setiawangsa, Setapak, 53300 Kuala Lumpur, Malaysia</span>
                </div>
            </div>

            <div class="info-item">
                <img src="../images/clock.png" alt="Hours" class="contact-icon-img">
                <div class="info-text">
                    <strong>Operation Hours</strong>
                    <span>Monday - Sunday: 10:00 AM - 8:00 PM</span>
                </div>
            </div>
        </div>


        <div class="form-panel">
            <h2>Send Us a Message</h2>
            <form action="submit_contact.php" method="POST" novalidate>
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name"
                        class="<?= $error_field === 'name' ? 'input-error' : '' ?>"
                        placeholder="Enter your full name">
                    <?php if ($error_field === 'name'): ?>
                        <span class="field-error-msg"><?= htmlspecialchars($status_message) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email"
                        class="<?= $error_field === 'email' ? 'input-error' : '' ?>"
                        placeholder="Enter your email address">
                    <?php if ($error_field === 'email'): ?>
                        <span class="field-error-msg"><?= htmlspecialchars($status_message) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" id="subject" name="subject"
                        class="<?= $error_field === 'subject' ? 'input-error' : '' ?>"
                        placeholder="Briefly describe your inquiry">
                    <?php if ($error_field === 'subject'): ?>
                        <span class="field-error-msg"><?= htmlspecialchars($status_message) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="message">Message Content</label>
                    <textarea id="message" name="message"
                        class="<?= $error_field === 'message' ? 'input-error' : '' ?>"
                        placeholder="Type your detailed message here..."></textarea>
                    <?php if ($error_field === 'message'): ?>
                        <span class="field-error-msg"><?= htmlspecialchars($status_message) ?></span>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn-submit">Send Message</button>
            </form>
        </div>
    </div>
</div>

<hr class="divider">

<section class="cta-section">
    <h2 class="cta-title">Need Quick Support?</h2>
    <p class="cta-text">Check out our Frequently Asked Questions (FAQ) page—the answer to your query might already be there.</p>
    <div class="btn-primary-wrapper">
        <a href="faq.php" class="btn-primary">
            View FAQ
        </a>
    </div>
</section>

</div>

<?php include '../include/footer.php'; ?>