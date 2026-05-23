<?php

namespace Mg\Pdv;

use Exception;

use Mg\Pessoa\Pessoa;
use Mg\Pessoa\PessoaEmail;
use Mg\Pessoa\PessoaService;
use Mg\Pessoa\PessoaTelefone;
use Mg\Pessoa\PessoaEndereco;

class PdvPessoaService
{

    public static function novaPessoa(
        $dados
    ) {
        if ($dados->fisica) {
            $codgrupoeconomico = PessoaService::buscaCodGrupoEconomicoPeloCpf($dados->cnpj);
        } else {
            $codgrupoeconomico = PessoaService::buscaCodGrupoEconomicoPelaRaizCnpj($dados->cnpj);
        }
        $p = new Pessoa([
            'cnpj' => $dados->cnpj,
            'fisica' => $dados->fisica,
            'ie' => $dados->ie??null,
            'pessoa' => $dados->pessoa,
            'fantasia' => $dados->fantasia,
            'codgrupoeconomico' => $codgrupoeconomico,
            'cliente' => true,
            'fornecedor' => false,
            'vendedor' => false,
            'creditobloqueado' => true,
            'notafiscal' => 0,
        ]);
        if (!$p->save()) {
            throw new Exception("Falha ao salvar Pessoa!");
        }
        $ordem = 1;
        foreach ($dados->emails as $email) {
            $pe = new PessoaEmail([
                "codpessoa" => $p->codpessoa,
                "ordem" => $ordem,
                "email" => $email,
                "nfe" => true,
                "cobranca" => true,
            ]);
            if (!$pe->save()) {
                throw new Exception("Falha ao salvar Email!");
            }
            $ordem++;
        }

        $ordem = 1;
        foreach ($dados->telefones as $tel) {
            // $pt = PessoaTelefone::limit(1)->get();
            // dd($pt);
            if (in_array($tel['tipo'], [1, 2])) {
                [$ddd, $numero] = explode(')', $tel['numero']);
                $ddd = numeroLimpo($ddd);
                $numero = numeroLimpo($numero);
            } else {
                $ddd = null;
                $numero = $tel['numero'];
            }
            $pt = new PessoaTelefone([
                "codpessoa" => $p->codpessoa,
                "ordem" => $ordem,
                "tipo" => $tel['tipo'],
                "pais" => "55",
                "ddd" => $ddd,
                "telefone" => $numero,
            ]);
            if (!$pt->save()) {
                throw new Exception("Falha ao salvar Telefone!");
            }
            $ordem++;
        }

        $ordem = 1;
        foreach ($dados->enderecos as $end) {
            $pe = new PessoaEndereco([
                "codpessoa" => $p->codpessoa,
                "ordem" => $ordem,
                "nfe" => true,
                "entrega" => true,
                "cobranca" => true,
                "cep" => numeroLimpo($end['cep']??null),
                "endereco" => $end['endereco']??null,
                "numero" => $end['numero']??null,
                "complemento" => $end['complemento']??null,
                "bairro" => $end['bairro']??null,
                "codcidade" => $end['codcidade'],
            ]);
            if (!$pe->save()) {
                throw new Exception("Falha ao salvar Endereço!");
            }
            $ordem++;
        }
        PessoaService::atualizaCamposLegado($p);
        return $p->refresh();
    }
}
