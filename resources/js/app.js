import './bootstrap';

import Alpine from 'alpinejs';
import Swal from 'sweetalert2';

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

Alpine.start();
