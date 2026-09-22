<script setup>
// Item de listagem de carga (espelha o item de negócio do drawer do PDV):
// avatar do sentido, placa, origem/destino, hora, progresso e a métrica da
// etapa. Puro — não lê a store — pra servir também no detalhe do plantio
// (TASK-73) e em qualquer outra lista de cargas.
import { computed } from 'vue'
import { formataHora, tempoRelativo } from '@components/formatters'
import {
  iconeCarga,
  corIconeCarga,
  pontosResumo,
  rotulosDoPapel,
  fmtNumero,
  ETAPA_META,
} from 'src/utils/carga'
import { sacas } from 'src/utils/desconto'
import CargaEtapaProgresso from './CargaEtapaProgresso.vue'

const props = defineProps({
  carga: { type: Object, required: true },
  pesosaca: { type: Number, default: 60 },
  to: { type: [String, Object], default: null },
  // aviso de classificação (cultura sem parâmetro) — quem tem a store passa.
  aviso: { type: String, default: null },
  // Ícone de sincronização. Só faz sentido em lista alimentada pelo Dexie: a
  // carga vinda do servidor não tem `sincronizado`, e o ícone acusaria
  // "Pendente" em toda linha. Default true preserva o pátio.
  sync: { type: Boolean, default: true },
  // Mostra origem → destino em vez de só o lado que identifica a carga
  // (pontosResumo). Numa tela filtrada por unidade, ver um lado só confunde.
  ambosLados: { type: Boolean, default: false },
})

// "Talhão 12 → Silo 1". Sem um dos lados, cai no resumo de um lado só.
const resumo = computed(() => {
  if (!props.ambosLados) return pontosResumo(props.carga)
  const origem = rotulosDoPapel(props.carga, 'ORIGEM')
  const destino = rotulosDoPapel(props.carga, 'DESTINO')
  if (origem && destino) return `${origem} → ${destino}`
  return origem || destino || pontosResumo(props.carga)
})

// Métrica adaptada à etapa: líquido (com sacas) > bruto > PBT > tara > ação pendente.
const metrica = computed(() => {
  const c = props.carga
  if (c.liquido != null) {
    return {
      principal: `${fmtNumero(c.liquido)} kg`,
      secundaria: `${fmtNumero(sacas(c.liquido, props.pesosaca), 1)} sc`,
      classe: 'text-green-9',
    }
  }
  if (c.bruto != null) return { principal: `Bruto ${fmtNumero(c.bruto)} kg`, classe: 'text-grey-9' }
  if (c.pbt != null) return { principal: `PBT ${fmtNumero(c.pbt)} kg`, classe: 'text-grey-9' }
  if (c.tara != null) return { principal: `Tara ${fmtNumero(c.tara)} kg`, classe: 'text-grey-9' }
  return { principal: ETAPA_META[c.etapa]?.acao || '', classe: 'text-grey-6' }
})
</script>

<template>
  <q-item clickable v-ripple :to="to" exact-active-class="bg-blue-1">
    <q-item-section avatar>
      <q-avatar :icon="iconeCarga(carga)" :color="corIconeCarga(carga)" text-color="white" />
    </q-item-section>

    <q-item-section>
      <q-item-label class="row items-baseline no-wrap q-gutter-x-xs">
        <span class="text-weight-bold">{{ carga.placa || 'Sem placa' }}</span>
        <span v-if="carga.placacarreta" class="text-caption text-grey-6">
          {{ carga.placacarreta }}
        </span>
      </q-item-label>
      <q-item-label caption class="ellipsis">{{ resumo }}</q-item-label>
      <q-item-label caption>
        {{ formataHora(carga.data) }} · {{ tempoRelativo(carga.data) }}
      </q-item-label>
      <CargaEtapaProgresso :carga="carga" class="q-mt-xs" />
    </q-item-section>

    <q-item-section side top>
      <div class="row items-center no-wrap q-gutter-x-xs">
        <q-icon v-if="aviso" name="warning" color="orange-7" size="18px">
          <q-tooltip>{{ aviso }}</q-tooltip>
        </q-icon>
        <q-icon v-if="carga.syncerro" name="sync_problem" color="red-6" size="18px">
          <q-tooltip>{{ carga.syncerro }}</q-tooltip>
        </q-icon>
        <q-icon
          v-if="sync"
          :name="carga.sincronizado ? 'cloud_done' : 'cloud_off'"
          :color="carga.sincronizado ? 'green-5' : 'orange-6'"
          size="18px"
        >
          <q-tooltip>{{ carga.sincronizado ? 'Sincronizado' : 'Pendente' }}</q-tooltip>
        </q-icon>
      </div>
      <q-item-label class="text-weight-medium" :class="metrica.classe">
        {{ metrica.principal }}
      </q-item-label>
      <q-item-label v-if="metrica.secundaria" caption>{{ metrica.secundaria }}</q-item-label>
      <q-badge
        v-if="carga.desconto"
        color="orange-1"
        text-color="orange-9"
        :label="`− ${fmtNumero(carga.desconto)} kg`"
      />
    </q-item-section>
  </q-item>
</template>
