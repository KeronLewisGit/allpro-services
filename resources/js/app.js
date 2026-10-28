import Alpine from 'alpinejs';

document.documentElement.classList.add('js'); // also set in <head>; kept for safety

const motionOK = () => matchMedia('(prefers-reduced-motion: no-preference)').matches;

/*
 * Theme toggle. The initial theme is set by an inline script in <head> to avoid a flash.
 * Where the View Transitions API exists (and motion is allowed) the new theme grows as a
 * circle from the button that was pressed.
 */
window.toggleTheme = (event) => {
    const root = document.documentElement;
    const current = root.dataset.theme || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    const next = current === 'dark' ? 'light' : 'dark';
    const apply = () => {
        root.dataset.theme = next;
        try { localStorage.setItem('theme', next); } catch (e) { /* storage blocked: the choice lasts this page only */ }
    };
    if (!document.startViewTransition || !motionOK()) return apply();
    const box = event?.currentTarget?.getBoundingClientRect?.();
    root.style.setProperty('--wx', box ? `${box.left + box.width / 2}px` : '100%');
    root.style.setProperty('--wy', box ? `${box.top + box.height / 2}px` : '0px');
    root.classList.add('theme-wipe');
    document.startViewTransition(apply).finished.finally(() => root.classList.remove('theme-wipe'));
};

/* Run once when an element first scrolls into view. */
const whenSeen = (el, fn, margin = '0px 0px -12% 0px') => {
    if (!('IntersectionObserver' in window)) return fn();
    const io = new IntersectionObserver((entries) => {
        if (entries.some((e) => e.isIntersecting)) { io.disconnect(); fn(); }
    }, { rootMargin: margin });
    io.observe(el);
};

/* Count-up for sourced figures. The final number is already in the HTML. */
const countUp = () => {
    if (!motionOK()) return;
    document.querySelectorAll('[data-count]').forEach((el) => {
        const to = Number(el.dataset.count);
        const from = Number(el.dataset.from ?? 0);
        if (!Number.isFinite(to)) return;
        el.textContent = String(from);
        whenSeen(el, () => {
            const start = performance.now();
            const dur = 1400;
            const tick = (now) => {
                const t = Math.min(1, (now - start) / dur);
                const eased = 1 - (1 - t) ** 3;
                el.textContent = String(Math.round(from + (to - from) * eased));
                if (t < 1) requestAnimationFrame(tick);
            };
            requestAnimationFrame(tick);
        });
    });
};

/* Scroll reveal */
const reveal = () => {
    const items = document.querySelectorAll('[data-reveal]');
    if (!('IntersectionObserver' in window)) {
        items.forEach((el) => el.classList.add('is-visible'));
        return;
    }
    const io = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                io.unobserve(entry.target);
            }
        });
    }, { rootMargin: '0px 0px -8% 0px' });
    items.forEach((el) => io.observe(el));
};

/* Submit buttons: one press only, with a visible "Sending…" state. */
Alpine.data('sendForm', () => ({
    submitting: false,
    submit(e) {
        if (!e.target.checkValidity()) return;
        this.submitting = true;
    },
}));

/* Column chart hover: one tooltip that follows the focused or hovered column. */
Alpine.data('columnChart', () => ({
    tip: null,
    show(el) {
        const box = el.getBoundingClientRect();
        const host = this.$root.getBoundingClientRect();
        this.tip = { label: el.dataset.label, value: el.dataset.value, x: box.left - host.left + box.width / 2, y: box.top - host.top };
    },
    hide() { this.tip = null; },
}));

/* Dashboard chrome: density, mobile nav, "/" to search. */
Alpine.data('adminShell', () => ({
    nav: false,
    density: (() => { try { return localStorage.getItem('density') || 'comfortable'; } catch (e) { return 'comfortable'; } })(),
    init() {
        this.$el.dataset.density = this.density;
        window.addEventListener('keydown', (e) => {
            const typing = ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName);
            if (e.key === '/' && !typing) {
                const search = document.querySelector('[data-search]');
                if (search) { e.preventDefault(); search.focus(); }
            }
        });
    },
    toggleDensity() {
        this.density = this.density === 'compact' ? 'comfortable' : 'compact';
        this.$el.dataset.density = this.density;
        try { localStorage.setItem('density', this.density); } catch (e) { /* ignore */ }
    },
}));

