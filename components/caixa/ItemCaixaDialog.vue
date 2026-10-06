<script setup>
// Entrada de item (doc-4, "Itens do caixa"): o item conta como cédula, então o saldo do
// portador só muda quando ele entra (+) ou sai sem venda (−: devolveu, perdeu). Vender não lança
// nada. Linhas novas de descrição (typeahead com as já usadas no item), preço e quantidade; cai no
// período da tela, com a data dentro dele, e só se cancela, com justificativa. Qualquer portador
// em espécie.
import { ref, computed, watch } from 'vue'
import { api } from 'src/services/api'
import MgInput from '@components/MgInput.vue'
import MgInputData from '@components/MgInputData.vue'
import MgInputValor from '@components/MgInputValor.vue'
import { formataNumero, formataTimestamp, formataTimestampIso } from '@components/formatters'
import { periodoStore, linhasParaSalvar, totalLinhas } from '@components/stores/periodoStore'

const SENTIDOS = [
  { label: 'Entrada', value: 1 },
  { label: 'Saída', value: -1 },
]

const store = periodoStore()
const periodo = computed(() => store.periodo)
const itens = computed(() => (periodo.value?.itens || []).filter((i) => !i.inativo))
const item = computed(() => itens.value.find((i) => i.codcaixaitem === form.value.codcaixaitem))

const limite = () =>
  periodo.value?.fim && new Date(periodo.value.fim) < new Date()
    ? new Date(periodo.value.fim)
    : new Date()
// lê o valor do form (ISO), não o texto que o MgInputData passa às rules; vazio fica com o !!v
const noPeriodo = () => {
  if (!periodo.value || !form.value.transacao) return true
  const d = new Date(form.value.transacao)
  return (
    (d >= new Date(periodo.value.inicio) && d <= limite()) ||
    `Fora do período (de ${formataTimestamp(periodo.value.inicio, 0)} a ${formataTimestamp(limite(), 0)})`
  )
}

const linhaVazia = () => ({ descricao: null, preco: null, quantidade: null })
// mesma regra do servidor (CaixaItemService::validarEntrada): linha toda vazia é ignorada; com
// qualquer campo preenchido, exige descrição, preço >= 0,01 e quantidade >= 1
const preenchida = (l) => !!l.descricao?.trim() || l.preco != null || l.quantidade != null
const regraDescricao = (l) => () => !preenchida(l) || !!l.descricao?.trim() || 'Obrigatório'
const regraPreco = (l) => (v) => !preenchida(l) || Number(v) >= 0.01 || 'Mínimo 0,01'
const regraQuantidade = (l) => (v) => !preenchida(l) || Number(v) >= 1 || 'Mínimo 1'
const vazio = () => ({
  codcaixaitem: store.item ?? itens.value[0]?.codcaixaitem ?? null,
  sinal: 1,
  linhas: [linhaVazia()],
  observacoes: '',
  transacao: formataTimestampIso(limite()),
})
const form = ref(vazio())
const total = computed(() => totalLinhas(form.value.linhas))
const valorLinha = (l) =>
  Number(l.quantidade) > 0 && Number(l.preco) > 0
    ? formataNumero(Number(l.quantidade) * Number(l.preco))
    : undefined

function incluirLinha() {
  form.value.linhas = [...form.value.linhas, linhaVazia()]
}

function excluirLinha(i) {
  form.value.linhas = form.value.linhas.filter((_, j) => j !== i)
}

// typeahead da descrição: as já usadas no item (entradas e contagens)
const sugestoes = ref([])
function buscarDescricao(busca, update) {
  api
    .get(`v1/caixa-item/${form.value.codcaixaitem}/descricao`, { params: { busca } })
    .then(({ data }) => update(() => (sugestoes.value = data.data)))
    .catch(() => update(() => (sugestoes.value = [])))
}

