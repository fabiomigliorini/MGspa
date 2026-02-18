<?php

/**
 * Created by php artisan gerador:model.
 * Date: 17/Feb/2026 11:49:45
 */

namespace Mg\Usuario;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Laravel\Passport\HasApiTokens;

use Mg\MgModel;
use Mg\Usuario\AuthAssignment;
use Mg\CupomFiscal\Ecf;
use Mg\Filial\Filial;
use Mg\Usuario\GrupoUsuarioUsuario;
use Mg\Titulo\LiquidacaoTitulo;
use Mg\Negocio\Negocio;
use Mg\Negocio\NegocioCaixaMercadoria;
use Mg\Negocio\NegocioProdutoBarra;
use Mg\NfeTerceiro\NfeTerceiro;
use Mg\NfeTerceiro\NfeTerceiroItem;
use Mg\Imagem\Imagem;
use Mg\NaturezaOperacao\Operacao;
use Mg\Pessoa\Pessoa;
use Mg\Portador\Portador;
use Mg\Usuario\Usuario;

class Usuario extends MgModel
{
    use Authenticatable, Authorizable, CanResetPassword, HasApiTokens;

    protected $table = 'tblusuario';
    protected $primaryKey = 'codusuario';


    protected $fillable = [
        'codecf',
        'codfilial',
        'codimagem',
        'codoperacao',
        'codpessoa',
        'codportador',
        'impressoramatricial',
        'impressoratelanegocio',
        'impressoratermica',
        'inativo',
        'remember_token',
        'senha',
        'ultimoacesso',
        'usuario'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'inativo',
        'ultimoacesso'
    ];

    protected $casts = [
        'codecf' => 'integer',
        'codfilial' => 'integer',
        'codimagem' => 'integer',
        'codoperacao' => 'integer',
        'codpessoa' => 'integer',
        'codportador' => 'integer',
        'codusuario' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
    ];

    public function findForPassport(string $username): Usuario
    {
        return $this->where('usuario', $username)->first();
    }


    public function getAuthPassword()
    {
        if (!empty($this->inativo)) {
            return null;
        }
        return $this->senha;
    }

    // Chaves Estrangeiras
    public function Ecf()
    {
        return $this->belongsTo(Ecf::class, 'codecf', 'codecf');
    }

    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }

    public function Imagem()
    {
        return $this->belongsTo(Imagem::class, 'codimagem', 'codimagem');
    }

    public function Operacao()
    {
        return $this->belongsTo(Operacao::class, 'codoperacao', 'codoperacao');
    }

    public function Pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoa', 'codpessoa');
    }

    public function Portador()
    {
        return $this->belongsTo(Portador::class, 'codportador', 'codportador');
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
    public function AuthAssignmentS()
    {
        return $this->hasMany(AuthAssignment::class, 'userid', 'codusuario');
    }

    public function EcfS()
    {
        return $this->hasMany(Ecf::class, 'codusuario', 'codusuario');
    }

    public function FilialAcbrNfeMonitorS()
    {
        return $this->hasMany(Filial::class, 'acbrnfemonitorcodusuario', 'codusuario');
    }

    public function GrupoUsuarioUsuarioS()
    {
        return $this->hasMany(GrupoUsuarioUsuario::class, 'codusuario', 'codusuario');
    }

    public function LiquidacaoTituloS()
    {
        return $this->hasMany(LiquidacaoTitulo::class, 'codusuario', 'codusuario');
    }

    public function LiquidacaoTituloEstornoS()
    {
        return $this->hasMany(LiquidacaoTitulo::class, 'codusuarioestorno', 'codusuario');
    }

    public function NegocioS()
    {
        return $this->hasMany(Negocio::class, 'codusuario', 'codusuario');
    }

    public function NegocioAcertoEntregaS()
    {
        return $this->hasMany(Negocio::class, 'codusuarioacertoentrega', 'codusuario');
    }

    public function NegocioConfissaoS()
    {
        return $this->hasMany(Negocio::class, 'codusuarioconfissao', 'codusuario');
    }

    public function NegocioRecebimentoS()
    {
        return $this->hasMany(Negocio::class, 'codusuariorecebimento', 'codusuario');
    }

    public function NegocioCaixaMercadoriaS()
    {
        return $this->hasMany(NegocioCaixaMercadoria::class, 'codusuariorecebimento', 'codusuario');
    }

    public function NegocioProdutoBarraS()
    {
        return $this->hasMany(NegocioProdutoBarra::class, 'codusuarioconferencia', 'codusuario');
    }

    public function NfeTerceiroS()
    {
        return $this->hasMany(NfeTerceiro::class, 'codusuarioconferencia', 'codusuario');
    }

    public function NfeTerceiroRevisaoS()
    {
        return $this->hasMany(NfeTerceiro::class, 'codusuariorevisao', 'codusuario');
    }

    public function NfeTerceiroItemS()
    {
        return $this->hasMany(NfeTerceiroItem::class, 'codusuarioconferencia', 'codusuario');
    }
}
