<script setup>
import { computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import {
  useNaturezaOperacaoStore,
  FINNFE_OPTIONS,
  OPERACAO_OPTIONS,
} from '../stores/naturezaOperacaoStore'
import {
  useTributacaoNaturezaOperacaoStore,
  TIPO_PRODUTO_OPTIONS,
} from '../stores/tributacaoNaturezaOperacaoStore'

const router = useRouter()
const route = useRoute()
const $q = useQuasar()
const naturezaOperacaoStore = useNaturezaOperacaoStore()
const tributacaoStore = useTributacaoNaturezaOperacaoStore()

const codnaturezaoperacao = computed(() => route.params.codnaturezaoperacao)
const naturezaOperacao = computed(() => naturezaOperacaoStore.currentNaturezaOperacao)
const loading = computed(() => naturezaOperacaoStore.loading.naturezaOperacao)
const tributacoes = computed(() => tributacaoStore.tributacoes)
const tributacoesLoading = computed(() => tributacaoStore.pagination.loading)

// Labels helpers
const getOperacaoLabel = (codoperacao) => {
  const opt = OPERACAO_OPTIONS.find((o) => o.value === codoperacao)
  return opt ? opt.label : '-'
}

const getFinnfeLabel = (finnfe) => {
  const opt = FINNFE_OPTIONS.find((o) => o.value === finnfe)
  return opt ? opt.label : '-'
}

const getTipoProdutoLabel = (codtipoproduto) => {
  const opt = TIPO_PRODUTO_OPTIONS.find((o) => o.value === codtipoproduto)
  return opt ? opt.label : '-'
}

// const formatCfop = (cfop) => {
//   if (!cfop) return '-'
//   const str = String(cfop)
//   return str.length === 4 ? `${str[0]}.${str.slice(1)}` : str
// }

const formatPercent = (value) => {
  if (value === null || value === undefined) return '-'
  return `${parseFloat(value).toFixed(2)}%`
}

// Ações
const handleBack = () => {
  router.push({ name: 'natureza-operacao' })
}

const handleEdit = () => {
  router.push({
    name: 'natureza-operacao-edit',
    params: { codnaturezaoperacao: codnaturezaoperacao.value },
  })
}

const handleDelete = () => {
  $q.dialog({
    title: 'Confirmar exclusão',
    message: `Deseja realmente excluir a natureza de operação "${naturezaOperacao.value?.naturezaoperacao}"?`,
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Excluir', color: 'negative' },
    persistent: true,
  }).onOk(async () => {
    try {
      await naturezaOperacaoStore.deleteNaturezaOperacao(codnaturezaoperacao.value)
      $q.notify({ type: 'positive', message: 'Natureza de Operação excluída com sucesso' })
      router.push({ name: 'natureza-operacao' })
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erro ao excluir',
        caption: error.response?.data?.message || error.message,
      })
    }
  })
}

// const handleCreateTributacao = () => {
//   router.push({
//     name: 'tributacao-natureza-operacao-create',
//     params: { codnaturezaoperacao: codnaturezaoperacao.value },
//   })
// }

const handleViewTributacao = (codtributacaonaturezaoperacao) => {
  router.push({
    name: 'tributacao-natureza-operacao-view',
    params: {
      codnaturezaoperacao: codnaturezaoperacao.value,
      codtributacaonaturezaoperacao,
    },
  })
}

const onLoadTributacoes = async (index, done) => {
  try {
    await tributacaoStore.fetchTributacoes()
    done(!tributacaoStore.pagination.hasMore)
  } catch {
    $q.notify({ type: 'negative', message: 'Erro ao carregar tributações' })
    done(true)
  }
}

// Carrega dados
const loadData = async () => {
  try {
    await naturezaOperacaoStore.fetchNaturezaOperacao(codnaturezaoperacao.value)
    tributacaoStore.setCodNaturezaOperacao(parseInt(codnaturezaoperacao.value))
    await tributacaoStore.fetchTributacoes(true)
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao carregar Natureza de Operação',
      caption: error.response?.data?.message || error.message,
    })
    router.push({ name: 'natureza-operacao' })
  }
}

watch(codnaturezaoperacao, loadData)
onMounted(loadData)
</script>

