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
                return $data->isoFormat('DD MMMM YYYY');
                break;

            case 'EC':
            case 'EXTENSOCURTO':
                return $data->isoFormat('MMM/YYYY');
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
        return mascarar(documentoLimpo($numero), '##.###.###/####-##');
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
        if ($fisica === '?' || $fisica === null) {
            $string = documentoLimpo($string);
            $fisica = ctype_digit($string) && strlen($string) <= 11;
        }

        if ($fisica) {
            return formataCpf($string);
        } else {
            return formataCnpj($string);
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

if (!function_exists('formataTelefone')) {
    // celular (99) 9-9999-9999, fixo (99) 9999-9999: o mesmo formataTelefone do @components/formatters
    function formataTelefone($string)
    {
        $string = numeroLimpo($string ?? '');
        switch (strlen($string)) {
            case 11:
                return formataPorMascara($string, '(##) #-####-####');
            case 10:
                return formataPorMascara($string, '(##) ####-####');
            case 9:
                return formataPorMascara($string, '#-####-####');
            case 8:
                return formataPorMascara($string, '####-####');
        }
        return $string;
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
        return formataPorMascara(chaveDfeLimpa($chave), "#### #### #### #### #### #### #### #### #### #### ####", false);
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

/*
 * CPF e CNPJ (TASK-283). Desde jul/2026 a Receita emite CNPJ com letras: 14 caracteres, os
 * 12 primeiros de A-Z/0-9 e os 2 dígitos verificadores numéricos. O banco guarda texto
 * maiúsculo com zeros à esquerda, cada um no seu tamanho: CPF com 11, CNPJ com 14.
 */

if (!function_exists('documentoLimpo')) {
    // Tira pontuação e espaço, mantém letras (em maiúsculo) e dígitos. Não completa zeros.
    function documentoLimpo($documento)
    {
        return preg_replace('/[^0-9A-Z]/', '', strtoupper((string) $documento));
    }
}

if (!function_exists('cnpjLimpo')) {
    // CNPJ no formato do banco: 14 caracteres, maiúsculo, zeros à esquerda. Vazio vira null.
    function cnpjLimpo($cnpj)
    {
        $cnpj = documentoLimpo($cnpj);
        if ($cnpj === '') {
            return null;
        }
        return str_pad($cnpj, 14, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('cpfLimpo')) {
    // CPF no formato do banco: 11 dígitos, zeros à esquerda. Vazio vira null.
    function cpfLimpo($cpf)
    {
        $cpf = numeroLimpo((string) $cpf);
        if ($cpf === '') {
            return null;
        }
        return str_pad($cpf, 11, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('cnpjCpfLimpo')) {
    // CPF (11) ou CNPJ (14) no formato do banco. Sem $fisica, é CPF quando só tem dígitos,
    // cabe em 11 e o dígito de CPF bate; senão é CNPJ.
    function cnpjCpfLimpo($documento, $fisica = null)
    {
        $documento = documentoLimpo($documento);
        if ($documento === '') {
            return null;
        }
        if ($fisica === null) {
            $fisica = ctype_digit($documento)
                && strlen($documento) <= 11
                && cpfValido($documento);
        }
        return $fisica ? cpfLimpo($documento) : cnpjLimpo($documento);
    }
}

if (!function_exists('cpfValido')) {
    function cpfValido($cpf)
    {
        $cpf = numeroLimpo((string) $cpf);
        if ($cpf === '' || strlen($cpf) > 11) {
            return false;
        }
        $cpf = str_pad($cpf, 11, '0', STR_PAD_LEFT);
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }
        for ($t = 9; $t < 11; $t++) {
            $d = 0;
            for ($c = 0; $c < $t; $c++) {
                $d += (int) $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ((int) $cpf[$t] !== $d) {
                return false;
            }
        }
        return true;
    }
}

if (!function_exists('cnpjValido')) {
    // Módulo 11 com cada caractere valendo (ASCII - 48): dígito vale ele mesmo, A=17, B=18...
    function cnpjValido($cnpj)
    {
        $cnpj = documentoLimpo($cnpj);
        if ($cnpj === '' || strlen($cnpj) > 14) {
            return false;
        }
        $cnpj = str_pad($cnpj, 14, '0', STR_PAD_LEFT);
        if (!preg_match('/^[0-9A-Z]{12}[0-9]{2}$/', $cnpj)) {
            return false;
        }
        if (preg_match('/^(.)\1{13}$/', $cnpj)) {
            return false;
        }
        $pesos = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        foreach ([12, 13] as $posicao) {
            $soma = 0;
            for ($i = 0; $i < $posicao; $i++) {
                $soma += (ord($cnpj[$i]) - 48) * $pesos[$i + 13 - $posicao];
            }
            $resto = $soma % 11;
            $digito = ($resto < 2) ? 0 : 11 - $resto;
            if ((int) $cnpj[$posicao] !== $digito) {
                return false;
            }
        }
        return true;
    }
}

if (!function_exists('cnpjCpfValido')) {
    function cnpjCpfValido($documento)
    {
        $documento = documentoLimpo($documento);
        if (ctype_digit($documento) && strlen($documento) <= 11 && cpfValido($documento)) {
            return true;
        }
        return cnpjValido($documento);
    }
}

if (!function_exists('chaveDfeLimpa')) {
    // Chave de acesso (NF-e, NFC-e, MDF-e 44; NFS-e 50) sem pontuação. Pode ter letras nas
    // posições do CNPJ do emitente.
    function chaveDfeLimpa($chave)
    {
        return documentoLimpo($chave);
    }
}

if (!function_exists('digitoChaveDfe')) {
    // Dígito verificador da chave de 44 posições (módulo 11, pesos 2 a 9 da direita para a
    // esquerda), com cada caractere valendo (ASCII - 48). Recebe as 43 primeiras posições.
    function digitoChaveDfe($chave43)
    {
        $chave43 = substr(chaveDfeLimpa($chave43), 0, 43);
        if (strlen($chave43) != 43) {
            return -1;
        }
        $soma = 0;
        $peso = 2;
        for ($i = 42; $i >= 0; $i--) {
            $soma += (ord($chave43[$i]) - 48) * $peso;
            $peso = ($peso == 9) ? 2 : $peso + 1;
        }
        $resto = $soma % 11;
        return ($resto < 2) ? 0 : 11 - $resto;
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
