<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import {
  useTributacaoNaturezaOperacaoStore,
  TIPO_PRODUTO_OPTIONS,
} from '../stores/tributacaoNaturezaOperacaoStore'

const router = useRouter()
const route = useRoute()
const $q = useQuasar()
const tributacaoStore = useTributacaoNaturezaOperacaoStore()

const loading = ref(false)
const codnaturezaoperacao = computed(() => route.params.codnaturezaoperacao)
const codtributacaonaturezaoperacao = computed(() => route.params.codtributacaonaturezaoperacao)
const tributacao = computed(() => tributacaoStore.currentTributacao)

const getTipoProdutoLabel = (codtipoproduto) => {
  const opt = TIPO_PRODUTO_OPTIONS.find((o) => o.value === codtipoproduto)
  return opt ? opt.label : '-'
}

const formatCfop = (cfop) => {
  if (!cfop) return '-'
  const str = String(cfop)
  return str.length === 4 ? `${str[0]}.${str.slice(1)}` : str
}

const formatPercent = (value) => {
  if (value === null || value === undefined) return '-'
  return `${parseFloat(value).toFixed(2)}%`
}

const formatDecimal = (value) => {
  if (value === null || value === undefined) return '-'
  return parseFloat(value).toFixed(2)
}

// Monta título da tributação
const titulo = computed(() => {
  if (!tributacao.value) return ''
  const parts = [
    tributacao.value.tributacao?.tributacao,
    tributacao.value.tipoProduto?.tipoproduto,
    tributacao.value.estado?.sigla,
    tributacao.value.ncm || 'Demais NCMs',
  ].filter(Boolean)
  return parts.join(' / ')
})

// Ações
const handleBack = () => {
  router.push({
    name: 'natureza-operacao-view',
    params: { codnaturezaoperacao: codnaturezaoperacao.value },
  })
}

const handleEdit = () => {
  router.push({
    name: 'tributacao-natureza-operacao-edit',
    params: {
      codnaturezaoperacao: codnaturezaoperacao.value,
      codtributacaonaturezaoperacao: codtributacaonaturezaoperacao.value,
    },
  })
}

const handleDelete = () => {
  $q.dialog({
    title: 'Confirmar exclusão',
    message: `Deseja excluir esta tributação?`,
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Excluir', color: 'negative' },
    persistent: true,
  }).onOk(async () => {
    try {
      await tributacaoStore.deleteTributacao(codtributacaonaturezaoperacao.value)
      $q.notify({ type: 'positive', message: 'Tributação excluída com sucesso' })
      handleBack()
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erro ao excluir',
        caption: error.response?.data?.message || error.message,
      })
    }
  })
}

const loadData = async () => {
  loading.value = true
  try {
    tributacaoStore.setCodNaturezaOperacao(parseInt(codnaturezaoperacao.value))
    await tributacaoStore.fetchTributacao(codtributacaonaturezaoperacao.value)
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao carregar Tributação',
      caption: error.response?.data?.message || error.message,
    })
    handleBack()
  } finally {
    loading.value = false
  }
}

onMounted(loadData)
</script>

