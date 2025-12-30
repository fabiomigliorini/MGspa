<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import { useNotaFiscalStore } from '../stores/notaFiscalStore'

const router = useRouter()
const route = useRoute()
const $q = useQuasar()
const notaFiscalStore = useNotaFiscalStore()

// State
const currentTab = ref('resumo')

// Computed
const nota = computed(() => notaFiscalStore.currentNota)
const itens = computed(() => notaFiscalStore.itens)
const pagamentos = computed(() => notaFiscalStore.pagamentos)
const duplicatas = computed(() => notaFiscalStore.duplicatas)
const referenciadas = computed(() => notaFiscalStore.referenciadas)
const cartasCorrecao = computed(() => notaFiscalStore.cartasCorrecao)

const loadingNota = computed(() => notaFiscalStore.loading.nota)
const loadingItens = computed(() => notaFiscalStore.loading.itens)
const loadingPagamentos = computed(() => notaFiscalStore.loading.pagamentos)
const loadingDuplicatas = computed(() => notaFiscalStore.loading.duplicatas)
const loadingReferenciadas = computed(() => notaFiscalStore.loading.referenciadas)
const loadingCartasCorrecao = computed(() => notaFiscalStore.loading.cartasCorrecao)

const notaBloqueada = computed(() => {
  if (!nota.value) return false
  return ['Autorizada', 'Cancelada', 'Inutilizada'].includes(nota.value.status)
})

const podeAdicionarCCe = computed(() => {
  return nota.value?.status === 'Autorizada'
})

// Methods
const loadData = async () => {
  try {
    await notaFiscalStore.fetchNota(route.params.codnotafiscal)

    if (nota.value) {
      await Promise.all([
        notaFiscalStore.fetchItens(route.params.codnotafiscal),
        notaFiscalStore.fetchPagamentos(route.params.codnotafiscal),
        notaFiscalStore.fetchDuplicatas(route.params.codnotafiscal),
        notaFiscalStore.fetchReferenciadas(route.params.codnotafiscal),
        notaFiscalStore.fetchCartasCorrecao(route.params.codnotafiscal),
      ])
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao carregar nota fiscal',
      caption: error.response?.data?.message || error.message,
    })
  }
}

const handleBack = () => {
  router.push({ name: 'notas' })
}

const handleEdit = () => {
  router.push({ name: 'nota-fiscal-edit', params: { codnotafiscal: route.params.codnotafiscal } })
}

const handleDelete = () => {
  $q.dialog({
    title: 'Confirmar exclusão',
    message: `Deseja realmente excluir a nota fiscal ${nota.value.modelo} nº ${nota.value.numero}?`,
    cancel: {
      label: 'Cancelar',
      flat: true,
    },
    ok: {
      label: 'Excluir',
      color: 'negative',
    },
    persistent: true,
  }).onOk(async () => {
    try {
      await notaFiscalStore.deleteNota(route.params.codnotafiscal)
      $q.notify({
        type: 'positive',
        message: 'Nota fiscal excluída com sucesso',
      })
      router.push({ name: 'notas' })
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erro ao excluir nota fiscal',
        caption: error.response?.data?.message || error.message,
      })
    }
  })
}

const handleAddItem = () => {
  router.push({
    name: 'nota-fiscal-item-adicionar',
    params: { codnotafiscal: route.params.codnotafiscal },
  })
}

const handleAddPagamento = () => {
  // TODO: Implementar dialog de pagamento
  $q.notify({ type: 'info', message: 'Funcionalidade em desenvolvimento' })
}

const handleAddDuplicata = () => {
  // TODO: Implementar dialog de duplicata
  $q.notify({ type: 'info', message: 'Funcionalidade em desenvolvimento' })
}

const handleAddReferenciada = () => {
  // TODO: Implementar dialog de nota referenciada
  $q.notify({ type: 'info', message: 'Funcionalidade em desenvolvimento' })
}

const handleAddCartaCorrecao = () => {
  // TODO: Implementar dialog de carta de correção
  $q.notify({ type: 'info', message: 'Funcionalidade em desenvolvimento' })
}

const getSituacaoColor = (situacao) => {
  const colors = {
    Digitacao: 'blue-grey',
    Autorizada: 'positive',
    Cancelada: 'negative',
    Inutilizada: 'warning',
    Denegada: 'deep-orange',
  }
  return colors[situacao] || 'grey'
}

