<?php

namespace App;

use DateTime;
use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;

class App
{

    public function __construct(
        private DatabaseFetcher $fetcher,
        private string $token,
        private ?string $proxy
    )
    {
    }

    public function run(
        string $path,
        ?string $queryParameters,
        ?string $authHeader
    ): void
    {
        if ($path === '/') {
            http_response_code(404);

            return;
        }

        if (! $authHeader || $authHeader !== 'Bearer ' . $this->token) {
            http_response_code(401);

            return;
        }

        
        $tiktokAccountSlug = substr($path, 1);

        $fetchedAccounts = $this->fetcher->query(
            $this->fetcher->createQuery(
                'tiktok_account'
            )->select(
                'fb_login',
                'fb_password'
            )->where('slug = :slug'),
            ['slug' => $tiktokAccountSlug]
        );

        if (empty($fetchedAccounts)) {
            http_response_code(404);

            return;
        }

        $body = file_get_contents('php://input');

        if (! $body) {
            http_response_code(400);

            return;
        }

        $jsonBody = json_decode($body, true);

        if (! $jsonBody) {
            http_response_code(400);

            return;
        }

        if (empty($jsonBody['video_url']) || empty($jsonBody['legend'])) {
            http_response_code(400);

            return;
        }

        $projectFolder = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
        $cacheFolder = $projectFolder . 'cache' . DIRECTORY_SEPARATOR;
        if (! file_exists($cacheFolder)) {
            mkdir($cacheFolder);
        }

        $videoUrl = $jsonBody['video_url'];

        $videoFileName = $cacheFolder . (new DateTime())->getTimestamp() . '.mp4';

        set_time_limit(0);

        $fp = fopen($videoFileName, 'w+');
        $ch = curl_init($videoUrl);
        curl_setopt($ch, CURLOPT_TIMEOUT, 50);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);

        $fetchedAccount = $fetchedAccounts[0];
        $fbLogin = $fetchedAccount['fb_login'];
        $fbPassword = $fetchedAccount['fb_password'];

        $output = trim(shell_exec(
            'LC_CTYPE=en_US.utf8 node '
            . $projectFolder
            . 'post.js '
            . escapeshellarg($fbLogin)
            . ' '
            . escapeshellarg($fbPassword)
            . ' '
            . escapeshellarg($videoFileName)
            . ' '
            . escapeshellarg($jsonBody['legend'])
            . (
                $this->proxy !== null
                ? ' ' . escapeshellarg($this->proxy)
                : ''
            )
            . ' 2>&1'
        ));

        unlink($videoFileName);

        if (str_starts_with($output, 'https://www.tiktok.com/')) {
            http_response_code(200);
            echo json_encode(['url' => $output]);
            return;
        }

        http_response_code(500);
        echo json_encode(['error' => $output]);
    }
}
