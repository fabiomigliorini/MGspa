<?php

namespace Mg\Titulo;

use Carbon\Carbon;
use Mg\Pagamento\Pagamento;
use Mg\Pagamento\PagamentoService;
use Mg\Portador\Portador;

class TituloService
{
    // codigos dos tipos de titulo (renumerados no M8.1 do doc-3: 1xx a
    // receber, 2xx a pagar; ver api/database/tipo_titulo_limpeza.sql)
    const TIPO_DUPLICATA_RECEBER = 100;
    const TIPO_VALE_COLABORADOR = 120;
    const TIPO_DUPLICATA_PAGAR = 200;
    const TIPO_VALE = 210;
    const TIPO_CREDITO_CLIENTE = 212;
    const TIPO_RH = 220;

    // credito que a propria venda gera e sai como vale impresso (vale compras
    // e credito da devolucao). Para PAGAR no PDV vale qualquer titulo com
    // saldo de credito.
    const TIPOS_VALE_PDV = [self::TIPO_VALE, self::TIPO_CREDITO_CLIENTE];

    // Regras de atualização de título em atraso (porta de MGJuros)
    const DIAS_TOLERANCIA_ATRASO = 3;
    const PERCENTUAL_JUROS_MES = 4.0;
    const PERCENTUAL_MULTA = 2.0;

    // $pagamento: titulo que movimenta portador (vale colaborador,
    // adiantamento) nasce com o dinheiro que andou (M8 doc-3)
    public static function criar(array $dados, ?Pagamento $pagamento = null): Titulo
    {
        $tipoTitulo = TipoTitulo::findOrFail($dados['codtipotitulo']);

        // valor -> debito/credito
        $valor = (float)($dados['valor'] ?? 0);
        if ($valor <= 0) {
            throw new \InvalidArgumentException('Valor deve ser maior que zero!');
        }

        $titulo = new Titulo([
            'codtipotitulo'    => $dados['codtipotitulo'],
            'codfilial'        => $dados['codfilial'],
            'codpessoa'        => $dados['codpessoa'],
            'codcontacontabil' => $dados['codcontacontabil'],
            'codportador'      => $dados['codportador'] ?? null,
            'numero'           => !empty($dados['numero']) ? $dados['numero'] : null,
            'fatura'           => $dados['fatura'] ?? null,
            'transacao'        => $dados['transacao'],
            'emissao'          => $dados['emissao'],
            'vencimento'       => $dados['vencimento'],
            'vencimentooriginal' => $dados['vencimentooriginal'] ?? $dados['vencimento'],
            'gerencial'        => !empty($dados['gerencial']),
            'observacao'       => $dados['observacao'] ?? null,
        ]);

        // se nao informou numero, usa data emissao + sufixo (1), (2)... se duplicado;
        // com `sufixo`, o numero informado tambem ganha o sufixo (repasse do
        // item do caixa, M13 doc-3)
        if (empty($titulo->numero) && !empty($titulo->emissao)) {
            $titulo->numero = Carbon::parse($titulo->emissao)->format('Y-m-d');
            self::aplicarSufixoNumero($titulo);
        } elseif (!empty($dados['sufixo'])) {
            self::aplicarSufixoNumero($titulo);
        }

        // sinal conforme a natureza do tipo: a pagar é negativo
        $titulo->valor = $tipoTitulo->ehReceber() ? $valor : -$valor;

        // valida unicidade do numero por pessoa
        self::validarNumeroUnico($titulo);

        self::implantar($titulo, $pagamento);

        return self::carregar($titulo->codtitulo);
    }

    /**
     * Grava um título novo e lança a implantação dele. Todo título nasce por
     * aqui: quem monta o Titulo na mão chama isto no lugar do save().
     *
     * Com $pagamento (título que movimenta portador: vale colaborador,
     * adiantamento), a implantação é o dinheiro que andou: total = valor,
     * ligada ao pagamento e no portador dele.
     */
    public static function implantar(Titulo $titulo, ?Pagamento $pagamento = null): Titulo
    {
        $titulo->save();

        MovimentoTituloService::lancar(
            $titulo,
            MovimentoTituloService::TIPO_IMPLANTACAO,
            (float) $titulo->valor,
            $pagamento ? ['total' => (float) $titulo->valor] : [],
            [
                'codportador'          => $pagamento
                    ? ($pagamento->codportadordestino ?? $pagamento->codportadororigem)
                    : $titulo->codportador,
                'codtituloagrupamento' => $titulo->codtituloagrupamento,
                'transacao'            => $titulo->transacao,
                'codpagamento'         => $pagamento->codpagamento ?? null,
            ]
        );

        return $titulo;
    }

