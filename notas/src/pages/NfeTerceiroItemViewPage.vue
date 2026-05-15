<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import { useNfeTerceiroStore } from '../stores/nfeTerceiroStore'
import SelectProdutoBarra from 'src/components/selects/SelectProdutoBarra.vue'
import MgInputValor from '@components/MgInputValor.vue'
import { formatCurrency, formatDate, formatDateTime, formatDecimal } from 'src/utils/formatters'
import { conformidades, corConformidade } from 'src/utils/nfeTerceiroItemConformidade'

const route = useRoute()
const $q = useQuasar()
const nfeTerceiroStore = useNfeTerceiroStore()

const mglaraUrl = process.env.MGLARA_URL || ''

const codnfeterceiro = computed(() => Number(route.params.codnfeterceiro))
const codnfeterceiroitem = computed(() => Number(route.params.codnfeterceiroitem))

const nfe = computed(() => nfeTerceiroStore.currentNfeTerceiro)
const item = computed(() => {
  if (!nfe.value?.itens) return null
  return nfe.value.itens.find((i) => i.codnfeterceiroitem === codnfeterceiroitem.value)
})
const produto = computed(() => item.value?.produtoBarra?.produto || null)
const variacao = computed(() => item.value?.produtoBarra?.variacao || null)

const analise = computed(() => nfeTerceiroStore.analiseItens[codnfeterceiroitem.value] || null)
const loadingAnalise = computed(
  () => !!nfeTerceiroStore.loadingAnaliseItens[codnfeterceiroitem.value]
)

const conf = computed(() => conformidades(item.value || {}, analise.value?.codtributacao))

const loading = ref(false)

const origemDescricao = (orig) => {
  const map = {
    0: 'Nacional, exceto as indicadas nos códigos 3, 4, 5 e 8',
    1: 'Estrangeira - Importação direta',
    2: 'Estrangeira - Adquirida no mercado interno',
    3: 'Nacional, mercadoria ou bem com Conteúdo de Importação superior a 40%',
    4: 'Nacional, cuja produção tenha sido feita em conformidade com os PPB',
    5: 'Nacional, mercadoria ou bem com Conteúdo de Importação inferior ou igual a 40%',
    6: 'Estrangeira - Importação direta, sem similar nacional',
    7: 'Estrangeira - Adquirida no mercado interno, sem similar nacional',
    8: 'Nacional, mercadoria ou bem com Conteúdo de Importação superior a 70%',
  }
  return map[orig] || '-'
}

const abcBadge = (p) => {
  if (!p?.abc) return null
  const map = {
    A: { color: 'green', label: 'A' },
    B: { color: 'orange', label: 'B' },
    C: { color: 'blue', label: 'C' },
  }
  return map[p.abc] || { color: 'grey', label: String(p.abc) }
}

const statusEmbalagemClasses = (status) => {
  switch (status) {
    case 'success':
      return 'bg-green-1'
    case 'warning':
      return 'bg-orange-1'
    case 'error':
      return 'bg-red-1'
    default:
      return ''
  }
}

const statusEmbalagemText = (status) => {
  switch (status) {
    case 'success':
      return 'text-green-8'
    case 'warning':
      return 'text-orange-8'
    case 'error':
      return 'text-red'
    default:
      return ''
  }
}

const ehBarrasInterna = (b) => String(b || '').startsWith('234')

// Tabela de custo: cada item { label, total, unitario, suffix?, destaque? }
const linhasCusto = computed(() => {
  if (!item.value) return []
  const a = analise.value
  const i = item.value
  const q = a?.quantidade || 0
  const linhas = []
  const add = (label, total, dividir = true, extra = {}) => {
    if (total === undefined || total === null) return
    if (Number(total) === 0) return
    linhas.push({
      label,
      total,
      unitario: dividir && q > 0 ? total / q : null,
      ...extra,
    })
  }
  add('Custo Produto', Number(i.vprod) || 0)
  add('IPI', Number(i.ipivipi) || 0)
  if (a) {
    add('ICMS ST', Number(a.vicmsstutilizado) || 0)
    add('ICMS Garantido', Number(a.vicmsgarantido) || 0)
  } else {
    add('ICMS ST', Number(i.vicmsst) || 0)
  }
  add('Complemento', Number(i.complemento) || 0)
  add('Desconto', -(Number(i.vdesc) || 0))
  add('Frete', Number(i.vfrete) || 0)
  add('Seguro', Number(i.vseg) || 0)
  add('Outro', Number(i.voutro) || 0)
  if (a && !a.vicmsstutilizado) {
    add('Crédito ICMS', -(Number(a.vicmscredito) || 0))
  }
  if (a?.vcusto !== null && a?.vcusto !== undefined) {
    linhas.push({
      label: 'Custo Final',
      total: a.vcusto,
      unitario: a.vcustounitario,
      destaque: true,
    })
  }
  return linhas
})

