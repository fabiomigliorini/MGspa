<script setup>
// Wizard de recebimento operado 100% pelo teclado.
// Passo 1: forma por número. Passo 2: valor deste pagamento (texto; Insert edita).
// Passo 3: perguntas da forma escolhida (componente em receber/Forma*.vue).
// O foco fica no card; nunca no campo de valor, para as setas não alterarem nada sem querer.
import { ref, computed, nextTick, onMounted, onUnmounted } from 'vue'
import { Notify, useQuasar } from 'quasar'
import { negocioStore } from 'stores/negocio'
import { pixStore } from 'stores/pix'
import { pagarMeStore } from 'stores/pagar-me'
import { saurusStore } from 'stores/saurus'
import { formataNumero } from '@components/formatters'
import MgInputValor from '@components/MgInputValor.vue'
import ListaOpcoes from './receber/ListaOpcoes.vue'
import FormaCartao from './receber/FormaCartao.vue'
import FormaPrazo from './receber/FormaPrazo.vue'
import FormaVale from './receber/FormaVale.vue'
import FormaPix from './receber/FormaPix.vue'
import FormaCheque from './receber/FormaCheque.vue'
import emitter from '../../utils/emitter.js'

const $q = useQuasar()
const sNegocio = negocioStore()
const sPix = pixStore()
const sPagarMe = pagarMeStore()
const sSaurus = saurusStore()

const cardRef = ref(null)
const listaFormasRef = ref(null)
const formaRef = ref(null)
const passo = ref(1)
const editando = ref(false)
const valorEdicao = ref(null)

const saldo = computed(() => sNegocio.valorapagar)
const valor = computed(() => sNegocio.receber.valor)
const total = computed(() => sNegocio.negocio?.valortotal ?? 0)
// já lançado em outros pagamentos (pagamento dividido)
const recebido = computed(() => Math.round((total.value - saldo.value) * 100) / 100)

// valor considerado: durante a edição acompanha o que está sendo digitado
const valorAtual = computed(() =>
  editando.value ? parseFloat(valorEdicao.value) || 0 : valor.value || 0,
)

// positivo = troco, negativo = ainda falta
const diferenca = computed(() => Math.round((valorAtual.value - saldo.value) * 100) / 100)
const temTroco = computed(() => diferenca.value > 0)
const temFalta = computed(() => diferenca.value < 0)

// ordem pela frequência de uso no caixa: cartão, PIX, dinheiro, depois o resto
const FORMAS = [
  { tecla: 1, valor: 'cartao', label: 'Cartão', icone: 'credit_card', componente: FormaCartao },
  { tecla: 2, valor: 'pix', label: 'PIX', icone: 'pix', componente: FormaPix },
  { tecla: 3, valor: 'dinheiro', label: 'Dinheiro', icone: 'local_atm', troco: true },
  { tecla: 4, valor: 'entrega', label: 'Pagamento na Entrega', icone: 'delivery_dining' },
  { tecla: 5, valor: 'prazo', label: 'Prazo', icone: 'receipt', componente: FormaPrazo },
  { tecla: 6, valor: 'vale', label: 'Vale Compras', icone: 'mdi-ticket', componente: FormaVale },
  { tecla: 7, valor: 'cheque', label: 'Cheque', icone: 'mdi-checkbook', componente: FormaCheque },
]

const formaAtual = computed(() => FORMAS.find((f) => f.valor === sNegocio.receber.forma))

// prazo e cheque exigem cliente identificado
const PRECISA_CLIENTE = ['prazo', 'cheque']
const opcoesFormas = computed(() =>
  FORMAS.map((f) =>
    PRECISA_CLIENTE.includes(f.valor) && sNegocio.negocio?.codpessoa == 1
      ? { ...f, desabilitado: true, motivo: 'Informe o cliente (F10)' }
      : f,
  ),
)

// eslint-disable-next-line no-unused-vars -- cabeçalho comentado em teste
const tituloPasso = computed(() => {
  if (passo.value === 1) return '1 · Forma'
  if (passo.value === 2) return '2 · Valor'
  return '3 · ' + (formaAtual.value?.label ?? '')
})

// desktop: janela com altura fixa; mobile: tela cheia
const mobile = computed(() => $q.screen.lt.sm)
const estiloCard = computed(() =>
  mobile.value ? '' : 'width: 560px; max-width: 95vw; height: 70vh',
)

const focar = () => {
  nextTick(() => cardRef.value?.$el?.focus())
}

// entra no wizard com o que o store preparou (abrirReceber): forma preenchida = pula a escolha
const entrar = () => {
  editando.value = false
  passo.value = formaAtual.value ? 2 : 1
  if (passo.value === 2) {
    prepararValor()
  }
}

// dinheiro: o operador digita o que recebeu (campo focado, vazio); demais: saldo como texto
const prepararValor = () => {
  if (formaAtual.value?.troco) {
    valorEdicao.value = null
    editando.value = true
  } else {
    editando.value = false
  }
}

const fechar = () => {
  sNegocio.dialog.receber = false
}

