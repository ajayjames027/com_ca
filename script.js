// Sticky Navigation Bar on Scroll
window.addEventListener('scroll', () => {
    const nav = document.querySelector('nav');
    if (!nav) return;
    if (window.scrollY > 40) {
        nav.style.background = 'rgba(3, 0, 20, 0.92)';
        nav.style.boxShadow = '0 8px 32px rgba(0, 0, 0, 0.6)';
    } else {
        nav.style.background = 'rgba(3, 0, 20, 0.75)';
        nav.style.boxShadow = 'none';
    }
});

// Smooth anchor scrolling
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const targetId = this.getAttribute('href');
        if (targetId === '#') return;
        const targetElement = document.querySelector(targetId);
        if (targetElement) {
            e.preventDefault();
            targetElement.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});
