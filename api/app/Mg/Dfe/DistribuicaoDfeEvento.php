<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:27:24
 */

namespace Mg\Dfe;

use Mg\MgModel;
use Mg\Dfe\DistribuicaoDfe;
use Mg\Dfe\DfeEvento;
use Mg\Usuario\Usuario;

class DistribuicaoDfeEvento extends MgModel
{
    protected $table = 'tbldistribuicaodfeevento';
    protected $primaryKey = 'coddistribuicaodfeevento';


    protected $fillable = [
        'cnpj',
        'coddfeevento',
        'cpf',
        'descricao',
        'orgao',
        'protocolo',
        'recebimento',
        'sequencia'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'cnpj' => 'float',
        'coddfeevento' => 'integer',
        'coddistribuicaodfeevento' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'cpf' => 'float',
        'criacao' => 'datetime',
        'orgao' => 'integer',
        'recebimento' => 'datetime',
        'sequencia' => 'integer'
    ];


    // Chaves Estrangeiras
    public function DfeEvento()
    {
        return $this->belongsTo(DfeEvento::class, 'coddfeevento', 'coddfeevento');
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
    public function DistribuicaoDfeS()
    {
        return $this->hasMany(DistribuicaoDfe::class, 'coddistribuicaodfeevento', 'coddistribuicaodfeevento');
    }

}
