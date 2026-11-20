<?php

namespace App\Domain\Notification\Tests\Unit\Config;

use App\Domain\Notification\Tests\TestCase;

class NotificationsConfigTest extends TestCase
{
    public function test_polling_interval_defaults_to_sixty_seconds(): void
    {
        $this->assertSame(60, config('notifications.polling_interval'));
    }

    public function test_polling_interval_can_be_overridden(): void
    {
        config(['notifications.polling_interval' => 30]);

        $this->assertSame(30, config('notifications.polling_interval'));
    }
}
