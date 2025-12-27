<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/Dec/2025 10:51:27
 */

namespace Mg\Tributacao;

use Mg\MgModel;
use Mg\NotaFiscal\NotaFiscalItemTributo;
use Mg\Tributacao\TributacaoRegra;
use Mg\Usuario\Usuario;

class Tributo extends MgModel
{
    protected $table = 'tbltributo';
    protected $primaryKey = 'codtributo';


    protected $fillable = [
        'codigo',
        'descricao',
        'ente'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codtributo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
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
    public function NotaFiscalItemTributoS()
    {
        return $this->hasMany(NotaFiscalItemTributo::class, 'codtributo', 'codtributo');
    }

    public function TributacaoRegraS()
    {
        return $this->hasMany(TributacaoRegra::class, 'codtributo', 'codtributo');
    }

}