<template>
  <q-page class="q-pa-md">
    <!-- Loading -->
    <div v-if="loading" class="row justify-center q-py-xl">
      <q-spinner color="primary" size="3em" />
    </div>

    <template v-else-if="tributacao">
      <!-- Header -->
      <div class="row items-center q-mb-md">
        <q-btn flat dense round icon="arrow_back" @click="handleBack" />
        <div class="text-h6 q-ml-sm ellipsis" style="max-width: 80%">{{ titulo }}</div>
        <q-space />
        <q-btn flat round icon="edit" color="primary" @click="handleEdit">
          <q-tooltip>Editar</q-tooltip>
        </q-btn>
        <q-btn flat round icon="delete" color="negative" @click="handleDelete">
          <q-tooltip>Excluir</q-tooltip>
        </q-btn>
      </div>

      <div class="row q-col-gutter-md">
        <!-- Coluna 1: Chave -->
        <div class="col-12 col-md-6">
          <q-card flat bordered>
            <div class="text-subtitle1 text-white bg-primary q-pa-sm">
              <q-icon name="vpn_key" class="q-mr-sm" />
              Chave
            </div>
            <q-list dense separator>
              <q-item>
                <q-item-section>
                  <q-item-label caption>Código</q-item-label>
                  <q-item-label>
                    #{{ String(tributacao.codtributacaonaturezaoperacao).padStart(8, '0') }}
                  </q-item-label>
                </q-item-section>
              </q-item>
              <q-item
                v-if="tributacao.codtributacao"
                clickable
                :to="{
                  name: 'tributacao-cadastro-edit',
                  params: { codtributacao: tributacao.codtributacao },
                }"
                target="_blank"
              >
                <q-item-section>
                  <q-item-label caption>Tributação</q-item-label>
                  <q-item-label class="text-primary">
                    {{ tributacao.tributacao?.tributacao || '-' }}
                  </q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-icon name="chevron_right" color="grey" />
                </q-item-section>
              </q-item>
              <q-item v-else>
                <q-item-section>
                  <q-item-label caption>Tributação</q-item-label>
                  <q-item-label>-</q-item-label>
                </q-item-section>
              </q-item>
              <q-item>
                <q-item-section>
                  <q-item-label caption>Tipo de Produto</q-item-label>
                  <q-item-label>{{ getTipoProdutoLabel(tributacao.codtipoproduto) }}</q-item-label>
                </q-item-section>
              </q-item>
              <q-item>
                <q-item-section>
                  <q-item-label caption>BIT</q-item-label>
                  <q-item-label>{{ tributacao.bit ? 'Sim' : 'Não' }}</q-item-label>
                </q-item-section>
              </q-item>
              <q-item>
                <q-item-section>
                  <q-item-label caption>NCM</q-item-label>
                  <q-item-label>{{ tributacao.ncm || '-' }}</q-item-label>
                </q-item-section>
              </q-item>
              <q-item
                v-if="tributacao.codestado"
                clickable
                :to="{ name: 'cidade' }"
                target="_blank"
              >
                <q-item-section>
                  <q-item-label caption>Estado</q-item-label>
                  <q-item-label class="text-primary">
                    {{ tributacao.estado?.estado || '-' }}
                  </q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-icon name="chevron_right" color="grey" />
                </q-item-section>
              </q-item>
              <q-item v-else>
                <q-item-section>
                  <q-item-label caption>Estado</q-item-label>
                  <q-item-label>-</q-item-label>
                </q-item-section>
              </q-item>
              <q-item
                v-if="tributacao.codcfop"
                clickable
                :to="{
                  name: 'cfop-edit',
                  params: { codcfop: tributacao.codcfop },
                }"
                target="_blank"
              >
                <q-item-section>
                  <q-item-label caption>CFOP</q-item-label>
                  <q-item-label class="text-primary">
                    {{ formatCfop(tributacao.cfop?.codcfop) }} - {{ tributacao.cfop?.cfop || '' }}
                  </q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-icon name="chevron_right" color="grey" />
                </q-item-section>
              </q-item>
              <q-item v-else>
                <q-item-section>
                  <q-item-label caption>CFOP</q-item-label>
                  <q-item-label>-</q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </q-card>
        </div>

        <!-- Coluna 2: Contábil -->
        <div class="col-12 col-md-6">
          <q-card flat bordered>
            <div class="text-subtitle1 text-white bg-grey-7 q-pa-sm">
              <q-icon name="account_balance" class="q-mr-sm" />
              Contábil
            </div>
            <q-list dense separator>
              <q-item>
                <q-item-section>
                  <q-item-label caption>Acumulador Vista</q-item-label>
                  <q-item-label>{{ tributacao.acumuladordominiovista || '-' }}</q-item-label>
                </q-item-section>
              </q-item>
              <q-item>
                <q-item-section>
                  <q-item-label caption>Acumulador Prazo</q-item-label>
                  <q-item-label>{{ tributacao.acumuladordominioprazo || '-' }}</q-item-label>
                </q-item-section>
              </q-item>
              <q-item>
                <q-item-section>
                  <q-item-label caption>Histórico Domínio</q-item-label>
                  <q-item-label class="text-caption">
                    {{ tributacao.historicodominio || '-' }}
                  </q-item-label>
                </q-item-section>
              </q-item>
              <q-item>
                <q-item-section>
                  <q-item-label caption>Movimentação Física</q-item-label>
                  <q-item-label>{{ tributacao.movimentacaofisica ? 'Sim' : 'Não' }}</q-item-label>
                </q-item-section>
              </q-item>
              <q-item>
                <q-item-section>
                  <q-item-label caption>Movimentação Contábil</q-item-label>
                  <q-item-label>{{ tributacao.movimentacaocontabil ? 'Sim' : 'Não' }}</q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </q-card>
        </div>
      </div>

      <!-- Lucro Presumido -->
      <q-card flat bordered class="q-mt-md">
        <div class="text-subtitle1 text-white bg-blue q-pa-sm">
          <q-icon name="business" class="q-mr-sm" />
          Lucro Presumido
        </div>
        <q-card-section>
          <div class="row q-col-gutter-md">
            <!-- ICMS -->
            <div class="col-6 col-sm-3">
              <div class="text-caption text-grey-7 q-mb-xs">ICMS</div>
              <div class="text-caption">
                <b>CST:</b>
                {{ tributacao.icmscst ?? '-' }}
              </div>
              <div class="text-caption">
                <b>Base:</b>
                {{ formatPercent(tributacao.icmslpbase) }}
              </div>
              <div class="text-caption">
                <b>Alíquota:</b>
                {{ formatPercent(tributacao.icmslppercentual) }}
              </div>
            </div>
            <!-- PIS -->
            <div class="col-6 col-sm-3">
              <div class="text-caption text-grey-7 q-mb-xs">PIS</div>
              <div class="text-caption">
                <b>CST:</b>
                {{ tributacao.piscst ?? '-' }}
              </div>
              <div class="text-caption">
                <b>Alíquota:</b>
                {{ formatPercent(tributacao.pispercentual) }}
              </div>
            </div>
            <!-- COFINS -->
            <div class="col-6 col-sm-3">
              <div class="text-caption text-grey-7 q-mb-xs">COFINS</div>
              <div class="text-caption">
                <b>CST:</b>
                {{ tributacao.cofinscst ?? '-' }}
              </div>
              <div class="text-caption">
                <b>Alíquota:</b>
                {{ formatPercent(tributacao.cofinspercentual) }}
              </div>
            </div>
            <!-- IPI/CSLL/IRPJ -->
            <div class="col-6 col-sm-3">
              <div class="text-caption text-grey-7 q-mb-xs">IPI / CSLL / IRPJ</div>
              <div class="text-caption">
                <b>IPI CST:</b>
                {{ tributacao.ipicst ?? '-' }}
              </div>
              <div class="text-caption">
                <b>CSLL:</b>
                {{ formatPercent(tributacao.csllpercentual) }}
              </div>
              <div class="text-caption">
                <b>IRPJ:</b>
                {{ formatPercent(tributacao.irpjpercentual) }}
              </div>
            </div>
          </div>
        </q-card-section>
      </q-card>

      <div class="row q-col-gutter-md q-mt-xs">
        <!-- Simples Nacional -->
        <div class="col-12 col-md-6">
          <q-card flat bordered>
            <div class="text-subtitle1 text-white bg-teal q-pa-sm">
              <q-icon name="store" class="q-mr-sm" />
              Simples Nacional
            </div>
            <q-list dense separator>
              <q-item>
                <q-item-section>
                  <q-item-label caption>CSOSN</q-item-label>
                  <q-item-label>{{ tributacao.csosn ?? '-' }}</q-item-label>
                </q-item-section>
              </q-item>
              <q-item>
                <q-item-section>
                  <q-item-label caption>ICMS Base</q-item-label>
                  <q-item-label>{{ formatPercent(tributacao.icmsbase) }}</q-item-label>
                </q-item-section>
              </q-item>
              <q-item>
                <q-item-section>
                  <q-item-label caption>ICMS %</q-item-label>
                  <q-item-label>{{ formatPercent(tributacao.icmspercentual) }}</q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </q-card>
        </div>

        <!-- Produtor Rural -->
        <div class="col-12 col-md-6">
          <q-card flat bordered>
            <div class="text-subtitle1 text-white bg-green q-pa-sm">
              <q-icon name="agriculture" class="q-mr-sm" />
              Produtor Rural
            </div>
            <q-list dense separator>
              <q-item>
                <q-item-section>
                  <q-item-label caption>Certidão SEFAZ MT</q-item-label>
                  <q-item-label>{{ tributacao.certidaosefazmt ? 'Sim' : 'Não' }}</q-item-label>
                </q-item-section>
              </q-item>
              <q-item>
                <q-item-section>
                  <q-item-label caption>FETHAB por KG</q-item-label>
                  <q-item-label>
                    {{ tributacao.fethabkg ? formatDecimal(tributacao.fethabkg) : '-' }}
                  </q-item-label>
                </q-item-section>
              </q-item>
              <q-item>
                <q-item-section>
                  <q-item-label caption>IAGRO por KG</q-item-label>
                  <q-item-label>
                    {{ tributacao.iagrokg ? formatDecimal(tributacao.iagrokg) : '-' }}
                  </q-item-label>
                </q-item-section>
              </q-item>
              <q-item>
                <q-item-section>
                  <q-item-label caption>FUNRURAL %</q-item-label>
                  <q-item-label>
                    {{
                      tributacao.funruralpercentual
                        ? formatPercent(tributacao.funruralpercentual)
                        : '-'
                    }}
                  </q-item-label>
                </q-item-section>
              </q-item>
              <q-item>
                <q-item-section>
                  <q-item-label caption>SENAR %</q-item-label>
                  <q-item-label>
                    {{
                      tributacao.senarpercentual ? formatPercent(tributacao.senarpercentual) : '-'
                    }}
                  </q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </q-card>
        </div>
      </div>

      <!-- Observações -->
      <q-card v-if="tributacao.observacoesnf" flat bordered class="q-mt-md">
        <div class="text-subtitle1 text-white bg-grey-7 q-pa-sm">
          <q-icon name="notes" class="q-mr-sm" />
          Observações NF
        </div>
        <q-card-section>
          <div class="text-body2">{{ tributacao.observacoesnf }}</div>
        </q-card-section>
      </q-card>

      <!-- Auditoria -->
      <div v-if="tributacao.usuarioAlteracao" class="text-caption text-grey q-mt-lg">
        Alterado em {{ new Date(tributacao.alteracao).toLocaleString('pt-BR') }} por
        {{ tributacao.usuarioAlteracao.usuario }}
      </div>
    </template>
  </q-page>
</template>
