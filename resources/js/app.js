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


// Real Media Library image previews: existing asset selection or local file upload.
document.querySelectorAll('[data-media-picker]').forEach(select => {
    const preview = document.getElementById(select.dataset.mediaPicker);
    if (!preview) return;
    const sync = () => {
        const option = select.selectedOptions[0];
        if (option?.dataset.preview) {
            preview.src = option.dataset.preview;
            preview.hidden = false;
        } else {
            preview.removeAttribute('src');
            preview.hidden = true;
        }
    };
    select.addEventListener('change', sync);
    sync();
});

document.querySelectorAll('input[type="file"][data-file-preview]').forEach(input => {
    let previousUrl = null;
    const preview = document.getElementById(input.dataset.filePreview);
    if (!preview) return;
    input.addEventListener('change', () => {
        if (previousUrl) URL.revokeObjectURL(previousUrl);
        const file = input.files?.[0];
        if (file?.type?.startsWith('image/')) {
            previousUrl = URL.createObjectURL(file);
            preview.src = previousUrl;
            preview.hidden = false;
        } else {
            preview.removeAttribute('src');
            preview.hidden = true;
        }
    });
});


// Simple WYSIWYG/HTML source editor. Server-side SafeHtml is mandatory and is
// the only trusted sanitizer; raw HTML is not injected back into the DOM.
document.querySelectorAll('[data-rich-editor]').forEach(root => {
    const canvas = root.querySelector('[data-editor-canvas]');
    const source = root.querySelector('[data-editor-source]');
    const toolbar = root.querySelector('[data-html-toggle]');
    if (!canvas || !source) return;

    let mode = 'visual';
    source.value = canvas.innerHTML;

    root.querySelectorAll('.editor-toolbar button:not([data-html-toggle])').forEach(button => {
        button.addEventListener('mousedown', event => event.preventDefault());
        button.addEventListener('click', () => {
            if (mode !== 'visual') return;
            canvas.focus();
            const command = button.dataset.editCommand;
            const block = button.dataset.editBlock;
            if (command) document.execCommand(command, false, null);
            if (block) document.execCommand('formatBlock', false, block);
            if (button.hasAttribute('data-edit-link')) {
                const href = window.prompt('Enter an HTTPS link or a site path starting with /');
                if (href !== null) {
                    const value = href.trim();
                    const local = value.startsWith('/') && !value.startsWith('//');
                    let safe = local;
                    if (!safe) {
                        try {
                            const parsed = new URL(value);
                            safe = parsed.protocol === 'https:' || parsed.protocol === 'http:';
                        } catch (_) { safe = false; }
                    }
                    if (safe) document.execCommand('createLink', false, value);
                }
            }
            source.value = canvas.innerHTML;
        });
    });

    canvas.addEventListener('input', () => {
        if (mode === 'visual') source.value = canvas.innerHTML;
        else if (mode === 'source-pending') {
            mode = 'visual';
            source.value = canvas.innerHTML;
        }
    });

    toolbar?.addEventListener('click', () => {
        if (mode === 'visual' || mode === 'source-pending') {
            if (mode === 'visual') source.value = canvas.innerHTML;
            canvas.hidden = true;
            source.hidden = false;
            source.focus();
            mode = 'html';
            toolbar.textContent = 'Text';
            return;
        }
        // Do not trust raw input by inserting it as live HTML. The next save
        // sanitizes it server-side; here users can return to plain text mode.
        canvas.textContent = source.value;
        canvas.hidden = false;
        source.hidden = true;
        mode = 'source-pending';
        toolbar.textContent = 'HTML';
        canvas.focus();
    });

    root.closest('form')?.addEventListener('submit', () => {
        if (mode === 'visual') source.value = canvas.innerHTML;
    });
});
