<script setup>
// Dialog especialista do pedido Safrapay/Saurus: foca em Consultar (Enter) e consulta sozinho
// a cada 3s enquanto o pedido estiver pendente.
import { ref } from 'vue'
import { debounce } from 'quasar'
import { saurusStore } from 'stores/saurus'
import { formataNumero, formataTimestampCompleto } from '@components/formatters'
import { useConsultaAutomatica } from '../../composables/useConsultaAutomatica.js'
import emitter from '../../utils/emitter.js'

const sSaurus = saurusStore()
const btnConsultarRef = ref(null)

// 0 pendente · 2 aprovado · 3 cancelado
const pendente = () => sSaurus.pedido?.status == 0

const verificarPago = () => {
  if (sSaurus.pedido.status == 2) {
    parar()
    sSaurus.dialog.detalhesPedido = false
    emitter.emit('pagamentoAdicionado')
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

const cancelar = async () => {
  await sSaurus.cancelarPedido()
  if (sSaurus.pedido.status == 3) {
    sSaurus.dialog.detalhesPedido = false
  }
}

const onShow = () => {
  btnConsultarRef.value?.$el?.focus()
  iniciar()
}
</script>
<template>
  <q-dialog v-model="sSaurus.dialog.detalhesPedido" @show="onShow" @hide="parar">
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
          label="cancelar"
          color="grey-8"
          @click="cancelar()"
          tabindex="-1"
          v-if="sSaurus.pedido.status != 3"
        />
        <q-btn flat label="consultar" color="primary" @click="consultar()" ref="btnConsultarRef" />
        <q-btn
          v-if="sSaurus.pedido.status == 0"
          flat
          label="Reenviar"
          color="primary"
          @click="reenviar()"
          tabindex="-1"
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
