/*
 * Moramarket interaction layer — one reusable source for storefront, admin,
 * vendor and customer dashboards. Keep versioning in Git tags.
 */
const $ = (selector, root = document) => root.querySelector(selector);
const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));

const modalTriggers = new WeakMap();
let pendingConfirmation = null;
let pendingLink = null;

function setTheme(theme) {
    const value = theme === 'dark' ? 'dark' : 'light';
    document.documentElement.dataset.theme = value;
    try { localStorage.setItem('mm-theme', value); } catch (_) { /* storage may be unavailable */ }
    document.cookie = 'mm-theme=' + value + '; Path=/; SameSite=Lax'
        + (window.location.protocol === 'https:' ? '; Secure' : '');
    $$('[data-theme-toggle]').forEach(button => {
        button.setAttribute('aria-label', value === 'dark' ? 'Activate light mode' : 'Activate dark mode');
    });
}

function toggleSidebar() {
    const sidebar = $('#workspace-sidebar');
    if (!sidebar) return;
    const open = sidebar.classList.toggle('is-open');
    $$('[data-sidebar-toggle]').forEach(button => button.setAttribute('aria-expanded', String(open)));
}

function openDialog(id, trigger = null) {
    const dialog = document.getElementById(id);
    if (!(dialog instanceof HTMLDialogElement) || !dialog.showModal) return;
    closeMenus();
    if (trigger instanceof HTMLElement) modalTriggers.set(dialog, trigger);
    if (!dialog.open) dialog.showModal();
    const first = $('input:not([type=hidden]):not([disabled]),select:not([disabled]),textarea:not([disabled]),button:not([disabled])', dialog);
    first?.focus({ preventScroll: true });
}

function closeMenus() {
    $$('.action-popover:popover-open').forEach(menu => menu.hidePopover());
}

function placeMenu(menu, trigger) {
    const bounds = trigger.getBoundingClientRect();
    const width = Math.min(Math.max(menu.offsetWidth || 180, 180), window.innerWidth - 16);
    const left = Math.max(8, Math.min(bounds.right - width, window.innerWidth - width - 8));
    const preferred = bounds.bottom + 6;
    const top = preferred + menu.offsetHeight > window.innerHeight - 8
        ? Math.max(8, bounds.top - menu.offsetHeight - 6) : preferred;
    menu.style.left = left + 'px';
    menu.style.top = top + 'px';
}

function openActionMenu(trigger) {
    const menu = document.getElementById(trigger.dataset.menuId);
    if (!menu || typeof menu.showPopover !== 'function') return;
    if (menu.matches(':popover-open')) {
        menu.hidePopover();
        return;
    }
    closeMenus();
    menu.showPopover();
    placeMenu(menu, trigger);
    trigger.setAttribute('aria-expanded', 'true');
}

function safeLink(value) {
    if (/^https?:\/\//i.test(value)) {
        try {
            const parsed = new URL(value);
            return ['http:', 'https:'].includes(parsed.protocol) && !/[\\\u0000-\u001f]/.test(value);
        } catch (_) { return false; }
    }
    return value.startsWith('/') && !value.startsWith('//') && !/[\\\u0000-\u001f]/.test(value);
}

function confirmForm(form) {
    const dialog = $('#mm-global-confirm');
    if (!dialog?.showModal) return; // never submit destructive actions without confirmation
    pendingConfirmation = form;
    const message = $('[data-confirm-message]', dialog);
    if (message) message.textContent = form.dataset.confirm || 'Confirm this action?';
    openDialog(dialog.id);
}

function attachEditor(root) {
    const canvas = $('[data-editor-canvas]', root);
    const source = $('[data-editor-source]', root);
    const toggle = $('[data-html-toggle]', root);
    if (!canvas || !source || !toggle) return;
    let mode = 'visual';
    source.value = canvas.innerHTML;

    const sync = () => {
        if (mode === 'visual') source.value = canvas.innerHTML;
    };
    canvas.addEventListener('input', sync);
    $$('[data-edit-command],[data-edit-block],[data-edit-link]', root).forEach(button => {
        button.addEventListener('mousedown', event => event.preventDefault());
        button.addEventListener('click', () => {
            if (mode !== 'visual') return;
            canvas.focus();
            if (button.hasAttribute('data-edit-link')) {
                const selection = window.getSelection();
                const range = selection?.rangeCount ? selection.getRangeAt(0).cloneRange() : null;
                pendingLink = { canvas, source, range };
                openDialog('mm-editor-link', button);
                return;
            }
            if (button.dataset.editCommand) {
                document.execCommand(button.dataset.editCommand, false, null);
            } else if (button.dataset.editBlock) {
                document.execCommand('formatBlock', false, button.dataset.editBlock);
            }
            sync();
        });
    });

    toggle.addEventListener('click', () => {
        if (mode === 'visual') {
            sync();
            canvas.hidden = true;
            source.hidden = false;
            toggle.textContent = 'Text';
            source.focus();
            mode = 'html';
            return;
        }
        // Source mode is never executed as live HTML in the browser. Save to
        // invoke the server-side allowlist sanitizer before public rendering.
        canvas.textContent = source.value;
        canvas.hidden = false;
        source.hidden = true;
        toggle.textContent = 'HTML';
        canvas.focus();
        mode = 'source';
    });

    canvas.addEventListener('input', () => {
        if (mode === 'source' || mode === 'source-editing') {
            mode = 'source-editing';
            source.value = canvas.textContent;
        }
    });
    root.closest('form')?.addEventListener('submit', () => {
        if (mode === 'visual') sync();
    });
}

