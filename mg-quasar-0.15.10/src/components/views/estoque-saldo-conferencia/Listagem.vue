<template>
<mg-layout drawer>
  <q-btn flat round slot="menuRight" @click="$router.push('/estoque-saldo-conferencia')">
    <q-icon name="arrow_back" />
  </q-btn>
  <template slot="title" v-if="carregado">
    {{data.local.estoquelocal }} / {{ data.marca.marca }}
  </template>

  <div slot="drawer">
    <!-- Filtra Ativos -->
    <q-list-header>Filtros</q-list-header>
    <q-item tag="label">
      <q-item-side icon="thumb_up"></q-item-side>
      <q-item-main>
        <q-item-tile title>Ativos</q-item-tile>
      </q-item-main>
      <q-item-side right>
        <q-radio v-model="filter.inativo" :val="0" />
      </q-item-side>
    </q-item>
    <!-- Filtra Inativos -->
    <q-item tag="label">
      <q-item-side icon="thumb_down">
      </q-item-side>
      <q-item-main>
        <q-item-tile title>Inativos</q-item-tile>
      </q-item-main>
      <q-item-side right>
        <q-radio v-model="filter.inativo" :val="1" />
      </q-item-side>
    </q-item>
    <!-- Filtra Ativos e Inativos -->
    <q-item tag="label">
      <q-item-side icon="thumbs_up_down">
      </q-item-side>
      <q-item-main>
        <q-item-tile title>Todos</q-item-tile>
      </q-item-main>
      <q-item-side right>
        <q-radio v-model="filter.inativo" :val="9" />
      </q-item-side>
    </q-item>
    <q-item-separator />
    <!-- Filtra por data de corte -->
    <q-list-header>Data de Corte Conferência</q-list-header>
    <q-item tag="label">
      <q-item-main>
        <q-datetime v-model="filter.dataCorte" type="date" format="DD/MMM/YYYY" />
      </q-item-main>
    </q-item>
  </div>

  <div slot="content">
    <q-tabs v-model="tabAberta">
      <q-tab slot="title" name="tabAConferir" default>
        <q-icon name="assignment_late" style="font-size: 30px" />
      </q-tab>
      <q-tab slot="title" name="tabConferido">
        <q-icon name="assignment_turned_in" style="font-size: 30px" />
      </q-tab>
    </q-tabs>

    <q-list highlight separator>
      <template v-for="produto in produtosMostrar">
        <q-item multiline @click.native="buscaProdutoPorCodvariacao(produto)">
          <!-- <q-item-side :avatar="produto.imagem" v-if="produto.imagem" /> -->
          <q-item-side v-if="produto.imagem">
            <img :src="produto.imagem" style="width: 55px; height: 55px" />
          </q-item-side>
          <q-item-main>
            <q-item-tile label lines="3">
              {{ produto.produto }}
              <template v-if="produto.variacao">- {{produto.variacao}}</template>
      </q-item-tile>
      <q-item-tile sublabel lines="2">
        <q-chip detail square dense icon="vpn_key">
          {{ numeral(produto.codproduto).format('000000') }}
        </q-chip>
        <q-chip detail square dense icon="widgets" :color="(produto.saldo>0)?'green':(produto.saldo<0)?'red':'grey'">
          {{ numeral(produto.saldo).format('0,0') }}
        </q-chip>
        <q-chip detail square dense icon="thumb_down" v-if="produto.inativo" color="red">
          {{ moment(produto.inativo).fromNow() }}
        </q-chip>
      </q-item-tile>
      </q-item-main>
      <q-item-side right v-if="produto.ultimaconferencia">
        <q-item-tile stamp>
          {{ moment(produto.ultimaconferencia).fromNow() }}
        </q-item-tile>
        <q-item-tile icon="assignment_turned_in" />
      </q-item-side>
      </q-item>
      </template>
    </q-list>

    <router-link :to="{ path: '/estoque-saldo-conferencia/conferencia' }">
      <q-page-sticky corner="bottom-right" :offset="[32, 32]">
        <q-btn round color="primary">
          <q-icon name="add" />
        </q-btn>
      </q-page-sticky>
    </router-link>

    <!-- MODAL DE DETALHES DO PRODUTO -->
    <q-modal v-model="modalProduto" v-if="produtoCarregado">

      <q-card class="bigger">
        <q-card-media>
          <center>
            <img :src="produtoImagem" style="max-width:300px; height: auto">
          </center>
        </q-card-media>
        <q-card-title class="relative-position">
          <!-- <q-btn fab color="primary" icon="place" class="absolute" style="top: 0; right: 8px; transform: translateY(-50%);" /> -->
          <div>
            {{produto.produto.produto}}
          </div>
          <div slot="subtitle">
            {{produto.variacao.variacao}}
          </div>
          <!-- <q-rating slot="subtitle" v-model="stars" :max="5" /> -->
          <div slot="right" class="row items-center">
            <q-icon name="vpn key" />&nbsp;
            {{ numeral(produto.produto.codproduto).format('000000') }}
          </div>
        </q-card-title>
        <q-card-main>
          <p class="text-faded">
            R$ {{ numeral(parseFloat(produto.produto.preco)).format('0,0.00') }}・Preço de Venda <br />

            <span v-if="produto.variacao.referencia">
              {{ produto.variacao.referencia }}
              ・Referência
              <br />
            </span>
            <span v-else-if="produto.produto.referencia">
              {{ produto.produto.referencia }}
              ・Referência
              <br />
            </span>
            <q-chip square color="negative" v-if="produto.produto.inativo">
              Produto Inativo {{ moment(produto.produto.inativo).fromNow() }}
            </q-chip>
            <q-chip square color="negative" v-if="produto.variacao.inativo">
              Variação Inativa {{ moment(produto.variacao.inativo).fromNow() }}
            </q-chip>
            <q-chip square color="negative" v-if="produto.variacao.descontinuado">
              Descontinuado {{ moment(produto.variacao.descontinuado).fromNow() }}
            </q-chip>
          </p>
        </q-card-main>
        <q-card-separator />
        <q-list>
          <q-item>
            <q-item-side>
              <q-item-tile color="primary" icon="widgets" />
            </q-item-side>
            <q-item-main>
              <q-item-tile label>
                <b>
                  {{ numeral(parseFloat(produto.saldoatual.quantidade)).format('0,0') }}
                </b>
                {{ produto.produto.siglaunidademedida }}
                em estoque
              </q-item-tile>
              <q-item-tile sublabel>
                Suegerido entre {{ numeral(parseFloat(produto.variacao.estoqueminimo)).format('0,0') }} e
                {{ numeral(parseFloat(produto.variacao.estoquemaximo)).format('0,0') }}
                {{ produto.produto.siglaunidademedida }}.
              </q-item-tile>
            </q-item-main>
          </q-item>
          <q-item>
            <q-item-side>
              <q-item-tile color="primary" icon="attach money" />
            </q-item-side>
            <q-item-main>
              <q-item-tile label>
                R$
                <b>{{ numeral(parseFloat(produto.saldoatual.custo)).format('0,0.00') }}</b>
              </q-item-tile>
              <q-item-tile sublabel>Custo de cada {{ produto.produto.unidademedida }}.</q-item-tile>
            </q-item-main>
          </q-item>
          <q-item>
            <q-item-side>
              <q-item-tile color="red" icon="place" />
            </q-item-side>
            <q-item-main>
              <q-item-tile label>
                {{ produto.localizacao.estoquelocal }}
              </q-item-tile>
              <q-item-tile sublabel v-if="produto.localizacao.corredor">
                Corredor&nbsp{{ numeral(produto.localizacao.corredor).format('00') }}
                Prateleira&nbsp{{ numeral(produto.localizacao.prateleira).format('00') }}
                Coluna&nbsp{{ numeral(produto.localizacao.coluna).format('00') }}
                Bloco&nbsp{{ numeral(produto.localizacao.bloco).format('00') }}
              </q-item-tile>
            </q-item-main>
          </q-item>
          <q-item>
            <q-item-side>
              <q-item-tile color="black" icon="view column" />
            </q-item-side>
            <q-item-main>
              <q-item-tile label>
                Códigos de Barras
              </q-item-tile>
              <q-item-tile sublabel>
                <template v-for="barra in produto.barras">
                  {{ barra.barras }} {{ barra.siglaunidademedida }}
                  <template v-if="barra.quantidade > 1">
                     C/{{ parseInt(barra.quantidade) }}
                  </template>
                  <br />
                </template>
              </q-item-tile>
            </q-item-main>
          </q-item>
        </q-list>
        <q-scroll-area style="height: 400px;">
          <q-timeline color="secondary" style="padding: 0 24px;">
            <q-timeline-entry heading >Conferências Efetuadas</q-timeline-entry>

            <q-timeline-entry v-for="conferencia in produto.conferencias"
              :title="conferencia.usuario"
              :subtitle="moment(conferencia.criacao).fromNow()+', COD: '+ conferencia.codestoquesaldoconferencia"
              side="right" icon="delete">
              <div>
                <p>Quantidade informada
                  <q-item-tile sublabel>
                    {{ numeral(parseFloat(conferencia.quantidadeinformada)).format('0,0') }}
                  </q-item-tile>
                </p>
                <p>Quantidade do sistema
                  <q-item-tile sublabel>
                    {{ numeral(parseFloat(conferencia.quantidadesistema)).format('0,0') }}
                  </q-item-tile>
                </p>
                <p>Custo médio informado
                  <q-item-tile sublabel>
                    {{numeral(parseFloat(conferencia.customedioinformado)).format('0,0.00')}}
                  </q-item-tile>
                </p>
                <p>Custo médio do sistema
                  <q-item-tile sublabel>
                    {{numeral(parseFloat(conferencia.customediosistema)).format('0,0.00')}}
                  </q-item-tile>
                </p>
                <p v-if="conferencia.observacoes">Observações
                  <q-item-tile sublabel>
                    {{conferencia.observacoes}}
                  </q-item-tile>
                </p>
              </div>
            </q-timeline-entry>
          </q-timeline>
        </q-scroll-area>

      </q-card>
      <footer class="fixed-bottom" style="background-color: white">
        <q-btn @click.native="editaConferencia()" flat color="primary" label="Fazer Nova Conferência"/>
        <q-btn flat @click="modalProduto = false" label="Fechar"/>
      </footer>
    </q-modal>

    <!-- Modal de conferência do produto -->

    <template>
      <q-modal v-model="modalConferencia" v-if="produtoCarregado">

        <q-list>
          <form @submit.prevent="salvaConferencia()">
            <p class="caption">{{produto.produto.produto}}<template v-if="produto.produto.variacao">- {{produto.produto.variacao}}</template></p>

            <q-item dense>
              <q-item-side align="center">
                <q-item-tile sublabel>
                  Quantidade atual
                </q-item-tile>
                {{numeral(parseFloat(produto.saldoatual.quantidade)).format('0,0')}}
              </q-item-side>
              <q-item-main>
                <q-input type="number" float-label="Inserir nova quantidade"/>
              </q-item-main>
            </q-item>
            <q-item-separator />

            <q-item dense>
              <q-item-side align="center">
                <q-item-tile sublabel>
                  Custo atual
                </q-item-tile>
                {{numeral(parseFloat(produto.saldoatual.custo)).format('0,0.00')}}
              </q-item-side>
              <q-item-main>
                <q-input type="number" float-label="inserir novo custo" prefix="$"/>
              </q-item-main>
            </q-item>
            <q-item-separator />

            <q-item dense>
              <q-item-main>
                <q-datetime v-model="filter.data" type="date" format="DD/MMM/YYYY HH:MM:ss" float-label="Data da conferência" />
              </q-item-main>
            </q-item>
            <q-item-separator />

            <q-item dense>
              <q-item-main>
                <q-input type="text"  float-label="Observações" />
              </q-item-main>
            </q-item>
            <q-item-separator />

            <q-item dense>
              <q-item-main>
                <q-datetime v-model="model" type="datetime" float-label="Vencimento" />
              </q-item-main>
            </q-item>
            <q-item-separator />

            <q-item dense>
              <q-item-side align="center">
                <q-item-tile sublabel>
                  Corredor atual
                </q-item-tile>
                {{produto.localizacao.corredor}}
              </q-item-side>
              <q-item-main>
                <q-input type="number" float-label="Novo corredor" />
              </q-item-main>
            </q-item>

            <q-item dense>
              <q-item-side align="center">
                <q-item-tile sublabel>
                  Prateleira atual
                </q-item-tile>
                {{produto.localizacao.corredor}}
              </q-item-side>
              <q-item-main>
                <q-input type="number" float-label="Nova prateleira" />
              </q-item-main>
            </q-item>

            <q-item dense>
              <q-item-side align="center">
                <q-item-tile sublabel>
                  Coluna atual
                </q-item-tile>
                {{produto.localizacao.corredor}}
              </q-item-side>
              <q-item-main>
                <q-input type="number" float-label="Nova Coluna" />
              </q-item-main>
            </q-item>

            <q-item dense>
              <q-item-side align="center">
                <q-item-tile sublabel>
                  Bloco atual
                </q-item-tile>
                {{produto.localizacao.corredor}}
              </q-item-side>
              <q-item-main>
                <q-input type="number" float-label="Novo Bloco" />
              </q-item-main>
            </q-item ><br />

        </form>
      </q-list >

        <footer class="fixed-bottom" style="background-color: white" >
          <q-btn @click.prevent="salvaConferencia(), modalProduto = false" flat color="primary" label="Salvar Conferencia"/>
          <q-btn flat @click="modalConferencia = false" label="Fechar"/>
        </footer>
      </q-modal>
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
  name: 'estoque-saldo-conferencia-listagem',
  components: {
    MgLayout,
    MgSelectEstoqueLocal,
    MgErrosValidacao,
    MgAutocompleteMarca
  },
  data() {
    return {
      data: {},
      filter: {
        codestoquelocal: null,
        codmarca: null,
        fiscal: 1,
        data: null,
        inativo: 0,
        dataCorte: null
      },
      carregado: false,
      codigoproduto: null,
      erros: false,
      tabAberta: 'tabAConferir',
      produto: {},
      conferencia: {},
      produtoImagem: null,
      modalProduto: false,
      modalConferencia: false,
      produtoCarregado: false
    }
  },
  watch: {
    filter: {
      handler: function(val, oldVal) {
        this.buscaListagem(false, null)
      },
      deep: true
    }
  },
  computed: {
    produtosMostrar: function() {
      if (!this.carregado) {
        return
      }
      let vm = this

      switch (vm.tabAberta) {

        case 'tabAConferir':
          return vm.data.produtos.filter(function(produto) {
            return (produto.ultimaconferencia == null) ||
              (vm.moment(produto.ultimaconferencia).isBefore(vm.filter.dataCorte))
          })
          break

        default:
          return vm.data.produtos.filter(function(produto) {
            return (produto.ultimaconferencia != null) &&
              (!vm.moment(produto.ultimaconferencia).isBefore(vm.filter.dataCorte))
          })
      }
    },
    header: {
      get() {
        return this.$store.state.estoqueSaldoConferencia.estoqueSaldoConferenciaState
      }
    }
  },
  methods: {

    salvaConferencia: function () {
      let vm = this
      vm.$q.dialog({
        title: 'Salvar',
        message: 'Tem certeza que deseja salvar?',
        ok: 'Salvar',
        cancel: 'Cancelar'
      }).then(() => {
        vm.$axios.post('estoque-saldo-conferencia', vm.data).then(function (request) {
          vm.$q.notify({
            message: 'Conferência realizada',
            type: 'positive',
          })
        }).catch(function (error) {
          vm.erros = error.response.data.erros
        })
      })
    },

    buscaListagem: function() {
      let vm = this
      let params = {
        codestoquelocal: vm.filter.codestoquelocal,
        codmarca: vm.filter.codmarca,
        fiscal: vm.filter.fiscal,
        inativo: vm.filter.inativo
      }
      vm.$axios.get('estoque-saldo-conferencia/busca-listagem', {
        params
      }).then(function(request) {
        vm.data = request.data
        vm.carregado = true
      }).catch(function(error) {
        console.log(error.response)
      })
    },

    editaConferencia: function() {
      let vm = this
        vm.modalConferencia = true,
        vm.modalProduto = false
      },

    buscaProdutoPorCodvariacao: function(produto) {
      let vm = this
      this.produtoImagem = produto.imagem
      let params = {
        codprodutovariacao: produto.codprodutovariacao,
        codestoquelocal: vm.filter.codestoquelocal,
        fiscal: vm.filter.fiscal
      }
      vm.$axios.get('estoque-saldo-conferencia/busca-produto', {
        params
      }).then(function(request) {
        //console.log(vm.produto)
        vm.produto = request.data
        vm.modalProduto = true
        vm.produtoCarregado = true
      }).catch(function(error) {
        vm.modalProduto = false
        vm.produtoCarregado = false
        console.log(error.response)
      })
    }
  },
  mounted() {
    this.buscaListagem()
  },
  created() {
    this.filter.codestoquelocal = this.$route.params.codestoquelocal
    this.filter.codmarca = this.$route.params.codmarca
    this.filter.fiscal = this.$route.params.fiscal
    this.filter.data = new Date(parseInt(this.$route.params.data))
    this.filter.dataCorte = this.moment().startOf('day').subtract(15, 'days').toDate()
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style>
</style>
