<?php
/**
 * Created by php artisan gerador:model.
 * Date: 03/Jun/2026 19:33:41
 */

namespace Mg\Fazenda;

use Mg\MgModel;
use Mg\Grao\MovimentoGrao;
use Mg\Safra\Safra;
use Mg\Fazenda\Talhao;
use Mg\Fazenda\Fazenda;
use Mg\Cultura\Variedade;

class Plantio extends MgModel
{
    protected $table = 'tblplantio';
    protected $primaryKey = 'codplantio';



    protected $fillable = [
        'areaplantada',
        'expectativasacas',
        'hacolhido',
        'codfazenda',
        'codsafra',
        'codtalhao',
        'codvariedade',
        'cor',
        'dataplantio',
        'geometria',
        'inativo',
        'latitude',
        'longitude',
        'talhao'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'areaplantada' => 'float',
        'expectativasacas' => 'float',
        'hacolhido' => 'float',
        'codfazenda' => 'integer',
        'codplantio' => 'integer',
        'codsafra' => 'integer',
        'codtalhao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codvariedade' => 'integer',
        'criacao' => 'datetime',
        'dataplantio' => 'date',
        'geometria' => 'array',
        'inativo' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float'
    ];


    // Chaves Estrangeiras
    public function Safra()
    {
        return $this->belongsTo(Safra::class, 'codsafra', 'codsafra');
    }

    public function Fazenda()
    {
        return $this->belongsTo(Fazenda::class, 'codfazenda', 'codfazenda');
    }

    // Talhao base do qual este plantio foi clonado (opcional; a divisao real
    // do plantio vive nas colunas geometria/talhao deste proprio registro).
    public function Talhao()
    {
        return $this->belongsTo(Talhao::class, 'codtalhao', 'codtalhao');
    }

    public function Variedade()
    {
        return $this->belongsTo(Variedade::class, 'codvariedade', 'codvariedade');
    }


    // Tabelas Filhas
    // Colheita do talhao = movimentos PLANTIO no extrato (colhido = SUM liquido).
    public function MovimentoGraoS()
    {
        return $this->hasMany(MovimentoGrao::class, 'codplantio', 'codplantio');
    }

}
