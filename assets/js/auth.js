// Arsy Delivery - Auth Page Effects
document.addEventListener('DOMContentLoaded', () => {
    initPasswordToggle();
    initFormSubmit();
    initCursorGlow();
    initInputAnimations();
    initParallaxOrbs();
});

function initPasswordToggle() {
    document.querySelectorAll('.toggle-password').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = btn.parentElement.querySelector('input');
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            btn.innerHTML = isPassword
                ? '<i class="fas fa-eye-slash"></i>'
                : '<i class="fas fa-eye"></i>';
        });
    });
}

function initFormSubmit() {
    const form = document.querySelector('.auth-form');
    if (!form) return;

    form.addEventListener('submit', () => {
        const btn = form.querySelector('.auth-submit');
        if (btn) btn.classList.add('loading');
    });
}

function initCursorGlow() {
    const panel = document.querySelector('.auth-form-panel');
    const glow = document.querySelector('.auth-cursor-glow');
    if (!panel || !glow) return;

    panel.addEventListener('mousemove', (e) => {
        const rect = panel.getBoundingClientRect();
        glow.style.left = (e.clientX - rect.left) + 'px';
        glow.style.top = (e.clientY - rect.top) + 'px';
    });
}

function initInputAnimations() {
    document.querySelectorAll('.auth-input').forEach(input => {
        input.addEventListener('focus', () => {
            input.parentElement.parentElement.classList.add('focused');
        });
        input.addEventListener('blur', () => {
            input.parentElement.parentElement.classList.remove('focused');
        });
    });
}

function initParallaxOrbs() {
    const brand = document.querySelector('.auth-brand');
    if (!brand || window.innerWidth < 992) return;

    brand.addEventListener('mousemove', (e) => {
        const rect = brand.getBoundingClientRect();
        const x = (e.clientX - rect.left) / rect.width - 0.5;
        const y = (e.clientY - rect.top) / rect.height - 0.5;

        document.querySelectorAll('.auth-orb').forEach((orb, i) => {
            const factor = (i + 1) * 12;
            orb.style.transform = `translate(${x * factor}px, ${y * factor}px)`;
        });

        const logo = document.querySelector('.auth-logo');
        if (logo) {
            logo.style.transform = `translate(${x * 8}px, ${y * 8}px)`;
        }
    });

    brand.addEventListener('mouseleave', () => {
        document.querySelectorAll('.auth-orb').forEach(orb => {
            orb.style.transform = '';
        });
        const logo = document.querySelector('.auth-logo');
        if (logo) logo.style.transform = '';
    });
}
