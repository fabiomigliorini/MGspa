<script setup>
// Wizard de cobrança operado 100% pelo teclado, o mesmo no PDV e no contas (M5/M6.1 doc-3).
// Passo 1: forma por número. Passo 2: valor deste pagamento (texto; Insert edita).
// Passo 3: perguntas da forma escolhida (componente em cobranca/Forma*.vue).
// O foco fica no card; nunca no campo de valor, para as setas não alterarem nada sem querer.
//
// Lê o que o documento informou no cobrancaStore ({ valor, total, saldo, sentido, pessoa,
// formasPermitidas, documento, contexto }) e devolve `pagamento` (meio, principal, juros,
// desconto, troco, portador, maquineta…), `parcelas` (condição, vencimento, valor) ou
// `cobranca` (integrada criada: quem abriu abre o dialog dela). Devolve para quem abriu: pelos
// tratadores do documento (aoPagamento, aoParcelas, aoCobranca) ou, sem eles, pelos eventos.
// Um wizard por app: o PDV abre o mesmo para a venda e para o Receber título.
import { ref, computed, nextTick } from 'vue'
import { Notify, useQuasar } from 'quasar'
import { cobrancaStore } from '@components/stores/cobrancaStore'
import { formataNumero } from '@components/formatters'
import MgInputValor from '@components/MgInputValor.vue'
import ListaOpcoes from './cobranca/ListaOpcoes.vue'
import FormaCartao from './cobranca/FormaCartao.vue'
import FormaPrazo from './cobranca/FormaPrazo.vue'
import FormaVale from './cobranca/FormaVale.vue'
import FormaPix from './cobranca/FormaPix.vue'
import FormaCheque from './cobranca/FormaCheque.vue'
import FormaPortador from './cobranca/FormaPortador.vue'
import FormaEstorno from './cobranca/FormaEstorno.vue'
import FormaRecebido from './cobranca/FormaRecebido.vue'
import { CONDICAO, MEIO, VISUAL } from './cobranca/pagamento.js'

const emit = defineEmits(['pagamento', 'parcelas', 'cobranca'])

const $q = useQuasar()
const sCobranca = cobrancaStore()

const cardRef = ref(null)
const listaFormasRef = ref(null)
const formaRef = ref(null)
const passo = ref(1)
const editando = ref(false)
const valorEdicao = ref(null)
// desconto por forma (só dinheiro da venda): sugerido pelo percentual da forma, editável (tecla −)
const desconto = ref(0)
const editandoDesconto = ref(false)
const descontoEdicao = ref(null)

const arredonda = (v) => Math.round((parseFloat(v) || 0) * 100) / 100

const entrada = computed(() => sCobranca.entrada)
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

// no contas o dinheiro sai de/entra num cofre, troco ou caixa escolhido; no PDV é a gaveta
const semPdv = computed(() => !sCobranca.contexto?.pdv && !sCobranca.ehNegocio)

// todas as formas; cada documento permite as suas (ordem pela frequência de uso no caixa).
// troco: só dinheiro na entrada; desconto: percentual sugerido na venda (0 = o operador dá com −)
const FORMAS = [
  { valor: 'cartao', label: 'Cartão', ...VISUAL.cartao, componente: FormaCartao },
  { valor: 'pix', label: 'PIX', ...VISUAL.pix, componente: FormaPix },
  { valor: 'dinheiro', label: 'Dinheiro', ...VISUAL.dinheiro, dinheiro: true, desconto: 0 },
  { valor: 'entrega', label: 'Pagamento na Entrega', ...VISUAL.entrega, direto: true },
  { valor: 'prazo', label: 'Prazo', ...VISUAL.prazo, componente: FormaPrazo },
  // vale pula o passo 2: o valor utilizado é decidido olhando o saldo do vale
  {
    valor: 'vale',
    label: 'Vale Compras',
    ...VISUAL.vale,
    componente: FormaVale,
    pulaValor: true,
  },
  { valor: 'cheque', label: 'Cheque', ...VISUAL.cheque, componente: FormaCheque },
  {
    valor: 'banco',
    label: 'Banco',
    caption: 'Transferência, TED, depósito, boleto',
    ...VISUAL.banco,
    componente: FormaPortador,
  },
  {
    valor: 'cartaoEmpresa',
    label: 'Cartão da Empresa',
    ...VISUAL.cartao,
    componente: FormaPortador,
  },
  {
    valor: 'estorno',
    label: 'Devolver no Cartão ou PIX',
    caption: 'Registra o cancelamento no cartão ou a devolução do PIX',
    ...VISUAL.estorno,
    componente: FormaEstorno,
  },
  // pagamento que já aconteceu e está sem amarração (só online: a lista vem do servidor)
  {
    valor: 'recebido',
    label: 'Já recebido',
    caption: 'PIX ou cartão que já entrou e está sem amarração',
    ...VISUAL.pix,
    componente: FormaRecebido,
    pulaValor: true,
    online: true,
  },
  {
    valor: 'compensacao',
    label: 'Compensação',
    caption: 'Sem dinheiro',
    ...VISUAL.compensacao,
    direto: true,
  },
]

