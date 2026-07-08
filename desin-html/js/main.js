

$(window).on('load', function () {

    $('.latest_blog_slider').owlCarousel({

        loop: true,

        center: false,

        margin: 40,

        nav: true,

        dots: false,

        navText: [

            '<i class="fa-solid fa-chevron-left"></i>',

            '<i class="fa-solid fa-chevron-right"></i>'

        ],

        responsive: {

            0: {
                items: 1
            },

            600: {
                items: 3,
                mergeFit: true,
            },

            1000: {
                items: 5,
                mergeFit: false,
            }

        }

    });

    $('#heroSlider').owlCarousel({

        items: 1,

        loop: true,

        nav: false,

        dots: true,

        autoplay: false,

        autoplayTimeout: 6000,

        smartSpeed: 600

    });

    $('#hotelPreviewSliderGuestHouse, #hotelPreviewSliderBanquets').owlCarousel({

        items: 1,

        loop: true,

        nav: false,
        margin: 30,

        dots: true,

        autoplay: false,

        autoplayTimeout: 5000,

        smartSpeed: 500

    });

    var $facilitiesSlider = $('#facilitiesSlider');

    $('#facilitiesTotal').text(String($facilitiesSlider.children().length).padStart(2, '0'));

    $facilitiesSlider.owlCarousel({

        loop: true,

        margin: 24,

        nav: false,

        dots: false,

        responsive: {

            0: {
                items: 1
            },

            576: {
                items: 2
            },

            992: {
                items: 3
            }

        }

    });

    $facilitiesSlider.on('changed.owl.carousel', function (e) {

        var current = e.relatedTarget.relative(e.item.index) + 1;

        $('#facilitiesCurrent').text(String(current).padStart(2, '0'));

    });

    $('.facilities-prev').on('click', function () {

        $facilitiesSlider.trigger('prev.owl.carousel');

    });

    $('.facilities-next').on('click', function () {

        $facilitiesSlider.trigger('next.owl.carousel');

    });

    $('#whyUsSlider').owlCarousel({

        items: 1,

        loop: true,

        nav: false,

        dots: true,

        autoplay: true,

        autoplayTimeout: 4000,

        smartSpeed: 500

    });

    var owlResizeTimer;

    $(window).on('resize', function () {

        clearTimeout(owlResizeTimer);

        owlResizeTimer = setTimeout(function () {
            $('.owl-carousel.owl-loaded').trigger('refresh.owl.carousel');
        }, 200);

    });

});

$('.tab-btn').on('click', function () {

    var target = $(this).data('tab');

    $(this).addClass('active').siblings('.tab-btn').removeClass('active');

    var $panel = $('.hero-booking__panel[data-panel="' + target + '"]');

    $panel.addClass('active').siblings('.hero-booking__panel').removeClass('active');

    $panel.find('.owl-carousel').trigger('refresh.owl.carousel');

});

$('#loadMoreBtn').on('click', function () {

    $('.project-card--extra').removeClass('project-card--extra');

    $(this).parent().remove();

});

var counterObserver = new IntersectionObserver(function (entries) {

    entries.forEach(function (entry) {

        if (!entry.isIntersecting) {
            return;
        }

        var el = entry.target;
        counterObserver.unobserve(el);

        var target = parseFloat(el.getAttribute('data-target'));
        var decimals = parseInt(el.getAttribute('data-decimals') || '0', 10);
        var duration = 1500;
        var startTime = null;

        function step(timestamp) {

            if (!startTime) {
                startTime = timestamp;
            }

            var progress = Math.min((timestamp - startTime) / duration, 1);

            el.textContent = (target * progress).toFixed(decimals);

            if (progress < 1) {
                requestAnimationFrame(step);
            }

        }

        requestAnimationFrame(step);

    });

}, { threshold: 0.4 });

document.querySelectorAll('.counter').forEach(function (el) {
    counterObserver.observe(el);
});
