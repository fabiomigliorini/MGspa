<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useClassificacaoStore } from 'src/stores/classificacao'
import { useCulturaStore } from 'src/stores/cultura'
import MgInputValor from '@components/MgInputValor.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'

// Parâmetros de classificação da CULTURA — cadastro único do desconto. Cada
// parâmetro carrega a fórmula inteira; não há tabela intermediária. A `ordem`
// é a cascata: quem tem "reduz base" diminui o peso antes dos seguintes.
const route = useRoute()
const codcultura = Number(route.params.codcultura)

const store = useClassificacaoStore()
const culturaStore = useCulturaStore()
const { parametros, formParametro, dialogParametro, salvandoParametro } = storeToRefs(store)
const cultura = ref(null)

const metodos = [
  { label: 'Normalizado (fórmula da norma)', value: 'NORMALIZADO' },
  { label: 'Fator por ponto (taxa comercial)', value: 'FATOR' },
]

const colunas = [
  { name: 'ordem', label: '#', field: 'ordem', align: 'left' },
  {
    name: 'parametroclassificacao',
    label: 'Parâmetro',
    field: 'parametroclassificacao',
    align: 'left',
  },
  { name: 'metodo', label: 'Método', field: 'metodo', align: 'left' },
  { name: 'tolerancia', label: 'Tolerância', field: 'tolerancia', align: 'right' },
  { name: 'valor', label: 'Fator / Deságio', field: 'valor', align: 'right' },
  { name: 'reduzbase', label: 'Reduz base', field: 'reduzbase', align: 'center' },
  { name: 'acoes', label: '', field: 'acoes', align: 'right' },
]

// A store guarda os parâmetros de todas as culturas quando a listagem é geral;
// aqui recortamos os desta cultura, já na ordem da cascata.
const linhas = computed(() =>
  parametros.value
    .filter((p) => p.codcultura === codcultura)
    .sort((a, b) => (Number(a.ordem) || 0) - (Number(b.ordem) || 0)),
)

const ehFator = computed(() => formParametro.value.metodo === 'FATOR')

function metodoLabel(v) {
  return metodos.find((m) => m.value === v)?.label ?? v
}
function fmt(v) {
  return Number(v ?? 0).toLocaleString('pt-BR', {
    minimumFractionDigits: 1,
    maximumFractionDigits: 1,
  })
}

