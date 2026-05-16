<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { formataNumero } from '@components/formatters'
import { useBoletoStore } from 'src/stores/boletoStore'

const route = useRoute()
const router = useRouter()
const store = useBoletoStore()

const anoAtual = computed(() => route.params.ano)
const mesAtual = computed(() => route.params.mes)
const diaAtual = computed(() => route.params.dia)

const anoOpcoes = computed(() =>
  store.liqAnos.map((a) => ({
    value: a.ano,
    label: `${a.ano} · ${formataNumero(a.total)} (${a.quantidade})`,
    titulo: a.ano,
    total: a.total,
    quantidade: a.quantidade,
  })),
)

const mesOpcoes = computed(() =>
  store.liqMeses.map((m) => ({
    value: m.mes,
    label: `${m.label} · ${formataNumero(m.total)} (${m.quantidade})`,
    titulo: m.label,
    total: m.total,
    quantidade: m.quantidade,
  })),
)

const diaOpcoes = computed(() =>
  store.liqDias.map((d) => ({
    value: d.dia.slice(-2),
    label: `${d.dia.slice(-2)} · ${formataNumero(d.total)} (${d.quantidade})`,
    titulo: d.dia.slice(-2),
    total: d.total,
    quantidade: d.quantidade,
  })),
)

function onAnoChange(ano) {
  router.push({ name: 'boleto-liquidados', params: { ano } })
}
function onMesChange(mes) {
  router.push({ name: 'boleto-liquidados', params: { ano: anoAtual.value, mes } })
}
function onDiaChange(dia) {
  router.push({
    name: 'boleto-liquidados',
    params: { ano: anoAtual.value, mes: mesAtual.value, dia },
  })
}
</script>

<template>
  <div class="bg-white q-pa-md">
    <q-select
      :model-value="anoAtual"
      @update:model-value="onAnoChange"
      :options="anoOpcoes"
      emit-value
      map-options
      label="Ano"
      outlined
      :bottom-slots="false"
      class="q-mb-md"
    >
      <template v-slot:option="{ itemProps, opt }">
        <q-item v-bind="itemProps">
          <q-item-section>
            <q-item-label class="text-weight-bold">{{ opt.titulo }}</q-item-label>
            <q-item-label caption>{{ formataNumero(opt.total) }}</q-item-label>
          </q-item-section>
          <q-item-section side>
            <q-item-label caption>{{ opt.quantidade }}</q-item-label>
          </q-item-section>
        </q-item>
      </template>
    </q-select>

    <q-select
      :model-value="mesAtual"
      @update:model-value="onMesChange"
      :options="mesOpcoes"
      emit-value
      map-options
      label="Mês"
      outlined
      :bottom-slots="false"
      :disable="!mesOpcoes.length"
      class="q-mb-md"
    >
      <template v-slot:option="{ itemProps, opt }">
        <q-item v-bind="itemProps">
          <q-item-section>
            <q-item-label class="text-weight-bold">{{ opt.titulo }}</q-item-label>
            <q-item-label caption>{{ formataNumero(opt.total) }}</q-item-label>
          </q-item-section>
          <q-item-section side>
            <q-item-label caption>{{ opt.quantidade }}</q-item-label>
          </q-item-section>
        </q-item>
      </template>
    </q-select>

    <q-select
      :model-value="diaAtual"
      @update:model-value="onDiaChange"
      :options="diaOpcoes"
      emit-value
      map-options
      label="Dia"
      outlined
      :bottom-slots="false"
      :disable="!diaOpcoes.length"
    >
      <template v-slot:option="{ itemProps, opt }">
        <q-item v-bind="itemProps">
          <q-item-section>
            <q-item-label class="text-weight-bold">{{ opt.titulo }}</q-item-label>
            <q-item-label caption>{{ formataNumero(opt.total) }}</q-item-label>
          </q-item-section>
          <q-item-section side>
            <q-item-label caption>{{ opt.quantidade }}</q-item-label>
          </q-item-section>
        </q-item>
      </template>
    </q-select>
  </div>
</template>
