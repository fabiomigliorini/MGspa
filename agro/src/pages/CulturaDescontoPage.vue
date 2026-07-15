<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useClassificacaoStore } from 'src/stores/classificacao'
import { useCulturaStore } from 'src/stores/cultura'
import MgInputValor from '@components/MgInputValor.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'

const route = useRoute()
const codcultura = Number(route.params.codcultura)

// Tabelas de classificação da cultura (padrões nomeados). Cada tabela tem itens
// (um por parâmetro do catálogo) com ordem + tolerância + fator/deságio. Uma é a
// padrão da cultura (usada quando a carga não vem de contrato).
const store = useClassificacaoStore()
const culturaStore = useCulturaStore()
const { tabelas, codPadrao, formTabela, dialogTabela, salvandoTabela } = storeToRefs(store)
const cultura = ref(null)

const colunas = [
  { name: 'tabelaclassificacao', label: 'Tabela', field: 'tabelaclassificacao', align: 'left' },
  { name: 'itens', label: 'Parâmetros', field: 'itens', align: 'left' },
  { name: 'acoes', label: '', field: 'acoes', align: 'right' },
]

function resumoItens(t) {
  return (t.TabelaClassificacaoItemS || [])
    .map((i) => i.ParametroClassificacao?.parametroclassificacao)
    .filter(Boolean)
    .join(', ')
}

onMounted(async () => {
  cultura.value = await culturaStore.buscar(codcultura)
  await store.carregarParametros()
  await store.carregarTabelas(codcultura)
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
          <q-avatar
            color="deep-orange-1"
            text-color="deep-orange-8"
            icon="percent"
            class="q-ml-sm"
          />
          <div class="col q-ml-md">
            <div class="text-h6">Tabelas de Classificação</div>
            <div class="text-caption text-grey-7">{{ cultura?.cultura }}</div>
          </div>
          <q-btn
            flat
            round
            size="sm"
            color="primary"
            icon="add"
            @click="store.novaTabela(codcultura)"
          >
            <q-tooltip>Nova tabela</q-tooltip>
          </q-btn>
        </q-card-section>
      </q-card>

      <q-banner rounded class="bg-blue-1 text-blue-9 q-mb-md">
        <template #avatar><q-icon name="info" color="blue-7" /></template>
        Cada tabela é um padrão de desconto (ex.: comprador/cooperativa). A marcada como
        <b>padrão</b> é usada no recebimento sem contrato; o contrato pode apontar a sua.
      </q-banner>

      <q-card bordered flat>
        <q-table
          :rows="tabelas"
          :columns="colunas"
          row-key="codtabelaclassificacao"
          flat
          hide-pagination
          :rows-per-page-options="[0]"
          no-data-label="Nenhuma tabela para esta cultura."
        >
          <template #body-cell-tabelaclassificacao="props">
            <q-td :props="props">
              <span class="text-weight-medium">{{ props.row.tabelaclassificacao }}</span>
              <q-badge
                v-if="props.row.codtabelaclassificacao === codPadrao"
                color="green-6"
                class="q-ml-sm"
                label="padrão"
              />
            </q-td>
          </template>
          <template #body-cell-itens="props">
            <q-td :props="props" class="text-grey-7">{{ resumoItens(props.row) }}</q-td>
          </template>
          <template #body-cell-acoes="props">
            <q-td :props="props">
              <MgInfoCriacao :registro="props.row" />
              <q-btn
                v-if="props.row.codtabelaclassificacao !== codPadrao"
                flat
                round
                size="sm"
                color="grey-7"
                icon="star_border"
                @click="store.marcarPadrao(props.row)"
              >
                <q-tooltip>Definir como padrão</q-tooltip>
              </q-btn>
              <q-btn
                flat
                round
                size="sm"
                color="grey-7"
                icon="edit"
                @click="store.editarTabela(props.row)"
              />
              <q-btn
                flat
                round
                size="sm"
                color="grey-7"
                icon="delete"
                @click="store.excluirTabela(props.row)"
              />
            </q-td>
          </template>
        </q-table>
      </q-card>

      <q-dialog v-model="dialogTabela">
        <q-card flat style="width: 640px; max-width: 95vw">
          <q-form @submit.prevent="store.salvarTabela()">
            <q-card-section class="bg-primary text-white">
              <div class="text-h6">
                {{ formTabela.codtabelaclassificacao ? 'Editar Tabela' : 'Nova Tabela' }}
              </div>
              <div class="text-caption">{{ cultura?.cultura }}</div>
            </q-card-section>
            <q-card-section class="q-pt-md">
              <q-input
                v-model="formTabela.tabelaclassificacao"
                label="Nome da tabela"
                autofocus
                class="q-mb-md"
                lazy-rules
                :rules="[(v) => !!v || 'Informe o nome']"
              />

              <div class="text-caption text-grey-7 q-mb-sm">
                Marque os parâmetros que esta tabela aplica e ajuste os valores:
              </div>
              <div
                v-for="item in formTabela.itens"
                :key="item.codparametroclassificacao"
                class="row items-center q-col-gutter-sm q-mb-sm"
              >
                <div class="col-12 col-sm-3">
                  <q-toggle
                    v-model="item.aplicar"
                    :label="item.parametroclassificacao"
                    color="primary"
                  />
                </div>
                <div class="col-4 col-sm-2">
                  <MgInputValor
                    v-model="item.ordem"
                    :decimals="0"
                    label="Ordem"
                    :disable="!item.aplicar"
                  />
                </div>
                <div class="col-4 col-sm-3">
                  <MgInputValor
                    v-model="item.tolerancia"
                    :decimals="3"
                    suffix="%"
                    label="Tolerância"
                    :disable="!item.aplicar"
                  />
                </div>
                <div class="col-4 col-sm-4">
                  <MgInputValor
                    v-if="item.metodo === 'FATOR'"
                    v-model="item.fator"
                    :decimals="3"
                    label="Fator /ponto"
                    :disable="!item.aplicar"
                  />
                  <MgInputValor
                    v-else
                    v-model="item.desagio"
                    :decimals="3"
                    suffix="%"
                    label="Deságio"
                    :disable="!item.aplicar"
                  />
                </div>
              </div>
            </q-card-section>
            <q-card-actions align="right">
              <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
              <q-btn type="submit" flat label="Salvar" color="primary" :loading="salvandoTabela" />
            </q-card-actions>
          </q-form>
        </q-card>
      </q-dialog>
    </div>
  </q-page>
</template>
