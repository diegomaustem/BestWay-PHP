<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Routes\Route;
use Dotenv\Dotenv;
use App\Utils\ExibeErros;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

try {
    Route::direcionaRota();
} catch (Throwable $th) {
    error_log("Log error:" . $th->getMessage());
    ExibeErros::erro("Erro interno: configuração de rotas indisponível", 500);
}