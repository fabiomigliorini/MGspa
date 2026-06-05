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

const colunas = [
  { etapa: 'PATIO', label: 'Pátio', icon: 'local_shipping', color: 'blue-grey-7' },
  { etapa: 'BRUTO', label: 'Peso Bruto', icon: 'scale', color: 'orange-8' },
  { etapa: 'CLASSIFICACAO', label: 'Classificação', icon: 'science', color: 'deep-purple-6' },
  { etapa: 'TARA', label: 'Tara', icon: 'monitor_weight', color: 'teal-7' },
  { etapa: 'FINALIZADO', label: 'Finalizado', icon: 'task_alt', color: 'green-7' },
]

const dialog = ref(false)
const cargaSel = ref(null)

function abrir(carga) {
  cargaSel.value = JSON.parse(JSON.stringify(carga))
  dialog.value = true
}

function novoCaminhao() {
  cargaSel.value = store.nova()
  dialog.value = true
}

async function onSalvar(carga) {
  await store.salvar(carga)
  dialog.value = false
}

const pesosaca = computed(() => culturaAtiva.value?.pesosaca || 60)

const totalSecoDia = computed(() =>
  cargasPorEtapa.value.FINALIZADO.reduce((s, c) => s + (Number(c.pesoliquidoseco) || 0), 0),
)

function fmt(v, dec = 0) {
  if (v === null || v === undefined || v === '') return '—'
  return Number(v).toLocaleString('pt-BR', { minimumFractionDigits: dec, maximumFractionDigits: dec })
}

function talhoes(carga) {
  if (!carga.plantios?.length) return 'Sem talhão'
  return carga.plantios
    .map((p) => p.rotulo || `Talhão ${p.codplantio}`)
    .join(' · ')
}

function resumo(carga) {
  if (carga.etapa === 'FINALIZADO') {
    return `${fmt(carga.pesoliquidoseco)} kg · ${fmt((carga.pesoliquidoseco || 0) / pesosaca.value)} sc`
  }
  if (carga.tara) return `líquido ${fmt(carga.pesoliquido)} kg`
  if (carga.pesobruto) return `bruto ${fmt(carga.pesobruto)} kg`
  return carga.motorista || '—'
}

onMounted(async () => {
  await store.carregarReferencias()
  await store.carregarCargas()
  store.sincronizar().catch(() => {})
})
</script>

<template>
  <q-page>
    <q-toolbar class="bg-white text-grey-9 q-px-md q-gutter-sm">
      <q-select
        :model-value="codsafraAtiva"
        :options="safras"
        option-value="codsafra"
        option-label="safra"
        emit-value
        map-options
        outlined
        label="Safra"
        style="min-width: 220px"
        @update:model-value="store.definirSafra"
      />

      <q-space />

      <q-chip :color="online ? 'green-1' : 'orange-1'" :text-color="online ? 'green-9' : 'orange-9'">
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

      <q-btn unelevated color="primary" icon="add" label="Caminhão" @click="novoCaminhao" />
    </q-toolbar>

    <q-separator />

    <div class="row no-wrap q-gutter-md q-pa-md" style="overflow-x: auto">
      <q-card
        v-for="col in colunas"
        :key="col.etapa"
        flat
        bordered
        style="min-width: 300px; max-width: 340px"
        class="column"
      >
        <q-item :class="`bg-${col.color} text-white`">
          <q-item-section avatar>
            <q-icon :name="col.icon" />
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-weight-medium">{{ col.label }}</q-item-label>
          </q-item-section>
          <q-item-section side>
            <q-badge color="white" :text-color="col.color" :label="cargasPorEtapa[col.etapa].length" />
          </q-item-section>
        </q-item>

        <q-list separator>
          <q-item
            v-for="carga in cargasPorEtapa[col.etapa]"
            :key="carga.uuid"
            clickable
            @click="abrir(carga)"
          >
            <q-item-section>
              <q-item-label class="text-weight-medium">{{ carga.placa || 'Sem placa' }}</q-item-label>
              <q-item-label caption>{{ talhoes(carga) }}</q-item-label>
              <q-item-label caption class="text-grey-8">{{ resumo(carga) }}</q-item-label>
            </q-item-section>
            <q-item-section side top>
              <q-icon
                :name="carga.sincronizado ? 'cloud_done' : 'cloud_off'"
                :color="carga.sincronizado ? 'green-5' : 'orange-6'"
              >
                <q-tooltip>{{ carga.sincronizado ? 'Sincronizado' : 'Pendente' }}</q-tooltip>
              </q-icon>
            </q-item-section>
          </q-item>

          <q-item v-if="!cargasPorEtapa[col.etapa].length">
            <q-item-section class="text-grey-5 text-center">vazio</q-item-section>
          </q-item>
        </q-list>

        <template v-if="col.etapa === 'FINALIZADO' && cargasPorEtapa.FINALIZADO.length">
          <q-separator />
          <q-item>
            <q-item-section class="text-caption text-grey-8">
              Total: {{ fmt(totalSecoDia) }} kg · {{ fmt(totalSecoDia / pesosaca) }} sc
            </q-item-section>
          </q-item>
        </template>
      </q-card>
    </div>

    <CargaDialog v-model="dialog" :carga="cargaSel" @salvar="onSalvar" />
  </q-page>
</template>
