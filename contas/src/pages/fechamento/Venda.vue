<script setup>
// Venda com diferença (M9 doc-3): os pagamentos não fecham com o total (correção de valor,
// registro indevido, cartão duplicado pela API). Venda não fica com diferença: ou é pagamento
// ou é duplicata; o conserto é no próprio negócio.
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import { formataNumero, formataTimestamp, formataData } from '@components/formatters'
import { CONDICOES, BANDEIRAS } from '@components/cobranca/pagamento.js'
import MgInput from '@components/MgInput.vue'
import MgSelect from '@components/MgSelect.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgSelectMaquineta from '@components/MgSelectMaquineta.vue'
import { useConferenciaStore } from 'src/stores/conferenciaStore'
import ListaLancamentos from 'src/components/conferencia/ListaLancamentos.vue'
import CorrecaoPagamentoDialog from 'src/components/conferencia/CorrecaoPagamentoDialog.vue'

const route = useRoute()
const $q = useQuasar()
const store = useConferenciaStore()

const id = computed(() => Number(route.params.id))
const venda = computed(() => store.venda)
const diferenca = computed(() => venda.value?.diferenca ?? 0)
const bate = computed(() => Math.abs(diferenca.value) < 0.005)

const urlVenda = computed(() => `${process.env.NEGOCIOS_URL}/negocio/${id.value}`)

const carregar = () => store.carregarVenda(id.value)

// ---- correções ----
const dialogCorrecao = ref(false)
const corrigindo = ref(null)
const corrigir = (l) => {
  corrigindo.value = l
  dialogCorrecao.value = true
}
const indevido = (l) => {
  $q.dialog({
    title: 'Registro indevido',
    message: `Cancelar o lançamento de ${formataNumero(l.total)}? Ele sai do lote/caixa como se nunca tivesse entrado.`,
    prompt: {
      model: '',
      type: 'text',
      label: 'Justificativa',
      isValid: (v) => v.trim().length >= 5,
    },
    cancel: { label: 'Voltar', color: 'grey-8', flat: true },
    ok: { label: 'Cancelar lançamento', color: 'red-5', flat: true },
  }).onOk(async (justificativa) => {
    if (await store.indevido(l.codpagamento, justificativa)) carregar()
  })
}

// ---- incluir o pagamento que faltou ----
const OPCOES_MEIO = [
  { value: 1, label: 'Dinheiro' },
  { value: 3, label: 'Cartão Crédito' },
  { value: 4, label: 'Cartão Débito' },
]
const OPCOES_BANDEIRA = Object.entries(BANDEIRAS).map(([value, label]) => ({
  value: Number(value),
  label,
}))
const dialogIncluir = ref(false)
const incluir = ref({})
const abrirIncluir = () => {
  incluir.value = {
    meio: 3,
    principal: diferenca.value > 0 ? diferenca.value : null,
    codmaquineta: null,
    bandeira: null,
    autorizacao: '',
    parcelas: 1,
    justificativa: '',
  }
  dialogIncluir.value = true
}
async function salvarIncluir() {
  const cartao = [3, 4].includes(incluir.value.meio)
  const ret = await store.incluirPagamento(id.value, {
    ...incluir.value,
    codmaquineta: cartao ? incluir.value.codmaquineta : null,
    bandeira: cartao ? incluir.value.bandeira : null,
    autorizacao: cartao ? incluir.value.autorizacao || null : null,
    parcelas: cartao ? incluir.value.parcelas : null,
  })
  if (ret) dialogIncluir.value = false
}

onMounted(carregar)
watch(id, carregar)
</script>

