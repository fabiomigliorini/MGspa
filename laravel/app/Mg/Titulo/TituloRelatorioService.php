<?php

namespace Mg\Titulo;

use Illuminate\Support\Facades\DB;
use Dompdf\Dompdf;
use Mg\Negocio\Negocio;
use Mg\Pessoa\Pessoa;
use Mg\Pdv\PdvAnexoService;

class TituloRelatorioService
{
    public static function listagem(Array $filtro)
    {
        $sql = '
            select p.codgrupoeconomico, p.codpessoa, p.fantasia, p.fisica, p.cnpj, p.pessoa, sum(saldo) as saldo
            from tbltitulo t
            INNER JOIN tblpessoa p ON (p.codpessoa = t.codpessoa)
            where t.saldo !=0
        ';
        if (isset($filtro['codpessoa'])) {
            $sql .= '
                and t.codpessoa = :codpessoa
            ';
            $bindings = ['codpessoa' => $filtro['codpessoa']];
        } elseif (isset($filtro['codgrupoeconomico'])) {
            $sql .= '
                and p.codgrupoeconomico = :codgrupoeconomico
            ';
            $bindings = ['codgrupoeconomico' => $filtro['codgrupoeconomico']];
        }
        $sql .= '
            group by p.codgrupoeconomico, p.codpessoa, p.fantasia, p.fisica, p.cnpj, p.pessoa
            order by p.fantasia            
        ';

        $regs = DB::select($sql, $bindings);
        $pessoas = Pessoa::hydrate($regs);

        foreach ($pessoas as $p) {
            $regs = DB::select('
                select distinct n.*
                from tbltitulo t
                inner join tblnegocioformapagamento nfp on (nfp.codnegocioformapagamento = t.codnegocioformapagamento)
                inner join tblnegocio n on (n.codnegocio = nfp.codnegocio)
                where t.codpessoa = :codpessoa
                and t.saldo !=0
                order by n.lancamento             
            ',[
                'codpessoa' => $p->codpessoa
            ]);
            $p->negocios = Negocio::hydrate($regs);
            foreach ($p->negocios as $n) {
                $n->anexos = PdvAnexoService::base64($n->codnegocio);
            }

            $regs = DB::select('
                select distinct ta.*
                from tbltitulo t
                inner join tbltituloagrupamento ta on (ta.codtituloagrupamento = t.codtituloagrupamento)
                where t.codpessoa = :codpessoa
                and t.saldo !=0
                order by ta.emissao 
            ',[
                'codpessoa' => $p->codpessoa
            ]);
            $p->agrupamentos = TituloAgrupamento::hydrate($regs);

            $regs = DB::select('
                select distinct t.*
                from tbltitulo t
                where t.codpessoa = :codpessoa
                and t.saldo != 0
                and t.codnegocioformapagamento is null
                and t.codtituloagrupamento is null
                order by t.vencimento  
            ',[
                'codpessoa' => $p->codpessoa
            ]);
            $p->titulos = Titulo::hydrate($regs);            
        }
        
        return $pessoas;
    }

    public static function pdf(Array $filtro)
    {

        // pega as pessoas
        $pessoas = TituloRelatorioService::listagem($filtro);

        // carrega HTML da view
        $dompdf = new Dompdf();
        $html = view('titulo-relatorio.relatorio', compact('pessoas'))->render();
        $dompdf->loadHtml($html);

        // Bobina 80mm x 297 (altura A4)
        $dompdf->setPaper([0.0, 0.0, 595.27, 841.89], 'portrait');

        // Renderiza
        $dompdf->render();

        // retorna o PDF em uma variavel
        return $dompdf->output();
    }
}
