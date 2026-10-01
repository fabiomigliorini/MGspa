<?php

namespace Mg\Pdv;

use Carbon\Carbon;
use Mg\Negocio\Negocio;
use Mg\Negocio\NegocioFormaPagamentoService;
use Mg\Negocio\NegocioParcela;
use Mg\Negocio\NegocioParcelaService;
use Mg\Negocio\NegocioService;
use Mg\Pagamento\Pagamento;
use Mg\Pagamento\PagamentoService;
use Mg\Titulo\TituloService;

/**
 * Pagamentos e parcelas do negocio no formato novo do PDV (M5 do plano
 * doc-3): o wizard de cobranca manda pagamentos (meio, principal, juros,
 * desconto, troco) e parcelas (condicao, vencimento e valor editados) por
 * uuid, e le' o mesmo formato de volta.
 *
 * O PDV novo se identifica pelo cabecalho X-Pagamento-Formato: 2. Sem ele
 * vale o formato antigo (NegocioFormaPagamentoService) ate todos os PDVs
 * atualizarem.
 */
class PdvNegocioPagamentoService
{
    // meios que o PDV lanca sozinho (os integrados vem do servidor)
    const MEIOS_PDV = [
        PagamentoService::MEIO_DINHEIRO,
        PagamentoService::MEIO_CHEQUE,
        PagamentoService::MEIO_CREDITO,
        PagamentoService::MEIO_DEBITO,
        PagamentoService::MEIO_VALE,
        PagamentoService::MEIO_OUTROS,
    ];

    // condicoes que o PDV escolhe (o vale da devolucao nasce no servidor)
    const CONDICOES_PDV = [
        NegocioParcelaService::CONDICAO_FECHAMENTO,
        NegocioParcelaService::CONDICAO_PARCELADO,
        NegocioParcelaService::CONDICAO_BOLETO,
        NegocioParcelaService::CONDICAO_ENTREGA,
        NegocioParcelaService::CONDICAO_PIX,
    ];

    public static function formatoNovo(): bool
    {
        return request()->header('X-Pagamento-Formato') == '2';
    }

    // Upsert por uuid, apaga o que nao veio. Pagamento integrado ou que ja'
    // saiu de pendente e parcela que ja' virou titulo nao mudam.
    public static function importar(Negocio $negocio, array $pagamentos, array $parcelas): void
    {
        $uuids = [];
        foreach ($pagamentos as $dados) {
            if (!empty($dados['integracao'])) {
                continue;
            }
            $uuids[] = $dados['uuid'];
            static::importarPagamento($negocio, $dados);
        }
        foreach ($negocio->PagamentoS()->whereNotIn('uuid', $uuids)->get() as $pag) {
            if ($pag->ehIntegrado() || $pag->estado != PagamentoService::ESTADO_PENDENTE) {
                continue;
            }
            $pag->delete();
        }

        $uuids = [];
        foreach ($parcelas as $dados) {
            $uuids[] = $dados['uuid'];
            static::importarParcela($negocio, $dados);
        }
        $negocio->NegocioParcelaS()
            ->whereNull('codtitulo')
            ->whereNotIn('uuid', $uuids)
            ->delete();
    }

    public static function importarPagamento(Negocio $negocio, array $dados): Pagamento
    {
        $pag = Pagamento::firstOrNew(['uuid' => $dados['uuid']]);
        if (!empty($pag->codnegocio) && $pag->codnegocio != $negocio->codnegocio) {
            abort(422, "Pagamento de outro negócio ({$pag->codnegocio})!");
        }
        if ($pag->exists && ($pag->ehIntegrado() || $pag->estado != PagamentoService::ESTADO_PENDENTE)) {
            return $pag;
        }
        $meio = (int) ($dados['meio'] ?? 0);
        if (!in_array($meio, static::MEIOS_PDV)) {
            abort(422, "Meio de pagamento {$meio} não pode ser lançado pelo PDV!");
        }
        PagamentoService::preencher($pag, [
            'uuid' => $dados['uuid'],
            'codnegocio' => $negocio->codnegocio,
            'codfilial' => $negocio->codfilial,
            'codpdv' => $negocio->codpdv,
            'meio' => $meio,
            'estado' => PagamentoService::ESTADO_PENDENTE,
            'principal' => $dados['principal'] ?? 0,
            'juros' => $dados['juros'] ?? 0,
            'multa' => 0,
            'desconto' => $dados['desconto'] ?? 0,
            'valortroco' => $dados['valortroco'] ?? null,
            'parcelas' => $dados['parcelas'] ?? null,
            'codpessoa' => $dados['codpessoa'] ?? null,
            'bandeira' => $dados['bandeira'] ?? null,
            'autorizacao' => $dados['autorizacao'] ?? null,
            'codmaquineta' => $dados['codmaquineta'] ?? null,
            'codtitulo' => $dados['codtitulo'] ?? null,
            'cmc7' => $dados['cmc7'] ?? null,
            'chequevencimento' => $dados['chequevencimento'] ?? null,
            'chequecnpj' => $dados['chequecnpj'] ?? null,
            'chequeemitente' => $dados['chequeemitente'] ?? null,
        ]);
        if ($pag->desconto > 0 && $meio != PagamentoService::MEIO_DINHEIRO) {
            abort(422, 'Desconto por forma de pagamento só no dinheiro!');
        }
        $pag->save();
        return $pag;
    }

