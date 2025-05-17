<?php 
namespace App\Utils;

class ExibeErros
{
    public static function erro(string $erro, int $code): string
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($code);
    
        $resposta = [
            'error' => $erro ? $erro : 'Erro desconhecido',
            'code' => $code
        ];
        exit(json_encode($resposta, JSON_UNESCAPED_UNICODE));
    }
}