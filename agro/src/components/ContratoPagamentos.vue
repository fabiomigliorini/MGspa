<script setup>
import { ref, computed } from 'vue'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import { useContratoDetalheStore } from 'src/stores/contratoDetalhe'
import { notifySuccess, notifyError } from 'src/utils/notify'
import {
  formataNumero,
  formataReal,
  formataData,
  formataDataIso,
  arredonda,
} from '@components/formatters'
import MgEmptyState from '@components/MgEmptyState.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgInputData from '@components/MgInputData.vue'
import MgSelectPortador from '@components/MgSelectPortador.vue'
import ContratoParcelasDialog from 'components/ContratoParcelasDialog.vue'

// Card "Parcelas de pagamento". Especialista no previsto x recebido do contrato:
// gera várias parcelas de uma vez (ContratoParcelasDialog), edita uma parcela
// e confirma o recebimento (valor real pode divergir do previsto). Lê do store
// da tela e persiste pelas actions (salvarPagamento/excluirPagamento/confirmar).
const $q = useQuasar()
const store = useContratoDetalheStore()
const {
  contrato,
  cod,
  pagamentos,
  previsto,
  pago,
  liquidoSc,
  fixado,
  saldoPagar,
  temUsd,
  previstoBrl,
  previstoUsd,
  recebidoUsd,
  cotacaoMediaUsd,
  aReceberBrl,
  aReceberUsd,
} = storeToRefs(store)

function n(v) {
  return Number(v) || 0
}

// Fixação de origem de uma parcela (moeda/preço). Base do rótulo por linha e da
// conversão US$ no recebimento. `ehUsd`/`fixacaoDaParcela` são fonte única no store.
const fixDaParcela = store.fixacaoDaParcela
function ehUsdParcela(p) {
  return store.ehUsd(fixDaParcela(p))
}
function rotuloFixacao(p) {
  const f = fixDaParcela(p)
  if (!f) return null
  return `${store.ehUsd(f) ? 'US$' : 'R$'} ${fmt(f.preco, 2)}/sc`
}

// Saldo a parcelar em sacas = fixado − sacas já parceladas (modo SACAS). O saldo
// em valor vem do store (saldoPagar). Ambos alimentam o gerador de parcelas e
// espelham a trava do backend; o servidor é quem garante.
const sacasParceladas = computed(() =>
  pagamentos.value.reduce((s, p) => s + (p.modo === 'SACAS' ? n(p.sacas) : 0), 0),
)
const saldoSacas = computed(() => Math.max(0, n(fixado.value) - sacasParceladas.value))
// Formatação vem dos helpers compartilhados (@components/formatters).
const rs = formataReal
const fmtData = formataData
function fmt(v, dec = 0) {
  return formataNumero(v, dec)
}

const modosParcela = [
  { label: 'Valor', value: 'VALOR' },
  { label: 'Sacas', value: 'SACAS' },
]

// O botão "+" abre o gerador de várias parcelas; a edição de uma parcela segue
// no dialog abaixo (form único).
const parcelasDialog = ref(false)

// --- Editar uma parcela (previsto) ---
const dialogParcela = ref(false)
const salvandoParcela = ref(false)
const formParcela = ref({})
// Guarda os valores originais da parcela em edição: ao reescrevê-la, o que ela já
// ocupava volta pro saldo (teto = saldo + original), espelhando a trava do backend.
const parcelaOriginal = ref({})
function editarParcela(p) {
  parcelaOriginal.value = { ...p }
  formParcela.value = { ...p }
  dialogParcela.value = true
}
const maxValorParcela = computed(() => saldoPagar.value + n(parcelaOriginal.value.valor))
const maxSacasParcela = computed(() => saldoSacas.value + n(parcelaOriginal.value.sacas))
// Em modo SACAS o valor previsto acompanha as sacas (× líquido/sc) — mas SÓ quando
// o usuário edita as sacas, nunca ao abrir (senão sobrescreveria o valor gravado).
function editarSacas(val) {
  formParcela.value.sacas = val
  formParcela.value.valor = arredonda(n(val) * liquidoSc.value, 2)
}

