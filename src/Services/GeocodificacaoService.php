<?php 
namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;

define("URL_GEOCODE", "https://api.distancematrix.ai/maps/api/geocode/json");

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
                    self::exibeErros(["error" => 'Falha no processo de geocodificação. Tente mais tarde!'], 500);
                }
            }
            return (object)$geodata;
        } catch (\Exception $e) {
            error_log("Erro de geocodificação: " . $e->getMessage());
            self::exibeErros(["error" => 'Falha no processo de geocodificação. Tente mais tarde!'], 500);
        }
    }

    private static function exibeErros($erro, $codigoHttp): string
    {
        http_response_code($codigoHttp);
        header('Content-Type: application/json; charset=utf-8');
    
        $resposta = [
            'error' => $erro ? $erro['error'] : 'Erro desconhecido',
            'code' => $codigoHttp
        ];
        exit(json_encode($resposta, JSON_UNESCAPED_UNICODE));
    }
}