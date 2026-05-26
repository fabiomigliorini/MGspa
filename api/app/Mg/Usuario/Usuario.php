<?php

namespace Mg\Usuario;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Laravel\Passport\HasApiTokens;
use Mg\Filial\Filial;
use Mg\Pessoa\Pessoa;
use Mg\Portador\Portador;

/**
 * Model de usuário do banco mgsis.tblusuario.
 *
 * Compatível com o que o MGAuth e o MGspa/laravel fazem hoje:
 *  - findForPassport($username): busca por `usuario`
 *  - getAuthPassword(): retorna null se o usuário estiver inativo
 *
 * Não estende MgModel propositalmente — os hooks de audit (creating/
 * updating com Auth::user()->codusuario) gerariam recursão durante o
 * próprio fluxo de autenticação.
 *
 * Relacionamentos de domínio (Pessoa, Portador, Filial, GrupoUsuarioUsuarioS)
 * são consumidos pelo UsuarioResource em `v1/auth/user`. Outros vínculos
 * (Ecf, Imagem, Operacao, Negocio*, NfeTerceiro*, etc.) serão adicionados
 * conforme as features dependentes forem migradas.
 */
class Usuario extends Model implements AuthenticatableContract, CanResetPasswordContract
{
    use Authenticatable, Authorizable, CanResetPassword, HasApiTokens;

    protected $table = 'tblusuario';

    protected $primaryKey = 'codusuario';

    public $timestamps = false;

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

    protected $hidden = [
        'senha',
        'remember_token',
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

    public function Pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoa', 'codpessoa');
    }

    public function Portador()
    {
        return $this->belongsTo(Portador::class, 'codportador', 'codportador');
    }

    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }

    public function GrupoUsuarioUsuarioS()
    {
        return $this->hasMany(GrupoUsuarioUsuario::class, 'codusuario', 'codusuario');
    }
}
