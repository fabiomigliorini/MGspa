<?php

namespace Mg\Requisicao;

use Mg\MgModel;
use Mg\Produto\ProdutoBarra;
use Mg\Usuario\Usuario;

class RequisicaoItem extends MgModel
{
    const MOTIVO_SISTEMA_ERRADO = 'E';
    const MOTIVO_AVARIADO = 'A';
    const MOTIVO_NAO_LOCALIZADO = 'N';
    const MOTIVO_ENVIADO_A_MAIS = 'M';
    const MOTIVO_SEM_SALDO = 'S';

    const MOTIVOS = [
        self::MOTIVO_SISTEMA_ERRADO => 'Estoque do sistema errado',
        self::MOTIVO_AVARIADO => 'Avariado / vencido',
        self::MOTIVO_NAO_LOCALIZADO => 'Não localizado',
        self::MOTIVO_ENVIADO_A_MAIS => 'Enviado a mais',
        self::MOTIVO_SEM_SALDO => 'Sem saldo',
    ];

    protected $table = 'tblrequisicaoitem';
    protected $primaryKey = 'codrequisicaoitem';

    protected $fillable = [
        'codrequisicao',
        'codprodutobarra',
        'quantidade',
        'motivo',
        'codprodutobarrasubstituto',
        'observacao',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codprodutobarra' => 'integer',
        'codprodutobarrasubstituto' => 'integer',
        'codrequisicao' => 'integer',
        'codrequisicaoitem' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'quantidade' => 'float',
        'quantidadeatendida' => 'float',
    ];

    // Chaves Estrangeiras
    public function Requisicao()
    {
        return $this->belongsTo(Requisicao::class, 'codrequisicao', 'codrequisicao');
    }

    public function ProdutoBarra()
    {
        return $this->belongsTo(ProdutoBarra::class, 'codprodutobarra', 'codprodutobarra');
    }

    public function ProdutoBarraSubstituto()
    {
        return $this->belongsTo(ProdutoBarra::class, 'codprodutobarrasubstituto', 'codprodutobarra');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }
}
