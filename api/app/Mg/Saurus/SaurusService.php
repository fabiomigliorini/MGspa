<?php

namespace Mg\Saurus;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Mg\Filial\Filial;
use Mg\Pagamento\Pagamento;
use Mg\Pagamento\PagamentoService;
use Mg\Negocio\Negocio;
use Mg\Negocio\NegocioService;
use Mg\Saurus\S2Pay\ApiService;
use Mg\Maquineta\Maquineta;
use Ramsey\Uuid\Uuid;

class SaurusService
{
    // maquineta SafraPay sem aparelho ativo: nunca pareada, ou o aparelho foi para outro PDV Saurus
    const SEM_APARELHO = 'Maquineta sem aparelho pareado: pareie no contas.';

    const STATUS_DESCRIPTION = [
        0 => 'pending',
        1 => 'processing',
        2 => 'approved',
        3 => 'canceled',
    ];


    const TYPE_DESCRIPTION = [
        1 => 'cash',
        2 => 'check',
        3 => 'credit',
        4 => 'debit',
        5 => 'credit_own',
        10 => 'food_voucher',
        11 => 'meal_voucher',
        12 => 'gift_voucher',
        13 => 'fuel_voucher',
        98 => 'bank_slip',
        99 => 'others',
    ];

    // O PDV Saurus da maquineta: registra (ou renova) na API e devolve o PDV com a chavepublica
    // que vira o QR Code lido pelo aparelho. Sem PDV Saurus, cria um com o proximo numero da
    // filial da maquineta (TASK-264).
    public static function registrarPdv(Maquineta $maquineta): SaurusPdv
    {
        $pdvSaurus = $maquineta->SaurusPdv;
        if ($pdvSaurus && $pdvSaurus->vencimento > now()) {
            return $pdvSaurus;
        }

        $pessoa = Filial::findOrFail($maquineta->codfilial)->Pessoa;

        if ($pdvSaurus) {
            $numero = $pdvSaurus->numero;
            $pdv_uuid = $pdvSaurus->id;
        } else {
            $ultimo = SaurusPdv::join('tblmaquineta', 'tblmaquineta.codsauruspdv', '=', 'tblsauruspdv.codsauruspdv')
                ->where('tblmaquineta.codfilial', $maquineta->codfilial)
                ->max('tblsauruspdv.numero');
            $numero = ($ultimo ?? 0) + 1;
            $pdv_uuid = (string) Uuid::uuid4();
        }

        $response = ApiService::functionPdvRegistrar($pdv_uuid, $pessoa, $numero);

        return SaurusPdv::updateOrCreate(
            [
                'id' => $pdv_uuid,
            ],
            [
                'autorizacao' => $response->autorizacao->response->chavePublica,
                'vencimento' => Carbon::parse($response->autorizacao->response->vencimento)->subHour(1)->subMinutes(10),
                'chavepublica' => $response->pdv->response->chavePublica,
                'contratoid' => $response->pdv->response->contratoId,
                'numero' => $numero,
            ]
        );
    }

    // Depois que o aparelho leu o QR Code: busca na API o pinpad pareado ao PDV Saurus e grava.
    // Ele fica ativo; os outros aparelhos deste PDV Saurus e o mesmo aparelho em outro PDV Saurus
    // ficam inativos (substituidos: o aparelho so' e' de um). Null enquanto o aparelho nao leu.
    public static function verificarLeitura(SaurusPdv $pdvSaurus): ?SaurusPinPad
    {
        $response = ApiService::functionPdvVerificar($pdvSaurus->autorizacao);

        if (str_contains($response->retTexto, 'Chave de Autorização está Inválida')) {
            $autorizacao = ApiService::functionAutorizacao($pdvSaurus->id, static::cnpj($pdvSaurus));
            $pdvSaurus->autorizacao = $autorizacao->response->chavePublica;
            $pdvSaurus->vencimento = Carbon::parse($autorizacao->response->vencimento)->subHour(1)->subMinutes(10);
            $pdvSaurus->save();
            $response = ApiService::functionPdvVerificar($pdvSaurus->autorizacao);
        }

        if (empty($response->response->pinPads)) {
            return null;
        }

        return static::gravarLeitura($pdvSaurus, $response->response->pinPads[0]);
    }

