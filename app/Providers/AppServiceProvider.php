<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use App\Models\Notification;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
            $event->extendSocialite('adobe', \SocialiteProviders\Adobe\Provider::class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('is-artist', function ($user) {
            return $user->is_artist === 1;
        });
        Gate::define('is-admin', function ($user) {
            return $user->is_admin === 1 || $user->is_admin === 2;
        });
        Gate::define('is-superadmin', function ($user) {
            return $user->is_admin === 2;
        });

        View::composer('*', function ($view) {
            $notifications = [];
            $totalUnreadCount = 0;
            if (Auth::check()) {
                // Get unread notifications for the authenticated user
                $unreadNotifications = Notification::where('status', 'unread')
                    ->where('user_id', Auth::id())
                    ->limit(3)
                    ->orderBy('created_at', 'desc')
                    ->get();

                $totalUnreadCount = $unreadNotifications->count();

                // Fetch read notifications if unread are fewer than 3
                if ($totalUnreadCount < 3) {
                    $readNotifications = Notification::where('status', 'read')
                        ->where('user_id', Auth::id())
                        ->latest()
                        ->limit(3 - $totalUnreadCount)
                        ->orderBy('created_at', 'desc')
                        ->get();

                    $notifications = $unreadNotifications->merge($readNotifications);
                } else {
                    $notifications = $unreadNotifications;
                }
            }

            // Pass data to all views
            $view->with([
                'notifications' => $notifications,
                'totalUnreadCount' => $totalUnreadCount,
            ]);
        });
    }
}