/* Home: the services switcher (ARIA tabs). Arrow keys move between services. */
Alpine.data('svcSwitch', (keys) => ({
    keys,
    active: keys[0],
    // Panels are only hidden once this runs, so a failed script never leaves them all hidden.
    init() { this.$el.classList.add('is-ready'); },
    select(k) { this.active = k; },
    step(d) {
        const i = (this.keys.indexOf(this.active) + d + this.keys.length) % this.keys.length;
        this.select(this.keys[i]);
        this.$nextTick(() => document.getElementById(`tab-${this.active}`)?.focus());
    },
}));

/*
 * Home: the coverage map. The office routes draw out from Oranmore once the map is seen; then
 * the regions take turns lighting up until someone hovers, focuses or taps one.
 */
Alpine.data('irelandMap', (keys) => ({
    keys,
    active: keys[0],
    drawn: false,
    timer: null,
    init() {
        whenSeen(this.$el, () => {
            this.drawn = true;
            if (!motionOK()) return;
            this.timer = setInterval(() => {
                if (document.hidden) return;
                this.active = this.keys[(this.keys.indexOf(this.active) + 1) % this.keys.length];
            }, 3200);
        }, '0px 0px -20% 0px');
    },
    pick(k) {
        this.active = k;
        clearInterval(this.timer);
    },
}));

/*
 * Long forms (quote, application): a step list that follows the section on screen, ticks off
 * each section once its required answers are valid, and a progress bar. Purely a guide: the
 * form works the same without it.
 */
Alpine.data('formSteps', () => ({
    current: null,
    done: {},
    pct: 0,
    init() {
        const form = this.$root.querySelector('form') ?? this.$root;
        this.sections = [...form.querySelectorAll('fieldset[data-step]')];
        const check = () => {
            const state = {};
            this.sections.forEach((fs) => {
                if (fs.offsetParent === null) { state[fs.dataset.step] = null; return; }
                const fields = [...fs.querySelectorAll('input, select, textarea')].filter((f) => !f.disabled && f.type !== 'hidden' && f.offsetParent !== null);
                const required = fields.filter((f) => f.required);
                const groups = [...fs.querySelectorAll('[data-need-one]')];
                const groupsOk = groups.every((g) => g.querySelector('input:checked'));
                const filled = required.every((f) => f.checkValidity()) && groupsOk;
                const touched = fields.some((f) => (f.type === 'checkbox' || f.type === 'radio' ? f.checked : f.value !== ''));
                state[fs.dataset.step] = filled && (required.length > 0 || groups.length > 0 ? true : touched);
            });
            this.done = state;
            const live = Object.values(state).filter((v) => v !== null);
            this.pct = live.length ? live.filter(Boolean).length / live.length : 0;
        };
        form.addEventListener('input', check);
        form.addEventListener('change', check);
        check();
        if ('IntersectionObserver' in window) {
            const io = new IntersectionObserver((entries) => {
                entries.forEach((e) => { if (e.isIntersecting) this.current = e.target.dataset.step; });
            }, { rootMargin: '-35% 0px -55% 0px' });
            this.sections.forEach((fs) => io.observe(fs));
        }
    },
}));

/*
 * Client services: the SLA clocks. Each ring shows the response window for a priority and,
 * once seen, counts down in real time as an example of what a client sees on their link.
 */
Alpine.data('slaClock', (hours) => ({
    total: hours * 3600,
    left: hours * 3600,
    get label() {
        const h = Math.floor(this.left / 3600);
        const m = Math.floor((this.left % 3600) / 60);
        const sec = this.left % 60;
        return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(sec).padStart(2, '0')}`;
    },
    get offset() { return 289 * (1 - this.left / this.total); },
    init() {
        if (!motionOK()) return;
        whenSeen(this.$el, () => {
            // Start a little way into the window so the ring is visibly moving.
            this.left = Math.round(this.total * 0.82);
            setInterval(() => { if (this.left > 0) this.left -= 1; }, 1000);
        });
    },
}));

window.Alpine = Alpine;
Alpine.start();
const ready = () => { reveal(); countUp(); };
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ready);
else ready();
