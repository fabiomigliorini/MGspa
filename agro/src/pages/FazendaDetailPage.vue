<script setup>
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import { useFazendaStore } from 'src/stores/fazenda'
import { notifySuccess, notifyError } from 'src/utils/notify'
import MgInputValor from '@components/MgInputValor.vue'
import MgEmptyState from '@components/MgEmptyState.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import MapaTalhoes from 'components/MapaTalhoes.vue'
import { PALETA_TALHAO, corTalhao } from 'src/utils/coresTalhao'

const route = useRoute()
const router = useRouter()
const $q = useQuasar()
const codfazenda = Number(route.params.codfazenda)

// Detalhe do domínio fazenda — UMA store p/ todas as telas de fazenda.
const store = useFazendaStore()
const {
  fazenda,
  resumo,
  talhoes,
  outrasFazendas,
  talhoesComGeo,
  referenciaMapa,
  dialogFazenda,
  formFazenda,
  salvandoFazenda,
  dialogTalhao,
  formTalhao,
  salvandoTalhao,
} = storeToRefs(store)

const kpis = computed(() => [
  { label: 'Talhões', valor: fmt(resumo.value.ntalhoes), icon: 'grass', cor: 'brown' },
  {
    label: 'Área total',
    valor: `${fmt(resumo.value.areatalhoes, 1)} ha`,
    icon: 'crop_landscape',
    cor: 'green',
  },
  {
    label: 'Área plantada',
    valor: `${fmt(resumo.value.areaplantada, 1)} ha`,
    icon: 'eco',
    cor: 'light-green',
  },
  {
    label: 'Produtividade',
    valor: `${fmt(resumo.value.produtividade, 1)} sc/ha`,
    icon: 'trending_up',
    cor: 'teal',
  },
])

function fmt(v, dec = 0) {
  if (v === null || v === undefined || v === '') return '0'
  return Number(v).toLocaleString('pt-BR', {
    minimumFractionDigits: dec,
    maximumFractionDigits: dec,
  })
}

function urlGoogleMaps(t) {
  return `https://www.google.com/maps/@${t.latitude},${t.longitude},800m/data=!3m1!1e3`
}

// Fazenda — edição/inativação chamam as actions do domínio (que refrescam a
// fazenda aberta + KPIs). Excluir confirma aqui e navega de volta.
function editarFazenda() {
  store.editarFazenda(fazenda.value)
}
function alternarInativoFazenda() {
  store.inativarFazenda(fazenda.value)
}
function excluirFazenda() {
  $q.dialog({
    title: 'Excluir',
    message: `Excluir a fazenda ${fazenda.value?.fazenda}?`,
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Excluir', color: 'red-5', flat: true },
  }).onOk(async () => {
    try {
      await store.excluirFazenda(codfazenda)
      notifySuccess('Excluído!')
      router.push({ name: 'fazendas' })
    } catch (e) {
      notifyError(e)
    }
  })
}

// Talhão — janela única (mapa) que escreve no form do domínio.
function onCentro(c) {
  formTalhao.value.latitude = c.lat
  formTalhao.value.longitude = c.lng
}

