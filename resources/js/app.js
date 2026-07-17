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

const checkoutForm = document.querySelector('[data-checkout-form]');

if (checkoutForm) {
    const durationOutput = checkoutForm.querySelector('[data-checkout-duration]');
    const totalOutput = checkoutForm.querySelector('[data-checkout-total] .price-block__value')
        ?? checkoutForm.querySelector('[data-checkout-total]');
    const submitButton = checkoutForm.querySelector('[data-checkout-submit]');

    const updateCheckoutSummary = () => {
        const selectedInput = checkoutForm.querySelector('input[name="duration_option_id"]:checked');
        const selectedOption = selectedInput?.closest('[data-checkout-option]');
        const selectedPrice = selectedOption?.querySelector('[data-option-price] .price-block__value');

        if (durationOutput && selectedInput) {
            durationOutput.textContent = selectedInput.dataset.optionLabel ?? 'Выбранный срок';
        }

        if (totalOutput && selectedPrice) {
            totalOutput.textContent = selectedPrice.textContent;
        }
    };

    checkoutForm.addEventListener('change', (event) => {
        if (event.target.matches('input[name="duration_option_id"]')) {
            updateCheckoutSummary();
        }
    });

    checkoutForm.addEventListener('submit', () => {
        if (!submitButton || submitButton.disabled) {
            return;
        }

        submitButton.disabled = true;
        submitButton.setAttribute('aria-busy', 'true');
        submitButton.textContent = 'Оформляем…';
    });

    updateCheckoutSummary();
}
