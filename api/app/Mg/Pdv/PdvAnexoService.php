<?php

namespace Mg\Pdv;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

use Mg\Negocio\Negocio;

class PdvAnexoService
{

    public static function diretorio(int $codnegocio)
    {
        return mascarar(numeroLimpo($codnegocio), '##/##/##/##/');
    }

    public static function nomeNovoArquivo(int $codnegocio, $pasta)
    {
        $ext = ($pasta == 'pdf') ? '.pdf' : '.jpeg';
        $arquivo = $pasta . '/' . date('Y-m-d-H-i-s') . '-' . uniqid() . $ext;
        return static::diretorio($codnegocio) . $arquivo;
    }

    public static function upload(int $codnegocio, string $pasta, string $ratio, string $anexoBase64)
    {
        switch ($pasta) {
            case 'confissao':
                return static::uploadConfissao($codnegocio, $pasta, $ratio, $anexoBase64);
                break;

            case 'imagem':
                return static::uploadImagem($codnegocio, $pasta, $ratio, $anexoBase64);
                break;

            case 'pdf':
                return static::uploadPdf($codnegocio, $pasta, $anexoBase64);
                break;

            default:
                # code...
                break;
        }
    }

    public static function marcaDataUsuarioConfissao(int $codnegocio)
    {
        $dir = static::diretorio($codnegocio);
        $anexos = Storage::disk('negocio-anexo')->allFiles("$dir/confissao");
        if (sizeof($anexos) > 0) {
            Negocio::where(['codnegocio' => $codnegocio])->whereNull('confissao')->update([
                'codusuarioconfissao' => Auth::user()->codusuario,
                'confissao' => Carbon::now(),
            ]);
        } else {
            Negocio::where(['codnegocio' => $codnegocio])->whereNotNull('confissao')->update([
                'codusuarioconfissao' => null,
                'confissao' => null,
            ]);
        }
    }


    public static function uploadImagem(int $codnegocio, string $pasta, string $ratio, string $anexoBase64)
    {
        // tira o anexo da string
        $data = explode(',', $anexoBase64);
        $jpeg = base64_decode($data[1]);
        $anexo = imagecreatefromstring($jpeg);

        // decide tamanho novo
        list($largura, $altura) = getimagesize($anexoBase64);
        $novaLargura = $largura;
        $novaAltura = $altura;
        $maxLargura = 1920;
        $maxAltura = 1080;
        do {
            if ($novaLargura > $maxLargura) {
                $prop = $maxLargura / $novaLargura;
                $novaAltura = floor($prop * $novaAltura);
                $novaLargura = floor($prop * $novaLargura);
            } else if ($novaAltura > $maxAltura) {
                $prop = $maxAltura / $novaAltura;
                $novaAltura = floor($prop * $novaAltura);
                $novaLargura = floor($prop * $novaLargura);
            }
        } while ($novaLargura > $maxLargura || $novaAltura > $maxAltura);

        // redimensiona
        $anexoRedimensionada = imagecreatetruecolor($novaLargura, $novaAltura);
        imagecopyresized($anexoRedimensionada, $anexo, 0, 0, 0, 0, $novaLargura, $novaAltura, $largura, $altura);

        // renderiza anexo para variavel       
        ob_start();
        imagejpeg($anexoRedimensionada);
        $data = ob_get_contents();
        ob_end_clean();

        // salva
        $anexo = static::nomeNovoArquivo($codnegocio, $pasta);
        Storage::disk('negocio-anexo')->put($anexo, $data);
        return $anexo;
    }

    public static function uploadConfissao(int $codnegocio, string $pasta, string $ratio, string $anexoBase64)
    {
        // tira o anexo da string
        $data = explode(',', $anexoBase64);
        $jpeg = base64_decode($data[1]);
        $anexo = imagecreatefromstring($jpeg);

        // deixa em preto e branco
        imagefilter($anexo, IMG_FILTER_GRAYSCALE);
        imagefilter($anexo, IMG_FILTER_BRIGHTNESS, -30);
        imagefilter($anexo, IMG_FILTER_CONTRAST, -30);

        // redimensiona
        list($largura, $altura) = getimagesize($anexoBase64);
        if ($ratio == '1:2') {
            $novaLargura = 500;
            $novaAltura = 1000;
        } else {
            $novaLargura = $largura;
            $novaAltura = $altura;
            $maxLargura = 1000;
            $maxAltura = 1000;
            do {
                if ($novaLargura > $maxLargura) {
                    $prop = $maxLargura / $novaLargura;
                    $novaAltura = floor($prop * $novaAltura);
                    $novaLargura = floor($prop * $novaLargura);
                } else if ($novaAltura > $maxAltura) {
                    $prop = $maxAltura / $novaAltura;
                    $novaAltura = floor($prop * $novaAltura);
                    $novaLargura = floor($prop * $novaLargura);
                }
            } while ($novaLargura > $maxLargura || $novaAltura > $maxAltura);
        }
        $anexoRedimensionada = imagecreatetruecolor($novaLargura, $novaAltura);
        imagecopyresized($anexoRedimensionada, $anexo, 0, 0, 0, 0, $novaLargura, $novaAltura, $largura, $altura);

        // renderiza anexo para variavel       
        ob_start();
        imagejpeg($anexoRedimensionada);
        $data = ob_get_contents();
        ob_end_clean();

        // salva
        $anexo = static::nomeNovoArquivo($codnegocio, $pasta);
        Storage::disk('negocio-anexo')->put($anexo, $data);

        // marca como conferido
        static::marcaDataUsuarioConfissao($codnegocio);

        return $anexo;
    }

