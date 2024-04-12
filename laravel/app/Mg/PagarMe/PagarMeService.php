<?php

namespace Mg\PagarMe;

use Illuminate\Support\Facades\Storage;

use Mg\Filial\Filial;
use Mg\Negocio\Negocio;
use Mg\Pessoa\Pessoa;
use Mg\Negocio\NegocioFormaPagamento;
use Mg\Negocio\NegocioService;
use Mg\FormaPagamento\FormaPagamento;

use Carbon\Carbon;

class PagarMeService
{

    const TYPE_DESCRIPTION = [
        1 => 'debit',
        2 => 'credit',
        3 => 'voucher',
        4 => 'prepaid',
    ];

    const TYPE_NUMBER = [
        'debit' => 1,
        'credit' => 2,
        'voucher' => 3,
        'prepaid' => 4,
    ];

    const STATUS_DESCRIPTION = [
        1 => 'pending',
        2 => 'paid',
        3 => 'canceled',
        4 => 'failed'
    ];

    const STATUS_NUMBER = [
        'pending' => 1,
        'paid' => 2,
        'canceled' => 3,
        'failed' => 4
    ];

    public static function buscaFilial(string $id)
    {
        $reg = Filial::where('pagarmeid', $id)->first();
        return $reg;
    }

    // Cadastra se POS novo
    public static function buscaOuCriaPos(int $codfilial, string $serial)
    {
        $reg = PagarMePos::firstOrNew([
            'codfilial' => $codfilial,
            'serial' => $serial
        ]);
        if (empty($reg->codpagarmepos)) {
            $reg->apelido = $serial;
            $reg->codfilial = $codfilial;
            $reg->save();
        }
        return $reg;
    }

    // Cadastra se Bandeira nova
    public static function buscaOuCriaBandeira(string $scheme)
    {
        $reg = PagarMeBandeira::firstOrNew([
            'scheme' => $scheme
        ]);
        if (empty($reg->codpagarmebandeira)) {
            $reg->bandeira = $scheme;
            $reg->save();
        }
        return $reg;
    }

    // Altera ou Cadastra pedido
    public static function alteraOuCriaPedido(
        int $codfilial,
        string $idpedido,
        int $status,
        ?int $codnegocio,
        ?int $codpdv,
        ?int $codpagarmepos,
        ?int $codpessoa,
        ?string $descricao,
        bool $fechado,
        ?bool $jurosloja,
        ?int $parcelas,
        int $tipo,
        ?float $valor,
        ?float $valorjuros,
        float $valortotal,
        ?float $valorparcela,
        ?float $valorpago,
        ?float $valorcancelado
    ) {
        $reg = PagarMePedido::firstOrNew([
            'codfilial' => $codfilial,
            'idpedido' => $idpedido,
        ]);
        $reg->status = $status;
        if (!empty($codnegocio)) {
            $reg->codnegocio = $codnegocio;
        }
        if (!empty($codpdv)) {
            $reg->codpdv = $codpdv;
        }
        $reg->codpagarmepos = $codpagarmepos;
        if (!empty($codpessoa)) {
            $reg->codpessoa = $codpessoa;
        }
        if (!empty($descricao)) {
            $reg->descricao = $descricao;
        }
        $reg->fechado = $fechado;
        if (!empty($jurosloja)) {
            $reg->jurosloja = $jurosloja;
        }
        if (!empty($parcelas)) {
            $reg->parcelas = $parcelas;
        }
        $reg->tipo = $tipo;
        if (!empty($valor)) {
            $reg->valor = $valor;
        }
        if (empty($reg->valor)) {
            $reg->valor = $valortotal - $valorjuros;
        }
        if (!empty($valorjuros)) {
            $reg->valorjuros = $valorjuros;
        }
        $reg->valortotal = $valortotal;
        if (!empty($valorparcela)) {
            $reg->valorparcela = $valorparcela;
        }
        if (!empty($valorpago)) {
            $reg->valorpago = $valorpago;
        }
        if (!empty($valorcancelado)) {
            $reg->valorcancelado = $valorcancelado;
        }
        $reg->valorpagoliquido = $reg->valorpago - $reg->valorcancelado;
        $reg->save();
        return $reg;
    }

