<?php

namespace Doarama;

use GuzzleHttp\Client;
use GuzzleHttp\Post\PostFile;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Subscriber\Retry\RetrySubscriber;

require_once 'vg_cfg.inc.php';
require_once 'vendor/autoload.php';


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
     * @return String The key of the visualization
     */
    public function createVisualization(Activity $activity) {
        if ($activity->trackData == null) return;
        if (count($activity->trackData['lat']) < 5) return;

        $client = $this->getClient();            

        try {
            // create activity
            $request = $client->createRequest('POST', 'api/0.2/activity');
            $postBody = $request->getBody();
            $postBody->addFile(new PostFile('gps_track', $activity->rawTrack));
            /** @var Response $response */
            $response = $client->send($request);
                       
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
    public $rawTrack;
    public $supported;
    public $id;

    public function __construct($trackData, $rawTrack, $supported, $type = self::TYPE_PARAGLIDER) {
        $this->trackData = $trackData;
        $this->type = $type;
        $this->rawTrack = $rawTrack;
        $this->supported = $supported;
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
