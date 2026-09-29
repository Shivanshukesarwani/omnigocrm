(function () {
    const applyOmniBranding = () => {
        if (!document.documentElement) {
            return;
        }

        document.documentElement.classList.add('omni-m3');

        if (document.body) {
            document.body.classList.add('omni-m3-body');
        }

        document.title = 'OmniGoCRM';

        const hash = window.location.hash || '';
        if ((hash === '' || hash === '#') && !window.__omniGoCrmInitialRouteSet) {
            window.__omniGoCrmInitialRouteSet = true;
            window.location.hash = '#OmniGoCRM';
        }
    };

    const start = () => {
        applyOmniBranding();
        window.setTimeout(applyOmniBranding, 100);
        window.setTimeout(applyOmniBranding, 800);
    };

    document.addEventListener('DOMContentLoaded', start);
    window.addEventListener('hashchange', applyOmniBranding);

    if (document.readyState !== 'loading') {
        start();
    }
})();