// Fixação da parcela em edição: em US$ o valor previsto = sacas × preço × cotação
// (o vínculo codcontratofixacao já vem no {...p}). Recalcula ao mexer nas sacas/cotação.
const editFix = computed(() => store.fixacaoDaParcela(formParcela.value))
const editUsd = computed(() => store.ehUsd(editFix.value))
function recalcUsd() {
  formParcela.value.valor = arredonda(
    n(formParcela.value.sacas) * n(editFix.value?.preco) * n(formParcela.value.cotacao),
    2,
  )
}
function editarSacasUsd(val) {
  formParcela.value.sacas = val
  recalcUsd()
}
function editarCotacaoUsd(val) {
  formParcela.value.cotacao = val
  recalcUsd()
}
async function salvarParcela() {
  if (salvandoParcela.value) return
  salvandoParcela.value = true
  try {
    await store.salvarPagamento(formParcela.value)
    notifySuccess('Parcela salva!')
    dialogParcela.value = false
  } catch (e) {
    notifyError(e)
  } finally {
    salvandoParcela.value = false
  }
}
function excluirParcela(p) {
  $q.dialog({
    title: 'Excluir',
    message: 'Excluir esta parcela?',
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Excluir', color: 'red-5', flat: true },
  }).onOk(async () => {
    try {
      await store.excluirPagamento(p)
      notifySuccess('Excluído!')
    } catch (e) {
      notifyError(e)
    }
  })
}

// --- Confirmar recebimento (valor real pode divergir do previsto) ---
const confirmDialog = ref(false)
const confirmSalvando = ref(false)
const confirmForm = ref({})
function abrirConfirmar(p) {
  const usd = ehUsdParcela(p)
  const f = fixDaParcela(p)
  confirmForm.value = {
    codcontratopagamento: p.codcontratopagamento,
    forma: p.forma,
    usd,
    sacas: n(p.sacas),
    preco: n(f?.preco),
    datarecebido: formataDataIso(new Date()),
    // US$: o R$ recebido é COMPUTADO da cotação (não digitado). BRL: sugere o previsto.
    valorrecebido: usd ? null : p.valor,
    cotacaorecebido: null,
    codportador: p.codportador || contrato.value?.codportador || null,
  }
  confirmDialog.value = true
}
// R$ recebido de uma parcela US$ = sacas × preço × cotação do dia (preview ao vivo).
const confirmValorUsd = computed(() =>
  arredonda(
    n(confirmForm.value.sacas) * n(confirmForm.value.preco) * n(confirmForm.value.cotacaorecebido),
    2,
  ),
)
async function confirmarRecebimento() {
  if (confirmSalvando.value) return
  confirmSalvando.value = true
  try {
    await store.confirmarRecebimento(confirmForm.value)
    notifySuccess('Recebimento confirmado!')
    confirmDialog.value = false
  } catch (e) {
    notifyError(e)
  } finally {
    confirmSalvando.value = false
  }
}
</script>

