<script setup>
import { computed, onUnmounted, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useQuasar } from 'quasar'
import { useRoute } from 'vue-router'
import { valeEmitidosStore } from 'stores/valeEmitidos'
import { api } from 'boot/axios'
import { abrirPdf } from '@components/abrirPdf'
import { formataCodigo, formataData, formataHora, formataNumero } from '@components/formatters'
import MgEmptyState from '@components/MgEmptyState.vue'

const route = useRoute()
const $q = useQuasar()
const sVales = valeEmitidosStore()
const { filtros, rows, carregando } = storeToRefs(sVales)
const scrollRef = ref(null)
let timer = null

// Os atalhos da listagem e do formulário abrem esta página já filtrada pelo modelo.
if (route.query.codvalemodelo) {
  filtros.value.codvalemodelo = route.query.codvalemodelo
}

const params = computed(() =>
  Object.fromEntries(
    Object.entries(filtros.value).filter(([, value]) => value !== null && value !== ''),
  ),
)
const colunasDesktop = [
  {
    name: 'data',
    label: 'Data',
    field: 'data',
    align: 'left',
    style: 'width: 120px; max-width: 120px',
    headerStyle: 'width: 120px; max-width: 120px',
  },
  {
    name: 'valorvale',
    label: 'Valor',
    field: 'valorvale',
    align: 'right',
    style: 'width: 120px; max-width: 120px',
    headerStyle: 'width: 120px; max-width: 120px',
  },
  {
    name: 'saldo',
    label: 'Saldo',
    field: 'titulos',
    align: 'right',
    style: 'width: 120px; max-width: 120px',
    headerStyle: 'width: 120px; max-width: 120px',
  },
  { name: 'nome', label: 'Nome', field: 'nome', align: 'left' },
  { name: 'favorecido', label: 'Favorecido', field: 'favorecido', align: 'left' },
  { name: 'situacao', label: 'Situação', field: 'situacao', align: 'center' },
]
const colunasMobile = colunasDesktop
  .filter(({ name }) => ['valorvale', 'nome', 'situacao'].includes(name))
  .map((coluna) =>
    coluna.name === 'valorvale'
      ? {
          ...coluna,
          style: 'width: 28%; max-width: 120px',
          headerStyle: 'width: 28%; max-width: 120px',
        }
      : coluna,
  )
const colunas = computed(() => ($q.screen.lt.sm ? colunasMobile : colunasDesktop))

watch(
  filtros,
  () => {
    clearTimeout(timer)
    timer = setTimeout(() => scrollRef.value?.reset(), 400)
  },
  { deep: true },
)
onUnmounted(() => clearTimeout(timer))

async function carregarMais(pagina, done) {
  const temMais = await sVales.carregar(pagina)
  done(!temMais)
}

const linkNegocio = (vale) => `/negocio/${vale.codnegocio}`

function urlTitulo(codtitulo) {
  return `${process.env.CONTAS_URL}/titulo/${codtitulo}`
}

function imprimir() {
  abrirPdf(api, 'v1/vale-modelo-emitidos/relatorio', params.value, {
    title: 'Vales Compras Emitidos',
  })
}
</script>

