<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Dialog } from 'quasar'
import { negocioStore } from 'stores/negocio'
import { cobrancaStore } from '@components/stores/cobrancaStore'
import { pixStore } from '@components/stores/pixStore'
import { pagarMeStore } from '@components/stores/pagarMeStore'
import { saurusStore } from '@components/stores/saurusStore'
import MgCobrancaDialog from '@components/MgCobrancaDialog.vue'
import PixCobDialog from '@components/cobranca/PixCobDialog.vue'
import PagarMePedidoDialog from '@components/cobranca/PagarMePedidoDialog.vue'
import SaurusPedidoDialog from '@components/cobranca/SaurusPedidoDialog.vue'
import LogoPagamento from '@components/cobranca/LogoPagamento.vue'
import { ouvir } from '@components/cobranca/eventos.js'
import { abrirCobrancaIntegrada } from '@components/cobranca/integrada.js'
import PagamentoDialog from 'components/offline/PagamentoDialog.vue'
import MgInputValor from '@components/MgInputValor.vue'
import { formataFromNow, formataNumero } from '@components/formatters'
import emitter from '../../utils/emitter.js'
import {
  CONDICOES,
  MEIO,
  gruposParcelas,
  resumoPagamento,
  resumoParcelas,
  tituloPagamento,
  valorExibido,
  visualCondicao,
  visualPagamento,
} from '@components/cobranca/pagamento.js'

const sNegocio = negocioStore()
const sCobranca = cobrancaStore()
const sPix = pixStore()
const sPagarMe = pagarMeStore()
const sSaurus = saurusStore()

const edicao = ref({
  valorprodutos: null,
  valorvales: null,
  percentualdesconto: null,
  valordesconto: null,
  valorfrete: null,
  valorseguro: null,
  valoroutras: null,
  valortotal: null,
})

// O desconto de cabecalho vale para o negocio inteiro -- mercadoria E vales
// (decisao 20). A base do % e do teto e' a soma dos dois brutos; sem vale,
// valorvales e' zero e a conta fica identica a de sempre.
const baseRateio = computed(
  () =>
    Math.round(
      (parseFloat(edicao.value.valorprodutos || 0) + parseFloat(edicao.value.valorvales || 0)) *
        100,
    ) / 100,
)

const editarValores = () => {
  edicao.value.valorprodutos = sNegocio.negocio.valorprodutos
  edicao.value.valorvales = sNegocio.negocio.valorvales
  // só o desconto digitado: o do pagamento é rateado sozinho
  const descontoDigitado = descontoItens.value
  if (descontoDigitado > 0 && baseRateio.value) {
    edicao.value.percentualdesconto = Math.round((descontoDigitado / baseRateio.value) * 1000) / 10
  } else {
    edicao.value.percentualdesconto = null
  }
  edicao.value.valordesconto = descontoDigitado || null
  edicao.value.valorfrete = sNegocio.negocio.valorfrete
  edicao.value.valorseguro = sNegocio.negocio.valorseguro
  edicao.value.valoroutras = sNegocio.negocio.valoroutras
  edicao.value.valortotal = sNegocio.negocio.valortotal
  sNegocio.dialog.valores = true
}

const maiorQueZeroRule = [
  (value) => {
    if (!value || parseFloat(value) >= 0) {
      return true
    }
    return 'Negativo!'
  },
]

const preenchimentoObrigatorioRule = [
  (val) => (val && parseFloat(val) >= 0.001) || '* Obrigatório!',
]

const salvar = async () => {
  Dialog.create({
    title: 'Salvar',
    message: 'Tem certeza que você deseja salvar?',
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'OK', color: 'primary', flat: true },
  }).onOk(() => {
    sNegocio.aplicarValoresCabecalho(
      parseFloat(edicao.value.valordesconto),
      parseFloat(edicao.value.valorfrete),
      parseFloat(edicao.value.valorseguro),
      parseFloat(edicao.value.valoroutras),
    )
    sNegocio.dialog.valores = false
  })
}

const recalcularValorDesconto = () => {
  if (edicao.value.percentualdesconto <= 0) {
    edicao.value.valordesconto = null
  } else {
    edicao.value.valordesconto =
      Math.round(baseRateio.value * edicao.value.percentualdesconto) / 100
  }
  recalcularValorTotal()
}

