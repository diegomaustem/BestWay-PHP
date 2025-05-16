<?php 
namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use App\Utils\ExibeErros;
use Throwable;

define("URL_GEOCODE", "https://api.distancematrix.ai/maps/api/geocode/jon");

class GeocodificacaoService 
{
    private object $enderecos;

    public function __construct(object $enderecos) 
    {
        $this->enderecos = $enderecos;
    }

    public function getLocalizacaoGeografica() 
    {
        $client = new Client();

        $promises = [
            'origem' => $client->getAsync(URL_GEOCODE, [
                'query' => [
                    'address' => $this->enderecos->origem,
                    'key' => $_ENV['GEOCODIFICACAO_API_KEY'],
                    'components' => 'country:BR'
                ]
            ]),
            'destino' => $client->getAsync(URL_GEOCODE, [
                'query' => [
                    'address' => $this->enderecos->destino,
                    'key' => $_ENV['GEOCODIFICACAO_API_KEY'],
                    'components' => 'country:BR'
                ]
            ])
        ];

        try {
            $results = Promise\Utils::settle($promises)->wait();

            foreach ($results as $tipo => $result) {
                if ($result['state'] === 'fulfilled') {
                    $geodata[$tipo] = json_decode($result['value']->getBody());
                } else {
                    ExibeErros::erro('Falha no processo de geocodificação. Tente mais tarde!', 500);
                }
            }
            return (object)$geodata;
        } catch (Throwable $th) {
            error_log("Log error:" . $th->getMessage());
            ExibeErros::erro('Falha no processo de geocodificação. Tente mais tarde!', 500);
        }
    }
}