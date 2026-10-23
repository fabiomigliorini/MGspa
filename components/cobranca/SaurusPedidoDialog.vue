<script setup>
// Dialog especialista do pedido Safrapay/Saurus: foca em Consultar (Enter) e consulta sozinho
// a cada 3s enquanto o pedido estiver pendente.
import { ref } from 'vue'
import { debounce, useQuasar } from 'quasar'
import { saurusStore } from '@components/stores/saurusStore'
import { formataNumero, formataTimestampCompleto } from '@components/formatters'
import { useConsultaAutomatica } from './useConsultaAutomatica.js'
import { avisar } from './eventos.js'
import { cancelarPagamento } from './cancelarPagamento.js'

const $q = useQuasar()
const sSaurus = saurusStore()
const btnConsultarRef = ref(null)
const btnReenviarRef = ref(null)

// 0 pendente · 2 aprovado · 3 cancelado
const pendente = () => sSaurus.pedido?.status == 0

// sem id a SafraPay não confirmou que recebeu: no timeout pode ter chegado, então segue
// consultando, mas o caixa sabe que pode Reenviar (TASK-118)
const naoConfirmado = () => pendente() && !sSaurus.pedido?.id

const verificarPago = () => {
  if (sSaurus.pedido.status == 2) {
    parar()
    sSaurus.dialog.detalhesPedido = false
    avisar('cobrancaConcluida', { tipo: 'saurus', dados: sSaurus.pedido })
  }
}

const { iniciar, parar } = useConsultaAutomatica({
  pendente,
  consultar: async () => {
    const ok = await sSaurus.consultarPedido(true)
    verificarPago()
    return ok
  },
})

const consultar = debounce(async () => {
  await sSaurus.consultarPedido()
  verificarPago()
  iniciar()
}, 500)

const reenviar = async () => {
  await sSaurus.reenviarPedido()
}

// a maquininha aceita pagar depois do cancelamento, e o cancelamento não vai para a Saurus:
// pede para digitar DESISTIR. O backend consulta antes e recusa se já foi pago (TASK-233).
// O botão se chama Desistir: "Cancelar" no rodapé de um dialog lê-se como fechar a janela
// A confirmação fecha junto com a cobrança: o cliente pode pagar enquanto o caixa digita
let confirmacao = null

const desistir = () => {
  confirmacao = $q
    .dialog({
      title: 'Desistir da cobrança?',
      message:
        'A maquininha continua aceitando o pagamento depois do cancelamento. Se o cliente pagar, abra a cobrança e consulte para puxar o cartão. Digite DESISTIR para confirmar:',
      prompt: {
        model: '',
        type: 'text',
        outlined: true,
        placeholder: 'DESISTIR',
        isValid: (v) => (v || '').trim() === 'DESISTIR',
      },
      ok: { label: 'Desistir', color: 'negative', flat: true },
      cancel: { label: 'Voltar', color: 'grey-8', flat: true },
    })
    .onOk(async () => {
      const ok = await sSaurus.cancelarPedido()
      if (ok === false) {
        // recusado (já pago, por exemplo): mostra o que a Saurus tem
        await sSaurus.consultarPedido(true)
        verificarPago()
        return
      }
      if (sSaurus.pedido.status == 3) {
        sSaurus.dialog.detalhesPedido = false
      }
    })
    .onDismiss(() => (confirmacao = null))
}

const onShow = () => {
  const btn = naoConfirmado() ? btnReenviarRef : btnConsultarRef
  btn.value?.$el?.focus()
  iniciar()
}

const onHide = () => {
  parar()
  confirmacao?.hide()
}

