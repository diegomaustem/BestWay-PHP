<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Routes\Route;

header('Content-Type: application/json; charset=utf-8');

try {
    Route::direcionaRota();
} catch (Throwable $th) {
    http_response_code(500);
    exit(json_encode(["error" =>"Erro interno: configuração de rotas indisponível"], JSON_UNESCAPED_UNICODE));
}