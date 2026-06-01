<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import { api } from 'src/services/api'
import { notifySuccess, notifyError } from 'src/utils/notify'
import MgAutocomplete from 'src/components/MgAutocomplete.vue'

const route = useRoute()
const $q = useQuasar()
const codproduto = computed(() => route.params.id)

const produto = ref(null)
const loading = ref(true)
const tab = ref('detalhes')

const formataMoeda = (v) =>
  (Number(v) || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
const formataData = (v) => (v ? new Date(v).toLocaleDateString('pt-BR') : '—')
const formataNum = (v) => (Number(v) || 0).toLocaleString('pt-BR', { maximumFractionDigits: 3 })
const abcColor = (abc) => ({ A: 'green-6', B: 'amber-7', C: 'blue-6', D: 'red-6' })[abc] || 'grey-6'
const breadcrumb = (p) =>
  [p.secaoproduto, p.familiaproduto, p.grupoproduto, p.subgrupoproduto, p.marca]
    .filter(Boolean)
    .join(' › ')

async function carregar() {
  loading.value = true
  try {
    const { data } = await api.get(`v1/produto/${codproduto.value}`)
    produto.value = data.data || data
  } catch (e) {
    notifyError(e, 'Erro ao carregar produto')
  } finally {
    loading.value = false
  }
}

// ───────────────────────────── Variações ──────────────────────────────
const dlgVar = ref(false)
const savingVar = ref(false)
const varModel = ref({})
const varNovo = ref(true)

const abrirVarNovo = () => {
  varNovo.value = true
  varModel.value = { variacao: '', codmarca: null, referencia: '', optMarca: null }
  dlgVar.value = true
}
const abrirVarEditar = (v) => {
  varNovo.value = false
  varModel.value = {
    codprodutovariacao: v.codprodutovariacao,
    variacao: v.variacao || '',
    codmarca: v.codmarca,
    referencia: v.referencia || '',
    optMarca: v.codmarca ? { label: v.variacao, value: v.codmarca } : null,
  }
  dlgVar.value = true
}
const salvarVar = async () => {
  savingVar.value = true
  const payload = {
    variacao: varModel.value.variacao || null,
    codmarca: varModel.value.codmarca || null,
    referencia: varModel.value.referencia || null,
  }
  try {
    if (varNovo.value) {
      await api.post(`v1/produto/${codproduto.value}/variacao`, payload)
      notifySuccess('Variação criada')
    } else {
      await api.put(
        `v1/produto/${codproduto.value}/variacao/${varModel.value.codprodutovariacao}`,
        payload,
      )
      notifySuccess('Variação atualizada')
    }
    dlgVar.value = false
    await carregar()
  } catch (e) {
    notifyError(e, 'Erro ao salvar variação')
  } finally {
    savingVar.value = false
  }
}
const excluirVar = (v) => {
  $q.dialog({ title: 'Excluir variação', message: `Excluir "${v.variacao || 'Sem variação'}"?`, cancel: true }).onOk(
    async () => {
      try {
        await api.delete(`v1/produto/${codproduto.value}/variacao/${v.codprodutovariacao}`)
        notifySuccess('Variação excluída')
        await carregar()
      } catch (e) {
        notifyError(e, 'Erro ao excluir')
      }
    },
  )
}
const toggleDescontinuar = async (v) => {
  try {
    if (v.descontinuado) {
      await api.delete(`v1/produto/${codproduto.value}/variacao/${v.codprodutovariacao}/descontinuar`)
      notifySuccess('Variação reativada')
    } else {
      await api.post(`v1/produto/${codproduto.value}/variacao/${v.codprodutovariacao}/descontinuar`)
      notifySuccess('Variação descontinuada')
    }
    await carregar()
  } catch (e) {
    notifyError(e, 'Erro ao alterar')
  }
}

// ─────────────────────────────── Barras ───────────────────────────────
const dlgBarra = ref(false)
const savingBarra = ref(false)
const barraModel = ref({})
const barraNovo = ref(true)

const abrirBarraNovo = (v) => {
  barraNovo.value = true
  barraModel.value = { codprodutovariacao: v.codprodutovariacao, codprodutoembalagem: null, barras: '', referencia: '' }
  dlgBarra.value = true
}
const abrirBarraEditar = (v, b) => {
  barraNovo.value = false
  barraModel.value = {
    codprodutobarra: b.codprodutobarra,
    codprodutovariacao: b.codprodutovariacao,
    codprodutoembalagem: b.codprodutoembalagem,
    barras: b.barras,
    referencia: b.referencia || '',
  }
  dlgBarra.value = true
}
const embOptions = computed(() =>
  (produto.value?.ProdutoEmbalagemS || []).map((e) => ({
    label: `C/${formataNum(e.quantidade)}`,
    value: e.codprodutoembalagem,
  })),
)
const salvarBarra = async () => {
  savingBarra.value = true
  const payload = {
    codprodutovariacao: barraModel.value.codprodutovariacao,
    codprodutoembalagem: barraModel.value.codprodutoembalagem || null,
    barras: barraModel.value.barras || null,
    referencia: barraModel.value.referencia || null,
  }
  try {
    if (barraNovo.value) {
      await api.post(`v1/produto/${codproduto.value}/barra`, payload)
      notifySuccess('Código de barras criado')
    } else {
      await api.put(`v1/produto/${codproduto.value}/barra/${barraModel.value.codprodutobarra}`, payload)
      notifySuccess('Código de barras atualizado')
    }
    dlgBarra.value = false
    await carregar()
  } catch (e) {
    notifyError(e, 'Erro ao salvar código de barras')
  } finally {
    savingBarra.value = false
  }
}
const excluirBarra = (b) => {
  $q.dialog({ title: 'Excluir', message: `Excluir o código ${b.barras}?`, cancel: true }).onOk(async () => {
    try {
      await api.delete(`v1/produto/${codproduto.value}/barra/${b.codprodutobarra}`)
      notifySuccess('Código excluído')
      await carregar()
    } catch (e) {
      notifyError(e, 'Erro ao excluir')
    }
  })
}

// ───────────────────────────── Embalagens ─────────────────────────────
const dlgEmb = ref(false)
const savingEmb = ref(false)
const embModel = ref({})
const embNovo = ref(true)
const unidades = ref([])

const abrirEmbNovo = () => {
  embNovo.value = true
  embModel.value = { codunidademedida: produto.value.codunidademedida, quantidade: null, preco: null, optUnid: produto.value.codunidademedida ? { label: produto.value.unidademedida, value: produto.value.codunidademedida } : null }
  dlgEmb.value = true
}
const abrirEmbEditar = (e) => {
  embNovo.value = false
  embModel.value = {
    codprodutoembalagem: e.codprodutoembalagem,
    codunidademedida: e.codunidademedida,
    quantidade: e.quantidade,
    preco: e.preco,
    optUnid: { label: e.unidademedida || '', value: e.codunidademedida },
  }
  dlgEmb.value = true
}
const salvarEmb = async () => {
  savingEmb.value = true
  const payload = {
    codunidademedida: embModel.value.codunidademedida,
    quantidade: embModel.value.quantidade,
    preco: embModel.value.preco || null,
  }
  try {
    if (embNovo.value) {
      await api.post(`v1/produto/${codproduto.value}/embalagem`, payload)
      notifySuccess('Embalagem criada')
    } else {
      await api.put(`v1/produto/${codproduto.value}/embalagem/${embModel.value.codprodutoembalagem}`, payload)
      notifySuccess('Embalagem atualizada')
    }
    dlgEmb.value = false
    await carregar()
  } catch (e) {
    notifyError(e, 'Erro ao salvar embalagem')
  } finally {
    savingEmb.value = false
  }
}
const excluirEmb = (e) => {
  $q.dialog({ title: 'Excluir embalagem', message: `Excluir embalagem C/${formataNum(e.quantidade)}?`, cancel: true }).onOk(
    async () => {
      try {
        await api.delete(`v1/produto/${codproduto.value}/embalagem/${e.codprodutoembalagem}`)
        notifySuccess('Embalagem excluída')
        await carregar()
      } catch (err) {
        notifyError(err, 'Erro ao excluir')
      }
    },
  )
}

// ──────────────────────── Abas de leitura (lazy) ──────────────────────
const estoque = ref(null)
const negocios = ref(null)
const notas = ref(null)
const compras = ref(null)
const mercos = ref(null)
const woo = ref(null)
const loadingTab = ref(false)

async function carregarAba(nome) {
  if (nome === 'estoque' && !estoque.value) await fetchInto('estoque', estoque)
  if (nome === 'negocios' && !negocios.value) await fetchInto('negocios', negocios)
  if (nome === 'notas' && !notas.value) await fetchInto('notas', notas)
  if (nome === 'compras' && !compras.value) await fetchInto('compras', compras)
  if (nome === 'mercos' && !mercos.value) await fetchInto('mercos', mercos)
  if (nome === 'woo' && !woo.value) await fetchInto('woo', woo)
}
async function fetchInto(path, target) {
  loadingTab.value = true
  try {
    const { data } = await api.get(`v1/produto/${codproduto.value}/${path}`)
    target.value = data
  } catch (e) {
    notifyError(e, `Erro ao carregar ${path}`)
    target.value = []
  } finally {
    loadingTab.value = false
  }
}

const exportarMercos = async () => {
  try {
    await api.post(`v1/produto/${codproduto.value}/mercos/exportar`)
    notifySuccess('Exportação ao Mercos disparada')
    mercos.value = null
    await carregarAba('mercos')
  } catch (e) {
    notifyError(e, 'Erro ao exportar ao Mercos')
  }
}
const exportarWoo = async () => {
  try {
    await api.post(`v1/woo/produto/${codproduto.value}/exportar`)
    notifySuccess('Exportação ao WooCommerce disparada')
    woo.value = null
    await carregarAba('woo')
  } catch (e) {
    notifyError(e, 'Erro ao exportar ao WooCommerce')
  }
}

onMounted(async () => {
  await carregar()
  const { data } = await api.get('v1/unidade-medida/autocompletar', { params: { unidademedida: '' } })
  unidades.value = data
})
</script>

<template>
  <q-page class="q-pa-md">
    <div v-if="loading" class="row justify-center q-my-xl">
      <q-spinner-dots color="primary" size="40px" />
    </div>

    <div v-else-if="produto" style="max-width: 1280px; margin: auto">
      <!-- Cabeçalho -->
      <q-card bordered flat class="q-mb-md">
        <q-item>
          <q-item-section side>
            <q-btn flat round dense icon="arrow_back" :to="{ name: 'produto' }" />
          </q-item-section>
          <q-item-section avatar>
            <q-avatar rounded size="72px" color="grey-3" text-color="grey-7">
              <img v-if="produto.url" :src="produto.url" />
              <q-icon v-else name="inventory_2" />
            </q-avatar>
          </q-item-section>
          <q-item-section>
            <q-item-label class="row items-center q-gutter-xs">
              <q-badge :color="abcColor(produto.abc)" :label="produto.abc" />
              <span class="text-caption text-grey-6">#{{ String(produto.codproduto).padStart(6, '0') }}</span>
              <q-badge v-if="produto.inativo" color="orange-7" label="Inativo" />
            </q-item-label>
            <q-item-label class="text-h6">{{ produto.produto }}</q-item-label>
            <q-item-label caption>{{ breadcrumb(produto) }}</q-item-label>
          </q-item-section>
          <q-item-section side>
            <div class="row items-center q-gutter-xs">
              <div class="text-h6 text-primary">{{ formataMoeda(produto.preco) }}</div>
              <q-btn
                flat
                round
                dense
                color="grey-7"
                icon="edit"
                :to="{ name: 'produto-editar', params: { id: produto.codproduto } }"
              >
                <q-tooltip>Editar</q-tooltip>
              </q-btn>
              <q-btn
                flat
                round
                dense
                color="grey-7"
                icon="content_copy"
                :to="{ name: 'produto-novo', query: { duplicar: produto.codproduto } }"
              >
                <q-tooltip>Duplicar</q-tooltip>
              </q-btn>
            </div>
          </q-item-section>
        </q-item>
      </q-card>

      <q-card bordered flat>
        <q-tabs
          v-model="tab"
          align="left"
          dense
          inline-label
          class="bg-grey-1 text-grey-8"
          active-color="primary"
          indicator-color="primary"
          @update:model-value="carregarAba"
        >
          <q-tab name="detalhes" icon="info" label="Detalhes" />
          <q-tab name="estoque" icon="inventory" label="Estoque" />
          <q-tab name="ncm" icon="receipt_long" label="NCM" />
          <q-tab name="negocios" icon="point_of_sale" label="Negócios" />
          <q-tab name="notas" icon="description" label="Notas Fiscais" />
          <q-tab name="compras" icon="shopping_cart" label="Compras" />
          <q-tab name="mercos" icon="hub" label="Mercos" />
          <q-tab name="woo" icon="storefront" label="WooCommerce" />
        </q-tabs>

        <q-separator />

        <q-tab-panels v-model="tab" animated>
          <!-- ───────────── Detalhes ───────────── -->
          <q-tab-panel name="detalhes">
            <div class="row q-col-gutter-md">
              <!-- Variações -->
              <div class="col-12 col-md-8">
                <div class="text-overline text-grey-8 row items-center">
                  VARIAÇÕES E CÓDIGOS DE BARRAS
                  <q-space />
                  <q-btn flat round dense size="sm" color="primary" icon="add" @click="abrirVarNovo">
                    <q-tooltip>Nova variação</q-tooltip>
                  </q-btn>
                </div>
                <q-list bordered separator class="rounded-borders">
                  <q-expansion-item
                    v-for="v in produto.ProdutoVariacaoS"
                    :key="v.codprodutovariacao"
                    default-opened
                    :label="v.variacao || '{Sem variação}'"
                    :caption="v.referencia ? `ref ${v.referencia}` : ''"
                    icon="style"
                  >
                    <template #header>
                      <q-item-section avatar><q-icon name="style" /></q-item-section>
                      <q-item-section>
                        <q-item-label
                          :class="v.descontinuado ? 'text-strike text-grey-5' : ''"
                        >
                          {{ v.variacao || '{Sem variação}' }}
                        </q-item-label>
                        <q-item-label caption v-if="v.referencia">ref {{ v.referencia }}</q-item-label>
                      </q-item-section>
                      <q-item-section side>
                        <div class="row no-wrap">
                          <q-btn flat dense round size="sm" color="grey-7" icon="add" @click.stop="abrirBarraNovo(v)">
                            <q-tooltip>Nova barra</q-tooltip>
                          </q-btn>
                          <q-btn flat dense round size="sm" color="grey-7" icon="edit" @click.stop="abrirVarEditar(v)">
                            <q-tooltip>Editar variação</q-tooltip>
                          </q-btn>
                          <q-btn
                            flat
                            dense
                            round
                            size="sm"
                            color="grey-7"
                            :icon="v.descontinuado ? 'play_arrow' : 'block'"
                            @click.stop="toggleDescontinuar(v)"
                          >
                            <q-tooltip>{{ v.descontinuado ? 'Reativar' : 'Descontinuar' }}</q-tooltip>
                          </q-btn>
                          <q-btn flat dense round size="sm" color="grey-7" icon="delete" @click.stop="excluirVar(v)">
                            <q-tooltip>Excluir variação</q-tooltip>
                          </q-btn>
                        </div>
                      </q-item-section>
                    </template>

                    <q-list>
                      <q-item v-for="b in v.ProdutoBarraS" :key="b.codprodutobarra" class="q-pl-xl">
                        <q-item-section avatar><q-icon name="qr_code_2" color="grey-6" /></q-item-section>
                        <q-item-section>
                          <q-item-label>{{ b.barras }}</q-item-label>
                          <q-item-label caption v-if="b.codprodutoembalagem">
                            embalagem #{{ b.codprodutoembalagem }}
                          </q-item-label>
                        </q-item-section>
                        <q-item-section side>
                          <div class="row no-wrap">
                            <q-btn flat dense round size="sm" color="grey-7" icon="edit" @click="abrirBarraEditar(v, b)" />
                            <q-btn flat dense round size="sm" color="grey-7" icon="delete" @click="excluirBarra(b)" />
                          </div>
                        </q-item-section>
                      </q-item>
                    </q-list>
                  </q-expansion-item>
                </q-list>
              </div>

              <!-- Embalagens + dados -->
              <div class="col-12 col-md-4">
                <div class="text-overline text-grey-8 row items-center">
                  EMBALAGENS
                  <q-space />
                  <q-btn flat round dense size="sm" color="primary" icon="add" @click="abrirEmbNovo">
                    <q-tooltip>Nova embalagem</q-tooltip>
                  </q-btn>
                </div>
                <q-list bordered separator class="rounded-borders q-mb-md">
                  <q-item v-if="!produto.ProdutoEmbalagemS || !produto.ProdutoEmbalagemS.length">
                    <q-item-section class="text-grey-6">Nenhuma embalagem</q-item-section>
                  </q-item>
                  <q-item v-for="e in produto.ProdutoEmbalagemS" :key="e.codprodutoembalagem">
                    <q-item-section>
                      <q-item-label>C/{{ formataNum(e.quantidade) }}</q-item-label>
                      <q-item-label caption>
                        {{ e.preco ? formataMoeda(e.preco) : formataMoeda(produto.preco * e.quantidade) + ' (calc)' }}
                      </q-item-label>
                    </q-item-section>
                    <q-item-section side>
                      <div class="row no-wrap">
                        <q-btn flat dense round size="sm" color="grey-7" icon="edit" @click="abrirEmbEditar(e)" />
                        <q-btn flat dense round size="sm" color="grey-7" icon="delete" @click="excluirEmb(e)" />
                      </div>
                    </q-item-section>
                  </q-item>
                </q-list>

                <q-list bordered class="rounded-borders">
                  <q-item>
                    <q-item-section>Tipo</q-item-section>
                    <q-item-section side>{{ produto.tipoproduto }}</q-item-section>
                  </q-item>
                  <q-separator />
                  <q-item>
                    <q-item-section>Tributação</q-item-section>
                    <q-item-section side>{{ produto.tributacao }}</q-item-section>
                  </q-item>
                  <q-separator />
                  <q-item>
                    <q-item-section>Controla estoque</q-item-section>
                    <q-item-section side>
                      <q-icon :name="produto.estoque ? 'check' : 'close'" :color="produto.estoque ? 'green' : 'grey'" />
                    </q-item-section>
                  </q-item>
                </q-list>

                <div v-if="produto.observacoes" class="q-mt-md text-body2 text-grey-7">
                  <div class="text-overline text-grey-8">OBSERVAÇÕES</div>
                  {{ produto.observacoes }}
                </div>
              </div>
            </div>
          </q-tab-panel>

          <!-- ───────────── Estoque ───────────── -->
          <q-tab-panel name="estoque">
            <q-inner-loading :showing="loadingTab"><q-spinner-dots color="primary" /></q-inner-loading>
            <div v-if="estoque && estoque.length">
              <q-card v-for="loc in estoque" :key="loc.codestoquelocal" bordered flat class="q-mb-md">
                <q-card-section class="row items-center bg-grey-1">
                  <div class="text-subtitle2">{{ loc.estoquelocal }}</div>
                  <q-space />
                  <div class="text-caption">
                    Físico: <b>{{ formataNum(loc.fisico.saldoquantidade) }}</b> ·
                    Fiscal: <b>{{ formataNum(loc.fiscal.saldoquantidade) }}</b>
                  </div>
                </q-card-section>
                <q-markup-table flat dense>
                  <thead>
                    <tr>
                      <th class="text-left">Variação</th>
                      <th class="text-right">Físico</th>
                      <th class="text-right">Fiscal</th>
                      <th class="text-right">Mín/Máx</th>
                      <th class="text-left">Localização</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="vr in loc.variacoes" :key="vr.codprodutovariacao">
                      <td>{{ vr.variacao || '{Sem variação}' }}</td>
                      <td class="text-right">{{ formataNum(vr.fisico.saldoquantidade) }}</td>
                      <td class="text-right">{{ formataNum(vr.fiscal.saldoquantidade) }}</td>
                      <td class="text-right">{{ vr.estoqueminimo || 0 }}/{{ vr.estoquemaximo || 0 }}</td>
                      <td>{{ [vr.corredor, vr.prateleira, vr.coluna, vr.bloco].filter(Boolean).join('-') || '—' }}</td>
                    </tr>
                  </tbody>
                </q-markup-table>
              </q-card>
            </div>
            <div v-else-if="!loadingTab" class="text-grey-6 q-pa-md text-center">Sem saldo de estoque</div>
          </q-tab-panel>

          <!-- ───────────── NCM ───────────── -->
          <q-tab-panel name="ncm">
            <q-list bordered separator class="rounded-borders">
              <q-item>
                <q-item-section>NCM</q-item-section>
                <q-item-section side>{{ produto.Ncm?.ncm }} — {{ produto.Ncm?.descricao }}</q-item-section>
              </q-item>
              <q-item>
                <q-item-section>CEST</q-item-section>
                <q-item-section side>{{ produto.Cest?.cest || '—' }}</q-item-section>
              </q-item>
              <q-item>
                <q-item-section>Tributação</q-item-section>
                <q-item-section side>{{ produto.tributacao }}</q-item-section>
              </q-item>
            </q-list>
          </q-tab-panel>

          <!-- ───────────── Negócios / Notas / Compras ───────────── -->
          <q-tab-panel name="negocios">
            <q-inner-loading :showing="loadingTab"><q-spinner-dots color="primary" /></q-inner-loading>
            <q-markup-table v-if="negocios" flat dense>
              <thead>
                <tr>
                  <th class="text-left">Negócio</th>
                  <th class="text-left">Data</th>
                  <th class="text-left">Pessoa</th>
                  <th class="text-left">Operação</th>
                  <th class="text-right">Qtd</th>
                  <th class="text-right">Unit.</th>
                  <th class="text-right">Total</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="n in negocios" :key="n.codnegocioprodutobarra">
                  <td>{{ n.codnegocio }}</td>
                  <td>{{ formataData(n.lancamento) }}</td>
                  <td>{{ n.pessoa }}</td>
                  <td>{{ n.naturezaoperacao }}</td>
                  <td class="text-right">{{ formataNum(n.quantidade) }}</td>
                  <td class="text-right">{{ formataMoeda(n.valorunitario) }}</td>
                  <td class="text-right">{{ formataMoeda(n.valortotal) }}</td>
                </tr>
                <tr v-if="!negocios.length"><td colspan="7" class="text-center text-grey-6">Nenhum negócio</td></tr>
              </tbody>
            </q-markup-table>
          </q-tab-panel>

          <q-tab-panel name="notas">
            <q-inner-loading :showing="loadingTab"><q-spinner-dots color="primary" /></q-inner-loading>
            <q-markup-table v-if="notas" flat dense>
              <thead>
                <tr>
                  <th class="text-left">NF</th>
                  <th class="text-left">Saída</th>
                  <th class="text-left">Pessoa</th>
                  <th class="text-left">Operação</th>
                  <th class="text-right">Qtd</th>
                  <th class="text-right">Total</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="n in notas" :key="n.codnotafiscalprodutobarra">
                  <td>{{ n.numero }}/{{ n.serie }}</td>
                  <td>{{ formataData(n.saida) }}</td>
                  <td>{{ n.pessoa }}</td>
                  <td>{{ n.naturezaoperacao }}</td>
                  <td class="text-right">{{ formataNum(n.quantidade) }}</td>
                  <td class="text-right">{{ formataMoeda(n.valortotal) }}</td>
                </tr>
                <tr v-if="!notas.length"><td colspan="6" class="text-center text-grey-6">Nenhuma nota</td></tr>
              </tbody>
            </q-markup-table>
          </q-tab-panel>

          <q-tab-panel name="compras">
            <q-inner-loading :showing="loadingTab"><q-spinner-dots color="primary" /></q-inner-loading>
            <q-markup-table v-if="compras" flat dense>
              <thead>
                <tr>
                  <th class="text-left">Emissão</th>
                  <th class="text-left">Produto (NF)</th>
                  <th class="text-right">Qtd</th>
                  <th class="text-right">Custo unit.</th>
                  <th class="text-right">Custo total</th>
                  <th class="text-right">Margem</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="c in compras" :key="c.codnfeterceiroitem">
                  <td>{{ formataData(c.emissao) }}</td>
                  <td>{{ c.xprod }}</td>
                  <td class="text-right">{{ formataNum(c.quantidadetotal) }}</td>
                  <td class="text-right">{{ formataMoeda(c.vuncom) }}</td>
                  <td class="text-right">{{ formataMoeda(c.valortotal) }}</td>
                  <td class="text-right">{{ c.margem ? Number(c.margem).toFixed(1) + '%' : '—' }}</td>
                </tr>
                <tr v-if="!compras.length"><td colspan="6" class="text-center text-grey-6">Nenhuma compra</td></tr>
              </tbody>
            </q-markup-table>
          </q-tab-panel>

          <!-- ───────────── Mercos ───────────── -->
          <q-tab-panel name="mercos">
            <div class="row items-center q-mb-sm">
              <div class="text-overline text-grey-8">INTEGRAÇÃO MERCOS</div>
              <q-space />
              <q-btn unelevated color="primary" icon="cloud_upload" label="Exportar ao Mercos" @click="exportarMercos" />
            </div>
            <q-inner-loading :showing="loadingTab"><q-spinner-dots color="primary" /></q-inner-loading>
            <q-markup-table v-if="mercos" flat dense>
              <thead>
                <tr>
                  <th class="text-left">Variação</th>
                  <th class="text-left">Embalagem</th>
                  <th class="text-left">ID Mercos</th>
                  <th class="text-right">Preço</th>
                  <th class="text-right">Saldo</th>
                  <th class="text-left">Situação</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="m in mercos" :key="m.codmercosproduto">
                  <td>{{ m.variacao || '{Sem variação}' }}</td>
                  <td>{{ m.embalagem ? 'C/' + formataNum(m.embalagem) : 'Unidade' }}</td>
                  <td>{{ m.produtoid || '—' }}</td>
                  <td class="text-right">{{ formataMoeda(m.preco) }}</td>
                  <td class="text-right">{{ formataNum(m.saldoquantidade) }}</td>
                  <td>
                    <q-badge :color="m.inativo ? 'grey-5' : 'green-6'" :label="m.inativo ? 'Inativo' : 'Ativo'" />
                  </td>
                </tr>
                <tr v-if="!mercos.length"><td colspan="6" class="text-center text-grey-6">Não exportado ao Mercos</td></tr>
              </tbody>
            </q-markup-table>
          </q-tab-panel>

          <!-- ───────────── WooCommerce ───────────── -->
          <q-tab-panel name="woo">
            <div class="row items-center q-mb-sm">
              <div class="text-overline text-grey-8">INTEGRAÇÃO WOOCOMMERCE</div>
              <q-space />
              <q-btn unelevated color="primary" icon="cloud_upload" label="Exportar ao Woo" @click="exportarWoo" />
            </div>
            <q-inner-loading :showing="loadingTab"><q-spinner-dots color="primary" /></q-inner-loading>
            <q-markup-table v-if="woo" flat dense>
              <thead>
                <tr>
                  <th class="text-left">Variação</th>
                  <th class="text-left">ID</th>
                  <th class="text-left">Integração</th>
                  <th class="text-left">Exportado</th>
                  <th class="text-left">Situação</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="w in woo" :key="w.codwooproduto">
                  <td>{{ w.variacao || '{Sem variação}' }}</td>
                  <td>{{ w.id }}{{ w.idvariation ? ' / ' + w.idvariation : '' }}</td>
                  <td>{{ w.integracao === 'P' ? 'Parcial' : 'Completa' }}</td>
                  <td>{{ formataData(w.exportacao) }}</td>
                  <td>
                    <q-badge :color="w.inativo ? 'grey-5' : 'green-6'" :label="w.inativo ? 'Inativo' : 'Ativo'" />
                  </td>
                </tr>
                <tr v-if="!woo.length"><td colspan="5" class="text-center text-grey-6">Não exportado ao Woo</td></tr>
              </tbody>
            </q-markup-table>
          </q-tab-panel>
        </q-tab-panels>
      </q-card>
    </div>

    <!-- Dialog Variação -->
    <q-dialog v-model="dlgVar">
      <q-card bordered flat style="width: 460px; max-width: 90vw">
        <q-card-section class="text-grey-9 text-overline">{{ varNovo ? 'NOVA VARIAÇÃO' : 'EDITAR VARIAÇÃO' }}</q-card-section>
        <q-form @submit.prevent="salvarVar">
          <q-separator inset />
          <q-card-section class="q-gutter-md">
            <q-input v-model="varModel.variacao" outlined label="Variação (vazio = sem variação)" autofocus />
            <MgAutocomplete
              v-model="varModel.codmarca"
              endpoint="v1/marca/autocompletar"
              search-param="marca"
              label="Marca específica (opcional)"
              :initial-option="varModel.optMarca"
            />
            <q-input v-model="varModel.referencia" outlined label="Referência" maxlength="50" />
          </q-card-section>
          <q-separator inset />
          <q-card-actions align="right">
            <q-btn flat label="Cancelar" color="grey-8" v-close-popup />
            <q-btn flat label="Salvar" type="submit" :loading="savingVar" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <!-- Dialog Barra -->
    <q-dialog v-model="dlgBarra">
      <q-card bordered flat style="width: 460px; max-width: 90vw">
        <q-card-section class="text-grey-9 text-overline">{{ barraNovo ? 'NOVO CÓDIGO' : 'EDITAR CÓDIGO' }}</q-card-section>
        <q-form @submit.prevent="salvarBarra">
          <q-separator inset />
          <q-card-section class="q-gutter-md">
            <q-input
              v-model="barraModel.barras"
              outlined
              label="Código de barras (vazio = gera interno)"
              autofocus
            />
            <q-select
              v-model="barraModel.codprodutoembalagem"
              :options="embOptions"
              emit-value
              map-options
              outlined
              clearable
              label="Embalagem (opcional)"
            />
            <q-input v-model="barraModel.referencia" outlined label="Referência" maxlength="50" />
          </q-card-section>
          <q-separator inset />
          <q-card-actions align="right">
            <q-btn flat label="Cancelar" color="grey-8" v-close-popup />
            <q-btn flat label="Salvar" type="submit" :loading="savingBarra" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <!-- Dialog Embalagem -->
    <q-dialog v-model="dlgEmb">
      <q-card bordered flat style="width: 460px; max-width: 90vw">
        <q-card-section class="text-grey-9 text-overline">{{ embNovo ? 'NOVA EMBALAGEM' : 'EDITAR EMBALAGEM' }}</q-card-section>
        <q-form @submit.prevent="salvarEmb">
          <q-separator inset />
          <q-card-section class="q-gutter-md">
            <q-input
              v-model.number="embModel.quantidade"
              outlined
              type="number"
              label="Quantidade"
              :rules="[(v) => v > 0 || 'Maior que zero']"
            />
            <q-select
              v-model="embModel.codunidademedida"
              :options="unidades"
              emit-value
              map-options
              outlined
              label="Unidade"
              :rules="[(v) => !!v || 'Obrigatório']"
            />
            <q-input
              v-model.number="embModel.preco"
              outlined
              type="number"
              step="0.01"
              prefix="R$"
              label="Preço (vazio = calculado)"
            />
          </q-card-section>
          <q-separator inset />
          <q-card-actions align="right">
            <q-btn flat label="Cancelar" color="grey-8" v-close-popup />
            <q-btn flat label="Salvar" type="submit" :loading="savingEmb" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-page>
</template>