<template>
  <q-page>
    <div class="q-pa-md" style="max-width: 1086px; margin: auto">
      <q-btn
        flat
        round
        icon="arrow_back"
        :to="{ name: 'fechamento' }"
        aria-label="Voltar"
        class="q-mb-sm"
      />

      <template v-if="venda">
        <q-card bordered flat class="q-mb-md">
          <q-card-section>
            <div class="row items-center">
              <div class="col">
                <div class="text-h6">
                  <a
                    :href="urlVenda"
                    target="_blank"
                    class="text-primary"
                    style="text-decoration: none"
                  >
                    Venda #{{ venda.codnegocio }}
                  </a>
                </div>
                <div class="text-caption text-grey-7">
                  {{ venda.fantasia }} · {{ venda.pdv }} ·
                  {{ formataTimestamp(venda.lancamento, 2) }}
                </div>
              </div>
              <div class="text-right">
                <div class="text-caption text-grey-7">Total</div>
                <div class="text-h6">{{ formataNumero(venda.valortotal) }}</div>
              </div>
            </div>
          </q-card-section>
          <q-card-section class="q-pt-none">
            <q-banner v-if="bate" rounded class="bg-green-1 text-green-9">
              Os pagamentos batem com o total da venda.
            </q-banner>
            <q-banner v-else rounded class="bg-red-1 text-red-9">
              {{ diferenca > 0 ? 'Faltou pagar' : 'Pagou a mais' }}
              <b>{{ formataNumero(Math.abs(diferenca)) }}</b>
            </q-banner>
          </q-card-section>
        </q-card>

        <q-card bordered flat class="q-mb-md">
          <q-card-section class="row items-center q-pb-sm">
            <div class="text-subtitle1 col">Pagamentos</div>
            <q-btn flat round size="sm" color="primary" icon="add" @click="abrirIncluir">
              <q-tooltip>Incluir o pagamento que faltou</q-tooltip>
            </q-btn>
          </q-card-section>
          <q-card-section class="q-pt-none">
            <ListaLancamentos
              :lancamentos="venda.pagamentos"
              editavel
              @corrigir="corrigir"
              @indevido="indevido"
            />
          </q-card-section>
          <q-list v-if="venda.parcelas.length" separator>
            <q-item-label header>A prazo</q-item-label>
            <q-item v-for="p in venda.parcelas" :key="p.codnegocioparcela">
              <q-item-section>
                <q-item-label>{{ CONDICOES[p.condicao] ?? p.condicao }}</q-item-label>
                <q-item-label caption>
                  vence {{ formataData(p.vencimento) }}
                  <template v-if="p.numero"> · título {{ p.numero }}</template>
                </q-item-label>
              </q-item-section>
              <q-item-section side>{{ formataNumero(p.valor) }}</q-item-section>
            </q-item>
          </q-list>
        </q-card>
      </template>
    </div>

    <CorrecaoPagamentoDialog
      v-model="dialogCorrecao"
      :lancamento="corrigindo"
      @corrigido="carregar"
    />

    <q-dialog v-model="dialogIncluir">
      <q-card flat style="width: 500px; max-width: 95vw">
        <q-form @submit.prevent="salvarIncluir">
          <q-card-section class="text-h6">Incluir pagamento</q-card-section>
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-12 col-sm-6">
                <q-select
                  v-model="incluir.meio"
                  :options="OPCOES_MEIO"
                  emit-value
                  map-options
                  outlined
                  label="Meio"
                  autofocus
                />
              </div>
              <div class="col-12 col-sm-6">
                <MgInputValor
                  v-model="incluir.principal"
                  label="Valor"
                  :min="0.01"
                  :rules="[(v) => v > 0]"
                  lazy-rules
                />
              </div>
              <template v-if="[3, 4].includes(incluir.meio)">
                <div class="col-12 col-sm-6">
                  <MgSelectMaquineta
                    v-model="incluir.codmaquineta"
                    :rules="[(v) => !!v]"
                    lazy-rules
                  />
                </div>
                <div class="col-12 col-sm-6">
                  <MgSelect
                    v-model="incluir.bandeira"
                    :options="OPCOES_BANDEIRA"
                    emit-value
                    map-options
                    outlined
                    clearable
                    label="Bandeira"
                  />
                </div>
                <div class="col-6">
                  <MgInput v-model="incluir.autorizacao" label="Autorização" maxlength="40" />
                </div>
                <div class="col-6">
                  <MgInputValor
                    v-model="incluir.parcelas"
                    label="Parcelas"
                    :decimals="0"
                    :min="1"
                    :grouping="false"
                  />
                </div>
              </template>
              <div class="col-12">
                <MgInput
                  v-model="incluir.justificativa"
                  label="Justificativa"
                  type="textarea"
                  autogrow
                  maxlength="300"
                  :rules="[(v) => (v || '').trim().length >= 5]"
                  lazy-rules
                />
              </div>
            </div>
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat label="Cancelar" color="grey-8" v-close-popup />
            <q-btn flat label="Incluir" color="primary" type="submit" :loading="store.salvando" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-page>
</template>
