<script setup>
// Borderô da maquineta de parceiro (doc-4, "Itens de parceiro"): o parceiro deixa a maquineta na
// loja, cartão e Pix vão direto para ele e só o dinheiro fica na gaveta. No fim do dia o caixa
// lança o total em dinheiro do borderô da maquineta, senão sobra dinheiro na contagem. Devolução
// (dinheiro devolvido ao cliente) entra negativa. A foto do borderô é opcional: sem ela a linha
// fica marcada "sem borderô" e a foto pode ser anexada depois. Cai no período da tela, com a data
// dentro dele, e só se cancela, com justificativa. Qualquer portador em espécie.
import { ref, computed, watch } from 'vue'
import MgInput from '@components/MgInput.vue'
import MgInputData from '@components/MgInputData.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgSlim from '@components/MgSlim.vue'
import { formataTimestampIso } from '@components/formatters'
import { periodoStore, limitePeriodo, dentroDoPeriodo } from '@components/stores/periodoStore'

const SENTIDOS = [
  { label: 'Dinheiro recebido', value: 1 },
  { label: 'Devolução', value: -1 },
]

const store = periodoStore()
const periodo = computed(() => store.periodo)
const maquinetas = computed(() =>
  (periodo.value?.maquinetas || []).map((m) => ({ value: m.codcaixaitem, label: m.item })),
)

const noPeriodo = () => dentroDoPeriodo(periodo.value, form.value.transacao)

// com uma maquineta só, ela já vem escolhida; com várias, quem lança escolhe
const vazio = () => ({
  codcaixaitem: maquinetas.value.length === 1 ? maquinetas.value[0].value : null,
  sinal: 1,
  valor: null,
  observacoes: '',
  transacao: formataTimestampIso(limitePeriodo(periodo.value)),
  anexoBase64: null,
})
const form = ref(vazio())

watch(
  () => store.dialogMaquineta,
  (aberto) => {
    if (aberto) form.value = vazio()
  },
)

async function salvar() {
  const f = form.value
  const ok = await store.lancarMaquineta({
    codcaixaitem: f.codcaixaitem,
    valor: f.sinal * f.valor,
    observacoes: f.observacoes,
    transacao: f.transacao,
    anexoBase64: f.anexoBase64,
  })
  if (ok) store.dialogMaquineta = false
}
</script>

<template>
  <q-dialog v-model="store.dialogMaquineta">
    <q-card flat style="width: 600px; max-width: 95vw">
      <q-form @submit.prevent="salvar">
        <q-card-section class="text-grey-9 text-overline">BORDERÔ DA MAQUINETA</q-card-section>
        <q-separator inset />
        <q-card-section class="text-caption text-grey-7 q-pb-none">
          Só o dinheiro: o total que a maquineta do parceiro recebeu em dinheiro no dia. Cartão e
          Pix vão direto para o parceiro.
        </q-card-section>
        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-12">
              <q-select
                v-model="form.codcaixaitem"
                outlined
                :options="maquinetas"
                emit-value
                map-options
                label="Maquineta"
                :autofocus="maquinetas.length > 1"
                :rules="[(v) => !!v || 'Escolha a maquineta']"
              />
            </div>
            <div class="col-12">
              <q-option-group v-model="form.sinal" type="radio" inline :options="SENTIDOS" />
            </div>
            <div class="col-12 col-sm-6">
              <MgInputData
                v-model="form.transacao"
                type="timestamp"
                default-time="now"
                label="Data"
                :rules="[(v) => !!v, noPeriodo]"
              />
            </div>
            <div class="col-12 col-sm-6">
              <MgInputValor
                v-model="form.valor"
                label="Valor em dinheiro"
                :autofocus="maquinetas.length <= 1"
                :rules="[(v) => v > 0 || 'Informe o valor']"
                lazy-rules
              />
            </div>
            <div class="col-12">
              <MgInput
                v-model="form.observacoes"
                label="Observação"
                type="textarea"
                autogrow
                maxlength="300"
              />
            </div>
            <!-- do tamanho das fotos do borderô: o quadrado, com o Slim preenchendo -->
            <div class="col-6 col-sm-4">
              <q-responsive :ratio="1">
                <MgSlim
                  manter
                  style="
                    position: absolute;
                    inset: 0;
                    min-width: 0;
                    min-height: 0;
                    overflow: hidden;
                  "
                  label="Toque para fotografar o borderô"
                  @imagem="(b) => (form.anexoBase64 = b)"
                  @removida="form.anexoBase64 = null"
                />
              </q-responsive>
            </div>
            <div v-if="!form.anexoBase64" class="col text-caption text-orange-8">
              Sem a foto do borderô: dá para lançar e anexar depois, na linha do lançamento.
            </div>
          </div>
        </q-card-section>
        <q-separator inset />
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn flat label="Lançar" color="primary" type="submit" :loading="store.salvando" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