    public static function uploadPdf(int $codnegocio, string $pasta, string $anexoBase64)
    {
        // tira o anexo da string
        $data = explode(',', $anexoBase64);
        $pdf = base64_decode($data[1]);
        // salva
        $anexo = static::nomeNovoArquivo($codnegocio, $pasta);
        Storage::disk('negocio-anexo')->put($anexo, $pdf);
        return $anexo;
    }

    public static function listagem(int $codnegocio, bool $fullpath = false)
    {
        $dir = static::diretorio($codnegocio);
        $anexos = Storage::disk('negocio-anexo')->allFiles($dir);
        sort($anexos, SORT_STRING);
        $ret = [
            'confissao' => [],
            'imagem' => [],
            'pdf' => [],
            'lixeira' => [],
        ];
        foreach ($anexos as $i => $anexo) {
            list($idx, $arquivo) = explode('/', str_replace($dir, '', $anexo));
            if (!$fullpath) {
                $anexo = $arquivo;
            }
            if (!isset($ret[$idx])) {
                $ret[$idx] = [];
            }
            $ret[$idx][] = $anexo;
            // $anexos[$i] = str_replace($dir, '', $anexo);
        }
        return $ret;
        // return $anexos;
    }

    public static function base64(int $codnegocio)
    {
        $listagem = static::listagem($codnegocio, true);
        foreach ($listagem as $pasta => $anexos) {
            if (!in_array($pasta, ['confissao', 'imagem'])) {
                continue;
            }
            foreach ($anexos as $i => $anexo) {
                $base64 = 'data:image/jpeg;base64,' . base64_encode(Storage::disk('negocio-anexo')->get($anexo));
                $listagem[$pasta][$i] = $base64;
            }
        }
        return $listagem;
    }

    public static function excluir(int $codnegocio, string $pasta, string $anexo)
    {
        $dir = static::diretorio($codnegocio);
        $antiga = "{$dir}/{$pasta}/{$anexo}";
        $nova = "{$dir}/lixeira/{$anexo}";
        Storage::disk('negocio-anexo')->move($antiga, $nova);
        static::marcaDataUsuarioConfissao($codnegocio);
        return $nova;
    }

    public static function sugerir(string $anexoBase64)
    {
        // tira o anexo da string
        $data = explode(',', $anexoBase64);
        $jpeg = base64_decode($data[1]);
        $anexo = imagecreatefromstring($jpeg);

        // salva ele num arquivo temporario
        $arquivo = tempnam('/tmp', 'anexo-') . '.jpeg';
        imagejpeg($anexo, $arquivo);

        // roda o tesseract OCR para extrair o texto
        $cmd = "tesseract --psm 6 '{$arquivo}' -";
        $ret = shell_exec($cmd);

        // apaga arquivo temporario
        unlink($arquivo);

        // explode o texto em linhas
        $linhas = preg_split('/$\R?^/m', $ret);

        // pega o soudex de conferencia e totalizando
        $sdexConferencia = soundex('conferencia');
        $sdexTotalizando = soundex('totalizando');

        // inicializa variaveis buscadas
        $valor = null;
        $codnegocio = null;

        // percorre as linhas
        foreach ($linhas as $linha) {

            // quebra a linha em palavras
            $linha = preg_replace("/[^A-Za-z0-9\,\.\# ]/", ' ', $linha);
            $linha = preg_replace('/\s+/', ' ', $linha);
            $palavras = preg_split('/\s+/', $linha, -1, PREG_SPLIT_NO_EMPTY);

            // percorre as palavras
            foreach ($palavras as $i => $palavra) {

                if (empty($codnegocio)) {
                    // verifica a similiaridade da palavra conferencia
                    $perc = null;
                    similar_text('conferencia', $palavra, $perc);

                    // calcula o soudex da palavra                
                    $sdex  = soundex($palavra);

                    // se é mais de 80% similar
                    // ou é o mesmo soudex, a proxima palavra é o numero do negocio
                    // Ex: Romaneio de conferência #03732930 sem
                    if ($perc > 80 || $sdex == $sdexConferencia) {
                        $codnegocio = numeroLimpo($palavras[$i + 1]);
                        continue;
                    }

                    // se comeca com #, tem exatamente 9 caracteres, 
                    // sendo que os ultimos oito são numericos
                    if (substr($palavra, 0, 1) == '#' && strlen($palavra) == 9) {
                        if (numeroLimpo($palavra) == substr($palavra, 1, 8)) {
                            $codnegocio = numeroLimpo($palavra);
                            continue;
                        }
                    }
                }

                if (empty($valor)) {
                    // verifica a similiaridade da palavra totalizando
                    $perc = null;
                    similar_text('totalizando', $palavra, $perc);

                    // calcla o soudenx da palavra
                    $sdex  = soundex($palavra);

                    // se é mais de 80% similar
                    // ou é o mesmo soudex, daqui duas palavras tem o valor
                    // Ex: Totalizando R$ 32,53 (trinta e dois reais e
                    if ($perc > 80 || $sdex == $sdexTotalizando) {
                        // dd($linha);
                        // dd($palavras[$i + 2]);
                        $valor = numeroLimpo($palavras[$i + 2]) / 100;
                        break;
                    }
                }
            }
        }

        $encontrados = static::procurar($codnegocio, $valor);

        // retorna tudo que econtrou
        return [
            'codnegocio' => $codnegocio,
            'valor' => $valor,
            'encontrados' => $encontrados
        ];
    }

