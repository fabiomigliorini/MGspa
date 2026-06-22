<script setup>
import { onMounted } from 'vue'
import { useCadastro } from 'src/composables/useCadastro'
import MgIconeCultura from 'components/MgIconeCultura.vue'
import MgEmptyState from '@components/MgEmptyState.vue'
import MgSafraForm from 'components/MgSafraForm.vue'

const cad = useCadastro('safra', 'codsafra', 'Safra')

function safraAnos(s) {
  if (!s.anoplantio) return 'Sem ano'
  return s.anocolheita && s.anocolheita !== s.anoplantio
    ? `${s.anoplantio}/${s.anocolheita}`
    : `${s.anoplantio}`
}

onMounted(() => cad.carregar())
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <q-card bordered flat class="q-mb-md">
        <q-card-section class="row items-center no-wrap">
          <q-btn flat round size="sm" color="grey-7" icon="arrow_back" :to="{ name: 'home' }" />
          <q-avatar color="light-green-1" text-color="light-green-9" icon="eco" class="q-ml-sm" />
          <div class="col q-ml-md">
            <div class="text-h6">Safras</div>
            <div class="text-caption text-grey-7">
              Abra uma safra para ver plantio e produtividade
            </div>
          </div>
          <q-btn flat round size="sm" color="primary" icon="add" @click="cad.abrirNovo()">
            <q-tooltip>Nova safra</q-tooltip>
          </q-btn>
        </q-card-section>
      </q-card>

      <div v-if="cad.items.length" class="row q-col-gutter-md">
        <div v-for="s in cad.items" :key="s.codsafra" class="col-12 col-sm-6">
          <q-card flat bordered class="overflow-hidden" :class="{ 'bg-grey-2': s.inativo }">
            <q-item
              clickable
              v-ripple
              :to="{ name: 'safra-detalhe', params: { codsafra: s.codsafra } }"
            >
              <q-item-section avatar>
                <MgIconeCultura :codcultura="s.codcultura" fallback-color="light-green-8" />
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-subtitle1">{{ s.safra }}</q-item-label>
                <q-item-label caption>
                  {{ s.Cultura?.cultura || '—' }} · {{ safraAnos(s) }}
                </q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-icon name="chevron_right" color="grey-6" />
              </q-item-section>
            </q-item>
          </q-card>
        </div>
      </div>

      <MgEmptyState v-else icon="eco">
        Nenhuma safra. Crie a primeira com o botão <q-icon name="add" />.
      </MgEmptyState>

      <q-dialog v-model="cad.dialog">
        <q-card flat style="width: 440px; max-width: 95vw">
          <q-form @submit="cad.salvar()">
            <q-card-section class="bg-primary text-white">
              <div class="text-h6">{{ cad.isNovo ? 'Nova Safra' : 'Editar Safra' }}</div>
            </q-card-section>
            <q-card-section class="q-pt-md">
              <MgSafraForm :cad="cad" :safras="cad.items" />
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
