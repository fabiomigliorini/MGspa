<?php

namespace Mg\Pdv;

use Illuminate\Support\Facades\Storage;

// use Mg\Negocio\Negocio;

class PdvAnexoService
{

    public static function diretorio(int $codnegocio)
    {
        return mascarar(numeroLimpo($codnegocio), '##/##/##/##/');
    }

    public static function nomeNovoArquivo(int $codnegocio, $pasta)
    {
        $ext = ($pasta == 'pdf')?'.pdf':'.jpeg';
        $arquivo = $pasta . '/' . date('Y-m-d-H-i-s') . '-' . uniqid() . $ext;
        return static::diretorio($codnegocio) . $arquivo;
    }

    public static function upload(int $codnegocio, string $pasta, string $anexoBase64)
    {
        switch ($pasta) {
            case 'confissao':
                return static::uploadConfissao($codnegocio, $pasta, $anexoBase64);
                break;

            case 'imagem':
                return static::uploadImagem($codnegocio, $pasta, $anexoBase64);
                break;

                case 'pdf':
                    return static::uploadPdf($codnegocio, $pasta, $anexoBase64);
                    break;
    
            default:
                # code...
                break;
        }
    }


    public static function uploadImagem(int $codnegocio, string $pasta, string $anexoBase64)
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

    public static function uploadConfissao(int $codnegocio, string $pasta, string $anexoBase64)
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
        $novaLargura = 500;
        $novaAltura = 1000;
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

    public static function base64 (int $codnegocio)
    {
        $listagem = static::listagem($codnegocio, true);
        foreach ($listagem as $pasta => $anexos) {
            if (!in_array($pasta, ['confissao','imagem'])) {
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
        return $nova;
    }
}
