<?php

namespace Doarama;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Subscriber\Retry\RetrySubscriber;

require_once 'vg_cfg.inc.php';
require_once 'vendor/autoload.php';


class Doarama {
    //const API_BASE_URL = 'https://api.doarama.com';
    // TODO: switch back to the main server when it is ready
    const API_BASE_URL = 'http://doarama-thirdparty-dev.herokuapp.com';
    const DOARAMA_CHUNK_LENGTH = 150;

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
     * @return String The key of the visualization
     */
    public function createVisualization(Activity $activity) {
        if ($activity->trackData == null) return;
        if (count($activity->trackData['lat']) < 5) return;

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
            return $response->json()['key'];
        } catch (\RuntimeException $exc) {
            return null;
        }
    }

    public function uploadActivity(Activity $activity) {
        if (null === $activity->id) return;
        $client = $this->getClient();
        try {
            $requests = [];
            $len = count($activity->trackData['lat']);
            for ($start = 0; $start < $len; $start += static::DOARAMA_CHUNK_LENGTH) {
                $end = min($start + static::DOARAMA_CHUNK_LENGTH, $len);
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
            $client->sendAll($requests, ['parallel' => 3]);
        } catch (\RuntimeException $exc) {
            return false;
        }
        return true;
    }

    public function getVisualizationUrl($key) {
        return static::API_BASE_URL . '/api/0.2/visualisation?k=' . $key;
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
            $retry = new RetrySubscriber([
                'filter' => RetrySubscriber::createStatusFilter(),
                'max' => 3
            ]);
            $this->client->getEmitter()->attach($retry);
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
    const TYPE_GLIDER = 11;

    public $type;

    public $trackData;

    public $id;

    // TODO Switch back to PARAGLIDER (not supported on the staging server)
    public function __construct($trackData, $type = self::TYPE_GLIDER) {
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
