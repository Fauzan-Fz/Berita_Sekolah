document.addEventListener('DOMContentLoaded', () => {
    // ── Account dropdown — data-attribute driven ──────────────────
    document.querySelectorAll('[data-dropdown]').forEach((root) => {
        const trigger = root.querySelector('[data-dropdown-trigger]');
        const panel = root.querySelector('[data-dropdown-panel]');
        const chevron = root.querySelector('[data-dropdown-chevron]');
        if (!trigger || !panel) return;

        const open = () => {
            panel.classList.add('pointer-events-auto', 'opacity-100', 'scale-100', 'translate-y-0');
            panel.classList.remove('pointer-events-none', 'opacity-0', 'scale-95', '-translate-y-1');
            trigger.setAttribute('aria-expanded', 'true');
            if (chevron) chevron.classList.add('rotate-180');
        };

        const close = () => {
            panel.classList.add('pointer-events-none', 'opacity-0', 'scale-95', '-translate-y-1');
            panel.classList.remove('pointer-events-auto', 'opacity-100', 'scale-100', 'translate-y-0');
            trigger.setAttribute('aria-expanded', 'false');
            if (chevron) chevron.classList.remove('rotate-180');
        };

        root._closeDropdown = close;

        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            document.querySelectorAll('[data-dropdown]').forEach((other) => {
                if (other !== root && other._closeDropdown) other._closeDropdown();
            });
            const isOpen = panel.classList.contains('opacity-100');
            isOpen ? close() : open();
        });

        document.addEventListener('click', (e) => {
            if (!root.contains(e.target)) close();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') close();
        });
    });

    // ── Toast auto-dismiss — flash messages ────────────────────────
    document.querySelectorAll('[data-toast]').forEach((toast) => {
        setTimeout(() => {
            toast.classList.add('opacity-0');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    });
});