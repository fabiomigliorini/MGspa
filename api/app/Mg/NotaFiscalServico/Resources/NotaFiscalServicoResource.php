<?php

namespace Mg\NotaFiscalServico\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Mg\NotaFiscalServico\Nbs;
use Mg\NotaFiscalServico\NfseXmlService;
use Mg\NotaFiscalServico\NotaFiscalServico;
use Mg\NotaFiscalServico\TributacaoNacional;

class NotaFiscalServicoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'codnotafiscalservico' => $this->codnotafiscalservico,
            'codfilial' => $this->codfilial,
            'filial' => $this->Filial?->only(['codfilial', 'filial']),
            'codpessoa' => $this->codpessoa,
            'pessoa' => $this->formatPessoa(),
            'serie' => $this->serie,
            'numero' => $this->numero,
            'emissao' => $this->emissao,
            'competencia' => $this->competencia?->format('Y-m-d'),
            'codtributacaonacional' => $this->codtributacaonacional,
            'tributacaonacional' => $this->TributacaoNacional ? [
                'codtributacaonacional' => $this->TributacaoNacional->codtributacaonacional,
                'codigo' => $this->TributacaoNacional->codigo,
                'codigoformatado' => TributacaoNacional::formataCodigo($this->TributacaoNacional->codigo),
                'item' => $this->TributacaoNacional->item,
                'descricao' => $this->TributacaoNacional->descricao,
            ] : null,
            'codnbs' => $this->codnbs,
            'nbs' => $this->Nbs ? [
                'codnbs' => $this->Nbs->codnbs,
                'codigo' => $this->Nbs->codigo,
                'codigoformatado' => Nbs::formataCodigo($this->Nbs->codigo),
                'descricao' => $this->Nbs->descricao,
            ] : null,
            'descricao' => $this->descricao,
            'valorservico' => $this->valorservico,
            'valordescontoincondicionado' => $this->valordescontoincondicionado,
            'valordescontocondicionado' => $this->valordescontocondicionado,
            'issaliquota' => $this->issaliquota,
            'issretido' => $this->issretido,
            'tributospercentual' => $this->tributospercentual,
            'basecalculo' => $this->base_calculo,
            'issvalor' => $this->iss_valor,
            'valorliquido' => $this->valor_liquido,
            'codcidadeprestacao' => $this->codcidadeprestacao,
            'cidadeprestacao' => $this->CidadePrestacao ? [
                'codcidade' => $this->CidadePrestacao->codcidade,
                'cidade' => $this->CidadePrestacao->cidade,
                'uf' => $this->CidadePrestacao->Estado?->sigla,
            ] : null,
            'observacoes' => $this->observacoes,
            'status' => $this->status,
            'statusdescricao' => NotaFiscalServico::STATUS[$this->status] ?? null,
            'nfsechave' => $this->nfsechave,
            'nfsenumero' => $this->nfsenumero,
            'nfseautorizacao' => $this->nfseautorizacao,
            'nfsecancelamento' => $this->nfsecancelamento,
            'justificativa' => $this->justificativa,
            'mensagem' => $this->mensagem,
            // Substituicao (TASK-279 #5): os dois lados do vinculo
            'codnotafiscalservicosubstituida' => $this->codnotafiscalservicosubstituida,
            'substituicaomotivo' => $this->substituicaomotivo,
            'substituicaomotivodescricao' => NfseXmlService::MOTIVOS_SUBSTITUICAO[$this->substituicaomotivo] ?? null,
            'substituicaojustificativa' => $this->substituicaojustificativa,
            'substituida' => $this->resumo($this->NotaFiscalServicoSubstituida),
            'substituta' => $this->resumo($this->substituta()),
            'criacao' => $this->criacao,
            'alteracao' => $this->alteracao,
            'codusuariocriacao' => $this->codusuariocriacao,
            'codusuarioalteracao' => $this->codusuarioalteracao,
        ];
    }

    // A substituta em andamento ou autorizada (so pode haver uma); sem ela, a ultima
    private function substituta(): ?NotaFiscalServico
    {
        $substitutas = $this->NotaFiscalServicoSubstitutaS->sortByDesc('codnotafiscalservico');
        return $substitutas->first(fn ($n) => $n->status != NotaFiscalServico::STATUS_CANCELADA)
            ?? $substitutas->first();
    }

    private function resumo(?NotaFiscalServico $nfs): ?array
    {
        if (!$nfs) {
            return null;
        }
        return [
            'codnotafiscalservico' => $nfs->codnotafiscalservico,
            'serie' => $nfs->serie,
            'numero' => $nfs->numero,
            'nfsenumero' => $nfs->nfsenumero,
            'nfseautorizacao' => $nfs->nfseautorizacao,
            'status' => $nfs->status,
            'substituicaomotivo' => $nfs->substituicaomotivo,
            'substituicaomotivodescricao' => NfseXmlService::MOTIVOS_SUBSTITUICAO[$nfs->substituicaomotivo] ?? null,
            'substituicaojustificativa' => $nfs->substituicaojustificativa,
        ];
    }

    private function formatPessoa(): ?array
    {
        if (!$this->Pessoa) {
            return null;
        }
        // retemiss: o formulario avisa quando a retencao da nota diverge do cadastro (TASK-282)
        $ret = $this->Pessoa->only(['codpessoa', 'pessoa', 'fantasia', 'cnpj', 'fisica', 'retemiss']);
        $ret['cidade'] = $this->Pessoa->Cidade?->cidade;
        $ret['uf'] = $this->Pessoa->Cidade?->Estado?->sigla;
        // Os mesmos destinatarios que o NfseService::mail usa quando ninguem informa
        $ret['emailnfe'] = $this->Pessoa->PessoaEmailS->where('nfe', true)->pluck('email')->implode(', ');
        return $ret;
    }
}
