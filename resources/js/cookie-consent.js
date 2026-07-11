const STORAGE_KEY = 'cookie_consent';
const EXPIRY_DAYS = 365;

let analyticsLoaded = false;
let marketingLoaded = false;
let consentReadyCallbacks = [];

function getAnalyticsConfig() {
    const element = document.getElementById('analytics-config');

    if (!element) {
        return null;
    }

    try {
        return JSON.parse(element.textContent);
    } catch {
        return null;
    }
}

function readStoredConsent() {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);

        if (!raw) {
            return null;
        }

        const data = JSON.parse(raw);

        if (!data || !data.timestamp) {
            return null;
        }

        const expiryMs = EXPIRY_DAYS * 24 * 60 * 60 * 1000;

        if (Date.now() - data.timestamp > expiryMs) {
            localStorage.removeItem(STORAGE_KEY);

            return null;
        }

        return {
            necessary: true,
            analytics: Boolean(data.analytics),
            marketing: Boolean(data.marketing),
            timestamp: data.timestamp,
        };
    } catch {
        return null;
    }
}

function saveConsent(preferences) {
    const data = {
        necessary: true,
        analytics: Boolean(preferences.analytics),
        marketing: Boolean(preferences.marketing),
        timestamp: Date.now(),
    };

    localStorage.setItem(STORAGE_KEY, JSON.stringify(data));

    return data;
}

function ensureGtag() {
    window.dataLayer = window.dataLayer || [];

    if (typeof window.gtag !== 'function') {
        window.gtag = function gtag() {
            window.dataLayer.push(arguments);
        };
    }
}

function updateGoogleConsent(preferences) {
    ensureGtag();

    window.gtag('consent', 'update', {
        analytics_storage: preferences.analytics ? 'granted' : 'denied',
        ad_storage: preferences.marketing ? 'granted' : 'denied',
        ad_user_data: preferences.marketing ? 'granted' : 'denied',
        ad_personalization: preferences.marketing ? 'granted' : 'denied',
    });
}

function loadScript(src, async = true) {
    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = src;
        script.async = async;
        script.onload = resolve;
        script.onerror = reject;
        document.head.appendChild(script);
    });
}

function loadGoogleTagManager(gtmId) {
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });

    return loadScript(`https://www.googletagmanager.com/gtm.js?id=${encodeURIComponent(gtmId)}`).then(() => {
        if (document.getElementById('gtm-noscript-container')) {
            return;
        }

        const noscript = document.createElement('noscript');
        noscript.id = 'gtm-noscript-container';
        noscript.innerHTML = `<iframe src="https://www.googletagmanager.com/ns.html?id=${gtmId}" height="0" width="0" style="display:none;visibility:hidden" title="Google Tag Manager"></iframe>`;
        document.body.insertBefore(noscript, document.body.firstChild);
    });
}

function loadGoogleAnalytics(gaId) {
    return loadScript(`https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(gaId)}`).then(() => {
        ensureGtag();
        window.gtag('js', new Date());
        window.gtag('config', gaId);
    });
}

function loadMetaPixel(metaPixelId) {
    if (window.fbq) {
        return Promise.resolve();
    }

    return new Promise((resolve) => {
        !function (f, b, e, v, n, t, s) {
            if (f.fbq) {
                return;
            }

            n = f.fbq = function () {
                n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments);
            };

            if (!f._fbq) {
                f._fbq = n;
            }

            n.push = n;
            n.loaded = !0;
            n.version = '2.0';
            n.queue = [];
            t = b.createElement(e);
            t.async = !0;
            t.src = v;
            t.onload = resolve;
            s = b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t, s);
        }(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');

        window.fbq('init', metaPixelId);
        window.fbq('track', 'PageView');
    });
}

function applyTracking(preferences) {
    const config = getAnalyticsConfig();

    if (!config) {
        return;
    }

    updateGoogleConsent(preferences);

    if (preferences.analytics && !analyticsLoaded) {
        if (config.gtmId) {
            loadGoogleTagManager(config.gtmId).catch(() => {});
            analyticsLoaded = true;
        } else if (config.gaId) {
            loadGoogleAnalytics(config.gaId).catch(() => {});
            analyticsLoaded = true;
        }
    }

    if (preferences.marketing && config.metaPixelId && !marketingLoaded) {
        loadMetaPixel(config.metaPixelId).catch(() => {});
        marketingLoaded = true;
    }
}

