<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Services\Subscription\HappRoutingMergedInput;
use App\Services\Subscription\HappRoutingRulesParser;
use App\Services\Subscription\HappRoutingSubscriptionLine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionSettingsController extends Controller
{
    public function editRouting(): View
    {
        $routingRaw = AppSetting::getValue('happ_routing_rules') ?? '';
        $routingPreview = HappRoutingRulesParser::parse((string) $routingRaw);
        $configSites = config('xui.happ_routing.direct_sites', []);
        $configIp = config('xui.happ_routing.direct_ip', []);
        $configBlockSites = config('xui.happ_routing.block_sites', []);
        $geoipUrl = trim((string) config('xui.happ_routing.geoip_url', ''));
        $geositeUrl = trim((string) config('xui.happ_routing.geosite_url', ''));

        $configSites = is_array($configSites) ? $configSites : [];
        $configIp = is_array($configIp) ? $configIp : [];
        $configBlockSites = is_array($configBlockSites) ? $configBlockSites : [];

        $mergedSites = HappRoutingMergedInput::mergedDirectSites();
        $mergedIp = HappRoutingMergedInput::mergedDirectIp();
        $mergedBlockSites = HappRoutingMergedInput::mergedBlockSites();

        $directIpFromAdmin = array_values(array_filter(array_map('trim', $routingPreview['ips']), fn (string $s): bool => $s !== ''));

        $allowGeosite = $geositeUrl !== '';
        $allowGeoip = $geoipUrl !== '';

        return view('admin.subscription.routing', [
            'routingRules' => (string) $routingRaw,
            'routingConfigSites' => $configSites,
            'routingConfigIp' => $configIp,
            'routingConfigBlockSites' => $configBlockSites,
            'routingMergedSites' => $mergedSites,
            'routingMergedIp' => $mergedIp,
            'routingMergedBlockSites' => $mergedBlockSites,
            'routingHappProfileSites' => HappRoutingSubscriptionLine::sitesForHappProfile($mergedSites, $allowGeosite),
            'routingDirectIpFromAdmin' => $directIpFromAdmin,
            'routingHappProfileIpExtras' => HappRoutingSubscriptionLine::extraDirectIpForHappProfile($mergedIp, $allowGeoip),
            'happRoutingEnabled' => filter_var(config('xui.happ_routing.enabled', false), FILTER_VALIDATE_BOOL),
            'happGeoipUrl' => $geoipUrl,
            'happGeositeUrl' => $geositeUrl,
            'maxRoutingEntries' => HappRoutingRulesParser::MAX_OUTPUT_ENTRIES,
        ]);
    }

    public function updateRouting(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'routing_rules' => ['nullable', 'string', 'max:12000'],
        ]);

        $input = (string) ($data['routing_rules'] ?? '');
        $inputTrimmed = trim($input);

        if ($inputTrimmed === '') {
            AppSetting::forgetKey('happ_routing_rules');
        } else {
            $parsed = HappRoutingRulesParser::parse($inputTrimmed);
            $n = count($parsed['sites']) + count($parsed['ips']);
            if ($n > HappRoutingRulesParser::MAX_OUTPUT_ENTRIES) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors([
                        'routing_rules' => 'Слишком много распознанных правил ('.$n.'). Максимум '.HappRoutingRulesParser::MAX_OUTPUT_ENTRIES.'. Уберите лишние строки или разбейте список.',
                    ]);
            }

            AppSetting::setValue('happ_routing_rules', $inputTrimmed);
        }

        return redirect()
            ->route('admin.subscription.routing')
            ->with('status', 'Сохранено. Клиентам нужно обновить подписку в Happ.');
    }

    public function editAnnounce(): View
    {
        $raw = (string) (AppSetting::getValue('marketing_announce_text') ?? '');
        $raw = str_replace(["\r\n", "\r"], "\n", $raw);

        return view('admin.subscription.announce', [
            'announceExtra' => $raw,
        ]);
    }

    public function updateAnnounce(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'announce_text' => ['nullable', 'string', 'max:4000'],
        ]);

        $input = (string) ($data['announce_text'] ?? '');
        $input = str_replace(["\r\n", "\r"], "\n", $input);
        $input = trim($input, " \t\n");

        if ($input === '') {
            AppSetting::forgetKey('marketing_announce_text');
        } else {
            AppSetting::setValue('marketing_announce_text', $input);
        }

        return redirect()
            ->route('admin.subscription.announce')
            ->with('status', 'Сохранено.');
    }
}
