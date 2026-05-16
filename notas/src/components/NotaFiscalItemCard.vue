<script setup>
import { computed, ref, onMounted } from 'vue'
import {
  formataNumero,
  formataNumero,
  formataNcm,
  formataCest,
  formataCodNegocio,
} from '@components/formatters'
import { getEnteIcon } from 'src/composables/useTributoIcons'

// const props = defineProps({
const props = defineProps({
  item: {
    type: Object,
    required: true,
  },
  notaBloqueada: {
    type: Boolean,
    default: false,
  },
})

// URL base para negócios
const negociosUrl = import.meta.env.VITE_NEGOCIOS_URL || process.env.NEGOCIOS_URL

const imagem = computed(() => {
  if (props.item.produtoBarra?.imagem) {
    return props.item.produtoBarra?.imagem
  }
  return '/produtoSemImagem.png'
})

const tabRural = computed(() => {
  return (
    props.item.funruralpercentual ||
    props.item.senarpercentual ||
    props.item.fethabkg ||
    props.item.iagrokg
  )
})

// URL do negócio
const negocioUrl = computed(() => {
  if (!props.item.codnegocio || !negociosUrl) return null
  return `${negociosUrl}/negocio/${props.item.codnegocio}`
})

// URL base para MGLara
const mglaraUrl = import.meta.env.VITE_MGLARA_URL || process.env.MGLARA_URL

const emit = defineEmits(['delete'])

const tab = ref('icms')

onMounted(() => {
  if (tabRural.value) {
    tab.value = 'rural'
  }
})

const getProdutoUrl = (codproduto) => {
  return `${mglaraUrl}/produto/${codproduto}`
}
</script>

