<script setup>
import { computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { formataNumero, formataDataSemHora } from '@components/formatters'
import { useBoletoStore } from 'src/stores/boletoStore'
import { ESTADO_COBRANCA } from 'src/constants/tituloBoleto'
import BoletoTabs from 'src/components/BoletoTabs.vue'

const route = useRoute()
const router = useRouter()
const store = useBoletoStore()

const ano = computed(() => route.params.ano)
const mes = computed(() => route.params.mes)
const dia = computed(() => route.params.dia)
const codportador = computed(() =>
  route.params.codportador ? Number(route.params.codportador) : null,
)

const portadorAtual = computed(() =>
  store.liqPortadores.find((p) => p.codportador === codportador.value),
)

function linkPortador(cp) {
  return {
    name: 'boleto-liquidados',
    params: { ano: ano.value, mes: mes.value, dia: dia.value, codportador: cp },
  }
}

let ultimaChave = ''

async function navegar() {
  if (route.name !== 'boleto-liquidados') return

  const chaveEntrada = [
    route.params.ano || '',
    route.params.mes || '',
    route.params.dia || '',
    route.params.codportador || '',
  ].join('|')
  if (chaveEntrada === ultimaChave) return
  ultimaChave = chaveEntrada

  const resp = await store.carregarLiqNavegacao({
    ano: route.params.ano,
    mes: route.params.mes,
    dia: route.params.dia,
    codportador: route.params.codportador,
  })
  if (!resp) return

  const diaDd = (resp.dia || '').slice(-2)
  const chaveResposta = [resp.ano || '', resp.mes || '', diaDd, resp.codportador || ''].join('|')

  if (chaveResposta !== chaveEntrada && resp.ano) {
    ultimaChave = chaveResposta
    router.replace({
      name: 'boleto-liquidados',
      params: {
        ano: resp.ano,
        mes: resp.mes,
        dia: diaDd,
        codportador: resp.codportador,
      },
    })
  }
}

watch(
  () => [route.params.ano, route.params.mes, route.params.dia, route.params.codportador],
  navegar,
  { immediate: true },
)
</script>

<template>
  <q-page>
    <BoletoTabs />
    <div class="q-pa-md" style="max-width: 1086px; margin: auto">
      <q-card flat bordered class="q-mb-md">
        <q-card-section class="q-py-sm">
          <div class="text-subtitle1 text-weight-bold">
            Boletos Liquidados
            <template v-if="portadorAtual">
              · {{ portadorAtual.conta }} {{ portadorAtual.portador }}
            </template>
          </div>
          <div class="text-caption text-grey-7">
            Boletos <b>liquidados</b> por data de crédito e portador.
          </div>
        </q-card-section>

        <q-separator />

        <q-card-section v-if="store.liqPortadores.length" class="q-py-sm">
          <div class="row q-gutter-xs">
            <q-btn
              v-for="p in store.liqPortadores"
              :key="p.codportador"
              :to="linkPortador(p.codportador)"
              :outline="p.codportador !== codportador"
              :color="p.codportador === codportador ? 'primary' : 'grey-8'"
              no-caps
            >
              <span>Conta {{ p.conta }} |</span>
              <span class="q-ml-xs">{{ formataNumero(p.total) }}</span>
              <q-badge class="q-ml-xs" color="grey-7" :label="p.quantidade" />
            </q-btn>
          </div>
        </q-card-section>
      </q-card>

      <q-list bordered separator dense class="text-caption bg-white">
        <q-item class="text-weight-bold text-caption text-grey-8 gt-xs">
          <q-item-section>
            <div class="row items-center q-col-gutter-x-sm">
              <div class="col-12 col-sm-3 col-md-1">Receb.</div>
              <div class="col-6 col-sm-3 col-md-1 text-right">Valor</div>
              <div class="col-6 col-sm-3 col-md-1 text-right">Juros</div>
              <div class="col-6 col-sm-3 col-md-1 text-right">Multa</div>
              <div class="col-6 col-sm-3 col-md-1 text-right">Outro</div>
              <div class="col-6 col-sm-3 col-md-1 text-right">Pago</div>
              <div class="col-6 col-sm-6 col-md-2">Título</div>
              <div class="col-12 col-sm-12 col-md-3">Cliente</div>
              <div class="col-6 col-sm-6 col-md-1">Estado</div>
            </div>
          </q-item-section>
        </q-item>

        <q-item
          v-for="b in store.liqLista"
          :key="b.codtituloboleto"
          clickable
          :to="'/titulo/' + b.codtitulo"
        >
          <q-item-section>
            <div class="row items-center q-col-gutter-x-sm">
              <div class="col-12 col-sm-3 col-md-1 ellipsis">
                {{ formataDataSemHora(b.datarecebimento) }}
              </div>
              <div class="col-6 col-sm-3 col-md-1 text-right ellipsis">
                {{ formataNumero(b.valoratual) }}
              </div>
              <div class="col-6 col-sm-3 col-md-1 text-right ellipsis">
                {{ formataNumero(b.valorjuromora) }}
              </div>
              <div class="col-6 col-sm-3 col-md-1 text-right ellipsis">
                {{ formataNumero(b.valormulta) }}
              </div>
              <div class="col-6 col-sm-3 col-md-1 text-right ellipsis">
                {{ formataNumero(b.valoroutro) }}
              </div>
              <div
                class="col-6 col-sm-3 col-md-1 text-right text-weight-bold ellipsis text-primary"
              >
                {{ formataNumero(b.valorpago) }}
              </div>
              <div class="col-6 col-sm-6 col-md-2 ellipsis">
                {{ b.numero }}
              </div>
              <div class="col-12 col-sm-12 col-md-3 text-weight-bold text-primary ellipsis">
                {{ b.fantasia }}
                <div v-if="b.saldo > 0" class="text-red text-caption">
                  * Saldo restante {{ formataNumero(b.saldo) }}
                </div>
              </div>
              <div class="col-6 col-sm-6 col-md-1 text-grey-7 ellipsis">
                {{ ESTADO_COBRANCA[b.estadotitulocobranca] || '' }}
              </div>
            </div>
          </q-item-section>
        </q-item>

        <q-item v-if="!store.liqLista.length && !store.carregandoLiq">
          <q-item-section class="text-center text-grey-6">
            Nenhum boleto liquidado encontrado
          </q-item-section>
        </q-item>
      </q-list>

      <q-inner-loading :showing="store.carregandoLiq" color="primary" />
    </div>
  </q-page>
</template>
