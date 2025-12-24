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
    :root {
        --primary-color: #FFB774;
        --primary-dark: #E89C55;
        --text-dark: #2F2F2F;
        --text-light: #666;
        --border-color: #e8e8e8;
        --bg-light: #FFF9F4;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: "Inter", system-ui, sans-serif;
        background: #fff;
        color: var(--text-dark);
    }

    /* Hero Section */
    .contact-hero {
        background: linear-gradient(135deg, #FFE8D1 0%, #FFF5EC 100%);
        padding: 80px 40px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .contact-hero::before {
        content: '';
        position: absolute;
        top: 20px;
        right: 10%;
        font-size: 120px;
        opacity: 0.1;
    }

    .contact-hero::after {
        content: '';
        position: absolute;
        bottom: 20px;
        left: 10%;
        font-size: 100px;
        opacity: 0.1;
    }

    .hero-badge {
        display: inline-block;
        background: white;
        padding: 8px 20px;
        border-radius: 30px;
        font-size: 14px;
        font-weight: 600;
        color: var(--primary-dark);
        margin-bottom: 20px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    }

    .contact-title {
        font-size: 48px;
        font-weight: 800;
        color: var(--text-dark);
        margin-bottom: 15px;
        line-height: 1.2;
    }

    .contact-subtitle {
        font-size: 18px;
        color: var(--text-light);
        max-width: 600px;
        margin: 0 auto;
        line-height: 1.6;
    }

    /* Container */
    .container-wrapper {
        max-width: 1280px;
        margin: 0 auto;
        padding: 80px 40px;
    }

    /* Quick Contact Cards */
    .quick-contact-section {
        margin-bottom: 80px;
    }

    .quick-contact-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 25px;
        margin-bottom: 60px;
    }

    .contact-card {
        background: white;
        border: 2px solid var(--border-color);
        border-radius: 20px;
        padding: 35px 25px;
        text-align: center;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .contact-card:hover {
        transform: translateY(-8px);
        border-color: var(--primary-color);
        box-shadow: 0 15px 40px rgba(255, 183, 116, 0.2);
    }

    .contact-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary-color), var(--primary-dark));
        transform: scaleX(0);
        transition: transform 0.3s ease;
    }

    .contact-card:hover::before {
        transform: scaleX(1);
    }

    .contact-icon-wrapper {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, var(--bg-light) 0%, #FFF5EC 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        transition: all 0.3s ease;
    }

    .contact-card:hover .contact-icon-wrapper {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
        transform: scale(1.1) rotate(10deg);
        box-shadow: 0 10px 30px rgba(255, 183, 116, 0.4);
    }

    .contact-icon-wrapper img {
        width: 40px;
        height: 40px;
        object-fit: contain;
        transition: all 0.3s ease;
    }

    .contact-card:hover .contact-icon-wrapper img {
        filter: brightness(0) invert(1);
    }

    .contact-card h3 {
        font-size: 20px;
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 10px;
    }

    .contact-card p {
        font-size: 15px;
        color: var(--text-light);
        line-height: 1.6;
        margin-bottom: 15px;
    }

    .contact-card a {
        color: var(--text-dark);
        text-decoration: none;
        font-weight: 600;
        transition: color 0.3s ease;
    }

    .contact-card a:hover {
        color: var(--primary-dark);
    }

    .info-content a {
        color: var(--text-dark);
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .info-content a:hover {
        color: var(--primary-dark);
    }

    /* Main Content Layout */
    .main-layout {
        display: grid;
        grid-template-columns: 1fr;
        gap: 40px;
        margin-bottom: 80px;
    }

    @media (min-width: 968px) {
        .main-layout {
            grid-template-columns: 1fr 1.2fr;
        }
    }

    /* Info Panel */
    .info-panel {
        background: white;
        padding: 40px;
        border-radius: 25px;
        border: 2px solid var(--border-color);
        height: fit-content;
        position: sticky;
        top: 120px;
    }

    .info-panel h2 {
        font-size: 28px;
        font-weight: 800;
        color: var(--text-dark);
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .store-image {
        width: 100%;
        height: 200px;
        border-radius: 15px;
        object-fit: cover;
        margin-bottom: 25px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }

    .info-list {
        list-style: none;
    }

    .info-list li {
        padding: 18px 0;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: flex-start;
        gap: 15px;
    }

    .info-list li:last-child {
        border-bottom: none;
    }

    .info-icon {
        width: 24px;
        height: 24px;
        flex-shrink: 0;
        margin-top: 2px;
    }

    .info-content strong {
        display: block;
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 5px;
        font-size: 15px;
    }

    .info-content span {
        font-size: 14px;
        color: var(--text-light);
        line-height: 1.6;
    }

    /* Form Panel */
    .form-panel {
        background: white;
        padding: 45px;
        border-radius: 25px;
        box-shadow: 0 8px 30px rgba(0,0,0,0.08);
        border: 2px solid var(--border-color);
    }

    .form-panel h2 {
        font-size: 28px;
        font-weight: 800;
        color: var(--text-dark);
        margin-bottom: 10px;
    }

    .form-description {
        font-size: 15px;
        color: var(--text-light);
        margin-bottom: 35px;
    }

    .form-group {
        margin-bottom: 25px;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 10px;
        font-size: 15px;
    }

    .form-group input,
    .form-group textarea {
        width: 100%;
        padding: 14px 18px;
        border: 2px solid var(--border-color);
        border-radius: 12px;
        font-size: 15px;
        color: var(--text-dark);
        transition: all 0.3s ease;
        font-family: inherit;
    }

    .form-group input:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 4px rgba(255, 183, 116, 0.1);
    }

    .form-group textarea {
        resize: vertical;
        min-height: 140px;
    }

    .input-error {
        border-color: #ef4444 !important;
        background-color: #fff5f5;
    }

    .field-error-msg {
        color: #ef4444;
        font-size: 13px;
        margin-top: 6px;
        display: block;
        font-weight: 500;
    }

    .btn-submit {
        width: 100%;
        background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        color: white;
        font-weight: 700;
        padding: 16px;
        border-radius: 12px;
        border: none;
        cursor: pointer;
        font-size: 16px;
        transition: all 0.3s ease;
        box-shadow: 0 8px 25px rgba(255, 183, 116, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .btn-submit:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 35px rgba(255, 183, 116, 0.4);
    }

    .btn-submit:active {
        transform: translateY(-1px);
    }

    /* Alert Messages */
    .alert {
        padding: 18px 25px;
        border-radius: 12px;
        margin-bottom: 30px;
        font-size: 15px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    .alert-success {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }

    .alert-error {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
    }

    /* Map Section */
    .map-section {
        background: var(--bg-light);
        padding: 80px 40px;
        margin-top: 60px;
    }

    .map-header {
        text-align: center;
        margin-bottom: 50px;
    }

    .map-header h2 {
        font-size: 36px;
        font-weight: 800;
        color: var(--text-dark);
        margin-bottom: 15px;
    }

    .map-header p {
        font-size: 17px;
        color: var(--text-light);
    }

    .map-layout {
        max-width: 1280px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: 1fr;
        gap: 30px;
    }

    @media (min-width: 968px) {
        .map-layout {
            grid-template-columns: 1.5fr 1fr;
        }
    }

    .map-container {
        width: 100%;
        height: 500px;
        border-radius: 25px;
        overflow: hidden;
        box-shadow: 0 15px 50px rgba(0,0,0,0.15);
        border: 3px solid white;
    }

    .map-container iframe {
        width: 100%;
        height: 100%;
        border: none;
    }

    .address-info {
        background: white;
        padding: 40px;
        border-radius: 25px;
        box-shadow: 0 15px 50px rgba(0,0,0,0.1);
        height: fit-content;
        border: 2px solid var(--border-color);
    }

    .address-info h3 {
        font-size: 24px;
        font-weight: 800;
        color: var(--text-dark);
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .address-details {
        margin-bottom: 25px;
    }

    .address-item {
        margin-bottom: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid var(--border-color);
    }

    .address-item:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .address-item strong {
        display: block;
        font-weight: 700;
        color: var(--primary-dark);
        margin-bottom: 8px;
        font-size: 15px;
    }

    .address-item span {
        color: var(--text-light);
        font-size: 14px;
        line-height: 1.7;
    }

    .map-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 14px 28px;
        background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        color: white;
        text-decoration: none;
        border-radius: 50px;
        font-weight: 600;
        font-size: 15px;
        transition: all 0.3s ease;
        box-shadow: 0 6px 20px rgba(255, 183, 116, 0.3);
    }

    .map-link:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(255, 183, 116, 0.4);
    }

    /* CTA Section */
    .cta-section {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
        color: white;
        text-align: center;
        padding: 80px 40px;
        border-radius: 30px;
        margin: 80px 40px 40px;
        max-width: 1280px;
        margin-left: auto;
        margin-right: auto;
        position: relative;
        overflow: hidden;
    }

    .cta-section::before {
        content: '❓';
        position: absolute;
        top: -20px;
        right: 10%;
        font-size: 150px;
        opacity: 0.1;
    }

    .cta-title {
        font-size: 36px;
        font-weight: 800;
        margin-bottom: 15px;
    }

    .cta-text {
        font-size: 18px;
        margin-bottom: 30px;
        opacity: 0.95;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }

    .btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        background: white;
        color: var(--primary-dark);
        font-weight: 700;
        padding: 16px 40px;
        border-radius: 50px;
        text-decoration: none;
        font-size: 16px;
        transition: all 0.3s ease;
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }

    .btn-primary:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(0,0,0,0.25);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .contact-hero {
            padding: 60px 20px;
        }

        .contact-title {
            font-size: 36px;
        }

        .container-wrapper {
            padding: 60px 20px;
        }

        .form-panel,
        .info-panel {
            padding: 30px 25px;
        }

        .map-container {
            height: 350px;
        }

        .cta-section {
            padding: 60px 25px;
            margin: 60px 20px 20px;
        }

        .cta-title {
            font-size: 28px;
        }
    }