document.addEventListener('click', event => {
    const theme = event.target.closest('[data-theme-toggle]');
    if (theme) { setTheme(document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark'); return; }

    const sidebar = event.target.closest('[data-sidebar-toggle]');
    if (sidebar) { toggleSidebar(); return; }

    const menu = event.target.closest('[data-menu-id]');
    if (menu) { openActionMenu(menu); return; }

    const open = event.target.closest('[data-open-modal]');
    if (open) { openDialog(open.dataset.openModal, open); return; }

    const close = event.target.closest('[data-close-modal]');
    if (close) { close.closest('dialog')?.close(); return; }

    const password = event.target.closest('[data-password-toggle]');
    if (password) {
        const input = document.getElementById(password.dataset.passwordToggle);
        if (input) {
            input.type = input.type === 'password' ? 'text' : 'password';
            password.setAttribute('aria-label', input.type === 'password' ? 'Show password' : 'Hide password');
        }
        return;
    }

    const dismiss = event.target.closest('[data-dismiss-toast]');
    if (dismiss) dismiss.closest('.toast')?.remove();

    if (event.target.closest('[data-open-modal]') === null
        && !event.target.closest('#workspace-sidebar,[data-sidebar-toggle]')
        && $('#workspace-sidebar')?.classList.contains('is-open')) {
        $('#workspace-sidebar').classList.remove('is-open');
        $$('[data-sidebar-toggle]').forEach(btn => btn.setAttribute('aria-expanded', 'false'));
    }
});

document.addEventListener('submit', event => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;

    if (form.id === 'mm-editor-link-form') {
        event.preventDefault();
        const link = $('#mm-link-url')?.value?.trim() || '';
        if (!safeLink(link) || !pendingLink) {
            $('#mm-link-url')?.setCustomValidity('Enter a valid HTTPS, HTTP or local path.');
            $('#mm-link-url')?.reportValidity();
            return;
        }
        $('#mm-link-url')?.setCustomValidity('');
        const { canvas, source, range } = pendingLink;
        $('#mm-editor-link')?.close();
        canvas.focus();
        if (range) {
            const selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(range);
        }
        document.execCommand('createLink', false, link);
        source.value = canvas.innerHTML;
        pendingLink = null;
        return;
    }

    if (form.hasAttribute('data-confirm') && !form.dataset.confirmApproved) {
        event.preventDefault();
        confirmForm(form);
        return;
    }

    // Preserve the exact modal that produced a server validation error.
    const dialog = form.closest('dialog.modal');
    if (dialog && !form.querySelector('input[name="_modal"]')) {
        const field = document.createElement('input');
        field.type = 'hidden';
        field.name = '_modal';
        field.value = dialog.id;
        form.appendChild(field);
    }

    if (form.hasAttribute('data-goto-form')) {
        const field = $('input[name="page"]', form);
        if (!field || !Number.isInteger(Number(field.value)) || Number(field.value) < 1
            || Number(field.value) > Number(field.max)) event.preventDefault();
    }
});

document.addEventListener('toggle', event => {
    const menu = event.target;
    if (!(menu instanceof HTMLElement) || !menu.classList.contains('action-popover')) return;
    const trigger = $$('[data-menu-id]').find(button => button.dataset.menuId === menu.id);
    if (trigger) trigger.setAttribute('aria-expanded', String(menu.matches(':popover-open')));
}, true);

$$('dialog.modal').forEach(dialog => {
    dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
    dialog.addEventListener('close', () => modalTriggers.get(dialog)?.focus({ preventScroll: true }));
});

$('#mm-global-confirm [data-confirm-approve]')?.addEventListener('click', () => {
    const form = pendingConfirmation;
    pendingConfirmation = null;
    $('#mm-global-confirm')?.close();
    if (!form) return;
    form.dataset.confirmApproved = '1';
    form.requestSubmit();
});

$('#mm-editor-link')?.addEventListener('close', () => {
    if (pendingLink && !$('#mm-editor-link')?.returnValue) pendingLink = null;
});

$('#mm-link-url')?.addEventListener('input', event => event.target.setCustomValidity(''));

$$('[data-rich-editor]').forEach(attachEditor);

$$('[data-media-picker]').forEach(select => {
    const preview = document.getElementById(select.dataset.mediaPicker);
    if (!preview) return;
    const update = () => {
        const url = select.selectedOptions[0]?.dataset.preview;
        if (url) { preview.src = url; preview.hidden = false; }
        else { preview.removeAttribute('src'); preview.hidden = true; }
    };
    select.addEventListener('change', update);
    update();
});

$$('input[type=file][data-file-preview]').forEach(input => {
    let blobUrl = null;
    const preview = document.getElementById(input.dataset.filePreview);
    if (!preview) return;
    input.addEventListener('change', () => {
        if (blobUrl) URL.revokeObjectURL(blobUrl);
        const file = input.files?.[0];
        if (file?.type?.startsWith('image/')) {
            blobUrl = URL.createObjectURL(file);
            preview.src = blobUrl;
            preview.hidden = false;
        } else {
            preview.removeAttribute('src');
            preview.hidden = true;
        }
    });
});

const oldModal = $('#mm-old-modal')?.dataset.modal;
if (oldModal && document.getElementById(oldModal) instanceof HTMLDialogElement) {
    openDialog(oldModal);
}
// The server renders html[data-theme] from the unencrypted preference cookie.
// Keep the cookie authoritative when localStorage and cookie disagree (e.g.
// after a restored session), and use localStorage only as a fallback.
let storedTheme = null;
try { storedTheme = localStorage.getItem('mm-theme'); } catch (_) { /* optional */ }
const cookieTheme = document.cookie.match(/(?:^|;\s*)mm-theme=(dark|light)(?:;|$)/)?.[1];
const initialTheme = cookieTheme || storedTheme || document.documentElement.dataset.theme;
setTheme(initialTheme === 'dark' ? 'dark' : 'light');
