<?php

use Carbon\Carbon;

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
                break;

            case 'M':
            case 'MEDIO':
                return $data->format('d/m/Y');
                break;

            case 'E':
            case 'EXTENSO':
                // ('%A %d %B %Y');  // Mittwoch 21 Mai 1975
                return $data->formatLocalized('%d %B %Y');
                break;

            case 'EC':
            case 'EXTENSOCURTO':
                return $data->formatLocalized('%b/%Y');
                break;

            case 'L':
            case 'LONGO':
                return $data->format('d/m/Y H:i');
                break;

            default:
                return $data->format($formato);
                break;
        }
    }
}

if (!function_exists('formataCodigo')) {
    function formataCodigo($numero, $digitos = 8)
    {
        return "#" . str_pad(numeroLimpo($numero), $digitos, "0", STR_PAD_LEFT);
    }
}

if (!function_exists('mascarar')) {
    function mascarar($numero, $mascara)
    {

        // Transforma Mascara em String Caso não Seja
        $mascara = (string) $mascara;

        // Descobre quantos digitos tem a mascara e adiciona zero a esquerda
        $mascara_limpa = preg_replace('/[^#]/', '', $mascara);
        $numero = str_pad((string) $numero, strlen($mascara_limpa), "0", STR_PAD_LEFT);

        // se a mascara nao tiver caracteres diferentes de # retorna
        if ($mascara == $mascara_limpa) {
            return $numero;
        }

        // Percorre mascara adicionando caracteres
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

if (!function_exists('formataCpfCnpj')) {
    function formataCpfCnpj($numero, $fisica = 9)
    {
        $numero = numeroLimpo('/[^0-9]/', '', $numero);
        if ($fisica == 9) {
            $fisica = strlen($numero) <= 11;
        }
        if ($fisica) {
            return formataCnpj($numero);
        } else {
            return formataCpf($numero);
        }
    }
}


if (!function_exists('formataValorPorExtenso')) {

    function formataValorPorExtenso($valor = 0, $maiusculas = false)
    {

        $singular = array("centavo", "real", "mil", "milhao", "bilhao", "trilhao", "quatrilhao");
        $plural = array("centavos", "reais", "mil", "milhoes", "bilhoes", "trilhoes", "quatrilhoes");

        $c = array("", "cem", "duzentos", "trezentos", "quatrocentos", "quinhentos", "seiscentos", "setecentos", "oitocentos", "novecentos");
        $d = array("", "dez", "vinte", "trinta", "quarenta", "cinquenta", "sessenta", "setenta", "oitenta", "noventa");
        $d10 = array("dez", "onze", "doze", "treze", "quatorze", "quinze", "dezesseis", "dezesete", "dezoito", "dezenove");
        $u = array("", "um", "dois", "tres", "quatro", "cinco", "seis",    "sete", "oito", "nove");

        $z = 0;
        $rt = "";

        $valor = number_format($valor, 2, ".", ".");
        $inteiro = explode(".", $valor);
        for ($i = 0; $i < count($inteiro); $i++)
            for ($ii = strlen($inteiro[$i]); $ii < 3; $ii++)
                $inteiro[$i] = "0" . $inteiro[$i];

        $fim = count($inteiro) - ($inteiro[count($inteiro) - 1] > 0 ? 1 : 2);
        for ($i = 0; $i < count($inteiro); $i++) {
            $valor = $inteiro[$i];
            $rc = (($valor > 100) && ($valor < 200)) ? "cento" : $c[$valor[0]];
            $rd = ($valor[1] < 2) ? "" : $d[$valor[1]];
            $ru = ($valor > 0) ? (($valor[1] == 1) ? $d10[$valor[2]] : $u[$valor[2]]) : "";

            $r = $rc . (($rc && ($rd || $ru)) ? " e " : "") . $rd . (($rd &&
                $ru) ? " e " : "") . $ru;
            $t = count($inteiro) - 1 - $i;
            $r .= $r ? " " . ($valor > 1 ? $plural[$t] : $singular[$t]) : "";
            if ($valor == "000") $z++;
            elseif ($z > 0) $z--;
            if (($t == 1) && ($z > 0) && ($inteiro[0] > 0)) $r .= (($z > 1) ? " de " : "") . $plural[$t];
            if ($r) $rt = $rt . ((($i > 0) && ($i <= $fim) &&
                ($inteiro[0] > 0) && ($z < 1)) ? (($i < $fim) ? ", " : " e ") : " ") . $r;
        }

        $rt = trim($rt);

        if (!$maiusculas) {
            return ($rt ? $rt : "zero");
        } else {

            if ($rt) $rt = str_replace(" E ", " e ", ucwords($rt));
            return (($rt) ? ($rt) : "Zero");
        }
    }
}

if (!function_exists('formataDataPorExtenso')) {
    function formataDataPorExtenso($data = false)
    {
        if ($data) {
            $data = Carbon::parse($data)->format('Y-m-d');
            $mes = date('m', strtotime($data));
        } else {
            $mes = date('m');
            $data = date('Y-m-d');
        }
        $meses = array(
            '01' => 'Janeiro',
            '02' => 'Fevereiro',
            '03' => 'Marco',
            '04' => 'Abril',
            '05' => 'Maio',
            '06' => 'Junho',
            '07' => 'Julho',
            '08' => 'Agosto',
            '09' => 'Setembro',
            '10' => 'Outubro',
            '11' => 'Novembro',
            '12' => 'Dezembro'
        );
        $dias = array(
            0 => 'Domingo',
            1 => 'Segunda-feira',
            2 => 'Terca-feira',
            3 => 'Quarta-feira',
            4 => 'Quinta-feira',
            5 => 'Sexta-feira',
            6 => 'Sabado'
        );
        return $dias[date('w', strtotime($data))] . ', ' . date('d', strtotime($data)) . ' de ' . $meses[$mes] . ' de ' . date('Y', strtotime($data));
    }
}

if (!function_exists('formataNumero')) {
    function formataNumero($numero, $digitos = 2)
    {
        if ($numero === null) {
            return $numero;
        }
        return number_format($numero, $digitos, ",", ".");
    }
}

if (!function_exists('formataNcm')) {
    function formataNcm($string)
    {
        $string = str_pad(numeroLimpo($string), 8, "-", STR_PAD_RIGHT);
        return formataPorMascara($string, "####.##.##", false);
    }
}

if (!function_exists('formataCest')) {
    function formataCest($string)
    {
        $string = str_pad(numeroLimpo($string), 7, "-", STR_PAD_RIGHT);
        return formataPorMascara($string, "##.###.##", false);
    }
}

if (!function_exists('formataPorMascara')) {
    function formataPorMascara($string, $mascara, $somenteNumeros = true)
    {
        if ($somenteNumeros) {
            $string = numeroLimpo($string);
        }
        /* @var $caracteres int */
        $caracteres = substr_count($mascara, '#');
        $string = str_pad($string, $caracteres, "0", STR_PAD_LEFT);
        $indice = -1;
        for ($i = 0; $i < strlen($mascara); $i++) :
            if ($mascara[$i] == '#') {
                $mascara[$i] = $string[++$indice];
            }
        endfor;
        return $mascara;
    }
}

if (!function_exists('converteParaNumerico')) {
    function converteParaNumerico($numero)
    {
        return str_replace(',', '.', (str_replace('.', '', $numero)));
    }
}

if (!function_exists('modelUrl')) {
    function modelUrl($string)
    {
        return $output = ltrim(strtolower(preg_replace('/[A-Z]/', '-$0', $string)), '-');
    }
}

if (!function_exists('isNull')) {
    function isNull($str)
    {
        return "<span class='null'>$str</span>";
    }
}

if (!function_exists('checkPermissao')) {
    function checkPermissao($f, $g, $array)
    {
        foreach ($array as $item) {
            if (isset($item['filial']) && $item['filial'] === $f) {
                if (isset($item['grupo']) && $item['grupo'] === $g) {
                    return 'checked';
                }
            }
        }
        return false;
    }
}

if (!function_exists('linkRel')) {
    function linkRel($text, $url, $id)
    {
        $link = url($url . '/' . $id);
        return "<a href='$link'>$text</a>";
    }
}

if (!function_exists('formataCnpjCpf')) {
    function formataCnpjCpf($string, $fisica = '?')
    {
        if ($fisica == '?') {
            $string = numeroLimpo($string);
            if (strlen($string) <= 11) {
                $fisica = true;
            } else {
                $fisica = false;
            }
        }

        if ($fisica) {
            return formataPorMascara($string, '###.###.###-##');
        } else {
            return formataPorMascara($string, '##.###.###/####-##');
        }
    }
}

if (!function_exists('formataPorMascara')) {
    function formataPorMascara($string, $mascara, $somenteNumeros = true)
    {
        if ($somenteNumeros) {
            $string = numeroLimpo($string);
        }
        /* @var $caracteres int */
        $caracteres = substr_count($mascara, '#');
        $string = str_pad($string, $caracteres, "0", STR_PAD_LEFT);
        $indice = -1;
        for ($i = 0; $i < strlen($mascara); $i++) :
            if ($mascara[$i] == '#') {
                $mascara[$i] = $string[++$indice];
            }
        endfor;
        return $mascara;
    }
}

if (!function_exists('formataInscricaoEstadual')) {
    function formataInscricaoEstadual($string, $siglaestado)
    {
        $mascara = array(
            'AC' => '##.###.###/###-##',
            'AL' => '#########',
            'AP' => '#########',
            'AM' => '##.###.###-#',
            'BA' => '#######-##',
            'CE' => '########-#',
            'DF' => '###########-##',
            'ES' => '###.###.##-#',
            'GO' => '##.###.###-#',
            'MA' => '#########',
            'MT' => '##.###.###-#',
            'MS' => '#########',
            'MG' => '###.###.###/####',
            'PA' => '##-######-#',
            'PB' => '########-#',
            'PR' => '########-##',
            'PE' => '##.#.###.#######-#',
            'PI' => '#########',
            'RJ' => '##.###.##-#',
            'RN' => '##.###.###-#',
            'RS' => '###-#######',
            'RO' => '#############-#',
            'RR' => '########-#',
            'SC' => '###.###.###',
            'SP' => '###.###.###.###',
            'SE' => '#########-#',
            'TO' => '###########',
        );

        if (!array_key_exists($siglaestado, $mascara)) {
            return $string;
        } else {
            return formataPorMascara($string, $mascara[$siglaestado]);
        }
    }
}

if (!function_exists('formataEndereco')) {
    function formataEndereco($endereco = null, $numero = null, $complemento = null, $bairro = null, $cidade = null, $estado = null, $cep = null, $multilinha = false)
    {
        $retorno = $endereco;

        if (!empty($numero)) {
            $retorno .= ', ' . $numero;
        }

        $q = $retorno;

        if (!empty($complemento)) {
            $retorno .= ' - ' . $complemento;
        }

        if (!empty($bairro)) {
            $retorno .= ' - ' . $bairro;
        }

        if (!empty($cidade)) {
            $retorno .= ' - ' . $cidade;
            $q .= ' - ' . $cidade;
        }

        if (!empty($estado)) {
            $retorno .= ' / ' . $estado;
            $q .= ' / ' . $estado;
        }

        if (!empty($cep)) {
            $retorno .= ' - ' . formataCep($cep);
        }

        $q = urlencode($q);

        if ($multilinha) {
            $retorno = str_replace(" - ", "<br>", $retorno);
        }

        return "<a href='http://maps.google.com/maps?q=$q' target='_blank'>" . $retorno . "</a>";
    }
}

if (!function_exists('formataCep')) {
    function formataCep($string)
    {
        return formataPorMascara($string, "##.###-###");
    }
}

if (!function_exists('formataLocalEstoque')) {
    function formataLocalEstoque($corredor, $prateleira, $coluna, $bloco)
    {
        if (($corredor + $prateleira + $coluna + $bloco) > 0) {
            $corredor = str_pad($corredor, 2, '0', STR_PAD_LEFT);
            $prateleira = str_pad($prateleira, 2, '0', STR_PAD_LEFT);
            $coluna = str_pad($coluna, 2, '0', STR_PAD_LEFT);
            $bloco = str_pad($bloco, 2, '0', STR_PAD_LEFT);
            return "{$corredor}.{$prateleira}.{$coluna}.{$bloco}";
        }
        return '';
    }
}

if (!function_exists('formataNumeroNota')) {
    function formataNumeroNota($emitida, $serie, $numero, $modelo)
    {
        return (($emitida) ? "N-" : "T-") . $serie . "-" . (!empty($modelo) ? $modelo . "-" : "") . formataPorMascara($numero, "########");
    }
}

if (!function_exists('formataChaveNfe')) {
    function formataChaveNfe($chave)
    {
        return formataPorMascara($chave, "#### #### #### #### #### #### #### #### #### #### ####");
    }
}

if (!function_exists('removeAcentos')) {
    function removeAcentos($string)
    {
        $map = [
            'á' => 'a',
            'à' => 'a',
            'ã' => 'a',
            'â' => 'a',
            'é' => 'e',
            'ê' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            'ç' => 'c',
            'Á' => 'A',
            'À' => 'A',
            'Ã' => 'A',
            'Â' => 'A',
            'É' => 'E',
            'Ê' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ô' => 'O',
            'Õ' => 'O',
            'Ú' => 'U',
            'Ü' => 'U',
            'Ç' => 'C'
        ];
        return strtr($string, $map);
    }
}

if (!function_exists('titulo')) {
    function titulo($codigo, $descricao, $inativo, $digitos_codigo = 8)
    {
        if (is_string($descricao)) {
            $descricao = [$descricao];
        }

        $html = '';

        $i = 0;

        foreach ($descricao as $url => $titulo) {
            if (is_numeric($url)) {
                $url = null;
            }

            $html .= ' <li class="' . (empty($url) ? 'active' : '') . '">';
            $html .= (empty($url)) ? '' : "<a href='$url'>";
            $html .= (empty($inativo)) ? '' : '<del>';
            if ($i === 1 && !empty($codigo)) {
                $html .= '<small>' . formataCodigo($codigo, $digitos_codigo) . '</small> - ';
            }
            $html .= $titulo;
            $html .= (empty($inativo)) ? '' : '</del>';;
            $html .= (empty($url)) ? '' : "</a>";
            $html .= '</li>';

            $i++;
        }

        if (!empty($inativo)) {
            $html .= ' <li class="text-danger">Inativo desde ' . formataData($inativo, 'L') . '</li>';
        }

        return $html;
    }
}

if (!function_exists('inativo')) {
    function inativo($inativo)
    {
        if (!empty($inativo)) {
            return "<span class='label label-danger'>Inativo desde " . formataData($inativo, 'L') . "</span>";
        }
    }
}

if (!function_exists('listagemTitulo')) {
    function listagemTitulo($titulo, $inativo)
    {
        if (!empty($inativo)) {
            return "<del>$titulo</del>";
        } else {
            return $titulo;
        }
    }
}


if (!function_exists('formataEstoqueMinimoMaximo')) {
    function formataEstoqueMinimoMaximo($minimo, $maximo, $saldo = 'Vazio')
    {
        $html = '';
        if (!empty($minimo)) {
            $class = ($saldo !== 'Vazio' && $saldo < $minimo) ? 'text-danger' : '';
            $html .= " <span class='$class'>" . formataNumero($minimo, 0) . " <span class='glyphicon glyphicon-arrow-down'></span></span> ";
        }

        if (!empty($maximo)) {
            $class = ($saldo !== 'Vazio' && $saldo > $maximo) ? 'text-danger' : '';
            $html .= " <span class='$class'>" . formataNumero($maximo, 0) . " <span class='glyphicon glyphicon-arrow-up'></span></span> ";
        }

        if (empty($html)) {
            $html = '&nbsp;';
        }


        return $html;
    }
}

if (!function_exists('urlArrGet')) {
    function urlArrGet($arrGet = [], $path = null, $parameters = [], $secure = null)
    {
        foreach ($arrGet as $key => $data) {
            if ($data instanceof Carbon) {
                $arrGet[$key] = $data->format('Y-m-d H:i:s');
            }
        }
        return url($path, $parameters, $secure) . '?' . http_build_query($arrGet);
    }
}

if (!function_exists('numeroLimpo')) {
    function numeroLimpo($numero)
    {
        return preg_replace('/[^0-9]|\s+/', '', $numero);
    }
}

if (!function_exists('primeiraLetraMaiuscula')) {
    function primeiraLetraMaiuscula(
        $str,
        $delimiters = [
            " ",
            "-",
            ".",
            "'",
            "O'",
            "Mc",
        ],
        $exceptions = [
            "de",
            "do",
            "da",
            "dos",
            "das",
            "AC",
            "AL",
            "AM",
            "RO",
            "RR",
            "PA",
            "AP",
            "TO",
            "MA",
            "PI",
            "CE",
            "RN",
            "PB",
            "PE",
            "SE",
            "BA",
            "MG",
            "ES",
            "RJ",
            "SP",
            "PR",
            "SC",
            "RS",
            "MS",
            "MT",
            "GO",
            "DF",
            "HGR",
        ]
    ) {
        $result = '';

        foreach ($delimiters as $delimiter) {
            # If string has a delimiter
            if (strstr($str, $delimiter)) {

                $ucfirst = array();
                # Apply ucfirst to every word
                foreach (explode($delimiter, mb_strtolower($str)) as $word) {
                    $word = mb_convert_case($word, MB_CASE_TITLE);

                    # Working with exceptions
                    if (in_array(mb_strtoupper($word), $exceptions)) {
                        $word = mb_strtoupper($word);
                    } elseif (in_array(mb_strtolower($word), $exceptions)) {
                        $word = mb_strtolower($word);
                    } elseif (preg_match('/^M{0,4}(CM|CD|D?C{0,3})(XC|XL|L?X{0,3})(IX|IV|V?I{0,3})$/', mb_strtoupper($word))) {
                        # Is roman numerals? # http://stackoverflow.com/a/267405/437459
                        $word = mb_strtoupper($word);
                    }

                    $ucfirst[] = $word;
                }

                # string's first character uppercased
                $result = implode($delimiter, $ucfirst);
            }
        }

        return $result;
    }
}
