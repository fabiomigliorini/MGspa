<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { api } from 'src/services/api'
import { useCadastro } from 'src/composables/useCadastro'
import MgInputValor from '@components/MgInputValor.vue'
import MgInputData from '@components/MgInputData.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'

const route = useRoute()
const cod = Number(route.params.codcontrato)

const fixCad = useCadastro(`contrato/${cod}/fixacao`, 'codfixacao', 'Fixação')
const pagCad = useCadastro(`contrato/${cod}/pagamento`, 'codpagamento', 'Pagamento')

const contrato = ref(null)
const carregando = ref(false)

const moedas = [
  { label: 'R$', value: 'BRL' },
  { label: 'US$', value: 'USD' },
]
const corTipo = { FIXO: 'green-7', FIXAR: 'orange-8', BARTER: 'deep-purple-6' }

function n(v) {
  return Number(v) || 0
}
function fmt(v, dec = 0) {
  if (v === null || v === undefined || v === '') return '—'
  return Number(v).toLocaleString('pt-BR', { minimumFractionDigits: dec, maximumFractionDigits: dec })
}
function rs(v) {
  return 'R$ ' + fmt(v, 2)
}
function fmtData(d) {
  if (!d) return ''
  const [a, m, dia] = d.slice(0, 10).split('-')
  return `${dia}/${m}/${a}`
}

// ---- Totais / reconciliação ----
const contratado = computed(() => n(contrato.value?.quantidade))
const carregado = computed(() => n(contrato.value?.carregado))
const saldo = computed(() => contratado.value - carregado.value)
const fixado = computed(() => n(contrato.value?.fixado))
const afixar = computed(() => contratado.value - fixado.value)
const valornf = computed(() => n(contrato.value?.valornf))
const pago = computed(() => n(contrato.value?.pago))

const precoMedio = computed(() => {
  if (!contrato.value) return 0
  if (contrato.value.tipo === 'FIXO') return n(contrato.value.preco)
  const fx = (contrato.value.ContratoFixacaoS || []).filter((f) => !f.inativo)
  const q = fx.reduce((s, f) => s + n(f.quantidade), 0)
  const v = fx.reduce((s, f) => s + n(f.quantidade) * n(f.precoreal), 0)
  return q > 0 ? v / q : 0
})
const valorCarregado = computed(() => carregado.value * precoMedio.value)

// "Bate?" — valor carregado x NFs x pago (tolerância de centavos)
const difNf = computed(() => valornf.value - valorCarregado.value)
const difPago = computed(() => pago.value - valornf.value)
const bate = computed(
  () => Math.abs(difNf.value) < 1 && Math.abs(difPago.value) < 1 && valornf.value > 0,
)

const fixacoes = computed(() => (contrato.value?.ContratoFixacaoS || []).filter((f) => !f.inativo))
const pagamentos = computed(() => (contrato.value?.ContratoPagamentoS || []).filter((p) => !p.inativo))
const embarques = computed(() => contrato.value?.EmbarqueContratoS || [])

async function recarregar() {
  carregando.value = true
  try {
    const { data } = await api.get(`v1/contrato/${cod}`)
    contrato.value = data
  } finally {
    carregando.value = false
  }
}

// ---- Fixação ----
function novaFixacao() {
  fixCad.abrirNovo({ data: new Date().toISOString().slice(0, 10), moeda: 'BRL' })
}
async function salvarFixacao() {
  await fixCad.salvar((f) => ({
    data: f.data,
    quantidade: f.quantidade,
    preco: f.preco,
    moeda: f.moeda,
    dolar: f.moeda === 'USD' ? f.dolar : null,
  }))
  await recarregar()
}
async function excluirFixacao(f) {
  fixCad.excluir(f)
  setTimeout(recarregar, 400)
}
const previewPrecoReal = computed(() => {
  const f = fixCad.form
  if (!f.preco) return null
  return f.moeda === 'USD' && f.dolar ? n(f.preco) * n(f.dolar) : n(f.preco)
})

