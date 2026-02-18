<?php

namespace Mg\Meta\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Mg\Meta\Meta;
use Mg\Meta\MetaService;
use Mg\Meta\MetaUnidadeNegocio;
use Mg\Meta\MetaUnidadeNegocioPessoa;
use Mg\Meta\MetaUnidadeNegocioPessoaFixo;

class MetaAggregateService
{
    public static function criar(array $data): Meta
    {
        $meta = Meta::create([
            'periodoinicial' => $data['periodoinicial'],
            'periodofinal' => $data['periodofinal'],
            'status' => $data['status'] ?? MetaService::META_STATUS_ABERTA,
            'processando' => false,
            'observacoes' => $data['observacoes'] ?? null,
        ]);

        Log::info('MetaAggregateService - Meta criada', [
            'codmeta' => $meta->codmeta,
        ]);

        if (!empty($data['unidades'])) {
            static::mergeUnidades($meta, $data['unidades']);
        } else {
            $ultimaMeta = Meta::where('codmeta', '!=', $meta->codmeta)
                ->orderBy('codmeta', 'desc')
                ->first();

            if ($ultimaMeta) {
                MetaService::duplicarConfiguracao($ultimaMeta, $meta);
                Log::info('MetaAggregateService - Configuracao copiada da meta anterior', [
                    'codmetaorigem' => $ultimaMeta->codmeta,
                    'codmetadestino' => $meta->codmeta,
                ]);
            }
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

        MetaUnidadeNegocioPessoaFixo::where('codmeta', $meta->codmeta)->delete();
        MetaUnidadeNegocioPessoa::where('codmeta', $meta->codmeta)->delete();
        MetaUnidadeNegocio::where('codmeta', $meta->codmeta)->delete();
        $meta->delete();

        Log::info('MetaAggregateService - Meta excluida', [
            'codmeta' => $meta->codmeta,
        ]);
    }

    // --- UNIDADES ---

    private static function mergeUnidades(Meta $meta, array $unidades): void
    {
        $idsRecebidos = [];

        foreach ($unidades as $unidadeData) {
            $unidade = MetaUnidadeNegocio::firstOrNew([
                'codmeta' => $meta->codmeta,
                'codunidadenegocio' => $unidadeData['codunidadenegocio'],
            ]);

            $unidade->fill([
                'valormeta' => $unidadeData['valormeta'] ?? null,
                'valormetacaixa' => $unidadeData['valormetacaixa'] ?? null,
                'valormetavendedor' => $unidadeData['valormetavendedor'] ?? null,
                'valormetaxerox' => $unidadeData['valormetaxerox'] ?? null,
                'percentualcomissaovendedor' => $unidadeData['percentualcomissaovendedor'] ?? null,
                'percentualcomissaovendedormeta' => $unidadeData['percentualcomissaovendedormeta'] ?? null,
                'percentualcomissaosubgerente' => $unidadeData['percentualcomissaosubgerente'] ?? null,
                'percentualcomissaosubgerentemeta' => $unidadeData['percentualcomissaosubgerentemeta'] ?? null,
                'percentualcomissaoxerox' => $unidadeData['percentualcomissaoxerox'] ?? null,
                'premioprimeirovendedor' => $unidadeData['premioprimeirovendedor'] ?? null,
                'premiosubgerentemeta' => $unidadeData['premiosubgerentemeta'] ?? null,
                'premiometaxerox' => $unidadeData['premiometaxerox'] ?? null,
            ]);
            $unidade->save();

            $idsRecebidos[] = $unidade->codmetaunidadenegocio;

            if (array_key_exists('pessoas', $unidadeData)) {
                static::mergePessoas($meta, $unidade->codunidadenegocio, $unidadeData['pessoas'] ?? []);
            }
        }

        // Excluir unidades que não vieram no payload
        $unidadesExcluir = MetaUnidadeNegocio::where('codmeta', $meta->codmeta)
            ->whereNotIn('codmetaunidadenegocio', $idsRecebidos)
            ->get();

        foreach ($unidadesExcluir as $unidade) {
            MetaUnidadeNegocioPessoaFixo::where('codmeta', $meta->codmeta)
                ->where('codunidadenegocio', $unidade->codunidadenegocio)
                ->delete();
            MetaUnidadeNegocioPessoa::where('codmeta', $meta->codmeta)
                ->where('codunidadenegocio', $unidade->codunidadenegocio)
                ->delete();
            $unidade->delete();
        }
    }

    // --- PESSOAS ---

    private static function mergePessoas(Meta $meta, int $codunidadenegocio, array $pessoas): void
    {
        $idsRecebidos = [];

        foreach ($pessoas as $pessoaData) {
            $pessoa = MetaUnidadeNegocioPessoa::firstOrNew([
                'codmeta' => $meta->codmeta,
                'codunidadenegocio' => $codunidadenegocio,
                'codpessoa' => $pessoaData['codpessoa'],
                'datainicial' => $pessoaData['datainicial'],
            ]);

            $pessoa->fill([
                'datafinal' => $pessoaData['datafinal'],
                'percentualvenda' => $pessoaData['percentualvenda'] ?? null,
                'percentualcaixa' => $pessoaData['percentualcaixa'] ?? null,
                'percentualsubgerente' => $pessoaData['percentualsubgerente'] ?? null,
                'percentualxerox' => $pessoaData['percentualxerox'] ?? null,
            ]);
            $pessoa->save();

            $idsRecebidos[] = $pessoa->codmetaunidadenegociopessoa;

            if (array_key_exists('fixos', $pessoaData)) {
                static::mergeFixos($meta, $codunidadenegocio, $pessoaData['codpessoa'], $pessoaData['fixos'] ?? []);
            }
        }

        // Excluir pessoas que não vieram no payload
        $pessoasExcluir = MetaUnidadeNegocioPessoa::where('codmeta', $meta->codmeta)
            ->where('codunidadenegocio', $codunidadenegocio)
            ->whereNotIn('codmetaunidadenegociopessoa', $idsRecebidos)
            ->pluck('codpessoa');

        if ($pessoasExcluir->isNotEmpty()) {
            MetaUnidadeNegocioPessoaFixo::where('codmeta', $meta->codmeta)
                ->where('codunidadenegocio', $codunidadenegocio)
                ->whereIn('codpessoa', $pessoasExcluir)
                ->delete();

            MetaUnidadeNegocioPessoa::where('codmeta', $meta->codmeta)
                ->where('codunidadenegocio', $codunidadenegocio)
                ->whereNotIn('codmetaunidadenegociopessoa', $idsRecebidos)
                ->delete();
        }
    }

    // --- FIXOS ---

    private static function mergeFixos(Meta $meta, int $codunidadenegocio, int $codpessoa, array $fixos): void
    {
        $idsRecebidos = [];

        foreach ($fixos as $fixoData) {
            $fixo = MetaUnidadeNegocioPessoaFixo::firstOrNew([
                'codmeta' => $meta->codmeta,
                'codunidadenegocio' => $codunidadenegocio,
                'codpessoa' => $codpessoa,
                'tipo' => $fixoData['tipo'],
            ]);

            $fixo->fill([
                'valor' => $fixoData['valor'] ?? null,
                'quantidade' => $fixoData['quantidade'] ?? null,
                'descricao' => $fixoData['descricao'] ?? null,
                'datainicial' => $fixoData['datainicial'] ?? null,
                'datafinal' => $fixoData['datafinal'] ?? null,
            ]);
            $fixo->save();

            $idsRecebidos[] = $fixo->codmetaunidadenegociopessoafixo;
        }

        // Excluir fixos que não vieram no payload
        MetaUnidadeNegocioPessoaFixo::where('codmeta', $meta->codmeta)
            ->where('codunidadenegocio', $codunidadenegocio)
            ->where('codpessoa', $codpessoa)
            ->whereNotIn('codmetaunidadenegociopessoafixo', $idsRecebidos)
            ->delete();
    }
}
