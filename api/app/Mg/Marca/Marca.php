<?php

namespace Mg\Marca;

use App\Models\Usuario;
use Mg\Imagem\Imagem;
use Mg\MgModel;

class Marca extends MgModel
{
    protected $table = 'tblmarca';
    protected $primaryKey = 'codmarca';

    protected $fillable = [
        'abccategoria',
        'abcignorar',
        'abcposicao',
        'codgrupoeconomico',
        'codimagem',
        'codopencart',
        'controlada',
        'dataultimacompra',
        'descricaosite',
        'estoquemaximodias',
        'estoqueminimodias',
        'inativo',
        'itensabaixominimo',
        'itensacimamaximo',
        'marca',
        'site',
        'vendaanopercentual',
        'vendaanovalor',
        'vendabimestrevalor',
        'vendasemestrevalor',
        'vendaultimocalculo',
    ];

    protected $casts = [
        'abccategoria' => 'integer',
        'abcignorar' => 'boolean',
        'abcposicao' => 'integer',
        'codgrupoeconomico' => 'integer',
        'codimagem' => 'integer',
        'codmarca' => 'integer',
        'codopencart' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'controlada' => 'boolean',
        'estoquemaximodias' => 'integer',
        'estoqueminimodias' => 'integer',
        'itensabaixominimo' => 'integer',
        'itensacimamaximo' => 'integer',
        'site' => 'boolean',
        'vendaanopercentual' => 'float',
        'vendaanovalor' => 'float',
        'vendabimestrevalor' => 'float',
        'vendasemestrevalor' => 'float',
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
        'dataultimacompra' => 'datetime',
        'inativo' => 'datetime',
        'vendaultimocalculo' => 'datetime',
    ];

    public function Imagem()
    {
        return $this->belongsTo(Imagem::class, 'codimagem', 'codimagem');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }
}
