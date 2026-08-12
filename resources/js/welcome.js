const initializeHomepage = () => {
    document.documentElement.classList.add('reveal-enabled');

    const revealElements = document.querySelectorAll('.reveal');

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                }
            });
        }, { threshold: 0.15, rootMargin: '0px 0px -30px 0px' });

        revealElements.forEach((element) => observer.observe(element));
    } else {
        revealElements.forEach((element) => element.classList.add('active'));
    }

    const navbar = document.getElementById('navbar');

    if (!navbar) {
        return;
    }

    window.addEventListener('scroll', () => {
        if (window.scrollY > 80) {
            navbar.style.background = 'rgba(23,23,23,0.95)';
            navbar.style.backdropFilter = 'blur(12px)';
            navbar.style.boxShadow = '0 4px 30px rgba(0,0,0,0.3)';
        } else {
            navbar.style.background = 'transparent';
            navbar.style.backdropFilter = 'none';
            navbar.style.boxShadow = 'none';
        }
    }, { passive: true });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeHomepage, { once: true });
} else {
    initializeHomepage();
}
