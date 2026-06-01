<script setup>
import { ref, computed, onMounted } from 'vue'
import { date, Notify } from 'quasar'
import { formataMesAno } from '@components/formatters'
import { useDominioStore } from '../stores/dominioStore'
import dominioService from '../services/dominioService'
import { notificarErro } from '../utils/notify'
import DominioAcumuladorDialog from '../components/dialogs/DominioAcumuladorDialog.vue'

const store = useDominioStore()

const tab = ref('export')
// Mês default = primeiro dia do mês anterior (formato YYYY-MM-DD)
const mes = ref(date.formatDate(date.subtractFromDate(Date.now(), { months: 1 }), 'YYYY-MM-01'))

const empresas = computed(() => store.empresas)
const loading = computed(() => store.loading)
const mesLabel = computed(() => formataMesAno(mes.value))

// Dialog acumulador
const acumDialog = ref(false)
const acumEdit = ref(null)

onMounted(() => {
  store.fetchEmpresas().catch((e) => notificarErro(e, 'Falha ao carregar empresas'))
})

// ---- Botões de exportação por filial ----
const exportar = async (fn, { naoLocalizados = false } = {}) => {
  try {
    const r = await fn()
    if (naoLocalizados) {
      Notify.create({
        type: r.registrosNaoLocalizados > 0 ? 'warning' : 'positive',
        icon: r.registrosNaoLocalizados > 0 ? 'warning' : 'done',
        message: `Arquivo ${r.arquivo} criado com ${r.registrosCompactados} registros (${r.registrosNaoLocalizados} não localizados)`,
        multiLine: true,
      })
    } else {
      Notify.create({
        type: 'positive',
        icon: 'done',
        message: `Arquivo ${r.arquivo} criado com ${r.registros} registros`,
        multiLine: true,
      })
    }
  } catch (error) {
    notificarErro(error, 'Falha ao gerar arquivo')
  }
}

const botoesExport = (codfilial) => [
  {
    icon: 'local_shipping',
    tooltip: 'XMLs das NFe de Entrada emitidas por nós (transferências e devoluções)',
    fn: () => exportar(() => dominioService.nfeEntrada(codfilial, mes.value), { naoLocalizados: true }),
  },
  {
    icon: 'receipt',
    tooltip: 'XMLs das NFCe (modelo 65) de Saída',
    fn: () => exportar(() => dominioService.nfeSaida(codfilial, 65, mes.value), { naoLocalizados: true }),
  },
  {
    icon: 'description',
    tooltip: 'XMLs das NFe (modelo 55) de Saída',
    fn: () => exportar(() => dominioService.nfeSaida(codfilial, 55, mes.value), { naoLocalizados: true }),
  },
  {
    icon: 'groups',
    tooltip: 'Cadastro de Fornecedores',
    fn: () => exportar(() => dominioService.pessoa(codfilial, mes.value)),
  },
  {
    icon: 'inventory_2',
    tooltip: 'Cadastro de Produtos',
    fn: () => exportar(() => dominioService.produto(codfilial, mes.value)),
  },
  {
    icon: 'article',
    tooltip: 'Notas Fiscais de Entrada',
    fn: () => exportar(() => dominioService.entrada(codfilial, mes.value)),
  },
  {
    icon: 'warehouse',
    tooltip: 'Saldos de Estoque (Anual)',
    fn: () => exportar(() => dominioService.estoque(codfilial, mes.value)),
  },
]

// ---- Acumuladores ----
const sortAcumuladores = (acs) =>
  [...acs].sort(
    (a, b) => a.codcfop - b.codcfop || parseFloat(a.icmscst) - parseFloat(b.icmscst),
  )

