<script setup>
import { ref, computed, onMounted } from 'vue'
import { useCadastro } from 'src/composables/useCadastro'
import MgInputValor from '@components/MgInputValor.vue'

const tal = useCadastro('talhao', 'codtalhao', 'Talhão')
const faz = useCadastro('fazenda', 'codfazenda', 'Fazenda')

const fazendaAtiva = ref(null)

const talhoesDaFazenda = computed(() =>
  tal.items.filter((t) => t.codfazenda === fazendaAtiva.value),
)

function fmt(v, dec = 2) {
  if (v === null || v === undefined || v === '') return '—'
  return Number(v).toLocaleString('pt-BR', { minimumFractionDigits: dec, maximumFractionDigits: dec })
}

function novoTalhao() {
  tal.abrirNovo({ codfazenda: fazendaAtiva.value })
}

function selecionarPrimeiraFazenda() {
  if (!fazendaAtiva.value && faz.items.length) {
    fazendaAtiva.value = faz.items[0].codfazenda
  }
}

async function carregar() {
  await faz.carregar()
  await tal.carregar()
  selecionarPrimeiraFazenda()
}

async function salvarFazenda() {
  await faz.salvar()
  await faz.carregar()
  selecionarPrimeiraFazenda()
}

onMounted(carregar)
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <q-card bordered flat class="q-mb-md">
        <q-card-section class="row items-center no-wrap">
          <q-btn flat round size="sm" color="grey-7" icon="arrow_back" :to="{ name: 'home' }" />
          <q-avatar color="brown-1" text-color="brown-8" icon="crop_landscape" class="q-ml-sm" />
          <div class="col q-ml-md">
            <div class="text-h6">Talhões</div>
            <div class="text-caption text-grey-7">Áreas da fazenda</div>
          </div>
          <q-btn flat round size="sm" color="primary" icon="add" @click="novoTalhao">
            <q-tooltip>Novo talhão</q-tooltip>
          </q-btn>
        </q-card-section>

        <q-separator inset />

        <q-card-section class="row items-center q-gutter-sm">
          <q-chip
            v-for="f in faz.items"
            :key="f.codfazenda"
            clickable
            :color="fazendaAtiva === f.codfazenda ? 'green-7' : 'grey-3'"
            :text-color="fazendaAtiva === f.codfazenda ? 'white' : 'grey-9'"
            icon="agriculture"
            @click="fazendaAtiva = f.codfazenda"
          >
            {{ f.fazenda }}
            <q-btn flat round size="xs" icon="edit" class="q-ml-xs" @click.stop="faz.editar(f)" />
          </q-chip>
          <q-btn flat color="primary" icon="add" label="Fazenda" @click="faz.abrirNovo()" />
        </q-card-section>
      </q-card>

      <div v-if="talhoesDaFazenda.length" class="row q-col-gutter-md">
        <div v-for="t in talhoesDaFazenda" :key="t.codtalhao" class="col-12 col-sm-6 col-md-4">
          <q-card flat bordered :class="{ 'bg-grey-2': t.inativo }">
            <q-card-section class="row items-center no-wrap">
              <q-avatar color="brown-6" text-color="white" icon="grass" />
              <div class="col q-ml-md">
                <div class="text-subtitle1">{{ t.talhao }}</div>
                <div class="text-caption text-grey-7">{{ fmt(t.area) }} ha</div>
              </div>
              <q-btn flat round size="sm" color="grey-7" icon="more_vert">
                <q-menu>
                  <q-list style="min-width: 150px">
                    <q-item clickable v-close-popup @click="tal.editar(t)">
                      <q-item-section avatar><q-icon name="edit" /></q-item-section>
                      <q-item-section>Editar</q-item-section>
                    </q-item>
                    <q-item clickable v-close-popup @click="tal.alternarInativo(t)">
                      <q-item-section avatar>
                        <q-icon :name="t.inativo ? 'play_arrow' : 'pause'" />
                      </q-item-section>
                      <q-item-section>{{ t.inativo ? 'Ativar' : 'Inativar' }}</q-item-section>
                    </q-item>
                    <q-item clickable v-close-popup @click="tal.excluir(t)">
                      <q-item-section avatar><q-icon name="delete" /></q-item-section>
                      <q-item-section>Excluir</q-item-section>
                    </q-item>
                  </q-list>
                </q-menu>
              </q-btn>
            </q-card-section>
            <q-badge v-if="t.inativo" color="grey-6" label="Inativo" class="q-ma-sm" />
          </q-card>
        </div>
      </div>

      <q-banner v-else rounded class="bg-grey-2 text-grey-7">
        Nenhum talhão nesta fazenda. Use o botão <q-icon name="add" /> para adicionar.
      </q-banner>

      <!-- Dialog Talhão -->
      <q-dialog v-model="tal.dialog">
        <q-card bordered flat style="width: 420px; max-width: 90vw">
          <q-form @submit="tal.salvar()">
            <q-card-section>
              <div class="text-h6">{{ tal.isNovo ? 'Novo Talhão' : 'Editar Talhão' }}</div>
            </q-card-section>
            <q-card-section class="q-gutter-md">
              <q-select
                v-model="tal.form.codfazenda"
                :options="faz.items"
                option-value="codfazenda"
                option-label="fazenda"
                emit-value
                map-options
                outlined
                label="Fazenda"
              />
              <q-input v-model="tal.form.talhao" label="Nome / número do talhão" outlined autofocus />
              <MgInputValor v-model="tal.form.area" :decimals="2" suffix="ha" label="Área" />
            </q-card-section>
            <q-card-actions align="right">
              <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
              <q-btn type="submit" unelevated label="Salvar" color="primary" :loading="tal.salvando" />
            </q-card-actions>
          </q-form>
        </q-card>
      </q-dialog>

      <!-- Dialog Fazenda -->
      <q-dialog v-model="faz.dialog">
        <q-card bordered flat style="width: 420px; max-width: 90vw">
          <q-form @submit="salvarFazenda()">
            <q-card-section>
              <div class="text-h6">{{ faz.isNovo ? 'Nova Fazenda' : 'Editar Fazenda' }}</div>
            </q-card-section>
            <q-card-section class="q-gutter-md">
              <q-input v-model="faz.form.fazenda" label="Nome da fazenda" outlined autofocus />
              <MgInputValor v-model="faz.form.areatotal" :decimals="2" suffix="ha" label="Área total" />
            </q-card-section>
            <q-card-actions align="right">
              <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
              <q-btn type="submit" unelevated label="Salvar" color="primary" :loading="faz.salvando" />
            </q-card-actions>
          </q-form>
        </q-card>
      </q-dialog>
    </div>
  </q-page>
</template>
