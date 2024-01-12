<?php

namespace App\Services;

class OpGGService
{
    public function obterDadosOpGG($summonerName)
    {
        $url = "https://www.op.gg/_next/data/hNkxrToTx8MW15R_8ziWW/en_US/summoners/br/{$summonerName}.json?region=br";

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $dados = json_decode($response, true);

        return $dados;
    }

    public function transformPlayerName($playerName)
    {
        $playerName = trim(substr($playerName, 7));

        $formattedName = strtolower(str_replace([' ', '#'], ['%20', '-'], $playerName));

        return $formattedName;
    }
}
