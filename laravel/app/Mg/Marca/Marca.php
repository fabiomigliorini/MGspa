<?php
/**
 * Created by php artisan gerador:model.
 * Date: 28/May/2021 15:27:32
 */

namespace Mg\Marca;

use Mg\MgModel;
use Mg\Produto\Produto;
use Mg\Produto\ProdutoBarra;
use Mg\Produto\ProdutoVariacao;
use Mg\GrupoEconomico\GrupoEconomico;
use Mg\Imagem\Imagem;
use Mg\Usuario\Usuario;

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
        'vendaultimocalculo'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'dataultimacompra',
        'inativo',
        'vendaultimocalculo'
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
        'vendasemestrevalor' => 'float'
    ];


    // Chaves Estrangeiras
    public function GrupoEconomico()
    {
        return $this->belongsTo(GrupoEconomico::class, 'codgrupoeconomico', 'codgrupoeconomico');
    }

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


    // Tabelas Filhas
    public function ProdutoS()
    {
        return $this->hasMany(Produto::class, 'codmarca', 'codmarca');
    }

    public function ProdutoBarraS()
    {
        return $this->hasMany(ProdutoBarra::class, 'codmarca', 'codmarca');
    }

    public function ProdutoVariacaoS()
    {
        return $this->hasMany(ProdutoVariacao::class, 'codmarca', 'codmarca');
    }

}