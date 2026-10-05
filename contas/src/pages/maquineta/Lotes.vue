<script setup>
// Lotes de uma maquineta (M9 doc-3): os borderôs conferidos e o aberto, do mais novo ao mais
// antigo. Cada um abre a conferência do lote (a mesma da tela Fechamentos).
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { api } from 'src/services/api'
import { notifyError } from 'src/utils/notify'
import { formataNumero, formataTimestamp } from '@components/formatters'
import MgEmptyState from '@components/MgEmptyState.vue'

const route = useRoute()
const id = computed(() => Number(route.params.id))

const lotes = ref([])
const pagina = ref(1)
const temMais = ref(true)
const carregando = ref(false)

const diferenca = (l) =>
  Math.round(
    ((l.creditoinformado ?? 0) +
      (l.debitoinformado ?? 0) -
      (l.creditosistema ?? 0) -
      (l.debitosistema ?? 0)) *
      100,
  ) / 100

async function buscar(reset = false) {
  if (reset) {
    pagina.value = 1
    temMais.value = true
    lotes.value = []
  }
  if (!temMais.value || carregando.value) return
  carregando.value = true
  try {
    const { data } = await api.get(`v1/maquineta/${id.value}/lote`, {
      params: { page: pagina.value },
    })
    lotes.value = [...lotes.value, ...data.data]
    temMais.value = pagina.value < (data.meta?.last_page ?? pagina.value)
    pagina.value++
  } catch (e) {
    notifyError(e, 'Erro ao buscar os lotes')
    temMais.value = false
  } finally {
    carregando.value = false
  }
}

const carregarMais = async (index, done) => {
  await buscar(false)
  done(!temMais.value)
}

const maquineta = computed(() => lotes.value[0])

onMounted(() => buscar(true))
watch(id, () => buscar(true))
</script>

<template>
  <q-page>
    <q-infinite-scroll :offset="250" @load="carregarMais">
      <div class="q-pa-md" style="max-width: 1086px; margin: auto">
        <div class="row items-center q-mb-sm">
          <q-btn flat round icon="arrow_back" :to="{ name: 'maquineta' }" aria-label="Voltar" />
          <div v-if="maquineta" class="q-ml-sm">
            <div class="text-h6">{{ maquineta.maquineta }}</div>
            <div class="text-caption text-grey-7">
              {{ maquineta.adquirente }} · {{ maquineta.filial }}
            </div>
          </div>
        </div>

        <q-list v-if="lotes.length" bordered separator class="bg-white rounded-borders">
          <q-item
            v-for="l in lotes"
            :key="l.codmaquinetalote"
            :to="{ name: 'fechamento-lote', params: { id: l.codmaquinetalote } }"
          >
            <q-item-section>
              <q-item-label>
                Lote {{ l.codmaquinetalote }} · {{ formataTimestamp(l.abertura, 2) }}
                <template v-if="l.fechamento">
                  até {{ formataTimestamp(l.fechamento, 2) }}</template
                >
              </q-item-label>
              <q-item-label v-if="!l.aberto" caption>
                Crédito {{ formataNumero(l.creditoinformado) }} · débito
                {{ formataNumero(l.debitoinformado) }} · {{ l.usuariofechamento }}
              </q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-badge v-if="l.aberto" color="amber-8" label="A conferir" />
              <q-badge
                v-else
                :color="Math.abs(diferenca(l)) < 0.005 ? 'green-7' : 'red-6'"
                :label="Math.abs(diferenca(l)) < 0.005 ? 'Bateu' : formataNumero(diferenca(l))"
              />
            </q-item-section>
          </q-item>
        </q-list>
        <MgEmptyState v-else-if="!carregando" icon="receipt_long">
          Nenhum lote: a maquineta ainda não teve cartão desde o início das conferências.
        </MgEmptyState>
      </div>
    </q-infinite-scroll>
  </q-page>
</template>
