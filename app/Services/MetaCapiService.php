<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Meta Conversions API sender.
 *
 * Sends Purchase (and optionally other) events server-side to Meta Graph API
 * so we stop losing ~30% of events to ad-blockers, iOS ITP and closed tabs.
 *
 * The corresponding client-side pixel event uses the same event_id so Meta
 * automatically deduplicates when both arrive.
 *
 * Docs: https://developers.facebook.com/docs/marketing-api/conversions-api
 */
class MetaCapiService
{
    private const GRAPH_VERSION = 'v21.0';

    public function sendPurchase(array $args): bool
    {
        $pixelId = env('META_PIXEL_ID');
        $token   = env('META_CAPI_TOKEN');

        if (! $pixelId || ! $token) {
            return false;
        }

        $userData = [];
        if (! empty($args['email']))         $userData['em'] = [hash('sha256', strtolower(trim($args['email'])))];
        if (! empty($args['phone']))         $userData['ph'] = [hash('sha256', preg_replace('/\D+/', '', $args['phone']))];
        if (! empty($args['first_name']))    $userData['fn'] = [hash('sha256', strtolower(trim($args['first_name'])))];
        if (! empty($args['last_name']))     $userData['ln'] = [hash('sha256', strtolower(trim($args['last_name'])))];
        if (! empty($args['city']))          $userData['ct'] = [hash('sha256', strtolower(preg_replace('/\s+/', '', $args['city'])))];
        if (! empty($args['postcode']))      $userData['zp'] = [hash('sha256', strtolower(preg_replace('/\s+/', '', $args['postcode'])))];
        if (! empty($args['country']))       $userData['country'] = [hash('sha256', strtolower(trim($args['country'])))];
        if (! empty($args['ip']))            $userData['client_ip_address'] = $args['ip'];
        if (! empty($args['user_agent']))    $userData['client_user_agent'] = $args['user_agent'];
        if (! empty($args['fbp']))           $userData['fbp'] = $args['fbp'];
        if (! empty($args['fbc']))           $userData['fbc'] = $args['fbc'];

        $event = [
            'event_name'       => 'Purchase',
            'event_time'       => time(),
            'event_id'         => (string) ($args['event_id'] ?? ''),
            'event_source_url' => $args['event_source_url'] ?? config('app.url'),
            'action_source'    => 'website',
            'user_data'        => $userData,
            'custom_data'      => [
                'currency'     => $args['currency'] ?? 'EUR',
                'value'        => round((float) ($args['value'] ?? 0), 2),
                'content_ids'  => $args['content_ids'] ?? [],
                'content_type' => 'product',
                'contents'     => $args['contents'] ?? [],
                'num_items'    => $args['num_items'] ?? count($args['contents'] ?? []),
                'order_id'     => (string) ($args['event_id'] ?? ''),
            ],
        ];

        $payload = ['data' => [$event]];
        if (! empty($args['test_event_code'])) {
            $payload['test_event_code'] = $args['test_event_code'];
        }

        try {
            $response = Http::timeout(5)
                ->connectTimeout(3)
                ->post(
                    sprintf('https://graph.facebook.com/%s/%s/events', self::GRAPH_VERSION, $pixelId),
                    array_merge($payload, ['access_token' => $token])
                );

            $ok = $response->successful();
            Log::info('[MetaCapi] Purchase sent', [
                'event_id' => $event['event_id'],
                'value'    => $event['custom_data']['value'],
                'status'   => $response->status(),
                'ok'       => $ok,
                'response' => substr($response->body(), 0, 300),
            ]);
            return $ok;
        } catch (\Throwable $e) {
            Log::warning('[MetaCapi] Purchase failed', [
                'event_id' => $event['event_id'],
                'error'    => $e->getMessage(),
            ]);
            return false;
        }
    }
}
