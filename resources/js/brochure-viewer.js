import * as pdfjsLib from 'pdfjs-dist/legacy/build/pdf.mjs';
import workerUrl from 'pdfjs-dist/legacy/build/pdf.worker.min.mjs?worker&url';

/**
 * Preview brosur PDF dengan PDF.js agar tampil juga di browser HP yang tidak bisa
 * menampilkan PDF di dalam halaman. Tiap halaman diberi tempat sesuai rasio aslinya,
 * lalu baru digambar saat mendekati layar supaya brosur panjang tetap ringan.
 */
// Worker dibundel Vite sebagai .js agar tidak bergantung pada MIME type .mjs di server.
pdfjsLib.GlobalWorkerOptions.workerSrc = workerUrl;

const viewer = document.querySelector('[data-pdf-viewer]');

if (viewer) {
    render(viewer);
}

async function render(viewer) {
    const status = viewer.querySelector('[data-status]');
    const pages = viewer.querySelector('[data-pages]');

    try {
        const pdf = await pdfjsLib.getDocument({ url: viewer.dataset.src, disableRange: true, disableStream: true }).promise;

        document.querySelectorAll('[data-page-count]').forEach((element) => {
            element.textContent = ` · ${pdf.numPages} halaman`;
        });

        const observer = new IntersectionObserver((entries) => {
            entries.filter((entry) => entry.isIntersecting).forEach((entry) => {
                observer.unobserve(entry.target);
                drawPage(pdf, entry.target);
            });
        }, { rootMargin: '600px 0px' });

        for (let number = 1; number <= pdf.numPages; number++) {
            const page = await pdf.getPage(number);
            const { width, height } = page.getViewport({ scale: 1 });

            const frame = document.createElement('div');
            frame.className = 'relative overflow-hidden rounded-lg bg-white shadow-md ring-1 ring-slate-200';
            frame.innerHTML = '<span data-spinner class="absolute left-1/2 top-1/2 -ml-4 -mt-4 h-8 w-8 animate-spin rounded-full border-4 border-blue-100 border-t-polmind-blue" aria-hidden="true"></span>';
            frame.style.aspectRatio = `${width} / ${height}`;
            frame.dataset.page = number;
            frame.setAttribute('role', 'img');
            frame.setAttribute('aria-label', `Halaman ${number} dari ${pdf.numPages}`);

            pages.append(frame);
            observer.observe(frame);
        }

        status.remove();
    } catch (error) {
        console.error(error);
        status.replaceWith(viewer.querySelector('template[data-error]').content.cloneNode(true));
    }
}

async function drawPage(pdf, frame) {
    const page = await pdf.getPage(Number(frame.dataset.page));
    // Mengikuti kerapatan layar (maks. 2x): cukup tajam di HP tanpa membuat gambar besar lambat digambar.
    const outputScale = Math.min(window.devicePixelRatio || 1, 2);
    const viewport = page.getViewport({ scale: frame.clientWidth / page.getViewport({ scale: 1 }).width });

    const canvas = document.createElement('canvas');
    canvas.width = Math.floor(viewport.width * outputScale);
    canvas.height = Math.floor(viewport.height * outputScale);
    canvas.className = 'block h-full w-full';

    await page.render({
        canvas,
        viewport,
        transform: outputScale !== 1 ? [outputScale, 0, 0, outputScale, 0, 0] : null,
    }).promise;

    frame.querySelector('[data-spinner]')?.remove();
    frame.append(canvas);
}
