<?php

namespace Mg\NotaFiscal\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
// use Mg\NotaFiscal\NotaFiscalService;
use Mg\NotaFiscal\Resources\NotaFiscalProdutoBarraResource;
use Mg\NotaFiscal\Resources\NotaFiscalPagamentoResource;
use Mg\NotaFiscal\Resources\NotaFiscalDuplicatasResource;
use Mg\NotaFiscal\Resources\NotaFiscalReferenciadaResource;
use Mg\NotaFiscal\Resources\NotaFiscalCartaCorrecaoResource;
use Mg\NfeTerceiro\Resources\NfeTerceiroResource;

class NotaFiscalDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'codnotafiscal' => $this->codnotafiscal,

            // Filial
            'codfilial' => $this->codfilial,
            'filial' => $this->formatFilial(),

            // Estoque Local
            'codestoquelocal' => $this->codestoquelocal,
            'estoqueLocal' => $this->formatEstoqueLocal(),

            // Pessoa
            'codpessoa' => $this->codpessoa,
            'pessoa' => $this->formatPessoa(),

            // Natureza de Operação
            'codnaturezaoperacao' => $this->codnaturezaoperacao,
            'naturezaOperacao' => $this->formatNaturezaOperacao(),

            // Operação
            'codoperacao' => $this->codoperacao,
            'operacao' => $this->formatOperacao(),

            // Dados da Nota
            'emitida' => $this->emitida,
            'modelo' => $this->modelo,
            'serie' => $this->serie,
            'numero' => $this->numero,
            'nfechave' => $this->nfechave,

            // Datas
            'emissao' => $this->emissao,
            'saida' => $this->saida,

            // Valores
            'valoroutras' => $this->valoroutras,
            'valorprodutos' => $this->valorprodutos,
            'valorfrete' => $this->valorfrete,
            'valorseguro' => $this->valorseguro,
            'valordesconto' => $this->valordesconto,
            'valortotal' => $this->valortotal,

            // Observacoes
            'observacoes' => $this->observacoes,

            // Transporte
            'frete' => $this->frete,
            'codtransportador' => $this->codtransportador,
            'transportador' => $this->formatTransportador(),
            'volumes' => $this->volumes,
            'volumesespecie' => $this->volumesespecie,
            'volumesmarca' => $this->volumesmarca,
            'volumesnumero' => $this->volumesnumero,
            'pesobruto' => $this->pesobruto,
            'pesoliquido' => $this->pesoliquido,
            'placa' => $this->placa,
            'codestadoplaca' => $this->codestadoplaca,
            'estadoPlaca' => $this->formatEstadoPlaca(),

            // NFe
            'nfeautorizacao' => $this->nfeautorizacao,
            'nfedataautorizacao' => $this->nfedataautorizacao,
            'nfecancelamento' => $this->nfecancelamento,
            'nfedatacancelamento' => $this->nfedatacancelamento,
            'nfeinutilizacao' => $this->nfeinutilizacao,
            'nfedatainutilizacao' => $this->nfedatainutilizacao,
            'justificativa' => $this->justificativa,
            // 'nfexml' => $this->nfexml,
            // 'nfeprotocolo' => $this->nfeprotocolo,
            // 'nferejeicao' => $this->nferejeicao,

            // Status
            'status' => $this->status,

            // Relacionamentos
            'itens' => NotaFiscalProdutoBarraResource::collection($this->NotaFiscalProdutoBarraS),
            'pagamentos' => NotaFiscalPagamentoResource::collection($this->NotaFiscalPagamentoS),
            'duplicatas' => NotaFiscalDuplicatasResource::collection($this->NotaFiscalDuplicatasS),
            'notasReferenciadas' => NotaFiscalReferenciadaResource::collection($this->NotaFiscalReferenciadaS),
            'cartasCorrecao' => NotaFiscalCartaCorrecaoResource::collection($this->NotaFiscalCartaCorrecaoS),
            'nfeTerceiros' => NfeTerceiroResource::collection($this->NfeTerceiroS),

            // Timestamps
            'criacao' => $this->criacao,
            'alteracao' => $this->alteracao,
        ];
    }

    private function formatFilial(): ?array
    {
        return $this->Filial?->only(['codfilial', 'filial', 'cnpj']);
    }

    private function formatEstoqueLocal(): ?array
    {
        return $this->EstoqueLocal?->only(['codestoquelocal', 'estoquelocal']);
    }

    private function formatPessoa(): ?array
    {
        $ret = $this->Pessoa?->only([
            'codpessoa',
            'pessoa',
            'fantasia',
            'cnpj',
            'fisica',
            'ie',
            'endereco',
            'numero',
            'bairro',
            'complemento',
            'cep',
            'telefone1',
            'email',
        ]);

        $ret['cidade'] = $this->Pessoa?->Cidade?->cidade;
        $ret['uf'] = $this->Pessoa?->Cidade?->Estado?->sigla;

        return $ret;
    }

    private function formatNaturezaOperacao(): ?array
    {
        return $this->NaturezaOperacao?->only(['codnaturezaoperacao', 'naturezaoperacao', 'cfop']);
    }

    private function formatOperacao(): ?array
    {
        return $this->Operacao?->only(['codoperacao', 'operacao']);
    }

    private function formatTransportador(): ?array
    {
        return $this->PessoaTransportador?->only(['codpessoa', 'pessoa', 'fantasia', 'cnpj', 'ie']);
    }

    private function formatEstadoPlaca(): ?array
    {
        return $this->EstadoPlaca?->only(['codestado', 'estado', 'sigla']);
    }
}
