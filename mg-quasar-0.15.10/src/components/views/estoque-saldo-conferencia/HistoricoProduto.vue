<template>
  <mg-layout>

    <q-btn flat round slot="menu" @click="$router.push('/estoque-saldo-conferencia/Listagem')">
      <q-icon name="arrow_back" />
    </q-btn>


      <template slot="title">
        {{data.produto.codproduto}} / {{data.produto.produto}}
      </template>
      <div slot="content">
        <div class="layout-padding">

          <template v-for="produto in historico">
          <q-card>
            <q-card-title>
              {{produto.codproduto}}
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
                  <q-btn flat round color="negative" icon="thumb_down">
                  </q-btn>
                </q-item-side>
              </q-item>

            </q-list>
          </q-card>
        </template>

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
  name: 'estoque-saldo-conferencia-listagem',
  components: {
    MgLayout,
    MgSelectEstoqueLocal,
    MgErrosValidacao,
    MgAutocompleteMarca
  },
  data () {
    return {
      data: {},
      filter: {
        codestoquelocal: null,
        codmarca: null,
        fiscal: 1,
        data: null,
        inativo: 1,
        dataCorte: null
      },
      carregado: false
    }
  },
  watch: {
    filter: {
      handler: function (val, oldVal) {
        this.buscaHistoricoProduto(false, null)
      },
      deep: true
    }
  },
  computed: {
    historico: function() {
      let vm = this
      if (vm.carregado) {
        return vm.data.produtos
      }
    },
    header: {
      get () {
        return this.$store.state.estoqueSaldoConferencia.estoqueSaldoConferenciaState
      }
    }
  },
  methods: {

    buscaHistoricoProduto: function () {
      let vm = this
      let params = {
        codprodutovariacao: vm.filter.codprodutovariacao,
        codestoquelocal: vm.filter.codestoquelocal,
        fiscal: 1
      }
      vm.$axios.get('estoque-saldo-conferencia/busca-produto', { params }).then(function (request) {
        vm.data = request.data
        vm.carregado = true
      }).catch(function (error) {
        console.log(error.response)
      })
    }

  },
  mounted () {
    this.buscaHistoricoProduto()
  },
  created () {
    this.filter.codprodutovariacao = this.$route.params.codprodutovariacao
    this.filter.codestoquelocal = this.$route.params.codestoquelocal
    this.filter.fiscal = this.$route.params.fiscal
    console.log(this.filter.data)
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style>
</style>
