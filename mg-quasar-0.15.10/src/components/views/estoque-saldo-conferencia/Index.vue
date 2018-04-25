<template>
  <mg-layout>

    <q-btn flat round slot="menu" @click="$router.push('/')">
      <q-icon name="arrow_back" />
    </q-btn>

    <q-btn flat round icon="done" slot="menuRight" @click.prevent="iniciarConferencia()" />

    <template slot="title">
      Conferência de estoque
    </template>
    <div slot="content">
      <div class="layout-padding">

        <div class="row gutter-x-sm gutter-y-lg">
          <div class="col-12 col-md-4">

            <div class="row">
              <div class="q-caption">Conferir Produtos de</div>
            </div>

            <!-- Codestoquelocal -->
            <div class="row">
              <div class="col">
                <mg-select-estoque-local
                  label="Local"
                  v-model="data.codestoquelocal"
                  required>
                </mg-select-estoque-local>
                <mg-erros-validacao :erros="erros.codestoquelocal"></mg-erros-validacao>
              </div>
            </div>

            <!-- Tipo - Fisico/Fiscal -->
            <div class="row">
              <div class="col">
                <q-select
                float-label="Tipo"
                v-model="data.fiscal"
                :options="tipos"
                />
                <mg-erros-validacao :erros="erros.fiscal"></mg-erros-validacao>
              </div>
            </div>

            <!-- Codigo da Marca -->
            <div class="row">
              <div class="col">
                <mg-autocomplete-marca placeholder="Marca" v-model="data.codmarca" :init="data.codmarca"></mg-autocomplete-marca>
                <mg-erros-validacao :erros="erros.codmarca"></mg-erros-validacao>
              </div>
            </div>

          </div>
          <div class="col-12 col-md-6">

            <div class="row">
              <div class="q-caption">Jogar alteraçao de Estoque em</div>
              <br />
            </div>

            <div class="row">
              <div class="col">
                <q-datetime-picker v-model="data.data" type="datetime" stack-label="Ajustar estoquem em"/>

                <!--
                <q-input v-model="data.data" stack-label="Ajustar estoquem em" type="datetime-local"/>
                -->
                <mg-erros-validacao :erros="erros.data"></mg-erros-validacao>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </mg-layout>
</template>

<script>

import MgSelectEstoqueLocal from '../../utils/select/MgSelectEstoqueLocal'
import MgLayout from '../../../layouts/MgLayout'
import MgErrosValidacao from '../../utils/MgErrosValidacao'
import MgAutocompleteMarca from '../../utils/autocomplete/MgAutocompleteMarca'

export default {
  name: 'estoque-saldo-conferencia-index',
  components: {
    MgLayout,
    MgSelectEstoqueLocal,
    MgErrosValidacao,
    MgAutocompleteMarca
  },
  data () {
    return {
      tipos: [
          {
            label: 'Fisico',
            value: 1
          },
          {
            label: 'Fiscal',
            value: 0
          }
      ],
      erros: {
        codestoquelocal: []
      }
    }
  },
  computed: {
    data: {
      get () {
        return this.$store.state.estoqueSaldoConferencia.estoqueSaldoConferenciaState
      }
    }
  },
  methods: {
    validaCampos: function () {
      var ret = true
      if (this.data.codestoquelocal == null) {
        this.erros.codestoquelocal = ['Selecione o Local']
        ret = false
      } else {
        this.erros.codestoquelocal = []
      }

      if (this.data.codmarca == null) {
        this.erros.codmarca = ['Selecione a Marca']
        ret = false
      } else {
        this.erros.codmarca = []
      }

      if (this.data.fiscal == null) {
        this.erros.fiscal = ['Selecione o Tipo']
        ret = false
      } else {
        this.erros.fiscal = []
      }

      if (this.data.data == null) {
        this.erros.data = ['Selecione a Data']
        ret = false
      } else {
        this.erros.data = []
      }

      return ret

    },
    iniciarConferencia: function () {

      if (this.validaCampos() == false) {
        return
      }

      let params = [
        this.data.codestoquelocal,
        this.data.codmarca,
        this.data.fiscal,
        this.data.data
      ]

      this.$router.push('/estoque-saldo-conferencia/listagem/'
        + this.data.codestoquelocal + '/'
        + this.data.codmarca + '/'
        + this.data.fiscal + '/'
        + this.data.data
      )
    },
    dadosConferencia: function (codestoquelocal, codmarca, fiscal) {
      /*
      let params = {
        fields:[
          'estoquelocal',
          'marca',
          'fiscal'
        ]
      }
      this.$axios.get('estoque-local/' + codestoquelocal, { params }).then(function (request) {
        this.data.estoquelocal = request.data.estoquelocal
        params = {
          fields:['marca']
        }
      }).catch(function (error) {
        console.log(error.response)
      })
      */
    }
  },
  mounted () {
    /*console.log(this.data)*/
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style>
</style>
