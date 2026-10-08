<script setup>
// Entrada ou saída de item (doc-4, "Itens do caixa"): o item conta como cédula, então o saldo do
// portador só muda quando ele entra (+) ou sai sem venda (−: devolveu, perdeu). Vender não lança
// nada. Cai no período da tela, com a data dentro dele, e só se cancela, com justificativa.
// Qualquer portador em espécie.
// Entrada ou saída já vem escolhida no Lançar (store.sentido; a saída com o caixa vazio o Lançar
// desabilita). Wizard: 1) o item (pula quando só tem um); 2) as linhas — na entrada, descrição
// (typeahead com as já usadas no item), preço e quantidade; na saída, só o que está no caixa
// (`saida` do item no período: saldo inicial + entradas − saídas), no jeito da contagem, com o
// disponível de teto; 3) data, o total e observação, foco no Lançar. Passo 1 no teclado (setas e
// Enter).
import { ref, computed, watch, nextTick } from 'vue'
import { api } from 'src/services/api'
import MgInput from '@components/MgInput.vue'
import MgInputData from '@components/MgInputData.vue'
import MgInputValor from '@components/MgInputValor.vue'
import ContagemCaixa from '@components/caixa/ContagemCaixa.vue'
import ListaOpcoes from '@components/cobranca/ListaOpcoes.vue'
import { formataNumero, formataTimestampIso } from '@components/formatters'
import {
  periodoStore,
  linhasParaSalvar,
  totalLinhas,
  limitePeriodo,
  dentroDoPeriodo,
} from '@components/stores/periodoStore'

const store = periodoStore()
const periodo = computed(() => store.periodo)
const itens = computed(() => (periodo.value?.itens || []).filter((i) => !i.inativo))
// na saída, só os itens que estão no caixa
const itensSaida = computed(() => itens.value.filter((i) => i.saida?.length))
const item = computed(() => itens.value.find((i) => i.codcaixaitem === form.value.codcaixaitem))

const noPeriodo = () => dentroDoPeriodo(periodo.value, form.value.transacao)

const linhaVazia = () => ({ descricao: null, preco: null, quantidade: null })
// mesma regra do servidor (CaixaItemService::validarEntrada): linha toda vazia é ignorada; com
// qualquer campo preenchido, exige descrição, preço >= 0,01 e quantidade >= 1
const preenchida = (l) => !!l.descricao?.trim() || l.preco != null || l.quantidade != null
const regraDescricao = (l) => () => !preenchida(l) || !!l.descricao?.trim() || 'Obrigatório'
const regraPreco = (l) => (v) => !preenchida(l) || Number(v) >= 0.01 || 'Mínimo 0,01'
const regraQuantidade = (l) => (v) => !preenchida(l) || Number(v) >= 1 || 'Mínimo 1'
const vazio = () => ({
  codcaixaitem: null,
  sinal: null,
  linhas: [linhaVazia()],
  saida: { contagem: {}, itens: {} },
  observacoes: '',
  transacao: formataTimestampIso(limitePeriodo(periodo.value)),
})
const form = ref(vazio())
// as linhas que vão: na entrada, as digitadas; na saída, as do caixa
const linhas = computed(() =>
  form.value.sinal < 0 ? form.value.saida.itens[form.value.codcaixaitem] || [] : form.value.linhas,
)
const total = computed(() => totalLinhas(linhas.value))
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

// ==== wizard ====

const passo = ref(1)
const cardRef = ref(null)
const listaRef = ref(null)
const lancarRef = ref(null)

const itensDoSentido = computed(() => (form.value.sinal < 0 ? itensSaida.value : itens.value))
const opcoesItem = computed(() =>
  itensDoSentido.value.map((i, n) => ({
    valor: i.codcaixaitem,
    label: i.item,
    icone: 'confirmation_number',
    cor: 'blue-grey-5',
  })),
)

const titulo = computed(
  () =>
    `${form.value.sinal > 0 ? 'Entrada' : 'Saída'}${item.value ? ` de ${item.value.item}` : ''}`,
)

// as linhas novas do item: na entrada, uma em branco; na saída, uma por tipo que está no caixa
function escolherItem(opcao) {
  const f = form.value
  f.codcaixaitem = opcao.valor
  f.linhas = [linhaVazia()]
  f.saida = {
    contagem: {},
    itens: {
      [f.codcaixaitem]: (item.value?.saida || []).map((l) => ({
        preco: l.preco,
        descricao: l.descricao,
        quantidade: null,
        disponivel: l.quantidade,
      })),
    },
  }
  passo.value = 2
}

