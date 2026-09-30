<?php

namespace Mg\Maquineta;

use Mg\MgModel;
use Mg\Filial\Filial;
use Mg\Negocio\NegocioFormaPagamento;
use Mg\PagarMe\PagarMePos;
use Mg\Pessoa\Pessoa;
use Mg\Saurus\SaurusPinPad;
use Mg\Usuario\Usuario;

/**
 * Terminal de cartão (cadastro único, M3 do plano doc-3).
 * Manual (integracao nula), PagarMe (P, um por POS) ou Saurus (S, um por pinpad).
 * codpessoa = adquirente; compartilhada = aparece no PDV de todas as filiais.
 */
class Maquineta extends MgModel
{
    const INTEGRACAO_PAGARME = 'P';
    const INTEGRACAO_SAURUS = 'S';

    protected $table = 'tblmaquineta';
    protected $primaryKey = 'codmaquineta';

    protected $fillable = [
        'apelido',
        'serial',
        'codfilial',
        'compartilhada',
        'codpessoa',
        'integracao',
        'codpagarmepos',
        'codsauruspinpad',
        'inativo',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codfilial' => 'integer',
        'codmaquineta' => 'integer',
        'codpagarmepos' => 'integer',
        'codpessoa' => 'integer',
        'codsauruspinpad' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'compartilhada' => 'boolean',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
    ];

    public function ehManual(): bool
    {
        return empty($this->integracao);
    }

    // Chaves Estrangeiras
    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }

    public function Pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoa', 'codpessoa');
    }

    public function PagarMePos()
    {
        return $this->belongsTo(PagarMePos::class, 'codpagarmepos', 'codpagarmepos');
    }

    public function SaurusPinPad()
    {
        return $this->belongsTo(SaurusPinPad::class, 'codsauruspinpad', 'codsauruspinpad');
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
    public function NegocioFormaPagamentoS()
    {
        return $this->hasMany(NegocioFormaPagamento::class, 'codmaquineta', 'codmaquineta');
    }
}
