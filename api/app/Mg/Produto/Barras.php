<?php

namespace Mg\Produto;

class Barras
{
    public static function calculaDigitoGtin($barras): int
    {
        //preenche com zeros a esquerda
        $codigo = "000000000000000000" . $barras;

        //pega 18 digitos
        $codigo = substr($codigo, -18);
        $soma = 0;

        //soma digito par *1 e impar *3
        for ($i = 1; $i<strlen($codigo); $i++) {
            $digito = substr($codigo, $i-1, 1);
            if ($i === 0 || !!($i && !($i%2))) {
                $multiplicador = 1;
            } else {
                $multiplicador = 3;
            }
            $soma +=  $digito * $multiplicador;
        }

        //subtrai da maior dezena
        $digito = (ceil($soma/10)*10) - $soma;

        //retorna digitocalculado
        return $digito;
    }

    public static function validarEan13($barras): bool
    {
        //calcula comprimento string
        $compr = strlen($barras);

        //precisa ter comprimento 13
        if ($compr != 13) {
            return false;
        }

        //precisa ser todo numerico
        if (!ctype_digit($barras)) {
            return false;
        }

        //calcula digito e verifica se bate com o digitado
        $digito = static::calculaDigitoGtin($barras);
        if ($digito == substr($barras, -1)) {
            return true;
        } else {
            return false;
        }
    }

    public static function validarCode128C($barras): bool
    {
        //calcula comprimento string
        $compr = strlen($barras);

        //precisa ter par
        if ($compr % 2 != 0) {
            return false;
        }

        //precisa ser todo numerico
        if (!ctype_digit($barras)) {
            return false;
        }

        return true;
    }


    public static function validarEan8($barras): bool
    {
        //calcula comprimento string
        $compr = strlen($barras);

        //precisa ter comprimento 13
        if ($compr != 8) {
            return false;
        }

        //precisa ser todo numerico
        if (!ctype_digit($barras)) {
            return false;
        }

        //calcula digito e verifica se bate com o digitado
        $digito = static::calculaDigitoGtin($barras);
        if ($digito == substr($barras, -1)) {
            return true;
        } else {
            return false;
        }
    }

    public static function validar($barras, $validarPrefixoGS1 = false): bool
    {
        //calcula comprimento string
        $compr = strlen($barras);

        //se nao tiver comprimento adequado retorna false
        if (($compr != 8)
            && ($compr != 12)
            && ($compr != 13)
            && ($compr != 14)
            && ($compr != 18)) {
            return false;
        }

        if (!ctype_digit($barras)) {
            return false;
        }

        if ($validarPrefixoGS1) {
            // valida prefixo de acordo com tabela receita
            if (!static::validarPrefixoGS1($barras)) {
                return false;
            }
            // Segundo documentacao Receita, so podem tamanhos 8/12/13/14
            if ($compr == 18) {
                return false;
            }
        }

        //calcula digito e verifica se bate com o digitado
        $digito = static::calculaDigitoGtin($barras);
        if ($digito == substr($barras, -1)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Valida prefixos do GS1 Conforme tabela receita federal
     */
    public static function validarPrefixoGS1 ($barras): bool
    {
        $barras = str_pad($barras, 14, '0', STR_PAD_LEFT);
        $inicio = substr($barras, 0, 6);
        if ($inicio == 0) {
            $prefixo = substr($barras, 6, 3);
        } else {
            $prefixo = substr($barras, 1, 3);
        }
        // Busca na tabela da Receita Federal
        $count = PrefixoGS1::whereRaw("'$prefixo' between inicial and final")->count();
        return ($count > 0);
    }

}