<template>
  <div class="col-xs-12 col-sm-4 col-md-3 col-lg-2">
    <q-card flat bordered class="full-height flex column">
      <q-item class="bg-grey-3">
        <q-item-section avatar>
          <q-avatar
            color="primary"
            text-color="blue-2"
            size="md"
            class="q-mr-sm text-weight-bolder"
          >
            {{ item.ordem }}
          </q-avatar>
        </q-item-section>

        <q-item-section style="height: 70px">
          <a
            :href="getProdutoUrl(item.produtoBarra?.codproduto)"
            target="_blank"
            class="text-primary text-weight-medium text-caption"
            style="text-decoration: none"
          >
            <q-item-label lines="3" class="text-black text-body2">
              {{ item.descricaoalternativa }}
              <span :class="item.descricaoalternativa ? 'text-strike text-weight-thin ' : null">
                {{ item.produtoBarra?.descricao }}
              </span>
            </q-item-label>
            <q-item-label caption>{{ item.produtoBarra?.barras || '-' }}</q-item-label>
          </a>
        </q-item-section>
      </q-item>

      <a v-if="imagem" :href="imagem" target="_blank" style="width: 100%">
        <q-img :src="imagem" :ratio="4 / 2" class="rounded-borders" />
      </a>

      <q-separator />
      <q-card-section class="q-pa-md">
        <div class="row q-col-gutter-sm">
          <!-- Quantodade -->
          <div class="col-4">
            <div class="text-caption text-grey-7">Quant</div>
            <div class="text-body2">
              {{ item.quantidade }} {{ item.produtoBarra?.unidade || '-' }}
            </div>
          </div>

          <!-- Valor Unitário -->
          <div class="col-4">
            <div class="text-caption text-grey-7">Preço</div>
            <div class="text-body2">{{ formataNumero(item.valorunitario) }}</div>
          </div>

          <!-- Total -->
          <div class="col-4">
            <div class="text-caption text-grey-7">Total</div>
            <div class="text-body1 text-primary text-weight-bold">
              {{ formataNumero(item.valortotal) }}
            </div>
          </div>

          <!-- Desconto -->
          <div class="col-4" v-if="item.valordesconto">
            <div class="text-caption text-grey-7">Desconto</div>
            <div class="text-caption">{{ formataNumero(item.valordesconto) }}</div>
          </div>

          <!-- Frete -->
          <div class="col-4" v-if="item.valorfrete">
            <div class="text-caption text-grey-7">Frete</div>
            <div class="text-caption">{{ formataNumero(item.valorfrete) }}</div>
          </div>

          <!-- Seguro -->
          <div class="col-4" v-if="item.valorseguro">
            <div class="text-caption text-grey-7">Seguro</div>
            <div class="text-bodycaption2">{{ formataNumero(item.valorseguro) }}</div>
          </div>

          <!-- Outras Despesas -->
          <div class="col-4" v-if="item.valoroutras">
            <div class="text-caption text-grey-7">Outras Despesas</div>
            <div class="text-caption">{{ formataNumero(item.valoroutras) }}</div>
          </div>

          <!-- Pedido -->
          <div v-if="item.pedido" class="col-6">
            <div class="text-caption text-grey-7">Pedido</div>
            <div class="text-caption">{{ item.pedido }}</div>
          </div>

          <!-- Pedido Item -->
          <div v-if="item.pedidoitem" class="col-6">
            <div class="text-caption text-grey-7">Item do Pedido</div>
            <div class="text-caption">{{ item.pedidoitem }}</div>
          </div>

          <!-- Negócio -->
          <div v-if="negocioUrl" class="col-6">
            <div class="text-caption text-grey-7">Negócio</div>
            <div class="text-caption">
              <a
                :href="negocioUrl"
                target="_blank"
                class="text-primary text-weight-medium"
                style="text-decoration: none"
              >
                {{ formataCodNegocio(item.codnegocio) }}
              </a>
            </div>
          </div>

          <!-- Devolução Percentual -->
          <div v-if="item.devolucaopercentual" class="col-6">
            <div class="text-caption text-grey-7">% Devolução</div>
            <div class="text-caption">{{ formataNumero(item.devolucaopercentual, 2) }}%</div>
          </div>

          <!-- Observações -->
          <div v-if="item.observacoes" class="col-12">
            <div class="text-caption text-grey-7">Observações</div>
            <div class="text-caption preserve-pre">{{ item.observacoes }}</div>
          </div>
        </div>
      </q-card-section>
      <q-separator />
      <q-card-section class="q-pa-none col-grow">
        <q-tabs
          dense
          size="xs"
          v-model="tab"
          class="text-grey-7"
          indicator-color="primary"
          active-bg-color="grey-3"
          inline-label
        >
          <q-tab label="Rural" name="rural" icon="agriculture" v-if="tabRural" />
          <q-tab label="ICMS" name="icms" icon="account_balance" />
          <q-tab label="IPI" name="ipi" icon="inventory" />
          <q-tab label="OUTROS" name="outros" icon="attach_money" />

          <template v-for="trib in item.tributos" :key="trib.codnotafiscalitemtributo">
            <q-tab
              :label="trib.tributo?.codigo"
              :name="'tributo-' + trib.codnotafiscalitemtributo"
              :icon="getEnteIcon(trib.tributo.ente)"
            />
          </template>
        </q-tabs>

        <q-separator />

        <q-tab-panels v-model="tab" animated>
          <q-tab-panel name="icms">
            <div class="row q-col-gutter-sm">
              <!-- NCM -->
              <div class="col-6">
                <div class="text-caption text-grey-7">NCM/CEST</div>
                <div class="text-caption ellipsis">
                  {{ formataNcm(item.produtoBarra?.ncm) }}
                  <template v-if="item.produtoBarra?.cest">
                    / {{ formataCest(item.produtoBarra?.cest) }}
                  </template>
                </div>
              </div>

              <!-- CSOSN -->
              <div v-if="item.csosn !== null" class="col-4">
                <div class="text-caption text-grey-7">CSOSN</div>
                <div class="text-caption ellipsis">{{ item.csosn }}</div>
              </div>

              <!-- ICMS CST -->
              <div v-if="item.icmscst !== null" class="col-2">
                <div class="text-caption text-grey-7">CST</div>
                <div class="text-caption">{{ item.icmscst }}</div>
              </div>

              <!-- CFOP -->
              <div v-if="item.codcfop !== null" class="col-4">
                <div class="text-caption text-grey-7">CFOP</div>
                <div>
                  <q-badge color="blue-grey-6" class="text-caption">
                    {{ item.codcfop }}
                    <q-tooltip v-if="item.cfop.cfop">{{ item.cfop.cfop }}</q-tooltip>
                  </q-badge>
                </div>
              </div>

              <!-- ICMS Base -->
              <div v-if="item.icmsbase" class="col-6">
                <div class="text-caption text-grey-7">Base</div>
                <div class="text-caption ellipsis">
                  {{ formataNumero(item.icmsbase) }}
                  <template v-if="item.icmsbasepercentual && item.icmsbasepercentual != 100">
                    ({{ formataNumero(item.icmsbasepercentual, 2) }}%)
                  </template>
                </div>
              </div>

              <!-- ICMS % -->
              <div v-if="item.icmspercentual" class="col-2">
                <div class="text-caption text-grey-7">Alíq</div>
                <div class="text-caption ellipsis">
                  {{ formataNumero(item.icmspercentual, 2) }}%
                </div>
              </div>

              <!-- ICMS Valor -->
              <div v-if="item.icmsvalor" class="col-4">
                <div class="text-caption text-grey-7">Valor</div>
                <div class="text-caption ellipsis text-weight-bold">
                  {{ formataNumero(item.icmsvalor) }}
                </div>
              </div>

              <!-- ICMS ST Base -->
              <div v-if="item.icmsstbase" class="col-6 col-sm-6">
                <div class="text-caption text-grey-7">Base ST</div>
                <div class="text-caption">{{ formataNumero(item.icmsstbase) }}</div>
              </div>

              <!-- ICMS ST % -->
              <div v-if="item.icmsstpercentual" class="col-2">
                <div class="text-caption text-grey-7">Alíq.</div>
                <div class="text-caption">{{ formataNumero(item.icmsstpercentual, 2) }}%</div>
              </div>

              <!-- ICMS ST Valor -->
              <div v-if="item.icmsstvalor" class="col-4">
                <div class="text-caption text-grey-7">Valor ST</div>
                <div class="text-caption text-weight-bold">
                  {{ formataNumero(item.icmsstvalor) }}
                </div>
              </div>
            </div>
          </q-tab-panel>

          <q-tab-panel name="ipi">
            <div class="row q-col-gutter-sm">
              <!-- IPI CST -->
              <div v-if="item.ipicst !== null" class="col-3">
                <div class="text-caption text-grey-7">CST</div>
                <div class="text-caption">{{ item.ipicst }}</div>
              </div>

              <!-- IPI Base -->
              <div v-if="item.ipibase" class="col-3">
                <div class="text-caption text-grey-7">Base</div>
                <div class="text-caption">{{ formataNumero(item.ipibase) }}</div>
              </div>

              <!-- IPI % -->
              <div v-if="item.ipipercentual" class="col-3">
                <div class="text-caption text-grey-7">Alíquota</div>
                <div class="text-caption">{{ formataNumero(item.ipipercentual, 2) }}%</div>
              </div>

              <!-- IPI Valor -->
              <div v-if="item.ipivalor" class="col-3">
                <div class="text-caption text-grey-7">Valor</div>
                <div class="text-caption text-weight-bold">{{ formataNumero(item.ipivalor) }}</div>
              </div>

              <!-- IPI Devolução Valor -->
              <div v-if="item.ipidevolucaovalor" class="col-3">
                <div class="text-caption text-grey-7">Vlr. Devolução</div>
                <div class="text-caption">{{ formataNumero(item.ipidevolucaovalor) }}</div>
              </div>
            </div>
          </q-tab-panel>

          <q-tab-panel name="outros">
            <div class="row q-col-gutter-sm">
              <!-- PIS -->

              <!-- PIS Base -->
              <div v-if="item.pisbase" class="col-3">
                <div class="text-caption text-grey-7">Base</div>
                <div class="text-caption">{{ formataNumero(item.pisbase) }}</div>
              </div>

              <!-- PIS % -->
              <div v-if="item.pispercentual" class="col-3">
                <div class="text-caption text-grey-7">Alíquota</div>
                <div class="text-caption">{{ formataNumero(item.pispercentual, 2) }}%</div>
              </div>

              <!-- PIS Valor -->
              <div v-if="item.pisvalor" class="col-3">
                <div class="text-caption text-grey-7">PIS</div>
                <div class="text-caption text-weight-bold">{{ formataNumero(item.pisvalor) }}</div>
              </div>

              <!-- PIS CST -->
              <div v-if="item.piscst !== null" class="col-3">
                <div class="text-caption text-grey-7 ellipsis">CST PIS</div>
                <div class="text-caption">{{ item.piscst }}</div>
              </div>

              <!-- COFINS -->

              <!-- COFINS Base -->
              <div v-if="item.cofinsbase" class="col-3">
                <div class="text-caption text-grey-7">Base</div>
                <div class="text-caption">{{ formataNumero(item.cofinsbase) }}</div>
              </div>

              <!-- COFINS % -->
              <div v-if="item.cofinspercentual" class="col-3">
                <div class="text-caption text-grey-7">Alíquota</div>
                <div class="text-caption">{{ formataNumero(item.cofinspercentual, 2) }}%</div>
              </div>

              <!-- COFINS Valor -->
              <div v-if="item.cofinsvalor" class="col-3">
                <div class="text-caption text-grey-7">COFINS</div>
                <div class="text-caption text-weight-bold">
                  {{ formataNumero(item.cofinsvalor) }}
                </div>
              </div>

              <!-- COFINS CST -->
              <div v-if="item.cofinscst !== null" class="col-3">
                <div class="text-caption text-grey-7 ellipsis">CST Cofins</div>
                <div class="text-caption">{{ item.cofinscst }}</div>
              </div>

              <!-- IRPJ -->

              <!-- IRPJ Base -->
              <div v-if="item.irpjbase" class="col-3">
                <div class="text-caption text-grey-7">Base</div>
                <div class="text-caption">{{ formataNumero(item.irpjbase) }}</div>
              </div>

              <!-- IRPJ % -->
              <div v-if="item.irpjpercentual" class="col-3">
                <div class="text-caption text-grey-7">Alíquota</div>
                <div class="text-caption">{{ formataNumero(item.irpjpercentual, 2) }}%</div>
              </div>

              <!-- IRPJ Valor -->
              <div v-if="item.irpjvalor" class="col-6">
                <div class="text-caption text-grey-7">IRPJ</div>
                <div class="text-caption text-weight-bold">
                  {{ formataNumero(item.irpjvalor) }}
                </div>
              </div>

              <!-- CSLL -->

              <!-- CSLL Base -->
              <div v-if="item.csllbase" class="col-3">
                <div class="text-caption text-grey-7">Base</div>
                <div class="text-caption">{{ formataNumero(item.csllbase) }}</div>
              </div>

              <!-- CSLL % -->
              <div v-if="item.csllpercentual" class="col-3">
                <div class="text-caption text-grey-7">Alíquota</div>
                <div class="text-caption">{{ formataNumero(item.csllpercentual, 2) }}%</div>
              </div>

              <!-- CSLL Valor -->
              <div v-if="item.csllvalor" class="col-6">
                <div class="text-caption text-grey-7">CSLL</div>
                <div class="text-caption text-weight-bold">
                  {{ formataNumero(item.csllvalor) }}
                </div>
              </div>
            </div>
          </q-tab-panel>

          <template v-for="trib in item.tributos" :key="trib.codnotafiscalitemtributo">
            <q-tab-panel :name="'tributo-' + trib.codnotafiscalitemtributo">
              <div class="row q-col-gutter-sm">
                <!-- CST -->
                <div class="col-2">
                  <div class="text-caption text-grey-7">CST</div>
                  <div class="text-caption">{{ trib.cst }}</div>
                </div>

                <!-- Base -->
                <div class="col-4">
                  <div class="text-caption text-grey-7">Base</div>
                  <div class="text-caption">
                    {{ formataNumero(trib.base) }}
                  </div>
                </div>

                <!-- Alíquota -->
                <div class="col-2">
                  <div class="text-caption text-grey-7">Alíq</div>
                  <div class="text-caption">{{ formataNumero(trib.aliquota, 2) }}%</div>
                </div>

                <!-- Valor -->
                <div class="col-4">
                  <div class="text-caption text-grey-7">Valor</div>
                  <div class="text-caption text-weight-bold">{{ formataNumero(trib.valor) }}</div>
                </div>

                <!-- geracredito -->
                <div v-if="trib.geracredito" class="col-2">
                  <div class="text-caption text-grey-7">Créd</div>
                  <div class="text-caption">{{ trib.geracredito ? 'Sim' : 'Não' }}</div>
                </div>

                <!-- valorcredito -->
                <div v-if="trib.valorcredito" class="col-4">
                  <div class="text-caption text-grey-7">Crédito</div>
                  <div class="text-caption text-weight-bold">
                    {{ formataNumero(trib.valorcredito) }}
                  </div>
                </div>

                <!-- ENTE -->
                <div class="col-2">
                  <div class="text-caption text-grey-7">Ente</div>
                  <div class="text-caption ellipsis row items-center q-gutter-xs">
                    <span>{{ trib.tributo.ente.charAt(0) }}</span>
                  </div>
                </div>

                <!-- CCLASSTRIB -->
                <div v-if="trib.cclasstrib !== null" class="col-4">
                  <div class="text-caption text-grey-7">Classif</div>
                  <div class="text-caption">{{ trib.cclasstrib }}</div>
                </div>

                <!-- basereducao -->
                <div v-if="trib.basereducao" class="col-3">
                  <div class="text-caption text-grey-7">Base</div>
                  <div class="text-caption">
                    {{ formataNumero(trib.basereducao) }}
                    <template v-if="trib.basereducaopercentual">
                      ({{ formataNumero(trib.basereducaopercentual, 2) }}%)
                    </template>
                  </div>
                </div>

                <!-- beneficiocodigo -->
                <div v-if="trib.beneficiocodigo" class="col-3">
                  <div class="text-caption text-grey-7">Benefício</div>
                  <div class="text-caption text-weight-bold">{{ trib.beneficiocodigo }}</div>
                </div>

                <!--fundamentolegal -->
                <div v-if="trib.fundamentolegal" class="col-3">
                  <div class="text-caption text-grey-7">Fundamento</div>
                  <div class="text-caption text-weight-bold">{{ trib.fundamentolegal }}</div>
                </div>
              </div>
            </q-tab-panel>
          </template>

          <q-tab-panel v-if="tabRural" name="rural">
            <div class="row q-col-gutter-sm">
              <!-- NEGATIVA SEFAZ -->
              <div v-if="item.certidaosefazmt" class="col-12">
                <q-badge color="warning" class="text-caption text-grey-9">
                  Destaca Certidão SEFAZ/MT
                </q-badge>
              </div>

              <!-- Funrural -->
              <div v-if="item.funruralpercentual" class="col-6">
                <div class="text-caption text-grey-7">Alíquota</div>
                <div class="text-caption">{{ formataNumero(item.funruralpercentual, 2) }}%</div>
              </div>

              <div v-if="item.funruralvalor" class="col-6">
                <div class="text-caption text-grey-7">Funrural</div>
                <div class="text-caption">{{ formataNumero(item.funruralvalor) }}</div>
              </div>

              <!-- Senar -->
              <div v-if="item.senarpercentual" class="col-6">
                <div class="text-caption text-grey-7">Alíquota</div>
                <div class="text-caption">{{ formataNumero(item.senarpercentual, 2) }}%</div>
              </div>

              <div v-if="item.senarvalor" class="col-6">
                <div class="text-caption text-grey-7">Senar</div>
                <div class="text-caption">{{ formataNumero(item.senarvalor) }}</div>
              </div>

              <!-- Fethab -->
              <div v-if="item.fethabkg" class="col-6">
                <div class="text-caption text-grey-7">Por Kg</div>
                <div class="text-caption">{{ formataNumero(item.fethabkg, 6) }}</div>
              </div>

              <!-- Fethab Valor -->
              <div v-if="item.fethabvalor" class="col-6">
                <div class="text-caption text-grey-7">Fethab</div>
                <div class="text-caption">{{ formataNumero(item.fethabvalor) }}</div>
              </div>

              <!-- IAgro -->
              <div v-if="item.iagrokg" class="col-6">
                <div class="text-caption text-grey-7">Por Kg</div>
                <div class="text-caption">{{ formataNumero(item.iagrokg, 6) }}</div>
              </div>

              <div v-if="item.iagrovalor" class="col-6">
                <div class="text-caption text-grey-7">IAgro</div>
                <div class="text-caption">{{ formataNumero(item.iagrovalor) }}</div>
              </div>
            </div>
          </q-tab-panel>
        </q-tab-panels>
      </q-card-section>
      <q-separator v-if="!notaBloqueada" />

      <q-card-actions align="right" v-if="!notaBloqueada">
        <q-btn
          flat
          dense
          round
          icon="edit"
          color="primary"
          size="sm"
          :disable="notaBloqueada"
          :to="{
            name: 'nota-fiscal-item-edit',
            params: {
              codnotafiscal: item.codnotafiscal,
              codnotafiscalitem: item.codnotafiscalprodutobarra,
            },
          }"
        >
          <q-tooltip>Editar</q-tooltip>
        </q-btn>
        <q-btn
          flat
          dense
          round
          icon="delete"
          color="negative"
          size="sm"
          :disable="notaBloqueada"
          @click="emit('delete', item)"
        >
          <q-tooltip>Excluir</q-tooltip>
        </q-btn>
      </q-card-actions>
    </q-card>
  </div>
</template>
