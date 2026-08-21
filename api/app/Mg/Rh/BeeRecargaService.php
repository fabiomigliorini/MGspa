<?php

namespace Mg\Rh;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Mg\Filial\Filial;
use Mg\Pessoa\PessoaCartao;
use Mg\Portador\Portador;
use Mg\Titulo\TituloService;

/**
 * Recarga (reposição de saldo) do cartão-benefício Bee.
 *
 * O lote é de uma EMPRESA MÃE num período — é assim que o financeiro sempre
 * fez à mão. A filial gravada no lote é a SEDE da empresa: só o lugar onde o
 * título de adiantamento é lançado, não o conjunto de colaboradores.
 *
 * A prévia é uma QUERY, não um estado: "acertos Bee da empresa no período menos
 * quem já está em lote vivo". Daí saem de graça a idempotência (ninguém é
 * recarregado duas vezes), a recarga complementar (gerar de novo traz só quem
 * entrou depois) e o desfazer (inativar o lote devolve as pessoas à prévia,
 * porque a consulta filtra por `br.inativo IS NULL`).
 *
 * O lote é imutável: a planilha lê os ITENS gravados, nunca recalcula o acerto.
 * Um .xlsx rebaixado meses depois reproduz exatamente o que foi enviado à Bee.
 */
class BeeRecargaService
{
    // Fornecedor e classificação do título de adiantamento. Os três valores
    // vêm dos lançamentos que o financeiro fazia à mão (tbltitulo dos lotes de
    // 30/07/2026) — mudar aqui muda para onde o dinheiro é lançado.
    const CODPESSOA_BEEVALE = 26169;   // Beevale Pagamentos E Beneficios Ltda
    const CODTIPOTITULO_ADTO = 120;    // Adto Fornecedor (credito = f -> debito)
    const CODCONTACONTABIL = 312;      // Vale Alimentacao Colaboradores