    public static function atualizar(Titulo $titulo, array $dados): Titulo
    {
        $tipoTitulo = TipoTitulo::findOrFail($dados['codtipotitulo'] ?? $titulo->codtipotitulo);

        $geradoAuto = $titulo->geradoAutomaticamente();
        $zerado = (float)$titulo->saldo == 0 && !empty($titulo->codtitulo);

        // valor: bloqueado se gerado auto ou já zerado
        $valorAntigo = (float)$titulo->valor;
        $valorNovo = (float)($dados['valor'] ?? abs($valorAntigo));
        if (!$geradoAuto && !$zerado) {
            if ($valorNovo <= 0) {
                throw new \InvalidArgumentException('Valor deve ser maior que zero!');
            }
        }

        // não pode trocar o tipo por um de outra natureza: viraria o sinal do título
        if ((int)$dados['codtipotitulo'] !== (int)$titulo->codtipotitulo) {
            $antigo = TipoTitulo::find($titulo->codtipotitulo);
            if ($antigo && $antigo->natureza !== $tipoTitulo->natureza) {
                throw new \InvalidArgumentException('Impossível alterar o tipo de título entre A Receber e A Pagar!');
            }
        }

        // Regras de edição (legado MGsis): apenas Numero e Valor são travados.
        // Numero: travado se gerado automaticamente (negocio/agrupamento/maquineta).
        // Valor: travado se gerado automaticamente OU já liquidado/estornado.

        // campos sempre editáveis
        $titulo->observacao = $dados['observacao'] ?? null;
        $titulo->fatura = $dados['fatura'] ?? null;
        $titulo->codcontacontabil = $dados['codcontacontabil'] ?? $titulo->codcontacontabil;
        $titulo->gerencial = !empty($dados['gerencial']);
        $titulo->codpessoa = $dados['codpessoa'] ?? $titulo->codpessoa;
        $titulo->codtipotitulo = $dados['codtipotitulo'] ?? $titulo->codtipotitulo;
        $titulo->codfilial = $dados['codfilial'] ?? $titulo->codfilial;
        // vencimento (data prática): sempre editável até liquidar
        $titulo->vencimento = $dados['vencimento'] ?? $titulo->vencimento;

        // numero, emissao, transacao, vencimentooriginal: travados se gerado automaticamente
        if (!$geradoAuto) {
            if (array_key_exists('numero', $dados)) {
                $titulo->numero = !empty($dados['numero']) ? $dados['numero'] : $titulo->codtitulo;
            }
            $titulo->vencimentooriginal = $dados['vencimentooriginal'] ?? $titulo->vencimentooriginal;
            $titulo->emissao = $dados['emissao'] ?? $titulo->emissao;
            $titulo->transacao = $dados['transacao'] ?? $titulo->transacao;
        }

        // valor: travado se gerado automaticamente ou já zerado
        if (!$geradoAuto && !$zerado) {
            $titulo->valor = $tipoTitulo->ehReceber() ? $valorNovo : -$valorNovo;
        }

        // portador: sempre aceita o que vem do request.
        $titulo->codportador = $dados['codportador'] ?? null;

        // valida filial-portador
        self::validarFilialPortador($titulo);
        // valida unicidade numero
        self::validarNumeroUnico($titulo);

        $titulo->save();

        // mudou o valor: a diferença entra como ajuste
        $diferenca = round((float)$titulo->valor - $valorAntigo, 2);
        if ($diferenca != 0) {
            MovimentoTituloService::lancar(
                $titulo,
                MovimentoTituloService::TIPO_AJUSTE,
                $diferenca,
                [],
                ['codportador' => $titulo->codportador]
            );
        }

        return self::carregar($titulo->codtitulo);
    }

    public static function carregar(int $codtitulo): Titulo
    {
        return Titulo::with([
            'Pessoa:codpessoa,fantasia,pessoa,cnpj,fisica',
            'Filial:codfilial,filial',
            'Portador:codportador,portador,codbanco,codfilial',
            'TipoTitulo:codtipotitulo,tipotitulo,natureza,pagar,receber',
            'ContaContabil:codcontacontabil,contacontabil',
            'UsuarioCriacao:codusuario,usuario',
            'UsuarioAlteracao:codusuario,usuario',
            'NegocioParcela:codnegocioparcela,codnegocio',
            'TituloAgrupamento:codtituloagrupamento,emissao',
            'MovimentoTituloS' => function ($q) {
                $q->orderBy('criacao')->orderBy('codmovimentotitulo')
                    ->with([
                        'TipoMovimentoTitulo:codtipomovimentotitulo,tipomovimentotitulo',
                        'Portador:codportador,portador',
                        'Pagamento:codpagamento,codnegocio',
                        'UsuarioCriacao:codusuario,usuario',
                    ]);
            },
            'TituloNfeTerceiroS',
            'TituloBoletoS' => function ($q) {
                $q->orderBy('criacao')
                    ->with(['Portador:codportador,portador,codbanco']);
            },
        ])->findOrFail($codtitulo);
    }

