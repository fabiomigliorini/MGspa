<?php

namespace Mg\Negocio;

use Carbon\Carbon;
use Mg\FormaPagamento\FormaPagamento;
use Mg\Maquineta\Maquineta;
use Mg\Maquineta\MaquinetaService;
use Mg\Pagamento\Pagamento;
use Mg\Pagamento\PagamentoService;
use Mg\Portador\Portador;
use Mg\Titulo\TituloService;

/**
 * Formato antigo da forma de pagamento do negocio (M4 do plano doc-3).
 *
 * tblnegocioformapagamento virou tblpagamento + tblnegocioparcela, mas o PDV
 * continua mandando e lendo o formato de hoje ate o M5: aqui ficam as duas
 * traducoes (forma antiga -> pagamento/parcelas no sync; pagamento/parcelas
 * -> forma antiga no NegocioResource). O codigo da forma antiga e' deduzido
 * de meio/condicao/integracao. Sai no M5.
 */
class NegocioFormaPagamentoService
{
    const BANDEIRAS = [
        1 => 'Visa',
        2 => 'Mastercard',
        3 => 'American Express',
        4 => 'Sorocred',
        5 => 'Diners Club',
        6 => 'Elo',
        7 => 'Hipercard',
        8 => 'Aura',
        9 => 'Cabal',
        99 => 'Outros'
    ];

    const TIPOS = [
        01 => 'Dinheiro',
        02 => 'Cheque',
        03 => 'Cartão de Crédito',
        04 => 'Cartão de Débito',
        05 => 'Crédito Loja',
        10 => 'Vale Alimentação',
        11 => 'Vale Refeição',
        12 => 'Vale Presente',
        13 => 'Vale Combustível',
        15 => 'Boleto Bancário',
        16 => 'Depósito Bancário',
        17 => 'Pagamento Instantâneo (PIX)',
        18 => 'Transferência bancária, Carteira Digital',
        19 => 'Programa de fidelidade, Cashback, Crédito Virtual',
        90 => 'Sem pagamento',
        99 => 'Outros'
    ];

    const CODFORMAPAGAMENTO_DINHEIRO = 1010;
    const CODFORMAPAGAMENTO_CHEQUE = 1020;
    const CODFORMAPAGAMENTO_VALE = 1030;
    const CODFORMAPAGAMENTO_ENTREGA_AVISTA = 1099;
    const CODFORMAPAGAMENTO_CARTAO_MANUAL = 2010;
    const CODFORMAPAGAMENTO_FECHAMENTO = 3020;
    const CODFORMAPAGAMENTO_BOLETO = 4100;
    const CODFORMAPAGAMENTO_CREDIARIO = 5100;
    const CODFORMAPAGAMENTO_LIO = 5603;
    const CODFORMAPAGAMENTO_PIX_QR = 5604;
    const CODFORMAPAGAMENTO_PAGARME = 5605;
    const CODFORMAPAGAMENTO_PIX_CHAVE = 5606;
    const CODFORMAPAGAMENTO_MERCOSPAY = 5607;
    const CODFORMAPAGAMENTO_SAURUS = 5608;

    // portador (adquirente) do Mercos Pay: separa o Mercos Pay (meio 99) do
    // cartao antigo sem tipo ao deduzir a forma antiga
    const CODPORTADOR_MERCOSPAY = 202046;

    // forma antiga a prazo -> condicao da parcela
    const CONDICAO_DA_FORMA = [
        3010 => NegocioParcelaService::CONDICAO_FECHAMENTO,
        3020 => NegocioParcelaService::CONDICAO_FECHAMENTO,
        5601 => NegocioParcelaService::CONDICAO_FECHAMENTO,
        5100 => NegocioParcelaService::CONDICAO_PARCELADO,
        4100 => NegocioParcelaService::CONDICAO_BOLETO,
        1099 => NegocioParcelaService::CONDICAO_ENTREGA,
        5606 => NegocioParcelaService::CONDICAO_PIX,
        5602 => NegocioParcelaService::CONDICAO_PIX,
    ];