<template>
  <q-page class="bg-grey-2">
    <q-infinite-scroll ref="scrollRef" @load="carregarMais" :offset="250">
      <div class="q-pa-md" style="max-width: 1086px; margin: auto">
        <div class="row justify-end q-mb-sm">
          <q-btn
            flat
            color="primary"
            icon="print"
            label="Imprimir lista"
            :disable="!rows.length && !carregando"
            @click="imprimir"
          />
        </div>

        <MgEmptyState v-if="!carregando && !rows.length" icon="card_giftcard">
          Nenhum vale emitido com esses filtros.
        </MgEmptyState>

        <q-table
          class="vale-emitidos-table"
          v-else
          :rows="rows"
          :columns="colunas"
          row-key="codnegociovale"
          flat
          bordered
          :loading="carregando"
          hide-pagination
          :rows-per-page-options="[0]"
          :pagination="{ rowsPerPage: 0 }"
        >
          <template #body="props">
            <q-tr :props="props">
              <q-td v-if="!$q.screen.lt.sm" key="data" :props="props">
                <router-link
                  :to="linkNegocio(props.row)"
                  class="block text-grey-9"
                  style="text-decoration: none"
                >
                  <div class="ellipsis">
                    {{ props.row.data ? formataData(props.row.data, 4) : '—' }}
                  </div>
                  <div v-if="props.row.data" class="text-caption text-grey-7 ellipsis">
                    {{ formataHora(props.row.data, true) }}
                  </div>
                </router-link>
              </q-td>
              <q-td key="valorvale" :props="props" class="text-right">
                <router-link
                  :to="linkNegocio(props.row)"
                  class="block text-grey-9"
                  style="text-decoration: none"
                >
                  <div class="ellipsis">{{ formataNumero(props.row.valorvale) }}</div>
                  <div class="text-caption text-primary text-weight-medium ellipsis">
                    {{ formataCodigo(props.row.codnegocio) }}
                  </div>
                </router-link>
              </q-td>
              <q-td v-if="!$q.screen.lt.sm" key="saldo" :props="props" class="text-right">
                <div v-for="titulo in props.row.titulos" :key="titulo.codtitulo">
                  <a
                    :href="urlTitulo(titulo.codtitulo)"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-primary"
                    style="text-decoration: none"
                  >
                    <div class="ellipsis">{{ formataNumero(Math.abs(titulo.saldo)) }}</div>
                    <div class="text-caption ellipsis">{{ titulo.numero }}</div>
                  </a>
                </div>
                <span v-if="!props.row.titulos?.length" class="text-grey-6">—</span>
              </q-td>
              <q-td key="nome" :props="props">
                <router-link
                  :to="linkNegocio(props.row)"
                  class="block text-grey-9"
                  style="text-decoration: none"
                >
                  <div class="ellipsis">{{ props.row.nome || '—' }}</div>
                  <div v-if="props.row.turma" class="text-caption text-grey-7 ellipsis">
                    {{ props.row.turma }}
                  </div>
                </router-link>
              </q-td>
              <q-td v-if="!$q.screen.lt.sm" key="favorecido" :props="props">
                <router-link
                  :to="linkNegocio(props.row)"
                  class="block text-grey-9"
                  style="text-decoration: none"
                >
                  <div class="ellipsis">
                    {{ props.row.favorecido || 'Ao portador' }}
                  </div>
                  <div v-if="props.row.modelo" class="text-caption text-grey-7 ellipsis">
                    {{ props.row.modelo }}
                  </div>
                </router-link>
              </q-td>
              <q-td key="situacao" :props="props">
                <router-link
                  :to="linkNegocio(props.row)"
                  class="block"
                  style="text-decoration: none"
                >
                  <q-badge
                    class="ellipsis"
                    style="max-width: 100%"
                    :color="props.row.situacao === 'cancelado' ? 'orange-7' : 'green-6'"
                  >
                    {{ props.row.situacao === 'cancelado' ? 'Cancelado' : 'Ativo' }}
                  </q-badge>
                </router-link>
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
.vale-emitidos-table :deep(table) {
  width: 100%;
  table-layout: fixed;
}

.vale-emitidos-table :deep(td) {
  overflow: hidden;
}

.vale-emitidos-table :deep(th:nth-child(1)),
.vale-emitidos-table :deep(td:nth-child(1)) {
  width: 15%;
}

.vale-emitidos-table :deep(th:nth-child(2)),
.vale-emitidos-table :deep(td:nth-child(2)) {
  width: 14%;
}

.vale-emitidos-table :deep(th:nth-child(3)),
.vale-emitidos-table :deep(td:nth-child(3)) {
  width: 14%;
}

.vale-emitidos-table :deep(th:nth-child(4)),
.vale-emitidos-table :deep(td:nth-child(4)) {
  width: 21%;
}

.vale-emitidos-table :deep(th:nth-child(5)),
.vale-emitidos-table :deep(td:nth-child(5)) {
  width: 25%;
}

.vale-emitidos-table :deep(th:nth-child(6)),
.vale-emitidos-table :deep(td:nth-child(6)) {
  width: 11%;
}

@media (max-width: 599px) {
  .vale-emitidos-table :deep(th:nth-child(1)),
  .vale-emitidos-table :deep(td:nth-child(1)) {
    width: 28%;
    max-width: 100px;
  }

  .vale-emitidos-table :deep(th:nth-child(2)),
  .vale-emitidos-table :deep(td:nth-child(2)) {
    width: 47%;
  }

  .vale-emitidos-table :deep(th:nth-child(3)),
  .vale-emitidos-table :deep(td:nth-child(3)) {
    width: 25%;
  }
}
</style>
