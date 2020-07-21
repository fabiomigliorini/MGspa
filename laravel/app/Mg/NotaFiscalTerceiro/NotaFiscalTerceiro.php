<?php
/**
 * Created by php artisan gerador:model.
 * Date: 21/Jul/2020 11:47:50
 */

namespace Mg\NotaFiscalTerceiro;

use Mg\MgModel;
use Mg\Dfe\DistribuicaoDfe;
use Mg\NotaFiscalTerceiro\NotaFiscalTerceiroDuplicata;
use Mg\NotaFiscalTerceiro\NotaFiscalTerceiroGrupo;
use Mg\NotaFiscalTerceiro\NotaFiscalTerceiroPagamento;
use Mg\Filial\Filial;
use Mg\NaturezaOperacao\NaturezaOperacao;
use Mg\Negocio\Negocio;
use Mg\NotaFiscal\NotaFiscal;
use Mg\Pessoa\Pessoa;
use Mg\Usuario\Usuario;

class NotaFiscalTerceiro extends MgModel
{

    const INDSITUACAO_AUTORIZADA = 1;
    const INDSITUACAO_DENEGADA = 2;
    const INDSITUACAO_CANCELADA = 3;

    // const INDMANIFESTACAO_SEM = null;
    const INDMANIFESTACAO_REALIZADA = 210200;
    const INDMANIFESTACAO_DESCONHECIDA = 210220;
    const INDMANIFESTACAO_NAOREALIZADA = 210240;
    const INDMANIFESTACAO_CIENCIA = 210210;

    protected $table = 'tblnotafiscalterceiro';
    protected $primaryKey = 'codnotafiscalterceiro';


    protected $fillable = [
        'arquivada',
        'cnpj',
        'codfilial',
        'codnaturezaoperacao',
        'codnegocio',
        'codnotafiscal',
        'codoperacao',
        'codpessoa',
        'cpf',
        'digito',
        'download',
        'emissao',
        'emitente',
        'entrada',
        'finalidade',
        'icmsbase',
        'icmsstbase',
        'icmsstvalor',
        'icmsvalor',
        'ie',
        'indmanifestacao',
        'indsituacao',
        'ipivalor',
        'justificativa',
        'modelo',
        'natop',
        'nfechave',
        'numero',
        'observacoes',
        'protocolo',
        'recebimento',
        'revisada',
        'serie',
        'tipo',
        'valordesconto',
        'valorfrete',
        'valoroutras',
        'valorprodutos',
        'valorseguro',
        'valortotal'
    ];

    protected $dates = [
        'alteracao',
        'arquivada',
        'criacao',
        'emissao',
        'entrada',
        'recebimento',
        'revisada'
    ];

    protected $casts = [
        'cnpj' => 'float',
        'codfilial' => 'integer',
        'codnaturezaoperacao' => 'integer',
        'codnegocio' => 'integer',
        'codnotafiscal' => 'integer',
        'codnotafiscalterceiro' => 'integer',
        'codoperacao' => 'integer',
        'codpessoa' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'cpf' => 'float',
        'download' => 'boolean',
        'finalidade' => 'integer',
        'icmsbase' => 'float',
        'icmsstbase' => 'float',
        'icmsstvalor' => 'float',
        'icmsvalor' => 'float',
        'indmanifestacao' => 'integer',
        'indsituacao' => 'integer',
        'ipivalor' => 'float',
        'modelo' => 'integer',
        'numero' => 'integer',
        'protocolo' => 'integer',
        'serie' => 'integer',
        'tipo' => 'integer',
        'valordesconto' => 'float',
        'valorfrete' => 'float',
        'valoroutras' => 'float',
        'valorprodutos' => 'float',
        'valorseguro' => 'float',
        'valortotal' => 'float'
    ];


    // Chaves Estrangeiras
    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }

    public function NaturezaOperacao()
    {
        return $this->belongsTo(NaturezaOperacao::class, 'codnaturezaoperacao', 'codnaturezaoperacao');
    }

    public function Negocio()
    {
        return $this->belongsTo(Negocio::class, 'codnegocio', 'codnegocio');
    }

    public function NotaFiscal()
    {
        return $this->belongsTo(NotaFiscal::class, 'codnotafiscal', 'codnotafiscal');
    }

    public function Pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoa', 'codpessoa');
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
    public function DistribuicaoDfeS()
    {
        return $this->hasMany(DistribuicaoDfe::class, 'codnotafiscalterceiro', 'codnotafiscalterceiro');
    }

    public function NotaFiscalTerceiroDuplicataS()
    {
        return $this->hasMany(NotaFiscalTerceiroDuplicata::class, 'codnotafiscalterceiro', 'codnotafiscalterceiro');
    }

    public function NotaFiscalTerceiroGrupoS()
    {
        return $this->hasMany(NotaFiscalTerceiroGrupo::class, 'codnotafiscalterceiro', 'codnotafiscalterceiro');
    }

    public function NotaFiscalTerceiroPagamentoS()
    {
        return $this->hasMany(NotaFiscalTerceiroPagamento::class, 'codnotafiscalterceiro', 'codnotafiscalterceiro');
    }

}