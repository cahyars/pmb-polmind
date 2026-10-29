<?php

namespace Tests;

use App\Models\RegistrationPath;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Cache jalur statis tidak ikut di-rollback antar test.
        RegistrationPath::flushCache();
    }
}
