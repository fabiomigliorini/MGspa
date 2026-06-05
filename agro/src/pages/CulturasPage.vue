<script setup>
import { onMounted } from 'vue'
import { useCadastro } from 'src/composables/useCadastro'
import MgInputValor from '@components/MgInputValor.vue'

const cad = useCadastro('cultura', 'codcultura', 'Cultura')

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
            <div class="text-caption text-grey-7">Milho, soja… e o peso da saca</div>
          </div>
          <q-btn flat round size="sm" color="primary" icon="add" @click="cad.abrirNovo({ pesosaca: 60 })">
            <q-tooltip>Nova cultura</q-tooltip>
          </q-btn>
        </q-card-section>
      </q-card>

      <div v-if="cad.items.length" class="row q-col-gutter-md">
        <div v-for="c in cad.items" :key="c.codcultura" class="col-12 col-sm-6 col-md-4">
          <q-card flat bordered :class="{ 'bg-grey-2': c.inativo }">
            <q-card-section class="row items-center no-wrap">
              <q-avatar color="light-green-7" text-color="white" icon="grain" />
              <div class="col q-ml-md">
                <div class="text-subtitle1">{{ c.cultura }}</div>
                <div class="text-caption text-grey-7">{{ Number(c.pesosaca) }} kg por saca</div>
              </div>
              <q-btn flat round size="sm" color="grey-7" icon="more_vert">
                <q-menu>
                  <q-list style="min-width: 150px">
                    <q-item clickable v-close-popup @click="cad.editar(c)">
                      <q-item-section avatar><q-icon name="edit" /></q-item-section>
                      <q-item-section>Editar</q-item-section>
                    </q-item>
                    <q-item clickable v-close-popup @click="cad.alternarInativo(c)">
                      <q-item-section avatar>
                        <q-icon :name="c.inativo ? 'play_arrow' : 'pause'" />
                      </q-item-section>
                      <q-item-section>{{ c.inativo ? 'Ativar' : 'Inativar' }}</q-item-section>
                    </q-item>
                    <q-item clickable v-close-popup @click="cad.excluir(c)">
                      <q-item-section avatar><q-icon name="delete" /></q-item-section>
                      <q-item-section>Excluir</q-item-section>
                    </q-item>
                  </q-list>
                </q-menu>
              </q-btn>
            </q-card-section>
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
              <MgInputValor v-model="cad.form.pesosaca" :decimals="0" suffix="kg/saca" label="Peso da saca" />
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
