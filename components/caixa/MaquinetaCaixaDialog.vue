<script setup>
// Borderô da maquineta de parceiro (doc-4, "Itens de parceiro"): o parceiro deixa a maquineta na
// loja, cartão e Pix vão direto para ele e só o dinheiro fica na gaveta. No fim do dia o caixa
// lança o total em dinheiro do borderô da maquineta, senão sobra dinheiro na contagem. Devolução
// (dinheiro devolvido ao cliente) é o mesmo lançamento, com o valor negativo. A foto do borderô é
// opcional: sem ela a linha fica marcada "sem borderô" e a foto pode ser anexada depois. Cai no
// período da tela, com a data dentro dele, e só se cancela, com justificativa. Qualquer portador
// em espécie.
// Wizard, como a entrada e saída de item: 1) a maquineta (pula quando só tem uma); 2) a foto do
// borderô (opcional); 3) data, o valor em dinheiro (foco nele) e observação. Passo 1 no teclado
// (setas e Enter).
import { ref, computed, watch, nextTick } from 'vue'
import MgInput from '@components/MgInput.vue'
import MgInputData from '@components/MgInputData.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgSlim from '@components/MgSlim.vue'
import ListaOpcoes from '@components/cobranca/ListaOpcoes.vue'
import { formataTimestampIso } from '@components/formatters'
import { periodoStore, limitePeriodo, dentroDoPeriodo } from '@components/stores/periodoStore'

const store = periodoStore()
const periodo = computed(() => store.periodo)
const maquinetas = computed(() => periodo.value?.maquinetas || [])
const maquineta = computed(() =>
  maquinetas.value.find((m) => m.codcaixaitem === form.value.codcaixaitem),
)

const noPeriodo = () => dentroDoPeriodo(periodo.value, form.value.transacao)

const vazio = () => ({
  codcaixaitem: null,
  valor: null,
  observacoes: '',
  transacao: formataTimestampIso(limitePeriodo(periodo.value)),
  anexoBase64: null,
})
const form = ref(vazio())

const opcoesMaquineta = computed(() =>
  maquinetas.value.map((m) => ({
    valor: m.codcaixaitem,
    label: m.item,
    icone: 'point_of_sale',
    cor: 'indigo-5',
  })),
)

const titulo = computed(() => `Borderô de ${maquineta.value?.item ?? 'parceiro'}`)

// ==== wizard ====

const passo = ref(1)
const cardRef = ref(null)
const listaRef = ref(null)
const continuarRef = ref(null)

// na lista, o foco no card (as teclas); na foto, no Continuar (Enter segue); o valor tem autofocus
function focar() {
  if (passo.value === 1) cardRef.value?.$el.focus()
  else if (passo.value === 2) continuarRef.value?.$el.focus()
}

function escolherMaquineta(opcao) {
  form.value.codcaixaitem = opcao.valor
  passo.value = 2
  nextTick(focar)
}

// com uma maquineta só, o passo 1 não aparece: voltar da foto fecha
const primeiro = computed(() => (maquinetas.value.length === 1 ? 2 : 1))

function voltar() {
  if (passo.value === primeiro.value) {
    store.dialogMaquineta = false
    return
  }
  passo.value--
  nextTick(focar)
}

// o submit do form: no passo 2 (a foto) segue; no 3 valida o valor e lança
function avancar() {
  if (passo.value === 3) {
    salvar()
    return
  }
  passo.value = 3
}

// passo 1: as teclas vão para a lista
function tecla(e) {
  if (passo.value > 1 || !listaRef.value?.tecla(e)) return
  e.preventDefault()
  e.stopPropagation()
}

watch(
  () => store.dialogMaquineta,
  (aberto) => {
    if (!aberto) return
    form.value = vazio()
    passo.value = 1
    if (maquinetas.value.length === 1) {
      escolherMaquineta({ valor: maquinetas.value[0].codcaixaitem })
    }
  },
)

async function salvar() {
  const f = form.value
  const ok = await store.lancarMaquineta({
    codcaixaitem: f.codcaixaitem,
    valor: f.valor,
    observacoes: f.observacoes,
    transacao: f.transacao,
    anexoBase64: f.anexoBase64,
  })
  if (ok) store.dialogMaquineta = false
}
</script>

<template>
  <q-dialog v-model="store.dialogMaquineta" @show="focar">
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

        <!-- todos os passos na mesma altura, o conteúdo no meio (margem auto: não corta o topo ao
             rolar) -->
        <div class="column no-wrap scroll" style="height: 400px; max-height: 70vh">
          <div class="q-my-auto">
            <!-- PASSO 1: A MAQUINETA -->
            <q-card-section v-if="passo === 1">
              <ListaOpcoes
                ref="listaRef"
                :opcoes="opcoesMaquineta"
                :inicial="form.codcaixaitem"
                @escolher="escolherMaquineta"
              />
            </q-card-section>

            <!-- PASSO 2: A FOTO DO BORDERÔ -->
            <q-card-section v-else-if="passo === 2">
              <div class="row justify-center q-col-gutter-md">
                <!-- do tamanho das fotos do borderô: o quadrado no centro, o Slim preenchendo -->
                <div class="col-6">
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
                <div v-if="!form.anexoBase64" class="col-12 text-caption text-orange-8 text-center">
                  Sem a foto do borderô: dá para lançar e anexar depois, na linha do lançamento.
                </div>
              </div>
            </q-card-section>

            <!-- PASSO 3: DATA, VALOR E OBSERVAÇÃO -->
            <template v-else>
              <q-card-section class="text-caption text-grey-7 q-pb-none">
                Só o dinheiro recebido no dia. Devolução: valor negativo.
              </q-card-section>
              <q-card-section>
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
                  <div class="col-12">
                    <MgInputValor
                      v-model="form.valor"
                      label="Valor em dinheiro"
                      prefix="R$"
                      autofocus
                      :rules="[(v) => !!Number(v) || 'Informe o valor']"
                      lazy-rules
                    />
                  </div>
                  <div class="col-12">
                    <MgInput v-model="form.observacoes" label="Observação" maxlength="300" />
                  </div>
                </div>
              </q-card-section>
            </template>
          </div>
        </div>

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
            ref="continuarRef"
            flat
            label="Continuar"
            color="primary"
            type="submit"
          />
          <q-btn
            v-if="passo === 3"
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
