function setTheme(theme) {
    document.documentElement.dataset.theme = theme;
    localStorage.setItem('mm-theme', theme);
}

document.addEventListener('click', (event) => {
    const themeButton = event.target.closest('[data-theme-toggle]');
    if (themeButton) {
        setTheme(document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark');
    }

    const sidebarToggle = event.target.closest('[data-sidebar-toggle]');
    if (sidebarToggle) {
        document.querySelector('#workspace-sidebar')?.classList.toggle('is-open');
    }

    const open = event.target.closest('[data-open-modal]');
    if (open) {
        const dialog = document.getElementById(open.dataset.openModal);
        if (dialog?.showModal) dialog.showModal();
    }

    const close = event.target.closest('[data-close-modal]');
    if (close) close.closest('dialog')?.close();

    const password = event.target.closest('[data-password-toggle]');
    if (password) {
        const input = document.getElementById(password.dataset.passwordToggle);
        if (input) {
            input.type = input.type === 'password' ? 'text' : 'password';
            password.setAttribute('aria-label', input.type === 'password' ? 'Show password' : 'Hide password');
        }
    }

    const menuButton = event.target.closest('[data-menu-id]');
    if (menuButton) {
        const menu = document.getElementById(menuButton.dataset.menuId);
        if (menu?.showPopover) {
            if (menu.matches(':popover-open')) {
                menu.hidePopover();
            } else {
                document.querySelectorAll('.action-popover:popover-open').forEach(other => other.hidePopover());
                menu.showPopover();
                const trigger = menuButton.getBoundingClientRect();
                const left = Math.max(8, Math.min(trigger.right - 170, window.innerWidth - 180));
                const top = Math.min(trigger.bottom + 5, window.innerHeight - menu.offsetHeight - 10);
                menu.style.left = left + 'px';
                menu.style.top = Math.max(8, top) + 'px';
            }
        }
    }

    const dismiss = event.target.closest('[data-dismiss-toast]');
    if (dismiss) dismiss.closest('.toast')?.remove();
});

document.querySelectorAll('dialog.modal').forEach(dialog => {
    dialog.addEventListener('click', e => {
        if (e.target === dialog) dialog.close();
    });
});

document.querySelectorAll('form[data-confirm]').forEach(form => {
    form.addEventListener('submit', e => {
        if (!window.confirm(form.dataset.confirm)) e.preventDefault();
    });
});

document.querySelectorAll('[data-goto-form]').forEach(form => {
    form.addEventListener('submit', e => {
        const input = form.querySelector('input[name="page"]');
        if (!input || !Number.isInteger(Number(input.value)) || Number(input.value) < 1) e.preventDefault();
    });
});
