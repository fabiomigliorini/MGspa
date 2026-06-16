<script setup>
import { ref, computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useCargaStore } from 'src/stores/carga'
import { useSincronizacaoStore } from 'src/stores/sincronizacao'
import CargaDialog from 'components/CargaDialog.vue'

const store = useCargaStore()
const sinc = useSincronizacaoStore()

const { safras, codsafraAtiva, cargasPorEtapa, culturaAtiva } = storeToRefs(store)
const { online, sincronizando } = storeToRefs(sinc)

// Etapas em ordem do fluxo da carga (chegada → finalização).
const etapas = [
  { etapa: 'BRUTO', label: 'Peso Bruto', curto: 'Bruto', icon: 'scale', color: 'orange-8' },
  { etapa: 'CLASSIFICACAO', label: 'Classificação', curto: 'Classif.', icon: 'science', color: 'deep-purple-6' },
  { etapa: 'TARA', label: 'Tara', curto: 'Tara', icon: 'monitor_weight', color: 'teal-7' },
  { etapa: 'FINALIZADO', label: 'Finalizado', curto: 'Final', icon: 'task_alt', color: 'green-7' },
]

const proximoLabel = {
  BRUTO: 'Classificar',
  CLASSIFICACAO: 'Classificar',
  TARA: 'Pesar tara',
  FINALIZADO: 'Finalizar',
}

const dialog = ref(false)
const cargaSel = ref(null)
const novo = ref(false)

function abrir(carga) {
  cargaSel.value = JSON.parse(JSON.stringify(carga))
  novo.value = false
  dialog.value = true
}

function novoCaminhao() {
  cargaSel.value = store.nova()
  novo.value = true
  dialog.value = true
}

// Salvar fecha o modal (deixa a carga na etapa atual).
async function onSalvar(carga) {
  await store.salvar(carga)
  dialog.value = false
}

// Avançar persiste mas mantém o modal aberto pra continuar o fluxo.
async function onAvancar(carga) {
  await store.salvar(carga)
}

const pesosaca = computed(() => culturaAtiva.value?.pesosaca || 60)

const totalSecoDia = computed(() =>
  cargasPorEtapa.value.FINALIZADO.reduce((s, c) => s + (Number(c.pesoliquidoseco) || 0), 0),
)

function indiceEtapa(etapa) {
  return etapas.findIndex((e) => e.etapa === etapa)
}

function corEtapa(etapa) {
  return etapas.find((e) => e.etapa === etapa)?.color || 'grey-5'
}

// Cor de cada segmento da barra de progresso do card.
function corSegmento(carga, i) {
  return i <= indiceEtapa(carga.etapa) ? corEtapa(carga.etapa) : 'grey-3'
}

function fmt(v, dec = 0) {
  if (v === null || v === undefined || v === '') return '—'
  return Number(v).toLocaleString('pt-BR', { minimumFractionDigits: dec, maximumFractionDigits: dec })
}

function talhoes(carga) {
  if (!carga.plantios?.length) return 'Sem talhão'
  return carga.plantios.map((p) => p.rotulo || `Talhão ${p.codplantio}`).join(' · ')
}

function resumo(carga) {
  if (carga.etapa === 'FINALIZADO') {
    return `${fmt(carga.pesoliquidoseco)} kg · ${fmt((carga.pesoliquidoseco || 0) / pesosaca.value)} sc`
  }
  if (carga.tara) return `Líquido ${fmt(carga.pesoliquido)} kg`
  if (carga.pesobruto) return `Bruto ${fmt(carga.pesobruto)} kg`
  return carga.motorista || 'Aguardando pesagem'
}

onMounted(async () => {
  await store.carregarReferencias()
  await store.carregarCargas()
  store.sincronizar().catch(() => {})
})
</script>

<template>
  <q-page>
    <!-- Barra superior: safra + status -->
    <div class="bg-white q-px-md q-py-sm">
      <div class="row items-center no-wrap q-gutter-sm">
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
    </div>

    <!-- Etapas empilhadas verticalmente, na ordem do fluxo da carga -->
    <div class="bg-grey-2 q-pa-md q-gutter-md">
      <div v-for="e in etapas" :key="e.etapa">
        <!-- Cabeçalho da etapa -->
        <q-item :class="`bg-${e.color} text-white`" class="rounded-borders">
          <q-item-section avatar>
            <q-icon :name="e.icon" />
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-weight-medium">{{ e.label }}</q-item-label>
          </q-item-section>
          <q-item-section side>
            <q-badge color="white" :text-color="e.color" :label="cargasPorEtapa[e.etapa].length" />
          </q-item-section>
        </q-item>

        <!-- Total do dia (só na etapa Finalizado) -->
        <q-banner
          v-if="e.etapa === 'FINALIZADO' && cargasPorEtapa.FINALIZADO.length"
          rounded
          class="bg-green-1 text-green-10 q-mt-sm"
        >
          <template #avatar>
            <q-icon name="agriculture" color="green-8" />
          </template>
          <div class="text-weight-medium">Total colhido hoje</div>
          {{ fmt(totalSecoDia) }} kg · {{ fmt(totalSecoDia / pesosaca) }} sacas
        </q-banner>

        <!-- Cards das cargas desta etapa -->
        <q-card
          v-for="carga in cargasPorEtapa[e.etapa]"
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
                <div class="text-caption text-grey-7">{{ talhoes(carga) }}</div>
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

          <!-- Barra de progresso das etapas -->
          <q-card-section class="q-py-xs">
            <div class="row no-wrap q-gutter-xs">
              <div
                v-for="(s, i) in etapas"
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
              :label="proximoLabel[carga.etapa]"
              icon-right="chevron_right"
              @click.stop="abrir(carga)"
            />
          </q-card-section>
        </q-card>

        <!-- Etapa vazia -->
        <div
          v-if="!cargasPorEtapa[e.etapa].length"
          class="text-grey-5 text-center q-pa-md"
        >
          Nenhum caminhão nesta etapa
        </div>
      </div>
    </div>

    <!-- Adicionar caminhão -->
    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn fab icon="add" color="primary" label="Caminhão" @click="novoCaminhao" />
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
