<?php

namespace AgroBateria;

use Illuminate\Support\Facades\DB;

/**
 * Massa de teste própria (prefixo ZZTESTE): nada real do dev é escrito.
 *
 * Base compartilhada pelas camadas: culturas ZZTESTE Soja/Milho (parâmetros da
 * norma, 1 variedade, tributos copiados da cultura real), safras 2099, fazenda
 * com talhões e plantios. Silo e contrato nascem por cenário (silo(), contrato())
 * para um cenário não enxergar o saldo do outro.
 *
 * Toda carga nasce com data fixa no PASSADO: o pátio puxa por dia e etapa
 * aberta, e assim a massa não aparece no pátio de ninguém.
 */
final class Massa
{
    public const PREFIXO = 'ZZTESTE';
    public const DIA = '2026-08-03 06:00:00';

    // IN MAPA 11/2007 (soja) e 60/2011 (milho): nome, tolerância, reduzbase.
    public const NORMA = [
        'soja' => [['Impureza', 1, true], ['Umidade', 14, true], ['Avariados', 8, false], ['Esverdeados', 8, false], ['Quebrados', 30, false]],
        'milho' => [['Impureza', 1, true], ['Umidade', 14, true], ['Avariados', 6, false]],
    ];

    public array $cultura = [];   // soja|milho => codcultura
    public array $variedade = []; // soja|milho => codvariedade
    public array $safra = [];     // soja|milho => codsafra
    public array $plantio = [];   // soja => [cod, cod, cod], milho => [cod]
    public int $fazenda = 0;
    public int $pessoa = 0;
    private int $minuto = 0;
    private int $seq = 0;

    public static function montar(): self
    {
        $m = new self();
        $m->pessoa = (int) DB::table('tblpessoa')->where('fisica', false)->whereNull('inativo')->orderBy('codpessoa')->value('codpessoa');
        if (!$m->pessoa) {
            throw new \RuntimeException('Nenhuma pessoa jurídica ativa para os contratos de teste.');
        }
        DB::transaction(function () use ($m) {
            foreach (['soja' => 'Soja', 'milho' => 'Milho'] as $k => $nome) {
                $m->cultura[$k] = DB::table('tblcultura')->insertGetId(
                    ['cultura' => static::PREFIXO . " {$nome}", 'pesosaca' => 60, 'cicloanos' => 1],
                    'codcultura'
                );
                foreach (static::NORMA[$k] as $i => [$param, $tol, $reduz]) {
                    DB::table('tblparametroclassificacao')->insert([
                        'codcultura' => $m->cultura[$k],
                        'parametroclassificacao' => $param,
                        'metodo' => 'NORMALIZADO',
                        'reduzbase' => $reduz,
                        'ordem' => $i + 1,
                        'tolerancia' => $tol,
                        'fator' => 0,
                        'desagio' => 0,
                    ]);
                }
                // Tributos da cultura real de mesmo nome: é o que a fixação usa.
                $real = DB::table('tblcultura')->whereRaw('lower(cultura) = ?', [strtolower($nome)])->value('codcultura');
                foreach (DB::table('tblculturatributo')->where('codcultura', $real)->whereNull('inativo')->get() as $t) {
                    DB::table('tblculturatributo')->insert([
                        'codcultura' => $m->cultura[$k],
                        'codtributo' => $t->codtributo,
                        'base' => $t->base,
                        'codunidadereferencia' => $t->codunidadereferencia,
                        'percentual' => $t->percentual,
                        'grupofethab' => $t->grupofethab,
                        'funrural' => $t->funrural,
                        'ordem' => $t->ordem,
                    ]);
                }
                $m->variedade[$k] = DB::table('tblvariedade')->insertGetId(
                    ['codcultura' => $m->cultura[$k], 'variedade' => static::PREFIXO . ' Variedade'],
                    'codvariedade'
                );
                $m->safra[$k] = DB::table('tblsafra')->insertGetId(
                    ['codcultura' => $m->cultura[$k], 'safra' => static::PREFIXO . " {$nome} 2099", 'anoplantio' => 2098, 'anocolheita' => 2099],
                    'codsafra'
                );
            }

            $m->fazenda = DB::table('tblfazenda')->insertGetId(['fazenda' => static::PREFIXO . ' Fazenda', 'areatotal' => 400], 'codfazenda');
            foreach (['soja' => 3, 'milho' => 1] as $k => $n) {
                for ($i = 1; $i <= $n; $i++) {
                    $nome = 'ZZ-' . strtoupper($k[0]) . $i;
                    $talhao = DB::table('tbltalhao')->insertGetId(['codfazenda' => $m->fazenda, 'talhao' => $nome, 'area' => 100], 'codtalhao');
                    $m->plantio[$k][] = DB::table('tblplantio')->insertGetId([
                        'codsafra' => $m->safra[$k],
                        'codtalhao' => $talhao,
                        'codvariedade' => $m->variedade[$k],
                        'codfazenda' => $m->fazenda,
                        'talhao' => $nome,
                        'areaplantada' => 100,
                        'expectativasacas' => 6000,
                    ], 'codplantio');
                }
            }
        });
        $m->gravarManifesto();
        return $m;
    }

