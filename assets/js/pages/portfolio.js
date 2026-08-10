(function() {
    'use strict';

    /* ================================================================
       FAQ Accordion
       ================================================================ */
    var faqTriggers = document.querySelectorAll('.js-faq-trigger');
    faqTriggers.forEach(function(trigger) {
        trigger.addEventListener('click', function() {
            var expanded = trigger.getAttribute('aria-expanded') === 'true';
            trigger.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            var panel = document.getElementById(trigger.getAttribute('aria-controls'));
            if (panel) panel.hidden = expanded;
        });
    });

    /* ================================================================
       Gallery Lightbox
       ================================================================ */
    var galleryImages = document.querySelectorAll('.pf-gallery__image');
    if (galleryImages.length) {
        var lightbox = document.createElement('div');
        lightbox.className = 'pf-lightbox';
        lightbox.setAttribute('role', 'dialog');
        lightbox.setAttribute('aria-modal', 'true');
        lightbox.hidden = true;
        lightbox.innerHTML = '<div class="pf-lightbox__backdrop"></div><div class="pf-lightbox__content"><button class="pf-lightbox__close" aria-label="Close">&times;</button><img src="" alt="" class="pf-lightbox__image"><p class="pf-lightbox__caption"></p><button class="pf-lightbox__prev" aria-label="Previous">&lsaquo;</button><button class="pf-lightbox__next" aria-label="Next">&rsaquo;</button></div>';
        document.body.appendChild(lightbox);

        var currentIdx = 0;
        var imgEl = lightbox.querySelector('.pf-lightbox__image');
        var captionEl = lightbox.querySelector('.pf-lightbox__caption');
        var closeBtn = lightbox.querySelector('.pf-lightbox__close');
        var prevBtn = lightbox.querySelector('.pf-lightbox__prev');
        var nextBtn = lightbox.querySelector('.pf-lightbox__next');
        var backdrop = lightbox.querySelector('.pf-lightbox__backdrop');

        function open(idx) {
            currentIdx = idx;
            var src = galleryImages[idx];
            imgEl.src = src.src;
            imgEl.alt = src.alt;
            var caption = src.closest('.pf-gallery__item');
            captionEl.textContent = caption ? (caption.querySelector('.pf-gallery__caption')?.textContent || '') : '';
            lightbox.hidden = false;
            document.body.style.overflow = 'hidden';
            prevBtn.style.display = galleryImages.length > 1 ? '' : 'none';
            nextBtn.style.display = galleryImages.length > 1 ? '' : 'none';
        }

        function close() { lightbox.hidden = true; document.body.style.overflow = ''; }

        function navigate(dir) {
            currentIdx = (currentIdx + dir + galleryImages.length) % galleryImages.length;
            open(currentIdx);
        }

        galleryImages.forEach(function(img, i) {
            img.style.cursor = 'pointer';
            img.addEventListener('click', function() { open(i); });
        });

        closeBtn.addEventListener('click', close);
        backdrop.addEventListener('click', close);
        prevBtn.addEventListener('click', function() { navigate(-1); });
        nextBtn.addEventListener('click', function() { navigate(1); });

        document.addEventListener('keydown', function(e) {
            if (lightbox.hidden) return;
            if (e.key === 'Escape') close();
            if (e.key === 'ArrowLeft') navigate(-1);
            if (e.key === 'ArrowRight') navigate(1);
        });
    }

    /* ================================================================
       Scroll Fade-In Animation
       ================================================================ */
    var fadeEls = document.querySelectorAll('.fade-in');
    if (fadeEls.length && 'IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in--visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

        fadeEls.forEach(function(el) { observer.observe(el); });
    }

    /* ================================================================
       Lightbox Styles (injected inline)
       ================================================================ */
    var lightboxStyle = document.createElement('style');
    lightboxStyle.textContent = '.pf-lightbox{position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center}.pf-lightbox__backdrop{position:absolute;inset:0;background:rgba(0,0,0,.9);cursor:pointer}.pf-lightbox__content{position:relative;max-width:90vw;max-height:90vh;display:flex;flex-direction:column;align-items:center}.pf-lightbox__image{max-width:90vw;max-height:80vh;object-fit:contain;border-radius:4px}.pf-lightbox__caption{color:#fff;margin-top:1rem;font-size:0.875rem;text-align:center}.pf-lightbox__close{position:absolute;top:-2.5rem;right:0;background:none;border:0;color:#fff;font-size:2rem;cursor:pointer;line-height:1}.pf-lightbox__prev,.pf-lightbox__next{position:absolute;top:50%;transform:translateY(-50%);background:rgba(255,255,255,.15);border:0;color:#fff;font-size:2.5rem;width:48px;height:48px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;line-height:1}.pf-lightbox__prev:hover,.pf-lightbox__next:hover{background:rgba(255,255,255,.3)}.pf-lightbox__prev{left:0}.pf-lightbox__next{right:0}@media(max-width:640px){.pf-lightbox__close{top:-2rem;font-size:1.5rem}.pf-lightbox__prev,.pf-lightbox__next{font-size:1.5rem;width:36px;height:36px}}';
    document.head.appendChild(lightboxStyle);
})();
