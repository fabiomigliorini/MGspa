<template>
  <mg-layout>

    <q-btn flat round slot="menu" @click="$router.push('/estoque-saldo-conferencia/Conferencia')">
      <q-icon name="arrow_back" />
    </q-btn>



    <template slot="title">
      produto.codproduto -
      produto.produto -
    </template>
    <div slot="content">
      <div class="layout-padding">

        <q-card>
          <q-card-title>
            produto.codproduto
            -
            produto.produto


            <!--
            se for inativo colocar uma classe de erro
            -->
            variacao.inativo
            produto.inativo

            <!--
            se for descontinuado uma classe de warning
            -->
            variacao.descontinuado

            <span slot="subtitle">
              variacao.variacao

              <!-- se a referencia da variacao for em branco mostrar a referencia do produto
              -->
              variacao.referencia
              produto.referencia


              localizacao.estoquelocal


            </span>
          </q-card-title>

          <q-card-main>
            Estoque: saldoatual.quantidade - R$ saldoatual.custo <br />
            Mínimo: localizacao.estoqueminimo <br />
            Mínimo: localizacao.estoquemaximo <br />
            Vencimento: localizacao.vencimento <br />
            localizacao.corredor": 2, <br />
            localizacao.prateleira": 2, <br />
            localizacao.coluna": 2, <br />
            localizacao.bloco": 2, <br />

          </q-card-main>

          <q-card-separator />
          <!-- LOOP NAS CONFERENCIAS -->
          <q-list>

            <!-- UM Q-ITEM para cada conferencia -->
            <q-item>
              <q-item-side avatar="statics/boy-avatar.png" />
              <q-item-main>
                <q-item-tile label>
                  conferencia.criacao
                  conferencia.quantidadeinformada
                  conferencia.quantidadesistema
                  conferencia.customedioinformado
                  conferencia.custosistema
                </q-item-tile>
                <q-item-tile sublabel>
                  conferencia.usuario
                  conferencia.observacoes
                  conferencia.data
                </q-item-tile>
              </q-item-main>
              <q-item-side right>
                <q-btn flat round dense icon="more_vert">
                  <q-popover>
                    <q-list link>
                      <q-item v-close-overlay>
                        <q-item-main label="Inativar" />
                      </q-item>
                    </q-list>
                  </q-popover>
                </q-btn>
              </q-item-side>
            </q-item>

            <!-- UM Q-ITEM para cada conferencia -->
            <q-item>
              <q-item-side avatar="statics/boy-avatar.png" />
              <q-item-main>
                <q-item-tile label>
                  conferencia.criacao
                  conferencia.quantidadeinformada
                  conferencia.quantidadesistema
                  conferencia.customedioinformado
                  conferencia.custosistema
                </q-item-tile>
                <q-item-tile sublabel>
                  conferencia.usuario
                  conferencia.observacoes
                  conferencia.data
                </q-item-tile>
              </q-item-main>
              <q-item-side right>
                <q-btn flat round color="negative" icon="thumb_down">
                </q-btn>
              </q-item-side>
            </q-item>

            <!-- UM Q-ITEM para cada conferencia -->
            <q-item>
              <q-item-side avatar="statics/boy-avatar.png" />
              <q-item-main>
                <q-item-tile label>
                  conferencia.criacao
                  conferencia.quantidadeinformada
                  conferencia.quantidadesistema
                  conferencia.customedioinformado
                  conferencia.custosistema
                </q-item-tile>
                <q-item-tile sublabel>
                  conferencia.usuario
                  conferencia.observacoes
                  conferencia.data
                </q-item-tile>
              </q-item-main>
              <q-item-side right>
                <q-btn flat round dense icon="more_vert">
                  <q-popover>
                    <q-list link>
                      <q-item v-close-overlay>
                        <q-item-main label="Inativar" />
                      </q-item>
                    </q-list>
                  </q-popover>
                </q-btn>
              </q-item-side>
            </q-item>

            <!-- UM Q-ITEM para cada conferencia -->
            <q-item>
              <q-item-side avatar="statics/boy-avatar.png" />
              <q-item-main>
                <q-item-tile label>
                  conferencia.criacao
                  conferencia.quantidadeinformada
                  conferencia.quantidadesistema
                  conferencia.customedioinformado
                  conferencia.custosistema
                </q-item-tile>
                <q-item-tile sublabel>
                  conferencia.usuario
                  conferencia.observacoes
                  conferencia.data
                </q-item-tile>
              </q-item-main>
              <q-item-side right>
                <q-btn flat round dense icon="more_vert">
                  <q-popover>
                    <q-list link>
                      <q-item v-close-overlay>
                        <q-item-main label="Inativar" />
                      </q-item>
                    </q-list>
                  </q-popover>
                </q-btn>
              </q-item-side>
            </q-item>




          </q-list>

        </q-card>

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
  computed: {
    data: {
      get () {
        return this.$store.state.estoqueSaldoConferencia.estoqueSaldoConferenciaState
      }
    }
  },
  methods: {
    iniciar: function () {
      let vm = this
      vm.dadosConferencia(vm.data.codestoquelocal, vm.data.codmarca)
      vm.$router.push('/estoque-saldo-conferencia/conferencia')
    },
    dadosConferencia: function (codestoquelocal, codmarca) {
      let vm = this
      let params = {
        fields:['estoquelocal']
      }
      vm.$axios.get('estoque-local/' + codestoquelocal, { params }).then(function (request) {
        vm.data.estoquelocal = request.data.estoquelocal
        params = {
          fields:['marca']
        }
        vm.$axios.get('marca/' + codmarca, { params }).then(function (request) {
          vm.data.marca = request.data.marca
          vm.$store.commit('estoqueSaldoConferencia/updateestoqueSaldoConferencia', vm.data)
        }).catch(function (error) {
          console.log(error.response)
        })
      }).catch(function (error) {
        console.log(error.response)
      })

    }
  },
  mounted () {
    console.log(this.data)
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style>
</style>
