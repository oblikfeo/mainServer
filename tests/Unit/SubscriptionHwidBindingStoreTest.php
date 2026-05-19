<?php

namespace Tests\Unit;

use App\Services\Subscription\SubscriptionHwidBindingStore;
use Tests\TestCase;

final class SubscriptionHwidBindingStoreTest extends TestCase
{
    public function test_drops_previous_binding_with_same_ip_and_type(): void
    {
        $old = hash('sha256', 'hwid-old');
        $new = hash('sha256', 'hwid-new');
        $hashes = [$old];
        $meta = [
            $old => ['type' => 'iOS', 'label' => 'iOS', 'ip' => '1.2.3.4', 'seen_at' => ''],
        ];

        [$hashes, $meta] = SubscriptionHwidBindingStore::dropSameIpAndType($hashes, $meta, '1.2.3.4', 'iOS');

        $this->assertSame([], $hashes);
        $this->assertArrayNotHasKey($old, $meta);

        $hashes[] = $new;
        $meta[$new] = ['type' => 'iOS', 'label' => 'iPhone 14', 'ip' => '1.2.3.4', 'seen_at' => ''];

        $this->assertCount(1, $hashes);
        $this->assertSame('iPhone 14', $meta[$new]['label']);
    }

    public function test_keeps_windows_and_ios_on_same_ip(): void
    {
        $win = hash('sha256', 'win');
        $ios = hash('sha256', 'ios');
        $hashes = [$win, $ios];
        $meta = [
            $win => ['type' => 'Windows', 'label' => 'magic', 'ip' => '1.2.3.4', 'seen_at' => ''],
            $ios => ['type' => 'iOS', 'label' => 'iOS', 'ip' => '1.2.3.4', 'seen_at' => ''],
        ];

        [$hashes, $meta] = SubscriptionHwidBindingStore::dropSameIpAndType($hashes, $meta, '1.2.3.4', 'iOS');

        $this->assertCount(1, $hashes);
        $this->assertSame($win, $hashes[0]);
        $this->assertArrayNotHasKey($ios, $meta);
    }
}
