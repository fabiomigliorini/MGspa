<script setup>
import { computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import MgEmptyState from '@components/MgEmptyState.vue'
import MgInput from '@components/MgInput.vue'
import { impressoraStore, TIPOS } from 'stores/impressora'

// Cadastro de impressoras (TASK-253). O nome da fila é o do CUPS do computador onde a
// impressora está, que o MGprint escuta; a descrição é o que aparece nas telas.
const sImpressora = impressoraStore()
const { form, dialog, salvando, isNovo } = storeToRefs(sImpressora)

const tipo = (valor) => TIPOS.find((t) => t.value === valor) || TIPOS[0]

// ativas primeiro, depois as inativas, cada grupo pela descrição
const lista = computed(() =>
  [...sImpressora.impressoras].sort(
    (a, b) =>
      (a.inativo ? 1 : 0) - (b.inativo ? 1 : 0) ||
      String(a.descricao).localeCompare(String(b.descricao)),
  ),
)

onMounted(() => sImpressora.carregar())
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <MgEmptyState v-if="!sImpressora.carregando && !lista.length" icon="print">
        Nenhuma impressora cadastrada.
      </MgEmptyState>

      <q-card v-else flat bordered>
        <q-list separator>
          <q-item v-for="i in lista" :key="i.codimpressora">
            <q-item-section avatar>
              <q-avatar :icon="tipo(i.tipo).icone" color="blue-grey" text-color="white" />
            </q-item-section>
            <q-item-section>
              <q-item-label class="ellipsis" :class="{ 'text-strike text-grey-6': i.inativo }">
                {{ i.descricao }}
              </q-item-label>
              <q-item-label caption class="ellipsis">
                {{ i.impressora }} · {{ tipo(i.tipo).label }}
              </q-item-label>
            </q-item-section>
            <q-item-section side>
              <div class="row no-wrap">
                <q-btn
                  flat
                  round
                  size="sm"
                  color="grey-7"
                  icon="edit"
                  @click="sImpressora.editar(i)"
                >
                  <q-tooltip>Editar</q-tooltip>
                </q-btn>
                <q-btn
                  flat
                  round
                  size="sm"
                  color="grey-7"
                  :icon="i.inativo ? 'play_arrow' : 'pause'"
                  @click="sImpressora.alternarInativo(i)"
                >
                  <q-tooltip>{{ i.inativo ? 'Ativar' : 'Inativar' }}</q-tooltip>
                </q-btn>
              </div>
            </q-item-section>
          </q-item>
        </q-list>
      </q-card>
    </div>

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn fab icon="add" color="primary" @click="sImpressora.nova()">
        <q-tooltip anchor="center left" self="center right">Nova impressora</q-tooltip>
      </q-btn>
    </q-page-sticky>

    <q-dialog v-model="dialog">
      <q-card flat style="width: 400px; max-width: 95vw">
        <q-form @submit.prevent="sImpressora.salvar()">
          <q-card-section class="text-grey-9 text-overline text-uppercase">
            {{ isNovo ? 'Nova impressora' : 'Editar impressora' }}
          </q-card-section>
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-12">
                <MgInput
                  v-model="form.descricao"
                  label="Descrição"
                  maxlength="100"
                  autofocus
                  :rules="[(v) => !!v || 'Informe a descrição']"
                />
              </div>
              <div class="col-12">
                <MgInput
                  v-model="form.impressora"
                  label="Nome da fila no CUPS"
                  hint="Igual ao configurado no MGprint do computador da impressora"
                  maxlength="100"
                  :rules="[
                    (v) => !!v || 'Informe o nome da fila',
                    (v) => /^[A-Za-z0-9_.-]+$/.test(v) || 'Só letras, números, ponto, hífen e _',
                  ]"
                />
              </div>
              <div class="col-12">
                <div class="text-caption text-grey-7">Tipo</div>
                <q-option-group v-model="form.tipo" :options="TIPOS" type="radio" inline />
              </div>
            </div>
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat label="Cancelar" color="grey-8" tabindex="-1" v-close-popup />
            <q-btn flat label="Salvar" color="primary" type="submit" :loading="salvando" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-page>
</template>
