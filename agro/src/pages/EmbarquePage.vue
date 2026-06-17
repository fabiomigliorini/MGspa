<script setup>
import { ref, onMounted, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useRoute, useRouter } from 'vue-router'
import { useEmbarqueStore } from 'src/stores/embarque'
import { useSincronizacaoStore } from 'src/stores/sincronizacao'
import EmbarqueDialog from 'components/EmbarqueDialog.vue'

const route = useRoute()
const router = useRouter()
const store = useEmbarqueStore()
const sinc = useSincronizacaoStore()
const { embarquesPorEtapa, contratoFiltradoRotulo } = storeToRefs(store)
const { online, sincronizando } = storeToRefs(sinc)

// Filtra o pátio pelo contrato vindo da rota (?codcontrato=). Reage a mudanças
// na query pra alternar entre contratos sem recarregar a página.
watch(
  () => route.query.codcontrato,
  (cod) => {
    store.filtroCodcontrato = cod ? Number(cod) : null
  },
  { immediate: true },
)

function limparFiltro() {
  router.replace({ name: 'embarque' })
}

const colunas = [
  { etapa: 'TARA', label: 'Tara', icon: 'monitor_weight', color: 'teal-7' },
  { etapa: 'BRUTO', label: 'Peso Bruto', icon: 'scale', color: 'orange-8' },
  { etapa: 'FISCAL', label: 'Nota Fiscal', icon: 'receipt_long', color: 'deep-orange-7' },
  { etapa: 'DESPACHADO', label: 'Despachado', icon: 'check_circle', color: 'green-7' },
]

// Rótulo da ação por etapa (mesmo texto do diálogo), exibido no botão do card.
const proximoLabel = {
  TARA: 'Pesar tara',
  BRUTO: 'Pesar bruto',
  FISCAL: 'Notas fiscais',
  DESPACHADO: 'Despachar',
}

// Barra de progresso do card: pinta os segmentos até a etapa atual.
function indiceEtapa(etapa) {
  return colunas.findIndex((c) => c.etapa === etapa)
}
function corEtapa(etapa) {
  return colunas.find((c) => c.etapa === etapa)?.color || 'grey-5'
}
function corSegmento(e, i) {
  return i <= indiceEtapa(e.etapa) ? corEtapa(e.etapa) : 'grey-3'
}

// Header/rodapé são reveal (view "hHh lpR fFf"); desconta a altura deles pra o
// kanban encaixar na área visível e rolar internamente (igual ao recebimento).
function pageStyleFn(offset) {
  return { minHeight: `calc(100vh - ${offset || 80}px)` }
}

const dialog = ref(false)
const sel = ref(null)
const ehNovo = ref(false)

function abrir(e) {
  sel.value = JSON.parse(JSON.stringify(e))
  ehNovo.value = false
  dialog.value = true
}
function novo() {
  sel.value = store.nova(store.filtroCodcontrato)
  ehNovo.value = true
  dialog.value = true
}
async function onSalvar(e) {
  await store.salvar(e)
  dialog.value = false
}

function fmt(v) {
  if (v === null || v === undefined || v === '') return '—'
  return Number(v).toLocaleString('pt-BR')
}
function contratos(e) {
  if (!e.contratos?.length) return 'Sem contrato'
  return e.contratos.map((c) => c.rotulo || `Contrato ${c.codcontrato}`).join(' · ')
}
function resumo(e) {
  if (e.etapa === 'DESPACHADO' || e.pesoliquido) return `${fmt(e.pesoliquido)} kg líquido`
  if (e.pesotara) return `tara ${fmt(e.pesotara)} kg`
  return e.motorista || '—'
}

onMounted(async () => {
  await store.carregarReferencias()
  await store.carregarEmbarques()
  store.sincronizar().catch(() => {})
})
</script>

