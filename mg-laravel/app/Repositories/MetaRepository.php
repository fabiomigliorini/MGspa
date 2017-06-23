<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

use App\Models\Meta;
use Illuminate\Support\Facades\DB;

/**
 * Description of MetaRepository
 * 
 * @property Validator $validator
 * @property Meta $model
 */
class MetaRepository extends MGRepository {
    
    public function boot() {
        $this->model = new Meta();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $data = $this->model->getAttributes();
        }
        
        if (empty($id)) {
            $id = $this->model->codmeta;
        }

        //dd($data);
        $this->validator = Validator::make($data, [
            'premioprimeirovendedorfilial' => 'required',            
        ], [
            'premioprimeirovendedorfilial.required' => 'O campo Prêmio Melhor Vendedor não pode ser vazio',
        ]);

        return $this->validator->passes();
        
    }
    
    public function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        if ($this->model->ProdutoS->count() > 0) {
            return 'Meta sendo utilizada em Produtos!';
        }
        return false;
    }
    
    public function listing($filters = [], $sort = [], $start = null, $length = null) {
        
        // Query da Entidade
        $qry = Meta::query();
        
        // Filtros
        if (!empty($filters['codmeta'])) {
            $qry->where('codmeta', '=', $filters['codmeta']);
        }
        
        if (!empty($filters['meta'])) {
            $qry->palavras('meta', $filters['meta']);
        }         
        
        switch ($filters['inativo']) {
            case 2: //Inativos
                $qry = $qry->inativo();
                break;

            case 9: //Todos
                break;

            case 1: //Ativos
            default:
                $qry = $qry->ativo();
                break;
        }
        
        $count = $qry->count();

        // Paginacao
        if (!empty($start)) {
            $qry->offset($start);
        }
        if (!empty($length)) {
            $qry->limit($length);
        }
        
        // Ordenacao
        foreach ($sort as $s) {
            $qry->orderBy($s['column'], $s['dir']);
        }
        
        // Registros
        return [
            'recordsFiltered' => $count
            , 'recordsTotal' => Meta::count()
            , 'data' => $qry->get()
        ];        
    }

    public function totalVendas()
    {
        $sql_filiais = "
            select
                  f.codfilial
                , f.filial
                , mf.valormetafilial
                , mf.valormetavendedor
                , (SELECT to_json(array_agg(t)) FROM (
                    select 
                        date_trunc('day', n.lancamento) as data,
                        sum((case when n.codoperacao = 1 then -1 else 1 end) * coalesce(n.valortotal, 0)) as valorvendas
                    from tblnegocio n
                    where n.codnegociostatus = 2 -- fechado
                    and n.codpessoa not in (select distinct f2.codpessoa from tblfilial f2)
                    and n.codnaturezaoperacao in (1, 2) -- Venda / Devolucao de Vendas -- TODO: Fazer modelagem para tirar o codigo fixo
                    and n.lancamento between m.periodoinicial and m.periodofinal
                    and n.codfilial = mf.codfilial
                    group by date_trunc('day', n.lancamento)
                    order by date_trunc('day', n.lancamento)
                    ) t) as valorvendaspordata
                , mfp.codpessoa
                , mfp.codcargo
                , p.pessoa
            from tblmeta m
            inner join tblmetafilial mf on (mf.codmeta = m.codmeta)
            inner join tblfilial f on (f.codfilial = mf.codfilial)
            left join tblmetafilialpessoa mfp on (mfp.codmetafilial = mf.codmetafilial and mfp.codcargo = 2) -- Subgerente -- TODO: Fazer modelagem
            left join tblpessoa p on (p.codpessoa = mfp.codpessoa)
            where m.codmeta = {$this->model->codmeta}
            --order by valorvendas desc
        ";
        
        $sql_vendedores = "
        select
              mf.codfilial
            , f.filial
            , mf.valormetavendedor
            , mfp.codpessoa
            , p.fantasia
            , (SELECT to_json(array_agg(t)) FROM (
            select
                date_trunc('day', n.lancamento) as data,
                sum(coalesce(npb.valortotal, 0) * (case when n.codoperacao = 1 then -1 else 1 end) * (coalesce(n.valortotal, 0) / coalesce(n.valorprodutos, 0))) as valorvendas
            from tblnegocio n
            inner join tblnegocioprodutobarra npb on (npb.codnegocio = n.codnegocio)
            inner join tblprodutobarra pb on (pb.codprodutobarra = npb.codprodutobarra)
            inner join tblproduto p on (p.codproduto = pb.codproduto)
            where n.codnegociostatus = 2 -- fechado
            and n.codpessoa not in (select distinct f2.codpessoa from tblfilial f2)
            and n.codnaturezaoperacao in (1, 2) -- Venda / Devolucao de Vendas -- TODO: Fazer modelagem para tirar o codigo fixo
            and p.codsubgrupoproduto != 2951 -- Somente Xerox
            and n.lancamento between m.periodoinicial and m.periodofinal
            and n.codpessoavendedor = mfp.codpessoa
            group by date_trunc('day', n.lancamento)
            order by date_trunc('day', n.lancamento)
            ) t) as valorvendaspordata
            , m.percentualcomissaovendedor
        from tblmeta m
        inner join tblmetafilial mf on (mf.codmeta = m.codmeta)
        inner join tblfilial f on (mf.codfilial = f.codfilial)
        inner join tblmetafilialpessoa mfp on (mfp.codmetafilial = mf.codmetafilial and mfp.codcargo = 1) -- Vendedor -- TODO: Fazer modelagem
        inner join tblpessoa p on (p.codpessoa = mfp.codpessoa)        
        where m.codmeta = {$this->model->codmeta}
        --order by valorvendas desc
        ";
        
        $sql_xerox = "
            select
              f.codfilial
            , f.filial
            , (SELECT to_json(array_agg(t)) FROM (
                select 
                    date_trunc('day', n.lancamento) as data,
                    --sum((case when n.codoperacao = 1 then -1 else 1 end) * coalesce(n.valortotal, 0)) as valorvendas
                    sum(coalesce(npb.valortotal, 0) * (case when n.codoperacao = 1 then -1 else 1 end) * (coalesce(n.valortotal, 0) / coalesce(n.valorprodutos, 0))) as valorvendas
                from tblnegocio n
                inner join tblnegocioprodutobarra npb on (npb.codnegocio = n.codnegocio)
                inner join tblprodutobarra pb on (pb.codprodutobarra = npb.codprodutobarra)
                inner join tblproduto p on (p.codproduto = pb.codproduto)
                where n.codnegociostatus = 2 -- fechado
                and n.codpessoa not in (select distinct f2.codpessoa from tblfilial f2)
                and n.codnaturezaoperacao in (1, 2) -- Venda / Devolucao de Vendas -- TODO: Fazer modelagem para tirar o codigo fixo
                and p.codsubgrupoproduto = 2951 -- Xerox -- TODO: Fazer modelagem para tirar o codigo fixo
                and n.lancamento between m.periodoinicial and m.periodofinal
                and n.codfilial = mf.codfilial
                group by date_trunc('day', n.lancamento)
                order by date_trunc('day', n.lancamento)
                ) t) as valorvendaspordata
            , m.percentualcomissaoxerox
            , mfp.codpessoa
            , p.pessoa
            from tblmeta m
            inner join tblmetafilial mf on (mf.codmeta = m.codmeta)
            inner join tblfilial f on (f.codfilial = mf.codfilial)
            left join tblmetafilialpessoa mfp on (mfp.codmetafilial = mf.codmetafilial and mfp.codcargo = 7) -- Subgerente -- TODO: Fazer modelagem
            left join tblpessoa p on (p.codpessoa = mfp.codpessoa)
            where m.codmeta = {$this->model->codmeta}
            --order by valorvendas desc
        ";
        
        $filiais    = DB::select($sql_filiais);
        $vendedores = DB::select($sql_vendedores);
        $xeroxs     = DB::select($sql_xerox);
        
        $array_melhoresvendedores = [];
        foreach ($filiais as $filial){
            $array_melhoresvendedores[$filial->codfilial]=[];
            foreach ($vendedores as $vendedor){
                $vendedor->valorvendas = array_sum(array_column(json_decode($vendedor->valorvendaspordata), 'valorvendas'));
                if($vendedor->codfilial == $filial->codfilial)
                {
                    array_push($array_melhoresvendedores[$filial->codfilial], $vendedor->valorvendas);
                }
            }
        }

        $retorno_vendedores = [];
        foreach ($vendedores as $vendedor){
            $vendedor->valorvendas = array_sum(array_column(json_decode($vendedor->valorvendaspordata), 'valorvendas'));
            $valorcomissaovendedor = ($vendedor->percentualcomissaovendedor / 100 ) * $vendedor->valorvendas;
            $valorcomissaometavendedor = ($vendedor->valorvendas >= $vendedor->valormetavendedor ? ($this->model->percentualcomissaovendedormeta / 100 ) * $vendedor->valorvendas : null);
            $falta = ($vendedor->valorvendas < $vendedor->valormetavendedor ? $vendedor->valormetavendedor - $vendedor->valorvendas : null);
            $melhorvendedor = null;
            
            if($vendedor->valorvendas == max($array_melhoresvendedores[$vendedor->codfilial]) && $vendedor->valorvendas >= $vendedor->valormetavendedor){
                $melhorvendedor = 200;
            }
            
            $retorno_vendedores[] = [
                'codfilial'                 => $vendedor->codfilial,
                'filial'                    => $vendedor->filial,
                'valormetavendedor'         => $vendedor->valormetavendedor,
                'codpessoa'                 => $vendedor->codpessoa,
                'pessoa'                    => $vendedor->fantasia, 
                'valorvendas'               => $vendedor->valorvendas,
                'percentualcomissaovendedor' =>  $vendedor->percentualcomissaovendedor,
                'valorcomissaovendedor'     => $valorcomissaovendedor,
                'valorcomissaometavendedor' => $valorcomissaometavendedor,
                'valortotalcomissao'        => $valorcomissaovendedor + $valorcomissaometavendedor + $melhorvendedor,
                'metaatingida'              => ($vendedor->valorvendas >= $vendedor->valormetavendedor) ? true : false,
                'primeirovendedor'          => $melhorvendedor,
                'falta'                     => $falta,
                'valorvendaspordata'        => json_decode($vendedor->valorvendaspordata, true),
            ];            
        }
        
        $retorno_filiais = [];
        foreach ($filiais as $filial){
            $filial->valorvendas = array_sum(array_column(json_decode($filial->valorvendaspordata), 'valorvendas'));
            $falta = ($filial->valorvendas < $filial->valormetafilial ? $filial->valormetafilial - $filial->valorvendas : null);
            $premio = ($filial->valorvendas >= $filial->valormetafilial ? ($filial->valorvendas / 100 ) * $this->model->percentualcomissaosubgerentemeta : null);
            $retorno_filiais[] = [
                'codfilial'                 => $filial->codfilial,
                'filial'                    => $filial->filial,
                'valormetafilial'           => $filial->valormetafilial,
                'valormetavendedor'         => $filial->valormetavendedor,
                'valorvendas'               => $filial->valorvendas,
                'codpessoa'                 => $filial->codpessoa,
                'pessoa'                    => $filial->pessoa,
                'falta'                     => $falta,
                'comissao'                  => $premio,
                'valorvendaspordata'        => json_decode($filial->valorvendaspordata, true),
            ];
        }        
        
        $retorno_xerox = [];
        foreach ($xeroxs as $xerox){
            $xerox->valorvendas = array_sum(array_column(json_decode($xerox->valorvendaspordata), 'valorvendas'));
            $retorno_xerox[] = [
                "codfilial"             => $xerox->codfilial,
                "filial"                => $xerox->filial,
                "valorvendas"           => $xerox->valorvendas,
                "percentualcomissaoxerox"=> $xerox->percentualcomissaoxerox,
                "codpessoa"             => $xerox->codpessoa,
                "pessoa"                => $xerox->pessoa,
                'comissao'              => ($xerox->valorvendas / 100 ) * $xerox->percentualcomissaoxerox,
                'valorvendaspordata'        => json_decode($xerox->valorvendaspordata, true),
            ];
        }        
        
        $retorno = [
            'filiais'       => $retorno_filiais,
            'vendedores'    => $retorno_vendedores,
            'xerox'         => $retorno_xerox
        ];
        
        return $retorno;
    }

    public function buscaProximos($qtd = 7)
    {
        $metas = $this->model->where('periodoinicial', '>', $this->model->periodofinal)
               ->orderBy('periodoinicial', 'asc')
               ->take($qtd)
               ->get();
        return $metas;
    }
    
    public function buscaAnteriores($qtd = 7)
    {
        $metas = $this->model->where('periodofinal', '<', $this->model->periodoinicial)
               ->orderBy('periodoinicial', 'desc')
               ->take($qtd)
               ->get();
        return $metas->reverse();
    }

    
}
