<script setup>
// O formulário do pagamento, o mesmo no Receber/Pagar Títulos (contas e PDV) e no Editar do
// detalhe do pagamento (TASK-236): título e valor no topo, data e pessoa, "referente a" e
// observação. O que não pode mudar aparece em somente leitura, sem sumir da tela. Cada tela põe
// os campos dela no slot padrão (o portador e a justificativa do Editar), o que vem depois dos
// campos no slot `rodape` (a forma escolhida no Receber) e os botões no slot `acoes`.
import { formataNumero } from '@components/formatters'
import MgInput from '@components/MgInput.vue'
import MgInputData from '@components/MgInputData.vue'
import MgSelectPessoa from '@components/MgSelectPessoa.vue'

defineProps({
  titulo: { type: String, required: true },
  valor: { type: Number, default: null },
  // a data aparece (o PIX usado na baixa tem a data dele e não mostra)
  comData: { type: Boolean, default: true },
  dataReadonly: { type: Boolean, default: false },
  focoPessoa: { type: Boolean, default: false },
})

const emit = defineEmits(['submit'])

const transacao = defineModel('transacao', { type: String, default: null })
const codpessoa = defineModel('codpessoa', { type: Number, default: null })
const referente = defineModel('referente', { type: String, default: '' })
const observacao = defineModel('observacao', { type: String, default: '' })
</script>

<template>
  <q-card flat style="width: 600px; max-width: 90vw">
    <q-form @submit.prevent="emit('submit')">
      <q-card-section class="text-grey-9 text-overline row items-center">
        {{ titulo.toUpperCase() }}
        <q-space />
        <span v-if="valor !== null" class="text-h6">R$ {{ formataNumero(valor) }}</span>
      </q-card-section>
      <q-separator inset />
      <q-card-section>
        <div class="row q-col-gutter-md">
          <div class="col-xs-12 col-sm-4" v-if="comData">
            <MgInputData
              v-model="transacao"
              type="timestamp"
              default-time="keep"
              :seconds="false"
              label="Data"
              :readonly="dataReadonly"
              :bottom-slots="false"
            />
          </div>
          <div class="col-xs-12" :class="comData ? 'col-sm-8' : ''">
            <MgSelectPessoa
              v-model="codpessoa"
              label="Pessoa"
              :autofocus="focoPessoa"
              :bottom-slots="false"
            />
          </div>
          <slot />
          <div class="col-12">
            <MgInput
              v-model="referente"
              label="Referente a (sai no recibo)"
              maxlength="255"
              :bottom-slots="false"
            />
          </div>
          <div class="col-12">
            <MgInput
              v-model="observacao"
              label="Observação (interna)"
              maxlength="300"
              :bottom-slots="false"
            />
          </div>
        </div>
      </q-card-section>
      <slot name="rodape" />
      <q-separator inset />
      <q-card-actions align="right">
        <slot name="acoes" />
      </q-card-actions>
    </q-form>
  </q-card>
</template>
