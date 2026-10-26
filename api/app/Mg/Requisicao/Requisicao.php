<?php

namespace Mg\Requisicao;

use Mg\MgModel;
use Mg\PgArray;
use Mg\Estoque\EstoqueLocal;
use Mg\Negocio\Negocio;
use Mg\Pdv\Pdv;
use Mg\Pessoa\Pessoa;
use Mg\Usuario\Usuario;

/**
 * Requisicao entre lojas (TASK-55, doc-6): a loja requisitante pede, a
 * requisitada atende com negocios de transferencia do PDV. O status e' gravado
 * so' pelo RequisicaoProcessamentoService. O codigo de retirada so' vai para o
 * lado de quem pediu (RequisicaoResource).
 */
class Requisicao extends MgModel
{
    const ATENDIMENTO_LOJA = 'L';
    const ATENDIMENTO_COMPRAS = 'C';

    const DESTINO_RETIRA = 'R';
    const DESTINO_ENTREGA = 'E';
    const DESTINO_LOJA = 'L';

    const DESTINOS = [
        self::DESTINO_RETIRA => 'Cliente retira',
        self::DESTINO_ENTREGA => 'Entregar no endereço',
        self::DESTINO_LOJA => 'Reposição da loja',
    ];

    const STATUS_AGUARDANDO = 'A';
    const STATUS_EM_SEPARACAO = 'S';
    const STATUS_PARCIAL = 'P';
    const STATUS_TOTAL = 'T';
    const STATUS_DIVERGENCIA = 'D';
    const STATUS_ENTREGUE = 'E';
    const STATUS_CANCELADA = 'C';

    const STATUS = [
        self::STATUS_AGUARDANDO => 'Aguardando',
        self::STATUS_EM_SEPARACAO => 'Em separação',
        self::STATUS_PARCIAL => 'Separado parcial',
        self::STATUS_TOTAL => 'Separado total',
        self::STATUS_DIVERGENCIA => 'Separado com divergências',
        self::STATUS_ENTREGUE => 'Entregue',
        self::STATUS_CANCELADA => 'Cancelada',
    ];

    // o numero ordena o painel (a mais urgente primeiro)
    const PRIORIDADE_BAIXA = 1;
    const PRIORIDADE_NORMAL = 2;
    const PRIORIDADE_ALTA = 3;
    const PRIORIDADE_URGENTE = 4;

    const PRIORIDADES = [
        self::PRIORIDADE_BAIXA => 'Baixa',
        self::PRIORIDADE_NORMAL => 'Normal',
        self::PRIORIDADE_ALTA => 'Alta',
        self::PRIORIDADE_URGENTE => 'Urgente',
    ];

    protected $table = 'tblrequisicao';
    protected $primaryKey = 'codrequisicao';

    protected $fillable = [
        'codestoquelocalrequisitante',
        'codestoquelocalrequisitada',
        'atendimento',
        'destino',
        'codpessoa',
        'nomes',
        'telefones',
        'endereco',
        'codnegocio',
        'observacoes',
    ];

    protected $hidden = ['codigoretirada'];

    protected $casts = [
        'alteracao' => 'datetime',
        'cancelamento' => 'datetime',
        'codestoquelocalrequisitada' => 'integer',
        'codestoquelocalrequisitante' => 'integer',
        'codnegocio' => 'integer',
        'codpdv' => 'integer',
        'codpessoa' => 'integer',
        'codrequisicao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocancelamento' => 'integer',
        'codusuariocriacao' => 'integer',
        'codusuariofinalizacao' => 'integer',
        'criacao' => 'datetime',
        'finalizacao' => 'datetime',
        'nomes' => PgArray::class,
        'prioridade' => 'integer',
        'telefones' => PgArray::class,
    ];

    // Chaves Estrangeiras
    public function EstoqueLocalRequisitante()
    {
        return $this->belongsTo(EstoqueLocal::class, 'codestoquelocalrequisitante', 'codestoquelocal');
    }

    public function EstoqueLocalRequisitada()
    {
        return $this->belongsTo(EstoqueLocal::class, 'codestoquelocalrequisitada', 'codestoquelocal');
    }

    public function Pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoa', 'codpessoa');
    }

    public function Negocio()
    {
        return $this->belongsTo(Negocio::class, 'codnegocio', 'codnegocio');
    }

    public function Pdv()
    {
        return $this->belongsTo(Pdv::class, 'codpdv', 'codpdv');
    }

    public function UsuarioFinalizacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariofinalizacao', 'codusuario');
    }

    public function UsuarioCancelamento()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocancelamento', 'codusuario');
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
    public function RequisicaoItemS()
    {
        return $this->hasMany(RequisicaoItem::class, 'codrequisicao', 'codrequisicao');
    }
}
