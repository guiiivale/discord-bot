<?php

namespace App\Services;

class OpGGService
{
    public function getDataOpGG($summonerName)
    {
        $url = "https://www.op.gg/_next/data/hNkxrToTx8MW15R_8ziWW/en_US/summoners/br/{$summonerName}.json?region=br";

        return $this->curl($url);
    }

    public function transformPlayerName($playerName)
    {
        $playerName = trim(substr($playerName, 7));

        $formattedName = strtolower(str_replace([' ', '#'], ['%20', '-'], $playerName));

        return $formattedName;
    }

    public function updateData($summonerName)
    {
        $id = $this->getDataOpGG($summonerName)['pageProps']['data']['summoner_id'];

        $url = "https://op.gg/api/v1.0/internal/bypass/summoners/br/{$id}/renewal";

        return $this->curl($url, 'POST');
    }

    protected function curl($url, $method = 'GET', $data = [])
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $data,
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $data = json_decode($response, true);

        return $data;
    }

    public function ingameData($summonerName)
    {
        $id = $this->getDataOpGG($summonerName)['pageProps']['data']['summoner_id'];

        $url = "https://op.gg/api/v1.0/internal/bypass/spectates/br/{$id}?hl=en_US";

        return $this->curl($url);
    }
}
