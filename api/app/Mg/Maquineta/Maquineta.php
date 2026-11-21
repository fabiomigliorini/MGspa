<?php

namespace Mg\Maquineta;

use Mg\MgModel;
use Mg\Filial\Filial;
use Mg\Pagamento\Pagamento;
use Mg\PagarMe\PagarMePos;
use Mg\Pessoa\Pessoa;
use Mg\Saurus\SaurusPdv;
use Mg\Usuario\Usuario;

/**
 * Terminal de cartão (cadastro único, M3 do plano doc-3).
 * Manual (integracao nula), PagarMe (P, um por POS) ou Saurus (S, um por PDV Saurus,
 * tblsauruspdv; os aparelhos pareados ficam na tblsauruspinpad). Nome, serial, filial e inativo moram
 * só aqui (TASK-264): as tabelas da operadora guardam só o que é dela.
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
        'codsauruspdv',
        'inativo',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codfilial' => 'integer',
        'codmaquineta' => 'integer',
        'codpagarmepos' => 'integer',
        'codpessoa' => 'integer',
        'codsauruspdv' => 'integer',
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

    public function SaurusPdv()
    {
        return $this->belongsTo(SaurusPdv::class, 'codsauruspdv', 'codsauruspdv');
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
    public function PagamentoS()
    {
        return $this->hasMany(Pagamento::class, 'codmaquineta', 'codmaquineta');
    }
}