    // ------------------------------------------------------------ fábricas

    public function silo(string $nome, string $tipo = 'PROPRIO', ?float $capacidadesacas = null, bool $inativo = false): int
    {
        $cod = DB::table('tblunidadearmazenadora')->insertGetId([
            'unidadearmazenadora' => static::PREFIXO . " {$nome}",
            'tipo' => $tipo,
            'capacidadesacas' => $capacidadesacas,
            'inativo' => $inativo ? now() : null,
        ], 'codunidadearmazenadora');
        $this->gravarManifesto();
        return $cod;
    }

    /** Contrato de teste. $sacas null = volume em aberto (sem teto). */
    public function contrato(string $cultura, string $operacao, ?float $sacas, array $extra = []): int
    {
        $cod = DB::table('tblcontrato')->insertGetId(array_replace([
            'contrato' => static::PREFIXO . '-' . str_pad((string) ++$this->seq, 4, '0', STR_PAD_LEFT) . '-' . substr(uniqid(), -4),
            'codpessoa' => $this->pessoa,
            'codcultura' => $this->cultura[$cultura],
            'codsafra' => $this->safra[$cultura],
            'quantidade' => $sacas,
            'operacao' => $operacao,
            'datacontrato' => '2026-08-01',
            'barter' => false,
        ], $extra), 'codcontrato');
        return $cod;
    }

    /**
     * Estoque inicial no silo por lançamento manual direto no banco — é preparo
     * do cenário, não o que está sendo testado.
     */
    public function estoque(int $silo, string $cultura, float $kg): void
    {
        DB::table('tblmovimentograo')->insert([
            'codcarga' => null,
            'manual' => true,
            'codsafra' => $this->safra[$cultura],
            'data' => static::DIA,
            'papel' => 'DESTINO',
            'contatipo' => 'UNIDADE',
            'codunidadearmazenadora' => $silo,
            'bruto' => $kg,
            'desconto' => 0,
            'liquido' => $kg,
            'observacao' => static::PREFIXO . ' estoque inicial',
        ]);
    }

    /** Parâmetros ATIVOS da cultura (lidos do banco, então refletem mudanças do cenário). */
    public function parametros(string $cultura): array
    {
        return DB::table('tblparametroclassificacao')
            ->where('codcultura', $this->cultura[$cultura])
            ->whereNull('inativo')
            ->orderBy('ordem')->orderBy('codparametroclassificacao')
            ->get()
            ->map(fn ($p) => [
                'cod' => (int) $p->codparametroclassificacao,
                'nome' => $p->parametroclassificacao,
                'metodo' => $p->metodo,
                'reduzbase' => (bool) $p->reduzbase,
                'ordem' => (int) $p->ordem,
                'tolerancia' => (float) $p->tolerancia,
                'fator' => (float) $p->fator,
                'desagio' => (float) $p->desagio,
            ])->all();
    }

    /** ['Umidade' => 18, ...] -> [codparametro => 18, ...] */
    public function leituras(string $cultura, array $porNome): array
    {
        $cod = [];
        foreach ($this->parametros($cultura) as $p) {
            $cod[$p['nome']] = $p['cod'];
        }
        $out = [];
        foreach ($porNome as $nome => $v) {
            if (!isset($cod[$nome])) {
                throw new \RuntimeException("Parâmetro {$nome} não existe na massa {$cultura}");
            }
            $out[$cod[$nome]] = $v;
        }
        return $out;
    }

    public function codParametro(string $cultura, string $nome): int
    {
        return (int) array_search($nome, array_column($this->parametros($cultura), 'nome', 'cod'), true);
    }

