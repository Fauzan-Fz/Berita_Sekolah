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

    // ── Profile dialog — open/close, avatar preview, bio counter, AJAX submit ──
    const profileDialog = document.querySelector('[data-dialog]');
    const profileForm = document.querySelector('[data-profile-form]');

    if (profileDialog && profileForm) {
        const errorBox = profileDialog.querySelector('[data-dialog-error]');
        const avatarInput = profileForm.querySelector('[data-avatar-input]');
        const avatarPreview = profileDialog.querySelector('[data-avatar-preview]');
        const avatarPlaceholder = profileDialog.querySelector('[data-avatar-placeholder]');
        const avatarRemove = profileDialog.querySelector('[data-avatar-remove]');
        const removeHidden = profileForm.querySelector('[data-avatar-remove-hidden]');
        const bioInput = profileForm.querySelector('[data-bio-input]');
        const bioCount = profileDialog.querySelector('[data-bio-count]');
        const submitBtn = profileDialog.querySelector('[data-profile-submit]');
        const originalPhoto = avatarPreview ? (avatarPreview.getAttribute('data-src') || '') : '';

        const hideError = () => {
            if (errorBox) errorBox.classList.add('hidden');
        };

        const showError = (msg) => {
            if (!errorBox) return;
            errorBox.textContent = msg;
            errorBox.classList.remove('hidden');
        };

        // Sinkronkan tampilan avatar dengan kondisi input saat ini.
        const syncAvatarUI = () => {
            if (!avatarPreview || !avatarInput) return;
            if (avatarInput.files && avatarInput.files.length > 0) return; // pratinjau sudah di-set saat change
            if (originalPhoto) {
                avatarPreview.src = originalPhoto;
                avatarPreview.classList.remove('hidden');
                if (avatarPlaceholder) avatarPlaceholder.classList.add('hidden');
                if (avatarRemove) avatarRemove.classList.remove('hidden');
            } else {
                avatarPreview.removeAttribute('src');
                avatarPreview.classList.add('hidden');
                if (avatarPlaceholder) avatarPlaceholder.classList.remove('hidden');
                if (avatarRemove) avatarRemove.classList.add('hidden');
            }
        };

        const syncBioCount = () => {
            if (!bioInput || !bioCount) return;
            const max = parseInt(bioInput.getAttribute('maxlength') || '1000', 10);
            bioCount.textContent = String(max - bioInput.value.length);
        };

        const openDialog = () => {
            profileForm.reset();
            hideError();
            if (avatarInput) avatarInput.value = '';
            syncAvatarUI();
            syncBioCount();
            profileDialog.classList.remove('hidden');
            document.querySelectorAll('[data-dropdown]').forEach((other) => {
                if (other._closeDropdown) other._closeDropdown();
            });
        };

        const closeDialog = () => {
            profileDialog.classList.add('hidden');
            profileForm.reset();
            hideError();
            if (avatarInput) avatarInput.value = '';
            syncAvatarUI();
        };

        document.querySelectorAll('[data-profile-trigger]').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                openDialog();
            });
        });

        profileDialog.querySelectorAll('[data-dialog-close]').forEach((btn) => {
            btn.addEventListener('click', closeDialog);
        });

        const overlay = profileDialog.querySelector('[data-dialog-overlay]');
        if (overlay) overlay.addEventListener('click', closeDialog);

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !profileDialog.classList.contains('hidden')) closeDialog();
        });

        if (avatarInput) {
            avatarInput.addEventListener('change', () => {
                const file = avatarInput.files && avatarInput.files[0];
                if (!file) return;
                const reader = new FileReader();
                reader.onload = (ev) => {
                    if (avatarPreview) {
                        avatarPreview.src = ev.target.result;
                        avatarPreview.classList.remove('hidden');
                    }
                    if (avatarPlaceholder) avatarPlaceholder.classList.add('hidden');
                    if (avatarRemove) avatarRemove.classList.remove('hidden');
                    if (removeHidden) removeHidden.value = '0';
                    hideError();
                };
                reader.readAsDataURL(file);
            });
        }

        if (avatarRemove) {
            avatarRemove.addEventListener('click', () => {
                if (avatarInput) avatarInput.value = '';
                if (avatarPreview) {
                    avatarPreview.removeAttribute('src');
                    avatarPreview.classList.add('hidden');
                }
                if (avatarPlaceholder) avatarPlaceholder.classList.remove('hidden');
                avatarRemove.classList.add('hidden');
                if (removeHidden) removeHidden.value = '1';
            });
        }

        if (bioInput) bioInput.addEventListener('input', syncBioCount);

        profileForm.addEventListener('submit', (e) => {
            e.preventDefault();
            hideError();
            if (submitBtn) submitBtn.disabled = true;

            fetch(profileForm.action, {
                method: 'POST',
                body: new FormData(profileForm),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            }).then(async (res) => {
                if (res.ok) {
                    window.location.reload();

                    return;
                }
                let message = 'Gagal menyimpan profil. Coba lagi.';
                try {
                    const data = await res.json();
                    if (data.errors) {
                        const first = Object.values(data.errors)[0];
                        message = Array.isArray(first) ? first[0] : String(first);
                    } else if (data.message) {
                        message = data.message;
                    }
                } catch (_) { /* biarkan pesan bawaan */ }
                showError(message);
            }).catch(() => {
                showError('Gagal menyimpan profil. Periksa koneksi lalu coba lagi.');
            }).finally(() => {
                if (submitBtn) submitBtn.disabled = false;
            });
        });
    }
});