// ---- Pagamento ----
function novoPagamento() {
  pagCad.abrirNovo({ data: new Date().toISOString().slice(0, 10) })
}
async function salvarPagamento() {
  await pagCad.salvar()
  await recarregar()
}
async function excluirPagamento(p) {
  pagCad.excluir(p)
  setTimeout(recarregar, 400)
}

onMounted(recarregar)
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <!-- Cabeçalho -->
      <q-card bordered flat class="q-mb-md">
        <q-card-section class="row items-center no-wrap">
          <q-btn flat round size="sm" color="grey-7" icon="arrow_back" :to="{ name: 'contratos' }" />
          <q-avatar
            :color="corTipo[contrato?.tipo] || 'indigo-7'"
            text-color="white"
            icon="description"
            class="q-ml-sm"
          />
          <div class="col q-ml-md">
            <div class="text-h6">
              {{ contrato?.contrato || 'Contrato' }}
              <q-chip
                v-if="contrato"
                dense
                square
                :color="corTipo[contrato.tipo]"
                text-color="white"
                :label="contrato.tipo"
              />
            </div>
            <div class="text-caption text-grey-7">
              {{ contrato?.Pessoa?.fantasia || contrato?.Pessoa?.pessoa }} ·
              {{ contrato?.Cultura?.cultura }}
              <span v-if="contrato?.Safra"> · {{ contrato.Safra.safra }}</span>
            </div>
          </div>
          <MgInfoCriacao
            :usuariocriacao="contrato?.usuariocriacao"
            :criacao="contrato?.criacao"
            :usuarioalteracao="contrato?.usuarioalteracao"
            :alteracao="contrato?.alteracao"
          />
          <q-btn flat color="green-7" icon="local_shipping" label="Embarque" :to="{ name: 'embarque' }" />
        </q-card-section>
      </q-card>

      <!-- Reconciliação físico / fiscal / financeiro -->
      <div class="row q-col-gutter-md q-mb-md">
        <div class="col-12 col-md-4">
          <q-card flat bordered class="full-height">
            <q-card-section>
              <div class="row items-center text-blue-grey-8">
                <q-icon name="local_shipping" class="q-mr-sm" /><span class="text-subtitle2">Físico</span>
              </div>
              <div class="text-h5 q-mt-sm">{{ fmt(carregado) }} <span class="text-caption">/ {{ fmt(contratado) }} sc</span></div>
              <q-linear-progress
                :value="contratado ? Math.min(1, carregado / contratado) : 0"
                color="green-6"
                track-color="grey-3"
                size="8px"
                rounded
                class="q-my-sm"
              />
              <div class="text-caption text-grey-7">Saldo a embarcar: <b>{{ fmt(saldo) }} sc</b></div>
            </q-card-section>
          </q-card>
        </div>
        <div class="col-6 col-md-4">
          <q-card flat bordered class="full-height">
            <q-card-section>
              <div class="row items-center text-deep-orange-8">
                <q-icon name="receipt_long" class="q-mr-sm" /><span class="text-subtitle2">Fiscal (NFs)</span>
              </div>
              <div class="text-h5 q-mt-sm">{{ rs(valornf) }}</div>
              <div class="text-caption text-grey-7 q-mt-sm">
                Valor carregado: {{ rs(valorCarregado) }}
              </div>
            </q-card-section>
          </q-card>
        </div>
        <div class="col-6 col-md-4">
          <q-card flat bordered class="full-height">
            <q-card-section>
              <div class="row items-center text-teal-8">
                <q-icon name="payments" class="q-mr-sm" /><span class="text-subtitle2">Financeiro</span>
              </div>
              <div class="text-h5 q-mt-sm">{{ rs(pago) }}</div>
              <div class="text-caption text-grey-7 q-mt-sm">Pago pelo comprador</div>
            </q-card-section>
          </q-card>
        </div>
      </div>

      <!-- Bate? -->
      <q-banner v-if="bate" rounded class="bg-green-1 text-green-9 q-mb-md">
        <template #avatar><q-icon name="verified" color="green-7" /></template>
        Tudo confere: valor carregado, NFs emitidas e pagamentos batem.
      </q-banner>
      <q-banner v-else rounded class="bg-amber-1 text-amber-9 q-mb-md">
        <template #avatar><q-icon name="balance" color="amber-8" /></template>
        NFs − carregado: <b>{{ rs(difNf) }}</b> · Pago − NFs: <b>{{ rs(difPago) }}</b>
      </q-banner>

      <!-- Fixação de preço -->
      <q-card flat bordered class="q-mb-md">
        <q-item>
          <q-item-section>
            <q-item-label class="text-subtitle1">Fixação de preço</q-item-label>
            <q-item-label caption>
              Fixado {{ fmt(fixado) }} sc · A fixar {{ fmt(afixar) }} sc · Preço médio
              {{ rs(precoMedio) }}/sc
            </q-item-label>
          </q-item-section>
          <q-item-section side>
            <q-btn flat round size="sm" color="primary" icon="add" @click="novaFixacao">
              <q-tooltip>Nova fixação</q-tooltip>
            </q-btn>
          </q-item-section>
        </q-item>
        <q-separator />
        <q-list separator>
          <q-item v-for="f in fixacoes" :key="f.codcontratofixacao">
            <q-item-section>
              <q-item-label>{{ fmt(f.quantidade) }} sc · {{ rs(f.precoreal) }}/sc</q-item-label>
              <q-item-label caption>
                {{ fmtData(f.data) }}
                <span v-if="f.moeda === 'USD'">· US$ {{ fmt(f.preco, 2) }} × {{ fmt(f.dolar, 4) }}</span>
              </q-item-label>
            </q-item-section>
            <q-item-section side class="row no-wrap items-center">
              <MgInfoCriacao
                :usuariocriacao="f.usuariocriacao"
                :criacao="f.criacao"
                :usuarioalteracao="f.usuarioalteracao"
                :alteracao="f.alteracao"
              />
              <q-btn flat round size="sm" color="grey-7" icon="edit" @click="fixCad.editar(f)" />
              <q-btn flat round size="sm" color="grey-7" icon="delete" @click="excluirFixacao(f)" />
            </q-item-section>
          </q-item>
          <q-item v-if="!fixacoes.length">
            <q-item-section class="text-grey-6">Nenhuma fixação. Para FIXO, informe o preço no contrato.</q-item-section>
          </q-item>
        </q-list>
      </q-card>

      <!-- Pagamentos -->
      <q-card flat bordered class="q-mb-md">
        <q-item>
          <q-item-section>
            <q-item-label class="text-subtitle1">Pagamentos do comprador</q-item-label>
            <q-item-label caption>Total {{ rs(pago) }}</q-item-label>
          </q-item-section>
          <q-item-section side>
            <q-btn flat round size="sm" color="primary" icon="add" @click="novoPagamento">
              <q-tooltip>Novo pagamento</q-tooltip>
            </q-btn>
          </q-item-section>
        </q-item>
        <q-separator />
        <q-list separator>
          <q-item v-for="p in pagamentos" :key="p.codcontratopagamento">
            <q-item-section>
              <q-item-label>{{ rs(p.valor) }}</q-item-label>
              <q-item-label caption>{{ fmtData(p.data) }}<span v-if="p.observacao"> · {{ p.observacao }}</span></q-item-label>
            </q-item-section>
            <q-item-section side class="row no-wrap items-center">
              <MgInfoCriacao
                :usuariocriacao="p.usuariocriacao"
                :criacao="p.criacao"
                :usuarioalteracao="p.usuarioalteracao"
                :alteracao="p.alteracao"
              />
              <q-btn flat round size="sm" color="grey-7" icon="edit" @click="pagCad.editar(p)" />
              <q-btn flat round size="sm" color="grey-7" icon="delete" @click="excluirPagamento(p)" />
            </q-item-section>
          </q-item>
          <q-item v-if="!pagamentos.length">
            <q-item-section class="text-grey-6">Nenhum pagamento lançado.</q-item-section>
          </q-item>
        </q-list>
      </q-card>

      <!-- Embarques / NFs -->
      <q-card flat bordered>
        <q-item>
          <q-item-section>
            <q-item-label class="text-subtitle1">Embarques e notas fiscais</q-item-label>
            <q-item-label caption>{{ fmt(carregado) }} sc carregadas · {{ rs(valornf) }} em NFs</q-item-label>
          </q-item-section>
        </q-item>
        <q-separator />
        <q-list separator>
          <q-item v-for="e in embarques" :key="e.codembarquecontrato">
            <q-item-section avatar><q-avatar color="green-6" text-color="white" icon="local_shipping" /></q-item-section>
            <q-item-section>
              <q-item-label>{{ fmt(e.quantidade) }} sc</q-item-label>
              <q-item-label caption>
                {{ e.Embarque?.placa || '—' }}
                <span v-if="e.numeronf"> · NF {{ e.numeronf }}</span>
              </q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-item-label class="text-deep-orange-9">{{ e.valornf ? rs(e.valornf) : '—' }}</q-item-label>
            </q-item-section>
          </q-item>
          <q-item v-if="!embarques.length">
            <q-item-section class="text-grey-6">Nenhum embarque ainda. Carregue no pátio de expedição.</q-item-section>
          </q-item>
        </q-list>
      </q-card>

      <!-- Dialog Fixação -->
      <q-dialog v-model="fixCad.dialog">
        <q-card bordered flat style="width: 420px; max-width: 90vw">
          <q-form @submit="salvarFixacao">
            <q-card-section><div class="text-h6">{{ fixCad.isNovo ? 'Nova fixação' : 'Editar fixação' }}</div></q-card-section>
            <q-card-section class="q-gutter-md">
              <div class="row q-col-gutter-md">
                <MgInputData v-model="fixCad.form.data" label="Data" type="date" class="col-6" />
                <MgInputValor v-model="fixCad.form.quantidade" :decimals="0" suffix="sc" label="Quantidade" class="col-6" />
              </div>
              <div class="row q-col-gutter-md items-center">
                <MgInputValor v-model="fixCad.form.preco" :decimals="2" label="Preço / saca" class="col" />
                <q-btn-toggle
                  v-model="fixCad.form.moeda"
                  :options="moedas"
                  no-caps
                  unelevated
                  toggle-color="primary"
                  color="grey-3"
                  text-color="grey-9"
                  class="col-auto"
                />
              </div>
              <MgInputValor
                v-if="fixCad.form.moeda === 'USD'"
                v-model="fixCad.form.dolar"
                :decimals="4"
                prefix="R$"
                label="Dólar travado"
              />
              <q-banner v-if="previewPrecoReal" rounded class="bg-grey-2 text-grey-8">
                Preço em reais: <b>{{ rs(previewPrecoReal) }}/sc</b>
              </q-banner>
            </q-card-section>
            <q-card-actions align="right">
              <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
              <q-btn type="submit" flat label="Salvar" color="primary" :loading="fixCad.salvando" />
            </q-card-actions>
          </q-form>
        </q-card>
      </q-dialog>

      <!-- Dialog Pagamento -->
      <q-dialog v-model="pagCad.dialog">
        <q-card bordered flat style="width: 380px; max-width: 90vw">
          <q-form @submit="salvarPagamento">
            <q-card-section><div class="text-h6">{{ pagCad.isNovo ? 'Novo pagamento' : 'Editar pagamento' }}</div></q-card-section>
            <q-card-section class="q-gutter-md">
              <MgInputData v-model="pagCad.form.data" label="Data" type="date" />
              <MgInputValor v-model="pagCad.form.valor" :decimals="2" prefix="R$" label="Valor" />
              <q-input v-model="pagCad.form.observacao" label="Observação" outlined />
            </q-card-section>
            <q-card-actions align="right">
              <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
              <q-btn type="submit" flat label="Salvar" color="primary" :loading="pagCad.salvando" />
            </q-card-actions>
          </q-form>
        </q-card>
      </q-dialog>
    </div>
  </q-page>
</template>
