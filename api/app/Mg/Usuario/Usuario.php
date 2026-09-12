<?php

namespace Mg\Usuario;

use DateTimeInterface;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\HasApiTokens;
use Mg\Filial\Filial;
use Mg\Imagem\Imagem;
use Mg\Pessoa\Pessoa;
use Mg\Portador\Portador;
use Mg\Usuario\GrupoUsuarioUsuario;

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

    /**
     * Audit trail — idêntico ao Mg\MgModel, replicado aqui porque este model não
     * pode estendê-lo: precisa estender Model + os contratos de autenticação
     * (ver SKIP_TABELAS em Mg\Gerador\GeradorModelCommand).
     *
     * As constantes e $timestamps têm que ficar na classe: um trait não pode
     * declará-los, porque colidem com os homônimos de Illuminate\...\Model
     * (fatal error de constante/propriedade incompatível no PHP 8.3).
     */
    const CREATED_AT = 'criacao';
    const UPDATED_AT = 'alteracao';

    public $timestamps = true;

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

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (($usuario = static::usuarioAutenticado()) !== null) {
                $model->attributes['codusuariocriacao'] = $usuario->codusuario;
                $model->attributes['codusuarioalteracao'] = $usuario->codusuario;
            }
        });

        static::updating(function ($model) {
            if (($usuario = static::usuarioAutenticado()) !== null) {
                $model->attributes['codusuarioalteracao'] = $usuario->codusuario;
            }
        });
    }

    /**
     * Usuário autenticado para o stamp de auditoria.
     *
     * Auth::user() usa o guard padrão ('web'/sessão), que nunca está autenticado
     * em requisições de token Bearer (que usam o guard 'api'/Passport). Por isso
     * resolvemos o usuário a partir do guard que estiver de fato autenticado.
     */
    protected static function usuarioAutenticado()
    {
        foreach (['api', 'web'] as $guard) {
            if (Auth::guard($guard)->check()) {
                return Auth::guard($guard)->user();
            }
        }
        return Auth::user();
    }

    /**
     * Data "naive", igual ao resto da API (Mg\MgModel) — é o que o
     * parseLocalDate() do frontend espera. O default do Eloquent (ISO-8601 em
     * UTC) faz o navegador reconverter e deslocar a hora.
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

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

    public function GrupoUsuarioUsuarioS()
    {
        return $this->hasMany(GrupoUsuarioUsuario::class, 'codusuario', 'codusuario');
    }
}
