function loadImageDimensions(url) {
    return new Promise((resolve) => {
        const image = new Image();
        image.onload = () => {
            resolve({
                width: image.naturalWidth,
                height: image.naturalHeight,
            });
        };
        image.onerror = () => {
            resolve({ width: 1200, height: 1200 });
        };
        image.src = url;
    });
}

async function initProductGallery() {
    const gallery = document.querySelector('[data-product-gallery]');

    if (!gallery) {
        return;
    }

    const [{ default: PhotoSwipeLightbox }] = await Promise.all([
        import('photoswipe/lightbox'),
        import('photoswipe/style.css'),
    ]);

    const anchors = [...gallery.querySelectorAll('[data-pswp-item]')];
    const mainButton = gallery.querySelector('[data-gallery-open]');
    const mainImage = gallery.querySelector('[data-gallery-main-image]');
    const thumbButtons = [...gallery.querySelectorAll('[data-gallery-thumb]')];

    if (anchors.length === 0 || !mainButton || !mainImage) {
        return;
    }

    await Promise.all(anchors.map(async (anchor) => {
        const dimensions = await loadImageDimensions(anchor.href);
        anchor.dataset.pswpWidth = String(dimensions.width);
        anchor.dataset.pswpHeight = String(dimensions.height);
    }));

    let activeIndex = 0;

    const lightbox = new PhotoSwipeLightbox({
        gallery,
        children: '[data-pswp-item]',
        pswpModule: () => import('photoswipe'),
    });

    lightbox.on('change', () => {
        activeIndex = lightbox.pswp.currIndex;
        syncActiveThumb(activeIndex);
        updateMainImage(activeIndex);
    });

    lightbox.init();

    function updateMainImage(index) {
        const anchor = anchors[index];

        if (!anchor) {
            return;
        }

        mainImage.src = anchor.dataset.galleryDisplayUrl || anchor.href;
        mainButton.setAttribute('aria-label', `Увеличи снимката ${index + 1} от ${anchors.length}`);
    }

    function syncActiveThumb(index) {
        thumbButtons.forEach((button, buttonIndex) => {
            const isActive = buttonIndex === index;

            button.classList.toggle('border-brand-500', isActive);
            button.classList.toggle('ring-2', isActive);
            button.classList.toggle('ring-brand-500/30', isActive);
            button.classList.toggle('border-stone-200', !isActive);
            button.setAttribute('aria-current', isActive ? 'true' : 'false');
        });
    }

    mainButton.addEventListener('click', () => {
        lightbox.loadAndOpen(activeIndex);
    });

    thumbButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const index = parseInt(button.dataset.galleryThumb || '0', 10);

            activeIndex = index;
            updateMainImage(index);
            syncActiveThumb(index);
            lightbox.loadAndOpen(index);
        });
    });

    syncActiveThumb(activeIndex);
}

document.addEventListener('DOMContentLoaded', () => {
    initProductGallery();
});
