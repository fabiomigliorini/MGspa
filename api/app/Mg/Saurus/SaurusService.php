<?php

namespace Mg\Saurus;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Mg\Filial\Filial;
use Mg\Pagamento\Pagamento;
use Mg\Pagamento\PagamentoService;
use Mg\Negocio\NegocioService;
use Mg\Pessoa\Pessoa;
use Mg\Saurus\S2Pay\ApiService;
use Mg\Maquineta\MaquinetaService;
use Ramsey\Uuid\Uuid;

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

    // Registra (ou renova) o PDV Saurus na API: devolve o PDV com a chavepublica que vira o QR
    // lido pelo pinpad. Sem uuid, cria um PDV novo com o próximo número da filial.
    public static function registrarPdv(string $apelido, int $codfilial, ?string $pdv_uuid = null): SaurusPdv
    {
        $pdv_uuid = $pdv_uuid ?? (string) Uuid::uuid4();

        $pdvSaurus = SaurusPdv::where('id', $pdv_uuid)->first();
        if ($pdvSaurus && $pdvSaurus->vencimento > now()) {
            return $pdvSaurus;
        }

        $pessoa = Filial::findOrFail($codfilial)->Pessoa;

        if ($pdvSaurus) {
            $numero = $pdvSaurus->numero;
        } else {
            $ultimo = SaurusPdv::where('codfilial', $codfilial)->orderBy('numero', 'desc')->first();
            $numero = $ultimo ? $ultimo->numero + 1 : 1;
        }

        $response = ApiService::functionPdvRegistrar($pdv_uuid, $pessoa, $numero);

        return SaurusPdv::updateOrCreate(
            [
                'id' => $pdv_uuid,
            ],
            [
                'apelido' => $apelido,
                'autorizacao' => $response->autorizacao->response->chavePublica,
                'vencimento' => Carbon::parse($response->autorizacao->response->vencimento)->subHour(1)->subMinutes(10),
                'chavepublica' => $response->pdv->response->chavePublica,
                'contratoid' => $response->pdv->response->contratoId,
                'codfilial' => $codfilial,
                'numero' => $numero,
            ]
        );
    }

    // Depois que o pinpad leu o QR: busca na API o pinpad pareado ao PDV Saurus e grava.
    // Null enquanto o pinpad não leu.
    public static function verificarLeitura(SaurusPdv $pdvSaurus): ?SaurusPinPad
    {
        $response = ApiService::functionPdvVerificar($pdvSaurus->autorizacao);

        if (str_contains($response->retTexto, 'Chave de Autorização está Inválida')) {
            $pessoa = Filial::findOrFail($pdvSaurus->codfilial)->Pessoa;
            $autorizacao = ApiService::functionAutorizacao($pdvSaurus->id, $pessoa->cnpj);
            $pdvSaurus->autorizacao = $autorizacao->response->chavePublica;
            $pdvSaurus->vencimento = Carbon::parse($autorizacao->response->vencimento)->subHour(1)->subMinutes(10);
            $pdvSaurus->save();
            $response = ApiService::functionPdvVerificar($pdvSaurus->autorizacao);
        }

        if (empty($response->response->pinPads)) {
            return null;
        }

        return SaurusPinPad::updateOrCreate(
            [
                'id' => $response->response->pinPads[0],
            ],
            [
                'apelido' => mb_substr($pdvSaurus->apelido, 0, 20),
                'codfilial' => $pdvSaurus->codfilial,
                'codsauruspdv' => $pdvSaurus->codsauruspdv,
            ]
        );
    }

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

            $pessoa = Pessoa::findOrFail(Filial::findOrFail($pdv->codfilial)->codpessoa);

            $responseAutorizacao = ApiService::functionAutorizacao($pdv->id, $pessoa->cnpj);

            $pdv->autorizacao = $responseAutorizacao->response->chavePublica;
            $pdv->vencimento = Carbon::parse($responseAutorizacao->response->vencimento)->subHour(1)->subMinutes(10);

            $pdv->save();
        }

        // quando da timeout ou algum erro na saurus, retorna o pedido mesmo assim
        try {
            $pedido = ApiService::functionPedidoCriar($ped, $pdv, $pos);
            $ped->id = $pedido->response->id;
            $ped->save();
        } catch (\Throwable $th) {
            //throw $th;
        }

        return $ped;
    }

    public static function consultarPedido($ped)
    {
        
        $pdv = SaurusPdv::findOrFail($ped->codsauruspdv);

        $pessoa = Pessoa::findOrFail(Filial::findOrFail($pdv->codfilial)->codpessoa);

        if($pdv->vencimento < now()) {

            $responseAutorizacao = ApiService::functionAutorizacao($pdv->id, $pessoa->cnpj);

            $pdv->autorizacao = $responseAutorizacao->response->chavePublica;
            $pdv->vencimento = Carbon::parse($responseAutorizacao->response->vencimento)->subHour(1)->subMinutes(10);

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

        // idPinPad da Saurus é o uuid (coluna id); serial é o número de série físico
        $codsauruspinpad = SaurusPinPad::where('id', $response->response->idPinPad)->first()->codsauruspinpad;

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

    public static function vincularPagamento(SaurusPedido $ped)
    {
        if (empty($ped->codnegocio)) {
            return false;
        }

        $tipo = 99; //Outros
        $autorizacao = null;
        $bandeira = null;
        // pinpad que cobrou; sem ele, o mais novo do PDV Saurus
        $pinpad = $ped->SaurusPdv->SaurusPinPadS()->orderBy('codsauruspinpad', 'desc')->first();
        foreach ($ped->SaurusPagamentoS as $pag) {
            $tipo = $pag->modpagamento;

            $autorizacao = $pag->autorizacao;
            $bandeira = static::buscaOuCriaBandeira(
                $pag->SaurusBandeira->bandeira
            );
            $pinpad = $pag->SaurusPinPad ?? $pinpad;
        }
        $maquineta = $pinpad ? MaquinetaService::daSaurusPinPad($pinpad) : null;

        // pagamento efetivado: a maquineta ja' confirmou (M4 doc-3). Um por
        // pedido; mesma autorizacao no negocio e' o mesmo pagamento.
        $pag = Pagamento::where('codsauruspedido', $ped->codsauruspedido)->first();
        if (!$pag && !empty($autorizacao)) {
            $pag = Pagamento::where('codnegocio', $ped->codnegocio)
                ->where('autorizacao', $autorizacao)
                ->where('codpessoa', config('mg.codpessoa_safra'))
                ->first();
        }
        $pag = $pag ?? new Pagamento();
        PagamentoService::preencher($pag, [
            'codnegocio' => $ped->codnegocio,
            'codfilial' => $ped->Negocio->codfilial,
            'codpdv' => $ped->Negocio->codpdv,
            'codsauruspedido' => $ped->codsauruspedido,
            'meio' => array_key_exists((int) $tipo, PagamentoService::MEIOS) ? (int) $tipo : PagamentoService::MEIO_OUTROS,
            'principal' => $ped->valor,
            'juros' => $ped->valorjuros ?? 0,
            'valortroco' => null,
            'autorizacao' => $autorizacao,
            'bandeira' => $bandeira->tband ?? null,
            'codpessoa' => config('mg.codpessoa_safra'),
            'codmaquineta' => $maquineta->codmaquineta ?? null,
        ]);
        $pag->save();
        if ($pag->estado != PagamentoService::ESTADO_CANCELADO) {
            PagamentoService::efetivar($pag);
        }

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