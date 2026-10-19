<template>
  <MGLayout>
    <template #tituloPagina>Aniversários</template>
    <template #content>
      <div style="max-width: 1280px; margin: auto; min-height: 100vh">
        <div class="row q-col-gutter-md q-pa-md">
          <div class="col-12">
            <div class="row q-col-gutter-md">
              <!-- Coluna Esquerda - Calendário -->
              <div class="col-md-6 col-12">
                <q-card bordered flat class="full-height">
                  <q-card-section class="text-grey-9 text-overline"> CALENDÁRIO </q-card-section>

                  <q-separator inset />

                  <q-card-section>
                    <div class="row">
                      <div class="col-auto">
                        <q-date
                          flat
                          bordered
                          v-model="date"
                          :options="events"
                          event-color="orange"
                          minimal
                        />
                      </div>
                      <div class="col q-pl-md">
                        <q-option-group v-model="tipo" :options="tipoOptions" color="primary" />
                      </div>
                    </div>
                  </q-card-section>
                </q-card>
              </div>

              <!-- Coluna Direita - Aniversariantes do Dia -->
              <div class="col-md-6 col-12">
                <q-card bordered flat class="full-height">
                  <q-card-section class="text-grey-9 text-overline">
                    ANIVERSARIANTES DO DIA
                  </q-card-section>

                  <q-separator inset />

                  <aniversario-item
                    :items="aniversariosDoDia"
                    empty-message="Nenhum aniversariante neste dia"
                  />
                </q-card>
              </div>

              <!-- Card Últimos Aniversários -->
              <div class="col-md-6 col-12">
                <q-card bordered flat class="full-height">
                  <q-card-section class="text-grey-9 text-overline"> ANTERIORES </q-card-section>

                  <q-separator inset />

                  <aniversario-item
                    :items="ultimosAniversarios"
                    empty-message="Nenhum aniversário nos últimos 7 dias"
                  />
                </q-card>
              </div>

              <!-- Card Próximos Aniversários -->
              <div class="col-md-6 col-12">
                <q-card bordered flat class="full-height">
                  <q-card-section class="text-grey-9 text-overline"> PRÓXIMOS </q-card-section>

                  <q-separator inset />

                  <aniversario-item
                    :items="proximosAniversarios"
                    empty-message="Nenhum aniversário nos próximos 7 dias"
                  />
                </q-card>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </MGLayout>
</template>

<script setup>
// 1. Imports do Vue
import { ref, computed, onMounted, watch } from 'vue'

// 2. Imports de stores
import { pessoaStore } from 'src/stores/pessoa'

// 3. Imports de utilitários
import { hoje, paraDate, somaAnos, somaDias } from '@components/utils/datas'

// 4. Imports de componentes
import MGLayout from 'layouts/MGLayout.vue'
import AniversarioItem from 'components/pessoa/AniversarioItem.vue'

// 5. Instâncias
const sPessoa = pessoaStore()

// 6. Refs e computed
const tipo = ref('todos')
// o q-date usa 'YYYY/MM/DD'; as contas, 'YYYY-MM-DD' (comparar o texto é comparar o dia)
const date = ref(hoje().replaceAll('-', '/'))
const aniversarios = ref([])
const events = ref([])

const tipoOptions = [
  { label: 'Todos', value: 'todos' },
  { label: 'Colaboradores', value: 'colaborador' },
  { label: 'Clientes', value: 'cliente' },
  { label: 'Fornecedores', value: 'fornecedor' },
]

// clicar de novo no dia selecionado limpa o calendário
const diaSelecionado = computed(() => (date.value ? date.value.replaceAll('/', '-') : null))

// Computed para aniversariantes do dia selecionado
const aniversariosDoDia = computed(() => {
  const dia = paraDate(diaSelecionado.value)
  if (!dia) return []
  return aniversarios.value.filter((a) => {
    return a.dia == dia.getDate() && a.mes == dia.getMonth() + 1
  })
})

// Computed para últimos 7 dias (excluindo o dia selecionado)
const ultimosAniversarios = computed(() => {
  const dataSelecionada = diaSelecionado.value
  const dataInicio = somaDias(dataSelecionada, -7)

  return aniversarios.value
    .filter((a) => a.data < dataSelecionada && a.data >= dataInicio)
    .sort((a, b) => b.data.localeCompare(a.data))
})

// Computed para próximos 7 dias (excluindo o dia selecionado)
const proximosAniversarios = computed(() => {
  const dataSelecionada = diaSelecionado.value
  const dataFim = somaDias(dataSelecionada, 7)

  return aniversarios.value
    .filter((a) => a.data > dataSelecionada && a.data <= dataFim)
    .sort((a, b) => a.data.localeCompare(b.data))
})

// 7. Funções
const getAniversarios = async () => {
  const ret = await sPessoa.buscaAniversarios(tipo.value)
  const dados = ret.data

  const dates = []
  const arrAniversarios = []
  const anoAtual = new Date().getFullYear()

  dados.forEach((el) => {
    const dataOriginal = paraDate(el.data)

    // Ajusta a data para o ano atual (29/02 vira 28/02 fora do bissexto)
    const dataAnoAtual = somaAnos(el.data, anoAtual - dataOriginal.getFullYear())
    el.data = dataAnoAtual
    el.dia = dataOriginal.getDate()
    el.mes = dataOriginal.getMonth() + 1
    dates.push(dataAnoAtual.replaceAll('-', '/'))
    arrAniversarios.push(el)
  })

  aniversarios.value = arrAniversarios
  events.value = dates
}

// 8. Watchers
watch(tipo, () => {
  getAniversarios()
})

// 9. Lifecycle
onMounted(() => {
  getAniversarios()
})
</script>
