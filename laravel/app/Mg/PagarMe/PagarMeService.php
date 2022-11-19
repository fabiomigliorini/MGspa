<?php

namespace Mg\PagarMe;

use Illuminate\Support\Facades\Storage;

use Mg\Filial\Filial;

use Carbon\Carbon;

class PagarMeService
{

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
        $reg->codnegocio = $codnegocio;
        $reg->codpagarmepos = $codpagarmepos;
        $reg->codpessoa = $codpessoa;
        $reg->descricao = $descricao;
        $reg->fechado = $fechado;
        $reg->jurosloja = $jurosloja;
        $reg->parcelas = $parcelas;
        $reg->tipo = $tipo;
        $reg->valor = $valor;
        $reg->valorpago = $valorpago;
        $reg->valorcancelado = $valorcancelado;
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
            $ped->valorcancelado = $pag;
            $ped->valorpagoliquido = $ped->valorpago - $ped->valorcancelado;
        }
        if ($ped->isDirty()) {
            $ped->save();
        }
        return true;
    }


}