    // Confere Totais do pedido
    // Vale o maior entre o que veio no objeto order do WebHook e a soma dos PagarMePagamentoS
    // E executado pra corrigir BUG quando webhook do cancelamento chega antes do webhook do pagamento
    public static function confereTotaisPedido(PagarMePedido $ped)
    {
        $ped = $ped->fresh();
        $pag = $ped->PagarMePagamentoS()->sum('valorpagamento');
        if ($pag > $ped->valorpago) {
            $ped->valorpago = $pag;
            $ped->valorpagoliquido = $ped->valorpago - $ped->valorcancelado;
        }
        $canc = $ped->PagarMePagamentoS()->sum('valorcancelamento');
        if ($canc > $ped->valorcancelado) {
            $ped->valorcancelado = $canc;
            $ped->valorpagoliquido = $ped->valorpago - $ped->valorcancelado;
        }
        if ($ped->isDirty()) {
            $ped->save();
        }
        return true;
    }

    public static function criarPedido(
        ?int $codfilial,
        int $codpagarmepos,
        int $tipo,
        float $valor,
        float $valorjuros,
        float $valortotal,
        float $valorparcela,
        int $parcelas,
        bool $jurosloja,
        ?string $descricao,
        ?int $codnegocio,
        ?int $codpdv,
        ?int $codpessoa
    ) {

        // Traz Pessoa e Filial do Negocio
        if (!empty($codnegocio)) {
            $neg = Negocio::findOrFail($codnegocio);
            $codpessoa = $neg->codpessoa;
            $codfilial = $neg->codfilial;
            if (empty($descricao)) {
                $descricao = 'Negocio ' . formataCodigo($codnegocio);
            }
        }

        // Se nao tiver pessoa, usa o COnsumidor
        if (empty($codpessoa)) {
            $codpessoa = 1;
        }
        $pes = Pessoa::findOrFail($codpessoa);

        // Monta uma descricao caso seja branca
        if (empty($descricao)) {
            $descricao = 'Valor Avulso';
        }

        // Busca POS e Filial
        $pos = PagarMePos::findOrFail($codpagarmepos);
        if (!empty($pos->codfilial)) {
            $codfilial = $pos->codfilial;
        }

        // Pega Secret da Filial
        $fil = Filial::findOrFail($codfilial);
        $api = new PagarMeApi($fil->pagarmesk);

        $ret = $api->postOrders(
            $pes->pessoa,
            $pes->email ?? 'nfe@mgpapelaria.com.br',
            $valortotal,
            $descricao,
            1,
            false,
            true,
            $pos->serial,
            static::TYPE_DESCRIPTION[$tipo],
            $parcelas,
            $jurosloja ? 'merchant' : 'issuer'
        );

        $ped = static::alteraOuCriaPedido(
            $codfilial,
            $api->response->id,
            static::STATUS_NUMBER[$api->response->status],
            $codnegocio,
            $codpdv,
            $codpagarmepos,
            $codpessoa,
            $descricao,
            $api->response->closed,
            $jurosloja,
            $parcelas,
            $tipo,
            $valor,
            $valorjuros,
            $valortotal,
            $valorparcela,
            0,
            0
        );

        return $ped;
    }

    public static function cancelarPedido(PagarmePedido $ped)
    {
        if ($ped->status != 1) {
            throw new \Exception("Pedido não consta como pendente! Status {$ped->status}!", 1);
        }

        $api = new PagarMeApi($ped->Filial->pagarmesk);

        // Opcoes Disponiveis: paid, canceled ou failed.
        if (!$api->patchOrdersClosed($ped->idpedido, 'canceled')) {
            return false;
        }

        $ret = $ped->update([
            'fechado' => $api->response->closed,
            'status' => static::STATUS_NUMBER[$api->response->status]
        ]);

        return $ped->fresh();
    }

