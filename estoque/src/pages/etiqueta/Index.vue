<script setup>
import { ref, computed, nextTick, onMounted } from 'vue'
import { api } from 'src/services/api'
import { notifySuccess, notifyError } from 'src/utils/notify'
import MgSelectImpressora from '@components/MgSelectImpressora.vue'

const etiquetas = ref([])

// Por código de barras
const quantidade = ref(1)
const barras = ref('')
const barrasRef = ref(null)
const buscandoBarras = ref(false)

// Por negócio
const codnegocio = ref(null)
const buscandoNegocio = ref(false)

// Por período
const dataInicial = ref('')
const dataFinal = ref('')
const buscandoPeriodo = ref(false)

// Impressão
const dialogImprimir = ref(false)
const modelo = ref('gondola')
const impressora = ref(null)
const imprimindo = ref(false)

const modeloOptions = [
  { label: 'Gôndola (grande)', value: 'gondola' },
  { label: '2 colunas', value: '2colunas' },
  { label: '2 colunas (sem preço)', value: '2colunas_sempreco' },
  { label: '3 colunas', value: '3colunas' },
  { label: '3 colunas (sem preço)', value: '3colunas_sempreco' },
  { label: '4 colunas (mini)', value: '4colunas' },
  { label: '4 colunas (sem preço)', value: '4colunas_sempreco' },
]

const totalEtiquetas = computed(() =>
  etiquetas.value.reduce((acc, e) => acc + (Number(e.quantidadeetiqueta) || 0), 0),
)

const codFormatado = (v) => '#' + String(v).padStart(6, '0')
const formataMoeda = (v) =>
  (Number(v) || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })

const focarBarras = async () => {
  await nextTick()
  barrasRef.value?.focus()
}

const adicionarBarras = async () => {
  let cod = (barras.value || '').trim()
  let qtd = Number(quantidade.value) || 1
  if (!cod) return
  // Leitor pode enviar "quantidade*codigo" (ex: 5*7891234567890)
  if (cod.includes('*')) {
    const partes = cod.split('*')
    if (partes.length === 2 && partes[1]) {
      qtd = parseInt(partes[0]) || qtd
      cod = partes[1]
    }
  }
  buscandoBarras.value = true
  try {
    const { data } = await api.get('v1/etiqueta/barras', { params: { barras: cod } })
    const etiqueta = { ...data.data, quantidadeetiqueta: qtd }
    etiquetas.value.unshift(etiqueta)
    notifySuccess(`${qtd} etiqueta(s) de "${etiqueta.produto}" adicionada(s)`)
    barras.value = ''
    quantidade.value = 1
  } catch (e) {
    notifyError(e, 'Código de barras não encontrado')
  } finally {
    buscandoBarras.value = false
    focarBarras()
  }
}

const adicionarNegocio = async () => {
  if (!codnegocio.value) return
  buscandoNegocio.value = true
  try {
    const { data } = await api.get('v1/etiqueta/negocio', {
      params: { codnegocio: codnegocio.value },
    })
    etiquetas.value = [...data.data, ...etiquetas.value]
    notifySuccess(`${data.data.length} etiqueta(s) adicionada(s)`)
    codnegocio.value = null
  } catch (e) {
    notifyError(e, 'Negócio não encontrado')
  } finally {
    buscandoNegocio.value = false
  }
}

const adicionarPeriodo = async () => {
  if (!dataInicial.value || !dataFinal.value) {
    notifyError(null, 'Informe as datas inicial e final')
    return
  }
  buscandoPeriodo.value = true
  try {
    const { data } = await api.get('v1/etiqueta/periodo', {
      params: { datainicial: dataInicial.value, datafinal: dataFinal.value },
    })
    etiquetas.value = [...data.data, ...etiquetas.value]
    notifySuccess(`${data.data.length} etiqueta(s) adicionada(s)`)
  } catch (e) {
    notifyError(e, 'Falha ao buscar produtos com preço alterado no período')
  } finally {
    buscandoPeriodo.value = false
  }
}

