/**
 * FSKTM PACE System — progressive UI behaviours.
 * Everything is progressive: pages remain usable without JavaScript.
 */
(function () {
    'use strict';

    const ready = (fn) => {
        if (document.readyState !== 'loading') fn();
        else document.addEventListener('DOMContentLoaded', fn);
    };

    ready(() => {
        /* ------------------------------------------------------------
         * Mobile slide-over sidebar
         * ---------------------------------------------------------- */
        const sidebar = document.getElementById('app-sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');

        const desktop = window.matchMedia('(min-width: 1024px)');
        let sidebarTrigger = null;
        const syncScrollLock = () => {
            const modalOpen = document.querySelector('[data-modal]:not(.hidden)');
            const sidebarOpen = sidebar && !desktop.matches && !sidebar.classList.contains('-translate-x-full');
            document.body.classList.toggle('overflow-hidden', Boolean(modalOpen || sidebarOpen));
        };
        const setSidebarExpanded = (expanded) => {
            document.querySelectorAll('[data-sidebar-open]').forEach((button) => button.setAttribute('aria-expanded', String(expanded)));
            if (sidebar) sidebar.inert = !desktop.matches && !expanded;
        };
        setSidebarExpanded(false);
        const openSidebar = () => {
            sidebarTrigger = document.activeElement;
            sidebar?.classList.remove('-translate-x-full');
            backdrop?.classList.remove('hidden');
            setSidebarExpanded(true);
            syncScrollLock();
            sidebar?.querySelector('[data-sidebar-close]')?.focus();
        };

        const closeSidebar = () => {
            sidebar?.classList.add('-translate-x-full');
            backdrop?.classList.add('hidden');
            setSidebarExpanded(false);
            syncScrollLock();
            if (sidebar?.contains(document.activeElement)) sidebarTrigger?.focus();
        };
        desktop.addEventListener('change', closeSidebar);

        document.querySelectorAll('[data-sidebar-open]').forEach((el) => el.addEventListener('click', openSidebar));
        document.querySelectorAll('[data-sidebar-close]').forEach((el) => el.addEventListener('click', closeSidebar));
        backdrop?.addEventListener('click', closeSidebar);

        /* ------------------------------------------------------------
         * Dropdown menus (topbar user menu)
         * ---------------------------------------------------------- */
        const closeMenus = (except) => {
            document.querySelectorAll('[data-menu]').forEach((menu) => {
                if (menu === except) return;
                menu.querySelector('[data-menu-panel]')?.classList.add('hidden');
                menu.querySelector('[data-menu-toggle]')?.setAttribute('aria-expanded', 'false');
            });
        };

        document.querySelectorAll('[data-menu-toggle]').forEach((toggle) => {
            toggle.addEventListener('click', (event) => {
                event.stopPropagation();
                const menu = toggle.closest('[data-menu]');
                const panel = menu?.querySelector('[data-menu-panel]');
                if (!panel) return;
                closeMenus(menu);
                panel.classList.toggle('hidden');
                toggle.setAttribute('aria-expanded', String(!panel.classList.contains('hidden')));
            });
        });

        document.addEventListener('click', () => closeMenus());

        /* ------------------------------------------------------------
         * Modals
         * ---------------------------------------------------------- */
        const modalTriggers = new WeakMap();
        const focusable = (root) => [...root.querySelectorAll('a[href], button, input, select, textarea, [tabindex]')].filter((el) => !el.disabled && el.tabIndex >= 0 && el.getClientRects().length);
        const openModal = (id) => {
            const modal = document.getElementById(id);
            if (!modal) return;
            modalTriggers.set(modal, document.activeElement);
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            syncScrollLock();
            requestAnimationFrame(() => (modal.querySelector('input:not([type=hidden]), select, textarea') || focusable(modal)[0] || modal).focus());
        };

        const closeModal = (modal) => {
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            syncScrollLock();
            modalTriggers.get(modal)?.focus();
        };

        document.querySelectorAll('[data-modal-open]').forEach((trigger) => {
            trigger.addEventListener('click', (event) => {
                event.preventDefault();
                openModal(trigger.getAttribute('data-modal-open'));
            });
        });

        document.querySelectorAll('[data-modal-close]').forEach((trigger) => {
            trigger.addEventListener('click', (event) => {
                event.preventDefault();
                closeModal(trigger.closest('[data-modal]'));
            });
        });

        document.querySelectorAll('[data-modal]').forEach((modal) => {
            modal.addEventListener('click', (event) => {
                if (event.target === modal) closeModal(modal);
            });
        });

        document.addEventListener('keydown', (event) => {
            const activeModal = [...document.querySelectorAll('[data-modal]:not(.hidden)')].at(-1);
            const focusRoot = activeModal || (!desktop.matches && sidebar && !sidebar.classList.contains('-translate-x-full') ? sidebar : null);
            if (event.key === 'Tab' && focusRoot) {
                const items = focusable(focusRoot);
                const first = items[0];
                const last = items.at(-1);
                if (!first) { event.preventDefault(); focusRoot.focus(); }
                else if (event.shiftKey && (document.activeElement === first || !focusRoot.contains(document.activeElement))) { event.preventDefault(); last.focus(); }
                else if (!event.shiftKey && (document.activeElement === last || !focusRoot.contains(document.activeElement))) { event.preventDefault(); first.focus(); }
            }
            if (event.key !== 'Escape') return;
            closeMenus();
            document.querySelectorAll('[data-modal]:not(.hidden)').forEach(closeModal);
            closeSidebar();
        });

        /* ------------------------------------------------------------
         * Toast auto-dismiss
         * ---------------------------------------------------------- */
        document.querySelectorAll('[data-toast]').forEach((toast) => {
            const remove = () => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-6px)';
                setTimeout(() => toast.remove(), 250);
            };
            toast.querySelector('[data-toast-close]')?.addEventListener('click', remove);
            setTimeout(remove, 6000);
        });

        /* ------------------------------------------------------------
         * Import summary alert dismiss
         * ---------------------------------------------------------- */
        document.querySelectorAll('[data-import-report-close]').forEach((button) => {
            button.addEventListener('click', () => {
                button.closest('[data-import-report]')?.remove();
            });
        });

        /* ------------------------------------------------------------
         * Reusable edit modals — populate fields from data-* attributes
         * ---------------------------------------------------------- */
        document.querySelectorAll('[data-edit]').forEach((trigger) => {
            trigger.addEventListener('click', () => {
                const modal = document.getElementById(trigger.dataset.edit);
                if (!modal) return;

                const form = modal.querySelector('form');
                if (form && trigger.dataset.action) form.setAttribute('action', trigger.dataset.action);

                // data-field-<name> attributes populate the matching form field.
                Object.entries(trigger.dataset).forEach(([key, value]) => {
                    if (!key.startsWith('field')) return;
                    const raw = key.slice(5);
                    const name = raw.charAt(0).toLowerCase() + raw.slice(1);
                    const field = form?.querySelector(`[name="${name}"]`);
                    if (field?.matches('select[data-prerequisite-picker]')) {
                        field.querySelectorAll('[data-legacy-prerequisite]').forEach((option) => option.remove());
                        if (value && ![...field.options].some((option) => option.value === value)) {
                            const option = new Option(value, value);
                            option.dataset.legacyPrerequisite = '';
                            field.add(option);
                        }
                    }
                    if (field) field.value = value ?? '';
                });
                form?.dispatchEvent(new CustomEvent('edit-fields-populated'));

                const title = modal.querySelector('[data-modal-title]');
                if (title && trigger.dataset.title) title.textContent = trigger.dataset.title;

                openModal(trigger.dataset.edit);
            });
        });

        /* Exclude the course itself from the prerequisite dropdown. */
        document.querySelectorAll('[data-prerequisite-picker]').forEach((picker) => {
            const form = picker.closest('form');
            const courseCode = form?.querySelector('[name="course_code"]');
            let originalCode = '';
            const sync = () => {
                [...picker.options].forEach((option) => {
                    const self = [originalCode, courseCode?.value.trim().toLowerCase()]
                        .filter(Boolean).includes(option.value.toLowerCase());
                    option.disabled = self;
                    option.hidden = self;
                    if (self && option.selected) picker.value = '';
                });
            };
            courseCode?.addEventListener('input', sync);
            form?.addEventListener('edit-fields-populated', () => {
                originalCode = courseCode?.value.trim().toLowerCase() || '';
                sync();
            });
            form?.addEventListener('reset', () => requestAnimationFrame(() => {
                originalCode = '';
                sync();
            }));
            sync();
        });

        /* ------------------------------------------------------------
         * Study plan: electives carry no year or semester
         * ---------------------------------------------------------- */
        document.querySelectorAll('form[data-plan-course-form]').forEach((form) => {
            const category = form.querySelector('[name="category"]');
            const placeInTerm = form.querySelector('[name="place_in_term"]');
            const termFields = form.querySelectorAll('[data-term-field]');
            const hint = form.querySelector('[data-term-hint]');

            const syncTermFields = () => {
                // "Place" is an explicit request for a term; every other add is
                // either an elective without one or a normal term placement.
                const ignoreTerm = category?.value === 'elective' && placeInTerm?.value !== '1';

                termFields.forEach((field) => {
                    field.disabled = ignoreTerm;
                    field.required = !ignoreTerm;
                });

                hint?.classList.toggle('hidden', !ignoreTerm);
            };

            category?.addEventListener('change', syncTermFields);
            syncTermFields();

            // The shared modal opener fills the form from data-* attributes
            // after this listener runs, so re-check the fields afterwards.
            document.querySelectorAll('[data-edit="add-plan-course"]').forEach((trigger) => {
                trigger.addEventListener('click', () => setTimeout(syncTermFields, 0));
            });
        });

        /* ------------------------------------------------------------
         * Selective "replace elective" modal
         * ---------------------------------------------------------- */
        document.querySelectorAll('[data-replace-entry]').forEach((trigger) => {
            trigger.addEventListener('click', () => {
                const modal = document.getElementById('replace-elective-modal');
                const form = modal?.querySelector('form');
                if (form) form.setAttribute('action', trigger.getAttribute('data-replace-entry'));
                const label = modal?.querySelector('[data-replace-label]');
                if (label) label.textContent = trigger.dataset.placeholder || '';
                openModal('replace-elective-modal');
            });
        });

        /* ------------------------------------------------------------
         * Live filtering for any [data-filter-target] table/list
         * ---------------------------------------------------------- */
        document.querySelectorAll('[data-filter-input]').forEach((input) => {
            const targetSelector = input.getAttribute('data-filter-input');
            input.addEventListener('input', () => {
                const term = input.value.toLowerCase().trim();
                document.querySelectorAll(`${targetSelector} [data-filter-row]`).forEach((row) => {
                    row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
                });
            });
        });

        /* ------------------------------------------------------------
         * Confirm destructive actions
         * ---------------------------------------------------------- */
        document.querySelectorAll('form[data-confirm]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (!window.confirm(form.getAttribute('data-confirm'))) event.preventDefault();
            });
        });

        /* ------------------------------------------------------------
         * Keep the scroll position across full-page form submissions.
         * Every form that changes something (POST/PUT/PATCH/DELETE) —
         * delete, update, create, import, reset password — remembers
         * where the user was, so the page reload after the redirect
         * does not jump to the top. Forms opted in with data-scroll-keep
         * always remember it. Only the path is compared, so filter
         * query strings may change freely.
         * ---------------------------------------------------------- */
        const SCROLL_KEY = 'pace:scroll-restore';
        const currentPath = window.location.pathname;

        const rememberScroll = () => {
            try {
                sessionStorage.setItem(SCROLL_KEY, JSON.stringify({ url: currentPath, y: window.scrollY }));
            } catch {
                /* storage unavailable — the page simply starts at the top */
            }
        };

        // Re-runnable so content swapped in via AJAX (e.g. the advisor's
        // supervisee panel) keeps the same behaviour as the initial page.
        window.bindDynamicBehaviours = (root = document) => {
            root.querySelectorAll('form').forEach((form) => {
                if (form.dataset.paceScrollKeepBound === '1') return;
                form.dataset.paceScrollKeepBound = '1';

                form.addEventListener('submit', () => {
                    const method = (form.getAttribute('method') || 'GET').toUpperCase();

                    // GET forms (filters, searches) normally start at the top;
                    // they only keep the position when explicitly opted in.
                    if (method === 'GET' && !form.hasAttribute('data-scroll-keep')) return;

                    rememberScroll();
                });
            });

            root.querySelectorAll('[data-auto-submit]').forEach((el) => {
                if (el.dataset.paceAutoSubmitBound === '1') return;
                el.dataset.paceAutoSubmitBound = '1';

                el.addEventListener('change', () => {
                    const form = el.closest('form');
                    rememberScroll();
                    form?.submit();
                });
            });
        };

        window.bindDynamicBehaviours();

        let pending = null;
        try {
            pending = JSON.parse(sessionStorage.getItem(SCROLL_KEY) ?? 'null');
            sessionStorage.removeItem(SCROLL_KEY);
        } catch {
            pending = null;
        }

        if (pending && pending.url === currentPath && Number(pending.y) > 0) {
            const y = Number(pending.y);
            const restore = () => window.scrollTo(0, y);

            // Re-apply once layout and fonts have settled.
            restore();
            requestAnimationFrame(restore);
            window.addEventListener('load', restore, { once: true });
        }
    });
})();
