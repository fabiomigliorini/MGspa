<script setup>
import { onMounted } from 'vue'
import { useCadastro } from 'src/composables/useCadastro'
import MgInputValor from '@components/MgInputValor.vue'

const cad = useCadastro('cultura', 'codcultura', 'Cultura')

const emojis = ['🌽', '🫛', '🌾', '☕', '🌻', '🥜', '🍅', '🌱']

// Ciclo da safra em anos civis: 1 = planta e colhe no mesmo ano (ex.: milho
// safrinha); 2 = planta num ano e colhe no seguinte (ex.: soja).
const opcoesCiclo = [
  { label: 'Mesmo ano (ex.: milho)', value: 1 },
  { label: 'Vira o ano (ex.: soja)', value: 2 },
]

onMounted(() => cad.carregar())
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
            @click="cad.abrirNovo({ pesosaca: 60, cicloanos: 1 })"
          >
            <q-tooltip>Nova cultura</q-tooltip>
          </q-btn>
        </q-card-section>
      </q-card>

      <div v-if="cad.items.length" class="row q-col-gutter-md">
        <div v-for="c in cad.items" :key="c.codcultura" class="col-12 col-sm-6 col-md-4">
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

      <q-banner v-else rounded class="bg-grey-2 text-grey-7">Nenhuma cultura cadastrada.</q-banner>

      <q-dialog v-model="cad.dialog">
        <q-card bordered flat style="width: 380px; max-width: 90vw">
          <q-form @submit="cad.salvar()">
            <q-card-section>
              <div class="text-h6">{{ cad.isNovo ? 'Nova Cultura' : 'Editar Cultura' }}</div>
            </q-card-section>
            <q-card-section class="q-gutter-md">
              <q-input v-model="cad.form.cultura" label="Cultura" outlined autofocus />
              <q-input v-model="cad.form.icone" label="Emoji" outlined maxlength="4" hint="Opcional">
                <template #prepend>
                  <span style="font-size: 20px">{{ cad.form.icone || '🌱' }}</span>
                </template>
              </q-input>
              <div class="row q-gutter-xs">
                <q-chip
                  v-for="e in emojis"
                  :key="e"
                  clickable
                  :label="e"
                  @click="cad.form.icone = e"
                />
              </div>
              <MgInputValor
                v-model="cad.form.pesosaca"
                :decimals="0"
                suffix="kg/saca"
                label="Peso da saca"
              />
              <q-select
                v-model="cad.form.cicloanos"
                :options="opcoesCiclo"
                emit-value
                map-options
                outlined
                label="Ciclo da safra"
                hint="Define o ano de colheita sugerido ao abrir uma safra"
              />
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
