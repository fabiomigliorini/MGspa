<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import { formataNumero, formataData, formataCodigo } from '@components/formatters'
import { notifySuccess, notifyError } from 'src/utils/notify'
import { useAuthStore } from 'src/stores/auth'
import { usePagamentoStore } from 'src/stores/pagamentoStore'
import { PERMISSOES } from 'src/constants/permissoes'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import MgInput from '@components/MgInput.vue'
import MgInputData from '@components/MgInputData.vue'
import SelectPessoa from '@components/MgSelectPessoa.vue'
import MgSelectPortador from '@components/MgSelectPortador.vue'
import { MEIOS, meioDoTipo } from 'src/stores/pagamentoStore'
import { abrirPdf } from 'src/utils/abrirPdf'

const route = useRoute()
const $q = useQuasar()
const auth = useAuthStore()
const store = usePagamentoStore()
const { pagamento: liq } = storeToRefs(store)

const podeMutar = computed(() =>
  auth.temAlgumaPermissao([
    PERMISSOES.ADMINISTRADOR,
    PERMISSOES.FINANCEIRO,
    PERMISSOES.COBRANCA,
    PERMISSOES.GERENTE,
    PERMISSOES.CAIXA,
  ]),
)

const loading = ref(false)

const id = computed(() => (route.params.id ? Number(route.params.id) : null))

const estornado = computed(() => liq.value?.estado === 'C')
const acertoRh = computed(() => !!liq.value?.codperiodocolaboradoracerto)

// acerto de RH estorna pelo acerto; baixa de boleto, pelo banco
const podeEstornar = computed(
  () =>
    podeMutar.value && liq.value && !estornado.value && !acertoRh.value && !liq.value.baixabanco,
)

// venda, acerto de RH e boleto pago pelo banco não se editam aqui
const podeEditar = computed(
  () =>
    podeMutar.value && liq.value && !estornado.value && !acertoRh.value && !liq.value.baixabanco,
)

// encontro de contas sem dinheiro continua sem portador
const semPortador = computed(() => liq.value?.operacao === 'CP' && !liq.value?.codportador)

const dialogEditar = ref(false)
const salvandoEdicao = ref(false)
const editar = ref({})

function abrirDialogEditar() {
  editar.value = {
    codpessoa: liq.value.codpessoa,
    codportador: liq.value.codportador,
    meio: liq.value.meio,
    transacao: String(liq.value.lancamento).slice(0, 10),
    observacao: liq.value.observacoes ?? '',
  }
  dialogEditar.value = true
}

// trocar o portador sugere o meio dele
const portadorEscolhido = (p) => {
  if (p) editar.value.meio = meioDoTipo(p.tipo)
}

async function salvarEdicao() {
  salvandoEdicao.value = true
  try {
    await store.atualizar(id.value, {
      ...editar.value,
      codportador: semPortador.value ? null : editar.value.codportador,
      meio: semPortador.value ? null : editar.value.meio,
      observacao: editar.value.observacao || null,
    })
    notifySuccess('Alterado')
    dialogEditar.value = false
  } catch (e) {
    notifyError(e, 'Erro ao alterar')
  } finally {
    salvandoEdicao.value = false
  }
}

const operacaoDescricao = computed(
  () => ({ CR: 'Recebimento', DB: 'Pagamento', CP: 'Encontro de contas' })[liq.value?.operacao],
)

async function carregar() {
  if (!id.value) return
  loading.value = true
  try {
    await store.carregar(id.value)
  } catch (e) {
    notifyError(e, 'Erro ao carregar')
    liq.value = null
  } finally {
    loading.value = false
  }
}

function abrirRecibo(rota) {
  const titulos = {
    recibo: 'Recibo',
    'recibo-recebimento': 'Recibo de Recebimento',
    'recibo-pagamento': 'Recibo de Pagamento',
  }
  abrirPdf(`v1/pagamento/${id.value}/${rota}`, {}, { title: titulos[rota] || 'Recibo' })
}

