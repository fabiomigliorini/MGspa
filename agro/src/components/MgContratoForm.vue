<script setup>
import { computed } from 'vue'
import MgSelectPessoa from '@components/MgSelectPessoa.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgInputData from '@components/MgInputData.vue'

// Form único de novo/editar contrato (recebe :cad). Cultura e safra não
// aparecem: o contrato vive dentro da safra, então o pai força esse vínculo
// via :fixar. Usado na grid da safra (MgContratosSafra) e na edição do
// detalhe (ContratoDetailPage).
const props = defineProps({
  cad: { type: Object, required: true },
  naturezas: { type: Array, default: () => [] },
  // { codsafra, codcultura } — fixa o vínculo no salvar (criação dentro da safra)
  fixar: { type: Object, default: null },
})
const emit = defineEmits(['saved'])

// Indireção (padrão MgSafraForm): v-model escreve no objeto reativo do cad sem
// disparar vue/no-mutating-props.
const cad = computed(() => props.cad)

const tipos = [
  { label: 'Fixo', value: 'FIXO' },
  { label: 'A fixar', value: 'FIXAR' },
  { label: 'Barter', value: 'BARTER' },
]
const moedas = [
  { label: 'R$', value: 'BRL' },
  { label: 'US$', value: 'USD' },
]

async function salvar() {
  await props.cad.salvar((f) => (props.fixar ? { ...f, ...props.fixar } : f))
  if (!props.cad.dialog) emit('saved')
}
</script>

<template>
  <q-dialog v-model="cad.dialog">
    <q-card bordered flat style="width: 560px; max-width: 95vw">
      <q-form @submit="salvar">
        <q-card-section class="bg-primary text-white">
          <div class="text-h6">{{ cad.isNovo ? 'Novo Contrato' : 'Editar Contrato' }}</div>
        </q-card-section>
        <q-card-section class="scroll" style="max-height: 70vh">
          <div class="row q-col-gutter-md">
            <!-- Tipo do contrato (segmentado, largura total) -->
            <div class="col-12">
              <q-btn-toggle
                v-model="cad.form.tipo"
                :options="tipos"
                spread
                no-caps
                unelevated
                toggle-color="primary"
                color="grey-3"
                text-color="grey-9"
              />
            </div>

            <!-- Identificação + comprador -->
            <div class="col-12 col-sm-5">
              <q-input v-model="cad.form.contrato" label="Nº / identificação" outlined autofocus />
            </div>
            <div class="col-12 col-sm-7">
              <MgSelectPessoa v-model="cad.form.codpessoa" label="Comprador" />
            </div>

            <!-- Quantidade + preço + moeda -->
            <div class="col-6 col-sm-4">
              <MgInputValor
                v-model="cad.form.quantidade"
                :decimals="0"
                suffix="sc"
                label="Quantidade"
              />
            </div>
            <div class="col-6 col-sm-4">
              <MgInputValor
                v-model="cad.form.preco"
                :decimals="2"
                :label="cad.form.tipo === 'FIXO' ? 'Preço / saca' : 'Preço referência'"
              />
            </div>
            <div class="col-12 col-sm-4 self-center">
              <q-btn-toggle
                v-model="cad.form.moeda"
                :options="moedas"
                spread
                no-caps
                unelevated
                toggle-color="primary"
                color="grey-3"
                text-color="grey-9"
              />
            </div>

            <!-- Aviso FIXO -->
            <div v-if="cad.form.tipo === 'FIXO'" class="col-12">
              <q-banner rounded class="bg-blue-grey-1 text-blue-grey-8">
                <template #avatar><q-icon name="lock" color="blue-grey-6" /></template>
                FIXO trava o preço aqui; a fixação é gerada automaticamente.
              </q-banner>
            </div>

            <!-- Embarque + local -->
            <div class="col-12 col-sm-6">
              <MgInputData v-model="cad.form.dataembarque" label="Embarque até" type="date" />
            </div>
            <div class="col-12 col-sm-6">
              <q-input v-model="cad.form.localentrega" label="Local / FOB-CIF" outlined />
            </div>

            <!-- Dados fiscais (NF) -->
            <div class="col-12">
              <q-expansion-item icon="receipt_long" label="Dados fiscais (NF)">
                <div class="row q-col-gutter-md q-pt-sm">
                  <div class="col-12">
                    <q-select
                      v-model="cad.form.codnaturezaoperacao"
                      :options="naturezas"
                      option-value="codnaturezaoperacao"
                      option-label="naturezaoperacao"
                      emit-value
                      map-options
                      outlined
                      clearable
                      label="Natureza da operação"
                    />
                  </div>
                  <div class="col-12">
                    <MgSelectPessoa
                      v-model="cad.form.codpessoanf"
                      label="Emitir NF para (destinatário)"
                    />
                  </div>
                  <div class="col-12">
                    <q-input
                      v-model="cad.form.observacaonf"
                      label="Observações da NF"
                      type="textarea"
                      autogrow
                      outlined
                    />
                  </div>
                </div>
              </q-expansion-item>
            </div>

            <!-- Observações -->
            <div class="col-12">
              <q-input
                v-model="cad.form.observacao"
                label="Observações"
                type="textarea"
                autogrow
                outlined
              />
            </div>
          </div>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn type="submit" flat label="Salvar" color="primary" :loading="cad.salvando" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
