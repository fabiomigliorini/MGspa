<script setup>
// Caixa do PDV (TASK-188, M9.x; doc-4, "Caixa do PDV"): a mesma tela do período do portador do
// contas, só com o período aberto da gaveta deste PDV. O caixa abre, conta (inicial e final, com os
// itens), faz sangria e reforço, lança o borderô da maquineta de parceiro e imprime o borderô do
// caixa; quem fecha é o gerente, no contas. Sem período aberto, só o Abrir.
import { computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import MgEmptyState from '@components/MgEmptyState.vue'
import Periodo from '@components/portador/Periodo.vue'
import { formataNumero } from '@components/formatters'
import { periodoStore } from '@components/stores/periodoStore'
import { caixaStore } from 'stores/caixa'
import { negocioStore } from 'stores/negocio'
import { sincronizacaoStore } from 'stores/sincronizacao'

const $q = useQuasar()
const sCaixa = caixaStore()
const sNegocio = negocioStore()
const sSinc = sincronizacaoStore()
const store = periodoStore()
const { portador, periodo, carregando, salvando } = storeToRefs(store)

const aberto = computed(
  () =>
    portador.value?.codportador === sCaixa.gaveta?.codportador &&
    periodo.value?.situacao === 'aberto',
)

// a gaveta do PDV e o último período dela (o aberto, se houver)
async function carregar() {
  await sCaixa.status()
  if (!sCaixa.gaveta) return
  await store.carregar(sCaixa.gaveta.codportador, null, {
    codpdv: sSinc.pdv.codpdv,
    impressora: sNegocio.padrao.impressora,
  })
}

// só abre; a contagem inicial é o botão ao lado do saldo inicial. A tela vai para o período novo
function abrir() {
  $q.dialog({
    title: 'Abrir caixa',
    message: `Abrir o caixa em ${sCaixa.gaveta.portador}?`,
    cancel: { label: 'Não', color: 'grey-8', flat: true },
    ok: { label: 'Sim', color: 'primary', flat: true },
  }).onOk(async () => {
    const cod = await store.abrir()
    if (cod) await store.carregar(sCaixa.gaveta.codportador, cod, store.contexto)
  })
}

onMounted(carregar)
</script>

<template>
  <q-page>
    <div class="q-pa-md" style="max-width: 1086px; margin: auto">
      <MgEmptyState v-if="!sCaixa.carregando && !sCaixa.gaveta" icon="point_of_sale">
        Este PDV não tem gaveta. Peça ao administrador para vincular a gaveta em Configurações →
        PDV.
      </MgEmptyState>

      <template v-else-if="sCaixa.gaveta">
        <!-- a gaveta e o saldo, como no contas; no celular o saldo desce para a linha de baixo -->
        <div class="row items-center q-col-gutter-x-sm q-mb-sm">
          <div class="col-12 col-sm text-h5 text-grey-9 ellipsis">
            {{ sCaixa.gaveta.portador }}
            <q-badge v-if="aberto" color="green-7" class="q-ml-sm text-body2" label="Aberto" />
          </div>
          <div v-if="aberto" class="col-12 col-sm-auto text-h6 text-grey-9">
            R$ {{ formataNumero(periodo.saldofinal) }}
          </div>
        </div>

        <!-- o período aberto, como no contas -->
        <Periodo v-if="aberto" />

        <!-- sem período aberto: só abrir -->
        <MgEmptyState v-else-if="!carregando" icon="point_of_sale">
          <div class="q-mb-sm">Caixa fechado.</div>
          <q-btn
            flat
            no-caps
            color="primary"
            icon="lock_open"
            label="Abrir caixa"
            :loading="salvando"
            @click="abrir"
          />
        </MgEmptyState>
      </template>
    </div>

    <q-inner-loading :showing="carregando || sCaixa.carregando" color="primary" />
  </q-page>
</template>
