<?php

namespace Doarama;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Message\Response;

require 'vg_cfg.inc.php';
require 'vendor/autoload.php';


class Doarama {
    const API_BASE_URL = 'https://api.doarama.com';

    /** @var Client */
    private $client;

    private $apiName;
    private $apiKey;

    public function __construct($apiName, $apiKey) {
        $this->apiName = $apiName;
        $this->apiKey = $apiKey;
    }

    /**
     * @param Activity $activity
     * @return String The url of the visualization, null on error
     */
    public function createActivity(Activity $activity) {
        if ($activity->trackData == null) return;
        if (count($activity->trackData['lat']) < 1) return;

        $client = $this->getClient();

        // Create an activity
        $createOptions = [
            'startLatitude' => $activity->trackData['lat'][0],
            'startLongitude' => $activity->trackData['lon'][0],
            'startTime' => $activity->getTimeMs(0),
        ];

        try {
            // create an activity
            /** @var Response $response */
            $response = $client->post('api/0.2/activity/create',
                                      ['json' => $createOptions]);
            if (!$this->isSuccessfulResponse($response)) {
                return null;
            }
            $activity->id = $response->json()['id'];
            $client->post('/api/0.2/activity/' . $activity->id,
                          ['json' => ['activityTypeId' => $activity->type]]);
            // create a visualization
            $response = $client->post('api/0.2/visualisation',
                                      ['json' => ['activityIds' => [ $activity->id]]]);
            if (!$this->isSuccessfulResponse($response)) {
                return null;
            }
            $visId = $response->json()['id'];
            // get the visualization url
            $response = $client->get("/api/0.2/visualisation/" . $visId . "/url");
            if (!$this->isSuccessfulResponse($response)) {
                return null;
            }
            $url = $response->json()['url'];

        } catch (\RuntimeException $exc) {
        }

        return $url;
    }

    public function uploadAtivity(Activity $activity, $finishRequest = false) {
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        $client = $this->getClient();
        if (null === $activity->id) {
            throw new \RuntimeException("The activity id must be set before uploading the fixes");
        }
        try {
            $requests = [];
            $len = count($activity->trackData['lat']);
            for ($start = 0; $start < $len; $start += 200) {
                $end = min($start + 200, $len);
                $samples = [];
                for ($i = $start; $i < $end; $i++) {
                    $samples[] = [
                        'time' => $activity->getTimeMs($i),
                        'coords' => [
                            'latitude' => $activity->trackData['lat'][$i],
                            'longitude' => $activity->trackData['lon'][$i],
                            'altitude' => $activity->trackData['elev'][$i],
                        ]
                    ];
                }
                $requests[] = $client->createRequest('POST', '/api/0.2/activity/record',
                    ['json' => [
                        'samples' => $samples,
                        'activityId' => $activity->id,
                        'altitudeReference' => 'WGS84'
                    ]]
                );
            }
            $client->sendAll($requests);
        } catch (\RuntimeException $exc) {
            return false;
        }
        return true;
    }

    /**
     * @return Client
     */
    private function getClient() {
        if (null === $this->client) {
            $this->client = new Client([
                'base_url' => static::API_BASE_URL,
                'defaults' => [
                    'headers' => [
                        'user-id'  => 'vicb',
                        'api-name' => $this->apiName,
                        'api-key'  => $this->apiKey,
                        'Accept'   => 'application/json'
                    ],
                ]
            ]);
        }

        return $this->client;
    }

    private function isSuccessfulResponse(Response $response) {
        $statusCode = $response->getStatusCode();
        return $statusCode >= 200 && $statusCode <= 300;
    }
}

class Activity {
    const TYPE_UNDEFINED = 0;
    const TYPE_PARAGLIDER = 29;

    public $type;

    public $trackData;

    public $id;

    public function __construct($trackData, $type = self::TYPE_PARAGLIDER) {
        $this->trackData = $trackData;
        $this->type = $type;
    }

    public function getTimeMs($index) {
        $time = $this->trackData['time'];

        if (array_key_exists('date', $this->trackData)) {
            $date = $this->trackData['date'];
        } else {
            $date = [
                'month' => date('n'),
                'day' => date('j'),
                'year' => date('Y'),
            ];
        }

        return 1000 * gmmktime($time['hour'][$index], $time['min'][$index], $time['sec'][$index],
                               $date['month'], $date['day'], $date ['year']);

    }
}
