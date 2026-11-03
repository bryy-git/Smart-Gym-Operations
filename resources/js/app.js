import './bootstrap';

const initializePasswordToggles = () => {
    document.querySelectorAll('input[type="password"]').forEach((input) => {
        const parent = input.parentElement;

        if (!parent) {
            return;
        }

        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'password-toggle';
        toggle.innerHTML = '<svg class="password-toggle-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle><path class="password-toggle-slash" d="m3 3 18 18"></path></svg>';
        toggle.setAttribute('aria-label', 'Show password');
        toggle.setAttribute('aria-pressed', 'false');
        toggle.title = 'Show password';

        const wrapper = document.createElement('div');
        wrapper.className = 'password-control';
        parent.insertBefore(wrapper, input);
        wrapper.append(input, toggle);

        toggle.addEventListener('click', () => {
            const showPassword = input.type === 'password';
            input.type = showPassword ? 'text' : 'password';
            toggle.setAttribute('aria-label', showPassword ? 'Hide password' : 'Show password');
            toggle.setAttribute('aria-pressed', String(showPassword));
            toggle.title = showPassword ? 'Hide password' : 'Show password';
            toggle.classList.toggle('is-visible', showPassword);
        });
    });
};

const initializeSiteNavigation = () => {
    const menuButton = document.querySelector('.site-menu-toggle');
    const menuLinks = document.querySelector('#site-links');

    if (!menuButton || !menuLinks) {
        return;
    }

    const setMenuExpanded = (expanded) => {
        menuButton.setAttribute('aria-expanded', String(expanded));
        menuButton.setAttribute('aria-label', expanded ? 'Close navigation' : 'Open navigation');
        menuLinks.classList.toggle('is-open', expanded);
    };

    menuButton.addEventListener('click', () => {
        const expanded = menuButton.getAttribute('aria-expanded') === 'true';
        setMenuExpanded(!expanded);
    });

    menuLinks.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            setMenuExpanded(false);
        });
    });
};

const initializeSidebarAccordions = () => {
    document.querySelectorAll('nav').forEach((navigation) => {
        const groups = [...navigation.querySelectorAll('details[data-sidebar-group]')];

        if (groups.length === 0) {
            return;
        }

        const closeGroup = (group) => {
            if (!group.open || group.dataset.sidebarClosing === 'true') {
                return;
            }

            const panel = group.querySelector('.sidebar-group-links');
            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            if (!panel || prefersReducedMotion || typeof panel.animate !== 'function') {
                group.open = false;
                return;
            }

            group.dataset.sidebarClosing = 'true';
            const animation = panel.animate(
                [
                    { height: `${panel.getBoundingClientRect().height}px`, opacity: 1, transform: 'translateY(0)' },
                    { height: '0px', opacity: 0, transform: 'translateY(-3px)' },
                ],
                { duration: 160, easing: 'ease-out' },
            );
            animation.addEventListener('finish', () => {
                group.open = false;
                delete group.dataset.sidebarClosing;
            }, { once: true });
        };

        const initiallyExpanded = groups.find((group) => group.open);
        if (initiallyExpanded) {
            groups.forEach((group) => {
                if (group !== initiallyExpanded) {
                    group.open = false;
                }
            });
        }

        groups.forEach((group) => {
            group.querySelector('summary')?.addEventListener('click', (event) => {
                if (group.open) {
                    event.preventDefault();
                    closeGroup(group);
                    return;
                }

                groups.forEach(closeGroup);
            });

            group.addEventListener('toggle', () => {
                if (group.open) {
                    groups.forEach((otherGroup) => {
                        if (otherGroup !== group) {
                            closeGroup(otherGroup);
                        }
                    });
                }
            });
        });
    });
};

const initializeMobileSidebar = () => {
    document.querySelectorAll('.mobile-sidebar-toggle').forEach((toggle) => {
        const menu = document.getElementById(toggle.getAttribute('aria-controls') || '');
        const mobileViewport = window.matchMedia('(max-width: 850px)');

        if (!(menu instanceof HTMLElement)) {
            return;
        }

        const setExpanded = (expanded) => {
            menu.hidden = !expanded;
            toggle.setAttribute('aria-expanded', String(expanded));
            toggle.setAttribute('aria-label', expanded ? 'Close navigation' : 'Open navigation');
        };

        toggle.addEventListener('click', () => {
            setExpanded(toggle.getAttribute('aria-expanded') !== 'true');
        });
        menu.querySelectorAll('nav a').forEach((link) => {
            link.addEventListener('click', () => {
                setExpanded(false);
            });
        });
        mobileViewport.addEventListener('change', ({ matches }) => {
            if (!matches) {
                setExpanded(false);
            }
        });
    });
};

const initializeMotion = () => {
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (prefersReducedMotion || !('IntersectionObserver' in window)) {
        return;
    }

    document.documentElement.classList.add('motion-ready');

    const revealElements = document.querySelectorAll(
        '.hero-copy, .hero-art, .about-image-wrap, .about-copy, .section-heading, .feature-card, .plan-card, .cta-panel, .contact-row, .admin-area .page-heading, .admin-area .card'
    );

    const observer = new IntersectionObserver((entries, currentObserver) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                currentObserver.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.12,
        rootMargin: '0px 0px -36px 0px',
    });

    revealElements.forEach((element) => {
        element.classList.add('reveal-on-scroll');
        observer.observe(element);
    });
};

const initializeFormFeedback = () => {
    document.querySelectorAll('.js-loading-form').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.getAttribute('aria-busy') === 'true') {
                event.preventDefault();
                return;
            }

            const submitButton = event.submitter || form.querySelector('button[type="submit"]');

            if (!submitButton) {
                return;
            }

            submitButton.dataset.originalLabel = submitButton.textContent.trim();
            submitButton.textContent = submitButton.dataset.loadingLabel || 'Please wait...';
            submitButton.disabled = true;
            submitButton.setAttribute('aria-live', 'polite');
            form.setAttribute('aria-busy', 'true');
        });
    });

    document.querySelectorAll('#validation-summary').forEach((summary) => {
        summary.focus();
    });

    window.addEventListener('pageshow', () => {
        document.querySelectorAll('.js-loading-form[aria-busy="true"]').forEach((form) => {
            const submitButton = form.querySelector('button[type="submit"]');

            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = submitButton.dataset.originalLabel || submitButton.textContent;
                submitButton.removeAttribute('aria-live');
                delete submitButton.dataset.originalLabel;
            }

            form.removeAttribute('aria-busy');
        });
    });
};

const initializeApplication = () => {
    initializePasswordToggles();
    initializeSiteNavigation();
    initializeSidebarAccordions();
    initializeMobileSidebar();
    initializeFormFeedback();
    initializeMotion();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeApplication, { once: true });
} else {
    initializeApplication();
}
