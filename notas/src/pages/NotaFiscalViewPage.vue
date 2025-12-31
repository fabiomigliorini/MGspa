<script setup>
import { computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import { useNotaFiscalStore } from '../stores/notaFiscalStore'
import { getStatusLabel, getStatusColor, getModeloLabel } from '../constants/notaFiscal'

const router = useRouter()
const route = useRoute()
const $q = useQuasar()
const notaFiscalStore = useNotaFiscalStore()

// Computed
const nota = computed(() => notaFiscalStore.currentNota)
const itens = computed(() => notaFiscalStore.currentNota?.itens)
const pagamentos = computed(() => notaFiscalStore.currentNota?.pagamentos)
const duplicatas = computed(() => notaFiscalStore.currentNota?.duplicatas)
const referenciadas = computed(() => notaFiscalStore.currentNota?.notasReferenciadas)
// const cartas = computed(() => notaFiscalStore.currentNota?.cartasCorrecao)

const loadingNota = computed(() => notaFiscalStore.loading.nota)

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

              <q-separator class="q-my-xs" />

              <!-- NATUREZA -->
              <div class="text-caption text-grey-7">
                <q-icon name="compare_arrows" size="xs" class="q-mr-xs" />
                Natureza de Operação
              </div>
              <div class="text-body1 text-weight-medium">
                {{ nota.codoperacao === 1 ? 'Entrada' : 'Saída' }} |
                {{ nota.naturezaOperacao?.naturezaoperacao || '-' }}
              </div>

              <q-separator class="q-my-xs" />

              <!-- Emissao -->
              <div class="text-caption text-grey-7">Emissão</div>
              <div class="text-body1">{{ formatDate(nota.emissao) }}</div>

              <q-separator class="q-my-xs" />

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
              <q-separator class="q-my-xs" />

              <div class="col-12 col-md-6" v-if="nota.transportador">
                <div class="text-caption text-grey-7">Transportador</div>
                <div class="text-body1 ellipsis">{{ nota.transportador.fantasia }}</div>
              </div>
              <q-separator class="q-my-xs" />
              <div class="col-6 col-md-3" v-if="nota.placa">
                <div class="text-caption text-grey-7">Placa</div>
                <div class="text-body2 ellipsis">{{ nota.placa }}/{{ nota.estadoPlaca.sigla || nota.estadoPlaca }}</div>
              </div>
              <q-separator class="q-my-xs" />
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
              <q-separator class="q-my-xs" />
              <div class="col-6 col-md-2" v-if="nota.pesobruto">
                <div class="text-caption text-grey-7">Peso Bruto</div>
                <div class="text-body2 ellipsis">{{ formatDecimal(nota.pesobruto, 3) }} kg</div>
              </div>
              <q-separator class="q-my-xs" />
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
              <q-separator class="q-my-xs" />

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
                <q-separator class="q-my-xs" />
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
                <q-separator class="q-my-xs" />
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
                <q-separator class="q-my-xs" />
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
              <q-separator class="q-my-xs" />
            </div>

            <!-- CNPJ -->
            <div class="col-12 col-sm-4 col-md-2">
              <div class="text-caption text-grey-7">
                {{ nota.pessoa?.fisica === 1 ? 'CPF' : 'CNPJ' }}
              </div>
              <div class="text-body1 text-weight-bold text-primary ellipsis">
                {{ nota.pessoa?.cnpj || nota.pessoa?.cpf || '-' }}
              </div>
              <q-separator class="q-my-xs" />
            </div>

            <!-- IE -->
            <div class="col-6 col-sm-8 col-md-2" v-if="nota.pessoa?.ie">
              <div class="text-caption text-grey-7">Inscrição Estadual</div>
              <div class="text-body2 ellipsis">{{ nota.pessoa.ie }}</div>
              <q-separator class="q-my-xs" />
            </div>

            <!-- EMAIL -->
            <div class="col-12 col-sm-4 col-md-3" v-if="nota.pessoa?.email">
              <div class="text-caption text-grey-7">E-MAIL</div>
              <div class="text-body2 ellipsis">{{ nota.pessoa.email }}</div>
              <q-separator class="q-my-xs" />
            </div>


            <!-- ENDEREÇO -->
            <div class="col-12 col-sm-8 col-md-3" v-if="nota.pessoa?.endereco">
              <div class="text-caption text-grey-7">Endereço Fiscal</div>
              <div class="text-body2 ellipsis">
                {{ nota.pessoa.endereco }},
                {{ nota.pessoa.numero }}
              </div>
              <q-separator class="q-my-xs" />
            </div>

            <!-- BAIRRO -->
            <div class="col-12 col-sm-4 col-md-2" v-if="nota.pessoa?.bairro">
              <div class="text-caption text-grey-7">Bairro</div>
              <div class="text-body2 ellipsis">{{ nota.pessoa.bairro }}</div>
              <q-separator class="q-my-xs" />
            </div>

            <!-- CIDADE -->
            <div class="col-12 col-sm-5 col-md-2" v-if="nota.pessoa?.cidade">
              <div class="text-caption text-grey-7">Cidade</div>
              <div class="text-body2 ellipsis">
                {{ nota.pessoa.cidade }} / {{ nota.pessoa.uf }}
              </div>
              <q-separator class="q-my-xs" />
            </div>

            <!-- CEP -->
            <div class="col-12 col-sm-3 col-md-2" v-if="nota.pessoa?.cep">
              <div class="text-caption text-grey-7">CEP</div>
              <div class="text-body2 ellipsis">{{ nota.pessoa.cep }}</div>
              <q-separator class="q-my-xs" />
            </div>

            <!-- TELEFONE -->
            <div class="col-6 col-sm-4 col-md-3" v-if="nota.pessoa?.telefone1">
              <div class="text-caption text-grey-7">Telefone</div>
              <div class="text-body2 ellipsis">{{ nota.pessoa.telefone1 }}</div>
              <q-separator class="q-my-xs" />
            </div>

          </div>
        </q-card-section>
      </q-card>


      <!-- Transportador -->


      <q-separator class="q-my-md" />

      <!-- Cálculo do Imposto -->
      <q-card flat class="q-mb-md">
        <q-card-section>
          <div class="text-subtitle1 text-weight-bold q-mb-md">CÁLCULO DO IMPOSTO</div>
          <div class="row q-col-gutter-sm">
            <div class="col-6 col-md-3">
              <div class="text-caption text-grey-7">BASE CÁLC. ICMS</div>
              <div class="text-body2">R$ {{ formatCurrency(nota.baseicms) }}</div>
            </div>
            <div class="col-6 col-md-3">
              <div class="text-caption text-grey-7">VALOR ICMS</div>
              <div class="text-body2">R$ {{ formatCurrency(nota.valoricms) }}</div>
            </div>
            <div class="col-6 col-md-3">
              <div class="text-caption text-grey-7">BASE CÁLC. ICMS ST</div>
              <div class="text-body2">R$ {{ formatCurrency(nota.baseicmsst) }}</div>
            </div>
            <div class="col-6 col-md-3">
              <div class="text-caption text-grey-7">VALOR ICMS ST</div>
              <div class="text-body2">R$ {{ formatCurrency(nota.valoricmsst) }}</div>
            </div>
            <div class="col-6 col-md-3">
              <div class="text-caption text-grey-7">VALOR IPI</div>
              <div class="text-body2">R$ {{ formatCurrency(nota.valoripi) }}</div>
            </div>
            <div class="col-6 col-md-3">
              <div class="text-caption text-grey-7">VALOR PIS</div>
              <div class="text-body2">R$ {{ formatCurrency(nota.valorpis) }}</div>
            </div>
            <div class="col-6 col-md-3">
              <div class="text-caption text-grey-7">VALOR COFINS</div>
              <div class="text-body2">R$ {{ formatCurrency(nota.valorcofins) }}</div>
            </div>
            <div class="col-6 col-md-3">
              <div class="text-caption text-grey-7">VALOR APROX. TRIBUTOS</div>
              <div class="text-body2">R$ {{ formatCurrency(nota.valoribpt) }}</div>
            </div>
          </div>
        </q-card-section>
      </q-card>

      <!-- Valores Totais -->
      <q-card flat class="q-mb-md">
        <q-card-section>
          <div class="row q-col-gutter-sm">
            <div class="col-6 col-md-3">
              <div class="text-caption text-grey-7">VALOR PRODUTOS</div>
              <div class="text-body1 text-weight-bold">R$ {{ formatCurrency(nota.valorprodutos) }}</div>
            </div>
            <div class="col-6 col-md-3">
              <div class="text-caption text-grey-7">DESCONTO</div>
              <div class="text-body2">R$ {{ formatCurrency(nota.valordesconto) }}</div>
            </div>
            <div class="col-6 col-md-2">
              <div class="text-caption text-grey-7">FRETE</div>
              <div class="text-body2">R$ {{ formatCurrency(nota.valorfrete) }}</div>
            </div>
            <div class="col-6 col-md-2">
              <div class="text-caption text-grey-7">SEGURO</div>
              <div class="text-body2">R$ {{ formatCurrency(nota.valorseguro) }}</div>
            </div>
            <div class="col-12 col-md-2">
              <div class="text-caption text-grey-7">OUTRAS DESPESAS</div>
              <div class="text-body2">R$ {{ formatCurrency(nota.valoroutros) }}</div>
            </div>
            <div class="col-12">
              <q-separator class="q-my-sm" />
            </div>
            <div class="col-12">
              <div class="text-caption text-grey-7">VALOR TOTAL DA NOTA</div>
              <div class="text-h5 text-weight-bold text-primary">
                R$ {{ formatCurrency(nota.valortotal) }}
              </div>
            </div>
          </div>
        </q-card-section>
      </q-card>

      <q-separator class="q-my-md" />

      <!-- Produtos / Serviços -->
      <q-card flat class="q-mb-md">
        <q-card-section>
          <div class="text-subtitle1 text-weight-bold q-mb-md">
            DADOS DOS PRODUTOS / SERVIÇOS
            <q-badge color="primary" class="q-ml-sm">{{ itens.length }}</q-badge>
          </div>

          <div v-if="itens.length === 0" class="text-center q-py-md text-grey-7">
            <q-icon name="inventory_2" size="2em" />
            <div class="q-mt-sm">Nenhum item adicionado</div>
          </div>

          <div v-else>
            <q-markup-table flat bordered dense>
              <thead>
                <tr>
                  <th class="text-left" style="width: 80px"></th>
                  <th class="text-left">Produto</th>
                  <th class="text-center" style="width: 80px">Qtd</th>
                  <th class="text-right" style="width: 100px">Valor Unit.</th>
                  <th class="text-right" style="width: 100px">Valor Total</th>
                  <th class="text-center" style="width: 80px">Ações</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in itens" :key="item.codnotafiscalprodutobarra">
                  <td>
                    <q-img v-if="item.produtoBarra?.imagem" :src="item.produtoBarra.imagem"
                      style="width: 60px; height: 60px" fit="contain" class="rounded-borders">
                      <template v-slot:error>
                        <div class="absolute-full flex flex-center bg-grey-3">
                          <q-icon name="image" size="30px" color="grey-5" />
                        </div>
                      </template>
                    </q-img>
                  </td>
                  <td>
                    <div class="text-body2 text-weight-medium">{{ item.produtoBarra?.descricao || '-' }}</div>
                    <div class="text-caption text-grey-7">{{ item.produtoBarra?.barras || '-' }}</div>
                  </td>
                  <td class="text-center">{{ item.quantidade }}</td>
                  <td class="text-right">R$ {{ formatCurrency(item.valorunitario) }}</td>
                  <td class="text-right text-weight-bold">R$ {{ formatCurrency(item.valortotal) }}</td>
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
          </div>
        </q-card-section>
      </q-card>

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