function notifyConsentReady(preferences) {
    consentReadyCallbacks.forEach((callback) => {
        callback(preferences);
    });
}

function hideBanner() {
    const banner = document.getElementById('cookie-consent-banner');

    if (banner) {
        banner.classList.add('hidden');
        banner.setAttribute('aria-hidden', 'true');
    }
}

function showBanner() {
    const banner = document.getElementById('cookie-consent-banner');

    if (banner) {
        banner.classList.remove('hidden');
        banner.setAttribute('aria-hidden', 'false');
    }
}

function setToggleState(name, enabled) {
    const toggle = document.querySelector(`[data-cookie-toggle="${name}"]`);

    if (!toggle) {
        return;
    }

    toggle.checked = enabled;
    toggle.setAttribute('aria-checked', enabled ? 'true' : 'false');
}

function syncSettingsToggles(preferences) {
    setToggleState('analytics', preferences.analytics);
    setToggleState('marketing', preferences.marketing);
}

function getSettingsFromToggles() {
    const analyticsToggle = document.querySelector('[data-cookie-toggle="analytics"]');
    const marketingToggle = document.querySelector('[data-cookie-toggle="marketing"]');

    return {
        necessary: true,
        analytics: analyticsToggle ? analyticsToggle.checked : false,
        marketing: marketingToggle ? marketingToggle.checked : false,
    };
}

function openSettingsModal() {
    const modal = document.getElementById('cookie-consent-settings');
    const stored = readStoredConsent() || { necessary: true, analytics: false, marketing: false };

    if (!modal) {
        return;
    }

    syncSettingsToggles(stored);
    modal.classList.remove('hidden');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('overflow-hidden');

    const firstFocusable = modal.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');

    if (firstFocusable) {
        firstFocusable.focus();
    }
}

function closeSettingsModal() {
    const modal = document.getElementById('cookie-consent-settings');

    if (!modal) {
        return;
    }

    modal.classList.add('hidden');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('overflow-hidden');
}

function commitConsent(preferences) {
    const saved = saveConsent(preferences);

    applyTracking(saved);
    hideBanner();
    closeSettingsModal();
    notifyConsentReady(saved);
}

let cookieConsentInitialized = false;

function initCookieConsent() {
    if (cookieConsentInitialized) {
        return;
    }

    cookieConsentInitialized = true;
    const banner = document.getElementById('cookie-consent-banner');
    const modal = document.getElementById('cookie-consent-settings');

    const acceptAllButton = document.getElementById('cookie-consent-accept-all');
    const rejectAllButton = document.getElementById('cookie-consent-reject-all');
    const openSettingsButton = document.getElementById('cookie-consent-open-settings');
    const saveSettingsButton = document.getElementById('cookie-consent-save-settings');
    const closeSettingsButton = document.getElementById('cookie-consent-close-settings');
    const settingsBackdrop = document.getElementById('cookie-consent-settings-backdrop');

    if (banner) {
        const stored = readStoredConsent();

        if (stored) {
            applyTracking(stored);
            hideBanner();
            notifyConsentReady(stored);
        } else {
            showBanner();
        }

        acceptAllButton?.addEventListener('click', () => {
            commitConsent({ necessary: true, analytics: true, marketing: true });
        });

        rejectAllButton?.addEventListener('click', () => {
            commitConsent({ necessary: true, analytics: false, marketing: false });
        });

        openSettingsButton?.addEventListener('click', openSettingsModal);
    }

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-cookie-settings-open]');

        if (!trigger || !modal) {
            return;
        }

        event.preventDefault();
        openSettingsModal();
    });

    saveSettingsButton?.addEventListener('click', () => {
        commitConsent(getSettingsFromToggles());
    });

    closeSettingsButton?.addEventListener('click', closeSettingsModal);
    settingsBackdrop?.addEventListener('click', closeSettingsModal);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
            closeSettingsModal();
        }
    });
}

window.CookieConsent = {
    getPreferences() {
        return readStoredConsent();
    },
    openSettings: openSettingsModal,
    onConsentReady(callback) {
        const stored = readStoredConsent();

        if (stored) {
            callback(stored);
        } else {
            consentReadyCallbacks.push(callback);
        }
    },
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCookieConsent);
} else {
    initCookieConsent();
}
