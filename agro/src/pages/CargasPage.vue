<script setup>
// Listagem do HISTÓRICO de romaneios (consulta). A operação continua no pátio
// (/carga/:uuid) — aqui nada é editado; o olho abre a ficha só-leitura.
//
// Layout, UI e UX espelhados da ValeModeloPage (negocios /vale-modelo): página
// cinza centralizada, botões no topo, q-table num card e ações em ícone na
// última coluna. As colunas são todos os dados do romaneio; as que sobrarem
// saem depois da validação.
import { ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { formataTimestamp } from '@components/formatters'
import MgEmptyState from '@components/MgEmptyState.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import { useCargaListagemStore } from 'src/stores/cargaListagem'
import {
  fmtNumero,
  iconeCarga,
  rotulosDoPapel,
  sentidoMeta,
  ETAPA_META,
  ETAPA_FINAL,
} from 'src/utils/carga'

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
  { name: 'pbt', label: 'PBT', field: 'pbt', align: 'right' },
  { name: 'tara', label: 'Tara', field: 'tara', align: 'right' },
  { name: 'bruto', label: 'Bruto', field: 'bruto', align: 'right' },
  { name: 'desconto', label: 'Desconto', field: 'desconto', align: 'right' },
  { name: 'liquido', label: 'Líquido', field: 'liquido', align: 'right' },
  { name: 'sacas', label: 'Sacas', field: 'liquido', align: 'right' },

  { name: 'acoes', label: '', field: 'acoes', align: 'right' },
]

// Colunas antes do PBT: a linha de totais junta todas numa célula só.
const COLSPAN_ROTULO_TOTAL = colunas.findIndex((c) => c.name === 'pbt')

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

// pesosaca da própria safra da carga: uma listagem sem filtro mistura culturas,
// e usar o peso de uma delas erraria as sacas das outras.
// `Safra.cultura` em minúsculo: relação aninhada num model é serializada pelo
// Eloquent em snake_case (só o `Safra` do topo é PascalCase, posto pelo Resource).
function sacasDa(carga) {
  if (carga.liquido == null) return null
  return Number(carga.liquido) / (Number(carga.Safra?.cultura?.pesosaca) || 60)
}

