<script setup>
// Card de um vale compras usado como pagamento neste negócio: mostra qual
// vale foi usado e o saldo atual dele. Com saldo, imprime o Contra Vale (o
// mesmo título, mesmo código VAL…, com o saldo que sobrou); esgotado, o card
// fica só para consulta.
import { computed } from 'vue'
import { Notify } from 'quasar'
import { api } from 'boot/axios'
import { abrirPdf } from '@components/abrirPdf'
import { negocioStore } from 'stores/negocio'
import { formataNumero, formataCodigo } from '@components/formatters'

const props = defineProps({
  pagamento: { type: Object, required: true },
})

const sNegocio = negocioStore()

const saldo = computed(() => parseFloat(props.pagamento.valesaldo) || 0)

const urlTitulo = () => process.env.CONTAS_URL + '/titulo/' + props.pagamento.codtitulo

const urlVale = () => '/v1/pdv/negocio/' + sNegocio.negocio.codnegocio + '/vale'

const imprimir = async () => {
  if (!sNegocio.padrao.impressora) {
    Notify.create({
      type: 'negative',
      message: 'Nenhuma impressora térmica selecionada!',
      timeout: 3000,
      actions: [{ icon: 'close', color: 'white' }],
    })
    return
  }
  await api.post(urlVale() + '/' + sNegocio.padrao.impressora, null, {
    params: { codtitulo: props.pagamento.codtitulo },
  })
  Notify.create({
    type: 'positive',
    message: 'Impressão Solicitada!',
    timeout: 1000,
    actions: [{ icon: 'close', color: 'white' }],
  })
}

const abrir = () =>
  abrirPdf(
    api,
    urlVale(),
    { codtitulo: props.pagamento.codtitulo },
    { title: 'Contra Vale', size: 'cupom', onImprimir: imprimir },
  )
</script>

<template>
  <!-- mesmo formato do card do vale vendido -->
  <div class="col-xs-6 col-sm-4 col-md-4 col-lg-3 col-xl-2">
    <q-card flat bordered>
      <!-- cabecalho abre o titulo do vale no app de contas, como a nota abre o app de notas -->
      <q-item clickable v-ripple :href="urlTitulo()" target="_blank">
        <q-item-section avatar>
          <q-avatar icon="card_giftcard" color="teal" text-color="white" />
        </q-item-section>
        <q-item-section>
          <q-item-label class="ellipsis">Contra Vale</q-item-label>
          <q-item-label caption class="ellipsis">{{ pagamento.valenumero }}</q-item-label>
          <q-item-label caption class="ellipsis">
            {{ formataCodigo(pagamento.codtitulo) }}
          </q-item-label>
        </q-item-section>
      </q-item>
      <q-separator inset />

      <q-item>
        <q-item-section>
          <q-item-label class="ellipsis">{{ pagamento.valefavorecido }}</q-item-label>
        </q-item-section>
      </q-item>

      <q-item>
        <q-item-section>
          <q-item-label>
            R$
            <span class="text-weight-bold">{{ formataNumero(pagamento.valorpagamento) }}</span>
          </q-item-label>
          <q-item-label caption lines="1">Usado neste negócio</q-item-label>
        </q-item-section>
      </q-item>

      <q-item>
        <q-item-section>
          <q-item-label v-if="saldo > 0">
            R$ <span class="text-weight-bold">{{ formataNumero(saldo) }}</span>
          </q-item-label>
          <q-item-label v-else class="text-grey-7">Esgotado</q-item-label>
          <q-item-label caption lines="1">Saldo do vale</q-item-label>
        </q-item-section>
      </q-item>

      <!-- vale esgotado: o card fica so' para consulta, sem papel para imprimir -->
      <template v-if="saldo > 0">
        <q-separator inset />
        <q-card-actions align="right">
          <q-btn flat dense round size="sm" color="primary" icon="print" @click="abrir()">
            <q-tooltip>Imprimir Contra Vale</q-tooltip>
          </q-btn>
        </q-card-actions>
      </template>
    </q-card>
  </div>
</template>
