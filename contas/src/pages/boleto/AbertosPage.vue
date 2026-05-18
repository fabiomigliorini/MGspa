<script setup>
import { computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { formataNumero, formataData } from '@components/formatters'
import { useBoletoStore } from 'src/stores/boletoStore'
import { ESTADO_COBRANCA, TIPOS_ABERTOS } from 'src/constants/tituloBoleto'
import BoletoTabs from 'src/components/BoletoTabs.vue'

const route = useRoute()
const router = useRouter()
const store = useBoletoStore()

const tipoAtual = computed(() => route.query.tipo || 'vencer7')
const categoriaAtual = computed(
  () =>
    store.abertosResumo.find((c) => c.tipo === tipoAtual.value) ||
    TIPOS_ABERTOS.find((t) => t.tipo === tipoAtual.value) || { label: tipoAtual.value },
)

watch(
  () => route.query.tipo,
  (tipo) => {
    if (tipo && route.name === 'boleto-abertos') store.carregarAbertos(tipo)
  },
)

onMounted(async () => {
  if (!route.query.tipo) {
    router.replace({ name: 'boleto-abertos', query: { tipo: 'vencer7' } })
    return
  }
  store.carregarResumoAbertos()
  store.carregarAbertos(tipoAtual.value)
})
</script>

<template>
  <q-page>
    <BoletoTabs />
    <div class="q-pa-md" style="max-width: 1086px; margin: auto">
      <q-card flat bordered class="q-mb-md">
        <q-card-section class="q-py-sm row items-center no-wrap">
          <div class="col">
            <div class="text-subtitle1 text-weight-bold">{{ categoriaAtual.label }}</div>
            <div class="text-caption text-grey-7">Boletos em aberto no banco</div>
          </div>
          <q-chip
            v-if="categoriaAtual.total != null"
            outline
            color="primary"
            text-color="primary"
            class="q-ml-sm"
          >
            {{ formataNumero(categoriaAtual.total) }}
            <span class="q-ml-sm text-grey-7">· {{ categoriaAtual.quantidade }}</span>
          </q-chip>
        </q-card-section>
      </q-card>

      <q-list bordered separator dense class="text-caption bg-white">
        <q-item class="text-weight-bold text-caption text-grey-8 gt-xs">
          <q-item-section>
            <div class="row items-center q-col-gutter-x-sm">
              <div class="col-12 col-sm-4 col-md-1">Vencimento</div>
              <div class="col-12 col-sm-4 col-md-1 text-right">Valor</div>
              <div class="col-12 col-sm-4 col-md-2">Título</div>
              <div class="col-12 col-sm-12 col-md-2">Cliente</div>
              <div class="col-6 col-sm-6 col-md-3">Portador</div>
              <div class="col-6 col-sm-6 col-md-2">Nosso Número</div>
              <div class="col-12 col-sm-12 col-md-1">Estado</div>
            </div>
          </q-item-section>
        </q-item>

        <q-item
          v-for="b in store.abertosLista"
          :key="b.codtituloboleto"
          clickable
          :to="'/titulo/' + b.codtitulo"
        >
          <q-item-section>
            <div class="row items-center q-col-gutter-x-sm">
              <div class="col-12 col-sm-4 col-md-1 ellipsis">
                {{ formataData(b.vencimento) }}
              </div>
              <div
                class="col-12 col-sm-4 col-md-1 text-right text-weight-bold ellipsis text-primary"
              >
                {{ formataNumero(b.valoratual) }}
              </div>
              <div class="col-12 col-sm-4 col-md-2 ellipsis">
                {{ b.numero }}
              </div>
              <div class="col-12 col-sm-12 col-md-2 text-primary text-weight-bold ellipsis">
                {{ b.fantasia }}
                <div v-if="b.valoratual != b.saldo" class="text-red text-caption">
                  * Valor diverge do saldo {{ formataNumero(b.saldo) }}
                </div>
              </div>
              <div class="col-6 col-sm-6 col-md-3 text-grey-7 ellipsis">{{ b.portador }}</div>
              <div class="col-6 col-sm-6 col-md-2 text-grey-7 ellipsis">{{ b.nossonumero }}</div>
              <div class="col-12 col-sm-12 col-md-1 text-grey-7 ellipsis">
                {{ ESTADO_COBRANCA[b.estadotitulocobranca] || '' }}
              </div>
            </div>
          </q-item-section>
        </q-item>

        <q-item v-if="!store.abertosLista.length && !store.carregandoAbertos">
          <q-item-section class="text-center text-grey-6">Nenhum boleto encontrado</q-item-section>
        </q-item>
      </q-list>

      <q-inner-loading :showing="store.carregandoAbertos" color="primary" />
    </div>
  </q-page>
</template>