function estornar() {
  $q.dialog({
    title: 'Estornar',
    message: 'Estornar desfaz a baixa de todos os títulos. Informe o motivo:',
    prompt: {
      model: '',
      type: 'text',
      outlined: true,
      isValid: (v) => (v || '').trim().length >= 5,
    },
    ok: { label: 'Estornar', color: 'negative', flat: true },
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
  }).onOk(async (justificativa) => {
    try {
      await store.estornar(id.value, justificativa)
      notifySuccess('Estornado')
    } catch (e) {
      notifyError(e, 'Erro ao estornar')
    }
  })
}

const urlPessoa = (cod) => (cod ? `${process.env.PESSOAS_URL}/pessoa/${cod}` : null)

onMounted(carregar)
watch(() => route.fullPath, carregar)
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1000px; margin: auto">
      <template v-if="liq">
        <!-- Cabeçalho -->
        <q-item class="q-pb-md q-px-none">
          <q-item-section avatar>
            <q-btn
              flat
              dense
              round
              icon="arrow_back"
              :to="{ name: 'pagamento' }"
              aria-label="Voltar"
            />
          </q-item-section>
          <q-item-section>
            <div class="text-h4 text-grey-9">
              {{ operacaoDescricao }} {{ formataCodigo(liq.codpagamento) }}
            </div>
            <div v-if="liq.codliquidacaotituloantigo" class="text-grey-7">
              Liquidação {{ formataCodigo(liq.codliquidacaotituloantigo) }} (histórico)
            </div>
            <div v-if="estornado" class="text-negative">
              Estornado em {{ formataData(liq.cancelamento) }} · {{ liq.justificativa }}
            </div>
            <div v-if="acertoRh" class="text-orange-8">Acerto de RH: estorne pelo acerto</div>
          </q-item-section>
        </q-item>

        <!-- Cards resumo -->
        <div class="row q-col-gutter-md q-mb-md">
          <!-- PESSOA -->
          <div class="col-xs-6 col-sm-4">
            <q-card bordered flat class="q-py-sm text-center">
              <div class="text-caption text-grey-7">Pessoa</div>
              <div class="text-h6 ellipsis">
                <a
                  :href="urlPessoa(liq.codpessoa)"
                  class="text-primary"
                  style="text-decoration: none"
                >
                  {{ liq.fantasia }}
                </a>
              </div>
            </q-card>
          </div>

          <!-- DATA -->
          <div class="col-xs-6 col-sm-2">
            <q-card bordered flat class="q-py-sm text-center">
              <div class="text-caption text-grey-7">Data</div>
              <div class="text-h6 text-grey-9">
                {{ formataData(liq.lancamento) }}
              </div>
            </q-card>
          </div>

          <!-- Portador -->
          <div class="col-xs-6 col-sm-3">
            <q-card bordered flat class="q-py-sm text-center">
              <div class="text-caption text-grey-7">{{ liq.meiodescricao }}</div>
              <div class="text-h6 text-grey-9 ellipsis">{{ liq.portador || 'Sem portador' }}</div>
            </q-card>
          </div>

          <!-- TOTAL -->
          <div class="col-xs-6 col-sm-3">
            <q-card bordered flat class="q-py-sm text-center">
              <div class="text-caption text-grey-7">Total</div>
              <div
                class="text-h6 ellipsis"
                :class="liq.operacao === 'CR' ? 'text-orange' : 'text-green'"
              >
                {{ formataNumero(liq.total) }} {{ liq.operacao }}
              </div>
            </q-card>
          </div>
        </div>

        <div class="row q-col-gutter-md">
          <!-- Detalhes + ações -->
          <div class="col-xs-12 col-sm-8">
            <q-card bordered flat>
              <q-card-section class="text-grey-9 text-overline row items-center">
                DETALHES
                <q-space />
                <q-btn
                  v-if="podeEditar"
                  flat
                  round
                  size="sm"
                  icon="edit"
                  color="grey-7"
                  @click="abrirDialogEditar"
                >
                  <q-tooltip>Editar</q-tooltip>
                </q-btn>
                <q-btn
                  flat
                  round
                  dense
                  icon="receipt"
                  size="sm"
                  color="grey-7"
                  @click="abrirRecibo('recibo')"
                  v-if="!estornado"
                >
                  <q-tooltip>Recibo</q-tooltip>
                </q-btn>
                <q-btn
                  v-if="!estornado && liq.recebimento"
                  flat
                  round
                  dense
                  icon="south_west"
                  size="sm"
                  color="grey-7"
                  @click="abrirRecibo('recibo-recebimento')"
                >
                  <q-tooltip>Recibo Recebimento</q-tooltip>
                </q-btn>
                <q-btn
                  v-if="!estornado && liq.pagamento"
                  flat
                  round
                  dense
                  icon="north_east"
                  size="sm"
                  color="grey-7"
                  @click="abrirRecibo('recibo-pagamento')"
                >
                  <q-tooltip>Recibo Pagamento</q-tooltip>
                </q-btn>
                <q-btn
                  v-if="podeEstornar"
                  flat
                  round
                  dense
                  icon="undo"
                  size="sm"
                  color="grey-7"
                  @click="estornar"
                >
                  <q-tooltip>Estornar</q-tooltip>
                </q-btn>
                <MgInfoCriacao :registro="liq" />
              </q-card-section>
              <q-card-section class="q-pt-none">
                <div
                  class="text-body2 bg-grey-2 rounded-borders q-pa-md"
                  style="white-space: pre-line"
                >
                  <span v-if="liq.observacoes">
                    {{ liq.observacoes }}
                  </span>
                  <span v-else class="text-italic text-grey-7"> Sem Observações </span>
                </div>
              </q-card-section>
              <q-card-section v-if="liq.juros || liq.multa || liq.desconto" class="q-pt-none">
                <div class="row q-col-gutter-md text-center">
                  <div class="col">
                    <div class="text-caption text-grey-7">Principal</div>
                    <div>{{ formataNumero(liq.principal) }}</div>
                  </div>
                  <div class="col" v-if="liq.juros">
                    <div class="text-caption text-grey-7">Juros</div>
                    <div class="text-orange">{{ formataNumero(liq.juros) }}</div>
                  </div>
                  <div class="col" v-if="liq.multa">
                    <div class="text-caption text-grey-7">Multa</div>
                    <div class="text-orange">{{ formataNumero(liq.multa) }}</div>
                  </div>
                  <div class="col" v-if="liq.desconto">
                    <div class="text-caption text-grey-7">Desconto</div>
                    <div class="text-blue">{{ formataNumero(liq.desconto) }}</div>
                  </div>
                  <div class="col">
                    <div class="text-caption text-grey-7">Total</div>
                    <div class="text-weight-bold">{{ formataNumero(liq.total) }}</div>
                  </div>
                </div>
              </q-card-section>
              <!-- <q-list separator v-if="liq.movimentos?.length">
            <q-item
              v-for="m in liq.movimentos"
              :key="m.codmovimentotitulo"
              :to="{ name: 'titulo-detalhe', params: { codtitulo: m.codtitulo } }"
            >
              <q-item-section style="flex: 0 0 90px; min-width: 0" class="gt-xs">
                <q-item-label caption :class="m.titulo?.gerencial ? 'text-orange' : 'text-green'">
                  {{ m.titulo?.filial }}
                </q-item-label>
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-weight-medium text-primary">
                  {{ m.titulo?.numero }}
                </q-item-label>
              </q-item-section>
              <q-item-section class="gt-xs">
                <q-item-label class="text-primary">
                  {{ m.titulo?.fantasia }}
                </q-item-label>
              </q-item-section>
              <q-item-section class="gt-xs">
                <q-item-label caption>
                  {{ formataData(m.titulo?.vencimento) }}
                </q-item-label>
                <q-item-label caption v-if="m.titulo?.boleto">
                  Boleto {{ m.titulo?.nossonumero }}
                </q-item-label>
              </q-item-section>
              <q-item-section>
                <q-item-label
                  class="text-weight-bold text-right"
                  :class="m.operacao === 'CR' ? 'text-orange' : 'text-green'"
                >
                  {{ formataNumero(m.total) }} {{ m.operacao }}
                </q-item-label>
                <q-item-label class="text-right" caption>{{ m.tipomovimentotitulo }}</q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
          <q-card-section v-else class="text-center text-grey-6 q-pa-md">
            Nenhum título baixado
          </q-card-section> -->
            </q-card>
          </div>

          <!-- Títulos Baixados -->
          <div class="col-xs-12 col-sm-4">
            <q-card bordered flat>
              <q-card-section class="text-grey-9 text-overline">
                MOVIMENTOS ({{ liq.movimentos?.length || 0 }})
              </q-card-section>
              <q-list separator v-if="liq.movimentos?.length">
                <q-item
                  v-for="m in liq.movimentos"
                  :key="m.codmovimentotitulo"
                  :to="{ name: 'titulo-detalhe', params: { codtitulo: m.titulo?.codtitulo } }"
                >
                  <q-item-section>
                    <q-item-label class="text-weight-medium text-primary">
                      {{ m.titulo?.numero }}
                    </q-item-label>
                    <q-item-label caption v-if="m.titulo.codpessoa != liq.codpessoa">
                      {{ m.titulo?.fantasia }}
                    </q-item-label>

                    <q-item-label
                      caption
                      :class="m.titulo?.gerencial ? 'text-orange' : 'text-green'"
                    >
                      {{ m.titulo?.filial }}
                    </q-item-label>
                    <q-item-label caption v-if="m.titulo?.boleto">
                      Boleto {{ m.titulo?.nossonumero }}
                    </q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <q-item-label
                      class="text-weight-bold text-right"
                      :class="m.operacao === 'CR' ? 'text-orange' : 'text-green'"
                    >
                      {{ formataNumero(m.total) }} {{ m.operacao }}
                    </q-item-label>
                    <!-- juros, multa e desconto vêm na mesma linha da baixa -->
                    <template v-if="m.juros || m.multa || m.desconto">
                      <q-item-label caption>
                        Principal {{ formataNumero(m.principal) }}
                      </q-item-label>
                      <q-item-label caption v-if="m.juros" class="text-orange">
                        Juros {{ formataNumero(m.juros) }}
                      </q-item-label>
                      <q-item-label caption v-if="m.multa" class="text-orange">
                        Multa {{ formataNumero(m.multa) }}
                      </q-item-label>
                      <q-item-label caption v-if="m.desconto" class="text-blue">
                        Desconto {{ formataNumero(m.desconto) }}
                      </q-item-label>
                    </template>
                    <q-item-label caption>{{ m.tipomovimentotitulo }}</q-item-label>
                    <q-item-label caption>
                      {{ formataData(m.titulo?.vencimento) }}
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </q-card>
          </div>
        </div>
      </template>
    </div>

    <q-inner-loading :showing="loading || salvandoEdicao" color="primary" />

    <!-- Dialog Editar -->
    <q-dialog v-model="dialogEditar">
      <q-card flat style="width: 500px; max-width: 90vw">
        <q-card-section class="text-grey-9 text-overline">EDITAR</q-card-section>
        <q-form @submit.prevent="salvarEdicao">
          <q-separator inset />
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-12">
                <SelectPessoa
                  v-model="editar.codpessoa"
                  outlined
                  label="Pessoa"
                  autofocus
                  :rules="[(v) => !!v || 'Obrigatório']"
                />
              </div>
              <div class="col-8" v-if="!semPortador">
                <MgSelectPortador
                  v-model="editar.codportador"
                  outlined
                  label="Portador"
                  sem-gaveta
                  :rules="[(v) => !!v || 'Obrigatório']"
                  @select="portadorEscolhido"
                />
              </div>
              <div class="col-4">
                <MgInputData
                  v-model="editar.transacao"
                  type="date"
                  label="Data"
                  stack-label
                  :rules="[(v) => !!v || 'Obrigatório']"
                />
              </div>
              <div class="col-8" v-if="!semPortador">
                <q-select
                  v-model="editar.meio"
                  :options="MEIOS"
                  emit-value
                  map-options
                  outlined
                  label="Meio"
                  :rules="[(v) => !!v || 'Obrigatório']"
                />
              </div>
              <div class="col-12">
                <MgInput
                  v-model="editar.observacao"
                  outlined
                  type="textarea"
                  label="Observações"
                  autogrow
                  maxlength="300"
                />
              </div>
            </div>
          </q-card-section>
          <q-separator inset />
          <q-card-actions align="right">
            <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
            <q-btn flat label="Salvar" type="submit" color="primary" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-page>
</template>
