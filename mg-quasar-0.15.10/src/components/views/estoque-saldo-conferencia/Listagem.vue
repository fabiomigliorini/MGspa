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

    <q-page-sticky corner="bottom-right" :offset="[32, 32]">
      <q-btn round color="primary" icon="add" @click.native="modalBuscaPorBarras = true"/>
    </q-page-sticky>

    <!-- MODAL DE DETALHES DO PRODUTO -->
    <div class="col-md-12 col-lg-12">
    <q-modal v-model="modalProduto" v-if="produtoCarregado">
      <div  style="width:100vw; height:auto">
        <div>
          <q-card>
            <div class="row justify-center">

              <div class="col-xs-12 col-sm-12 col-md-3 col-lg-3" align="center">
                <q-card-media>
                  <center>
                    <img :src="produtoImagem" style="max-width:25vh; height: auto">
                  </center>
                </q-card-media>

                <q-card-title class="relative-position">
                  <div>
                    <q-item-tile>
                      <q-chip square color="negative" v-if="produto.produto.inativo">
                        Produto Inativo {{ moment(produto.produto.inativo).fromNow() }}
                      </q-chip>
                      <q-chip square color="negative" v-if="produto.variacao.inativo">
                        Variação Inativa {{ moment(produto.variacao.inativo).fromNow() }}
                      </q-chip>
                      <q-chip square color="negative" v-if="produto.variacao.descontinuado">
                        Descontinuado {{ moment(produto.variacao.descontinuado).fromNow() }}
                      </q-chip>
                    </q-item-tile>
                    <q-item-tile>
                      {{produto.produto.produto}}
                    </q-item-tile>
                  </div>
                  <div slot="subtitle">
                    {{produto.variacao.variacao}}
                  </div>
                </q-card-title>

                <q-card-main>
                  <div>
                    <q-icon name="vpn key" />&nbsp;
                    {{ numeral(produto.produto.codproduto).format('000000') }}
                  </div>
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

                  </p>

                </q-card-main>
              </div>
              <q-card-separator />
              <div class="col-xs-12 col-sm-12 col-md-3 col-lg-3">
                <q-list no-border>

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
                      <q-item-tile color="primary" icon="attach_money" />
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
              </div>

              <div class="col-sm-12 col-xs-12 col-lg-4 col-md-4" v-if="produto.conferencias.length > 0">
                <q-scroll-area style="height: 300px;">
                  <q-timeline color="secondary" style="padding: 0 24px;">
                    <h5 align="center"><strong>Conferências Efetuadas</strong></h5>
                    <q-timeline-entry v-for="conferencia in produto.conferencias"
                    :subtitle="moment(conferencia.criacao).fromNow()+', COD: '+ conferencia.codestoquesaldoconferencia"
                    side="right">
                      <div>
                        <q-item-tile>
                          <p><strong>{{conferencia.usuario}}</strong>
                            <q-btn flat icon="delete" color="red"  @click.native="excluirConferencia(conferencia.codestoquesaldoconferencia)"/>
                          </p>
                        </q-item-tile>
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
                        <p v-if="conferencia.observacoes">
                          Observações
                          <q-item-tile sublabel>
                            {{conferencia.observacoes}}
                          </q-item-tile>
                        </p>
                      </div>
                    </q-timeline-entry>
                  </q-timeline>
                </q-scroll-area>

              </div>

            </div>
            <div>
              <q-btn @click.native="editaConferencia()" flat color="primary" label="Fazer Nova Conferência"/>
              <q-btn flat @click="modalProduto = false" label="Fechar"/>
            </div>
          </q-card>

        </div>
      </div>
    </q-modal>
    </div>

    <!-- MODAL DE CONFERÊNCIA DO PRODUTO -->
    <template>
      <q-modal v-model="modalConferencia" v-if="produtoCarregado" @hide="focoCampoBarras()" @show="focoCampoQuantidadeInformada()">

          <!-- <q-collapsible icon="perm_identity" label="Second">
            <div>
              Content
            </div>
          </q-collapsible> -->

        <form @submit.prevent="salvaConferencia()">
          <q-list>
            <p class="caption" align="center">
              {{produto.produto.produto}}<template v-if="produto.produto.variacao">-
              {{produto.produto.variacao}}</template>
            </p>

            <q-item dense>
              <q-item-side align="center">
                <q-item-tile icon="widgets" :color="(produto.saldoatual.quantidade>0)?'green':(produto.saldoatual.quantidade<0)?'red':'grey'"/>
                  {{numeral(parseFloat(produto.saldoatual.quantidade)).format('0,0')}}
              </q-item-side>
              <q-item-main>
                <q-input type="number" float-label="Inserir nova quantidade" v-model="conferencia.quantidadeinformada" ref="campoQuantidadeInformada"/>
                <!-- <mg-erros-validacao :erros="erros.quantidadeinformada"/> -->
              </q-item-main>
            </q-item>

            <q-item dense>
              <q-item-side align="center">
                <q-item-tile icon="attach_money"/>
                {{numeral(parseFloat(produto.saldoatual.custo)).format('0,0.00')}}
              </q-item-side>
              <q-item-main>
                <q-input type="number" float-label="inserir novo custo" prefix="$" v-model="conferencia.customedioinformado"/>
                <!-- <mg-erros-validacao :erros="erros.customedioinformado"/> -->
              </q-item-main>
            </q-item>

            <!-- Adicionar data de validade -->
            <q-collapsible icon="date_range" label="Adicionar data de validade">
              <div>
                <q-item dense>
                  <q-item-main>
                    <q-datetime type="datetime" float-label="Validade" v-model="conferencia.vencimento"/>
                  </q-item-main>
                </q-item>
              </div>
            </q-collapsible>

            <!-- Adicionar Observações -->
            <q-collapsible icon="assignment" label="Adicionar Observações">
              <div>
                <q-item dense>
                  <q-item-main>
                    <q-input type="textarea"  float-label="Observações" v-model="conferencia.observacoes"/>
                  </q-item-main>
                </q-item>
              </div>
            </q-collapsible>

            <!-- Adicionar localização -->
            <q-collapsible icon="place" label="Adicionar localização">
              <div>
                <q-item dense>
                  <q-item-main>
                    <q-input type="number" float-label="Corredor" v-model="conferencia.corredor"/>
                  </q-item-main>
                </q-item>

                <q-item dense>
                  <q-item-main>
                    <q-input type="number" float-label="Prateleira" v-model="conferencia.prateleira" />
                  </q-item-main>
                </q-item>

                <q-item dense>
                  <q-item-main>
                    <q-input type="number" float-label="Coluna" v-model="conferencia.coluna"/>
                  </q-item-main>
                </q-item>

                <q-item dense>
                  <q-item-main>
                    <q-input type="number" float-label="Bloco" v-model="conferencia.bloco"/>
                  </q-item-main>
                </q-item ><br />

              </div>
            </q-collapsible>

          </q-list >
          <div>
            <q-btn flat color="primary" type="submit" label="Salvar Conferencia"/>
            <q-btn flat @click.native="modalConferencia = false" label="Fechar"/>
          </div>
        </form>

      </q-modal>
    </template>

    <!-- MODAL DE BUSCA POR BARRAS -->
    <template>
      <q-modal v-model="modalBuscaPorBarras" @show="focoCampoBarras()">
        <div style="height:20vh; padding:10px" align="center">
          <form @submit.prevent="buscaProdutoPorBarras()">
            <q-input v-model="buscaPorBarras.barras" float-label="Código" ref="campoBarras"/>
          </form>
        </div>
        <footer class="fixed-bottom" style="background-color: white">
          <q-btn flat @click="modalBuscaPorBarras = false" label="Fechar"/>
          <q-btn flat @click="buscaProdutoPorBarras()" label="buscar"/>
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
      filter: {
        codestoquelocal: null,
        codmarca: null,
        fiscal: 1,
        data: null,
        inativo: 0,
        dataCorte: null
      },
      buscaPorBarras: {},
      erros: {},
      produto: {},
      data: {},
      conferencia: {},
      carregado: false,
      codigoproduto: null,
      erros: false,
      tabAberta: 'tabAConferir',
      produtoImagem: null,
      modalProduto: false,
      modalConferencia: false,
      modalBuscaPorBarras: false,
      produtoCarregado: false,
      conferenciaExcluida: false,
      conferenciaSalva: false
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
  },
  methods: {
    validaCampos: function () {
      let vm = this
      let ret = true

      if (vm.conferencia.quantidadeinformada == null) {
        vm.erros.quantidadeinformada = ['Informe a quantidade']
        ret = false
      } else {
        vm.erros.quantidadeinformada = []
      }

      if (vm.conferencia.customedioinformado == null) {
        vm.erros.customedioinformado = ['Informe o custo']
        ret = false
      } else {
        vm.erros.customedioinformado = []
      }
      return ret
    },
    salvaConferencia: function () {
      // if (vm.validaCampos() == false) {
      //   return
      // }
      let vm = this
      vm.$q.dialog({
        title: 'Salvar',
        message: 'Tem certeza que deseja salvar?',
        ok: 'Salvar',
        cancel: 'Cancelar',
      }).then(() => {
        let params = {
          codprodutovariacao: vm.produto.variacao.codprodutovariacao,
          codestoquelocal: vm.produto.localizacao.codestoquelocal,
          fiscal: parseInt(vm.filter.fiscal),
          quantidadeinformada: vm.conferencia.quantidadeinformada,
          customedioinformado: vm.conferencia.customedioinformado,
          data: vm.filter.data,
          observacoes: vm.conferencia.observacoes,
          vencimento: new Date(parseInt(vm.conferencia.vencimento)),
          corredor: vm.conferencia.corredor,
          prateleira: vm.conferencia.prateleira,
          coluna: vm.conferencia.coluna,
          bloco: vm.conferencia.bloco
        }
        console.log(params)
        vm.$axios.post('estoque-saldo-conferencia', params).then(function (request) {
          vm.modalConferencia = false
          vm.conferenciaSalva = true
          vm.$q.notify({
            message: 'Conferência realizada',
            type: 'positive',
          })
        }).catch(function (error) {
          vm.erros = error.response.data.erros
        }).finally(function (request){
          vm.buscaListagem()
        })
      })
    },
    excluirConferencia: function (codestoquesaldoconferencia) {
      let vm = this
      vm.$q.dialog({
        title: 'Excluir',
        message: 'Tem certeza que deseja excluir?',
        ok: 'Excluir',
        cancel: 'Cancelar'
      }).then(() => {
        console.log(codestoquesaldoconferencia)
        vm.$axios.post('estoque-saldo-conferencia/' + codestoquesaldoconferencia + '/inativo').then(function (request) {
          vm.$q.notify({
            message: 'Excluido com sucesso',
            type: 'positive',
          })
          vm.conferenciaExcluida = true
        }).catch(function (error) {
          vm.erros = error.response.data.erros
        }).finally(function (request){
          vm.buscaListagem()
          vm.modalProduto = false
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
        vm.modalProduto = false,
        vm.modalBuscaPorBarras = false
      },
    buscaProdutoPorCodvariacao: function(produto) {
      let vm = this
      vm.produtoImagem = produto.imagem
      let params = {
        codprodutovariacao: produto.codprodutovariacao,
        codestoquelocal: vm.filter.codestoquelocal,
        fiscal: vm.filter.fiscal
      }
      vm.$axios.get('estoque-saldo-conferencia/busca-produto', {
        params
      }).then(function(request) {
        vm.produto = request.data
        vm.modalProduto = true
        vm.produtoCarregado = true
      }).catch(function(error) {
        vm.modalProduto = false
        vm.produtoCarregado = false
        console.log(error.response)
      })
    },
    focoCampoBarras: function () {
      this.$nextTick(() => this.$refs.campoBarras.focus())
    },
    focoCampoQuantidadeInformada: function () {
      this.$nextTick(() => this.$refs.campoQuantidadeInformada.focus())
    },
    buscaProdutoPorBarras: function() {
      /*
      console.log('entrou')
      return
      */
      let vm = this
      let params = {
        barras: vm.buscaPorBarras.barras,
        codestoquelocal: vm.filter.codestoquelocal,
        fiscal: vm.filter.fiscal
      }
      vm.$axios.get('estoque-saldo-conferencia/busca-produto', {
        params
      }).then(function(request) {
        vm.produto = request.data
        vm.buscaPorBarras.barras = null
        vm.modalConferencia = true
        vm.produtoCarregado = true
      }).catch(function(error) {
        vm.modalConferencia = false
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
