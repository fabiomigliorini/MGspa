<template>
  <mg-layout drawer back-path="/estoque-saldo-conferencia/">

    <template slot="title">
      Notas Fiscais de Terceiro
    </template>

    <div slot="drawer">
      <q-list-header>Filtros</q-list-header>
    </div>


    <div slot="content">

      <!-- <template v-if="carregado"> -->
      <template>
        <h1>aqui</h1>
      </template>

    </div>
  </mg-layout>
</template>

<script>
import MgSelectEstoqueLocal from '../../utils/select/MgSelectEstoqueLocal'
import MgLayout from '../../../layouts/MgLayout'
import MgErrosValidacao from '../../utils/MgErrosValidacao'
import MgAutocompleteMarca from '../../utils/autocomplete/MgAutocompleteMarca'

export default {
  name: 'nfe-terceiro-detalhes-nfe',
  components: {
    MgLayout,
    MgSelectEstoqueLocal,
    MgErrosValidacao,
    MgAutocompleteMarca
  },
  data() {
    return {
      filter: {},
      data: {},
      carregado: false,
    }
  },
  watch: {
    // observa filtro, sempre que alterado chama a api
    filter: {
      handler: function (val, oldVal) {},
      deep: true
    }

  },
  methods: {
    carregaNota: function() {
      let params= {
        chave: this.filter.chave
      }
      console.log(params)
      this.$axios.get('nfe-terceiro/busca-nfeterceiro',{params}).then(function(request){
        console.log(request)
      }).catch(function(error) {
        console.log(error)
      })
    },
  },
  created() {
    this.filter.chave = this.$route.params.chave
    this.carregaNota();
  }
}
</script>