// formas da venda, quando o documento não diz
const FORMAS_NEGOCIO = [
  'cartao',
  'pix',
  'dinheiro',
  'entrega',
  'prazo',
  'vale',
  'cheque',
  'recebido',
]

const formaAtual = computed(() => FORMAS.find((f) => f.valor === sCobranca.forma))
const comDesconto = computed(() => formaAtual.value?.desconto !== undefined && sCobranca.ehNegocio)
// o dinheiro do contas pergunta o portador no passo 3
const componenteAtual = computed(() =>
  formaAtual.value?.dinheiro && semPdv.value ? FormaPortador : formaAtual.value?.componente,
)

// prazo e cheque recebido exigem cliente identificado
const PRECISA_CLIENTE = ['prazo', 'cheque']
const opcoesFormas = computed(() =>
  FORMAS.filter((f) => (sCobranca.formasPermitidas ?? FORMAS_NEGOCIO).includes(f.valor))
    .filter((f) => !f.online || navigator.onLine)
    .map((f, i) => {
      const opcao = { ...f, tecla: i < 9 ? i + 1 : null }
      if (PRECISA_CLIENTE.includes(f.valor) && sCobranca.consumidor) {
        return { ...opcao, desabilitado: true, motivo: 'Informe o cliente (F10)' }
      }
      // PDV sem gaveta ou com o caixa fechado (M9 doc-3): o contexto diz o motivo
      if (f.dinheiro && sCobranca.contexto?.bloqueioDinheiro) {
        return { ...opcao, desabilitado: true, motivo: sCobranca.contexto.bloqueioDinheiro }
      }
      return opcao
    }),
)

const verbo = computed(() => (entrada.value ? 'Receber' : 'Pagar'))

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

// dinheiro recebido: o operador digita o que recebeu (campo focado, vazio); demais: saldo
const prepararValor = () => {
  editandoDesconto.value = false
  desconto.value = comDesconto.value
    ? arredonda((saldo.value * formaAtual.value.desconto) / 100)
    : 0
  if (formaAtual.value?.dinheiro && entrada.value) {
    valorEdicao.value = null
    editando.value = true
  } else {
    editando.value = false
  }
}

const fechar = () => {
  sCobranca.fechar()
}

const notificar = (message) => {
  Notify.create({
    type: 'negative',
    message,
    timeout: 3000, // 3 segundos
    actions: [{ icon: 'close', color: 'white' }],
  })
}

// valor travado (baixa de títulos, vale): só o dinheiro recebido se digita, para o troco
const podeEditar = computed(
  () => !sCobranca.valorFixo || (formaAtual.value?.dinheiro && entrada.value),
)

// ---- edição do valor (Insert) ----
const editar = () => {
  if (!podeEditar.value) {
    return
  }
  valorEdicao.value = valor.value
  editando.value = true
}

