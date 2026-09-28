<script setup>
// Drawer esquerdo do pátio (espelha OfflineLeftDrawerTabNegocios do PDV):
// "No pátio" (qualquer sentido, qualquer dia, qualquer safra) e "Finalizadas"
// (as últimas). A seleção é pela rota carga/:uuid. Sem filtro de safra: o pátio
// é físico — a safra de cada carga aparece no item e se escolhe no modal dela.
//
// Sem botão de sincronizar aqui: o gatilho é único e vive fora do drawer. Este
// componente não fala com a store de sincronização.
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import { useCargaStore } from 'src/stores/carga'
import { fmtNumero } from 'src/utils/carga'
import CargaListItem from './CargaListItem.vue'

const router = useRouter()
const $q = useQuasar()
const store = useCargaStore()

const { safrasAtivas, cargasNoPatio, cargasFinalizadas, totaisFinalizadas } = storeToRefs(store)

function rota(carga) {
  return { name: 'carga', params: { uuid: carga.uuid } }
}

function novaCarga() {
  // Sem safra no cache (ex.: cold start offline) não há safra pra escolher no
  // modal de Operação — a carga não teria como ser registrada. Barra antes.
  if (!safrasAtivas.value.length) {
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
      :pesosaca="store.pesosacaDaCarga(c)"
      :safra="store.safraDaCarga(c)?.safra"
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
    <CargaListItem
      :carga="c"
      :pesosaca="store.pesosacaDaCarga(c)"
      :safra="store.safraDaCarga(c)?.safra"
      :to="rota(c)"
    />
    <q-separator />
  </template>
  <div v-if="!cargasFinalizadas.length" class="text-grey-5 text-center q-pa-md">
    Nenhuma carga finalizada
  </div>
</template>
