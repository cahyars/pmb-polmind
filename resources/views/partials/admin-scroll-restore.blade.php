{{--
    Setelah aksi admin (form POST/PATCH/DELETE) yang kembali ke halaman yang sama, halaman
    discroll ke form yang tadi diklik, dan pesannya ditampilkan sebagai toast karena banner
    pesan ada di atas halaman. Pesan sekali tampil (token API, PIN baru) tetap dibaca di atas.
--}}
<script>
    (() => {
        const KEY = 'pmb.admin.scroll-after-action';
        const currentUrl = () => location.pathname + location.search;
        const formsWithAction = (action) => [...document.querySelectorAll('form')].filter((form) => form.getAttribute('action') === action);

        document.addEventListener('submit', (event) => {
            const form = event.target;

            if ((form.getAttribute('method') || 'get').toLowerCase() === 'get' || form.target === '_blank') {
                return;
            }

            const action = form.getAttribute('action');

            try {
                sessionStorage.setItem(KEY, JSON.stringify({
                    url: currentUrl(),
                    y: window.scrollY,
                    action,
                    index: formsWithAction(action).indexOf(form),
                    top: form.getBoundingClientRect().top,
                    at: Date.now(),
                }));
            } catch (error) {}
        }, true);

        let saved = null;

        try {
            saved = JSON.parse(sessionStorage.getItem(KEY));
            sessionStorage.removeItem(KEY);
        } catch (error) {}

        const flash = @js([
            'keepTop' => session()->hasAny(['generated_token', 'login_reset', 'walk_in_account']),
            'success' => session('success'),
            'error' => $errors->first() ?: null,
        ]);

        if (! saved || saved.url !== currentUrl() || Date.now() - saved.at > 60000 || flash.keepTop) {
            return;
        }

        const restore = () => {
            // Form yang sama (misal tombol aksi di baris yang sama) diletakkan di posisi layar semula;
            // jika barisnya sudah hilang dari daftar, kembali ke posisi scroll semula.
            const form = formsWithAction(saved.action)[saved.index];
            const y = form && form.offsetParent !== null
                ? form.getBoundingClientRect().top + window.scrollY - saved.top
                : saved.y;

            window.scrollTo({ top: Math.max(0, y), behavior: 'instant' });

            if ((flash.success || flash.error) && ! window.Swal.isVisible()) {
                window.Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: flash.error ? 'error' : 'success',
                    title: flash.error || flash.success,
                    showConfirmButton: false,
                    showCloseButton: true,
                    timer: flash.error ? 8000 : 4000,
                    timerProgressBar: true,
                });
            }
        };

        document.addEventListener('alpine:initialized', () => requestAnimationFrame(restore));
    })();
</script>
