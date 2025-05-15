<?php 
namespace App\Routes;
use App\Controllers\RotaFacilController;
class Route 
{
    public static function direcionaRota() 
    {
        $metodo = $_SERVER['REQUEST_METHOD'];
        $uri    = $_SERVER['REQUEST_URI'] ?? '';

        $rota = explode("/api/", $uri)[1];

        if($metodo != 'POST' || $rota !== 'rotaFacil') {
            self::exibeErros(["error" => "Método ou rota não identificado."], 404);
        }

        $enderecosOD = json_decode(file_get_contents('php://input'));
        $enderecosODValidos = self::validaEnderecosEntrada($enderecosOD);

        if($enderecosODValidos !== true) {
            self::exibeErros(["error" => "Estrutura de endereços inválida!"], 422);
        }
        new RotaFacilController($enderecosOD);
    }

    private static function validaEnderecosEntrada(object $enderecosOD): bool | null
    {
        if ($enderecosOD === null || !isset($enderecosOD->origem, $enderecosOD->destino)) {
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