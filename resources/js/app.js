import './bootstrap';
import './product-gallery';

function enableDragScroll(track) {
    if (track.dataset.dragScrollInit === 'true') {
        return;
    }

    track.dataset.dragScrollInit = 'true';

    track.querySelectorAll('img').forEach((image) => {
        image.setAttribute('draggable', 'false');
    });

    track.addEventListener('dragstart', (event) => {
        event.preventDefault();
    });

    let isDragging = false;
    let didDrag = false;
    let startX = 0;
    let startScrollLeft = 0;

    const onMouseMove = (event) => {
        if (!isDragging) {
            return;
        }

        event.preventDefault();

        const delta = event.clientX - startX;

        if (Math.abs(delta) > 4) {
            didDrag = true;
        }

        track.scrollLeft = startScrollLeft - delta;
    };

    const endDrag = () => {
        if (!isDragging) {
            return;
        }

        isDragging = false;
        track.classList.remove('is-dragging');
        track.style.scrollSnapType = '';
        track.style.scrollBehavior = '';
        document.removeEventListener('mousemove', onMouseMove);
        document.removeEventListener('mouseup', endDrag);
    };

    track.addEventListener('mousedown', (event) => {
        if (event.button !== 0) {
            return;
        }

        isDragging = true;
        didDrag = false;
        startX = event.clientX;
        startScrollLeft = track.scrollLeft;
        track.classList.add('is-dragging');
        track.style.scrollSnapType = 'none';
        track.style.scrollBehavior = 'auto';

        event.preventDefault();

        document.addEventListener('mousemove', onMouseMove);
        document.addEventListener('mouseup', endDrag);
    });

    track.addEventListener('click', (event) => {
        if (!didDrag) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
        didDrag = false;
    }, true);
}

function initMobileMenu() {
    const menu = document.getElementById('mobile-menu');
    const panel = document.getElementById('mobile-menu-panel');
    const backdrop = document.getElementById('mobile-menu-backdrop');
    const openButton = document.getElementById('mobile-menu-open');
    const closeButton = document.getElementById('mobile-menu-close');

    if (!menu || !panel || !backdrop || !openButton || !closeButton) {
        return;
    }

    let isOpen = false;

    const openMenu = () => {
        if (isOpen) {
            return;
        }

        isOpen = true;
        menu.classList.remove('hidden');
        menu.setAttribute('aria-hidden', 'false');
        openButton.setAttribute('aria-expanded', 'true');
        document.body.classList.add('overflow-hidden');

        requestAnimationFrame(() => {
            backdrop.classList.remove('opacity-0');
            panel.classList.remove('translate-x-full');
        });
    };

    const closeMenu = () => {
        if (!isOpen) {
            return;
        }

        isOpen = false;
        menu.setAttribute('aria-hidden', 'true');
        openButton.setAttribute('aria-expanded', 'false');
        backdrop.classList.add('opacity-0');
        panel.classList.add('translate-x-full');
        document.body.classList.remove('overflow-hidden');

        window.setTimeout(() => {
            if (!isOpen) {
                menu.classList.add('hidden');
            }
        }, 300);
    };

    openButton.addEventListener('click', openMenu);
    closeButton.addEventListener('click', closeMenu);
    backdrop.addEventListener('click', closeMenu);

    menu.querySelectorAll('[data-mobile-menu-close]').forEach((element) => {
        element.addEventListener('click', closeMenu);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && isOpen) {
            closeMenu();
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initMobileMenu();

    document.querySelectorAll('[data-slider-track]').forEach((track) => {
        enableDragScroll(track);
    });

    document.querySelectorAll('[data-horizontal-slider]').forEach((slider) => {
        const track = slider.querySelector('[data-slider-track]');
        const previousButton = slider.querySelector('[data-slider-prev]');
        const nextButton = slider.querySelector('[data-slider-next]');

        if (!track || !previousButton || !nextButton) {
            return;
        }

        const updateButtons = () => {
            const maxScrollLeft = track.scrollWidth - track.clientWidth;

            previousButton.disabled = track.scrollLeft <= 4;
            nextButton.disabled = track.scrollLeft >= maxScrollLeft - 4;
        };

        const scrollSlider = (direction) => {
            track.scrollBy({
                left: direction * Math.max(track.clientWidth * 0.85, 220),
                behavior: 'smooth',
            });
        };

        previousButton.addEventListener('click', () => scrollSlider(-1));
        nextButton.addEventListener('click', () => scrollSlider(1));
        track.addEventListener('scroll', updateButtons, { passive: true });
        window.addEventListener('resize', updateButtons);

        updateButtons();
    });
});