const avisar = (message) => {
  Notify.create({
    type: 'negative',
    message,
    timeout: 3000, // 3 segundos
    actions: [{ icon: 'close', color: 'white' }],
  })
}

// ---- edição do valor (Insert) ----
const editar = () => {
  valorEdicao.value = valor.value
  editando.value = true
}

const aplicarEdicao = async () => {
  const v = parseFloat(valorEdicao.value)
  if (!v || v <= 0) {
    avisar('Informe o valor!')
    return
  }
  sNegocio.receber.valor = Math.round(v * 100) / 100
  // dinheiro: Enter no valor já lança
  if (formaAtual.value.troco) {
    await dinheiro()
    return
  }
  editando.value = false
  focar()
}

const cancelarEdicao = () => {
  // dinheiro não tem modo texto: Esc volta para a escolha da forma
  if (formaAtual.value.troco) {
    voltar()
    return
  }
  editando.value = false
  focar()
}

// ---- navegação ----
const escolherForma = (forma) => {
  sNegocio.receber.forma = forma.valor
  passo.value = 2
  prepararValor()
}

// passo 2 → Dinheiro lança direto; as outras seguem para as perguntas da forma
const continuar = async () => {
  if (!valor.value || valor.value <= 0) {
    editar()
    return
  }
  if (formaAtual.value.troco) {
    await dinheiro()
    return
  }
  if (temTroco.value) {
    avisar('Valor maior que o saldo: só Dinheiro dá troco!')
    return
  }
  // entrega não tem perguntas: lança direto
  if (formaAtual.value.valor === 'entrega') {
    await entrega()
    return
  }
  passo.value = 3
}

const voltar = () => {
  if (passo.value === 1) {
    fechar()
    return
  }
  editando.value = false
  passo.value -= 1
  if (passo.value === 2) {
    prepararValor()
  }
  if (passo.value === 1) {
    sNegocio.receber.forma = null
  }
  focar()
}

const dinheiro = async () => {
  await sNegocio.adicionarPagamento({
    codformapagamento: parseInt(process.env.CODFORMAPAGAMENTO_DINHEIRO),
    tipo: 1, // Dinheiro
    valorpagamento: valor.value,
    valortroco: temTroco.value ? diferenca.value : null,
  })
  depoisDeAdicionar()
}

// cliente paga ao receber ou retirar o produto: título único, vence no dia
const entrega = async () => {
  await sNegocio.adicionarPagamento({
    codformapagamento: parseInt(process.env.CODFORMAPAGAMENTO_ENTREGA),
    tipo: 5, // Crédito Loja
    valorpagamento: valor.value,
    parcelas: 1,
    valorparcela: valor.value,
    dias: 0,
  })
  depoisDeAdicionar()
}

// fecha sempre; se ainda falta, o painel de totais pisca o "Faltando"/"Troco"
const depoisDeAdicionar = () => {
  fechar()
  emitter.emit('pagamentoAdicionado')
}

// cobrança integrada criada: fecha o wizard e abre o dialog especialista
const abrirCobranca = ({ tipo, dados }) => {
  fechar()
  switch (tipo) {
    case 'pix':
      sPix.pixCob = dados
      sPix.dialog.detalhesPixCob = true
      break
    case 'pagarme':
      sPagarMe.pedido = dados
      sPagarMe.dialog.detalhesPedido = true
      break
    case 'saurus':
      sSaurus.pedido = dados
      sSaurus.dialog.detalhesPedido = true
      break
  }
}

// bipagem VAL… abre direto na forma vale
const valeLido = (codigo) => {
  if (!sNegocio.podeEditar || !sNegocio.negocio?.financeiro) {
    return
  }
  sNegocio.abrirReceber({ forma: 'vale', codtituloVale: codigo })
  entrar()
}

onMounted(() => {
  emitter.on('valeComprasLido', valeLido)
})

onUnmounted(() => {
  emitter.off('valeComprasLido', valeLido)
})

