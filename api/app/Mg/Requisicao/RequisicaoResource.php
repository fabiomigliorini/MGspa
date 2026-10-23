<?php

namespace Mg\Requisicao;

use Illuminate\Http\Resources\Json\JsonResource;
use Mg\Produto\ProdutoBarra;
use Mg\Produto\ProdutoBarraPesquisaResource;

// A requisicao para as telas. O codigo de retirada so' vai para o dispositivo da
// loja que pediu; os "pode" dizem o que a tela oferece (o backend confere de novo).
class RequisicaoResource extends JsonResource
{
    const RELACOES = [
        'EstoqueLocalRequisitante.Filial.Pessoa',
        'EstoqueLocalRequisitada.Filial.Pessoa',
        'Pessoa',
        'Pdv',
        'UsuarioCriacao',
        'UsuarioFinalizacao',
        'UsuarioCancelamento',
        'RequisicaoItemS.ProdutoBarra.Produto.UnidadeMedida',
        'RequisicaoItemS.ProdutoBarra.ProdutoEmbalagem.UnidadeMedida',
        'RequisicaoItemS.ProdutoBarra.ProdutoVariacao.ProdutoImagem',
        'RequisicaoItemS.ProdutoBarraSubstituto.Produto.UnidadeMedida',
        'RequisicaoItemS.ProdutoBarraSubstituto.ProdutoEmbalagem.UnidadeMedida',
        'RequisicaoItemS.ProdutoBarraSubstituto.ProdutoVariacao',
    ];

    public function toArray($request)
    {
        $pdv = $request->attributes->get('pdv');
        $req = $this->resource;
        $ehRequisitante = RequisicaoService::ehRequisitante($req, $pdv);
        $requisitada = $req->EstoqueLocalRequisitada;
        // atendido por item na menor embalagem, as partes (negocios) e o fora do pedido
        $calc = RequisicaoProcessamentoService::calcular([$req->codrequisicao]);
        $substitutos = array_filter($req->RequisicaoItemS->map(
            fn ($i) => $i->ProdutoBarraSubstituto?->codprodutovariacao
        )->all());

        return [
            'codrequisicao' => $req->codrequisicao,
            'status' => $req->status,
            'statusdescricao' => Requisicao::STATUS[$req->status] ?? null,
            'atendimento' => $req->atendimento,
            'destino' => $req->destino,
            'destinodescricao' => Requisicao::DESTINOS[$req->destino] ?? null,
            'codestoquelocalrequisitante' => $req->codestoquelocalrequisitante,
            'requisitante' => $req->EstoqueLocalRequisitante->estoquelocal ?? null,
            'siglarequisitante' => $req->EstoqueLocalRequisitante->sigla ?? null,
            'codestoquelocalrequisitada' => $req->codestoquelocalrequisitada,
            'requisitada' => $requisitada->estoquelocal ?? null,
            'siglarequisitada' => $requisitada->sigla ?? null,
            'codpessoa' => $req->codpessoa,
            'pessoa' => $req->Pessoa->fantasia ?? null,
            'nome' => $req->nome,
            'telefone' => $req->telefone,
            'endereco' => $req->endereco,
            'codnegocio' => $req->codnegocio,
            'codpdv' => $req->codpdv,
            'pdv' => $req->Pdv->apelido ?? null,
            'observacoes' => $req->observacoes,
            'codigoretirada' => $this->when($ehRequisitante, $req->codigoretirada),
            'finalizacao' => $req->finalizacao,
            'usuariofinalizacao' => $req->UsuarioFinalizacao->usuario ?? null,
            'cancelamento' => $req->cancelamento,
            'usuariocancelamento' => $req->UsuarioCancelamento->usuario ?? null,
            'criacao' => $req->criacao,
            'codusuariocriacao' => $req->codusuariocriacao,
            'usuariocriacao' => $req->UsuarioCriacao->usuario ?? null,
            'alteracao' => $req->alteracao,
            'codusuarioalteracao' => $req->codusuarioalteracao,
            'ehrequisitante' => $ehRequisitante,
            'ehrequisitada' => RequisicaoService::ehRequisitada($req, $pdv),
            'podeeditar' => RequisicaoService::podeEditar($req, $pdv),
            'podecancelar' => RequisicaoService::podeCancelar($req, $pdv),
            'podejustificar' => RequisicaoService::podeJustificar($req, $pdv),
            'podeseparar' => RequisicaoService::podeSeparar($req, $pdv),
            'podeentregar' => RequisicaoEntregaService::podeEntregar($req, $pdv),
            'comcodigo' => RequisicaoEntregaService::comCodigo($req),
            'partespendentes' => RequisicaoEntregaService::partesPendentes($req),
            'partes' => array_values(array_map(fn ($n) => [
                'codnegocio' => $n['codnegocio'],
                'codnegociostatus' => $n['codnegociostatus'],
                'lancamento' => $n['lancamento'],
                'entregue' => $n['entregue'],
            ], array_filter($calc['negocios'], fn ($n) => in_array($req->codrequisicao, $n['requisicoes'])))),
            'foradopedido' => array_map(fn ($f) => [
                'codnegocio' => $f['codnegocio'],
                'quantidade' => $f['quantidade'],
                'sigla' => $f['sigla'],
                'resolvido' => in_array($f['codprodutovariacao'], $substitutos),
                'produto' => (new ProdutoBarraPesquisaResource(ProdutoBarra::find($f['codprodutobarra'])))->toArray($request),
            ], $calc['fora'][$req->codrequisicao] ?? []),
            'itens' => $req->RequisicaoItemS->sortBy('codrequisicaoitem')->values()->map(fn ($i) => [
                'codrequisicaoitem' => $i->codrequisicaoitem,
                'codprodutobarra' => $i->codprodutobarra,
                'quantidade' => $i->quantidade,
                'quantidadeatendida' => $i->quantidadeatendida,
                // pedido x atendido na menor embalagem envolvida ("faltam 3 pacotes")
                'exibicao' => isset($calc['itens'][$i->codrequisicaoitem]) ? [
                    'pedido' => round($calc['itens'][$i->codrequisicaoitem]['pedido'] / $calc['itens'][$i->codrequisicaoitem]['fator'], 3),
                    'atendido' => round($calc['itens'][$i->codrequisicaoitem]['atendido'] / $calc['itens'][$i->codrequisicaoitem]['fator'], 3),
                    'sigla' => $calc['itens'][$i->codrequisicaoitem]['sigla'],
                ] : null,
                'motivo' => $i->motivo,
                'motivodescricao' => RequisicaoItem::MOTIVOS[$i->motivo] ?? null,
                'codprodutobarrasubstituto' => $i->codprodutobarrasubstituto,
                'observacao' => $i->observacao,
                'produto' => (new ProdutoBarraPesquisaResource($i->ProdutoBarra))->toArray($request),
                'substituto' => $i->ProdutoBarraSubstituto
                    ? (new ProdutoBarraPesquisaResource($i->ProdutoBarraSubstituto))->toArray($request)
                    : null,
            ]),
        ];
    }
}
