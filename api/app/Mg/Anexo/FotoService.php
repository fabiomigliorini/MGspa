<?php

namespace Mg\Anexo;

use Illuminate\Support\Facades\Storage;

/**
 * Foto anexada a um registro, no disco do dono: cada dominio tem o seu
 * (maquineta-anexo, portador-anexo...) e a pasta e' o
 * registro. Sem coluna no banco: o disco e' o indice. O servico nao sabe de
 * quem e' a foto.
 */
class FotoService
{
    public static function fotos(string $disco, string $pasta): array
    {
        $arquivos = Storage::disk($disco)->files($pasta);
        sort($arquivos, SORT_STRING);
        return array_map('basename', $arquivos);
    }

    public static function gravar(string $disco, string $pasta, string $anexoBase64): string
    {
        $arquivo = $pasta . '/' . date('Y-m-d-H-i-s') . '-' . uniqid() . '.jpeg';
        Storage::disk($disco)->put($arquivo, static::jpeg($anexoBase64, 1600, 1600));
        return basename($arquivo);
    }

    public static function mostrar(string $disco, string $pasta, string $arquivo)
    {
        return Storage::disk($disco)->response(static::caminho($disco, $pasta, $arquivo));
    }

    public static function excluir(string $disco, string $pasta, string $arquivo): void
    {
        Storage::disk($disco)->delete(static::caminho($disco, $pasta, $arquivo));
    }

    private static function caminho(string $disco, string $pasta, string $arquivo): string
    {
        $caminho = $pasta . '/' . basename($arquivo);
        if (!Storage::disk($disco)->exists($caminho)) {
            abort(404, 'Foto inexistente!');
        }
        return $caminho;
    }

    // Foto (data URL base64) em JPEG, reduzida para caber em
    // $maxLargura x $maxAltura
    public static function jpeg(string $anexoBase64, int $maxLargura = 1920, int $maxAltura = 1080): string
    {
        // tira o anexo da string
        $data = explode(',', $anexoBase64);
        $jpeg = base64_decode($data[1]);
        $anexo = imagecreatefromstring($jpeg);

        // decide tamanho novo
        list($largura, $altura) = getimagesize($anexoBase64);
        $novaLargura = $largura;
        $novaAltura = $altura;
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
        return $data;
    }
}
