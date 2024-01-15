<?php

namespace App\Services;

use App\Models\SpotifyToken;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Http;

class SpotifyService
{
    public function getToken()
    {
        $clientId = Env::get('SPOTIFY_CLIENT_ID');
        $clientSecret = Env::get('SPOTIFY_CLIENT_SECRET');

        $response = Http::asForm()
            ->post('https://accounts.spotify.com/api/token', [
                'grant_type' => 'client_credentials',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ]);

        if ($response->successful()) {
            $data = $response->json();

            $token = $data['access_token'];
            $expiresIn = $data['expires_in'];
            $tokenType = $data['token_type'];

            $this->saveToken($token, $expiresIn, $tokenType);
        }

        return false;
    }

    protected function saveToken($token, $expiresIn, $tokenType)
    {
        $spotify = SpotifyToken::first();

        if($spotify) {
            $spotify->token = $token;
            $spotify->expires_in = $expiresIn;
            $spotify->token_type = $tokenType;
            $spotify->save();

            return $spotify;
        }

        $spotify = SpotifyToken::create([
            'token' => $token,
            'expires_in' => $expiresIn,
            'token_type' => $tokenType,
        ]);

        return $spotify;
    }

    public function search($query, $type)
    {
        $spotify = SpotifyToken::first();

        if(!$spotify) {
            return false;
        }

        $response = Http::withHeaders([
            'Authorization' => "{$spotify->token_type} {$spotify->token}",
        ])->get('https://api.spotify.com/v1/search', [
            'q' => $query,
            'type' => $type,
        ]);

        if ($response->successful()) {
            $data = $response->json();

            return $data;
        }

        return false;
    }

    public function getArtistData($id)
    {
        $spotify = SpotifyToken::first();

        if(!$spotify) {
            return false;
        }

        $response = Http::withHeaders([
            'Authorization' => "{$spotify->token_type} {$spotify->token}",
        ])->get("https://api.spotify.com/v1/artists/{$id}");

        if ($response->successful()) {
            $data = $response->json();

            return $data;
        }

        return false;
    }
}