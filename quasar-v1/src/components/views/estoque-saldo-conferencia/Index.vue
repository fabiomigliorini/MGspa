<template>
  <mg-layout  back-path="/">
    <template slot="title">
      Conferência de estoque
    </template>
    <div slot="content">
      <div class="row q-pa-md justify-center">
        <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">

          <div class="text-subtitle2 text-grey-7 col-12">Conferir Produtos de</div>

          <!-- Codestoquelocal -->
          <div class="col-12">
            <mg-select-estoque-local label="Local" v-model="data.codestoquelocal" :loadData="loadFields"/>
          </div>

          <!-- Tipo - Fisico/Fiscal -->
          <div class="col-12">
            <q-select label="Tipo" v-model="data.fiscal" :options="tipos" map-options/>
          </div>

          <!-- Codigo da Marca -->
          <div class="col-12">
            <mg-autocomplete-marca label="Marca" v-model="data.codmarca" :init="data.codmarca"/>
          </div>

          <!-- Data para jogar o movimento do estoque -->
          <div class="col-12">
            <q-input label="Ajustar Estoque em" v-model="data.data" mask="##/##/####" :rules="['data.data']">
              <template v-slot:append>
                <q-icon name="event" class="cursor-pointer">
                  <q-popup-proxy ref="qDateProxy" transition-show="scale" transition-hide="scale">
                    <q-date mask="DD-MM-YYYY" v-model="data.data" @input="() => $refs.qDateProxy.hide()" minimal />
                  </q-popup-proxy>
                </q-icon>
              </template>
            </q-input>
          </div>

          <q-page-sticky corner="bottom-right" :offset="[32, 32]">
            <q-btn fab color="primary" icon="done" @click.prevent="iniciarConferencia()" />
          </q-page-sticky>

        </div>
      </div>

    </div>
  </mg-layout>
</template>

<script>
import MgSelectEstoqueLocal from '../../utils/select/MgSelectEstoqueLocal'
import MgLayout from '../../../layouts/MgLayout'
import MgAutocompleteMarca from '../../utils/autocomplete/MgAutocompleteMarca'
export default {
  name: 'estoque-saldo-conferencia-index',
  components: {
    MgLayout,
    MgSelectEstoqueLocal,
    MgAutocompleteMarca
  },
  data () {
    return {
      loadFields: false,
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

      this.$router.push('/estoque-saldo-conferencia/listagem/'
        + this.data.codestoquelocal + '/'
        + this.data.codmarca + '/'
        + this.data.fiscal + '/'
        + this.data.data
      )
    },
  },
  mounted () {
    this.loadFields = true;
    if (this.data.data == null) {
      this.data.data = this.moment().format('YYYY-MM-DDTHH:mm')
    }
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style>
</style>
