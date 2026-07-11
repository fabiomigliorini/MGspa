<script setup>
import { onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useCulturaStore } from 'src/stores/cultura'
import MgInputValor from '@components/MgInputValor.vue'
import MgEmptyState from '@components/MgEmptyState.vue'

// Lista do domínio cultura — tudo vem da store do domínio.
const store = useCulturaStore()
const { culturas, dialogCultura, formCultura, salvandoCultura } = storeToRefs(store)

const emojis = ['🌽', '🫛', '🌾', '☕', '🌻', '🥜', '🍅', '🌱']

// Ciclo da safra em anos civis: 1 = planta e colhe no mesmo ano (ex.: milho
// safrinha); 2 = planta num ano e colhe no seguinte (ex.: soja).
const opcoesCiclo = [
  { label: 'Mesmo ano (ex.: milho)', value: 1 },
  { label: 'Vira o ano (ex.: soja)', value: 2 },
]

onMounted(() => store.carregarCulturas())
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <q-card bordered flat class="q-mb-md">
        <q-card-section class="row items-center no-wrap">
          <q-btn flat round size="sm" color="grey-7" icon="arrow_back" :to="{ name: 'home' }" />
          <q-avatar color="blue-grey-1" text-color="blue-grey-8" icon="category" class="q-ml-sm" />
          <div class="col q-ml-md">
            <div class="text-h6">Culturas</div>
            <div class="text-caption text-grey-7">
              Variedades, descontos e safras de cada cultura
            </div>
          </div>
          <q-btn
            flat
            round
            size="sm"
            color="primary"
            icon="add"
            @click="store.novaCultura({ pesosaca: 60, cicloanos: 1 })"
          >
            <q-tooltip>Nova cultura</q-tooltip>
          </q-btn>
        </q-card-section>
      </q-card>

      <div v-if="culturas.length" class="row q-col-gutter-md">
        <div v-for="c in culturas" :key="c.codcultura" class="col-12 col-sm-6 col-md-4">
          <q-card flat bordered class="overflow-hidden" :class="{ 'bg-grey-2': c.inativo }">
            <q-item
              clickable
              v-ripple
              :to="{ name: 'cultura-detalhe', params: { codcultura: c.codcultura } }"
            >
              <q-item-section avatar>
                <q-avatar v-if="c.icone" color="light-green-1">
                  <span style="font-size: 26px">{{ c.icone }}</span>
                </q-avatar>
                <q-avatar v-else color="light-green-7" text-color="white" icon="grain" />
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-subtitle1">{{ c.cultura }}</q-item-label>
                <q-item-label caption>{{ Number(c.pesosaca) }} kg por saca</q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-icon name="chevron_right" color="grey-6" />
              </q-item-section>
            </q-item>
            <q-badge v-if="c.inativo" color="grey-6" label="Inativo" class="q-ma-sm" />
          </q-card>
        </div>
      </div>

      <MgEmptyState v-else icon="grass">Nenhuma cultura cadastrada.</MgEmptyState>

      <q-dialog v-model="dialogCultura">
        <q-card flat style="width: 440px; max-width: 95vw">
          <q-form @submit.prevent="store.salvarCultura()">
            <q-card-section class="bg-primary text-white">
              <div class="text-h6">
                {{ formCultura.codcultura ? 'Editar Cultura' : 'Nova Cultura' }}
              </div>
            </q-card-section>
            <q-card-section class="q-pt-md">
              <div class="row q-col-gutter-md">
                <div class="col-12 col-sm-8">
                  <q-input
                    v-model="formCultura.cultura"
                    label="Cultura"
                    outlined
                    autofocus
                    lazy-rules
                    :rules="[(v) => !!v && v.length >= 2]"
                  />
                </div>
                <div class="col-12 col-sm-4">
                  <q-input
                    v-model="formCultura.icone"
                    label="Emoji"
                    outlined
                    maxlength="4"
                    hint="Opcional"
                  >
                    <template #prepend>
                      <span style="font-size: 20px">{{ formCultura.icone || '🌱' }}</span>
                    </template>
                  </q-input>
                </div>
                <div class="col-12">
                  <div class="row q-gutter-xs">
                    <q-chip
                      v-for="e in emojis"
                      :key="e"
                      clickable
                      :label="e"
                      @click="formCultura.icone = e"
                    />
                  </div>
                </div>
                <div class="col-12 col-sm-6">
                  <MgInputValor
                    v-model="formCultura.pesosaca"
                    :decimals="0"
                    suffix="kg/saca"
                    label="Peso da saca"
                    lazy-rules
                    :rules="[(v) => v == null || v > 0]"
                  />
                </div>
                <div class="col-12 col-sm-6">
                  <q-select
                    v-model="formCultura.cicloanos"
                    :options="opcoesCiclo"
                    emit-value
                    map-options
                    outlined
                    label="Ciclo da safra"
                    hint="Define o ano de colheita sugerido ao abrir uma safra"
                  />
                </div>
              </div>
            </q-card-section>
            <q-card-actions align="right">
              <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
              <q-btn type="submit" flat label="Salvar" color="primary" :loading="salvandoCultura" />
            </q-card-actions>
          </q-form>
        </q-card>
      </q-dialog>
    </div>
  </q-page>
</template>
