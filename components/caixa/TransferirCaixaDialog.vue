<script setup>
// Transferência (doc-4, redefinição do dinheiro): não é pagamento; o saldo sai de um portador e
// entra no outro. Enviar (sangria, envio ao financeiro, depósito) ou receber (reforço). Cai no
// período da tela, com a data dentro dele (do início ao fim; aberto, até agora); o outro lado,
// pela data. Só os portadores do usuário: destino = depositante, origem = operador. Nasce feita
// quando quem registra é gestor do destino; senão fica a confirmar.
// Enviar ou receber já vem escolhido no FAB dos lançamentos (store.sentido). Wizard, como a entrada
// e saída de item: 1) o outro portador (os em espécie da filial primeiro); 2) data, valor (foco
// nele) e observação. Passo 1 no teclado (setas e Enter), como o wizard de cobrança.
import { ref, computed, watch, nextTick } from 'vue'
import MgInput from '@components/MgInput.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgInputData from '@components/MgInputData.vue'
import ListaOpcoes from '@components/cobranca/ListaOpcoes.vue'
import { logo } from '@components/cobranca/logos.js'
import { formataTimestampIso } from '@components/formatters'
import { useSelectCacheStore } from '@components/stores/selectCacheStore'
import { periodoStore, limitePeriodo, dentroDoPeriodo } from '@components/stores/periodoStore'

const store = periodoStore()
const cache = useSelectCacheStore()
const caixa = computed(() => !!store.portador?.ehCaixa)
// a data fica dentro do período da tela (do início ao fim; aberto, até agora)
const sessao = computed(() => store.periodo)
// lê o valor do form (ISO), não o texto que o MgInputData passa às rules; vazio fica com o !!v
const naSessao = () =>
  !form.value.transacao ||
  (new Date(form.value.transacao) > new Date()
    ? 'Não pode ser no futuro'
    : dentroDoPeriodo(sessao.value, form.value.transacao))
const vazio = () => ({
  sentido: null,
  codportador: null,
  valor: null,
  observacoes: '',
  transacao: formataTimestampIso(limitePeriodo(sessao.value)),
})
const form = ref(vazio())

// os portadores em espécie e banco em que o usuário tem papel (o mesmo v1/select/portador do
// MgSelectPortador): enviar pede depositante no destino, receber pede operador na origem
const NIVEL = { D: 1, O: 2, G: 3 }
const portadores = ref([])
const outro = computed(() => portadores.value.find((p) => p.codportador === form.value.codportador))

const opcoesPortador = computed(() => {
  const minimo = NIVEL[form.value.sentido === 'E' ? 'D' : 'O']
  const codfilial = Number(store.portador?.codfilial)
  const daFilial = (p) => p.tipo === 'E' && Number(p.codfilial) === codfilial
  const lista = portadores.value
    .filter(
      (p) =>
        ['E', 'B'].includes(p.tipo) &&
        p.codportador !== store.portador?.codportador &&
        (NIVEL[p.papel] ?? 0) >= minimo,
    )
    .sort((a, b) => (daFilial(a) ? 0 : 1) - (daFilial(b) ? 0 : 1))
  return lista.map((p, i) => ({
    valor: p.codportador,
    label: p.portador,
    caption: [p.filial, p.banco].filter(Boolean).join(' · '),
    logo: p.codbanco ? logo(`/bancos/${p.codbanco}.svg`) : null,
    icone: p.tipo === 'E' ? 'savings' : 'account_balance',
    cor: 'blue-8',
    grupo: daFilial(p) ? 'Desta filial' : 'Mais opções',
  }))
})

const titulo = computed(() => {
  const enviar = form.value.sentido === 'E'
  const acao = caixa.value ? (enviar ? 'Sangria' : 'Reforço') : enviar ? 'Enviar' : 'Receber'
  return outro.value ? `${acao} ${enviar ? 'para' : 'de'} ${outro.value.portador}` : acao
})

// ==== wizard ====

const passo = ref(1)
const cardRef = ref(null)
const listaRef = ref(null)

function escolherPortador(opcao) {
  form.value.codportador = opcao.valor
  passo.value = 2
}

function voltar() {
  if (passo.value === 1) {
    store.dialogTransferir = false
    return
  }
  passo.value--
  if (passo.value === 1) nextTick(() => cardRef.value?.$el.focus())
}

// o submit do form: no passo 2 valida e lança
function avancar() {
  if (passo.value === 2) salvar()
}

// passo 1: as teclas vão para a lista
function tecla(e) {
  if (passo.value > 1 || !listaRef.value?.tecla(e)) return
  e.preventDefault()
  e.stopPropagation()
}

watch(
  () => store.dialogTransferir,
  async (aberto) => {
    if (!aberto) return
    form.value = { ...vazio(), sentido: store.sentido }
    passo.value = 1
    try {
      portadores.value = await cache.loadList('portador', 'v1/select/portador')
    } catch {
      portadores.value = []
    }
  },
)

async function salvar() {
  const ok = await store.transferir({
    ...form.value,
    observacoes: form.value.observacoes || null,
  })
  if (ok) store.dialogTransferir = false
}
</script>

<template>
  <q-dialog v-model="store.dialogTransferir" @show="cardRef?.$el.focus()">
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

        <!-- PASSO 1: O OUTRO PORTADOR -->
        <q-card-section v-if="passo === 1" class="scroll" style="max-height: 60vh">
          <ListaOpcoes
            v-if="opcoesPortador.length"
            ref="listaRef"
            :opcoes="opcoesPortador"
            @escolher="escolherPortador"
          />
          <div v-else class="text-caption text-grey-7">
            Nenhum portador em que você possa
            {{ form.sentido === 'E' ? 'depositar' : 'retirar' }}.
          </div>
        </q-card-section>

        <!-- PASSO 2: DATA, VALOR E OBSERVAÇÃO -->
        <q-card-section v-else>
          <div class="row q-col-gutter-md">
            <div class="col-12">
              <MgInputData
                v-model="form.transacao"
                type="timestamp"
                label="Data"
                :rules="[(v) => !!v, naSessao]"
              />
            </div>
            <div class="col-12">
              <MgInputValor
                v-model="form.valor"
                label="Valor"
                prefix="R$"
                autofocus
                :rules="[(v) => v > 0 || 'Informe o valor']"
                lazy-rules
              />
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
            :label="passo === 1 ? 'Cancelar' : 'Voltar'"
            color="grey-8"
            tabindex="-1"
            @click="voltar"
          />
          <q-btn
            v-if="passo === 2"
            flat
            :label="caixa ? 'Lançar' : 'Transferir'"
            color="primary"
            type="submit"
            :loading="store.salvando"
          />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
