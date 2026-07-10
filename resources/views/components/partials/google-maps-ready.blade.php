@php
    $googleMapsKey = $googleMapsKey ?? config('services.google_maps.key');
@endphp

@if ($googleMapsKey)
    <script src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsKey }}&language=bg&region=BG" async defer></script>
@endif

<script>
    window.ensureGoogleMapsReady = window.ensureGoogleMapsReady || function () {
        if (window.__googleMapsReadyPromise) {
            return window.__googleMapsReadyPromise;
        }

        window.__googleMapsReadyPromise = new Promise((resolve, reject) => {
            let attempts = 0;
            const maxAttempts = 150;

            const check = () => {
                attempts++;

                if (typeof google !== 'undefined' && google.maps && typeof google.maps.Map === 'function') {
                    resolve(google.maps);
                    return;
                }

                if (attempts >= maxAttempts) {
                    reject(new Error('Google Maps не се зареди.'));
                    return;
                }

                setTimeout(check, 100);
            };

            check();
        });

        return window.__googleMapsReadyPromise;
    };
</script>
