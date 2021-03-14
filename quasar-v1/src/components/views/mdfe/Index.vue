<template xmlns:v-slot="http://www.w3.org/1999/XSL/Transform">
<mg-layout back-path="/">
  <!-- Título da Página -->
  <template slot="title">
    MDFe's
  </template>

  <!-- Conteúdo Princial (Meio) -->
  <div slot="content" v-if="state">
    <!--
    <template>
      <q-tabs v-model="state.tab" class="bg-primary text-white shadow-2">
        <q-tab name="1" label="Digitação" />
        <q-tab name="2" label="Transmitidas" />
        <q-tab name="3" label="Autorizadas" />
        <q-tab name="4" label="Não Autorizadas" />
        <q-tab name="5" label="Encerradas" />
        <q-tab name="9" label="Canceladas" />
      </q-tabs>
    </template>
    -->
    <template>
      <q-list separator>
        <template v-for="mdfe in state.mdfes.data">
          <q-item clickable v-ripple :to="'/mdfe/' + mdfe.codmdfe">
            <q-item-section avatar>
              <q-avatar :color="statusColor(mdfe.codmdfestatus)">
                {{mdfe.mdfestatussigla}}
              </q-avatar>
            </q-item-section>
            <q-item-section>
              <q-item-label>
                {{ formataCodigo(mdfe.codmdfe) }}
                {{mdfe.filial}}
                <template v-if="mdfe.numero">
                  Nº {{mdfe.numero}}
                </template>
                <template v-if="mdfe.inicioviagem">
                  <abbr :title="moment(mdfe.inicioviagem).format('llll')">
                    {{ moment(mdfe.inicioviagem).fromNow() }}
                  </abbr>
                </template>
              </q-item-label>
              <q-item-label caption>
                <template v-for="veiculo in mdfe.MdfeVeiculoS">
                  {{veiculo.placa}}
                </template>
              </q-item-label>
              <q-item-label caption>
                <template v-for="nfe in mdfe.MdfeNfeS">
                  NFe {{ formataChave(nfe.nfechave) }}
                </template>
              </q-item-label>
            </q-item-section>
          </q-item>
        </template>
      </q-list>
    </template>

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-fab color="primary" icon="add" direction="up">
        <q-fab-action color="primary" label="NFe" icon="fas fa-barcode" to="/mdfe/create-nfechave" />
        <q-fab-action color="primary" label="Manual" icon="create" to="/mdfe/create" />
      </q-fab>
      <q-btn fab icon="add" color="primary" to="/mdfe/tipo/create" v-if="state.tab == 'tipo'" />
      <q-btn fab icon="add" color="primary" to="/mdfe/conjunto/create" v-if="state.tab == 'conjunto'" />
      <q-btn fab icon="add" color="primary" to="/mdfe/create" v-if="state.tab == 'mdfe'" />
    </q-page-sticky>

  </div>

</mg-layout>
</template>

<script>
import MgLayout from '../../../layouts/MgLayout'
import MgNoData from '../../utils/MgNoData'
import {
  debounce
} from 'quasar'

export default {

  components: {
    MgLayout,
    MgNoData
  },

  data() {
    return {
      tab: 'tipo',
      data: [],
      page: 1,
      filter: {}, // Vem do Store
      loading: true,
    }
  },

  watch: {

    // observa filtro, sempre que alterado chama a api
    filter: {
      handler: function(val, oldVal) {
        this.page = 1
        //this.loadData(false, null)
      },
      deep: true
    }

  },

  methods: {

    statusColor: function (codmdfestatus) {
      var ret = this.$store.state.mdfe.colorsStatus.find(el => el.value === codmdfestatus)
      if (ret) {
        return ret.color
      }
      return null;
    },

    formataCodigo: function(codigo) {
      if (codigo == null) {
        return null;
      }
      return '#' + this.numeral(parseFloat(codigo)).format('00000000');
    },

    formataChave: function(chave){
      if (chave == undefined) {
        return null;
      }
      if(chave.length !== 44) {
        return chave;
      } else {
    		return chave.split("").reduceRight(function(elemento, anterior){
    			var temp = anterior + elemento;
    		    if(temp.replace(/\s/g, "").length % 4 === 0) return " " + temp;
    		    else return temp;
    		});
    	}
    },

    loadMdfe: debounce(function(concat, done) {
      var vm = this
      vm.$axios.get('mdfe').then(response => {
        vm.state.mdfes = response.data
      })
    }, 500),

  },

  // na criacao, busca filtro do Vuex
  created() {
    this.state = this.$store.state.mdfe
    this.loadMdfe();
  }

}
</script>

<style>
</style>