const remover = (idx) => etiquetas.value.splice(idx, 1)

const limparTodas = () => {
  etiquetas.value = []
}

const abrirImprimir = () => {
  if (etiquetas.value.length === 0) {
    notifyError(null, 'Nenhuma etiqueta para imprimir')
    return
  }
  dialogImprimir.value = true
}

const imprimir = async () => {
  if (!impressora.value) {
    notifyError(null, 'Selecione a impressora')
    return
  }
  imprimindo.value = true
  try {
    const { data } = await api.post('v1/etiqueta/imprimir', {
      modelo: modelo.value,
      impressora: impressora.value,
      etiquetas: etiquetas.value.map((e) => ({
        codprodutobarra: e.codprodutobarra,
        quantidadeetiqueta: Number(e.quantidadeetiqueta) || 1,
      })),
    })
    notifySuccess(`${data.quantidadeetiqueta} etiqueta(s) enviada(s) para ${data.impressora}`)
    dialogImprimir.value = false
  } catch (e) {
    notifyError(e, 'Erro ao imprimir etiquetas')
  } finally {
    imprimindo.value = false
  }
}

onMounted(focarBarras)
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1280px; margin: auto">
      <!-- Formas de adicionar -->
      <div class="row q-col-gutter-md">
        <div class="col-12 col-md-4">
          <q-card bordered flat class="full-height">
            <q-card-section class="text-grey-9 text-overline">
              <q-icon name="qr_code_scanner" class="q-mr-xs" /> Por código de barras
            </q-card-section>
            <q-separator inset />
            <q-card-section>
              <q-form @submit.prevent="adicionarBarras">
                <q-input
                  v-model.number="quantidade"
                  outlined
                  type="number"
                  min="1"
                  label="Quantidade"
                  class="q-mb-sm"
                >
                  <template #prepend><q-icon name="tag" /></template>
                </q-input>
                <q-input
                  ref="barrasRef"
                  v-model="barras"
                  outlined
                  label="Código de barras"
                  autofocus
                  @keyup.enter="adicionarBarras"
                >
                  <template #prepend><q-icon name="qr_code_2" /></template>
                  <template #append>
                    <q-btn
                      flat
                      round
                      dense
                      icon="add"
                      color="primary"
                      :loading="buscandoBarras"
                      @click="adicionarBarras"
                    />
                  </template>
                </q-input>
              </q-form>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-md-4">
          <q-card bordered flat class="full-height">
            <q-card-section class="text-grey-9 text-overline">
              <q-icon name="receipt_long" class="q-mr-xs" /> Por negócio
            </q-card-section>
            <q-separator inset />
            <q-card-section>
              <q-form @submit.prevent="adicionarNegocio">
                <q-input
                  v-model.number="codnegocio"
                  outlined
                  type="number"
                  label="Número do negócio"
                  @keyup.enter="adicionarNegocio"
                >
                  <template #prepend><q-icon name="numbers" /></template>
                  <template #append>
                    <q-btn
                      flat
                      round
                      dense
                      icon="add"
                      color="primary"
                      :loading="buscandoNegocio"
                      @click="adicionarNegocio"
                    />
                  </template>
                </q-input>
              </q-form>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-md-4">
          <q-card bordered flat class="full-height">
            <q-card-section class="text-grey-9 text-overline">
              <q-icon name="date_range" class="q-mr-xs" /> Por período (preço alterado)
            </q-card-section>
            <q-separator inset />
            <q-card-section>
              <q-input
                v-model="dataInicial"
                outlined
                type="date"
                label="Data inicial"
                stack-label
                class="q-mb-sm"
              />
              <q-input
                v-model="dataFinal"
                outlined
                type="date"
                label="Data final"
                stack-label
                class="q-mb-sm"
              />
              <q-btn
                unelevated
                color="primary"
                icon="add"
                label="Adicionar"
                :loading="buscandoPeriodo"
                @click="adicionarPeriodo"
              />
            </q-card-section>
          </q-card>
        </div>
      </div>

      <!-- Barra de status -->
      <div class="row items-center q-mt-md q-mb-sm">
        <div class="text-subtitle1">
          {{ etiquetas.length }} produto(s) · {{ totalEtiquetas }} etiqueta(s)
        </div>
        <q-space />
        <q-btn
          v-if="etiquetas.length"
          flat
          dense
          color="grey-7"
          icon="delete_sweep"
          label="Limpar"
          @click="limparTodas"
        />
      </div>

      <!-- Grid de etiquetas -->
      <div v-if="etiquetas.length === 0" class="text-center text-grey-6 q-pa-xl">
        Nenhuma etiqueta. Adicione produtos pelos campos acima.
      </div>

      <div class="row q-col-gutter-md">
        <div
          v-for="(item, idx) in etiquetas"
          :key="item.codprodutobarra + '-' + idx"
          class="col-xs-12 col-sm-6 col-md-4 col-lg-3"
        >
          <q-card bordered flat>
            <q-item>
              <q-item-section avatar>
                <q-avatar rounded size="48px" color="grey-3" text-color="grey-7">
                  <img v-if="item.imagem" :src="item.imagem" />
                  <q-icon v-else name="inventory_2" />
                </q-avatar>
              </q-item-section>
              <q-item-section>
                <q-item-label class="ellipsis">{{ item.produto }}</q-item-label>
                <q-item-label caption>{{ codFormatado(item.codproduto) }}</q-item-label>
                <q-item-label caption>{{ item.barras }}</q-item-label>
              </q-item-section>
              <q-item-section side top>
                <q-btn flat dense round size="sm" color="grey-7" icon="close" @click="remover(idx)">
                  <q-tooltip>Remover</q-tooltip>
                </q-btn>
              </q-item-section>
            </q-item>
            <q-separator inset />
            <q-card-section class="row items-center q-py-sm">
              <div>
                <div class="text-weight-medium">{{ formataMoeda(item.preco) }}</div>
                <div class="text-caption text-grey-6">
                  {{ item.unidademedidasigla }}
                  <span v-if="item.quantidadeembalagem > 1">· C/{{ item.quantidadeembalagem }}</span>
                </div>
              </div>
              <q-space />
              <q-input
                v-model.number="item.quantidadeetiqueta"
                outlined
                type="number"
                min="1"
                label="Qtd"
                style="width: 90px"
                :bottom-slots="false"
              />
            </q-card-section>
          </q-card>
        </div>
      </div>
    </div>

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn fab icon="print" color="primary" @click="abrirImprimir">
        <q-tooltip anchor="center left" self="center right">Imprimir etiquetas</q-tooltip>
      </q-btn>
    </q-page-sticky>

    <q-dialog v-model="dialogImprimir">
      <q-card bordered flat style="width: 420px; max-width: 90vw">
        <q-card-section class="text-grey-9 text-overline">IMPRIMIR ETIQUETAS</q-card-section>
        <q-separator inset />
        <q-card-section>
          <div class="text-caption text-grey-7 q-mb-md">
            {{ totalEtiquetas }} etiqueta(s) de {{ etiquetas.length }} produto(s)
          </div>
          <q-select
            v-model="modelo"
            :options="modeloOptions"
            emit-value
            map-options
            outlined
            label="Modelo"
            class="q-mb-md"
            :bottom-slots="false"
          >
            <template #prepend><q-icon name="grid_view" /></template>
          </q-select>
          <MgSelectImpressora v-model="impressora" />
        </q-card-section>
        <q-separator inset />
        <q-card-actions align="right" class="text-primary">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn flat label="Imprimir" :loading="imprimindo" @click="imprimir" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>
