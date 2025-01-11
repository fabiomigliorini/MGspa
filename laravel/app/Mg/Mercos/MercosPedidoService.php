<?php

namespace Mg\Mercos;

use \Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

use Mg\Negocio\Negocio;
use Mg\Negocio\NegocioFormaPagamento;
use Mg\Negocio\NegocioProdutoBarra;
use Mg\Pdv\Pdv;

class MercosPedidoService
{

    public static function importarPedidoApos($alterado_apos, Pdv $pdv = null)
    {

        // busca ultima alteracao importada do mercos
        if (!($alterado_apos instanceof Carbon)) {
            $alterado_apos = MercosPedido::max('ultimaalteracaomercos');
            if ($alterado_apos != null) {
                $alterado_apos = Carbon::parse($alterado_apos)->addSeconds(1);
            } else {
                $alterado_apos = Carbon::now()->subYear(1);
            }
        }

        $importados = 0;
        $erros = 0;
        $ate = $alterado_apos;

        $api = new MercosApi();
        $peds = $api->getPedidos($alterado_apos);
        // $mps = [];
        foreach ($peds as $ped) {
            $mp = static::parsePedido($ped, $pdv);
            if ($mp) {
                $importados++;
                if ($mp->ultimaalteracaomercos > $ate) {
                    $ate = $mp->ultimaalteracaomercos;
                }
            } else {
                $erros++;
            }
            // $mps[] = $mp;
        }
        $ret = [
            'importados' => $importados,
            'erros' => $erros,
            'ate' => $ate->format('Y-m-d H:i:s'),
            'listagem' => static::listagem()
            // 'mps' => $mps
        ];
        return $ret;
    }

