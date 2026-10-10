<script setup>
// Pagamento: o detalhe compartilhado (MgPagamentoDetalhe) com o que é do contas: recibos em PDF e
// o lápis. O lápis corrige pessoa e observação; a data só do pagamento manual (a do integrado é a
// do banco/maquineta) e, se muda, pede justificativa (TASK-204). Meio e portador são o fato e não
// mudam: errou, desamarra e lança de novo.
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { notifySuccess, notifyError } from 'src/utils/notify'
import { useAuthStore } from 'src/stores/auth'
import { PERMISSOES } from 'src/constants/permissoes'
import { abrirPdf } from 'src/utils/abrirPdf'
import { configurarPagamentoLista } from 'src/utils/pagamentoLista'
import { formataTimestampIso } from '@components/formatters'
import MgPagamentoDetalhe from '@components/MgPagamentoDetalhe.vue'
import MgInput from '@components/MgInput.vue'
import MgInputData from '@components/MgInputData.vue'
import MgSelectPessoa from '@components/MgSelectPessoa.vue'

const route = useRoute()
const auth = useAuthStore()
const store = configurarPagamentoLista()

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
const pag = computed(() => store.pagamento)

// venda pelo negócio, acerto pelo acerto
const podeEditar = computed(() => podeMutar.value && pag.value?.editavel)

async function carregar() {
  if (!id.value) return
  loading.value = true
  try {
    await store.carregar(id.value)
  } catch (e) {
    notifyError(e, 'Erro ao carregar')
    store.pagamento = null
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

const dialogEditar = ref(false)
const salvandoEdicao = ref(false)
const editar = ref({})

function abrirDialogEditar() {
  editar.value = {
    codpessoa: pag.value.codpessoa,
    transacao: formataTimestampIso(pag.value.transacao).slice(0, 16),
    observacao: pag.value.observacoes ?? '',
    justificativa: '',
  }
  dialogEditar.value = true
}

// a data mudou: pede a justificativa
const dataMudou = computed(
  () =>
    !!editar.value.transacao &&
    !!pag.value &&
    editar.value.transacao !== formataTimestampIso(pag.value.transacao).slice(0, 16),
)

// mudar de mês avisa (DIMP e relatórios já apurados), sem bloquear
const trocaMes = computed(
  () =>
    dataMudou.value &&
    editar.value.transacao.slice(0, 7) !== formataTimestampIso(pag.value.transacao).slice(0, 7),
)

async function salvarEdicao() {
  salvandoEdicao.value = true
  try {
    await store.atualizar(id.value, {
      ...editar.value,
      observacao: editar.value.observacao || null,
      justificativa: dataMudou.value ? editar.value.justificativa : null,
    })
    notifySuccess('Alterado')
    dialogEditar.value = false
  } catch (e) {
    notifyError(e, 'Erro ao alterar')
  } finally {
    salvandoEdicao.value = false
  }
}

onMounted(carregar)
watch(() => route.fullPath, carregar)
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1000px; margin: auto">
      <q-btn
        flat
        round
        icon="arrow_back"
        :to="{ name: 'pagamento' }"
        aria-label="Voltar"
        class="q-mb-sm"
      />
      <MgPagamentoDetalhe
        v-if="pag"
        :pode-estornar="podeMutar"
        :to-titulo="(codtitulo) => ({ name: 'titulo-detalhe', params: { codtitulo } })"
      >
        <template #acoes>
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
          <!-- recibo só com título amarrado (o desamarrado não conta) -->
          <template v-if="pag.estado !== 'C' && pag.movimentos?.some((m) => !m.estornado)">
            <q-btn
              flat
              round
              size="sm"
              icon="receipt"
              color="grey-7"
              @click="abrirRecibo('recibo')"
            >
              <q-tooltip>Recibo</q-tooltip>
            </q-btn>
            <q-btn
              v-if="pag.recebimento"
              flat
              round
              size="sm"
              icon="south_west"
              color="grey-7"
              @click="abrirRecibo('recibo-recebimento')"
            >
              <q-tooltip>Recibo Recebimento</q-tooltip>
            </q-btn>
            <q-btn
              v-if="pag.pagamento"
              flat
              round
              size="sm"
              icon="north_east"
              color="grey-7"
              @click="abrirRecibo('recibo-pagamento')"
            >
              <q-tooltip>Recibo Pagamento</q-tooltip>
            </q-btn>
          </template>
        </template>
      </MgPagamentoDetalhe>
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
                <MgSelectPessoa
                  v-model="editar.codpessoa"
                  label="Pessoa"
                  autofocus
                  :rules="[(v) => !!v || 'Obrigatório']"
                />
              </div>
              <div class="col-12 text-caption text-grey-7">
                {{ pag.meiodescricao }} · {{ pag.portador || 'sem portador' }}
              </div>
              <div class="col-12 col-sm-5" v-if="pag.manual">
                <MgInputData
                  v-model="editar.transacao"
                  type="timestamp"
                  default-time="keep"
                  :seconds="false"
                  label="Data"
                  :rules="[(v) => !!v || 'Obrigatório']"
                />
              </div>
              <div
                v-if="trocaMes"
                class="col-12 text-caption text-orange-9 row no-wrap items-center"
              >
                <q-icon name="warning" size="xs" class="q-mr-xs" />
                Muda de mês: pode afetar a DIMP e relatórios já apurados.
              </div>
              <div class="col-12" v-if="dataMudou">
                <MgInput
                  v-model="editar.justificativa"
                  label="Por que a data mudou"
                  maxlength="300"
                  :rules="[
                    (v) => (v || '').trim().length >= 5 || 'Diga o motivo (mínimo 5 letras)',
                  ]"
                  lazy-rules
                />
              </div>
              <div class="col-12">
                <MgInput
                  v-model="editar.observacao"
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
