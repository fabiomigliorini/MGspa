<?php
/**
 * Created by php artisan gerador:model.
 * Date: 17/Jul/2020 15:47:56
 */

namespace Mg\Dfe;

use Mg\MgModel;
use Mg\Dfe\DfeEvento;
use Mg\Dfe\DistribuicaoDfe;
use Mg\Usuario\Usuario;

class DistribuicaoDfeEvento extends MgModel
{
    protected $table = 'tbldistribuicaodfeevento';
    protected $primaryKey = 'coddistribuicaodfeevento';


    protected $fillable = [
        'cnpj',
        'coddfeevento',
        'coddistribuicaodfe',
        'cpf',
        'data',
        'descricao',
        'nfechave',
        'orgao',
        'protocolo',
        'recebimento',
        'sequencia'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'data',
        'recebimento'
    ];

    protected $casts = [
        'cnpj' => 'float',
        'coddfeevento' => 'integer',
        'coddistribuicaodfe' => 'integer',
        'coddistribuicaodfeevento' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'cpf' => 'float',
        'orgao' => 'integer',
        'sequencia' => 'integer'
    ];


    // Chaves Estrangeiras
    public function DfeEvento()
    {
        return $this->belongsTo(DfeEvento::class, 'coddfeevento', 'coddfeevento');
    }

    public function DistribuicaoDfe()
    {
        return $this->belongsTo(DistribuicaoDfe::class, 'coddistribuicaodfe', 'coddistribuicaodfe');
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