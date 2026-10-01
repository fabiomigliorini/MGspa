<script setup>
import { formataNumero, formataCodigo, formataData } from '@components/formatters'
import { ref, watch } from 'vue'
import { debounce } from 'quasar'
import { liquidacaoStore } from 'src/stores/liquidacao'
import moment from 'moment/min/moment-with-locales'
moment.locale('pt-br')

const sLiquidacao = liquidacaoStore()
const scrollRef = ref(null)

const onLoad = async (index, done) => {
  await sLiquidacao.getLiquidacoesPaginacao()
  if (sLiquidacao.paginacao.current_page >= sLiquidacao.paginacao.last_page) {
    done(true)
  } else {
    done(false)
  }
}

const inicializa = debounce(async () => {
  await sLiquidacao.getLiquidacoes()
  try {
    scrollRef.value.reset()
    scrollRef.value.resume()
  } catch (error) {
    console.log(error)
  }
})

watch(
  () => sLiquidacao.filtro,
  () => {
    inicializa()
  },
  { deep: true },
)

// recebimentos e pagamentos de títulos (M6 doc-3; era a liquidação)
const statusClass = (pag) => {
  if (pag.estado == 'C') {
    return 'bg-deep-orange-1 text-deep-orange-10'
  }
  return ''
}

// CR recebeu, DB pagou, CP encontro de contas sem dinheiro
const iconeLiquidacao = (pag) =>
  ({ DB: 'mdi-checkbook-arrow-left', CR: 'mdi-checkbook-arrow-right' })[pag.operacao] ??
  'mdi-checkbook'

const corIconeLiquidacao = (pag) => ({ DB: 'secondary', CR: 'negative' })[pag.operacao] ?? 'grey'

const descricaoOperacao = (pag) =>
  ({ DB: 'Pago', CR: 'Recebido' })[pag.operacao] ?? 'Encontro de contas'

const urlPagamento = (pag) => `${process.env.CONTAS_URL}/pagamento/${pag.codpagamento}`
</script>
<template>
  <q-page class="bg-grey-2">
    <!-- <pre>
      {{ sLiquidacao.listagem[0] }}
    </pre> -->
    <div v-if="sLiquidacao.listagem.length == 0" class="absolute-center text-grey text-center">
      <q-icon name="do_not_disturb" color="" size="300px" />
      <h3>Nenhum registro localizado!</h3>
    </div>
    <q-list v-else>
      <q-infinite-scroll @load="onLoad" ref="scrollRef">
        <template :key="index" v-for="(item, index) in sLiquidacao.listagem">
          <q-item :href="urlPagamento(item)" target="_blank" :class="statusClass(item)" class="row">
            <!-- ICONE -->
            <q-item-section avatar>
              <q-avatar
                :icon="iconeLiquidacao(item)"
                :color="corIconeLiquidacao(item)"
                text-color="white"
              />
            </q-item-section>

            <!-- PORTADOR -->
            <q-item-section class="col-2">
              <q-item-label lines="1">
                {{ item.portador || item.meiodescricao }}
              </q-item-label>
              <q-item-label class="ellipsis" caption>
                {{ formataCodigo(item.codpagamento) }}
              </q-item-label>
            </q-item-section>

            <!-- VALOR -->
            <q-item-section class="col-xs-3 col-sm-2">
              <q-item-label class="text-right">
                {{ formataNumero(item.total) }}
              </q-item-label>
              <q-item-label class="ellipsis text-right" caption>
                {{ descricaoOperacao(item) }}
              </q-item-label>
            </q-item-section>

            <!-- PESSOA/VENDEDOR/COD/NATUREZA -->
            <q-item-section>
              <q-item-label>
                {{ item.fantasia }}
              </q-item-label>
              <q-item-label caption>
                {{ item.meiodescricao }}
                <span v-if="item.estado == 'C'"> · Estornado </span>
                <span v-if="item.codperiodocolaboradoracerto"> · Acerto RH </span>
              </q-item-label>
            </q-item-section>

            <!-- OBSERVACAO -->
            <q-item-section class="gt-sm col-sm-3 col-md-2 col-lg-1">
              <q-item-label caption lines="3" style="white-space: pre-line">
                {{ item.observacoes }}
              </q-item-label>
            </q-item-section>

            <!-- DATA/STATUS -->
            <q-item-section class="col-xs-4 col-sm-3 col-md-2 col-lg-1 ellipsis" side>
              <q-item-label caption>
                {{ formataData(item.lancamento) }}
              </q-item-label>
              <q-item-label caption>
                {{ item.usuariocriacao }}
              </q-item-label>
              <q-item-label caption v-if="item.codpdv">
                {{ item.pdv }}
              </q-item-label>
            </q-item-section>
          </q-item>
          <q-separator />
        </template>

        <template v-slot:loading>
          <div class="row justify-center q-my-md">
            <q-spinner-dots color="primary" size="40px" />
          </div>
        </template>
      </q-infinite-scroll>
    </q-list>
  </q-page>
</template>
