<script setup>
import { formataNumero, formataCodigo, formataData } from '@components/formatters'
import { negocioStore } from 'stores/negocio'
import moment from 'moment/min/moment-with-locales'
moment.locale('pt-br')

const sNegocio = negocioStore()

const urlTitulo = (codtitulo) => {
  return process.env.CONTAS_URL + '/titulo/' + codtitulo
}
</script>
<template>
  <div
    class="col-xs-12 col-sm-6 col-md-4 col-lg-3 col-xl-2"
    v-for="titulo in sNegocio.negocio.titulos"
    :key="titulo.codtitulo"
  >
    <q-card>
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
            {{ formataNumero(titulo.debito + titulo.credito) }}
          </q-item-label>
          <q-item-label caption lines="1">
            <template v-if="Math.abs(titulo.saldo) > 0">
              <template v-if="titulo.saldo != Math.abs(titulo.debito + titulo.credito)">
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
    </q-card>
  </div>
</template>
