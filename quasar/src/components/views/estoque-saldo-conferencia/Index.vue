<template>
  <mg-layout  back-path="/">
    <template slot="title">
      Conferência de estoque
    </template>
    <div slot="content">
      <div class="row q-pa-md justify-center">
        <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3 q-gutter-md">

          <div class="text-subtitle2 text-grey-7 col-12">Conferir Produtos de</div>

          <!-- Codestoquelocal -->
          <div class="col-12">
            <mg-select-estoque-local label="Local" v-model="data.codestoquelocal"/>
          </div>

          <!-- Tipo - Fisico/Fiscal -->
          <div class="col-12">
            <q-select outlined label="Tipo" v-model="data.fiscal" :options="tipos" map-options/>
          </div>

          <!-- Codigo da Marca -->
          <div class="col-12">
            <mg-autocomplete-marca
              label="Marca"
              v-model="data.codmarca"
              :init="data.codmarca"
              />
          </div>

          <!-- Data para jogar o movimento do estoque -->
          <div class="col-12">
            <q-input outlined v-model="data.data" mask="##/##/#### ##:##" input-class="text-center" label="Data de ajuste">
              <template v-slot:prepend>
                <q-icon name="event" class="cursor-pointer">
                  <q-popup-proxy transition-show="scale" transition-hide="scale">
                    <q-date v-model="data.data" mask="DD/MM/YYYY HH:mm" />
                  </q-popup-proxy>
                </q-icon>
              </template>
              <template v-slot:append>
                <q-icon name="access_time" class="cursor-pointer">
                  <q-popup-proxy transition-show="scale" transition-hide="scale">
                    <q-time v-model="data.data" mask="DD/MM/YYYY HH:mm" format24h />
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
    mostrarErro: function (mensagem) {
      this.$q.notify({
        message: mensagem,
        color: 'negative',
      })
    },
    validaCampos: function () {
      if (this.data.codestoquelocal == null) {
        this.mostrarErro('Selecione o Local!')
        return false
      }

      if (this.data.codmarca == null) {
        this.mostrarErro('Selecione a Marca!')
        return false
      }

      if (this.data.fiscal == null) {
        this.mostrarErro('Selecione o Tipo!')
        return false
      }

      if (this.data.data == null) {
        this.mostrarErro('Informe a data para ajustar o estoque!')
        return false
      }

      return true;
    },
    iniciarConferencia: function () {
      if (this.validaCampos() == false) {
        return
      }
      this.$router.push('/estoque-saldo-conferencia/listagem/'
        + this.data.codestoquelocal + '/'
        + this.data.codmarca + '/'
        + this.data.fiscal + '/'
        + this.moment(this.data.data, 'DD/MM/YYYY HH:mm').format('YYYY-MM-DDTHH:mm')
      )
    },
  },
  mounted () {
    this.loadFields = true;
    if (this.data.data == null) {
      this.data.data = this.moment().format('DD/MM/YYYY HH:mm')
      // this.data.data = new Date()
    }
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style>
</style>
