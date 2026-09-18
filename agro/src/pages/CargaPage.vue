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
import { ETAPA_META, cargaFinalizada, fmtNumero } from 'src/utils/carga'
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
  let salva
  try {
    salva = await store.salvar(carga)
  } catch (e) {
    // Falha ao gravar no Dexie (cota, aba em modo privado): a promessa morria em
    // silêncio e o clique não produzia NADA na tela.
    $q.notify({
      type: 'negative',
      message: 'Não foi possível gravar a carga neste dispositivo.',
      caption: String(e?.message || e),
    })
    throw e
  }
  if (salva.codsafra && salva.codsafra !== codsafraAtiva.value) {
    await store.definirSafra(salva.codsafra)
  }
  return salva
}

// Todo clique responde. O envio ao servidor é assíncrono (e avisa sozinho se for
// rejeitado); aqui confirmamos o que já está garantido: gravado no aparelho.
function avisarGravado(carga) {
  if (cargaFinalizada(carga)) {
    $q.notify({
      type: 'positive',
      icon: 'task_alt',
      message: `Carga finalizada — ${fmtNumero(carga.liquido)} kg líquidos.`,
      caption: 'Ela foi pra "Finalizadas", à esquerda — é de lá que ela reabre pra imprimir.',
      timeout: 6000,
      actions: [
        {
          label: 'Imprimir',
          color: 'white',
          handler: () => router.replace({ name: 'carga', params: { uuid: carga.uuid } }),
        },
      ],
    })
    return
  }
  $q.notify({
    type: 'positive',
    icon: ETAPA_META[carga.etapa]?.icon,
    message: `Etapa atual: ${ETAPA_META[carga.etapa]?.label || carga.etapa}.`,
  })
}

// Romaneio FECHADO → o centro volta em branco, pronto pro próximo caminhão (o
// mesmo efeito do F2). Só no fechamento: nas etapas do meio o caminhão ainda vai
// voltar à balança, e a carga precisa continuar aberta pra receber o próximo peso.
function limparParaProxima() {
  router.replace({ name: 'carga', params: { uuid: 'nova' } })
}

async function onSalvar(carga) {
  const salva = await persistir(carga)
  if (novo.value) router.replace({ name: 'carga', params: { uuid: salva.uuid } })
  avisarGravado(salva)
  if (cargaFinalizada(salva)) limparParaProxima()
}
async function onAvancar(carga) {
  const salva = await persistir(carga)
  avisarGravado(salva)
  if (cargaFinalizada(salva)) limparParaProxima()
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
    <!-- `key` = uuid: trocar de carga REMONTA o formulário. É o que devolve o
         cursor pra placa na carga em branco que abre depois de finalizar; sem
         isso o autofocus só valeria na primeira montagem da página. Salvar a
         MESMA carga não muda o uuid, então nada remonta no meio da digitação. -->
    <CargaForm
      v-if="cargaSel"
      ref="formRef"
      :key="cargaSel.uuid"
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
