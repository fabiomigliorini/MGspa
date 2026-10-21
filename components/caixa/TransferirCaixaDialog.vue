<script setup>
// Transferência (doc-4, redefinição do dinheiro): não é pagamento; o saldo sai de um portador e
// entra no outro (sangria, reforço, envio ao financeiro, depósito). Cai no período da tela, com a
// data dentro dele (do início ao fim; aberto, até agora); o outro lado, pela data. Só os portadores
// do usuário: destino = depositante, origem = operador. Nasce feita quando quem registra é gestor
// do destino; senão fica a confirmar.
// Uma opção só no Lançar, Transferência (Sangria / Reforço): o lado não se presume (TASK-252).
// Wizard: 1) de onde sai o dinheiro (este portador no topo); 2) para onde vai, só quando sai deste
// (sai de outro: entra neste, um lado é sempre o da tela); 3) data, valor (foco nele) e
// observação, com o "sai de / entra em" em cima. Nas listas, os desta filial primeiro e depois um
// grupo por filial; setas e Enter. O cabeçalho tem o título do passo (Saindo de, Recebendo em),
// o filtro (só no desktop) e a pergunta em vermelho; só a lista rola.
import { ref, computed, watch } from 'vue'
import { useQuasar } from 'quasar'
import MgInput from '@components/MgInput.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgInputData from '@components/MgInputData.vue'
import ListaOpcoes from '@components/cobranca/ListaOpcoes.vue'
import { logo } from '@components/cobranca/logos.js'
import { formataTimestampIso } from '@components/formatters'
import { useSelectCacheStore } from '@components/stores/selectCacheStore'
import { periodoStore, limitePeriodo, dentroDoPeriodo } from '@components/stores/periodoStore'

const $q = useQuasar()
const store = periodoStore()
const cache = useSelectCacheStore()
const caixa = computed(() => !!store.portador?.ehCaixa)
const este = computed(() => store.portador?.codportador)
// a data fica dentro do período da tela (do início ao fim; aberto, até agora)
const sessao = computed(() => store.periodo)
// lê o valor do form (ISO), não o texto que o MgInputData passa às rules; vazio fica com o !!v
const naSessao = () =>
  !form.value.transacao ||
  (new Date(form.value.transacao) > new Date()
    ? 'Não pode ser no futuro'
    : dentroDoPeriodo(sessao.value, form.value.transacao))
const vazio = () => ({
  codportadororigem: null,
  codportadordestino: null,
  valor: null,
  observacoes: '',
  transacao: formataTimestampIso(limitePeriodo(sessao.value)),
})
const form = ref(vazio())

// os portadores em espécie e banco em que o usuário tem papel (o mesmo v1/select/portador do
// MgSelectPortador): a origem pede operador, o destino depositante
const NIVEL = { D: 1, O: 2, G: 3 }
const portadores = ref([])
const nome = (codportador) =>
  codportador === este.value
    ? store.portador?.portador
    : portadores.value.find((p) => p.codportador === codportador)?.portador

const opcao = (p) => ({
  valor: p.codportador,
  label: p.portador,
  logo: p.codbanco ? logo(`/bancos/${p.codbanco}.svg`) : null,
  icone: p.tipo === 'E' ? 'savings' : 'account_balance',
  cor: 'blue-8',
})

// os outros portadores com o papel mínimo: os desta filial primeiro, depois um grupo por filial e
// os sem filial no fim (espécie antes de banco)
const outros = (papel) => {
  const codfilial = Number(store.portador?.codfilial)
  const daFilial = (p) => Number(p.codfilial) === codfilial
  const ordem = (p) => (daFilial(p) ? 0 : p.filial ? 1 : 2)
  return portadores.value
    .filter(
      (p) =>
        ['E', 'B'].includes(p.tipo) &&
        p.codportador !== este.value &&
        (NIVEL[p.papel] ?? 0) >= NIVEL[papel],
    )
    .sort(
      (a, b) =>
        ordem(a) - ordem(b) ||
        String(a.filial ?? '').localeCompare(String(b.filial ?? '')) ||
        (a.tipo === 'E' ? 0 : 1) - (b.tipo === 'E' ? 0 : 1) ||
        String(a.portador).localeCompare(String(b.portador)),
    )
    .map((p) => ({
      ...opcao(p),
      caption: [p.filial, p.banco].filter(Boolean).join(' · '),
      filial: p.filial,
      grupo: daFilial(p) ? 'Desta filial' : p.filial || 'Sem filial',
    }))
}

// ==== wizard ====

const passo = ref(1)
const cardRef = ref(null)
const listaRef = ref(null)

// passo 1: este portador no topo, sem grupo; passo 2: para onde vai (sai deste)
const opcoesPasso = computed(() =>
  passo.value === 1
    ? [
        { ...opcao(store.portador ?? {}), caption: caixa.value ? 'Este caixa' : 'Este portador' },
        ...outros('O'),
      ]
    : outros('D'),
)

// o filtro do cabeçalho (só no desktop): nome, caption ou filial; zera a cada passo
const filtro = ref('')
const opcoesFiltradas = computed(() => {
  const texto = filtro.value.trim().toLowerCase()
  if (!texto) return opcoesPasso.value
  return opcoesPasso.value.filter((o) =>
    [o.label, o.caption, o.filial].some((c) => c && String(c).toLowerCase().includes(texto)),
  )
})
watch(passo, () => (filtro.value = ''))

