<script setup>
import { formataNumero, formataCodNegocio } from '@components/formatters'
import { ref, watch } from 'vue'
import { debounce } from 'quasar'

import { conferenciaStore } from 'src/stores/conferencia'
import moment from 'moment/min/moment-with-locales'
moment.locale('pt-br')

const sConferencia = conferenciaStore()

const inicializa = debounce(async () => {
  await sConferencia.getConferencia()
}, 500)

watch(
  () => sConferencia.filtro,
  () => {
    inicializa()
  },
  { deep: true },
)
</script>
<template>
  <q-page>
    <div v-if="sConferencia.conferencias.length == 0" class="absolute-center text-grey text-center">
      <q-icon name="do_not_disturb" color="" size="300px" />
      <h3>Nenhum registro localizado!</h3>
    </div>

    <q-list class="q-pa-md" v-else>
      <template :key="index" v-for="(item, index) in sConferencia.conferencias">
        <q-item class="row" :to="'/negocio/' + item.codnegocio">
          <q-item-section class="col-xs-3">
            <q-item-label> {{ item.fantasia }} </q-item-label>
            <q-item-label class="ellipsis" caption>
              {{ formataCodNegocio(item.codnegocio) }}
            </q-item-label>
          </q-item-section>

          <q-item-section class="col-md-2 col-xs-2">
            <q-item-label>
              <template v-if="item.valortotal">
                R$
                {{ formataNumero(item.valortotal) }}
              </template>
            </q-item-label>
            <q-item-label caption>Total</q-item-label>
          </q-item-section>

          <q-item-section>
            <q-item-label>
              <template v-if="item.valorpix">
                R$
                {{ formataNumero(item.valorpix) }}
              </template>
              &nbsp;
            </q-item-label>
            <q-item-label caption>Pix</q-item-label>
          </q-item-section>

          <q-item-section>
            <q-item-label>
              <template v-if="item.valorstone">
                R$
                {{ formataNumero(item.valorstone) }}
              </template>
              &nbsp;
            </q-item-label>
            <q-item-label caption>Stone</q-item-label>
          </q-item-section>

          <q-item-section>
            <q-item-label>
              <template v-if="item.valortitulo">
                R$
                {{ formataNumero(item.valortitulo) }}
              </template>
              &nbsp;
            </q-item-label>
            <q-item-label caption>Titulo</q-item-label>
          </q-item-section>

          <q-item-section>
            <q-item-label>
              <template v-if="item.valordiferenca">
                R$
                {{ formataNumero(item.valordiferenca) }}
              </template>
              &nbsp;
            </q-item-label>
            <q-item-label caption>Diferença</q-item-label>
          </q-item-section>
        </q-item>
        <q-separator v-if="index >= 0" />
      </template>
    </q-list>
  </q-page>
</template>
