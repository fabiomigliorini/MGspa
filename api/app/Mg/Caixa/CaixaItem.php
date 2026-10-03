<?php

namespace Mg\Caixa;

use Mg\ContaContabil\ContaContabil;
use Mg\Filial\Filial;
use Mg\MgModel;
use Mg\Pessoa\Pessoa;
use Mg\Usuario\Usuario;

/**
 * Item do caixa (M13 doc-3): mercadoria de parceiro fora do fiscal que passa
 * pela gaveta. Modo C contagem (chips, ingressos: conta-se o estoque na
 * abertura e no fechamento) ou M maquineta/terceiro (Bilhete Agora,
 * Redeflex: vendido, entrada e saida). O liquido da sessao vira titulo de
 * repasse para a pessoa do item.
 */
class CaixaItem extends MgModel
{
    const MODO_CONTAGEM = 'C';
    const MODO_MAQUINETA = 'M';

    const MODOS = [
        self::MODO_CONTAGEM => 'Contagem',
        self::MODO_MAQUINETA => 'Maquineta/terceiro',
    ];

    protected $table = 'tblcaixaitem';
    protected $primaryKey = 'codcaixaitem';

    protected $fillable = [
        'item',
        'modo',
        'codfilial',
        'codpessoa',
        'codcontacontabil',
        'ordem',
        'inativo',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codcaixaitem' => 'integer',
        'codcontacontabil' => 'integer',
        'codfilial' => 'integer',
        'codpessoa' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'ordem' => 'integer',
    ];

    public function ehContagem(): bool
    {
        return $this->modo === static::MODO_CONTAGEM;
    }

    // Chaves Estrangeiras
    public function ContaContabil()
    {
        return $this->belongsTo(ContaContabil::class, 'codcontacontabil', 'codcontacontabil');
    }

    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }

    public function Pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoa', 'codpessoa');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    // Tabelas Filhas
    public function CaixaItemLancamentoS()
    {
        return $this->hasMany(CaixaItemLancamento::class, 'codcaixaitem', 'codcaixaitem');
    }
}
