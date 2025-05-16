<?php 
namespace App\Controllers;
use App\Services\GeocodificacaoService;
use App\Services\MatrixDistanciaService;

class RotaFacilController 
{
    private object $enderecosOD;

    public function __construct(object $enderecosOD) 
    {
        $this->enderecosOD = $enderecosOD;
        $this->getRotaFacil();
    }

    public function getRotaFacil() 
    { 
        $enderecos = $this->formataEndOD($this->enderecosOD);
        $geocodificacao = new GeocodificacaoService($enderecos);

        $enderecoCordenadas = $this->formataEndCoordenadas($geocodificacao->getLocalizacaoGeografica());
        $coordenadasOD = $this->formataCoordOD($enderecoCordenadas);

        $matrixDistancia = new MatrixDistanciaService($coordenadasOD);
        $percurso = $this->formataPercurso($matrixDistancia->getPercurso());

        exit(json_encode($percurso, JSON_UNESCAPED_UNICODE));
    }

    private function formataEndOD(object $enderecosOD): object
    {
        $formata = function($enderecoOD) {
            $partes = [
                $enderecoOD->logradouro,
                $enderecoOD->numero !== null ? $enderecoOD->numero : null,
                $enderecoOD->cidade,
                $enderecoOD->estado
            ];
            
            return implode(',', array_filter($partes));
        };

        $enderecosFormatados = new \stdClass();
        $enderecosFormatados->origem = $formata($enderecosOD->origem);
        $enderecosFormatados->destino = $formata($enderecosOD->destino);

        return $enderecosFormatados;
    }

    private function formataEndCoordenadas(object $geocodificacao): object
    {
        $formataCoordenadas = function(object $localizacao): object {
            if (!isset(
                $localizacao->result,
                $localizacao->result[0],
                $localizacao->result[0]->formatted_address,
                $localizacao->result[0]->geometry,
                $localizacao->result[0]->geometry->location,
                $localizacao->result[0]->geometry->location->lat,
                $localizacao->result[0]->geometry->location->lng
            )) {
                return (object) [
                    'erro' => 'Estrutura de coordenadas inválida'
                ];
            }

            return (object) [
                'endereco' => $localizacao->result[0]->formatted_address,
                'latitude' => (float)$localizacao->result[0]->geometry->location->lat,
                'longitude' => (float)$localizacao->result[0]->geometry->location->lng
            ];
        };

        $origem = $formataCoordenadas($geocodificacao->origem);
        $destino = $formataCoordenadas($geocodificacao->destino);

        return (object) [
            'origem' => isset($origem->erro) ? null : $origem,
            'destino' => isset($destino->erro) ? null : $destino,
            'erro' => isset($origem->erro) ? $origem->erro : (isset($destino->erro) ? $destino->erro : null)
        ];
    }

    private function formataCoordOD(object $coordenadasOD): object
    {
        return (object) [
            'origem' => (object) [
                'latitude' => $coordenadasOD->origem->latitude,
                'longitude' => $coordenadasOD->origem->longitude,
            ],
            'destino' => (object) [
                'latitude' => $coordenadasOD->destino->latitude,
                'longitude' => $coordenadasOD->destino->longitude,
            ],
        ];
    }

    private function formataPercurso(array $percurs): object 
    {
        $elemento = $percurso['rows'][0]['elements'][0] ?? null;
    
        return (object)[
            'origem' => $percurso['origin_addresses'][0] ?? null,
            'destino' => $percurso['destination_addresses'][0] ?? null,
            'distancia' => (object)['km' => $elemento['distance']['text'] ?? null],
            'duração' => (object)['tempo' => $elemento['duration']['text'] ?? null],
            'erro' => (!isset(
                $percurso['origin_addresses'][0],
                $percurso['destination_addresses'][0],
                $elemento['distance']['text'],
                $elemento['duration']['text']
            )) ? 'Estrutura inválida' : null
        ];
    }
}