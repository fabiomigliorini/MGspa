<?php

namespace Mg\Ibpt\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Situação da tabela do IBPT de uma UF, para a tela de importação.
 */
class IbptStatusResource extends JsonResource
{
    public function toArray($request): array
    {
        $vigenciafim = $this->vigenciafim ? Carbon::parse($this->vigenciafim) : null;

        return [
            'codestado' => (int) $this->codestado,
            'sigla' => $this->sigla,
            'estado' => $this->estado,
            'ncms' => (int) $this->ncms,
            'versao' => $this->versao,
            'vigenciainicio' => $this->vigenciainicio,
            'vigenciafim' => $this->vigenciafim,
            'atualizacao' => $this->atualizacao,
            // Negativo = já vencida. É o que a tela usa para destacar o que precisa
            // ser atualizado antes de travar a informação da Lei 12.741 nas notas.
            'diasparavencer' => $vigenciafim
                ? Carbon::today()->diffInDays($vigenciafim, false)
                : null,
        ];
    }
}
