<?php 
namespace App\Controllers;

class RotaFacilController {

    private $enderecosOD = null;

    public function __construct($enderecosOD) {
        $this->enderecosOD = $enderecosOD;
        $this->getRotaFacil();
    }

    public function getRotaFacil() 
    { 
        $enderecos = $this->formataEnderecosOD($this->enderecosOD);
    }

    private function formataEnderecosOD($enderecosOD)
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

        return [
            'origem' => $formata($enderecosOD->origem),
            'destino' => $formata($enderecosOD->destino)
        ];
    }
}