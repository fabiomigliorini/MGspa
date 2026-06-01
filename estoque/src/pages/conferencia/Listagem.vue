<script setup>
import { ref, computed, nextTick, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { api } from 'src/services/api'
import { useConferenciaStore } from 'src/stores/conferenciaStore'
import { goBack } from 'src/utils/goBack'
import { notifySuccess, notifyError } from 'src/utils/notify'

const route = useRoute()
const router = useRouter()
const $q = useQuasar()
const store = useConferenciaStore()

const aba = ref('aconferir')

const dialogProduto = ref(false)
const produto = ref(null)
const carregandoProduto = ref(false)

const dialogConferencia = ref(false)
const salvandoConf = ref(false)
const conf = ref(novaConf())

const dialogBarras = ref(false)
const barrasBusca = ref('')
const barrasRef = ref(null)

function novaConf() {
  return {
    quantidade: null,
    custo: null,
    vencimento: '',
    corredor: null,
    prateleira: null,
    coluna: null,
    bloco: null,
    observacoes: '',
  }
}

const tituloLocal = computed(
  () => store.setup.estoquelocal || `Local #${store.setup.codestoquelocal}`,
)

const codFormatado = (v) => '#' + String(v).padStart(6, '0')
const formataMoeda = (v) =>
  (Number(v) || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
const formataData = (v) => (v ? new Date(v).toLocaleDateString('pt-BR') : '—')
const formataDataHora = (v) => (v ? new Date(v).toLocaleString('pt-BR') : '—')

const corSaldo = (q) => (q > 0 ? 'green-6' : q < 0 ? 'red-6' : 'grey-5')

const mudarAba = (val) => {
  aba.value = val
  store.setConferidos(val === 'conferidos')
  store.fetchListagem(true)
}

const carregarMais = async (index, done) => {
  await store.fetchListagem(false)
  done(!store.hasMore)
}

const abrirProduto = async (codprodutovariacao) => {
  carregandoProduto.value = true
  dialogProduto.value = true
  produto.value = null
  try {
    const { data } = await api.get('v1/estoque-saldo-conferencia/busca-produto', {
      params: {
        codprodutovariacao,
        codestoquelocal: store.setup.codestoquelocal,
        fiscal: store.setup.fiscal,
      },
    })
    produto.value = data
  } catch (e) {
    dialogProduto.value = false
    notifyError(e, 'Erro ao carregar produto')
  } finally {
    carregandoProduto.value = false
  }
}

const focarBarras = async () => {
  await nextTick()
  barrasRef.value?.focus()
}

const abrirDialogBarras = () => {
  barrasBusca.value = ''
  dialogBarras.value = true
  focarBarras()
}

const buscarPorBarras = async () => {
  const cod = (barrasBusca.value || '').trim()
  if (!cod) return
  try {
    const { data } = await api.get('v1/estoque-saldo-conferencia/busca-produto', {
      params: { barras: cod, codestoquelocal: store.setup.codestoquelocal, fiscal: store.setup.fiscal },
    })
    if (data.erro) {
      notifyError(null, data.mensagem || 'Código de barras não encontrado')
      return
    }
    produto.value = data
    dialogBarras.value = false
    dialogProduto.value = true
  } catch (e) {
    notifyError(e, 'Código de barras não encontrado')
  }
}

const abrirConferencia = () => {
  conf.value = {
    quantidade: null,
    custo: produto.value?.saldoatual?.custo ?? null,
    vencimento: produto.value?.localizacao?.vencimento
      ? String(produto.value.localizacao.vencimento).slice(0, 10)
      : '',
    corredor: produto.value?.localizacao?.corredor ?? null,
    prateleira: produto.value?.localizacao?.prateleira ?? null,
    coluna: produto.value?.localizacao?.coluna ?? null,
    bloco: produto.value?.localizacao?.bloco ?? null,
    observacoes: '',
  }
  dialogConferencia.value = true
}

const salvarConferencia = async () => {
  if (conf.value.quantidade === null || conf.value.quantidade === '') {
    notifyError(null, 'Informe a quantidade conferida')
    return
  }
  salvandoConf.value = true
  try {
    const { data } = await api.post('v1/estoque-saldo-conferencia', {
      codprodutovariacao: produto.value.variacao.codprodutovariacao,
      codestoquelocal: store.setup.codestoquelocal,
      fiscal: store.setup.fiscal,
      quantidadeinformada: conf.value.quantidade,
      customedioinformado: conf.value.custo ?? 0,
      data: store.setup.data,
      observacoes: conf.value.observacoes || null,
      vencimento: conf.value.vencimento || null,
      corredor: conf.value.corredor,
      prateleira: conf.value.prateleira,
      coluna: conf.value.coluna,
      bloco: conf.value.bloco,
    })
    produto.value = data
    notifySuccess('Conferência realizada')
    dialogConferencia.value = false
    store.fetchListagem(true)
  } catch (e) {
    notifyError(e, 'Erro ao salvar conferência')
  } finally {
    salvandoConf.value = false
  }
}

const zerar = () => {
  $q.dialog({
    title: 'Zerar saldo',
    message: 'Confirma zerar o saldo deste produto neste local?',
    cancel: true,
  }).onOk(async () => {
    try {
      const { data } = await api.post('v1/estoque-saldo-conferencia/zerar-produto', {
        codprodutovariacao: produto.value.variacao.codprodutovariacao,
        codestoquelocal: store.setup.codestoquelocal,
        fiscal: store.setup.fiscal,
        data: store.setup.data,
      })
      produto.value = data
      notifySuccess('Saldo zerado')
      store.fetchListagem(true)
    } catch (e) {
      notifyError(e, 'Erro ao zerar saldo')
    }
  })
}

const excluirConferencia = (id) => {
  $q.dialog({
    title: 'Excluir conferência',
    message: 'Confirma excluir este lançamento de conferência?',
    cancel: true,
  }).onOk(async () => {
    try {
      const { data } = await api.post(`v1/estoque-saldo-conferencia/${id}/inativo`)
      produto.value = data
      notifySuccess('Conferência excluída')
      store.fetchListagem(true)
    } catch (e) {
      notifyError(e, 'Erro ao excluir conferência')
    }
  })
}

onMounted(() => {
  store.aplicarParamsRota(route.params)
  store.fetchListagem(true)
})
</script>

<template>
  <q-page>
    <div class="q-pa-md" style="margin: auto; max-width: 1086px">
      <!-- Cabeçalho -->
      <div class="row items-center q-mb-sm">
        <q-btn flat round dense icon="arrow_back" @click="goBack(router)" />
        <div class="q-ml-sm">
          <div class="text-h6">{{ tituloLocal }}</div>
          <div class="text-caption text-grey-7">
            {{ store.setup.fiscal ? 'Fiscal' : 'Físico' }}
            <span v-if="store.setup.marca"> · {{ store.setup.marca }}</span>
            <span v-if="store.setup.conferenciaperiodica"> · Periódica</span>
          </div>
        </div>
      </div>

      <q-tabs
        v-model="aba"
        class="text-primary"
        active-color="primary"
        indicator-color="primary"
        align="left"
        narrow-indicator
        @update:model-value="mudarAba"
      >
        <q-tab name="aconferir" label="A conferir" />
        <q-tab name="conferidos" label="Já conferidos" />
      </q-tabs>
      <q-separator />

      <q-infinite-scroll @load="carregarMais" :offset="250" class="q-mt-md">
        <div
          v-if="store.produtos.length === 0 && !store.loading"
          class="text-center text-grey-6 q-pa-xl"
        >
          Nenhum produto nesta lista
        </div>

        <q-list bordered separator class="bg-white rounded-borders">
          <q-item
            v-for="item in store.produtos"
            :key="item.codprodutovariacao"
            clickable
            @click="abrirProduto(item.codprodutovariacao)"
          >
            <q-item-section avatar>
              <q-avatar rounded size="48px" color="grey-3" text-color="grey-7">
                <img v-if="item.imagem" :src="item.imagem" />
                <q-icon v-else name="inventory_2" />
              </q-avatar>
            </q-item-section>
            <q-item-section>
              <q-item-label class="ellipsis">
                {{ item.produto }}
                <span v-if="item.variacao" class="text-grey-7">— {{ item.variacao }}</span>
              </q-item-label>
              <q-item-label caption>
                {{ codFormatado(item.codproduto) }}
                <q-badge
                  v-if="item.inativo"
                  color="orange-7"
                  class="q-ml-xs"
                  label="Inativo"
                />
              </q-item-label>
              <q-item-label caption>
                Última conferência: {{ formataData(item.ultimaconferencia) }}
              </q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-badge :color="corSaldo(item.saldoquantidade)" class="text-body2">
                {{ item.saldoquantidade }}
              </q-badge>
            </q-item-section>
          </q-item>
        </q-list>

        <template #loading>
          <div class="row justify-center q-my-md">
            <q-spinner-dots color="primary" size="32px" />
          </div>
        </template>
      </q-infinite-scroll>
    </div>

    <!-- FAB busca por código de barras -->
    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn fab icon="qr_code_scanner" color="primary" @click="abrirDialogBarras">
        <q-tooltip anchor="center left" self="center right">Buscar por código de barras</q-tooltip>
      </q-btn>
    </q-page-sticky>

    <!-- Dialog busca por barras -->
    <q-dialog v-model="dialogBarras">
      <q-card bordered flat style="width: 400px; max-width: 90vw">
        <q-card-section class="text-grey-9 text-overline">BUSCAR POR CÓDIGO DE BARRAS</q-card-section>
        <q-separator inset />
        <q-card-section>
          <q-input
            ref="barrasRef"
            v-model="barrasBusca"
            outlined
            label="Código de barras"
            autofocus
            @keyup.enter="buscarPorBarras"
          >
            <template #prepend><q-icon name="qr_code_2" /></template>
          </q-input>
        </q-card-section>
        <q-separator inset />
        <q-card-actions align="right" class="text-primary">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn flat label="Buscar" @click="buscarPorBarras" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Dialog detalhe do produto -->
    <q-dialog v-model="dialogProduto">
      <q-card bordered flat style="width: 720px; max-width: 95vw">
        <q-card-section class="row items-center">
          <div v-if="produto" class="text-h6 ellipsis">
            {{ produto.produto.produto }}
            <span v-if="produto.variacao.variacao" class="text-grey-7">
              — {{ produto.variacao.variacao }}
            </span>
          </div>
          <q-space />
          <q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>

        <q-separator />

        <div v-if="carregandoProduto" class="row justify-center q-my-xl">
          <q-spinner-dots color="primary" size="40px" />
        </div>

        <q-card-section v-else-if="produto" style="max-height: 65vh" class="scroll">
          <div class="row q-col-gutter-md">
            <div class="col-12 col-sm-6">
              <div class="text-caption text-grey-6">Saldo atual</div>
              <div class="text-h5" :class="'text-' + corSaldo(produto.saldoatual.quantidade)">
                {{ produto.saldoatual.quantidade }} {{ produto.produto.siglaunidademedida }}
              </div>
              <div class="text-caption text-grey-7">
                Custo médio: {{ formataMoeda(produto.saldoatual.custo) }}
              </div>
              <div class="text-caption text-grey-7">
                Preço de venda: {{ formataMoeda(produto.produto.preco) }}
              </div>
              <div class="text-caption text-grey-7">
                Estoque min/máx:
                {{ produto.variacao.estoqueminimo }} / {{ produto.variacao.estoquemaximo }}
              </div>
            </div>
            <div class="col-12 col-sm-6">
              <div class="text-caption text-grey-6">Localização — {{ produto.localizacao.estoquelocal }}</div>
              <div class="text-body2">
                Corredor {{ produto.localizacao.corredor ?? '—' }} ·
                Prateleira {{ produto.localizacao.prateleira ?? '—' }}
              </div>
              <div class="text-body2">
                Coluna {{ produto.localizacao.coluna ?? '—' }} ·
                Bloco {{ produto.localizacao.bloco ?? '—' }}
              </div>
              <div v-if="produto.localizacao.vencimento" class="text-body2">
                Vencimento: {{ formataData(produto.localizacao.vencimento) }}
              </div>
            </div>
          </div>

          <q-separator class="q-my-md" />

          <div class="text-overline text-grey-7">Códigos de barras</div>
          <div class="row q-gutter-xs q-mb-md">
            <q-chip
              v-for="b in produto.barras"
              :key="b.barras"
              dense
              outline
              color="grey-8"
              :label="`${b.barras} (${b.quantidade} ${b.siglaunidademedida})`"
            />
          </div>

          <div class="text-overline text-grey-7">Conferências anteriores</div>
          <q-list v-if="produto.conferencias && produto.conferencias.length" separator>
            <q-item v-for="c in produto.conferencias" :key="c.codestoquesaldoconferencia">
              <q-item-section>
                <q-item-label>
                  {{ c.quantidadeinformada }}
                  <span class="text-grey-6">(sistema: {{ c.quantidadesistema }})</span>
                  · {{ formataMoeda(c.customedioinformado) }}
                </q-item-label>
                <q-item-label caption>
                  {{ formataDataHora(c.criacao) }} · {{ c.usuario }}
                </q-item-label>
                <q-item-label v-if="c.observacoes" caption class="text-grey-7">
                  {{ c.observacoes }}
                </q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-btn
                  flat
                  dense
                  round
                  size="sm"
                  color="grey-7"
                  icon="delete"
                  @click="excluirConferencia(c.codestoquesaldoconferencia)"
                >
                  <q-tooltip>Excluir</q-tooltip>
                </q-btn>
              </q-item-section>
            </q-item>
          </q-list>
          <div v-else class="text-grey-6 q-py-sm">Nenhuma conferência registrada</div>
        </q-card-section>

        <q-separator />
        <q-card-actions align="right">
          <q-btn flat color="grey-7" icon="exposure_zero" label="Zerar" @click="zerar" />
          <q-btn unelevated color="primary" icon="fact_check" label="Conferir" @click="abrirConferencia" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Dialog lançamento de conferência -->
    <q-dialog v-model="dialogConferencia">
      <q-card bordered flat style="width: 480px; max-width: 95vw">
        <q-card-section class="text-grey-9 text-overline">CONFERIR PRODUTO</q-card-section>
        <q-separator inset />
        <q-form @submit.prevent="salvarConferencia">
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-6">
                <q-input
                  v-model.number="conf.quantidade"
                  outlined
                  type="number"
                  step="any"
                  label="Quantidade conferida"
                  autofocus
                  :rules="[(v) => (v !== null && v !== '') || 'Obrigatório']"
                />
              </div>
              <div class="col-6">
                <q-input
                  v-model.number="conf.custo"
                  outlined
                  type="number"
                  step="any"
                  label="Custo médio"
                />
              </div>
              <div class="col-12">
                <q-input
                  v-model="conf.vencimento"
                  outlined
                  type="date"
                  stack-label
                  label="Vencimento (opcional)"
                />
              </div>
              <div class="col-3">
                <q-input v-model.number="conf.corredor" outlined type="number" label="Corredor" />
              </div>
              <div class="col-3">
                <q-input v-model.number="conf.prateleira" outlined type="number" label="Prat." />
              </div>
              <div class="col-3">
                <q-input v-model.number="conf.coluna" outlined type="number" label="Coluna" />
              </div>
              <div class="col-3">
                <q-input v-model.number="conf.bloco" outlined type="number" label="Bloco" />
              </div>
              <div class="col-12">
                <q-input
                  v-model="conf.observacoes"
                  outlined
                  type="textarea"
                  autogrow
                  label="Observações"
                />
              </div>
            </div>
          </q-card-section>
          <q-separator inset />
          <q-card-actions align="right" class="text-primary">
            <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
            <q-btn flat label="Salvar" type="submit" :loading="salvandoConf" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-page>
</template>