    // condicao -> forma antiga e tPag
    const FORMA_DA_CONDICAO = [
        NegocioParcelaService::CONDICAO_FECHAMENTO => 3020,
        NegocioParcelaService::CONDICAO_PARCELADO => 5100,
        NegocioParcelaService::CONDICAO_BOLETO => 4100,
        NegocioParcelaService::CONDICAO_ENTREGA => 1099,
        NegocioParcelaService::CONDICAO_PIX => 5606,
        NegocioParcelaService::CONDICAO_VALE => 1030,
    ];

    const TIPO_DA_CONDICAO = [
        NegocioParcelaService::CONDICAO_FECHAMENTO => 5,
        NegocioParcelaService::CONDICAO_PARCELADO => 5,
        NegocioParcelaService::CONDICAO_BOLETO => 15,
        NegocioParcelaService::CONDICAO_ENTREGA => 5,
        NegocioParcelaService::CONDICAO_PIX => 16,
        NegocioParcelaService::CONDICAO_VALE => 90,
    ];

    // ---------------------------------------------------------------
    // Forma antiga -> pagamento / parcelas (sync do PDV)
    // ---------------------------------------------------------------

    // Meio do pagamento pelo tipo (tPag) que o PDV manda ou pela forma
    public static function meio(?int $tipo, ?int $codformapagamento): int
    {
        if (!empty($tipo) && array_key_exists($tipo, PagamentoService::MEIOS) && $tipo < 90) {
            return $tipo;
        }
        switch ($codformapagamento) {
            case static::CODFORMAPAGAMENTO_DINHEIRO:
                return PagamentoService::MEIO_DINHEIRO;
            case static::CODFORMAPAGAMENTO_CHEQUE:
                return PagamentoService::MEIO_CHEQUE;
            case static::CODFORMAPAGAMENTO_VALE:
                return PagamentoService::MEIO_VALE;
            case static::CODFORMAPAGAMENTO_PIX_QR:
                return PagamentoService::MEIO_PIX;
        }
        return PagamentoService::MEIO_OUTROS;
    }

    // A forma antiga e' prazo (vira parcela)?
    public static function ehPrazo(array $pagto): bool
    {
        return array_key_exists((int) ($pagto['codformapagamento'] ?? 0), static::CONDICAO_DA_FORMA);
    }

    // Importa os pagamentos do PDV (formato antigo): upsert por uuid, apaga
    // o que nao veio. Os integrados (PIX QR, PagarMe, Saurus) sao do
    // servidor: o sync nao grava nem apaga.
    public static function importar(Negocio $negocio, array $pagamentos): void
    {
        $uuids = [];
        foreach ($pagamentos as $pagto) {
            if (!empty($pagto['integracao'])) {
                continue;
            }
            $uuids[] = $pagto['uuid'];
            if (static::ehPrazo($pagto)) {
                static::importarPrazo($negocio, $pagto);
            } else {
                static::importarPagamento($negocio, $pagto);
            }
        }

        // exclui o que nao veio no post
        foreach ($negocio->PagamentoS()->whereNotIn('uuid', $uuids)->get() as $pag) {
            if ($pag->ehIntegrado() || $pag->estado != PagamentoService::ESTADO_PENDENTE) {
                continue;
            }
            $pag->delete();
        }
        $negocio->NegocioParcelaS()
            ->whereNull('codtitulo')
            ->where(function ($q) use ($uuids) {
                $q->whereNull('uuidforma')->orWhereNotIn('uuidforma', $uuids);
            })
            ->delete();
    }

