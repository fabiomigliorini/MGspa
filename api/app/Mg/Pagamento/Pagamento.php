<?php

namespace Mg\Pagamento;

use Mg\MgModel;
use Mg\Caixa\CaixaItemLancamento;
use Mg\Cheque\Cheque;
use Mg\Filial\Filial;
use Mg\Lio\LioPedido;
use Mg\Conferencia\ConferenciaService;
use Mg\Maquineta\Maquineta;
use Mg\Maquineta\MaquinetaLote;
use Mg\Negocio\Negocio;
use Mg\PagarMe\PagarMePedido;
use Mg\Pdv\Pdv;
use Mg\Pessoa\Pessoa;
use Mg\Pix\Pix;
use Mg\Pix\PixCob;
use Mg\Portador\Portador;
use Mg\Portador\PortadorMovimento;
use Mg\Portador\PortadorPeriodo;
use Mg\Rh\PeriodoColaboradorAcerto;
use Mg\Saurus\SaurusPedido;
use Mg\Titulo\MovimentoTitulo;
use Mg\Titulo\Titulo;
use Mg\Usuario\Usuario;

/**
 * O ato de o dinheiro se mover (M4 do plano doc-3).
 * Valor sempre positivo; o sentido vem de origem/destino (nulo = fora da
 * empresa). total = principal + juros + multa - desconto (o que ficou);
 * valortroco a parte. Constantes de meio/estado/motivo em PagamentoService.
 */
class Pagamento extends MgModel
{
    protected $table = 'tblpagamento';
    protected $primaryKey = 'codpagamento';

    protected $fillable = [
        'uuid',
        'codportadororigem',
        'codportadordestino',
        'meio',
        'estado',
        'principal',
        'juros',
        'multa',
        'desconto',
        'total',
        'valortroco',
        'parcelas',
        'transacao',
        'efetivacao',
        'codusuarioefetivacao',
        'cancelamento',
        'codusuariocancelamento',
        'justificativa',
        'codpessoa',
        'codpdv',
        'codfilial',
        'motivo',
        'codnegocio',
        'codpagamentoorigem',
        'codmaquineta',
        'bandeira',
        'autorizacao',
        'nsu',
        'codpixcob',
        'codpix',
        'codpagarmepedido',
        'codsauruspedido',
        'codliopedido',
        'codtitulo',
        'codliquidacaotituloantigo',
        'codperiodocolaboradoracerto',
        'cmc7',
        'chequevencimento',
        'chequecnpj',
        'chequeemitente',
        'observacoes',
        'codmaquinetalote',
        'codmaquinetalotecancelamento',
        'indevido',
        'codportadorperiodo',
        'conferencia',
        'codusuarioconferencia',
        'codcaixaitemlancamento',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'bandeira' => 'integer',
        'cancelamento' => 'datetime',
        'chequecnpj' => 'integer',
        'chequevencimento' => 'date',
        'codfilial' => 'integer',
        'codliopedido' => 'integer',
        'codmaquineta' => 'integer',
        'codmaquinetalote' => 'integer',
        'codmaquinetalotecancelamento' => 'integer',
        'codportadorperiodo' => 'integer',
        'codcaixaitemlancamento' => 'integer',
        'codusuarioconferencia' => 'integer',
        'conferencia' => 'datetime',
        'indevido' => 'boolean',
        'codnegocio' => 'integer',
        'codpagamento' => 'integer',
        'codpagamentoorigem' => 'integer',
        'codpagarmepedido' => 'integer',
        'codpdv' => 'integer',
        'codpessoa' => 'integer',
        'codpix' => 'integer',
        'codpixcob' => 'integer',
        'codportadordestino' => 'integer',
        'codportadororigem' => 'integer',
        'codsauruspedido' => 'integer',
        'codtitulo' => 'integer',
        'codliquidacaotituloantigo' => 'integer',
        'codperiodocolaboradoracerto' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocancelamento' => 'integer',
        'codusuariocriacao' => 'integer',
        'codusuarioefetivacao' => 'integer',
        'criacao' => 'datetime',
        'desconto' => 'float',
        'efetivacao' => 'datetime',
        'juros' => 'float',
        'transacao' => 'datetime',
        'meio' => 'integer',
        'multa' => 'float',
        'parcelas' => 'integer',
        'principal' => 'float',
        'total' => 'float',
        'valortroco' => 'float',
    ];

    // Cartao no lote da maquineta e dinheiro na sessao da gaveta (M9 doc-3):
    // todo pagamento gravado passa pela conferencia, venha de onde vier.
    // creating/updating, nao saving: o saving do MgModel devolve true e
    // interrompe os outros ouvintes.
    protected static function booted()
    {
        $vincular = function (Pagamento $pag) {
            ConferenciaService::vincular($pag);
        };
        static::creating($vincular);
        static::updating($vincular);
    }

