<?php

namespace Mg\Estoque\MinimoMaximo;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

use DB;

use Mg\Marca\Marca;

class FaltandoMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $marca;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Marca $marca = null)
    {
        $this->marca = $marca;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
      $sql = "
        with estoque as (
            select
                elpv.codprodutovariacao
                , sum(es.saldoquantidade) as estoque
            from tblestoquelocalprodutovariacao elpv
            inner join tblestoquelocal el on (el.codestoquelocal = elpv.codestoquelocal)
            left join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
            where el.inativo is null
            group by elpv.codprodutovariacao
        ), chegando as (
            select
                pb_nti.codprodutovariacao
                , sum(cast(coalesce(nti.qcom * coalesce(pe_nti.quantidade, 1), 0) as bigint)) as chegando
            from tblnfeterceiro nt
            inner join tblnfeterceiroitem nti on (nt.codnfeterceiro = nti.codnfeterceiro)
            inner join tblprodutobarra pb_nti on (pb_nti.codprodutobarra = nti.codprodutobarra)
            left join tblprodutoembalagem pe_nti on (pe_nti.codprodutoembalagem = pb_nti.codprodutoembalagem)
            where nt.codnotafiscal IS NULL
            AND (nt.indmanifestacao IS NULL OR nt.indmanifestacao NOT IN (210220, 210240))
            AND nt.indsituacao = 1
            AND nt.ignorada = FALSE
            group by pb_nti.codprodutovariacao
        )
        select
          m.codmarca
          , m.marca
          , p.codproduto
          , pv.codprodutovariacao
          , p.produto || coalesce(' | ' || pv.variacao, '') as produto
          , coalesce(pv.referencia, p.referencia) as referencia
          , pv.dataultimacompra
          , pv.custoultimacompra
          , pv.quantidadeultimacompra
          , pv.estoqueminimo
          , pv.estoquemaximo
          , e.estoque
          , c.chegando
          , pv.lotecompra
          , pv.descontinuado
        from tblproduto p
        inner join tblmarca m on (m.codmarca = p.codmarca)
        inner join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
        left join estoque e on (e.codprodutovariacao = pv.codprodutovariacao)
        left join chegando c on (c.codprodutovariacao = pv.codprodutovariacao)
        where m.controlada = true
        and p.inativo is null
        and pv.inativo is null
        and pv.descontinuado is null
        and pv.estoqueminimo > 0
        and pv.estoqueminimo > (coalesce(c.chegando, 0) + coalesce(e.estoque, 0))
        order by m.marca, m.codmarca, p.produto, p.codproduto, pv.variacao, pv.codprodutovariacao
      ";
      $params = [];
      if (!empty($this->marca)) {
        $params['codmarca'] = $this->marca->codmarca;
      }
      $produtos = collect(DB::select($sql, $params));
      $marcas = $produtos->groupBy('marca');

      return $this
        ->subject("Produtos Abaixo do Minimo")
        ->view('faltando-mail.faltando')
        ->with(['marcas' => $marcas]);

      /*
        $pathNFeAutorizada = NFePHPRepositoryPath::pathNFeAutorizada($this->marca);
        if (!file_exists($pathNFeAutorizada)) {
            throw new \Exception("Arquivo XML não localizado ($pathNFeAutorizada)!");
        }

        $pathDanfe = NFePHPRepositoryPath::pathDanfe($this->marca);
        if (!file_exists($pathDanfe)) {
            NFePHPRepository::danfe($this->marca);
            if (!file_exists($pathDanfe)) {
                throw new \Exception("Erro ao gerar arquivo PDF ($pathDanfe)!");
            }
        }

          */
    }
}
