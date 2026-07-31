<?php
/**
 * ColaboradorCartao — cartao-beneficio (Bee) do colaborador.
 *
 * ATENCAO: model MANTIDO A' MAO. NAO regenerar com `gerador:model`.
 * A tabela NAO esta' registrada no IndiceModels.json de proposito — assim
 * `gerador:model --all` nunca a sobrescreve. O gerador nao conhece o cast
 * `encrypted` nem os accessors abaixo; regenerar faria o numero do cartao
 * trafegar em claro (ciphertext cru) na API. Mexer no schema => editar aqui.
 */

namespace Mg\Colaborador;

use Mg\MgModel;

class ColaboradorCartao extends MgModel
{
    protected $table = 'tblcolaboradorcartao';
    protected $primaryKey = 'codcolaboradorcartao';

    protected $fillable = [
        'codcolaborador',
        'numero',
        'validademes',
        'validadeano',
        'email',
        'observacao',
        'inativo',
    ];

    protected $casts = [
        'codcolaboradorcartao' => 'integer',
        'codcolaborador' => 'integer',
        'numero' => 'encrypted',   // criptografado em repouso; nunca sai em claro (ver Resource)
        'validademes' => 'integer',
        'validadeano' => 'integer',
        'inativo' => 'datetime',
        'criacao' => 'datetime',
        'alteracao' => 'datetime',
        'codusuariocriacao' => 'integer',
        'codusuarioalteracao' => 'integer',
    ];

    // numero_ultimos4/validade sao os UNICOS derivados do cartao que a API
    // expoe (o Resource faz unset do numero cru). Nunca retornar '' aqui: o
    // hook saving() do MgModel converte '' em null e injetaria uma coluna
    // inexistente no INSERT.
    protected $appends = ['numero_ultimos4', 'validade'];

    public function getNumeroUltimos4Attribute()
    {
        $n = preg_replace('/\D/', '', (string) $this->numero);
        if ($n === '') {
            return null;
        }
        return substr($n, -4);
    }

    public function getValidadeAttribute()
    {
        return sprintf('%02d/%02d', $this->validademes, $this->validadeano);
    }

    // Chaves Estrangeiras
    public function Colaborador()
    {
        return $this->belongsTo(Colaborador::class, 'codcolaborador', 'codcolaborador');
    }
}
