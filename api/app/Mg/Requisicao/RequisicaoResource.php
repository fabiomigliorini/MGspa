<?php

namespace Mg\Requisicao;

use Illuminate\Http\Resources\Json\JsonResource;
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
            'itens' => $req->RequisicaoItemS->sortBy('codrequisicaoitem')->values()->map(fn ($i) => [
                'codrequisicaoitem' => $i->codrequisicaoitem,
                'codprodutobarra' => $i->codprodutobarra,
                'quantidade' => $i->quantidade,
                'quantidadeatendida' => $i->quantidadeatendida,
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
