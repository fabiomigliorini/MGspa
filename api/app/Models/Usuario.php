<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Laravel\Passport\HasApiTokens;

/**
 * Model de usuário do banco mgsis.tblusuario. Versão enxuta — só o que
 * importa pra autenticação. Os relacionamentos de domínio (Filial,
 * Pessoa, Portador, etc.) ficam no MGspa/laravel até as controllers
 * correspondentes serem migradas pra cá.
 *
 * Compatível com o que o MGAuth e o MGspa/laravel fazem hoje:
 *  - findForPassport($username): busca por `usuario`
 *  - getAuthPassword(): retorna null se o usuário estiver inativo
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
}