    // o aparelho $id leu o QR Code: fica ativo neste PDV Saurus; os outros deste PDV Saurus e ele
    // mesmo em outro PDV Saurus ficam inativos
    public static function gravarLeitura(SaurusPdv $pdvSaurus, string $id): SaurusPinPad
    {
        // aparelho vindo de outro PDV Saurus ganha registro neste e nao arrasta a maquineta de la'
        $pin = SaurusPinPad::firstOrNew([
            'id' => $id,
            'codsauruspdv' => $pdvSaurus->codsauruspdv,
        ]);
        $pin->inativo = null;
        $pin->save();

        SaurusPinPad::where('codsauruspinpad', '!=', $pin->codsauruspinpad)
            ->where(function ($q) use ($pdvSaurus, $id) {
                $q->where('codsauruspdv', $pdvSaurus->codsauruspdv)->orWhere('id', $id);
            })
            ->whereNull('inativo')
            ->update(['inativo' => Carbon::now()]);

        return $pin;
    }

    // o aparelho que recebe a cobranca do PDV Saurus; null se nenhum aparelho foi pareado (ou o
    // dele foi pareado em outro PDV Saurus)
    public static function pinpadAtivo(?SaurusPdv $pdvSaurus): ?SaurusPinPad
    {
        if (!$pdvSaurus) {
            return null;
        }
        return $pdvSaurus->SaurusPinPadS()->whereNull('inativo')
            ->orderBy('codsauruspinpad', 'desc')->first();
    }

    // aparelho que cobrou (idPinPad = uuid do aparelho, coluna id), no PDV Saurus do pedido. Nao
    // achou (leitura ainda nao confirmada): mantem o ja' gravado no cartao, senao o ativo
    public static function pinpadQueCobrou(SaurusPedido $ped, SaurusPdv $pdv, ?string $idPinPad): ?int
    {
        return SaurusPinPad::where('id', $idPinPad)
            ->where('codsauruspdv', $ped->codsauruspdv)
            ->value('codsauruspinpad')
            ?? SaurusPagamento::where('codsauruspedido', $ped->codsauruspedido)->value('codsauruspinpad')
            ?? static::pinpadAtivo($pdv)?->codsauruspinpad;
    }

    // CNPJ da filial da maquineta do PDV Saurus: e' o que o autoriza na Saurus
    private static function cnpj(SaurusPdv $pdvSaurus): string
    {
        $maquineta = $pdvSaurus->Maquineta;
        if (!$maquineta) {
            abort(422, 'PDV Saurus sem maquineta!');
        }
        return Filial::findOrFail($maquineta->codfilial)->Pessoa->cnpj;
    }

    // Cancelar e' so' na nossa tabela: a Saurus nao fica sabendo e a maquininha
    // continua aceitando o pagamento. Por isso antes pergunta 'a Saurus: o que
    // ela diz pago vira pagamento (consultarPedido) e fica; so' o resto e'
    // cancelado. A Saurus fora do ar nao segura a venda: cancela assim mesmo e
    // registra no log (TASK-233)
    private static function cancelarSeNaoPago($peds)
    {
        foreach ($peds as $ped) {
            try {
                $ped = static::consultarPedido($ped);
            } catch (\Throwable $th) {
                Log::error("Erro ao consultar pedido Saurus {$ped->codsauruspedido} antes de cancelar: {$th->getMessage()}");
            }
            if ($ped->status != 0) {
                continue;
            }
            $ped->status = 3;
            $ped->save();
        }
    }

    // fechar ou cancelar a venda cancela as cobrancas dela que ficaram abertas
    public static function cancelarPedidosAbertosNegocio(Negocio $negocio)
    {
        static::cancelarSeNaoPago($negocio->SaurusPedidoS()->where('status', 0)->get());
    }