    public static function importarPagamento(Negocio $negocio, array $pagto): Pagamento
    {
        $pag = Pagamento::firstOrNew(['uuid' => $pagto['uuid']]);
        if (!empty($pag->codnegocio) && $pag->codnegocio != $negocio->codnegocio) {
            throw new \Exception("Tentando atualizar um pagamento de outro negocio {$pag->codnegocio}/{$negocio->codnegocio}!", 1);
        }
        if ($pag->exists && $pag->estado != PagamentoService::ESTADO_PENDENTE) {
            return $pag;
        }
        // a mesma forma pode ter sido prazo antes
        $negocio->NegocioParcelaS()->where('uuidforma', $pagto['uuid'])->whereNull('codtitulo')->delete();

        $valor = round((float) ($pagto['valorpagamento'] ?? 0), 2);
        $troco = round((float) ($pagto['valortroco'] ?? 0), 2);
        $juros = round((float) ($pagto['valorjuros'] ?? 0), 2);
        $codformapagamento = (int) ($pagto['codformapagamento'] ?? 0);

        $dados = [
            'uuid' => $pagto['uuid'],
            'codnegocio' => $negocio->codnegocio,
            'codfilial' => $negocio->codfilial,
            'codpdv' => $negocio->codpdv,
            'meio' => static::meio($pagto['tipo'] ?? null, $codformapagamento),
            'estado' => PagamentoService::ESTADO_PENDENTE,
            'principal' => round(abs($valor) - $troco, 2),
            'juros' => $juros,
            'valortroco' => $troco ?: null,
            'parcelas' => $pagto['parcelas'] ?? null,
            'codpessoa' => $pagto['codpessoa'] ?? null,
            'bandeira' => $pagto['bandeira'] ?? null,
            'autorizacao' => $pagto['autorizacao'] ?? null,
            'codmaquineta' => $pagto['codmaquineta'] ?? null,
            'codtitulo' => $pagto['codtitulo'] ?? null,
            'cmc7' => $pagto['cmc7'] ?? null,
            'chequevencimento' => $pagto['chequevencimento'] ?? null,
            'chequecnpj' => $pagto['chequecnpj'] ?? null,
            'chequeemitente' => $pagto['chequeemitente'] ?? null,
            'codportadororigem' => null,
            'codpagamentoorigem' => null,
        ];

        // cartao manual: PDV antigo manda o serial digitado (M3)
        if ($codformapagamento == static::CODFORMAPAGAMENTO_CARTAO_MANUAL && empty($dados['codmaquineta']) && !empty($dados['codpessoa'])) {
            $dados['codmaquineta'] = static::maquinetaDoCartaoManual($pagto['serialmaquineta'] ?? null, $negocio, $dados['codpessoa']);
        }

        // valor negativo: estorno de cartao lancado na propria venda vira
        // pagamento contrario (saiu da adquirente)
        if ($valor < 0) {
            $dados['codportadororigem'] = static::portadorDaAdquirente($dados['codmaquineta'], $dados['codpessoa']);
            $dados['codpagamentoorigem'] = Pagamento::where('codnegocio', $negocio->codnegocio)
                ->whereNull('codportadororigem')
                ->whereRaw('lower(autorizacao) = lower(?)', [$dados['autorizacao'] ?? ''])
                ->where('uuid', '!=', $pagto['uuid'])
                ->orderBy('codpagamento', 'desc')
                ->value('codpagamento');
        }

        PagamentoService::preencher($pag, $dados);
        $pag->save();
        return $pag;
    }

    public static function importarPrazo(Negocio $negocio, array $pagto): array
    {
        // a mesma forma pode ter sido pagamento antes
        Pagamento::where('uuid', $pagto['uuid'])
            ->where('codnegocio', $negocio->codnegocio)
            ->where('estado', PagamentoService::ESTADO_PENDENTE)
            ->delete();
        // parcelas em aberto sao recalculadas a cada sync (vencimento a
        // partir de hoje, como o fechamento fazia)
        $negocio->NegocioParcelaS()->where('uuidforma', $pagto['uuid'])->whereNull('codtitulo')->delete();

        $juros = round((float) ($pagto['valorjuros'] ?? 0), 2);
        $valor = round((float) ($pagto['valorpagamento'] ?? 0) + $juros, 2);
        if ($valor <= 0) {
            return [];
        }
        return NegocioParcelaService::gerar(
            $negocio,
            static::CONDICAO_DA_FORMA[(int) $pagto['codformapagamento']],
            $valor,
            $juros,
            $pagto['parcelas'] ?? 1,
            $pagto['valorparcela'] ?? null,
            isset($pagto['dias']) ? (int) $pagto['dias'] : null,
            $pagto['uuid']
        );
    }

    // PDV antigo manda o serial digitado e nao o codmaquineta: serial + filial
    // vira maquineta (cria a manual se nao achar); sem serial, a do parceiro
    // se for a unica (acesso de site).
    public static function maquinetaDoCartaoManual(?string $serial, Negocio $negocio, $codpessoa): ?int
    {
        if (!empty(trim($serial ?? ''))) {
            return MaquinetaService::resolverSerial($serial, $negocio->codfilial, $codpessoa)->codmaquineta;
        }
        return MaquinetaService::unicaDoParceiro($codpessoa, $negocio->codfilial)->codmaquineta ?? null;
    }

