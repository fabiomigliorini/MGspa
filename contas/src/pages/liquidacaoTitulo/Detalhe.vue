<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import { api } from 'src/services/api'
import { formataNumero, formataData, formataCodNegocio } from "@components/formatters"
import { notifySuccess, notifyError } from 'src/utils/notify'
import { useAuthStore } from 'src/stores/auth'
import { useLiquidacaoTituloStore } from 'src/stores/liquidacaoTituloStore'
import { PERMISSOES } from 'src/constants/permissoes'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import MgInputData from '@components/MgInputData.vue'
import SelectPessoa from 'src/components/select/SelectPessoa.vue'
import SelectPortador from 'src/components/select/SelectPortador.vue'
import { abrirPdf } from 'src/utils/abrirPdf'

const route = useRoute()
const $q = useQuasar()
const auth = useAuthStore()
const store = useLiquidacaoTituloStore()

const podeMutar = computed(() =>
  auth.hasAnyPermission([
    PERMISSOES.ADMINISTRADOR,
    PERMISSOES.FINANCEIRO,
    PERMISSOES.COBRANCA,
    PERMISSOES.GERENTE,
    PERMISSOES.CAIXA,
  ]),
)

const liq = ref(null)
const loading = ref(false)

const id = computed(() => (route.params.id ? Number(route.params.id) : null))

const estornado = computed(() => !!liq.value?.estornado)
const fechadaPeriodo = computed(() => !!liq.value?.codperiodo)

const podeEstornar = computed(
  () => podeMutar.value && liq.value && !estornado.value && !fechadaPeriodo.value,
)

const podeEditar = computed(
  () => podeMutar.value && liq.value && !estornado.value && !fechadaPeriodo.value,
)

const dialogEditar = ref(false)
const editar = ref({ codpessoa: null, codportador: null, transacao: null, observacao: '' })
const salvandoEdicao = ref(false)

function abrirDialogEditar() {
  editar.value = {
    codpessoa: liq.value?.codpessoa ?? null,
    codportador: liq.value?.codportador ?? null,
    transacao: liq.value?.transacao ? String(liq.value.transacao).slice(0, 10) : null,
    observacao: liq.value?.observacao ?? '',
  }
  dialogEditar.value = true
}

async function salvarEdicao() {
  salvandoEdicao.value = true
  try {
    const { data } = await api.put(`v1/liquidacao-titulo/${id.value}`, {
      codpessoa: editar.value.codpessoa,
      codportador: editar.value.codportador,
      transacao: editar.value.transacao,
      observacao: editar.value.observacao || null,
    })
    liq.value = data.data
    store.upsertLocal(data.data)
    notifySuccess('Liquidação atualizada')
    dialogEditar.value = false
  } catch (e) {
    notifyError(e, 'Erro ao atualizar liquidação')
  } finally {
    salvandoEdicao.value = false
  }
}

async function carregar() {
  if (!id.value) return
  loading.value = true
  try {
    const { data } = await api.get(`v1/liquidacao-titulo/${id.value}`)
    liq.value = data.data
  } catch (e) {
    notifyError(e, 'Erro ao carregar liquidação')
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
  abrirPdf(
    `v1/liquidacao-titulo/${id.value}/${rota}`,
    {},
    {
      title: titulos[rota] || 'Recibo',
    },
  )
}

function estornar() {
  $q.dialog({
    title: 'Estornar',
    message: 'Confirma estornar esta liquidação?',
    cancel: true,
  }).onOk(async () => {
    try {
      const { data } = await api.post(`v1/liquidacao-titulo/${id.value}/estornar`)
      liq.value = data.data
      store.upsertLocal(data.data)
      notifySuccess('Liquidação estornada')
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
              :to="{ name: 'liquidacao-titulo' }"
              aria-label="Voltar"
            />
          </q-item-section>
          <q-item-section>
            <div class="text-h4 text-grey-9">
              Liquidação {{ formataCodNegocio(liq.codliquidacaotitulo) }}
            </div>
            <div v-if="estornado" class="text-negative">
              Estornada em {{ formataData(liq.estornado) }}
            </div>
            <div v-if="fechadaPeriodo" class="text-orange-8">
              Fechada em período RH #{{ liq.codperiodo }}
            </div>
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
              <div class="text-caption text-grey-7">Transação</div>
              <div class="text-h6 text-grey-9">
                {{ formataData(liq.transacao) }}
              </div>
            </q-card>
          </div>

          <!-- Portador -->
          <div class="col-xs-6 col-sm-3">
            <q-card bordered flat class="q-py-sm text-center">
              <div class="text-caption text-grey-7">Portador</div>
              <div class="text-h6 text-grey-9 ellipsis">{{ liq.portador }}</div>
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
                {{ formataNumero(liq.valor) }} {{ liq.operacao }}
              </div>
            </q-card>
          </div>
        </div>

        <div class="row q-col-gutter-md">
          <!-- Detalhes + ações -->
          <div class="col-xs-12 col-sm-8">
            <q-card bordered flat>
              <q-card-section class="text-grey-9 text-overline row items-center">
                DETALHES DA LIQUIDAÇÃO
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
                  v-if="!estornado && liq.credito > 0"
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
                  v-if="!estornado && liq.debito > 0"
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
                <MgInfoCriacao
                  :usuariocriacao="liq.usuariocriacao"
                  :criacao="liq.criacao"
                  :usuarioalteracao="liq.usuarioalteracao"
                  :alteracao="liq.alteracao"
                />
              </q-card-section>
              <q-card-section class="q-pt-none">
                <div
                  class="text-body2 bg-grey-2 rounded-borders q-pa-md"
                  style="white-space: pre-line"
                >
                  <span v-if="liq.observacao">
                    {{ liq.observacao }}
                  </span>
                  <span v-else class="text-italic text-grey-7"> Sem Observações </span>
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
                  {{ formataNumero(m.valor) }} {{ m.operacao }}
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
                      {{ formataNumero(m.valor) }} {{ m.operacao }}
                    </q-item-label>
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
      <q-card bordered flat style="width: 500px; max-width: 90vw">
        <q-card-section class="text-grey-9 text-overline">EDITAR LIQUIDAÇÃO</q-card-section>
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
              <div class="col-8">
                <SelectPortador
                  v-model="editar.codportador"
                  outlined
                  label="Portador"
                  :rules="[(v) => !!v || 'Obrigatório']"
                />
              </div>
              <div class="col-4">
                <MgInputData
                  v-model="editar.transacao"
                  type="date"
                  label="Transação"
                  year-digits="2"
                  stack-label
                  :rules="[(v) => !!v || 'Obrigatório']"
                />
              </div>
            </div>
            <q-input
              v-model="editar.observacao"
              outlined
              type="textarea"
              label="Observações"
              autogrow
              maxlength="500"
            />
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