    /** Chegada da próxima carga: dia fixo no passado, um minuto depois da anterior. */
    public function data(): string
    {
        return date('Y-m-d H:i:s', strtotime(static::DIA) + 60 * $this->minuto++);
    }

    // ------------------------------------------------------------- limpeza

    /**
     * Apaga TUDO que for da massa, pelas chaves ZZTESTE — não só pelo manifesto:
     * ajuste manual de silo pode ficar sem safra, e pedido que deu timeout não
     * devolveu id. Aborta sem apagar nada se alguma carga de FORA da massa
     * aponta para silo, contrato ou talhão da massa.
     *
     * @return array<string,int> quantos de cada, e 'restantes' (tem que ser 0)
     */
    public static function limpar(): array
    {
        $zz = static::PREFIXO . '%';
        $ou = fn (array $ids) => $ids ?: [0];
        $culturas = DB::table('tblcultura')->where('cultura', 'like', $zz)->pluck('codcultura')->all();
        $safras = DB::table('tblsafra')->where(fn ($q) => $q->where('safra', 'like', $zz)->orWhereIn('codcultura', $ou($culturas)))->pluck('codsafra')->all();
        $fazendas = DB::table('tblfazenda')->where('fazenda', 'like', $zz)->pluck('codfazenda')->all();
        $silos = DB::table('tblunidadearmazenadora')->where('unidadearmazenadora', 'like', $zz)->pluck('codunidadearmazenadora')->all();
        $contratos = DB::table('tblcontrato')->where(fn ($q) => $q->where('contrato', 'like', $zz)->orWhereIn('codcultura', $ou($culturas)))->pluck('codcontrato')->all();
        $plantios = DB::table('tblplantio')->where(fn ($q) => $q->whereIn('codsafra', $ou($safras))->orWhereIn('codfazenda', $ou($fazendas)))->pluck('codplantio')->all();

        $alheias = DB::table('tblcargaponto as p')
            ->join('tblcarga as c', 'c.codcarga', '=', 'p.codcarga')
            ->whereNotIn('c.codsafra', $ou($safras))
            ->where(fn ($q) => $q->whereIn('p.codunidadearmazenadora', $ou($silos))
                ->orWhereIn('p.codcontrato', $ou($contratos))
                ->orWhereIn('p.codplantio', $ou($plantios)))
            ->distinct()->pluck('c.codcarga')->all();
        if ($alheias) {
            throw new \RuntimeException('Limpeza abortada, nada foi apagado: cargas fora da massa usam silo/contrato/talhão ZZTESTE: ' . implode(', ', $alheias));
        }

        $n = [];
        DB::transaction(function () use (&$n, $ou, $culturas, $safras, $fazendas, $silos, $contratos, $plantios) {
            $cargas = DB::table('tblcarga')->whereIn('codsafra', $ou($safras))->pluck('codcarga')->all();
            $n['movimentos'] = DB::table('tblmovimentograo')->where(fn ($q) => $q
                ->whereIn('codsafra', $ou($safras))
                ->orWhereIn('codunidadearmazenadora', $ou($silos))
                ->orWhereIn('codcontrato', $ou($contratos))
                ->orWhereIn('codplantio', $ou($plantios))
                ->orWhereIn('codcarga', $ou($cargas)))->delete();
            $n['classificacoes'] = DB::table('tblcargaclassificacao')->whereIn('codcarga', $ou($cargas))->delete();
            $n['cargas'] = DB::table('tblcarga')->whereIn('codcarga', $ou($cargas))->delete(); // pontos em cascata
            $fixacoes = DB::table('tblcontratofixacao')->whereIn('codcontrato', $ou($contratos))->pluck('codcontratofixacao')->all();
            $n['pagamentos'] = DB::table('tblcontratopagamento')->whereIn('codcontratofixacao', $ou($fixacoes))->delete();
            $n['contratos'] = DB::table('tblcontrato')->whereIn('codcontrato', $ou($contratos))->delete(); // fixação, câmbio e nota em cascata
            $n['plantios'] = DB::table('tblplantio')->whereIn('codplantio', $ou($plantios))->delete();
            $n['talhoes'] = DB::table('tbltalhao')->whereIn('codfazenda', $ou($fazendas))->delete();
            $n['fazendas'] = DB::table('tblfazenda')->whereIn('codfazenda', $ou($fazendas))->delete();
            $n['silos'] = DB::table('tblunidadearmazenadora')->whereIn('codunidadearmazenadora', $ou($silos))->delete();
            $n['parametros'] = DB::table('tblparametroclassificacao')->whereIn('codcultura', $ou($culturas))->delete();
            $n['tributos'] = DB::table('tblculturatributo')->whereIn('codcultura', $ou($culturas))->delete();
            $n['safras'] = DB::table('tblsafra')->whereIn('codsafra', $ou($safras))->delete();
            $n['variedades'] = DB::table('tblvariedade')->whereIn('codcultura', $ou($culturas))->delete();
            $n['culturas'] = DB::table('tblcultura')->whereIn('codcultura', $ou($culturas))->delete();
        });
        $n['tokens'] = Ambiente::revogar();
        @unlink(Ambiente::arquivo('manifesto.json'));

        $n['restantes'] = DB::table('tblcultura')->where('cultura', 'like', $zz)->count()
            + DB::table('tblsafra')->where('safra', 'like', $zz)->count()
            + DB::table('tblfazenda')->where('fazenda', 'like', $zz)->count()
            + DB::table('tblunidadearmazenadora')->where('unidadearmazenadora', 'like', $zz)->count()
            + DB::table('tblcontrato')->where('contrato', 'like', $zz)->count();
        return $n;
    }

