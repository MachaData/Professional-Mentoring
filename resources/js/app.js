import './bootstrap';

// Copy-to-clipboard helper used by the "Copiar encuesta" buttons in the portal.
window.pmCopy = async (text, btn) => {
    try {
        await navigator.clipboard.writeText(text);
    } catch {
        const el = document.createElement('textarea');
        el.value = text;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        el.remove();
    }
    if (btn) {
        const label = btn.querySelector('[data-label]');
        const original = label ? label.textContent : null;
        if (label) label.textContent = btn.dataset.copied || 'Copiado';
        setTimeout(() => {
            if (label && original !== null) label.textContent = original;
        }, 1800);
    }
};
