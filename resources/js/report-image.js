import { toPng } from 'html-to-image';

// Tombol [data-download-report="<id elemen>"] menyimpan elemen itu sebagai satu gambar PNG.
document.querySelectorAll('[data-download-report]').forEach((button) => {
    button.addEventListener('click', async () => {
        const target = document.getElementById(button.dataset.downloadReport);
        const label = button.querySelector('[data-label]');
        const original = label?.textContent;

        if (! target) return;

        button.disabled = true;
        if (label) label.textContent = 'Menyiapkan gambar...';

        try {
            // Font laporan memakai Arial bawaan sistem, jadi font web tidak perlu disematkan.
            const dataUrl = await toPng(target, { pixelRatio: 2, backgroundColor: '#ffffff', skipFonts: true });
            const link = document.createElement('a');

            link.download = button.dataset.filename || 'laporan.png';
            link.href = dataUrl;
            link.click();
        } catch (error) {
            console.error(error);
            window.Swal?.fire({ icon: 'error', title: 'Gambar gagal dibuat', text: 'Muat ulang halaman lalu coba lagi.' });
        } finally {
            button.disabled = false;
            if (label) label.textContent = original;
        }
    });
});
