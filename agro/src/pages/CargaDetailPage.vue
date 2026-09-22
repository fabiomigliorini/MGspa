<script setup>
// Ficha do romaneio — SOMENTE LEITURA. Quem edita é o pátio (/carga/:uuid), que
// trabalha offline no Dexie; aqui é consulta do histórico, direto do servidor.
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import { api } from 'src/services/api'
import { formataTimestamp, tempoRelativo } from '@components/formatters'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import { sentidoMeta, ETAPA_META, fmtNumero } from 'src/utils/carga'
import { imprimirTicket, ticketDoServidor } from 'src/utils/ticket'
import { notifyError } from 'src/utils/notify'
import CargaEtapaProgresso from 'components/carga/CargaEtapaProgresso.vue'

const route = useRoute()
const $q = useQuasar()

const carga = ref(null)
const carregando = ref(false)

const meta = computed(() => sentidoMeta(carga.value?.sentido))
const etapa = computed(() => ETAPA_META[carga.value?.etapa] || {})
// Relação aninhada dentro de um MODEL (não de um Resource) sai em minúsculo:
// `Safra` é PascalCase porque o CargaResource a expõe na mão, mas a `cultura`
// de dentro dela é serializada pelo Eloquent, que usa snake_case.
const cultura = computed(() => carga.value?.Safra?.cultura || null)
const pesosaca = computed(() => Number(cultura.value?.pesosaca) || 60)
const pontos = computed(() => carga.value?.CargaPontoS || [])
const origens = computed(() => pontos.value.filter((p) => p.papel === 'ORIGEM'))
const destinos = computed(() => pontos.value.filter((p) => p.papel === 'DESTINO'))
const classificacao = computed(() =>
  (carga.value?.classificacao || []).filter(
    (l) => l.leitura !== null && l.leitura !== undefined && l.leitura !== '',
  ),
)

const sacasLiquido = computed(() =>
  carga.value?.liquido != null ? Number(carga.value.liquido) / pesosaca.value : null,
)

const pesos = computed(() => {
  const c = carga.value || {}
  return [
    { label: 'Peso bruto total', valor: c.pbt },
    { label: 'Tara', valor: c.tara },
    { label: 'Bruto (carga)', valor: c.bruto },
    { label: 'Desconto', valor: c.desconto, classe: 'text-orange-9' },
  ].filter((p) => p.valor != null)
})

function kgPonto(p) {
  return p.liquido != null ? `${fmtNumero(p.liquido)} kg` : ''
}

async function carregar(codcarga) {
  if (!codcarga) return
  carregando.value = true
  try {
    const { data } = await api.get(`v1/carga/${codcarga}`)
    // ATENÇÃO: esta resposta NÃO vem embrulhada em `data`, ao contrário da
    // maioria dos Resources. O JsonResource só embrulha quando o array ainda
    // não tem a chave do wrapper — e `data` é COLUNA da carga (chegada no
    // pátio). Ler `data.data` aqui devolveria o timestamp, não a carga.
    carga.value = data?.codcarga != null ? data : (data?.data ?? null)
  } catch (e) {
    notifyError(e)
  } finally {
    carregando.value = false
  }
}

function imprimir() {
  if (!carga.value) return
  if (!imprimirTicket(ticketDoServidor(carga.value))) {
    $q.notify({ type: 'warning', message: 'Permita pop-ups para imprimir o romaneio.' })
  }
}