watch(
  () => store.dialogItem,
  (aberto) => {
    if (aberto) form.value = vazio()
  },
)
// trocou o item: linhas novas
watch(
  () => form.value.codcaixaitem,
  () => (form.value.linhas = [linhaVazia()]),
)

async function salvar() {
  const f = form.value
  const ok = await store.lancarItem({
    codcaixaitem: f.codcaixaitem,
    sinal: f.sinal,
    linhas: linhasParaSalvar(f.linhas),
    observacoes: f.observacoes,
    transacao: f.transacao,
  })
  if (ok) store.dialogItem = false
}
</script>

<template>
  <q-dialog v-model="store.dialogItem">
    <q-card flat style="width: 600px; max-width: 95vw">
      <q-form @submit.prevent="salvar">
        <q-card-section class="text-grey-9 text-overline text-uppercase">
          {{ form.sinal > 0 ? 'Entrada' : 'Saída' }} de {{ item?.item ?? 'item' }}
        </q-card-section>
        <q-separator inset />
        <q-card-section class="text-caption text-grey-7 q-pb-none">
          O item conta como cédula: vender não lança nada. Aqui só o que chegou ou saiu sem venda
          (devolvido, perdido).
        </q-card-section>
        <q-card-section>
          <div class="row q-col-gutter-md">
            <div v-if="itens.length > 1" class="col-12">
              <q-option-group
                v-model="form.codcaixaitem"
                type="radio"
                inline
                :options="itens.map((i) => ({ value: i.codcaixaitem, label: i.item }))"
              />
            </div>
            <div class="col-12">
              <q-option-group v-model="form.sinal" type="radio" inline :options="SENTIDOS" />
            </div>
            <div class="col-12">
              <MgInputData
                v-model="form.transacao"
                type="timestamp"
                default-time="now"
                label="Data"
                :rules="[(v) => !!v, noPeriodo]"
              />
            </div>
            <div class="col-12">
              <div v-for="(l, i) in form.linhas" :key="i" class="row q-col-gutter-sm items-start">
                <div class="col-12 col-sm-6">
                  <q-select
                    :model-value="l.descricao"
                    :options="sugestoes"
                    use-input
                    fill-input
                    hide-selected
                    input-debounce="300"
                    outlined
                    label="Descrição"
                    maxlength="50"
                    :autofocus="i === 0"
                    :rules="[regraDescricao(l)]"
                    @filter="buscarDescricao"
                    @input-value="(v) => (l.descricao = v || null)"
                    @update:model-value="(v) => (l.descricao = v || null)"
                  />
                </div>
                <div class="col-6 col-sm-3">
                  <MgInputValor v-model="l.preco" label="Preço" :rules="[regraPreco(l)]" />
                </div>
                <div class="col-6 col-sm-3">
                  <MgInputValor
                    v-model="l.quantidade"
                    label="Quantidade"
                    :decimals="0"
                    :min="0"
                    :rules="[regraQuantidade(l)]"
                    bottom-slots
                  >
                    <template #hint>
                      <div class="text-right">{{ valorLinha(l) }}</div>
                    </template>
                    <template v-if="form.linhas.length > 1" #append>
                      <q-icon
                        name="close"
                        tabindex="-1"
                        class="cursor-pointer"
                        @click.stop="excluirLinha(i)"
                      />
                    </template>
                  </MgInputValor>
                </div>
              </div>
              <q-btn
                flat
                size="sm"
                color="primary"
                icon="add"
                label="Linha"
                @click="incluirLinha"
              />
            </div>
            <div class="col-12 row items-center">
              <div class="col text-subtitle2">Total</div>
              <div class="text-h6">R$ {{ formataNumero(total) }}</div>
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
          </div>
        </q-card-section>
        <q-separator inset />
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn
            flat
            label="Lançar"
            color="primary"
            type="submit"
            :disable="!form.codcaixaitem || total <= 0"
            :loading="store.salvando"
          />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
