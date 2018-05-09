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
        <q-modal v-model="modalProduto" v-if="produtoCarregado" maximized>
          <div class="row">
            <!-- COLUNA 1 -->
            <div class="col-sm-12 col-md-5 col-lg-5">
              <q-card-media>
                <center>
                  <img :src="produtoImagem" style="max-width:95vw; max-height:80vh; height: auto">
                </center>
              </q-card-media>
              <q-card-title>
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
                  {{produto.variacao.variacao}}
                </q-item-tile>
              </q-card-title>
            </div>

            <!-- COLUNA 2 -->
            <div class="col-sm-12 col-md-7 col-lg-7">
              <div class="row">
                <div class="col-sm-12 col-md-4">
                  <q-list no-border>
                    <q-item>
                      <q-item-side>
                        <q-item-tile color="primary" icon="widgets" />
                      </q-item-side>
                      <q-item-main>
                        <q-item-tile label>
                          <b>{{ numeral(parseFloat(produto.saldoatual.quantidade)).format('0,0') }}</b>
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
                          Vendido à R$
                          <b>{{ numeral(parseFloat(produto.produto.preco)).format('0,0.00') }}</b>
                        </q-item-tile>
                        <q-item-tile sublabel>
                          Cada {{ produto.produto.unidademedida }} custa R$
                          <b>{{ numeral(parseFloat(produto.saldoatual.custo)).format('0,0.00') }}</b>.
                        </q-item-tile>
                      </q-item-main>
                    </q-item>
                  </q-list>
                </div>
                <div class="col-sm-12 col-md-4">
                  <q-list no-border>
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
                    <q-item v-if="produto.localizacao.vencimento">
                      <q-item-side>
                        <q-item-tile color="red" icon="access alarm" />
                      </q-item-side>
                      <q-item-main>
                        <q-item-tile label>
                          Vence
                          {{ moment(produto.localizacao.vencimento).toNow() }}
                        </q-item-tile>
                      </q-item-main>
                    </q-item>
                  </q-list>
                </div>
                <div class="col-sm-12 col-md-4">
                  <q-list no-border>
                    <q-item>
                      <q-item-side>
                        <q-item-tile color="black" icon="vpn key" />
                      </q-item-side>
                      <q-item-main>
                        <q-item-tile label>
                          {{ numeral(produto.produto.codproduto).format('000000') }}
                        </q-item-tile>
                      </q-item-main>
                    </q-item>
                    <q-item>
                      <q-item-side>
                        <q-item-tile color="black" icon="local offer" />
                      </q-item-side>
                      <q-item-main>
                        <q-item-tile label>
                          <template v-if="produto.variacao.referencia">
                            {{ produto.variacao.referencia }}
                          </template>
                          <template v-else-if="produto.produto.referencia">
                            {{ produto.produto.referencia }}
                          </template>
                        </q-item-tile>
                      </q-item-main>
                    </q-item>
                    <q-item>
                      <q-item-side>
                        <q-item-tile color="black" icon="view column" />
                      </q-item-side>
                      <q-item-main>
                        <q-item-tile sublabel>
                          <small>
                            <template v-for="barra in produto.barras">
                              {{ barra.barras }} {{ barra.siglaunidademedida }}
                              <template v-if="barra.quantidade > 1">
                                C/{{ parseInt(barra.quantidade) }}
                              </template><br />
                            </template>
                          </small>
                        </q-item-tile>
                      </q-item-main>
                    </q-item>
                  </q-list>
                </div>
              </div>
              <q-timeline color="secondary" style="padding: 0 24px;" v-if="produto.conferencias.length > 0">
                <q-timeline-entry subtitle="Últimas conferências" side="left"/>
                <q-timeline-entry
                  v-for="(conferencia, iConferencia) in produto.conferencias"
                  :title="numeral(parseFloat(conferencia.quantidadeinformada)).format('0,0') + ' '
                  + produto.produto.siglaunidademedida + ' custando R$ '
                  + numeral(parseFloat(conferencia.customedioinformado)).format('0,0.00')
                  + ' cada ' + produto.produto.unidademedida"
                  :subtitle="conferencia.usuario + ' ' + moment(conferencia.criacao).fromNow()"
                  :side="((iConferencia%2)==0)?'right':'left'"
                >
                <small class="text-faded">
                  O saldo do sistema no momento da conferência era de
                  <b>{{ numeral(parseFloat(conferencia.quantidadesistema)).format('0,0') }}</b> {{produto.produto.siglaunidademedida}}
                  custando R$ <b>{{numeral(parseFloat(conferencia.customediosistema)).format('0,0.00')}}</b>
                  cada {{ produto.produto.unidademedida }}.
                </small>
                <small class="text-faded" v-if="conferencia.observacoes">
                  <br />
                  {{conferencia.observacoes}}
                </small>
                <q-btn round flat dense icon="thumb_down" color="red"  @click.native="excluirConferencia(conferencia.codestoquesaldoconferencia)"/>
              </q-timeline-entry>
              </q-timeline>
            </div>
          </div>
          <q-page-sticky position="bottom-right" :offset="[32, -18]">
            <q-btn round color="primary" icon="create" @click.native="editaConferencia()" />
          </q-page-sticky>
          <q-page-sticky position="top-right" :offset="[32, -18]">
            <q-btn round color="faded" icon="close" @click="modalProduto = false" />
          </q-page-sticky>
        </q-modal>
      </div>

      <!-- MODAL DE CONFERÊNCIA DO PRODUTO -->
      <template>
        <q-modal maximized v-model="modalConferencia" v-if="produtoCarregado" @hide="focoCampoBarras()" @show="focoCampoQuantidadeInformada()">
          <div class="column">
            <div class="row justify-center">
              <div class="col-sm-12 col-lg-5 self-center">
                <form @submit.prevent="salvaConferencia()">
                  <q-list>
                    <p class="caption" align="center">
                      <b>
                        {{produto.produto.produto}}<template v-if="produto.produto.variacao">- {{produto.produto.variacao}}</template>
                      </b>
                    </p>
                    <!-- ADICIONAR QUANTIDADE -->
                    <q-item dense>
                      <q-item-main>
                        <q-item-tile>
                          <small>
                            A quantidade atual é
                            <b>{{numeral(parseFloat(produto.saldoatual.quantidade)).format('0,0')}}</b> <small>{{ produto.produto.siglaunidademedida }}</small>
                          </small>
                        </q-item-tile>
                        <q-item-tile>
                          <q-input type="number" float-label="Nova quantidade" v-model="conferencia.quantidadeinformada" ref="campoQuantidadeInformada"/>
                          <mg-erros-validacao :erros="erros.validaQuantidadeinformada"/>
                        </q-item-tile>
                      </q-item-main>
                    </q-item>

                    <!-- ADICIONAR CUSTO MEDIO -->
                    <q-collapsible icon="attach_money" label="Custo do produto">
                      <q-item dense>
                        <q-item-main>
                          <q-item-tile>
                            <small>
                              O custo atual é R$
                              <b>{{numeral(parseFloat(produto.saldoatual.custo)).format('0,0.00')}}</b>
                            </small>
                          </q-item-tile>
                          <q-item-tile>
                            <q-input type="number" float-label="Novo custo" prefix="$" v-model="conferencia.customedioinformado"/>
                          </q-item-tile>
                        </q-item-main>
                      </q-item>
                    </q-collapsible>

                    <!-- ADICIONAR DATA DE VALIDADE -->
                    <q-collapsible icon="access alarm" label="Data de validade">
                      <q-item dense>
                        <q-item-main>
                          <q-item-tile v-if="produto.localizacao.vencimento">
                            <small>
                              A vencimento atual é
                              {{ moment(produto.localizacao.vencimento).toNow() }}
                            </small>
                          </q-item-tile>
                          <q-item-tile>
                            <q-datetime clearable type="date" float-label="Validade" v-model="conferencia.vencimento"/>
                          </q-item-tile>
                        </q-item-main>
                      </q-item>
                    </q-collapsible>

                    <!-- ADICIONAR OBSERVAÇÕES -->
                    <q-collapsible icon="description" label="Observações">
                      <div>
                        <q-item dense>
                          <q-item-main>
                            <q-input type="textarea" float-label="Observações" v-model="conferencia.observacoes"/>
                          </q-item-main>
                        </q-item>
                      </div>
                    </q-collapsible>

                    <!-- ADICIONAR LOCALIZAÇÃO -->
                    <q-collapsible icon="place" label="Localização">
                      <q-item dense>
                        <q-item-main>
                          <q-item-tile v-if="produto.localizacao.corredor">
                            <small>
                              O corredor atual é
                              <b>{{ numeral(produto.localizacao.corredor).format('00') }}</b>
                            </small>
                          </q-item-tile>
                          <q-item-tile>
                            <q-input type="number" float-label="Novo corredor" v-model="conferencia.corredor"/>
                          </q-item-tile>
                        </q-item-main>
                      </q-item>

                      <q-item dense>
                        <q-item-main>
                          <q-item-tile v-if="produto.localizacao.prateleira">
                            <small>
                              A preteleira atual é
                              <b>{{ numeral(produto.localizacao.prateleira).format('00') }}</b>
                            </small>
                          </q-item-tile>
                          <q-item-tile>
                            <q-input type="number" float-label="Nova prateleira" v-model="conferencia.prateleira" />
                          </q-item-tile>
                        </q-item-main>
                      </q-item>

                      <q-item dense>
                        <q-item-main>
                          <q-item-tile v-if="produto.localizacao.coluna">
                            <small>
                              A coluna atual é
                              <b>{{ numeral(produto.localizacao.coluna).format('00') }}</b>
                            </small>
                          </q-item-tile>
                          <q-item-tile>
                            <q-input type="number" float-label="Nova coluna" v-model="conferencia.coluna"/>
                          </q-item-tile>
                        </q-item-main>
                      </q-item>

                      <q-item dense>
                        <q-item-main>
                          <q-item-tile v-if="produto.localizacao.bloco">
                            <small>
                              O bloco atual é
                              <b>{{ numeral(produto.localizacao.bloco).format('00') }}</b>
                            </small>
                          </q-item-tile>
                          <q-item-tile>
                            <q-input type="number" float-label="Novo bloco" v-model="conferencia.bloco"/>
                          </q-item-tile>
                        </q-item-main>
                      </q-item ><br />
                    </q-collapsible>
                  </q-list >
                  <div>
                    <q-page-sticky position="top-right" :offset="[32, -18]">
                      <q-btn round color="faded" icon="close" @click.prevent="modalConferencia = false" />
                    </q-page-sticky>
                    <q-page-sticky position="bottom-right" :offset="[32, -18]">
                      <q-btn round color="primary" icon="done" type="submit" />
                    </q-page-sticky>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </q-modal>
      </template>

      <!-- MODAL DE BUSCA POR BARRAS -->
      <template>
        <q-modal maximized v-model="modalBuscaPorBarras" @show="focoCampoBarras()">
          <div style="padding-top: 20px">
            <div class="row justify-center ">
              <div class="col-sm-12 col-lg-4 ">
                <q-list style="padding:10px" align="center">
                  <form @submit.prevent="buscaProdutoPorBarras()">
                    <q-input v-model="buscaPorBarras.barras" float-label="Código" ref="campoBarras"/>
                  </form>
                  <div align="end">
                    <q-btn flat @click="modalBuscaPorBarras = false" label="Fechar"/>
                  </div>
                </q-list>
              </div>
            </div>
          </div>
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
      erros: {
        validaQuantidadeinformada: null
      },
      buscaPorBarras: {},
      produto: {},
      data: {},
      conferencia: {},
      carregado: false,
      codigoproduto: null,
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
        ret = false
        vm.erros.validaQuantidadeinformada = ['Informe a quantidade']
      } else {
        vm.erros.validaQuantidadeinformada = null
      }
      return ret
    },
    salvaConferencia: function () {
      let vm = this
      if (vm.validaCampos() == false) {
        return
      }
      if(vm.conferencia.customedioinformado == null){
        vm.conferencia.customedioinformado = vm.produto.saldoatual.custo
      }
      if(vm.conferencia.corredor == null && vm.conferencia.prateleira == null && vm.conferencia.coluna == null && vm.conferencia.bloco == null)
      {
        vm.conferencia.corredor = vm.produto.localizacao.corredor
        vm.conferencia.prateleira = vm.produto.localizacao.prateleira
        vm.conferencia.coluna = vm.produto.localizacao.coluna
        vm.conferencia.bloco = vm.produto.localizacao.bloco
      }
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
          vencimento: vm.conferencia.vencimento,
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
        }).then(function (request){
          vm.buscaListagem()
          vm.conferencia = []
        })
      })
    },
    editaConferencia: function() {
      let vm = this
      vm.modalConferencia = true,
      vm.modalProduto = false,
      vm.modalBuscaPorBarras = false,
      vm. conferencia = []
    },
    excluirConferencia: function (codestoquesaldoconferencia) {
      let vm = this
      vm.$q.dialog({
        title: 'Inativar',
        message: 'Tem certeza que deseja inativar?',
        ok: 'Inativar',
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