    public static function cancelarPedidosAbertosPos(int $codpagarmepos)
    {
        $peds = PagarMePedido::where([
            'codpagarmepos' => $codpagarmepos,
            'status' => 1,
        ])->get();
        foreach ($peds as $ped) {
            try {
                static::cancelarPedido($ped);
            } catch (\Exception $e) {
                static::consultarPedido($ped);
            }
        }
        return true;
    }

    public static function fecharPedidoSePago(PagarmePedido $ped)
    {

        if ($ped->valorpagoliquido < $ped->valor) {
            return false;
        }

        if ($ped->fechado) {
            return true;
        }

        $api = new PagarMeApi($ped->Filial->pagarmesk);

        // tenta marcar "paid"
        // trata o erro, pq se webhook vier fora de ordem
        // api retorna erro: 404: This order is closed.
        try {
            // Opcoes Disponiveis: paid, canceled ou failed.
            if (!$api->patchOrdersClosed($ped->idpedido, 'paid')) {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }

        $ret = $ped->update([
            'fechado' => $api->response->closed,
            'status' => static::STATUS_NUMBER[$api->response->status]
        ]);

        return $ped->fresh();
    }


    public static function vincularNegocioFormaPagamento(PagarMePedido $ped)
    {
        if (empty($ped->codnegocio)) {
            return false;
        }

        if ($ped->valorpagoliquido <= 0) {
            NegocioFormaPagamento::where([
                'codpagarmepedido' => $ped->codpagarmepedido,
            ])->delete();
            return true;
        }

        $fp = FormaPagamento::firstOrNew([
            'stone' => true,
            'integracao' => true
        ]);

        if (!$fp->exists) {
            $fp->formapagamento = 'Stone PagarMe';
            $fp->avista = true;
            $fp->integracao = true;
            $fp->save();
        }

        $tipo = 99; //Outros
        $autorizacao = null;
        $bandeira = null;
        foreach ($ped->PagarMePagamentoS as $pag) {
            if ($pag->valorcancelamento) {
                continue;
            }
            switch ($pag->tipo) {
                case 1: //debit
                    $tipo = 4; //Cartão de Débito
                    break;
                case 2: //credit
                    $tipo = 3; //Cartão de Crédito
                    break;
                case 3: //voucher
                    $tipo = 3; //Cartão de Crédito
                    break;
                case 4: //prepaid
                    $tipo = 3; //Cartão de Crédito
                    break;
            }
            $autorizacao = $pag->autorizacao;
            $bandeira = static::converteBandeiraPagarMeParaBandeiraNfe(
                $pag->PagarMeBandeira->bandeira
            );
        }

        NegocioFormaPagamento::updateOrCreate(
            [
                'codnegocio' => $ped->codnegocio,
                'autorizacao' => $autorizacao,
                'codpessoa' => env('PAGAR_ME_CODPESSOA')
            ],
            [
                'codpagarmepedido' => $ped->codpagarmepedido,
                'codformapagamento' => $fp->codformapagamento,
                'avista' => true,
                'valorpagamento' => $ped->valorpagoliquido,
                'valorjuros' => $ped->valorjuros,
                'valortotal' => $ped->valorpagoliquido + $ped->valorjuros,
                'valortroco' => null,
                'tipo' => $tipo,
                'bandeira' => $bandeira,
                'integracao' => true
            ]
        );

        NegocioService::fecharSePago($ped->Negocio);

        return true;
    }

    public static function converteBandeiraPagarMeParaBandeiraNfe($bandeiraPagarMe)
    {
        switch (strtoupper($bandeiraPagarMe)) {
            case 'VISA':
                return 1; // Visa
            case 'MASTERCARD':
                return 2; // Mastercard
            case 'AMERICANEXPRESS':
                return 3; // American Express
            case 'SOROCRED':
                return 4; // Sorocred
            case 'DINERSCLUB':
                return 5; // Diners Club
            case 'ELO':
                return 6; // Elo
            case 'HIPERCARD':
                return 7; // Hipercard
            case 'AURA':
                return 8; // Aura
            case 'CABAL':
                return 9; // Cabal
            case 'TICKET':
            default:
                return 99; //Outros
        }
    }

    public static function alteraOuCriaPagamento(
        string $idtransacao,
        int $codfilial,
        ?string $codpagarmepos,
        ?string $bandeira,
        ?bool $jurosloja,
        ?int $parcelas,
        ?int $tipo,
        ?int $codpagarmepedido,
        ?string $autorizacao,
        ?string $identificador,
        ?string $nsu,
        ?string $nome,
        ?string $transacao,
        ?string $status,
        ?float $valor
    ) {

        // Bandeira
        $codpagarmebandeira = null;
        if (!empty($bandeira)) {
            $band = PagarMeService::buscaOuCriaBandeira($bandeira);
            $codpagarmebandeira = $band->codpagarmebandeira;
        }

        // data da transacao - covnerte Timezone de UTC pra America/Cuiaba
        $transacao = Carbon::parse($transacao);
        $transacao->setTimezone(config('app.timezone'));

        // decide se é pagamento ou cancelamento pelo status da transacao
        $valorpagamento = null;
        $valorcancelamento = null;
        switch (strtolower($status)) {
            case 'paid':
                $valorpagamento = $valor;
                break;

            case 'canceled':
                $valorcancelamento = $valor;
                break;
        }

        // Busca na filial transacao com aquele id
        $pp = PagarMePagamento::updateOrCreate([
            'idtransacao' => $idtransacao,
            'codfilial' => $codfilial,
        ], [
            'codpagarmebandeira' => $codpagarmebandeira,
            'codpagarmepos' => $codpagarmepos,
            'jurosloja' => $jurosloja,
            'parcelas' => $parcelas,
            'tipo' => $tipo,
            'codpagarmepedido' => $codpagarmepedido,
            'autorizacao' => $autorizacao,
            'identificador' => $identificador,
            'nsu' => $nsu,
            'nome' => $nome,
            'transacao' => $transacao,
            'valorpagamento' => $valorpagamento,
            'valorcancelamento' => $valorcancelamento,
        ]);

        return $pp;
    }

    public static function consultarPedido(PagarmePedido $ped)
    {

        $api = new PagarMeApi($ped->Filial->pagarmesk);

        // Opcoes Disponiveis: paid, canceled ou failed.
        if (!$api->getOrdersId($ped->idpedido)) {
            return false;
        }

        $ped->valortotal = $api->response->amount / 100;
        $ped->valor = $ped->valortotal - $ped->valorjuros;
        $valorpago = null;
        $valorcancelado = null;
        foreach ($api->response->charges ?? [] as $charge) {
            if (isset($charge->paid_amount)) {
                $valorpago += $charge->paid_amount / 100;
            }
            if (isset($charge->canceled_amount)) {
                $valorcancelado += $charge->canceled_amount / 100;
            }
            if (isset($charge->last_transaction)) {

                // POS
                $pos = PagarMeService::buscaOuCriaPos(
                    $ped->codfilial,
                    $charge->metadata->terminal_serial_number
                );

                $pp = PagarMeService::alteraOuCriaPagamento(
                    $charge->last_transaction->id,
                    $ped->codfilial,
                    $pos->codpagarmepos,
                    $charge->metadata->scheme_name,
                    ($charge->metadata->installment_type == 'MerchantFinanced'),
                    $charge->metadata->installment_quantity ?? 1,
                    static::TYPE_NUMBER[strtolower($charge->metadata->account_funding_source)] ?? 1,
                    $ped->codpagarmepedido,
                    $charge->metadata->authorization_code,
                    $charge->metadata->initiator_transaction_key,
                    $charge->code,
                    $charge->metadata->account_holder_name,
                    $charge->metadata->transaction_timestamp,
                    $charge->last_transaction->status,
                    $charge->last_transaction->amount / 100
                );
            }
        }
        $ped->valorpago = $valorpago;
        $ped->valorcancelado = $valorcancelado;
        $ped->valorpagoliquido = $valorpago - $valorcancelado;
        $ped->fechado = $api->response->closed;
        $ped->status = static::STATUS_NUMBER[$api->response->status];
        $ped->save();

        // cria forma de pagamento e atrela ao negocio
        static::vincularNegocioFormaPagamento($ped);

        return $ped->fresh();
    }
}