    // Veio de uma integracao (PIX QR, PagarMe, Saurus, Lio): o PDV nao
    // grava nem apaga pelo sync.
    public function ehIntegrado(): bool
    {
        return !empty($this->codpixcob)
            || !empty($this->codpagarmepedido)
            || !empty($this->codsauruspedido)
            || !empty($this->codliopedido);
    }

    // Pagamento de saida (contrario da venda: estorno de cartao, devolucao)
    public function ehSaida(): bool
    {
        return !empty($this->codportadororigem) && empty($this->codportadordestino);
    }

    // Portador do pagamento: o destino (recebimento) ou a origem (pagamento)
    public function portadorDoPagamento(): ?Portador
    {
        return $this->PortadorDestino ?? $this->PortadorOrigem;
    }

    // Chaves Estrangeiras
    public function Cheque()
    {
        return $this->hasOne(Cheque::class, 'codpagamento', 'codpagamento');
    }

    public function CaixaItemLancamento()
    {
        return $this->belongsTo(CaixaItemLancamento::class, 'codcaixaitemlancamento', 'codcaixaitemlancamento');
    }

    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }

    public function LioPedido()
    {
        return $this->belongsTo(LioPedido::class, 'codliopedido', 'codliopedido');
    }

    public function Maquineta()
    {
        return $this->belongsTo(Maquineta::class, 'codmaquineta', 'codmaquineta');
    }

    public function MaquinetaLote()
    {
        return $this->belongsTo(MaquinetaLote::class, 'codmaquinetalote', 'codmaquinetalote');
    }

    public function MaquinetaLoteCancelamento()
    {
        return $this->belongsTo(MaquinetaLote::class, 'codmaquinetalotecancelamento', 'codmaquinetalote');
    }

    public function Negocio()
    {
        return $this->belongsTo(Negocio::class, 'codnegocio', 'codnegocio');
    }

    public function PagamentoOrigem()
    {
        return $this->belongsTo(Pagamento::class, 'codpagamentoorigem', 'codpagamento');
    }

    public function PagarMePedido()
    {
        return $this->belongsTo(PagarMePedido::class, 'codpagarmepedido', 'codpagarmepedido');
    }

    public function Pdv()
    {
        return $this->belongsTo(Pdv::class, 'codpdv', 'codpdv');
    }

    public function Pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoa', 'codpessoa');
    }

    public function Pix()
    {
        return $this->belongsTo(Pix::class, 'codpix', 'codpix');
    }

    public function PixCob()
    {
        return $this->belongsTo(PixCob::class, 'codpixcob', 'codpixcob');
    }

    public function PortadorDestino()
    {
        return $this->belongsTo(Portador::class, 'codportadordestino', 'codportador');
    }

    public function PortadorOrigem()
    {
        return $this->belongsTo(Portador::class, 'codportadororigem', 'codportador');
    }

    public function PortadorPeriodo()
    {
        return $this->belongsTo(PortadorPeriodo::class, 'codportadorperiodo', 'codportadorperiodo');
    }

    public function PeriodoColaboradorAcerto()
    {
        return $this->belongsTo(PeriodoColaboradorAcerto::class, 'codperiodocolaboradoracerto', 'codperiodocolaboradoracerto');
    }

    public function SaurusPedido()
    {
        return $this->belongsTo(SaurusPedido::class, 'codsauruspedido', 'codsauruspedido');
    }

    public function Titulo()
    {
        return $this->belongsTo(Titulo::class, 'codtitulo', 'codtitulo');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCancelamento()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocancelamento', 'codusuario');
    }

    public function UsuarioConferencia()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioconferencia', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    public function UsuarioEfetivacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioefetivacao', 'codusuario');
    }

    // Tabelas Filhas
    public function MovimentoTituloS()
    {
        return $this->hasMany(MovimentoTitulo::class, 'codpagamento', 'codpagamento');
    }

    public function PagamentoContrarioS()
    {
        return $this->hasMany(Pagamento::class, 'codpagamentoorigem', 'codpagamento');
    }

    public function PagamentoCorrecaoS()
    {
        return $this->hasMany(PagamentoCorrecao::class, 'codpagamento', 'codpagamento');
    }

    // razao (M10 doc-3): o que este pagamento lancou em cada portador
    public function PortadorMovimentoS()
    {
        return $this->hasMany(PortadorMovimento::class, 'codpagamento', 'codpagamento');
    }
}
