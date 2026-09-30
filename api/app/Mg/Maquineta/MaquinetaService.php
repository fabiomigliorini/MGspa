<?php

namespace Mg\Maquineta;

use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Mg\Negocio\NegocioFormaPagamento;
use Mg\PagarMe\PagarMePos;
use Mg\Saurus\SaurusPdv;
use Mg\Saurus\SaurusPinPad;
use Mg\Usuario\Autorizador;

class MaquinetaService
{
    const RELACOES = ['Filial', 'Pessoa', 'SaurusPinPad'];

    // Admin e Financeiro em qualquer filial; Gerente só na própria
    public static function podeGerenciar(?int $codfilial): bool
    {
        if (Autorizador::pode(['Financeiro'])) {
            return true;
        }
        return !empty($codfilial) && Autorizador::pode(['Gerente'], $codfilial);
    }

    public static function autorizar(?int $codfilial): void
    {
        if (!static::podeGerenciar($codfilial)) {
            abort(403, 'Maquinetas desta filial só com Financeiro, Administrador ou Gerente da filial!');
        }
    }

    public static function listar(array $filtros)
    {
        $q = Maquineta::with(static::RELACOES);

        if (!empty($filtros['codmaquineta'])) {
            $q->where('codmaquineta', $filtros['codmaquineta']);
        }

        if (!empty($filtros['texto'])) {
            $texto = '%' . preg_replace('/\s+/', '%', trim($filtros['texto'])) . '%';
            $q->where(function ($q) use ($texto) {
                $q->where('apelido', 'ilike', $texto)->orWhere('serial', 'ilike', $texto);
            });
        }

        // filial: as dela e as compartilhadas (que também aparecem no PDV dela)
        if (!empty($filtros['codfilial'])) {
            $q->where(function ($q) use ($filtros) {
                $q->where('codfilial', $filtros['codfilial'])->orWhere('compartilhada', true);
            });
        }

        if (!empty($filtros['codpessoa'])) {
            $q->where('codpessoa', $filtros['codpessoa']);
        }

        // integracao: M manual, P PagarMe, S Saurus
        if (!empty($filtros['integracao'])) {
            if ($filtros['integracao'] === 'M') {
                $q->whereNull('integracao');
            } else {
                $q->where('integracao', $filtros['integracao']);
            }
        }

        if (array_key_exists('inativo', $filtros) && $filtros['inativo'] !== null && $filtros['inativo'] !== '') {
            if (filter_var($filtros['inativo'], FILTER_VALIDATE_BOOLEAN)) {
                $q->whereNotNull('inativo');
            } else {
                $q->whereNull('inativo');
            }
        }

        $q->orderBy('codfilial')->orderBy('apelido')->orderBy('codmaquineta');

        return $q->paginate(50);
    }