<template>
  <q-page class="column" :style-fn="pageStyleFn">
    <q-toolbar class="bg-white text-grey-9 q-px-md q-gutter-sm">
      <q-icon name="outbound" size="sm" color="green-7" />
      <div class="text-subtitle1">Pátio de Expedição</div>
      <q-chip
        v-if="contratoFiltradoRotulo"
        removable
        color="green-1"
        text-color="green-9"
        icon="filter_alt"
        :label="contratoFiltradoRotulo"
        @remove="limparFiltro"
      >
        <q-tooltip>Mostrando só este contrato — clique no × para ver todos</q-tooltip>
      </q-chip>
      <q-space />
      <q-chip
        :color="online ? 'green-1' : 'orange-1'"
        :text-color="online ? 'green-9' : 'orange-9'"
      >
        <q-icon :name="online ? 'cloud_done' : 'cloud_off'" class="q-mr-xs" />
        {{ online ? 'Online' : 'Offline' }}
      </q-chip>
      <q-btn
        flat
        round
        icon="sync"
        :loading="sincronizando"
        color="grey-7"
        @click="store.sincronizar()"
      >
        <q-tooltip>Sincronizar</q-tooltip>
      </q-btn>
      <q-btn unelevated color="primary" icon="add" label="Caminhão" @click="novo" />
    </q-toolbar>

    <q-separator />

    <!-- Kanban horizontal: uma coluna por etapa (igual ao pátio de recebimento) -->
    <div class="row no-wrap q-gutter-md q-pa-md bg-grey-2 col" style="overflow-x: auto">
      <q-card
        v-for="col in colunas"
        :key="col.etapa"
        flat
        bordered
        class="overflow-hidden col column"
        style="min-width: 260px"
      >
        <!-- Cabeçalho da etapa -->
        <q-item :class="`bg-${col.color} text-white`" class="rounded-borders">
          <q-item-section avatar>
            <q-icon :name="col.icon" />
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-weight-medium">{{ col.label }}</q-item-label>
          </q-item-section>
          <q-item-section side>
            <q-badge
              color="white"
              :text-color="col.color"
              :label="embarquesPorEtapa[col.etapa].length"
            />
          </q-item-section>
        </q-item>

        <div class="q-px-sm q-pb-sm col scroll">
          <!-- Cards dos caminhões desta etapa -->
          <q-card
            v-for="e in embarquesPorEtapa[col.etapa]"
            :key="e.uuid"
            flat
            bordered
            class="cursor-pointer q-mt-sm"
            @click="abrir(e)"
          >
            <q-card-section class="q-pb-xs">
              <div class="row items-start no-wrap">
                <div class="col">
                  <div class="text-h6 text-weight-bold">{{ e.placa || 'Sem placa' }}</div>
                  <div class="text-caption text-grey-7">{{ contratos(e) }}</div>
                </div>
                <q-icon
                  :name="e.sincronizado ? 'cloud_done' : 'cloud_off'"
                  :color="e.sincronizado ? 'green-5' : 'orange-6'"
                  size="sm"
                >
                  <q-tooltip>{{ e.sincronizado ? 'Sincronizado' : 'Pendente' }}</q-tooltip>
                </q-icon>
              </div>
            </q-card-section>

            <!-- Barra de progresso das etapas -->
            <q-card-section class="q-py-xs">
              <div class="row no-wrap q-gutter-xs">
                <div
                  v-for="(s, i) in colunas"
                  :key="s.etapa"
                  class="col"
                  :class="`bg-${corSegmento(e, i)}`"
                  style="height: 6px; border-radius: 3px"
                />
              </div>
            </q-card-section>

            <q-card-section class="q-pt-xs row items-center justify-between">
              <div class="text-body2 text-grey-9">{{ resumo(e) }}</div>
              <q-btn
                flat
                color="primary"
                :label="proximoLabel[e.etapa]"
                icon-right="chevron_right"
                @click.stop="abrir(e)"
              />
            </q-card-section>
          </q-card>

          <!-- Etapa vazia -->
          <div v-if="!embarquesPorEtapa[col.etapa].length" class="text-grey-5 text-center q-pa-md">
            Nenhum caminhão nesta etapa
          </div>
        </div>
      </q-card>
    </div>

    <EmbarqueDialog v-model="dialog" :embarque="sel" :novo="ehNovo" @salvar="onSalvar" />
  </q-page>
</template>
