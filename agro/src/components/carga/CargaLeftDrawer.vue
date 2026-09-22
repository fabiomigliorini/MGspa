<script setup>
// Drawer esquerdo do pátio (espelha OfflineLeftDrawerTabNegocios do PDV):
// safra + status do sync, "No pátio" (qualquer sentido, qualquer dia) e
// "Finalizadas" (as últimas). A seleção é pela rota carga/:uuid.
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import { useCargaStore } from 'src/stores/carga'
import { useSincronizacaoStore } from 'src/stores/sincronizacao'
import { fmtNumero } from 'src/utils/carga'
import CargaListItem from './CargaListItem.vue'

const router = useRouter()
const $q = useQuasar()
const store = useCargaStore()
const sinc = useSincronizacaoStore()

const { safras, codsafraAtiva, cargasNoPatio, cargasFinalizadas, totaisFinalizadas, pesosaca } =
  storeToRefs(store)
const { online, sincronizando } = storeToRefs(sinc)

function rota(carga) {
  return { name: 'carga', params: { uuid: carga.uuid } }
}

function novaCarga() {
  // Sem safra ativa (ex.: cold start offline sem cache) a carga nasceria com
  // codsafra:null — invisível na lista e rejeitada pra sempre no sync. Barra antes.
  if (!codsafraAtiva.value) {
    $q.notify({
      type: 'warning',
      message: 'Sincronize ao menos uma safra antes de registrar cargas.',
    })
    return
  }
  router.push({ name: 'carga', params: { uuid: 'nova' } })
}
</script>

<template>
  <div class="q-pa-sm">
    <div class="row items-center no-wrap q-gutter-x-xs">
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
      <q-btn
        flat
        round
        :icon="online ? 'cloud_done' : 'cloud_off'"
        :color="online ? 'green-7' : 'orange-7'"
        :loading="sincronizando"
        @click="store.sincronizar({ force: true })"
      >
        <q-tooltip>{{ online ? 'Online' : 'Offline' }} — sincronizar tudo</q-tooltip>
      </q-btn>
    </div>
  </div>

  <q-separator />

  <q-item-label header class="row items-center">
    No pátio
    <q-badge color="orange-7" class="q-ml-sm" :label="cargasNoPatio.length" />
    <q-space />
    <q-btn flat dense color="primary" icon="add" label="F2" @click="novaCarga">
      <q-tooltip>Nova carga</q-tooltip>
    </q-btn>
  </q-item-label>
  <template v-for="c in cargasNoPatio" :key="c.uuid">
    <CargaListItem
      :carga="c"
      :pesosaca="pesosaca"
      :to="rota(c)"
      :aviso="store.avisoClassificacao(c)"
    />
    <q-separator />
  </template>
  <div v-if="!cargasNoPatio.length" class="text-grey-5 text-center q-pa-md">
    Nenhum caminhão no pátio
  </div>

  <q-item-label header class="row items-center">
    Finalizadas
    <q-badge color="green-7" class="q-ml-sm" :label="cargasFinalizadas.length" />
  </q-item-label>
  <q-banner
    v-if="cargasFinalizadas.length"
    class="bg-green-1 text-green-10 q-mx-sm q-mb-sm rounded-borders"
  >
    <div class="text-weight-medium">
      {{ fmtNumero(totaisFinalizadas.liquido) }} kg -
      {{ fmtNumero(totaisFinalizadas.sacas, 1) }} sacas
    </div>
    <div class="text-caption text-weight-medium">
      Descontos de {{ fmtNumero(totaisFinalizadas.desconto) }} kg - ({{
        fmtNumero(totaisFinalizadas.descontoSacas, 1)
      }}sc)
    </div>
  </q-banner>
  <template v-for="c in cargasFinalizadas" :key="c.uuid">
    <CargaListItem :carga="c" :pesosaca="pesosaca" :to="rota(c)" />
    <q-separator />
  </template>
  <div v-if="!cargasFinalizadas.length" class="text-grey-5 text-center q-pa-md">
    Nenhuma carga finalizada
  </div>
</template>