    public static function criarPedido(
        $idpedido,
        $codsauruspdv,
        $codnegocio,
        $valor,
        $valorjuros,
        $valortotal,
        $valorparcela,
        $idfaturapag,
        $modpagamento,
        $parcelas,
        $status,
        $codusuariocriacao,
        $criacao,
        $pdv,
        $pos
    ) {
        // chave que nao renova e' erro na tela antes de gravar: nao fica pedido orfao
        static::autorizar($pdv);

        $ped = new SaurusPedido();
        $ped->idpedido = $idpedido;
        $ped->codsauruspdv = $codsauruspdv;
        $ped->codnegocio = $codnegocio;
        $ped->valor = $valor;
        $ped->valorjuros = $valorjuros;
        $ped->valortotal = $valortotal;
        $ped->valorparcela = $valorparcela;
        $ped->idfaturapag = $idfaturapag;
        $ped->modpagamento = $modpagamento;
        $ped->parcelas = $parcelas;
        $ped->status = $status;
        $ped->codusuariocriacao = $codusuariocriacao;
        $ped->criacao = $criacao;
        $ped->save();

        // falha no envio nao estoura: no timeout a cobranca costuma ter chegado e
        // ser paga (a consulta e' pelo idfaturapag). O id nulo e' o aviso, e o
        // dialog oferece Reenviar (TASK-118)
        try {
            static::enviarPedido($ped, $pdv, $pos);
        } catch (\Throwable $th) {
        }

        return $ped;
    }

    // Manda o pedido para a maquininha. Sem id na resposta a Saurus nao confirmou
    // o recebimento. Reenviar nao duplica: substituir=1 com o mesmo IdPedido (TASK-118)
    public static function enviarPedido(SaurusPedido $ped, SaurusPdv $pdv, SaurusPinPad $pos)
    {
        static::autorizar($pdv);

        try {
            $response = ApiService::functionPedidoCriar($ped, $pdv, $pos);
        } catch (\Throwable $th) {
            abort(422, 'A SafraPay não respondeu. Tente reenviar.');
        }

        if (empty($response->response->id)) {
            abort(422, 'A SafraPay não aceitou a cobrança: ' . ($response->retTexto ?? 'sem resposta'));
        }

        $ped->id = $response->response->id;
        $ped->save();

        return $ped;
    }

    // a chave de autorizacao do PDV Saurus vence: renova antes de falar com a Saurus
    private static function autorizar(SaurusPdv $pdv)
    {
        if ($pdv->vencimento >= now()) {
            return;
        }

        $responseAutorizacao = ApiService::functionAutorizacao($pdv->id, static::cnpj($pdv));

        $pdv->autorizacao = $responseAutorizacao->response->chavePublica;
        $pdv->vencimento = Carbon::parse($responseAutorizacao->response->vencimento)->subHour(1)->subMinutes(10);

        $pdv->save();
    }

    public static function consultarPedido($ped)
    {

        $pdv = SaurusPdv::findOrFail($ped->codsauruspdv);

        static::autorizar($pdv);

        $response = ApiService::functionPagamentoConsultar($ped->idfaturapag, $pdv->autorizacao);

        if($response->response == null || $response->response == 'null') {
            return $ped;
        }

        if($response->response->indStatus == 1){
            $ped->status = 2;
            $ped->save();
        }

        $bandeira = self::buscaOuCriaBandeira($response->response->bandeira);

        $codsauruspinpad = static::pinpadQueCobrou($ped, $pdv, $response->response->idPinPad ?? null);

        $saurusPagamento = SaurusPagamento::updateOrCreate(
            [
                'codsauruspedido' => $ped->codsauruspedido,
            ],
            [
                'codsauruspinpad' => $codsauruspinpad,
                'id' => $response->response->id,
                'nsu' => $response->response->codNSU,
                'autorizacao' => $response->response->codAut,
                'controle' => $response->response->codControle,
                // a Saurus manda a hora em UTC (gravada como veio, ficava 4h adiantada) (TASK-228)
                'transacao' => $response->response->dTransacao
                    ? Carbon::parse($response->response->dTransacao, 'UTC')->setTimezone(config('app.timezone'))
                    : null,
                'status' => $response->response->indStatus,
                'cartao' => $response->response->numCartao,
                'valor' => $response->response->valorPagamento,
                'valorjuros' => $ped->valorjuros, 
                'valortotal' => $ped->valortotal,
                'valorparcela' => $ped->valorparcela,
                'modpagamento' => $response->response->modPagamento,
                'codsaurusbandeira' => $bandeira->codsaurusbandeira,
                'parcelas' => $response->response->qtdeParcelas,
                'codusuariocriacao' => auth()->user()->codusuario,
                'criacao' => now(),

            ]
        );

        $ped->fresh();

        if($ped->status == 2) {
            self::vincularPagamento($ped);
        }

        return $ped;
        
    }

