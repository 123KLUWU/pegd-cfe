<?php

namespace App\Services;

use Google\Client;
use Google\Service\Docs;
use Google\Service\Drive;
use Google\Service\Sheets;
use Illuminate\Support\Facades\Log;
use App\Models\GoogleToken;
use Carbon\Carbon;

class GoogleDriveService
{
        protected ?Client $client = null;
        protected ?string $lastError = null;
    
        public function __construct() {
            $this->client = $this->buildAuthorizedClientOrNull();
        }
    
        public function isReady(): bool { return $this->client !== null; }
        public function lastError(): ?string { return $this->lastError; }
    
        public function getDriveService(): ?Drive  { return $this->client ? new Drive($this->client) : null; }
        public function getDocsService():  ?Docs   { return $this->client ? new Docs($this->client) : null; }
        public function getSheetsService(): ?Sheets{ return $this->client ? new Sheets($this->client) : null; }
    
        private function baseClient(): Client {
            $c = new Client();
            $c->setClientId(env('GOOGLE_CLIENT_ID'));
            $c->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
            $c->setRedirectUri(env('GOOGLE_REDIRECT_URI'));
            $c->setScopes(explode(' ', env('GOOGLE_SCOPES')));
            $c->setAccessType('offline');
            // No setPrompt aquí (solo en el flujo de consentimiento manual)
            return $c;
        }
    
        private function buildAuthorizedClientOrNull(): ?Client {
            $row = GoogleToken::first();
            if (!$row || !$row->refresh_token) {
                $this->lastError = 'NO_REFRESH_TOKEN';
                return null;
            }
    
            $c = $this->baseClient();
    
            // Si tenemos access token vigente, úsalo
            if ($row->access_token && $row->access_token_expires_at && $row->access_token_expires_at->isFuture()) {
                $c->setAccessToken([
                    'access_token' => $row->access_token,
                    'created'      => time(),
                    'expires_in'   => $row->access_token_expires_at->diffInSeconds(now())
                ]);
                $c->refreshToken($row->refresh_token); // para tenerlo configurado en el client
                return $c;
            }
    
            // Intentar refrescar en silencio
            try {
                $c->refreshToken($row->refresh_token);
                $new = $c->getAccessToken();
    
                // Si Google rota RT, actualizar
                $maybeRt = $c->getRefreshToken();
                if (!empty($maybeRt) && $maybeRt !== $row->refresh_token) {
                    $row->refresh_token = $maybeRt;
                }
    
                $row->access_token = $new['access_token'] ?? null;
                if (!empty($new['expires_in'])) {
                    $row->access_token_expires_at = now()->addSeconds($new['expires_in'] - 60);
                }
                $row->save();
    
                return $c;
            } catch (\Google\Service\Exception $e) {
                // invalid_grant / revocado / token de Testing
                Log::warning('Google token invalidado: '.$e->getMessage());
                $row->update(['access_token'=>null,'access_token_expires_at'=>null]);
                $this->lastError = 'NEED_CONSENT_AGAIN';
                return null;
            } catch (\Throwable $e) {
                Log::error('Error OAuth Google: '.$e->getMessage());
                $this->lastError = 'OAUTH_ERROR';
                return null;
            }
        }
}