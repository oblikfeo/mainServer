<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Services\Subscription\HappRoutingRulesParser;
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

        $configSites = is_array($configSites) ? $configSites : [];
        $mergedSites = [];
        $seen = [];
        foreach ([...$configSites, ...$routingPreview['sites']] as $s) {
            $s = trim((string) $s);
            if ($s === '') {
                continue;
            }
            $k = strtolower($s);
            if (isset($seen[$k])) {
                continue;
            }
            $seen[$k] = true;
            $mergedSites[] = $s;
        }

        $directIpFromAdmin = array_values(array_filter(array_map('trim', $routingPreview['ips']), fn (string $s): bool => $s !== ''));

        return view('admin.subscription.routing', [
            'routingRules' => (string) $routingRaw,
            'routingConfigSites' => $configSites,
            'routingMergedSites' => $mergedSites,
            'routingDirectIpFromAdmin' => $directIpFromAdmin,
            'happRoutingEnabled' => filter_var(config('xui.happ_routing.enabled', false), FILTER_VALIDATE_BOOL),
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
