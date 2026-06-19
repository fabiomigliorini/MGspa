<script setup>
import { ref, computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useCargaStore } from 'src/stores/carga'
import { useSincronizacaoStore } from 'src/stores/sincronizacao'
import CargaDialog from 'components/CargaDialog.vue'

const store = useCargaStore()
const sinc = useSincronizacaoStore()

const { safras, codsafraAtiva, sentidoAtivo, etapasDoSentido, cargasPorEtapa, culturaAtiva } =
  storeToRefs(store)
const { online, sincronizando } = storeToRefs(sinc)

// Metadados de cada etapa (todas as etapas possíveis; o board mostra as do sentido).
const ETAPA_META = {
  PBT: { label: 'Peso Bruto', icon: 'scale', color: 'orange-8' },
  TARA: { label: 'Tara', icon: 'monitor_weight', color: 'teal-7' },
  CLASSIFICACAO: { label: 'Classificação', icon: 'science', color: 'deep-purple-6' },
  FISCAL: { label: 'Nota Fiscal', icon: 'receipt_long', color: 'deep-orange-7' },
  FINALIZADO: { label: 'Finalizado', icon: 'task_alt', color: 'green-7' },
}

const colunas = computed(() => etapasDoSentido.value.map((e) => ({ etapa: e, ...ETAPA_META[e] })))

const finalizadosOcultos = ref(true)
function listaVisivel(etapa) {
  return !(etapa === 'FINALIZADO' && finalizadosOcultos.value)
}

const voltarSafra = computed(() =>
  codsafraAtiva.value ? { name: 'safra-detalhe', params: { codsafra: codsafraAtiva.value } } : null,
)

const dialog = ref(false)
const cargaSel = ref(null)
const novo = ref(false)

function abrir(carga) {
  cargaSel.value = JSON.parse(JSON.stringify(carga))
  novo.value = false
  dialog.value = true
}
function novaCarga() {
  cargaSel.value = store.nova()
  novo.value = true
  dialog.value = true
}
async function onSalvar(carga) {
  await store.salvar(carga)
  dialog.value = false
}
async function onAvancar(carga) {
  await store.salvar(carga)
}

const pesosaca = computed(() => culturaAtiva.value?.pesosaca || 60)
const totalLiquidoDia = computed(() =>
  (cargasPorEtapa.value.FINALIZADO || []).reduce((s, c) => s + (Number(c.liquido) || 0), 0),
)

function indiceEtapa(etapa) {
  return etapasDoSentido.value.indexOf(etapa)
}
function corSegmento(carga, i) {
  return i <= indiceEtapa(carga.etapa) ? ETAPA_META[carga.etapa]?.color || 'grey-5' : 'grey-3'
}

function fmt(v, dec = 0) {
  if (v === null || v === undefined || v === '') return '—'
  return Number(v).toLocaleString('pt-BR', {
    minimumFractionDigits: dec,
    maximumFractionDigits: dec,
  })
}

function pontosResumo(carga) {
  const origem = (carga.pontos || []).filter((p) => p.papel === 'ORIGEM')
  const destino = (carga.pontos || []).filter((p) => p.papel === 'DESTINO')
  const lista = (carga.sentido === 'SAIDA' ? destino : origem).map((p) => p.rotulo).filter(Boolean)
  return lista.length ? lista.join(' · ') : 'Sem origem/destino'
}

function resumo(carga) {
  if (carga.etapa === 'FINALIZADO') {
    return `${fmt(carga.liquido)} kg · ${fmt((carga.liquido || 0) / pesosaca.value)} sc`
  }
  if (carga.bruto) return `Bruto ${fmt(carga.bruto)} kg`
  if (carga.pbt) return `PBT ${fmt(carga.pbt)} kg`
  return carga.motorista || 'Aguardando pesagem'
}

function pageStyleFn(offset) {
  return { minHeight: `calc(100vh - ${offset || 80}px)` }
}

onMounted(async () => {
  await store.carregarReferencias()
  await store.carregarCargas()
  store.sincronizar().catch(() => {})
})
</script>