<template>
  <q-page class="q-pa-md">
    <!-- Loading -->
    <div v-if="loading" class="row justify-center q-py-xl">
      <q-spinner color="primary" size="3em" />
    </div>

    <template v-else-if="naturezaOperacao">
      <!-- Header -->
      <div class="row items-center q-mb-md">
        <q-btn flat dense round icon="arrow_back" @click="handleBack" />
        <div class="text-h5 q-ml-sm">{{ naturezaOperacao.naturezaoperacao }}</div>
        <q-space />
        <q-btn flat round icon="edit" color="primary" @click="handleEdit">
          <q-tooltip>Editar</q-tooltip>
        </q-btn>
        <q-btn flat round icon="delete" color="negative" @click="handleDelete">
          <q-tooltip>Excluir</q-tooltip>
        </q-btn>
      </div>

      <!-- Detalhes em 2 colunas -->
      <div class="row q-col-gutter-md q-mb-lg">
        <div class="col-12 col-md-6">
          <q-card flat bordered>
            <q-card-section class="bg-primary text-white q-px-md q-py-sm q-mb-sm">
              <div class="text-body2 text-weight-bold">DETALHES</div>
            </q-card-section>
            <q-list separator>
              <q-item>
                <q-item-section class="text-caption">
                  <q-item-label class="text-subtitle2 text-grey-7">Código</q-item-label>
                  #{{ String(naturezaOperacao.codnaturezaoperacao).padStart(8, '0') }}
                </q-item-section>
              </q-item>
              <q-item>
                <q-item-section class="text-caption">
                  <q-item-label class="text-subtitle2 text-grey-7">Operação</q-item-label>
                  {{ getOperacaoLabel(naturezaOperacao.codnaturezaoperacao) }}
                </q-item-section>
              </q-item>
              <q-item>
                <q-item-section class="text-caption">
                  <q-item-label class="text-subtitle2 text-grey-7">Nossa Emissão</q-item-label>
                  {{ naturezaOperacao.emitida ? 'Sim' : 'Não' }}
                </q-item-section>
              </q-item>
              <q-item>
                <q-item-section class="text-caption">
                  <q-item-label class="text-subtitle2 text-grey-7">Finalidade NFe</q-item-label>
                  {{ getFinnfeLabel(naturezaOperacao.finnfe) }}
                </q-item-section>
              </q-item>
              <q-item v-if="naturezaOperacao.observacoesnf">
                <q-item-section class="text-caption">
                  <q-item-label class="text-subtitle2 text-grey-7">Observações NF</q-item-label>
                  {{ naturezaOperacao.observacoesnf }}
                </q-item-section>
              </q-item>
              <q-item v-if="naturezaOperacao.mensagemprocom">
                <q-item-section class="text-caption">
                  <q-item-label class="text-subtitle2 text-grey-7">Mensagem Procom</q-item-label>
                  {{ naturezaOperacao.mensagemprocom }}
                </q-item-section>
              </q-item>
              <q-item v-if="naturezaOperacao.vendadevolucao">
                <q-item-section class="text-caption">
                  <q-item-label class="text-subtitle2 text-grey-7">
                    Contabiliza como devolução de Venda
                  </q-item-label>
                  {{ naturezaOperacao.vendadevolucao === true ? 'Sim' : 'Não' }}
                </q-item-section>
              </q-item>
              <q-item>
                <q-item-section class="text-caption">
                  <q-item-label class="text-subtitle2 text-grey-7">Tipo Titulo</q-item-label>
                  {{ naturezaOperacao.tipoTitulo.tipotitulo }}
                </q-item-section>
              </q-item>
            </q-list>
          </q-card>
        </div>

        <div class="col-12 col-md-6">
          <q-card flat bordered>
            <q-card-section class="bg-primary text-white q-px-md q-py-sm q-mb-sm">
              <div class="text-body2 text-weight-bold">CONTABILIDADE</div>
            </q-card-section>
            <q-list separator>
              <q-item>
                <q-item-section class="text-caption">
                  <q-item-label class="text-subtitle2 text-grey-7">Configurações</q-item-label>
                  <q-item-label>
                    <q-badge v-if="naturezaOperacao.ibpt" color="grey" class="q-mr-xs">
                      IBPT
                    </q-badge>
                    <q-badge v-if="naturezaOperacao.estoque" color="teal" class="q-mr-xs">
                      Estoque
                    </q-badge>
                    <q-badge v-if="naturezaOperacao.financeiro" color="purple" class="q-mr-xs">
                      Financeiro
                    </q-badge>
                    <q-badge v-if="naturezaOperacao.venda" color="green" class="q-mr-xs">
                      Venda
                    </q-badge>
                    <q-badge v-if="naturezaOperacao.compra" color="blue" class="q-mr-xs">
                      Compra
                    </q-badge>
                    <q-badge v-if="naturezaOperacao.transferencia" color="amber" class="q-mr-xs">
                      Transferência
                    </q-badge>
                    <q-badge v-if="naturezaOperacao.vendadevolucao" color="red" class="q-mr-xs">
                      Dev. Venda
                    </q-badge>
                    <span
                      v-if="
                        !naturezaOperacao.ibpt &&
                        !naturezaOperacao.estoque &&
                        !naturezaOperacao.financeiro &&
                        !naturezaOperacao.venda &&
                        !naturezaOperacao.compra &&
                        !naturezaOperacao.transferencia &&
                        !naturezaOperacao.vendadevolucao
                      "
                      class="text-grey"
                    >
                      -
                    </span>
                  </q-item-label>
                </q-item-section>
              </q-item>
              <q-item v-if="naturezaOperacao.naturezaOperacaoDevolucao">
                <q-item-section class="text-caption">
                  <q-item-label class="text-subtitle2 text-grey-7">
                    Natureza de Devolução
                  </q-item-label>
                  {{ naturezaOperacao.naturezaOperacaoDevolucao.naturezaoperacao }}
                </q-item-section>
              </q-item>
              <q-item v-if="naturezaOperacao.tipoTitulo">
                <q-item-section class="text-caption">
                  <q-item-label class="text-subtitle2 text-grey-7">Tipo Título</q-item-label>
                  {{ naturezaOperacao.tipoTitulo.tipotitulo }}
                </q-item-section>
              </q-item>
              <q-item v-if="naturezaOperacao.contaContabil">
                <q-item-section class="text-caption">
                  <q-item-label class="text-subtitle2 text-grey-7">Conta Contábil</q-item-label>
                  {{ naturezaOperacao.contaContabil.contacontabil }}
                </q-item-section>
              </q-item>
              <q-item>
                <q-item-section class="text-caption">
                  <q-item-label class="text-subtitle2 text-grey-7">
                    Calcular Tributos com base IBPT
                  </q-item-label>
                  {{ naturezaOperacao.ibpt === true ? 'Sim' : 'Não' }}
                </q-item-section>
              </q-item>
              <q-item>
                <q-item-section class="text-caption">
                  <q-item-label class="text-subtitle2 text-grey-7">Movimenta Estoque</q-item-label>
                  {{ naturezaOperacao.estoque === true ? 'Sim' : 'Não' }}
                </q-item-section>
              </q-item>
              <q-item v-if="naturezaOperacao.compra">
                <q-item-section class="text-caption">
                  <q-item-label class="text-subtitle2 text-grey-7">
                    Contabiliza como Compra
                  </q-item-label>
                  {{ naturezaOperacao.compra === true ? 'Sim' : 'Não' }}
                </q-item-section>
              </q-item>
              <q-item v-else-if="naturezaOperacao.venda">
                <q-item-section class="text-caption">
                  <q-item-label class="text-subtitle2 text-grey-7">
                    Contabiliza como venda
                  </q-item-label>
                  {{ naturezaOperacao.venda === true ? 'Sim' : 'Não' }}
                </q-item-section>
              </q-item>
            </q-list>
          </q-card>
        </div>
      </div>

      <!-- Tributações -->
      <q-card flat bordered class="full-height">
        <q-card-section class="bg-primary text-white">
          <div class="row items-center justify-between">
            <div class="text-h6">
              <q-icon name="receipt_long" size="1.5em" class="q-mr-sm" />
              Tributações
              <q-badge
                color="white"
                text-color="primary"
                class="q-ml-sm text-weight-bold text-body1"
              >
                {{ tributacoes.length }}
              </q-badge>
              <q-btn
                v-if="!notaBloqueada"
                flat
                dense
                color="white"
                icon="add"
                size="md"
                :to="{
                  name: 'tributacao-natureza-operacao-create',
                  params: { codnaturezaoperacao: codnaturezaoperacao.value },
                }"
                class="q-ml-sm"
              >
                <q-tooltip>Adicionar Item</q-tooltip>
              </q-btn>
            </div>
          </div>
        </q-card-section>
        <q-card-section>
          <!-- Loading tributações -->
          <div
            v-if="tributacoesLoading && tributacoes.length === 0"
            class="row justify-center q-py-md"
          >
            <q-spinner color="primary" size="2em" />
          </div>
          <div v-else-if="tributacoes.length === 0" class="row justify-center q-py-md">
            <q-icon name="receipt_long" size="3em" color="grey-5" />
            <div class="text-body2 text-grey-7 q-mt-sm">Nenhuma tributação cadastrada</div>
          </div>

          <!-- Lista de tributações -->
          <q-infinite-scroll v-else @load="onLoadTributacoes" :offset="250">
            <div class="row q-col-gutter-md">
              <template v-for="trib in tributacoes" :key="trib.codtributacaonaturezaoperacao">
                <div class="col-xs-12 col-sm-4 col-md-3 col-lg-2">
                  <q-card flat bordered class="full-height flex column">
                    <q-item class="bg-grey-3">
                      <q-item-section avatar>
                        <q-avatar
                          color="primary"
                          text-color="blue-2"
                          size="md"
                          class="q-mr-sm text-weight-bolder"
                        >
                          {{ trib.codtributacaonaturezaoperacao }}
                        </q-avatar>
                      </q-item-section>
                      <q-item-section style="height: 70px">
                        <q-item-label lines="3" class="text-black text-body2">
                          {{ trib.codcfop }}
                        </q-item-label>
                        <q-item-label caption>
                          {{ trib.tributacao }}
                        </q-item-label>
                      </q-item-section>
                    </q-item>
                  </q-card>
                </div>
              </template>
            </div>

            <q-card
              flat
              bordered
              class="q-mb-sm"
              v-for="trib in tributacoes"
              :key="trib.codtributacaonaturezaoperacao"
            >
              <q-card-section
                class="q-py-sm cursor-pointer"
                @click="handleViewTributacao(trib.codtributacaonaturezaoperacao)"
              >
                <!-- Linha 1: Identificação -->
                <div class="items-center row q-mb-xs q-gutter-lg">
                  <div class="text-body2 text-primary text-weight-bold">
                    #{{ trib.codtributacaonaturezaoperacao }}
                  </div>
                  <div v-if="trib.tributacao" class="col-auto text-grey-7 text-weight-bold">
                    {{ trib.tributacao }}
                  </div>
                  <div class="text-body2 text-grey-7 text-weight-bold">
                    {{ getTipoProdutoLabel(trib.codtipoproduto) }}
                  </div>
                  <div v-if="trib.tipotributacao" class="text-body2 text-grey-7 text-weight-bold">
                    {{ trib.tipotributacao }}
                  </div>
                  <div v-if="trib.ncm" class="text-body2 text-grey-7 text-weight-bold">
                    NCM: {{ trib.ncm }}
                  </div>
                  <div
                    class="text-body2 text-white bg-primary text-weight-bold ellipsis rounded-borders q-px-xs"
                    style="max-width: 500px"
                  >
                    CFOP: {{ trib.codcfop }} - {{ trib.cfop }}
                  </div>
                </div>

                <!-- Linha 2: Tributos -->
                <div class="row q-col-gutter-xs text-caption">
                  <!-- Simples -->
                  <div class="col-6 col-sm-4 col-md-2">
                    <div class="text-grey-7 text-weight-bold">Simples:</div>
                    <div>{{ trib.csosn || '-' }}</div>
                    <div>{{ formatPercent(trib.icmsbase) }}</div>
                    <div>{{ formatPercent(trib.icmspercentualSimples) }}</div>
                  </div>
                  <!-- ICMS -->
                  <div class="col-6 col-sm-4 col-md-2">
                    <div class="text-grey-7 text-weight-bold">ICMS</div>
                    <div>{{ trib.icmscst ?? '-' }}</div>
                    <div>{{ formatPercent(trib.icmslpbase) }}</div>
                    <div>{{ formatPercent(trib.icmspercentual) }}</div>
                  </div>
                  <!-- PIS -->
                  <div class="col-6 col-sm-4 col-md-2">
                    <div class="text-grey-7 text-weight-bold">PIS</div>
                    <div>{{ trib.piscst ?? '-' }}</div>
                    <div>{{ formatPercent(trib.pispercentual) }}</div>
                  </div>
                  <!-- COFINS -->
                  <div class="col-6 col-sm-4 col-md-2">
                    <div class="text-grey-7 text-weight-bold">Cofins</div>
                    <div>{{ trib.cofinscst ?? '-' }}</div>
                    <div>{{ formatPercent(trib.cofinspercentual) }}</div>
                  </div>
                  <!-- IPI/CSLL/IRPJ -->
                  <div class="col-6 col-sm-4 col-md-2">
                    <div class="text-grey-7 text-weight-bold">IPI/CSLL/IRPJ</div>
                    <div>{{ trib.ipicst ?? '-' }}</div>
                    <div>{{ formatPercent(trib.csllpercentual) }}</div>
                    <div>{{ formatPercent(trib.irpjpercentual) }}</div>
                  </div>
                </div>
              </q-card-section>
            </q-card>

            <template v-slot:loading>
              <div class="row justify-center q-my-md">
                <q-spinner-dots color="primary" size="40px" />
              </div>
            </template>
          </q-infinite-scroll>
        </q-card-section>
      </q-card>

      <!-- Empty state -->

      <!-- Auditoria -->
      <div v-if="naturezaOperacao.usuarioAlteracao" class="text-caption text-grey q-mt-lg">
        Alterado em {{ new Date(naturezaOperacao.alteracao).toLocaleString('pt-BR') }} por
        {{ naturezaOperacao.usuarioAlteracao.usuario }}
      </div>
    </template>
  </q-page>
</template>
