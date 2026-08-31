<?php

namespace Tests\Unit;

use App\Services\Telegram\TelegramBotRegistrationService;
use Tests\TestCase;

final class CampaignLabelTest extends TestCase
{
    public function test_accepts_labels_in_telegram_deep_link_alphabet(): void
    {
        foreach ([
            'ad_reels_0901',
            'ad_shorts_0901',
            'ad-tg-bloger1',
            'AD_REELS_0901',
            'a',
        ] as $label) {
            $this->assertTrue(
                TelegramBotRegistrationService::isCampaignLabel($label),
                "Метка {$label} должна приниматься"
            );
        }
    }

    public function test_rejects_labels_telegram_cannot_carry(): void
    {
        foreach ([
            '' => 'пустая',
            'ad reels' => 'пробел',
            'ad.reels' => 'точка',
            'реклама_1' => 'кириллица',
            'ad/reels' => 'слэш',
            'ad+reels' => 'плюс',
        ] as $label => $why) {
            $this->assertFalse(
                TelegramBotRegistrationService::isCampaignLabel($label),
                "Метка должна отклоняться ({$why})"
            );
        }
    }

    public function test_rejects_label_longer_than_column(): void
    {
        $this->assertTrue(TelegramBotRegistrationService::isCampaignLabel(str_repeat('a', 64)));
        $this->assertFalse(TelegramBotRegistrationService::isCampaignLabel(str_repeat('a', 65)));
    }
}
