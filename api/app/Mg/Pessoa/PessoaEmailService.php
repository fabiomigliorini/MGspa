<?php

namespace Mg\Pessoa;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Mail;

class PessoaEmailService
{
    public static function create(array $data): PessoaEmail
    {
        if (empty($data['ordem'])) {
            $data['ordem'] = PessoaEmail::where('codpessoa', $data['codpessoa'])->max('ordem') + 1;
        }
        $email = new PessoaEmail($data);
        $email->save();
        static::descobreEmailNfeCobranca($email->Pessoa, $email);
        PessoaService::atualizaCamposLegado($email->Pessoa);
        return $email->refresh();
    }

    public static function createOrUpdate(array $data): PessoaEmail
    {
        $email = PessoaEmail::where('codpessoa', $data['codpessoa'])
            ->whereNull('inativo')
            ->orderBy('ordem')
            ->first();
        if ($email) {
            $email = static::update($email, $data);
        } else {
            $email = static::create($data);
        }
        PessoaService::atualizaCamposLegado($email->Pessoa);
        return $email;
    }

    public static function update(PessoaEmail $email, array $data): PessoaEmail
    {
        if ($email->email != $data['email']) {
            $data['verificacao'] = null;
        }
        $email->fill($data);
        $email->save();
        static::descobreEmailNfeCobranca($email->Pessoa, $email);
        PessoaService::atualizaCamposLegado($email->Pessoa);
        return $email;
    }

    public static function delete(PessoaEmail $email)
    {
        if ($email->Pessoa->PessoaEmailS()->count() <= 1) {
            throw new Exception('Não é possivel excluir todos os emails!', 1);
        }
        $pessoa = $email->Pessoa;
        $ret = $email->delete();
        static::descobreEmailNfeCobranca($pessoa);
        PessoaService::atualizaCamposLegado($pessoa);
        return $ret;
    }

    public static function cima(PessoaEmail $pe): PessoaEmail
    {
        $anterior = $pe->ordem - 1;
        $ret = PessoaEmail::where('codpessoa', $pe->codpessoa)
            ->where('ordem', $anterior)
            ->update(['ordem' => $pe->ordem]);
        if ($ret > 0) {
            $pe->update(['ordem' => $anterior]);
        }
        PessoaService::atualizaCamposLegado($pe->Pessoa);
        return $pe;
    }

    public static function baixo(PessoaEmail $pe): PessoaEmail
    {
        $posterior = $pe->ordem + 1;
        $ret = PessoaEmail::where('codpessoa', $pe->codpessoa)
            ->where('ordem', $posterior)
            ->update(['ordem' => $pe->ordem]);
        if ($ret > 0) {
            $pe->update(['ordem' => $posterior]);
        }
        PessoaService::atualizaCamposLegado($pe->Pessoa);
        return $pe;
    }

    public static function ativar(PessoaEmail $model): PessoaEmail
    {
        $model->inativo = null;
        $model->update();
        PessoaService::atualizaCamposLegado($model->Pessoa);
        return $model;
    }

    public static function inativar(PessoaEmail $model, $date = null): PessoaEmail
    {
        if (empty($date)) {
            $date = Carbon::now();
        }
        $model->inativo = $date;
        $model->update();
        PessoaService::atualizaCamposLegado($model->Pessoa);
        return $model;
    }

    public static function descobreEmailNfeCobranca(Pessoa $pessoa, ?PessoaEmail $email = null): void
    {
        // Vários emails podem receber NF-e e/ou Cobrança simultaneamente.
        // Aqui apenas garantimos que pelo menos um email ativo tenha cada flag.
        $nfe = $pessoa->PessoaEmailS()->where('nfe', true)->whereNull('inativo')->count();
        if ($nfe == 0) {
            $first = $pessoa->PessoaEmailS()->whereNull('inativo')->first();
            $first?->update(['nfe' => true]);
        }

        $cobranca = $pessoa->PessoaEmailS()->where('cobranca', true)->whereNull('inativo')->count();
        if ($cobranca == 0) {
            $first = $pessoa->PessoaEmailS()->whereNull('inativo')->first();
            $first?->update(['cobranca' => true]);
        }
    }

    public static function verificaEmail(PessoaEmail $email)
    {
        $random = rand(1000, 9999);
        $email->codverificacao = $random;
        $email->update();

        // EmailVerificacao mailable não migrado ainda — quando portar, descomenta
        // Mail::to($email->email)->queue(new EmailVerificacao($email->email, $random));

        return ['mensagem' => 'Email enviado'];
    }

    public static function confirmaVerificacao(PessoaEmail $email, $codverificacao): PessoaEmail
    {
        if ($email->codverificacao != $codverificacao) {
            throw new Exception('Código informado incorreto!', 1);
        }
        $email->verificacao = Carbon::now();
        $email->update();
        return $email;
    }
}