// Fórmula da quantidade calculada: "672,000 (56,000 CX × 12,000)"
const quantidadeCalculadaTexto = computed(() => {
  const a = analise.value
  if (!a || !a.quantidade) return null
  const base = formatDecimal(a.quantidade, 3)
  const emb = a.embalagemBase
  const qcom = item.value?.qcom
  if (!emb || !qcom) return base
  return `${base} (${formatDecimal(qcom, 3)} ${emb.sigla || ''} × ${formatDecimal(emb.quantidade, 3)})`
})

// Linha "ICMS Venda" com sufixo (alíquota @ redução)
const linhaIcmsVenda = computed(() => {
  const a = analise.value
  if (!a || !a.vicmsvenda) return null
  const aliquota = formatDecimal(a.picmsvenda || 0, 2)
  const reducao = formatDecimal((a.picmsbasereducao || 0) * 100, 2)
  return {
    total: a.vicmsvenda,
    unitario: a.quantidade > 0 ? a.vicmsvenda / a.quantidade : null,
    suffix: `(${aliquota}% @ ${reducao}%)`,
  }
})

// Linhas de venda por embalagem (não-base)
const vendasPorEmbalagem = computed(() => {
  const a = analise.value
  if (!a?.vendas?.length) return []
  return a.vendas.filter(
    (v) => v.codprodutoembalagem !== null && v.codprodutoembalagem !== undefined
  )
})

const showDetalhes = ref(false)
const formDetalhes = ref({})

const abrirDetalhes = () => {
  formDetalhes.value = {
    codprodutobarra: item.value.codprodutobarra,
    margem: item.value.margem,
    complemento: item.value.complemento,
    observacoes: item.value.observacoes,
  }
  showDetalhes.value = true
}

const salvarDetalhes = async () => {
  try {
    await nfeTerceiroStore.updateItem(
      codnfeterceiro.value,
      codnfeterceiroitem.value,
      formDetalhes.value
    )
    showDetalhes.value = false
    $q.notify({ type: 'positive', message: 'Item atualizado' })
    await carregarAnalise()
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao atualizar item',
      caption: error.response?.data?.message || error.message,
    })
  }
}

const handleConferencia = () => {
  $q.dialog({
    title: 'Confirmar',
    message: item.value?.conferencia ? 'Desmarcar conferência?' : 'Marcar como conferido?',
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Confirmar', color: 'primary' },
  }).onOk(async () => {
    try {
      await nfeTerceiroStore.toggleConferenciaItem(codnfeterceiro.value, codnfeterceiroitem.value)
      $q.notify({ type: 'positive', message: 'Conferência atualizada' })
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erro ao alterar conferência',
        caption: error.response?.data?.message || error.message,
      })
    }
  })
}

const conferenciaTooltip = computed(() => {
  if (!item.value?.conferencia) return 'Marcar como conferido'
  const usuario = item.value.usuarioConferencia?.usuario
  const quando = formatDateTime(item.value.conferencia)
  return usuario ? `Conferido por ${usuario} em ${quando}` : `Conferido em ${quando}`
})

const carregarAnalise = async () => {
  if (!codnfeterceiro.value || !codnfeterceiroitem.value) return
  try {
    await nfeTerceiroStore.fetchAnaliseItem(codnfeterceiro.value, codnfeterceiroitem.value, {
      force: true,
    })
  } catch (error) {
    if (error?.response?.status !== 404) {
      console.error('Falha ao carregar análise do item', error)
    }
  }
}

onMounted(async () => {
  if (!nfe.value || nfe.value.codnfeterceiro !== codnfeterceiro.value) {
    loading.value = true
    try {
      await nfeTerceiroStore.fetchNfeTerceiro(codnfeterceiro.value)
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erro ao carregar NFe',
        caption: error.response?.data?.message || error.message,
      })
    } finally {
      loading.value = false
    }
  }
  await carregarAnalise()
})

watch(codnfeterceiroitem, () => carregarAnalise())
</script>

