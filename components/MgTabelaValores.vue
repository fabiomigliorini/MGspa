<script setup>
import { computed, useSlots, watchEffect } from 'vue'
import MgEmptyState from './MgEmptyState.vue'
import { formataNumero, formataPercentual } from './formatters'

/**
 * Tabela de valores padrão dos apps.
 *
 * Cuida de estrutura e formatação — colgroup, alinhamento, em-dash de nulo,
 * rodapé de subtotais, estado vazio. Não sabe nada de regra de negócio: tudo
 * que for específico do domínio entra pelo slot `celula-<nome>`.
 *
 * Descritor de coluna:
 *   nome      chave no objeto da linha E nome do slot           (obrigatório)
 *   label     texto do cabeçalho
 *   tipo      'texto' | 'numero' | 'percentual' | 'progresso' | 'slot'
 *   align     'left' | 'right' | 'center'   (default deriva do tipo)
 *   largura   ex. '9%' — vira <col style="width:9%">
 *   campo     quando a chave no objeto difere de `nome`
 *   valor     (linha, sub) => valor derivado
 *   casas     casas decimais (numero | percentual)
 *   cor       (valor, linha) => 'green' — cor da barra ou do texto
 *   classe    classe extra da célula
 *   agrupada  com `subLinhas`: renderiza uma vez só, com rowspan
 *   vazio     override do em-dash desta coluna
 */
const props = defineProps({
  colunas: { type: Array, required: true },

  // Cada item vira um <tr> — ou N, se `subLinhas` estiver setado.
  linhas: { type: Array, default: () => [] },

  // Linhas de <tfoot>: [{ classe, valores: { <nomeColuna>: valor } }].
  // Reaproveita `colunas` para largura, alinhamento e formatação.
  // Coluna ausente em `valores` fica em branco (não vira em-dash).
  rodape: { type: Array, default: () => [] },

  chave: { type: [String, Function], default: null },

  // Campo array da linha que gera sub-linhas com rowspan (ex.: 'indicadores').
  subLinhas: { type: String, default: null },

  linhaClasse: { type: [String, Function], default: null },

  // Linha clicável: cursor-pointer + emit 'linha-click'.
  clicavel: { type: Boolean, default: false },

  // false = tabela só de rodapé (linha de total isolada num card).
  cabecalho: { type: Boolean, default: true },

  dense: { type: Boolean, default: false },

  // Por padrão as células não quebram — colunas de valor não devem partir.
  // Ligue em tabelas com célula descritiva de várias linhas.
  wrapCells: { type: Boolean, default: false },

  vazio: { type: String, default: 'Nenhum registro encontrado.' },
  vazioIcone: { type: String, default: 'inbox' },

  // Texto das células sem valor no corpo da tabela.
  vazioCelula: { type: String, default: '—' },
})

const emit = defineEmits(['linha-click'])
const slots = useSlots()

// Slot `celula-x`/`rodape-x` sem coluna `x` é ignorado sem erro nenhum — some da
// tela em silêncio. Em dev, aponta o dedo.
if (import.meta.env.DEV) {
  watchEffect(() => {
    const nomes = new Set(props.colunas.map((c) => c.nome))
    Object.keys(slots)
      .filter((s) => /^(celula|rodape|cabecalho)-/.test(s))
      .map((s) => s.replace(/^(celula|rodape|cabecalho)-/, ''))
      .filter((n) => !nomes.has(n))
      .forEach((n) =>
        console.warn(
          `[MgTabelaValores] slot para a coluna "${n}", que não existe em \`colunas\` — ` +
            'a célula não vai aparecer. Colunas: ' + [...nomes].join(', '),
        ),
      )
  })
}

const larguraFixa = computed(() => props.colunas.some((c) => c.largura))

const alignDe = (col) => {
  if (col.align) return col.align
  return col.tipo === 'numero' || col.tipo === 'percentual' ? 'right' : 'left'
}

const classeCelula = (col, valor, linha) => {
  const classes = ['text-' + alignDe(col)]
  if (col.classe) classes.push(col.classe)
  if (col.cor && col.tipo !== 'progresso' && valor != null) {
    classes.push('text-' + col.cor(valor, linha))
  }
  return classes
}

const valorCelula = (col, linha, sub) => {
  if (typeof col.valor === 'function') return col.valor(linha, sub)
  // Sem `subLinhas`, ou coluna agrupada, o valor vem da linha; senão, da sub-linha.
  const fonte = props.subLinhas && !col.agrupada ? sub : linha
  return fonte?.[col.campo || col.nome] ?? null
}

// `formataNumero` devolve '' para entrada inválida — por isso o segundo teste.
const formataCelula = (col, valor, textoVazio) => {
  if (valor === null || valor === undefined || valor === '') return textoVazio
  if (col.tipo === 'numero') return formataNumero(valor, col.casas ?? 2) || textoVazio
  if (col.tipo === 'percentual') {
    return formataNumero(valor, col.casas ?? 2) === ''
      ? textoVazio
      : formataPercentual(valor, col.casas ?? 2)
  }
  return valor
}

