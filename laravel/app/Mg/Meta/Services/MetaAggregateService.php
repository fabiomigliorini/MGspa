<?php

namespace Mg\Meta\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Mg\Meta\Meta;
use Mg\Meta\MetaService;
use Mg\Meta\MetaUnidadeNegocio;
use Mg\Meta\MetaUnidadeNegocioPessoa;

class MetaAggregateService
{
    public static function criar(array $data): Meta
    {
        $meta = Meta::create([
            'periodoinicial' => $data['periodoinicial'],
            'periodofinal' => $data['periodofinal'],
            'status' => $data['status'] ?? MetaService::META_STATUS_ABERTA,
            'processando' => false,
            'percentualcomissaovendedor' => $data['percentualcomissaovendedor'] ?? null,
            'percentualcomissaovendedormeta' => $data['percentualcomissaovendedormeta'] ?? null,
            'percentualcomissaoxerox' => $data['percentualcomissaoxerox'] ?? null,
            'percentualcomissaosubgerentemeta' => $data['percentualcomissaosubgerentemeta'] ?? null,
            'premioprimeirovendedorfilial' => $data['premioprimeirovendedorfilial'] ?? null,
            'observacoes' => $data['observacoes'] ?? null,
        ]);

        Log::info('MetaAggregateService - Meta criada', [
            'codmeta' => $meta->codmeta,
        ]);

        if (!empty($data['unidades'])) {
            static::salvarUnidades($meta, $data['unidades']);
        }

        return $meta;
    }

    public static function atualizar(Meta $meta, array $data): Meta
    {
        if ($meta->status === MetaService::META_STATUS_FECHADA) {
            throw new Exception("Meta #{$meta->codmeta} esta fechada e nao pode ser alterada.");
        }

        $camposMeta = array_intersect_key($data, array_flip([
            'periodoinicial',
            'periodofinal',
            'percentualcomissaovendedor',
            'percentualcomissaovendedormeta',
            'percentualcomissaoxerox',
            'percentualcomissaosubgerentemeta',
            'premioprimeirovendedorfilial',
            'observacoes',
        ]));

        if (!empty($camposMeta)) {
            $meta->update($camposMeta);
        }

        Log::info('MetaAggregateService - Meta atualizada', [
            'codmeta' => $meta->codmeta,
        ]);

        if (array_key_exists('unidades', $data)) {
            static::mergeUnidades($meta, $data['unidades'] ?? []);
        }

        return $meta->fresh();
    }

    public static function excluir(Meta $meta): void
    {
        if ($meta->status === MetaService::META_STATUS_FECHADA) {
            throw new Exception("Meta #{$meta->codmeta} esta fechada e nao pode ser excluida.");
        }

        if ($meta->processando) {
            throw new Exception("Meta #{$meta->codmeta} esta sendo processada. Aguarde.");
        }

        MetaUnidadeNegocioPessoa::where('codmeta', $meta->codmeta)->delete();
        MetaUnidadeNegocio::where('codmeta', $meta->codmeta)->delete();
        $meta->delete();

        Log::info('MetaAggregateService - Meta excluida', [
            'codmeta' => $meta->codmeta,
        ]);
    }

    // --- UNIDADES ---

    private static function salvarUnidades(Meta $meta, array $unidades): void
    {
        foreach ($unidades as $unidadeData) {
            $unidade = MetaUnidadeNegocio::create([
                'codmeta' => $meta->codmeta,
                'codunidadenegocio' => $unidadeData['codunidadenegocio'],
                'valormeta' => $unidadeData['valormeta'] ?? null,
                'valormetacaixa' => $unidadeData['valormetacaixa'] ?? null,
                'valormetavendedor' => $unidadeData['valormetavendedor'] ?? null,
                'valormetaxerox' => $unidadeData['valormetaxerox'] ?? null,
            ]);

            if (!empty($unidadeData['pessoas'])) {
                static::salvarPessoas($meta, $unidade->codunidadenegocio, $unidadeData['pessoas']);
            }
        }
    }