const recalcularPercentualDesconto = () => {
  if (edicao.value.valordesconto <= 0) {
    edicao.value.percentualdesconto = null
  } else {
    edicao.value.percentualdesconto =
      Math.round((edicao.value.valordesconto * 1000) / baseRateio.value) / 10
  }
  recalcularValorTotal()
}

const recalcularValorTotal = () => {
  // a face dos vales entra no total como o valorprodutos da mercadoria
  let total = baseRateio.value
  if (edicao.value.valordesconto) {
    total -= parseFloat(edicao.value.valordesconto)
  }
  if (edicao.value.valorfrete) {
    total += parseFloat(edicao.value.valorfrete)
  }
  if (edicao.value.valorseguro) {
    total += parseFloat(edicao.value.valorseguro)
  }
  if (edicao.value.valoroutras) {
    total += parseFloat(edicao.value.valoroutras)
  }
  edicao.value.valortotal = Math.round(total * 100) / 100
}

const receber = () => {
  sNegocio.abrirReceber()
}

const dialogDetalhesPixCob = (pixCob) => {
  sPix.pixCob = pixCob
  sPix.dialog.detalhesPixCob = true
}

const dialogDetalhesPagarMePedido = (ped) => {
  sPagarMe.pedido = ped
  sPagarMe.dialog.detalhesPedido = true
}

const dialogDetalhesSaurusPedido = (ped) => {
  sSaurus.pedido = ped
  sSaurus.dialog.detalhesPedido = true
}

// ---- wizard de cobrança: o negócio consome o que ele emite ----
const receberDialogRef = ref(null)

const pagamentoRecebido = async (pag) => {
  await sNegocio.adicionarPagamento(pag)
  emitter.emit('pagamentoAdicionado')
}

const parcelasRecebidas = async (parcelas) => {
  await sNegocio.adicionarParcelas(parcelas)
  emitter.emit('pagamentoAdicionado')
}

// cobrança integrada criada: abre o dialog especialista dela
const cobrancaCriada = (cobranca) => abrirCobrancaIntegrada(cobranca)

// bipagem VAL… abre direto na forma vale
const valeLido = async (codigo) => {
  if (!sNegocio.podeEditar || !sNegocio.negocio?.financeiro) {
    return
  }
  const aberto = sCobranca.dialog
  await sNegocio.abrirReceber({ forma: 'vale', codtituloVale: codigo })
  if (aberto) {
    receberDialogRef.value?.entrar()
  }
}

const cobrancaAtualizada = (dados) => sNegocio.cobrancaAtualizada(dados)
// banco/maquineta confirmou a cobrança deste negócio: o painel pisca o que falta (ou o troco);
// a do Receber título não é da venda
const cobrancaConcluida = ({ dados }) => {
  if (dados?.codnegocio && dados.codnegocio == sNegocio.negocio?.codnegocio) {
    emitter.emit('pagamentoAdicionado')
  }
}
let deixarDeOuvir = []

// grupo de parcelas abre o detalhe com as datas (e Excluir)
const abrirParcelas = (grupo) => {
  sNegocio.pagamentoDetalhe = grupo
  sNegocio.dialog.pagamento = true
}

// pagamento de integração abre o dialog da cobrança (tem o pagador, NSU etc); o resto, o detalhe
const abrirPagamento = (pag) => {
  const n = sNegocio.negocio
  const cob = pag.codpixcob && n.pixCob?.find((c) => c.codpixcob == pag.codpixcob)
  if (cob) {
    return dialogDetalhesPixCob(cob)
  }
  const pagarMe =
    pag.codpagarmepedido &&
    n.PagarMePedidoS?.find((p) => p.codpagarmepedido == pag.codpagarmepedido)
  if (pagarMe) {
    return dialogDetalhesPagarMePedido(pagarMe)
  }
  const saurus =
    pag.codsauruspedido && n.SaurusPedidoS?.find((p) => p.codsauruspedido == pag.codsauruspedido)
  if (saurus) {
    return dialogDetalhesSaurusPedido(saurus)
  }
  sNegocio.pagamentoDetalhe = pag
  sNegocio.dialog.pagamento = true
}

