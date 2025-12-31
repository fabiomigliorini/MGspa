<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import { useNotaFiscalStore } from '../stores/notaFiscalStore'
import {
  getStatusLabel,
  getStatusColor,
  getModeloLabel,
  getPagamentoIcon,
  getPagamentoColor,
  getFreteLabel,
} from '../constants/notaFiscal'
import {
  formatCnpjCpf,
  formatDateTime,
  formatDate,
  formatCurrency,
  formatDecimal,
  formatNumero,
  formatChave,
  formatProtocolo,
} from 'src/utils/formatters'
import NotaFiscalItemCard from '../components/NotaFiscalItemCard.vue'

const router = useRouter()
const route = useRoute()
const $q = useQuasar()
const notaFiscalStore = useNotaFiscalStore()

// Computed
const nota = computed(() => notaFiscalStore.currentNota)
const itens = computed(() => {
  const itensData = notaFiscalStore.itens
  // Ordena por ordem e depois por codnotafiscalprodutobarra
  return [...itensData].sort((a, b) => {
    // Primeiro compara por ordem
    if (a.ordem !== b.ordem) {
      return (a.ordem || 0) - (b.ordem || 0)
    }
    // Se ordem for igual, compara por codnotafiscalprodutobarra
    return (a.codnotafiscalprodutobarra || 0) - (b.codnotafiscalprodutobarra || 0)
  })
})
const pagamentos = computed(() => notaFiscalStore.pagamentos)
const duplicatas = computed(() => notaFiscalStore.duplicatas)
const referenciadas = computed(() => notaFiscalStore.referenciadas)
const cartasCorrecao = computed(() => {
  const cartas = notaFiscalStore.cartasCorrecao || []
  // Ordena por sequência decrescente (maior primeiro)
  return [...cartas].sort((a, b) => (b.sequencia || 0) - (a.sequencia || 0))
})

// Computa a maior sequência das cartas de correção
const maiorSequenciaCartaCorrecao = computed(() => {
  if (cartasCorrecao.value.length === 0) return 0
  return Math.max(...cartasCorrecao.value.map(c => c.sequencia || 0))
})

const loadingNota = computed(() => notaFiscalStore.loading.nota)

// Paginação client-side dos itens - dinâmica por breakpoint
const itensPorPagina = computed(() => {
  if ($q.screen.xs) return 1  // Desktop pequeno: 4 por linha = 3 linhas
  if ($q.screen.sm) return 3  // Tablet: 3 por linha = 4 linhas
  if ($q.screen.md) return 4   // Mobile: 1 por linha = 6 linhas
  // if ($q.screen.lg) return 6   // Mobile: 1 por linha = 6 linhas
  return 6                     // Desktop grande (lg/xl): 6 por linha = 2 linhas
})
const paginaAtualItens = ref(1)

const totalPaginasItens = computed(() => Math.ceil(itens.value.length / itensPorPagina.value))

const itensPaginados = computed(() => {
  const inicio = (paginaAtualItens.value - 1) * itensPorPagina.value
  const fim = inicio + itensPorPagina.value
  return itens.value.slice(inicio, fim)
})

const mudarPaginaItens = (novaPagina) => {
  paginaAtualItens.value = novaPagina
  // Scroll suave para o topo da seção de itens
  const itensSection = document.querySelector('#itens-section')
  if (itensSection) {
    itensSection.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
}

const notaBloqueada = computed(() => {
  if (!nota.value) return false
  return ['AUT', 'CAN', 'INU'].includes(nota.value.status)
})

// Methods
const loadData = async () => {
  try {
    // O backend já retorna todos os dados relacionados (itens, pagamentos, duplicatas, referenciadas)
    // em uma única requisição, então não precisamos fazer chamadas separadas
    // A verificação de cache é feita dentro do fetchNota
    await notaFiscalStore.fetchNota(route.params.codnotafiscal)
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao carregar nota fiscal',
      caption: error.response?.data?.message || error.message,
    })
  }
}

