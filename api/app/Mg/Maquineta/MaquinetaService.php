<?php

namespace Mg\Maquineta;

use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Mg\Pagamento\Pagamento;
use Mg\PagarMe\PagarMePos;
use Mg\Saurus\SaurusPdv;
use Mg\Saurus\SaurusService;
use Mg\Conferencia\ConferenciaAutorizador;

class MaquinetaService
{
    const RELACOES = ['Filial', 'Pessoa', 'SaurusPdv'];

    // Admin e Financeiro em qualquer filial; Gerente só na própria
    public static function podeGerenciar(?int $codfilial): bool
    {
        return ConferenciaAutorizador::pode($codfilial);
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

        return $q->get();
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

    // Manual, PagarMe (o POS nasce junto, só com o código) ou SafraPay (nasce sem PDV Saurus: o
    // Parear, dentro dela, registra o PDV Saurus e o aparelho). Nome, serial e filial ficam só na
    // maquineta.
    public static function criar(array $dados): Maquineta
    {
        $integracao = $dados['integracao'] ?? null;
        $maquineta = new Maquineta([
            'apelido' => $dados['apelido'],
            'serial' => $dados['serial'] ?? null,
            'codfilial' => $dados['codfilial'],
            'compartilhada' => $dados['compartilhada'] ?? false,
            'codpessoa' => $dados['codpessoa'] ?? null,
        ]);

        if ($integracao === Maquineta::INTEGRACAO_PAGARME) {
            $existe = Maquineta::where('integracao', Maquineta::INTEGRACAO_PAGARME)
                ->where('serial', $maquineta->serial)
                ->where('codfilial', $maquineta->codfilial)
                ->exists();
            if ($existe) {
                abort(422, 'Já existe maquineta para este POS PagarMe nesta filial!');
            }
            $maquineta->integracao = Maquineta::INTEGRACAO_PAGARME;
            $maquineta->codpessoa = config('services.pagarme.codpessoa');
            $maquineta->codpagarmepos = PagarMePos::create()->codpagarmepos;
        }

        if ($integracao === Maquineta::INTEGRACAO_SAURUS) {
            $maquineta->integracao = Maquineta::INTEGRACAO_SAURUS;
            $maquineta->codpessoa = config('mg.codpessoa_safra');
        }

        $maquineta->save();
        return $maquineta->fresh(static::RELACOES);
    }

    // Adquirente só muda na manual. Nada é copiado para a operadora (TASK-264).
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
        return $maquineta->fresh(static::RELACOES);
    }

    public static function inativar(Maquineta $maquineta): Maquineta
    {
        $maquineta->inativo = Carbon::now();
        $maquineta->save();
        return $maquineta->fresh(static::RELACOES);
    }

    public static function ativar(Maquineta $maquineta): Maquineta
    {
        $maquineta->inativo = null;
        $maquineta->save();
        return $maquineta->fresh(static::RELACOES);
    }

    // Manual, ou SafraPay que nunca foi pareada (sem PDV Saurus: criada por engano). As outras
    // integradas são inativadas (o webhook da PagarMe recriaria; a SafraPay pareada tem PDV Saurus)
    public static function excluir(Maquineta $maquineta): void
    {
        $nuncaPareada = $maquineta->integracao === Maquineta::INTEGRACAO_SAURUS && !$maquineta->codsauruspdv;
        if (!$maquineta->ehManual() && !$nuncaPareada) {
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

        Pagamento::where('codmaquineta', $errada->codmaquineta)
            ->update(['codmaquineta' => $certa->codmaquineta]);
        $errada->delete();

        return $certa->fresh(static::RELACOES);
    }

    // ---- integrações ----

    // POS que o webhook da PagarMe trouxe e ainda não tem maquineta: nasce com o serial e a
    // filial da conta (PagarMeService::buscaOuCriaPos)
    public static function daPagarMePos(PagarMePos $pos, string $serial, int $codfilial): Maquineta
    {
        return Maquineta::firstOrCreate(
            ['codpagarmepos' => $pos->codpagarmepos],
            [
                'apelido' => mb_substr($serial, 0, 50),
                'serial' => $serial,
                'codfilial' => $codfilial,
                'codpessoa' => config('services.pagarme.codpessoa'),
                'integracao' => Maquineta::INTEGRACAO_PAGARME,
            ]
        );
    }

    // Parear, passo 1: o PDV Saurus da maquineta (registra na Saurus com a filial dela, ou renova
    // o mesmo), cuja chavepublica vira o QR Code que o aparelho lê
    public static function qrCode(Maquineta $maquineta): SaurusPdv
    {
        static::exigirSaurus($maquineta);
        $pdv = SaurusService::registrarPdv($maquineta);
        if ($maquineta->codsauruspdv !== $pdv->codsauruspdv) {
            $maquineta->codsauruspdv = $pdv->codsauruspdv;
            $maquineta->save();
        }
        return $pdv;
    }

    // Parear, passo 2: o aparelho leu o QR Code — ele vira o aparelho ativo da maquineta
    public static function confirmarLeitura(Maquineta $maquineta): Maquineta
    {
        static::exigirSaurus($maquineta);
        if (!$maquineta->SaurusPdv) {
            abort(422, 'Gere o QR Code antes de confirmar a leitura.');
        }
        if (!SaurusService::verificarLeitura($maquineta->SaurusPdv)) {
            abort(422, 'O pinpad ainda não leu o QR Code. Leia na maquininha e confirme de novo.');
        }
        return $maquineta->fresh(static::RELACOES);
    }

    private static function exigirSaurus(Maquineta $maquineta): void
    {
        if ($maquineta->integracao !== Maquineta::INTEGRACAO_SAURUS) {
            abort(422, 'Só maquineta SafraPay integrada é pareada pelo QR Code.');
        }
        if ($maquineta->inativo) {
            abort(422, 'Ative a maquineta antes de parear.');
        }
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

    // Ativas que o cartão do wizard oferece (estoque local do PDV e contas): todas, de todas as
    // filiais — o entregador sai com a maquineta de outra loja e o registro tem que bater com a
    // realidade. As da filial (e compartilhadas) primeiro; as outras com `outrafilial`.
    public static function paraPdv(int $codfilial): array
    {
        return DB::select('
            select
                m.codmaquineta, m.apelido, m.serial, m.codfilial, f.filial, m.compartilhada,
                (m.codfilial <> :codfilial and not m.compartilhada) as outrafilial,
                m.codpessoa, trim(p.fantasia) as adquirente, m.integracao, m.codpagarmepos,
                m.codsauruspdv
            from tblmaquineta m
            inner join tblpessoa p on (p.codpessoa = m.codpessoa)
            inner join tblfilial f on (f.codfilial = m.codfilial)
            where m.inativo is null
            order by (m.codfilial <> :codfilial2 and not m.compartilhada), m.apelido, m.codmaquineta
        ', ['codfilial' => $codfilial, 'codfilial2' => $codfilial]);
    }
}