    // portador tipo A (adquirente) da maquineta/parceiro
    public static function portadorDaAdquirente($codmaquineta, $codpessoa): int
    {
        if (!empty($codmaquineta)) {
            $codpessoa = Maquineta::where('codmaquineta', $codmaquineta)->value('codpessoa') ?? $codpessoa;
        }
        $codportador = Portador::where('tipo', Portador::TIPO_ADQUIRENTE)
            ->where('codpessoa', $codpessoa)
            ->whereNull('inativo')
            ->orderBy('codportador')
            ->value('codportador');
        if (empty($codportador)) {
            abort(422, 'Pagamento negativo sem adquirente cadastrada como portador! Exclua o pagamento e lance de novo.');
        }
        return $codportador;
    }

    // Filtro da listagem do PDV por forma antiga: codnegocio dos pagamentos
    // e parcelas que o codigo representa (subquery para whereIn)
    public static function filtroForma($query, array $codigos): void
    {
        $query->select('codnegocio')->from('tblpagamento')->whereNotNull('codnegocio')
            ->where('estado', '!=', PagamentoService::ESTADO_CANCELADO)
            ->where(function ($q) use ($codigos) {
                $q->whereRaw('false');
                foreach ($codigos as $cod) {
                    switch ((int) $cod) {
                        case static::CODFORMAPAGAMENTO_PIX_QR:
                            $q->orWhereNotNull('codpixcob');
                            break;
                        case static::CODFORMAPAGAMENTO_PAGARME:
                            $q->orWhereNotNull('codpagarmepedido');
                            break;
                        case static::CODFORMAPAGAMENTO_SAURUS:
                            $q->orWhereNotNull('codsauruspedido');
                            break;
                        case static::CODFORMAPAGAMENTO_LIO:
                            $q->orWhereNotNull('codliopedido');
                            break;
                        case static::CODFORMAPAGAMENTO_DINHEIRO:
                            $q->orWhere('meio', PagamentoService::MEIO_DINHEIRO);
                            break;
                        case static::CODFORMAPAGAMENTO_CHEQUE:
                            $q->orWhere('meio', PagamentoService::MEIO_CHEQUE);
                            break;
                        case static::CODFORMAPAGAMENTO_VALE:
                            $q->orWhere('meio', PagamentoService::MEIO_VALE);
                            break;
                        case static::CODFORMAPAGAMENTO_MERCOSPAY:
                            $q->orWhere('codportadordestino', static::CODPORTADOR_MERCOSPAY);
                            break;
                        case static::CODFORMAPAGAMENTO_CARTAO_MANUAL:
                            $q->orWhere(function ($c) {
                                $c->whereIn('meio', [PagamentoService::MEIO_CREDITO, PagamentoService::MEIO_DEBITO, PagamentoService::MEIO_OUTROS])
                                    ->whereNull('codpixcob')->whereNull('codpagarmepedido')
                                    ->whereNull('codsauruspedido')->whereNull('codliopedido')
                                    ->whereRaw('coalesce(codportadordestino, 0) <> ?', [static::CODPORTADOR_MERCOSPAY]);
                            });
                            break;
                    }
                }
            });
        $condicoes = [];
        foreach ($codigos as $cod) {
            if (isset(static::CONDICAO_DA_FORMA[(int) $cod])) {
                $condicoes[] = static::CONDICAO_DA_FORMA[(int) $cod];
            }
            if ((int) $cod == static::CODFORMAPAGAMENTO_VALE) {
                $condicoes[] = NegocioParcelaService::CONDICAO_VALE;
            }
        }
        if (!empty($condicoes)) {
            $query->union(
                \DB::table('tblnegocioparcela')->select('codnegocio')->whereIn('condicao', array_unique($condicoes))
            );
        }
    }

    // Filtro da listagem do PDV: pagamento integrado ou manual
    public static function filtroIntegracao($query, bool $integracao): void
    {
        $integrado = 'coalesce(codpixcob, codpagarmepedido, codsauruspedido, codliopedido) is not null';
        $query->select('codnegocio')->from('tblpagamento')->whereNotNull('codnegocio')
            ->where('estado', '!=', PagamentoService::ESTADO_CANCELADO)
            ->whereRaw($integracao ? $integrado : "not ({$integrado})");
        if (!$integracao) {
            $query->union(\DB::table('tblnegocioparcela')->select('codnegocio'));
        }
    }

