<template>
  <mg-layout drawer back-path="/nfe-terceiro">

    <template slot="title">
      Detalhes da nota
    </template>

    <div slot="drawer">

      <template v-if="carregado">
        <q-list>
          <q-list-header>{{nf.emitente}}</q-list-header>

          <q-item dense>
            <q-item-side>
              <q-icon name="date_range" size="25px"/>
              Emissão:
            </q-item-side>
            <q-item-main align="end">{{moment(nf.emissao).format("DD MMM YYYY")}}</q-item-main>
          </q-item>

          <q-item dense>
            <q-item-side>
              <q-icon name="attach_money" size="25px"/>
              Total da nota:
            </q-item-side>
            <q-item-main align="end">R$ {{nf.valortotal}}</q-item-main>
          </q-item>

          <q-item dense>
            <q-item-side>
              <q-icon name="attach_money" size="25px"/>
              Total Complemento:
            </q-item-side>
            <q-item-main align="end">R$ 0000,00</q-item-main>
          </q-item>

          <q-item dense>
            <q-item-side>
              <q-icon name="attach_money" size="25px"/>
              Total Geral:
            </q-item-side>
            <q-item-main align="end">R$ 0000,00</q-item-main>
          </q-item>

          <q-item dense>
            <q-item-main>
              <!--informar natureza da operacao -->
              <q-item-tile>
                <q-field icon="arrow_drop_down_circle">
                  <q-input  float-label="Natureza da oprecação" clearable v-model="filter.filtro" />
                </q-field>
              </q-item-tile>

              <!-- informa data de entrada da nota -->
              <q-item-tile>
                <q-field icon="date_range">
                  <q-input stack-label="Entrada" type="date" v-model="filter.datainicial" align="center" clearable />
                </q-field>
              </q-item-tile>

            </q-item-main>
          </q-item>
          <q-item-separator />
        </q-list>

        <!-- lista de itens -->
        <template v-if="itensCarregado">
          <q-list link  highlight v-for="produto in itens.data" :key="produto.codnotafiscalterceiro">

            <q-item @click.native="produtoCarregado = true, produtoSelecionado = produto, filter.tabsModel = 'produto' ">
              <q-item-main class="q-body-1">

                <!-- <q-item-tile sublabel>
                  <small>index: {{itens.data.indexOf(produto)}}</small>
                </q-item-tile> -->

                <q-item-tile sublabel>
                  {{produto.produto}}
                </q-item-tile>

                <q-item-tile class="text-weight-medium">
                  <template v-if="produto.barras">{{produto.barras}} /</template> {{produto.referencia}}
                </q-item-tile>

              </q-item-main>
            </q-item>

          </q-list>
        </template>

      </template>

    </div>

    <div slot="content">
      <q-tabs v-model="filter.tabsModel" slot="tabHeader">
        <q-tab name="detalhes" slot="title" label="NFe"/>
        <q-tab name="produto" slot="title" label="Produto"/>


        <!-- detalhes da nota -->
        <q-tab-pane name="detalhes">

          <template v-if="carregado">
            <div class="row gutter-sm">
              <div class="col-12">

                <q-card >
                  <q-card-title class="q-py-none">

                    <q-item>
                      <q-item-main>

                        <q-item-tile>
                          {{nf.emitente}} <small class="text-faded">{{nf.cnpj.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, "$1.$2.$3/$4-$5")}} / {{nf.ie}}</small>
                        </q-item-tile>

                        <q-item-tile sublabel style="overflow: hidden" class="text-no-wrap">
                          <small><q-icon name="vpn_key"/>{{nf.nfechave}}</small>
                        </q-item-tile>

                        <q-item-tile sublabel>
                          <small><q-icon name="local_offer"/> {{nf.codnotafiscal}}</small>
                        </q-item-tile>

                      </q-item-main>
                    </q-item>

                  </q-card-title>
                  <q-card-separator />
                  <q-card-main>

                    <div class="row gutter-xs">

                      <!-- Valores -->
                      <div class="col-xs-12 col-sm-6 col-md-3 ">
                        <q-card >
                          <q-card-title align="center">Valores</q-card-title>
                          <q-card-separator />
                          <q-card-main class="q-py-none">

                            <q-item>
                              <q-item-side>Produtos:</q-item-side>
                              <q-item-main align="end">R$ {{nf.valorprodutos}}</q-item-main>
                            </q-item>

                            <q-item>
                              <q-item-side>Frete:</q-item-side>
                              <q-item-main align="end">R$ {{nf.valorfrete}}</q-item-main>
                            </q-item>

                            <q-item>
                              <q-item-side>Seguro:</q-item-side>
                              <q-item-main align="end">R$ {{nf.valorseguro}}</q-item-main>
                            </q-item>

                            <q-item>
                              <q-item-side>Desconto:</q-item-side>
                              <q-item-main align="end">R$ {{nf.valordesconto}}</q-item-main>
                            </q-item>

                            <q-item>
                              <q-item-side>Outros:</q-item-side>
                              <q-item-main align="end">R$ {{nf.valoroutras}}</q-item-main>
                            </q-item>

                            <q-item>
                              <q-item-side>Total:</q-item-side>
                              <q-item-main align="end">R$ {{nf.valortotal}}</q-item-main>
                            </q-item>

                          </q-card-main>
                        </q-card>
                      </div>

                      <!-- ICMS -->
                      <div class="col-xs-12 col-sm-6 col-md-3">
                        <q-card>
                          <q-card-title align="center">ICMS</q-card-title>
                          <q-card-separator />
                          <q-card-main class="q-py-none">

                            <q-item>
                              <q-item-side>Base:</q-item-side>
                              <q-item-main align="end">R$ {{nf.icmsbase}}</q-item-main>
                            </q-item>

                            <q-item>
                              <q-item-side>Total:</q-item-side>
                              <q-item-main align="end">R$ {{nf.icmsvalor}}</q-item-main>
                            </q-item>

                          </q-card-main>
                        </q-card>
                      </div>

                      <!-- ICMSST -->
                      <div class="col-xs-12 col-sm-6 col-md-3">
                        <q-card>
                          <q-card-title align="center">
                            ICMSST
                          </q-card-title>
                          <q-card-separator />
                          <q-card-main class="q-py-none">

                            <q-item>
                              <q-item-side>Base:</q-item-side>
                              <q-item-main align="end">R$ {{nf.icmsstbase}}</q-item-main>
                            </q-item>

                            <q-item>
                              <q-item-side>Total:</q-item-side>
                              <q-item-main align="end">R$ {{nf.icmsstvalor}}</q-item-main>
                            </q-item>

                          </q-card-main>
                        </q-card>
                      </div>

                      <!-- IPI -->
                      <div class="col-xs-12 col-sm-6 col-md-3">
                        <q-card>
                          <q-card-title align="center">
                            IPI
                          </q-card-title>
                          <q-card-separator />
                          <q-card-main  class="q-py-none">

                            <q-item>
                              <q-item-side>Total:</q-item-side>
                              <q-item-main align="end">R$ {{nf.ipivalor}}</q-item-main>
                            </q-item>

                          </q-card-main>
                        </q-card>
                      </div>

                    </div>
                  </q-card-main>
                </q-card>

              </div>
            </div>

            <q-page-sticky position="bottom-right" :offset="[25, 25]">
              <q-btn round color="primary" icon="done" @click="atualizaNota()" />
            </q-page-sticky>

          </template>

          <template v-else>
            <q-page padding>
              <center>
                <div style="max-width:50vw" class="text-center">
                  <q-card color="orange">
                    <q-card-title>Primeiro baixe a nota para ver os detalhes</q-card-title>
                  </q-card>
                </div>
              </center>
            </q-page>
          </template>

          <!-- fim do detalhes da nota -->
        </q-tab-pane>

        <!-- detalhes do produto -->
        <q-tab-pane name="produto" style="height:100vh">

          <template v-if="produtoCarregado">
            <q-list class="q-pa-xs">
              <q-list-header>{{produtoSelecionado.produto}}</q-list-header>
              <div class="row gutter-xs">

                <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">
                  <q-card>

                    <!-- <q-card-title align="center">
                      ICMSST
                    </q-card-title>
                    <q-card-separator /> -->

                    <q-card-main class="q-pa-none">

                      <q-item class="q-body-1">
                        <q-item-side>Referência:</q-item-side>
                        <q-item-main align="end">{{ produtoSelecionado.referencia}}</q-item-main>
                      </q-item>

                      <q-item class="q-body-1" v-if="produtoSelecionado.barras">
                        <q-item-side>Barras:</q-item-side>
                        <q-item-main align="end">{{ produtoSelecionado.barras }}</q-item-main>
                      </q-item>

                      <q-item class="q-body-1" v-if="produtoSelecionado.barrastributavel">
                        <q-item-side>Barras Tributável:</q-item-side>
                        <q-item-main align="end"></q-item-main>{{ produtoSelecionado.barrastributavel }}</q-item-main>
                      </q-item>

                      <q-item class="q-body-1">
                        <q-item-side>NCM:</q-item-side>
                        <q-item-main align="end">{{ produtoSelecionado.ncm }}</q-item-main>
                      </q-item>

                      <q-item class="q-body-1">
                        <q-item-side>CFOP:</q-item-side>
                        <q-item-main align="end">{{ produtoSelecionado.cfop }}</q-item-main>
                      </q-item>

                      <q-item class="q-body-1">
                        <q-item-side>Origem:</q-item-side>
                        <q-item-main align="end">{{ produtoSelecionado.origem }}</q-item-main>
                      </q-item>

                      <q-item class="q-body-1">
                        <q-item-side>CSOSN:</q-item-side>
                        <q-item-main align="end">{{ produtoSelecionado.csosn }}</q-item-main>
                      </q-item>

                      <q-item class="q-body-1">
                        <q-item-side>Total:</q-item-side>
                        <q-item-main align="end">R$ {{ numeral(parseFloat(produtoSelecionado.valortotal)).format('0,0.00') }}</q-item-main>
                      </q-item>

                    </q-card-main>
                  </q-card>
                </div>

                <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">
                  <q-card>

                    <!-- <q-card-title align="center">
                      ICMSST
                    </q-card-title>
                    <q-card-separator /> -->

                    <q-card-main class="q-pa-none">

                      <q-item class="q-body-1">
                        <q-item-side>Quantidade:</q-item-side>
                        <q-item-main align="end">{{ parseFloat(produtoSelecionado.quantidade) }} - {{produtoSelecionado.unidademedida}}</q-item-main>
                      </q-item>

                      <q-item class="q-body-1">
                        <q-item-side>Valor unidade:</q-item-side>
                        <q-item-main align="end">R$ {{ numeral(parseFloat(produtoSelecionado.valorunitario)).format('0,0.00') }}</q-item-main>
                      </q-item>

                      <q-item class="q-body-1">
                        <q-item-side>Total produto:</q-item-side>
                        <q-item-main align="end">R$ {{ numeral(parseFloat(produtoSelecionado.valorproduto)).format('0,0.00') }}</q-item-main>
                      </q-item>

                      <q-item class="q-body-1">
                        <q-item-side>Frete:</q-item-side>
                        <q-item-main align="end">R$ {{ numeral(parseFloat(produtoSelecionado.valorfrete)).format('0,0.00') }}</q-item-main>
                      </q-item>

                      <q-item class="q-body-1">
                        <q-item-side>Seguro:</q-item-side>
                        <q-item-main align="end">R$ {{ numeral(parseFloat(produtoSelecionado.valorseguro)).format('0,0.00') }}</q-item-main>
                      </q-item>

                      <q-item class="q-body-1">
                        <q-item-side>Desconto:</q-item-side>
                        <q-item-main align="end">R$ {{ numeral(parseFloat(produtoSelecionado.valordesconto)).format('0,0.00') }}</q-item-main>
                      </q-item>

                      <q-item class="q-body-1">
                        <q-item-side>Outras:</q-item-side>
                        <q-item-main align="end">R$ {{ numeral(parseFloat(produtoSelecionado.valoroutras)).format('0,0.00') }}</q-item-main>
                      </q-item>

                      <q-item class="q-body-1">
                        <q-item-side>Total:</q-item-side>
                        <q-item-main align="end">R$ {{ numeral(parseFloat(produtoSelecionado.valortotal)).format('0,0.00') }}</q-item-main>
                      </q-item>

                    </q-card-main>
                  </q-card>
                </div>

                <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">
                  <q-card>

                    <!-- <q-card-title align="center">
                      ICMSST
                    </q-card-title>
                    <q-card-separator /> -->

                    <q-card-main class="q-pa-none">

                      <q-item class="q-body-1">
                        <q-item-side>IcmsBaseModalidade:</q-item-side>
                        <q-item-main align="end">{{ produtoSelecionado.icmsbasemodalidade }}</q-item-main>
                      </q-item>

                      <q-item class="q-body-1">
                        <q-item-side>ICMSCST:</q-item-side>
                        <q-item-main align="end">R$ {{ numeral(parseFloat(produtoSelecionado.icmscst)).format('0,0.00') }}</q-item-main>
                      </q-item>

                      <q-item class="q-body-1">
                        <q-item-side>ICMS Base:</q-item-side>
                        <q-item-main align="end">R$ {{ numeral(parseFloat(produtoSelecionado.icmsbase)).format('0,0.00') }}</q-item-main>
                      </q-item>

                      <q-item class="q-body-1">
                        <q-item-side>ICMS Percentual:</q-item-side>
                        <q-item-main align="end">R$ {{ numeral(parseFloat(produtoSelecionado.icmspercentual)).format('0,0.00') }}</q-item-main>
                      </q-item>

                      <q-item class="q-body-1">
                        <q-item-side>ICMS Valor:</q-item-side>
                        <q-item-main align="end">R$ {{ numeral(parseFloat(produtoSelecionado.icmsvalor)).format('0,0.00') }}</q-item-main>
                      </q-item>

                      <q-item class="q-body-1">
                        <q-item-side>IcmsStBseModalidade:</q-item-side>
                        <q-item-main align="end">R$ {{ numeral(parseFloat(produtoSelecionado.icmsstbasemodalidade)).format('0,0.00') }}</q-item-main>
                      </q-item>

                      <q-item class="q-body-1">
                        <q-item-side>IcmsStBase:</q-item-side>
                        <q-item-main align="end">R$ {{ numeral(parseFloat(produtoSelecionado.icmsstbase)).format('0,0.00') }}</q-item-main>
                      </q-item>

                      <q-item class="q-body-1">
                        <q-item-side>IcmsStPercentual:</q-item-side>
                        <q-item-main align="end">R$ {{ numeral(parseFloat(produtoSelecionado.icmsstpercentual)).format('0,0.00') }}</q-item-main>
                      </q-item>

                      <q-item class="q-body-1">
                        <q-item-side>IcmsStValor:</q-item-side>
                        <q-item-main align="end">R$ {{ numeral(parseFloat(produtoSelecionado.icmsstvalor)).format('0,0.00') }}</q-item-main>
                      </q-item>

                    </q-card-main>
                  </q-card>
                </div>

                <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">
                  <q-card>

                    <!-- <q-card-title align="center">
                      ICMSST
                    </q-card-title>
                    <q-card-separator /> -->

                    <q-card-main class="q-pa-none">

                      <q-item class="q-body-1">
                        <q-item-side>IPICST:</q-item-side>
                        <q-item-main align="end">{{ produtoSelecionado.ipicst }}</q-item-main>
                      </q-item>

                      <q-item class="q-body-1">
                        <q-item-side>IPI Base:</q-item-side>
                        <q-item-main align="end">R$ {{ numeral(parseFloat(produtoSelecionado.ipibase)).format('0,0.00') }}</q-item-main>
                      </q-item>

                      <q-item class="q-body-1">
                        <q-item-side>IPI Percentual:</q-item-side>
                        <q-item-main align="end">R$ {{ numeral(parseFloat(produtoSelecionado.ipipercentual)).format('0,0.00') }}</q-item-main>
                      </q-item>

                      <q-item class="q-body-1">
                        <q-item-side>IPI Valor :</q-item-side>
                        <q-item-main align="end">R$ {{ numeral(parseFloat(produtoSelecionado.ipivalor)).format('0,0.00') }}</q-item-main>
                      </q-item>

                      <q-item class="q-body-1">
                        <q-item-side>PISCST:</q-item-side>
                        <q-item-main align="end">{{ produtoSelecionado.piscst }}</q-item-main>
                      </q-item>

                      <q-item class="q-body-1">
                        <q-item-side>PIS Base:</q-item-side>
                        <q-item-main align="end">R$ {{ numeral(parseFloat(produtoSelecionado.pisbase)).format('0,0.00') }}</q-item-main>
                      </q-item>

                      <q-item class="q-body-1">
                        <q-item-side>PIS Percentual:</q-item-side>
                        <q-item-main align="end">R$ {{ numeral(parseFloat(produtoSelecionado.pispercentual)).format('0,0.00') }}</q-item-main>
                      </q-item>

                      <q-item class="q-body-1">
                        <q-item-side>PIS Valor:</q-item-side>
                        <q-item-main align="end">R$ {{ numeral(parseFloat(produtoSelecionado.pisvalor)).format('0,0.00') }}</q-item-main>
                      </q-item>

                      <q-item class="q-body-1">
                        <q-item-side>COFINSCST:</q-item-side>
                        <q-item-main align="end">{{ produtoSelecionado.cofinscst }}</q-item-main>
                      </q-item>

                      <q-item class="q-body-1">
                        <q-item-side>COFINS Base:</q-item-side>
                        <q-item-main align="end">R$ {{ numeral(parseFloat(produtoSelecionado.cofinsbase)).format('0,0.00') }}</q-item-main>
                      </q-item>

                      <q-item class="q-body-1">
                        <q-item-side>COFINS Percentual:</q-item-side>
                        <q-item-main align="end">R$ {{ numeral(parseFloat(produtoSelecionado.cofinspercentual)).format('0,0.00') }}</q-item-main>
                      </q-item>

                      <q-item class="q-body-1">
                        <q-item-side>COFINS Valor:</q-item-side>
                        <q-item-main align="end">R$ {{ numeral(parseFloat(produtoSelecionado.cofinsvalor)).format('0,0.00') }}</q-item-main>
                      </q-item>

                    </q-card-main>
                  </q-card>
                </div>

              </div>
            </q-list>
          </template>

          <q-page-sticky position="bottom-right" :offset="[25, 25]">
            <q-btn round color="primary" icon="done" @click="atualizaNota()" />
          </q-page-sticky>
        </q-tab-pane>

      </q-tabs>

    </div>
  </mg-layout>