// ---- teclado ----
const tecla = (e) => {
  // atalhos da venda que continuam valendo com o dialog aberto
  if (e.key === 'F3' || e.key === 'F4') {
    return
  }

  let tratada = true

  if (editando.value) {
    // no modo edição as demais teclas são do MgInputValor (inclusive as setas)
    switch (e.key) {
      case 'Enter':
        aplicarEdicao()
        break
      case 'Escape':
        cancelarEdicao()
        break
      case 'F6':
      case 'F7':
      case 'F8':
      case 'F9':
        break
      default:
        tratada = false
    }
  } else if (passo.value === 3) {
    // a forma trata primeiro; Esc não consumido volta ao passo 2
    tratada = !!formaRef.value?.tecla(e)
    if (!tratada && e.key === 'Escape') {
      voltar()
      tratada = true
    }
    if (!tratada && ['F6', 'F7', 'F8', 'F9'].includes(e.key)) {
      tratada = true
    }
  } else {
    switch (e.key) {
      case 'Escape':
        voltar()
        break
      case 'Insert':
        if (passo.value === 2) {
          editar()
        }
        break
      case 'Enter':
        if (passo.value === 2) {
          continuar()
        } else {
          tratada = !!listaFormasRef.value?.tecla(e)
        }
        break
      case 'F6':
      case 'F7':
      case 'F8':
      case 'F9':
        break
      default:
        tratada = passo.value === 1 ? !!listaFormasRef.value?.tecla(e) : false
    }
  }

  if (tratada) {
    e.preventDefault()
    e.stopPropagation()
  }
}
</script>
<template>
  <q-dialog
    v-model="sNegocio.dialog.receber"
    no-esc-dismiss
    :maximized="mobile"
    @before-show="entrar"
    @show="focar"
  >
    <q-card
      flat
      :style="estiloCard"
      tabindex="0"
      class="no-outline column no-wrap q-pa-md"
      ref="cardRef"
      @keydown="tecla"
    >
      <!-- CABEÇALHO: comentado para testar sem ele
      <q-card-section class="col-auto row items-center q-pb-none">
        <div class="text-h5">Receber</div>
        <q-space />
        <div class="text-subtitle1 text-grey-7">{{ tituloPasso }}</div>
      </q-card-section>
      -->

      <!-- PASSO 1: FORMA -->
      <q-card-section v-if="passo === 1" class="col scroll column no-wrap">
        <div class="q-my-auto">
          <lista-opcoes ref="listaFormasRef" :opcoes="opcoesFormas" @escolher="escolherForma" />
        </div>
      </q-card-section>

      <!-- PASSO 2: VALOR -->
      <!-- valores empilhados: valor grande, título embaixo; todos no mesmo tamanho -->
      <!-- column + q-my-auto: centraliza na vertical sem cortar o topo se passar da altura -->
      <q-card-section v-else-if="passo === 2" class="col scroll column no-wrap">
        <div class="q-my-auto">
          <div class="q-mb-md text-right">
            <div class="text-h2 text-weight-bold text-grey-8">R$ {{ formataNumero(total) }}</div>
            <div class="text-subtitle1 text-grey-7">Total</div>
          </div>

          <div class="q-mb-md text-right" v-if="recebido > 0">
            <div class="text-h2 text-weight-bold text-grey-8">R$ {{ formataNumero(recebido) }}</div>
            <div class="text-subtitle1 text-grey-7">Já recebido</div>
          </div>

          <div class="q-mb-md text-right">
            <template v-if="!editando">
              <div class="text-h2 text-weight-bold text-primary cursor-pointer" @click="editar">
                R$ {{ formataNumero(valor) }}
                <q-tooltip class="bg-accent">Alterar valor (Insert)</q-tooltip>
              </div>
              <div class="row items-center justify-end text-subtitle1 text-grey-7">
                <q-icon :name="formaAtual.icone" size="xs" class="q-mr-xs" />
                Receber em {{ formaAtual.label }}
              </div>
              <div class="row items-center justify-end text-subtitle1 text-grey-7">
                <span class="text-grey-5 q-ml-sm"> Tecla Insert altera o valor à Receber </span>
              </div>
            </template>
            <MgInputValor
              v-else
              v-model="valorEdicao"
              :label="(formaAtual.troco ? 'Recebido em ' : 'Receber em ') + formaAtual.label"
              prefix="R$"
              :min="0.01"
              autofocus
              :hint="formaAtual.troco ? 'Enter lança · Esc volta' : 'Enter aplica · Esc desfaz'"
              class="q-field--auto-height"
              input-class="text-right text-h2 text-weight-bold text-primary"
            />
          </div>

          <div class="text-right" v-if="temFalta">
            <div class="text-h2 text-weight-bold text-orange-10">
              R$ {{ formataNumero(Math.abs(diferenca)) }}
            </div>
            <div class="text-subtitle1 text-orange-10">Falta</div>
          </div>
          <div class="text-right" v-else>
            <div class="text-h2 text-weight-bold text-green-9">
              R$ {{ formataNumero(diferenca) }}
            </div>
            <div class="text-subtitle1 text-green-9">Troco</div>
          </div>
        </div>
      </q-card-section>

      <!-- PASSO 3: PERGUNTAS DA FORMA -->
      <q-card-section v-else class="col scroll column no-wrap">
        <div class="q-my-auto">
          <component
            :is="formaAtual.componente"
            ref="formaRef"
            @concluido="depoisDeAdicionar"
            @cobranca="abrirCobranca"
          />
        </div>
      </q-card-section>

      <!-- RODAPÉ -->
      <q-card-actions align="right" class="col-auto">
        <q-btn
          flat
          color="grey-8"
          :label="passo === 1 ? 'Cancelar (Esc)' : 'Voltar (Esc)'"
          tabindex="-1"
          @click="voltar"
        />
        <q-btn
          v-if="passo === 2"
          flat
          color="primary"
          :label="
            formaAtual.troco || formaAtual.valor === 'entrega'
              ? 'Lançar (Enter)'
              : 'Continuar (Enter)'
          "
          tabindex="-1"
          @click="editando ? aplicarEdicao() : continuar()"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>
