<?php

namespace Mg\Titulo;

use Carbon\Carbon;
use Mg\Portador\Portador;

class TituloService
{
    const TIPO_VALE = 3;
    const TIPO_RH = 952;

    public static function criar(array $dados): Titulo
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

        // se nao informou numero, usa data emissao + sufixo (1), (2)... se duplicado
        if (empty($titulo->numero) && !empty($titulo->emissao)) {
            $titulo->numero = Carbon::parse($titulo->emissao)->format('Y-m-d');
            self::aplicarSufixoNumero($titulo);
        }

        // débito/crédito conforme tipotitulo
        if ($tipoTitulo->credito) {
            $titulo->credito = $valor;
            $titulo->debito = 0;
        } else {
            $titulo->credito = 0;
            $titulo->debito = $valor;
        }

        $titulo->sistema = Carbon::now();

        // valida unicidade do numero por pessoa
        self::validarNumeroUnico($titulo);

        $titulo->save();

        return self::carregar($titulo->codtitulo);
    }

    public static function atualizar(Titulo $titulo, array $dados): Titulo
    {
        $tipoTitulo = TipoTitulo::findOrFail($dados['codtipotitulo'] ?? $titulo->codtipotitulo);

        $geradoAuto = !empty($titulo->codnegocioformapagamento) || !empty($titulo->codtituloagrupamento);
        $zerado = (float)$titulo->saldo == 0 && !empty($titulo->codtitulo);

        // valor: bloqueado se gerado auto ou já zerado
        $valorNovo = (float)($dados['valor'] ?? abs((float)$titulo->debito - (float)$titulo->credito));
        if (!$geradoAuto && !$zerado) {
            if ($valorNovo <= 0) {
                throw new \InvalidArgumentException('Valor deve ser maior que zero!');
            }
        }

        // não pode trocar tipo entre crédito e débito
        if ((int)$dados['codtipotitulo'] !== (int)$titulo->codtipotitulo) {
            $antigo = TipoTitulo::find($titulo->codtipotitulo);
            if ($antigo && (
                (bool)$antigo->credito !== (bool)$tipoTitulo->credito ||
                (bool)$antigo->debito !== (bool)$tipoTitulo->debito
            )) {
                throw new \InvalidArgumentException('Impossível alterar o tipo de título entre Débito e Crédito!');
            }
        }

        // Regras de edição (legado MGsis): apenas Numero e Valor são travados.
        // Numero: travado se gerado automaticamente (negocio/agrupamento).
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
            if ($tipoTitulo->credito) {
                $titulo->credito = $valorNovo;
                $titulo->debito = 0;
            } else {
                $titulo->credito = 0;
                $titulo->debito = $valorNovo;
            }
        }

        // portador: sempre aceita o que vem do request.
        $titulo->codportador = $dados['codportador'] ?? null;

        // valida filial-portador
        self::validarFilialPortador($titulo);
        // valida unicidade numero
        self::validarNumeroUnico($titulo);

        $titulo->save();

        return self::carregar($titulo->codtitulo);
    }

    public static function carregar(int $codtitulo): Titulo
    {
        return Titulo::with([
            'Pessoa:codpessoa,fantasia,pessoa,cnpj,fisica',
            'Filial:codfilial,filial',
            'Portador:codportador,portador,codbanco,codfilial',
            'TipoTitulo:codtipotitulo,tipotitulo,credito,debito,pagar,receber',
            'ContaContabil:codcontacontabil,contacontabil',
            'UsuarioCriacao:codusuario,usuario',
            'UsuarioAlteracao:codusuario,usuario',
            'NegocioFormaPagamento:codnegocioformapagamento,codnegocio',
            'TituloAgrupamento:codtituloagrupamento,emissao',
            'MovimentoTituloS' => function ($q) {
                $q->orderBy('criacao')->orderBy('sistema')->orderBy('codmovimentotitulo')
                    ->with([
                        'TipoMovimentoTitulo:codtipomovimentotitulo,tipomovimentotitulo',
                        'Portador:codportador,portador',
                        'NegocioFormaPagamento:codnegocioformapagamento,codnegocio',
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

    public static function estornar(Titulo $titulo)
    {
        if (!empty($titulo->estornado)) {
            throw new \Exception("Titulo já está estornado!", 1);
        }

        // só pode estornar título não movimentado
        if (round((float)$titulo->debito - (float)$titulo->credito, 2) != round((float)$titulo->saldo, 2)) {
            throw new \Exception("Impossível estornar um título movimentado!", 1);
        }

        $mov = new MovimentoTitulo([
            'codtitulo' => $titulo->codtitulo,
            'codtipomovimentotitulo' => MovimentoTituloService::TIPO_ESTORNO_IMPLANTACAO,
            'debito' => $titulo->creditosaldo,
            'credito' => $titulo->debitosaldo,
            'transacao' => date('Y-m-d'),
            'codtituloagrupamento' => $titulo->codtituloagrupamento,
            'codportador' => $titulo->codportador,
            'sistema' => date('Y-m-d H:i:s'),
        ]);
        $mov->save();
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
}