// o pagamento que esta cobrança gerou: cancela em qualquer tela; quem pode e o que falta (a venda
// aberta), o servidor diz (TASK-30)
const cancelarPagamentoGerado = async () => {
  const ok = await cancelarPagamento({
    codpagamento: sSaurus.pedido.codpagamento,
    codnegocio: sSaurus.pedido.codnegocio,
    aviso: 'Não estorna na maquininha: o estorno é feito nela.',
  })
  if (ok) {
    sSaurus.pedido.codpagamento = null
    sSaurus.dialog.detalhesPedido = false
  }
}
</script>
<template>
  <q-dialog v-model="sSaurus.dialog.detalhesPedido" @show="onShow" @hide="onHide">
    <q-card flat style="width: 600px">
      <q-card-section>
        <div class="text-h6">
          Cobrança Safrapay/Saurus de R$
          {{ formataNumero(sSaurus.pedido.valor) }}
        </div>
        <div class="text-subtitle2 text-grey text-uppercase">
          {{ sSaurus.pedido.statusdescricao }}
          <span v-if="pendente()" class="text-lowercase"> · consultando automaticamente</span>
        </div>
        <div v-if="naoConfirmado()" class="text-negative">
          A SafraPay não confirmou que a cobrança chegou na maquininha. Se não apareceu lá,
          Reenviar.
        </div>

        <!-- PAGAMENTOS -->
        <q-list>
          <template v-for="pag in sSaurus.pedido.SaurusPagamentoS" :key="pag.codsauruspagamento">
            <!-- VALOR -->
            <q-separator spaced />
            <template v-if="pag.valorcancelamento">
              <q-item>
                <q-item-section avatar>
                  <q-icon color="negative" name="attach_money" />
                </q-item-section>
                <q-item-section>
                  <q-item-label>
                    R$
                    {{ formataNumero(pag.valorcancelamento) }}
                  </q-item-label>
                  <q-item-label caption> Valor Cancelamento </q-item-label>
                </q-item-section>
              </q-item>
            </template>
            <template v-else>
              <q-item>
                <q-item-section avatar>
                  <q-icon color="secondary" name="attach_money" />
                </q-item-section>
                <q-item-section>
                  <q-item-label>
                    R$
                    {{ formataNumero(pag.valortotal) }}
                  </q-item-label>
                  <q-item-label caption> Valor efetivamente Pago </q-item-label>
                </q-item-section>
              </q-item>
            </template>

            <!-- NOME -->
            <q-separator spaced />
            <q-item>
              <q-item-section avatar>
                <q-icon color="primary" name="person" />
              </q-item-section>
              <q-item-section>
                <q-item-label>
                  {{ pag.bandeira }}
                  <span v-if="pag.nome">
                    {{ pag.nome }}
                  </span>
                </q-item-label>
                <q-item-label caption>
                  <span class="text-uppercase">
                    {{ pag.tipodescricao }}
                  </span>
                  <span v-if="pag.parcelas > 1"> em {{ pag.parcelas }} parcelas </span>
                </q-item-label>
              </q-item-section>
            </q-item>

            <!-- ID -->
            <q-separator spaced />
            <q-item>
              <q-item-section avatar>
                <q-icon color="primary" name="fingerprint" />
              </q-item-section>
              <q-item-section>
                <q-item-label class="ellipsis">
                  Autorização {{ pag.autorizacao }} <br />
                </q-item-label>
                <q-item-label caption class="ellipsis">
                  NSU {{ pag.nsu }} <br />
                  Identificador {{ pag.identificador }} <br />
                  Transação {{ pag.idtransacao }} <br />
                </q-item-label>
              </q-item-section>
            </q-item>

            <!-- POS -->
            <q-separator spaced />
            <q-item>
              <q-item-section avatar>
                <q-icon color="primary" name="point_of_sale" />
              </q-item-section>
              <q-item-section>
                <q-item-label> POS {{ pag.apelido }} Serial {{ pag.pos }} </q-item-label>
                <q-item-label caption>
                  {{ formataTimestampCompleto(pag.horario) }}
                </q-item-label>
              </q-item-section>
            </q-item>
          </template>

          <!-- ID PEDIDO -->
          <q-separator spaced />
          <q-item>
            <q-item-section avatar>
              <q-icon color="primary" name="post_add" />
            </q-item-section>
            <q-item-section>
              <q-item-label class="ellipsis"> Pedido {{ sSaurus.pedido.idpedido }} </q-item-label>
              <q-item-label caption class="ellipsis">
                <span v-if="sSaurus.pedido.fechado"> Fechado </span>
                <span v-else> Aberto </span>
              </q-item-label>
            </q-item-section>
          </q-item>

          <template v-if="!sSaurus.pedido.SaurusPagamentoS?.length">
            <!-- POS -->
            <q-separator spaced />
            <q-item>
              <q-item-section avatar>
                <q-icon color="primary" name="point_of_sale" />
              </q-item-section>
              <q-item-section>
                <q-item-label>
                  POS: {{ sSaurus.pedido.apelido }} <br />Serial:
                  {{ sSaurus.pedido.pos }}
                </q-item-label>
                <q-item-label caption>
                  {{ formataTimestampCompleto(sSaurus.pedido.criacao) }}
                </q-item-label>
              </q-item-section>
            </q-item>

            <!-- VALOR -->
            <q-separator spaced />
            <q-item>
              <q-item-section avatar>
                <q-icon color="primary" name="attach_money" />
              </q-item-section>
              <q-item-section>
                <q-item-label>
                  R$
                  {{ formataNumero(sSaurus.pedido.valor) }}
                </q-item-label>
                <q-item-label caption>
                  <span class="text-uppercase">
                    {{ sSaurus.pedido.tipodescricao }}
                  </span>
                  <span v-if="sSaurus.pedido.parcelas > 1">
                    em {{ sSaurus.pedido.parcelas }}
                    parcelas de R$
                    {{ formataNumero(sSaurus.pedido.valorparcela) }}
                    <span v-if="sSaurus.pedido.valorjuros"> (C/Juros) </span>
                  </span>
                </q-item-label>
              </q-item-section>
            </q-item>
          </template>
        </q-list>
      </q-card-section>
      <q-card-actions align="right">
        <q-btn
          flat
          label="Desistir da cobrança"
          color="negative"
          @click="desistir()"
          tabindex="-1"
          v-if="sSaurus.pedido.status == 0"
        />
        <q-btn
          v-if="sSaurus.pedido.codpagamento"
          flat
          label="Cancelar pagamento"
          color="negative"
          @click="cancelarPagamentoGerado()"
          tabindex="-1"
        />
        <q-btn flat label="consultar" color="primary" @click="consultar()" ref="btnConsultarRef" />
        <q-btn
          v-if="sSaurus.pedido.status == 0"
          flat
          label="Reenviar"
          color="primary"
          @click="reenviar()"
          ref="btnReenviarRef"
        />
        <q-btn
          flat
          label="Fechar"
          color="primary"
          @click="sSaurus.dialog.detalhesPedido = false"
          tabindex="-1"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>