</style>

<!-- Hero Section -->
<div class="contact-hero">
    <span class="hero-badge">
    <i class="fas fa-paw"></i> Get In Touch
</span>
    <h1 class="contact-title">We'd Love to Hear From You</h1>
    <p class="contact-subtitle">
        Have questions about our products or services? Our friendly team is here to help you find the perfect solutions for your pets.
    </p>
</div>

<div class="container-wrapper">
    <?php if ($status_type === 'success'): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle" style="font-size: 24px;"></i>
            <?= htmlspecialchars($status_message) ?>
        </div>
    <?php elseif ($status_type === 'error' && empty($error_field)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle" style="font-size: 24px;"></i>
            <?= htmlspecialchars($status_message) ?>
        </div>
    <?php endif; ?>

    <!-- Quick Contact Section -->
    <div class="quick-contact-section">
        <div class="quick-contact-grid">
            <div class="contact-card">
                <div class="contact-icon-wrapper">
                    <img src="../images/telephone.png" alt="Phone">
                </div>
                <h3>Call Us</h3>
                <p>Speak with our customer service team</p>
                <a href="tel:+60388881234">+60 3-8888 1234</a>
            </div>

            <div class="contact-card">
                <div class="contact-icon-wrapper">
                    <img src="../images/mail.png" alt="Email">
                </div>
                <h3>Email Us</h3>
                <p>Send us your inquiries anytime</p>
                <a href="mailto:support@petbuddy.my">support@petbuddy.my</a>
            </div>

            <div class="contact-card">
                <div class="contact-icon-wrapper">
                    <img src="../images/store.png" alt="Store">
                </div>
                <h3>Visit Store</h3>
                <p>Come see us at our flagship location</p>
                <a href="#map">View Location</a>
            </div>

            <div class="contact-card">
                <div class="contact-icon-wrapper">
                    <img src="../images/clock.png" alt="Hours">
                </div>
                <h3>Business Hours</h3>
                <p>Mon - Sun: 10:00 AM - 8:00 PM</p>
                <span style="font-size: 13px; color: var(--text-light);">Every Day of the Week</span>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-layout">
        <!-- Info Panel -->
        <div class="info-panel">
            <h2><i class="fas fa-store"></i> Our Store</h2>
            <img src="../images/tarumtarena.jpg" 
                 alt="PetBuddy Store" 
                 class="store-image"
                 onerror="this.style.display='none'">
            
            <ul class="info-list">
                <li>
                    <img src="../images/store.png" alt="Address" class="info-icon">
                    <div class="info-content">
                        <strong>Flagship Store Address</strong>
                        <span>Arena, TAR UMT, Jalan Genting Kelang, Setapak, 53100 Kuala Lumpur, Malaysia</span>
                    </div>
                </li>
                <li>
                    <img src="../images/telephone.png" alt="Phone" class="info-icon">
                    <div class="info-content">
                        <strong>Customer Service</strong>
                        <span><a href="tel:+60388881234">+60 3-8888 1234</a></span>
                    </div>
                </li>
                <li>
                    <img src="../images/mail.png" alt="Email" class="info-icon">
                    <div class="info-content">
                        <strong>Email Support</strong>
                        <span><a href="mailto:support@petbuddy.my">support@petbuddy.my</a></span>
                    </div>
                </li>
                <li>
                    <img src="../images/clock.png" alt="Hours" class="info-icon">
                    <div class="info-content">
                        <strong>Operation Hours</strong>
                        <span>Monday - Sunday<br>10:00 AM - 8:00 PM</span>
                    </div>
                </li>
            </ul>
        </div>

        <!-- Form Panel -->
        <div class="form-panel">
            <h2>Send Us a Message</h2>
            <p class="form-description">Fill out the form below and we'll get back to you as soon as possible.</p>
            
            <form action="submit_contact.php" method="POST" novalidate>
                <div class="form-group">
                    <label for="name">Your Name *</label>
                    <input type="text" 
                           id="name" 
                           name="name"
                           class="<?= $error_field === 'name' ? 'input-error' : '' ?>"
                           placeholder="Enter your full name"
                           required>
                    <?php if ($error_field === 'name'): ?>
                        <span class="field-error-msg"><?= htmlspecialchars($status_message) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" 
                           id="email" 
                           name="email"
                           class="<?= $error_field === 'email' ? 'input-error' : '' ?>"
                           placeholder="your.email@example.com"
                           required>
                    <?php if ($error_field === 'email'): ?>
                        <span class="field-error-msg"><?= htmlspecialchars($status_message) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="subject">Subject *</label>
                    <input type="text" 
                           id="subject" 
                           name="subject"
                           class="<?= $error_field === 'subject' ? 'input-error' : '' ?>"
                           placeholder="What is your inquiry about?"
                           required>
                    <?php if ($error_field === 'subject'): ?>
                        <span class="field-error-msg"><?= htmlspecialchars($status_message) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="message">Message *</label>
                    <textarea id="message" 
                              name="message"
                              class="<?= $error_field === 'message' ? 'input-error' : '' ?>"
                              placeholder="Tell us more about your inquiry..."
                              required></textarea>
                    <?php if ($error_field === 'message'): ?>
                        <span class="field-error-msg"><?= htmlspecialchars($status_message) ?></span>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane"></i>
                    Send Message
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Map Section -->
<div class="map-section" id="map">
    <div class="map-header">
        <h2>Visit Our Store</h2>
        <p>Find us on the map and come visit our flagship location</p>
    </div>
    
    <div class="map-layout">
        <div class="map-container">
            <iframe 
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d1991.7660979083544!2d101.72661575660737!3d3.2166928771711074!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31cc39003e2819bb%3A0xccb90e34ef34e052!2sTAR%20UMT%20Arena!5e0!3m2!1szh-CN!2smy!4v1766559255079!5m2!1szh-CN!2smy" 
                allowfullscreen="" 
                loading="lazy" 
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>

        <div class="address-info">
            <h3><i class="fas fa-map-marker-alt"></i> Store Location</h3>
            
            <div class="address-details">
                <div class="address-item">
                    <strong>Full Address</strong>
                    <span>Arena, TAR UMT<br>Jalan Genting Kelang, Setapak<br>53100 Kuala Lumpur<br>Malaysia</span>
                </div>

                <div class="address-item">
                    <strong>Parking Available</strong>
                    <span>Free parking for customers at TAR UMT Arena</span>
                </div>

                <div class="address-item">
                    <strong>Public Transport</strong>
                    <span>Accessible via LRT and bus services</span>
                </div>
            </div>

            <a href="https://www.google.com/maps/place/TAR+UMT+Arena/@3.2158164,101.7273638,18z/data=!3m1!4b1!4m6!3m5!1s0x31cc39003e2819bb:0xccb90e34ef34e052!8m2!3d3.2158137!4d101.7286539!16s%2Fg%2F11x7q7wxk3?entry=ttu&g_ep=EgoyMDI1MTIwOS4wIKXMDSoASAFQAw%3D%3D" 
               target="_blank" 
               class="map-link">
                <i class="fas fa-map-marker-alt"></i>
                Open in Google Maps
            </a>
        </div>
    </div>
</div>

<!-- CTA Section -->
<section class="cta-section">
    <h2 class="cta-title">Need Quick Answers?</h2>
    <p class="cta-text">
        Check out our Frequently Asked Questions page—your question might already be answered there!
    </p>
    <a href="faq.php" class="btn-primary">
        <i class="fas fa-question-circle"></i>
        View FAQ
    </a>
</section>



<?php include '../include/footer.php'; ?>