    public static function buscaOuCriaBandeira(string $bandeira)
    {
        $tband = null;

        switch(strtoupper($bandeira)) {
            case 'VISA':
                $tband = 1;
                break;
            case 'MASTERCARD':
                $tband = 2;
                break;
            case 'AMERICAN EXPRESS':
                $tband = 3;
                break;
            case 'SOROCRED':
                $tband = 4;
                break;
            case 'DINERS CLUB':
                $tband = 5;
                break;
            case 'ELO':
                $tband = 6;
                break;
            case 'HIPERCARD':
                $tband = 7;
                break;
            case 'AURA':
                $tband = 8;
                break;
            case 'CABAL':
                $tband = 9;
                break;
            default:
                $tband = 99;
                break;
        }
            
            
        $reg = SaurusBandeira::firstOrNew([
            'bandeira' => $bandeira
        ]);

        if (empty($reg->codsaurusbandeira)) {
            $reg->tband = $tband;
            $reg->codusuariocriacao = auth()->user()->codusuario;
            $reg->criacao = now();
            $reg->save();
        }

        return $reg;
    }

    // Pedido pago da venda aberta vira o pagamento efetivado da venda (M4
    // doc-3). Fora de uma venda (baixa de titulos, adiantamento) nao vira
    // pagamento sozinho: a tela que cobrou grava o pagamento ao finalizar
    // (pagar); fechada antes, o cartao se lanca 'a mao (TASK-220). Venda
    // cancelada: o pagamento foi cancelado junto e nao volta.
    public static function vincularPagamento(SaurusPedido $ped)
    {
        if (empty($ped->codnegocio) || $ped->Negocio->codnegociostatus == NegocioService::STATUS_CANCELADO) {
            return true;
        }
        $cartao = static::cartaoDoPedido($ped);

        // pagamento efetivado: a maquineta ja' confirmou (M4 doc-3). Um por
        // pedido; mesma autorizacao no negocio e' o mesmo pagamento.
        $pag = Pagamento::where('codsauruspedido', $ped->codsauruspedido)->first();
        if (!$pag && !empty($cartao['autorizacao'])) {
            $pag = Pagamento::where('codnegocio', $ped->codnegocio)
                ->where('autorizacao', $cartao['autorizacao'])
                ->where('codpessoa', config('mg.codpessoa_safra'))
                ->first();
        }
        $pag = $pag ?? new Pagamento();
        PagamentoService::preencher($pag, array_merge($cartao, [
            'codnegocio' => $ped->codnegocio,
            'codfilial' => $ped->Negocio->codfilial ?? $ped->SaurusPdv->Maquineta->codfilial ?? null,
            'codpdv' => $ped->Negocio->codpdv ?? null,
            'codpessoa' => config('mg.codpessoa_safra'),
        ]));
        $pag->save();
        if ($pag->estado != PagamentoService::ESTADO_CANCELADO) {
            PagamentoService::efetivar($pag);
        }
        NegocioService::fecharSePago($ped->Negocio);

        return true;
    }