    /**
     * Massa mantida para conferir na tela SEM aparecer no pátio de ninguém: o
     * pátio só oferece safra, silo, contrato e talhão ATIVOS e esconde carga
     * cancelada (desde a TASK-109 ele puxa as abertas de qualquer dia e safra).
     * Carga que ficou aberta (recusada no meio, cópia velha que venceu) vira
     * cancelada; o resto fica inativo. Listagem e relatório seguem mostrando
     * tudo — filtre pelo motorista "ZZTESTE".
     *
     * @return array<string,int>
     */
    public static function recolher(): array
    {
        $zz = static::PREFIXO . '%';
        $agora = now();
        $safras = DB::table('tblsafra')->where('safra', 'like', $zz)->pluck('codsafra')->all() ?: [0];
        $culturas = DB::table('tblcultura')->where('cultura', 'like', $zz)->pluck('codcultura')->all() ?: [0];
        $fazendas = DB::table('tblfazenda')->where('fazenda', 'like', $zz)->pluck('codfazenda')->all() ?: [0];
        $ativo = fn ($q) => $q->whereNull('inativo');
        return [
            'cargas_abertas_canceladas' => $ativo(DB::table('tblcarga')->whereIn('codsafra', $safras)->where('etapa', '<>', 'FINALIZADO'))->update(['inativo' => $agora]),
            'safras' => $ativo(DB::table('tblsafra')->whereIn('codsafra', $safras))->update(['inativo' => $agora]),
            'silos' => $ativo(DB::table('tblunidadearmazenadora')->where('unidadearmazenadora', 'like', $zz))->update(['inativo' => $agora]),
            'contratos' => $ativo(DB::table('tblcontrato')->whereIn('codcultura', $culturas))->update(['inativo' => $agora]),
            'plantios' => $ativo(DB::table('tblplantio')->whereIn('codsafra', $safras))->update(['inativo' => $agora]),
            'talhoes' => $ativo(DB::table('tbltalhao')->whereIn('codfazenda', $fazendas))->update(['inativo' => $agora]),
            'fazendas' => $ativo(DB::table('tblfazenda')->whereIn('codfazenda', $fazendas))->update(['inativo' => $agora]),
            'variedades' => $ativo(DB::table('tblvariedade')->whereIn('codcultura', $culturas))->update(['inativo' => $agora]),
            'culturas' => $ativo(DB::table('tblcultura')->whereIn('codcultura', $culturas))->update(['inativo' => $agora]),
        ];
    }

    public static function existe(): bool
    {
        return DB::table('tblcultura')->where('cultura', 'like', static::PREFIXO . '%')->exists();
    }

    private function gravarManifesto(): void
    {
        file_put_contents(Ambiente::arquivo('manifesto.json'), json_encode([
            'criado' => date('c'),
            'cultura' => $this->cultura,
            'safra' => $this->safra,
            'plantio' => $this->plantio,
            'fazenda' => $this->fazenda,
        ], JSON_PRETTY_PRINT));
    }
}
