<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>EENNRA Group of Guest Houses & Banquets</title>
    @include('partials.favicon')

    <link rel="stylesheet" href="/landing/css/bootstrap.min.css">

    <link rel="stylesheet" href="/landing/css/all.min.css">

    <link rel="stylesheet" href="/landing/css/owl.carousel.min.css">

    <link rel="stylesheet" href="/landing/css/owl.theme.default.min.css">

    <link rel="stylesheet" href="/landing/css/menu.css">

    <link rel="stylesheet" href="/landing/css/style.css">

    <link rel="stylesheet" href="/landing/css/responsive.css">

</head>

<body>

    @include('partials.site-header')

    <!-- hero banner -->
    <section class="hero-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="hero-banner">

                        <div class="hero-slider owl-carousel" id="heroSlider">

                            <div class="hero-slide" style="background-image: url({{ asset('landing/images/slider1.jpg') }});">
                                <div class="container">
                                    <div class="hero-slide__content">
                                        <h1>Beauty and Comfort in One Place</h1>
                                        <p>Every corner of our guest houses offer stunning views and amenities that make you feel right at home.</p>
                                        <a href="#" class="btn-get-started"><i class="fa-solid fa-arrow-up-right-from-square"></i> Get Started</a>
                                    </div>
                                </div>
                            </div>

                            <div class="hero-slide" style="background-image: url({{ asset('landing/images/slider1.jpg') }});">
                                <div class="container">
                                    <div class="hero-slide__content">
                                        <h1>A Home Away From Home</h1>
                                        <p>Comfortable rooms, warm hospitality and everything you need for a relaxing stay.</p>
                                        <a href="#" class="btn-get-started"><i class="fa-solid fa-arrow-up-right-from-square"></i> Get Started</a>
                                    </div>
                                </div>
                            </div>

                            <div class="hero-slide" style="background-image: url({{ asset('landing/images/slider1.jpg') }});">
                                <div class="container">
                                    <div class="hero-slide__content">
                                        <h1>Celebrate Every Occasion</h1>
                                        <p>Elegant banquet halls designed to make your events memorable and hassle-free.</p>
                                        <a href="#" class="btn-get-started"><i class="fa-solid fa-arrow-up-right-from-square"></i> Get Started</a>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="hero-booking">

                            <div class="hero-booking__top">

                                <div class="hero-booking__tabs">
                                    <button type="button" class="tab-btn active" data-tab="guest-house">Guest House</button>
                                    <button type="button" class="tab-btn" data-tab="banquets">Banquets</button>
                                </div>

                                <div class="hero-booking__card">

                                    <div class="hero-booking__panel active" data-panel="guest-house">

                                        <div class="hotel-preview owl-carousel" id="hotelPreviewSliderGuestHouse">

                                            @forelse ($guestHouseFeaturedProperties as $property)
                                                @php
                                                    $previewImage = $property->images->first();
                                                    $previewImageUrl = $previewImage ? asset('storage/'.$previewImage->path) : asset('landing/images/formslider1.jpg');
                                                    $previewLocation = collect([$property->location, $property->city])->filter()->unique()->join(', ');
                                                @endphp
                                                <div class="hotel-preview__item">
                                                    <div class="hotel-preview__info">
                                                        <div class="hotel-preview__badge">
                                                            <i class="fa-solid fa-circle-check"></i>
                                                        </div>
                                                        <h4>{{ $property->name }}</h4>
                                                        <p class="hotel-preview__location">{{ $previewLocation ?: $property->city }}</p>
                                                        <p class="hotel-preview__address">
                                                            <i class="fa-solid fa-location-dot"></i>
                                                            <span>{{ $property->address }}</span>
                                                        </p>
                                                    </div>
                                                    <div class="hotel-preview__thumb" style="background-image: url({{ $previewImageUrl }});"></div>
                                                </div>
                                            @empty
                                                <div class="hotel-preview__item">
                                                    <div class="hotel-preview__info">
                                                        <div class="hotel-preview__badge">
                                                            <i class="fa-solid fa-circle-check"></i>
                                                        </div>
                                                        <h4>Innra Hotel &amp; Banquet</h4>
                                                        <p class="hotel-preview__location">Golpark, Kolkata</p>
                                                        <p class="hotel-preview__address">
                                                            <i class="fa-solid fa-location-dot"></i>
                                                            <span>82C, Meghnad Saha Sarani, Southern Avenue, Golpark, near HDFC Bank, Opposite Ramkrishna Mission, Kolkata &ndash; 700029</span>
                                                        </p>
                                                    </div>
                                                    <div class="hotel-preview__thumb" style="background-image: url({{ asset('landing/images/formslider1.jpg') }});"></div>
                                                </div>
                                            @endforelse

                                        </div>

                                        <form id="heroGuestHouseForm" class="hero-booking__form" method="GET" action="{{ route('book.search') }}">

                                            <div class="form-group form-group--full">
                                                <label>Location</label>
                                                <div class="form-control-wrap">
                                                    <select name="property_id" required>
                                                        <option value="">Select Guest House</option>
                                                        @forelse ($guestHouseProperties as $property)
                                                            <option value="{{ $property->id }}">
                                                                {{ $property->locationDropdownLabel() }}
                                                            </option>
                                                        @empty
                                                            <option disabled>No guest houses available</option>
                                                        @endforelse
                                                    </select>
                                                    <i class="fa-solid fa-chevron-down"></i>
                                                </div>
                                            </div>

                                            <div class="form-row">
                                                <div class="form-group">
                                                    <label>Check In</label>
                                                    <div class="form-control-wrap">
                                                        <input id="heroCheckIn" type="date" name="check_in" value="{{ now()->toDateString() }}" min="{{ now()->toDateString() }}" required>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Check Out</label>
                                                    <div class="form-control-wrap">
                                                        <input id="heroCheckOut" type="date" name="check_out" value="{{ now()->addDay()->toDateString() }}" min="{{ now()->addDay()->toDateString() }}" required>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-row">
                                                <div class="form-group">
                                                    <label>Adults</label>
                                                    <div class="form-control-wrap">
                                                        <select name="adults">
                                                            @foreach (range(1, 6) as $count)
                                                                <option value="{{ $count }}" @selected($count === 2)>{{ $count }} {{ Str::plural('Adult', $count) }}</option>
                                                            @endforeach
                                                        </select>
                                                        <i class="fa-solid fa-chevron-down"></i>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Children</label>
                                                    <div class="form-control-wrap">
                                                        <select name="children">
                                                            @foreach (range(0, 4) as $count)
                                                                <option value="{{ $count }}">{{ $count }} {{ Str::plural('Child', $count) }}</option>
                                                            @endforeach
                                                        </select>
                                                        <i class="fa-solid fa-chevron-down"></i>
                                                    </div>
                                                </div>
                                            </div>

                                        </form>

                                    </div>

                                    <div class="hero-booking__panel" data-panel="banquets">

                                        @php
                                            $firstBanquet = $banquetHalls->first();
                                            $bqImage = $firstBanquet?->images->first();
                                            $bqImageUrl = $bqImage ? asset('storage/'.$bqImage->path) : asset('landing/images/formslider3.jpg');
                                            $bqLocation = $firstBanquet ? collect([$firstBanquet->property?->location, $firstBanquet->property?->city])->filter()->unique()->join(', ') : '';
                                        @endphp
                                        <div class="hotel-preview">
                                            <div class="hotel-preview__item">
                                                <div class="hotel-preview__info">
                                                    <div class="hotel-preview__badge">
                                                        <i class="fa-solid fa-circle-check"></i>
                                                    </div>
                                                    <h4 id="banquetPreviewName">{{ $firstBanquet?->name ?? 'Select a banquet hall' }}</h4>
                                                    <p class="hotel-preview__location" id="banquetPreviewLocation">{{ $bqLocation ?: '—' }}</p>
                                                    <p class="hotel-preview__address">
                                                        <i class="fa-solid fa-location-dot"></i>
                                                        <span id="banquetPreviewAddress">{{ $firstBanquet?->property?->address ?: 'Choose a hall to see details' }}</span>
                                                    </p>
                                                </div>
                                                <div class="hotel-preview__thumb" id="banquetPreviewThumb" style="background-image: url('{{ $bqImageUrl }}');"></div>
                                            </div>
                                        </div>

                                        <form id="heroBanquetForm" class="hero-booking__form">

                                            <div class="form-group form-group--full">
                                                <label>Location</label>
                                                <div class="form-control-wrap">
                                                    <select name="banquet" required>
                                                        <option value="">Select Banquet Hall</option>
                                                        @forelse ($banquetHalls as $banquet)
                                                            @php($bImg = $banquet->images->first())
                                                            <option value="{{ $banquet->id }}"
                                                                data-name="{{ $banquet->name }}"
                                                                data-location="{{ collect([$banquet->property?->location, $banquet->property?->city])->filter()->unique()->join(', ') }}"
                                                                data-address="{{ $banquet->property?->address }}"
                                                                data-image="{{ $bImg ? asset('storage/'.$bImg->path) : asset('landing/images/formslider3.jpg') }}"
                                                                @selected($loop->first)>
                                                                {{ collect([$banquet->property?->name, $banquet->property?->location, $banquet->property?->city])->filter()->unique()->join(' - ') }}
                                                            </option>
                                                        @empty
                                                            <option value="" disabled>No banquet halls available</option>
                                                        @endforelse
                                                    </select>
                                                    <i class="fa-solid fa-chevron-down"></i>
                                                </div>
                                            </div>

                                            <div class="form-row">
                                                <div class="form-group">
                                                    <label>Event Date</label>
                                                    <div class="form-control-wrap">
                                                        <input type="date" name="event_date" min="{{ now()->toDateString() }}" required aria-label="Event date">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Event Time</label>
                                                    <div class="form-control-wrap">
                                                        <input type="time" name="event_time" required aria-label="Event time">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-row">
                                                <div class="form-group">
                                                    <label>Guests</label>
                                                    <div class="form-control-wrap">
                                                        <input
                                                            type="number"
                                                            name="guest_count"
                                                            min="1"
                                                            max="10000"
                                                            step="1"
                                                            inputmode="numeric"
                                                            placeholder="Enter number of guests"
                                                            aria-label="Number of guests"
                                                            required
                                                        >
                                                        <i class="fa-solid fa-users"></i>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Event Type</label>
                                                    <div class="form-control-wrap">
                                                        <select name="event_type" aria-label="Event type" required>
                                                            <option value="" selected disabled>Select Event Type</option>
                                                            <option value="marriage">Marriage</option>
                                                            <option value="anniversary">Anniversary</option>
                                                            <option value="birthday">Birthday</option>
                                                            <option value="corporate_party">Corporate Party</option>
                                                            <option value="meeting">Meeting</option>
                                                            <option value="others">Others</option>
                                                        </select>
                                                        <i class="fa-solid fa-chevron-down"></i>
                                                    </div>
                                                </div>
                                            </div>

                                        </form>

                                    </div>

                                </div>

                            </div>

                            <a href="#" id="heroBookingSubmit" class="btn-book-now hero-booking__submit">Check Availability &amp; Book</a>

                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- features section -->
    <section class="features-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
            <div class="features-box">

                <div class="feature-item">
                    <div class="feature-item__icon">
                        <img src="{{ asset('landing/images/building-04.svg') }}" alt="Luxury Accommodations">
                    </div>
                    <div class="feature-item__content">
                        <h4>Luxury Accommodations</h4>
                        <p>Enjoy the comfort of our elegantly designed rooms.</p>
                    </div>
                </div>

                <div class="feature-item">
                    <div class="feature-item__icon">
                        <img src="{{ asset('landing/images/car-01.svg') }}" alt="Private Transportation">
                    </div>
                    <div class="feature-item__content">
                        <h4>Private Transportation</h4>
                        <p>Private transportation services to make travel easier.</p>
                    </div>
                </div>

                <div class="feature-item">
                    <div class="feature-item__icon">
                        <i class="fa-solid fa-earth-americas"></i>
                    </div>
                    <div class="feature-item__content">
                        <h4>Cultural Experiences</h4>
                        <p>Take a tour of the local culture and activities we offer.</p>
                    </div>
                </div>

            </div>
            </div>
            </div>
        </div>
    </section>

    <!-- facilities section -->
    <section class="facilities-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
            <div class="facilities-box">
                <div class="facilities-grid">

                    <div class="facilities-main">

                        <div class="facilities-header">
                            <div class="facilities-header__text">
                                <h2>Best Facilities in Class</h2>
                                <p>Enjoy a refreshing stay with modern facilities and friendly service. We are committed to providing an extraordinary stay.</p>
                            </div>
                            <div class="facilities-header__nav">
                                <span class="facilities-counter"><span id="facilitiesCurrent">01</span> / <span id="facilitiesTotal">03</span></span>
                                <button type="button" class="facilities-nav-btn facilities-prev" aria-label="Previous slide">
                                    <i class="fa-solid fa-arrow-left"></i>
                                </button>
                                <button type="button" class="facilities-nav-btn facilities-next" aria-label="Next slide">
                                    <i class="fa-solid fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>

                        <div class="facilities-slider owl-carousel" id="facilitiesSlider">
                            <div class="facilities-slide" style="background-image: url({{ asset('landing/images/formslider1.jpg') }});"></div>
                            <div class="facilities-slide" style="background-image: url({{ asset('landing/images/formslider2.jpg') }});"></div>
                            <div class="facilities-slide" style="background-image: url({{ asset('landing/images/formslider3.jpg') }});"></div>
                        </div>

                    </div>

                    <div class="facilities-side">
                        <p>Our hotels offer unrivaled luxury and service with world-class facilities and personalized service.</p>
                        <p class="facilities-side__stat">570K Verified Guest</p>
                        <p>Every aspect of our hotel is designed to provide tranquility and bliss. Enjoy every moment in a place designed to pamper you.</p>
                        <div class="facilities-side__meta">
                            <span class="facilities-rating">4.8 <i class="fa-solid fa-star"></i></span>
                            <a href="#" class="facilities-link">Guest Testimonials</a>
                        </div>
                    </div>

                </div>
            </div>
            </div>
            </div>
        </div>
    </section>

    <!-- projects section -->
    <section class="projects-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">

            <div class="section-heading">
                <h2>The Exclusive Gathering<br>You&rsquo;ve Been Dreaming Of</h2>
                <p>Surrounded by stunning views and luxurious amenities, this hidden paradise awaits you.</p>
            </div>

            <div class="projects-grid">

                <div class="project-card">
                    <div class="project-card__image" style="background-image: url({{ asset('landing/images/formslider1.jpg') }});"></div>
                    <div class="project-card__body">
                        <h4>Eennra Oiitijhyya</h4>
                        <p class="project-card__location">Keyatala, Kolkata</p>
                        <div class="project-card__rating">
                            <span class="stars"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></span>
                            <span class="reviews">(711 Reviews)</span>
                        </div>
                        <div class="project-card__cta">
                            <a href="{{ route('book.search') }}" class="btn-book-now">Book Now</a>
                            <a href="#" class="project-card__link" aria-label="View details"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                        </div>
                    </div>
                </div>

                <div class="project-card">
                    <div class="project-card__image" style="background-image: url({{ asset('landing/images/formslider2.jpg') }});"></div>
                    <div class="project-card__body">
                        <h4>Basera</h4>
                        <p class="project-card__location">Golpark, Kolkata</p>
                        <div class="project-card__rating">
                            <span class="stars"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></span>
                            <span class="reviews">(711 Reviews)</span>
                        </div>
                        <div class="project-card__cta">
                            <a href="{{ route('book.search') }}" class="btn-book-now">Book Now</a>
                            <a href="#" class="project-card__link" aria-label="View details"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                        </div>
                    </div>
                </div>

                <div class="project-card">
                    <div class="project-card__image" style="background-image: url({{ asset('landing/images/formslider3.jpg') }});"></div>
                    <div class="project-card__body">
                        <h4>Innra Guest House</h4>
                        <p class="project-card__location">Sarat Bose Road, Kolkata</p>
                        <div class="project-card__rating">
                            <span class="stars"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></span>
                            <span class="reviews">(711 Reviews)</span>
                        </div>
                        <div class="project-card__cta">
                            <a href="{{ route('book.search') }}" class="btn-book-now">Book Now</a>
                            <a href="#" class="project-card__link" aria-label="View details"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                        </div>
                    </div>
                </div>

                <div class="project-card">
                    <div class="project-card__image" style="background-image: url({{ asset('landing/images/formslider1.jpg') }});"></div>
                    <div class="project-card__body">
                        <h4>Innra Hotel &amp; Banquet</h4>
                        <p class="project-card__location">Golpark, Kolkata</p>
                        <div class="project-card__rating">
                            <span class="stars"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></span>
                            <span class="reviews">(711 Reviews)</span>
                        </div>
                        <div class="project-card__cta">
                            <a href="{{ route('book.search') }}" class="btn-book-now">Book Now</a>
                            <a href="#" class="project-card__link" aria-label="View details"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                        </div>
                    </div>
                </div>

                <div class="project-card">
                    <div class="project-card__image" style="background-image: url({{ asset('landing/images/formslider2.jpg') }});"></div>
                    <div class="project-card__body">
                        <h4>Eennra Grand Residency</h4>
                        <p class="project-card__location">Salt Lake, Kolkata</p>
                        <div class="project-card__rating">
                            <span class="stars"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></span>
                            <span class="reviews">(711 Reviews)</span>
                        </div>
                        <div class="project-card__cta">
                            <a href="{{ route('book.search') }}" class="btn-book-now">Book Now</a>
                            <a href="#" class="project-card__link" aria-label="View details"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                        </div>
                    </div>
                </div>

                <div class="project-card">
                    <div class="project-card__image" style="background-image: url({{ asset('landing/images/formslider3.jpg') }});"></div>
                    <div class="project-card__body">
                        <h4>Eennra Grand Banquet Hall</h4>
                        <p class="project-card__location">Golpark, Kolkata</p>
                        <div class="project-card__rating">
                            <span class="stars"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></span>
                            <span class="reviews">(711 Reviews)</span>
                        </div>
                        <div class="project-card__cta">
                            <a href="{{ route('book.search') }}" class="btn-book-now">Book Now</a>
                            <a href="#" class="project-card__link" aria-label="View details"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                        </div>
                    </div>
                </div>

                <div class="project-card project-card--extra">
                    <div class="project-card__image" style="background-image: url({{ asset('landing/images/formslider1.jpg') }});"></div>
                    <div class="project-card__body">
                        <h4>Eennra Celebration Hall</h4>
                        <p class="project-card__location">Salt Lake, Kolkata</p>
                        <div class="project-card__rating">
                            <span class="stars"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></span>
                            <span class="reviews">(711 Reviews)</span>
                        </div>
                        <div class="project-card__cta">
                            <a href="{{ route('book.search') }}" class="btn-book-now">Book Now</a>
                            <a href="#" class="project-card__link" aria-label="View details"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                        </div>
                    </div>
                </div>

                <div class="project-card project-card--extra">
                    <div class="project-card__image" style="background-image: url({{ asset('landing/images/formslider2.jpg') }});"></div>
                    <div class="project-card__body">
                        <h4>Eennra Riverside Retreat</h4>
                        <p class="project-card__location">Howrah, Kolkata</p>
                        <div class="project-card__rating">
                            <span class="stars"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></span>
                            <span class="reviews">(711 Reviews)</span>
                        </div>
                        <div class="project-card__cta">
                            <a href="{{ route('book.search') }}" class="btn-book-now">Book Now</a>
                            <a href="#" class="project-card__link" aria-label="View details"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                        </div>
                    </div>
                </div>

                <div class="project-card project-card--extra">
                    <div class="project-card__image" style="background-image: url({{ asset('landing/images/formslider3.jpg') }});"></div>
                    <div class="project-card__body">
                        <h4>Eennra Heritage Manor</h4>
                        <p class="project-card__location">Ballygunge, Kolkata</p>
                        <div class="project-card__rating">
                            <span class="stars"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></span>
                            <span class="reviews">(711 Reviews)</span>
                        </div>
                        <div class="project-card__cta">
                            <a href="{{ route('book.search') }}" class="btn-book-now">Book Now</a>
                            <a href="#" class="project-card__link" aria-label="View details"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                        </div>
                    </div>
                </div>

                <div class="project-card project-card--extra">
                    <div class="project-card__image" style="background-image: url({{ asset('landing/images/formslider1.jpg') }});"></div>
                    <div class="project-card__body">
                        <h4>Eennra Lakeview Residency</h4>
                        <p class="project-card__location">Rajarhat, Kolkata</p>
                        <div class="project-card__rating">
                            <span class="stars"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></span>
                            <span class="reviews">(711 Reviews)</span>
                        </div>
                        <div class="project-card__cta">
                            <a href="{{ route('book.search') }}" class="btn-book-now">Book Now</a>
                            <a href="#" class="project-card__link" aria-label="View details"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                        </div>
                    </div>
                </div>

                <div class="project-card project-card--extra">
                    <div class="project-card__image" style="background-image: url({{ asset('landing/images/formslider2.jpg') }});"></div>
                    <div class="project-card__body">
                        <h4>Innra Business Suites</h4>
                        <p class="project-card__location">Park Street, Kolkata</p>
                        <div class="project-card__rating">
                            <span class="stars"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></span>
                            <span class="reviews">(711 Reviews)</span>
                        </div>
                        <div class="project-card__cta">
                            <a href="{{ route('book.search') }}" class="btn-book-now">Book Now</a>
                            <a href="#" class="project-card__link" aria-label="View details"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                        </div>
                    </div>
                </div>

                <div class="project-card project-card--extra">
                    <div class="project-card__image" style="background-image: url({{ asset('landing/images/formslider3.jpg') }});"></div>
                    <div class="project-card__body">
                        <h4>Eennra Garden Villa</h4>
                        <p class="project-card__location">Behala, Kolkata</p>
                        <div class="project-card__rating">
                            <span class="stars"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></span>
                            <span class="reviews">(711 Reviews)</span>
                        </div>
                        <div class="project-card__cta">
                            <a href="{{ route('book.search') }}" class="btn-book-now">Book Now</a>
                            <a href="#" class="project-card__link" aria-label="View details"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                        </div>
                    </div>
                </div>

            </div>

            <div class="projects-load-more">
                <button type="button" class="btn-load-more" id="loadMoreBtn">Load More</button>
            </div>
            </div>
            </div>
        </div>
    </section>

    <!-- why us section -->
    <section class="why-us-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
            <div class="why-us-box">
                <div class="why-us-grid">

                    <div class="why-us-media">
                        <div class="why-us-slider owl-carousel" id="whyUsSlider">
                            <div class="why-us-slide">
                                <img src="{{ asset('landing/images/formslider1.jpg') }}" alt="Why choose us">
                            </div>
                            <div class="why-us-slide">
                                <img src="{{ asset('landing/images/formslider2.jpg') }}" alt="Why choose us">
                            </div>
                            <div class="why-us-slide">
                                <img src="{{ asset('landing/images/formslider3.jpg') }}" alt="Why choose us">
                            </div>
                        </div>
                    </div>

                    <div class="why-us-content">
                        <h2>Why People Choose Us?</h2>
                        <p>Our venue offers a truly exclusive experience, with luxurious facilities and personalized service. Enjoy comfort and luxury designed especially for you.</p>

                        <div class="why-us-stats">
                            <div class="why-us-stat">
                                <h3><span class="counter" data-target="4.9" data-decimals="1">0</span></h3>
                                <p>Guest Ratings</p>
                            </div>
                            <div class="why-us-stat">
                                <h3><span class="counter" data-target="150">0</span>+</h3>
                                <p>Total Rooms</p>
                            </div>
                            <div class="why-us-stat">
                                <h3><span class="counter" data-target="10">0</span>+</h3>
                                <p>Banquets</p>
                            </div>
                        </div>

                        <a href="#" class="btn-get-in-touch"><i class="fa-solid fa-arrow-up-right-from-square"></i> Get in Touch</a>
                    </div>

                </div>
            </div>
            </div>
        </div>
    </section>

    <!-- testimonials section -->
    <section class="testimonials-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">

            <div class="section-heading">
                <h2>Testimonials</h2>
                <p>Every corner of our hotel offers stunning views and amenities that make you feel right at home.</p>
            </div>

            <div class="testimonials-grid">

                <div class="testimonial-card">
                    <div class="testimonial-card__content">
                        <i class="fa-solid fa-quote-left testimonial-card__quote-icon"></i>
                        <p class="testimonial-card__text">A great stay, the staff were very friendly and helpful! The executive suite was very spacious and comfortable.</p>
                        <div class="testimonial-card__author">
                            <span class="testimonial-card__avatar"></span>
                            <div>
                                <h5>Guest&rsquo;s Name</h5>
                                <p>Kolkata</p>
                            </div>
                        </div>
                    </div>
                    <div class="testimonial-card__media" style="background-image: url({{ asset('landing/images/formslider1.jpg') }});">
                        <div class="testimonial-card__media-overlay">
                            <h6>Anjali Bati</h6>
                            <div class="testimonial-card__rating">
                                <span class="stars"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></span>
                                <span class="reviews">(450 Reviews)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card">
                    <div class="testimonial-card__content">
                        <i class="fa-solid fa-quote-left testimonial-card__quote-icon"></i>
                        <p class="testimonial-card__text">A great stay, the staff were very friendly and helpful! The executive suite was very spacious and comfortable.</p>
                        <div class="testimonial-card__author">
                            <span class="testimonial-card__avatar"></span>
                            <div>
                                <h5>Guest&rsquo;s Name</h5>
                                <p>Kolkata</p>
                            </div>
                        </div>
                    </div>
                    <div class="testimonial-card__media" style="background-image: url({{ asset('landing/images/formslider2.jpg') }});">
                        <div class="testimonial-card__media-overlay">
                            <h6>Basera</h6>
                            <div class="testimonial-card__rating">
                                <span class="stars"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></span>
                                <span class="reviews">(450 Reviews)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card">
                    <div class="testimonial-card__content">
                        <i class="fa-solid fa-quote-left testimonial-card__quote-icon"></i>
                        <p class="testimonial-card__text">A great stay, the staff were very friendly and helpful! The executive suite was very spacious and comfortable.</p>
                        <div class="testimonial-card__author">
                            <span class="testimonial-card__avatar"></span>
                            <div>
                                <h5>Guest&rsquo;s Name</h5>
                                <p>Kolkata</p>
                            </div>
                        </div>
                    </div>
                    <div class="testimonial-card__media" style="background-image: url({{ asset('landing/images/formslider3.jpg') }});">
                        <div class="testimonial-card__media-overlay">
                            <h6>Innra Guest House</h6>
                            <div class="testimonial-card__rating">
                                <span class="stars"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></span>
                                <span class="reviews">(450 Reviews)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card">
                    <div class="testimonial-card__content">
                        <i class="fa-solid fa-quote-left testimonial-card__quote-icon"></i>
                        <p class="testimonial-card__text">A great stay, the staff were very friendly and helpful! The executive suite was very spacious and comfortable.</p>
                        <div class="testimonial-card__author">
                            <span class="testimonial-card__avatar"></span>
                            <div>
                                <h5>Guest&rsquo;s Name</h5>
                                <p>Kolkata</p>
                            </div>
                        </div>
                    </div>
                    <div class="testimonial-card__media" style="background-image: url({{ asset('landing/images/formslider1.jpg') }});">
                        <div class="testimonial-card__media-overlay">
                            <h6>Innra Hotel &amp; Banquet</h6>
                            <div class="testimonial-card__rating">
                                <span class="stars"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></span>
                                <span class="reviews">(450 Reviews)</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            </div>
            </div>
        </div>
    </section>

    <!-- contact section -->
    <section class="contact-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
            <div class="contact-box">
                <div class="contact-grid">

                    <div class="contact-info">
                        <h2>Contact Us</h2>
                        <p>Contact now and find comfort and the best service that will make your holiday an unforgettable moment.</p>

                        <div class="contact-info__card">
                            <div class="contact-info__row">
                                <i class="fa-solid fa-phone"></i>
                                <div>
                                    <span>Call Us</span>
                                    <p>+91 98300 983XX</p>
                                </div>
                            </div>
                            <div class="contact-info__divider"></div>
                            <div class="contact-info__row">
                                <i class="fa-solid fa-envelope"></i>
                                <div>
                                    <span>Mail Us</span>
                                    <p>book@eennra.com</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form class="contact-form">

                        <div class="contact-form__row">
                            <div class="contact-form__group">
                                <label>Name</label>
                                <input type="text" placeholder="Your Name">
                            </div>
                            <div class="contact-form__group">
                                <label>Mobile</label>
                                <input type="text" placeholder="Our Mobile No">
                            </div>
                        </div>

                        <div class="contact-form__row">
                            <div class="contact-form__group">
                                <label>Email</label>
                                <input type="email" placeholder="Your Email ID">
                            </div>
                            <div class="contact-form__group">
                                <label>Location</label>
                                <input type="text" placeholder="Your Location">
                            </div>
                        </div>

                        <div class="contact-form__group contact-form__group--full">
                            <label>Message</label>
                            <textarea rows="4" placeholder="Your Message"></textarea>
                        </div>

                        <button type="submit" class="btn-get-in-touch contact-form__submit">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i> Submit
                        </button>

                    </form>

                </div>
            </div>
            </div>
            </div>
        </div>
    </section>

    @include('partials.site-footer')

    <!-- js link -->
    <script src="{{ asset('landing/js/jquery-3.3.1.min.js') }}"></script>
    <script src="{{ asset('landing/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('landing/js/owl.carousel.min.js') }}"></script>
    <!-- <script src="{{ asset('landing/js/aos.js') }}"></script> -->
    <script src="{{ asset('landing/js/header.js') }}"></script>
    <script src="{{ asset('landing/js/main.js') }}"></script>
    <script>
        (function () {
            const form = document.getElementById('heroGuestHouseForm');
            const checkIn = document.getElementById('heroCheckIn');
            const checkOut = document.getElementById('heroCheckOut');

            // Keep check-out at least one night after check-in.
            checkIn?.addEventListener('change', function () {
                if (!this.value) return;
                const next = new Date(this.value); next.setDate(next.getDate() + 1);
                const min = next.toISOString().slice(0, 10);
                checkOut.min = min;
                if (!checkOut.value || checkOut.value <= this.value) checkOut.value = min;
            });

            // Keep the banquet header card in sync with the selected hall.
            (function () {
                const sel = document.querySelector('#heroBanquetForm [name="banquet"]');
                if (!sel) return;
                const nameEl = document.getElementById('banquetPreviewName');
                const locEl = document.getElementById('banquetPreviewLocation');
                const addrEl = document.getElementById('banquetPreviewAddress');
                const thumbEl = document.getElementById('banquetPreviewThumb');
                function sync() {
                    const opt = sel.options[sel.selectedIndex];
                    if (!opt || !opt.value) return;
                    if (nameEl) nameEl.textContent = opt.dataset.name || '';
                    if (locEl) locEl.textContent = opt.dataset.location || '—';
                    if (addrEl) addrEl.textContent = opt.dataset.address || '';
                    if (thumbEl && opt.dataset.image) thumbEl.style.backgroundImage = `url('${opt.dataset.image}')`;
                }
                sel.addEventListener('change', sync);
                sync();
            })();

            document.getElementById('heroBookingSubmit')?.addEventListener('click', function (event) {
                event.preventDefault();
                const activePanel = document.querySelector('.hero-booking__panel.active')?.dataset.panel;

                if (activePanel === 'banquets') {
                    const bform = document.getElementById('heroBanquetForm');
                    if (!bform || !bform.reportValidity()) return;
                    const id = bform.querySelector('[name="banquet"]').value;
                    if (!id) return;
                    const params = new URLSearchParams();
                    ['guest_count', 'event_type', 'event_date', 'event_time'].forEach((n) => {
                        const v = bform.querySelector(`[name="${n}"]`)?.value;
                        if (v) params.set(n, v);
                    });
                    window.location = '/banquets/' + encodeURIComponent(id) + (params.toString() ? '?' + params.toString() : '');
                    return;
                }

                if (form && form.reportValidity()) form.submit();
            });
        })();
    </script>

</body>
</html>
