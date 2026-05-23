<?php

namespace Mg\Pessoa;

use Carbon\Carbon;
use Exception;

class PessoaEnderecoService
{
    public static function create(array $data): PessoaEndereco
    {
        if (empty($data['ordem'])) {
            $data['ordem'] = PessoaEndereco::where('codpessoa', $data['codpessoa'])->max('ordem') + 1;
        }
        $end = new PessoaEndereco($data);
        $end->save();
        static::descobreEnderecoNfeCobrancaEntrega($end->Pessoa, $end);
        PessoaService::atualizaCamposLegado($end->Pessoa);
        return $end->refresh();
    }

    public static function createOrUpdate(array $data): PessoaEndereco
    {
        $end = PessoaEndereco::where('codpessoa', $data['codpessoa'])
            ->whereNull('inativo')
            ->orderBy('ordem')
            ->first();
        if ($end) {
            $end = static::update($end, $data);
        } else {
            $end = static::create($data);
        }
        PessoaService::atualizaCamposLegado($end->Pessoa);
        return $end;
    }

    public static function update(PessoaEndereco $end, array $data): PessoaEndereco
    {
        $end->fill($data);
        $end->save();
        static::descobreEnderecoNfeCobrancaEntrega($end->Pessoa, $end);
        PessoaService::atualizaCamposLegado($end->Pessoa);
        return $end;
    }

    public static function delete(PessoaEndereco $end)
    {
        if ($end->Pessoa->PessoaEnderecoS()->count() <= 1) {
            throw new Exception('Não é possivel excluir todos os endereços!', 1);
        }
        $pessoa = $end->Pessoa;
        $ret = $end->delete();
        static::descobreEnderecoNfeCobrancaEntrega($pessoa);
        PessoaService::atualizaCamposLegado($pessoa);
        return $ret;
    }

    public static function descobreEnderecoNfeCobrancaEntrega(Pessoa $pessoa, ?PessoaEndereco $end = null): void
    {
        $codpessoaendereconfe = null;
        $codpessoaenderecocobranca = null;
        $codpessoaenderecoentrega = null;
        if ($end) {
            if ($end->nfe && empty($end->inativo)) {
                $codpessoaendereconfe = $end->codpessoaendereco;
            }
            if ($end->cobranca && empty($end->inativo)) {
                $codpessoaenderecocobranca = $end->codpessoaendereco;
            }
            if ($end->entrega && empty($end->inativo)) {
                $codpessoaenderecoentrega = $end->codpessoaendereco;
            }
        }
        if ($codpessoaendereconfe) {
            $pessoa->PessoaEnderecoS()
                ->where('nfe', true)
                ->where('codpessoaendereco', '!=', $codpessoaendereconfe)
                ->whereNull('inativo')
                ->update(['nfe' => false]);
        }
        if ($codpessoaenderecocobranca) {
            $pessoa->PessoaEnderecoS()
                ->where('cobranca', true)
                ->where('codpessoaendereco', '!=', $codpessoaenderecocobranca)
                ->whereNull('inativo')
                ->update(['cobranca' => false]);
        }

        $nfe = $pessoa->PessoaEnderecoS()->where('nfe', true)->whereNull('inativo')->count();
        if ($nfe == 0) {
            $first = $pessoa->PessoaEnderecoS()->whereNull('inativo')->first();
            $first?->update(['nfe' => true]);
        }

        $cobranca = $pessoa->PessoaEnderecoS()->where('cobranca', true)->whereNull('inativo')->count();
        if ($cobranca == 0) {
            $first = $pessoa->PessoaEnderecoS()->whereNull('inativo')->first();
            $first?->update(['cobranca' => true]);
        }

        $entrega = $pessoa->PessoaEnderecoS()->where('entrega', true)->whereNull('inativo')->count();
        if ($entrega == 0) {
            $first = $pessoa->PessoaEnderecoS()->whereNull('inativo')->first();
            $first?->update(['entrega' => true]);
        }
    }

    public static function cima(PessoaEndereco $end): PessoaEndereco
    {
        $anterior = $end->ordem - 1;
        $ret = PessoaEndereco::where('codpessoa', $end->codpessoa)
            ->where('ordem', $anterior)
            ->update(['ordem' => $end->ordem]);
        if ($ret > 0) {
            $end->update(['ordem' => $anterior]);
        }
        PessoaService::atualizaCamposLegado($end->Pessoa);
        return $end;
    }

    public static function baixo(PessoaEndereco $end): PessoaEndereco
    {
        $posterior = $end->ordem + 1;
        $ret = PessoaEndereco::where('codpessoa', $end->codpessoa)
            ->where('ordem', $posterior)
            ->update(['ordem' => $end->ordem]);
        if ($ret > 0) {
            $end->update(['ordem' => $posterior]);
        }
        PessoaService::atualizaCamposLegado($end->Pessoa);
        return $end;
    }

    public static function ativar(PessoaEndereco $end): PessoaEndereco
    {
        $end->inativo = null;
        $end->update();
        static::descobreEnderecoNfeCobrancaEntrega($end->Pessoa, $end);
        PessoaService::atualizaCamposLegado($end->Pessoa);
        return $end;
    }

    public static function inativar(PessoaEndereco $end, $date = null): PessoaEndereco
    {
        if (empty($date)) {
            $date = Carbon::now();
        }
        $end->inativo = $date;
        $end->update();
        static::descobreEnderecoNfeCobrancaEntrega($end->Pessoa);
        PessoaService::atualizaCamposLegado($end->Pessoa);
        return $end;
    }
}
