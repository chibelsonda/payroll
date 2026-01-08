<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Dotenv\Dotenv;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        // Load .env.testing if it exists (for database configuration)
        $envTesting = __DIR__.'/../.env.testing';
        if (file_exists($envTesting)) {
            $dotenv = Dotenv::createImmutable(dirname($envTesting), '.env.testing');
            $dotenv->load();
        }

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
