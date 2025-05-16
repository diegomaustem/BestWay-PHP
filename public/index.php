<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Routes\Route;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

header('Content-Type: application/json; charset=utf-8');

try {
    Route::direcionaRota();
} catch (Throwable $th) {
    error_log("Erro interno:" . $th->getMessage());
    http_response_code(500);
    exit(json_encode(["error" =>"Erro interno: configuração de rotas indisponível"], JSON_UNESCAPED_UNICODE));
}