<template>
  <q-page padding>
    <div v-if="loading" class="row justify-center q-py-xl">
      <q-spinner color="primary" size="3em" />
    </div>

    <div v-else-if="!item" class="row justify-center q-py-xl">
      <div class="text-h6 text-grey-7">Item não encontrado</div>
    </div>

    <div v-else>
      <!-- Header -->
      <div class="row items-center q-mb-md no-wrap">
        <q-btn
          flat
          dense
          round
          icon="arrow_back"
          :to="{ name: 'nfe-terceiro-view', params: { codnfeterceiro: codnfeterceiro } }"
          class="q-mr-sm flex-shrink-0"
        >
          <q-tooltip>Voltar</q-tooltip>
        </q-btn>

        <div class="col" style="min-width: 0">
          <div class="text-h5 ellipsis">{{ item.xprod }}</div>
          <div class="row items-center q-gutter-xs q-mt-xs" v-if="produto">
            <a
              v-if="produto.codproduto"
              :href="`${mglaraUrl}/produto/${produto.codproduto}`"
              target="_blank"
              class="text-primary text-weight-bold"
              style="text-decoration: none"
            >
              {{ produto.produto }}
            </a>
            <span class="text-grey-7" v-if="variacao?.variacao">| {{ variacao.variacao }}</span>
            <q-badge
              v-if="abcBadge(produto)"
              :color="abcBadge(produto).color"
              :label="abcBadge(produto).label"
              class="q-ml-xs"
            >
              <q-tooltip>Classificação ABC</q-tooltip>
            </q-badge>
            <q-badge v-if="produto.inativo" color="red" class="q-ml-xs">
              Inativo desde {{ formatDate(produto.inativo) }}
            </q-badge>
          </div>
        </div>

        <q-btn
          flat
          dense
          icon="task_alt"
          :color="item.conferencia ? 'green' : 'grey-7'"
          class="q-mr-sm flex-shrink-0"
          @click="handleConferencia"
        >
          <q-tooltip>{{ conferenciaTooltip }}</q-tooltip>
        </q-btn>

        <q-btn
          flat
          dense
          icon="edit_note"
          color="grey-7"
          class="flex-shrink-0"
          @click="abrirDetalhes"
        >
          <q-tooltip>Informar Detalhes</q-tooltip>
        </q-btn>
      </div>

      <!-- Banner: infadprod -->
      <q-banner
        v-if="item.infadprod"
        rounded
        dense
        class="bg-grey-2 text-grey-9 q-mb-md"
        icon="info"
      >
        {{ item.infadprod }}
      </q-banner>

      <!-- Linha 1: Embalagens + Custo + Conformidade -->
      <div class="row q-col-gutter-md q-mb-md">
        <!-- Card: Embalagens -->
        <div class="col-12 col-md-5">
          <q-card flat bordered class="full-height">
            <q-card-section class="bg-primary text-white">
              <div class="text-body2">
                <q-icon name="inventory_2" size="1.5em" class="q-mr-sm" />
                Embalagens
                <q-spinner v-if="loadingAnalise" color="white" size="1em" class="q-ml-sm" />
              </div>
            </q-card-section>
            <q-card-section v-if="!analise && !loadingAnalise" class="text-center text-grey-6">
              Sem produto vinculado para calcular sugestão
            </q-card-section>
            <q-markup-table v-else-if="analise?.vendas?.length" dense flat>
              <thead>
                <tr>
                  <th class="text-right">Qtde</th>
                  <th class="text-left">UM</th>
                  <th class="text-left">Barras</th>
                  <th class="text-right">Venda</th>
                  <th class="text-right">Sugestão</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(v, idx) in analise.vendas"
                  :key="idx"
                  :class="statusEmbalagemClasses(v.status)"
                >
                  <td class="text-right text-weight-medium">
                    {{ v.qtd_convertida !== null ? formatDecimal(v.qtd_convertida, 2) : '-' }}
                  </td>
                  <td>
                    {{ v.sigla || '-' }}
                    <span v-if="v.quantidade_emb > 1" class="text-weight-bold">
                      C/{{ formatDecimal(v.quantidade_emb, 0) }}
                    </span>
                  </td>
                  <td>
                    <template
                      v-for="b in (variacao?.produtoBarras || []).filter(
                        (pb) => pb.codprodutoembalagem === v.codprodutoembalagem
                      )"
                      :key="b.codprodutobarra"
                    >
                      <span
                        :class="
                          ehBarrasInterna(b.barras)
                            ? 'text-red text-weight-bold'
                            : 'text-green-8 text-weight-medium'
                        "
                        style="font-family: monospace; display: block"
                      >
                        {{ b.barras }}
                      </span>
                    </template>
                  </td>
                  <td
                    class="text-right"
                    :class="`text-weight-bold ${statusEmbalagemText(v.status)}`"
                  >
                    {{ formatCurrency(v.preco_atual) }}
                  </td>
                  <td class="text-right text-grey-8">
                    {{ v.sugestao !== null ? formatCurrency(v.sugestao) : '-' }}
                  </td>
                </tr>
              </tbody>
            </q-markup-table>
            <q-card-section v-else class="text-center text-grey-6">
              {{
                analise?.vcusto !== null && analise?.vcusto !== undefined
                  ? 'Margem zerada — informe margem em "Informar Detalhes"'
                  : 'Sem dados de venda'
              }}
            </q-card-section>
          </q-card>
        </div>

        <!-- Card: Custo -->
        <div class="col-12 col-md-4">
          <q-card flat bordered class="full-height">
            <q-card-section class="bg-primary text-white">
              <div class="text-body2">
                <q-icon name="payments" size="1.5em" class="q-mr-sm" />
                Custo
              </div>
            </q-card-section>
            <q-list dense separator>
              <q-item v-for="(l, idx) in linhasCusto" :key="idx">
                <q-item-section>
                  <q-item-label :class="l.destaque ? 'text-weight-bold' : ''">
                    {{ l.label }}
                  </q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-item-label :class="l.destaque ? 'text-weight-bold text-primary' : ''">
                    R$ {{ formatCurrency(l.total) }}
                    <span v-if="l.unitario !== null" class="text-grey-7 text-caption q-ml-xs">
                      ({{ formatCurrency(l.unitario) }})
                    </span>
                  </q-item-label>
                </q-item-section>
              </q-item>

              <!-- Quantidade Calculada com fórmula de conversão -->
              <q-item v-if="quantidadeCalculadaTexto">
                <q-item-section>
                  <q-item-label>Quantidade Calculada</q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-item-label class="text-grey-9">{{ quantidadeCalculadaTexto }}</q-item-label>
                </q-item-section>
              </q-item>

              <!-- ICMS Venda com alíquota e redução -->
              <q-item v-if="linhaIcmsVenda">
                <q-item-section>
                  <q-item-label>ICMS Venda</q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-item-label>
                    R$ {{ formatCurrency(linhaIcmsVenda.total) }}
                    <span
                      v-if="linhaIcmsVenda.unitario !== null"
                      class="text-grey-7 text-caption q-ml-xs"
                    >
                      ({{ formatCurrency(linhaIcmsVenda.unitario) }})
                    </span>
                    <span class="text-grey-7 text-caption q-ml-xs">
                      {{ linhaIcmsVenda.suffix }}
                    </span>
                  </q-item-label>
                </q-item-section>
              </q-item>

              <q-item v-if="analise && (Number(item.margem) || 0) > 0">
                <q-item-section>
                  <q-item-label>Margem</q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-item-label>{{ formatDecimal(item.margem, 2) }}%</q-item-label>
                </q-item-section>
              </q-item>
              <q-item v-if="analise?.vsugestaovenda">
                <q-item-section>
                  <q-item-label class="text-weight-bold">Sugestão Venda</q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-item-label class="text-weight-bold text-primary">
                    R$ {{ formatCurrency(analise.vsugestaovenda) }}
                  </q-item-label>
                </q-item-section>
              </q-item>

              <!-- Sugestão + Venda por embalagem -->
              <template v-for="v in vendasPorEmbalagem" :key="`emb-${v.codprodutoembalagem}`">
                <q-item>
                  <q-item-section>
                    <q-item-label class="text-grey-7">Sugestão</q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <q-item-label class="text-grey-8">
                      R$ {{ formatCurrency(v.sugestao) }}
                    </q-item-label>
                  </q-item-section>
                </q-item>
                <q-item>
                  <q-item-section>
                    <q-item-label>
                      {{ v.sigla }}
                      <span v-if="v.quantidade_emb > 1" class="text-weight-bold">
                        C/{{ formatDecimal(v.quantidade_emb, 0) }}
                      </span>
                    </q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <q-item-label :class="`text-weight-bold ${statusEmbalagemText(v.status)}`">
                      R$ {{ formatCurrency(v.preco_atual) }}
                      <span v-if="v.quantidade_emb > 0" class="text-grey-7 text-caption q-ml-xs">
                        ({{ formatCurrency(v.preco_atual / v.quantidade_emb) }})
                      </span>
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </template>
            </q-list>
            <q-card-section v-if="!linhasCusto.length" class="text-center text-grey-6">
              Sem custos calculados
            </q-card-section>
          </q-card>
        </div>

        <!-- Card: Conformidade -->
        <div class="col-12 col-md-3">
          <q-card flat bordered class="full-height">
            <q-card-section class="bg-primary text-white">
              <div class="text-body2">
                <q-icon name="rule" size="1.5em" class="q-mr-sm" />
                NF × Cadastro
              </div>
            </q-card-section>
            <q-list dense separator>
              <q-item v-for="(c, key) in conf" :key="key">
                <q-item-section avatar>
                  <q-icon
                    :name="c.ok === true ? 'check_circle' : c.ok === false ? 'cancel' : 'remove'"
                    :class="corConformidade(c)"
                  />
                </q-item-section>
                <q-item-section>
                  <q-item-label class="text-caption">
                    {{
                      {
                        ncm: 'NCM',
                        cest: 'CEST',
                        origem: 'Origem',
                        cean: 'EAN',
                        ceantrib: 'EAN Trib',
                        cst: 'Tributação',
                      }[key]
                    }}
                  </q-item-label>
                  <q-item-label caption class="text-grey-7 ellipsis">
                    {{ c.motivo }}
                  </q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </q-card>
        </div>
      </div>

      <!-- Linha 2: Dados da Nota + Impostos -->
      <div class="row q-col-gutter-md">
        <!-- Card: Dados da Nota -->
        <div class="col-12 col-md-6">
          <q-card flat bordered class="full-height">
            <q-card-section class="bg-primary text-white">
              <div class="text-body2">
                <q-icon name="receipt_long" size="1.5em" class="q-mr-sm" />
                Dados da Nota
              </div>
            </q-card-section>
            <q-card-section class="q-py-sm">
              <div class="text-caption text-weight-bold q-mb-xs">Identificação</div>
              <div class="row q-col-gutter-sm">
                <div class="col-6 col-sm-4">
                  <div class="text-caption text-grey-7">#</div>
                  <div class="text-body2">{{ item.codnfeterceiroitem }}</div>
                </div>
                <div class="col-6 col-sm-4">
                  <div class="text-caption text-grey-7">Item</div>
                  <div class="text-body2">{{ item.nitem }}</div>
                </div>
                <div class="col-6 col-sm-4">
                  <div class="text-caption text-grey-7">Referência</div>
                  <div class="text-body2">{{ item.cprod }}</div>
                </div>
              </div>
            </q-card-section>

            <q-separator />

            <q-card-section class="q-py-sm">
              <div class="text-caption text-weight-bold q-mb-xs">Classificação Fiscal</div>
              <div class="row q-col-gutter-sm">
                <div class="col-6 col-sm-4">
                  <div class="text-caption text-grey-7">CFOP</div>
                  <div class="text-body2">{{ item.cfop }}</div>
                </div>
                <div class="col-6 col-sm-4">
                  <div class="text-caption text-grey-7">CST/CSOSN</div>
                  <div class="text-body2" :class="corConformidade(conf.cst)">
                    {{ item.cst }}{{ item.csosn }}
                    <q-tooltip v-if="produto?.tributacao?.tributacao">
                      Cadastro: {{ produto.tributacao.tributacao }}
                    </q-tooltip>
                  </div>
                </div>
                <div class="col-12 col-sm-4">
                  <div class="text-caption text-grey-7">Origem</div>
                  <div class="text-body2" :class="corConformidade(conf.origem)">
                    {{ item.orig }} - {{ origemDescricao(item.orig) }}
                  </div>
                </div>
              </div>
            </q-card-section>

            <q-separator />

            <q-card-section class="q-py-sm">
              <div class="text-caption text-weight-bold q-mb-xs">Códigos</div>
              <div class="row q-col-gutter-sm">
                <div class="col-6 col-sm-4">
                  <div class="text-caption text-grey-7">EAN</div>
                  <div
                    class="text-body2"
                    :class="corConformidade(conf.cean)"
                    style="font-family: monospace"
                  >
                    {{ item.cean || '-' }}
                  </div>
                </div>
                <div v-if="item.ceantrib && item.ceantrib !== item.cean" class="col-6 col-sm-4">
                  <div class="text-caption text-grey-7">EAN Trib</div>
                  <div
                    class="text-body2"
                    :class="corConformidade(conf.ceantrib)"
                    style="font-family: monospace"
                  >
                    {{ item.ceantrib }}
                  </div>
                </div>
                <div class="col-6 col-sm-4">
                  <div class="text-caption text-grey-7">NCM</div>
                  <div class="text-body2" :class="corConformidade(conf.ncm)">
                    {{ item.ncm }}
                    <q-tooltip v-if="produto?.ncm?.ncm">Cadastro: {{ produto.ncm.ncm }}</q-tooltip>
                  </div>
                </div>
                <div class="col-6 col-sm-3">
                  <div class="text-caption text-grey-7">CEST</div>
                  <div class="text-body2" :class="corConformidade(conf.cest)">
                    {{ item.cest || '-' }}
                    <q-tooltip v-if="produto?.cest?.cest">
                      Cadastro: {{ produto.cest.cest }}
                    </q-tooltip>
                  </div>
                </div>
              </div>
            </q-card-section>

            <q-separator />

            <q-card-section class="q-py-sm">
              <div class="text-caption text-weight-bold q-mb-xs">Comercial</div>
              <div class="row q-col-gutter-sm">
                <div class="col-6 col-sm-4">
                  <div class="text-caption text-grey-7">Quantidade</div>
                  <div class="text-body2">{{ formatDecimal(item.qcom, 3) }} {{ item.ucom }}</div>
                </div>
                <div class="col-6 col-sm-4">
                  <div class="text-caption text-grey-7">Preço</div>
                  <div class="text-body2">{{ formatDecimal(item.vuncom, 6) }}</div>
                </div>
                <div class="col-6 col-sm-4">
                  <div class="text-caption text-grey-7">Total</div>
                  <div class="text-subtitle1 text-weight-bold">
                    R$ {{ formatCurrency(item.vprod) }}
                  </div>
                </div>
              </div>
            </q-card-section>

            <q-separator />

            <q-card-section class="q-py-sm">
              <div class="text-caption text-weight-bold q-mb-xs">Tributário</div>
              <div class="row q-col-gutter-sm">
                <div class="col-6 col-sm-4">
                  <div class="text-caption text-grey-7">Quantidade</div>
                  <div class="text-body2">{{ formatDecimal(item.qtrib, 3) }} {{ item.utrib }}</div>
                </div>
                <div class="col-6 col-sm-4">
                  <div class="text-caption text-grey-7">Preço</div>
                  <div class="text-body2">{{ formatDecimal(item.vuntrib, 6) }}</div>
                </div>
              </div>
            </q-card-section>
          </q-card>
        </div>

        <!-- Card: Impostos -->
        <div class="col-12 col-md-6">
          <q-card flat bordered class="full-height">
            <q-card-section class="bg-primary text-white">
              <div class="text-body2">
                <q-icon name="account_balance" size="1.5em" class="q-mr-sm" />
                Impostos
              </div>
            </q-card-section>
            <q-card-section class="q-py-sm">
              <div class="text-caption text-weight-bold q-mb-xs">ICMS</div>
              <div class="row q-col-gutter-sm">
                <div class="col-4">
                  <div class="text-caption text-grey-7">Base</div>
                  <div class="text-body2">R$ {{ formatCurrency(item.vbc) }}</div>
                </div>
                <div class="col-4">
                  <div class="text-caption text-grey-7">Alíquota</div>
                  <div class="text-body2">{{ formatDecimal(item.picms, 2) }}%</div>
                </div>
                <div class="col-4">
                  <div class="text-caption text-grey-7">Valor</div>
                  <div class="text-body2">R$ {{ formatCurrency(item.vicms) }}</div>
                </div>
              </div>
            </q-card-section>

            <q-separator />

            <q-card-section class="q-py-sm">
              <div class="text-caption text-weight-bold q-mb-xs">ICMS ST</div>
              <div class="row q-col-gutter-sm">
                <div class="col-4">
                  <div class="text-caption text-grey-7">Base</div>
                  <div class="text-body2">R$ {{ formatCurrency(item.vbcst) }}</div>
                </div>
                <div class="col-4">
                  <div class="text-caption text-grey-7">Alíquota</div>
                  <div class="text-body2">{{ formatDecimal(item.picmsst, 2) }}%</div>
                </div>
                <div class="col-4">
                  <div class="text-caption text-grey-7">Valor</div>
                  <div class="text-body2">R$ {{ formatCurrency(item.vicmsst) }}</div>
                </div>
              </div>
            </q-card-section>

            <q-separator />

            <q-card-section class="q-py-sm">
              <div class="text-caption text-weight-bold q-mb-xs">IPI</div>
              <div class="row q-col-gutter-sm">
                <div class="col-4">
                  <div class="text-caption text-grey-7">Base</div>
                  <div class="text-body2">R$ {{ formatCurrency(item.ipivbc) }}</div>
                </div>
                <div class="col-4">
                  <div class="text-caption text-grey-7">Alíquota</div>
                  <div class="text-body2">{{ formatDecimal(item.ipipipi, 2) }}%</div>
                </div>
                <div class="col-4">
                  <div class="text-caption text-grey-7">Valor</div>
                  <div class="text-body2">R$ {{ formatCurrency(item.ipivipi) }}</div>
                </div>
              </div>
            </q-card-section>

            <q-separator />

            <q-card-section class="q-py-sm">
              <div class="text-caption text-weight-bold q-mb-xs">PIS</div>
              <div class="row q-col-gutter-sm">
                <div class="col-4">
                  <div class="text-caption text-grey-7">Base</div>
                  <div class="text-body2">R$ {{ formatCurrency(item.pisvbc) }}</div>
                </div>
                <div class="col-4">
                  <div class="text-caption text-grey-7">Alíquota</div>
                  <div class="text-body2">{{ formatDecimal(item.pisppis, 2) }}%</div>
                </div>
                <div class="col-4">
                  <div class="text-caption text-grey-7">Valor</div>
                  <div class="text-body2">R$ {{ formatCurrency(item.pisvpis) }}</div>
                </div>
              </div>
            </q-card-section>

            <q-separator />

            <q-card-section class="q-py-sm">
              <div class="text-caption text-weight-bold q-mb-xs">COFINS</div>
              <div class="row q-col-gutter-sm">
                <div class="col-4">
                  <div class="text-caption text-grey-7">Base</div>
                  <div class="text-body2">R$ {{ formatCurrency(item.cofinsvbc) }}</div>
                </div>
                <div class="col-4">
                  <div class="text-caption text-grey-7">Alíquota</div>
                  <div class="text-body2">{{ formatDecimal(item.cofinspcofins, 2) }}%</div>
                </div>
                <div class="col-4">
                  <div class="text-caption text-grey-7">Valor</div>
                  <div class="text-body2">R$ {{ formatCurrency(item.cofinsvcofins) }}</div>
                </div>
              </div>
            </q-card-section>

            <q-separator />

            <q-card-section class="q-py-sm">
              <div class="text-caption text-weight-bold q-mb-xs">Outros Valores</div>
              <div class="row q-col-gutter-sm">
                <div class="col-4" v-if="item.vfrete">
                  <div class="text-caption text-grey-7">Frete</div>
                  <div class="text-body2">R$ {{ formatCurrency(item.vfrete) }}</div>
                </div>
                <div class="col-4" v-if="item.vseg">
                  <div class="text-caption text-grey-7">Seguro</div>
                  <div class="text-body2">R$ {{ formatCurrency(item.vseg) }}</div>
                </div>
                <div class="col-4" v-if="item.vdesc">
                  <div class="text-caption text-grey-7">Desconto</div>
                  <div class="text-body2">R$ {{ formatCurrency(item.vdesc) }}</div>
                </div>
                <div class="col-4" v-if="item.voutro">
                  <div class="text-caption text-grey-7">Outras</div>
                  <div class="text-body2">R$ {{ formatCurrency(item.voutro) }}</div>
                </div>
                <div class="col-4" v-if="item.complemento">
                  <div class="text-caption text-grey-7">Complemento</div>
                  <div class="text-body2">R$ {{ formatCurrency(item.complemento) }}</div>
                </div>
                <div class="col-4" v-if="item.margem">
                  <div class="text-caption text-grey-7">Margem</div>
                  <div class="text-body2">{{ formatDecimal(item.margem, 2) }}%</div>
                </div>
              </div>
            </q-card-section>
          </q-card>
        </div>
      </div>

      <!-- Dialog: Informar Detalhes -->
      <q-dialog v-model="showDetalhes">
        <q-card style="min-width: 700px; max-width: 900px">
          <q-form @submit.prevent="salvarDetalhes">
            <q-card-section class="bg-primary text-white">
              <div class="text-body2">
                <q-icon name="edit_note" size="1.5em" class="q-mr-sm" />
                Informar Detalhes
              </div>
            </q-card-section>

          <q-card-section class="q-pb-none">
            <SelectProdutoBarra
              v-model="formDetalhes.codprodutobarra"
              label="Produto"
              :bottom-slots="false"
              dense
            />
            <div class="text-caption text-grey-7 q-mt-xs" v-if="item.xprod">
              Descrição na NFe: {{ item.xprod }}
            </div>
          </q-card-section>

          <q-card-section class="q-py-none">
            <div class="row q-col-gutter-sm">
              <div class="col-6 col-sm-4">
                <div class="text-caption text-grey-7">EAN</div>
                <div class="text-body2">{{ item.cean || '-' }}</div>
              </div>
              <div class="col-6 col-sm-4">
                <div class="text-caption text-grey-7">Referência</div>
                <div class="text-body2">{{ item.cprod }}</div>
              </div>
              <div class="col-6 col-sm-4">
                <div class="text-caption text-grey-7">Total</div>
                <div class="text-body2">R$ {{ formatCurrency(item.vprod) }}</div>
              </div>

              <div class="col-6 col-sm-4">
                <div class="text-caption text-grey-7">EAN Trib</div>
                <div class="text-body2">{{ item.ceantrib || '-' }}</div>
              </div>
              <div class="col-6 col-sm-4">
                <div class="text-caption text-grey-7">NCM</div>
                <div class="text-body2">{{ item.ncm }}</div>
              </div>
              <div class="col-6 col-sm-4">
                <div class="text-caption text-grey-7">IPI Valor</div>
                <div class="text-body2">R$ {{ formatCurrency(item.ipivipi) }}</div>
              </div>

              <div class="col-6 col-sm-4">
                <div class="text-caption text-grey-7">Quantidade / UM</div>
                <div class="text-body2">{{ formatDecimal(item.qcom, 2) }} {{ item.ucom }}</div>
              </div>
              <div class="col-6 col-sm-4">
                <div class="text-caption text-grey-7">CEST</div>
                <div class="text-body2">{{ item.cest || '-' }}</div>
              </div>
              <div class="col-6 col-sm-4">
                <div class="text-caption text-grey-7">ICMS ST Valor</div>
                <div class="text-body2">R$ {{ formatCurrency(item.vicmsst) }}</div>
              </div>

              <div class="col-6 col-sm-4">
                <div class="text-caption text-grey-7">Preço</div>
                <div class="text-body2">{{ formatDecimal(item.vuncom, 2) }}</div>
              </div>
            </div>
          </q-card-section>

          <q-separator class="q-my-md" />

          <q-card-section class="q-py-none">
            <div class="row q-col-gutter-md items-start">
              <div class="col-4">
                <MgInputValor v-model="formDetalhes.margem" label="Margem %" dense />
              </div>
              <div class="col-4">
                <MgInputValor v-model="formDetalhes.complemento" label="Outros Custos" dense />
              </div>
              <div class="col-4">
                <div class="text-caption text-grey-7">Total Custo</div>
                <div class="text-subtitle1 text-weight-bold">
                  R$
                  {{
                    analise?.vcusto !== null && analise?.vcusto !== undefined
                      ? formatCurrency(
                          analise.vcusto -
                            (Number(item.complemento) || 0) +
                            (Number(formDetalhes.complemento) || 0)
                        )
                      : formatCurrency(
                          (item.vprod || 0) +
                            (item.ipivipi || 0) +
                            (item.vicmsst || 0) +
                            (formDetalhes.complemento || 0)
                        )
                  }}
                </div>
              </div>
            </div>
          </q-card-section>

          <q-card-section class="q-pt-md">
            <q-input
              v-model="formDetalhes.observacoes"
              label="Observações"
              outlined
              dense
              maxlength="500"
            />
          </q-card-section>

          <q-separator />

            <q-card-actions align="right">
              <q-btn flat label="Cancelar" v-close-popup />
              <q-btn type="submit" color="primary" label="Salvar" />
            </q-card-actions>
          </q-form>
        </q-card>
      </q-dialog>
    </div>
  </q-page>
</template>
