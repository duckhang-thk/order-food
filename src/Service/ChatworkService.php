<?php

namespace App\Service;

use Cake\Core\Configure;
use RuntimeException;

class ChatworkService
{
    private string $apiToken;
    private string $roomId;

    public function __construct()
    {
        $this->apiToken = (string)env('CHATWORK_API_TOKEN');
        $this->roomId = (string)env('CHATWORK_ROOM_ID');

        if ($this->apiToken === '' || $this->roomId === '') {
            throw new RuntimeException('Chatwork configuration is missing.');
        }
    }

    public function send(string $message): bool
    {
        $url = sprintf(
            'https://api.chatwork.com/v2/rooms/%s/messages',
            $this->roomId
        );

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'body' => $message,
            ]),
            CURLOPT_HTTPHEADER => [
                'X-ChatWorkToken: ' . $this->apiToken,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);

            throw new RuntimeException(
                'Chatwork API request failed: ' . $error
            );
        }

        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new RuntimeException(
                sprintf(
                    'Chatwork API returned HTTP %d: %s',
                    $httpCode,
                    $response
                )
            );
        }

        return true;
    }
}