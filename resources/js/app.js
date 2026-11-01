import './bootstrap';

import Alpine from 'alpinejs';
import Swal from 'sweetalert2';
import flatpickr from 'flatpickr';
import { Indonesian } from 'flatpickr/dist/l10n/id.js';
import 'flatpickr/dist/flatpickr.min.css';

window.Alpine = Alpine;
window.Swal = Swal;

// Pratinjau berkas (gambar/PDF) dalam modal, tanpa membuka tab baru.
Alpine.store('preview', {
    open: false,
    url: '',
    name: '',
    type: 'other',

    show(url, name = '') {
        const extension = (name || url).split('?')[0].split('.').pop().toLowerCase();

        this.url = url;
        this.name = name;
        this.type = extension === 'pdf'
            ? 'pdf'
            : (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension) ? 'image' : 'other');
        this.open = true;
        document.body.classList.add('overflow-hidden');
    },

    close() {
        this.open = false;
        this.url = '';
        document.body.classList.remove('overflow-hidden');
    },
});

// Isian tanggal tampil dd/mm/yyyy di semua browser (bisa diketik langsung); nilai yang dikirim tetap yyyy-mm-dd.
window.enhanceDateInput = (input) => {
    if (input._flatpickr) {
        return;
    }

    flatpickr(input, {
        locale: Indonesian,
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd/m/Y',
        allowInput: true,
        disableMobile: true,
        minDate: input.min || null,
        maxDate: input.max || null,
        onReady: (selectedDates, dateString, instance) => {
            instance.altInput.placeholder = 'dd/mm/yyyy';
            instance.altInput.autocomplete = 'off';

            // Label tetap memfokuskan isian yang terlihat.
            if (input.id) {
                instance.altInput.id = input.id;
                input.removeAttribute('id');
            }
        },
    });
};

const enhanceDateInputs = (root) => root.querySelectorAll?.('input[type="date"]').forEach(window.enhanceDateInput);

Alpine.start();

enhanceDateInputs(document);

// Isian tanggal yang muncul belakangan (baris cicilan dari x-for Alpine) ikut diubah.
new MutationObserver((mutations) => mutations.forEach((mutation) => mutation.addedNodes.forEach((node) => {
    if (node.nodeType !== Node.ELEMENT_NODE) {
        return;
    }

    if (node.matches('input[type="date"]')) {
        window.enhanceDateInput(node);
    } else {
        enhanceDateInputs(node);
    }
}))).observe(document.body, { childList: true, subtree: true });