// Recarrega ao navegar de um romaneio pro outro sem remontar a página.
watch(() => route.params.codcarga, carregar)
onMounted(() => carregar(route.params.codcarga))
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <div v-if="carregando && !carga" class="text-center q-pa-xl">
        <q-spinner color="primary" size="3em" />
      </div>

      <q-card v-else-if="!carga" flat bordered>
        <q-card-section class="text-center q-pa-xl text-grey-6">
          <q-icon name="search_off" size="4em" color="grey-4" />
          <div class="text-subtitle1 q-mt-md">Romaneio não encontrado</div>
          <q-btn
            flat
            color="primary"
            icon="arrow_back"
            label="Voltar"
            class="q-mt-md"
            :to="{ name: 'cargas' }"
          />
        </q-card-section>
      </q-card>

      <template v-else>
        <q-banner v-if="carga.inativo" rounded class="bg-red-1 text-red-10 q-mb-md">
          <template #avatar><q-icon name="block" color="negative" /></template>
          Romaneio cancelado em {{ formataTimestamp(carga.inativo) }}. Os pesos não entram no
          estoque.
        </q-banner>

        <!-- CABEÇALHO -->
        <q-card flat bordered class="q-mb-md">
          <q-card-section class="row items-center q-col-gutter-md">
            <div class="col-auto">
              <q-avatar :icon="meta.icon" :color="meta.color" text-color="white" size="56px" />
            </div>
            <div class="col">
              <div class="text-h5">
                {{ meta.label }} <span class="text-grey-6">#{{ carga.codcarga }}</span>
              </div>
              <div class="text-caption text-grey-7">
                <q-icon :name="etapa.icon" :color="etapa.color" size="16px" />
                {{ etapa.label }} · Chegada {{ formataTimestamp(carga.data) }} ·
                {{ tempoRelativo(carga.data) }}
              </div>
              <div class="text-caption text-grey-7">
                {{ carga.Safra?.safra }} · {{ cultura?.cultura }}
              </div>
            </div>
            <div class="col-12 col-md-4 text-right">
              <div class="text-h4 text-weight-bolder text-green-8">
                {{ fmtNumero(carga.liquido) }} <small class="text-grey-6">kg</small>
              </div>
              <div v-if="sacasLiquido !== null" class="text-caption text-grey-7">
                {{ fmtNumero(sacasLiquido, 1) }} sacas de {{ pesosaca }} kg
              </div>
            </div>
          </q-card-section>
          <q-card-section class="q-pt-none">
            <CargaEtapaProgresso :carga="carga" labels />
          </q-card-section>
        </q-card>

        <div class="row q-col-gutter-md">
          <!-- PESAGEM -->
          <div class="col-12 col-md-6">
            <q-card flat bordered class="fit">
              <q-card-section class="q-pb-none">
                <div class="text-subtitle2 text-grey-8">Pesagem</div>
              </q-card-section>
              <q-list>
                <q-item v-for="p in pesos" :key="p.label">
                  <q-item-section>
                    <q-item-label caption>{{ p.label }}</q-item-label>
                  </q-item-section>
                  <q-item-section class="text-right">
                    <q-item-label class="text-subtitle1" :class="p.classe">
                      {{ fmtNumero(p.valor) }} <small class="text-grey-6">kg</small>
                    </q-item-label>
                  </q-item-section>
                </q-item>
                <q-separator spaced />
                <q-item>
                  <q-item-section>
                    <q-item-label caption>Líquido</q-item-label>
                  </q-item-section>
                  <q-item-section class="text-right">
                    <q-item-label class="text-h6 text-green-8">
                      {{ fmtNumero(carga.liquido) }} <small class="text-grey-6">kg</small>
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </q-card>
          </div>

          <!-- CAMINHÃO -->
          <div class="col-12 col-md-6">
            <q-card flat bordered class="fit">
              <q-card-section class="q-pb-none">
                <div class="text-subtitle2 text-grey-8">Caminhão e motorista</div>
              </q-card-section>
              <q-list>
                <q-item>
                  <q-item-section avatar>
                    <q-avatar icon="local_shipping" color="secondary" text-color="white" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label>
                      {{ carga.placa || 'Sem placa' }}
                      <span v-if="carga.placacarreta" class="text-grey-6">
                        / {{ carga.placacarreta }}
                      </span>
                    </q-item-label>
                    <q-item-label v-if="carga.Veiculo?.veiculo" caption>
                      {{ carga.Veiculo.veiculo }}
                    </q-item-label>
                  </q-item-section>
                </q-item>
                <q-item>
                  <q-item-section avatar>
                    <q-avatar icon="person" color="secondary" text-color="white" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label>{{ carga.motorista || 'Não informado' }}</q-item-label>
                    <q-item-label v-if="carga.PessoaMotorista" caption>
                      Cadastrado ·
                      {{ carga.PessoaMotorista.fantasia || carga.PessoaMotorista.pessoa }}
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </q-card>
          </div>

          <!-- ORIGENS / DESTINOS -->
          <div class="col-12 col-md-6">
            <q-card flat bordered class="fit">
              <q-card-section class="q-pb-none">
                <div class="text-subtitle2 text-grey-8">Origem do grão</div>
              </q-card-section>
              <q-list>
                <q-item v-for="p in origens" :key="'o' + p.codcargaponto">
                  <q-item-section avatar>
                    <q-avatar icon="login" color="brown-5" text-color="white" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label>{{ p.rotulo || 'Não informada' }}</q-item-label>
                    <q-item-label caption>{{ kgPonto(p) }}</q-item-label>
                  </q-item-section>
                </q-item>
                <q-item v-if="!origens.length">
                  <q-item-section class="text-grey-6">Sem origem registrada</q-item-section>
                </q-item>
              </q-list>
            </q-card>
          </div>

          <div class="col-12 col-md-6">
            <q-card flat bordered class="fit">
              <q-card-section class="q-pb-none">
                <div class="text-subtitle2 text-grey-8">Destino do grão</div>
              </q-card-section>
              <q-list>
                <q-item v-for="p in destinos" :key="'d' + p.codcargaponto">
                  <q-item-section avatar>
                    <q-avatar icon="logout" color="teal-7" text-color="white" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label>{{ p.rotulo || 'Não informado' }}</q-item-label>
                    <q-item-label caption>{{ kgPonto(p) }}</q-item-label>
                    <q-item-label v-if="p.numeronf" caption>
                      NF {{ p.numeronf }}
                      <span v-if="p.valornf"> · R$ {{ fmtNumero(p.valornf, 2) }}</span>
                    </q-item-label>
                  </q-item-section>
                </q-item>
                <q-item v-if="!destinos.length">
                  <q-item-section class="text-grey-6">Sem destino registrado</q-item-section>
                </q-item>
              </q-list>
            </q-card>
          </div>

          <!-- CLASSIFICAÇÃO -->
          <div v-if="classificacao.length" class="col-12">
            <q-card flat bordered>
              <q-card-section class="q-pb-sm">
                <div class="text-subtitle2 text-grey-8">Classificação</div>
              </q-card-section>
              <q-markup-table flat>
                <thead>
                  <tr>
                    <th class="text-left">Parâmetro</th>
                    <th class="text-right">Leitura</th>
                    <th class="text-right">Tolerância</th>
                    <th class="text-right">Desconto</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="l in classificacao" :key="l.codcargaclassificacao">
                    <td class="text-left">
                      {{
                        l.ParametroClassificacao?.parametroclassificacao ||
                        `#${l.codparametroclassificacao}`
                      }}
                    </td>
                    <td class="text-right">{{ fmtNumero(l.leitura, 1) }} %</td>
                    <td class="text-right text-grey-6">
                      {{
                        l.ParametroClassificacao
                          ? fmtNumero(l.ParametroClassificacao.tolerancia, 1) + ' %'
                          : '—'
                      }}
                    </td>
                    <td class="text-right text-orange-9">{{ fmtNumero(l.desconto) }} kg</td>
                  </tr>
                </tbody>
              </q-markup-table>
            </q-card>
          </div>

          <!-- OBSERVAÇÃO -->
          <div v-if="carga.observacao" class="col-12">
            <q-card flat bordered>
              <q-card-section>
                <div class="text-subtitle2 text-grey-8 q-mb-xs">Observação</div>
                <div style="white-space: pre-wrap">{{ carga.observacao }}</div>
              </q-card-section>
            </q-card>
          </div>

          <div class="col-12">
            <MgInfoCriacao :registro="carga" />
          </div>
        </div>
      </template>
    </div>

    <q-page-sticky v-if="carga" position="bottom-right" :offset="[18, 18]">
      <div class="column q-gutter-sm items-end">
        <q-btn fab-mini icon="arrow_back" color="grey-7" :to="{ name: 'cargas' }">
          <q-tooltip>Voltar para a listagem</q-tooltip>
        </q-btn>
        <q-btn fab icon="print" color="primary" @click="imprimir">
          <q-tooltip>Imprimir romaneio</q-tooltip>
        </q-btn>
      </div>
    </q-page-sticky>
  </q-page>
</template>
