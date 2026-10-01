<?php

namespace Mg\Pdv;

use Illuminate\Support\Facades\DB;
use Mg\Pagamento\PagamentoDetalheResource;

/**
 * Vale colaborador e adiantamentos lancados no PDV (M8 doc-3). Rota
 * v1/pdv/titulo; o dispositivo autoriza. Estorno e recibo sao os da
 * listagem de pagamentos (v1/pdv/pagamento).
 */
class PdvTituloController
{
    public function store(PdvTituloStoreRequest $request)
    {
        $pdv = PdvService::autoriza($request->pdv);
        DB::beginTransaction();
        $pags = PdvTituloService::lancar($pdv, $request->validated());
        DB::commit();
        return PagamentoDetalheResource::collection($pags);
    }
}
