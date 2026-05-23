<?php

namespace Mg\Pessoa;

use Mg\MgModel;

class Sexo extends MgModel
{
    protected $table = 'tblsexo';
    protected $primaryKey = 'codsexo';

    public $timestamps = false;

    protected $fillable = ['sexo'];

    protected $casts = [
        'codsexo' => 'integer',
    ];
}
