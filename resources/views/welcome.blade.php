<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="landing-document">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#121212">
    <meta name="description" content="SmartGym helps members stay connected to their membership and helps gym teams manage daily operations.">
    <title>SmartGym Operations | Your Fitness Journey Starts Here</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="landing">
<header class="site-header">
    <nav class="site-nav" aria-label="Main navigation">
        <div class="site-nav-inner">
            <a class="brand" href="#home"><img class="brand-logo" src="{{ asset('images/logo.png') }}" alt=""> SmartGym</a>
            <div class="site-links" id="site-links">
                <a href="#home">Home</a>
                <a href="#about">About</a>
                <a href="#features">Features</a>
                <a href="#plans">Membership</a>
                <a href="#contact">Contact</a>
            </div>
            <div class="site-actions">
                <a class="button" href="{{ route('login') }}">Login</a>
                <button class="site-menu-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="site-links">
                    <span class="hamburger-icon" aria-hidden="true"><span></span><span></span><span></span></span>
                </button>
            </div>
        </div>
    </nav>
</header>

<main>
    <section class="hero" id="home" aria-labelledby="hero-title">
        <div class="hero-art" aria-hidden="true">
            <img class="hero-image" src="{{ asset('images/gym-hero.jpg') }}" alt="" fetchpriority="high">
        </div>
        <div class="landing-container hero-copy">
            <span class="eyebrow">Your fitness journey, better organized</span>
            <h1 id="hero-title">Everything Your Gym Needs.<span>All in One Place.</span></h1>
            <p>One calm place for memberships, check-ins, payments, and equipment — built for members, staff, and owners.</p>
            <div class="hero-actions">
                <a class="button" href="#plans">View memberships</a>
                <a class="button secondary" href="#features">Learn more</a>
            </div>
            <div class="hero-stats" aria-label="SmartGym at a glance">
                <div><strong>3</strong><span>Role-based portals</span></div>
                <div><strong>Member</strong><span>Plans and account details</span></div>
                <div><strong>Operations</strong><span>Attendance and equipment</span></div>
            </div>
        </div>
    </section>

    <section class="landing-section about-section" id="about">
        <div class="landing-container about-panel">
            <div class="about-copy">
                <span class="eyebrow">About SmartGym</span>
                <h2>More than a place to work out.</h2>
                <p>SmartGym Operations connects members with the information they need and gives gym teams practical tools for day-to-day operations. Memberships, visits, payments, and updates are easier to follow when they are organized in one system.</p>
                <p>Whether you are checking your membership or keeping the gym running smoothly, SmartGym makes the essentials easier to manage.</p>
                <a class="text-link" href="#features">Get to know SmartGym <span aria-hidden="true">→</span></a>
            </div>
        </div>
    </section>

    <section class="landing-section" id="features">
        <div class="landing-container">
            <div class="section-heading">
                <span class="eyebrow">Everything in one place</span>
                <h2>Less paperwork. More training.</h2>
                <p>Practical tools for members, staff, and owners — without getting in the way.</p>
            </div>
            <div class="feature-grid">
                <article class="feature-card">
                    <span class="feature-icon" aria-hidden="true">01</span>
                    <h3>Members</h3>
                    <p>Member profiles and key details are organized for authorized gym teams.</p>
                </article>
                <article class="feature-card">
                    <span class="feature-icon" aria-hidden="true">02</span>
                    <h3>Memberships</h3>
                    <p>Review plans, membership requests, and recorded payments in one place.</p>
                </article>
                <article class="feature-card">
                    <span class="feature-icon" aria-hidden="true">03</span>
                    <h3>Attendance</h3>
                    <p>Keep gym check-ins organized and make visit history easy to review.</p>
                </article>
                <article class="feature-card">
                    <span class="feature-icon" aria-hidden="true">04</span>
                    <h3>Payments</h3>
                    <p>Find payment records and receipts alongside membership information.</p>
                </article>
                <article class="feature-card">
                    <span class="feature-icon" aria-hidden="true">05</span>
                    <h3>Equipment care</h3>
                    <p>Track equipment condition, usage, and maintenance history.</p>
                </article>
                <article class="feature-card">
                    <span class="feature-icon" aria-hidden="true">06</span>
                    <h3>Tools for gym teams</h3>
                    <p>Role-based dashboards help members, staff, and admins find relevant tools.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="landing-section plans-section" id="plans">
        <div class="landing-container">
            <div class="plans-intro">
                <div class="section-heading">
                    <span class="eyebrow">Membership options</span>
                    <h2>Find a plan that fits your next step.</h2>
                    <p>Simple access, honest pricing, and the freedom to build a routine that works for you.</p>
                </div>
            </div>
            @if ($plans->isEmpty())
                <div class="card">
                    <h3>Plans are not available right now</h3>
                    <p>Please check back later or sign in to your account for membership updates.</p>
                </div>
            @else
                <div class="plan-grid">
                    @foreach ($plans as $plan)
                        <article class="plan-card">
                            <span class="eyebrow">{{ $plan->duration_days }} days</span>
                            <h3>{{ $plan->plan_name }}</h3>
                            @if ($plan->description)
                                <p>{{ $plan->description }}</p>
                            @endif
                            <p class="plan-price">{{ number_format((float) $plan->price, 2) }}</p>
                            <a class="button secondary" href="{{ route('login') }}">Sign in to request this plan <span aria-hidden="true">→</span></a>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section class="landing-section">
        <div class="landing-container cta-panel">
            <div>
                <span class="eyebrow">Your next step</span>
                <h2>Ready to start your fitness journey?</h2>
                <p>Sign in to your SmartGym account to explore membership plans and manage your requests.</p>
            </div>
            <a class="button" href="{{ route('login') }}">Log in to continue <span aria-hidden="true">↗</span></a>
        </div>
    </section>

    <section class="landing-section contact-section" id="contact">
        <div class="landing-container contact-row">
            <div>
                <span class="eyebrow">Contact</span>
                <h2>We’re here to help.</h2>
                <p>For assistance, sign in to your account or contact your gym team directly.</p>
            </div>
            <a class="text-link" href="{{ route('login') }}">Go to login <span aria-hidden="true">→</span></a>
        </div>
    </section>
</main>

<footer class="site-footer">
    <div class="landing-container footer-row">
        <a class="brand" href="#home"><img class="brand-logo" src="{{ asset('images/logo.png') }}" alt=""> SmartGym Operations</a>
        <nav class="footer-links" aria-label="Footer navigation">
            <a href="#about">About</a>
            <a href="#features">Features</a>
            <a href="#plans">Plans</a>
            <a href="{{ route('login') }}">Log in</a>
        </nav>
        <small>© {{ now()->year }} SmartGym Operations</small>
    </div>
</footer>
</body>
</html>
