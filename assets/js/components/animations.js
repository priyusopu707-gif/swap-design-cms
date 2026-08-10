/**
 * Swap Design - Scroll Animations
 * Intersection Observer-based reveal animations.
 */
document.addEventListener('DOMContentLoaded', () => {
    const reveals = $$('[data-reveal]');

    if (!reveals.length) return;

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');
                }
            });
        },
        { threshold: 0.15, rootMargin: '0px 0px -50px 0px' }
    );

    reveals.forEach((el) => observer.observe(el));
});
