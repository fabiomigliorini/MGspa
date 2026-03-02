<?php

namespace Mg\Rh;

use Illuminate\Http\Resources\Json\JsonResource;

class IndicadorResource extends JsonResource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);
        $ret['usuariocriacao'] = @$this->UsuarioCriacao->usuario;
        $ret['usuarioalteracao'] = @$this->UsuarioAlteracao->usuario;

        if (isset($this->indicador_lancamento_s_count)) {
            $ret['lancamentos_count'] = $this->indicador_lancamento_s_count;
        }

        $ret['colaborador_nome'] = $this->Colaborador?->Pessoa?->fantasia;
        $ret['setor_nome'] = $this->Setor?->setor;
        $ret['unidade_negocio_nome'] = $this->UnidadeNegocio?->descricao;

        return $ret;
    }
}
