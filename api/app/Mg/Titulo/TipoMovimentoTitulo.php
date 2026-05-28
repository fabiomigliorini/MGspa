<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:22:50
 */

namespace Mg\Titulo;

use Mg\MgModel;
use Mg\Titulo\MovimentoTitulo;
use Mg\Titulo\TipoTitulo;
use Mg\Usuario\Usuario;

class TipoMovimentoTitulo extends MgModel
{
    protected $table = 'tbltipomovimentotitulo';
    protected $primaryKey = 'codtipomovimentotitulo';


    protected $fillable = [
        'ajuste',
        'armotizacao',
        'desconto',
        'estorno',
        'implantacao',
        'inativo',
        'juros',
        'observacao',
        'pagamento',
        'tipomovimentotitulo'
    ];

    protected $casts = [
        'ajuste' => 'boolean',
        'alteracao' => 'datetime',
        'armotizacao' => 'boolean',
        'codtipomovimentotitulo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'desconto' => 'boolean',
        'estorno' => 'boolean',
        'implantacao' => 'boolean',
        'inativo' => 'datetime',
        'juros' => 'boolean',
        'pagamento' => 'boolean'
    ];


    // Chaves Estrangeiras
    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }


    // Tabelas Filhas
    public function MovimentoTituloS()
    {
        return $this->hasMany(MovimentoTitulo::class, 'codtipomovimentotitulo', 'codtipomovimentotitulo');
    }

    public function TipoTituloS()
    {
        return $this->hasMany(TipoTitulo::class, 'codtipomovimentotitulo', 'codtipomovimentotitulo');
    }

}
