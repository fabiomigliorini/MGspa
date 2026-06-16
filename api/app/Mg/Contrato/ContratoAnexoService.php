<?php

namespace Mg\Contrato;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Anexos (PDFs) do contrato — espelha o padrão do PessoaAnexoService: arquivos
 * num disk do Storage (contrato-anexo), diretório mascarado por codcontrato,
 * índice JSON com label/observações/auditoria e pasta de lixeira. Vários
 * anexos por contrato.
 */
class ContratoAnexoService
{
    const DISK = 'contrato-anexo';

    public static function diretorio(int $codcontrato): string
    {
        return mascarar(numeroLimpo($codcontrato), '##/##/##/##/');
    }

    public static function upload(int $codcontrato, UploadedFile $arquivo, ?string $label = null): array
    {
        $dir = static::diretorio($codcontrato);
        $unq = uniqid();
        $nomeOriginal = preg_replace('/[^A-Za-z0-9._-]/', '_', $arquivo->getClientOriginalName());
        $nome = "{$unq}-{$nomeOriginal}";
        Storage::disk(static::DISK)->putFileAs("{$dir}/ativos", $arquivo, $nome);

        $indice = static::indice($dir);
        $codusuario = Auth::user()->codusuario ?? null;
        $data = date('Y-m-d H:i:s');
        $indice[$nome] = [
            'label' => $label ?: $arquivo->getClientOriginalName(),
            'observacoes' => null,
            'codusuariocriacao' => $codusuario,
            'criacao' => $data,
        ];
        static::salvarIndice($dir, $indice);

        return ['nome' => $nome];
    }

    public static function listagem(int $codcontrato): array
    {
        $dir = static::diretorio($codcontrato);
        $indice = static::indice($dir);
        $ret = [];
        try {
            $arqs = Storage::disk(static::DISK)->allFiles("{$dir}/ativos");
        } catch (\Throwable $e) {
            $arqs = [];
        }
        foreach ($arqs as $arq) {
            $nome = basename($arq);
            $ret[] = [
                'nome' => $nome,
                'label' => $indice[$nome]['label'] ?? $nome,
                'tipo' => pathinfo($nome, PATHINFO_EXTENSION),
                'size' => Storage::disk(static::DISK)->size($arq),
                'criacao' => $indice[$nome]['criacao'] ?? null,
                'lastModified' => Carbon::createFromTimestamp(Storage::disk(static::DISK)->lastModified($arq)),
            ];
        }
        return $ret;
    }

    public static function download(int $codcontrato, string $nome)
    {
        $dir = static::diretorio($codcontrato);
        return Storage::disk(static::DISK)->response("{$dir}/ativos/{$nome}");
    }

    public static function excluir(int $codcontrato, string $nome): bool
    {
        $dir = static::diretorio($codcontrato);
        $de = "{$dir}/ativos/{$nome}";
        $para = "{$dir}/lixeira/{$nome}";
        if (Storage::disk(static::DISK)->exists($de)) {
            Storage::disk(static::DISK)->move($de, $para);
        }
        $indice = static::indice($dir);
        unset($indice[$nome]);
        static::salvarIndice($dir, $indice);
        return true;
    }

    protected static function indice(string $dir): array
    {
        $caminho = "{$dir}/indice.json";
        if (!Storage::disk(static::DISK)->exists($caminho)) {
            return [];
        }
        return json_decode(Storage::disk(static::DISK)->get($caminho), true) ?: [];
    }

    protected static function salvarIndice(string $dir, array $indice): void
    {
        Storage::disk(static::DISK)->put("{$dir}/indice.json", json_encode($indice));
    }
}