<template>
  <q-card flat bordered class="q-mb-md">
    <q-item>
      <q-item-section>
        <q-item-label class="text-subtitle1">Parcelas de pagamento</q-item-label>
        <!-- Moeda-aware: com fixação US$ nunca soma BRL+US$ num número só. -->
        <q-item-label v-if="temUsd" caption>
          Previsto R$ {{ rs(previstoBrl) }} · US$ {{ fmt(previstoUsd, 2) }}
          · Recebido R$ {{ rs(pago) }}<span v-if="recebidoUsd">
            (US$ {{ fmt(recebidoUsd, 2) }} @ {{ fmt(cotacaoMediaUsd, 4) }})</span
          >
          · A receber R$ {{ rs(aReceberBrl) }} · US$ {{ fmt(aReceberUsd, 2) }}
        </q-item-label>
        <q-item-label v-else caption>
          Previsto {{ rs(previsto) }} · Recebido {{ rs(pago) }} · A pagar {{ rs(saldoPagar) }}
        </q-item-label>
      </q-item-section>
      <q-item-section side>
        <q-btn flat round size="sm" color="primary" icon="add" @click="parcelasDialog = true">
          <q-tooltip>Novas parcelas</q-tooltip>
        </q-btn>
      </q-item-section>
    </q-item>
    <q-separator />
    <q-list separator>
      <q-item v-for="p in pagamentos" :key="p.codcontratopagamento">
        <q-item-section avatar>
          <q-avatar
            :color="p.datarecebido ? 'green-5' : 'grey-4'"
            :text-color="p.datarecebido ? 'white' : 'grey-8'"
            :icon="p.datarecebido ? 'check' : 'schedule'"
          />
        </q-item-section>
        <q-item-section>
          <q-item-label>
            {{ rs(p.valor) }}
            <span v-if="p.sacas" class="text-caption text-grey-7">({{ fmt(p.sacas) }} sc)</span>
            <q-badge
              v-if="rotuloFixacao(p)"
              :color="ehUsdParcela(p) ? 'deep-purple-6' : 'blue-grey-5'"
              :label="rotuloFixacao(p)"
              class="q-ml-xs"
            />
          </q-item-label>
          <q-item-label caption>
            Prev. {{ fmtData(p.data) }}
            <span v-if="p.datarecebido" class="text-green-8">
              · Receb. {{ fmtData(p.datarecebido) }} {{ rs(p.valorrecebido) }}
              <span v-if="p.cotacaorecebido">@ {{ fmt(p.cotacaorecebido, 4) }}</span>
            </span>
            <span v-if="p.observacao"> · {{ p.observacao }}</span>
          </q-item-label>
        </q-item-section>
        <q-item-section side>
          <div class="row items-center no-wrap">
            <q-btn
              v-if="!p.datarecebido"
              flat
              dense
              round
              size="sm"
              color="green-7"
              icon="task_alt"
              @click="abrirConfirmar(p)"
            >
              <q-tooltip>Confirmar recebimento</q-tooltip>
            </q-btn>
            <MgInfoCriacao :registro="p" />
            <q-btn
              flat
              dense
              round
              size="sm"
              color="grey-7"
              icon="edit"
              @click="editarParcela(p)"
            />
            <q-btn
              flat
              dense
              round
              size="sm"
              color="grey-7"
              icon="delete"
              @click="excluirParcela(p)"
            />
          </div>
        </q-item-section>
      </q-item>
      <MgEmptyState v-if="!pagamentos.length" plain icon="payments">
        Nenhuma parcela lançada.
      </MgEmptyState>
    </q-list>

    <!-- Dialog Parcela (editar previsto) -->
    <q-dialog v-model="dialogParcela">
      <q-card flat style="width: 440px; max-width: 95vw">
        <q-form @submit.prevent="salvarParcela">
          <q-card-section class="bg-primary text-white">
            <div class="text-h6">Editar parcela</div>
          </q-card-section>
          <q-card-section class="q-pt-md">
            <div class="row q-col-gutter-md">
              <div v-if="rotuloFixacao(formParcela)" class="col-12">
                <q-badge
                  :color="editUsd ? 'deep-purple-6' : 'blue-grey-5'"
                  :label="`Fixação ${rotuloFixacao(formParcela)}`"
                />
              </div>
              <!-- BRL escolhe modo; US$ é sempre em sacas (o valor vem da cotação). -->
              <div v-if="!editUsd" class="col-12 col-sm-6">
                <q-select
                  v-model="formParcela.modo"
                  :options="modosParcela"
                  label="Modo de pagamento"
                  outlined
                  emit-value
                  map-options
                />
              </div>
              <div class="col-12 col-sm-6">
                <MgInputData
                  v-model="formParcela.data"
                  label="Data prevista"
                  type="date"
                  lazy-rules
                  :rules="[(v) => !!v]"
                />
              </div>
              <!-- US$: sacas + cotação computam o valor previsto. -->
              <template v-if="editUsd">
                <div class="col-12 col-sm-6">
                  <MgInputValor
                    :model-value="formParcela.sacas"
                    :decimals="0"
                    :max="maxSacasParcela"
                    suffix="sc"
                    label="Sacas"
                    lazy-rules
                    :rules="[(v) => v > 0 || 'Informe as sacas']"
                    @update:model-value="editarSacasUsd"
                  />
                </div>
                <div class="col-12 col-sm-6">
                  <MgInputValor
                    :model-value="formParcela.cotacao"
                    :decimals="4"
                    prefix="R$"
                    label="Cotação do dólar"
                    lazy-rules
                    :rules="[(v) => v > 0 || 'Informe a cotação']"
                    @update:model-value="editarCotacaoUsd"
                  />
                </div>
                <div class="col-12 col-sm-6">
                  <MgInputValor
                    :model-value="formParcela.valor"
                    :decimals="2"
                    prefix="R$"
                    label="Valor previsto"
                    readonly
                    bg-color="grey-2"
                  />
                </div>
              </template>
              <template v-else>
                <div v-if="formParcela.modo === 'SACAS'" class="col-12 col-sm-6">
                  <MgInputValor
                    :model-value="formParcela.sacas"
                    :decimals="0"
                    :max="maxSacasParcela"
                    suffix="sc"
                    label="Sacas"
                    lazy-rules
                    :rules="[(v) => v > 0 || 'Informe as sacas']"
                    @update:model-value="editarSacas"
                  />
                </div>
                <div class="col-12 col-sm-6">
                  <MgInputValor
                    v-model="formParcela.valor"
                    :decimals="2"
                    :max="maxValorParcela"
                    prefix="R$"
                    label="Valor previsto"
                    lazy-rules
                    :rules="[(v) => v > 0 || 'Informe o valor']"
                  />
                </div>
              </template>
              <div class="col-12">
                <MgSelectPortador
                  v-model="formParcela.codportador"
                  label="Portador (conta que recebe)"
                />
              </div>
              <div class="col-12">
                <q-input v-model="formParcela.observacao" label="Observação" outlined />
              </div>
            </div>
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
            <q-btn type="submit" flat label="Salvar" color="primary" :loading="salvandoParcela" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <!-- Gerador de várias parcelas (parte do saldo FIXADO, não do contratado) -->
    <ContratoParcelasDialog
      v-model="parcelasDialog"
      :cod="cod"
      :contrato="contrato"
      @saved="store.carregar()"
    />

    <!-- Dialog Confirmar recebimento -->
    <q-dialog v-model="confirmDialog">
      <q-card flat style="width: 440px; max-width: 95vw">
        <q-form @submit.prevent="confirmarRecebimento">
          <q-card-section class="bg-primary text-white">
            <div class="text-h6">Confirmar recebimento</div>
          </q-card-section>
          <q-card-section class="q-pt-md">
            <div class="row q-col-gutter-md">
              <div class="col-12 col-sm-6">
                <MgInputData
                  v-model="confirmForm.datarecebido"
                  label="Data recebida"
                  type="date"
                  autofocus
                  lazy-rules
                  :rules="[(v) => !!v]"
                />
              </div>
              <!-- US$: informa a cotação do dia; o R$ recebido é computado (sacas ×
                   preço × cotação). BRL: informa o valor recebido direto. -->
              <div v-if="confirmForm.usd" class="col-12 col-sm-6">
                <MgInputValor
                  v-model="confirmForm.cotacaorecebido"
                  :decimals="4"
                  prefix="R$"
                  label="Cotação do dólar"
                  lazy-rules
                  :rules="[(v) => v > 0 || 'Informe a cotação']"
                />
              </div>
              <div v-else class="col-12 col-sm-6">
                <MgInputValor
                  v-model="confirmForm.valorrecebido"
                  :decimals="2"
                  prefix="R$"
                  label="Valor recebido"
                  lazy-rules
                  :rules="[(v) => v > 0]"
                />
              </div>
              <div v-if="confirmForm.usd" class="col-12">
                <div class="text-caption text-grey-7">
                  {{ fmt(confirmForm.sacas) }} sc × US$ {{ fmt(confirmForm.preco, 2) }} × cotação
                  = <b class="text-green-9">{{ rs(confirmValorUsd) }}</b>
                </div>
              </div>
              <div class="col-12">
                <MgSelectPortador
                  v-model="confirmForm.codportador"
                  label="Portador (conta que recebeu)"
                  lazy-rules
                  :rules="[(v) => !!v || 'Informe o portador']"
                />
              </div>
            </div>
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
            <q-btn
              type="submit"
              flat
              label="Confirmar"
              color="primary"
              :loading="confirmSalvando"
              :disable="confirmForm.usd && !confirmForm.cotacaorecebido"
            />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-card>
</template>
