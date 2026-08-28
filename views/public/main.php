<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKonnect | Home</title>
    
    <link rel="stylesheet" href="../../styles/global.css">
    <link rel="stylesheet" href="../../styles/public/main.css">
    <link rel="stylesheet" href="../../styles/public/header.css">
    <link rel="stylesheet" href="../../styles/public/footer.css">
</head>
<body>

<?php include __DIR__ . '/../../components/public/navbar.php'; ?>

<main>

    <section class="hero">
        <div class="hero-bg"></div>
        <div class="hero-overlay"></div>

        <div class="hero-content">
            <h1 class="fade-in">Empowering the Youth of Barangay Sauyo</h1>
            <p class="hero-subtitle fade-in-delay">
                Transparent. Accessible. Connected.
            </p>

            <p class="hero-description fade-in-delay-2">
                SKonnect is the official digital platform of the Sangguniang Kabataan,
                providing transparent governance, accessible services, and real-time updates
                for every youth in our community.
            </p>

            <div class="hero-buttons fade-in-delay-3">
                <a href="../auth/register.php" class="btn btn-primary">Join Now</a>
                <a href="announcements.php" class="btn btn-outline">View Updates</a>
            </div>
        </div>

        <div class="floating-shape shape1"></div>
        <div class="floating-shape shape2"></div>
        <div class="floating-shape shape3"></div>
    </section>

    <section class="stats">
        <div class="container stats-grid">
            <div class="stat-card">
                <h2 class="counter" data-target="1200">0</h2>
                <p>Registered Youth</p>
            </div>
            <div class="stat-card">
                <h2 class="counter" data-target="35">0</h2>
                <p>Active Programs</p>
            </div>
            <div class="stat-card">
                <h2 class="counter" data-target="280">0</h2>
                <p>Requests Processed</p>
            </div>
            <div class="stat-card">
                <h2>100%</h2>
                <p>Transparency Commitment</p>
            </div>
        </div>
    </section>

    <section class="features">
        <div class="container">
            <h2 class="section-title">Our Services</h2>
            <div class="features-grid">
                <div class="card">
                    <div class="card-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                    </div>
                    <h3>Announcements</h3>
                    <p>Stay updated with the latest SK activities, programs, and community events in Barangay Sauyo.</p>
                </div>
                <div class="card">
                    <div class="card-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                    </div>
                    <h3>Submit Requests</h3>
                    <p>Apply for scholarships, financial assistance, and other SK programs conveniently online.</p>
                </div>
                <div class="card">
                    <div class="card-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                        </svg>
                    </div>
                    <h3>Transparency</h3>
                    <p>View ongoing projects, budget allocations, and services provided by the SK Council.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="programs">
        <div class="container">
            <h2 class="section-title">Ongoing Youth Programs</h2>

            <div class="program-grid">
                <div class="program-card">
                    <h3>Educational Assistance 2026</h3>
                    <p>Financial aid for deserving students in Barangay Sauyo.</p>
                    <span class="badge">Open</span>
                </div>

                <div class="program-card">
                    <h3>Medical Assistance Program</h3>
                    <p>Support for youth with medical emergencies and needs.</p>
                    <span class="badge">Available</span>
                </div>

                <div class="program-card">
                    <h3>Sports Development League</h3>
                    <p>Promoting physical wellness and youth engagement.</p>
                    <span class="badge">Ongoing</span>
                </div>
            </div>
        </div>
    </section>

    <section class="about">
        <div class="container">
            <div class="about-content">
                <h2>About SKonnect</h2>
                <p>SKonnect is Barangay Sauyo's digital platform designed to bridge the gap between the youth and the Sangguniang Kabataan. We are committed to transparent governance, accessible services, and active youth participation in community development.</p>
                <p>Through this platform, you can easily access SK services, stay informed about youth programs, and actively participate in shaping the future of our barangay.</p>
            </div>
        </div>
    </section>
</main>

<section class="cta">
    <div class="container">
        <h2>Be Part of the Change</h2>
        <p>Join SKonnect and actively participate in shaping the future of Barangay Sauyo.</p>
        <a href="../auth/register.php" class="btn btn-primary">Create an Account</a>
    </div>
</section>

<section class="contact-preview">
    <div class="container contact-grid">

        <div class="contact-card">
            <h2>Visit the SK Office</h2>

            <div class="contact-item">
                <span class="label">Address</span>
                <p>Barangay Sauyo Hall, Quezon City</p>
            </div>

            <div class="contact-item">
                <span class="label">Email</span>
                <p>sksauyo@gmail.com</p>
            </div>

            <div class="contact-item">
                <span class="label">Office Hours</span>
                <p>8:00 AM – 5:00 PM</p>
            </div>
        </div>

        <div class="map-embed">
            <iframe
                src="https://www.google.com/maps?q=Barangay+Sauyo+Hall,+Sauyo,+Novaliches,+Quezon+City&output=embed"
                width="100%"
                height="100%"
                style="border:0;"
                allowfullscreen=""
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                title="Barangay Sauyo Hall Location">
            </iframe>
        </div>

    </div>
</section>


<?php include __DIR__ . '/../../components/public/footer.php'; ?>

<script src="../../scripts/public/main.js"></script>
</body>
</html>