    public static function importarParcela(Negocio $negocio, array $dados): NegocioParcela
    {
        $np = NegocioParcela::firstOrNew(['uuid' => $dados['uuid']]);
        if (!empty($np->codnegocio) && $np->codnegocio != $negocio->codnegocio) {
            abort(422, "Parcela de outro negócio ({$np->codnegocio})!");
        }
        if (!empty($np->codtitulo)) {
            return $np;
        }
        $condicao = $dados['condicao'] ?? null;
        if (!in_array($condicao, static::CONDICOES_PDV)) {
            abort(422, "Condição de prazo {$condicao} não pode ser lançada pelo PDV!");
        }
        $valor = round((float) ($dados['valor'] ?? 0), 2);
        $juros = round((float) ($dados['juros'] ?? 0), 2);
        if ($valor <= 0) {
            abort(422, 'O valor de cada parcela precisa ser maior que zero!');
        }
        if ($juros < 0 || $juros > $valor) {
            abort(422, 'Juros da parcela inválido!');
        }
        if (empty($dados['vencimento'])) {
            abort(422, 'Informe o vencimento de cada parcela!');
        }
        $np->fill([
            'uuid' => $dados['uuid'],
            'codnegocio' => $negocio->codnegocio,
            'condicao' => $condicao,
            'numero' => (int) ($dados['numero'] ?? 1),
            'vencimento' => Carbon::parse($dados['vencimento'])->startOfDay(),
            'valor' => $valor,
            'juros' => $juros,
            'uuidforma' => null,
        ]);
        $np->save();
        return $np;
    }

    // Parcela vencida nao vira titulo: o operador ajusta no Prazo
    public static function validarVencimentos(Negocio $negocio): void
    {
        $vencida = $negocio->NegocioParcelaS()
            ->whereNull('codtitulo')
            ->whereNull('uuidforma')
            ->where('vencimento', '<', Carbon::today())
            ->orderBy('vencimento')
            ->first();
        if ($vencida) {
            $venc = $vencida->vencimento->format('d/m/Y');
            abort(422, "A parcela {$vencida->numero} vence em {$venc}, antes de hoje! Exclua o prazo e lance de novo.");
        }
    }

    // ---------------------------------------------------------------
    // Formato novo devolvido ao PDV
    // ---------------------------------------------------------------

    // Cancelado so' aparece quando o negocio inteiro esta' cancelado
    public static function pagamentos(Negocio $negocio): array
    {
        $cancelado = $negocio->codnegociostatus == NegocioService::STATUS_CANCELADO;
        $ret = [];
        foreach ($negocio->PagamentoS()->orderBy('codpagamento')->get() as $pag) {
            if (!$cancelado && $pag->estado == PagamentoService::ESTADO_CANCELADO) {
                continue;
            }
            $ret[] = static::pagamento($pag);
        }
        return $ret;
    }