onMounted(async () => {
  cultura.value = await culturaStore.buscar(codcultura)
  await store.carregarParametros(codcultura)
})
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <q-card bordered flat class="q-mb-md">
        <q-card-section class="row items-center no-wrap">
          <q-btn
            flat
            round
            size="sm"
            color="grey-7"
            icon="arrow_back"
            :to="{ name: 'cultura-detalhe', params: { codcultura } }"
          />
          <q-avatar color="deep-orange-1" text-color="deep-orange-8" icon="rule" class="q-ml-sm" />
          <div class="col q-ml-md">
            <div class="text-h6">Classificação</div>
            <div class="text-caption text-grey-7">{{ cultura?.cultura }}</div>
          </div>
          <q-btn
            flat
            round
            size="sm"
            color="primary"
            icon="add"
            @click="store.novoParametro(codcultura)"
          >
            <q-tooltip>Novo parâmetro</q-tooltip>
          </q-btn>
        </q-card-section>
      </q-card>

      <q-banner rounded class="bg-blue-1 text-blue-9 q-mb-md">
        <template #avatar><q-icon name="info" color="blue-7" /></template>
        O desconto é calculado direto por estes parâmetros, na ordem em que aparecem.
        <b>Normalizado</b> é a fórmula da norma —
        <code>(leitura − tolerância) ÷ (100 − tolerância)</code>, a mesma da IN MAPA e das cartilhas
        de classificação. <b>Fator por ponto</b> só para comprador que cobra taxa de secagem.
        <b>Reduz base</b> = o desconto deste diminui o peso antes dos próximos (impureza e umidade
        reduzem; defeitos não).
      </q-banner>

      <q-card bordered flat>
        <q-table
          :rows="linhas"
          :columns="colunas"
          row-key="codparametroclassificacao"
          flat
          hide-pagination
          :rows-per-page-options="[0]"
          no-data-label="Nenhum parâmetro cadastrado — sem eles o desconto sai zero."
        >
          <template #body-cell-parametroclassificacao="props">
            <q-td :props="props">
              <span :class="props.row.inativo ? 'text-strike text-grey-6' : ''">
                {{ props.row.parametroclassificacao }}
              </span>
            </q-td>
          </template>
          <template #body-cell-metodo="props">
            <q-td :props="props">{{ metodoLabel(props.row.metodo) }}</q-td>
          </template>
          <template #body-cell-tolerancia="props">
            <q-td :props="props">{{ fmt(props.row.tolerancia) }}%</q-td>
          </template>
          <template #body-cell-valor="props">
            <q-td :props="props">
              <span v-if="props.row.metodo === 'FATOR'">{{ fmt(props.row.fator) }}</span>
              <span v-else-if="Number(props.row.desagio)">
                {{ fmt(props.row.desagio) }}% deságio
              </span>
              <span v-else class="text-grey-5">—</span>
            </q-td>
          </template>
          <template #body-cell-reduzbase="props">
            <q-td :props="props">
              <q-icon
                :name="props.row.reduzbase ? 'check_circle' : 'remove'"
                :color="props.row.reduzbase ? 'green-6' : 'grey-5'"
              />
            </q-td>
          </template>
          <template #body-cell-acoes="props">
            <q-td :props="props">
              <MgInfoCriacao :registro="props.row" />
              <q-btn
                flat
                round
                size="sm"
                color="grey-7"
                :icon="props.row.inativo ? 'play_arrow' : 'pause'"
                @click="store.inativarParametro(props.row)"
              >
                <q-tooltip>{{ props.row.inativo ? 'Ativar' : 'Inativar' }}</q-tooltip>
              </q-btn>
              <q-btn
                flat
                round
                size="sm"
                color="grey-7"
                icon="edit"
                @click="store.editarParametro(props.row)"
              />
              <q-btn
                flat
                round
                size="sm"
                color="grey-7"
                icon="delete"
                @click="store.excluirParametro(props.row)"
              />
            </q-td>
          </template>
        </q-table>
      </q-card>

      <q-dialog v-model="dialogParametro">
        <q-card flat style="width: 480px; max-width: 95vw">
          <q-form @submit.prevent="store.salvarParametro()">
            <q-card-section class="bg-primary text-white">
              <div class="text-h6">
                {{ formParametro.codparametroclassificacao ? 'Editar' : 'Novo' }} parâmetro
              </div>
            </q-card-section>
            <q-card-section class="q-pt-md">
              <div class="row q-col-gutter-md">
                <div class="col-8">
                  <q-input
                    v-model="formParametro.parametroclassificacao"
                    label="Parâmetro"
                    autofocus
                    lazy-rules
                    :rules="[(v) => !!v || 'Informe o nome']"
                  />
                </div>
                <div class="col-4">
                  <MgInputValor
                    v-model="formParametro.ordem"
                    :decimals="0"
                    :min="0"
                    :grouping="false"
                    label="Ordem"
                    hint="Posição na cascata"
                  />
                </div>
                <div class="col-12">
                  <q-select
                    v-model="formParametro.metodo"
                    :options="metodos"
                    emit-value
                    map-options
                    label="Método"
                    lazy-rules
                    :rules="[(v) => !!v || 'Selecione o método']"
                  />
                </div>
                <div class="col-6">
                  <MgInputValor
                    v-model="formParametro.tolerancia"
                    :decimals="1"
                    suffix="%"
                    label="Tolerância"
                    hint="Abaixo dela não há desconto"
                    lazy-rules
                    :rules="[(v) => (v != null && v >= 0 && v <= 100) || 'Informe de 0 a 100%']"
                  />
                </div>
                <div class="col-6">
                  <!-- Um campo por método: fator é a taxa por ponto (só FATOR),
                       deságio é o abatimento sobre a quebra (só NORMALIZADO).
                       Mostrar os dois convidaria a preencher o que é ignorado. -->
                  <MgInputValor
                    v-if="ehFator"
                    v-model="formParametro.fator"
                    :decimals="1"
                    label="Fator por ponto"
                    hint="1,5 = 1,5% por ponto acima"
                    lazy-rules
                    :rules="[(v) => (v != null && v >= 0 && v <= 100) || 'Informe de 0 a 100']"
                  />
                  <MgInputValor
                    v-else
                    v-model="formParametro.desagio"
                    :decimals="1"
                    suffix="%"
                    label="Deságio"
                    hint="0 = fórmula pura da norma"
                    lazy-rules
                    :rules="[(v) => (v != null && v >= 0 && v <= 100) || 'Informe de 0 a 100%']"
                  />
                </div>
                <div class="col-12">
                  <q-toggle
                    v-model="formParametro.reduzbase"
                    label="Reduz a base (desconta o peso antes dos próximos)"
                    color="primary"
                  />
                </div>
              </div>
            </q-card-section>
            <q-card-actions align="right">
              <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
              <q-btn
                type="submit"
                flat
                label="Salvar"
                color="primary"
                :loading="salvandoParametro"
              />
            </q-card-actions>
          </q-form>
        </q-card>
      </q-dialog>
    </div>
  </q-page>
</template>
