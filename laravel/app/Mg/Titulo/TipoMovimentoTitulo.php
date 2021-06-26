<?php
/**
 * Created by php artisan gerador:model.
 * Date: 20/Jun/2020 14:48:30
 */

namespace Mg\Titulo;

use Mg\MgModel;
use Mg\Titulo\MovimentoTitulo;
use Mg\Titulo\TipoTitulo;
use Mg\Usuario\Usuario;

class TipoMovimentoTitulo extends MgModel
{
    const JUROS = 400;
    const MULTA = 401;
    const DESCONTO = 500;
    const LIQUIDACAO = 600;
    const ESTORNOLIQUIDACAO = 930;

    protected $table = 'tbltipomovimentotitulo';
    protected $primaryKey = 'codtipomovimentotitulo';

    protected $fillable = [
        'ajuste',
        'armotizacao',
        'desconto',
        'estorno',
        'implantacao',
        'juros',
        'observacao',
        'pagamento',
        'tipomovimentotitulo'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'ajuste' => 'boolean',
        'armotizacao' => 'boolean',
        'codtipomovimentotitulo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'desconto' => 'boolean',
        'estorno' => 'boolean',
        'implantacao' => 'boolean',
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