    public static function parsePedido($ped, Pdv $pdv = null)
    {

        $mp = MercosPedido::firstOrNew([
            'pedidoid' => $ped->id
        ]);
        $mp->numero = $ped->numero;
        $mp->condicaopagamento = $ped->condicao_pagamento;
        $mp->ultimaalteracaomercos = Carbon::createFromFormat('Y-m-d H:i:s', $ped->ultima_alteracao, 'America/Sao_Paulo')->setTimezone('America/Cuiaba');
        $ee = $ped->endereco_entrega;
        $end = '';
        if (!empty($ee->endereco)) {
            $end = [
                $ee->endereco,
                $ee->numero,
                $ee->complemento,
                $ee->bairro,
                $ee->cidade,
                $ee->estado,
                $ee->cep
            ];
            $end = array_filter($end, function ($a) {
                return trim($a) !== "";
            });
            $end = implode(', ', $end);
            $mp->enderecoentrega = $end;
        }
        $mp->save();

        if (empty($mp->codnegocio)) {
            $n = new Negocio();
        } else {
            $n = $mp->Negocio;
        }

        if (in_array($n->codnegociostatus, [2, 3])) {
            return $mp;
        }

        $n->codestoquelocal = env('MERCOS_CODESTOQUELOCAL');
        $n->codfilial = $n->EstoqueLocal->codfilial;
        // $n->lancamento = $ped->data_criacao;
        $n->lancamento = $ped->data_emissao;
        if (empty($n->lancamento)) {
            $n->lancamento = $ped->ultima_alteracao;
        }
        $n->codnaturezaoperacao = env('MERCOS_CODNATUREZAOPERACAO');
        $n->codoperacao = $n->NaturezaOperacao->codoperacao;
        $n->codnegociostatus = 1;
        $n->codpessoavendedor = env('MERCOS_CODPESSOAVENDEDOR');
        $n->codusuario = Auth::user()->codusuario;
        $n->codusuariocriacao = $n->codusuario;
        $n->codusuarioalteracao = $n->codusuario;
        if ($pdv) {
            $n->codpdv = $pdv->codpdv;
        }

        $mc = MercosClienteService::buscaOuCriaPeloId($ped->cliente_id);
        $n->codpessoa = $mc->codpessoa;
        $n->valortotal = $ped->total;
        if (isset($ped->modalidade_entrega_nome)) {
            $n->observacoes = $ped->modalidade_entrega_nome;
        }
        // os valores abaixo serao importados pelos itens depois
        // $n->valorprodutos = 
        // $n->valordesconto =
        // $n->valorfrete =
        $n->save();

        $mp->codnegocio = $n->codnegocio;
        $mp->save();

        // importa itens
        $mpis = [];
        foreach ($ped->itens as $item) {
            if ($mpi = static::parsePedidoItem($item, $n, $mp)) {
                $mpis[] = $mpi;
            }
        }

        // exclui itens que não eram vinculados à nenhum item
        $cods = collect($mpis)->pluck('codnegocioprodutobarra')->toArray();
        NegocioProdutoBarra::where('codnegocio', $n->codnegocio)
            ->whereNull('inativo')
            ->whereNotIn('codnegocioprodutobarra', $cods)
            ->update(['inativo' => Carbon::now()]);

        // calcula valorprodutos e valordesconto pela somatoria dos produtos
        static::totalizaDescontoProduto($n);

        // rateia o frete pelos produtos
        if ($ped->valor_frete) {
            static::rateiaFrete($n, $ped->valor_frete);
        }

        // Marca Mercos Pay como forma de pagamento
        switch ($ped->condicao_pagamento_id) {
            case 1567554: // Boleto 
            case 1186408: // Cartão 1x 
            case 1193911: // Cartão 2x 
            case 1193912: // Cartão 3x 
            case 1193913: // Cartão 4x 
            case 1193914: // Cartão 5x 
            case 1193915: // Cartão 6x 
            case 1567555: // Cartão 7x 
            case 1567556: // Cartão 8x 
            case 1567557: // Cartão 9x 
            case 1567558: // Cartão 10x 
            case 1567559: // Cartão 11x 
            case 1567560: // Cartão 12x 
            case 1193916: // PIX
                $nfp = NegocioFormaPagamento::firstOrNew([
                    'codnegocio' => $n->codnegocio,
                    'codformapagamento' => env('MERCOS_CODFORMAPAGAMENTO_MERCOSPAY')
                ]);
                $nfp->valorpagamento = $n->valortotal;
                $nfp->valortotal = $n->valortotal;
                $nfp->tipo = 99; // tpag 99 = Outros
                $nfp->avista = true;
                $nfp->integracao = false;
                $nfp->save();
                break;

            case 1127747: // Prazo
            default:
                break;
        }
        return $mp;
    }

    public static function totalizaDescontoProduto(Negocio $n)
    {
        $n->valordesconto = $n->NegocioProdutoBarraS()->whereNull('inativo')->sum('valordesconto');
        $n->valorprodutos = $n->NegocioProdutoBarraS()->whereNull('inativo')->sum('valorprodutos');
        $n->save();
    }

    public static function rateiaFrete(Negocio $n, $frete)
    {
        if (!$n->valorprodutos) {
            return;
        }
        $perc = $frete / $n->valorprodutos;
        $npbs = $n->NegocioProdutoBarraS()->whereNull('inativo')->get();
        $iUltimo = count($npbs) - 1;
        $saldo = $frete;
        foreach ($npbs as $i => $npb) {
            $valorfrete = round($perc * $npb->valorprodutos, 2);
            if ($valorfrete > $saldo || $i == $iUltimo) {
                $valorfrete = $saldo;
            }
            $saldo -= $valorfrete;
            $npb->valorfrete = $valorfrete;
            $npb->valortotal += $npb->valorfrete;
            $npb->save();
        }
        $n->valorfrete = $frete;
        $n->save();
    }

