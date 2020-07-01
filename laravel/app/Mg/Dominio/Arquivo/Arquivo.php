<?php

namespace Mg\Dominio\Arquivo;

use Illuminate\Support\Facades\Storage;

use Mg\Dominio\Arquivo\Registro;

/**
 * Classe base para geração de arquivos textos para integracao
 * com o Dominio Sistemas
 *
 * @property string     $arquivo   Nome do arquivo que será gerado
 * @property Registro[] $registros Registros gerados
 */
class Arquivo
{
    public $arquivo;
    public $registros = [];

    /**
     * Processa e gera os registros
     * @return boolean
     */
    function processa()
    {
        return true;
    }

    /**
     * Metodo que grava o arquivo texto
     * @return boolean
     */
    function grava()
    {
        $conteudo = '';
        foreach ($this->registros as $reg)
        {
            $conteudo .= $reg->geraLinha();
        }
        return Storage::disk('dominio')->put($this->arquivo, $conteudo);
    }


}
