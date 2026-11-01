<?php

namespace App\Providers;

use App\Models\Applicant;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->logAuthentication();
    }

    /**
     * Login, logout, dan login gagal masuk Log Aktivitas. Login As dicatat tersendiri oleh LoginAsController.
     */
    private function logAuthentication(): void
    {
        $place = fn (string $guard) => $guard === 'web' ? 'panel admin' : 'portal camaba';
        $actor = fn ($user) => $user instanceof User ? ActivityLogger::userActor($user) : ActivityLogger::applicantActor($user);

        Event::listen(Login::class, function (Login $event) use ($place, $actor) {
            if (request()->routeIs('admin.login-as.store')) {
                return;
            }

            ActivityLogger::record('auth.login', 'Login ke ' . $place($event->guard), [
                'actor' => $actor($event->user),
                'applicant_id' => $event->user instanceof Applicant ? $event->user->id : null,
            ]);
        });

        Event::listen(Logout::class, function (Logout $event) use ($place, $actor) {
            if ($event->user) {
                ActivityLogger::record('auth.logout', 'Logout dari ' . $place($event->guard), [
                    'actor' => $actor($event->user),
                    'applicant_id' => $event->user instanceof Applicant ? $event->user->id : null,
                ]);
            }
        });

        Event::listen(Failed::class, function (Failed $event) use ($place) {
            ActivityLogger::record('auth.failed', 'Login gagal ke ' . $place($event->guard), [
                'actor' => [
                    'type' => 'guest',
                    // Email terdaftar tetapi password salah: kaitkan ke akunnya agar muncul di log akun itu.
                    'id' => $event->user?->getAuthIdentifier(),
                    'name' => (string) ($event->credentials['email'] ?? '-'),
                    'role' => $event->guard === 'web' ? 'Panel admin' : 'Portal camaba',
                ],
            ]);
        });
    }
}
