<template xmlns:v-slot="http://www.w3.org/1999/XSL/Transform">
  <mg-layout drawer back-path="/">
    <!-- Título da Página -->
    <template slot="title">
      Pix
    </template>

    <!-- Menu Drawer (Esquerda) -->
    <template slot="drawer">

      <q-item-label header>Pessoa</q-item-label>

      <!-- Filtro de Descricao -->
      <q-item>
        <q-item-section>
          <q-input outlined v-model="filter.nome" label="Nome" >
            <template v-slot:prepend>
              <q-icon name="search" />
            </template>
          </q-input>
        </q-item-section>
      </q-item>

      <!-- Filtro de Descricao -->
      <q-item>
        <q-item-section>
          <q-input outlined v-model="filter.cpf" label="CPF/CNPJ" >
            <template v-slot:prepend>
              <q-icon name="search" />
            </template>
          </q-input>
        </q-item-section>
      </q-item>

      <q-item-label header>Valor</q-item-label>

      <!-- Filtros de Valor -->
      <q-item>
        <q-item-section>
          <div class="row">
            <div class="col-6 q-pr-sm">
              <q-input
                outlined
                v-model="filter.valorinicial"
                label="De R$"
                input-class="text-right"
                type="number"
                step="0.01"
                />
            </div>
            <div class="col-6 q-pl-sm">
              <q-input
                outlined
                v-model="filter.valorfinal"
                label="Até R$"
                input-class="text-right"
                type="number"
                step="0.01"
                />
            </div>
          </div>
        </q-item-section>
      </q-item>


      <q-item-label header>Data</q-item-label>

      <q-item>
        <q-item-section>
          <q-input outlined v-model="filter.horarioinicial" mask="##/##/#### ##:##" input-class="text-center" label="De">
            <template v-slot:prepend>
              <q-icon name="event" class="cursor-pointer">
                <q-popup-proxy transition-show="scale" transition-hide="scale">
                  <q-date v-model="filter.horarioinicial" mask="DD/MM/YYYY HH:mm" />
                </q-popup-proxy>
              </q-icon>
            </template>
            <template v-slot:append>
              <q-icon name="access_time" class="cursor-pointer">
                <q-popup-proxy transition-show="scale" transition-hide="scale">
                  <q-time v-model="filter.horarioinicial" mask="DD/MM/YYYY HH:mm" format24h />
                </q-popup-proxy>
              </q-icon>
            </template>
          </q-input>
        </q-item-section>
      </q-item>

      <q-item>
        <q-item-section>
          <q-input outlined v-model="filter.horariofinal" mask="##/##/#### ##:##" input-class="text-center" label="Ate">
            <template v-slot:prepend>
              <q-icon name="event" class="cursor-pointer">
                <q-popup-proxy transition-show="scale" transition-hide="scale">
                  <q-date v-model="filter.horariofinal" mask="DD/MM/YYYY HH:mm" />
                </q-popup-proxy>
              </q-icon>
            </template>
            <template v-slot:append>
              <q-icon name="access_time" class="cursor-pointer">
                <q-popup-proxy transition-show="scale" transition-hide="scale">
                  <q-time v-model="filter.horariofinal" mask="DD/MM/YYYY HH:mm" format24h />
                </q-popup-proxy>
              </q-icon>
            </template>
          </q-input>
        </q-item-section>
      </q-item>


       <!-- NEGOCIO -->
      <q-item-label header>Vinculo Com Negócio</q-item-label>
      <q-separator/>

      <q-item dense>
        <q-item-section avatar>
          <q-icon name="done" />
        </q-item-section>
        <q-item-section>Com Vínculo</q-item-section>
        <q-item-section side>
          <q-radio v-model="filter.negocio" val="com" />
        </q-item-section>
      </q-item>

      <q-item dense>
        <q-item-section avatar>
          <q-icon name="close" />
        </q-item-section>
        <q-item-section>Sem Vínculo</q-item-section>
        <q-item-section side>
          <q-radio v-model="filter.negocio" val="sem" />
        </q-item-section>
      </q-item>

      <q-item dense>
        <q-item-section avatar>
          <q-icon name="all_inclusive" />
        </q-item-section>
        <q-item-section>todos</q-item-section>
        <q-item-section side>
          <q-radio v-model="filter.negocio" val="todos" />
        </q-item-section>
      </q-item>

      <q-item-label header>Ordenar Por</q-item-label>
      <q-separator/>

      <!-- Ordena Cronologicamente -->
      <q-item dense>
        <q-item-section avatar>
          <q-icon name="schedule" />
        </q-item-section>
        <q-item-section>Data</q-item-section>
        <q-item-section side>
          <q-radio v-model="filter.sort" val="horario" />
        </q-item-section>
      </q-item>

      <!-- Ordena por Valor -->
      <q-item dense>
        <q-item-section avatar>
          <q-icon name="attach_money" />
        </q-item-section>
        <q-item-section>Valor</q-item-section>
        <q-item-section side>
          <q-radio v-model="filter.sort" val="valor" />
        </q-item-section>
      </q-item>

      <!-- Ordena Alfabeticamente -->
      <q-item dense>
        <q-item-section avatar>
          <q-icon name="sort_by_alpha" />
        </q-item-section>
        <q-item-section>Nome</q-item-section>
        <q-item-section side>
          <q-radio v-model="filter.sort" val="nome" />
        </q-item-section>
      </q-item>




    </template>

    <!-- Conteúdo Princial (Meio) -->
    <div slot="content">

      <!-- Se tiver registros -->
      <q-list v-if="data.length > 0">

        <!-- Scroll infinito -->
        <q-infinite-scroll @load="loadMore" ref="infiniteScroll">

          <!-- Percorre registros  -->
          <template v-for="item in data">

            <!-- Link para detalhes -->
            <!-- <q-item :to="'/pix/' + item.codpix" > -->
            <q-item  >

              <!-- Imagem -->
              <q-item-section avatar>
                <q-avatar icon="hourglass_empty" color="red"  text-color="white" v-if="(item.codpix == null)" />
                <q-avatar icon="done" text-color="white" color="green" v-else />
              </q-item-section>

              <!-- Coluna 1 -->
              <q-item-section >
                <q-item-label>
                  <template v-if="item.codpix == null">
                    Aguardando Pagamento
                  </template>
                  <template v-else-if="item.nome == null">
                    Sem Identificação de Nome
                  </template>
                  <template v-else>
                    {{ item.nome }}
                  </template>
                </q-item-label>
                <q-item-label caption>
                  {{ formataCPF(item.cpf) }}
                  {{ formataCNPJ(item.cnpj) }}
                </q-item-label>
                <q-item-label caption>
                  {{ item.portador }}
                </q-item-label>
              </q-item-section>

              <q-item-section class="gt-xs">
                <q-item-label caption v-if="item.codnegocio" @click="abrirNegocio(item.codnegocio)" class="cursor-pointer">
                  <!-- <q-icon name="label" /> -->
                  Negócio #{{ numeral(item.codnegocio).format('00000000') }}
                </q-item-label>
                <q-item-label caption v-if="item.txid">
                  <!-- <q-icon name="label" /> -->
                  {{ item.txid }}
                </q-item-label>
                <q-item-label caption v-if="item.e2eid">
                  <!-- <q-icon name="label" /> -->
                  {{ item.e2eid }}
                </q-item-label>
              </q-item-section>

              <!-- Direita (Estrelas) -->
              <q-item-section avatar>
                <q-item-label>
                  <small class="text-grey">R$</small>
                  <b>
                    {{ numeral(item.valor).format('0,0.00') }}
                  </b>
                </q-item-label>
                <q-item-label caption>
                  <abbr :title="moment(item.horario).format('LLL')">
                    {{ moment(item.horario).fromNow() }}
                  </abbr>
                </q-item-label>
              </q-item-section>

            </q-item>
            <q-separator />

          </template>
          <template v-slot:loading>
            <div class="row justify-center q-my-md">
              <q-spinner-dots color="primary" size="40px" />
            </div>
          </template>
        </q-infinite-scroll>
        <div class="row" style="margin: 40px">
        </div>
      </q-list>

      <!-- Se não tiver registros -->
      <mg-no-data v-else-if="!loading" class="layout-padding"></mg-no-data>

      <q-page-sticky position="bottom-right" :offset="[18, 18]">
        <q-btn fab icon="refresh" color="primary" @click="refresh()" :loading="consultando" />
      </q-page-sticky>

    </div>

  </mg-layout>