// pagamento parcial lançado: chama atenção para o que ainda falta (ou o troco)
const piscandoSaldo = ref(false)
let timerPiscar = null
const piscarSaldo = () => {
  if (sNegocio.valorapagar == 0) {
    return
  }
  piscandoSaldo.value = false
  clearTimeout(timerPiscar)
  setTimeout(() => {
    piscandoSaldo.value = true
    timerPiscar = setTimeout(() => (piscandoSaldo.value = false), 2000)
  }, 50)
}

onMounted(() => {
  emitter.on('pagamentoAdicionado', piscarSaldo)
  emitter.on('valeComprasLido', valeLido)
  deixarDeOuvir = [
    ouvir('cobrancaAtualizada', cobrancaAtualizada),
    ouvir('cobrancaConcluida', cobrancaConcluida),
  ]
})

onUnmounted(() => {
  emitter.off('pagamentoAdicionado', piscarSaldo)
  emitter.off('valeComprasLido', valeLido)
  deixarDeOuvir.forEach((parar) => parar())
  clearTimeout(timerPiscar)
})

// cobranças ainda não pagas (a paga já virou linha em negocio.pagamentos, com integracao)
const corCobranca = (cancelada) => (cancelada ? 'grey' : 'warning')

const CANCELADAS_PIX = ['EXPIRADO', 'REMOVIDA_PELO_USUARIO_RECEBEDOR']

const resumoPedido = (ped) =>
  [
    ped.parcelas > 1 ? `${ped.parcelas}x ${formataNumero(ped.valorparcela)}` : null,
    `POS ${ped.apelido}`,
    formataFromNow(ped.criacao),
  ]
    .filter(Boolean)
    .join(' · ')

const cobrancas = computed(() => {
  const n = sNegocio.negocio
  if (!n) {
    return []
  }
  const pix = (n.pixCob ?? [])
    .filter((c) => c.status != 'CONCLUIDA')
    .map((c) => ({
      chave: 'pix' + c.codpixcob,
      icone: 'pix',
      cancelada: CANCELADAS_PIX.includes(c.status),
      cor: corCobranca(CANCELADAS_PIX.includes(c.status)),
      titulo: 'PIX',
      status: c.status,
      valor: c.valororiginal,
      resumo: formataFromNow(c.criacao),
      abrir: () => dialogDetalhesPixCob(c),
    }))
  const pedido = (ped, chave, abrir) => ({
    chave,
    cancelada: ped.status == 3,
    icone: 'credit_card',
    cor: corCobranca(ped.status == 3),
    titulo: 'Cartão ' + (ped.tipodescricao ?? '').toLowerCase(),
    status: ped.statusdescricao,
    valor: ped.valortotal,
    resumo: resumoPedido(ped),
    abrir: () => abrir(ped),
  })
  const pagarMe = (n.PagarMePedidoS ?? [])
    .filter((p) => p.status != 2)
    .map((p) => pedido(p, 'pagarme' + p.codpagarmepedido, dialogDetalhesPagarMePedido))
  const saurus = (n.SaurusPedidoS ?? [])
    .filter((p) => p.status != 2)
    .map((p) => pedido(p, 'saurus' + p.codsauruspedido, dialogDetalhesSaurusPedido))
  return [...pix, ...pagarMe, ...saurus]
})

// na tela, cobrança não paga fica em bloco próprio, abaixo do saldo, para não
// ser lida como pagamento já confirmado
const gruposCobranca = computed(() =>
  [
    { titulo: 'Aguardando Pagamento', itens: cobrancas.value.filter((c) => !c.cancelada) },
    { titulo: 'Canceladas', itens: cobrancas.value.filter((c) => c.cancelada) },
  ].filter((g) => g.itens.length),
)

// Consumo de vários vales vira UMA linha na tela (decisão 6 do plano): no
// FIFO por escola um pagamento de R$ 300 pode virar 5 vales, e 5 linhas
// iguais escondem o resto do pagamento. No banco continuam N, e o grupo
// abre em um toque para o operador poder tirar um do lote.
const ehValeUsado = (p) => p.meio == MEIO.VALE && p.codtitulo