onMounted(() => {
  store.carregarFazenda(codfazenda)
  store.carregarTalhoes(codfazenda)
  store.carregarOutrasFazendas(codfazenda)
})
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <!-- Cabeçalho -->
      <q-card bordered flat class="q-mb-md">
        <q-card-section class="row items-center">
          <div class="col-12 col-sm row items-center no-wrap">
            <q-btn
              flat
              round
              size="sm"
              color="grey-7"
              icon="arrow_back"
              :to="{ name: 'fazendas' }"
            />
            <q-avatar color="green-1" text-color="green-8" icon="agriculture" class="q-ml-sm" />
            <div class="col q-ml-md">
              <div class="text-h6">{{ fazenda?.fazenda || 'Fazenda' }}</div>
              <div class="text-caption text-grey-7">
                {{ fmt(resumo.areatalhoes, 1) }} ha de área total
              </div>
            </div>
          </div>
          <div
            class="col-12 col-sm-auto row items-center justify-end no-wrap"
            :class="{ 'q-mt-sm': $q.screen.lt.sm }"
          >
            <MgInfoCriacao :registro="fazenda" />
            <q-btn flat dense round size="sm" color="grey-7" icon="edit" @click="editarFazenda">
              <q-tooltip>Editar fazenda</q-tooltip>
            </q-btn>
            <q-btn
              flat
              dense
              round
              size="sm"
              color="grey-7"
              :icon="fazenda?.inativo ? 'play_arrow' : 'pause'"
              @click="alternarInativoFazenda"
            >
              <q-tooltip>{{ fazenda?.inativo ? 'Ativar' : 'Inativar' }}</q-tooltip>
            </q-btn>
            <q-btn flat dense round size="sm" color="grey-7" icon="delete" @click="excluirFazenda">
              <q-tooltip>Excluir</q-tooltip>
            </q-btn>
          </div>
        </q-card-section>
      </q-card>

      <!-- KPIs -->
      <div class="row q-col-gutter-md q-mb-md">
        <div v-for="k in kpis" :key="k.label" class="col-6 col-md-3">
          <q-card flat bordered>
            <q-card-section class="row items-center no-wrap">
              <q-avatar :color="`${k.cor}-1`" :text-color="`${k.cor}-8`" :icon="k.icon" />
              <div class="q-ml-md">
                <div class="text-h6 text-weight-bold">{{ k.valor }}</div>
                <div class="text-caption text-grey-7">{{ k.label }}</div>
              </div>
            </q-card-section>
          </q-card>
        </div>
      </div>

      <!-- Talhões -->
      <q-card bordered flat class="q-mb-md">
        <q-item>
          <q-item-section avatar>
            <q-avatar color="brown-1" text-color="brown-8" icon="grass" />
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-subtitle1">Layout base</q-item-label>
            <q-item-label caption
              >Modelo de talhões da fazenda — cada safra clona e ajusta</q-item-label
            >
          </q-item-section>
          <q-item-section side>
            <q-btn
              flat
              round
              size="sm"
              color="primary"
              icon="add"
              @click="store.novoTalhao(codfazenda)"
            >
              <q-tooltip>Novo talhão</q-tooltip>
            </q-btn>
          </q-item-section>
        </q-item>
        <q-separator />
        <q-list separator>
          <q-item v-for="t in talhoes" :key="t.codtalhao" :class="{ 'bg-grey-2': t.inativo }">
            <q-item-section avatar>
              <q-avatar
                text-color="white"
                icon="grass"
                :style="{ backgroundColor: corTalhao(t) }"
              />
            </q-item-section>
            <q-item-section>
              <q-item-label class="text-weight-medium">{{ t.talhao }}</q-item-label>
              <q-item-label caption>{{ fmt(t.area, 2) }} ha</q-item-label>
            </q-item-section>
            <q-item-section side>
              <div class="row items-center no-wrap">
                <q-badge v-if="!t.geometria" color="grey-5" label="sem mapa" />
                <MgInfoCriacao :registro="t" />
                <q-btn
                  flat
                  dense
                  round
                  size="sm"
                  color="grey-7"
                  icon="edit"
                  @click="store.editarTalhao(t)"
                >
                  <q-tooltip>Editar / desenhar polígono</q-tooltip>
                </q-btn>
                <q-btn
                  v-if="t.latitude && t.longitude"
                  flat
                  dense
                  round
                  size="sm"
                  color="grey-7"
                  icon="open_in_new"
                  type="a"
                  :href="urlGoogleMaps(t)"
                  target="_blank"
                >
                  <q-tooltip>Abrir no Google Maps</q-tooltip>
                </q-btn>
                <q-btn
                  flat
                  dense
                  round
                  size="sm"
                  color="grey-7"
                  :icon="t.inativo ? 'play_arrow' : 'pause'"
                  @click="store.inativarTalhao(t)"
                >
                  <q-tooltip>{{ t.inativo ? 'Ativar' : 'Inativar' }}</q-tooltip>
                </q-btn>
                <q-btn
                  flat
                  dense
                  round
                  size="sm"
                  color="grey-7"
                  icon="delete"
                  @click="store.excluirTalhao(t)"
                >
                  <q-tooltip>Excluir</q-tooltip>
                </q-btn>
              </div>
            </q-item-section>
          </q-item>
          <MgEmptyState v-if="!talhoes.length" plain icon="crop_square">
            Nenhum talhão cadastrado.
          </MgEmptyState>
        </q-list>
      </q-card>

      <!-- Por safra -->
      <q-card bordered flat class="overflow-hidden q-mb-md">
        <q-item>
          <q-item-section avatar>
            <q-avatar color="light-green-1" text-color="light-green-9" icon="eco" />
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-subtitle1">Por safra</q-item-label>
            <q-item-label caption>Plantio e produtividade nesta fazenda</q-item-label>
          </q-item-section>
        </q-item>
        <q-separator />
        <q-list separator>
          <q-item
            v-for="s in resumo.safras"
            :key="s.codsafra"
            clickable
            v-ripple
            :to="{ name: 'safra-detalhe', params: { codsafra: s.codsafra } }"
          >
            <q-item-section avatar>
              <q-avatar color="light-green-7" text-color="white" icon="eco" />
            </q-item-section>
            <q-item-section>
              <q-item-label class="text-weight-medium">{{ s.safra }}</q-item-label>
              <q-item-label caption>{{ fmt(s.area, 1) }} ha · {{ fmt(s.sacas) }} sc</q-item-label>
            </q-item-section>
            <q-item-section side class="text-right">
              <q-item-label class="text-weight-bold text-teal-8">
                {{ fmt(s.produtividade, 1) }} sc/ha
              </q-item-label>
            </q-item-section>
          </q-item>
          <MgEmptyState v-if="!resumo.safras.length" plain icon="eco">
            Nenhum plantio nesta fazenda ainda.
          </MgEmptyState>
        </q-list>
      </q-card>

      <!-- Mapa (abaixo do "Por safra") -->
      <q-card bordered flat>
        <q-item>
          <q-item-section avatar>
            <q-avatar color="green-1" text-color="green-8" icon="map" />
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-subtitle1">Mapa</q-item-label>
            <q-item-label caption>Layout base sobre imagem de satélite</q-item-label>
          </q-item-section>
        </q-item>
        <q-separator />
        <MapaTalhoes v-if="talhoesComGeo.length" :talhoes="talhoesComGeo" height="400px" />
        <MgEmptyState v-else plain icon="map">
          Nenhum talhão com polígono ainda. Use <q-icon name="add" /> para desenhar o primeiro.
        </MgEmptyState>
      </q-card>

      <!-- Dialog Fazenda -->
      <q-dialog v-model="dialogFazenda">
        <q-card flat style="width: 440px; max-width: 95vw">
          <q-form @submit.prevent="store.salvarFazenda()">
            <q-card-section class="bg-primary text-white">
              <div class="text-h6">Editar Fazenda</div>
            </q-card-section>
            <q-card-section class="q-pt-md">
              <div class="row q-col-gutter-md">
                <div class="col-12">
                  <q-input
                    v-model="formFazenda.fazenda"
                    label="Nome da fazenda"
                    outlined
                    autofocus
                    lazy-rules
                    :rules="[(v) => !!v && v.length >= 2]"
                  />
                </div>
                <div class="col-12 text-caption text-grey-6">
                  A área total é calculada automaticamente a partir dos talhões.
                </div>
              </div>
            </q-card-section>
            <q-card-actions align="right">
              <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
              <q-btn type="submit" flat label="Salvar" color="primary" :loading="salvandoFazenda" />
            </q-card-actions>
          </q-form>
        </q-card>
      </q-dialog>
    </div>

    <!-- Dialog Talhão = mapa tela cheia + bottom sheet de campos + fechar -->
    <q-dialog v-model="dialogTalhao" maximized>
      <q-card class="relative-position" style="width: 100vw; height: 100vh">
        <!-- Mapa de fundo, ocupa a tela toda -->
        <MapaTalhoes
          v-if="dialogTalhao"
          :key="formTalhao.codtalhao || 'novo'"
          modo="editar"
          :geometria="formTalhao.geometria"
          :cor="formTalhao.cor"
          :referencia="referenciaMapa"
          :outras="outrasFazendas"
          height="100%"
          :offset-inferior="120"
          @update:geometria="formTalhao.geometria = $event"
          @update:centro="onCentro"
          @update:area="formTalhao.area = $event"
        />

        <!-- Bottom sheet: cor + nome + área + salvar, numa barra única na base -->
        <div class="absolute-bottom q-pa-md q-mb-sm" style="z-index: 1000">
          <q-card flat bordered class="q-pa-sm" style="margin: 0 auto; max-width: 560px">
            <q-form
              class="row items-center no-wrap q-gutter-sm"
              @submit.prevent="store.salvarTalhao()"
            >
              <q-btn
                round
                :style="{ backgroundColor: formTalhao.cor }"
                text-color="white"
                icon="palette"
              >
                <q-tooltip>Cor do talhão</q-tooltip>
                <q-popup-proxy>
                  <q-color
                    v-model="formTalhao.cor"
                    :palette="PALETA_TALHAO"
                    default-view="palette"
                    no-header
                    no-footer
                  />
                </q-popup-proxy>
              </q-btn>
              <q-input
                v-model="formTalhao.talhao"
                label="Nome"
                outlined
                autofocus
                bg-color="white"
                class="col"
                lazy-rules
                :rules="[(v) => !!v]"
              />
              <MgInputValor
                v-model="formTalhao.area"
                :decimals="2"
                suffix="ha"
                label="Área"
                bg-color="white"
                style="width: 120px"
                lazy-rules
                :rules="[(v) => v > 0]"
              />
              <q-btn type="submit" round color="primary" icon="save" :loading="salvandoTalhao">
                <q-tooltip>Salvar talhão</q-tooltip>
              </q-btn>
            </q-form>
          </q-card>
        </div>

        <!-- FAB fechar (topo-direita) -->
        <q-btn
          fab
          round
          icon="close"
          color="white"
          text-color="grey-9"
          v-close-popup
          tabindex="-1"
          class="absolute"
          style="right: 16px; top: 16px; z-index: 1000"
        >
          <q-tooltip>Fechar</q-tooltip>
        </q-btn>
      </q-card>
    </q-dialog>
  </q-page>
</template>
