<?php 
namespace App\Routes;
use App\Controllers\RotaFacilController;
use App\Utils\ExibeErros;

class Route 
{
    public static function direcionaRota(): void 
    {
        $metodo = $_SERVER['REQUEST_METHOD'];
        $uri    = $_SERVER['REQUEST_URI'] ?? '';

        $rota = explode("/api/", $uri)[1];

        if($metodo != 'POST' || $rota !== 'rotaFacil') {
            ExibeErros::erro("Método ou rota não identificado.", 404);
        }
        
        $dadosEntrada = file_get_contents('php://input');

        if(!is_string($dadosEntrada)) {
            ExibeErros::erro('Dados de entrada inválidos', 400);
        }

        $enderecosOD = json_decode($dadosEntrada);       
        $enderecosODValidos = self::validaEnderecosEntrada($enderecosOD);

        if($enderecosODValidos !== true) {
            ExibeErros::erro("Estrutura de endereços inválida!", 422);
        }
        new RotaFacilController($enderecosOD);
    }

    private static function validaEnderecosEntrada(object $enderecosOD): bool | null
    {
        if ($enderecosOD == null || !isset($enderecosOD->origem, $enderecosOD->destino)) {
            return null;
        }

        $camposObrigatorios = ["logradouro", "cidade", "estado"];

        foreach ([$enderecosOD->origem, $enderecosOD->destino] as $endereco) {
            foreach ($camposObrigatorios as $campo) {
                if (!isset($endereco->$campo) || empty(trim($endereco->$campo))) {
                    return null;
                }
            }
        }
        return true;
    }
}