<?php

namespace App\Services;

use Google\Client;
use Google\Service\Docs;
use Google\Service\Drive;
use Google\Service\Sheets;
use Illuminate\Support\Facades\Log;

class GoogleDriveService
{
    protected $client;

    public function __construct()
    {
        $client = new Client();
        $client->setApplicationName(env('GOOGLE_APPLICATION_NAME'));
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $client->setRedirectUri(env('GOOGLE_REDIRECT_URI'));
        $client->setAccessType('offline'); // Muy importante para obtener un refresh_token
        $client->setPrompt('select_account consent'); // Fuerza a seleccionar cuenta y consentir

        // Define los scopes necesarios
        $client->setScopes(explode(' ', env('GOOGLE_SCOPES')));

        // Si ya tenemos un refresh token guardado, lo usamos para obtener un nuevo access token
        if (env('GOOGLE_REFRESH_TOKEN')) {
            $client->fetchAccessTokenWithRefreshToken(env('GOOGLE_REFRESH_TOKEN'));
        }

        $this->client = $client;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getDriveService(): Drive
    {
        return new Drive($this->client);
    }

    public function getDocsService(): Docs
    {
        return new Docs($this->client);
    }

    public function getSheetsService(): Sheets
    {
        return new Sheets($this->client);
    }
}