// o vale cancelado na venda reaberta fica fora do grupo, na própria linha (para reativar)
const valesLancados = computed(() => sNegocio.pagamentosAtivos.filter(ehValeUsado))

const agruparVales = computed(() => valesLancados.value.length > 1)

const valesExpandidos = ref(false)

const valesTotal = computed(() =>
  valesLancados.value.reduce((soma, p) => soma + parseFloat(p.total), 0),
)

// a lista da tela: sem agrupamento é a de sempre, com agrupamento os vales
// saem daqui e viram a linha única
const pagamentosVisiveis = computed(() => {
  const pagamentos = sNegocio.negocio?.pagamentos ?? []
  if (!agruparVales.value) {
    return pagamentos
  }
  return pagamentos.filter((p) => !ehValeUsado(p) || p.estado == 'C')
})

// a inativa (venda reaberta) vira grupo à parte, para reativar
const grupos = computed(() => [
  ...gruposParcelas(sNegocio.parcelasAtivas),
  ...gruposParcelas((sNegocio.negocio?.parcelas ?? []).filter((np) => np.inativo)).map((g) => ({
    ...g,
    inativa: true,
  })),
])

// desconto dado na forma de pagamento (dinheiro): está rateado no desconto dos itens, mas
// aparece à parte do desconto digitado
const descontoPagamentos = computed(
  () =>
    Math.round(sNegocio.pagamentosAtivos.reduce((soma, p) => soma + (p.desconto || 0), 0) * 100) /
    100,
)

const descontoItens = computed(
  () => Math.round(((sNegocio.negocio?.valordesconto || 0) - descontoPagamentos.value) * 100) / 100,
)

const temLancamento = computed(
  () =>
    sNegocio.negocio.pagamentos.length > 0 ||
    (sNegocio.negocio.parcelas ?? []).length > 0 ||
    cobrancas.value.length > 0,
)

// só aparece quando há o que receber (venda vazia não mostra o botão)
const mostrarReceber = computed(
  () =>
    sNegocio.negocio.financeiro && sNegocio.podeEditar && !temLancamento.value && faltando.value,
)

const mostrarSaldo = computed(
  () => sNegocio.negocio.financeiro && temLancamento.value && sNegocio.valorapagar != 0,
)

const faltando = computed(() => sNegocio.valorapagar > 0)

