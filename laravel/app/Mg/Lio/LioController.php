<?php

namespace Mg\Lio;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Mg\MgController;

use DB;

use App\Http\Requests;

class LioController extends MgController
{

    public function vendasAbertas(Request $request)
    {
        $request->validate([
          'cnpj' => ['required', 'numeric'],
          'terminal' => ['required', 'numeric'],
        ]);
        $cnpj = $request->cnpj;
        $terminal = $request->terminal;
        $sql = '
            with orig as (
            	select
            		n.codnegocio,
            		n.lancamento,
            		p.fantasia,
            		n.valortotal,
            		(
            			select  sum(nfp.valorpagamento)
            			from tblnegocioformapagamento nfp
            			where nfp.codnegocio = n.codnegocio
            			group by nfp.codnegocio

            		) as valorpago
            	from tblnegocio n
            	inner join tblnaturezaoperacao nat on (nat.codnaturezaoperacao = n.codnaturezaoperacao )
            	inner join tblpessoa p on (p.codpessoa = n.codpessoa )
            	inner join tblfilial f on (f.codfilial = n.codfilial)
            	inner join tblpessoa pf on (pf.codpessoa = f.codpessoa)
            	where n.codnegociostatus  = 1
            	and nat.venda = true
            	and n.valortotal > 0
            	and pf.cnpj = :cnpj
            	order by n.lancamento desc
            	limit 100
            )
            select *, valortotal - coalesce(valorpago, 0) as valorsaldo
            from orig
            order by lancamento desc
        ';

        $negocios = DB::select($sql, [
            'cnpj' => $cnpj
        ]);
        return $negocios;
    }

    public function order(Request $request)
    {
        $request->validate([
          'order' => ['required', 'json'],
        ]);
        $order = $request->order;
        $obj = json_decode($order);
        $arquivo = "{$obj->id}.json";
        Storage::disk('lio')->put($arquivo, $order);
    }


}
