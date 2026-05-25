<?php

namespace Mg\Titulo;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class TipoMovimentoTituloResource extends Resource
{
    public function toArray($request)
    {
        return [
            'codtipomovimentotitulo' => $this->codtipomovimentotitulo,
            'tipomovimentotitulo' => $this->tipomovimentotitulo,
            'implantacao' => (bool) $this->implantacao,
            'ajuste' => (bool) $this->ajuste,
            'armotizacao' => (bool) $this->armotizacao,
            'juros' => (bool) $this->juros,
            'desconto' => (bool) $this->desconto,
            'pagamento' => (bool) $this->pagamento,
            'estorno' => (bool) $this->estorno,
            'observacao' => $this->observacao,
            'inativo' => $this->inativo,
            'criacao' => $this->criacao,
            'alteracao' => $this->alteracao,
            'codusuariocriacao' => $this->codusuariocriacao,
            'codusuarioalteracao' => $this->codusuarioalteracao,
        ];
    }
}
