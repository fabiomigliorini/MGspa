<script setup>
// Dialog especialista do pedido Stone/PagarMe: foca em Consultar (Enter) e consulta sozinho
// a cada 3s enquanto o pedido estiver pendente.
import { ref } from 'vue'
import { debounce, useQuasar } from 'quasar'
import { pagarMeStore } from '@components/stores/pagarMeStore'
import { formataNumero, formataTimestampCompleto } from '@components/formatters'
import { useConsultaAutomatica } from './useConsultaAutomatica.js'
import { avisar } from './eventos.js'

const $q = useQuasar()
const sPagarMe = pagarMeStore()
const btnConsultarRef = ref(null)

// 1 pendente · 2 pago · 3 cancelado · 4 falhou
const pendente = () => sPagarMe.pedido?.status == 1

const verificarPago = () => {
  if (sPagarMe.pedido.status == 2) {
    parar()
    sPagarMe.dialog.detalhesPedido = false
    avisar('cobrancaConcluida', { tipo: 'pagarme', dados: sPagarMe.pedido })
  }
}

const { iniciar, parar } = useConsultaAutomatica({
  pendente,
  consultar: async () => {
    const ok = await sPagarMe.consultarPedido(true)
    verificarPago()
    return ok
  },
})

const consultar = debounce(async () => {
  await sPagarMe.consultarPedido()
  verificarPago()
  iniciar()
}, 500)

// mesmo modelo da Safrapay/Saurus: pede para digitar DESISTIR. O botão se chama Desistir:
// "Cancelar" no rodapé de um dialog lê-se como fechar a janela
// A confirmação fecha junto com a cobrança: o cliente pode pagar enquanto o caixa digita
let confirmacao = null

const desistir = () => {
  confirmacao = $q
    .dialog({
      title: 'Desistir da cobrança?',
      message: 'Digite DESISTIR para confirmar:',
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
      const ok = await sPagarMe.cancelarPedido()
      if (ok === false) {
        // recusado (já pago, por exemplo): mostra o que a Stone tem
        await sPagarMe.consultarPedido(true)
        verificarPago()
        return
      }
      if (sPagarMe.pedido.status == 3) {
        sPagarMe.dialog.detalhesPedido = false
      }
    })
    .onDismiss(() => (confirmacao = null))
}

const onShow = () => {
  btnConsultarRef.value?.$el?.focus()
  iniciar()
}

const onHide = () => {
  parar()
  confirmacao?.hide()
}
</script>
<template>
  <q-dialog v-model="sPagarMe.dialog.detalhesPedido" @show="onShow" @hide="onHide">
    <q-card flat style="width: 600px">
      <q-card-section>
        <div class="text-h6">
          Cobrança Stone/PagarMe de R$
          {{ formataNumero(sPagarMe.pedido.valor) }}
        </div>
        <div class="text-subtitle2 text-grey text-uppercase">
          {{ sPagarMe.pedido.statusdescricao }}
          <span v-if="pendente()" class="text-lowercase"> · consultando automaticamente</span>
        </div>

        <!-- PAGAMENTOS -->
        <q-list>
          <template v-for="pag in sPagarMe.pedido.PagarMePagamentoS" :key="pag.codpagarmepagamento">
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
                    {{ formataNumero(pag.valorpagamento) }}
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
              <q-item-label class="ellipsis"> Pedido {{ sPagarMe.pedido.idpedido }} </q-item-label>
              <q-item-label caption class="ellipsis">
                <span v-if="sPagarMe.pedido.fechado"> Fechado </span>
                <span v-else> Aberto </span>
              </q-item-label>
            </q-item-section>
          </q-item>

          <template v-if="!sPagarMe.pedido.PagarMePagamentoS?.length">
            <!-- POS -->
            <q-separator spaced />
            <q-item>
              <q-item-section avatar>
                <q-icon color="primary" name="point_of_sale" />
              </q-item-section>
              <q-item-section>
                <q-item-label>
                  POS {{ sPagarMe.pedido.apelido }} Serial
                  {{ sPagarMe.pedido.pos }}
                </q-item-label>
                <q-item-label caption>
                  {{ formataTimestampCompleto(sPagarMe.pedido.criacao) }}
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
                  {{ formataNumero(sPagarMe.pedido.valor) }}
                </q-item-label>
                <q-item-label caption>
                  <span class="text-uppercase">
                    {{ sPagarMe.pedido.tipodescricao }}
                  </span>
                  <span v-if="sPagarMe.pedido.parcelas > 1">
                    em {{ sPagarMe.pedido.parcelas }}
                    parcelas de R$
                    {{ formataNumero(sPagarMe.pedido.valorparcela) }}
                    <span v-if="sPagarMe.pedido.valorjuros"> (C/Juros) </span>
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
          v-if="sPagarMe.pedido.status == 1"
        />
        <q-btn flat label="consultar" color="primary" @click="consultar()" ref="btnConsultarRef" />
        <q-btn
          flat
          label="Fechar"
          color="primary"
          @click="sPagarMe.dialog.detalhesPedido = false"
          tabindex="-1"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>