const handleDelete = () => {
  $q.dialog({
    title: 'Confirmar exclusão',
    message: `Deseja realmente excluir a nota fiscal ${getModeloLabel(nota.value.modelo)} nº ${nota.value.numero}?`,
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

const handleEditItem = (item) => {
  // TODO: Implementar edição de item
  console.log('Editar item:', item)
}

const handleDeleteItem = (item) => {
  $q.dialog({
    title: 'Confirmar exclusão',
    message: `Deseja realmente excluir o item "${item.produtoBarra?.descricao}"?`,
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
      await notaFiscalStore.deleteItem(route.params.codnotafiscal, item.codnotafiscalprodutobarra)
      $q.notify({
        type: 'positive',
        message: 'Item excluído com sucesso',
      })
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erro ao excluir item',
        caption: error.response?.data?.message || error.message,
      })
    }
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

      <!-- Header com Voltar -->
      <div class="row items-center q-mb-md">
        <div class="text-h5">
          <q-btn flat dense round icon="arrow_back" :to="{ name: 'notas' }" class="q-mr-sm" size="0.8em" />
          Nota
        </div>
        <q-space />

        <q-btn flat dense color="primary" icon="edit"
          :to="{ name: 'nota-fiscal-edit', params: { codnotafiscal: route.params.codnotafiscal } }"
          :disable="notaBloqueada" class="q-mr-sm">
          <q-tooltip>Editar</q-tooltip>
        </q-btn>
        <q-btn flat dense color="negative" icon="delete" @click="handleDelete" :disable="notaBloqueada">
          <q-tooltip>Excluir</q-tooltip>
        </q-btn>
      </div>

      <div class="row q-col-gutter-md">
        <div class="col-12 col-sm-6 col-md-3">
          <q-card flat bordered class="full-height">
            <q-card-section>

              <!-- FILILA / LOCAL -->
              <div class="text-caption text-grey-7 ">
                <q-icon name="business" size="xs" class="q-mr-xs" />
                Filial / Local de Estoque
              </div>
              <div class="text-subtitle1 text-weight-bold">
                {{ nota.filial?.filial || '-' }}
                <span class="text-grey-7">
                  /
                  {{ nota.estoqueLocal?.estoquelocal || '-' }}
                </span>
              </div>

              <!-- NATUREZA -->
              <div class="text-caption text-grey-7">
                <q-icon name="compare_arrows" size="xs" class="q-mr-xs" />
                Natureza de Operação
              </div>
              <div class="text-body1 text-weight-medium">
                {{ nota.codoperacao === 1 ? 'Entrada' : 'Saída' }} |
                {{ nota.naturezaOperacao?.naturezaoperacao || '-' }}
              </div>

              <!-- Emissao -->
              <div class="text-caption text-grey-7">Emissão</div>
              <div class="text-body1">{{ formatDate(nota.emissao) }}</div>

              <!-- saida  -->
              <div class="text-caption text-grey-7">Saída/Entrada</div>
              <div class="text-body1">{{ formatDateTime(nota.saida) }}</div>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-sm-6 col-md-3">
          <q-card flat bordered class="q-mb-md full-height">
            <q-card-section>
              <!-- <div class="text-subtitle1 text-weight-bold q-mb-md">TRANSPORTADOR / VOLUMES TRANSPORTADOS</div> -->
              <div class="col-12 col-md-6">
                <div class="text-caption text-grey-7">Frete</div>
                <div class="text-body1 ellipsis">{{ getFreteLabel(nota.frete) }}</div>
              </div>

              <div class="col-12 col-md-6" v-if="nota.transportador">
                <div class="text-caption text-grey-7">Transportador</div>
                <div class="text-body1 ellipsis">{{ nota.transportador.fantasia }}</div>
              </div>

              <div class="col-6 col-md-3" v-if="nota.placa">
                <div class="text-caption text-grey-7">Placa</div>
                <div class="text-body2 ellipsis">{{ nota.placa }}/{{ nota.estadoPlaca.sigla || nota.estadoPlaca }}</div>
              </div>

              <div class="col-6 col-md-2" v-if="nota.volumes">
                <div class="text-caption text-grey-7">Volumes</div>
                <div class="text-body2 ellipsis">
                  {{ nota.volumes }}
                  {{ nota.volumesespecie }}
                  {{ nota.volumesmarca }}
                  <template v-if="nota.volumesnumero">
                    N {{ nota.volumesnumero }}
                  </template>
                </div>
              </div>

              <div class="col-6 col-md-2" v-if="nota.pesobruto">
                <div class="text-caption text-grey-7">Peso Bruto</div>
                <div class="text-body2 ellipsis">{{ formatDecimal(nota.pesobruto, 3) }} kg</div>
              </div>

              <div class="col-6 col-md-2" v-if="nota.pesoliquido">
                <div class="text-caption text-grey-7">Peso Líquido</div>
                <div class="text-body2 ellipsis">{{ formatDecimal(nota.pesoliquido, 3) }} kg</div>
              </div>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-sm-12 col-md-6">
          <q-card flat bordered class="q-mb-md full-height">
            <q-card-section>
              <!-- Chave de Acesso -->
              <template v-if="ref.nfechave">
                <div class="text-caption text-grey-7 ">
                  <q-icon name="fingerprint" size="xs" class="q-mr-xs" />
                  Chave de Acesso
                </div>
                <div class="text-caption" style="font-family: monospace;">
                  {{ formatChave(nota.nfechave) }}
                </div>
              </template>

              <template v-if="nota.modelo">
                <div class="text-caption text-grey-7 ">
                  <q-icon name="fingerprint" size="xs" class="q-mr-xs" />
                  {{ getModeloLabel(nota.modelo) }}
                </div>
                <div class="text-caption" style="font-family: monospace;">
                  {{ formatNumero(nota.numero) }} - Série {{ nota.serie }}
                </div>
              </template>



              <!-- STATUS -->
              <div class="text-caption text-grey-7">
                Status da Nota
              </div>
              <div class="text-body1">
                <q-badge :color="getStatusColor(nota.status)" class="text-subtitle2">
                  {{ getStatusLabel(nota.status) }}
                </q-badge>
              </div>

              <!-- Autorizacao  -->
              <template v-if="nota.nfeautorizacao">
                <div class="text-caption text-grey-7">
                  Autorização
                </div>
                <div class="text-caption">
                  <span style="font-family: monospace;">
                    {{ formatProtocolo(nota.nfeautorizacao) }}
                  </span>
                  <span class="text-grey-7">

                    |
                    {{ formatDateTime(nota.nfedataautorizacao) }}
                  </span>
                </div>
              </template>

              <!-- Cancelamento  -->
              <template v-if="nota.nfecancelamento">
                <div class="text-caption text-grey-7">
                  Cancelamento
                </div>
                <div class="text-caption">
                  <span style="font-family: monospace;">
                    {{ formatProtocolo(nota.nfecancelamento) }}
                  </span>
                  <span class="text-grey-7">
                    |
                    {{ formatDateTime(nota.nfedatacancelamento) }}
                  </span>
                </div>
              </template>

              <!-- Inutilizacao  -->
              <template v-if="nota.nfeinutilizacao">
                <div class="text-caption text-grey-7">
                  Inutilização
                </div>
                <div class="text-caption">
                  <span style="font-family: monospace;">
                    {{ formatProtocolo(nota.nfeinutilizacao) }}
                  </span>
                  <span class="text-grey-7">
                    |
                    {{ formatDateTime(nota.nfedatainutilizacao) }}
                  </span>
                </div>
              </template>

            </q-card-section>

          </q-card>
        </div>

      </div>




      <!-- Destinatário/Remetente -->
      <!-- <div class="text-subtitle1 text-weight-bold q-mb-md">
        {{ nota.codoperacao === 1 ? 'DESTINATÁRIO' : 'REMETENTE' }}
      </div> -->
      <q-card flat bordered class="q-my-md full-height">
        <q-card-section>
          <div class="row q-col-gutter-sm">
            <!-- NOME -->
            <div class="col-12 col-sm-8 col-md-5">
              <div class="text-caption text-grey-7 ">Nome | Razão Social</div>
              <div class="text-body1 text-weight-bold text-primary ellipsis">
                {{ nota.pessoa?.fantasia || '-' }}
                <span class="text-grey-7">
                  |
                  {{ nota.pessoa?.pessoa || '-' }}
                </span>
              </div>
            </div>

            <!-- CNPJ -->
            <div class="col-12 col-sm-4 col-md-2">
              <div class="text-caption text-grey-7">
                {{ nota.pessoa?.fisica === 1 ? 'CPF' : 'CNPJ' }}
              </div>
              <div class="text-body1 text-weight-bold text-primary ellipsis">
                {{ formatCnpjCpf(nota.pessoa?.cnpj, nota.pessoa?.fisica) }}
              </div>
            </div>

            <!-- IE -->
            <div class="col-6 col-sm-8 col-md-2" v-if="nota.pessoa?.ie">
              <div class="text-caption text-grey-7">Inscrição Estadual</div>
              <div class="text-body2 ellipsis">{{ nota.pessoa.ie }}</div>
            </div>

            <!-- EMAIL -->
            <div class="col-12 col-sm-4 col-md-3" v-if="nota.pessoa?.email">
              <div class="text-caption text-grey-7">E-MAIL</div>
              <div class="text-body2 ellipsis">{{ nota.pessoa.email }}</div>
            </div>


            <!-- ENDEREÇO -->
            <div class="col-12 col-sm-8 col-md-3" v-if="nota.pessoa?.endereco">
              <div class="text-caption text-grey-7">Endereço Fiscal</div>
              <div class="text-body2 ellipsis">
                {{ nota.pessoa.endereco }},
                {{ nota.pessoa.numero }}
              </div>
            </div>

            <!-- BAIRRO -->
            <div class="col-12 col-sm-4 col-md-2" v-if="nota.pessoa?.bairro">
              <div class="text-caption text-grey-7">Bairro</div>
              <div class="text-body2 ellipsis">{{ nota.pessoa.bairro }}</div>
            </div>

            <!-- CIDADE -->
            <div class="col-12 col-sm-5 col-md-2" v-if="nota.pessoa?.cidade">
              <div class="text-caption text-grey-7">Cidade</div>
              <div class="text-body2 ellipsis">
                {{ nota.pessoa.cidade }} / {{ nota.pessoa.uf }}
              </div>
            </div>

            <!-- CEP -->
            <div class="col-12 col-sm-3 col-md-2" v-if="nota.pessoa?.cep">
              <div class="text-caption text-grey-7">CEP</div>
              <div class="text-body2 ellipsis">{{ nota.pessoa.cep }}</div>
            </div>

            <!-- TELEFONE -->
            <div class="col-6 col-sm-4 col-md-3" v-if="nota.pessoa?.telefone1">
              <div class="text-caption text-grey-7">Telefone</div>
              <div class="text-body2 ellipsis">{{ nota.pessoa.telefone1 }}</div>
            </div>

          </div>
        </q-card-section>
      </q-card>

      <!-- Cálculo do Imposto -->
      <q-card flat bordered class="q-my-md full-height">
        <q-card-section>
          <div class="row q-col-gutter-sm">

            <!-- ICMS -->
            <div class="col-6 col-sm-4 col-md-2 q-py-sm">
              <div class="text-caption text-grey-7">ICMS</div>
              <div class="text-body2">
                {{ formatCurrency(nota.baseicms) }} /
                {{ formatCurrency(nota.valoricms) }}
              </div>
            </div>

            <!-- ICMS ST -->
            <div class="col-6 col-sm-4 col-md-2 q-py-sm">
              <div class="text-caption text-grey-7">ICMS ST</div>
              <div class="text-body2">
                {{ formatCurrency(nota.baseicmsst) }} /
                {{ formatCurrency(nota.valoricmsst) }}
              </div>
            </div>

            <!-- IPI -->
            <div class="col-6 col-sm-4 col-md-2 q-py-sm">
              <div class="text-caption text-grey-7">IPI</div>
              <div class="text-body2">
                {{ formatCurrency(nota.valoripi) }}
              </div>
            </div>

            <!-- PIS -->
            <div class="col-6 col-sm-4 col-md-2 q-py-sm">
              <div class="text-caption text-grey-7">PIS</div>
              <div class="text-body2">
                {{ formatCurrency(nota.valorpis) }}
              </div>
            </div>

            <!-- COFINS -->
            <div class="col-6 col-sm-4 col-md-2 q-py-sm">
              <div class="text-caption text-grey-7">COFINS</div>
              <div class="text-body2">
                {{ formatCurrency(nota.valorcofins) }}
              </div>
            </div>

            <!-- IBPT -->
            <div class="col-6 col-sm-4 col-md-2 q-py-sm">
              <div class="text-caption text-grey-7">IBPT</div>
              <div class="text-body2">
                {{ formatCurrency(nota.valoribpt) }}
              </div>
            </div>

            <!-- Produtos -->
            <div class="col-6 col-sm-4 col-md-2 q-py-sm">
              <div class="text-caption text-grey-7">Produtos</div>
              <div class="text-body2 text-weight-bold"> {{ formatCurrency(nota.valorprodutos) }}</div>
            </div>

            <!-- Desconto -->
            <div class="col-6 col-sm-4 col-md-2 q-py-sm">
              <div class="text-caption text-grey-7">Desconto</div>
              <div class="text-body2"> {{ formatCurrency(nota.valordesconto) }}</div>
            </div>

            <!-- Frete -->
            <div class="col-6 col-sm-4 col-md-2 q-py-sm">
              <div class="text-caption text-grey-7">Frete</div>
              <div class="text-body2"> {{ formatCurrency(nota.valorfrete) }}</div>
            </div>

            <!-- Seguro -->
            <div class="col-6 col-sm-4 col-md-2 q-py-sm">
              <div class="text-caption text-grey-7">Seguro</div>
              <div class="text-body2"> {{ formatCurrency(nota.valorseguro) }}</div>
            </div>

            <!-- Outras Despesas -->
            <div class="col-6 col-sm-4 col-md-2 q-py-sm">
              <div class="text-caption text-grey-7">Outras Despesas</div>
              <div class="text-body2"> {{ formatCurrency(nota.valoroutros) }}</div>
            </div>


            <div class="col-6 col-sm-4 col-md-2 q-py-sm">
              <div class="text-caption text-grey-7">Total</div>
              <div class="text-h6 text-weight-bold text-primary">
                {{ formatCurrency(nota.valortotal) }}
              </div>
            </div>
          </div>
        </q-card-section>
      </q-card>


      <!-- Produtos / Serviços -->



      <div class="row items-center justify-between q-mb-md">
        <div class="q-mb-sm q-mt-lg">
          <span class="text-h5 ">
            <q-icon name="inventory_2" size="1.5em" class="q-mr-sm" />
            Itens
          </span>
          <q-badge color="primary" class="q-ml-sm">{{ itens.length }}</q-badge>
        </div>

        <!-- Paginação no topo (mobile/desktop) -->
        <div class="flex justify-end q-mb-sm" v-if="totalPaginasItens > 1">
          <q-pagination size="md" v-model="paginaAtualItens" :max="totalPaginasItens" :max-pages="5" direction-links
            boundary-links @update:model-value="mudarPaginaItens" />
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="itens.length === 0" class="text-left q-mt-none q-mb-lg text-grey-7">
        Nenhum item adicionado
      </div>


      <!-- Cards dos Itens -->
      <div v-else class="row q-col-gutter-md">
        <NotaFiscalItemCard v-for="item in itensPaginados" :key="item.codnotafiscalprodutobarra" :item="item"
          :nota-bloqueada="notaBloqueada" @edit="handleEditItem" @delete="handleDeleteItem" />
      </div>


      <!-- PAGINACAO RODAPE -->
      <div class="row items-center justify-between q-mb-md">

        <!-- Info da paginação -->
        <div v-if="itens.length > itensPorPagina" class="text-center text-caption text-grey-7 q-mt-sm">
          Mostrando {{ (paginaAtualItens - 1) * itensPorPagina + 1 }}
          -
          {{ Math.min(paginaAtualItens * itensPorPagina, itens.length) }}
          de {{ itens.length }} itens
        </div>

        <!-- Paginação no rodapé -->
        <div v-if="totalPaginasItens > 1" class="flex justify-center q-mt-md">
          <q-pagination size="md" v-model="paginaAtualItens" :max="totalPaginasItens" :max-pages="5" direction-links
            boundary-links @update:model-value="mudarPaginaItens" />
        </div>

      </div>

      <!-- Formas de Pagamento -->
      <div class="q-mb-md  q-mt-lg">
        <span class="text-h5">
          <q-icon name="payments" size="1.5em" class="q-mr-sm" /> Formas de Pagamento
        </span>
        <q-badge color="primary" class="q-ml-sm">{{ pagamentos.length }}</q-badge>
      </div>

      <div v-if="pagamentos.length === 0" class="text-left q-mt-none q-mb-lg text-grey-7">
        Nenhuma forma de pagamento adicionada
      </div>

      <div v-else class="row q-col-gutter-md q-mb-md">
        <div v-for="pag in pagamentos" :key="pag.codnotafiscalpagamento" class="col-xs-12 col-sm-4 col-md-3 col-lg-2">

          <q-card flat bordered class="full-height">
            <q-card-section>
              <div class="row items-center justify-between q-mb-md">
                <div class="row items-center" style="min-width: 0; flex: 1;">
                  <q-avatar :color="getPagamentoColor(pag.tipo)" text-color="white" size="md" class="q-mr-sm"
                    style="flex-shrink: 0;">
                    <q-icon :name="getPagamentoIcon(pag.tipo)" />
                  </q-avatar>
                  <div style="min-width: 0; flex: 1;">
                    <div class="text-subtitle2 text-weight-bold ellipsis">
                      {{ pag.tipodescricao || '-' }}
                    </div>
                    <div v-if="pag.bandeiradescricao" class="text-caption text-grey-7 ellipsis">
                      {{ pag.bandeiradescricao }}
                    </div>
                  </div>
                </div>
                <q-badge v-if="pag.avista" color="positive" outline style="flex-shrink: 0;">
                  À vista
                </q-badge>
              </div>

              <div class="text-h5 text-primary text-weight-bold q-mb-sm">
                {{ formatCurrency(pag.valorpagamento) }}
              </div>

              <div v-if="pag.fantasia" class="row items-center q-mb-xs">
                <q-icon name="business" size="xs" class="q-mr-xs text-grey-6" />
                <div class="text-caption text-grey-7 ellipsis">
                  {{ pag.fantasia }}
                </div>
              </div>

              <div v-if="pag.autorizacao" class="row items-center q-mb-xs">
                <q-icon name="verified" size="xs" class="q-mr-xs text-grey-6" />
                <div class="text-caption text-grey-7">
                  Aut: {{ pag.autorizacao }}
                </div>
              </div>

              <div v-if="pag.troco" class="row items-center">
                <q-icon name="change_circle" size="xs" class="q-mr-xs text-grey-6" />
                <div class="text-caption text-grey-7">
                  Troco: {{ formatCurrency(pag.troco) }}
                </div>
              </div>
            </q-card-section>

            <q-separator v-if="!notaBloqueada" />

            <q-card-actions v-if="!notaBloqueada" align="right">
              <q-btn flat dense icon="edit" color="primary" size="sm">
                <q-tooltip>Editar</q-tooltip>
              </q-btn>
              <q-btn flat dense icon="delete" color="negative" size="sm">
                <q-tooltip>Excluir</q-tooltip>
              </q-btn>
            </q-card-actions>
          </q-card>
        </div>
      </div>

      <!-- Duplicatas -->
      <div class="q-mb-md q-mt-lg">
        <span class="text-h5">
          <q-icon name="receipt_long" size="1.5em" class="q-mr-sm" /> Duplicatas
        </span>
        <q-badge color="primary" class="q-ml-sm">{{ duplicatas.length }}</q-badge>
      </div>

      <div v-if="duplicatas.length === 0" class="text-left q-mt-none q-mb-lg text-grey-7">
        Nenhuma duplicata adicionada
      </div>

      <div v-else class="row q-col-gutter-md q-mb-md">
        <div v-for="dup in duplicatas" :key="dup.codnotafiscalduplicatas" class="col-xs-12 col-sm-4 col-md-3 col-lg-2">
          <q-card flat bordered class="full-height">
            <q-card-section>
              <div class="row items-center q-mb-md">
                <q-avatar color="indigo" text-color="white" size="md" class="q-mr-sm" style="flex-shrink: 0;">
                  <q-icon name="receipt_long" />
                </q-avatar>
                <div style="min-width: 0; flex: 1;">
                  <div class="text-subtitle2 text-weight-bold ellipsis">
                    {{ dup.fatura || '-' }}
                  </div>
                </div>
              </div>

              <div class="text-caption text-grey-7">Vencimento</div>
              <div class="text-body2 q-mb-sm">{{ formatDate(dup.vencimento) }}</div>

              <div class="text-caption text-grey-7">Valor</div>
              <div class="text-h5 text-primary text-weight-bold">
                {{ formatCurrency(dup.valor) }}
              </div>
            </q-card-section>

            <q-separator v-if="!notaBloqueada" />

            <q-card-actions v-if="!notaBloqueada" align="right">
              <q-btn flat dense icon="edit" color="primary" size="sm">
                <q-tooltip>Editar</q-tooltip>
              </q-btn>
              <q-btn flat dense icon="delete" color="negative" size="sm">
                <q-tooltip>Excluir</q-tooltip>
              </q-btn>
            </q-card-actions>
          </q-card>
        </div>
      </div>

      <!-- Notas Referenciadas -->
      <div class="q-mb-md q-mt-lg">
        <span class="text-h5">
          <q-icon name="link" size="1.5em" class="q-mr-sm" /> Notas Fiscais Referenciadas
        </span>
        <q-badge color="primary" class="q-ml-sm">{{ referenciadas.length }}</q-badge>
      </div>

      <div v-if="referenciadas.length === 0" class="text-left q-mt-none q-mb-lg text-grey-7">
        Nenhuma nota fiscal referenciada
      </div>

      <div v-else class="row q-col-gutter-md q-mb-md">
        <div v-for="ref in referenciadas" :key="ref.codnotafiscalreferenciada"
          class="col-xs-12 col-sm-6 col-md-6 col-lg-4">
          <q-card flat bordered class="full-height">
            <q-card-section>
              <div class="row items-center q-mb-sm">
                <q-icon name="link" size="sm" color="primary" class="q-mr-sm" />
                <div class="text-subtitle2 text-weight-bold">
                  Nota Referenciada
                </div>
              </div>

              <div class="text-caption text-grey-7">Chave de Acesso</div>
              <div class="text-caption" style="font-family: monospace;">
                {{ formatChave(ref.nfechave) }}
              </div>
            </q-card-section>

            <q-separator v-if="!notaBloqueada" />

            <q-card-actions v-if="!notaBloqueada" align="right">
              <q-btn flat dense icon="edit" color="primary" size="sm">
                <q-tooltip>Editar</q-tooltip>
              </q-btn>
              <q-btn flat dense icon="delete" color="negative" size="sm">
                <q-tooltip>Excluir</q-tooltip>
              </q-btn>
            </q-card-actions>
          </q-card>
        </div>
      </div>


      <!-- Cartas de Correção -->
      <div class="q-mb-md q-mt-lg">
        <span class="text-h5">
          <q-icon name="edit_note" size="1.5em" class="q-mr-sm" />
          Cartas de Correção
        </span>
        <q-badge color="primary" class="q-ml-sm">{{ cartasCorrecao.length }}</q-badge>
      </div>

      <div v-if="cartasCorrecao.length === 0" class="text-left q-mt-none q-mb-lg text-grey-7">
        Nenhuma carta de correção emitida
      </div>

      <div v-else class="row q-col-gutter-md q-mb-md">
        <div v-for="carta in cartasCorrecao" :key="carta.codnotafiscalcartacorrecao"
          class="col-xs-12 col-sm-6 col-md-6 col-lg-4">
          <q-card flat bordered class="full-height">
            <q-card-section>
              <div class="row items-center justify-between q-mb-sm">
                <div class="row items-center">
                  <q-icon name="edit_note" size="sm" color="primary" class="q-mr-sm" />
                  <div class="text-subtitle2 text-weight-bold">
                    Correção Seq {{ carta.sequencia }} Lote {{ carta.lote }}
                  </div>
                </div>
                <q-badge v-if="carta.status" :color="carta.status === 'AUT' ? 'positive' : 'grey'">
                  {{ carta.status === 'AUT' ? 'Autorizada' : carta.status }}
                </q-badge>
              </div>



              <div class="row q-col-gutter-sm">

                <div class="col-4">
                  <div class="text-caption text-grey-7">Data</div>
                  <div class="text-caption ellipsis">
                    {{ formatDateTime(carta.data) }}
                  </div>
                </div>

                <div class="col">
                  <div v-if="carta.protocolo" class="q-mb-sm">
                    <div class="text-caption text-grey-7">Protocolo</div>
                    <div class="text-caption ellipsis">
                      <span style="font-family: monospace;">
                        {{ formatProtocolo(carta.protocolo) }}
                      </span>
                      <span class="text-grey-7">
                        |
                        {{ formatDateTime(carta.protocolodata) }}
                      </span>
                    </div>
                  </div>

                </div>
                <div class="col-12 col-md-8">
                  <div class="text-caption text-grey-7">Correção</div>
                  <div class="text-caption "
                    :class="carta.sequencia !== maiorSequenciaCartaCorrecao ? 'text-grey-8 text-strike' : ''"
                    style="white-space: pre-wrap">
                    {{ carta.texto || '-' }}
                  </div>
                </div>

              </div>
            </q-card-section>

            <q-separator v-if="!notaBloqueada" />

            <q-card-actions v-if="!notaBloqueada" align="right">
              <q-btn flat dense icon="edit" color="primary" size="sm">
                <q-tooltip>Editar</q-tooltip>
              </q-btn>
              <q-btn flat dense icon="delete" color="negative" size="sm">
                <q-tooltip>Excluir</q-tooltip>
              </q-btn>
            </q-card-actions>
          </q-card>
        </div>
      </div>


      <!-- Informações Adicionais -->
      <q-card flat class="q-mb-md" v-if="nota.observacoes || nota.informacoesfisco || nota.informacoescontribuinte">
        <q-card-section>
          <div class="text-subtitle1 text-weight-bold q-mb-md">INFORMAÇÕES ADICIONAIS</div>

          <div v-if="nota.informacoesfisco" class="q-mb-md">
            <div class="text-caption text-grey-7 text-weight-bold">INFORMAÇÕES COMPLEMENTARES DE INTERESSE DO FISCO
            </div>
            <div class="text-body2" style="white-space: pre-wrap">{{ nota.informacoesfisco }}</div>
          </div>

          <div v-if="nota.informacoescontribuinte" class="q-mb-md">
            <div class="text-caption text-grey-7 text-weight-bold">INFORMAÇÕES ADICIONAIS DE INTERESSE DO CONTRIBUINTE
            </div>
            <div class="text-body2" style="white-space: pre-wrap">{{ nota.informacoescontribuinte }}</div>
          </div>

          <div v-if="nota.observacoes">
            <div class="text-caption text-grey-7 text-weight-bold">OBSERVAÇÕES</div>
            <div class="text-body2" style="white-space: pre-wrap">{{ nota.observacoes }}</div>
          </div>
        </q-card-section>
      </q-card>
    </div>

    <!-- Erro -->
    <q-card v-else flat bordered class="q-pa-xl text-center">
      <q-icon name="error" size="4em" color="negative" />
      <div class="text-h6 text-grey-7 q-mt-md">Nota fiscal não encontrada</div>
    </q-card>
  </q-page>
</template>
