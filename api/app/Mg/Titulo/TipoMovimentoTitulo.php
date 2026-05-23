<?php

namespace Mg\Titulo;

use App\Models\Usuario;
use Mg\MgModel;

class TipoMovimentoTitulo extends MgModel
{
    protected $table = 'tbltipomovimentotitulo';
    protected $primaryKey = 'codtipomovimentotitulo';

    protected $fillable = [
        'ajuste', 'armotizacao', 'desconto', 'estorno', 'implantacao',
        'inativo', 'juros', 'observacao', 'pagamento', 'tipomovimentotitulo',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'ajuste' => 'boolean',
        'armotizacao' => 'boolean',
        'codtipomovimentotitulo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'desconto' => 'boolean',
        'estorno' => 'boolean',
        'implantacao' => 'boolean',
        'juros' => 'boolean',
        'pagamento' => 'boolean',
    ];

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    public function TipoTituloS()
    {
        return $this->hasMany(TipoTitulo::class, 'codtipomovimentotitulo', 'codtipomovimentotitulo');
    }
}