    public static function parsePedidoItem($item, Negocio $n, MercosPedido $mp)
    {
        // se é item excluido do mercos, ignora
        if ($item->excluido) {
            return;
        }

        // instancia tabela que amarra item do mercos com item do mgsis
        $mpi = MercosPedidoItem::firstOrNew([
            'itemid' => $item->id,
            'codmercospedido' => $mp->codmercospedido,
        ]);

        // se item não tiver sido excluido, aproveita o mesmo
        if (!empty($mpi->codnegocioprodutobarra)) {
            if (empty($mpi->NegocioProdutoBarra->inativo)) {
                $npb = $mpi->NegocioProdutoBarra;
            }
        }

        // procura codigo do produto barra
        $pb = MercosProdutoService::procurarProdutoBarra($item->produto_id, $item->produto_codigo, $item->produto_agregador_id);
        if (!$pb) {
            return false;
        }

        // cria item do mgsis
        if (!isset($npb)) {
            $npb = new NegocioProdutoBarra([
                'codnegocio' => $n->codnegocio,
                'codprodutobarra' => $pb->codprodutobarra,
            ]);
        }

        // preenche com os dados do mercos
        $npb->fill([
            'codnegocio' => $n->codnegocio,
            'codprodutobarra' => $pb->codprodutobarra,
            'quantidade' => $item->quantidade,
            'valorunitario' => $item->preco_tabela,
            'valorprodutos' => $item->preco_tabela * $item->quantidade,
            'valortotal' => $item->subtotal,
        ]);

        // calcula o desconto se houver
        $npb->valordesconto = $npb->valorprodutos - $npb->valortotal;
        if ($npb->valordesconto && $npb->valorprodutos) {
            $npb->percentualdesconto = (($npb->valordesconto * 100) / $npb->valorprodutos);
        }

        // se for item nao localizado coloca descricao nas observacoes do item
        if ($npb->codprodutobarra == env('MERCOS_CODPRODUTOBARRA_NAO_CADASTRADO')) {
            $npb->observacoes = "{$item->produto_codigo} - {$item->produto_nome}";
        }

        // salva item
        $npb->save();

        // informa item na tabela de relacionamento
        $mpi->codnegocioprodutobarra = $npb->codnegocioprodutobarra;
        $mpi->save();
        return $mpi;
    }

    public static function exportarFaturamento(Negocio $n)
    {
        $ret = [];
        if ($n->codnegociostatus != 2) {
            return $ret;
        }
        $api = new MercosApi();
        foreach ($n->MercosPedidoS as $mp) {
            if (empty($mp->faturamentoid)) {
                $api->postFaturamento(
                    $mp->pedidoid,
                    $n->valortotal,
                    $n->lancamento,
                    null,
                    'Negocio ' . formataCodigo($n->codnegocio)
                );
                $mp->faturamentoid = $api->headers['meuspedidosid'];
                $mp->save();
                $ret[] = $mp->faturamentoid;
            } else {
                $api->putFaturamento(
                    $mp->faturamentoid,
                    $mp->pedidoid,
                    $n->valortotal,
                    $n->lancamento,
                    null,
                    'Negocio ' . formataCodigo($n->codnegocio)
                );
                $ret[] = $mp->faturamentoid;
            }
        }
        return $ret;
    }

    public static function listagem()
    {
        $sql = '
            select 
                mp.codmercospedido, 
                mp.pedidoid, 
                mp.numero, 
                mp.condicaopagamento, 
                mp.ultimaalteracaomercos, 
                mp.codnegocio, 
                n.uuid, 
                n.codpdv,
                n.valortotal, 
                n.codpessoa, 
                p.fantasia
            from tblmercospedido mp
            inner join tblnegocio n on (n.codnegocio = mp.codnegocio)
            inner join tblpessoa p on (p.codpessoa = n.codpessoa)
            where n.codnegociostatus = 1 
            order by mp.numero desc, mp.pedidoid desc
        ';
        return DB::select($sql);
    }

    public static function reimportar(MercosPedido $ped)
    {
        $api = new MercosApi();
        $desde = $ped->ultimaalteracaomercos;
        $ret = collect($api->getPedidos($desde->addMinute(-1)));
        if (!$ped = $ret->where('id', $ped->pedidoid)->first()) {
            throw new Exception("Impossível localizar pedido no retorno da API do Mercos!", 1);
        }
        $ped = static::parsePedido($ped);
        return $ped;
    }
}