function escolher(escolhida) {
  if (passo.value === 2) {
    form.value.codportadordestino = escolhida.valor
    passo.value = 3
    return
  }
  form.value.codportadororigem = escolhida.valor
  if (escolhida.valor === este.value) {
    passo.value = 2
    return
  }
  // sai de outro: entra neste
  form.value.codportadordestino = este.value
  passo.value = 3
}

function voltar() {
  if (passo.value === 1) {
    store.dialogTransferir = false
    return
  }
  // do valor, volta para o "para onde vai" só se ele apareceu
  passo.value = passo.value === 3 && form.value.codportadororigem === este.value ? 2 : 1
}

// o submit do form: no passo 3 valida e lança
function avancar() {
  if (passo.value === 3) salvar()
}

// ao abrir: o foco no card (as setas vão para a lista), se o filtro não o pegou
function focar() {
  if (!cardRef.value?.$el.contains(document.activeElement)) cardRef.value?.$el.focus()
}

// passos 1 e 2: as teclas vão para a lista
function tecla(e) {
  if (passo.value === 3 || !listaRef.value?.tecla(e)) return
  e.preventDefault()
  e.stopPropagation()
}

watch(
  () => store.dialogTransferir,
  async (aberto) => {
    if (!aberto) return
    form.value = vazio()
    passo.value = 1
    filtro.value = ''
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
  <q-dialog v-model="store.dialogTransferir" @show="focar">
    <q-card
      ref="cardRef"
      flat
      tabindex="0"
      class="no-outline column no-wrap"
      style="width: 400px; max-width: 95vw; height: 80vh"
      @keydown="tecla"
    >
      <!-- altura fixa (80vh) em todos os passos: a lista ou o formulário ocupam o meio -->
      <q-form class="col column no-wrap" @submit.prevent="avancar">
        <!-- CABEÇALHO: o título do passo, o filtro (só no desktop) e a pergunta em vermelho; não
             rola com a lista -->
        <q-card-section>
          <div class="text-grey-9 text-overline text-uppercase">
            {{ ['Saindo de', 'Recebendo em', 'Transferência'][passo - 1] }}
          </div>
          <MgInput
            v-if="passo < 3 && !$q.platform.is.mobile"
            :key="passo"
            v-model="filtro"
            label="Filtrar (nome ou filial)"
            autofocus
            class="q-mt-sm"
          >
            <template #prepend>
              <q-icon name="search" />
            </template>
          </MgInput>
          <div v-if="passo === 1" class="text-subtitle1 text-red-5 q-mt-sm">
            De onde <span class="text-weight-bold">SAI</span> o dinheiro?
          </div>
          <div v-else-if="passo === 2" class="text-subtitle1 text-red-5 q-mt-sm">
            Para onde <span class="text-weight-bold">VAI</span> o dinheiro?
          </div>
        </q-card-section>
        <q-separator inset />

        <!-- O MEIO: a lista rola; no passo 3, o conteúdo fica no centro da altura (os q-space
             crescem quando sobra espaço e somem quando falta, e aí o meio rola) -->
        <div class="col column no-wrap scroll">
          <q-space v-if="passo === 3" />
          <!-- O QUE JÁ SE ESCOLHEU: SAI DE / ENTRA EM -->
          <q-card-section v-if="passo > 1" class="q-pb-none">
            <q-item class="q-px-none">
              <q-item-section avatar>
                <q-icon name="outbox" color="red-5" />
              </q-item-section>
              <q-item-section>
                <q-item-label caption>Sai de</q-item-label>
                <q-item-label class="text-subtitle1">
                  {{ nome(form.codportadororigem) }}
                </q-item-label>
              </q-item-section>
            </q-item>
            <q-item v-if="passo === 3" class="q-px-none">
              <q-item-section avatar>
                <q-icon name="move_to_inbox" color="green-6" />
              </q-item-section>
              <q-item-section>
                <q-item-label caption>Entra em</q-item-label>
                <q-item-label class="text-subtitle1">
                  {{ nome(form.codportadordestino) }}
                </q-item-label>
              </q-item-section>
            </q-item>
          </q-card-section>

          <!-- PASSOS 1 E 2: DE ONDE SAI, PARA ONDE VAI (só a lista rola) -->
          <q-card-section v-if="passo < 3" class="col scroll">
            <ListaOpcoes
              v-if="opcoesFiltradas.length"
              :key="passo"
              ref="listaRef"
              :opcoes="opcoesFiltradas"
              @escolher="escolher"
            />
            <div v-else-if="opcoesPasso.length" class="text-grey-6 text-italic q-pa-sm">
              Nada encontrado
            </div>
            <div v-else class="text-caption text-grey-7">
              Nenhum portador em que você possa depositar.
            </div>
          </q-card-section>

          <!-- PASSO 3: DATA, VALOR E OBSERVAÇÃO -->
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
          <q-space v-if="passo === 3" />
        </div>

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
            v-if="passo === 3"
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