const aplicarEdicao = () => {
  const v = parseFloat(valorEdicao.value)
  if (!v || v <= 0) {
    notificar('Informe o valor!')
    return
  }
  if (sCobranca.valorFixo && arredonda(v) < aPagar.value) {
    notificar(
      `O valor é R$ ${formataNumero(aPagar.value)}: para pagar uma parte, ajuste os títulos.`,
    )
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
  if (!comDesconto.value) {
    return
  }
  editando.value = false
  descontoEdicao.value = desconto.value || null
  editandoDesconto.value = true
}

const aplicarDesconto = () => {
  const d = arredonda(descontoEdicao.value)
  if (d < 0 || d >= saldo.value) {
    notificar('Desconto precisa ser menor que o saldo!')
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
  if (formaAtual.value?.dinheiro && entrada.value) {
    valorEdicao.value = null
    editando.value = true
  }
  focar()
}

// ---- navegação ----
const escolherForma = (forma) => {
  sCobranca.forma = forma.valor
  irParaForma()
  // escolhida com o mouse, o item some: o foco volta para o card (Enter/Esc/Insert)
  focar()
}

// passo 2 → dinheiro do PDV e formas diretas lançam; as outras seguem para as perguntas
const continuar = async () => {
  if (!valor.value || valor.value <= 0) {
    editar()
    return
  }
  if (sCobranca.valorFixo && temFalta.value) {
    notificar(
      `O valor é R$ ${formataNumero(aPagar.value)}: para pagar uma parte, ajuste os títulos.`,
    )
    return
  }
  if (temTroco.value && !(formaAtual.value.dinheiro && entrada.value)) {
    notificar(
      entrada.value ? 'Valor maior que o saldo: só Dinheiro dá troco!' : 'Valor maior que o saldo!',
    )
    return
  }
  if (formaAtual.value.dinheiro && !semPdv.value) {
    pagamento(pagamentoDinheiro.value)
    return
  }
  if (formaAtual.value.direto) {
    direto()
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
  // uma forma só: não há lista para voltar
  if (passo.value === 1 && sCobranca.formasPermitidas?.length === 1) {
    fechar()
    return
  }
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
const pagamentoDinheiro = computed(() => {
  const totalPago = Math.min(valor.value, aPagar.value)
  return {
    meio: MEIO.DINHEIRO,
    principal: arredonda(totalPago + desconto.value),
    desconto: desconto.value,
    valortroco: temTroco.value && entrada.value ? diferenca.value : null,
  }
})

// entrega: título único que vence no dia; compensação: sem dinheiro
const direto = () => {
  if (formaAtual.value.valor === 'entrega') {
    const d = new Date()
    parcelas([
      {
        condicao: CONDICAO.ENTREGA,
        numero: 1,
        vencimento: [
          d.getFullYear(),
          String(d.getMonth() + 1).padStart(2, '0'),
          String(d.getDate()).padStart(2, '0'),
        ].join('-'),
        valor: valor.value,
        juros: 0,
      },
    ])
    return
  }
  pagamento({ meio: MEIO.COMPENSACAO, principal: valor.value })
}

// fecha sempre; quem abriu decide o resto (painel de totais pisca o "Faltando"/"Troco")
const devolver = (evento, tratador, dados) => {
  const documento = sCobranca.documento
  fechar()
  if (documento?.[tratador]) {
    documento[tratador](dados)
    return
  }
  emit(evento, dados)
}

const pagamento = (pag) => devolver('pagamento', 'aoPagamento', pag)

const parcelas = (lista) => devolver('parcelas', 'aoParcelas', lista)

// cobrança integrada criada: fecha o wizard e quem abriu abre o dialog especialista
const abrirCobranca = (cobranca) => devolver('cobranca', 'aoCobranca', cobranca)

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
        if (comDesconto.value) {
          editarDesconto()
        } else {
          tratada = false
        }
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
            <div class="text-subtitle1 text-grey-7">{{ entrada ? 'Já recebido' : 'Já pago' }}</div>
          </div>

          <div class="q-mb-md text-right">
            <template v-if="!editando">
              <div
                class="text-h2 text-weight-bold text-primary"
                :class="{ 'cursor-pointer': podeEditar }"
                @click="editar"
              >
                R$ {{ formataNumero(valor) }}
                <q-tooltip v-if="podeEditar" class="bg-accent">Alterar valor (Insert)</q-tooltip>
              </div>
              <div class="row items-center justify-end text-subtitle1 text-grey-7">
                <q-icon
                  :name="formaAtual.icone"
                  :color="formaAtual.cor"
                  size="xs"
                  class="q-mr-xs"
                />
                {{ formaAtual.dinheiro && entrada ? 'Recebido em' : verbo + ' em' }}
                {{ formaAtual.label }}
              </div>
              <div
                v-if="podeEditar"
                class="row items-center justify-end text-subtitle1 text-grey-7"
              >
                <span class="text-grey-5 q-ml-sm"> Tecla Insert altera o valor </span>
              </div>
            </template>
            <MgInputValor
              v-else
              v-model="valorEdicao"
              :label="
                (formaAtual.dinheiro && entrada ? 'Recebido em ' : verbo + ' em ') +
                formaAtual.label
              "
              prefix="R$"
              :min="0.01"
              autofocus
              hint="Enter aplica · Esc desfaz"
              class="q-field--auto-height"
              input-class="text-right text-h2 text-weight-bold text-primary"
            />
          </div>

          <!-- desconto da forma (dinheiro da venda): tecla − edita -->
          <div class="q-mb-md text-right" v-if="comDesconto">
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
            :is="componenteAtual"
            ref="formaRef"
            v-bind="formaAtual.dinheiro ? { base: pagamentoDinheiro } : {}"
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
              : (formaAtual.dinheiro && !semPdv) || formaAtual.direto
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
