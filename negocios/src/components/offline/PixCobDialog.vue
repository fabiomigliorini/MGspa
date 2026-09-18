<script setup>
// Dialog especialista da cobrança PIX (QR Code): transmite ao banco se for nova, mostra o QR,
// foca em Consultar (Enter) e consulta sozinho a cada 3s enquanto estiver pendente.
import { ref, watch } from 'vue'
import { Notify, debounce } from 'quasar'
import QRCode from 'qrcode'
import { pixStore } from 'stores/pix'
import {
  formataCpf,
  formataCnpj,
  formataNumero,
  formataTimestampCompleto,
} from '@components/formatters'
import { useConsultaAutomatica } from '../../composables/useConsultaAutomatica.js'
import emitter from '../../utils/emitter.js'

const sPix = pixStore()

const btnConsultarRef = ref(null)
const qrDataUrl = ref(null)

const pendente = () => !['CONCLUIDA', 'EXPIRADO'].includes(sPix.pixCob?.status)

const verificarConcluida = () => {
  if (sPix.pixCob.status == 'CONCLUIDA') {
    parar()
    sPix.dialog.detalhesPixCob = false
    emitter.emit('pagamentoAdicionado')
  }
}

const { iniciar, parar } = useConsultaAutomatica({
  pendente,
  consultar: async () => {
    const ok = await sPix.consultarPixCob(true)
    verificarConcluida()
    return ok
  },
})

const consultar = debounce(async () => {
  await sPix.consultarPixCob()
  verificarConcluida()
  iniciar()
}, 500)

const onShow = async () => {
  if (sPix.pixCob.qrcode == null) {
    await sPix.transmitirPixCob()
  }
  btnConsultarRef.value?.$el?.focus()
  iniciar()
}

const onHide = () => {
  parar()
}

// QR gerado localmente (funciona sem internet e não manda o payload para fora)
watch(
  () => sPix.pixCob?.qrcode,
  async (qrcode) => {
    qrDataUrl.value = qrcode ? await QRCode.toDataURL(qrcode, { width: 512, margin: 1 }) : null
  },
  { immediate: true },
)

const transmitir = () => {
  sPix.transmitirPixCob()
}

const imprimir = () => {
  sPix.imprimirPixCob()
}

const textoMensagem = () => {
  var mensagem = 'Olá,\n\n'
  mensagem +=
    'Você está recebendo um link para pagamento via PIX de sua compra na *MG Papelaria* no valor de R$ *' +
    sPix.pixCob.valororiginal.toLocaleString('pt-br', {
      minimumFractionDigits: 2,
    }) +
    '*!\n\n'
  mensagem +=
    'Abra https://pix.mgpapelaria.com.br/' + sPix.pixCob.codpixcob + ' e siga as instruções:\n\n'
  mensagem += '*Obrigado* pela confiança!'
  return mensagem
}

const mensagem = () => {
  navigator.clipboard.writeText(textoMensagem()).then(() => {
    Notify.create({
      type: 'positive',
      message: 'Mensagem copiada para a área de transferência!',
      timeout: 1000, // 1 segundo
      actions: [{ icon: 'close', color: 'white' }],
    })
  })
}

