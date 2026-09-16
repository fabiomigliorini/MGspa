<script setup>
// Pátio de Cargas — centro da tela (espelha o IndexPage do PDV): a carga aberta
// pela rota carga/:uuid (ou 'nova') no formulário; listagem no drawer esquerdo
// (CargaLeftDrawer) e resumo no direito (CargaResumo), ambos via meta da rota.
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import { db } from 'boot/db'
import { useCargaStore } from 'src/stores/carga'
import CargaForm from 'components/carga/CargaForm.vue'

const route = useRoute()
const router = useRouter()
const $q = useQuasar()
const store = useCargaStore()
const { cargaAtiva, codsafraAtiva } = storeToRefs(store)

const formRef = ref(null)
const cargaNova = ref(null)
const carregado = ref(false)

const novo = computed(() => route.params.uuid === 'nova')
// Nova vive só em memória até "Registrar" (senão um clique em "Nova" já criaria
// lixo no pátio e no servidor); as demais vêm da store (Dexie).
const cargaSel = computed(() => (novo.value ? cargaNova.value : cargaAtiva.value))

async function selecionar(uuid) {
  if (!carregado.value) return
  if (!uuid) {
    store.abrir(null)
    cargaNova.value = null
    return
  }
  if (uuid === 'nova') {
    if (!codsafraAtiva.value) {
      $q.notify({
        type: 'warning',
        message: 'Sincronize ao menos uma safra antes de registrar cargas.',
      })
      router.replace({ name: 'carga' })
      return
    }
    store.abrir(null)
    cargaNova.value = store.nova()
    return
  }
  cargaNova.value = null
  store.abrir(uuid)
  if (cargaAtiva.value) return
  // Não está na safra ativa: pode ser link de outra safra — troca a safra.
  const c = await db.carga.get(uuid)
  if (c && c.codsafra !== codsafraAtiva.value) {
    await store.definirSafra(c.codsafra)
    if (cargaAtiva.value) return
  }
  $q.notify({ type: 'warning', message: 'Carga não encontrada neste dispositivo.' })
  router.replace({ name: 'carga' })
}
watch(() => route.params.uuid, selecionar)

// Grava e, se o talhão escolhido no mapa levou a carga pra outra safra, troca
// a safra ativa da listagem — senão a carga sumiria da lista após salvar.
async function persistir(carga) {
  const salva = await store.salvar(carga)
  if (salva.codsafra && salva.codsafra !== codsafraAtiva.value) {
    await store.definirSafra(salva.codsafra)
  }
  return salva
}
async function onSalvar(carga) {
  const salva = await persistir(carga)
  if (novo.value) router.replace({ name: 'carga', params: { uuid: salva.uuid } })
}
async function onAvancar(carga) {
  await persistir(carga)
}
async function onCancelar(carga) {
  // Já no servidor → inativa (estorna); pendente local → descarta do Dexie.
  if (carga.codcarga || carga.sincronizado) await store.inativar(carga)
  else await store.descartarPendente(carga)
  router.replace({ name: 'carga' })
}

function novaCarga() {
  router.push({ name: 'carga', params: { uuid: 'nova' } })
}

// Atalhos (padrão do PDV): F2 nova, F3 ação principal da etapa, F4 imprimir.
function hotkeys(event) {
  switch (event.key) {
    case 'F2':
      event.preventDefault()
      novaCarga()
      break
    case 'F3':
      event.preventDefault()
      formRef.value?.submit()
      break
    case 'F4':
      event.preventDefault()
      formRef.value?.imprimir()
      break
  }
}

function pageStyleFn(offset) {
  return { minHeight: `calc(100vh - ${offset || 80}px)` }
}

onMounted(async () => {
  await store.carregarReferencias()
  await store.carregarCargas()
  carregado.value = true
  await selecionar(route.params.uuid)
  store.sincronizar().catch(() => {})
  document.addEventListener('keydown', hotkeys)
})

onUnmounted(() => {
  document.removeEventListener('keydown', hotkeys)
  store.abrir(null)
})
</script>

<template>
  <q-page class="bg-grey-2" :style-fn="pageStyleFn">
    <CargaForm
      v-if="cargaSel"
      ref="formRef"
      :carga="cargaSel"
      :novo="novo"
      @salvar="onSalvar"
      @avancar="onAvancar"
      @cancelar="onCancelar"
    />

    <div v-else class="absolute-center text-center text-grey-6 q-pa-md">
      <q-icon name="local_shipping" size="120px" color="grey-4" />
      <div class="text-h6 q-mt-md">Nenhuma carga aberta</div>
      <div class="text-body2 q-mb-md">
        Escolha um caminhão na lista ao lado ou registre a chegada de um novo.
      </div>
      <q-btn
        color="primary"
        icon="add"
        label="Nova carga (F2)"
        :disable="!codsafraAtiva"
        @click="novaCarga"
      >
        <q-tooltip v-if="!codsafraAtiva">Sincronize uma safra antes de registrar cargas</q-tooltip>
      </q-btn>
    </div>
  </q-page>
</template>
