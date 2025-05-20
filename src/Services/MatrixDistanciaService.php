<?php 
namespace App\Services;

use GuzzleHttp\Client;
use Throwable;

define("URL_MATRIX", "https://api.distancematrix.ai/maps/api/distancematrix/json");

class MatrixDistanciaService 
{
    private object $coordenadasOD;

    public function __construct(object $coordenadasOD) 
    {
        $this->coordenadasOD = $coordenadasOD;
    }

    public function getPercurso(): object | false
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
                    return json_decode($response->getBody());
                });
            return $promise->wait(); 
        } catch (Throwable $th) {
            error_log("Log error:" . $th->getMessage());
            return false;
        }
    }

    private function formatStringOD(object $coordenadas): string 
    {
        return $coordenadas->latitude . ',' . $coordenadas->longitude;
    }
}