    // Cartao cobrado na baixa de titulos ou no adiantamento: o pagamento
    // nasce quando a tela grava, um por pedido (TASK-220). $campos: codpessoa,
    // observacoes, referente, codpdv, codfilial.
    public static function pagar(int $codsauruspedido, float $valor, array $campos): Pagamento
    {
        $ped = SaurusPedido::lockForUpdate()->findOrFail($codsauruspedido);
        if ($ped->status != 2) {
            abort(422, 'O pedido da SafraPay não está pago!');
        }
        if (!empty($ped->codnegocio)) {
            abort(422, "O pedido da SafraPay é da venda #{$ped->codnegocio}!");
        }
        $usado = Pagamento::where('codsauruspedido', $ped->codsauruspedido)
            ->where('estado', '!=', PagamentoService::ESTADO_CANCELADO)
            ->value('codpagamento');
        if ($usado) {
            abort(422, "O cartão deste pedido já está no pagamento #{$usado}!");
        }
        $cartao = static::cartaoDoPedido($ped);
        if (abs($cartao['principal'] - round($valor, 2)) > 0.005) {
            abort(422, 'O cartão é de R$ ' . number_format($cartao['principal'], 2, ',', '.')
                . ' e a baixa é de R$ ' . number_format($valor, 2, ',', '.') . '!');
        }
        // a hora em que o cartao passou
        $passou = $ped->SaurusPagamentoS->max('transacao');
        $pag = PagamentoService::criar(array_merge($cartao, [
            'codnegocio' => null,
            'codfilial' => $campos['codfilial'] ?? $ped->SaurusPdv->Maquineta->codfilial ?? null,
            'codpdv' => $campos['codpdv'] ?? null,
            'estado' => PagamentoService::ESTADO_PENDENTE,
            'transacao' => $passou ? Carbon::parse($passou) : Carbon::now(),
            'codpessoa' => $campos['codpessoa'] ?? null,
            'observacoes' => $campos['observacoes'] ?? null,
            'referente' => $campos['referente'] ?? null,
        ]));
        PagamentoService::efetivar($pag);
        return $pag;
    }

    // O cartao do pedido: meio, principal, juros, autorizacao, NSU,
    // parcelas, bandeira e maquineta (a do PDV Saurus do pedido)
    private static function cartaoDoPedido(SaurusPedido $ped): array
    {
        $tipo = 99; //Outros
        $autorizacao = null;
        $nsu = null;
        $bandeira = null;
        foreach ($ped->SaurusPagamentoS as $pag) {
            $tipo = $pag->modpagamento;

            $autorizacao = $pag->autorizacao;
            $nsu = $pag->nsu;
            $bandeira = static::buscaOuCriaBandeira(
                $pag->SaurusBandeira->bandeira
            );
        }
        $maquineta = static::maquinetaDoPedido($ped);
        return [
            'codsauruspedido' => $ped->codsauruspedido,
            // debito ou credito, como a Safra mostra; voucher e o resto, Outros,
            // como na Stone (o vale-presente 12 nao e' o nosso Vale Compras) (TASK-228)
            'meio' => in_array((int) $tipo, PagamentoService::MEIOS_CARTAO) ? (int) $tipo : PagamentoService::MEIO_OUTROS,
            'principal' => $ped->valor,
            'juros' => $ped->valorjuros ?? 0,
            'valortroco' => null,
            'autorizacao' => $autorizacao,
            // NSU e parcelas, como no relatorio da maquineta (M9.8)
            'nsu' => $nsu,
            'parcelas' => $ped->parcelas,
            'bandeira' => $bandeira->tband ?? null,
            'codmaquineta' => $maquineta->codmaquineta ?? null,
        ];
    }

    // a maquineta do pedido: a do PDV Saurus que cobrou
    public static function maquinetaDoPedido(SaurusPedido $ped): ?Maquineta
    {
        return $ped->SaurusPdv->Maquineta ?? null;
    }

    // Botao Cancelar: pergunta 'a Saurus antes e recusa se ja' foi pago (a
    // maquininha aceita pagar depois do cancelamento). Saurus fora do ar
    // recusa tambem: o caixa tenta de novo (TASK-233)
    public static function cancelarPedido(SaurusPedido $ped)
    {
        if ($ped->status == 0) {
            try {
                $ped = static::consultarPedido($ped);
            } catch (\Throwable $th) {
                Log::error("Erro ao consultar pedido Saurus {$ped->codsauruspedido} antes de cancelar: {$th->getMessage()}");
                abort(422, 'Não foi possível consultar a SafraPay antes de cancelar. Tente de novo.');
            }
        }
        if ($ped->status == 2) {
            abort(422, 'O pedido já foi pago na maquininha e não pode ser cancelado!');
        }
        if ($ped->status != 0) {
            abort(422, "Pedido não consta como pendente! Status {$ped->status}!");
        }

        $ped->update([
            'status' => 3
        ]);

        return $ped->fresh();
    }
}