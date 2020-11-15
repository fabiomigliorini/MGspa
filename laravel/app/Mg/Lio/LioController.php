<?php

namespace Mg\Lio;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests;
use DB;

use Mg\MgController;

class LioController extends MgController
{

    public function vendasAbertas(Request $request)
    {
        $request->validate([
          'terminal' => ['required', 'string'],
        ]);
        $terminal = $request->terminal;
        $codusuario = Auth::user()->codusuario;

        $sql = '
            with orig as (
                select
                    n.codnegocio,
                    n.lancamento,
                    p.fantasia,
                    n.valortotal,
                    (
                        select sum(nfp.valorpagamento)
                        from tblnegocioformapagamento nfp
                        where nfp.codnegocio = n.codnegocio
                        group by nfp.codnegocio
                    ) as valorpago,
                    case when n.codusuario = u.codusuario then true else false end as usuario,
                    case when n.codfilial = u.codfilial then true else false end as filial,
                    n.codusuario
                from tblnegocio n
                inner join tblnaturezaoperacao nat on (nat.codnaturezaoperacao = n.codnaturezaoperacao )
                inner join tblpessoa p on (p.codpessoa = n.codpessoa )
                inner join tblfilial f on (f.codfilial = n.codfilial)
                inner join tblpessoa pf on (pf.codpessoa = f.codpessoa)
                left join tblusuario u on (u.codusuario = :codusuario)
                where n.codnegociostatus  = 1
                and nat.venda = true
                and n.valortotal > 0
            )
            select *, valortotal - coalesce(valorpago, 0) as valorsaldo
            from orig
            where valortotal > coalesce(valorpago, 0)
            order by usuario desc, filial desc, lancamento desc
            limit 100
        ';

        $negocios = DB::select($sql, [
           'codusuario' => $codusuario
        ]);
        return $negocios;
    }

    public function order(Request $request)
    {
        $request->validate([
          'order' => ['required', 'json'],
        ]);
        $id = LioJsonService::salvar($request->order, $request->pagamentos);
        DB::beginTransaction();
        LioService::parse($id);
        DB::commit();
        return $id;
    }

    public function parse($id, Request $request)
    {
        DB::beginTransaction();
        $ret = LioService::parse($id);
        DB::commit();
        return $ret;
    }

    /*
     * Callback que servidor da Cielo chama
     * Nao estamos usando esses dados por enquanto
     */
    public function callback(Request $request)
    {
        $file = 'callback/' . date('Y-m-d H-i-s') . ' ' . uniqid() . '.json';
        Storage::disk('lio')->put($file, $request->getContent());
        return response()->json($request->all(), 200);
    }

}