const novoAcumulador = (codfilial) => {
  acumEdit.value = { coddominioacumulador: null, codfilial }
  acumDialog.value = true
}
const editarAcumulador = (ac) => {
  acumEdit.value = { ...ac }
  acumDialog.value = true
}
const excluirAcumulador = (ac) => {
  Notify.create({
    type: 'warning',
    message: `Excluir acumulador CFOP ${ac.codcfop} / CST ${ac.icmscst}?`,
    actions: [
      { label: 'Cancelar', color: 'white' },
      {
        label: 'Excluir',
        color: 'white',
        handler: async () => {
          try {
            await dominioService.excluirAcumulador(ac.coddominioacumulador)
            store.removeAcumulador(ac)
            Notify.create({ type: 'positive', icon: 'done', message: 'Acumulador excluído!' })
          } catch (error) {
            notificarErro(error, 'Falha ao excluir acumulador')
          }
        },
      },
    ],
  })
}
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <div class="row items-center q-mb-md q-gutter-md">
        <div class="text-h5">Exportação Domínio</div>
        <q-space />
        <q-input
          outlined
          readonly
          :model-value="mesLabel"
          label="Mês de referência"
          style="max-width: 220px"
        >
          <template v-slot:prepend>
            <q-icon name="event" />
          </template>
          <template v-slot:append>
            <q-icon name="edit_calendar" class="cursor-pointer">
              <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                <q-date v-model="mes" mask="YYYY-MM-DD" default-view="Months" minimal>
                  <div class="row items-center justify-end">
                    <q-btn v-close-popup label="OK" color="primary" flat />
                  </div>
                </q-date>
              </q-popup-proxy>
            </q-icon>
          </template>
        </q-input>
      </div>

      <q-tabs
        v-model="tab"
        class="bg-primary text-white shadow-2 rounded-borders q-mb-md"
        active-color="white"
      >
        <q-tab name="export" icon="file_download" label="Exportação" />
        <q-tab name="acum" icon="rule" label="Acumuladores" />
      </q-tabs>

      <div v-if="loading" class="row justify-center q-mt-xl">
        <q-spinner color="primary" size="3em" />
      </div>

      <q-tab-panels v-else v-model="tab" animated class="bg-transparent">
        <!-- ===== Exportação ===== -->
        <q-tab-panel name="export" class="q-pa-none q-gutter-md">
          <q-card v-for="empresa in empresas" :key="empresa.codempresa" flat bordered>
            <q-card-section class="bg-primary text-white text-subtitle1 text-weight-medium">
              #{{ empresa.codempresa }} · {{ empresa.empresa }}
            </q-card-section>
            <q-list separator>
              <q-item v-for="filial in empresa.filiais" :key="filial.codfilial">
                <q-item-section avatar>
                  <q-avatar color="primary" text-color="white">
                    {{ filial.filial.charAt(0) }}
                  </q-avatar>
                </q-item-section>
                <q-item-section>
                  <q-item-label>{{ filial.filial }}</q-item-label>
                  <q-item-label caption>
                    #{{ filial.codfilial }} · Domínio {{ filial.empresadominio }}
                  </q-item-label>
                </q-item-section>
                <q-item-section side>
                  <div class="row no-wrap">
                    <q-btn
                      v-for="(btn, i) in botoesExport(filial.codfilial)"
                      :key="i"
                      flat
                      round
                      size="sm"
                      color="grey-7"
                      :icon="btn.icon"
                      @click="btn.fn"
                    >
                      <q-tooltip max-width="260px">{{ btn.tooltip }}</q-tooltip>
                    </q-btn>
                  </div>
                </q-item-section>
              </q-item>
            </q-list>
          </q-card>
        </q-tab-panel>

        <!-- ===== Acumuladores ===== -->
        <q-tab-panel name="acum" class="q-pa-none q-gutter-md">
          <q-card v-for="empresa in empresas" :key="empresa.codempresa" flat bordered>
            <q-card-section class="bg-primary text-white text-subtitle1 text-weight-medium">
              #{{ empresa.codempresa }} · {{ empresa.empresa }}
            </q-card-section>
            <q-list separator>
              <q-expansion-item
                v-for="filial in empresa.filiais"
                :key="filial.codfilial"
                icon="store"
                :label="filial.filial"
                :caption="`${filial.acumuladores.length} combinação(ões) de CFOP/CST`"
              >
                <div class="q-pa-md">
                  <q-btn
                    flat
                    color="primary"
                    icon="add"
                    label="Nova Combinação de CFOP e CST"
                    class="q-mb-sm"
                    @click="novoAcumulador(filial.codfilial)"
                  />
                  <q-list separator bordered class="rounded-borders">
                    <q-item
                      v-for="ac in sortAcumuladores(filial.acumuladores)"
                      :key="ac.coddominioacumulador"
                    >
                      <q-item-section side>
                        <q-chip square color="primary" text-color="white">{{ ac.codcfop }}</q-chip>
                      </q-item-section>
                      <q-item-section>
                        <q-item-label caption>{{ ac.cfop }}</q-item-label>
                        <q-item-label v-if="ac.historico" caption>{{ ac.historico }}</q-item-label>
                      </q-item-section>
                      <q-item-section side>
                        <q-chip square color="primary" text-color="white">
                          {{ String(ac.icmscst).padStart(2, '0') }}
                        </q-chip>
                      </q-item-section>
                      <q-item-section>
                        <q-item-label>
                          Acum. <b>{{ ac.acumuladoravista }}</b> / <b>{{ ac.acumuladorprazo }}</b>
                        </q-item-label>
                        <q-item-label v-if="ac.movimentacaofisica" caption>
                          Movimentação Física
                        </q-item-label>
                        <q-item-label v-if="ac.movimentacaocontabil" caption>
                          Movimentação Contábil
                        </q-item-label>
                      </q-item-section>
                      <q-item-section side>
                        <div class="row no-wrap">
                          <q-btn
                            flat
                            round
                            size="sm"
                            color="grey-7"
                            icon="edit"
                            @click="editarAcumulador(ac)"
                          />
                          <q-btn
                            flat
                            round
                            size="sm"
                            color="grey-7"
                            icon="delete"
                            @click="excluirAcumulador(ac)"
                          />
                        </div>
                      </q-item-section>
                    </q-item>
                  </q-list>
                </div>
              </q-expansion-item>
            </q-list>
          </q-card>
        </q-tab-panel>
      </q-tab-panels>
    </div>

    <DominioAcumuladorDialog
      v-model="acumDialog"
      :acumulador="acumEdit"
      @saved="store.upsertAcumulador"
    />
  </q-page>
</template>
