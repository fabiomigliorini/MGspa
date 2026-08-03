<?php
/**
 * PessoaCartao — cartao da pessoa: Beneficio (Bee) ou Corporativo.
 *
 * O titular e' a PESSOA, nao o vinculo: isso permite cartao em nome de um
 * colaborador ou de uma FILIAL (toda filial e' uma pessoa cadastrada, via
 * tblfilial.codpessoa). Quem garante que a pessoa e' colaborador ou filial
 * e' o PessoaCartaoController.
 *
 * ATENCAO: model MANTIDO A' MAO. NAO regenerar com `gerador:model`.
 * A tabela NAO esta' registrada no IndiceModels.json de proposito — assim
 * `gerador:model --all` nunca a sobrescreve. O gerador nao conhece o cast
 * `encrypted` nem os accessors abaixo; regenerar faria o numero do cartao
 * trafegar em claro (ciphertext cru) na API. Mexer no schema => editar aqui.
 *
 * ATENCAO 2: `codpessoacartao` (a PK daqui) tambem existe como coluna em
 * tblliquidacaotitulo, onde significa outra coisa — la' e' um codpessoa (o
 * titular do cartao de credito da liquidacao) e NAO e' FK desta tabela.
 */

namespace Mg\Pessoa;

use Mg\MgModel;

class PessoaCartao extends MgModel
{
    protected $table = 'tblpessoacartao';
    protected $primaryKey = 'codpessoacartao';

    // Tipo do cartao (coluna tipo)
    const TIPO_BENEFICIO = 'B';    // cartao-beneficio (Bee) do colaborador
    const TIPO_CORPORATIVO = 'C';  // cartao corporativo (colaborador ou filial)

    const TIPO_DESCRICAO = [
        self::TIPO_BENEFICIO => 'Benefício',
        self::TIPO_CORPORATIVO => 'Corporativo',
    ];

    protected $fillable = [
        'codpessoa',
        'tipo',
        'numero',
        'validademes',
        'validadeano',
        'email',
        'observacao',
        'inativo',
    ];

    // O numero cru NUNCA serializa (toArray/toJson) — nem se alguem devolver o
    // model direto, sem passar pelo PessoaCartaoResource. A leitura em PHP
    // ($reg->numero) continua funcionando p/ o accessor e o assertNumeroInedito.
    protected $hidden = [
        'numero',
    ];

    protected $casts = [
        'codpessoacartao' => 'integer',
        'codpessoa' => 'integer',
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
    protected $appends = ['validade', 'tipo_descricao'];

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

    public function getTipoDescricaoAttribute()
    {
        return self::TIPO_DESCRICAO[$this->tipo] ?? $this->tipo;
    }

    // Chaves Estrangeiras
    public function Pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoa', 'codpessoa');
    }
}