    public static function pagamento(Pagamento $pag): array
    {
        $ret = [
            'codpagamento' => $pag->codpagamento,
            'uuid' => $pag->uuid,
            'codnegocio' => $pag->codnegocio,
            'meio' => $pag->meio,
            'meiodescricao' => PagamentoService::MEIOS[$pag->meio] ?? null,
            'estado' => $pag->estado,
            'saida' => $pag->ehSaida(),
            'principal' => $pag->principal,
            'juros' => $pag->juros,
            'multa' => $pag->multa,
            'desconto' => $pag->desconto,
            'total' => $pag->total,
            'valortroco' => $pag->valortroco,
            'integracao' => $pag->ehIntegrado(),
            'codpessoa' => $pag->codpessoa,
            'parceiro' => $pag->Pessoa->fantasia ?? null,
            'bandeira' => $pag->bandeira,
            'nomebandeira' => NegocioFormaPagamentoService::BANDEIRAS[$pag->bandeira] ?? null,
            'autorizacao' => $pag->autorizacao,
            'parcelas' => $pag->parcelas,
            'codmaquineta' => $pag->codmaquineta,
            'maquineta' => $pag->Maquineta->apelido ?? null,
            'codtitulo' => $pag->codtitulo,
            'cmc7' => $pag->cmc7,
            'chequevencimento' => $pag->chequevencimento ? $pag->chequevencimento->format('Y-m-d') : null,
            'chequecnpj' => $pag->chequecnpj,
            'chequeemitente' => $pag->chequeemitente,
            'codpixcob' => $pag->codpixcob,
            'codpagarmepedido' => $pag->codpagarmepedido,
            'codsauruspedido' => $pag->codsauruspedido,
            'codliopedido' => $pag->codliopedido,
            'codportadordestino' => $pag->codportadordestino,
            // logo do banco na listagem do PDV (public/bancos/{codbanco}.svg)
            'codbanco' => $pag->PixCob->Portador->codbanco ?? null,
            'criacao' => $pag->criacao,
            'codusuariocriacao' => $pag->codusuariocriacao,
            'alteracao' => $pag->alteracao,
            'codusuarioalteracao' => $pag->codusuarioalteracao,
        ];
        // vale compras usado no pagamento: card do Contra Vale (saldo atual do titulo)
        if (!empty($pag->codtitulo) && $pag->Titulo->codtipotitulo == TituloService::TIPO_VALE) {
            $ret['valenumero'] = $pag->Titulo->numero;
            $ret['valefavorecido'] = $pag->Titulo->Pessoa->fantasia;
            $ret['valesaldo'] = round(-$pag->Titulo->saldo, 2);
        }
        return $ret;
    }

    public static function parcelas(Negocio $negocio): array
    {
        $ret = [];
        $parcelas = $negocio->NegocioParcelaS()
            ->orderBy('condicao')
            ->orderBy('vencimento')
            ->orderBy('codnegocioparcela')
            ->get();
        foreach ($parcelas as $np) {
            $ret[] = [
                'codnegocioparcela' => $np->codnegocioparcela,
                'uuid' => $np->uuid,
                'codnegocio' => $np->codnegocio,
                'condicao' => $np->condicao,
                'condicaodescricao' => NegocioParcelaService::CONDICOES[$np->condicao] ?? null,
                'numero' => $np->numero,
                'vencimento' => $np->vencimento->format('Y-m-d'),
                'valor' => $np->valor,
                'juros' => $np->juros,
                'codtitulo' => $np->codtitulo,
                'titulonumero' => $np->Titulo->numero ?? null,
                'criacao' => $np->criacao,
                'alteracao' => $np->alteracao,
            ];
        }
        return $ret;
    }

    // ---------------------------------------------------------------
    // Filtros da listagem do PDV (subquery de codnegocio para whereIn)
    // ---------------------------------------------------------------

    // forma = lista de "m<meio>" (pagamento) e "c<condicao>" (parcela);
    // negocio com qualquer uma delas
    public static function filtroForma($query, array $formas): void
    {
        $meios = [];
        $condicoes = [];
        foreach ($formas as $forma) {
            if (substr($forma, 0, 1) == 'm') {
                $meios[] = (int) substr($forma, 1);
            } elseif (substr($forma, 0, 1) == 'c') {
                $condicoes[] = substr($forma, 1, 1);
            }
        }
        $query->select('codnegocio')->from('tblpagamento')->whereNotNull('codnegocio')
            ->where('estado', '!=', PagamentoService::ESTADO_CANCELADO)
            ->whereIn('meio', $meios);
        if (!empty($condicoes)) {
            $query->union(
                \DB::table('tblnegocioparcela')->select('codnegocio')->whereIn('condicao', $condicoes)
            );
        }
    }
}
