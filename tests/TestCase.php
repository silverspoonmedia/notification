<?php

namespace App\Domain\Notification\Tests;

use App\Domain\Notification\NotificationServiceProvider;
use App\Support\Testing\PackageTestCase;

/**
 * Notification's standalone harness: the kernel plus this package's provider
 * and migrations. Tests that exercise a host HTTP route live in
 * `tests/Domain/Core/Notification/Feature/`, not here.
 */
abstract class TestCase extends PackageTestCase
{
    protected function getPackageProviders($app): array
    {
        return [...parent::getPackageProviders($app), NotificationServiceProvider::class];
    }

    protected function runExtraDatabaseMigrations(): void
    {
        $this->runPackageMigrations(dirname(__DIR__).'/database/migrations');
    }
}
