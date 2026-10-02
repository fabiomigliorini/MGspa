<?php

namespace Mg\Pdv;

use Illuminate\Support\Facades\DB;
use Mg\Pagamento\PagamentoDetalheResource;
use Mg\Titulo\TituloAdiantamentoService;
use Mg\Titulo\TituloAdiantamentoStoreRequest;

/**
 * Vale colaborador e adiantamentos lancados no PDV (M8 doc-3). Rota
 * v1/pdv/titulo; o dispositivo autoriza e o dinheiro vai na gaveta dele.
 * Estorno e recibo sao os da listagem de pagamentos (v1/pdv/pagamento).
 */
class PdvTituloController
{
    public function store(TituloAdiantamentoStoreRequest $request)
    {
        $pdv = PdvService::autoriza($request->pdv);
        DB::beginTransaction();
        $pags = TituloAdiantamentoService::lancar($request->validated(), $pdv);
        DB::commit();
        return PagamentoDetalheResource::collection($pags);
    }
}
