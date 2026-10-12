<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Janet's Beauty Salon | Baguio City</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    @vite(['resources/css/landing.css'])
</head>
<body>
@php
    $loggedIn = auth('customer')->check() || auth('staff')->check();
    $homeUrl = \App\Support\Home::url();
@endphp
<div class="site-shell">
    <nav class="nav">
        <a class="brand-mark" href="#home">
            <div class="brand-name">Janet's<em>Beauty Salon</em></div>
        </a>
        <div class="nav-links">
            <a href="#story">Story</a>
            <a href="#categories">Services</a>
            <a href="#gallery">Gallery</a>
            <a href="#location">Visit</a>
            @if ($loggedIn)
                <a href="{{ $homeUrl }}" class="btn-main"><i class="fas fa-gauge"></i> My dashboard</a>
            @else
                <a href="{{ route('login') }}" class="btn-main"><i class="fas fa-calendar-check"></i> Login / Book</a>
            @endif
        </div>
    </nav>

    <main>
        <section id="home" class="hero">
            <div class="hero-inner">
                <div class="hero-copy">
                    <span class="eyebrow"><i class="fas fa-spa"></i> Baguio City, Philippines</span>
                    <h1>Janet's<em>Beauty Salon</em></h1>
                    <p>Premium hair, makeup, nails, facial, massage, and event beauty services shaped with care, warmth, and detail.</p>
                    <div class="hero-actions">
                        @if ($loggedIn)
                            <a href="{{ $homeUrl }}" class="btn-main"><i class="fas fa-gauge"></i> Go to my dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="btn-main"><i class="fas fa-calendar-check"></i> Book appointment</a>
                            <a href="{{ route('register') }}" class="btn-ghost"><i class="fas fa-user-plus"></i> Create account</a>
                        @endif
                    </div>
                    <div class="hero-steps">
                        <div class="hero-step"><i class="fas fa-list"></i><span>{{ $categories->count() }} curated service categories</span></div>
                        <div class="hero-step"><i class="fas fa-clock"></i><span>Open {{ $openingHours }}</span></div>
                        <div class="hero-step"><i class="fas fa-home"></i><span>Makeup services available for home appointments</span></div>
                    </div>
                </div>

                <div class="quick-panel">
                    <div class="mini-stat"><strong>20+</strong><span>Years of salon experience</span></div>
                    <div class="mini-stat"><strong>82+</strong><span>Available beauty services</span></div>
                    <div class="mini-action">
                        <p>Abanao-Zandueta-Kayong-Chugum-Otek, Baguio City. Appointments, walk-ins, and event styling are welcome.</p>
                        <a href="#location" class="btn-ghost"><i class="fas fa-location-dot"></i> Visit details</a>
                    </div>
                </div>
            </div>
        </section>

        <section id="story" class="section story">
            <div class="section-inner story-grid">
                <div class="story-copy">
                    <span class="eyebrow"><i class="fas fa-heart"></i> Our Story</span>
                    <h2>Quiet luxury, careful hands, and a deeply personal salon experience.</h2>
                    <p>Founded by Janet Arkias, the salon brings together modern beauty techniques and the familiar warmth clients return to. Every appointment is handled with attention to timing, comfort, and the look you want to carry out the door.</p>
                    <div class="story-stats">
                        <div class="story-stat"><strong>15k+</strong><span>Clients served</span></div>
                        <div class="story-stat"><strong>10</strong><span>Beauty experts</span></div>
                        <div class="story-stat"><strong>7</strong><span>Days open</span></div>
                    </div>
                </div>
                <div class="story-image">
                    <img src="https://images.pexels.com/photos/3998424/pexels-photo-3998424.jpeg?auto=compress&cs=tinysrgb&w=1000" alt="Salon interior">
                </div>
            </div>
        </section>

        <section id="categories" class="section">
            <div class="section-inner">
                <div class="section-head">
                    <div>
                        <span class="eyebrow"><i class="fas fa-wand-magic-sparkles"></i> Service Menu</span>
                        <h2>Beauty services for daily care and special moments.</h2>
                    </div>
                    <p>Browse by category, then sign in to choose your service, preferred date, and available staff.</p>
                </div>

                <div class="category-grid">
                    @forelse ($categories as $category)
                        <a class="category-card" href="{{ route('login') }}">
                            <div>
                                <div class="category-icon"><i class="fas {{ $category->icon }}"></i></div>
                                <h3>{{ $category->category_name }}</h3>
                            </div>
                            <div class="card-meta">
                                <span>{{ $category->services_count }} services</span>
                                @if ($category->is_event_type)
                                    <span class="badge"><i class="fas fa-star"></i> Event</span>
                                @endif
                            </div>
                        </a>
                    @empty
                        <div class="empty-card">Service categories are being prepared. Please check back soon or contact the salon for available appointments.</div>
                    @endforelse
                </div>
            </div>
        </section>

        <section id="services" class="section services">
            <div class="section-inner">
                <div class="section-head">
                    <div>
                        <span class="eyebrow"><i class="fas fa-gem"></i> Popular Picks</span>
                        <h2>Affordable favorites to begin with.</h2>
                    </div>
                    <a href="{{ route('login') }}" class="btn-main"><i class="fas fa-calendar-check"></i> View all services</a>
                </div>

                <div class="popular-grid">
                    @forelse ($popularServices as $service)
                        <a class="service-card" href="{{ route('login') }}">
                            <div class="service-img" style="background-image:url('{{ $service->image_url }}');"></div>
                            <div class="service-info">
                                <h3>{{ $service->serviceName }}</h3>
                                <p>{{ $service->category?->category_name }}</p>
                                <div class="card-meta">
                                    <span class="price">PHP {{ number_format((float) $service->servicePrice) }}</span>
                                    <span class="service-duration"><i class="far fa-clock"></i> {{ $service->duration_label }}</span>
                                </div>
                                @if ($service->is_home_service)
                                    <span class="badge"><i class="fas fa-home"></i> Home Service</span>
                                @endif
                            </div>
                        </a>
                    @empty
                        <div class="empty-card">Featured services are being refreshed. Sign in or visit the salon for the current service menu.</div>
                    @endforelse
                </div>
            </div>
        </section>

        @if ($testimonials->isNotEmpty())
            <section id="reviews" class="section story">
                <div class="section-inner">
                    <div class="section-head">
                        <div>
                            <span class="eyebrow"><i class="fas fa-comments"></i> Client Feedback</span>
                            <h2>What clients say after their appointments.</h2>
                        </div>
                        <p>The latest feedback from completed bookings across hair, makeup, nails, and in-salon care.</p>
                    </div>

                    @if ($feedbackSummary->total > 0 && $feedbackSummary->average !== null)
                        <p class="testimonial-summary">
                            <strong>{{ number_format($feedbackSummary->average, 1) }}</strong>
                            <span class="testimonial-stars" aria-hidden="true">★</span>
                            from {{ $feedbackSummary->total }} {{ \Illuminate\Support\Str::plural('review', $feedbackSummary->total) }}
                        </p>
                    @endif

                    <div class="testimonial-grid">
                        @foreach ($testimonials as $testimonial)
                            <article class="testimonial-card" @if ($loop->iteration > 6) data-extra-review hidden @endif>
                                <div class="testimonial-stars" aria-label="{{ $testimonial->stars }} out of 5 stars">{{ str_repeat('★', $testimonial->stars) }}</div>
                                <p class="testimonial-copy">&ldquo;{{ $testimonial->comment }}&rdquo;</p>
                                <div class="testimonial-meta">
                                    <strong>{{ $testimonial->customerName }}</strong>
                                    <span>{{ $testimonial->serviceName }}{{ $testimonial->staffName !== '' ? ' • ' . $testimonial->staffName : '' }}</span>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    @if ($testimonials->count() > 6)
                        <p style="text-align:center;margin-top:20px">
                            <button type="button" class="btn-ghost" id="moreReviews">Show more reviews</button>
                        </p>
                        <script>
                            document.getElementById('moreReviews').addEventListener('click', function () {
                                document.querySelectorAll('[data-extra-review]').forEach(function (card) { card.hidden = false; });
                                this.parentElement.remove();
                            });
                        </script>
                    @endif
                </div>
            </section>
        @endif

        <section id="gallery" class="section">
            <div class="section-inner">
                <div class="section-head">
                    <div>
                        <span class="eyebrow"><i class="fas fa-images"></i> Gallery</span>
                        <h2>A glimpse of the salon experience.</h2>
                    </div>
                    <p>Soft finishes, polished details, and a setting made for relaxed appointments.</p>
                </div>
                <div class="gallery-grid">
                    @foreach ($gallery as $image)
                        <div class="gallery-item"><img src="{{ $image }}" alt="Salon gallery image" loading="lazy"></div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="section services">
            <div class="section-inner">
                <div class="section-head">
                    <div>
                        <span class="eyebrow"><i class="fas fa-check-circle"></i> Why Clients Return</span>
                        <h2>Practical comforts that make booking easier.</h2>
                    </div>
                </div>
                <div class="promise-grid">
                    <div class="promise-item">
                        <div class="promise-icon"><i class="fas fa-home"></i></div>
                        <h3>Home service available</h3>
                        <p>Selected makeup services can be arranged at your home for added comfort.</p>
                    </div>
                    <div class="promise-item">
                        <div class="promise-icon"><i class="fas fa-clock"></i></div>
                        <h3>Flexible hours</h3>
                        <p>Daily operating hours give you more room to book around your schedule.</p>
                    </div>
                    <div class="promise-item">
                        <div class="promise-icon"><i class="fas fa-user-check"></i></div>
                        <h3>Experienced professionals</h3>
                        <p>Salon staff handle appointments with skill, care, and clear service timing.</p>
                    </div>
                    <div class="promise-item">
                        <div class="promise-icon"><i class="fas fa-tag"></i></div>
                        <h3>Accessible pricing</h3>
                        <p>Choose from a wide service menu with transparent starting prices.</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="location" class="section location">
            <div class="section-inner">
                <div class="section-head">
                    <div>
                        <span class="eyebrow"><i class="fas fa-location-dot"></i> Visit Us</span>
                        <h2>Find us in Baguio City.</h2>
                    </div>
                </div>

                <div class="location-grid">
                    <div class="location-details">
                        <p><i class="fas fa-map-marker-alt"></i> <span>Abanao-Zandueta-Kayong-Chugum-Otek, Baguio City, Philippines 2600</span></p>
                        <p><i class="fas fa-phone-alt"></i> <span>+63 912 345 6789</span></p>
                        <p><i class="far fa-clock"></i> <span>Mon - Sun: 7AM - 10PM</span></p>
                        <a href="{{ $loggedIn ? $homeUrl : route('login') }}" class="btn-main"><i class="fas fa-calendar-check"></i> Book an appointment</a>
                    </div>
                    <div class="map-container">
                        <iframe title="Janet's Beauty Salon location map" src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d956.8049201734657!2d120.59329696962541!3d16.413665699019823!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3391a144a8b992fb%3A0xdbd76993e1f91201!2sHabanyero%20Street%20Burger!5e0!3m2!1sen!2sph!4v1777904987152!5m2!1sen!2sph" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                    <a href="https://www.google.com/maps/place/Habanyero+Street+Burger/@16.4136657,120.593297,19z/data=!4m6!3m5!1s0x3391a144a8b992fb:0xdbd76993e1f91201!8m2!3d16.4136657!4d120.5939407!16s%2Fg%2F11w81qdsqw?entry=ttu&g_ep=EgoyMDI2MDQyOS4wIKXMDSoASAFQAw%3D%3D"
                       target="_blank" rel="noopener" class="btn-ghost map-link">
                        <i class="fas fa-map"></i> Open in Google Maps
                    </a>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="footer-inner">
            <span>Copyright {{ date('Y') }} Janet's Beauty Salon</span>
            <span>Baguio's premium beauty destination</span>
        </div>
    </footer>
</div>
</body>
</html>
