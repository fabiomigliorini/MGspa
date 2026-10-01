<script setup>
// Wizard de recebimento operado 100% pelo teclado.
// Passo 1: forma por número. Passo 2: valor deste pagamento (texto; Insert edita).
// Passo 3: perguntas da forma escolhida (componente em receber/Forma*.vue).
// O foco fica no card; nunca no campo de valor, para as setas não alterarem nada sem querer.
//
// Desacoplado da venda (M5 do plano doc-3): lê o que o documento informou no store cobranca
// ({ valor, total, saldo, sentido, pessoa, formasPermitidas, documento }) e emite `pagamento`
// (formato novo: meio, principal, juros, desconto, troco…), `parcelas` (condição, vencimento,
// valor) ou `cobranca` (integrada criada: o pai abre o dialog dela).
import { ref, computed, nextTick } from 'vue'
import { Notify, useQuasar } from 'quasar'
import { cobrancaStore } from 'stores/cobranca'
import { formataNumero } from '@components/formatters'
import moment from 'moment'
import MgInputValor from '@components/MgInputValor.vue'
import ListaOpcoes from './receber/ListaOpcoes.vue'
import FormaCartao from './receber/FormaCartao.vue'
import FormaPrazo from './receber/FormaPrazo.vue'
import FormaVale from './receber/FormaVale.vue'
import FormaPix from './receber/FormaPix.vue'
import FormaCheque from './receber/FormaCheque.vue'
import { CONDICAO, MEIO, VISUAL } from '../../utils/pagamento.js'

const emit = defineEmits(['pagamento', 'parcelas', 'cobranca'])

const $q = useQuasar()
const sCobranca = cobrancaStore()

const cardRef = ref(null)
const listaFormasRef = ref(null)
const formaRef = ref(null)
const passo = ref(1)
const editando = ref(false)
const valorEdicao = ref(null)
// desconto por forma (só dinheiro): sugerido pelo percentual da forma, editável (tecla −)
const desconto = ref(0)
const editandoDesconto = ref(false)
const descontoEdicao = ref(null)

const arredonda = (v) => Math.round((parseFloat(v) || 0) * 100) / 100

const saldo = computed(() => sCobranca.saldo)
const valor = computed(() => sCobranca.valor)
const total = computed(() => sCobranca.total ?? 0)
// já lançado em outros pagamentos (pagamento dividido)
const recebido = computed(() => arredonda(total.value - saldo.value))
// o que falta pagar depois do desconto desta forma
const aPagar = computed(() => arredonda(saldo.value - desconto.value))

// valor considerado: durante a edição acompanha o que está sendo digitado
const valorAtual = computed(() =>
  editando.value ? parseFloat(valorEdicao.value) || 0 : valor.value || 0,
)

// positivo = troco, negativo = ainda falta
const diferenca = computed(() => arredonda(valorAtual.value - aPagar.value))
const temTroco = computed(() => diferenca.value > 0)
const temFalta = computed(() => diferenca.value < 0)

// ordem pela frequência de uso no caixa: cartão, PIX, dinheiro, depois o resto
// desconto: percentual sugerido pela forma (0 = sem sugestão; o operador ainda pode dar com −)
const FORMAS = [
  { tecla: 1, valor: 'cartao', label: 'Cartão', ...VISUAL.cartao, componente: FormaCartao },
  { tecla: 2, valor: 'pix', label: 'PIX', ...VISUAL.pix, componente: FormaPix },
  {
    tecla: 3,
    valor: 'dinheiro',
    label: 'Dinheiro',
    ...VISUAL.dinheiro,
    troco: true,
    desconto: 0,
  },
  { tecla: 4, valor: 'entrega', label: 'Pagamento na Entrega', ...VISUAL.entrega },
  { tecla: 5, valor: 'prazo', label: 'Prazo', ...VISUAL.prazo, componente: FormaPrazo },
  // vale pula o passo 2: o valor utilizado é decidido olhando o saldo do vale
  {
    tecla: 6,
    valor: 'vale',
    label: 'Vale Compras',
    ...VISUAL.vale,
    componente: FormaVale,
    pulaValor: true,
  },
  { tecla: 7, valor: 'cheque', label: 'Cheque', ...VISUAL.cheque, componente: FormaCheque },
]

