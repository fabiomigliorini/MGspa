<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useCulturaStore } from 'src/stores/cultura'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import MgEmptyState from '@components/MgEmptyState.vue'

const route = useRoute()
const codcultura = Number(route.params.codcultura)

// Variedades vivem na store do domínio cultura. Aqui a tela só lista/edita as
// desta cultura; o cabeçalho usa o nome da cultura (cache da store).
const store = useCulturaStore()
const { variedades, dialogVariedade, formVariedade, salvandoVariedade } = storeToRefs(store)
const cultura = ref(null)

onMounted(async () => {
  cultura.value = await store.buscar(codcultura)
  store.carregarVariedades(codcultura)
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
          <q-avatar color="teal-1" text-color="teal-8" icon="spa" class="q-ml-sm" />
          <div class="col q-ml-md">
            <div class="text-h6">Variedades</div>
            <div class="text-caption text-grey-7">{{ cultura?.cultura }}</div>
          </div>
          <q-btn
            flat
            round
            size="sm"
            color="primary"
            icon="add"
            @click="store.novaVariedade(codcultura)"
          >
            <q-tooltip>Nova variedade</q-tooltip>
          </q-btn>
        </q-card-section>
      </q-card>

      <q-card bordered flat>
        <q-list separator>
          <q-item v-for="v in variedades" :key="v.codvariedade" :class="{ 'bg-grey-2': v.inativo }">
            <q-item-section avatar>
              <q-avatar color="teal-1" text-color="teal-8" icon="spa" />
            </q-item-section>
            <q-item-section>
              <q-item-label class="text-weight-medium">{{ v.variedade }}</q-item-label>
              <q-item-label v-if="v.inativo" caption>Inativa</q-item-label>
            </q-item-section>
            <q-item-section side>
              <div class="row items-center no-wrap">
                <MgInfoCriacao :registro="v" />
                <q-btn
                  flat
                  dense
                  round
                  size="sm"
                  color="grey-7"
                  icon="edit"
                  @click="store.editarVariedade(v)"
                >
                  <q-tooltip>Editar</q-tooltip>
                </q-btn>
                <q-btn
                  flat
                  dense
                  round
                  size="sm"
                  color="grey-7"
                  :icon="v.inativo ? 'play_arrow' : 'pause'"
                  @click="store.inativarVariedade(v)"
                >
                  <q-tooltip>{{ v.inativo ? 'Ativar' : 'Inativar' }}</q-tooltip>
                </q-btn>
                <q-btn
                  flat
                  dense
                  round
                  size="sm"
                  color="grey-7"
                  icon="delete"
                  @click="store.excluirVariedade(v)"
                >
                  <q-tooltip>Excluir</q-tooltip>
                </q-btn>
              </div>
            </q-item-section>
          </q-item>

          <MgEmptyState v-if="!variedades.length" plain icon="spa">
            Nenhuma variedade. Use <q-icon name="add" /> para adicionar.
          </MgEmptyState>
        </q-list>
      </q-card>

      <q-dialog v-model="dialogVariedade">
        <q-card flat style="width: 440px; max-width: 95vw">
          <q-form @submit.prevent="store.salvarVariedade()">
            <q-card-section class="bg-primary text-white">
              <div class="text-h6">
                {{ formVariedade.codvariedade ? 'Editar Variedade' : 'Nova Variedade' }}
              </div>
              <div class="text-caption">{{ cultura?.cultura }}</div>
            </q-card-section>
            <q-card-section class="q-pt-md">
              <div class="row q-col-gutter-md">
                <div class="col-12">
                  <q-input
                    v-model="formVariedade.variedade"
                    label="Variedade"
                    outlined
                    autofocus
                    lazy-rules
                    :rules="[(v) => !!v && v.length >= 2]"
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
                :loading="salvandoVariedade"
              />
            </q-card-actions>
          </q-form>
        </q-card>
      </q-dialog>
    </div>
  </q-page>
</template>
