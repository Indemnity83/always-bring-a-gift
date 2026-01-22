<?php

namespace Tests;

use PHPUnit\Framework\Attributes\Before;

class ReminderTestCase extends TestCase
{
    /**
     * Skip tests early when the required sqlite driver is not available.
     */
    #[Before]
    protected function requireSqlite(): void
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite extension is required for reminder tests.');
        }
    }
}
