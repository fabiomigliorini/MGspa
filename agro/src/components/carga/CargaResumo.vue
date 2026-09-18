<script setup>
// Drawer direito do pátio (espelha TotalNegocio + DetalheNegocio do PDV): pesos
// em destaque, progresso das etapas, classificação e a ficha da carga ABERTA
// (estado gravado no Dexie — o formulário no centro mostra a prévia ao vivo).
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import { formataTimestamp, tempoRelativo } from '@components/formatters'
import { useCargaStore } from 'src/stores/carga'
import { sentidoMeta, ETAPA_META, fmtNumero } from 'src/utils/carga'
import { sacas } from 'src/utils/desconto'
import CargaEtapaProgresso from './CargaEtapaProgresso.vue'

const store = useCargaStore()
const { cargaAtiva: carga, safraAtiva, culturaAtiva, pesosaca } = storeToRefs(store)

const meta = computed(() => sentidoMeta(carga.value?.sentido))
const etapa = computed(() => ETAPA_META[carga.value?.etapa] || {})
const veiculo = computed(() => store.veiculoPorId(carga.value?.codveiculo))
const chips = computed(() => store.chipsClassificacao(carga.value))
const origens = computed(() => (carga.value?.pontos || []).filter((p) => p.papel === 'ORIGEM'))
const destinos = computed(() => (carga.value?.pontos || []).filter((p) => p.papel === 'DESTINO'))

const pesos = computed(() => {
  const c = carga.value || {}
  return [
    { label: 'PBT', valor: c.pbt },
    { label: 'Tara', valor: c.tara },
    { label: 'Bruto', valor: c.bruto },
    { label: 'Desconto', valor: c.desconto, classe: 'text-orange-9' },
  ].filter((p) => p.valor != null)
})

// kg do ponto: o gravado (rateado ao salvar / vindo do servidor) ou o % ainda
// sem peso.
function kgPonto(p) {
  if (p.liquido != null) return `${fmtNumero(p.liquido)} kg`
  if (p.percentual != null) return `${fmtNumero(p.percentual, 1)}%`
  return ''
}
</script>

