<?php
namespace App\Services;

use App\Models\GoogleToken;
use Google\Client;
use Carbon\Carbon;
use Exception;

class GoogleAuthService
{
  private function baseClient(): Client {
    $c = new Client();
    $c->setClientId(env('GOOGLE_CLIENT_ID'));
    $c->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
    $c->setRedirectUri(env('GOOGLE_REDIRECT_URI'));
    $c->setScopes(explode(' ', env('GOOGLE_SCOPES')));
    $c->setAccessType('offline');  // <- Pide refresh_token
    $c->setPrompt('consent');      // <- Forzar 1a vez
    return $c;
  }

  public function authUrl(): string {
    return $this->baseClient()->createAuthUrl();
  }

  // Callback: guarda refresh_token (P1 Guardado correcto)
  public function handleCallback(string $code): void {
    $client = $this->baseClient();
    $token = $client->fetchAccessTokenWithAuthCode($code);

    if (isset($token['error'])) {
      throw new Exception('OAuth error: '.$token['error']);
    }

    $row = GoogleToken::firstOrCreate([]);
    if (!empty($token['refresh_token'])) {
      $row->refresh_token = $token['refresh_token']; // <- guardar bien
    }
    $row->access_token = $token['access_token'] ?? null;
    if (!empty($token['expires_in'])) {
      $row->access_token_expires_at = now()->addSeconds($token['expires_in'] - 60);
    }
    $row->save();
  }

  // Obtén cliente listo para APIs; refresca y rota si hace falta
  public function getAuthorizedClient(): Client {
    $row = GoogleToken::first();
    if (!$row || !$row->refresh_token) {
      throw new Exception('NO_REFRESH_TOKEN');
    }

    $client = $this->baseClient();

    // Usa access_token si está vigente
    if ($row->access_token && $row->access_token_expires_at?->isFuture()) {
      $client->setAccessToken([
        'access_token' => $row->access_token,
        'created'      => time(),
        'expires_in'   => $row->access_token_expires_at->diffInSeconds(now())
      ]);
      $client->refreshToken($row->refresh_token);
      return $client;
    }

    // (P2 Rotación y refresh correctos)
    try {
      $client->refreshToken($row->refresh_token);
      $new = $client->getAccessToken();

      // Si Google rota el refresh_token, actualízalo
      $maybeNewRT = $client->getRefreshToken();
      if (!empty($maybeNewRT) && $maybeNewRT !== $row->refresh_token) {
        $row->refresh_token = $maybeNewRT;  // <- ROTACIÓN
      }

      $row->access_token = $new['access_token'] ?? null;
      if (!empty($new['expires_in'])) {
        $row->access_token_expires_at = now()->addSeconds($new['expires_in'] - 60);
      }
      $row->save();

      return $client;
    } catch (\Google\Service\Exception $e) {
      // invalid_grant => el refresh_token ya no sirve (revocado/antiguo de Testing)
      $row->update(['access_token'=>null,'access_token_expires_at'=>null]);
      throw new Exception('NEED_CONSENT_AGAIN');
    }
  }
}
