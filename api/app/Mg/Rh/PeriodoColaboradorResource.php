<?php

namespace Mg\Rh;

use Illuminate\Http\Resources\Json\JsonResource;

class PeriodoColaboradorResource extends JsonResource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);

        // Setor do colaborador (vínculo agora é 1:1 em tblperiodocolaborador.codsetor)
        $setor = $this->Setor;
        $ret['setor'] = $setor ? [
            'codsetor' => $setor->codsetor,
            'setor' => $setor->setor,
            'codunidadenegocio' => $setor->codunidadenegocio,
            'unidade_negocio' => $setor->UnidadeNegocio ? [
                'codunidadenegocio' => $setor->UnidadeNegocio->codunidadenegocio,
                'descricao' => $setor->UnidadeNegocio->descricao,
            ] : null,
        ] : null;

        // Indicadores do colaborador (pessoais + coletivos do seu setor/unidade)
        $pessoais = $this->indicadores_pessoais ?? collect();
        $ret['indicadores'] = $this->todosIndicadores($pessoais);

        // Acertos ativos (eventos) — para o card de acertos na tela de detalhe
        unset($ret['periodo_colaborador_acerto_s']);
        $ret['acertos'] = ($this->PeriodoColaboradorAcertoS ?? collect())->map(function ($ac) {
            return [
                'codperiodocolaboradoracerto' => (int) $ac->codperiodocolaboradoracerto,
                'data'            => $ac->data ? $ac->data->format('Y-m-d') : null,
                'forma'           => $ac->forma,
                'forma_descricao' => $ac->forma_descricao,
                'rubricas'        => (float) $ac->rubricas,
                'creditos'        => (float) $ac->creditos,
                'debitos'         => (float) $ac->debitos,
                'saldo'           => (float) $ac->saldo,
                'observacao'      => $ac->observacao,
                'inativo'         => $ac->inativo,
                'criacao'         => $ac->criacao,
                'usuariocriacao'  => $ac->usuariocriacao,
                // Só os títulos da baixa original (tipo 601), evita duplicar com ajustes.
                'titulos'         => $ac->MovimentoTituloS
                    ->where('codtipomovimentotitulo', 601)
                    ->map(fn ($m) => [
                        'codtitulo' => (int) $m->codtitulo,
                        'numero'    => optional($m->Titulo)->numero,
                        'valor'     => (float) (($m->credito ?? 0) + ($m->debito ?? 0)),
                    ])->values(),
            ];
        })->values();

        // Remover atributos temporários do output
        unset($ret['indicadores_pessoais']);
        unset($ret['indicadores_coletivos']);

        return $ret;
    }

    private function todosIndicadores($pessoais)
    {
        $map = [];

        // Pessoais (V/C)
        foreach ($pessoais as $ind) {
            $map[$ind->codindicador] = $ind;
        }

        // Coletivos (S/U) do setor/unidade deste colaborador
        $coletivos = $this->indicadores_coletivos ?? collect();
        $meuSetor = $this->codsetor;
        $minhaUnidade = $this->Setor->codunidadenegocio ?? null;

        foreach ($coletivos as $ind) {
            if (!isset($map[$ind->codindicador])) {
                if (($meuSetor && $ind->codsetor === $meuSetor)
                    || ($minhaUnidade && $ind->codunidadenegocio === $minhaUnidade)) {
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
