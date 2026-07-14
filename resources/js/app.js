const menuToggle = document.querySelector('[data-menu-toggle]');
const mobileMenu = document.querySelector('[data-mobile-menu]');
const siteHeader = document.querySelector('[data-site-header]');

if (menuToggle && mobileMenu && siteHeader) {
    const setMenuState = (isOpen, restoreFocus = false) => {
        menuToggle.setAttribute('aria-expanded', String(isOpen));
        mobileMenu.hidden = !isOpen;
        document.body.classList.toggle('menu-open', isOpen);

        const label = menuToggle.querySelector('.sr-only');
        if (label) {
            label.textContent = isOpen ? 'Закрыть меню' : 'Открыть меню';
        }

        if (!isOpen && restoreFocus) {
            menuToggle.focus();
        }
    };

    menuToggle.addEventListener('click', () => {
        const isOpen = menuToggle.getAttribute('aria-expanded') === 'true';
        setMenuState(!isOpen);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && menuToggle.getAttribute('aria-expanded') === 'true') {
            setMenuState(false, true);
        }
    });

    document.addEventListener('click', (event) => {
        if (menuToggle.getAttribute('aria-expanded') === 'true' && !siteHeader.contains(event.target)) {
            setMenuState(false);
        }
    });

    window.matchMedia('(min-width: 981px)').addEventListener('change', (event) => {
        if (event.matches) {
            setMenuState(false);
        }
    });
}
