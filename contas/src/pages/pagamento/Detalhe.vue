<script setup>
// Pagamento (M6.1 doc-3): o detalhe compartilhado (MgPagamentoDetalhe) com o que é do contas:
// recibos em PDF e a correção de pessoa, portador, meio, data e observação (como a liquidação).
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { notifySuccess, notifyError } from 'src/utils/notify'
import { useAuthStore } from 'src/stores/auth'
import { PERMISSOES } from 'src/constants/permissoes'
import { abrirPdf } from 'src/utils/abrirPdf'
import { configurarPagamentoLista } from 'src/utils/pagamentoLista'
import { MEIOS } from '@components/cobranca/pagamento.js'
import MgPagamentoDetalhe from '@components/MgPagamentoDetalhe.vue'
import MgInput from '@components/MgInput.vue'
import MgInputData from '@components/MgInputData.vue'
import MgSelectPessoa from '@components/MgSelectPessoa.vue'
import MgSelectPortador from '@components/MgSelectPortador.vue'

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

// meios que a correção escolhe (os internos ficam só no histórico)
const OPCOES_MEIO = [1, 2, 3, 4, 15, 16, 17, 18, 99].map((m) => ({ value: m, label: MEIOS[m] }))
// meio sugerido pelo tipo do portador (o mesmo do backend)
const meioDoTipo = (tipo) => ({ E: 1, B: 18, A: 18, C: 3 })[tipo] ?? 99

const loading = ref(false)
const id = computed(() => (route.params.id ? Number(route.params.id) : null))
const pag = computed(() => store.pagamento)

// só baixa de título feita à mão se edita (venda pelo negócio, acerto pelo acerto, boleto pelo banco)
const podeEditar = computed(() => podeMutar.value && pag.value?.estornavel)
// encontro de contas sem dinheiro continua sem portador
const semPortador = computed(() => pag.value?.operacao === 'CP' && !pag.value?.codportador)

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
    codportador: pag.value.codportador,
    meio: pag.value.meio,
    transacao: String(pag.value.transacao).slice(0, 10),
    observacao: pag.value.observacoes ?? '',
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
          <template v-if="pag.estado !== 'C' && pag.movimentos?.length">
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
              <div class="col-8" v-if="!semPortador">
                <MgSelectPortador
                  v-model="editar.codportador"
                  label="Portador"
                  sem-gaveta
                  :rules="[(v) => !!v || 'Obrigatório']"
                  @select="portadorEscolhido"
                />
              </div>
              <div class="col-4">
                <MgInputData
                  v-model="editar.transacao"
                  label="Data"
                  :rules="[(v) => !!v || 'Obrigatório']"
                />
              </div>
              <div class="col-8" v-if="!semPortador">
                <q-select
                  v-model="editar.meio"
                  :options="OPCOES_MEIO"
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
