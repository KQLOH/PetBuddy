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

    .about-title {
        font-size: 2.25rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 2rem;
    }

    .story-layout {
        display: flex;
        flex-direction: column;
        gap: 2rem;
        align-items: flex-start;
        margin-bottom: 3rem;
    }

    .story-image-content {
        width: 100%;
    }

    .story-text-content {
        width: 100%;
    }

    @media (min-width: 768px) {
        .story-layout {
            flex-direction: row;
        }

        .story-image-content {
            width: 50%;
        }

        .story-text-content {
            width: 50%;
        }
    }

    .story-paragraph {
        font-size: 1.125rem;
        color: #374151;
        line-height: 1.625;
    }

    .story-paragraph-semibold {
        font-weight: 600;
    }

    .store-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 0.5rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    .services-section {
        margin-bottom: 5rem;
        padding-top: 3rem;
    }

    .services-title {
        font-size: 2.25rem;
        font-weight: 700;
        text-align: center;
        color: #1f2937;
        margin-bottom: 2.5rem;
    }

    .services-description {
        text-align: center;
        font-size: 1.125rem;
        color: #4b5563;
        margin-bottom: 3rem;
        max-width: 56rem;
        margin-left: auto;
        margin-right: auto;
    }

    .services-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 2rem;
    }

    @media (min-width: 768px) {
        .services-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (min-width: 1024px) {
        .services-grid {
            grid-template-columns: repeat(4, 1fr);
        }
    }

    .service-card {
        padding: 1.5rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        transition: all 0.3s ease;
    }

    .service-card-hover:hover {
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        border-color: #FFB774;
    }


    .card-icon {
        font-size: 2.25rem;
        display: block;
        margin-bottom: 1rem;
    }

    .card-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 0.5rem;
    }

    .card-text {
        color: #4b5563;
        font-size: 0.875rem;
    }

    .divider {
        margin-top: 3rem;
        margin-bottom: 3rem;
        border-top: 2px solid #f3f4f6;
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
    .card-image {
    width: 3.5rem;
    height: 3.5rem; 
    display: block;
    margin-bottom: 1rem;
    object-fit: contain; 
}
</style>

<div class="container-wrapper">

    <div class="aboutHeader">
        <div class="aboutHeaderh1">
            <h1 class="about-title">Our Story</h1>
        </div>

        <div class="story-layout">

            <div class="aboutHeaderimg story-image-content">
                <img src="../images/petstore.png" alt="PetBuddy physical store or team image" class="store-image">
            </div>

            <div class="aboutHeaderword story-text-content">
                <p class="story-paragraph">
                    PetBuddy's vision came to life in early 2025 with the launch of its flagship store in Setapak, Kuala Lumpur, initiated by a collective of local entrepreneurs, certified veterinarians, and pet specialists to offer an integrated, expert-driven approach to pet wellness. Governed by the pledge "More Care, More Joy, More Buddy," the founding philosophy is to be a holistic care partner for pet families, offering everything from meticulously curated, evidence-based nutritional products to professional and ethical services like grooming and consultations. Following a positive reception from the Setapak community, PetBuddy is swiftly evolving into Malaysia's definitive expert pet care provider, expanding its reach nationwide, pioneering innovative member benefits, and hosting educational workshops, all while remaining rooted in fostering a healthier, happier future for companion animals and supporting local welfare charities.
                </p>

            </div>

        </div>

        <section class="services-section">
            <h2 class="services-title">Our Core Services & Commitment</h2>

            <p class="services-description">
                PetBuddy is designed as a fully integrated ecosystem that supports every aspect of your pet's well-being, from initial adoption to senior care management.
            </p>

            <div class="services-grid">

                <div class="service-card service-card-hover">
                    <img src="../images/shopping-cart.png" class="card-image">
                    <h3 class="card-title">Expert-Curated Retail</h3>
                    <p class="card-text">We provide a meticulously selected range of premium, evidence-based nutrition and wellness products. Every item is verified by our specialists for safety and efficacy.</p>
                </div>

                <div class="service-card service-card-hover">
                    <img src="../images/heartbeat.png"  class="card-image">
                    <h3 class="card-title">Wellness & Advice</h3>
                    <p class="card-text">Offering professional nutritional counseling and preventative health advice. Our goal is to proactively support your pet's long-term vitality and health management.</p>
                </div>

                <div class="service-card service-card-hover">
                    <img src="../images/grooming-specialist.png"  class="card-image">
                    <h3 class="card-title">Professional Grooming</h3>
                    <p class="card-text">Our Setapak location offers calming, certified grooming services. Our experts are trained to handle all breeds, prioritizing comfort and stress-free experiences.</p>
                </div>

                <div class="service-card service-card-hover">
                    <img src="../images/engagement.png"  class="card-image">
                    <h3 class="card-title">Ethical & Community Support</h3>
                    <p class="card-text">We strictly follow ethical sourcing guidelines and actively partner with local KL animal welfare organizations to support adoption and rescue initiatives.</p>
                </div>

            </div>
        </section>

        <hr class="divider">

        <section class="cta-section">
            <h2 class="cta-title">Ready to meet the team behind the mission?</h2>
            <p class="cta-text">Learn more about the veterinarians and pet specialists dedicated to your pet's health.</p>
            <div class="btn-primary-wrapper">
                <a href="contact.php" class="btn-primary">
                    Meet Our Team
                </a>
            </div>
        </section>

    </div>
</div>

<?php include '../include/footer.php'; ?>
<?php include '../include/chat_widget.php'; ?>