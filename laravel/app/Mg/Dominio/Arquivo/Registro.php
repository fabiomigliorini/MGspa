<?php

namespace Mg\Dominio\Arquivo;
use Carbon\Carbon;

class Registro
{
    public $campos = [];
    public $quebraLinha = "\r\n";

    public function geraLinha()
    {
        $linha = '';
        foreach ($this->campos as $campo => $detalhes)
        {
            $valor = (isset($this->$campo))?$this->$campo:null;

            $tamanho = $detalhes['tamanho'];

            $padChar = ' ';
            $padType = STR_PAD_RIGHT;

            if ($valor !== null)
            {
                switch ($detalhes['tipo'])
                {
                    case 'decimal':
                        $valor = round((float)$valor * pow(10, $detalhes['casas']), 0);
                    case 'numeric':
                        $valor = preg_replace('/[^0-9]/', '', $valor);
                        $padChar = '0';
                        $padType = STR_PAD_LEFT;
                        break;

                    case 'date':
                        if ($valor instanceof Carbon)
                            $valor = $valor->format($detalhes['formato']);
                        break;

                }
            }

            $valor = str_pad($valor, $tamanho, $padChar, $padType);
            if (strlen($valor) > $tamanho)
                $valor = substr ($valor, 0, $tamanho);

            $linha .= $valor;
        }
        return $linha . $this->quebraLinha;
    }
}
