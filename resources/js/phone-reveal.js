const STORAGE_KEY = 'allo_phone_revealed';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function trackingConfig() {
    const element = document.getElementById('meta-tracking-config');

    if (!element) {
        return {};
    }

    try {
        return JSON.parse(element.textContent);
    } catch {
        return {};
    }
}

function newEventId() {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
        return crypto.randomUUID();
    }

    return `phone-reveal-${Date.now()}-${Math.random().toString(16).slice(2)}`;
}

function revealAll() {
    document.documentElement.classList.add('phones-revealed');

    document.querySelectorAll('[data-phone-reveal]').forEach((root) => {
        const trigger = root.querySelector('[data-phone-reveal-trigger]');
        const numbers = root.querySelector('[data-phone-reveal-numbers]');

        if (trigger) {
            trigger.setAttribute('hidden', '');
            trigger.classList.add('hidden');
            trigger.setAttribute('aria-expanded', 'true');
        }

        if (numbers) {
            numbers.removeAttribute('hidden');
            numbers.classList.remove('hidden');
            numbers.classList.add(numbers.dataset.display === 'stack' ? 'block' : 'flex');
        }
    });
}

function trackClient(eventId) {
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({
        event: 'phone_reveal',
        event_id: eventId,
    });

    if (typeof window.gtag === 'function') {
        window.gtag('event', 'phone_reveal', {
            event_category: 'contact',
            event_label: 'show_phones',
            event_id: eventId,
        });
    }

    if (window.MetaTracking?.enqueue) {
        window.MetaTracking.enqueue({
            event_name: 'Contact',
            event_id: eventId,
            payload: {
                content_name: 'phone_reveal',
                content_category: 'contact',
            },
        });
    }
}

async function reportReveal() {
    const config = trackingConfig();
    const url = config.phoneRevealUrl || '/phone-reveal';
    let eventId = newEventId();

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                event_source_url: window.location.href,
            }),
        });

        if (response.ok) {
            const data = await response.json();

            if (data.event_id) {
                eventId = data.event_id;
            }
        }
    } catch {
        // Still reveal the numbers if tracking is unavailable.
    }

    trackClient(eventId);
}

function onRevealClick(event) {
    event.preventDefault();

    let alreadyRevealed = false;

    try {
        alreadyRevealed = sessionStorage.getItem(STORAGE_KEY) === '1';
        sessionStorage.setItem(STORAGE_KEY, '1');
    } catch {
        alreadyRevealed = document.documentElement.classList.contains('phones-revealed');
    }

    revealAll();

    if (!alreadyRevealed) {
        reportReveal();
    }
}

export function initPhoneReveal() {
    try {
        if (sessionStorage.getItem(STORAGE_KEY) === '1') {
            revealAll();

            return;
        }
    } catch {
        // Ignore storage access errors and keep the button visible.
    }

    document.querySelectorAll('[data-phone-reveal-trigger]').forEach((button) => {
        button.addEventListener('click', onRevealClick);
    });
}
