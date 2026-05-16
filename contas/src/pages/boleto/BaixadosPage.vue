<script setup>
import { onMounted, watch } from 'vue'
import { date } from 'quasar'
import { formataNumero } from '@components/formatters'
import { useBoletoStore } from 'src/stores/boletoStore'
import { ESTADO_COBRANCA, TIPO_BAIXA } from 'src/constants/tituloBoleto'
import BoletoTabs from 'src/components/BoletoTabs.vue'

const store = useBoletoStore()

const formatData = (v) => (v ? date.formatDate(v, 'DD/MM/YYYY') : '')

watch(
  () => [store.baixadosFiltros.codportador, store.baixadosFiltros.tipobaixa],
  () => store.carregarBaixados(),
)

onMounted(() => store.carregarBaixados())
</script>

<template>
  <q-page>
    <BoletoTabs />
    <div class="q-pa-md" style="max-width: 1086px; margin: auto">
      <q-card flat bordered class="q-mb-md">
        <q-card-section class="q-py-sm">
          <div class="text-subtitle1 text-weight-bold">Boletos Baixados</div>
          <div class="text-caption text-grey-7">
            Títulos com boleto <b>baixado</b>, mas com <b>saldo em aberto</b> no sistema.
          </div>
        </q-card-section>
      </q-card>

      <q-list bordered separator dense class="text-caption bg-white">
        <q-item class="text-weight-bold text-grey-8 gt-xs">
          <q-item-section>
            <div class="row items-center q-col-gutter-x-sm">
              <div class="col-12 col-sm-4 col-md-1">Vencimento</div>
              <div class="col-12 col-sm-4 col-md-1 text-right">Valor</div>
              <div class="col-12 col-sm-4 col-md-2">Título</div>
              <div class="col-12 col-sm-12 col-md-2">Cliente</div>
              <div class="col-6 col-sm-6 col-md-2">Portador</div>
              <div class="col-6 col-sm-6 col-md-2">Nosso Número</div>
              <div class="col-12 col-sm-12 col-md-2">Baixa</div>
            </div>
          </q-item-section>
        </q-item>

        <q-item
          v-for="b in store.baixadosLista"
          :key="b.codtituloboleto"
          clickable
          :to="'/titulo/' + b.codtitulo"
        >
          <q-item-section>
            <div class="row items-center q-col-gutter-x-sm">
              <div class="col-12 col-sm-4 col-md-1 ellipsis">
                {{ formatData(b.vencimento) }}
              </div>
              <div
                class="col-12 col-sm-4 col-md-1 text-right text-weight-bold ellipsis text-primary"
              >
                {{ formataNumero(b.valoratual) }}
              </div>
              <div class="col-12 col-sm-4 col-md-2 ellipsis">
                {{ b.numero }}
              </div>
              <div class="col-12 col-sm-12 col-md-2 text-primary ellipsis text-weight-bold">
                {{ b.fantasia }}
                <div v-if="b.valoratual != b.saldo" class="text-red text-caption">
                  * Valor diverge do saldo {{ formataNumero(b.saldo) }}
                </div>
              </div>
              <div class="col-6 col-sm-6 col-md-2 text-grey-7 ellipsis">{{ b.portador }}</div>
              <div class="col-6 col-sm-6 col-md-2 text-grey-7 ellipsis">{{ b.nossonumero }}</div>
              <div class="col-12 col-sm-12 col-md-2 text-grey-7 ellipsis">
                {{ ESTADO_COBRANCA[b.estadotitulocobranca] || '' }}
                <template v-if="b.tipobaixatitulo">
                  {{ TIPO_BAIXA[b.tipobaixatitulo] || '' }}
                </template>
              </div>
            </div>
          </q-item-section>
        </q-item>

        <q-item v-if="!store.baixadosLista.length && !store.carregandoBaixados">
          <q-item-section class="text-center text-grey-6">Nenhum boleto encontrado</q-item-section>
        </q-item>
      </q-list>

      <q-inner-loading :showing="store.carregandoBaixados" color="primary" />
    </div>
  </q-page>
</template>
