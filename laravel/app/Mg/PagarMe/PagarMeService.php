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

    public static function buscaFilial (string $id)
    {
        $reg = Filial::where('pagarmeid', $id)->first();
        return $reg;
    }

    // Cadastra se POS novo
    public static function buscaOuCriaPos (int $codfilial, string $serial)
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
    public static function buscaOuCriaBandeira (string $scheme)
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
    public static function alteraOuCriaPedido (
        int $codfilial,
        string $idpedido,
        int $status,
        ?int $codnegocio,
        int $codpagarmepos,
        ?int $codpessoa,
        ?string $descricao,
        bool $fechado,
        ?bool $jurosloja,
        ?int $parcelas,
        int $tipo,
        float $valor,
        ?float $valorpago,
        ?float $valorcancelado
    ){
        $reg = PagarMePedido::firstOrNew([
            'codfilial' => $codfilial,
            'idpedido' => $idpedido,
        ]);
        $reg->status = $status;
        if (!empty($codnegocio)) {
            $reg->codnegocio = $codnegocio;
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
        $reg->valor = $valor;
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
    public static function confereTotaisPedido (PagarMePedido $ped)
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

    public static function criarPedido (
        ?int $codfilial,
        int $codpagarmepos,
        int $tipo,
        float $valor,
        int $parcelas,
        bool $jurosloja,
        ?string $descricao,
        ?int $codnegocio,
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
            $pes->email??'nfe@mgpapelaria.com.br',
            $valor,
            $descricao,
            1,
            false,
            true,
            $pos->serial,
            static::TYPE_DESCRIPTION[$tipo],
            $parcelas,
            $jurosloja?'merchant':'issuer'
        );

        $ped = static::alteraOuCriaPedido (
            $codfilial,
            $api->response->id,
            static::STATUS_NUMBER[$api->response->status],
            $codnegocio,
            $codpagarmepos,
            $codpessoa,
            $descricao,
            $api->response->closed,
            $jurosloja,
            $parcelas,
            $tipo,
            $valor,
            0,
            0
        );

        return $ped;

    }

    public static function cancelarPedido (PagarmePedido $ped)
    {
        if ($ped->status != 1) {
            throw new \Exception("Pedido não consta como pendente! Status {$ped->status}!", 1);
        }

        $api = new PagarMeApi($ped->Filial->pagarmesk);

        // Opcoes Disponiveis: paid, canceled ou failed.
        if (!$api->patchOrdersClosed($ped->idpedido, 'canceled')) {
            return false;
        }

        // dd($api->response->closed);
        // dd(static::STATUS_NUMBER[$api->response->status]);

        $ret = $ped->update([
            'fechado' => $api->response->closed,
            'status' => static::STATUS_NUMBER[$api->response->status]
        ]);

        // dd($ret);

        return $ped->fresh();
    }


    public static function vincularNegocioFormaPagamento(PagarMePedido $ped)
    {
        if (empty($ped->codnegocio)) {
            return;
        }
        $nfp = NegocioFormaPagamento::firstOrNew([
            'codpagarmepedido' => $ped->codpagarmepedido
        ]);
        $nfp->codnegocio = $ped->codnegocio;
        $fp = FormaPagamento::firstOrNew(['stone' => true, 'integracao' => true]);
        if (!$fp->exists) {
            $fp->formapagamento = 'Stone PagarMe';
            $fp->avista = true;
            $fp->integracao = true;
            $fp->save();
        }
        $nfp->codformapagamento = $fp->codformapagamento;
        $nfp->valorpagamento = $ped->valorpagoliquido;
        $nfp->save();

        $fechado = NegocioService::fecharSePago($ped->Negocio);

        $nfp->save();
    }

}
