<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:32:41
 */

namespace Mg\Stone;

use Mg\MgModel;
use Mg\Stone\StonePreTransacao;
use Mg\Stone\StoneFilial;
use Mg\Usuario\Usuario;

class StonePos extends MgModel
{
    protected $table = 'tblstonepos';
    protected $primaryKey = 'codstonepos';


    protected $fillable = [
        'apelido',
        'codstonefilial',
        'inativo',
        'islinked',
        'referenceid',
        'serialnumber'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codstonefilial' => 'integer',
        'codstonepos' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'islinked' => 'boolean'
    ];


    // Chaves Estrangeiras
    public function StoneFilial()
    {
        return $this->belongsTo(StoneFilial::class, 'codstonefilial', 'codstonefilial');
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
    public function StonePreTransacaoS()
    {
        return $this->hasMany(StonePreTransacao::class, 'codstonepos', 'codstonepos');
    }

}
