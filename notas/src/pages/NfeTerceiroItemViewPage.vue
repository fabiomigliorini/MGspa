<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import { useNfeTerceiroStore } from '../stores/nfeTerceiroStore'
import { formatCurrency, formatDecimal } from 'src/utils/formatters'

const route = useRoute()
const $q = useQuasar()
const nfeTerceiroStore = useNfeTerceiroStore()

const produtosUrl = process.env.PRODUTOS_URL || ''

const codnfeterceiro = computed(() => Number(route.params.codnfeterceiro))
const codnfeterceiroitem = computed(() => Number(route.params.codnfeterceiroitem))

const nfe = computed(() => nfeTerceiroStore.currentNfeTerceiro)
const item = computed(() => {
  if (!nfe.value?.itens) return null
  return nfe.value.itens.find((i) => i.codnfeterceiroitem === codnfeterceiroitem.value)
})
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
      formDetalhes.value,
    )
    showDetalhes.value = false
    $q.notify({ type: 'positive', message: 'Item atualizado' })
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
      await nfeTerceiroStore.toggleConferenciaItem(
        codnfeterceiro.value,
        codnfeterceiroitem.value,
      )
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
})
</script>

<template>
  <q-page padding>
    <!-- Loading -->
    <div v-if="loading" class="row justify-center q-py-xl">
      <q-spinner color="primary" size="3em" />
    </div>

    <!-- Sem item -->
    <div v-else-if="!item" class="row justify-center q-py-xl">
      <div class="text-h6 text-grey-7">Item não encontrado</div>
    </div>

    <div v-else>
      <!-- Cabeçalho -->
      <div class="row items-center q-mb-md" style="flex-wrap: nowrap">
        <q-btn
          flat dense round
          icon="arrow_back"
          :to="{ name: 'nfe-terceiro-view', params: { codnfeterceiro: codnfeterceiro } }"
          class="q-mr-sm"
          style="flex-shrink: 0"
        >
          <q-tooltip>Voltar</q-tooltip>
        </q-btn>

        <div style="flex: 1; min-width: 0">
          <div class="text-h5 ellipsis">{{ item.xprod }}</div>
          <div class="text-body2" v-if="item.produtoBarra">
            <a
              v-if="item.produtoBarra.produto?.codproduto"
              :href="`${produtosUrl}/produto/${item.produtoBarra.produto.codproduto}`"
              target="_blank"
              class="text-primary text-weight-bold"
              style="text-decoration: none"
            >
              {{ item.produtoBarra.produto?.produto }}
            </a>
            <span class="text-grey-7" v-if="item.produtoBarra.variacao?.variacao">
              | {{ item.produtoBarra.variacao.variacao }}
            </span>
          </div>
        </div>

        <!-- Conferência -->
        <q-btn
          flat dense
          icon="task_alt"
          :color="item.conferencia ? 'green' : 'grey-7'"
          class="q-mr-sm"
          @click="handleConferencia"
        >
          <q-tooltip>{{ item.conferencia ? 'Conferido' : 'Marcar como conferido' }}</q-tooltip>
        </q-btn>

        <!-- Informar Detalhes -->
        <q-btn
          flat dense
          icon="edit_note"
          color="grey-7"
          @click="abrirDetalhes"
        >
          <q-tooltip>Informar Detalhes</q-tooltip>
        </q-btn>
      </div>

      <!-- Dialog: Informar Detalhes -->
      <q-dialog v-model="showDetalhes" persistent>
        <q-card style="min-width: 700px; max-width: 900px">
          <q-card-section class="bg-primary text-white">
            <div class="text-body2">
              <q-icon name="edit_note" size="1.5em" class="q-mr-sm" />
              Informar Detalhes
            </div>
          </q-card-section>

          <q-card-section class="q-pb-none">
            <!-- Produto -->
            <div class="text-caption text-grey-7">Produto</div>
            <div class="text-body2 text-weight-bold q-mb-md">
              {{ item.produtoBarra?.barras || item.cean || '-' }} -
              {{ item.produtoBarra?.produto?.produto || item.xprod }}
              <span v-if="item.produtoBarra?.variacao?.variacao" class="text-grey-7">
                | {{ item.produtoBarra.variacao.variacao }}
              </span>
            </div>
          </q-card-section>

          <!-- Dados readonly em grid 3 colunas -->
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

          <!-- Editáveis + Total Custo em linha alinhada -->
          <q-card-section class="q-py-none">
            <div class="row q-col-gutter-md items-start">
              <div class="col-4">
                <q-input
                  v-model.number="formDetalhes.margem"
                  label="Margem %"
                  type="number"
                  step="0.01"
                  outlined dense
                />
              </div>
              <div class="col-4">
                <q-input
                  v-model.number="formDetalhes.complemento"
                  label="Outros Custos"
                  type="number"
                  step="0.01"
                  outlined dense
                />
              </div>
              <div class="col-4">
                <div class="text-caption text-grey-7">Total Custo</div>
                <div class="text-subtitle1 text-weight-bold">
                  R$ {{ formatCurrency((item.vprod || 0) + (item.ipivipi || 0) + (item.vicmsst || 0) + (formDetalhes.complemento || 0)) }}
                </div>
              </div>
            </div>
          </q-card-section>

          <!-- Observações: linha única, full-width -->
          <q-card-section class="q-pt-md">
            <q-input
              v-model="formDetalhes.observacoes"
              label="Observações"
              outlined dense
              maxlength="500"
            />
          </q-card-section>

          <q-separator />

          <q-card-actions align="right">
            <q-btn flat label="Cancelar" v-close-popup />
            <q-btn color="primary" label="Salvar" @click="salvarDetalhes" />
          </q-card-actions>
        </q-card>
      </q-dialog>

      <!-- Cards -->
      <div class="row q-col-gutter-md">
        <!-- Card: Dados da Nota (XML) -->
        <div class="col-12 col-md-6">
          <q-card flat bordered class="full-height">
            <q-card-section class="bg-primary text-white">
              <div class="text-body2">
                <q-icon name="receipt_long" size="1.5em" class="q-mr-sm" />
                Dados da Nota
              </div>
            </q-card-section>
            <q-card-section>
              <div class="row q-col-gutter-sm">
                <div class="col-6 col-sm-4">
                  <div class="text-caption text-grey-7">#</div>
                  <div class="text-body2">{{ item.codnfeterceiroitem }}</div>
                </div>
                <div class="col-6 col-sm-4">
                  <div class="text-caption text-grey-7">Número</div>
                  <div class="text-body2">{{ item.nitem }}</div>
                </div>
                <div class="col-6 col-sm-4">
                  <div class="text-caption text-grey-7">Referência</div>
                  <div class="text-body2">{{ item.cprod }}</div>
                </div>
                <div class="col-6 col-sm-4">
                  <div class="text-caption text-grey-7">CFOP</div>
                  <div class="text-body2">{{ item.cfop }}</div>
                </div>
                <div class="col-6 col-sm-4">
                  <div class="text-caption text-grey-7">CST</div>
                  <div class="text-body2">{{ item.cst }}</div>
                </div>
                <div class="col-12 col-sm-4">
                  <div class="text-caption text-grey-7">Origem</div>
                  <div class="text-body2">{{ item.orig }} - {{ origemDescricao(item.orig) }}</div>
                </div>
                <div class="col-6 col-sm-4">
                  <div class="text-caption text-grey-7">EAN</div>
                  <div class="text-body2" style="font-family: monospace">{{ item.cean || '-' }}</div>
                </div>
                <div class="col-6 col-sm-4">
                  <div class="text-caption text-grey-7">EAN Trib</div>
                  <div class="text-body2" style="font-family: monospace">{{ item.ceantrib || '-' }}</div>
                </div>
                <div class="col-6 col-sm-4">
                  <div class="text-caption text-grey-7">NCM</div>
                  <div class="text-body2">{{ item.ncm }}</div>
                </div>
                <div class="col-6 col-sm-4" v-if="item.cest">
                  <div class="text-caption text-grey-7">CEST</div>
                  <div class="text-body2">{{ item.cest }}</div>
                </div>
                <div class="col-6 col-sm-4">
                  <div class="text-caption text-grey-7">Quantidade Com</div>
                  <div class="text-body2">{{ formatDecimal(item.qcom, 3) }} {{ item.ucom }}</div>
                </div>
                <div class="col-6 col-sm-4">
                  <div class="text-caption text-grey-7">Preço</div>
                  <div class="text-body2">{{ formatDecimal(item.vuncom, 6) }}</div>
                </div>
                <div class="col-6 col-sm-4">
                  <div class="text-caption text-grey-7">Total</div>
                  <div class="text-subtitle1 text-weight-bold">R$ {{ formatCurrency(item.vprod) }}</div>
                </div>
                <div class="col-6 col-sm-4">
                  <div class="text-caption text-grey-7">Quantidade Trib</div>
                  <div class="text-body2">{{ formatDecimal(item.qtrib, 3) }} {{ item.utrib }}</div>
                </div>
                <div class="col-6 col-sm-4">
                  <div class="text-caption text-grey-7">Preço Trib</div>
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
            <q-card-section>
              <div class="row q-col-gutter-sm">
                <!-- ICMS -->
                <div class="col-12 text-caption text-weight-bold q-mt-xs">ICMS</div>
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

                <!-- ICMS ST -->
                <div class="col-12 text-caption text-weight-bold q-mt-sm">ICMS ST</div>
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

                <!-- IPI -->
                <div class="col-12 text-caption text-weight-bold q-mt-sm">IPI</div>
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

                <!-- PIS -->
                <div class="col-12 text-caption text-weight-bold q-mt-sm">PIS</div>
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

                <!-- COFINS -->
                <div class="col-12 text-caption text-weight-bold q-mt-sm">COFINS</div>
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

                <!-- Outros valores -->
                <div class="col-12 text-caption text-weight-bold q-mt-sm">Outros Valores</div>
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
    </div>
  </q-page>
</template>
