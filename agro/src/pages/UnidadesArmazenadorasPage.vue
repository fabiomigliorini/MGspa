<script setup>
import { computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useUnidadeArmazenadoraStore } from 'src/stores/unidadeArmazenadora'
import MgInputValor from '@components/MgInputValor.vue'
import MgSelectPessoa from '@components/MgSelectPessoa.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import MgEmptyState from '@components/MgEmptyState.vue'

// Tela do domínio unidade armazenadora — tudo vem da store. TIPOS/meta é
// apresentação (ícone/cor) e fica na tela.
const store = useUnidadeArmazenadoraStore()
const { unidades, carregando, dialog, form, salvando } = storeToRefs(store)
const isNovo = computed(() => !form.value.codunidadearmazenadora)

const TIPOS = [
  { value: 'PROPRIO', label: 'Próprio', icon: 'home', color: 'green-7' },
  { value: 'TERCEIRO', label: 'Terceiro', icon: 'handshake', color: 'blue-grey-7' },
  { value: 'SILOBAG', label: 'Silo bag', icon: 'inventory', color: 'amber-8' },
]
function meta(tipo) {
  return TIPOS.find((t) => t.value === tipo) || { label: tipo, icon: 'warehouse', color: 'grey-7' }
}

onMounted(store.carregar)
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <div class="row items-center q-mb-md">
        <div class="text-h5 col">Unidades Armazenadoras</div>
        <q-btn flat round size="sm" color="primary" icon="add" @click="store.novo">
          <q-tooltip>Nova unidade</q-tooltip>
        </q-btn>
      </div>

      <q-card bordered flat>
        <q-list separator>
          <q-item v-for="u in unidades" :key="u.codunidadearmazenadora">
            <q-item-section avatar>
              <q-icon :name="meta(u.tipo).icon" :color="meta(u.tipo).color" size="sm" />
            </q-item-section>
            <q-item-section>
              <q-item-label :class="{ 'text-strike text-grey-6': u.inativo }">
                {{ u.unidadearmazenadora }}
              </q-item-label>
              <q-item-label caption>
                {{ meta(u.tipo).label }}
                <span v-if="u.Pessoa"> · {{ u.Pessoa.fantasia || u.Pessoa.pessoa }}</span>
                <span v-if="u.capacidadesacas">
                  · {{ Number(u.capacidadesacas).toLocaleString('pt-BR') }} sc</span
                >
              </q-item-label>
            </q-item-section>
            <q-item-section side>
              <div class="row items-center no-wrap q-gutter-xs">
                <MgInfoCriacao :registro="u" />
                <q-btn
                  flat
                  round
                  size="sm"
                  color="grey-7"
                  :icon="u.inativo ? 'play_arrow' : 'pause'"
                  @click="store.alternarInativo(u)"
                >
                  <q-tooltip>{{ u.inativo ? 'Ativar' : 'Inativar' }}</q-tooltip>
                </q-btn>
                <q-btn flat round size="sm" color="grey-7" icon="edit" @click="store.editar(u)">
                  <q-tooltip>Editar</q-tooltip>
                </q-btn>
              </div>
            </q-item-section>
          </q-item>

          <MgEmptyState v-if="!unidades.length && !carregando" plain icon="warehouse">
            Nenhuma unidade armazenadora cadastrada
          </MgEmptyState>
        </q-list>
      </q-card>
    </div>

    <q-dialog v-model="dialog">
      <q-card flat style="width: 500px; max-width: 90vw">
        <q-form @submit.prevent="store.salvar()">
          <q-card-section class="bg-primary text-white">
            <div class="text-h6">{{ isNovo ? 'Nova unidade' : 'Editar unidade' }}</div>
          </q-card-section>
          <q-card-section class="q-pt-md">
            <div class="row q-col-gutter-md">
              <div class="col-12">
                <q-input
                  v-model="form.unidadearmazenadora"
                  label="Nome"
                  outlined
                  autofocus
                  maxlength="60"
                  lazy-rules
                  :rules="[(v) => !!v]"
                />
              </div>
              <div class="col-12 col-sm-6">
                <q-select
                  v-model="form.tipo"
                  label="Tipo"
                  outlined
                  :options="TIPOS"
                  option-value="value"
                  option-label="label"
                  emit-value
                  map-options
                  lazy-rules
                  :rules="[(v) => !!v]"
                />
              </div>
              <div class="col-12 col-sm-6">
                <MgInputValor
                  v-model="form.capacidadesacas"
                  :decimals="0"
                  suffix="sc"
                  label="Capacidade"
                />
              </div>
              <div v-if="form.tipo === 'TERCEIRO'" class="col-12">
                <MgSelectPessoa v-model="form.codpessoa" label="Dono (armazém de terceiro)" />
              </div>
              <div class="col-12">
                <q-input
                  v-model="form.observacao"
                  label="Observação"
                  type="textarea"
                  autogrow
                  outlined
                />
              </div>
            </div>
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
            <q-btn type="submit" flat label="Salvar" color="primary" :loading="salvando" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-page>
</template>
