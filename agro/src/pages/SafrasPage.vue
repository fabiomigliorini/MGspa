<script setup>
import { ref, onMounted } from 'vue'
import { api } from 'src/services/api'
import { useCadastro } from 'src/composables/useCadastro'
import MgInputData from '@components/MgInputData.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import MgIconeCultura from 'components/MgIconeCultura.vue'

const cad = useCadastro('safra', 'codsafra', 'Safra')
const culturas = ref([])

async function carregarCulturas() {
  const { data } = await api.get('v1/cultura')
  culturas.value = data.data ?? data
}

function fmtData(d) {
  if (!d) return ''
  const [a, m, dia] = d.slice(0, 10).split('-')
  return `${dia}/${m}/${a}`
}

function periodo(s) {
  const i = fmtData(s.datainicio)
  const f = fmtData(s.datafim)
  if (i && f) return `${i} a ${f}`
  return i || f || 'Sem período'
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
          <q-avatar color="light-green-1" text-color="light-green-9" icon="eco" class="q-ml-sm" />
          <div class="col q-ml-md">
            <div class="text-h6">Safras</div>
            <div class="text-caption text-grey-7">Abra uma safra para ver plantio e produtividade</div>
          </div>
          <q-btn flat round size="sm" color="primary" icon="add" @click="cad.abrirNovo()">
            <q-tooltip>Nova safra</q-tooltip>
          </q-btn>
        </q-card-section>
      </q-card>

      <div v-if="cad.items.length" class="row q-col-gutter-md">
        <div v-for="s in cad.items" :key="s.codsafra" class="col-12 col-sm-6">
          <q-card flat bordered class="overflow-hidden" :class="{ 'bg-grey-2': s.inativo }">
            <q-item clickable v-ripple :to="{ name: 'safra-detalhe', params: { codsafra: s.codsafra } }">
              <q-item-section avatar>
                <MgIconeCultura :codcultura="s.codcultura" fallback-color="light-green-8" />
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-subtitle1">{{ s.safra }}</q-item-label>
                <q-item-label caption>
                  {{ s.cultura?.cultura || '—' }} · {{ periodo(s) }}
                </q-item-label>
              </q-item-section>
              <q-item-section side>
                <div class="row items-center no-wrap" @click.prevent.stop>
                  <MgInfoCriacao
                    :usuariocriacao="s.usuariocriacao"
                    :criacao="s.criacao"
                    :usuarioalteracao="s.usuarioalteracao"
                    :alteracao="s.alteracao"
                  />
                  <q-btn
                    flat
                    dense
                    round
                    size="sm"
                    color="grey-7"
                    icon="edit"
                    @click="cad.editar(s)"
                  >
                    <q-tooltip>Editar</q-tooltip>
                  </q-btn>
                  <q-btn
                    flat
                    dense
                    round
                    size="sm"
                    color="grey-7"
                    :icon="s.inativo ? 'play_arrow' : 'pause'"
                    @click="cad.alternarInativo(s)"
                  >
                    <q-tooltip>{{ s.inativo ? 'Ativar' : 'Inativar' }}</q-tooltip>
                  </q-btn>
                  <q-btn
                    flat
                    dense
                    round
                    size="sm"
                    color="grey-7"
                    icon="delete"
                    @click="cad.excluir(s)"
                  >
                    <q-tooltip>Excluir</q-tooltip>
                  </q-btn>
                </div>
              </q-item-section>
            </q-item>
          </q-card>
        </div>
      </div>

      <q-banner v-else rounded class="bg-grey-2 text-grey-7">
        Nenhuma safra. Crie a primeira com o botão <q-icon name="add" />.
      </q-banner>

      <q-dialog v-model="cad.dialog">
        <q-card bordered flat style="width: 440px; max-width: 90vw">
          <q-form @submit="cad.salvar()">
            <q-card-section>
              <div class="text-h6">{{ cad.isNovo ? 'Nova Safra' : 'Editar Safra' }}</div>
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
              <q-input
                v-model="cad.form.safra"
                label="Safra"
                hint="Ex.: Milho 2ª Safra 2026"
                outlined
                autofocus
              />
              <div class="row q-col-gutter-md">
                <MgInputData v-model="cad.form.datainicio" label="Início" type="date" class="col-6" />
                <MgInputData v-model="cad.form.datafim" label="Fim" type="date" class="col-6" />
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
