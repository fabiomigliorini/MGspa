<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import { useNotaFiscalStore } from '../stores/notaFiscalStore'
import { getStatusLabel, getStatusColor, getModeloLabel } from '../constants/notaFiscal'
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
    await notaFiscalStore.fetchNota(route.params.codnotafiscal)
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

const formatDateTime = (value) => {
  if (!value) return '-'
  const dateObj = new Date(value)
  return dateObj.toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  })
}

const formatDate = (value) => {
  if (!value) return '-'
  const dateObj = new Date(value)
  return dateObj.toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

const formatCurrency = (value) => {
  if (!value) return '0,00'
  return parseFloat(value).toLocaleString('pt-BR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
}

const formatDecimal = (value, digitos) => {
  if (!value) return null
  return parseFloat(value).toLocaleString('pt-BR', {
    minimumFractionDigits: digitos,
    maximumFractionDigits: digitos,
  })
}

const formatNumero = (numero) => {
  if (!numero) return '00000000'
  return String(numero).padStart(8, '0')
}

const formatChave = (chave) => {
  if (!chave) return '-'
  // Formata a chave em grupos de 4 dígitos
  return chave.match(/.{1,4}/g)?.join(' ') || chave
}

const getFreteLabel = (frete) => {
  const labels = {
    0: 'Emitente (Terceirizado)',
    1: 'Destinatário (Terceirizado)',
    2: 'Terceiro (Nem Emitente, Nem Destinatário)',
    3: 'Emitente (Próprio)',
    4: 'Destinatario (Próprio)',
    9: 'Sem Frete'
  }
  return labels[frete] || '-'
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
        <q-btn flat dense round icon="arrow_back" @click="handleBack" class="q-mr-sm" />
        <div class="text-h5">
          {{ getModeloLabel(nota.modelo) }}
          {{ formatNumero(nota.numero) }} - Série {{ nota.serie }}
        </div>
        <q-space />
        <q-btn flat icon="edit" label="Editar" @click="handleEdit" :disable="notaBloqueada" class="q-mr-sm" />
        <q-btn flat color="negative" icon="delete" label="Excluir" @click="handleDelete" :disable="notaBloqueada" />
      </div>

      <div class="row q-col-gutter-md">
        <div class="col-12 col-sm-6 col-md-3">
          <q-card class="full-height">
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
          <q-card class="q-mb-md full-height">
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
          <q-card class="q-mb-md full-height">
            <q-card-section>
              <!-- Chave de Acesso -->
              <div class="text-caption text-grey-7 ">
                <q-icon name="fingerprint" size="xs" class="q-mr-xs" />
                Chave de Acesso
              </div>
              <div class="text-body1 ellipsis">
                {{ formatChave(nota.nfechave) }}
              </div>

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
                <div class="text-body1">
                  {{ nota.nfeautorizacao }}
                  <span class="text-grey-7 text-caption">

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
                <div class="text-body1">
                  {{ nota.nfecancelamento }}
                  <span class="text-grey-7 text-caption">
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
                <div class="text-body1">
                  {{ nota.nfeinutilizacao }}
                  <span class="text-grey-7 text-caption">
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
      <q-card class="q-my-md full-height">
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
                {{ nota.pessoa?.cnpj || nota.pessoa?.cpf || '-' }}
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
      <q-card class="q-my-md full-height">
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
              <div class="text-body2 text-weight-bold">R$ {{ formatCurrency(nota.valorprodutos) }}</div>
            </div>

            <!-- Desconto -->
            <div class="col-6 col-sm-4 col-md-2 q-py-sm">
              <div class="text-caption text-grey-7">Desconto</div>
              <div class="text-body2">R$ {{ formatCurrency(nota.valordesconto) }}</div>
            </div>

            <!-- Frete -->
            <div class="col-6 col-sm-4 col-md-2 q-py-sm">
              <div class="text-caption text-grey-7">Frete</div>
              <div class="text-body2">R$ {{ formatCurrency(nota.valorfrete) }}</div>
            </div>

            <!-- Seguro -->
            <div class="col-6 col-sm-4 col-md-2 q-py-sm">
              <div class="text-caption text-grey-7">Seguro</div>
              <div class="text-body2">R$ {{ formatCurrency(nota.valorseguro) }}</div>
            </div>

            <!-- Outras Despesas -->
            <div class="col-6 col-sm-4 col-md-2 q-py-sm">
              <div class="text-caption text-grey-7">Outras Despesas</div>
              <div class="text-body2">R$ {{ formatCurrency(nota.valoroutros) }}</div>
            </div>


            <div class="col-6 col-sm-4 col-md-2 q-py-sm">
              <div class="text-caption text-grey-7">Total</div>
              <div class="text-body1 text-weight-bold text-primary">
                R$ {{ formatCurrency(nota.valortotal) }}
              </div>
            </div>
          </div>
        </q-card-section>
      </q-card>


      <!-- Produtos / Serviços -->
      <div class="row items-center justify-between q-mb-md">
        <div class="q-mb-md">
          <span class="text-h5 ">
            Itens
          </span>
          <q-badge color="primary" class="q-ml-sm">{{ itens.length }}</q-badge>
        </div>

        <!-- Paginação no topo (mobile/desktop) -->
        <div class="flex justify-end q-mb-md" v-if="totalPaginasItens > 1">
          <q-pagination size="md" v-model="paginaAtualItens" :max="totalPaginasItens" :max-pages="5" direction-links
            boundary-links @update:model-value="mudarPaginaItens" />
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="itens.length === 0" class="text-center q-py-md text-grey-7">
        <q-icon name="inventory_2" size="2em" />
        <div class="q-mt-sm">Nenhum item adicionado</div>
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
      <q-card flat class="q-mb-md" v-if="pagamentos.length > 0">
        <q-card-section>
          <div class="text-subtitle1 text-weight-bold q-mb-md">
            FORMAS DE PAGAMENTO
            <q-badge color="primary" class="q-ml-sm">{{ pagamentos.length }}</q-badge>
          </div>

          <q-markup-table flat bordered dense>
            <thead>
              <tr>
                <th class="text-left">Forma de Pagamento</th>
                <th class="text-right">Valor</th>
                <th class="text-center" style="width: 80px">Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="pag in pagamentos" :key="pag.codnotafiscalpagamento">
                <td>{{ pag.formapagamento || '-' }}</td>
                <td class="text-right text-weight-bold">R$ {{ formatCurrency(pag.valorpagamento) }}</td>
                <td class="text-center">
                  <q-btn flat round dense icon="edit" size="sm" color="primary" :disable="notaBloqueada">
                    <q-tooltip>Editar</q-tooltip>
                  </q-btn>
                  <q-btn flat round dense icon="delete" size="sm" color="negative" :disable="notaBloqueada">
                    <q-tooltip>Excluir</q-tooltip>
                  </q-btn>
                </td>
              </tr>
            </tbody>
          </q-markup-table>
        </q-card-section>
      </q-card>

      <!-- Duplicatas -->
      <q-card flat class="q-mb-md" v-if="duplicatas.length > 0">
        <q-card-section>
          <div class="text-subtitle1 text-weight-bold q-mb-md">
            DUPLICATAS
            <q-badge color="primary" class="q-ml-sm">{{ duplicatas.length }}</q-badge>
          </div>

          <q-markup-table flat bordered dense>
            <thead>
              <tr>
                <th class="text-left">Parcela</th>
                <th class="text-left">Vencimento</th>
                <th class="text-right">Valor</th>
                <th class="text-center" style="width: 80px">Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="dup in duplicatas" :key="dup.codnotafiscalduplicatas">
                <td>{{ dup.numero || '-' }}</td>
                <td>{{ formatDate(dup.vencimento) }}</td>
                <td class="text-right text-weight-bold">R$ {{ formatCurrency(dup.valor) }}</td>
                <td class="text-center">
                  <q-btn flat round dense icon="edit" size="sm" color="primary" :disable="notaBloqueada">
                    <q-tooltip>Editar</q-tooltip>
                  </q-btn>
                  <q-btn flat round dense icon="delete" size="sm" color="negative" :disable="notaBloqueada">
                    <q-tooltip>Excluir</q-tooltip>
                  </q-btn>
                </td>
              </tr>
            </tbody>
          </q-markup-table>
        </q-card-section>
      </q-card>

      <!-- Notas Referenciadas -->
      <q-card flat class="q-mb-md" v-if="referenciadas.length > 0">
        <q-card-section>
          <div class="text-subtitle1 text-weight-bold q-mb-md">
            NOTAS FISCAIS REFERENCIADAS
            <q-badge color="primary" class="q-ml-sm">{{ referenciadas.length }}</q-badge>
          </div>

          <q-markup-table flat bordered dense>
            <thead>
              <tr>
                <th class="text-left">Chave de Acesso</th>
                <th class="text-center" style="width: 80px">Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="ref in referenciadas" :key="ref.codnotafiscalreferenciada">
                <td style="word-break: break-all;">{{ formatChave(ref.chave || ref.nfe) }}</td>
                <td class="text-center">
                  <q-btn flat round dense icon="edit" size="sm" color="primary" :disable="notaBloqueada">
                    <q-tooltip>Editar</q-tooltip>
                  </q-btn>
                  <q-btn flat round dense icon="delete" size="sm" color="negative" :disable="notaBloqueada">
                    <q-tooltip>Excluir</q-tooltip>
                  </q-btn>
                </td>
              </tr>
            </tbody>
          </q-markup-table>
        </q-card-section>
      </q-card>

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
    <q-card v-else flat class="q-pa-xl text-center">
      <q-icon name="error" size="4em" color="negative" />
      <div class="text-h6 text-grey-7 q-mt-md">Nota fiscal não encontrada</div>
    </q-card>
  </q-page>
</template>
