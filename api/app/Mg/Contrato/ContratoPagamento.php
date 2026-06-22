<?php
/**
 * Created by php artisan gerador:model.
 * Date: 03/Jun/2026 23:47:42
 */

namespace Mg\Contrato;

use Mg\MgModel;
use Mg\Contrato\Contrato;
use Mg\Portador\Portador;

class ContratoPagamento extends MgModel
{
    protected $table = 'tblcontratopagamento';
    protected $primaryKey = 'codcontratopagamento';

    protected $appends = ['usuariocriacao', 'usuarioalteracao'];

    protected $fillable = [
        'codcontrato',
        'data',
        'inativo',
        'observacao',
        'valor',
        'forma',
        'modo',
        'sacas',
        'datarecebido',
        'valorrecebido',
        'codportador'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codcontrato' => 'integer',
        'codcontratopagamento' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'data' => 'date',
        'inativo' => 'datetime',
        'valor' => 'float',
        'sacas' => 'float',
        'datarecebido' => 'date',
        'valorrecebido' => 'float',
        'codportador' => 'integer'
    ];


    // Chaves Estrangeiras
    public function Contrato()
    {
        return $this->belongsTo(Contrato::class, 'codcontrato', 'codcontrato');
    }

    public function Portador()
    {
        return $this->belongsTo(Portador::class, 'codportador', 'codportador');
    }

}
