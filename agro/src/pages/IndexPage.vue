<script setup>
import { computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useAuth } from 'src/composables/useAuth'
import { useCargaStore } from 'src/stores/carga'
import { useSincronizacaoStore } from 'src/stores/sincronizacao'

const { usuario } = useAuth()
const store = useCargaStore()
const sinc = useSincronizacaoStore()
const { safras, codsafraAtiva, cargas, produtividade, safraAtiva } = storeToRefs(store)
const { online } = storeToRefs(sinc)

// Independente do sentido/agrupamento e dos nomes de etapa: tudo que não está
// finalizado está "no pátio".
const noPatio = computed(
  () => cargas.value.filter((c) => !c.inativo && c.etapa !== 'FINALIZADO').length,
)
const finalizados = computed(
  () => cargas.value.filter((c) => !c.inativo && c.etapa === 'FINALIZADO').length,
)
const pendentes = computed(() => cargas.value.filter((c) => !c.sincronizado && !c.inativo).length)

function fmt(v, dec = 0) {
  if (v === null || v === undefined || v === '') return '0'
  return Number(v).toLocaleString('pt-BR', {
    minimumFractionDigits: dec,
    maximumFractionDigits: dec,
  })
}

const kpis = computed(() => [
  { label: 'No pátio', valor: fmt(noPatio.value), icon: 'local_shipping', cor: 'orange' },
  { label: 'Recebidas', valor: fmt(finalizados.value), icon: 'task_alt', cor: 'green' },
  {
    label: 'Colhido (sacas)',
    valor: fmt(produtividade.value.sacas),
    icon: 'grain',
    cor: 'amber',
  },
  {
    label: 'Produtividade (sc/ha)',
    valor: fmt(produtividade.value.produtividadeMedia, 1),
    icon: 'trending_up',
    cor: 'teal',
  },
])

const atalhos = [
  {
    label: 'Pátio de Cargas',
    sub: 'Movimentação, carga e descarga.',
    icon: 'local_shipping',
    cor: 'green-7',
    to: { name: 'carga' },
  },
  {
    label: 'Estoque & Extrato',
    sub: 'Saldos por unidade e movimentos',
    icon: 'inventory_2',
    cor: 'amber-8',
    to: { name: 'extrato' },
  },
  {
    label: 'Safras',
    sub: 'Plantio, contratos e produtividade',
    icon: 'eco',
    cor: 'light-green-8',
    to: { name: 'safras' },
  },
  {
    label: 'Fazendas',
    sub: 'Talhões, mapa e produtividade',
    icon: 'agriculture',
    cor: 'green-7',
    to: { name: 'fazendas' },
  },
  {
    label: 'Culturas',
    sub: 'Variedades, descontos e safras',
    icon: 'category',
    cor: 'blue-grey-7',
    to: { name: 'culturas' },
  },
  {
    label: 'Unidades Armazenadoras',
    sub: 'Silos, armazéns e capacidade',
    icon: 'warehouse',
    cor: 'amber-8',
    to: { name: 'unidades-armazenadoras' },
  },
]

onMounted(async () => {
  await store.carregarReferencias()
  await store.carregarCargas()
  store.sincronizar().catch(() => {})
})
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <!-- Cabeçalho -->
      <q-card bordered flat class="q-mb-md">
        <q-card-section class="row items-center no-wrap q-gutter-md">
          <q-avatar size="56px" color="green-1" text-color="green-8" icon="agriculture" />
          <div class="col">
            <div class="text-h5">MG Agro</div>
            <div class="text-subtitle2 text-grey-7">
              Olá{{ usuario?.usuario ? ', ' + usuario.usuario : '' }} — controle de safra
            </div>
          </div>
          <q-select
            v-if="safras.length"
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
          <q-chip
            :color="online ? 'green-1' : 'orange-1'"
            :text-color="online ? 'green-9' : 'orange-9'"
            :icon="online ? 'cloud_done' : 'cloud_off'"
            :label="online ? 'Online' : 'Offline'"
          />
        </q-card-section>
      </q-card>

      <!-- Sem safra -->
      <q-banner v-if="!safras.length" rounded class="bg-amber-1 text-amber-9 q-mb-md">
        <template #avatar><q-icon name="eco" color="amber-8" /></template>
        Nenhuma safra cadastrada ainda. Comece criando uma safra.
        <template #action>
          <q-btn flat color="amber-9" label="Criar safra" :to="{ name: 'safras' }" />
        </template>
      </q-banner>

      <!-- KPIs -->
      <div class="row q-col-gutter-md q-mb-md">
        <div v-for="k in kpis" :key="k.label" class="col-6 col-md-3">
          <q-card flat bordered>
            <q-card-section class="row items-center no-wrap">
              <q-avatar :color="`${k.cor}-1`" :text-color="`${k.cor}-8`" :icon="k.icon" />
              <div class="q-ml-md">
                <div class="text-h5 text-weight-bold">{{ k.valor }}</div>
                <div class="text-caption text-grey-7">{{ k.label }}</div>
              </div>
            </q-card-section>
          </q-card>
        </div>
      </div>

      <!-- Pendências de sincronização -->
      <q-banner v-if="pendentes" rounded class="bg-orange-1 text-orange-9 q-mb-md">
        <template #avatar><q-icon name="cloud_off" color="orange-8" /></template>
        {{ pendentes }} carga(s) aguardando sincronização.
        <template #action>
          <q-btn flat color="orange-9" label="Sincronizar" @click="store.sincronizar({ force: true })" />
        </template>
      </q-banner>

      <!-- Atalhos -->
      <div class="row q-col-gutter-md">
        <div v-for="a in atalhos" :key="a.label" class="col-12 col-sm-6 col-md-4">
          <q-card flat bordered class="overflow-hidden">
            <q-item clickable v-ripple :to="a.to" class="q-py-md">
              <q-item-section avatar>
                <q-avatar :color="a.cor" text-color="white" :icon="a.icon" />
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-subtitle1">{{ a.label }}</q-item-label>
                <q-item-label caption>{{ a.sub }}</q-item-label>
              </q-item-section>
              <q-item-section side><q-icon name="chevron_right" /></q-item-section>
            </q-item>
          </q-card>
        </div>
      </div>

      <div v-if="safraAtiva" class="text-caption text-grey-6 q-mt-md text-center">
        Safra atual: {{ safraAtiva.safra }}
      </div>
    </div>
  </q-page>
</template>
