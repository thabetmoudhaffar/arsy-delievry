// Arsy Delivery - Glovo-style Landing Page
document.addEventListener('DOMContentLoaded', () => {
    initFadeIn();
    initLocationModal();
    initHorizontalScroll();
    initMobileNav();
    initAddressSync();
    loadSavedAddress();
    initHeroBentoTilt();
});

function initHeroBentoTilt() {
    const bento = document.getElementById('hero-bento');
    if (!bento || window.innerWidth < 992) return;

    bento.addEventListener('mousemove', (e) => {
        const rect = bento.getBoundingClientRect();
        const x = (e.clientX - rect.left) / rect.width - 0.5;
        const y = (e.clientY - rect.top) / rect.height - 0.5;
        bento.style.transform = `rotateY(${x * 6}deg) rotateX(${-y * 6}deg)`;
    });

    bento.addEventListener('mouseleave', () => {
        bento.style.transform = '';
    });
}

function initFadeIn() {
    const elements = document.querySelectorAll('.fade-in');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) entry.target.classList.add('visible');
        });
    }, { threshold: 0.1 });
    elements.forEach(el => observer.observe(el));
}

function initLocationModal() {
    const modal = document.getElementById('location-modal');
    const openBtn = document.getElementById('location-btn');
    const closeBtn = document.getElementById('modal-close');
    const backdrop = modal?.querySelector('.glovo-modal-backdrop');
    const saveBtn = document.getElementById('modal-save');
    const locateBtn = document.getElementById('modal-locate');
    const modalInput = document.getElementById('modal-address');
    const heroInput = document.getElementById('hero-address');
    const label = document.getElementById('location-label');

    if (!modal) return;

    const open = () => {
        modal.classList.add('open');
        hideLocationMsg();
        if (modalInput) modalInput.value = heroInput?.value || localStorage.getItem('arsy_address') || '';
        modalInput?.focus();
    };

    const close = () => modal.classList.remove('open');

    openBtn?.addEventListener('click', open);
    closeBtn?.addEventListener('click', close);
    backdrop?.addEventListener('click', close);

    saveBtn?.addEventListener('click', () => {
        const addr = modalInput?.value.trim();
        if (!addr) return;
        saveAddress(addr);
        close();
    });

    locateBtn?.addEventListener('click', () => requestUserLocation(locateBtn, modalInput, close));

    modalInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') saveBtn?.click();
    });
}

function showLocationMsg(text, type = 'info') {
    const el = document.getElementById('location-msg');
    if (!el) return;
    el.hidden = false;
    el.className = `location-msg location-msg--${type}`;
    el.innerHTML = text;
}

function hideLocationMsg() {
    const el = document.getElementById('location-msg');
    if (el) el.hidden = true;
}

function getGeoErrorMessage(error) {
    const messages = {
        1: 'Autorisez la localisation dans votre navigateur, ou saisissez l\'adresse manuellement.',
        2: 'Position indisponible. Vérifiez que le GPS est activé.',
        3: 'Délai dépassé. Réessayez ou entrez votre adresse à la main.',
    };
    return messages[error?.code] || 'Impossible d\'obtenir votre position. Entrez votre adresse manuellement.';
}

async function reverseGeocode(lat, lng) {
    try {
        const url = `https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json&accept-language=fr`;
        const res = await fetch(url, { headers: { 'Accept-Language': 'fr' } });
        if (!res.ok) throw new Error('geocode failed');
        const data = await res.json();
        return data.display_name || null;
    } catch {
        return null;
    }
}

function requestUserLocation(btn, inputEl, onSuccess) {
    hideLocationMsg();

    if (!navigator.geolocation) {
        showLocationMsg('<i class="fas fa-info-circle"></i> Votre navigateur ne supporte pas la géolocalisation. Entrez votre adresse ci-dessus.', 'info');
        return;
    }

    const defaultHtml = '<i class="fas fa-crosshairs"></i> Utiliser ma position';
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Localisation...';

    navigator.geolocation.getCurrentPosition(
        async (pos) => {
            const { latitude, longitude } = pos.coords;
            localStorage.setItem('arsy_lat', latitude);
            localStorage.setItem('arsy_lng', longitude);

            showLocationMsg('<i class="fas fa-spinner fa-spin"></i> Recherche de l\'adresse...', 'info');

            const address = await reverseGeocode(latitude, longitude);
            const finalAddr = address || `Lat: ${latitude.toFixed(4)}, Lng: ${longitude.toFixed(4)}`;

            if (inputEl) inputEl.value = finalAddr;
            saveAddress(finalAddr, latitude, longitude);
            btn.disabled = false;
            btn.innerHTML = defaultHtml;
            hideLocationMsg();
            onSuccess?.();
        },
        (error) => {
            btn.disabled = false;
            btn.innerHTML = defaultHtml;
            showLocationMsg(`<i class="fas fa-exclamation-circle"></i> ${getGeoErrorMessage(error)}`, 'error');
        },
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 60000 }
    );
}

function saveAddress(addr, lat = null, lng = null) {
    localStorage.setItem('arsy_address', addr);
    if (lat != null && lng != null) {
        localStorage.setItem('arsy_lat', lat);
        localStorage.setItem('arsy_lng', lng);
    }
    const label = document.getElementById('location-label');
    const heroInput = document.getElementById('hero-address');
    if (label) label.textContent = addr.length > 28 ? addr.substring(0, 28) + '…' : addr;
    if (heroInput) heroInput.value = addr;
}

function loadSavedAddress() {
    const saved = localStorage.getItem('arsy_address');
    if (saved) saveAddress(saved);
}

function initAddressSync() {
    const heroInput = document.getElementById('hero-address');
    heroInput?.addEventListener('change', () => {
        if (heroInput.value.trim()) saveAddress(heroInput.value.trim());
    });
}

function initHorizontalScroll() {
    document.querySelectorAll('.glovo-categories-scroll, .glovo-stores-scroll').forEach(el => {
        let isDown = false, startX, scrollLeft;

        el.addEventListener('mousedown', (e) => {
            isDown = true;
            startX = e.pageX - el.offsetLeft;
            scrollLeft = el.scrollLeft;
            el.style.cursor = 'grabbing';
        });

        el.addEventListener('mouseleave', () => { isDown = false; el.style.cursor = ''; });
        el.addEventListener('mouseup', () => { isDown = false; el.style.cursor = ''; });

        el.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            el.scrollLeft = scrollLeft - (e.pageX - el.offsetLeft - startX) * 1.5;
        });
    });
}

function initMobileNav() {
    const btn = document.getElementById('glovo-menu-btn');
    const nav = document.querySelector('.glovo-nav');
    btn?.addEventListener('click', () => nav?.classList.toggle('open'));
}
