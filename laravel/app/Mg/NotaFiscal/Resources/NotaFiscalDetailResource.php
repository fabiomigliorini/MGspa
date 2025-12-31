<?php

namespace Mg\NotaFiscal\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Mg\NotaFiscal\NotaFiscalService;
use Mg\NotaFiscal\Resources\NotaFiscalProdutoBarraResource;
use Mg\NotaFiscal\Resources\NotaFiscalPagamentoResource;
use Mg\NotaFiscal\Resources\NotaFiscalDuplicatasResource;
use Mg\NotaFiscal\Resources\NotaFiscalReferenciadaResource;
use Mg\NotaFiscal\Resources\NotaFiscalCartaCorrecaoResource;

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

            // Informações Adicionais
            'informacoescontribuinte' => $this->informacoescontribuinte,
            'informacoesfisco' => $this->informacoesfisco,

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
            // 'nfexml' => $this->nfexml,
            // 'nfeprotocolo' => $this->nfeprotocolo,
            // 'nferejeicao' => $this->nferejeicao,

            // Status
            'status' => $this->getStatus(),

            // Relacionamentos
            'itens' => $this->whenLoaded('NotaFiscalProdutoBarraS', function () {
                return NotaFiscalProdutoBarraResource::collection($this->NotaFiscalProdutoBarraS);
            }),
            'pagamentos' => $this->whenLoaded('NotaFiscalPagamentoS', function () {
                return NotaFiscalPagamentoResource::collection($this->NotaFiscalPagamentoS);
            }),
            'duplicatas' => $this->whenLoaded('NotaFiscalDuplicatasS', function () {
                return NotaFiscalDuplicatasResource::collection($this->NotaFiscalDuplicatasS);
            }),
            'notasReferenciadas' => $this->whenLoaded('NotaFiscalReferenciadaS', function () {
                return NotaFiscalReferenciadaResource::collection($this->NotaFiscalReferenciadaS);
            }),
            'cartasCorrecao' => $this->whenLoaded('NotaFiscalCartaCorrecaoS', function () {
                return NotaFiscalCartaCorrecaoResource::collection($this->NotaFiscalCartaCorrecaoS);
            }),

            // Timestamps
            'criacao' => $this->criacao,
            'alteracao' => $this->alteracao,
        ];
    }

    private function formatFilial(): ?array
    {
        if (!$this->relationLoaded('Filial')) {
            return null;
        }

        return $this->Filial?->only(['codfilial', 'filial', 'cnpj']);
    }

    private function formatEstoqueLocal(): ?array
    {
        if (!$this->relationLoaded('EstoqueLocal')) {
            return null;
        }

        return $this->EstoqueLocal?->only(['codestoquelocal', 'estoquelocal']);
    }

    private function formatPessoa(): ?array
    {
        if (!$this->relationLoaded('Pessoa')) {
            return null;
        }

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
        if (!$this->relationLoaded('NaturezaOperacao')) {
            return null;
        }

        return $this->NaturezaOperacao?->only(['codnaturezaoperacao', 'naturezaoperacao', 'cfop']);
    }

    private function formatOperacao(): ?array
    {
        if (!$this->relationLoaded('Operacao')) {
            return null;
        }

        return $this->Operacao?->only(['codoperacao', 'operacao']);
    }

    private function formatTransportador(): ?array
    {
        if (!$this->relationLoaded('PessoaTransportador')) {
            return null;
        }

        return $this->PessoaTransportador?->only(['codpessoa', 'pessoa', 'fantasia', 'cnpj', 'ie']);
    }

    private function formatEstadoPlaca(): ?array
    {
        if (!$this->relationLoaded('EstadoPlaca')) {
            return null;
        }

        return $this->EstadoPlaca?->only(['codestado', 'estado', 'sigla']);
    }

    private function getStatus(): string
    {
        return NotaFiscalService::getStatusNota($this->resource);
    }
}
