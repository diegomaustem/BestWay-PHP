<?php 
namespace App\Controllers;
use App\Services\GeocodificacaoService;
use App\Services\MatrixDistanciaService;
use App\Utils\ExibeErros;

class RotaFacilController 
{
    private object $enderecosOD;

    public function __construct(object $enderecosOD) 
    {
        $this->enderecosOD = $enderecosOD;
        $this->getRotaFacil();
    }

    public function getRotaFacil(): void 
    { 
        $enderecos = $this->formataEndOD($this->enderecosOD);
        $geoService = new GeocodificacaoService($enderecos); 
        $geocodificacao = $geoService->getLocalizacaoGeografica();

        if(!is_object($geocodificacao)) {
            ExibeErros::erro('Falha no processo de geocodificação. Tente mais tarde!', 500);
        }

        $enderecoCordenadas = $this->formataEndCoordenadas($geocodificacao);
        $coordenadasOD = $this->formataCoordOD($enderecoCordenadas);

        $matrixService = new MatrixDistanciaService($coordenadasOD);
        $matrixDistancia = $matrixService->getPercurso();

        if(!is_object($matrixDistancia)) {
            ExibeErros::erro('Falha no processo de calcular distância. Tente mais tarde!', 500);
        }

        $percurso = $this->formataPercurso($matrixDistancia);
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

    private function formataPercurso(object $percurso): object 
    { 
        $elemento = $percurso->rows[0]->elements[0] ?? null;

        return (object)[
            'origem' => $percurso->origin_addresses[0] ?? null,
            'destino' => $percurso->destination_addresses[0] ?? null,
            'distancia' => (object)['km' => $elemento->distance->text ?? null],
            'duracao' => (object)['tempo' => $elemento->duration->text ?? null],
            'erro' => (!isset(
                $percurso->origin_addresses[0],
                $percurso->destination_addresses[0],
                $elemento->distance->text,
                $elemento->duration->text
            )) ? 'Estrutura inválida' : null
        ];
    }
}