<template>
  <div v-if="!carga" class="q-pa-lg text-center text-grey-6">
    <q-icon name="local_shipping" size="48px" color="grey-4" />
    <div class="q-mt-sm">Abra uma carga para ver o resumo.</div>
  </div>

  <template v-else>
    <!-- PESOS -->
    <q-list dense class="q-mt-md">
      <q-item v-for="p in pesos" :key="p.label">
        <q-item-section>
          <q-item-label caption>{{ p.label }}</q-item-label>
        </q-item-section>
        <q-item-section class="text-right">
          <q-item-label class="text-h6" :class="p.classe || 'text-grey-6'">
            {{ fmtNumero(p.valor) }} <small>kg</small>
          </q-item-label>
        </q-item-section>
      </q-item>
      <q-item>
        <q-item-section>
          <q-item-label caption>Líquido</q-item-label>
        </q-item-section>
        <q-item-section class="text-right">
          <q-item-label class="text-h4 text-weight-bolder text-green-8">
            {{ fmtNumero(carga.liquido) }} <small class="text-grey-6">kg</small>
          </q-item-label>
          <q-item-label v-if="carga.liquido != null" caption>
            {{ fmtNumero(sacas(carga.liquido, pesosaca), 1) }} sacas de {{ pesosaca }} kg
          </q-item-label>
        </q-item-section>
      </q-item>
    </q-list>

    <!-- ETAPAS -->
    <div class="q-px-md q-py-sm">
      <CargaEtapaProgresso :carga="carga" labels />
    </div>

    <!-- CLASSIFICAÇÃO -->
    <div v-if="chips.length" class="row items-center q-gutter-xs q-px-md q-pb-sm">
      <q-chip
        v-for="chip in chips"
        :key="chip.key"
        dense
        square
        size="sm"
        :color="chip.fora ? 'orange-2' : 'blue-grey-1'"
        :text-color="chip.fora ? 'orange-10' : 'blue-grey-8'"
      >
        {{ chip.label }} {{ fmtNumero(chip.leitura, 1) }}%
        <q-tooltip>{{ chip.nome }}{{ chip.fora ? ' — acima da tolerância' : '' }}</q-tooltip>
      </q-chip>
    </div>

    <q-separator spaced />

    <q-list>
      <!-- ROMANEIO / ETAPA -->
      <q-item>
        <q-item-section avatar top>
          <q-avatar :icon="meta.icon" :color="meta.color" text-color="white" />
        </q-item-section>
        <q-item-section>
          <q-item-label lines="1">{{ meta.label }}</q-item-label>
          <q-item-label caption>
            <q-icon :name="etapa.icon" :color="etapa.color" size="16px" class="q-mr-xs" />
            {{ etapa.label }}
          </q-item-label>
          <q-item-label v-if="carga.inativo" caption class="text-negative">
            Cancelada em {{ formataTimestamp(carga.inativo) }}
          </q-item-label>
        </q-item-section>
      </q-item>

      <!-- SAFRA / CULTURA -->
      <q-item>
        <q-item-section avatar top>
          <q-avatar icon="eco" color="light-green-8" text-color="white" />
        </q-item-section>
        <q-item-section>
          <q-item-label lines="1">{{ safraAtiva?.safra || '—' }}</q-item-label>
          <q-item-label caption>{{ culturaAtiva?.cultura || 'Cultura' }}</q-item-label>
        </q-item-section>
      </q-item>

      <!-- CAMINHÃO / MOTORISTA -->
      <q-item>
        <q-item-section avatar top>
          <q-avatar icon="local_shipping" color="secondary" text-color="white" />
        </q-item-section>
        <q-item-section>
          <q-item-label lines="1">
            {{ carga.placa || 'Sem placa' }}
            <span v-if="carga.placacarreta" class="text-grey-6">/ {{ carga.placacarreta }}</span>
          </q-item-label>
          <q-item-label v-if="veiculo?.veiculo" caption>{{ veiculo.veiculo }}</q-item-label>
          <q-item-label caption>{{ carga.motorista || 'Motorista não informado' }}</q-item-label>
        </q-item-section>
      </q-item>

      <!-- CHEGADA -->
      <q-item>
        <q-item-section avatar top>
          <q-avatar icon="schedule" color="secondary" text-color="white" />
        </q-item-section>
        <q-item-section>
          <q-item-label lines="1">{{ formataTimestamp(carga.data) }}</q-item-label>
          <q-item-label caption>Chegada · {{ tempoRelativo(carga.data) }}</q-item-label>
        </q-item-section>
      </q-item>

      <q-separator spaced />

      <!-- ORIGENS -->
      <q-item v-for="(p, i) in origens" :key="'o' + i">
        <q-item-section avatar top>
          <q-avatar icon="login" color="brown-5" text-color="white" />
        </q-item-section>
        <q-item-section>
          <q-item-label lines="2">{{ p.rotulo || 'Origem não informada' }}</q-item-label>
          <q-item-label caption>Origem · {{ kgPonto(p) }}</q-item-label>
        </q-item-section>
      </q-item>

      <!-- DESTINOS -->
      <q-item v-for="(p, i) in destinos" :key="'d' + i">
        <q-item-section avatar top>
          <q-avatar icon="logout" color="teal-7" text-color="white" />
        </q-item-section>
        <q-item-section>
          <q-item-label lines="2">{{ p.rotulo || 'Destino não informado' }}</q-item-label>
          <q-item-label caption>Destino · {{ kgPonto(p) }}</q-item-label>
          <q-item-label v-if="p.numeronf" caption>
            NF {{ p.numeronf }}
            <span v-if="p.valornf"> · R$ {{ fmtNumero(p.valornf, 2) }}</span>
          </q-item-label>
        </q-item-section>
      </q-item>

      <q-separator spaced />

      <!-- CÓDIGO / SINCRONIZAÇÃO -->
      <q-item>
        <q-item-section avatar top>
          <q-btn
            round
            :color="carga.syncerro ? 'negative' : carga.sincronizado ? 'secondary' : 'accent'"
            :icon="carga.syncerro ? 'sync_problem' : 'file_upload'"
            @click="store.reenviar(carga)"
          >
            <q-tooltip>
              {{ carga.syncerro || (carga.sincronizado ? 'Sincronizada' : 'Pendente') }} — reenviar
            </q-tooltip>
          </q-btn>
        </q-item-section>
        <q-item-section>
          <q-item-label v-if="carga.codcarga" lines="1">#{{ carga.codcarga }}</q-item-label>
          <q-item-label v-else lines="1">Não integrada</q-item-label>
          <q-item-label caption class="ellipsis">{{ carga.uuid }}</q-item-label>
          <q-item-label v-if="carga.syncerro" caption class="text-negative">
            {{ carga.syncerro }}
          </q-item-label>
        </q-item-section>
      </q-item>
    </q-list>
  </template>
</template>
