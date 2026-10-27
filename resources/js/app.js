import './bootstrap';
import Alpine from 'alpinejs';

/**
 * Montant FCFA : chiffres groupés par milliers pendant la saisie.
 * @param {string} value
 * @returns {string}
 */
function groupThousands(value) {
    const digits = String(value).replace(/\D/g, '');
    return digits.replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
}

Alpine.data('moneyInput', (initial = '') => ({
    display: groupThousands(initial),
    format(event) {
        this.display = groupThousands(event.target.value);
    },
}));

/** Recherche globale Ctrl + K (DASH-06). */
Alpine.data('commandPalette', (endpoint) => ({
    open: false,
    query: '',
    results: [],
    active: 0,
    timer: null,
    toggle() {
        this.open = !this.open;
        if (this.open) {
            this.$nextTick(() => this.$refs.search.focus());
        }
    },
    close() {
        this.open = false;
        this.query = '';
        this.results = [];
    },
    search() {
        clearTimeout(this.timer);
        if (this.query.trim().length < 2) {
            this.results = [];
            return;
        }
        this.timer = setTimeout(async () => {
            try {
                const response = await window.axios.get(endpoint, { params: { q: this.query } });
                this.results = response.data.data ?? [];
                this.active = 0;
            } catch (error) {
                this.results = [];
            }
        }, 180);
    },
    move(step) {
        if (this.results.length === 0) return;
        this.active = (this.active + step + this.results.length) % this.results.length;
    },
    go() {
        const target = this.results[this.active];
        if (target) window.location.href = target.url;
    },
}));

/** Saisie OTP en 6 cases avec collage et lecture automatique du SMS. */
Alpine.data('otpInput', (length = 6) => ({
    digits: Array(length).fill(''),
    get value() {
        return this.digits.join('');
    },
    input(index, event) {
        const char = event.target.value.replace(/\D/g, '').slice(-1);
        this.digits[index] = char;
        event.target.value = char;
        if (char && index < length - 1) this.$refs[`d${index + 1}`].focus();
        if (this.value.length === length) this.$nextTick(() => this.$root.closest('form').requestSubmit());
    },
    back(index, event) {
        if (!this.digits[index] && index > 0) {
            this.$refs[`d${index - 1}`].focus();
            event.preventDefault();
        }
    },
    paste(event) {
        const pasted = (event.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, length);
        if (!pasted) return;
        event.preventDefault();
        pasted.split('').forEach((char, i) => (this.digits[i] = char));
        if (pasted.length === length) this.$nextTick(() => this.$root.closest('form').requestSubmit());
    },
}));

/** Compte à rebours avant renvoi d'un code. */
Alpine.data('countdown', (seconds) => ({
    left: seconds,
    init() {
        const tick = setInterval(() => {
            this.left = Math.max(0, this.left - 1);
            if (this.left === 0) clearInterval(tick);
        }, 1000);
    },
    get label() {
        const minutes = Math.floor(this.left / 60);
        const rest = String(this.left % 60).padStart(2, '0');
        return `${minutes}:${rest}`;
    },
}));

/*
 * Lignes de tableau cliquables et confirmations : délégation d'événements
 * (aucun gestionnaire en ligne, compatible avec la politique CSP stricte).
 */
document.addEventListener('click', (event) => {
    const row = event.target.closest('[data-href]');
    if (!row || event.target.closest('a, button, input, select, textarea, label')) return;
    if (event.ctrlKey || event.metaKey) {
        window.open(row.dataset.href, '_blank');
    } else {
        window.location.href = row.dataset.href;
    }
});

document.addEventListener('submit', (event) => {
    const message = event.target.dataset.confirm;
    if (message && !window.confirm(message)) event.preventDefault();
});

window.Alpine = Alpine;
Alpine.start();
