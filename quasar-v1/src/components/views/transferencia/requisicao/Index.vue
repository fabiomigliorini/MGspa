<template>
  <mg-layout  back-path="/">
    <template slot="title">
      Requisição de Trasnferência
    </template>
    <div slot="content">
      <div class="layout-padding">
        <div class="row justify-center">
          <div class="col-xs-12 col-sm-6 col-md-4">
            <div class="row">
              <div class="text-subtitle1">Conferir Produtos de</div>
            </div>

            <!-- Codestoquelocal -->
            <div class="row">
              <div class="col">
                <mg-select-estoque-local label="Local" v-model="data.codestoquelocal"/>
              </div>
            </div>

            <!-- Tipo - Fisico/Fiscal -->
            <div class="row">
              <div class="col">
                <q-select label="Tipo" v-model="data.fiscal" :options="tipos"/>
              </div>
            </div>

            <!-- Codigo da Marca -->
            <div class="row">
              <div class="col">
                <mg-autocomplete-marca label="Marca" v-model="data.codmarca" :init="data.codmarca"/>
              </div>
            </div>

            <!-- Data para jogar o movimento do estoque -->
            <div class="row">
              <div class="col">
                <q-input label="Ajustar Estoque em" v-model="data.data" :rules="['data.data']">
                  <template v-slot:prepend>
                    <q-icon name="event" class="cursor-pointer">
                      <q-popup-proxy transition-show="scale" transition-hide="scale">
                        <q-date mask="YYYY-MM-DD HH:mm" v-model="data.data" />
                      </q-popup-proxy>
                    </q-icon>
                  </template>

                  <template v-slot:append>
                    <q-icon name="access_time" class="cursor-pointer">
                      <q-popup-proxy transition-show="scale" transition-hide="scale">
                        <q-time mask="YYYY-MM-DD HH:mm" v-model="data.data" format24h/>
                      </q-popup-proxy>
                    </q-icon>
                  </template>

                 <!-- <template v-slot:append>
                    <q-icon name="event" class="cursor-pointer">
                      <q-popup-proxy ref="qDateProxy" transition-show="scale" transition-hide="scale">
                        <q-date minimal mask="DD-MM-YYYY HH:ss" v-model="data.data" @input="() => $refs.qDateProxy.hide()" />
                      </q-popup-proxy>
                    </q-icon>
                  </template>-->
                </q-input>
              </div>
            </div>

          </div>
          <q-page-sticky corner="bottom-right" :offset="[32, 32]">
            <q-btn round color="primary" icon="done" @click.prevent="iniciarConferencia()" />
          </q-page-sticky>
        </div>
      </div>
    </div>
  </mg-layout>
</template>

<script>

import MgSelectEstoqueLocal from '../../../utils/select/MgSelectEstoqueLocal'
import MgLayout from '../../../../layouts/MgLayout'
import MgErrosValidacao from '../../../utils/MgErrosValidacao'
import MgAutocompleteMarca from '../../../utils/autocomplete/MgAutocompleteMarca'

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
            value: 0
          },
          {
            label: 'Fiscal',
            value: 1
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
      var ret = true;
      if (this.data.codestoquelocal == null) {
        this.erros.codestoquelocal = ['Selecione o Local'];
        ret = false
      } else {
        this.erros.codestoquelocal = []
      }

      if (this.data.codmarca == null) {
        this.erros.codmarca = ['Selecione a Marca'];
        ret = false
      } else {
        this.erros.codmarca = []
      }

      if (this.data.fiscal == null) {
        this.erros.fiscal = ['Selecione o Tipo'];
        ret = false
      } else {
        this.erros.fiscal = []
      }

      if (this.data.data == null) {
        this.erros.data = ['Selecione a Data'];
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

      this.$router.push('/transferencia/requisicao/nova/'
        + this.data.codestoquelocal + '/'
        + this.data.codmarca + '/'
        + this.data.fiscal + '/'
        + this.data.data
      )
    },
  },
  created () {
    if (this.data.data == null) {
      this.data.data = this.moment().format('YYYY-MM-DDTHH:mm')
    }
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style>
</style>