const textoCelula = (col, valor) => formataCelula(col, valor, col.vazio ?? props.vazioCelula)
// No rodapé, coluna sem valor fica em branco — em-dash ali sugeriria "sem dado".
const textoRodape = (col, valor) => formataCelula(col, valor, col.vazio ?? '')

const chaveLinha = (linha, indice) => {
  if (typeof props.chave === 'function') return props.chave(linha, indice)
  if (props.chave) return linha?.[props.chave] ?? indice
  return indice
}

// Pré-processa uma vez por render: evita refazer o loop a cada interpolação.
const linhasRender = computed(() =>
  props.linhas.map((linha, indice) => {
    const subs = props.subLinhas ? linha?.[props.subLinhas] || [] : []
    return {
      linha,
      indice,
      chave: chaveLinha(linha, indice),
      // Sempre ao menos um <tr>: colaborador sem indicador continua aparecendo.
      subs: subs.length ? subs : [null],
      span: Math.max(1, subs.length),
      classe: typeof props.linhaClasse === 'function' ? props.linhaClasse(linha) : props.linhaClasse,
    }
  }),
)

const vazia = computed(() => props.linhas.length === 0 && props.rodape.length === 0)

// Clique em botão/link dentro da linha não navega — dispensa @click.stop no chamador.
const onClickLinha = (ev, linha) => {
  if (!props.clicavel) return
  if (ev.target?.closest?.('button, a, input, .q-toggle, .q-checkbox')) return
  emit('linha-click', linha)
}
</script>

<template>
  <MgEmptyState v-if="vazia" plain :icon="vazioIcone">{{ vazio }}</MgEmptyState>

  <q-markup-table
    v-else
    flat
    separator="horizontal"
    :dense="dense"
    :wrap-cells="wrapCells"
    :class="larguraFixa ? 'mg-tabela-fixa' : ''"
  >
    <colgroup v-if="larguraFixa">
      <col v-for="col in colunas" :key="col.nome" :style="col.largura ? { width: col.largura } : {}" />
    </colgroup>

    <thead v-if="cabecalho">
      <tr>
        <th v-for="col in colunas" :key="col.nome" :class="'text-' + alignDe(col)">
          <slot :name="'cabecalho-' + col.nome" :coluna="col">{{ col.label }}</slot>
        </th>
      </tr>
    </thead>

    <tbody v-if="linhasRender.length">
      <template v-for="r in linhasRender" :key="r.chave">
        <tr
          v-for="(sub, iSub) in r.subs"
          :key="iSub"
          :class="[r.classe, clicavel ? 'cursor-pointer' : '']"
          @click="onClickLinha($event, r.linha)"
        >
          <template v-for="col in colunas" :key="col.nome">
            <td
              v-if="!(subLinhas && col.agrupada) || iSub === 0"
              :rowspan="subLinhas && col.agrupada && r.span > 1 ? r.span : null"
              :class="classeCelula(col, valorCelula(col, r.linha, sub), r.linha)"
            >
              <slot
                :name="'celula-' + col.nome"
                :linha="r.linha"
                :sub="sub"
                :valor="valorCelula(col, r.linha, sub)"
                :coluna="col"
                :indice="r.indice"
              >
                <q-linear-progress
                  v-if="col.tipo === 'progresso' && valorCelula(col, r.linha, sub) != null"
                  size="8px"
                  stripe
                  rounded
                  :value="Math.min((valorCelula(col, r.linha, sub) || 0) / 100, 1)"
                  :color="col.cor ? col.cor(valorCelula(col, r.linha, sub), r.linha) : 'primary'"
                />
                <span v-else-if="col.tipo === 'progresso'" class="text-grey">
                  {{ col.vazio ?? vazioCelula }}
                </span>
                <template v-else>
                  {{ textoCelula(col, valorCelula(col, r.linha, sub)) }}
                </template>
              </slot>
            </td>
          </template>
        </tr>
      </template>
    </tbody>

    <tfoot v-if="rodape.length">
      <tr v-for="(linha, iRodape) in rodape" :key="iRodape" :class="linha.classe || 'text-weight-bold'">
        <td v-for="col in colunas" :key="col.nome" :class="'text-' + alignDe(col)">
          <slot
            :name="'rodape-' + col.nome"
            :linha="linha"
            :valor="linha.valores?.[col.nome]"
            :coluna="col"
            :indice="iRodape"
          >
            <q-linear-progress
              v-if="col.tipo === 'progresso' && linha.valores?.[col.nome] != null"
              size="8px"
              stripe
              rounded
              :value="Math.min((linha.valores[col.nome] || 0) / 100, 1)"
              :color="col.cor ? col.cor(linha.valores[col.nome], linha) : 'primary'"
            />
            <template v-else-if="col.tipo !== 'progresso'">
              {{ textoRodape(col, linha.valores?.[col.nome]) }}
            </template>
          </slot>
        </td>
      </tr>
    </tfoot>
  </q-markup-table>
</template>

<style scoped>
/* QMarkupTable joga os attrs no <div> container, não na <table> — por isso o
   `style="table-layout: fixed"` usado à mão pelas telas nunca teve efeito. */
.mg-tabela-fixa :deep(table.q-table) {
  table-layout: fixed;
}
</style>
