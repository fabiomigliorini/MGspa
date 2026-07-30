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
