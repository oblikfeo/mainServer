<?php

namespace App\Console\Commands;

use App\Services\TestKeys\TestKeyManager;
use Illuminate\Console\Command;

class TestKeysCleanupCommand extends Command
{
    protected $signature = 'test-keys:cleanup';

    protected $description = 'Удалить просроченные тестовые ключи (панель + БД)';

    public function handle(TestKeyManager $manager): int
    {
        $n = $manager->cleanupExpired();
        $this->info("Снято ключей: {$n}");

        return self::SUCCESS;
    }
}

