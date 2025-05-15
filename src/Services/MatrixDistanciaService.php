<?php 
namespace App\Services;

use GuzzleHttp\Client;

define("URL_MATRIX", "https://api.distancematrix.ai/maps/api/distancematrix/json");

class MatrixDistanciaService 
{
    private object $coordenadasOD;

    public function __construct(object $coordenadasOD) {
        $this->coordenadasOD = $coordenadasOD;
    }

    public function getPercurso() 
    {
        $client = new Client();

        try {
            $promise = $client->getAsync(URL_MATRIX, [
                'query' => [
                    'origins' => $this->formatStringOD($this->coordenadasOD->origem),
                    'destinations' => $this->formatStringOD($this->coordenadasOD->destino),
                    'key' => $_ENV['MATRIXDISTANCIA_API_KEY']
                ]
            ])->then(
                function ($response) {
                    return json_decode($response->getBody(), true);
                },
                function ($exception) {
                    self::exibeErros(["error" => 'Falha de requisição. Tente mais tarde!'], 404);
                }
            );
            
            return $promise->wait(); 
        } catch (\Exception $e) {
            error_log("Erro de matrix distancia: " . $e->getMessage());
            self::exibeErros(["error" => 'Falha no processo de calcular distância. Tente mais tarde!'], 500);
        }
    }

    private function formatStringOD(object $coordenadas): string 
    {
        return $coordenadas->latitude . ',' . $coordenadas->longitude;
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