<template>
  <q-page class="column" :style-fn="pageStyleFn">
    <!-- Barra superior: safra + sentido + status -->
    <div class="bg-white q-px-md q-py-sm">
      <div class="row items-center no-wrap q-gutter-sm">
        <q-btn
          flat
          round
          icon="arrow_back"
          color="grey-7"
          :to="voltarSafra"
          :disable="!voltarSafra"
        >
          <q-tooltip>Voltar para a safra</q-tooltip>
        </q-btn>

        <q-select
          :model-value="codsafraAtiva"
          :options="safras"
          option-value="codsafra"
          option-label="safra"
          emit-value
          map-options
          outlined
          label="Safra"
          class="col"
          @update:model-value="store.definirSafra"
        />

        <q-chip
          :color="online ? 'green-1' : 'orange-1'"
          :text-color="online ? 'green-9' : 'orange-9'"
          :icon="online ? 'cloud_done' : 'cloud_off'"
        >
          <span class="gt-xs q-ml-xs">{{ online ? 'Online' : 'Offline' }}</span>
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
      </div>

      <!-- Seletor de operação (sentido) -->
      <div class="row q-mt-sm">
        <q-btn-toggle
          :model-value="sentidoAtivo"
          :options="store.SENTIDOS.map((s) => ({ value: s.value, label: s.label, icon: s.icon }))"
          unelevated
          no-caps
          toggle-color="primary"
          color="white"
          text-color="grey-8"
          class="col"
          spread
          @update:model-value="store.definirSentido"
        />
      </div>
    </div>

    <!-- Kanban horizontal: uma coluna por etapa do sentido -->
    <div class="row no-wrap q-gutter-md q-pa-md bg-grey-2 col" style="overflow-x: auto">
      <q-card
        v-for="e in colunas"
        :key="e.etapa"
        flat
        bordered
        class="overflow-hidden col column"
        style="min-width: 260px"
      >
        <q-item
          :class="`bg-${e.color} text-white`"
          class="rounded-borders"
          :clickable="e.etapa === 'FINALIZADO'"
          @click="e.etapa === 'FINALIZADO' && (finalizadosOcultos = !finalizadosOcultos)"
        >
          <q-item-section avatar><q-icon :name="e.icon" /></q-item-section>
          <q-item-section>
            <q-item-label class="text-weight-medium">{{ e.label }}</q-item-label>
          </q-item-section>
          <q-item-section side>
            <div class="row items-center no-wrap q-gutter-x-sm">
              <q-badge
                color="white"
                :text-color="e.color"
                :label="(cargasPorEtapa[e.etapa] || []).length"
              />
              <q-icon
                v-if="e.etapa === 'FINALIZADO'"
                :name="finalizadosOcultos ? 'expand_more' : 'expand_less'"
                size="sm"
                color="white"
              />
            </div>
          </q-item-section>
        </q-item>

        <div class="q-px-sm q-pb-sm col scroll">
          <q-banner
            v-if="e.etapa === 'FINALIZADO' && (cargasPorEtapa.FINALIZADO || []).length"
            rounded
            class="bg-green-1 text-green-10 q-mt-sm"
          >
            <template #avatar><q-icon name="agriculture" color="green-8" /></template>
            <div class="text-weight-medium">Total líquido</div>
            {{ fmt(totalLiquidoDia) }} kg · {{ fmt(totalLiquidoDia / pesosaca) }} sacas
          </q-banner>

          <div v-show="listaVisivel(e.etapa)">
            <q-card
              v-for="carga in cargasPorEtapa[e.etapa] || []"
              :key="carga.uuid"
              flat
              bordered
              class="cursor-pointer q-mt-sm"
              @click="abrir(carga)"
            >
              <q-card-section class="q-pb-xs">
                <div class="row items-start no-wrap">
                  <div class="col">
                    <div class="text-h6 text-weight-bold">{{ carga.placa || 'Sem placa' }}</div>
                    <div class="text-caption text-grey-7">{{ pontosResumo(carga) }}</div>
                  </div>
                  <q-icon
                    :name="carga.sincronizado ? 'cloud_done' : 'cloud_off'"
                    :color="carga.sincronizado ? 'green-5' : 'orange-6'"
                    size="sm"
                  >
                    <q-tooltip>{{ carga.sincronizado ? 'Sincronizado' : 'Pendente' }}</q-tooltip>
                  </q-icon>
                </div>
              </q-card-section>

              <q-card-section class="q-py-xs">
                <div class="row no-wrap q-gutter-xs">
                  <div
                    v-for="(s, i) in colunas"
                    :key="s.etapa"
                    class="col"
                    :class="`bg-${corSegmento(carga, i)}`"
                    style="height: 6px; border-radius: 3px"
                  />
                </div>
              </q-card-section>

              <q-card-section class="q-pt-xs row items-center justify-between">
                <div class="text-body2 text-grey-9">{{ resumo(carga) }}</div>
                <q-btn
                  flat
                  color="primary"
                  label="Abrir"
                  icon-right="chevron_right"
                  @click.stop="abrir(carga)"
                />
              </q-card-section>
            </q-card>

            <div
              v-if="!(cargasPorEtapa[e.etapa] || []).length"
              class="text-grey-5 text-center q-pa-md"
            >
              Nenhum caminhão nesta etapa
            </div>
          </div>
        </div>
      </q-card>
    </div>

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn fab icon="add" color="primary" label="Carga" @click="novaCarga" />
    </q-page-sticky>

    <CargaDialog
      v-model="dialog"
      :carga="cargaSel"
      :novo="novo"
      @salvar="onSalvar"
      @avancar="onAvancar"
    />
  </q-page>
</template>
