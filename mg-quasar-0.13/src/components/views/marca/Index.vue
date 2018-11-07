<template>
  <mg-layout drawer>

    <template slot="title">
      Marcas
    </template>

    <template slot="drawer">

      <div class="list">

        {{ numeral(1234567890).format('0,0.[000]') }}

        <br>
        {{ numeral(1234567890.12345).format() }}
        <br>
        {{ numeral(10000000000000.12345).format('0,0 b') }}
        <br>
        {{ numeral(1111111111.12345).format('0.[0] a') }}
        <br>
        {{ numeral(1).format('0 o') }}
        <br>
        {{ numeral(.12).format('0 %') }}

        <form>

          <div class="item three-lines">
            <i class="item-primary">search</i>
            <div class="item-content">
              <div class="floating-label">
                <input required class="full-width" v-model="filter.marca">
                <label>Descrição</label>
              </div>
            </div>
          </div>

          <div class="list-label">Ordenar Por</div>

          <label class="item">
            <i class="item-primary">trending_up</i>
            <div class="item-content has-secondary">
              Vendas
            </div>
            <div class="item-secondary">
              <q-radio v-model="filter.sort" val="abcposicao"></q-radio>
            </div>
          </label>

          <label class="item">
            <i class="item-primary">sort_by_alpha</i>
            <div class="item-content has-secondary">
              Descrição
            </div>
            <div class="item-secondary">
              <q-radio v-model="filter.sort" val="marca"></q-radio>
            </div>
          </label>

          <div class="list-label">Estoque</div>

          <label class="item">
            <i class="item-primary">arrow_upward</i>
            <div class="item-content has-secondary">
              Sobrando
            </div>
            <div class="item-secondary">
              <q-toggle v-model="filter.sobrando"></q-toggle>
            </div>
          </label>

          <label class="item">
            <i class="item-primary">arrow_downward</i>
            <div class="item-content has-secondary">
              Faltando
            </div>
            <div class="item-secondary">
              <q-toggle v-model="filter.faltando"></q-toggle>
            </div>
          </label>

          <div class="list-label">Curva ABC</div>

          <div class="item two-lines">
            <i class="item-primary">star</i>
            <div class="item-content">
              <q-double-range
                v-model="filter.abccategoria"
                label
                markers
                snap
                :min="0"
                :max="3"
                :step="1"
              ></q-double-range>
            </div>
          </div>

          <div class="list-label">Ativos</div>

          <label class="item">
            <i class="item-primary">thumb_up</i>
            <div class="item-content has-secondary">
              Ativos
            </div>
            <div class="item-secondary">
              <q-radio v-model="filter.inativo" val="1"></q-radio>
            </div>
          </label>

          <label class="item">
            <i class="item-primary">thumb_down</i>
            <div class="item-content has-secondary">
              Inativos
            </div>
            <div class="item-secondary">
              <q-radio v-model="filter.inativo" val="2"></q-radio>
            </div>
          </label>

          <label class="item">
            <i class="item-primary">thumbs_up_down</i>
            <div class="item-content has-secondary">
              Ativos e Inativos
            </div>
            <div class="item-secondary">
              <q-radio v-model="filter.inativo" val="9"></q-radio>
            </div>
          </label>
        </form>
      </div>

    </template>

    <div slot="content">

      <div class="list striped no-border item-delimiter" v-if="data.length > 0">
        <q-infinite-scroll :handler="refresher">
          <template v-for="item in data">
            <div class="item item-link two-lines" v-link="'/marca/' + item.codmarca">
              <img class="item-primary thumbnail hoverable-2" v-if="item.imagem" :src="item.imagem.url">
              <img class="item-primary thumbnail hoverable-2" v-else>
              <div class="item-content">
                <div class="row">
                  <div class="gt-sm-width-1of4">
                    {{ item.marca }}
                  </div>
                  <div class="gt-sm gt-sm-width-1of4 text-grey">
                    <span v-if="item.itensabaixominimo > 0">
                      {{ item.itensabaixominimo }} <i>arrow_downward</i>
                    </span>
                    <span v-if="item.itensacimamaximo > 0">
                       <i>arrow_upward</i>
                      {{ item.itensacimamaximo }}
                    </span>
                  </div>
                  <div class="gt-sm gt-sm-width-1of4 text-grey">
                  </div>
                  <div class="auto">
                  </div>
                  <div>
                    <q-rating
                      disable
                      class="orange"
                      v-model="item.abccategoria"
                      :max="3"
                    ></q-rating>
                  </div>
                </div>
                <div class="row">
                  <div class="gt-sm-width-1of4">
                    #{{ parseInt(item.codmarca).toLocaleString('pt-BR', { minimumIntegerDigits: 8, useGrouping: false }) }}
                  </div>
                  <div class="gt-sm gt-sm-width-1of4 text-grey">
                    <i>date_range</i>
                    {{ item.estoqueminimodias }} à
                    {{ item.estoquemaximodias }} Dias
                  </div>
                  <div class="gt-sm gt-sm-width-1of4 text-grey">
                    <span v-if="item.dataultimacompra" class="text-grey">
                      <i>add_shopping_cart</i>
                       {{ moment(item.dataultimacompra).fromNow() }}
                    </span>
                  </div>
                  <div class="auto">
                  </div>
                  <div>
                    {{ parseFloat(item.vendaanopercentual).toLocaleString('pt-BR', {minimumFractionDigits: 4, maximumFractionDigits: 4}) }}%
                    <template v-if="item.abcposicao">
                       ({{ parseInt(item.abcposicao).toLocaleString('pt-BR') }}&deg;)
                    </template>
                    <template v-else>
                      (&#8212;)
                    </template>
                  </div>
                </div>
              </div>
            </div>
          </template>
        </q-infinite-scroll>
      </div>
      <mg-no-data v-else-if="!loading" class="layout-padding"></mg-no-data>

    </div>

  </mg-layout>
</template>

<script>

import MgLayout from '../../layouts/MgLayout'
import MgNoData from '../../utils/MgNoData'
import { Utils } from 'quasar'

export default {

  components: {
    MgLayout,
    MgNoData
  },

  data () {
    return {
      data: [],
      page: 1,
      filter: {}, // Vem do Store
      loading: true
    }
  },

  watch: {
    filter: {
      handler: function (val, oldVal) {
        this.page = 1
        this.loadData(false, null)
      },
      deep: true
    }
  },

  methods: {

    refresher (index, done) {
      this.page++
      this.loadData(true, done)
    },

    loadData: Utils.debounce(function (concat, done) {
      this.$store.commit('filter/marca', this.filter)
      var vm = this
      var params = this.filter
      params.page = this.page
      this.loading = true
      window.axios.get('marca', {
        params
      }).then(response => {
        if (concat) {
          vm.data = vm.data.concat(response.data.data)
        }
        else {
          vm.data = response.data.data
        }
        this.loading = false
        if (done) {
          done()
        }
      })
    }, 500),

    go (id) {
      this.$router.push('/marca/' + id)
    }

  },

  created () {
    this.filter = this.$store.getters['filter/marca']
  }

}
</script>

<style>
</style>
