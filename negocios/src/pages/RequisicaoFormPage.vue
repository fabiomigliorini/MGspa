<script setup>
import { computed, nextTick, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { db } from 'boot/db'
import MgInput from '@components/MgInput.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgInputProdutoBarras from '@components/MgInputProdutoBarras.vue'
import MgSelectPessoa from '@components/MgSelectPessoa.vue'
import MgEmptyState from '@components/MgEmptyState.vue'
import ListaOpcoes from '@components/cobranca/ListaOpcoes.vue'
import { formataCodigo, formataTelefone, mascaraTelefone } from '@components/formatters'
import { isTelefoneValido } from '@components/validador'
import {
  requisicaoStore,
  DESTINOS,
  PRIORIDADES,
  PRIORIDADE,
  PRIORIDADE_DO_DESTINO,
  lojaDaRequisicao,
} from 'stores/requisicao'
import { negocioStore } from 'stores/negocio'

// Nova requisição / editar (TASK-55, doc-6) em etapas, no modelo do wizard de cobrança e do
// lançamento de item do caixa: uma pergunta por vez, Voltar/Continuar no rodapé e as escolhas
// (as duas lojas e o destino) em lista operada pelo teclado (setas, número, Enter).
// Editar só enquanto aguardando, pela loja que pediu: a loja que pede não muda, então o editar
// começa na etapa seguinte.
const route = useRoute()
const router = useRouter()
const sRequisicao = requisicaoStore()
const sNegocio = negocioStore()
const { form, salvando, isNovo, itensNegocio } = storeToRefs(sRequisicao)

const temCliente = computed(() => form.value.destino !== 'L')

// as etapas que existem para o que já foi respondido
const PERGUNTAS = {
  requisitante: 'Quem está requisitando?',
  requisitada: 'Quem vai atender?',
  destino: 'Para onde vai a mercadoria?',
  negocio: 'Veio de uma venda? (opcional)',
  cliente: 'Quem retira ou recebe?',
  endereco: 'Onde entregar?',
  produtos: 'Quais produtos?',
  conferencia: 'Confira e salve',
}

// nascida do negócio (botão do PDV): o número e a loja que pede já vieram dele
const doNegocio = ref(false)

// quem pede é a loja do PDV: só o Gerente escolhe outra, e só na requisição nova e avulsa
const etapas = computed(() => {
  const lista = []
  if (isNovo.value && sNegocio.podeEscolherLoja && !doNegocio.value) lista.push('requisitante')
  lista.push('requisitada', 'destino')
  if (temCliente.value) {
    if (!doNegocio.value) lista.push('negocio')
    lista.push('cliente')
  }
  if (form.value.destino === 'E') lista.push('endereco')
  lista.push('produtos', 'conferencia')
  return lista
})

const passo = ref(0)
const etapa = computed(() => etapas.value[passo.value] || 'conferencia')
const ultima = computed(() => etapa.value === 'conferencia')
// nas listas quem lê o teclado é o card; nas demais etapas o foco é do campo
const etapaLista = computed(() => ['requisitante', 'requisitada', 'destino'].includes(etapa.value))

// produtos: nascido de um negócio, marca-se o que falta na venda; senão, bipa-se
const modoNegocio = computed(() => isNovo.value && temCliente.value && !!form.value.codnegocio)

const cardRef = ref(null)
const listaRef = ref(null)
const salvarRef = ref(null)

// ---- as listas das etapas de escolha ----
const locais = ref([])
const nomeLocal = (codestoquelocal) =>
  locais.value.find((l) => l.codestoquelocal === codestoquelocal)?.estoquelocal || ''

const opcaoLoja = (local, i) => ({
  valor: local.codestoquelocal,
  label: local.estoquelocal,
  caption: local.sigla,
  icone: 'warehouse',
  cor: 'blue-grey-5',
  tecla: i < 9 ? i + 1 : null,
})

const locaisComEstoque = computed(() => locais.value.filter(lojaDaRequisicao))
const opcoesRequisitante = computed(() => locaisComEstoque.value.map(opcaoLoja))
const opcoesRequisitada = computed(() =>
  locaisComEstoque.value
    .filter((l) => l.codestoquelocal !== form.value.codestoquelocalrequisitante)
    .map(opcaoLoja),
)
// o destino dito com as lojas já escolhidas: "Cliente retira no Centro"
const labelDestino = (destino) => {
  const atende = nomeLocal(form.value.codestoquelocalrequisitada)
  const pede = nomeLocal(form.value.codestoquelocalrequisitante)
  return {
    R: `Cliente retira no ${atende}`,
    E: `${atende} entrega no endereço do cliente`,
    L: `Reposição de mercadoria do ${atende} para ${pede}`,
  }[destino]
}

const opcoesDestino = computed(() =>
  DESTINOS.map((d, i) => ({
    valor: d.value,
    label: labelDestino(d.value),
    icone: d.icone,
    cor: d.cor,
    tecla: i + 1,
  })),
)

function escolherRequisitante(opcao) {
  form.value.codestoquelocalrequisitante = opcao.valor
  if (form.value.codestoquelocalrequisitada === opcao.valor) {
    form.value.codestoquelocalrequisitada = null
  }
  avancar()
}

function escolherRequisitada(opcao) {
  form.value.codestoquelocalrequisitada = opcao.valor
  avancar()
}

// na requisição nova o destino pré-seleciona a prioridade; quem pede troca na conferência
function escolherDestino(opcao) {
  form.value.destino = opcao.valor
  if (isNovo.value) form.value.prioridade = PRIORIDADE_DO_DESTINO[opcao.valor]
  avancar()
}

// depois de criada, a prioridade só muda pelo botão do detalhe
const opcoesPrioridade = PRIORIDADES.map((p) => ({ value: p.value, label: p.label }))

// ---- produtos ----
// Último bipado primeiro: o produto novo nasce no topo da lista, que é a
// confirmação visual de quem está montando a requisição. O índice real viaja
// junto porque remover mexe direto em form.itens.
const itensExibicao = computed(() =>
  form.value.itens.map((item, indice) => ({ item, indice })).reverse(),
)

const marcados = computed(() => itensNegocio.value.filter((i) => i.marcado && i.quantidade > 0))
// marcar todos: true/false, ou null (indeterminado) quando só parte está marcada
const todosMarcados = computed(() => {
  const qtd = itensNegocio.value.filter((i) => i.marcado).length
  if (!qtd) return false
  return qtd === itensNegocio.value.length ? true : null
})
function marcarTodos() {
  const marcar = todosMarcados.value !== true
  itensNegocio.value.forEach((i) => (i.marcado = marcar))
}
const semProdutos = computed(() =>
  modoNegocio.value ? !marcados.value.length : !form.value.itens.length,
)

// pessoa cadastrada preenche o primeiro nome e o endereço (dá para mudar depois)
function escolherPessoa(pessoa) {
  if (!pessoa) return
  form.value.nomes[0] = pessoa.label || form.value.nomes[0]
  const endereco = [
    [pessoa.endereco, pessoa.numero].filter(Boolean).join(', '),
    pessoa.complemento,
    pessoa.bairro,
    [pessoa.cidade, pessoa.uf].filter(Boolean).join('/'),
  ]
    .filter(Boolean)
    .join(' - ')
  if (endereco) form.value.endereco = endereco
}

// ---- quem retira ou recebe: um nome e um telefone por linha ----
// O telefone é só dígitos; celular é o que tem 9 depois do DDD, e a máscara troca na hora
const tipoTelefone = (v) => (String(v ?? '')[2] === '9' ? 2 : 1)
const nomeRefs = []

function novoContato() {
  form.value.nomes.push('')
  form.value.telefones.push('')
  nextTick(() => nomeRefs[form.value.nomes.length - 1]?.focus())
}

function removerContato(i) {
  form.value.nomes.splice(i, 1)
  form.value.telefones.splice(i, 1)
}

// ---- navegação ----
// listas: o card (lê as teclas); conferência: o Salvar; as demais têm campo com autofocus
const focar = () =>
  nextTick(() => {
    if (etapaLista.value) cardRef.value?.$el?.focus()
    else if (ultima.value) salvarRef.value?.$el?.focus()
  })

// o submit do form: valida os campos da etapa, trata o que ela decide e segue
async function avancar() {
  if (etapa.value === 'negocio' && !(await sRequisicao.carregarNegocio())) return
  if (etapa.value === 'produtos') {
    if (modoNegocio.value) sRequisicao.aplicarSelecaoNegocio()
    if (!form.value.itens.length) return
  }
  if (ultima.value) {
    salvar()
    return
  }
  passo.value++
  focar()
}

function voltar() {
  if (passo.value === 0) {
    router.push(
      isNovo.value ? sRequisicao.enderecoPainel : `/requisicao/${form.value.codrequisicao}`,
    )
    return
  }
  passo.value--
  focar()
}

// ESC volta; com o menu do select aberto, só fecha o menu. O q-select dá preventDefault
// no ESC mesmo fechado, então quem diz se o menu está aberto é o aria-expanded. Nas listas
// o card repassa as teclas (setas, número, Enter)
function tecla(e) {
  if (e.key === 'Escape') {
    if (e.target?.getAttribute?.('aria-expanded') === 'true') return
    e.preventDefault()
    voltar()
    return
  }
  if (!etapaLista.value || !listaRef.value?.tecla(e)) return
  e.preventDefault()
  e.stopPropagation()
}

async function salvar() {
  const salva = await sRequisicao.salvar()
  if (salva) router.replace(`/requisicao/${salva.codrequisicao}`)
}

onMounted(async () => {
  locais.value = await db.estoqueLocal
    .orderBy('[codfilial+sigla]')
    .filter((l) => l.inativo == null)
    .toArray()
  if (route.params.codrequisicao) {
    if (await sRequisicao.carregar(route.params.codrequisicao)) {
      sRequisicao.editar(sRequisicao.requisicao)
    }
  } else {
    sRequisicao.novo({
      codestoquelocalrequisitante: sNegocio.padrao.codestoquelocal,
      codnegocio: Number(route.query.codnegocio) || null,
    })
    // do negócio: já traz cliente, itens e a loja; recusado (não fechado), segue avulsa
    if (form.value.codnegocio) {
      doNegocio.value = await sRequisicao.carregarNegocio({ comLoja: true })
      if (!doNegocio.value) form.value.codnegocio = null
    }
  }
  passo.value = 0
  focar()
})
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <q-card ref="cardRef" flat bordered tabindex="0" class="no-outline" @keydown="tecla">
        <q-form @submit.prevent="avancar">
          <q-card-section class="row items-center q-pb-none">
            <div class="col">
              <div class="text-grey-9 text-overline">
                {{
                  isNovo
                    ? 'Nova requisição'
                    : `Editar requisição ${formataCodigo(form.codrequisicao)}`
                }}
              </div>
              <div class="text-h6 text-weight-regular">{{ PERGUNTAS[etapa] }}</div>
            </div>
          </q-card-section>

          <!-- ETAPA: LOJA QUE PEDE -->
          <q-card-section v-if="etapa === 'requisitante'">
            <ListaOpcoes
              ref="listaRef"
              :opcoes="opcoesRequisitante"
              :inicial="form.codestoquelocalrequisitante"
              :selecionar-primeira="false"
              @escolher="escolherRequisitante"
            />
          </q-card-section>

          <!-- ETAPA: LOJA QUE ATENDE -->
          <q-card-section v-else-if="etapa === 'requisitada'">
            <ListaOpcoes
              ref="listaRef"
              :opcoes="opcoesRequisitada"
              :inicial="form.codestoquelocalrequisitada"
              :selecionar-primeira="false"
              @escolher="escolherRequisitada"
            />
          </q-card-section>

          <!-- ETAPA: DESTINO DA MERCADORIA -->
          <q-card-section v-else-if="etapa === 'destino'">
            <ListaOpcoes
              ref="listaRef"
              :opcoes="opcoesDestino"
              :inicial="form.destino"
              @escolher="escolherDestino"
            />
          </q-card-section>

          <!-- ETAPA: NEGÓCIO -->
          <q-card-section v-else-if="etapa === 'negocio'">
            <div class="row q-col-gutter-md">
              <div class="col-12 col-sm-4">
                <MgInputValor
                  v-model="form.codnegocio"
                  label="Número do negócio (opcional)"
                  :decimals="0"
                  :grouping="false"
                  align="left"
                  :min="0"
                  clearable
                  autofocus
                  :loading="sRequisicao.carregandoNegocio"
                />
              </div>
              <div class="col-12 text-body2 text-grey-7">
                Não é obrigatório: deixe em branco e clique em Pular para montar a requisição
                bipando os produtos. Com o número de uma venda fechada, vêm o cliente e a lista de
                produtos para marcar.
              </div>
            </div>
          </q-card-section>

          <!-- ETAPA: QUEM RETIRA OU RECEBE -->
          <q-card-section v-else-if="etapa === 'cliente'">
            <div class="row q-col-gutter-md">
              <div class="col-12">
                <MgSelectPessoa
                  v-model="form.codpessoa"
                  label="Cliente cadastrado (opcional)"
                  clearable
                  @select="escolherPessoa"
                />
              </div>
              <template v-for="(nome, i) in form.nomes" :key="i">
                <div class="col-12 col-sm-8">
                  <MgInput
                    :ref="(el) => (nomeRefs[i] = el)"
                    v-model="form.nomes[i]"
                    label="Nome de quem retira ou recebe"
                    maxlength="100"
                    :autofocus="i === 0"
                    :rules="[(v) => !!v || 'Informe o nome']"
                  />
                </div>
                <div class="col-12 col-sm-4">
                  <MgInput
                    v-model="form.telefones[i]"
                    label="Telefone"
                    type="tel"
                    unmasked-value
                    :mask="mascaraTelefone(tipoTelefone(form.telefones[i]))"
                    :rules="[
                      (v) => !!v || 'Informe o telefone',
                      (v) => isTelefoneValido(v, tipoTelefone(v)) || 'Telefone incompleto',
                    ]"
                  >
                    <template #append>
                      <q-btn
                        v-if="form.nomes.length > 1"
                        flat
                        round
                        size="sm"
                        color="grey-7"
                        icon="remove"
                        tabindex="-1"
                        @click="removerContato(i)"
                      >
                        <q-tooltip>Tirar este nome e telefone</q-tooltip>
                      </q-btn>
                      <q-btn
                        v-if="i === form.nomes.length - 1"
                        flat
                        round
                        size="sm"
                        color="primary"
                        icon="add"
                        tabindex="-1"
                        @click="novoContato"
                      >
                        <q-tooltip>Mais um nome e telefone</q-tooltip>
                      </q-btn>
                    </template>
                  </MgInput>
                </div>
              </template>
            </div>
          </q-card-section>

          <!-- ETAPA: ENDEREÇO DA ENTREGA -->
          <q-card-section v-else-if="etapa === 'endereco'">
            <MgInput
              v-model="form.endereco"
              label="Endereço completo da entrega"
              type="textarea"
              autogrow
              maxlength="300"
              autofocus
              :rules="[(v) => !!v || 'Informe o endereço da entrega']"
            />
          </q-card-section>

          <!-- ETAPA: PRODUTOS DO NEGÓCIO (marcar o que falta) -->
          <template v-else-if="etapa === 'produtos' && modoNegocio">
            <q-card-section class="q-pt-sm text-body2 text-grey-7">
              Marque o que a loja não tinha e o cliente vai buscar na outra.
            </q-card-section>
            <MgEmptyState v-if="!itensNegocio.length" icon="inventory_2" class="q-mb-md">
              Nenhum produto neste negócio.
            </MgEmptyState>
            <q-list v-else separator>
              <q-item clickable v-ripple @click="marcarTodos">
                <q-item-section side @click.stop>
                  <q-checkbox
                    :model-value="todosMarcados"
                    tabindex="-1"
                    @update:model-value="marcarTodos"
                  />
                </q-item-section>
                <q-item-section class="text-weight-medium">Marcar todos</q-item-section>
              </q-item>
              <q-item
                v-for="item in itensNegocio"
                :key="item.codprodutobarra"
                clickable
                v-ripple
                @click="item.marcado = !item.marcado"
              >
                <q-item-section side @click.stop>
                  <q-checkbox v-model="item.marcado" tabindex="-1" />
                </q-item-section>
                <q-item-section avatar>
                  <q-avatar rounded size="48px">
                    <img v-if="item.produto.imagem" :src="item.produto.imagem" />
                    <q-icon v-else name="inventory_2" color="grey-5" />
                  </q-avatar>
                </q-item-section>
                <q-item-section>
                  <q-item-label>{{ item.produto.descricao }}</q-item-label>
                  <q-item-label caption>{{ item.produto.barras }}</q-item-label>
                </q-item-section>
                <q-item-section side style="width: 130px" @click.stop>
                  <MgInputValor
                    v-model="item.quantidade"
                    :decimals="item.produto.decimais || 0"
                    :min="0"
                    :suffix="item.produto.sigla"
                    :readonly="!item.marcado"
                    :rules="[(v) => !item.marcado || v > 0 || 'Maior que zero']"
                  />
                </q-item-section>
              </q-item>
            </q-list>
          </template>

          <!-- ETAPA: PRODUTOS BIPADOS -->
          <template v-else-if="etapa === 'produtos'">
            <q-card-section>
              <MgInputProdutoBarras
                label="Acrescentar produto (bipe, ou digite 5* e o código)"
                pesquisa
                autofocus
                :codestoquelocal="form.codestoquelocalrequisitada"
                @select="sRequisicao.adicionarItem"
              />
            </q-card-section>
            <MgEmptyState v-if="!form.itens.length" icon="inventory_2" class="q-mb-md">
              Nenhum produto na requisição.
            </MgEmptyState>
            <q-list v-else separator>
              <q-item v-for="{ item, indice } in itensExibicao" :key="item.codprodutobarra">
                <q-item-section avatar>
                  <q-avatar rounded size="48px">
                    <img v-if="item.produto.imagem" :src="item.produto.imagem" />
                    <q-icon v-else name="inventory_2" color="grey-5" />
                  </q-avatar>
                </q-item-section>
                <q-item-section>
                  <q-item-label>{{ item.produto.descricao }}</q-item-label>
                  <q-item-label caption>{{ item.produto.barras }}</q-item-label>
                </q-item-section>
                <q-item-section side style="width: 130px">
                  <MgInputValor
                    v-model="item.quantidade"
                    :decimals="item.produto.decimais || 0"
                    :min="0"
                    :suffix="item.produto.sigla"
                    :rules="[(v) => v > 0 || 'Maior que zero']"
                  />
                </q-item-section>
                <q-item-section side>
                  <q-btn
                    flat
                    round
                    size="sm"
                    color="grey-7"
                    icon="delete"
                    tabindex="-1"
                    @click="sRequisicao.removerItem(indice)"
                  >
                    <q-tooltip>Tirar da requisição</q-tooltip>
                  </q-btn>
                </q-item-section>
              </q-item>
            </q-list>
          </template>

          <!-- ETAPA: CONFERÊNCIA -->
          <q-card-section v-else>
            <div class="row q-col-gutter-md">
              <div class="col-12 text-body1">
                <div class="q-mb-sm">
                  <span class="text-grey-7">Loja que pede:</span>
                  {{ nomeLocal(form.codestoquelocalrequisitante) }}
                </div>
                <div class="q-mb-sm">
                  <span class="text-grey-7">Loja que atende:</span>
                  {{ nomeLocal(form.codestoquelocalrequisitada) }}
                </div>
                <div class="q-mb-sm">
                  <span class="text-grey-7">Destino:</span>
                  {{ labelDestino(form.destino) }}
                </div>
                <div class="q-mb-sm" v-if="form.codnegocio">
                  <span class="text-grey-7">Negócio:</span> {{ formataCodigo(form.codnegocio) }}
                </div>
                <div v-for="(nome, i) in temCliente ? form.nomes : []" :key="i" class="q-mb-sm">
                  <span class="text-grey-7">Retira ou recebe:</span> {{ nome }} ·
                  {{ formataTelefone(form.telefones[i]) }}
                </div>
                <div class="q-mb-sm" v-if="form.destino === 'E'">
                  <span class="text-grey-7">Endereço:</span> {{ form.endereco }}
                </div>
                <div class="q-mb-sm">
                  <span class="text-grey-7">Produtos:</span> {{ form.itens.length }}
                </div>
                <div class="q-mb-sm" v-if="!isNovo">
                  <span class="text-grey-7">Prioridade:</span>
                  {{ PRIORIDADE[form.prioridade]?.label }}
                </div>
              </div>
              <div class="col-12" v-if="isNovo">
                <div class="text-grey-7">Prioridade</div>
                <q-option-group
                  v-model="form.prioridade"
                  :options="opcoesPrioridade"
                  type="radio"
                  inline
                />
              </div>
              <div class="col-12">
                <MgInput
                  v-model="form.observacoes"
                  label="Observações"
                  type="textarea"
                  autogrow
                  maxlength="500"
                />
              </div>
            </div>
          </q-card-section>

          <q-separator inset />
          <q-card-actions align="right">
            <q-btn
              flat
              color="grey-8"
              :label="passo === 0 ? 'Cancelar' : 'Voltar'"
              tabindex="-1"
              @click="voltar"
            />
            <q-btn
              v-if="ultima"
              ref="salvarRef"
              flat
              color="primary"
              label="Salvar"
              type="submit"
              :loading="salvando"
              :disable="!form.itens.length"
            />
            <q-btn
              v-else-if="!etapaLista"
              flat
              color="primary"
              :label="etapa === 'negocio' && !form.codnegocio ? 'Pular' : 'Continuar'"
              type="submit"
              :loading="sRequisicao.carregandoNegocio"
              :disable="etapa === 'produtos' && semProdutos"
            />
          </q-card-actions>
        </q-form>
      </q-card>
    </div>
  </q-page>
</template>
