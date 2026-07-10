(function () {
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    document.querySelectorAll('[data-hero-carousel]').forEach((carousel) => {
        const slides = Array.from(carousel.querySelectorAll('[data-hero-slide]'));
        const dots = Array.from(carousel.querySelectorAll('[data-hero-dot]'));
        const counter = carousel.querySelector('[data-hero-counter]');
        const previousButton = carousel.querySelector('[data-hero-prev]');
        const nextButton = carousel.querySelector('[data-hero-next]');

        if (slides.length < 2) {
            return;
        }

        const intervalMs = Number.parseInt(carousel.dataset.heroInterval, 10) || 5000;
        let currentIndex = slides.findIndex((slide) => slide.classList.contains('is-active'));

        if (currentIndex < 0) {
            currentIndex = 0;
        }

        let timer = null;
        let isPaused = false;

        const updateSlide = (nextIndex) => {
            currentIndex = (nextIndex + slides.length) % slides.length;

            slides.forEach((slide, index) => {
                const isActive = index === currentIndex;

                slide.classList.toggle('is-active', isActive);
                slide.setAttribute('aria-hidden', isActive ? 'false' : 'true');
            });

            dots.forEach((dot, index) => {
                const isActive = index === currentIndex;

                dot.classList.toggle('is-active', isActive);
                dot.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });

            if (counter) {
                counter.textContent = `${currentIndex + 1} / ${slides.length}`;
            }
        };

        const stopTimer = () => {
            if (timer !== null) {
                clearInterval(timer);
                timer = null;
            }
        };

        const startTimer = () => {
            if (prefersReducedMotion || isPaused) {
                return;
            }

            stopTimer();
            timer = setInterval(() => {
                updateSlide(currentIndex + 1);
            }, intervalMs);
        };

        const goToSlide = (index) => {
            updateSlide(index);
            startTimer();
        };

        dots.forEach((dot) => {
            dot.addEventListener('click', () => {
                goToSlide(Number.parseInt(dot.dataset.heroDot, 10));
            });
        });

        previousButton?.addEventListener('click', () => {
            goToSlide(currentIndex - 1);
        });

        nextButton?.addEventListener('click', () => {
            goToSlide(currentIndex + 1);
        });

        let touchStartX = 0;
        let touchStartY = 0;

        carousel.addEventListener('touchstart', (event) => {
            if (event.touches.length !== 1) {
                return;
            }

            touchStartX = event.touches[0].clientX;
            touchStartY = event.touches[0].clientY;
        }, { passive: true });

        carousel.addEventListener('touchend', (event) => {
            if (event.changedTouches.length !== 1) {
                return;
            }

            const touchEndX = event.changedTouches[0].clientX;
            const touchEndY = event.changedTouches[0].clientY;
            const deltaX = touchEndX - touchStartX;
            const deltaY = touchEndY - touchStartY;

            if (Math.abs(deltaX) < 40 || Math.abs(deltaX) < Math.abs(deltaY)) {
                return;
            }

            if (deltaX < 0) {
                goToSlide(currentIndex + 1);
            } else {
                goToSlide(currentIndex - 1);
            }
        }, { passive: true });

        carousel.addEventListener('mouseenter', () => {
            isPaused = true;
            stopTimer();
        });

        carousel.addEventListener('mouseleave', () => {
            isPaused = false;
            startTimer();
        });

        updateSlide(currentIndex);
        startTimer();
    });
})();
