<template>
  <mg-layout>

    <q-btn flat round slot="menu" @click="$router.push('/estoque-saldo-conferencia/Listagem')">
      <q-icon name="arrow_back" />
    </q-btn>

      <div slot="content">
        <div class="layout-padding">

            <q-card>
              <template v-if="BuscaHistorico">
              <q-card-title>
                #{{result.produto.codproduto}}<br/>
                {{result.produto.produto}}
                <!-- se for inativo colocar uma classe de erro -->
                <!-- se for descontinuado uma classe de warning -->
                <q-chip tag square pointing="left" color="negative" v-if="result.produto.inativo">Inativo</q-chip>
                <q-chip tag square pointing="left" color="negative" v-if="result.variacao.inativo">Inativo</q-chip>
                <q-chip tag square pointing="left" color="warning" v-if="result.variacao.descontinuado">Descontinuado</q-chip>

                <span slot="subtitle">
                  {{result.variacao.variacao}}
                  <!-- se a referencia da variacao for em branco mostrar a referencia do produto  -->
                  #{{ result.variacao.referencia}}
                  <template v-if="result.variacao.referencia == null">{{result.produto.referencia}}</template>
                  Local: {{result.localizacao.estoquelocal}}
                </span>
              </q-card-title>

              <q-card-main>
                Saldo: {{result.saldoatual.quantidade}} <br />
                Custo: R$ {{result.saldoatual.custo}} <br />
                Mínimo: {{result.localizacao.estoqueminimo}} <br />
                Máximo: {{result.localizacao.estoquemaximo}} <br />
                Vencimento: {{result.localizacao.vencimento}} <br />
                Corredor: {{result.localizacao.corredor}} <br />
                Prateleira: {{result.localizacao.prateleira}} <br />
                Coluna: {{result.localizacao.coluna}}<br />
                Bloco: {{result.localizacao.bloco}}<br />
              </q-card-main>
            </template>

              <q-card-separator />
              <!-- LOOP NAS CONFERENCIAS -->
                <template v-for="conferido in buscaConferencias">
                  <q-list>
                    <!-- UM Q-ITEM para cada conferencia -->
                    <q-item>
                      <q-item-side avatar="statics/boy-avatar.png" />
                      <q-item-main>
                        <q-item-tile label>
                          Criado em: {{conferido.criacao}} <br />
                          Quantidade informada: {{conferido.quantidadeinformada}} <br />
                          Quantidade sistema: {{conferido.quantidadesistema}} <br />
                          conferencia.customedioinformado <br />
                          conferencia.custosistema <br />
                        </q-item-tile>
                        <q-item-tile sublabel>
                          conferencia.usuario
                          conferencia.observacoes
                          conferencia.data
                        </q-item-tile>
                      </q-item-main>
                    <q-item-side right>
                      <q-btn flat round color="negative" icon="thumb_down"></q-btn>
                    </q-item-side>
                  </q-item>
              </q-list>
            </template>

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
  name: 'estoque-saldo-conferencia-listagem',
  components: {
    MgLayout,
    MgSelectEstoqueLocal,
    MgErrosValidacao,
    MgAutocompleteMarca
  },
  data: {
    result: [],
  },
  data () {
    return {
      filter: {
        codprodutovariacao: null,
        codestoquelocal: null,
        fiscal: 1,
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
    BuscaHistorico: function() {
      let vm = this
      if (vm.carregado) {
        return vm.result
      }
    },
    buscaConferencias: function() {
      let vm = this
      if (vm.carregado) {
        return vm.result.conferencias
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
        vm.result = request.data
        vm.carregado = true
        console.log(vm.result)
        console.log('aqui o get')
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
    console.log('Fiscal:')
    console.log(this.filter.fiscal)
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style>
</style>
