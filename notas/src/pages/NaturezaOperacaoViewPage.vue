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

const handleDelete = () => {
  $q.dialog({
    title: 'Confirmar exclusão',
    message: `Deseja realmente excluir a natureza de operação "${naturezaOperacao.value?.naturezaoperacao}"?`,
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Excluir', color: 'negative' },
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

const handleDeleteTributacao = (trib) => {
  $q.dialog({
    title: 'Confirmar exclusão',
    message: `Deseja realmente excluir a tributação #${trib.codtributacaonaturezaoperacao}?`,
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Excluir', color: 'negative' },
  }).onOk(async () => {
    try {
      await tributacaoStore.deleteTributacao(trib.codtributacaonaturezaoperacao)
      $q.notify({ type: 'positive', message: 'Tributação excluída com sucesso' })
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erro ao excluir tributação',
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
        <q-btn flat dense round icon="arrow_back" :to="{ name: 'natureza-operacao' }" />
        <div class="text-h5 q-ml-sm">{{ naturezaOperacao.naturezaoperacao }}</div>
        <q-space />
        <q-btn
          flat
          round
          icon="edit"
          color="primary"
          :to="{
            name: 'natureza-operacao-edit',
            params: { codnaturezaoperacao: codnaturezaoperacao },
          }"
        >
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
                  {{ getOperacaoLabel(naturezaOperacao.codoperacao) }}
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
                flat
                dense
                color="white"
                icon="add"
                size="md"
                :to="{
                  name: 'tributacao-natureza-operacao-create',
                  params: { codnaturezaoperacao: codnaturezaoperacao },
                }"
                class="q-ml-sm"
              >
                <q-tooltip>Adicionar Tributação</q-tooltip>
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
                <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">
                  <q-card flat bordered class="full-height flex column">
                    <!-- Header -->
                    <router-link
                      :to="{
                        name: 'tributacao-natureza-operacao-view',
                        params: {
                          codnaturezaoperacao: codnaturezaoperacao,
                          codtributacaonaturezaoperacao: trib.codtributacaonaturezaoperacao,
                        },
                      }"
                      class="bg-grey-3 q-pa-sm cursor-pointer block"
                      style="text-decoration: none; color: inherit"
                    >
                      <div class="row items-center q-gutter-sm text-caption">
                        <q-badge color="primary" class="text-weight-bold">
                          #{{ String(trib.codtributacaonaturezaoperacao).padStart(8, '0') }}
                        </q-badge>
                        <span class="text-weight-bold text-secondary">
                          {{ trib.tributacao?.tributacao || trib.tributacao || '-' }}
                        </span>
                        <span class="text-tertiary">
                          {{ getTipoProdutoLabel(trib.codtipoproduto) }}
                        </span>
                        <span v-if="trib.estado?.sigla" class="text-grey-7">
                          {{ trib.estado.sigla }}
                        </span>
                        <span v-if="trib.ncm" class="text-grey-7">NCM: {{ trib.ncm }}</span>
                        <span class="text-yellow-9">CFOP: {{ trib.codcfop }}</span>
                        <span
                          class="text-yellow-9"
                          style="
                            display: inline-block;
                            max-width: 150px;
                            overflow: hidden;
                            white-space: nowrap;
                            text-overflow: ellipsis;
                            vertical-align: middle;
                          "
                        >
                          {{ trib.cfop?.cfop || trib.cfop || '' }}
                        </span>
                        <q-badge v-if="trib.bit">BIT</q-badge>
                      </div>
                    </router-link>

                    <q-separator />

                    <!-- Tributos -->
                    <q-card-section class="q-pa-sm col-grow justify-center">
                      <div class="row q-col-gutter-x-xs q-col-gutter-y-sm text-caption">
                        <!-- Simples -->
                        <div class="col-6">
                          <div class="text-grey-7">Simples</div>
                          <div>{{ trib.csosn ?? '-' }}</div>
                          <div>{{ formatPercent(trib.icmsbase) }}</div>
                          <div>{{ formatPercent(trib.icmspercentual) }}</div>
                        </div>
                        <!-- ICMS LP -->
                        <div class="col-6">
                          <div class="text-grey-7">ICMS</div>
                          <div>{{ trib.icmscst ?? '-' }}</div>
                          <div>{{ formatPercent(trib.icmslpbase) }}</div>
                          <div>{{ formatPercent(trib.icmslppercentual) }}</div>
                        </div>
                        <!-- PIS -->
                        <div class="col-6">
                          <div class="text-grey-7">PIS</div>
                          <div>{{ trib.piscst ?? '-' }}</div>
                          <div>{{ formatPercent(trib.pispercentual) }}</div>
                        </div>
                        <!-- COFINS -->
                        <div class="col-6">
                          <div class="text-grey-7">Cofins</div>
                          <div>{{ trib.cofinscst ?? '-' }}</div>
                          <div>{{ formatPercent(trib.cofinspercentual) }}</div>
                        </div>
                        <!-- IPI/CSLL/IRPJ -->
                        <div class="col-6">
                          <div class="text-grey-7">IPI/CSLL/IRPJ</div>
                          <div>{{ trib.ipicst ?? '-' }}</div>
                          <div>{{ formatPercent(trib.csllpercentual) }}</div>
                          <div>{{ formatPercent(trib.irpjpercentual) }}</div>
                        </div>
                        <!-- Rural (se houver) -->
                        <div
                          v-if="
                            trib.fethabkg ||
                            trib.iagrokg ||
                            trib.funruralpercentual ||
                            trib.senarpercentual
                          "
                          class="col-6"
                        >
                          <div class="text-grey-7">Rural</div>
                          <div v-if="trib.funruralpercentual">
                            {{ formatPercent(trib.funruralpercentual) }}
                          </div>
                          <div v-if="trib.senarpercentual">
                            {{ formatPercent(trib.senarpercentual) }}
                          </div>
                          <div v-if="trib.fethabkg">{{ trib.fethabkg }}/kg</div>
                          <div v-if="trib.iagrokg">{{ trib.iagrokg }}/kg</div>
                        </div>
                      </div>
                    </q-card-section>

                    <q-separator />

                    <!-- Ações -->
                    <q-card-actions align="right">
                      <q-btn
                        flat
                        round
                        icon="edit"
                        color="primary"
                        size="sm"
                        :to="{
                          name: 'tributacao-natureza-operacao-edit',
                          params: {
                            codnaturezaoperacao: codnaturezaoperacao,
                            codtributacaonaturezaoperacao: trib.codtributacaonaturezaoperacao,
                          },
                        }"
                      >
                        <q-tooltip>Editar</q-tooltip>
                      </q-btn>
                      <q-btn
                        flat
                        round
                        icon="delete"
                        color="negative"
                        size="sm"
                        @click="handleDeleteTributacao(trib)"
                      >
                        <q-tooltip>Excluir</q-tooltip>
                      </q-btn>
                    </q-card-actions>
                  </q-card>
                </div>
              </template>
            </div>

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
