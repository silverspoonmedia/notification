<?php

namespace App\Domain\Notification;

use App\Domain\Notification\Events\ActivityNotificationEvent;
use App\Domain\Notification\Listeners\SendActivityNotification;
use App\Domain\Notification\Repositories\Contracts\NotificationRepositoryInterface;
use App\Domain\Notification\Repositories\Contracts\NotificationTypeSettingRepositoryInterface;
use App\Domain\Notification\Repositories\Eloquent\NotificationRepository;
use App\Domain\Notification\Repositories\Eloquent\NotificationTypeSettingRepository;
use App\Domain\Notification\Services\NotificationService;
use App\Domain\Notification\Services\TypeNotificationDispatcher;
use App\Support\Notifications\Contracts\NotificationDispatcher;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/notifications.php', 'notifications');

        $this->app->bind(NotificationRepositoryInterface::class, NotificationRepository::class);
        $this->app->bind(NotificationTypeSettingRepositoryInterface::class, NotificationTypeSettingRepository::class);

        // Kernel seam: a packaged domain emits by type value and never imports
        // this enum. `silverspoon/billing` calls it only when bound.
        $this->app->bind(NotificationDispatcher::class, function ($app): NotificationDispatcher {
            return new TypeNotificationDispatcher($app->make(NotificationService::class));
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Plain (non-namespaced) lang path: FileLoader::loadPaths() resolves
        // "{path}/{locale}/{group}.php", so the path is the parent of the locale
        // directories. Keys stay `api/notifications.*` — no key moves.
        $this->app['translator']->getLoader()->addPath(__DIR__.'/../resources/lang');

        // The hero event every feature dispatches: domain code describes WHAT
        // happened and WHO should hear it; the listener persists and fans out.
        Event::listen(ActivityNotificationEvent::class, SendActivityNotification::class);
    }
}
