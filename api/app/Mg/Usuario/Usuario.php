<?php

namespace Mg\Usuario;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Laravel\Passport\HasApiTokens;
use Mg\Filial\Filial;
use Mg\Imagem\Imagem;
use Mg\MgModel;
use Mg\Pessoa\Pessoa;
use Mg\Portador\Portador;

class Usuario extends MgModel implements AuthenticatableContract, CanResetPasswordContract
{
    use Authenticatable;
    use Authorizable;
    use CanResetPassword;
    use HasApiTokens;

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
        'usuario',
    ];

    protected $hidden = [
        'senha',
        'remember_token',
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
        'codusuariocriacao' => 'integer',
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'ultimoacesso' => 'datetime',
    ];

    public function findForPassport(string $username): ?self
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

    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }

    public function Imagem()
    {
        return $this->belongsTo(Imagem::class, 'codimagem', 'codimagem');
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
        return $this->belongsTo(self::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(self::class, 'codusuariocriacao', 'codusuario');
    }

    public function GrupoUsuarioUsuarioS()
    {
        return $this->hasMany(GrupoUsuarioUsuario::class, 'codusuario', 'codusuario');
    }
}
