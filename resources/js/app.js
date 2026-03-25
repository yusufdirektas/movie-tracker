import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

const initDesktopNavbarKeyboardNavigation = () => {
    const navGroup = document.querySelector('[data-nav-group]');
    if (!navGroup) return;

    const navItems = Array.from(navGroup.querySelectorAll('[data-nav-item]'));
    if (navItems.length === 0) return;

    navGroup.addEventListener('keydown', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) return;
        if (!target.matches('[data-nav-item]')) return;

        const currentIndex = navItems.indexOf(target);
        if (currentIndex === -1) return;

        let nextIndex = null;

        if (event.key === 'ArrowRight') {
            nextIndex = (currentIndex + 1) % navItems.length;
        } else if (event.key === 'ArrowLeft') {
            nextIndex = (currentIndex - 1 + navItems.length) % navItems.length;
        } else if (event.key === 'Home') {
            nextIndex = 0;
        } else if (event.key === 'End') {
            nextIndex = navItems.length - 1;
        }

        if (nextIndex === null) return;

        event.preventDefault();
        navItems[nextIndex].focus();
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDesktopNavbarKeyboardNavigation);
} else {
    initDesktopNavbarKeyboardNavigation();
}
