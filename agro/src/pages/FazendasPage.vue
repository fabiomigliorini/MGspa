<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { api } from 'src/services/api'
import { useCadastro } from 'src/composables/useCadastro'
import { notifyError } from 'src/utils/notify'
import MgMapaTalhoes from 'components/MgMapaTalhoes.vue'
import MgEmptyState from '@components/MgEmptyState.vue'

const router = useRouter()
const cad = useCadastro('fazenda', 'codfazenda', 'Fazenda')

// Talhões de todas as fazendas (cada card mostra os seus, com polígono).
const talhoes = ref([])
function talhoesDaFazenda(codfazenda) {
  return talhoes.value.filter((t) => t.codfazenda === codfazenda && t.geometria)
}

function fmt(v, dec = 2) {
  if (v === null || v === undefined || v === '') return '—'
  return Number(v).toLocaleString('pt-BR', {
    minimumFractionDigits: dec,
    maximumFractionDigits: dec,
  })
}

// Clique num polígono → abre a fazenda dona do talhão.
function irParaFazenda(codtalhao) {
  const t = talhoes.value.find((x) => x.codtalhao === codtalhao)
  if (t) router.push({ name: 'fazenda-detalhe', params: { codfazenda: t.codfazenda } })
}

onMounted(async () => {
  try {
    await cad.carregar()
    const { data } = await api.get('v1/talhao')
    talhoes.value = data.data ?? data
  } catch (e) {
    notifyError(e)
  }
})
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <q-card bordered flat class="q-mb-md">
        <q-card-section class="row items-center no-wrap">
          <q-btn flat round size="sm" color="grey-7" icon="arrow_back" :to="{ name: 'home' }" />
          <q-avatar color="green-1" text-color="green-8" icon="agriculture" class="q-ml-sm" />
          <div class="col q-ml-md">
            <div class="text-h6">Fazendas</div>
            <div class="text-caption text-grey-7">
              Talhões, mapa e produtividade de cada fazenda
            </div>
          </div>
          <q-btn flat round size="sm" color="primary" icon="add" @click="cad.abrirNovo()">
            <q-tooltip>Nova fazenda</q-tooltip>
          </q-btn>
        </q-card-section>
      </q-card>

      <div v-if="cad.items.length" class="row q-col-gutter-md">
        <div v-for="f in cad.items" :key="f.codfazenda" class="col-12 col-sm-6 col-md-4">
          <q-card flat bordered class="overflow-hidden" :class="{ 'bg-grey-2': f.inativo }">
            <q-item
              clickable
              v-ripple
              :to="{ name: 'fazenda-detalhe', params: { codfazenda: f.codfazenda } }"
            >
              <q-item-section avatar>
                <q-avatar color="green-7" text-color="white" icon="agriculture" />
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-subtitle1">{{ f.fazenda }}</q-item-label>
                <q-item-label caption>{{ fmt(f.areatotal) }} ha</q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-icon name="chevron_right" color="grey-6" />
              </q-item-section>
            </q-item>
            <q-badge v-if="f.inativo" color="grey-6" label="Inativo" class="q-ma-sm" />
            <!-- Mini-mapa dos talhões desta fazenda -->
            <MgMapaTalhoes
              v-if="talhoesDaFazenda(f.codfazenda).length"
              :talhoes="talhoesDaFazenda(f.codfazenda)"
              height="180px"
              @select="irParaFazenda"
            />
            <div v-else class="flex flex-center bg-grey-3 text-grey-6 column" style="height: 180px">
              <q-icon name="map" size="md" />
              <div class="text-caption q-mt-xs">Sem mapa</div>
            </div>
          </q-card>
        </div>
      </div>

      <MgEmptyState v-else icon="agriculture">
        Nenhuma fazenda cadastrada. Use o botão <q-icon name="add" /> para adicionar.
      </MgEmptyState>

      <q-dialog v-model="cad.dialog">
        <q-card flat style="width: 440px; max-width: 95vw">
          <q-form @submit="cad.salvar()">
            <q-card-section class="bg-primary text-white">
              <div class="text-h6">{{ cad.isNovo ? 'Nova Fazenda' : 'Editar Fazenda' }}</div>
            </q-card-section>
            <q-card-section class="q-pt-md">
              <div class="row q-col-gutter-md">
                <div class="col-12">
                  <q-input v-model="cad.form.fazenda" label="Nome da fazenda" outlined autofocus />
                </div>
                <div class="col-12 text-caption text-grey-6">
                  A área total é calculada automaticamente a partir dos talhões.
                </div>
              </div>
            </q-card-section>
            <q-card-actions align="right">
              <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
              <q-btn type="submit" flat label="Salvar" color="primary" :loading="cad.salvando" />
            </q-card-actions>
          </q-form>
        </q-card>
      </q-dialog>
    </div>
  </q-page>
</template>
