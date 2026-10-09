<script setup>
// Correção de um lançamento na conferência (M9 doc-3): o gerente acerta o pagamento com a
// realidade — crédito/débito, maquineta, bandeira, autorização, parcelas, valor, ou cartão que foi
// dinheiro. Justificativa obrigatória; o antes/depois fica gravado. Mudar de período é alterar a
// data (TASK-204: a data manda no período), no botão da data da linha.
import { ref, computed, watch } from 'vue'
import { BANDEIRAS } from '@components/cobranca/pagamento.js'
import MgInput from '@components/MgInput.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgSelectMaquineta from '@components/MgSelectMaquineta.vue'
import { useConferenciaStore } from 'src/stores/conferenciaStore'

const model = defineModel({ type: Boolean, default: false })
const props = defineProps({
  lancamento: { type: Object, default: null },
})
const emit = defineEmits(['corrigido'])

const store = useConferenciaStore()

const OPCOES_MEIO = [
  { value: 1, label: 'Dinheiro' },
  { value: 3, label: 'Cartão Crédito' },
  { value: 4, label: 'Cartão Débito' },
]
const OPCOES_BANDEIRA = Object.entries(BANDEIRAS).map(([value, label]) => ({
  value: Number(value),
  label,
}))

const form = ref({})

const cartao = computed(() => [3, 4].includes(form.value.meio))
// valor e meio só mudam em pagamento de venda ou avulso (o de título estorna e lança de novo)
const deTitulo = computed(() => props.lancamento?.origem === 'T')

watch(model, (aberto) => {
  if (!aberto || !props.lancamento) return
  const l = props.lancamento
  form.value = {
    meio: l.meio,
    principal: l.principal,
    codmaquineta: l.codmaquineta,
    bandeira: l.bandeira,
    autorizacao: l.autorizacao,
    parcelas: l.parcelas,
    justificativa: '',
  }
})

async function salvar() {
  const l = props.lancamento
  const payload = { justificativa: form.value.justificativa }
  for (const campo of [
    'meio',
    'principal',
    'codmaquineta',
    'bandeira',
    'autorizacao',
    'parcelas',
  ]) {
    if (form.value[campo] !== l[campo]) payload[campo] = form.value[campo]
  }
  const ret = await store.corrigirPagamento(l.codpagamento, payload)
  if (ret) {
    model.value = false
    emit('corrigido', ret)
  }
}
</script>

<template>
  <q-dialog v-model="model">
    <q-card flat style="width: 500px; max-width: 95vw">
      <q-form @submit.prevent="salvar">
        <q-card-section class="text-h6">Corrigir lançamento</q-card-section>
        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-12 col-sm-6">
              <q-select
                v-model="form.meio"
                :options="OPCOES_MEIO"
                emit-value
                map-options
                outlined
                label="Meio"
                :disable="deTitulo"
                autofocus
              />
            </div>
            <div class="col-12 col-sm-6">
              <MgInputValor
                v-model="form.principal"
                label="Valor"
                :min="0.01"
                :readonly="deTitulo"
              />
            </div>
            <template v-if="cartao">
              <div class="col-12 col-sm-6">
                <MgSelectMaquineta v-model="form.codmaquineta" label="Maquineta" />
              </div>
              <div class="col-12 col-sm-6">
                <q-select
                  v-model="form.bandeira"
                  :options="OPCOES_BANDEIRA"
                  emit-value
                  map-options
                  outlined
                  clearable
                  label="Bandeira"
                />
              </div>
              <div class="col-6 col-sm-3">
                <MgInput v-model="form.autorizacao" label="Autorização" maxlength="40" />
              </div>
              <div class="col-6 col-sm-3">
                <MgInputValor
                  v-model="form.parcelas"
                  label="Parcelas"
                  :decimals="0"
                  :min="1"
                  :grouping="false"
                />
              </div>
            </template>
            <div class="col-12">
              <MgInput
                v-model="form.justificativa"
                label="Justificativa"
                type="textarea"
                autogrow
                maxlength="300"
                :rules="[(v) => (v || '').trim().length >= 5]"
                lazy-rules
              />
            </div>
          </div>
          <div v-if="deTitulo" class="text-caption text-grey-7">
            Pagamento de título: valor e meio se corrigem estornando e lançando de novo.
          </div>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup />
          <q-btn flat label="Corrigir" color="primary" type="submit" :loading="store.salvando" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
