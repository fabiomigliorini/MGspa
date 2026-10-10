<script setup>
// Pagamentos não resolvidos, o mesmo no contas e no PDV: o pagamento é o fato (o dinheiro que
// andou) e a amarração é outra coisa (conceito do Fábio, 09/10/2026). Aqui aparece o que não
// bate: o saldo (pago − devolvido) diferente do que está amarrado — o PIX que caiu sem ninguém
// esperando, a cobrança que confirmou depois, a venda cancelada que deixou o PIX solto, o
// desamarrado. Cada linha mostra o que sobra livre e o que se pode fazer com ele, cada botão uma
// coisa: amarrar a títulos (abre o Receber Título com o pagamento), adiantamento (o mesmo
// diálogo do vale), "já lançado" (o digitado é o mesmo dinheiro: fica o integrado) e devolver
// (PIX e cartão). Só os portadores em que o usuário tem papel.
import { ref, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { api } from 'src/services/api'
import { formataNumero, formataData, formataTimestamp } from '@components/formatters'
import { visualPagamento } from '@components/cobranca/pagamento.js'
import LogoPagamento from '@components/cobranca/LogoPagamento.vue'
import MgEmptyState from '@components/MgEmptyState.vue'
import MgInput from '@components/MgInput.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgAdiantamentoDialog from '@components/MgAdiantamentoDialog.vue'

const props = defineProps({
  // 'v1/pagamento' (contas) ou 'v1/pdv/pagamento' (PDV)
  endpoint: { type: String, required: true },
  // o que vai em toda chamada (no PDV, o uuid do aparelho)
  fixos: { type: Object, default: () => ({}) },
  // rota do Receber Título com o pagamento: (pendente) => rota
  rotaTitulos: { type: Function, required: true },
  // rota do detalhe do pagamento: (codpagamento) => rota (opcional)
  rotaDetalhe: { type: Function, default: null },
  // o MgAdiantamentoDialog do app: { formas, contexto, finalizar, comData, comFilial,
  // filialPadrao, padrao }
  adiantamento: { type: Object, required: true },
})

const $q = useQuasar()

const carregando = ref(false)
const pendentes = ref([])

const notificar = (type, message) =>
  $q.notify({
    type,
    message,
    color: type === 'positive' ? 'green-5' : 'red-5',
    icon: type === 'positive' ? 'done' : 'error',
  })
const erro = (e, padrao) =>
  notificar('negative', e?.response?.data?.message ?? e?.message ?? padrao)

const carregar = async () => {
  carregando.value = true
  try {
    const { data } = await api.get(`${props.endpoint}/pendentes`, { params: props.fixos })
    pendentes.value = data.data ?? []
  } catch (e) {
    erro(e, 'Erro ao carregar')
  } finally {
    carregando.value = false
  }
}

onMounted(carregar)

const descricao = (p) => `${p.meiodescricao} #${p.codpagamento}`

// ---- adiantamento ----
const dialogAdiantamento = ref(false)
const pagamentoAdiantamento = ref(null)
const abrirAdiantamento = (p) => {
  pagamentoAdiantamento.value = {
    codpagamento: p.codpagamento,
    livre: p.livre,
    descricao: descricao(p),
    codpessoa: p.codpessoa,
    entrada: p.entrada,
  }
  dialogAdiantamento.value = true
}
const adiantamentoLancado = () => {
  notificar('positive', 'Adiantamento lançado')
  carregar()
}

// ---- já lançado ----
const dialogDuplicado = ref(false)
const integrado = ref(null)
const duplicados = ref([])
const codduplicado = ref(null)
const justificativaDuplicado = ref('')
const salvando = ref(false)

const abrirDuplicado = async (p) => {
  integrado.value = p
  duplicados.value = []
  codduplicado.value = null
  justificativaDuplicado.value = ''
  dialogDuplicado.value = true
  try {
    const { data } = await api.get(`${props.endpoint}/${p.codpagamento}/duplicados`, {
      params: props.fixos,
    })
    duplicados.value = data.data ?? []
    codduplicado.value = duplicados.value[0]?.codpagamento ?? null
  } catch (e) {
    erro(e, 'Erro ao procurar o lançado')
  }
}

const confirmarDuplicado = async () => {
  if (salvando.value) return
  salvando.value = true
  try {
    await api.post(`${props.endpoint}/${integrado.value.codpagamento}/ja-lancado`, {
      ...props.fixos,
      codpagamento: codduplicado.value,
      justificativa: justificativaDuplicado.value,
    })
    notificar('positive', 'Ficou o pagamento integrado; o digitado foi cancelado')
    dialogDuplicado.value = false
    carregar()
  } catch (e) {
    erro(e, 'Erro ao amarrar')
  } finally {
    salvando.value = false
  }
}

// ---- devolver ----
const dialogDevolver = ref(false)
const devolvendo = ref(null)
const valorDevolver = ref(null)
const justificativaDevolver = ref('')

const abrirDevolver = (p) => {
  devolvendo.value = p
  valorDevolver.value = p.livre
  justificativaDevolver.value = ''
  dialogDevolver.value = true
}

const confirmarDevolver = async () => {
  if (salvando.value) return
  salvando.value = true
  try {
    await api.post(`${props.endpoint}/${devolvendo.value.codpagamento}/devolver`, {
      ...props.fixos,
      valor: valorDevolver.value,
      justificativa: justificativaDevolver.value,
    })
    notificar('positive', 'Devolução registrada')
    dialogDevolver.value = false
    carregar()
  } catch (e) {
    erro(e, 'Erro ao devolver')
  } finally {
    salvando.value = false
  }
}

const podeDevolver = (p) => p.operador && p.entrada && [3, 4, 17].includes(Number(p.meio))
</script>

<template>
  <div>
    <q-card bordered flat>
      <q-card-section class="text-grey-9 text-overline row items-center">
        NÃO RESOLVIDOS ({{ pendentes.length }})
        <q-space />
        <q-btn flat round size="sm" icon="refresh" color="grey-7" @click="carregar">
          <q-tooltip>Atualizar</q-tooltip>
        </q-btn>
      </q-card-section>
      <MgEmptyState v-if="!carregando && !pendentes.length" plain icon="task_alt">
        Nenhum pagamento sem amarração
      </MgEmptyState>
      <q-list v-else separator>
        <q-item v-for="p in pendentes" :key="p.codpagamento">
          <q-item-section avatar>
            <logo-pagamento v-bind="visualPagamento(p)" />
          </q-item-section>
          <q-item-section>
            <q-item-label>
              <router-link
                v-if="rotaDetalhe"
                :to="rotaDetalhe(p.codpagamento)"
                class="text-primary"
                style="text-decoration: none"
              >
                {{ descricao(p) }}
              </router-link>
              <template v-else>{{ descricao(p) }}</template>
            </q-item-label>
            <q-item-label caption>
              {{ formataTimestamp(p.transacao) }} · {{ p.portador }}
              <template v-if="p.pdv"> · {{ p.pdv }}</template>
            </q-item-label>
            <q-item-label caption>
              {{ p.pessoa || 'sem pessoa' }}
              <template v-if="p.autorizacao"> · aut. {{ p.autorizacao }}</template>
            </q-item-label>
          </q-item-section>
          <q-item-section side>
            <q-item-label
              class="text-weight-bold"
              :class="p.entrada ? 'text-green-8' : 'text-red-8'"
            >
              R$ {{ formataNumero(p.total) }}
            </q-item-label>
            <q-item-label caption class="text-orange-9">
              livre R$ {{ formataNumero(p.livre) }}
            </q-item-label>
          </q-item-section>
          <q-item-section side>
            <div class="row no-wrap">
              <q-btn
                flat
                round
                size="sm"
                icon="receipt_long"
                color="grey-7"
                :to="rotaTitulos(p)"
                :disable="p.livre <= 0"
              >
                <q-tooltip>Amarrar a títulos</q-tooltip>
              </q-btn>
              <q-btn
                flat
                round
                size="sm"
                icon="savings"
                color="grey-7"
                :disable="p.livre <= 0"
                @click="abrirAdiantamento(p)"
              >
                <q-tooltip>Lançar como vale / adiantamento</q-tooltip>
              </q-btn>
              <q-btn
                v-if="p.integrado && p.operador"
                flat
                round
                size="sm"
                icon="content_copy"
                color="grey-7"
                @click="abrirDuplicado(p)"
              >
                <q-tooltip>Já lançado: é o mesmo que um pagamento digitado</q-tooltip>
              </q-btn>
              <q-btn
                v-if="podeDevolver(p)"
                flat
                round
                size="sm"
                icon="undo"
                color="grey-7"
                :disable="p.livre <= 0"
                @click="abrirDevolver(p)"
              >
                <q-tooltip>Devolver (PIX ou cartão)</q-tooltip>
              </q-btn>
            </div>
          </q-item-section>
        </q-item>
      </q-list>
      <q-inner-loading :showing="carregando" />
    </q-card>

    <MgAdiantamentoDialog
      v-model="dialogAdiantamento"
      :formas="adiantamento.formas"
      :contexto="adiantamento.contexto"
      :finalizar="adiantamento.finalizar"
      :com-data="!!adiantamento.comData"
      :com-filial="!!adiantamento.comFilial"
      :filial-padrao="adiantamento.filialPadrao ?? null"
      :padrao="adiantamento.padrao ?? {}"
      :pagamento="pagamentoAdiantamento"
      @finalizado="adiantamentoLancado"
    />

    <!-- já lançado: escolhe o digitado que é o mesmo dinheiro -->
    <q-dialog v-model="dialogDuplicado">
      <q-card flat style="width: 600px; max-width: 90vw">
        <q-form @submit.prevent="confirmarDuplicado">
          <q-card-section class="text-grey-9 text-overline">JÁ LANÇADO</q-card-section>
          <q-separator inset />
          <q-card-section v-if="integrado" class="text-body2">
            {{ descricao(integrado) }} · R$ {{ formataNumero(integrado.total) }} ·
            {{ formataTimestamp(integrado.transacao) }}
            <div class="text-caption text-grey-7">
              Fica este, que veio do banco/maquineta. O digitado escolhido é cancelado como registro
              indevido e as amarrações dele passam para este.
            </div>
          </q-card-section>
          <q-list v-if="duplicados.length" separator>
            <q-item v-for="d in duplicados" :key="d.codpagamento" tag="label">
              <q-item-section avatar>
                <q-radio v-model="codduplicado" :val="d.codpagamento" />
              </q-item-section>
              <q-item-section>
                <q-item-label>
                  {{ d.meiodescricao }} #{{ d.codpagamento }} · R$ {{ formataNumero(d.total) }}
                </q-item-label>
                <q-item-label caption>
                  {{ formataData(d.transacao) }}
                  <template v-if="d.maquineta"> · {{ d.maquineta }}</template>
                  <template v-if="d.autorizacao"> · aut. {{ d.autorizacao }}</template>
                  <template v-if="d.codnegocio"> · venda {{ d.codnegocio }}</template>
                  <template v-if="d.titulos"> · {{ d.titulos }} título(s)</template>
                </q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
          <MgEmptyState v-else plain icon="search_off">
            Nenhum pagamento digitado com o mesmo valor por perto
          </MgEmptyState>
          <q-card-section>
            <MgInput
              v-model="justificativaDuplicado"
              label="Justificativa"
              maxlength="300"
              autofocus
              :rules="[(v) => (v || '').trim().length >= 5 || 'Mínimo 5 letras']"
            />
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
            <q-btn
              flat
              label="É este"
              type="submit"
              color="primary"
              :disable="!codduplicado"
              :loading="salvando"
            />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <!-- devolver: registra a devolução do PIX / o cancelamento no cartão -->
    <q-dialog v-model="dialogDevolver">
      <q-card flat style="width: 400px; max-width: 90vw">
        <q-form @submit.prevent="confirmarDevolver">
          <q-card-section class="text-grey-9 text-overline">DEVOLVER</q-card-section>
          <q-separator inset />
          <q-card-section v-if="devolvendo">
            <div class="text-body2 q-mb-md">
              {{ descricao(devolvendo) }} · livre R$ {{ formataNumero(devolvendo.livre) }}
            </div>
            <div class="row q-col-gutter-md">
              <div class="col-12">
                <MgInputValor
                  v-model="valorDevolver"
                  label="Valor devolvido"
                  prefix="R$"
                  :min="0.01"
                  autofocus
                  :rules="[(v) => v > 0 && v <= devolvendo.livre]"
                />
              </div>
              <div class="col-12">
                <MgInput
                  v-model="justificativaDevolver"
                  label="Por que devolveu"
                  maxlength="300"
                  :rules="[(v) => (v || '').trim().length >= 5 || 'Mínimo 5 letras']"
                />
              </div>
            </div>
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
            <q-btn flat label="Devolver" type="submit" color="primary" :loading="salvando" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </div>
</template>
