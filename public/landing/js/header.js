document.addEventListener('DOMContentLoaded', function () {

    var navToggle = document.getElementById('mobileNavToggle');
    var menuClose = document.getElementById('mobileMenuClose');
    var mobileMenu = document.getElementById('mobileMenu');
    var overlay = document.getElementById('mobileMenuOverlay');

    function openMenu() {
        mobileMenu.classList.add('active');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeMenu() {
        mobileMenu.classList.remove('active');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    navToggle.addEventListener('click', openMenu);
    menuClose.addEventListener('click', closeMenu);
    overlay.addEventListener('click', closeMenu);

    var toggleButtons = mobileMenu.querySelectorAll('.mobile-menu__toggle');

    toggleButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var li = btn.closest('li');
            var subMenu = li.querySelector('.sub-menu');
            var isOpen = btn.classList.contains('active');

            btn.classList.toggle('active', !isOpen);
            subMenu.classList.toggle('is-open', !isOpen);
        });
    });

    var footerYear = document.getElementById('footerYear');

    if (footerYear) {
        footerYear.textContent = new Date().getFullYear();
    }

});
