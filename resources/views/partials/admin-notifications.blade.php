{{-- Lonceng admin tanpa refresh: polling ringan tiap 30 detik (2 menit saat tab tidak dilihat). --}}
<script>
    document.addEventListener('alpine:init', () => {
        const config = @js([
            'url' => route('admin.notifications.index'),
            'counts' => $badges,
            'cursor' => \App\Services\AdminNotifications::cursor(),
            'labels' => collect(\App\Services\AdminNotifications::LABELS)->map(fn ($meta) => ['label' => $meta['label'], 'url' => url($meta['url'])]),
            // Polling berhenti setelah admin diam selama umur session, agar session tetap kedaluwarsa seperti biasa.
            'idleLimitMs' => config('session.lifetime') * 60 * 1000,
        ]);

        const SOUND_KEY = 'pmb.admin.notification-sound';
        const readSound = () => {
            try {
                return localStorage.getItem(SOUND_KEY) !== 'off';
            } catch (error) {
                return true;
            }
        };

        Alpine.store('adminBadges', {
            counts: config.counts,
            cursor: config.cursor,
            sound: readSound(),

            label(key) {
                return this.counts[key] > 99 ? '99+' : String(this.counts[key]);
            },

            items() {
                return Object.entries(config.labels)
                    .filter(([key]) => this.counts[key] > 0)
                    .map(([key, meta]) => ({ key, count: this.label(key), label: meta.label, url: meta.url }));
            },

            toggleSound() {
                this.sound = ! this.sound;
                try {
                    localStorage.setItem(SOUND_KEY, this.sound ? 'on' : 'off');
                } catch (error) {}
            },
        });

        const badges = Alpine.store('adminBadges');
        const baseTitle = document.title;
        let lastActivity = Date.now();
        let lastPoll = Date.now();
        let timer = null;
        let stopped = false;

        ['pointerdown', 'keydown', 'scroll', 'mousemove'].forEach((event) => {
            window.addEventListener(event, () => { lastActivity = Date.now(); }, { passive: true });
        });

        const updateTitle = () => {
            const total = Object.values(badges.counts).reduce((sum, count) => sum + count, 0);
            document.title = total > 0 ? `(${total > 99 ? '99+' : total}) ${baseTitle}` : baseTitle;
        };

        const beep = () => {
            try {
                const context = new (window.AudioContext || window.webkitAudioContext)();
                [880, 1175].forEach((frequency, index) => {
                    const oscillator = context.createOscillator();
                    const gain = context.createGain();
                    const start = context.currentTime + index * 0.18;
                    oscillator.frequency.value = frequency;
                    gain.gain.setValueAtTime(0.15, start);
                    gain.gain.exponentialRampToValueAtTime(0.001, start + 0.25);
                    oscillator.connect(gain).connect(context.destination);
                    oscillator.start(start);
                    oscillator.stop(start + 0.25);
                });
                setTimeout(() => context.close(), 1000);
            } catch (error) {}
        };

        const announce = (events) => {
            if (badges.sound) {
                beep();
            }

            // Jangan menimpa dialog SweetAlert yang sedang terbuka (mis. konfirmasi verifikasi).
            if (window.Swal.isVisible()) {
                return;
            }

            const single = events.length === 1;
            window.Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'info',
                title: single ? events[0].title : `${events.length} notifikasi baru`,
                text: events.slice(0, 3).map((event) => single ? event.text : `${event.title}: ${event.text}`).join('\n'),
                showConfirmButton: false,
                showCloseButton: true,
                timer: 8000,
                timerProgressBar: true,
                customClass: { popup: 'cursor-pointer whitespace-pre-line' },
                didOpen: (popup) => {
                    popup.addEventListener('click', (event) => {
                        if (! event.target.closest('.swal2-close')) {
                            window.location.href = events[0].url;
                        }
                    });
                },
            });
        };

        const poll = async () => {
            if (stopped) {
                return;
            }

            if (Date.now() - lastActivity > config.idleLimitMs) {
                stopped = true;
                return;
            }

            lastPoll = Date.now();

            try {
                const params = new URLSearchParams({ applicant_after: badges.cursor.applicant, payment_after: badges.cursor.payment });
                const response = await fetch(`${config.url}?${params}`, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });

                // Session habis atau akun dinonaktifkan: berhenti, halaman berikutnya akan diarahkan ke login.
                if ([401, 403, 419].includes(response.status)) {
                    stopped = true;
                    return;
                }

                if (response.ok) {
                    const data = await response.json();
                    badges.counts = data.counts;
                    badges.cursor = data.cursor;
                    updateTitle();

                    if (data.events.length > 0) {
                        announce(data.events);
                    }
                }
            } catch (error) {
                // Jaringan putus sementara: coba lagi pada jadwal berikutnya.
            }

            schedule();
        };

        const schedule = () => {
            clearTimeout(timer);
            timer = setTimeout(poll, document.visibilityState === 'visible' ? 30000 : 120000);
        };

        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible' && ! stopped) {
                Date.now() - lastPoll > 10000 ? poll() : schedule();
            }
        });

        updateTitle();
        schedule();
    });
</script>
