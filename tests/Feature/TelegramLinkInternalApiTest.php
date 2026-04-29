<?php

namespace Tests\Feature;

use Tests\TestCase;

final class TelegramLinkInternalApiTest extends TestCase
{
    public function test_claim_requires_bearer_token(): void
    {
        $this->postJson('/api/internal/telegram/link/claim', [])->assertForbidden();
    }
}