    static function procurar($codnegocio, $valor)
    {
        // verifica se encontra no banco de dados um negocio com esse valor e codnegocio
        return Negocio::where(['codnegocio' => $codnegocio, 'valortotal' => $valor])->count();
    }

    static function faltando($ano, $mes)
    {
        // busca todos negocios a prazo com saldo em aberto
        $sql = "
            with s as (
                select t.codnegocioformapagamento, abs(sum(t.saldo)) as valorsaldo
                from tbltitulo t 
                group by t.codnegocioformapagamento 
            )
            select distinct
                n.codfilial, 
                f.filial, 
                n.codpdv, 
                p.apelido pdv, 
                n.codusuario, 
                u.usuario, 
                n.codpessoa,
                pe.fantasia,
                date_trunc('day', n.lancamento) as data,
                n.lancamento, 
                n.codnegocio, 
                n.valortotal,
                s.valorsaldo
            from tblnegocio n
            inner join tblnegocioformapagamento nfp on (nfp.codnegocio = n.codnegocio)
            inner join tblformapagamento fp on (fp.codformapagamento = nfp.codformapagamento)
            inner join tblfilial f on (f.codfilial = n.codfilial)
            inner join tblpessoa pe on (pe.codpessoa = n.codpessoa)
            inner join tblusuario u on (u.codusuario = n.codusuario)
            inner join s on (s.codnegocioformapagamento = nfp.codnegocioformapagamento)
            inner join tblnaturezaoperacao nat on (nat.codnaturezaoperacao = n.codnaturezaoperacao)
            left join tblpdv p on (p.codpdv = n.codpdv)
            where n.codnegociostatus = 2
            and date_trunc('month', n.lancamento) = :mes
            and n.confissao is null
            and nat.venda
        ";

        // filtra o mes selecionado
        $regs = collect(DB::select($sql, [
            'mes' => "{$ano}-{$mes}-01"
        ]));
        $datas = $regs->groupBy('data');

        // monta esqueleto retorno
        $ret = (object) [
            'resumo' => [],
            'datas' => [],
        ];

        // percorre todos registros
        foreach ($datas as $data => $itens) {

            // pega somente a data, remove hora
            $data = substr($data, 0, 10);

            // agrupa as filiais
            $codfilials = $itens->groupBy('codfilial')->sortKeys();

            // monta esqueleto de retorno da data
            $retData = (object) [
                'data' => $data,
                'faltando' => 0,
                'filiais' => [],
            ];

            // percorre as filiais
            foreach ($codfilials as $codfilial => $negs) {

                // monta esqueleto de retorno da filial
                $retFilial = (object) [
                    'codfilial' => $codfilial,
                    'filial' => $negs[0]->filial,
                    'faltando' => count($negs),
                    'negocios' => $negs,
                ];
                $retData->faltando += $retFilial->faltando;
                $retData->filiais[] = $retFilial;
            }


            // se tem anexo faltando adiciona a filial no array de retorno
            if ($retData->faltando > 0) {
                // concatena a data
                $ret->datas[] = $retData;
                $ret->resumo[] = $data;
            }
        }
        return $ret;
    }

    public static function ignorarConfissao($codnegocio)
    {
        return Negocio::where(['codnegocio' => $codnegocio])->whereNull('confissao')->update([
            'codusuarioconfissao' => Auth::user()->codusuario,
            'confissao' => Carbon::now(),
        ]);
    }
}
