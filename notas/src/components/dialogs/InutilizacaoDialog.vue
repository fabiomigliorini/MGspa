<script setup>
import { ref, watch } from 'vue'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import MgInputValor from '@components/MgInputValor.vue'
import MgSelectFilial from '@components/MgSelectFilial.vue'
import { useInutilizacaoStore } from '../../stores/inutilizacaoStore'

const $q = useQuasar()
const inutilizacaoStore = useInutilizacaoStore()
const { salvando } = storeToRefs(inutilizacaoStore)

const model = defineModel({ type: Boolean })

const modeloOptions = [
  { label: '55 — NF-e', value: 55 },
  { label: '65 — NFC-e', value: 65 },
]

const cad = ref({})
const filialNome = ref('')

const limpar = () => {
  // A filial da aba e so a sugestao: da para inutilizar em qualquer filial emitente, inclusive
  // uma que ainda nao tem historico e por isso nem aparece nas abas.
  cad.value = {
    codfilial: inutilizacaoStore.codfilial,
    modelo: 55,
    serie: 1,
    numeroinicial: null,
    numerofinal: null,
    justificativa: '',
  }
}

// A serie padrao e a da filial escolhida (as filiais 920/921 nao usam serie 1).
const onSelecionarFilial = (filial) => {
  filialNome.value = filial?.label || ''
  if (filial?.nfeserie) cad.value.serie = filial.nfeserie
}

const salvar = async () => {
  // Ato irreversivel na SEFAZ: confirma antes de queimar a numeracao.
  $q.dialog({
    title: 'Inutilizar numeração',
    message:
      `Inutilizar os números ${cad.value.numeroinicial} a ${cad.value.numerofinal} ` +
      `(modelo ${cad.value.modelo}, série ${cad.value.serie}) da filial ${filialNome.value}? ` +
      'A SEFAZ não desfaz isso.',
    ok: { label: 'Inutilizar', color: 'red-7', flat: true },
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
  }).onOk(async () => {
    try {
      const inut = await inutilizacaoStore.inutilizar({ ...cad.value })

      $q.notify({
        color: inut.homologada ? 'green-5' : 'red-5',
        icon: inut.homologada ? 'done' : 'error',
        message: `${inut.cstat ?? ''} ${inut.xmotivo ?? 'Inutilização enviada'}`,
      })

      if (inut.homologada) {
        // Leva a tela ate a faixa recem criada, senao o usuario nao ve o resultado quando
        // escolhe uma filial diferente da aba aberta.
        await inutilizacaoStore.navegarPara(cad.value.codfilial, inut.protocolodata)
        model.value = false
      }
    } catch (error) {
      $q.notify({
        color: 'red-5',
        icon: 'error',
        message: error.response?.data?.message || error.message,
      })
    }
  })
}

watch(model, (aberto) => {
  if (aberto) limpar()
})
</script>

<template>
  <q-dialog v-model="model">
    <q-card flat style="width: 400px; max-width: 90vw">
      <q-form @submit.prevent="salvar">
        <q-card-section class="bg-red-7 text-white">
          <div class="text-h6">Inutilizar Faixa</div>
          <div class="text-caption">Queima uma faixa de numeração na SEFAZ</div>
        </q-card-section>

        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-12">
              <MgSelectFilial
                v-model="cad.codfilial"
                emite-nfe
                autofocus
                :rules="[(v) => !!v]"
                lazy-rules
                @select="onSelecionarFilial"
              />
            </div>

            <div class="col-12 col-sm-6">
              <q-select
                v-model="cad.modelo"
                :options="modeloOptions"
                label="Modelo"
                outlined
                emit-value
                map-options
                :rules="[(v) => !!v]"
                lazy-rules
              />
            </div>

            <div class="col-12 col-sm-6">
              <MgInputValor
                v-model="cad.serie"
                label="Série"
                :decimals="0"
                :grouping="false"
                align="left"
                :min="0"
                :rules="[(v) => v !== null]"
                lazy-rules
              />
            </div>

            <div class="col-12 col-sm-6">
              <MgInputValor
                v-model="cad.numeroinicial"
                label="Número inicial"
                :decimals="0"
                :grouping="false"
                align="left"
                :min="1"
                :rules="[(v) => !!v]"
                lazy-rules
              />
            </div>

            <div class="col-12 col-sm-6">
              <MgInputValor
                v-model="cad.numerofinal"
                label="Número final"
                :decimals="0"
                :grouping="false"
                align="left"
                :min="1"
                :rules="[(v) => !!v, (v) => v >= cad.numeroinicial || 'Menor que o número inicial']"
                lazy-rules
              />
            </div>

            <div class="col-12">
              <!-- 15 caracteres e o minimo exigido pela SEFAZ -->
              <q-input
                v-model="cad.justificativa"
                label="Justificativa"
                outlined
                counter
                maxlength="255"
                :rules="[(v) => (v || '').length >= 15 || 'Mínimo 15 caracteres']"
                lazy-rules
              />
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn flat label="Inutilizar" color="red-7" type="submit" :loading="salvando" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