    // adquirentes que têm maquineta (filtro e formulário do contas)
    public static function adquirentes(): array
    {
        return DB::select('
            select p.codpessoa, p.fantasia, count(*) as maquinetas
            from tblmaquineta m
            inner join tblpessoa p on (p.codpessoa = m.codpessoa)
            group by p.codpessoa, p.fantasia
            order by p.fantasia
        ');
    }

    // Manual ou PagarMe (o POS nasce junto). Saurus nasce do pareamento (parearSaurusPinPad).
    public static function criar(array $dados): Maquineta
    {
        $integracao = $dados['integracao'] ?? null;

        if ($integracao === Maquineta::INTEGRACAO_PAGARME) {
            $pos = PagarMePos::firstOrNew([
                'codfilial' => $dados['codfilial'],
                'serial' => $dados['serial'],
            ]);
            if ($pos->exists && Maquineta::where('codpagarmepos', $pos->codpagarmepos)->exists()) {
                abort(422, 'Já existe maquineta para este POS PagarMe nesta filial!');
            }
            $pos->apelido = mb_substr($dados['apelido'], 0, 20);
            $pos->inativo = null;
            $pos->save();
            $maquineta = static::daPagarMePos($pos);
            $maquineta->apelido = $dados['apelido'];
            $maquineta->save();
            return $maquineta->fresh(static::RELACOES);
        }

        $maquineta = Maquineta::create([
            'apelido' => $dados['apelido'],
            'serial' => $dados['serial'] ?? null,
            'codfilial' => $dados['codfilial'],
            'compartilhada' => $dados['compartilhada'] ?? false,
            'codpessoa' => $dados['codpessoa'],
        ]);
        return $maquineta->fresh(static::RELACOES);
    }

    // Integradas replicam apelido, serial e filial no POS PagarMe ou no pinpad/PDV Saurus.
    // Adquirente só muda na manual.
    public static function atualizar(Maquineta $maquineta, array $dados): Maquineta
    {
        $maquineta->apelido = $dados['apelido'];
        $maquineta->serial = $dados['serial'] ?? null;
        $maquineta->codfilial = $dados['codfilial'];
        $maquineta->compartilhada = $dados['compartilhada'] ?? false;
        if ($maquineta->ehManual() && !empty($dados['codpessoa'])) {
            $maquineta->codpessoa = $dados['codpessoa'];
        }
        $maquineta->save();

        if ($maquineta->integracao === Maquineta::INTEGRACAO_PAGARME) {
            $pos = $maquineta->PagarMePos;
            $pos->apelido = mb_substr($maquineta->apelido, 0, 20);
            $pos->serial = $maquineta->serial ?? $pos->serial;
            $pos->codfilial = $maquineta->codfilial;
            $pos->save();
        }

        // o serial físico fica só na maquineta: tblsauruspinpad.serial vai como IdPinPad na
        // cobrança (ApiService::functionPedidoCriar) e hoje é nulo
        if ($maquineta->integracao === Maquineta::INTEGRACAO_SAURUS) {
            $pin = $maquineta->SaurusPinPad;
            $pin->apelido = mb_substr($maquineta->apelido, 0, 20);
            $pin->codfilial = $maquineta->codfilial;
            $pin->save();
            if (static::pinpadAtual($pin)) {
                $pdv = $pin->SaurusPdv;
                $pdv->apelido = $maquineta->apelido;
                $pdv->codfilial = $maquineta->codfilial;
                $pdv->save();
            }
        }

        return $maquineta->fresh(static::RELACOES);
    }

    public static function inativar(Maquineta $maquineta): Maquineta
    {
        $agora = Carbon::now();
        $maquineta->inativo = $agora;
        $maquineta->save();

        if ($maquineta->integracao === Maquineta::INTEGRACAO_PAGARME) {
            $maquineta->PagarMePos->update(['inativo' => $agora]);
        }

        if ($maquineta->integracao === Maquineta::INTEGRACAO_SAURUS) {
            $pin = $maquineta->SaurusPinPad;
            $pin->update(['inativo' => $agora]);
            // PDV Saurus sem nenhuma maquineta ativa sai da lista de cobrança
            $outros = Maquineta::whereNull('inativo')
                ->whereIn('codsauruspinpad', SaurusPinPad::where('codsauruspdv', $pin->codsauruspdv)->select('codsauruspinpad'))
                ->exists();
            if (!$outros && $pin->SaurusPdv) {
                $pin->SaurusPdv->update(['inativo' => $agora]);
            }
        }

        return $maquineta->fresh(static::RELACOES);
    }

    public static function ativar(Maquineta $maquineta): Maquineta
    {
        $maquineta->inativo = null;
        $maquineta->save();

        if ($maquineta->integracao === Maquineta::INTEGRACAO_PAGARME) {
            $maquineta->PagarMePos->update(['inativo' => null]);
        }

        if ($maquineta->integracao === Maquineta::INTEGRACAO_SAURUS) {
            $pin = $maquineta->SaurusPinPad;
            $pin->update(['inativo' => null]);
            if ($pin->SaurusPdv) {
                $pin->SaurusPdv->update(['inativo' => null]);
            }
        }

        return $maquineta->fresh(static::RELACOES);
    }

    // Só manual: a integrada é inativada (o webhook/pareamento recriaria)
    public static function excluir(Maquineta $maquineta): void
    {
        if (!$maquineta->ehManual()) {
            abort(422, 'Maquineta integrada não pode ser excluída. Inative ao invés de excluir.');
        }
        try {
            $maquineta->delete();
        } catch (QueryException $e) {
            if (($e->errorInfo[0] ?? null) === '23503') {
                abort(409, 'Maquineta com pagamentos, não pode ser excluída. Junte com a certa ou inative.');
            }
            throw $e;
        }
    }

    // Manual criada por engano (serial digitado errado): os pagamentos vão para a certa,
    // da mesma adquirente, e a errada é excluída.
    public static function juntar(Maquineta $errada, Maquineta $certa): Maquineta
    {
        if (!$errada->ehManual()) {
            abort(422, 'Só maquineta manual pode ser juntada em outra.');
        }
        if ($errada->codmaquineta === $certa->codmaquineta) {
            abort(422, 'Escolha outra maquineta para juntar.');
        }
        if ($errada->codpessoa !== $certa->codpessoa) {
            abort(422, 'Só dá para juntar maquinetas da mesma adquirente.');
        }

        NegocioFormaPagamento::where('codmaquineta', $errada->codmaquineta)
            ->update(['codmaquineta' => $certa->codmaquineta]);
        $errada->delete();

        return $certa->fresh(static::RELACOES);
    }

    // ---- integrações ----

    public static function daPagarMePos(PagarMePos $pos): Maquineta
    {
        return Maquineta::firstOrCreate(
            ['codpagarmepos' => $pos->codpagarmepos],
            [
                'apelido' => $pos->apelido,
                'serial' => $pos->serial,
                'codfilial' => $pos->codfilial,
                'codpessoa' => config('services.pagarme.codpessoa'),
                'integracao' => Maquineta::INTEGRACAO_PAGARME,
                'inativo' => $pos->inativo,
            ]
        );
    }

    public static function daSaurusPinPad(SaurusPinPad $pin): Maquineta
    {
        $pdv = $pin->SaurusPdv;
        return Maquineta::firstOrCreate(
            ['codsauruspinpad' => $pin->codsauruspinpad],
            [
                'apelido' => $pdv->apelido ?? $pin->apelido ?? "Pinpad {$pin->codsauruspinpad}",
                'serial' => $pin->serial,
                'codfilial' => $pdv->codfilial ?? $pin->codfilial,
                'codpessoa' => config('mg.codpessoa_safra'),
                'integracao' => Maquineta::INTEGRACAO_SAURUS,
            ]
        );
    }

    // Pinpad que acabou de ler o QR: a maquineta dele fica ativa e as dos pinpads anteriores
    // do mesmo PDV Saurus são inativadas (o aparelho foi trocado ou pareado de novo).
    public static function parearSaurusPinPad(SaurusPinPad $pin): Maquineta
    {
        $agora = Carbon::now();
        $pin->update(['inativo' => null]);
        if ($pin->SaurusPdv) {
            $pin->SaurusPdv->update(['inativo' => null]);
        }

        $maquineta = static::daSaurusPinPad($pin);
        if ($maquineta->inativo) {
            $maquineta->update(['inativo' => null]);
        }

        $anteriores = SaurusPinPad::where('codsauruspdv', $pin->codsauruspdv)
            ->where('codsauruspinpad', '!=', $pin->codsauruspinpad)
            ->pluck('codsauruspinpad');
        SaurusPinPad::whereIn('codsauruspinpad', $anteriores)->whereNull('inativo')->update(['inativo' => $agora]);
        Maquineta::whereIn('codsauruspinpad', $anteriores)->whereNull('inativo')->update(['inativo' => $agora]);

        return $maquineta->fresh(static::RELACOES);
    }

    // pinpad mais novo do PDV Saurus (o que recebe a cobrança hoje)
    public static function pinpadAtual(SaurusPinPad $pin): bool
    {
        return !SaurusPinPad::where('codsauruspdv', $pin->codsauruspdv)
            ->where('codsauruspinpad', '>', $pin->codsauruspinpad)
            ->exists();
    }

    // ---- PDV ----

    // Serial digitado (PDV antigo): casa por serial + filial (ativa primeiro); senão cria a
    // manual daquela filial com a adquirente do pagamento.
    public static function resolverSerial(string $serial, int $codfilial, int $codpessoa): Maquineta
    {
        $serial = trim($serial);
        $maquineta = Maquineta::where('serial', $serial)
            ->where('codfilial', $codfilial)
            ->orderByRaw('inativo is null desc')
            ->orderBy('codmaquineta', 'desc')
            ->first();
        if ($maquineta) {
            return $maquineta;
        }
        return Maquineta::create([
            'apelido' => mb_substr($serial, 0, 50),
            'serial' => $serial,
            'codfilial' => $codfilial,
            'codpessoa' => $codpessoa,
        ]);
    }

    // A maquineta do parceiro no PDV quando é a única (ex.: acesso de site)
    public static function unicaDoParceiro(int $codpessoa, int $codfilial): ?Maquineta
    {
        $lista = Maquineta::whereNull('inativo')
            ->where('codpessoa', $codpessoa)
            ->where(function ($q) use ($codfilial) {
                $q->where('codfilial', $codfilial)->orWhere('compartilhada', true);
            })
            ->limit(2)
            ->get();
        return $lista->count() === 1 ? $lista->first() : null;
    }

    // Ativas que aparecem no PDV da filial (dela + compartilhadas), para o estoque-local
    public static function paraPdv(int $codfilial): array
    {
        return DB::select('
            select
                m.codmaquineta, m.apelido, m.serial, m.codfilial, m.compartilhada,
                m.codpessoa, trim(p.fantasia) as adquirente, m.integracao, m.codpagarmepos,
                pin.codsauruspdv
            from tblmaquineta m
            inner join tblpessoa p on (p.codpessoa = m.codpessoa)
            left join tblsauruspinpad pin on (pin.codsauruspinpad = m.codsauruspinpad)
            where m.inativo is null
              and (m.codfilial = :codfilial or m.compartilhada)
            order by m.apelido, m.codmaquineta
        ', ['codfilial' => $codfilial]);
    }
}
