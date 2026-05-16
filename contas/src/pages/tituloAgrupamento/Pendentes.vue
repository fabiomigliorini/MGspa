<script setup>
import { onMounted, computed } from 'vue'
import { date } from 'quasar'
import { formataNumero } from '@components/formatters'
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
  abrirPdf('v1/titulo-agrupamento/relatorio-pendentes', params, {
    title: 'Fechamentos Pendentes',
  })
}

const total = computed(() => store.items.reduce((acc, r) => acc + (Number(r.saldo) || 0), 0))

onMounted(() => {
  if (store.items.length === 0) store.fetchItems()
})
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
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
          <q-btn flat dense icon="print" color="grey-8" @click="abrirRelatorio" label="Relatório" />
        </q-item-section>
      </q-item>

      <q-card flat bordered>
        <q-inner-loading :showing="store.loading" color="primary" />
        <q-list separator>
          <template v-for="r in store.items" :key="r.codpessoa">
            <q-item
              :to="{
                name: 'agrupamento-novo',
                query: { codpessoa: r.codpessoa },
              }"
            >
              <q-item-section avatar class="gt-xs">
                <q-avatar icon=" receipt_long" color="indigo-7" text-color="white" size="40px" />
              </q-item-section>
              <q-item-section>
                <q-item-label>
                  <a
                    :href="`${PESSOAS_URL}/pessoa/${r.codpessoa}`"
                    target="_blank"
                    class="text-primary text-weight-bold"
                    style="text-decoration: none"
                  >
                    <template v-if="r.grupoeconomico">
                      {{ r.grupoeconomico }}

                      -
                    </template>
                    {{ r.fantasia }}
                  </a>
                </q-item-label>
                <q-item-label v-if="r.grupocliente">
                  <span class="text-grey-7">
                    {{ r.grupocliente }}
                  </span>
                </q-item-label>
                <q-item-label v-if="r.formapagamento">
                  <span class="text-grey-7">
                    {{ r.formapagamento }}
                  </span>
                </q-item-label>
              </q-item-section>

              <q-item-section side>
                <q-item-label
                  class="text-right text-weight-bold"
                  :class="classeVencimento(r.vencimento)"
                >
                  {{ formataNumero(r.saldo) }}
                </q-item-label>
                <q-item-label class="text-right" caption>
                  {{ formatData(r.vencimento) }}
                </q-item-label>
              </q-item-section>
            </q-item>
          </template>
        </q-list>
        <q-markup-table flat>
          <thead>
            <tr>
              <th class="text-left">Cliente</th>
              <th class="text-right">Vencimento</th>
              <th class="text-left"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in store.items" :key="r.codpessoa">
              <td></td>

              <td>
                <q-btn flat dense round icon="open_in_new" color="grey-7">
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
