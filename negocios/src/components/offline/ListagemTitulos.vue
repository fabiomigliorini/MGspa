<script setup>
import { Notify } from 'quasar'
import { api } from 'boot/axios'
import { abrirPdf } from '@components/abrirPdf'
import { formataNumero, formataCodigo, formataData } from '@components/formatters'
import { negocioStore } from 'stores/negocio'
import moment from 'moment/min/moment-with-locales'
moment.locale('pt-br')

const TIPO_VALE = 3

const sNegocio = negocioStore()

const urlTitulo = (codtitulo) => {
  return process.env.CONTAS_URL + '/titulo/' + codtitulo
}

// credito de vale gerado pelo negocio (ex.: devolucao): com saldo, imprime o
// vale; esgotado, o card fica so para consulta
const ehValeComSaldo = (titulo) => titulo.codtipotitulo == TIPO_VALE && titulo.saldo < 0

const urlVale = () => '/v1/pdv/negocio/' + sNegocio.negocio.codnegocio + '/vale'

const imprimir = async (codtitulo) => {
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
    params: { codtitulo },
  })
  Notify.create({
    type: 'positive',
    message: 'Impressão Solicitada!',
    timeout: 1000,
    actions: [{ icon: 'close', color: 'white' }],
  })
}

const abrir = (codtitulo) =>
  abrirPdf(
    api,
    urlVale(),
    { codtitulo },
    { title: 'Vale Compras', size: 'cupom', onImprimir: () => imprimir(codtitulo) },
  )
</script>
<template>
  <div
    class="col-xs-6 col-sm-4 col-md-4 col-lg-3 col-xl-2"
    v-for="titulo in sNegocio.negocio.titulos"
    :key="titulo.codtitulo"
  >
    <q-card flat bordered class="full-height column no-wrap">
      <q-item clickable v-ripple :href="urlTitulo(titulo.codtitulo)" target="_blank">
        <q-item-section avatar>
          <q-avatar icon="receipt" color="primary" text-color="white"> </q-avatar>
        </q-item-section>
        <q-item-section>
          <q-item-label class="ellipsis"> Título </q-item-label>
          <q-item-label caption class="ellipsis">
            {{ titulo.numero }}
          </q-item-label>
          <q-item-label caption class="ellipsis">
            {{ formataCodigo(titulo.codtitulo) }}
          </q-item-label>
        </q-item-section>
      </q-item>
      <q-separator inset />

      <q-item>
        <q-item-section>
          <q-item-label class="ellipsis">
            {{ formataData(titulo.vencimento) }}
          </q-item-label>
          <q-item-label class="ellipsis" caption lines="1">
            {{ moment(titulo.vencimento).fromNow() }}
          </q-item-label>
        </q-item-section>
      </q-item>

      <q-item>
        <q-item-section>
          <q-item-label>
            R$
            {{ formataNumero(Math.abs(titulo.valor)) }}
          </q-item-label>
          <q-item-label caption lines="1">
            <template v-if="Math.abs(titulo.saldo) > 0">
              <template v-if="titulo.saldo != Math.abs(titulo.valor)">
                R$
                {{ formataNumero(titulo.saldo) }}
              </template>
              Em aberto
            </template>
            <template v-else-if="titulo.estornado"> Estornado </template>
            <template v-else> Agrupado/Liquidado </template>
          </q-item-label>
        </q-item-section>
      </q-item>

      <q-item>
        <q-item-section>
          <q-item-label class="ellipsis">
            {{ titulo.tipotitulo }}
          </q-item-label>
          <q-item-label class="ellipsis" caption lines="1">
            {{ titulo.fantasia }}
          </q-item-label>
        </q-item-section>
      </q-item>

      <q-item>
        <q-item-section>
          <q-item-label class="ellipsis" v-if="titulo.boleto">
            Boleto {{ titulo.nossonumero }}
          </q-item-label>
          <q-item-label class="ellipsis" v-else> Sem Boleto </q-item-label>
          <q-item-label class="ellipsis" caption lines="1">
            {{ titulo.portador }}
          </q-item-label>
        </q-item-section>
      </q-item>
      <q-space />

      <template v-if="ehValeComSaldo(titulo)">
        <q-separator inset />
        <q-card-actions align="right">
          <q-btn
            flat
            dense
            round
            size="sm"
            color="primary"
            icon="print"
            @click="abrir(titulo.codtitulo)"
          >
            <q-tooltip>Imprimir Vale</q-tooltip>
          </q-btn>
        </q-card-actions>
      </template>
    </q-card>
  </div>
</template>
