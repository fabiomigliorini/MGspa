<?php

namespace Mg\Rh;

use Illuminate\Http\Resources\Json\JsonResource;

class PeriodoColaboradorResource extends JsonResource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);
        $ret['usuariocriacao'] = @$this->UsuarioCriacao->usuario;
        $ret['usuarioalteracao'] = @$this->UsuarioAlteracao->usuario;

        $pessoais = $this->indicadores_pessoais ?? collect();
        $todosSetores = $this->PeriodoColaboradorSetorS->pluck('codsetor');

        // Indicadores por PCS (para listagem Colaboradores.vue)
        if (isset($ret['periodo_colaborador_setor_s'])) {
            foreach ($ret['periodo_colaborador_setor_s'] as &$pcsArr) {
                $pcs = $this->PeriodoColaboradorSetorS
                    ->firstWhere('codperiodocolaboradorsetor', $pcsArr['codperiodocolaboradorsetor']);
                $pcsArr['indicadores'] = $pcs
                    ? $this->indicadoresParaSetor($pcs, $pessoais, $todosSetores)
                    : [];
            }
        }

        // Indicadores flat (para ColaboradorDetalhe.vue — card INDICADORES)
        $ret['indicadores'] = $this->todosIndicadores($pessoais);

        // Remover atributos temporários do output
        unset($ret['indicadores_pessoais']);
        unset($ret['indicadores_coletivos']);

        return $ret;
    }

    private function indicadoresParaSetor($pcs, $pessoais, $todosSetores)
    {
        $result = [];
        $codsetor = $pcs->codsetor;
        $codunidade = $pcs->Setor->codunidadenegocio ?? null;
        $outrosSetores = $todosSetores->reject(fn($s) => $s === $codsetor);

        // 1. Pessoais (V/C) por codsetor direto
        foreach ($pessoais as $ind) {
            if ($ind->codsetor === $codsetor) {
                $result[$ind->codindicador] = $ind;
            }
        }

        // 2. Pessoais (V/C) por unidade, excluindo os que batem com outro PCS
        if ($codunidade) {
            foreach ($pessoais as $ind) {
                if (!isset($result[$ind->codindicador])
                    && $ind->codunidadenegocio === $codunidade
                    && !$outrosSetores->contains($ind->codsetor)) {
                    $result[$ind->codindicador] = $ind;
                }
            }
        }

        // 3. Indicadores referenciados por rubricas DESTE PCS
        $rubricasPcs = $this->ColaboradorRubricaS
            ->where('codperiodocolaboradorsetor', $pcs->codperiodocolaboradorsetor);

        foreach ($rubricasPcs as $rubrica) {
            foreach (['Indicador', 'IndicadorCondicao'] as $rel) {
                if ($rubrica->$rel && !isset($result[$rubrica->$rel->codindicador])) {
                    $result[$rubrica->$rel->codindicador] = $rubrica->$rel;
                }
            }
        }

        // 4. Rubricas sem PCS vinculado — incluir se indicador bate por unidade
        if ($codunidade) {
            $rubricasSemPcs = $this->ColaboradorRubricaS
                ->whereNull('codperiodocolaboradorsetor');

            foreach ($rubricasSemPcs as $rubrica) {
                foreach (['Indicador', 'IndicadorCondicao'] as $rel) {
                    $ind = $rubrica->$rel;
                    if ($ind && !isset($result[$ind->codindicador])
                        && ($ind->codunidadenegocio === $codunidade || $ind->codsetor === $codsetor)) {
                        $result[$ind->codindicador] = $ind;
                    }
                }
            }
        }

        return collect($result)->values()->map(fn($ind) => [
            'codindicador' => $ind->codindicador,
            'tipo' => $ind->tipo,
            'valoracumulado' => $ind->valoracumulado,
            'meta' => $ind->meta,
            'codsetor' => $ind->codsetor,
            'codunidadenegocio' => $ind->codunidadenegocio,
            'setor' => $ind->Setor ? ['setor' => $ind->Setor->setor] : null,
            'unidade_negocio' => $ind->UnidadeNegocio ? ['descricao' => $ind->UnidadeNegocio->descricao] : null,
        ])->toArray();
    }

    private function todosIndicadores($pessoais)
    {
        $map = [];

        // Pessoais (V/C)
        foreach ($pessoais as $ind) {
            $map[$ind->codindicador] = $ind;
        }

        // Coletivos (S/U) para os setores/unidades deste colaborador
        $coletivos = $this->indicadores_coletivos ?? collect();
        $meusSetores = $this->PeriodoColaboradorSetorS->pluck('codsetor')->toArray();
        $minhasUnidades = $this->PeriodoColaboradorSetorS
            ->map(fn($pcs) => $pcs->Setor->codunidadenegocio ?? null)
            ->filter()
            ->unique()
            ->toArray();

        foreach ($coletivos as $ind) {
            if (!isset($map[$ind->codindicador])) {
                if (in_array($ind->codsetor, $meusSetores) || in_array($ind->codunidadenegocio, $minhasUnidades)) {
                    $map[$ind->codindicador] = $ind;
                }
            }
        }

        // Referenciados por rubricas (fallback — caso algum indicador não bata com setor/unidade)
        foreach ($this->ColaboradorRubricaS as $rubrica) {
            foreach (['Indicador', 'IndicadorCondicao'] as $rel) {
                if ($rubrica->$rel && !isset($map[$rubrica->$rel->codindicador])) {
                    $map[$rubrica->$rel->codindicador] = $rubrica->$rel;
                }
            }
        }

        return collect($map)->values()->toArray();
    }
}
