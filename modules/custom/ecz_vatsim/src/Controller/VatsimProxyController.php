<?php

namespace Drupal\ecz_vatsim\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use GuzzleHttp\Exception\RequestException;

class VatsimProxyController extends ControllerBase {

    public function getLiveData() {
        $cid = 'ecz_vatsim:filtered_live_data';
        $cache = \Drupal::cache()->get($cid);

        if ($cache) {
            return new JsonResponse($cache->data);
        }

        $config = \Drupal::config('ecz_vatsim.settings');

        $feed_url = $config->get('feed_url') ?: 'https://data.vatsim.net/v3/vatsim-data.json';
        $raw_prefixes = $config->get('prefixes') ?: 'TNCA, TNCB, TNCC, TNCM, TQPF, TNCS, TNCE, TNCF, TBPB, TFFF, TFFR, TAPA, TGPY, TVSA, TLPL, TDPD, TKPK, TTPP';

        $target_prefixes = array_filter(array_map(function($prefix) {
            return strtoupper(trim($prefix));
        }, explode(',', $raw_prefixes)));

        try {
            $response = \Drupal::httpClient()->get($feed_url, ['timeout' => 5]);
            $raw_data = json_decode($response->getBody()->getContents(), TRUE);

            $filtered_controllers = [];
            $inbounds = [];
            $outbounds = [];

            // Filter Controllers
            if (!empty($raw_data['controllers']) && is_array($raw_data['controllers'])) {
                foreach ($raw_data['controllers'] as $controller) {
                    if (empty($controller['callsign'])) {
                        continue;
                    }

                    $callsign = strtoupper($controller['callsign']);
                    $matches = FALSE;

                    foreach ($target_prefixes as $prefix) {
                        if (strpos($callsign, $prefix) === 0) {
                            $matches = TRUE;
                            break;
                        }
                    }

                    $is_not_obs = (substr($callsign, -4) !== '_OBS') && ($controller['facility'] !== 0);

                    if ($matches && $is_not_obs) {
                        $filtered_controllers[] = [
                            'callsign' => $controller['callsign'],
                            'name' => $controller['name'],
                            'frequency' => $controller['frequency'],
                        ];
                    }
                }
            }

            if (!empty($raw_data['pilots']) && is_array($raw_data['pilots'])) {
                foreach ($raw_data['pilots'] as $pilot) {
                    if (empty($pilot['flight_plan'])) {
                        continue;
                    }

                    $dep = strtoupper($pilot['flight_plan']['departure'] ?? '');
                    $arr = strtoupper($pilot['flight_plan']['arrival'] ?? '');

                    $is_arr = FALSE;
                    $is_dep = FALSE;

                    foreach ($target_prefixes as $prefix) {
                        if ($prefix !== '' && strpos($arr, $prefix) === 0) {
                            $is_arr = TRUE;
                        }
                        if ($prefix !== '' && strpos($dep, $prefix) === 0) {
                            $is_dep = TRUE;
                        }
                    }

                    $acft = !empty($pilot['flight_plan']['aircraft'])
                        ? substr(explode('/', $pilot['flight_plan']['aircraft'])[0], 0, 8)
                        : 'N/A';

                    $pilot_payload = [
                        'callsign' => $pilot['callsign'],
                        'name' => $pilot['name'],
                        'aircraft' => $acft,
                        'departure' => $pilot['flight_plan']['departure'] ?? 'N/A',
                        'arrival' => $pilot['flight_plan']['arrival'] ?? 'N/A',
                    ];

                    if ($is_arr) {
                        $inbounds[] = $pilot_payload;
                    }
                    if ($is_dep) {
                        $outbounds[] = $pilot_payload;
                    }
                }
            }

            $payload = [
                'controllers' => $filtered_controllers,
                'inbounds' => $inbounds,
                'outbounds' => $outbounds,
            ];

            \Drupal::cache()->set($cid, $payload, time() + 60);

            return new JsonResponse($payload);

        }
        catch (RequestException $e) {
            \Drupal::logger('ecz_vatsim')->error('Failed to fetch VATSIM data: @error', ['@error' => $e->getMessage()]);
            return new JsonResponse(['error' => 'Unable to load radar data.'], 500);
        }
    }
}