// com um item só, o passo 1 não aparece: voltar das linhas fecha
const primeiro = computed(() => (itensDoSentido.value.length === 1 ? 2 : 1))

function voltar() {
  if (passo.value === primeiro.value) {
    store.dialogItem = false
    return
  }
  passo.value--
  if (passo.value === 1) nextTick(() => cardRef.value?.$el.focus())
}

// o submit do form: no passo 2 valida as linhas e segue; no 3 lança
function avancar() {
  if (passo.value === 2) {
    if (total.value <= 0) return
    passo.value = 3
    nextTick(() => lancarRef.value?.$el.focus())
    return
  }
  if (passo.value === 3) salvar()
}

// passo 1: as teclas vão para a lista
function tecla(e) {
  if (passo.value > 1 || !listaRef.value?.tecla(e)) return
  e.preventDefault()
  e.stopPropagation()
}

watch(
  () => store.dialogItem,
  (aberto) => {
    if (!aberto) return
    form.value = { ...vazio(), sinal: store.sentido }
    passo.value = 1
    if (itensDoSentido.value.length === 1) {
      escolherItem({ valor: itensDoSentido.value[0].codcaixaitem })
    }
  },
)

async function salvar() {
  const f = form.value
  const ok = await store.lancarItem({
    codcaixaitem: f.codcaixaitem,
    sinal: f.sinal,
    linhas: linhasParaSalvar(linhas.value),
    observacoes: f.observacoes,
    transacao: f.transacao,
  })
  if (ok) store.dialogItem = false
}
</script>

<template>
  <q-dialog v-model="store.dialogItem" @show="passo === 1 && cardRef?.$el.focus()">
    <q-card
      ref="cardRef"
      flat
      tabindex="0"
      class="no-outline"
      style="width: 400px; max-width: 95vw"
      @keydown="tecla"
    >
      <q-form @submit.prevent="avancar">
        <q-card-section class="text-grey-9 text-overline text-uppercase">
          {{ titulo }}
        </q-card-section>
        <q-separator inset />

        <!-- PASSO 1: O ITEM -->
        <q-card-section v-if="passo === 1">
          <ListaOpcoes ref="listaRef" :opcoes="opcoesItem" @escolher="escolherItem" />
        </q-card-section>

        <!-- PASSO 2: AS LINHAS -->
        <q-card-section v-else-if="passo === 2">
          <ContagemCaixa
            v-if="form.sinal < 0"
            v-model="form.saida"
            :bloco="form.codcaixaitem"
            :nome="item.item"
            autofocus
          />
          <template v-else>
            <div v-for="(l, i) in form.linhas" :key="i" class="row q-col-gutter-sm items-start">
              <div class="col-12">
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
              <div class="col-6">
                <MgInputValor v-model="l.preco" label="Preço" :rules="[regraPreco(l)]" />
              </div>
              <div class="col-6">
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
            <q-btn flat size="sm" color="primary" icon="add" label="Linha" @click="incluirLinha" />
            <div class="row items-center q-mt-md">
              <div class="col text-subtitle2">Total</div>
              <div class="text-h6">R$ {{ formataNumero(total) }}</div>
            </div>
          </template>
        </q-card-section>

        <!-- PASSO 3: DATA, TOTAL E OBSERVAÇÃO -->
        <q-card-section v-else>
          <div class="row q-col-gutter-md">
            <div class="col-12">
              <MgInputData
                v-model="form.transacao"
                type="timestamp"
                default-time="now"
                label="Data"
                :rules="[(v) => !!v, noPeriodo]"
              />
            </div>
            <div class="col-12 row items-center">
              <div class="col text-subtitle2">Total</div>
              <div class="text-h6">R$ {{ formataNumero(total) }}</div>
            </div>
            <div class="col-12">
              <MgInput v-model="form.observacoes" label="Observação" maxlength="300" />
            </div>
          </div>
        </q-card-section>

        <q-separator inset />
        <q-card-actions align="right">
          <q-btn
            flat
            :label="passo === primeiro ? 'Cancelar' : 'Voltar'"
            color="grey-8"
            tabindex="-1"
            @click="voltar"
          />
          <q-btn
            v-if="passo === 2"
            flat
            label="Continuar"
            color="primary"
            type="submit"
            :disable="total <= 0"
          />
          <q-btn
            v-if="passo === 3"
            ref="lancarRef"
            flat
            label="Lançar"
            color="primary"
            type="submit"
            :loading="store.salvando"
          />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