    /**
     * Empresas mãe com colaborador no período — uma tab da tela por linha daqui.
     *
     * A empresa vem da filial do VÍNCULO (tblcolaborador.codfilial ->
     * tblfilial.codempresa), não da unidade de negócio onde a pessoa trabalha.
     *
     * Parte do VÍNCULO, não do acerto: como dá para recarregar quem ainda não
     * tem acerto Bee, a aba precisa existir antes de existir acerto nenhum —
     * senão não haveria por onde começar. `total` continua sendo o extrato dos
     * acertos Bee (o que a empresa deve pelo cartão), e vem zero quando não há.
     */
    public static function empresas(int $codperiodo): array
    {
        return DB::select("
            SELECT
                e.codempresa,
                e.empresa,
                COALESCE(SUM(ABS(a.saldo)), 0) AS total
            FROM tblperiodocolaborador pc
            JOIN tblcolaborador col ON col.codcolaborador = pc.codcolaborador
            JOIN tblfilial f        ON f.codfilial = col.codfilial
            JOIN tblempresa e       ON e.codempresa = f.codempresa
            LEFT JOIN tblperiodocolaboradoracerto a
                   ON a.codperiodocolaborador = pc.codperiodocolaborador
                  AND a.inativo IS NULL
                  AND a.forma = :forma
                  AND a.saldo <> 0
            WHERE pc.codperiodo = :codperiodo
            GROUP BY e.codempresa, e.empresa
            ORDER BY e.empresa
        ", [
            'codperiodo' => $codperiodo,
            'forma' => PeriodoColaboradorAcerto::FORMA_BEE,
        ]);
    }

    /**
     * Situação de recarga de cada colaborador da empresa — TODO o quadro do
     * período, tenha acerto Bee ou não.
     *
     * Lista todo mundo porque é daqui que sai a lista do diálogo de nova
     * recarga, e dá para recarregar quem ainda não tem acerto (adiantamento).
     * Quem não tem acerto nem recarga vem com os três valores zerados; é a tela
     * que separa "envolvido na recarga" (extrato ou recarga <> 0) do resto, para
     * os totais não contarem o quadro inteiro.
     *
     * Três valores por linha:
     *   extrato = a que ele tem direito no período (o que a tela de acerto
     *             gravou como "Recarga Bee");
     *   recarga = quanto já entrou em lote vivo;
     *   saldo   = o que falta (extrato - recarga).
     *
     * O saldo é o que substitui o antigo "quem não está em lote": recarga
     * parcial e acerto que cresceu depois voltam a aparecer sozinhos. Quem já
     * está zerado continua listado, com saldo 0 — o RH vê quem foi atendido em
     * vez da linha sumir.
     *
     * O setor é só para exibir/ordenar (vem por LEFT JOIN: o vínculo com a
     * filial não garante setor no período). A filial acompanha cada linha
     * porque uma empresa tem várias, e é ela que decide onde o título é lançado.
     *
     * Uma linha por VÍNCULO (codperiodocolaborador): os vários eventos de acerto
     * Bee do mesmo vínculo entram somados. Atenção: isso NÃO agrupa por pessoa —
     * quem tem dois vínculos na mesma empresa aparece duas vezes, de propósito
     * (são dois registros distintos, e o item do lote aponta para o vínculo).
     * Quem junta os dois num CPF só é a planilha, em linhasDaPlanilha().
     *
     * O acerto entra por LEFT JOIN, não INNER: sem isso uma recarga viva SUMIRIA
     * da tela se o acerto que a originou fosse inativado depois — o item
     * continuaria contando e ninguém veria. Os predicados de `a` têm que ficar
     * no ON; movê-los para o WHERE transforma o LEFT JOIN de volta em INNER.
     */
    public static function previa(int $codperiodo, int $codempresa): array
    {
        $linhas = DB::select("
            SELECT
                pc.codperiodocolaborador,
                p.codpessoa,
                p.pessoa AS nome,
                p.cnpj   AS cpf,
                p.fisica,
                s.codsetor,
                s.setor,
                f.codfilial,
                f.filial,
                COALESCE(SUM(ABS(a.saldo)), 0) AS extrato,
                MAX(r.recarregado) AS recarga,
                COALESCE(SUM(ABS(a.saldo)), 0) - MAX(r.recarregado) AS saldo
            FROM tblperiodocolaborador pc
            JOIN tblcolaborador col ON col.codcolaborador = pc.codcolaborador
            JOIN tblpessoa p        ON p.codpessoa = col.codpessoa
            JOIN tblfilial f        ON f.codfilial = col.codfilial
            LEFT JOIN tblsetor s    ON s.codsetor = pc.codsetor
            LEFT JOIN tblperiodocolaboradoracerto a
                   ON a.codperiodocolaborador = pc.codperiodocolaborador
                  AND a.inativo IS NULL
                  AND a.forma = :forma
                  AND a.saldo <> 0
            LEFT JOIN LATERAL (
                SELECT
                    COALESCE(SUM(i.valor) FILTER (WHERE br.inativo IS NULL), 0) AS recarregado
                FROM tblbeerecargaperiodocolaborador i
                JOIN tblbeerecarga br ON br.codbeerecarga = i.codbeerecarga
                WHERE i.codperiodocolaborador = pc.codperiodocolaborador
            ) r ON true
            WHERE pc.codperiodo = :codperiodo
              AND f.codempresa = :codempresa
            GROUP BY pc.codperiodocolaborador, p.codpessoa, p.pessoa, p.cnpj, p.fisica,
                     s.codsetor, s.setor, f.codfilial, f.filial
            ORDER BY s.setor NULLS FIRST, p.pessoa
        ", [
            'codperiodo' => $codperiodo,
            'codempresa' => $codempresa,
            'forma' => PeriodoColaboradorAcerto::FORMA_BEE,
        ]);

        return static::anexarCartao($linhas);
    }

    /**
     * Anexa cartão-benefício às linhas da prévia — só exibição (o cartão não
     * filtra quem entra no lote), mas o RH precisa ver quem está sem cadastro.
     *
     * Não dá para trazer o número no SQL: a coluna é `encrypted` e só o cast do
     * Eloquent decifra. Daí a segunda consulta e a máscara vindo do accessor.
     */
    protected static function anexarCartao(array $linhas): array
    {
        if (empty($linhas)) {
            return [];
        }

        // groupBy, não keyBy: nada impede dois cartões-benefício ativos na mesma
        // pessoa (um por empresa, por exemplo) e o keyBy descartaria um em
        // silêncio — mostrar um cartão arbitrário a quem está conferindo cartão
        // é mentira. Exibe o mais antigo e conta quantos são.
        $cartoes = PessoaCartao::whereIn('codpessoa', array_unique(array_column($linhas, 'codpessoa')))
            ->where('tipo', PessoaCartao::TIPO_BENEFICIO)
            ->whereNull('inativo')
            ->orderBy('codpessoacartao')
            ->get()
            ->groupBy('codpessoa');

        foreach ($linhas as $l) {
            $daPessoa = $cartoes->get($l->codpessoa);
            $cartao = $daPessoa ? $daPessoa->first() : null;
            $l->extrato = (float) $l->extrato;
            $l->recarga = (float) $l->recarga;
            $l->saldo = (float) $l->saldo;
            $l->cartao = $cartao ? $cartao->numero_mascarado : null;
            $l->validade = $cartao ? $cartao->validade : null;
            $l->cartoes = $daPessoa ? $daPessoa->count() : 0;
            $l->sem_cartao = $cartao === null;
        }

        return $linhas;
    }

    /**
     * Gera o lote da empresa: título de adiantamento + recarga + itens, nesta
     * ordem (cada passo depende da chave gerada pelo anterior).
     *
     * Um lote por empresa mãe — é assim que o financeiro sempre lançou à mão
     * (R$ 12.201,64 Migliorini e R$ 7.943,22 FDF em 30/07/2026). Como a tab é a
     * empresa, nenhum lote mistura CNPJ.
     *
     * Recalcula a prévia aqui dentro de propósito — o front manda só a empresa
     * e, no lote avulso, quem e quanto. Nunca o saldo: ele é lido do banco
     * dentro do lock, então tela desatualizada não paga ninguém duas vezes.
     *
     * Sem $itens é o lote do mês: todo mundo com saldo, pelo saldo cheio.
     * Com $itens é o lote avulso: só as linhas pedidas, cada uma com o valor
     * pedido, revalidado contra o saldo de agora (ver linhasSolicitadas).
     */
    public static function gerar(
        int $codperiodo,
        int $codempresa,
        ?array $itens = null,
        ?string $dia = null,
        ?string $observacao = null,
        ?int $codportador = null
    ): BeeRecarga {
        // Serializa gerações concorrentes do mesmo período (double-submit, duas
        // abas). Sem isto, duas transações leriam a mesma prévia e criariam dois
        // lotes com as mesmas pessoas. Não dá para travar a prévia direto: ela
        // tem GROUP BY, e o Postgres não aceita FOR UPDATE com agregação.
        DB::select('SELECT codperiodo FROM tblperiodo WHERE codperiodo = ? FOR UPDATE', [$codperiodo]);

        // A prévia é lida DEPOIS do lock, sempre. É o único ponto onde o saldo
        // é verdade — os dois caminhos abaixo partem dela.
        $previa = static::previa($codperiodo, $codempresa);

        if ($itens === null) {
            // Só quem ainda tem o que receber. Quem está zerado continua na
            // prévia (a tela mostra o histórico dele), mas não entra em lote
            // novo. Grava o SALDO, não o extrato: numa recarga complementar só
            // a diferença vai para o cartão (e para a planilha).
            $linhas = array_values(array_filter($previa, fn ($l) => $l->saldo > 0));
            foreach ($linhas as $l) {
                $l->valor = $l->saldo;
            }
        } else {
            $linhas = static::linhasSolicitadas($previa, $itens);
        }

        if (empty($linhas)) {
            throw new \Exception('Nenhum colaborador com saldo de recarga nesta empresa.');
        }

        $valor = array_sum(array_column($linhas, 'valor'));
        $hoje = Carbon::today();

        // O portador escolhido manda na filial do título quando ele tem uma:
        // dizer "sai da conta do Depósito" e lançar o título no Botânico seria
        // incoerente, e o TituloService::atualizar recusaria qualquer edição
        // depois. Portador sem filial (Carteira, Programação Pagamentos) cai na
        // heurística do maior valor.
        $portador = static::portadorValidado($codportador, $codempresa);
        $codfilial = $portador && $portador->codfilial
            ? (int) $portador->codfilial
            : static::codfilialDoTitulo($linhas);

        // `dia` é quando o saldo tem que estar no cartão; as datas do título
        // continuam sendo hoje, porque o adiantamento à Beevale é lançado agora.
        // O título nasce EM ABERTO: o portador diz de onde o dinheiro vai sair,
        // e a baixa continua sendo do Financeiro, pela tela de Liquidação.
        $titulo = TituloService::criar([
            'codtipotitulo' => static::CODTIPOTITULO_ADTO,
            'codfilial' => $codfilial,
            'codportador' => $portador ? $portador->codportador : null,
            'codpessoa' => static::CODPESSOA_BEEVALE,
            'codcontacontabil' => static::CODCONTACONTABIL,
            'valor' => $valor,
            'transacao' => $hoje->toDateString(),
            'emissao' => $hoje->toDateString(),
            'vencimento' => $hoje->toDateString(),
            'observacao' => 'Recarga cartão Bee - período ' . $codperiodo
                . ($observacao ? ' - ' . $observacao : ''),
        ]);

        $recarga = new BeeRecarga([
            'codperiodo' => $codperiodo,
            'codfilial' => $codfilial,
            'codtitulo' => $titulo->codtitulo,
            'valor' => $valor,
            'dia' => $dia ?: $hoje->toDateString(),
            'status' => BeeRecarga::STATUS_PENDENTE,
            'exportacao' => Carbon::now(),
            'observacao' => $observacao ?: null,
        ]);
        $recarga->save();

        foreach ($linhas as $l) {
            $item = new BeeRecargaPeriodoColaborador([
                'codbeerecarga' => $recarga->codbeerecarga,
                'codperiodocolaborador' => $l->codperiodocolaborador,
                'valor' => $l->valor,
            ]);
            $item->save();
        }

        // O model recém-inserido não carrega as colunas que o banco/os hooks
        // preencheram (codusuariocriacao entre elas) — sem o refresh o card de
        // auditoria da tela nasceria vazio até um F5.
        return $recarga->refresh();
    }

    /**
     * Em qual filial da empresa o título é lançado.
     *
     * Um título tem UMA filial (tbltitulo.codfilial é NOT NULL) e o lote é de
     * uma empresa inteira, então alguma filial precisa recebê-lo. A escolhida é
     * a que concentra o maior valor do lote — o título fica onde está o dinheiro.
     *
     * Isso reproduz os dois lançamentos manuais do financeiro (Deposito 101 para
     * a Migliorini, FDF 201 para a FDF) sem depender de cadastro novo, e acerta
     * também a Fazenda, cuja operação está em Renascer (402) e não na filial
     * homônima 401 — uma heurística de "menor código" erraria esse caso.
     */
    /**
     * Portador do título, quando o RH escolheu um.
     *
     * O TituloService só valida filial x portador no atualizar(), não no criar()
     * — então a validação tem que vir daqui, senão o lote nasce num estado que a
     * tela de títulos se recusa a salvar depois.
     *
     * A trava que importa é a EMPRESA: o lote é de um CNPJ só, e um portador da
     * filial errada jogaria o adiantamento na empresa errada. Portador sem
     * filial (Carteira, Programação Pagamentos) serve a qualquer uma.
     */
    protected static function portadorValidado(?int $codportador, int $codempresa): ?Portador
    {
        if (empty($codportador)) {
            return null;
        }

        $portador = Portador::find($codportador);
        if (!$portador) {
            throw new \Exception('Portador não encontrado.');
        }
        if ($portador->inativo) {
            throw new \Exception("Portador {$portador->portador} está inativo.");
        }

        if ($portador->codfilial) {
            $filial = Filial::find($portador->codfilial);
            if (!$filial || (int) $filial->codempresa !== $codempresa) {
                throw new \Exception(
                    "Portador {$portador->portador} é de outra empresa — o lote é de um CNPJ só."
                );
            }
        }

        return $portador;
    }

    protected static function codfilialDoTitulo(array $linhas): int
    {
        $porFilial = [];
        foreach ($linhas as $l) {
            $porFilial[$l->codfilial] = ($porFilial[$l->codfilial] ?? 0) + $l->valor;
        }
        arsort($porFilial);

        return (int) array_key_first($porFilial);
    }

    /**
     * Casa o pedido do front com a prévia RECÉM-LIDA dentro do lock.
     *
     * Do payload só saem codperiodocolaborador e valor: extrato, saldo, filial e
     * cartão vêm sempre do banco. Uma aba aberta há uma hora não consegue pagar
     * de novo quem já foi pago, porque o teto é o saldo de agora, não o que ela
     * viu — é isso que substitui, no lote avulso, a recomputação cega do lote
     * do mês.
     *
     * Rejeita, não trunca: o operador baixa a planilha no segundo seguinte e
     * transfere o dinheiro. Mandar em silêncio um valor diferente do que ele
     * aprovou é pior do que um 422 que se resolve com um F5.
     *
     * Falhou uma linha, falha o lote inteiro: o lote tem UM título, então
     * commitar só parte dele seria pagar um total que ninguém aprovou. Junta
     * todos os erros antes de estourar para o RH corrigir de uma vez.
     */
    protected static function linhasSolicitadas(array $previa, array $itens): array
    {
        $porVinculo = [];
        foreach ($previa as $l) {
            $porVinculo[(int) $l->codperiodocolaborador] = $l;
        }

        $linhas = [];
        $erros = [];
        $vistos = [];

        foreach ($itens as $item) {
            $codpc = (int) ($item['codperiodocolaborador'] ?? 0);
            $valor = round((float) ($item['valor'] ?? 0), 2);

            if (!isset($porVinculo[$codpc])) {
                $erros[] = "Colaborador #{$codpc} não está na prévia desta empresa neste período.";
                continue;
            }

            $linha = $porVinculo[$codpc];

            if (isset($vistos[$codpc])) {
                $erros[] = "{$linha->nome}: repetido no lote.";
                continue;
            }
            $vistos[$codpc] = true;

            if ($valor <= 0) {
                $erros[] = "{$linha->nome}: valor deve ser maior que zero.";
                continue;
            }

            // Sem extrato não há direito nenhum, e o direito só nasce de rubrica
            // ou título em aberto, sempre pela via do acerto. Mensagem própria
            // porque o teto abaixo diria apenas "saldo disponível 0,00", que não
            // conta ao RH o que fazer.
            if ($linha->extrato <= 0) {
                $erros[] = sprintf(
                    '%s: sem extrato no período. Lance a rubrica e o acerto antes de recarregar.',
                    $linha->nome
                );
                continue;
            }

            // A recarga ENTREGA um direito que já existe; ela nunca o cria. O
            // teto é o SALDO (extrato menos o que já foi para o cartão), então a
            // soma das recargas de um colaborador nunca passa do que o acerto
            // dele justifica. Recarga parcial segue livre — o que se proíbe é o
            // excedente, que não é reconciliado com a folha nem transportado
            // para o período seguinte. Para entregar mais, sobe-se o extrato
            // pelo acerto, que é onde a decisão do valor pertence.
            //
            // Tolerância de meio centavo, como no EfetivarAcertoRequest: os dois
            // lados arredondam em 2 casas e a comparação não pode falhar por isso.
            if ($valor > $linha->saldo + 0.005) {
                $erros[] = sprintf(
                    '%s: saldo disponível %s, solicitado %s. Para entregar mais, lance o acerto antes.',
                    $linha->nome,
                    number_format($linha->saldo, 2, ',', '.'),
                    number_format($valor, 2, ',', '.')
                );
                continue;
            }

            $l = clone $linha;
            $l->valor = $valor;
            $linhas[] = $l;
        }

        if ($erros) {
            throw new \Exception(implode(' ', $erros));
        }

        return $linhas;
    }

    public static function confirmar(int $codperiodo, int $codbeerecarga): BeeRecarga
    {
        $recarga = BeeRecarga::where('codperiodo', $codperiodo)->findOrFail($codbeerecarga);

        if ($recarga->inativo) {
            throw new \Exception('Recarga inativa não pode ser confirmada.');
        }
        if ($recarga->status == BeeRecarga::STATUS_OK) {
            throw new \Exception('Esta recarga já está confirmada.');
        }

        $recarga->status = BeeRecarga::STATUS_OK;
        $recarga->save();

        return $recarga;
    }

    /**
     * Desfaz a confirmação (OK -> PEND).
     *
     * Existe porque confirmar TRAVA o lote: depois de dizer que o saldo caiu nos
     * cartões, inativar passaria a apagar o registro de um pagamento que já
     * aconteceu. Quem confirmou por engano desconfirma e só então inativa.
     *
     * Desconfirmar em si é sempre seguro — é só o RH dizendo "ainda não conferi".
     */
    public static function desconfirmar(int $codperiodo, int $codbeerecarga): BeeRecarga
    {
        $recarga = BeeRecarga::where('codperiodo', $codperiodo)->findOrFail($codbeerecarga);

        if ($recarga->status != BeeRecarga::STATUS_OK) {
            throw new \Exception('Esta recarga ainda não foi confirmada.');
        }

        $recarga->status = BeeRecarga::STATUS_PENDENTE;
        $recarga->save();

        return $recarga;
    }

    /**
     * Inativa o lote e estorna o adiantamento.
     *
     * Os itens ficam gravados — são o histórico do que foi enviado à operadora.
     * Eles apenas param de contar na prévia, que filtra pelo `inativo` do lote,
     * e por isso os colaboradores voltam a aparecer como pendentes.
     *
     * Só vale para lote PENDENTE. Confirmado significa que o dinheiro está no
     * cartão do colaborador, e dali não volta: inativar faria o sistema esquecer
     * o pagamento e recarregar a mesma pessoa de novo no próximo lote. Corrigir
     * valor de lote confirmado é pelo ACERTO, que deixa o rastro do negativo.
     */
    public static function inativar(int $codperiodo, int $codbeerecarga): BeeRecarga
    {
        $recarga = BeeRecarga::with('Titulo')
            ->where('codperiodo', $codperiodo)
            ->findOrFail($codbeerecarga);

        if ($recarga->inativo) {
            throw new \Exception('Esta recarga já está inativa.');
        }

        if ($recarga->status == BeeRecarga::STATUS_OK) {
            throw new \Exception(
                'Esta recarga já foi confirmada nos cartões. Inativar faria o sistema esquecer um '
                . 'pagamento que já aconteceu, e o colaborador seria recarregado de novo. Se a '
                . 'confirmação foi engano, desconfirme antes; se o valor foi errado, corrija pelo acerto.'
            );
        }

        // O TituloService recusa título movimentado com uma frase que não diz o
        // que fazer. Aqui a causa é sempre a mesma: o Financeiro já liquidou o
        // adiantamento, e o dinheiro saiu do banco.
        try {
            TituloService::estornar($recarga->getRelationValue('Titulo'));
        } catch (\Exception $e) {
            throw new \Exception(
                'O adiantamento à Beevale já foi liquidado pelo Financeiro. Estorne a liquidação '
                . 'antes de inativar a recarga. (' . $e->getMessage() . ')'
            );
        }

        $recarga->inativo = Carbon::now();
        $recarga->save();

        return $recarga;
    }

    /**
     * Linhas da planilha — dos ITENS gravados, nunca do acerto atual.
     *
     * O setor vem junto só para a tabela do card do lote na tela; a planilha
     * ignora a coluna.
     */
    public static function linhasDoLote(int $codbeerecarga): array
    {
        return DB::select("
            SELECT
                p.pessoa AS nome,
                p.cnpj   AS cpf,
                p.fisica,
                i.valor,
                s.setor
            FROM tblbeerecargaperiodocolaborador i
            JOIN tblperiodocolaborador pc ON pc.codperiodocolaborador = i.codperiodocolaborador
            JOIN tblcolaborador col       ON col.codcolaborador = pc.codcolaborador
            JOIN tblpessoa p              ON p.codpessoa = col.codpessoa
            LEFT JOIN tblsetor s          ON s.codsetor = pc.codsetor
            WHERE i.codbeerecarga = :codbeerecarga
            ORDER BY s.setor NULLS FIRST, p.pessoa
        ", ['codbeerecarga' => $codbeerecarga]);
    }

    /**
     * Mesmas linhas, para vários lotes de uma vez — o index() do período monta
     * um card por lote e sem isto faria uma consulta de 4 joins por card.
     *
     * Devolve um mapa codbeerecarga => linhas. Lista IN montada na mão porque
     * DB::select não expande array em bind; o intval em cada elemento é a
     * proteção contra injeção.
     */
    public static function linhasDeLotes(array $codbeerecargas): array
    {
        $ids = array_values(array_unique(array_map('intval', $codbeerecargas)));
        if (empty($ids)) {
            return [];
        }

        $linhas = DB::select("
            SELECT
                i.codbeerecarga,
                p.pessoa AS nome,
                p.cnpj   AS cpf,
                p.fisica,
                i.valor,
                s.setor
            FROM tblbeerecargaperiodocolaborador i
            JOIN tblperiodocolaborador pc ON pc.codperiodocolaborador = i.codperiodocolaborador
            JOIN tblcolaborador col       ON col.codcolaborador = pc.codcolaborador
            JOIN tblpessoa p              ON p.codpessoa = col.codpessoa
            LEFT JOIN tblsetor s          ON s.codsetor = pc.codsetor
            WHERE i.codbeerecarga IN (" . implode(',', $ids) . ")
            ORDER BY s.setor NULLS FIRST, p.pessoa
        ");

        $mapa = array_fill_keys($ids, []);
        foreach ($linhas as $l) {
            $cod = (int) $l->codbeerecarga;
            unset($l->codbeerecarga);
            $mapa[$cod][] = $l;
        }

        return $mapa;
    }

    /**
     * Linhas da PLANILHA — uma por CPF, não por vínculo.
     *
     * É a única diferença em relação a linhasDoLote(): quem tem dois vínculos na
     * mesma empresa gera dois itens (e o card mostra os dois, que é a verdade do
     * que foi gravado), mas a operadora recusa o arquivo com CPF repetido. Aqui
     * eles viram uma linha somada — o total do lote não muda, então o título
     * continua batendo com o arquivo.
     *
     * Agrupa por cnpj, não por codpessoa: dois cadastros com o mesmo CPF (existe
     * em base antiga) também precisam sair numa linha só.
     */
    public static function linhasDaPlanilha(int $codbeerecarga): array
    {
        return DB::select("
            SELECT
                MIN(p.pessoa) AS nome,
                p.cnpj        AS cpf,
                p.fisica,
                SUM(i.valor)  AS valor
            FROM tblbeerecargaperiodocolaborador i
            JOIN tblperiodocolaborador pc ON pc.codperiodocolaborador = i.codperiodocolaborador
            JOIN tblcolaborador col       ON col.codcolaborador = pc.codcolaborador
            JOIN tblpessoa p              ON p.codpessoa = col.codpessoa
            WHERE i.codbeerecarga = :codbeerecarga
            GROUP BY p.cnpj, p.fisica
            ORDER BY MIN(p.pessoa)
        ", ['codbeerecarga' => $codbeerecarga]);
    }
}
