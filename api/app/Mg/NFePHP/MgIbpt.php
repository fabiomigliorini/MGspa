<?php

namespace Mg\NFePHP;

use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

use Mg\Filial\Filial;
use Mg\Ibpt\Ibpt as IbptTabela;
use Mg\NotaFiscal\NotaFiscal;
use Mg\NotaFiscal\NotaFiscalProdutoBarra;

use NFePHP\Ibpt\Ibpt;
use NFePHP\Ibpt\RestInterface;

class MgIbpt extends Ibpt
{
    /**
     * Enquanto a API do IBPT estiver fora, não adianta tentar de novo a cada item de
     * cada nota - cada tentativa custa o timeout inteiro. Na primeira falha paramos
     * de consultar por um tempo e servimos só o cache.
     */
    const CIRCUITO_CHAVE = 'ibpt:indisponivel';
    const CIRCUITO_SEGUNDOS = 1800;

    protected $filial;

    public function __construct(
        Filial $filial,
        $proxy = [],
        RestInterface $rest = null
    ) {
        $this->filial = $filial;
        $cnpj = mascarar($filial->Pessoa->cnpj, '##############');
        $token = $filial->tokenibpt;
        parent::__construct($cnpj, $token, $proxy, $rest ?: new MgIbptRest());
    }

    /**
     * Devolve o registro de cache com os percentuais de tributo do item.
     *
     * A emissão nunca depende da API: o cache é a fonte primária e, se estiver
     * vencido, seguimos com o último valor conhecido enquanto tentamos atualizar
     * em background do próprio fluxo (stale-while-revalidate). Alíquota do mês
     * passado é incomparavelmente melhor que nota sem a informação da Lei 12.741.
     */
    public function pesquisar(NotaFiscalProdutoBarra $nfpb)
    {
        $nf = $nfpb->NotaFiscal;

        // A alíquota é da UF de DESTINO (do cliente). Sem destinatário com endereço
        // - NFCe para consumidor não identificado - vale a UF da própria filial.
        $cidade = optional($nf->Pessoa)->Cidade ?: $nf->Filial->Pessoa->Cidade;

        $reg = IbptTabela::firstOrNew([
            'codestado' => $cidade->codestado,
            'ncm' => $nfpb->ProdutoBarra->Produto->Ncm->ncm,
            'extarif' => 0,
        ]);

        if ($this->deveConsultar($nf, $reg)) {
            $this->consultar($nfpb, $cidade->Estado->sigla, $reg);
        }

        return $reg;
    }

    /**
     * Só vale a pena bater na API quando o cache está mesmo vencido, não estamos
     * em contingência e a API não acabou de falhar.
     */
    protected function deveConsultar(NotaFiscal $nf, IbptTabela $reg)
    {
        // Contingência offline da NFCe não faz consulta externa
        if ($nf->Filial->Empresa->modoemissaonfce == 9) {
            return false;
        }

        // Cache dentro da vigência
        if (!empty($reg->vigenciafim) && $nf->emissao->lte($reg->vigenciafim->copy()->endOfDay())) {
            return false;
        }

        // Circuito aberto: a API falhou há pouco, não insiste item a item
        if (Cache::get(self::CIRCUITO_CHAVE)) {
            return false;
        }

        return true;
    }

    /**
     * Consulta a API e atualiza o cache. Qualquer falha é absorvida aqui - o
     * chamador segue com o registro que já tinha.
     */
    protected function consultar(NotaFiscalProdutoBarra $nfpb, $uf, IbptTabela $reg)
    {
        try {
            $consulta = $this->productTaxes(
                $uf,
                $reg->ncm,
                $reg->extarif,
                $nfpb->ProdutoBarra->Produto->produto,
                $nfpb->ProdutoBarra->UnidadeMedida->sigla,
                $nfpb->valorunitario,
                $nfpb->ProdutoBarra->barras,
                $nfpb->ProdutoBarra->codproduto
            );
        } catch (Exception $e) {
            $this->abrirCircuito($e->getMessage());
            return;
        }

        $httpcode = $consulta->httpcode ?? 200;

        // 404 é NCM que o IBPT não reconhece - o serviço está de pé, o dado é que
        // não existe. Marca a vigência para hoje só para não reconsultar o mesmo
        // NCM a cada item, sem descartar alíquota que já tivéssemos em cache.
        if ($httpcode == 404) {
            if (empty($reg->descricao)) {
                $reg->descricao = 'Nao Localizado';
            }
            $reg->vigenciainicio = $reg->vigenciainicio ?: Carbon::today();
            $reg->vigenciafim = Carbon::today();
            $reg->save();
            return;
        }

        // Qualquer outro código (403 token inválido, 5xx, ...) é problema de serviço
        if ($httpcode != 200 || !isset($consulta->Descricao)) {
            $this->abrirCircuito("HTTP {$httpcode}");
            return;
        }

        $reg->descricao = $consulta->Descricao;
        $reg->nacional = $consulta->Nacional;
        $reg->estadual = $consulta->Estadual;
        $reg->importado = $consulta->Importado;
        $reg->municipal = $consulta->Municipal;
        $reg->tipo = $consulta->Tipo;
        $reg->vigenciainicio = Carbon::createFromFormat('d/m/Y', $consulta->VigenciaInicio);
        $reg->vigenciafim = Carbon::createFromFormat('d/m/Y', $consulta->VigenciaFim);
        $reg->chave = $consulta->Chave;
        $reg->versao = $consulta->Versao;
        $reg->fonte = $consulta->Fonte;
        $reg->save();
    }

    protected function abrirCircuito($motivo)
    {
        Cache::put(self::CIRCUITO_CHAVE, true, self::CIRCUITO_SEGUNDOS);
        Log::warning("IBPT indisponível, emitindo com o cache existente. {$motivo}");
    }
}
