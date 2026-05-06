<script setup>
import { onMounted, computed } from 'vue'
import { date } from 'quasar'
import { formataNumero } from 'src/utils/formatters.js'
import { useAgrupamentoPendenteStore } from 'src/stores/agrupamentoPendenteStore'
import { abrirPdf } from 'src/utils/abrirPdf'

const store = useAgrupamentoPendenteStore()
const PESSOAS_URL = process.env.PESSOAS_URL

function classeVencimento(v) {
  if (!v) return ''
  const d = new Date(String(v).slice(0, 10))
  const hoje = new Date()
  hoje.setHours(0, 0, 0, 0)
  const diff = Math.floor((d - hoje) / 86400000)
  if (diff < -30) return 'text-red'
  if (diff > 45) return 'text-green'
  return 'text-orange'
}

function formatData(v) {
  return v ? date.formatDate(v, 'DD/MM/YYYY') : ''
}

function abrirRelatorio() {
  const params = {}
  for (const [k, v] of Object.entries(store.filters)) {
    if (v === null || v === undefined || v === '') continue
    params[k] = v
  }
  abrirPdf('v1/titulo-agrupamento/relatorio-pendentes', params)
}

const total = computed(() =>
  store.items.reduce((acc, r) => acc + (Number(r.saldo) || 0), 0),
)

onMounted(() => {
  if (store.items.length === 0) store.fetchItems()
})
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1200px; margin: auto">
      <q-item class="q-pb-md q-px-none">
        <q-item-section avatar>
          <q-btn
            flat
            dense
            round
            icon="arrow_back"
            :to="{ name: 'agrupamento' }"
            aria-label="Voltar"
          />
        </q-item-section>
        <q-item-section>
          <div class="text-h5 text-grey-9">Fechamentos Pendentes</div>
          <div v-if="store.items.length" class="text-caption text-grey-7">
            {{ store.items.length }} cliente(s) — {{ formataNumero(total) }}
          </div>
        </q-item-section>
        <q-item-section side>
          <q-btn
            flat
            dense
            icon="picture_as_pdf"
            color="grey-8"
            @click="abrirRelatorio"
            label="PDF"
          />
        </q-item-section>
      </q-item>

      <q-card flat bordered>
        <q-inner-loading :showing="store.loading" color="primary" />
        <q-markup-table flat dense>
          <thead>
            <tr>
              <th class="text-left">Grupo Cliente</th>
              <th class="text-left">Grupo Econômico</th>
              <th class="text-left">Cliente</th>
              <th class="text-left">Vencimento</th>
              <th class="text-right">Saldo</th>
              <th class="text-left">Forma</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in store.items" :key="r.codpessoa">
              <td>{{ r.grupocliente }}</td>
              <td>{{ r.grupoeconomico }}</td>
              <td>
                <a
                  :href="`${PESSOAS_URL}/pessoa/${r.codpessoa}`"
                  target="_blank"
                  class="text-primary"
                >
                  {{ r.fantasia }}
                </a>
              </td>
              <td :class="classeVencimento(r.vencimento)">{{ formatData(r.vencimento) }}</td>
              <td class="text-right text-weight-bold">{{ formataNumero(r.saldo) }}</td>
              <td>{{ r.formapagamento }}</td>
              <td>
                <q-btn
                  flat
                  dense
                  round
                  size="sm"
                  icon="play_arrow"
                  color="primary"
                  :to="{
                    name: 'agrupamento-novo',
                    query: { codpessoa: r.codpessoa },
                  }"
                >
                  <q-tooltip>Agrupar</q-tooltip>
                </q-btn>
              </td>
            </tr>
          </tbody>
        </q-markup-table>
        <div v-if="!store.items.length && !store.loading" class="text-center text-grey q-pa-xl">
          Nenhum pendente encontrado
        </div>
      </q-card>
    </div>
  </q-page>
</template>