    // ---------------------------------------------------------------
    // Pagamento / parcelas -> forma antiga (o que o PDV le hoje)
    // ---------------------------------------------------------------

    public static function codFormaPagamento(Pagamento $pag): int
    {
        if (!empty($pag->codpixcob)) {
            return static::CODFORMAPAGAMENTO_PIX_QR;
        }
        if (!empty($pag->codpagarmepedido)) {
            return static::CODFORMAPAGAMENTO_PAGARME;
        }
        if (!empty($pag->codsauruspedido)) {
            return static::CODFORMAPAGAMENTO_SAURUS;
        }
        if (!empty($pag->codliopedido)) {
            return static::CODFORMAPAGAMENTO_LIO;
        }
        switch ($pag->meio) {
            case PagamentoService::MEIO_DINHEIRO:
                return static::CODFORMAPAGAMENTO_DINHEIRO;
            case PagamentoService::MEIO_CHEQUE:
                return static::CODFORMAPAGAMENTO_CHEQUE;
            case PagamentoService::MEIO_VALE:
                return static::CODFORMAPAGAMENTO_VALE;
        }
        if ($pag->meio == PagamentoService::MEIO_OUTROS && empty($pag->codmaquineta) && $pag->codportadordestino == static::CODPORTADOR_MERCOSPAY) {
            return static::CODFORMAPAGAMENTO_MERCOSPAY;
        }
        return static::CODFORMAPAGAMENTO_CARTAO_MANUAL;
    }

    // nome da forma antiga (tblformapagamento congelada)
    public static function nomeFormaPagamento(int $codformapagamento): ?string
    {
        static $cache = [];
        if (!array_key_exists($codformapagamento, $cache)) {
            $cache[$codformapagamento] = FormaPagamento::where('codformapagamento', $codformapagamento)->value('formapagamento');
        }
        return $cache[$codformapagamento];
    }

    // Um item no formato antigo por pagamento e um por forma a prazo
    // (parcelas agrupadas pelo uuidforma). Cancelado so' aparece quando o
    // negocio inteiro esta' cancelado.
    public static function formaAntiga(Negocio $negocio): array
    {
        $ret = [];
        $cancelado = $negocio->codnegociostatus == NegocioService::STATUS_CANCELADO;
        $pags = $negocio->PagamentoS()->orderBy('codpagamento')->get();
        foreach ($pags as $pag) {
            if (!$cancelado && $pag->estado == PagamentoService::ESTADO_CANCELADO) {
                continue;
            }
            $ret[] = static::formaAntigaDoPagamento($pag);
        }
        $grupos = $negocio->NegocioParcelaS()
            ->orderBy('codnegocioparcela')
            ->get()
            ->groupBy(fn($np) => $np->uuidforma ?? $np->uuid);
        foreach ($grupos as $uuid => $parcelas) {
            $ret[] = static::formaAntigaDasParcelas($uuid, $parcelas);
        }
        return $ret;
    }

