<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$routing = (string) App\Services\Subscription\HappRoutingSubscriptionLine::feedRoutingLine();
echo 'routing_len='.strlen($routing)."\n";
echo 'routing_is_off='.( $routing === 'happ://routing/off' ? 'yes' : 'no' )."\n";
echo 'routing_off_when_ruvds='.( config('xui.happ_routing.routing_off_when_ruvds') ? '1' : '0' )."\n";

$token = App\Models\Subscription::query()->value('token');
$url = rtrim((string) config('app.url'), '/').'/sub/'.$token;
$body = file_get_contents($url, false, stream_context_create(['http' => ['header' => "X-Hwid: diag\r\n"]]));
$first = strtok((string) $body, "\n");
echo 'feed_line0_len='.strlen((string) $first)."\n";
echo 'feed_line0_prefix='.substr((string) $first, 0, 60)."\n";
