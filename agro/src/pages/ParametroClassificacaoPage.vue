<script setup>
import { onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useClassificacaoStore } from 'src/stores/classificacao'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'

// Catálogo global de parâmetros de classificação. O que é intrínseco do
// parâmetro (metodo/reduzbase) vive aqui; os números (tolerância/fator/deságio)
// e a ordem ficam por tabela (Tabelas de Classificação da cultura).
const store = useClassificacaoStore()
const { parametros, formParametro, dialogParametro, salvandoParametro } = storeToRefs(store)

const metodos = [
  { label: 'Normalizado (tolerância)', value: 'NORMALIZADO' },
  { label: 'Fator por ponto', value: 'FATOR' },
]

const colunas = [
  {
    name: 'parametroclassificacao',
    label: 'Parâmetro',
    field: 'parametroclassificacao',
    align: 'left',
  },
  { name: 'metodo', label: 'Método', field: 'metodo', align: 'left' },
  { name: 'reduzbase', label: 'Reduz base', field: 'reduzbase', align: 'center' },
  { name: 'acoes', label: '', field: 'acoes', align: 'right' },
]

function metodoLabel(v) {
  return metodos.find((m) => m.value === v)?.label ?? v
}

onMounted(() => store.carregarParametros())
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <q-card bordered flat class="q-mb-md">
        <q-card-section class="row items-center no-wrap">
          <q-avatar color="deep-orange-1" text-color="deep-orange-8" icon="rule" />
          <div class="col q-ml-md">
            <div class="text-h6">Parâmetros de Classificação</div>
            <div class="text-caption text-grey-7">Catálogo usado por todas as tabelas</div>
          </div>
          <q-btn flat round size="sm" color="primary" icon="add" @click="store.novoParametro()">
            <q-tooltip>Novo parâmetro</q-tooltip>
          </q-btn>
        </q-card-section>
      </q-card>

      <q-banner rounded class="bg-blue-1 text-blue-9 q-mb-md">
        <template #avatar><q-icon name="info" color="blue-7" /></template>
        <b>Método</b> define o cálculo do desconto: <b>Fator por ponto</b> (umidade/secagem) ou
        <b>Normalizado</b> (impureza, defeitos). <b>Reduz base</b> = o desconto deste diminui o peso
        antes dos próximos (impureza e umidade reduzem; defeitos não).
      </q-banner>

      <q-card bordered flat>
        <q-table
          :rows="parametros"
          :columns="colunas"
          row-key="codparametroclassificacao"
          flat
          hide-pagination
          :rows-per-page-options="[0]"
          no-data-label="Nenhum parâmetro cadastrado."
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
        <q-card flat style="width: 440px; max-width: 95vw">
          <q-form @submit.prevent="store.salvarParametro()">
            <q-card-section class="bg-primary text-white">
              <div class="text-h6">
                {{
                  formParametro.codparametroclassificacao ? 'Editar Parâmetro' : 'Novo Parâmetro'
                }}
              </div>
            </q-card-section>
            <q-card-section class="q-pt-md">
              <div class="row q-col-gutter-md">
                <div class="col-12">
                  <q-input
                    v-model="formParametro.parametroclassificacao"
                    label="Parâmetro"
                    autofocus
                    lazy-rules
                    :rules="[(v) => !!v || 'Informe o nome']"
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