    public static function formaAntigaDoPagamento(Pagamento $pag): array
    {
        $sinal = $pag->ehSaida() ? -1 : 1;
        $cod = static::codFormaPagamento($pag);
        $ret = [
            'codnegocioformapagamento' => $pag->codpagamento,
            'codpagamento' => $pag->codpagamento,
            'codnegocio' => $pag->codnegocio,
            'codformapagamento' => $cod,
            'formapagamento' => static::nomeFormaPagamento($cod),
            'uuid' => $pag->uuid,
            'valorpagamento' => round($sinal * ($pag->principal + ($pag->valortroco ?? 0)), 2),
            'valorjuros' => $pag->juros ?: null,
            'valortroco' => $pag->valortroco,
            'valortotal' => round($sinal * ($pag->total + ($pag->valortroco ?? 0)), 2),
            'avista' => true,
            'tipo' => $pag->meio,
            'integracao' => $pag->ehIntegrado(),
            'codpessoa' => $pag->codpessoa,
            'bandeira' => $pag->bandeira,
            'autorizacao' => $pag->autorizacao,
            'parcelas' => $pag->parcelas,
            'valorparcela' => null,
            'dias' => null,
            'codtitulo' => $pag->codtitulo,
            'codmaquineta' => $pag->codmaquineta,
            'serialmaquineta' => $pag->Maquineta->serial ?? null,
            'codpixcob' => $pag->codpixcob,
            'codpagarmepedido' => $pag->codpagarmepedido,
            'codsauruspedido' => $pag->codsauruspedido,
            'codliopedido' => $pag->codliopedido,
            'cmc7' => $pag->cmc7,
            'chequevencimento' => $pag->chequevencimento ? $pag->chequevencimento->format('Y-m-d') : null,
            'chequecnpj' => $pag->chequecnpj,
            'chequeemitente' => $pag->chequeemitente,
            'estado' => $pag->estado,
            'criacao' => $pag->criacao,
            'codusuariocriacao' => $pag->codusuariocriacao,
            'alteracao' => $pag->alteracao,
            'codusuarioalteracao' => $pag->codusuarioalteracao,
            'parceiro' => $pag->Pessoa->fantasia ?? null,
            'maquineta' => $pag->Maquineta->apelido ?? null,
            // logo do banco na listagem do PDV (public/bancos/{codbanco}.svg)
            'codbanco' => $pag->PixCob->Portador->codbanco ?? null,
            'nomebandeira' => static::BANDEIRAS[$pag->bandeira] ?? null,
            'nometipo' => static::TIPOS[$pag->meio] ?? null,
        ];
        // vale compras usado no pagamento: card do Contra Vale (saldo atual do titulo)
        if (!empty($pag->codtitulo) && $pag->Titulo->codtipotitulo == TituloService::TIPO_VALE) {
            $ret['valenumero'] = $pag->Titulo->numero;
            $ret['valefavorecido'] = $pag->Titulo->Pessoa->fantasia;
            $ret['valesaldo'] = round(-$pag->Titulo->saldo, 2);
        }
        return $ret;
    }

    public static function formaAntigaDasParcelas(string $uuid, $parcelas): array
    {
        $primeira = $parcelas->first();
        $condicao = $primeira->condicao;
        $cod = static::FORMA_DA_CONDICAO[$condicao];
        $tipo = static::TIPO_DA_CONDICAO[$condicao];
        $valortotal = round($parcelas->sum('valor'), 2);
        $juros = round($parcelas->sum('juros'), 2);
        $base = $primeira->criacao ? $primeira->criacao->copy()->startOfDay() : Carbon::today();
        $dias = (int) $base->diffInDays($primeira->vencimento, false);
        if ($parcelas->count() > 1 || $condicao == NegocioParcelaService::CONDICAO_FECHAMENTO) {
            $dias = 30;
        }
        return [
            'codnegocioformapagamento' => -$primeira->codnegocioparcela,
            'codnegocio' => $primeira->codnegocio,
            'codformapagamento' => $cod,
            'formapagamento' => static::nomeFormaPagamento($cod),
            'condicao' => $condicao,
            'uuid' => $uuid,
            'valorpagamento' => round($valortotal - $juros, 2),
            'valorjuros' => $juros ?: null,
            'valortroco' => null,
            'valortotal' => $valortotal,
            'avista' => false,
            'tipo' => $tipo,
            'integracao' => false,
            'codpessoa' => null,
            'bandeira' => null,
            'autorizacao' => null,
            'parcelas' => $parcelas->count(),
            'valorparcela' => $primeira->valor,
            'dias' => max(0, $dias),
            'codtitulo' => null,
            'codmaquineta' => null,
            'serialmaquineta' => null,
            'codpixcob' => null,
            'codpagarmepedido' => null,
            'codsauruspedido' => null,
            'codliopedido' => null,
            'cmc7' => null,
            'chequevencimento' => null,
            'chequecnpj' => null,
            'chequeemitente' => null,
            'criacao' => $primeira->criacao,
            'codusuariocriacao' => $primeira->codusuariocriacao,
            'alteracao' => $primeira->alteracao,
            'codusuarioalteracao' => $primeira->codusuarioalteracao,
            'parceiro' => null,
            'maquineta' => null,
            'codbanco' => null,
            'nomebandeira' => null,
            'nometipo' => static::TIPOS[$tipo] ?? null,
        ];
    }
}