    // Título que nasceu com dinheiro (vale colaborador, adiantamento): o
    // estorno devolve o total ao portador e cancela o pagamento
    // $cancelarPagamento = false: o titulo sai, o pagamento que nasceu com ele
    // fica (desamarrar, PagamentoTituloService::desamarrar)
    public static function estornar(Titulo $titulo, ?string $justificativa = null, bool $cancelarPagamento = true)
    {
        if (!empty($titulo->estornado)) {
            abort(422, 'Titulo já está estornado!');
        }

        // só pode estornar título não movimentado
        if (round((float)$titulo->valor, 2) != round((float)$titulo->saldo, 2)) {
            abort(422, 'Impossível estornar um título movimentado!');
        }

        $implantacao = $titulo->MovimentoTituloS()->orderBy('codmovimentotitulo')->first();
        $pagamento = optional($implantacao)->Pagamento;

        MovimentoTituloService::lancar(
            $titulo,
            MovimentoTituloService::TIPO_ESTORNO_IMPLANTACAO,
            -1 * (float)$titulo->saldo,
            $pagamento ? ['total' => -1 * (float) $implantacao->total] : [],
            [
                'codmovimentotituloestorno' => optional($implantacao)->codmovimentotitulo,
                'codtituloagrupamento'      => $titulo->codtituloagrupamento,
                'codportador'               => $pagamento ? $implantacao->codportador : $titulo->codportador,
                'codpagamento'              => $pagamento->codpagamento ?? null,
            ]
        );
        if ($pagamento && $cancelarPagamento) {
            PagamentoService::cancelar($pagamento, $justificativa ?: "Estorno do título {$titulo->numero}");
        }
        return self::carregar($titulo->codtitulo);
    }

    private static function validarFilialPortador(Titulo $titulo): void
    {
        if (empty($titulo->codfilial) || empty($titulo->codportador)) return;
        $portador = Portador::find($titulo->codportador);
        if (!$portador || empty($portador->codfilial)) return;
        if ((int)$portador->codfilial !== (int)$titulo->codfilial) {
            throw new \InvalidArgumentException("Este portador só é válido para a filial #{$portador->codfilial}!");
        }
    }

    private static function validarNumeroUnico(Titulo $titulo): void
    {
        if (empty($titulo->numero) || empty($titulo->codtipotitulo) || empty($titulo->codpessoa)) return;
        $q = Titulo::where('codpessoa', $titulo->codpessoa)
            ->where('numero', $titulo->numero);
        if (!empty($titulo->codtitulo)) {
            $q->where('codtitulo', '<>', $titulo->codtitulo);
        }
        $outro = $q->select('codtitulo')->first();
        if ($outro) {
            throw new \InvalidArgumentException("Número {$titulo->numero} já utilizado no título #{$outro->codtitulo}!");
        }
    }

    private static function aplicarSufixoNumero(Titulo $titulo): void
    {
        if (empty($titulo->numero) || empty($titulo->codpessoa)) return;
        $base = $titulo->numero;
        $i = 0;
        while ($i < 9999) {
            $candidato = $i === 0 ? $base : "{$base} ({$i})";
            $existe = Titulo::where('codpessoa', $titulo->codpessoa)
                ->where('numero', $candidato)
                ->when(!empty($titulo->codtitulo), fn($q) => $q->where('codtitulo', '<>', $titulo->codtitulo))
                ->exists();
            if (!$existe) {
                $titulo->numero = $candidato;
                return;
            }
            $i++;
        }
    }

    /**
     * Calcula juros, multa e valor atualizado de um título em atraso.
     * Juros de PERCENTUAL_JUROS_MES% ao mês (pro rata die) e multa de
     * PERCENTUAL_MULTA%, aplicados após DIAS_TOLERANCIA_ATRASO dias de atraso.
     */
    public static function calcularAtualizacao($saldo, $vencimento): array
    {
        $saldo = (float)$saldo;
        $vcto = $vencimento ? Carbon::parse($vencimento)->startOfDay() : null;
        $diasAtraso = $vcto ? (int)$vcto->diffInDays(Carbon::now()->startOfDay(), false) : 0;

        $multa = 0.0;
        $juros = 0.0;
        if ($diasAtraso > self::DIAS_TOLERANCIA_ATRASO && $saldo > 0) {
            $multa = round($saldo * (self::PERCENTUAL_MULTA / 100), 2);
            $juros = round($saldo * ((self::PERCENTUAL_JUROS_MES / 30) / 100) * $diasAtraso, 2);
        }

        return [
            'diasatraso'      => $diasAtraso,
            'multa'           => $multa,
            'juros'           => $juros,
            'valoratualizado' => round($saldo + $multa + $juros, 2),
        ];
    }
}