function carretas(carga) {
  return [carga.placacarreta, carga.placacarreta2].filter(Boolean).join(' / ')
}

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
            label="Imprimir lista"
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
          flat
          bordered
          :loading="carregando"
          hide-pagination
          :rows-per-page-options="[0]"
          :pagination="{ rowsPerPage: 0 }"
        >
          <template #body="props">
            <q-tr :props="props">
              <q-td key="codcarga" :props="props" class="text-weight-medium">
                #{{ props.row.codcarga }}
              </q-td>

              <q-td key="data" :props="props">
                {{ formataTimestamp(props.row.data, 2) }}
              </q-td>

              <q-td key="sentido" :props="props">
                <q-icon
                  :name="iconeCarga(props.row)"
                  :color="sentidoMeta(props.row.sentido).color"
                  size="xs"
                  class="q-mr-xs"
                />
                {{ sentidoMeta(props.row.sentido).label }}
              </q-td>

              <q-td
                key="etapa"
                :props="props"
                :class="
                  props.row.etapa !== ETAPA_FINAL
                    ? `text-${ETAPA_META[props.row.etapa]?.color}`
                    : ''
                "
              >
                {{ ETAPA_META[props.row.etapa]?.label ?? props.row.etapa }}
              </q-td>

              <q-td key="safra" :props="props">
                <span v-if="props.row.Safra?.safra">{{ props.row.Safra.safra }}</span>
                <span v-else class="text-grey-6">—</span>
              </q-td>

              <q-td key="cultura" :props="props">
                <span v-if="props.row.Safra?.cultura?.cultura">
                  {{ props.row.Safra.cultura.cultura }}
                </span>
                <span v-else class="text-grey-6">—</span>
              </q-td>

              <q-td key="placa" :props="props">
                <span v-if="props.row.placa">{{ props.row.placa }}</span>
                <span v-else class="text-grey-6">—</span>
              </q-td>

              <q-td key="carreta" :props="props">
                <span v-if="carretas(props.row)">{{ carretas(props.row) }}</span>
                <span v-else class="text-grey-6">—</span>
              </q-td>

              <q-td key="motorista" :props="props">
                <span v-if="props.row.motorista">{{ props.row.motorista }}</span>
                <span v-else class="text-grey-6">—</span>
              </q-td>

              <q-td key="origem" :props="props">
                <div v-if="rotulosDoPapel(props.row, 'ORIGEM')" class="ellipsis celula-ponto">
                  {{ rotulosDoPapel(props.row, 'ORIGEM') }}
                  <q-tooltip>{{ rotulosDoPapel(props.row, 'ORIGEM') }}</q-tooltip>
                </div>
                <span v-else class="text-grey-6">—</span>
              </q-td>

              <q-td key="destino" :props="props">
                <div v-if="rotulosDoPapel(props.row, 'DESTINO')" class="ellipsis celula-ponto">
                  {{ rotulosDoPapel(props.row, 'DESTINO') }}
                  <q-tooltip>{{ rotulosDoPapel(props.row, 'DESTINO') }}</q-tooltip>
                </div>
                <span v-else class="text-grey-6">—</span>
              </q-td>

              <q-td key="pbt" :props="props">{{ fmtNumero(props.row.pbt) }}</q-td>

              <q-td key="tara" :props="props">{{ fmtNumero(props.row.tara) }}</q-td>

              <q-td key="bruto" :props="props">{{ fmtNumero(props.row.bruto) }}</q-td>

              <q-td key="desconto" :props="props">{{ fmtNumero(props.row.desconto) }}</q-td>

              <q-td key="liquido" :props="props" class="text-weight-medium">
                {{ fmtNumero(props.row.liquido) }}
              </q-td>

              <q-td key="sacas" :props="props">{{ fmtNumero(sacasDa(props.row), 1) }}</q-td>

              <q-td key="inativo" :props="props">
                <q-badge v-if="props.row.inativo" color="orange-7">Cancelado</q-badge>
                <q-badge v-else color="green-6">Ativo</q-badge>
              </q-td>

              <q-td key="acoes" :props="props">
                <MgInfoCriacao :registro="props.row" />
                <q-btn
                  flat
                  round
                  size="sm"
                  color="grey-7"
                  icon="visibility"
                  :to="linkAbrir(props.row)"
                >
                  <q-tooltip>Abrir romaneio</q-tooltip>
                </q-btn>
              </q-td>
            </q-tr>
          </template>

          <!-- Uma linha por tipo, com cada soma embaixo da sua coluna -->
          <template #bottom-row>
            <q-tr v-for="t in linhasTotais()" :key="t.sentido" class="bg-grey-1 text-weight-medium">
              <q-td :colspan="COLSPAN_ROTULO_TOTAL">
                <q-icon
                  :name="sentidoMeta(t.sentido).icon"
                  :color="sentidoMeta(t.sentido).color"
                  size="xs"
                  class="q-mr-xs"
                />
                {{ t.rotulo }}
                <span class="text-grey-7 text-weight-regular">
                  · {{ fmtNumero(t.qtd) }} {{ t.qtd === 1 ? 'romaneio' : 'romaneios' }}
                </span>
              </q-td>
              <q-td />
              <q-td />
              <q-td class="text-right">{{ fmtNumero(t.bruto) }}</q-td>
              <q-td class="text-right">{{ fmtNumero(t.desconto) }}</q-td>
              <q-td class="text-right">{{ fmtNumero(t.liquido) }}</q-td>
              <q-td class="text-right">
                {{ fmtNumero(sacasTotal(t.liquido), 1) }}
                <q-tooltip v-if="culturaUnica">
                  Sacas de {{ culturaUnica.cultura }} ({{ culturaUnica.pesosaca }} kg)
                </q-tooltip>
                <q-tooltip v-else>Sacas só com uma cultura no filtro</q-tooltip>
              </q-td>
              <q-td />
              <q-td />
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
.celula-ponto {
  max-width: 180px;
}
</style>