    private static function mergeUnidades(Meta $meta, array $unidades): void
    {
        $idsRecebidos = [];

        foreach ($unidades as $unidadeData) {
            $destroy = $unidadeData['_destroy'] ?? false;
            $codmetaunidadenegocio = $unidadeData['codmetaunidadenegocio'] ?? null;

            if ($codmetaunidadenegocio) {
                $unidade = MetaUnidadeNegocio::where('codmetaunidadenegocio', $codmetaunidadenegocio)
                    ->where('codmeta', $meta->codmeta)
                    ->firstOrFail();

                if ($destroy) {
                    MetaUnidadeNegocioPessoa::where('codmeta', $meta->codmeta)
                        ->where('codunidadenegocio', $unidade->codunidadenegocio)
                        ->delete();
                    $unidade->delete();
                    continue;
                }

                $unidade->update([
                    'codunidadenegocio' => $unidadeData['codunidadenegocio'],
                    'valormeta' => $unidadeData['valormeta'] ?? $unidade->valormeta,
                    'valormetacaixa' => $unidadeData['valormetacaixa'] ?? $unidade->valormetacaixa,
                    'valormetavendedor' => $unidadeData['valormetavendedor'] ?? $unidade->valormetavendedor,
                    'valormetaxerox' => $unidadeData['valormetaxerox'] ?? $unidade->valormetaxerox,
                ]);

                $idsRecebidos[] = $unidade->codmetaunidadenegocio;
            } else {
                if ($destroy) {
                    continue;
                }

                $unidade = MetaUnidadeNegocio::create([
                    'codmeta' => $meta->codmeta,
                    'codunidadenegocio' => $unidadeData['codunidadenegocio'],
                    'valormeta' => $unidadeData['valormeta'] ?? null,
                    'valormetacaixa' => $unidadeData['valormetacaixa'] ?? null,
                    'valormetavendedor' => $unidadeData['valormetavendedor'] ?? null,
                    'valormetaxerox' => $unidadeData['valormetaxerox'] ?? null,
                ]);

                $idsRecebidos[] = $unidade->codmetaunidadenegocio;
            }

            if (array_key_exists('pessoas', $unidadeData)) {
                static::mergePessoas($meta, $unidade->codunidadenegocio, $unidadeData['pessoas'] ?? []);
            }
        }
    }

    // --- PESSOAS ---

    private static function salvarPessoas(Meta $meta, int $codunidadenegocio, array $pessoas): void
    {
        foreach ($pessoas as $pessoaData) {
            MetaUnidadeNegocioPessoa::create([
                'codmeta' => $meta->codmeta,
                'codunidadenegocio' => $codunidadenegocio,
                'codpessoa' => $pessoaData['codpessoa'],
                'percentualvenda' => $pessoaData['percentualvenda'] ?? null,
                'percentualcaixa' => $pessoaData['percentualcaixa'] ?? null,
                'percentualsubgerente' => $pessoaData['percentualsubgerente'] ?? null,
                'percentualxerox' => $pessoaData['percentualxerox'] ?? null,
                'valorfixo' => $pessoaData['valorfixo'] ?? null,
                'descricaovalorfixo' => $pessoaData['descricaovalorfixo'] ?? null,
            ]);
        }
    }

    private static function mergePessoas(Meta $meta, int $codunidadenegocio, array $pessoas): void
    {
        foreach ($pessoas as $pessoaData) {
            $destroy = $pessoaData['_destroy'] ?? false;
            $codmetaunidadenegociopessoa = $pessoaData['codmetaunidadenegociopessoa'] ?? null;

            if ($codmetaunidadenegociopessoa) {
                $pessoa = MetaUnidadeNegocioPessoa::where('codmetaunidadenegociopessoa', $codmetaunidadenegociopessoa)
                    ->where('codmeta', $meta->codmeta)
                    ->firstOrFail();

                if ($destroy) {
                    $pessoa->delete();
                    continue;
                }

                $pessoa->update([
                    'codpessoa' => $pessoaData['codpessoa'],
                    'codunidadenegocio' => $codunidadenegocio,
                    'percentualvenda' => $pessoaData['percentualvenda'] ?? $pessoa->percentualvenda,
                    'percentualcaixa' => $pessoaData['percentualcaixa'] ?? $pessoa->percentualcaixa,
                    'percentualsubgerente' => $pessoaData['percentualsubgerente'] ?? $pessoa->percentualsubgerente,
                    'percentualxerox' => $pessoaData['percentualxerox'] ?? $pessoa->percentualxerox,
                    'valorfixo' => $pessoaData['valorfixo'] ?? $pessoa->valorfixo,
                    'descricaovalorfixo' => $pessoaData['descricaovalorfixo'] ?? $pessoa->descricaovalorfixo,
                ]);
            } else {
                if ($destroy) {
                    continue;
                }

                MetaUnidadeNegocioPessoa::create([
                    'codmeta' => $meta->codmeta,
                    'codunidadenegocio' => $codunidadenegocio,
                    'codpessoa' => $pessoaData['codpessoa'],
                    'percentualvenda' => $pessoaData['percentualvenda'] ?? null,
                    'percentualcaixa' => $pessoaData['percentualcaixa'] ?? null,
                    'percentualsubgerente' => $pessoaData['percentualsubgerente'] ?? null,
                    'percentualxerox' => $pessoaData['percentualxerox'] ?? null,
                    'valorfixo' => $pessoaData['valorfixo'] ?? null,
                    'descricaovalorfixo' => $pessoaData['descricaovalorfixo'] ?? null,
                ]);
            }
        }
    }
}
