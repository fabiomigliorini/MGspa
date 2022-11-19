<?php
/**
 * Created by php artisan gerador:model.
 * Date: 19/Nov/2022 17:56:02
 */

namespace Mg\Filial;

use Mg\MgModel;
use Mg\Dfe\DistribuicaoDfe;
use Mg\CupomFiscal\Ecf;
use Mg\Estoque\EstoqueLocal;
use Mg\Usuario\GrupoUsuarioUsuario;
use Mg\NFePHP\IbptCache;
use Mg\Meta\MetaFilial;
use Mg\Negocio\Negocio;
use Mg\NfeTerceiro\NfeTerceiro;
use Mg\NotaFiscal\NotaFiscal;
use Mg\NotaFiscalTerceiro\NotaFiscalTerceiro;
use Mg\Portador\Portador;
use Mg\Titulo\Titulo;
use Mg\Usuario\Usuario;
use Mg\ValeCompra\ValeCompra;
use Mg\Lio\LioTerminal;
use Mg\Mdfe\Mdfe;
use Mg\Stone\StoneFilial;
use Mg\PagarMe\PagarMePagamento;
use Mg\PagarMe\PagarMePedido;
use Mg\PagarMe\PagarMePos;
use Mg\Filial\Empresa;
use Mg\Pessoa\Pessoa;

class Filial extends MgModel
{
    const CRT_SIMPLES = 1;
    const CRT_SIMPLES_EXCESSO = 2;
    const CRT_REGIME_NORMAL = 3;

    const NFEAMBIENTE_PRODUCAO = 1;
    const NFEAMBIENTE_HOMOLOGACAO = 2;

    protected $table = 'tblfilial';
    protected $primaryKey = 'codfilial';


    protected $fillable = [
        'acbrnfemonitorbloqueado',
        'acbrnfemonitorcaminho',
        'acbrnfemonitorcaminhorede',
        'acbrnfemonitorcodusuario',
        'acbrnfemonitorip',
        'acbrnfemonitorporta',
        'codempresa',
        'codpessoa',
        'crt',
        'dfe',
        'emitenfe',
        'empresadominio',
        'filial',
        'inativo',
        'nfcetoken',
        'nfcetokenid',
        'nfeambiente',
        'nfeserie',
        'odbcnumeronotafiscal',
        'pagarmeid',
        'pagarmesk',
        'senhacertificado',
        'tokenibpt',
        'ultimonsu',
        'validadecertificado'
    ];

    protected $dates = [
        'acbrnfemonitorbloqueado',
        'alteracao',
        'criacao',
        'inativo',
        'validadecertificado'
    ];

    protected $casts = [
        'acbrnfemonitorcodusuario' => 'integer',
        'acbrnfemonitorporta' => 'integer',
        'codempresa' => 'integer',
        'codfilial' => 'integer',
        'codpessoa' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'crt' => 'integer',
        'dfe' => 'boolean',
        'emitenfe' => 'boolean',
        'empresadominio' => 'float',
        'nfeambiente' => 'integer',
        'nfeserie' => 'integer',
        'ultimonsu' => 'integer'
    ];


    // Chaves Estrangeiras
    public function UsuarioAcbrNfeMonitor()
    {
        return $this->belongsTo(Usuario::class, 'acbrnfemonitorcodusuario', 'codusuario');
    }

    public function Empresa()
    {
        return $this->belongsTo(Empresa::class, 'codempresa', 'codempresa');
    }

    public function Pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoa', 'codpessoa');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }


    // Tabelas Filhas
    public function DistribuicaoDfeS()
    {
        return $this->hasMany(DistribuicaoDfe::class, 'codfilial', 'codfilial');
    }

    public function EcfS()
    {
        return $this->hasMany(Ecf::class, 'codfilial', 'codfilial');
    }

    public function EstoqueLocalS()
    {
        return $this->hasMany(EstoqueLocal::class, 'codfilial', 'codfilial');
    }

    public function GrupoUsuarioUsuarioS()
    {
        return $this->hasMany(GrupoUsuarioUsuario::class, 'codfilial', 'codfilial');
    }

    public function IbptCacheS()
    {
        return $this->hasMany(IbptCache::class, 'codfilial', 'codfilial');
    }

    public function LioTerminalS()
    {
        return $this->hasMany(LioTerminal::class, 'codfilial', 'codfilial');
    }

    public function MdfeS()
    {
        return $this->hasMany(Mdfe::class, 'codfilial', 'codfilial');
    }

    public function MetaFilialS()
    {
        return $this->hasMany(MetaFilial::class, 'codfilial', 'codfilial');
    }

    public function NegocioS()
    {
        return $this->hasMany(Negocio::class, 'codfilial', 'codfilial');
    }

    public function NfeTerceiroS()
    {
        return $this->hasMany(NfeTerceiro::class, 'codfilial', 'codfilial');
    }

    public function NotaFiscalS()
    {
        return $this->hasMany(NotaFiscal::class, 'codfilial', 'codfilial');
    }

    public function NotaFiscalTerceiroS()
    {
        return $this->hasMany(NotaFiscalTerceiro::class, 'codfilial', 'codfilial');
    }

    public function PagarMePagamentoS()
    {
        return $this->hasMany(PagarMePagamento::class, 'codfilial', 'codfilial');
    }

    public function PagarMePedidoS()
    {
        return $this->hasMany(PagarMePedido::class, 'codfilial', 'codfilial');
    }

    public function PagarMePosS()
    {
        return $this->hasMany(PagarMePos::class, 'codfilial', 'codfilial');
    }

    public function PortadorS()
    {
        return $this->hasMany(Portador::class, 'codfilial', 'codfilial');
    }

    public function StoneFilialS()
    {
        return $this->hasMany(StoneFilial::class, 'codfilial', 'codfilial');
    }

    public function TituloS()
    {
        return $this->hasMany(Titulo::class, 'codfilial', 'codfilial');
    }

    public function UsuarioS()
    {
        return $this->hasMany(Usuario::class, 'codfilial', 'codfilial');
    }

    public function ValeCompraS()
    {
        return $this->hasMany(ValeCompra::class, 'codfilial', 'codfilial');
    }

}