</template>

<script>
import MgSelectEstoqueLocal from '../../utils/select/MgSelectEstoqueLocal'
import MgLayout from '../../../layouts/MgLayout'
import MgErrosValidacao from '../../utils/MgErrosValidacao'
import MgAutocompleteMarca from '../../utils/autocomplete/MgAutocompleteMarca'

export default {
  name: 'nfe-terceiro-detalhes-nfe',
  components: {
    MgLayout,
    MgSelectEstoqueLocal,
    MgErrosValidacao,
    MgAutocompleteMarca
  },
  data() {
    return {
      filter: {
        tabsModel: 'detalhes',
      },
      data: {},
      carregado: false,
      itensCarregado: false,
      produtoSelecionado: null,
      produtoCarregado: false,
    }
  },
  watch: {
    // observa filtro, sempre que alterado chama a api
    filter: {
      handler: function (val, oldVal) {},
      deep: true
    }

  },
  methods: {
    carregaNota: function() {
      let vm = this
      let params= {
        chave: this.filter.chave
      }
      vm.$axios.get('nfe-terceiro/busca-nfeterceiro',{params}).then(function(request){
        if (request !== null){
          vm.nf = request.data[0]
          console.log(vm.nf)
          vm.carregaItens(vm.nf.codnotafiscalterceiro)
          vm.carregado = true
        }else{
          vm.carregado = false
        }
      }).catch(function(error) {
        console.log(error)
      })
    },

    carregaItens: function(codnotafiscalterceiro) {
      let vm = this
      let params= {
        codnotafiscalterceiro: codnotafiscalterceiro
      }
      // console.log(params)
      vm.$axios.get('nfe-terceiro/lista-item',{params}).then(function(request){
        vm.itens = request
        // console.log(request)
        vm.itensCarregado = true
      }).catch(function(error) {
        console.log(error)
      })
    },

  },
  mounted() {
    this.filter.chave = this.$route.params.chave
    this.carregaNota();

  }
}
</script>
