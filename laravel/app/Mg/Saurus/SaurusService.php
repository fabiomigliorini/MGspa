<?php

namespace Mg\Saurus;

use Illuminate\Support\Facades\Log;
use Mg\FormaPagamento\FormaPagamento;
use Mg\Negocio\NegocioFormaPagamento;
use Mg\Negocio\NegocioService;
use Mg\Saurus\S2Pay\ApiService;

class SaurusService
{

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

    public static function cancelarPedidosAbertosPdv($codsauruspdv)
    {
        $peds = SaurusPedido::where([
            'codsauruspdv' => $codsauruspdv,
            'status' => 0,
        ])->get();

        foreach ($peds as $ped) {
            try {
                // update pedido to status 3
                $ped->status = 3;
                $ped->save();

            } catch (\Exception $e) {
                
                Log::error('Erro ao cancelar pedido Saurus: '.$e->getMessage());
            }
        }

        return true;
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

        if($pdv->vencimento < now()) {

            $responseAutorizacao = ApiService::functionAutorizacao($pdv->id);

            $pdv->autorizacao = $responseAutorizacao->response->chavePublica;
            $pdv->vencimento = $responseAutorizacao->response->vencimento;

            $pdv->save();
        }

        $pedido = ApiService::functionPedidoCriar($ped, $pdv, $pos);
        
        $ped->id = $pedido->response->id;

        $ped->save();

        return $ped;
    }

    public static function consultarPedido($ped)
    {
        
        $pdv = SaurusPdv::findOrFail($ped->codsauruspdv);

        if($pdv->vencimento < now()) {

            $responseAutorizacao = ApiService::functionAutorizacao($pdv->id);

            $pdv->autorizacao = $responseAutorizacao->response->chavePublica;
            $pdv->vencimento = $responseAutorizacao->response->vencimento;

            $pdv->save();
        }

        $response = ApiService::functionPagamentoConsultar($ped->idfaturapag, $pdv->autorizacao);

        if($response->response == null || $response->response == 'null') {
            return $ped;
        }

        if($response->response->indStatus == 1){
            $ped->status = 2;
            $ped->save();
        }

        $bandeira = self::buscaOuCriaBandeira($response->response->bandeira);

        $codsauruspinpad = SaurusPinPad::where('serial', $response->response->idPinPad)->first()->codsauruspinpad;

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
                'transacao' => $response->response->dTransacao,
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
            self::vincularNegocioFormaPagamento($ped);
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

    public static function vincularNegocioFormaPagamento(SaurusPedido $ped)
    {
        if (empty($ped->codnegocio)) {
            return false;
        }

        $fp = FormaPagamento::firstOrNew([
            'safrapay' => true,
            'integracao' => true
        ]);

        if (!$fp->exists) {
            $fp->formapagamento = 'Saurus S2Pay';
            $fp->avista = true;
            $fp->integracao = true;
            $fp->safrapay = true;
            $fp->save();
        }

        $tipo = 99; //Outros
        $autorizacao = null;
        $bandeira = null;
        foreach ($ped->SaurusPagamentoS as $pag) {
            $tipo = $pag->modpagamento;
           
            $autorizacao = $pag->autorizacao;
            $bandeira = static::buscaOuCriaBandeira(
                $pag->SaurusBandeira->bandeira
            );
        }

        NegocioFormaPagamento::updateOrCreate(
            [
                'codnegocio' => $ped->codnegocio,
                'autorizacao' => $autorizacao,
                'codpessoa' => env('SAFRA_CODPESSOA')
            ],
            [
                'codsauruspedido' => $ped->codsauruspedido,
                'codformapagamento' => $fp->codformapagamento,
                'avista' => true,
                'valorpagamento' => $ped->valor,
                'valorjuros' => $ped->valorjuros,
                'valortotal' => $ped->valor + $ped->valorjuros,
                'valortroco' => null,
                'tipo' => $tipo,
                'bandeira' => $bandeira->tband,
                'integracao' => true
            ]
        );

        NegocioService::fecharSePago($ped->Negocio);

        return true;
    }

    public static function cancelarPedido(SaurusPedido $ped)
    {
        if ($ped->status != 0) {
            throw new \Exception("Pedido não consta como pendente! Status {$ped->status}!", 1);
        }


        $ped->update([
            'status' => 3
        ]);

        return $ped->fresh();
    }
}