<script setup>
import { nextTick } from 'vue'
import {
  formataNumero,
  formataCodigo,
  formataTimestampCompleto,
  formataNumeroNota,
} from '@components/formatters'
import { Notify } from 'quasar'
import { api, apiNota } from 'boot/axios'
import MgNotaFiscalAcoes from '@components/MgNotaFiscalAcoes.vue'
import { negocioStore } from 'stores/negocio'
import { sincronizacaoStore } from 'src/stores/sincronizacao'
import moment from 'moment/min/moment-with-locales'
moment.locale('pt-br')

const sNegocio = negocioStore()
const sSinc = sincronizacaoStore()

const STATUS_OPTIONS = [
  { label: 'Lançada', value: 'LAN', icon: 'description', color: 'blue-grey' },
  { label: 'Em Digitação', value: 'DIG', icon: 'edit_note', color: 'blue' },
  {
    label: 'Não Autorizada',
    value: 'ERR',
    icon: 'error',
    color: 'deep-orange',
  },
  {
    label: 'Autorizada',
    value: 'AUT',
    icon: 'check_circle',
    color: 'secondary',
  },
  {
    label: 'Cancelada',
    value: 'CAN',
    icon: 'highlight_off',
    color: 'negative',
  },
  { label: 'Inutilizada', value: 'INU', icon: 'block', color: 'warning' },
  { label: 'Denegada', value: 'DEN', icon: 'report', color: 'negative' },
]

const getStatusOption = (status) => {
  return STATUS_OPTIONS.find((opt) => opt.value === status) || STATUS_OPTIONS[0]
}

const urlNotaFiscal = (codnotafiscal) => {
  return process.env.NOTAS_URL + '/nota/' + codnotafiscal
}

// Map de instancias do MgNotaFiscalAcoes por codnotafiscal, para disparar
// o envio da nota recem-criada (fluxo nova -> enviar).
const notaRefs = new Map()
const setNotaRef = (codnotafiscal, el) => {
  if (el) {
    notaRefs.set(codnotafiscal, el)
  } else {
    notaRefs.delete(codnotafiscal)
  }
}

const nova = async (modelo) => {
  try {
    // busca registros na ApI
    const { data } = await api.post(
      `/api/v1/pdv/negocio/${sNegocio.negocio.codnegocio}/nota-fiscal`,
      { pdv: sSinc.pdv.uuid, modelo },
    )
    Notify.create({
      type: 'positive',
      message: 'Nota Fiscal Criada!',
      timeout: 1000, // 1 segundo
      actions: [{ icon: 'close', color: 'white' }],
    })
    sNegocio.negocio.notas = data.data.notas
    sNegocio.salvar(false)
    return data.data.notas.slice(-1)[0]
  } catch (error) {
    console.log(error)
    Notify.create({
      type: 'negative',
      message: error.response.data.message,
      timeout: 3000, // 3 segundos
      actions: [{ icon: 'close', color: 'white' }],
    })
  }
}

// Dispara o envio da nota recem-criada na instancia correta do componente.
const enviar = async (nota) => {
  if (!nota) {
    return
  }
  await nextTick()
  const comp = notaRefs.get(nota.codnotafiscal)
  if (comp) {
    await comp.enviarNfe()
  }
}

// Atualiza a listagem offline conforme o retorno das acoes do componente.
const onAcao = (acao, nota) => {
  if (acao === 'excluir') {
    const i = sNegocio.negocio.notas.findIndex((n) => n.codnotafiscal == nota.codnotafiscal)
    if (i >= 0) {
      sNegocio.negocio.notas.splice(i, 1)
    }
  } else if (nota) {
    const i = sNegocio.negocio.notas.findIndex((n) => n.codnotafiscal == nota.codnotafiscal)
    if (i >= 0) {
      sNegocio.negocio.notas[i] = nota
    }
  }
  sNegocio.salvar(false)
}

defineExpose({
  nova,
  enviar,
})
</script>
<template>
  <div
    class="col-xs-12 col-sm-6 col-md-6 col-lg-4 col-xl-3"
    v-for="nota in sNegocio.negocio.notas"
    :key="nota.codnotafiscal"
  >
    <q-card flat bordered>
      <q-item clickable v-ripple :href="urlNotaFiscal(nota.codnotafiscal)" target="_blank">
        <q-item-section avatar>
          <q-avatar
            :icon="getStatusOption(nota.status).icon"
            :color="getStatusOption(nota.status).color"
            text-color="white"
          />
        </q-item-section>

        <q-item-section>
          <q-item-label class="ellipsis">
            <template v-if="nota.modelo == 55"> Nota Fiscal</template>
            <template v-else-if="nota.modelo == 65"> Cupom</template>
            <template v-else> Documento</template>
          </q-item-label>
          <q-item-label caption class="ellipsis">
            {{ formataNumeroNota(nota.numero, nota.modelo, nota.serie, nota.emitida) }}
          </q-item-label>
          <q-item-label caption class="ellipsis">
            {{ formataCodigo(nota.codnotafiscal) }}
          </q-item-label>
        </q-item-section>
      </q-item>
      <q-separator inset />

      <q-item>
        <q-item-section>
          <q-item-label class="ellipsis">
            {{ nota.filial?.filial }}
          </q-item-label>
          <q-item-label caption>
            {{ moment(nota.saida).fromNow() }},
            {{ formataTimestampCompleto(nota.saida) }}
          </q-item-label>
        </q-item-section>
      </q-item>
      <q-item>
        <q-item-section>
          <q-item-label class="ellipsis">
            {{ nota.pessoa?.fantasia }}
          </q-item-label>
          <q-item-label caption class="ellipsis">
            {{ nota.naturezaOperacao?.naturezaoperacao }}
          </q-item-label>
        </q-item-section>
      </q-item>
      <q-item>
        <q-item-section>
          <q-item-label class="ellipsis">
            R$
            {{ formataNumero(nota.valortotal) }}
          </q-item-label>
          <q-item-label caption class="ellipsis">
            <q-icon
              :name="getStatusOption(nota.status).icon"
              :color="getStatusOption(nota.status).color"
              size="xs"
              class="q-mr-xs"
            />
            {{ getStatusOption(nota.status).label }}
          </q-item-label>
        </q-item-section>
      </q-item>
      <q-separator inset />

      <q-card-actions align="right" v-if="nota.emitida">
        <MgNotaFiscalAcoes
          :ref="(el) => setNotaRef(nota.codnotafiscal, el)"
          :nota="nota"
          :api="apiNota"
          compact
          show-extras
          mostrar-excluir
          :abrir-danfe-apos-enviar="true"
          :impressora="sNegocio.padrao.impressora"
          @action-completed="onAcao"
        />
      </q-card-actions>
    </q-card>
  </div>
</template>
