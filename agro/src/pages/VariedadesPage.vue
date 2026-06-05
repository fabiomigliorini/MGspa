<script setup>
import { ref, computed, onMounted } from 'vue'
import { api } from 'src/services/api'
import { useCadastro } from 'src/composables/useCadastro'

const cad = useCadastro('variedade', 'codvariedade', 'Variedade')
const culturas = ref([])

async function carregarCulturas() {
  const { data } = await api.get('v1/cultura')
  culturas.value = data.data ?? data
}

const grupos = computed(() =>
  culturas.value
    .filter((c) => !c.inativo)
    .map((c) => ({
      ...c,
      variedades: cad.items.filter((v) => v.codcultura === c.codcultura),
    })),
)

function novaNaCultura(codcultura) {
  cad.abrirNovo({ codcultura })
}

onMounted(async () => {
  await carregarCulturas()
  await cad.carregar()
})
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <q-card bordered flat class="q-mb-md">
        <q-card-section class="row items-center no-wrap">
          <q-btn flat round size="sm" color="grey-7" icon="arrow_back" :to="{ name: 'home' }" />
          <q-avatar color="teal-1" text-color="teal-8" icon="spa" class="q-ml-sm" />
          <div class="col q-ml-md">
            <div class="text-h6">Variedades</div>
            <div class="text-caption text-grey-7">Sementes plantadas, por cultura</div>
          </div>
          <q-btn flat round size="sm" color="primary" icon="add" @click="cad.abrirNovo()">
            <q-tooltip>Nova variedade</q-tooltip>
          </q-btn>
        </q-card-section>
      </q-card>

      <div v-for="g in grupos" :key="g.codcultura" class="q-mb-md">
        <q-card flat bordered>
          <q-item>
            <q-item-section avatar>
              <q-avatar color="light-green-7" text-color="white" icon="grain" />
            </q-item-section>
            <q-item-section>
              <q-item-label class="text-subtitle1">{{ g.cultura }}</q-item-label>
              <q-item-label caption>{{ g.variedades.length }} variedade(s)</q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-btn flat round size="sm" color="primary" icon="add" @click="novaNaCultura(g.codcultura)">
                <q-tooltip>Adicionar variedade de {{ g.cultura }}</q-tooltip>
              </q-btn>
            </q-item-section>
          </q-item>
          <q-separator inset />
          <q-card-section v-if="g.variedades.length" class="row q-gutter-sm">
            <q-chip
              v-for="v in g.variedades"
              :key="v.codvariedade"
              :color="v.inativo ? 'grey-3' : 'teal-1'"
              :text-color="v.inativo ? 'grey-7' : 'teal-9'"
              icon="spa"
            >
              {{ v.variedade }}
              <q-btn flat round size="xs" icon="more_vert" class="q-ml-xs">
                <q-menu>
                  <q-list style="min-width: 140px">
                    <q-item clickable v-close-popup @click="cad.editar(v)">
                      <q-item-section avatar><q-icon name="edit" /></q-item-section>
                      <q-item-section>Editar</q-item-section>
                    </q-item>
                    <q-item clickable v-close-popup @click="cad.alternarInativo(v)">
                      <q-item-section avatar>
                        <q-icon :name="v.inativo ? 'play_arrow' : 'pause'" />
                      </q-item-section>
                      <q-item-section>{{ v.inativo ? 'Ativar' : 'Inativar' }}</q-item-section>
                    </q-item>
                    <q-item clickable v-close-popup @click="cad.excluir(v)">
                      <q-item-section avatar><q-icon name="delete" /></q-item-section>
                      <q-item-section>Excluir</q-item-section>
                    </q-item>
                  </q-list>
                </q-menu>
              </q-btn>
            </q-chip>
          </q-card-section>
          <q-card-section v-else class="text-grey-6 text-caption">
            Sem variedades. Use <q-icon name="add" /> para adicionar.
          </q-card-section>
        </q-card>
      </div>

      <q-banner v-if="!grupos.length" rounded class="bg-grey-2 text-grey-7">
        Cadastre uma cultura primeiro.
      </q-banner>

      <q-dialog v-model="cad.dialog">
        <q-card bordered flat style="width: 400px; max-width: 90vw">
          <q-form @submit="cad.salvar()">
            <q-card-section>
              <div class="text-h6">{{ cad.isNovo ? 'Nova Variedade' : 'Editar Variedade' }}</div>
            </q-card-section>
            <q-card-section class="q-gutter-md">
              <q-select
                v-model="cad.form.codcultura"
                :options="culturas"
                option-value="codcultura"
                option-label="cultura"
                emit-value
                map-options
                outlined
                label="Cultura"
              />
              <q-input v-model="cad.form.variedade" label="Variedade" outlined autofocus />
            </q-card-section>
            <q-card-actions align="right">
              <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
              <q-btn type="submit" unelevated label="Salvar" color="primary" :loading="cad.salvando" />
            </q-card-actions>
          </q-form>
        </q-card>
      </q-dialog>
    </div>
  </q-page>
</template>
