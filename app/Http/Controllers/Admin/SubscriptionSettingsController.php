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
    public function edit(): View
    {
        $stored = AppSetting::getValue('happ_profile_title');
        $profileTitle = ($stored !== null && $stored !== '')
            ? $stored
            : (string) config('xui.sub_profile_title', 'nadezhda VPN');

        return view('admin.subscription.profile', [
            'profileTitle' => $profileTitle,
            'fromEnvDefault' => (string) config('xui.sub_profile_title', 'nadezhda VPN'),
        ]);
    }

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

        $displaySite = static function (string $s): string {
            $s = trim($s);
            foreach (['domain:', 'full:'] as $p) {
                if (str_starts_with(strtolower($s), $p)) {
                    return trim(substr($s, strlen($p)));
                }
            }

            return $s;
        };

        $mergedSitesDisplay = array_values(array_filter(array_map($displaySite, $mergedSites), fn (string $s): bool => $s !== ''));
        $directIpDisplay = array_values(array_filter(array_map('trim', $routingPreview['ips']), fn (string $s): bool => $s !== ''));

        $rawLines = [];
        foreach (preg_split('/\r\n|\r|\n/', (string) $routingRaw) ?: [] as $line) {
            $line = trim((string) $line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $rawLines[] = $line;
        }

        return view('admin.subscription.routing', [
            'routingRules' => (string) $routingRaw,
            'routingConfigSites' => $configSites,
            'routingMergedSites' => $mergedSites,
            'routingRawLines' => $rawLines,
            'routingMergedSitesDisplay' => $mergedSitesDisplay,
            'routingDirectIpDisplay' => $directIpDisplay,
            'happRoutingEnabled' => filter_var(config('xui.happ_routing.enabled', false), FILTER_VALIDATE_BOOL),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'profile_title' => ['nullable', 'string', 'max:25'],
        ]);

        $v = trim((string) ($data['profile_title'] ?? ''));
        if ($v === '') {
            AppSetting::forgetKey('happ_profile_title');
        } else {
            AppSetting::setValue('happ_profile_title', $v);
        }

        return redirect()
            ->route('admin.subscription.settings')
            ->with('status', 'Сохранено. В Happ обновите подписку.');
    }

    public function updateRouting(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'routing_rules' => ['nullable', 'string', 'max:12000'],
        ]);

        $rules = trim((string) ($data['routing_rules'] ?? ''));
        // Поле ввода специально пустое по умолчанию. Пустая отправка не должна стирать сохранённый список.
        if ($rules !== '') {
            AppSetting::setValue('happ_routing_rules', $rules);
        }

        return redirect()
            ->route('admin.subscription.routing')
            ->with('status', 'Сохранено. В Happ обновите подписку.');
    }
}