const whatsapp = () => {
  window.open('whatsapp://send?text=' + encodeURI(textoMensagem()))
}
</script>
<template>
  <q-dialog v-model="sPix.dialog.detalhesPixCob" @show="onShow" @hide="onHide">
    <q-card flat>
      <q-card-section>
        <div class="text-h6">Cobrança PIX de R$ {{ formataNumero(sPix.pixCob.valororiginal) }}</div>
        <div class="text-subtitle2 text-grey">
          {{ sPix.pixCob.status }}
          <span v-if="pendente()"> · consultando automaticamente</span>
        </div>

        <template v-if="sPix.pixCob.status == 'CONCLUIDA'">
          <template v-for="pix in sPix.pixCob.PixS" :key="pix.codpix">
            <q-list>
              <q-separator spaced />

              <!-- VALOR -->
              <q-item>
                <q-item-section avatar>
                  <q-icon color="primary" name="attach_money" />
                </q-item-section>
                <q-item-section>
                  <q-item-label> R$ {{ formataNumero(pix.valor) }} </q-item-label>
                  <q-item-label caption> Valor efetivamente Pago </q-item-label>
                </q-item-section>
              </q-item>
              <q-separator spaced />

              <!-- NOME -->
              <q-item>
                <q-item-section avatar>
                  <q-icon color="primary" name="person" />
                </q-item-section>
                <q-item-section>
                  <q-item-label>
                    {{ pix.nome }}
                  </q-item-label>
                  <q-item-label caption>
                    <template v-if="pix.cpf">
                      {{ formataCpf(pix.cpf) }}
                    </template>
                    <template v-if="pix.cnpj">
                      {{ formataCnpj(pix.cnpj) }}
                    </template>
                  </q-item-label>
                </q-item-section>
              </q-item>
              <q-separator spaced />

              <!-- ID -->
              <q-item>
                <q-item-section avatar>
                  <q-icon color="primary" name="fingerprint" />
                </q-item-section>
                <q-item-section>
                  <q-item-label class="ellipsis">
                    {{ pix.e2eid }}
                  </q-item-label>
                  <q-item-label caption class="ellipsis">
                    {{ pix.txid }}
                  </q-item-label>
                </q-item-section>
              </q-item>
              <q-separator spaced />

              <!-- PORTADOR -->
              <q-item>
                <q-item-section avatar>
                  <q-icon color="primary" name="place" />
                </q-item-section>
                <q-item-section>
                  <q-item-label>
                    {{ sPix.pixCob.portador }}
                  </q-item-label>
                  <q-item-label caption>
                    {{ formataTimestampCompleto(pix.horario) }}
                  </q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </template>
        </template>
        <template v-else>
          <q-img v-if="qrDataUrl" class="q-my-lg" :src="qrDataUrl" ratio="1" />
          <q-list>
            <!-- ID -->
            <q-item>
              <q-item-section avatar>
                <q-icon color="primary" name="fingerprint" />
              </q-item-section>
              <q-item-section>
                <q-item-label class="ellipsis">
                  {{ sPix.pixCob.qrcode }}
                </q-item-label>
                <q-item-label caption class="ellipsis">
                  {{ sPix.pixCob.txid }}
                </q-item-label>
              </q-item-section>
            </q-item>

            <div class="gt-xs">
              <q-separator spaced />

              <!-- PORTADOR -->
              <q-item>
                <q-item-section avatar>
                  <q-icon color="primary" name="place" />
                </q-item-section>
                <q-item-section>
                  <q-item-label>
                    {{ sPix.pixCob.portador }}
                  </q-item-label>
                  <q-item-label caption>
                    {{ formataTimestampCompleto(sPix.pixCob.criacao) }}
                  </q-item-label>
                </q-item-section>
              </q-item>
            </div>
          </q-list>
        </template>
      </q-card-section>
      <q-card-actions align="right">
        <template v-if="sPix.pixCob.status != 'CONCLUIDA'">
          <q-btn flat label="transmitir" color="primary" @click="transmitir()" tabindex="-1" />
          <q-btn
            flat
            label="whatsapp"
            color="primary"
            @click="whatsapp()"
            tabindex="-1"
            class="mobile-only"
          />
          <q-btn
            flat
            label="Mensagem"
            color="primary"
            @click="mensagem()"
            tabindex="-1"
            class="desktop-only"
          />
          <q-btn flat label="imprimir" color="primary" @click="imprimir()" tabindex="-1" />
        </template>
        <q-btn flat label="consultar" color="primary" @click="consultar()" ref="btnConsultarRef" />
        <q-btn
          flat
          label="Fechar"
          color="primary"
          @click="sPix.dialog.detalhesPixCob = false"
          tabindex="-1"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>
