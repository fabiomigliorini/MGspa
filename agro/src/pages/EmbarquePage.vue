<script setup>
import { ref, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useEmbarqueStore } from 'src/stores/embarque'
import { useSincronizacaoStore } from 'src/stores/sincronizacao'
import EmbarqueDialog from 'components/EmbarqueDialog.vue'

const store = useEmbarqueStore()
const sinc = useSincronizacaoStore()
const { embarquesPorEtapa } = storeToRefs(store)
const { online, sincronizando } = storeToRefs(sinc)

const colunas = [
  { etapa: 'PATIO', label: 'Pátio', icon: 'local_shipping', color: 'blue-grey-7' },
  { etapa: 'TARA', label: 'Tara', icon: 'monitor_weight', color: 'teal-7' },
  { etapa: 'CLASSIFICACAO', label: 'Classificação', icon: 'science', color: 'deep-purple-6' },
  { etapa: 'BRUTO', label: 'Peso Bruto', icon: 'scale', color: 'orange-8' },
  { etapa: 'FISCAL', label: 'Nota Fiscal', icon: 'receipt_long', color: 'deep-orange-7' },
  { etapa: 'DESPACHADO', label: 'Despachado', icon: 'check_circle', color: 'green-7' },
]

const dialog = ref(false)
const sel = ref(null)

function abrir(e) {
  sel.value = JSON.parse(JSON.stringify(e))
  dialog.value = true
}
function novo() {
  sel.value = store.nova()
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
  if (e.etapa === 'DESPACHADO' || e.pesoliquidoseco) return `${fmt(e.pesoliquidoseco)} kg seco`
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
  <q-page>
    <q-toolbar class="bg-white text-grey-9 q-px-md q-gutter-sm">
      <q-icon name="outbound" size="sm" color="green-7" />
      <div class="text-subtitle1">Pátio de Expedição</div>
      <q-space />
      <q-chip :color="online ? 'green-1' : 'orange-1'" :text-color="online ? 'green-9' : 'orange-9'">
        <q-icon :name="online ? 'cloud_done' : 'cloud_off'" class="q-mr-xs" />
        {{ online ? 'Online' : 'Offline' }}
      </q-chip>
      <q-btn flat round icon="sync" :loading="sincronizando" color="grey-7" @click="store.sincronizar()">
        <q-tooltip>Sincronizar</q-tooltip>
      </q-btn>
      <q-btn unelevated color="primary" icon="add" label="Caminhão" @click="novo" />
    </q-toolbar>

    <q-separator />

    <div class="row no-wrap q-gutter-md q-pa-md" style="overflow-x: auto">
      <q-card
        v-for="col in colunas"
        :key="col.etapa"
        flat
        bordered
        class="overflow-hidden"
        style="min-width: 280px; max-width: 320px"
      >
        <q-item :class="`bg-${col.color} text-white`">
          <q-item-section avatar><q-icon :name="col.icon" /></q-item-section>
          <q-item-section><q-item-label class="text-weight-medium">{{ col.label }}</q-item-label></q-item-section>
          <q-item-section side>
            <q-badge color="white" :text-color="col.color" :label="embarquesPorEtapa[col.etapa].length" />
          </q-item-section>
        </q-item>

        <q-list separator>
          <q-item
            v-for="e in embarquesPorEtapa[col.etapa]"
            :key="e.uuid"
            clickable
            @click="abrir(e)"
          >
            <q-item-section>
              <q-item-label class="text-weight-medium">{{ e.placa || 'Sem placa' }}</q-item-label>
              <q-item-label caption>{{ contratos(e) }}</q-item-label>
              <q-item-label caption class="text-grey-8">{{ resumo(e) }}</q-item-label>
            </q-item-section>
            <q-item-section side top>
              <q-icon
                :name="e.sincronizado ? 'cloud_done' : 'cloud_off'"
                :color="e.sincronizado ? 'green-5' : 'orange-6'"
              />
            </q-item-section>
          </q-item>
          <q-item v-if="!embarquesPorEtapa[col.etapa].length">
            <q-item-section class="text-grey-5 text-center">vazio</q-item-section>
          </q-item>
        </q-list>
      </q-card>
    </div>

    <EmbarqueDialog v-model="dialog" :embarque="sel" @salvar="onSalvar" />
  </q-page>
</template>