</template>

<script>

import MgLayout from '../../../layouts/MgLayout'
import MgNoData from '../../utils/MgNoData'
import { debounce } from 'quasar'
import { QSpinnerDots } from 'quasar'

export default {

  components: {
    MgLayout,
    MgNoData,
    QSpinnerDots
  },

  data () {
    return {
      data: [],
      page: 1,
      filter: {}, // Vem do Store
      loading: true,
      consultando: false
    }
  },

  watch: {

    // observa filtro, sempre que alterado chama a api
    filter: {
      handler: function (val, oldVal) {
        this.page = 1
        this.loadData(false, null)
      },
      deep: true
    }

  },

  methods: {

    formataCPF(cpf) {
      if (cpf == null) {
        return cpf
      }
      cpf = cpf.toString().padStart(11, '0')
      return cpf.slice(0,3) + "." +
        cpf.slice(3,6) + "." +
        cpf.slice(6,9) + "-" +
        cpf.slice(9, 11)
    },

    formataCNPJ(cnpj) {
      if (cnpj == null) {
        return cnpj
      }
      cnpj = cnpj.toString().padStart(14, '0')
      return cnpj.slice(0,2) + "." +
        cnpj.slice(2,5) + "." +
        cnpj.slice(5,8) + "/" +
        cnpj.slice(8, 12) + "-" +
        cnpj.slice(12, 14)
    },

    abrirNegocio (codnegocio) {
      console.log(codnegocio)
      var win = window.open(process.env.MGSIS_URL + '/index.php?r=negocio/view&id=' + codnegocio, '_blank');
    },

    refresh: debounce(function () {
      // inicializa variaveis
      var vm = this

      // monta URL pesquisa
      var url = 'pix/consultar'
      vm.consultando = true

      // faz chamada api
      vm.$axios.post(url).then(response => {
        vm.consultando = false
        vm.page = 1
        vm.loadData(false, vm.done)
        console.log(response)
      })
    }, 500),

    // scroll infinito - carregar mais registros
    loadMore (index, done) {
      this.page++
      this.loadData(true, done)
    },

    // carrega registros da api
    loadData: debounce(function (concat, done) {
      // salva no Vuex filtro da pix
      this.$store.commit('filtroPix/updateFiltroPix', this.filter)

      // inicializa variaveis
      var vm = this
      var params = this.filter
      params.page = this.page
      console.log(this.page)
      this.loading = true

      // faz chamada api
      vm.$axios.get('pix', {
        params
      }).then(response => {
        // Se for para concatenar, senao inicializa
        if (concat) {
          vm.data = vm.data.concat(response.data.data)
        }
        else {
          vm.data = response.data.data
        }
        // console.log(vm.data);

        // Desativa Scroll Infinito se chegou no fim
        if (vm.$refs.infiniteScroll) {
          if (response.data.data.length === 0) {
            vm.$refs.infiniteScroll.stop()
          }
          else {
            vm.$refs.infiniteScroll.resume()
          }
        }

        // despix flag de carregando
        this.loading = false

        // Executa done do scroll infinito
        if (done) {
          done()
        }
      })
    }, 500)

  },

  // na criacao, busca filtro do Vuex
  created () {
    this.filter = this.$store.state.filtroPix
  }

}
</script>

<style>
</style>
