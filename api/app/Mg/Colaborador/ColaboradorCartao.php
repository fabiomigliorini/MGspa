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

    // O numero cru NUNCA serializa (toArray/toJson) — nem se alguem devolver o
    // model direto, sem passar pelo ColaboradorCartaoResource. A leitura em PHP
    // ($reg->numero) continua funcionando p/ o accessor e o assertNumeroInedito.
    protected $hidden = [
        'numero',
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

    // Nunca retornar '' nos accessors abaixo: o hook saving() do MgModel
    // converte '' em null e injetaria uma coluna inexistente no INSERT.
    // numero_mascarado NAO entra no $appends de proposito — quem expoe a
    // mascara e' o Resource, sob a chave `numero` (o numero cru e' $hidden).
    protected $appends = ['validade'];

    // Mascara de exibicao: primeiros 4 + ultimos 4 ("1234 **** **** 1234"), o
    // que o PCI DSS permite mostrar (o limite dele e' primeiros 6 + ultimos 4).
    // Vem pronta do back — o front so' imprime a string. O accessor NAO pode se
    // chamar `numero`: sequestraria a leitura interna ($reg->numero), que o
    // assertNumeroInedito usa pra achar cartao duplicado no cadastro.
    public function getNumeroMascaradoAttribute()
    {
        $n = preg_replace('/\D/', '', (string) $this->numero);
        if ($n === '') {
            return null;
        }
        if (strlen($n) <= 8) {
            return '**** **** ' . substr($n, -4);
        }
        return substr($n, 0, 4) . ' **** **** ' . substr($n, -4);
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