const formatDate = (value) => {
  if (!value) return '-'
  // Converte ISO string ou timestamp para DD/MM/YYYY
  const dateObj = new Date(value)
  return dateObj.toLocaleDateString('pt-BR')
}

const formatCurrency = (value) => {
  if (!value) return '0,00'
  return parseFloat(value).toLocaleString('pt-BR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
}

// Lifecycle
onMounted(() => {
  loadData()
})
</script>

<template>
  <q-page padding>
    <!-- Loading -->
    <div v-if="loadingNota" class="row justify-center q-py-xl">
      <q-spinner color="primary" size="3em" />
    </div>

    <!-- Conteúdo -->
    <div v-else-if="nota">
      <!-- Header -->
      <div class="row items-start q-mb-md">
        <div class="col">
          <div class="row items-center q-mb-xs">
            <q-btn flat dense round icon="arrow_back" @click="handleBack" class="q-mr-sm" />
            <div class="text-h5">
              {{ nota.modelo }} Nº {{ nota.numero }}
              <span v-if="nota.serie"> / Série {{ nota.serie }}</span>
            </div>
            <q-badge :color="getSituacaoColor(nota.status)" class="q-ml-md">
              {{ nota.status }}
            </q-badge>
          </div>
          <div class="text-caption text-grey-7 q-ml-xl">
            <q-icon name="calendar_today" size="xs" class="q-mr-xs" />
            Emissão: {{ formatDate(nota.emissao) }} | Saída: {{ formatDate(nota.saida) }}
          </div>
          <div class="text-caption text-grey-7 q-ml-xl">
            <q-icon name="person" size="xs" class="q-mr-xs" />
            {{ nota.pessoa?.fantasia || nota.pessoa?.pessoa || 'Sem destinatário' }}
          </div>
        </div>
        <div class="col-auto">
          <q-btn
            flat
            icon="edit"
            label="Editar"
            @click="handleEdit"
            :disable="notaBloqueada"
            class="q-mr-sm"
          />
          <q-btn
            flat
            color="negative"
            icon="delete"
            label="Excluir"
            @click="handleDelete"
            :disable="notaBloqueada"
          />
        </div>
      </div>

      <!-- Tabs -->
      <q-card>
        <q-tabs
          v-model="currentTab"
          dense
          class="text-grey"
          active-color="primary"
          indicator-color="primary"
          align="left"
        >
          <q-tab name="resumo" label="Nota Completa" icon="description" />
          <q-tab name="itens" label="Itens" icon="inventory_2">
            <q-badge v-if="itens.length > 0" color="primary" floating>
              {{ itens.length }}
            </q-badge>
          </q-tab>
          <q-tab name="pagamentos" label="Pagamentos" icon="payment">
            <q-badge v-if="pagamentos.length > 0" color="primary" floating>
              {{ pagamentos.length }}
            </q-badge>
          </q-tab>
          <q-tab name="duplicatas" label="Duplicatas" icon="receipt_long">
            <q-badge v-if="duplicatas.length > 0" color="primary" floating>
              {{ duplicatas.length }}
            </q-badge>
          </q-tab>
          <q-tab name="referenciadas" label="NF Referenciadas" icon="link">
            <q-badge v-if="referenciadas.length > 0" color="primary" floating>
              {{ referenciadas.length }}
            </q-badge>
          </q-tab>
          <q-tab name="correcoes" label="Cartas Correção" icon="edit_note">
            <q-badge v-if="cartasCorrecao.length > 0" color="primary" floating>
              {{ cartasCorrecao.length }}
            </q-badge>
          </q-tab>
        </q-tabs>

        <q-separator />

        <q-tab-panels v-model="currentTab" animated>
          <!-- Nota Completa -->
          <q-tab-panel name="resumo">
            <div class="row q-col-gutter-md">
              <!-- Informações Gerais -->
              <div class="col-12 col-md-6">
                <q-card flat bordered>
                  <q-card-section>
                    <div class="text-h6 q-mb-md">Informações Gerais</div>

                    <div class="row q-col-gutter-sm">
                      <div class="col-6">
                        <div class="text-caption text-grey-7">Modelo</div>
                        <div class="text-body2">{{ nota.modelo }}</div>
                      </div>
                      <div class="col-6">
                        <div class="text-caption text-grey-7">Série</div>
                        <div class="text-body2">{{ nota.serie }}</div>
                      </div>
                      <div class="col-6">
                        <div class="text-caption text-grey-7">Número</div>
                        <div class="text-body2">{{ nota.numero }}</div>
                      </div>
                      <div class="col-6">
                        <div class="text-caption text-grey-7">Situação</div>
                        <div>
                          <q-badge :color="getSituacaoColor(nota.status)">
                            {{ nota.status }}
                          </q-badge>
                        </div>
                      </div>
                      <div class="col-12">
                        <div class="text-caption text-grey-7">Chave NFe</div>
                        <div class="text-body2">{{ nota.nfechave || '-' }}</div>
                      </div>
                      <div class="col-12">
                        <div class="text-caption text-grey-7">Natureza Operação</div>
                        <div class="text-body2">
                          {{ nota.naturezaOperacao?.naturezaoperacao || '-' }}
                        </div>
                      </div>
                    </div>
                  </q-card-section>
                </q-card>
              </div>

              <!-- Valores -->
              <div class="col-12 col-md-6">
                <q-card flat bordered>
                  <q-card-section>
                    <div class="text-h6 q-mb-md">Valores</div>

                    <div class="row q-col-gutter-sm">
                      <div class="col-12">
                        <div class="text-caption text-grey-7">Valor Produtos</div>
                        <div class="text-weight-bold text-primary">
                          R$ {{ formatCurrency(nota.valorprodutos) }}
                        </div>
                      </div>
                      <div class="col-6">
                        <div class="text-caption text-grey-7">Desconto</div>
                        <div class="text-body2">R$ {{ formatCurrency(nota.valordesconto) }}</div>
                      </div>
                      <div class="col-6">
                        <div class="text-caption text-grey-7">Frete</div>
                        <div class="text-body2">R$ {{ formatCurrency(nota.valorfrete) }}</div>
                      </div>
                      <div class="col-6">
                        <div class="text-caption text-grey-7">Seguro</div>
                        <div class="text-body2">R$ {{ formatCurrency(nota.valorseguro) }}</div>
                      </div>
                      <div class="col-6">
                        <div class="text-caption text-grey-7">Outras Despesas</div>
                        <div class="text-body2">R$ {{ formatCurrency(nota.valoroutros) }}</div>
                      </div>
                      <div class="col-12">
                        <q-separator class="q-my-sm" />
                        <div class="text-caption text-grey-7">Valor Total</div>
                        <div class="text-h5 text-weight-bold text-primary">
                          R$ {{ formatCurrency(nota.valortotal) }}
                        </div>
                      </div>
                    </div>
                  </q-card-section>
                </q-card>
              </div>

              <!-- Destinatário -->
              <div class="col-12" v-if="nota.pessoa">
                <q-card flat bordered>
                  <q-card-section>
                    <div class="text-h6 q-mb-md">Destinatário</div>

                    <div class="row q-col-gutter-sm">
                      <div class="col-12">
                        <div class="text-caption text-grey-7">Nome/Razão Social</div>
                        <div class="text-body2">{{ nota.pessoa.pessoa }}</div>
                      </div>
                      <div class="col-6" v-if="nota.pessoa.fantasia">
                        <div class="text-caption text-grey-7">Nome Fantasia</div>
                        <div class="text-body2">{{ nota.pessoa.fantasia }}</div>
                      </div>
                      <div class="col-6">
                        <div class="text-caption text-grey-7">CPF/CNPJ</div>
                        <div class="text-body2">
                          {{ nota.pessoa.cnpj || nota.pessoa.cpf || '-' }}
                        </div>
                      </div>
                      <div class="col-6" v-if="nota.cpf">
                        <div class="text-caption text-grey-7">CPF na Nota</div>
                        <div class="text-body2">{{ nota.cpf }}</div>
                      </div>
                    </div>
                  </q-card-section>
                </q-card>
              </div>

              <!-- Itens -->
              <div class="col-12">
                <q-card flat bordered>
                  <q-card-section>
                    <div class="row items-center q-mb-md">
                      <div class="col">
                        <div class="text-h6">Itens da Nota Fiscal</div>
                      </div>
                      <div class="col-auto">
                        <q-badge color="primary">{{ itens.length }} item(s)</q-badge>
                      </div>
                    </div>

                    <div v-if="loadingItens" class="row justify-center q-py-md">
                      <q-spinner color="primary" size="2em" />
                    </div>

                    <div v-else-if="itens.length === 0" class="text-center q-py-md text-grey-7">
                      <q-icon name="inventory_2" size="2em" />
                      <div class="q-mt-sm">Nenhum item adicionado</div>
                    </div>

                    <div v-else class="row q-col-gutter-sm">
                      <div
                        v-for="item in itens"
                        :key="item.codnotafiscalprodutobarra"
                        class="col-12"
                      >
                        <q-card flat bordered>
                          <q-card-section class="q-pa-sm">
                            <div class="row items-center">
                              <div class="col">
                                <div class="text-body2">
                                  {{ item.produtoBarra?.descricao || '-' }}
                                </div>
                                <div class="text-caption text-grey-7">
                                  Qtd: {{ item.quantidade }} | Valor Unit: R$
                                  {{ formatCurrency(item.valorunitario) }}
                                </div>
                              </div>
                              <div class="col-auto">
                                <div class="text-body1 text-weight-bold">
                                  R$ {{ formatCurrency(item.valortotal) }}
                                </div>
                              </div>
                            </div>
                          </q-card-section>
                        </q-card>
                      </div>
                    </div>
                  </q-card-section>
                </q-card>
              </div>

              <!-- Pagamentos -->
              <div class="col-12">
                <q-card flat bordered>
                  <q-card-section>
                    <div class="row items-center q-mb-md">
                      <div class="col">
                        <div class="text-h6">Formas de Pagamento</div>
                      </div>
                      <div class="col-auto">
                        <q-badge color="primary">{{ pagamentos.length }} pagamento(s)</q-badge>
                      </div>
                    </div>

                    <div v-if="loadingPagamentos" class="row justify-center q-py-md">
                      <q-spinner color="primary" size="2em" />
                    </div>

                    <div
                      v-else-if="pagamentos.length === 0"
                      class="text-center q-py-md text-grey-7"
                    >
                      <q-icon name="payment" size="2em" />
                      <div class="q-mt-sm">Nenhum pagamento adicionado</div>
                    </div>

                    <div v-else class="row q-col-gutter-sm">
                      <div
                        v-for="pag in pagamentos"
                        :key="pag.codnotafiscalpagamento"
                        class="col-12 col-md-6"
                      >
                        <q-card flat bordered>
                          <q-card-section class="q-pa-sm">
                            <div class="text-body2">
                              {{ pag.formapagamento || 'Não especificado' }}
                            </div>
                            <div class="text-caption text-grey-7">
                              R$ {{ formatCurrency(pag.valorpagamento) }}
                            </div>
                          </q-card-section>
                        </q-card>
                      </div>
                    </div>
                  </q-card-section>
                </q-card>
              </div>

              <!-- Duplicatas -->
              <div class="col-12">
                <q-card flat bordered>
                  <q-card-section>
                    <div class="row items-center q-mb-md">
                      <div class="col">
                        <div class="text-h6">Duplicatas / Parcelas</div>
                      </div>
                      <div class="col-auto">
                        <q-badge color="primary">{{ duplicatas.length }} duplicata(s)</q-badge>
                      </div>
                    </div>

                    <div v-if="loadingDuplicatas" class="row justify-center q-py-md">
                      <q-spinner color="primary" size="2em" />
                    </div>

                    <div
                      v-else-if="duplicatas.length === 0"
                      class="text-center q-py-md text-grey-7"
                    >
                      <q-icon name="receipt_long" size="2em" />
                      <div class="q-mt-sm">Nenhuma duplicata adicionada</div>
                    </div>

                    <div v-else class="row q-col-gutter-sm">
                      <div
                        v-for="dup in duplicatas"
                        :key="dup.codnotafiscalduplicatas"
                        class="col-12 col-md-6"
                      >
                        <q-card flat bordered>
                          <q-card-section class="q-pa-sm">
                            <div class="text-body2">Parcela {{ dup.numero || '-' }}</div>
                            <div class="text-caption text-grey-7">
                              Venc: {{ formatDate(dup.vencimento) }} | R$
                              {{ formatCurrency(dup.valor) }}
                            </div>
                          </q-card-section>
                        </q-card>
                      </div>
                    </div>
                  </q-card-section>
                </q-card>
              </div>

              <!-- Referenciadas -->
              <div class="col-12">
                <q-card flat bordered>
                  <q-card-section>
                    <div class="row items-center q-mb-md">
                      <div class="col">
                        <div class="text-h6">Notas Fiscais Referenciadas</div>
                      </div>
                      <div class="col-auto">
                        <q-badge color="primary">{{ referenciadas.length }} nota(s)</q-badge>
                      </div>
                    </div>

                    <div v-if="loadingReferenciadas" class="row justify-center q-py-md">
                      <q-spinner color="primary" size="2em" />
                    </div>

                    <div
                      v-else-if="referenciadas.length === 0"
                      class="text-center q-py-md text-grey-7"
                    >
                      <q-icon name="link" size="2em" />
                      <div class="q-mt-sm">Nenhuma nota referenciada</div>
                    </div>

                    <div v-else class="row q-col-gutter-sm">
                      <div
                        v-for="ref in referenciadas"
                        :key="ref.codnotafiscalreferenciada"
                        class="col-12"
                      >
                        <q-card flat bordered>
                          <q-card-section class="q-pa-sm">
                            <div class="text-body2">{{ ref.chave || ref.nfe || '-' }}</div>
                          </q-card-section>
                        </q-card>
                      </div>
                    </div>
                  </q-card-section>
                </q-card>
              </div>

              <!-- Cartas de Correção -->
              <div class="col-12">
                <q-card flat bordered>
                  <q-card-section>
                    <div class="row items-center q-mb-md">
                      <div class="col">
                        <div class="text-h6">Cartas de Correção (CC-e)</div>
                      </div>
                      <div class="col-auto">
                        <q-badge color="primary">{{ cartasCorrecao.length }} carta(s)</q-badge>
                      </div>
                    </div>

                    <div v-if="loadingCartasCorrecao" class="row justify-center q-py-md">
                      <q-spinner color="primary" size="2em" />
                    </div>

                    <div
                      v-else-if="cartasCorrecao.length === 0"
                      class="text-center q-py-md text-grey-7"
                    >
                      <q-icon name="edit_note" size="2em" />
                      <div class="q-mt-sm">Nenhuma carta de correção emitida</div>
                    </div>

                    <div v-else class="row q-col-gutter-sm">
                      <div
                        v-for="cce in cartasCorrecao"
                        :key="cce.codnotafiscalcartacorrecao"
                        class="col-12"
                      >
                        <q-card flat bordered>
                          <q-card-section class="q-pa-sm">
                            <div class="text-body2">Sequência: {{ cce.sequencia }}</div>
                            <div class="text-caption text-grey-7">{{ formatDate(cce.data) }}</div>
                            <div class="text-body2 q-mt-xs" style="white-space: pre-wrap">
                              {{ cce.correcao }}
                            </div>
                          </q-card-section>
                        </q-card>
                      </div>
                    </div>
                  </q-card-section>
                </q-card>
              </div>

              <!-- Observações -->
              <div class="col-12" v-if="nota.observacoes">
                <q-card flat bordered>
                  <q-card-section>
                    <div class="text-h6 q-mb-md">Observações</div>
                    <div class="text-body2" style="white-space: pre-wrap">
                      {{ nota.observacoes }}
                    </div>
                  </q-card-section>
                </q-card>
              </div>
            </div>
          </q-tab-panel>

          <!-- Itens -->
          <q-tab-panel name="itens">
            <div class="row items-center q-mb-md">
              <div class="col">
                <div class="text-h6">Itens da Nota Fiscal</div>
              </div>
              <div class="col-auto">
                <q-btn
                  color="primary"
                  icon="add"
                  label="Adicionar Item"
                  @click="handleAddItem"
                  :disable="notaBloqueada"
                />
              </div>
            </div>

            <div v-if="loadingItens" class="row justify-center q-py-xl">
              <q-spinner color="primary" size="2em" />
            </div>

            <div v-else-if="itens.length === 0" class="text-center q-py-xl text-grey-7">
              <q-icon name="inventory_2" size="3em" />
              <div class="q-mt-md">Nenhum item adicionado</div>
            </div>

            <div v-else>
              <!-- TODO: Implementar lista de itens com cards -->
              <div v-for="item in itens" :key="item.codnotafiscalprodutobarra">
                <q-card flat bordered class="q-mb-md">
                  <q-card-section>
                    <div class="text-body2">Item: {{ item.produtoBarra?.descricao || '-' }}</div>
                    <div class="text-caption text-grey-7">
                      Qtd: {{ item.quantidade }} | Valor: R$ {{ formatCurrency(item.valortotal) }}
                    </div>
                  </q-card-section>
                </q-card>
              </div>
            </div>
          </q-tab-panel>

          <!-- Pagamentos -->
          <q-tab-panel name="pagamentos">
            <div class="row items-center q-mb-md">
              <div class="col">
                <div class="text-h6">Formas de Pagamento</div>
              </div>
              <div class="col-auto">
                <q-btn
                  color="primary"
                  icon="add"
                  label="Adicionar Pagamento"
                  @click="handleAddPagamento"
                  :disable="notaBloqueada"
                />
              </div>
            </div>

            <div v-if="loadingPagamentos" class="row justify-center q-py-xl">
              <q-spinner color="primary" size="2em" />
            </div>

            <div v-else-if="pagamentos.length === 0" class="text-center q-py-xl text-grey-7">
              <q-icon name="payment" size="3em" />
              <div class="q-mt-md">Nenhum pagamento adicionado</div>
            </div>

            <div v-else>
              <!-- TODO: Implementar lista de pagamentos -->
              <div class="text-body2">{{ pagamentos.length }} pagamento(s)</div>
            </div>
          </q-tab-panel>

          <!-- Duplicatas -->
          <q-tab-panel name="duplicatas">
            <div class="row items-center q-mb-md">
              <div class="col">
                <div class="text-h6">Duplicatas / Parcelas</div>
              </div>
              <div class="col-auto">
                <q-btn
                  color="primary"
                  icon="add"
                  label="Adicionar Duplicata"
                  @click="handleAddDuplicata"
                  :disable="notaBloqueada"
                />
              </div>
            </div>

            <div v-if="loadingDuplicatas" class="row justify-center q-py-xl">
              <q-spinner color="primary" size="2em" />
            </div>

            <div v-else-if="duplicatas.length === 0" class="text-center q-py-xl text-grey-7">
              <q-icon name="receipt_long" size="3em" />
              <div class="q-mt-md">Nenhuma duplicata adicionada</div>
            </div>

            <div v-else>
              <!-- TODO: Implementar lista de duplicatas -->
              <div class="text-body2">{{ duplicatas.length }} duplicata(s)</div>
            </div>
          </q-tab-panel>

          <!-- Referenciadas -->
          <q-tab-panel name="referenciadas">
            <div class="row items-center q-mb-md">
              <div class="col">
                <div class="text-h6">Notas Fiscais Referenciadas</div>
              </div>
              <div class="col-auto">
                <q-btn
                  color="primary"
                  icon="add"
                  label="Adicionar Referência"
                  @click="handleAddReferenciada"
                  :disable="notaBloqueada"
                />
              </div>
            </div>

            <div v-if="loadingReferenciadas" class="row justify-center q-py-xl">
              <q-spinner color="primary" size="2em" />
            </div>

            <div v-else-if="referenciadas.length === 0" class="text-center q-py-xl text-grey-7">
              <q-icon name="link" size="3em" />
              <div class="q-mt-md">Nenhuma nota referenciada</div>
            </div>

            <div v-else>
              <!-- TODO: Implementar lista de referenciadas -->
              <div class="text-body2">{{ referenciadas.length }} nota(s) referenciada(s)</div>
            </div>
          </q-tab-panel>

          <!-- Cartas de Correção -->
          <q-tab-panel name="correcoes">
            <div class="row items-center q-mb-md">
              <div class="col">
                <div class="text-h6">Cartas de Correção Eletrônica (CC-e)</div>
              </div>
              <div class="col-auto">
                <q-btn
                  color="primary"
                  icon="add"
                  label="Nova Carta de Correção"
                  @click="handleAddCartaCorrecao"
                  :disable="!podeAdicionarCCe"
                />
              </div>
            </div>

            <div v-if="loadingCartasCorrecao" class="row justify-center q-py-xl">
              <q-spinner color="primary" size="2em" />
            </div>

            <div v-else-if="cartasCorrecao.length === 0" class="text-center q-py-xl text-grey-7">
              <q-icon name="edit_note" size="3em" />
              <div class="q-mt-md">Nenhuma carta de correção emitida</div>
            </div>

            <div v-else>
              <!-- TODO: Implementar lista de cartas de correção -->
              <div class="text-body2">{{ cartasCorrecao.length }} carta(s) de correção</div>
            </div>
          </q-tab-panel>
        </q-tab-panels>
      </q-card>
    </div>

    <!-- Erro -->
    <q-card v-else flat bordered class="q-pa-xl text-center">
      <q-icon name="error" size="4em" color="negative" />
      <div class="text-h6 text-grey-7 q-mt-md">Nota fiscal não encontrada</div>
    </q-card>
  </q-page>
</template>
