<script setup>
// Listagem do HISTÓRICO de romaneios (consulta). A operação continua no pátio
// (/carga/:uuid) — aqui nada é editado; o olho abre a ficha só-leitura.
//
// Layout, UI e UX espelhados da ValeModeloPage (negocios /vale-modelo): página
// cinza centralizada, botões no topo, q-table num card e ações em ícone na
// última coluna. As colunas são as escolhidas na validação (TASK-138), e as
// células do #body seguem a MESMA ordem do array `colunas` — o q-td não se
// reposiciona sozinho: célula fora de ordem desalinha do cabeçalho.
import { ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { formataData } from '@components/formatters'
import MgEmptyState from '@components/MgEmptyState.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import { useCargaListagemStore } from 'src/stores/cargaListagem'
import { fmtNumero, rotulosDoPapel, sentidoMeta, ETAPA_META } from 'src/utils/carga'

const store = useCargaListagemStore()
const { cargas, totais, paginacao, carregando, carregadoUmaVez, culturaUnica } = storeToRefs(store)

const colunas = [
  { name: 'sentido', label: 'Tipo', field: 'sentido', align: 'left' },
  { name: 'data', label: 'Data', field: 'data', align: 'left' },

  { name: 'etapa', label: 'Etapa', field: 'etapa', align: 'left' },
  { name: 'safra', label: 'Safra', field: 'codsafra', align: 'left' },

  { name: 'placa', label: 'Placa', field: 'placa', align: 'left' },

  { name: 'motorista', label: 'Motorista', field: 'motorista', align: 'left' },
  { name: 'origem', label: 'Origem', field: 'codcarga', align: 'left' },
  { name: 'destino', label: 'Destino', field: 'codcarga', align: 'left' },
  { name: 'bruto', label: 'Bruto', field: 'bruto', align: 'right' },
  { name: 'tara', label: 'Tara', field: 'tara', align: 'right' },
  { name: 'desconto', label: 'Desconto', field: 'desconto', align: 'right' },
  { name: 'liquido', label: 'Líquido', field: 'liquido', align: 'right' },
  { name: 'sacas', label: 'Sacas', field: 'liquido', align: 'right' },

  { name: 'acoes', label: '', field: 'acoes', align: 'right' },
]

// A linha de totais junta numa célula só as colunas antes do Bruto e põe cada
// soma embaixo da sua coluna; continua alinhada se a ordem mudar.
const COLSPAN_ROTULO_TOTAL = colunas.findIndex((c) => c.name === 'bruto')
const colunasTotais = colunas.slice(COLSPAN_ROTULO_TOTAL)
const CAMPOS_TOTAL = ['bruto', 'desconto', 'liquido']

// Totais do RECORTE INTEIRO (não da página carregada), um por tipo e sempre
// sem as canceladas — mesmo com "Cancelados"/"Todos" no filtro.
const TOTAL_ROTULOS = {
  ENTRADA: 'Recebido',
  SAIDA: 'Expedido',
  TRANSFERENCIA: 'Transferido',
}

const linhasTotais = () =>
  Object.entries(TOTAL_ROTULOS)
    .map(([sentido, rotulo]) => ({ sentido, rotulo, ...(totais.value.sentidos?.[sentido] ?? {}) }))
    .filter((t) => t.qtd)

// Só faz sentido somar sacas quando o recorte tem UMA cultura — 1000 sacas de
// soja mais 1000 de milho não é 2000 de coisa nenhuma.
function sacasTotal(liquido) {
  const peso = Number(culturaUnica.value?.pesosaca) || 0
  return peso > 0 ? liquido / peso : null
}

function valorTotal(t, coluna) {
  if (coluna === 'sacas') return fmtNumero(sacasTotal(t.liquido), 1)
  return CAMPOS_TOTAL.includes(coluna) ? fmtNumero(t[coluna]) : ''
}

// pesosaca da própria safra da carga: uma listagem sem filtro mistura culturas,
// e usar o peso de uma delas erraria as sacas das outras.
// `Safra.cultura` em minúsculo: relação aninhada num model é serializada pelo
// Eloquent em snake_case (só o `Safra` do topo é PascalCase, posto pelo Resource).
function sacasDa(carga) {
  if (carga.liquido == null) return null
  return Number(carga.liquido) / (Number(carga.Safra?.cultura?.pesosaca) || 60)
}

// A linha inteira abre o romaneio, e por <a> de verdade: Ctrl+clique, botão do
// meio e "Abrir em nova guia" funcionam. Um link por célula (td não pode ficar
// dentro de <a>); só o da 1ª célula entra no Tab, senão seriam 13 paradas por
// linha.
const linkAbrir = (carga) => ({ name: 'carga-detalhe', params: { codcarga: carga.codcarga } })

// Quem carrega é o q-infinite-scroll, inclusive a primeira página.
store.reiniciar()
const scrollRef = ref(null)

const carregarMais = async (indice, done) => done(!(await store.carregarMais()))

// Filtro novo recarrega da página 1: se o scroll já tinha parado no fim da
// lista anterior, ele volta a funcionar.
watch(
  () => paginacao.value.hasMore,
  (temMais) => {
    if (temMais) scrollRef.value?.resume()
  },
)
</script>

<template>
  <q-page class="bg-grey-2">
    <q-infinite-scroll ref="scrollRef" @load="carregarMais" :offset="250">
      <div class="q-pa-md" style="max-width: 1200px; margin: auto">
        <div class="row justify-end q-mb-sm">
          <q-btn
            flat
            color="primary"
            icon="print"
            label="Gerar Relatório"
            :disable="!cargas.length"
            @click="store.imprimirRelatorio()"
          />
        </div>

        <MgEmptyState v-if="carregadoUmaVez && !carregando && !cargas.length" icon="local_shipping">
          Nenhum romaneio com esses filtros.
        </MgEmptyState>

        <q-table
          v-else
          :rows="cargas"
          :columns="colunas"
          row-key="codcarga"
          class="tabela-cargas"
          flat
          bordered
          wrap-cells
          :loading="carregando"
          hide-pagination
          :rows-per-page-options="[0]"
          :pagination="{ rowsPerPage: 0 }"
        >
          <template #body="props">
            <q-tr :props="props">
              <q-td key="sentido" :props="props" class="text-no-wrap">
                <router-link :to="linkAbrir(props.row)" class="link-linha">
                  {{ sentidoMeta(props.row.sentido).label }}
                </router-link>
              </q-td>

              <q-td key="data" :props="props" class="text-no-wrap">
                <router-link :to="linkAbrir(props.row)" class="link-linha" tabindex="-1">
                  {{ formataData(props.row.data) }}
                </router-link>
              </q-td>

              <!-- Sem a coluna Situação, o cancelado aparece no lugar da etapa -->
              <q-td
                key="etapa"
                :props="props"
                :class="props.row.inativo ? '' : `text-${ETAPA_META[props.row.etapa]?.color}`"
              >
                <router-link :to="linkAbrir(props.row)" class="link-linha" tabindex="-1">
                  <q-badge v-if="props.row.inativo" color="orange-7">Cancelado</q-badge>
                  <template v-else>
                    {{ ETAPA_META[props.row.etapa]?.label ?? props.row.etapa }}
                  </template>
                </router-link>
              </q-td>

              <q-td key="safra" :props="props">
                <router-link :to="linkAbrir(props.row)" class="link-linha" tabindex="-1">
                  <span v-if="props.row.Safra?.safra">{{ props.row.Safra.safra }}</span>
                  <span v-else class="text-grey-6">—</span>
                </router-link>
              </q-td>

              <q-td key="placa" :props="props">
                <router-link :to="linkAbrir(props.row)" class="link-linha" tabindex="-1">
                  <span v-if="props.row.placa">{{ props.row.placa }}</span>
                  <span v-else class="text-grey-6">—</span>
                </router-link>
              </q-td>

              <q-td key="motorista" :props="props">
                <router-link :to="linkAbrir(props.row)" class="link-linha" tabindex="-1">
                  <div v-if="props.row.motorista" class="ellipsis celula-ellipsis">
                    {{ props.row.motorista }}
                    <q-tooltip>{{ props.row.motorista }}</q-tooltip>
                  </div>
                  <span v-else class="text-grey-6">—</span>
                </router-link>
              </q-td>

              <q-td key="origem" :props="props">
                <router-link :to="linkAbrir(props.row)" class="link-linha" tabindex="-1">
                  <div v-if="rotulosDoPapel(props.row, 'ORIGEM')" class="ellipsis celula-ellipsis">
                    {{ rotulosDoPapel(props.row, 'ORIGEM') }}
                    <q-tooltip>{{ rotulosDoPapel(props.row, 'ORIGEM') }}</q-tooltip>
                  </div>
                  <span v-else class="text-grey-6">—</span>
                </router-link>
              </q-td>

              <q-td key="destino" :props="props">
                <router-link :to="linkAbrir(props.row)" class="link-linha" tabindex="-1">
                  <div v-if="rotulosDoPapel(props.row, 'DESTINO')" class="ellipsis celula-ellipsis">
                    {{ rotulosDoPapel(props.row, 'DESTINO') }}
                    <q-tooltip>{{ rotulosDoPapel(props.row, 'DESTINO') }}</q-tooltip>
                  </div>
                  <span v-else class="text-grey-6">—</span>
                </router-link>
              </q-td>

              <q-td key="bruto" :props="props">
                <router-link :to="linkAbrir(props.row)" class="link-linha" tabindex="-1">
                  {{ fmtNumero(props.row.bruto) }}
                </router-link>
              </q-td>

              <q-td key="tara" :props="props">
                <router-link :to="linkAbrir(props.row)" class="link-linha" tabindex="-1">
                  {{ fmtNumero(props.row.tara) }}
                </router-link>
              </q-td>

              <q-td key="desconto" :props="props">
                <router-link :to="linkAbrir(props.row)" class="link-linha" tabindex="-1">
                  {{ fmtNumero(props.row.desconto) }}
                </router-link>
              </q-td>

              <q-td key="liquido" :props="props" class="text-weight-medium">
                <router-link :to="linkAbrir(props.row)" class="link-linha" tabindex="-1">
                  {{ fmtNumero(props.row.liquido) }}
                </router-link>
              </q-td>

              <q-td key="sacas" :props="props">
                <router-link :to="linkAbrir(props.row)" class="link-linha" tabindex="-1">
                  {{ fmtNumero(sacasDa(props.row), 1) }}
                </router-link>
              </q-td>

              <q-td key="acoes" :props="props" class="text-no-wrap">
                <MgInfoCriacao :registro="props.row" />
              </q-td>
            </q-tr>
          </template>

          <!-- Uma linha por tipo, com cada soma embaixo da sua coluna -->
          <template #bottom-row>
            <q-tr v-for="t in linhasTotais()" :key="t.sentido" class="bg-grey-1 text-weight-medium">
              <q-td :colspan="COLSPAN_ROTULO_TOTAL">
                {{ t.rotulo }}
                <span class="text-grey-7 text-weight-regular">
                  · {{ fmtNumero(t.qtd) }} {{ t.qtd === 1 ? 'romaneio' : 'romaneios' }}
                </span>
              </q-td>
              <q-td v-for="col in colunasTotais" :key="col.name" class="text-right">
                {{ valorTotal(t, col.name) }}
                <template v-if="col.name === 'sacas'">
                  <q-tooltip v-if="culturaUnica">
                    Sacas de {{ culturaUnica.cultura }} ({{ culturaUnica.pesosaca }} kg)
                  </q-tooltip>
                  <q-tooltip v-else>Sacas só com uma cultura no filtro</q-tooltip>
                </template>
              </q-td>
            </q-tr>
          </template>
        </q-table>
      </div>

      <template #loading>
        <div class="row justify-center q-my-md">
          <q-spinner-dots color="primary" size="32px" />
        </div>
      </template>
    </q-infinite-scroll>
  </q-page>
</template>

<style scoped>
/* Respiro lateral de 8px (o padrão do q-table é 16px) e texto quebrando linha
   (wrap-cells): as 14 colunas cabem em 1200px sem rolagem lateral. */
.tabela-cargas :deep(th),
.tabela-cargas :deep(td) {
  padding-left: 8px;
  padding-right: 8px;
}

/* Motorista, origem e destino numa linha só, cortados com reticências; o
   texto inteiro fica no tooltip. Acima da camada do link (z-index), senão o
   tooltip não recebe o mouse — o clique continua no link, que é o pai. */
.celula-ellipsis {
  position: relative;
  z-index: 1;
  max-width: 105px;
}

/* O link da célula cobre a célula inteira (o td do q-table já é
   position: relative), e não só o texto: clicar no respiro também abre. */
.link-linha {
  color: inherit;
  text-decoration: none;
}
.link-linha::after {
  content: '';
  position: absolute;
  inset: 0;
}
</style>