const formaAtual = computed(() => FORMAS.find((f) => f.valor === sCobranca.forma))

// prazo e cheque exigem cliente identificado
const PRECISA_CLIENTE = ['prazo', 'cheque']
const opcoesFormas = computed(() =>
  FORMAS.filter((f) => sCobranca.permitida(f.valor)).map((f) =>
    PRECISA_CLIENTE.includes(f.valor) && sCobranca.consumidor
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

// entra no wizard com o que o documento preparou: forma preenchida = pula a escolha
const entrar = () => {
  editando.value = false
  editandoDesconto.value = false
  desconto.value = 0
  if (!formaAtual.value) {
    passo.value = 1
    return
  }
  irParaForma()
}

// forma escolhida: passo 2 (valor), ou direto às perguntas quando a forma decide o valor
const irParaForma = () => {
  if (formaAtual.value.pulaValor) {
    passo.value = 3
    return
  }
  passo.value = 2
  prepararValor()
}

// dinheiro: o operador digita o que recebeu (campo focado, vazio); demais: saldo como texto
const prepararValor = () => {
  editandoDesconto.value = false
  desconto.value = formaAtual.value?.desconto
    ? arredonda((saldo.value * formaAtual.value.desconto) / 100)
    : 0
  if (formaAtual.value?.troco) {
    valorEdicao.value = null
    editando.value = true
  } else {
    editando.value = false
  }
}

const fechar = () => {
  sCobranca.fechar()
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

const aplicarEdicao = () => {
  const v = parseFloat(valorEdicao.value)
  if (!v || v <= 0) {
    avisar('Informe o valor!')
    return
  }
  sCobranca.valor = arredonda(v)
  editando.value = false
  focar()
}

const cancelarEdicao = () => {
  editando.value = false
  focar()
}

// ---- edição do desconto (tecla −), só na forma que aceita desconto ----
const editarDesconto = () => {
  if (formaAtual.value?.desconto === undefined) {
    return
  }
  editando.value = false
  descontoEdicao.value = desconto.value || null
  editandoDesconto.value = true
}

const aplicarDesconto = () => {
  const d = arredonda(descontoEdicao.value)
  if (d < 0 || d >= saldo.value) {
    avisar('Desconto precisa ser menor que o saldo!')
    return
  }
  desconto.value = d
  editandoDesconto.value = false
  // dinheiro volta a pedir o recebido, agora com o desconto
  prepararValorDinheiro()
}

const cancelarDesconto = () => {
  editandoDesconto.value = false
  prepararValorDinheiro()
}

const prepararValorDinheiro = () => {
  if (formaAtual.value?.troco) {
    valorEdicao.value = null
    editando.value = true
  }
  focar()
}

// ---- navegação ----
const escolherForma = (forma) => {
  sCobranca.forma = forma.valor
  irParaForma()
}

// passo 2 → Dinheiro lança (após conferir recebido/troco); as outras seguem para as perguntas da forma
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
  passo.value = passo.value === 3 && formaAtual.value?.pulaValor ? 1 : passo.value - 1
  if (passo.value === 2) {
    prepararValor()
  }
  if (passo.value === 1) {
    sCobranca.forma = null
  }
  focar()
}

// dinheiro: o que ficou (total) é o recebido até o que falta; o resto é troco.
// principal = total + desconto (o desconto quita a parte dele do saldo)
const dinheiro = () => {
  const totalPago = Math.min(valor.value, aPagar.value)
  pagamento({
    meio: MEIO.DINHEIRO,
    principal: arredonda(totalPago + desconto.value),
    desconto: desconto.value,
    valortroco: temTroco.value ? diferenca.value : null,
  })
}

// cliente paga ao receber ou retirar o produto: título único, vence no dia
const entrega = () => {
  parcelas([
    {
      condicao: CONDICAO.ENTREGA,
      numero: 1,
      vencimento: moment().format('YYYY-MM-DD'),
      valor: valor.value,
      juros: 0,
    },
  ])
}

// fecha sempre; quem abriu decide o resto (painel de totais pisca o "Faltando"/"Troco")
const pagamento = (pag) => {
  fechar()
  emit('pagamento', pag)
}

const parcelas = (lista) => {
  fechar()
  emit('parcelas', lista)
}

// cobrança integrada criada: fecha o wizard e o pai abre o dialog especialista
const abrirCobranca = (cobranca) => {
  fechar()
  emit('cobranca', cobranca)
}

// o documento abriu de novo com a forma escolhida (bipagem VAL… com o dialog aberto)
defineExpose({ entrar })

// ---- teclado ----
const tecla = (e) => {
  // atalhos da venda que continuam valendo com o dialog aberto
  if (e.key === 'F3' || e.key === 'F4') {
    return
  }

  let tratada = true

  if (editandoDesconto.value) {
    switch (e.key) {
      case 'Enter':
        aplicarDesconto()
        break
      case 'Escape':
        cancelarDesconto()
        break
      default:
        tratada = false
    }
  } else if (editando.value) {
    // no modo edição as demais teclas são do MgInputValor (inclusive as setas)
    switch (e.key) {
      case 'Enter':
        aplicarEdicao()
        break
      case 'Escape':
        cancelarEdicao()
        break
      case '-':
      case 'Subtract':
        editarDesconto()
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
      case '-':
      case 'Subtract':
        if (passo.value === 2) {
          editarDesconto()
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
    v-model="sCobranca.dialog"
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
                <q-icon
                  :name="formaAtual.icone"
                  :color="formaAtual.cor"
                  size="xs"
                  class="q-mr-xs"
                />
                {{ formaAtual.troco ? 'Recebido em' : 'Receber em' }} {{ formaAtual.label }}
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
              hint="Enter aplica · Esc desfaz"
              class="q-field--auto-height"
              input-class="text-right text-h2 text-weight-bold text-primary"
            />
          </div>

          <!-- desconto da forma (dinheiro): tecla − edita -->
          <div class="q-mb-md text-right" v-if="formaAtual.desconto !== undefined">
            <MgInputValor
              v-if="editandoDesconto"
              v-model="descontoEdicao"
              label="Desconto"
              prefix="R$"
              :min="0"
              autofocus
              hint="Enter aplica · Esc desfaz"
              class="q-field--auto-height"
              input-class="text-right text-h4 text-weight-bold text-green-8"
            />
            <template v-else-if="desconto > 0">
              <div class="text-h4 text-weight-bold text-green-8">
                − R$ {{ formataNumero(desconto) }}
              </div>
              <div class="text-subtitle1 text-grey-7">
                Desconto · a pagar R$ {{ formataNumero(aPagar) }}
              </div>
            </template>
            <div v-else class="text-subtitle1 text-grey-5">Tecla − dá desconto</div>
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
            @pagamento="pagamento"
            @parcelas="parcelas"
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
            editando || editandoDesconto
              ? 'Aplicar (Enter)'
              : formaAtual.troco || formaAtual.valor === 'entrega'
                ? 'Lançar (Enter)'
                : 'Continuar (Enter)'
          "
          tabindex="-1"
          @click="editandoDesconto ? aplicarDesconto() : editando ? aplicarEdicao() : continuar()"
        />
        <q-btn
          v-if="passo === 3 && formaRef?.acao"
          flat
          color="primary"
          :label="formaRef.acao.label"
          tabindex="-1"
          @click="formaRef.acao.executar()"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>
