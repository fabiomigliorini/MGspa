<?php

use Carbon\Carbon;

if (!function_exists('removeAcentos')) {
    function removeAcentos($string)
    {
        $map = [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a',
            'é' => 'e', 'ê' => 'e',
            'í' => 'i',
            'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ú' => 'u', 'ü' => 'u',
            'ç' => 'c',
            'Á' => 'A', 'À' => 'A', 'Ã' => 'A', 'Â' => 'A',
            'É' => 'E', 'Ê' => 'E',
            'Í' => 'I',
            'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O',
            'Ú' => 'U', 'Ü' => 'U',
            'Ç' => 'C',
        ];
        return strtr($string, $map);
    }
}

if (!function_exists('numeroLimpo')) {
    function numeroLimpo($numero)
    {
        return preg_replace('/[^0-9]|\s+/', '', $numero);
    }
}

if (!function_exists('formataData')) {
    function formataData($data, $formato = 'C')
    {
        if (empty($data)) {
            return null;
        }
        if (!$data instanceof Carbon) {
            $data = new Carbon($data);
        }
        switch (strtoupper($formato)) {
            case 'C':
            case 'CURTO':
                return $data->format('d/m/y');
            case 'M':
            case 'MEDIO':
                return $data->format('d/m/Y');
            case 'L':
            case 'LONGO':
                return $data->format('d/m/Y H:i');
            default:
                return $data->format($formato);
        }
    }
}

if (!function_exists('formataNumero')) {
    function formataNumero($numero, $digitos = 2)
    {
        if ($numero === null) {
            return $numero;
        }
        return number_format($numero, $digitos, ',', '.');
    }
}

if (!function_exists('mascarar')) {
    function mascarar($numero, $mascara)
    {
        $mascara = (string) $mascara;
        $mascara_limpa = preg_replace('/[^#]/', '', $mascara);
        $numero = str_pad((string) $numero, strlen($mascara_limpa), '0', STR_PAD_LEFT);
        if ($mascara == $mascara_limpa) {
            return $numero;
        }
        $mascarado = '';
        $k = 0;
        for ($i = 0; $i <= strlen($mascara) - 1; $i++) {
            if ($mascara[$i] == '#') {
                $mascarado .= $numero[$k++];
            } else {
                $mascarado .= $mascara[$i];
            }
        }
        return $mascarado;
    }
}

if (!function_exists('formataPorMascara')) {
    function formataPorMascara($string, $mascara, $somenteNumeros = true)
    {
        if ($somenteNumeros) {
            $string = numeroLimpo($string);
        }
        $caracteres = substr_count($mascara, '#');
        $string = str_pad($string, $caracteres, '0', STR_PAD_LEFT);
        $indice = -1;
        for ($i = 0; $i < strlen($mascara); $i++) {
            if ($mascara[$i] == '#') {
                $mascara[$i] = $string[++$indice];
            }
        }
        return $mascara;
    }
}

if (!function_exists('formataCnpj')) {
    function formataCnpj($numero)
    {
        return mascarar(numeroLimpo($numero), '##.###.###/####-##');
    }
}

if (!function_exists('formataCpf')) {
    function formataCpf($numero)
    {
        return mascarar(numeroLimpo($numero), '###.###.###-##');
    }
}

if (!function_exists('formataCnpjCpf')) {
    function formataCnpjCpf($numero, $fisica = 9)
    {
        $numero = numeroLimpo($numero);
        if ($fisica == 9) {
            $fisica = strlen($numero) <= 11;
        }
        return $fisica ? formataCpf($numero) : formataCnpj($numero);
    }
}

if (!function_exists('formataCep')) {
    function formataCep($string)
    {
        return formataPorMascara($string, '##.###-###');
    }
}

if (!function_exists('formataCodigo')) {
    function formataCodigo($numero, $digitos = 8)
    {
        return '#' . str_pad(numeroLimpo($numero), $digitos, '0', STR_PAD_LEFT);
    }
}