const podeReceber = computed(() => faltando.value && sNegocio.podeEditar)
</script>
<template>
  <!-- Editar Valores Desconto / Frete / etc -->
  <q-dialog v-model="sNegocio.dialog.valores">
    <q-card style="width: 350px; max-width: 80vw">
      <q-form ref="formItem" @submit="salvar()">
        <q-card-section>
          <!-- sem :rules porque venda so' de vale nao tem mercadoria: cobrar
               obrigatorio aqui travava o dialog num campo readonly que
               ninguem consegue preencher. Quem garante que o negocio nao e'
               zerado e' o Total, la' embaixo -->
          <div class="row justify-end q-col-gutter-md">
            <div class="col-6"></div>
            <div class="col-6">
              <MgInputValor
                readonly
                bottom-slots
                v-model="edicao.valorprodutos"
                prefix="R$"
                label="Total Produtos"
              />
            </div>
          </div>
          <!-- a base do % e do desconto e' produtos + vales: sem mostrar os
               vales aqui, o desconto calculado parece nao bater com nada.
               bottom-slots: campo sem :rules nao reserva a linha da mensagem
               embaixo -- era o degrau no espacamento entre "Total Vales" e
               "Desconto" -->
          <div class="row justify-end q-col-gutter-md" v-if="edicao.valorvales > 0">
            <div class="col-6"></div>
            <div class="col-6">
              <MgInputValor
                readonly
                bottom-slots
                v-model="edicao.valorvales"
                prefix="R$"
                label="Total Vales"
              />
            </div>
          </div>
          <div class="row justify-end q-col-gutter-md">
            <div class="col-6">
              <MgInputValor
                :decimals="1"
                :min="0"
                :max="99.9"
                v-model="edicao.percentualdesconto"
                label="% Desc"
                suffix="%"
                :rules="maiorQueZeroRule"
                @change="recalcularValorDesconto()"
                autofocus
              />
            </div>
            <div class="col-6">
              <MgInputValor
                :max="baseRateio - 0.01"
                v-model="edicao.valordesconto"
                prefix="R$"
                label="Desconto"
                :rules="maiorQueZeroRule"
                @change="recalcularPercentualDesconto()"
              />
            </div>
          </div>
          <div class="row justify-end q-col-gutter-md">
            <div class="col-6">
              <MgInputValor
                v-model="edicao.valorfrete"
                prefix="R$"
                label="Frete"
                :rules="maiorQueZeroRule"
                @change="recalcularValorTotal()"
              />
            </div>
          </div>
          <div class="row justify-end q-col-gutter-md">
            <div class="col-6">
              <MgInputValor
                v-model="edicao.valorseguro"
                prefix="R$"
                label="Seguro"
                :rules="maiorQueZeroRule"
                @change="recalcularValorTotal()"
              />
            </div>
          </div>
          <div class="row justify-end q-col-gutter-md">
            <div class="col-6">
              <MgInputValor
                v-model="edicao.valoroutras"
                prefix="R$"
                label="Outras"
                :rules="maiorQueZeroRule"
                @change="recalcularValorTotal()"
              />
            </div>
          </div>
          <div class="row justify-end q-col-gutter-md">
            <div class="col-6">
              <MgInputValor
                v-model="edicao.valortotal"
                prefix="R$"
                label="Total"
                :rules="preenchimentoObrigatorioRule"
                @change="recalcularValorTotal()"
              />
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn
            flat
            label="Cancelar"
            color="grey-8"
            @click="sNegocio.dialog.valores = false"
            tabindex="-1"
          />
          <q-btn type="submit" flat label="Salvar" color="primary" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <!-- DIALOGS DE PAGAMENTOS -->
  <mg-cobranca-dialog
    ref="receberDialogRef"
    @pagamento="pagamentoRecebido"
    @parcelas="parcelasRecebidas"
    @cobranca="cobrancaCriada"
  />
  <pix-cob-dialog :impressora="sNegocio.padrao.impressora" />
  <pagar-me-pedido-dialog />
  <saurus-pedido-dialog />
  <pagamento-dialog />

  <template v-if="sNegocio.negocio">
    <!-- TOTAIS -->
    <q-list dense class="q-mt-md">
      <q-item
        v-if="parseFloat(sNegocio.negocio.valorprodutos) - parseFloat(sNegocio.negocio.valortotal)"
      >
        <q-item-section>
          <q-item-label caption>Produtos</q-item-label>
        </q-item-section>
        <q-item-section class="text-right">
          <q-item-label class="text-h5 text-grey-6">
            {{ formataNumero(sNegocio.negocio.valorprodutos) }}
          </q-item-label>
        </q-item-section>
      </q-item>

      <q-item v-if="sNegocio.negocio.valorvales">
        <q-item-section>
          <q-item-label caption>Vales</q-item-label>
        </q-item-section>
        <q-item-section class="text-right">
          <q-item-label class="text-h5 text-grey-6">
            {{ formataNumero(sNegocio.negocio.valorvales) }}
          </q-item-label>
        </q-item-section>
      </q-item>

      <q-item v-if="descontoItens">
        <q-item-section>
          <q-item-label caption>Desconto</q-item-label>
        </q-item-section>
        <q-item-section class="text-right">
          <q-item-label class="text-h5 text-weight-bolder text-green-8">
            {{ formataNumero(descontoItens) }}
          </q-item-label>
        </q-item-section>
      </q-item>

      <q-item v-if="descontoPagamentos">
        <q-item-section>
          <q-item-label caption>Desconto no pagamento</q-item-label>
        </q-item-section>
        <q-item-section class="text-right">
          <q-item-label class="text-h5 text-weight-bolder text-green-8">
            {{ formataNumero(descontoPagamentos) }}
          </q-item-label>
        </q-item-section>
      </q-item>

      <q-item v-if="sNegocio.negocio.valorfrete">
        <q-item-section>
          <q-item-label caption>Frete</q-item-label>
        </q-item-section>
        <q-item-section class="text-right">
          <q-item-label class="text-h5 text-grey-6">
            {{ formataNumero(sNegocio.negocio.valorfrete) }}
          </q-item-label>
        </q-item-section>
      </q-item>

      <q-item v-if="sNegocio.negocio.valorseguro">
        <q-item-section>
          <q-item-label caption>Seguro</q-item-label>
        </q-item-section>
        <q-item-section class="text-right">
          <q-item-label class="text-h5 text-grey-6">
            {{ formataNumero(sNegocio.negocio.valorseguro) }}
          </q-item-label>
        </q-item-section>
      </q-item>

      <q-item v-if="sNegocio.negocio.valoroutras">
        <q-item-section>
          <q-item-label caption>Outras</q-item-label>
        </q-item-section>
        <q-item-section class="text-right">
          <q-item-label class="text-h5 text-grey-6">
            {{ formataNumero(sNegocio.negocio.valoroutras) }}
          </q-item-label>
        </q-item-section>
      </q-item>

      <q-item v-if="sNegocio.negocio.valorjuros">
        <q-item-section>
          <q-item-label caption>Juros</q-item-label>
        </q-item-section>
        <q-item-section class="text-right">
          <q-item-label class="text-h5 text-grey-6">
            {{ formataNumero(sNegocio.negocio.valorjuros) }}
          </q-item-label>
        </q-item-section>
      </q-item>

      <!-- TOTAL -->
      <q-item @click="editarValores()" v-ripple :clickable="sNegocio.podeEditar">
        <q-item-section class="text-right">
          <Transition
            mode="out-in"
            :duration="{ enter: 300, leave: 300 }"
            leave-active-class="animated bounceOut"
            enter-active-class="animated bounceIn"
          >
            <q-item-label class="" :key="sNegocio.negocio.valortotal">
              <span class="float-left text-grey">R$ </span>
              <span class="text-h3 text-primary text-weight-bolder">
                {{ formataNumero(sNegocio.negocio.valortotal) }}
              </span>
            </q-item-label>
          </Transition>
        </q-item-section>
      </q-item>
    </q-list>

    <!-- RECEBER: sem nenhum lançamento ainda, o botão é a porta de entrada -->
    <div class="q-px-md q-pt-sm" v-if="mostrarReceber">
      <q-btn
        outline
        color="primary"
        class="full-width"
        icon="add"
        label="Receber"
        @click="receber()"
      >
        <q-badge outline color="primary" label="F8" class="q-ml-sm" />
      </q-btn>
    </div>

    <!-- PAGAMENTOS + COBRANÇAS EM ABERTO -->
    <q-list v-if="temLancamento" class="q-pt-sm">
      <!-- VALES AGRUPADOS: uma linha, que abre no toque -->
      <template v-if="agruparVales">
        <q-item clickable v-ripple @click="valesExpandidos = !valesExpandidos">
          <q-item-section avatar>
            <logo-pagamento v-bind="visualPagamento(valesLancados[0])" size="40px" />
          </q-item-section>
          <q-item-section>
            <q-item-label class="ellipsis">Vale Compras</q-item-label>
            <q-item-label caption class="ellipsis"> {{ valesLancados.length }} vales </q-item-label>
          </q-item-section>
          <q-item-section side class="text-subtitle1 text-weight-bold text-grey-9">
            {{ formataNumero(valesTotal) }}
            <q-icon :name="valesExpandidos ? 'expand_less' : 'expand_more'" color="grey-6" />
          </q-item-section>
        </q-item>
        <q-item
          v-for="pag in valesExpandidos ? valesLancados : []"
          :key="pag.uuid"
          clickable
          v-ripple
          class="q-pl-xl"
          @click="abrirPagamento(pag)"
        >
          <q-item-section>
            <q-item-label class="ellipsis">Vale #{{ pag.codtitulo }}</q-item-label>
          </q-item-section>
          <q-item-section side class="text-subtitle1 text-grey-8">
            {{ formataNumero(pag.total) }}
          </q-item-section>
        </q-item>
      </template>

      <q-item
        v-for="pag in pagamentosVisiveis"
        :key="pag.uuid"
        clickable
        v-ripple
        @click="abrirPagamento(pag)"
      >
        <q-item-section avatar>
          <logo-pagamento v-bind="visualPagamento(pag)" size="40px" />
        </q-item-section>
        <q-item-section :class="{ 'text-strike text-grey-6': pag.estado == 'C' }">
          <q-item-label class="ellipsis">{{ tituloPagamento(pag) }}</q-item-label>
          <q-item-label caption class="ellipsis" v-if="pag.estado == 'C'">Cancelado</q-item-label>
          <q-item-label caption class="ellipsis" v-else-if="resumoPagamento(pag)">
            {{ resumoPagamento(pag) }}
          </q-item-label>
        </q-item-section>
        <q-item-section
          side
          class="text-subtitle1 text-weight-bold"
          :class="pag.estado == 'C' ? 'text-strike text-grey-6' : 'text-grey-9'"
        >
          {{ formataNumero(valorExibido(pag)) }}
        </q-item-section>
      </q-item>

      <!-- PRAZO: uma linha por condição, com as datas -->
      <q-item
        v-for="grupo in grupos"
        :key="'parcelas' + grupo.condicao + (grupo.inativa ? 'i' : '')"
        clickable
        v-ripple
        @click="abrirParcelas(grupo)"
      >
        <q-item-section avatar>
          <logo-pagamento v-bind="visualCondicao(grupo.condicao)" size="40px" />
        </q-item-section>
        <q-item-section :class="{ 'text-strike text-grey-6': grupo.inativa }">
          <q-item-label class="ellipsis">{{ CONDICOES[grupo.condicao] }}</q-item-label>
          <q-item-label caption class="ellipsis" v-if="grupo.inativa">Cancelado</q-item-label>
          <q-item-label caption class="ellipsis" v-else>{{ resumoParcelas(grupo) }}</q-item-label>
        </q-item-section>
        <q-item-section
          side
          class="text-subtitle1 text-weight-bold"
          :class="grupo.inativa ? 'text-strike text-grey-6' : 'text-grey-9'"
        >
          {{ formataNumero(grupo.valor) }}
        </q-item-section>
      </q-item>

      <!-- SALDO: faltando é a ação de receber (clique ou F8); troco só informa -->
      <div class="q-px-sm q-pt-sm" v-if="mostrarSaldo">
        <q-item
          class="rounded-borders q-px-sm"
          :class="[faltando ? 'bg-red-1' : 'bg-green-1', { 'animated flash': piscandoSaldo }]"
          :clickable="podeReceber"
          v-ripple="podeReceber"
          @click="podeReceber && receber()"
        >
          <q-item-section>
            <q-item-label class="text-subtitle1" :class="faltando ? 'text-red-9' : 'text-green-9'">
              {{ faltando ? 'Faltando' : 'Troco' }}
              <q-badge v-if="podeReceber" outline color="red-9" label="F8" class="q-ml-sm" />
            </q-item-label>
          </q-item-section>
          <q-item-section
            side
            class="text-h5 text-weight-bold"
            :class="faltando ? 'text-red-9' : 'text-green-9'"
          >
            {{ formataNumero(Math.abs(sNegocio.valorapagar)) }}
          </q-item-section>
        </q-item>
      </div>

      <!-- COBRANÇAS: abaixo do saldo, separadas do que já foi pago -->
      <template v-for="grupo in gruposCobranca" :key="grupo.titulo">
        <q-item-label header>{{ grupo.titulo }}</q-item-label>
        <q-item v-for="cob in grupo.itens" :key="cob.chave" clickable v-ripple @click="cob.abrir()">
          <q-item-section avatar>
            <q-avatar :color="cob.cor" text-color="white" :icon="cob.icone" />
          </q-item-section>
          <q-item-section>
            <q-item-label class="ellipsis">
              {{ cob.titulo }}
              <q-badge :color="cob.cor" :label="cob.status" class="q-ml-xs text-lowercase" />
            </q-item-label>
            <q-item-label caption class="ellipsis">{{ cob.resumo }}</q-item-label>
          </q-item-section>
          <q-item-section side class="text-subtitle1 text-weight-bold text-grey-6">
            {{ formataNumero(cob.valor) }}
          </q-item-section>
        </q-item>
      </template>
    </q-list>
  </template>
</template>
