import './stimulus_bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

const miniCartContentId = 'miniCartContent';
const cartToggleButtonId = 'cartToggleButton';
const cartCountBadgeId = 'cartCountBadge';

const withAjaxParam = (url) => {
    if (!url) return url;
    if (url.includes('ajax=1')) return url;
    const separator = url.includes('?') ? '&' : '?';
    return `${url}${separator}ajax=1`;
};

const fetchMiniCart = async () => {
    const container = document.getElementById(miniCartContentId);
    if (!container) return;
    const url = withAjaxParam(container.getAttribute('data-mini-cart-url'));
    if (!url) return;

    const response = await fetch(url, {
        credentials: 'same-origin',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'text/html',
        },
    });
    if (!response.ok) return;
    const html = await response.text();
    if (!html.trim().startsWith('<')) {
        await fetchMiniCart();
        return;
    }
    container.innerHTML = html;
    updateCartBadge(container);
};

const openMiniCart = () => {
    const offcanvasEl = document.getElementById('cartOffcanvas');
    if (!offcanvasEl || !window.bootstrap) return;
    const offcanvas = window.bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl);
    offcanvas.show();
};

const handleCartAction = async (event) => {
    const target = event.target.closest('[data-cart-action]');
    if (!target) return;

    event.preventDefault();
    const url = withAjaxParam(target.getAttribute('href'));
    if (!url) return;

    const response = await fetch(url, {
        credentials: 'same-origin',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'text/html',
        },
    });
    if (!response.ok) return;

    const container = document.getElementById(miniCartContentId);
    if (container) {
        const html = await response.text();
        if (!html.trim().startsWith('<')) {
            await fetchMiniCart();
        } else {
            container.innerHTML = html;
            updateCartBadge(container);
        }
    }
};

const handleAddToCart = async (event) => {
    const target = event.target.closest('[data-add-to-cart]');
    if (!target) return;

    event.preventDefault();
    const url = withAjaxParam(target.getAttribute('href'));
    if (!url) return;

    const response = await fetch(url, {
        credentials: 'same-origin',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'text/html',
        },
    });
    if (!response.ok) return;

    const container = document.getElementById(miniCartContentId);
    if (container) {
        const html = await response.text();
        if (!html.trim().startsWith('<')) {
            await fetchMiniCart();
        } else {
            container.innerHTML = html;
            updateCartBadge(container);
        }
    }
    openMiniCart();
};

const updateCartBadge = (container) => {
    const badge = document.getElementById(cartCountBadgeId);
    if (!badge || !container) return;
    const countEl = container.querySelector('[data-cart-count]');
    const count = countEl ? countEl.getAttribute('data-cart-count') : '0';
    badge.textContent = count;
    badge.classList.toggle('d-none', count === '0');
    if (count !== '0') {
        badge.classList.add('d-inline-flex');
    }
};

let cartHandlersBound = false;

const initializeCartHandlers = () => {
    if (!cartHandlersBound) {
        document.body.addEventListener('click', handleAddToCart);
        document.body.addEventListener('click', handleCartAction);
        cartHandlersBound = true;
    }

    const container = document.getElementById(miniCartContentId);
    if (container) {
        updateCartBadge(container);
    }
    fetchMiniCart();

    const cartToggle = document.getElementById(cartToggleButtonId);
    if (cartToggle) {
        cartToggle.addEventListener('click', fetchMiniCart);
    }
};

document.addEventListener('DOMContentLoaded', initializeCartHandlers);
document.addEventListener('turbo:load', initializeCartHandlers);
