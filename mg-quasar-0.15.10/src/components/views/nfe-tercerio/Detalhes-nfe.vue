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
            <q-item-main align="end">R$ {{numeral(parseFloat(nf.valortotal)).format('0,0.00')}}</q-item-main>
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
                  <mg-select-natureza-operacao label="Natureza da operação" v-model="natop" />
                </q-field>
              </q-item-tile>

              <!-- informa data de entrada da nota -->
              <q-item-tile>
                <q-field icon="date_range">
                  <q-input stack-label="Entrada" type="date" v-model="dataEntrada" align="center" clearable />
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

                        <q-item-tile sublabel>
                          <small><q-icon name="business_center"/> {{nf.natop}}</small>
                        </q-item-tile>

                      </q-item-main>

                      <q-item-side icon="location_on" color="red">
                        {{nf.filial}}
                      </q-item-side>
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
                              <q-item-main align="end">R$ {{numeral(parseFloat(nf.valorprodutos)).format('0,0.00')}}</q-item-main>
                            </q-item>

                            <q-item>
                              <q-item-side>Frete:</q-item-side>
                              <q-item-main align="end">R$ {{numeral(parseFloat(nf.valorfrete)).format('0,0.00')}}</q-item-main>
                            </q-item>

                            <q-item>
                              <q-item-side>Seguro:</q-item-side>
                              <q-item-main align="end">R$ {{numeral(parseFloat(nf.valorseguro)).format('0,0.00')}}</q-item-main>
                            </q-item>

                            <q-item>
                              <q-item-side>Desconto:</q-item-side>
                              <q-item-main align="end">R$ {{numeral(parseFloat(nf.valordesconto)).format('0,0.00')}}</q-item-main>
                            </q-item>

                            <q-item>
                              <q-item-side>Outros:</q-item-side>
                              <q-item-main align="end">R$ {{numeral(parseFloat(nf.valoroutras)).format('0,0.00')}}</q-item-main>
                            </q-item>

                            <q-item>
                              <q-item-side>Total:</q-item-side>
                              <q-item-main align="end">R$ {{numeral(parseFloat(nf.valortotal)).format('0,0.00')}}</q-item-main>
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
                              <q-item-main align="end">R$ {{numeral(parseFloat(nf.icmsbase)).format('0,0.00')}}</q-item-main>
                            </q-item>

                            <q-item>
                              <q-item-side>Total:</q-item-side>
                              <q-item-main align="end">R$ {{numeral(parseFloat(nf.icmsvalor)).format('0,0.00')}}</q-item-main>
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
                              <q-item-main align="end">R$ {{numeral(parseFloat(nf.icmsstbase)).format('0,0.00')}}</q-item-main>
                            </q-item>

                            <q-item>
                              <q-item-side>Total:</q-item-side>
                              <q-item-main align="end">R$ {{numeral(parseFloat(nf.icmsstvalor)).format('0,0.00')}}</q-item-main>
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
                              <q-item-main align="end">R$ {{numeral(parseFloat(nf.ipivalor)).format('0,0.00')}}</q-item-main>
                            </q-item>

                          </q-card-main>
                        </q-card>
                      </div>

                    </div>
                  </q-card-main>
                </q-card>

              </div>
            </div>
          </template>

          <template v-else>
            <q-page padding>
              <div>
                <q-card v-if="dfecarregada">

                  <q-card-title>
                    <q-item>
                      <q-item-main>
                        <div class="row">
                          {{dfe.emitente}}
                        </div>
                        <div class="row">
                          <small class="text-faded">
                            {{dfe.cnpj.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, "$1.$2.$3/$4-$5")}} / {{dfe.ie}}
                          </small>
                        </div>
                      </q-item-main>
                      <q-item-side icon="location_on" color="red">
                        {{dfe.filial}}
                      </q-item-side>
                    </q-item>
                  </q-card-title>
                  <q-card-separator />
                  <q-card-main>

                    <div class="row">
                      <div class="col-12" style="overflow:hidden">
                        <q-item>
                          <q-item-side icon="vpn_key"/>
                          <q-item-main>
                            {{dfe.nfechave}}
                            <q-chip square dense v-if="dfe.csitnfe == 1" color="green">Autorizada</q-chip>
                            <q-chip square dense v-if="dfe.csitnfe == 3" color="red">Cancelada</q-chip>
                            <q-chip square dense v-if="dfe.csitnfe == 2" color="red">Denegada</q-chip>
                          </q-item-main>
                        </q-item>
                      </div>
                    </div>

                    <div class="row">
                      <div class="col-12">
                        <q-item>
                          <q-item-side icon="offline_pin"/>
                          <q-item-main>
                            {{dfe.protocolo}}
                          </q-item-main>
                        </q-item>
                      </div>
                    </div>

                    <div class="row">
                      <div class="col-12">
                        <q-item>
                          <q-item-side icon="swap_horizontal_circle"/>
                          <q-item-main>
                            <template v-if="dfe.tipo == 1">Saída</template>
                            <template v-if="dfe.tipo == 0">Entrada</template>
                          </q-item-main>
                        </q-item>
                      </div>
                    </div>

                    <div class="row">
                      <div class="col-12">
                        <q-item>
                          <q-item-side icon="date_range"/>
                          <q-item-main>
                            {{moment(dfe.emissao).format("DD MMM YYYY HH:mm:ss")}}
                          </q-item-main>
                        </q-item>
                      </div>
                    </div>

                    <div class="row">
                      <div class="col-12">
                        <q-item>
                          <q-item-side icon="attach_money"/>
                          <q-item-main>
                            R$ {{numeral(parseFloat(dfe.valortotal)).format('0,0.00')}}
                          </q-item-main>
                        </q-item>
                      </div>
                    </div>

                  </q-card-main>
                  <q-card-separator/>
                  <q-card-main>

                    <div class="row items-center">
                      <div class="col-1">
                        <q-btn color="primary" @click.native="modalManifestacao = true, chaveManifestacao = dfe.nfechave, filialManifestacao = dfe.codfilial" round dense>
                          <q-icon name="arrow_drop_down_circle" size="25px"/>
                        </q-btn>
                        <q-tooltip anchor="top left" self="bottom middle">
                          Manifestação
                        </q-tooltip>
                      </div>

                      <div class="col-1">
                        <q-btn dense @click="downloadNFe(dfe.codfilial, dfe.nfechave)" round color="primary" icon="cloud_download"/>
                        <q-tooltip anchor="top left" self="bottom middle">
                          Baixar XML
                        </q-tooltip>
                      </div>

                      <div class="col-3">
                        <q-field >
                          <q-uploader inverted :url="url" stack-label="localizar XML"/>
                        </q-field>
                        <q-tooltip anchor="top left" self="bottom middle">
                          Localizar XML
                        </q-tooltip>
                      </div>
                    </div>

                  </q-card-main>
                </q-card>
              </div>
            </q-page>
          </template>

          <!-- MODAL DE MANIFESTACAO -->
          <template>
            <q-modal v-model="modalManifestacao">

              <q-card>
                <q-card-title align="center">
                  Manifestacão
                </q-card-title>
                <q-card-separator />
                <q-card-main>

                  <q-item>
                    <q-item-main>
                      <div class=" gutter-y-sm">
                        <div class="row">
                          <q-radio v-model="codmanifestacao" val="210210" label="Ciência da operação" color="orange"/>
                        </div>

                        <div class="row">
                          <q-radio v-model="codmanifestacao" val="210200" label="operação realizada" color="green"/>
                        </div>

                        <div class="row">
                          <q-radio v-model="codmanifestacao" val="210220" label="operação desconhecida" color="red"/>
                        </div>

                        <div class="row">
                          <q-radio v-model="codmanifestacao" val="210240" label="operação não realizada" color="red"/>
                        </div>
                      </div>
                    </q-item-main>
                  </q-item>
                  <q-item-separator/>

                  <q-item v-if="codmanifestacao == 210240 || codmanifestacao == 210220">
                    <q-item-main>
                      <q-field :count="15">
                        <q-input clearable min-length="15" type="textarea" inverted-light color="warning" v-model="justificativa" float-label="Justificativa"/>
                      </q-field>
                    </q-item-main>
                  </q-item>

                </q-card-main>
                <q-card-separator/>

                <q-card-actions align="end">
                  <q-btn color="red" @click="modalManifestacao = false" icon="arrow_back" round dense />
                  <q-btn color="primary" @click="enviarManifestacao()" icon="done" round dense />
                </q-card-actions>

              </q-card>
            </q-modal>
          </template>

          <!-- fim do detalhes da nota -->
        </q-tab-pane>

        <!-- detalhes do produto -->
        <q-tab-pane name="produto">

          <template v-if="produtoCarregado">
            <q-list class="q-pa-xs" no-border>
              <q-list-header>{{produtoSelecionado.produto}}</q-list-header>
              <div class="row gutter-xs">

                <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">
                  <q-card>

                    <q-card-media>
                      <img src="~assets/dummy.png" />
                    </q-card-media>

                    <q-card-main class="q-pa-none gutter-y-none">

                      <q-list highlight>

                        <q-item class="q-body-1">
                          <q-item-side>Quantidade:</q-item-side>
                          <q-item-main align="end">{{ parseFloat(produtoSelecionado.quantidade) }} - {{produtoSelecionado.unidademedida}}</q-item-main>
                        </q-item>

                        <q-item class="q-body-1">
                          <q-item-side>Referência:</q-item-side>
                          <q-item-main align="end">{{ produtoSelecionado.referencia}}</q-item-main>
                        </q-item>

                        <q-item class="q-body-1" v-if="produtoSelecionado.barras">
                          <q-item-side>CEAN:</q-item-side>
                          <q-item-main align="end">{{ produtoSelecionado.barras }}</q-item-main>
                        </q-item>

                        <q-item class="q-body-1" v-if="produtoSelecionado.barrastributavel">
                          <q-item-side>CEAN Trib:</q-item-side>
                          <q-item-main align="end"></q-item-main>{{ produtoSelecionado.barrastributavel }}</q-item-main>
                        </q-item>

                      </q-list>
                    </q-card-main>
                  </q-card>
                </div>

                <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">
                  <q-card>

                    <!-- <q-card-title align="center">
                      ICMSST
                    </q-card-title>
                    <q-card-separator /> -->

                    <q-card-main class="q-pa-none gutter-y-none">
                      <q-list highlight>

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

                      </q-list>
                    </q-card-main>
                  </q-card>
                </div>

                <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">
                  <q-card>

                    <!-- <q-card-title align="center">
                      ICMSST
                    </q-card-title>
                    <q-card-separator /> -->

                    <q-card-main class="q-pa-none gutter-y-none">
                      <q-list highlight>

                        <q-item class="q-body-1">
                          <q-item-side>ICMS Modalidade:</q-item-side>
                          <q-item-main align="end">{{ produtoSelecionado.icmsbasemodalidade }}</q-item-main>
                        </q-item>

                        <q-item class="q-body-1">
                          <q-item-side>ICMS CST:</q-item-side>
                          <q-item-main align="end">R$ {{ numeral(parseFloat(produtoSelecionado.icmscst)).format('0,0.00') }}</q-item-main>
                        </q-item>

                        <q-item class="q-body-1">
                          <q-item-side>ICMS Base:</q-item-side>
                          <q-item-main align="end">R$ {{ numeral(parseFloat(produtoSelecionado.icmsbase)).format('0,0.00') }}</q-item-main>
                        </q-item>

                        <q-item class="q-body-1">
                          <q-item-side>ICMS%:</q-item-side>
                          <q-item-main align="end">R$ {{ numeral(parseFloat(produtoSelecionado.icmspercentual)).format('0,0.00') }}</q-item-main>
                        </q-item>

                        <q-item class="q-body-1">
                          <q-item-side>ICMS Valor:</q-item-side>
                          <q-item-main align="end">R$ {{ numeral(parseFloat(produtoSelecionado.icmsvalor)).format('0,0.00') }}</q-item-main>
                        </q-item>

                        <q-item class="q-body-1">
                          <q-item-side>ICMS ST Modalidade:</q-item-side>
                          <q-item-main align="end">{{ produtoSelecionado.icmsstbasemodalidade }}</q-item-main>
                        </q-item>

                        <q-item class="q-body-1">
                          <q-item-side>ICMS ST Base:</q-item-side>
                          <q-item-main align="end">R$ {{ numeral(parseFloat(produtoSelecionado.icmsstbase)).format('0,0.00') }}</q-item-main>
                        </q-item>

                        <q-item class="q-body-1">
                          <q-item-side>ICMS ST%:</q-item-side>
                          <q-item-main align="end">R$ {{ numeral(parseFloat(produtoSelecionado.icmsstpercentual)).format('0,0.00') }}</q-item-main>
                        </q-item>

                        <q-item class="q-body-1">
                          <q-item-side>ICMS ST Valor:</q-item-side>
                          <q-item-main align="end">R$ {{ numeral(parseFloat(produtoSelecionado.icmsstvalor)).format('0,0.00') }}</q-item-main>
                        </q-item>

                      </q-list>
                    </q-card-main>
                  </q-card>
                </div>

                <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">
                  <q-card>

                    <!-- <q-card-title align="center">
                      ICMSST
                    </q-card-title>
                    <q-card-separator /> -->

                    <q-card-main class="q-pa-none gutter-y-none">
                      <q-list highlight>

                        <q-item class="q-body-1">
                          <q-item-side>IPI CST:</q-item-side>
                          <q-item-main align="end">{{ produtoSelecionado.ipicst }}</q-item-main>
                        </q-item>

                        <q-item class="q-body-1">
                          <q-item-side>IPI Base:</q-item-side>
                          <q-item-main align="end">R$ {{ numeral(parseFloat(produtoSelecionado.ipibase)).format('0,0.00') }}</q-item-main>
                        </q-item>

                        <q-item class="q-body-1">
                          <q-item-side>IPI%:</q-item-side>
                          <q-item-main align="end">R$ {{ numeral(parseFloat(produtoSelecionado.ipipercentual)).format('0,0.00') }}</q-item-main>
                        </q-item>

                        <q-item class="q-body-1">
                          <q-item-side>IPI Valor :</q-item-side>
                          <q-item-main align="end">R$ {{ numeral(parseFloat(produtoSelecionado.ipivalor)).format('0,0.00') }}</q-item-main>
                        </q-item>

                        <q-item class="q-body-1">
                          <q-item-side>PIS CST:</q-item-side>
                          <q-item-main align="end">{{ produtoSelecionado.piscst }}</q-item-main>
                        </q-item>

                        <q-item class="q-body-1">
                          <q-item-side>PIS Base:</q-item-side>
                          <q-item-main align="end">R$ {{ numeral(parseFloat(produtoSelecionado.pisbase)).format('0,0.00') }}</q-item-main>
                        </q-item>

                        <q-item class="q-body-1">
                          <q-item-side>PIS%:</q-item-side>
                          <q-item-main align="end">R$ {{ numeral(parseFloat(produtoSelecionado.pispercentual)).format('0,0.00') }}</q-item-main>
                        </q-item>

                        <q-item class="q-body-1">
                          <q-item-side>PIS Valor:</q-item-side>
                          <q-item-main align="end">R$ {{ numeral(parseFloat(produtoSelecionado.pisvalor)).format('0,0.00') }}</q-item-main>
                        </q-item>

                        <q-item class="q-body-1">
                          <q-item-side>COFINS CST:</q-item-side>
                          <q-item-main align="end">{{ produtoSelecionado.cofinscst }}</q-item-main>
                        </q-item>

                        <q-item class="q-body-1">
                          <q-item-side>COFINS Base:</q-item-side>
                          <q-item-main align="end">R$ {{ numeral(parseFloat(produtoSelecionado.cofinsbase)).format('0,0.00') }}</q-item-main>
                        </q-item>

                        <q-item class="q-body-1">
                          <q-item-side>COFINS%:</q-item-side>
                          <q-item-main align="end">R$ {{ numeral(parseFloat(produtoSelecionado.cofinspercentual)).format('0,0.00') }}</q-item-main>
                        </q-item>

                        <q-item class="q-body-1">
                          <q-item-side>COFINS Valor:</q-item-side>
                          <q-item-main align="end">R$ {{ numeral(parseFloat(produtoSelecionado.cofinsvalor)).format('0,0.00') }}</q-item-main>
                        </q-item>

                      </q-list>
                    </q-card-main>
                  </q-card>
                </div>

              </div>
            </q-list>
          </template>
        </q-tab-pane>

        <br />
        <br />
      </q-tabs>

      <template>
        <q-modal v-model="modalDividirProduto" maximized>
          <q-page padding>

            <q-list v-if="produtoSelecionado" no-border>
              <q-list-header>{{produtoSelecionado.produto}}</q-list-header>
              <q-item>
                <q-item-main>

                  <div class="row">
                    <div class="col-xs-12">
                      <div class="row">
                        Quantidade
                      </div>
                      <div class="row">
                        {{parseFloat(produtoSelecionado.quantidade)}} {{produtoSelecionado.unidademedida}}
                      </div>
                    </div>
                    <div class="col-xs-12">
                      <div class="row">
                        valor unitário
                      </div>
                      <div class="row">
                        {{produtoSelecionado.valorunitario}}
                      </div>
                    </div>
                    <div class="col-xs-12">
                      <div class="row">
                        Total do produto
                      </div>
                      <div class="row">
                        {{produtoSelecionado.valorproduto}}
                      </div>
                    </div>
                  </div>
                </q-item-main>

                <q-item-side class="gutter-y-xs">
                  <q-item-tile>
                    <q-btn-dropdown label="Dividir Item" color="primary">
                      <q-list link highlight>

                        <q-item>
                          <q-item-main>
                            <q-radio v-model="tipoDivisao" val="1" label="Quantidade" />
                          </q-item-main>
                        </q-item>

                        <q-item>
                          <q-item-main>
                            <q-radio v-model="tipoDivisao" val="2" label="Valor" />
                          </q-item-main>
                        </q-item>

                      </q-list>
                    </q-btn-dropdown>
                  </q-item-tile>

                  <q-item-tile>
                    <q-btn label="Agrupar item" color="primary"/>
                  </q-item-tile>

                </q-item-side>
              </q-item>
            </q-list>

            <template>
              <q-list v-for="item in quantidadeDivisao" :key="item" no-border>
                <q-item >
                  <q-item-main>
                    <div class="row gutter-xs">
                      <div class="col-12">

                        <q-card>
                          <q-card-main>
                            <div class="col-xs-12 col-sm-6 col-md-3 col-lg-2">
                              <q-input v-model="itemDividido.prod[item]" float-label="Nome" clearable/>
                              <q-input v-model="itemDividido.val[item]" float-label="valor" clearable/>
                            </div>
                          </q-card-main>
                        </q-card>

                      </div>
                    </div>
                  </q-item-main>
                </q-item>
              </q-list>
            </template>

            <q-page-sticky position="bottom-right" :offset="[25, 80]">
              <q-btn round color="red" icon="arrow_back" @click="modalDividirProduto = false" />
            </q-page-sticky>
            <q-page-sticky position="bottom-right" :offset="[25, 25]">
              <q-btn round color="primary" icon="done" @click="dividirItem()" />
            </q-page-sticky>
          </q-page>
        </q-modal>
      </template>

      <template>
        <q-modal v-model="modalTipoDivisao">
          <q-card>

            <q-card-title>
              Informe a quantidade que deseja dividir
            </q-card-title>
            <q-card-separator />

            <q-card-main>
              <q-input clearable type="number" float-label="Quantidade" v-model="quantidadeDivisao"/>
            </q-card-main>
            <q-card-separator/>

            <q-card-actions align="end">
              <q-btn dense round icon="arrow_back" color="red" @click="modalTipoDivisao = false"/>
              <q-btn dense round icon="done" color="primary" @click.native="dividirItem()"/>
            </q-card-actions>

          </q-card>
        </q-modal>
      </template>

      <q-page-sticky position="bottom-right" :offset="[25, 25]" v-if="carregado">
        <q-fab icon="add" direction="up" color="primary" dense>
          <q-fab-action color="primary" icon="done" @click="atualizaNota()" />
          <q-fab-action v-if="produtoSelecionado" @click="modalDividirProduto = true" color="primary" icon="edit" />
        </q-fab>
      </q-page-sticky>


    </div>
  </mg-layout>
</template>

<script>
import MgSelectEstoqueLocal from '../../utils/select/MgSelectEstoqueLocal'
import MgSelectNaturezaOperacao from '../../utils/select/MgSelectNaturezaOperacao'
import MgLayout from '../../../layouts/MgLayout'
import MgErrosValidacao from '../../utils/MgErrosValidacao'
import MgAutocompleteMarca from '../../utils/autocomplete/MgAutocompleteMarca'

export default {
  name: 'nfe-terceiro-detalhes-nfe',
  components: {
    MgLayout,
    MgSelectEstoqueLocal,
    MgSelectNaturezaOperacao,
    MgErrosValidacao,
    MgAutocompleteMarca
  },
  data() {
    return {
      filter: {
        tabsModel: 'detalhes',
      },
      data: {},
      itemDividido: {
        prod:{},
        val:{}
      },
      url: '192.168.1.185/upload.php',
      prod: null,
      carregado: false,
      dfecarregada: false,
      itensCarregado: false,
      produtoSelecionado: null,
      produtoCarregado: false,
      natop: null,
      dataEntrada: null,
      modalDividirProduto: false,
      modalTipoDivisao: null,
      modalManifestacao: false,
      tipoDivisao: null,
      quantidadeDivisao: null,
      justificativa: null,
      codmanifestacao: null,
      chaveManifestacao: null,
      filialManifestacao: null,
    }
  },
  watch: {
    tipoDivisao: function(tipo){
      this.modalTipoDivisao = true
    },
    dataEntrada: function(data){
      console.log(data)
    },
    natop: function(operacao){
      console.log(operacao)
    },
    // observa filtro, sempre que alterado chama a api
    filter: {
      handler: function (val, oldVal) {},
      deep: true
    }

  },
  methods: {

    dividirItem: function(){
      let vm = this
      console.log(this.itemDividido)
      if(vm.quantidadeDivisao > vm.produtoSelecionado.quantidade){
        vm.$q.notify({
          message: 'A quantidade desejada é maior que a diponível',
          type: 'negative',
        })
        return
      }
    },
    enviarManifestacao: function(chave, filial){
      let vm = this

      if (vm.codmanifestacao == 210240 || vm.codmanifestacao == 210220){
        if(vm.justificativa == null || vm.justificativa.length < 15){
          vm.$q.notify({
            message: 'Justificativa deve conter no mínimo 15 caracteres',
            type: 'negative',
          })
          return
        }
      }
      // Monta Parametros da API
      let params = {
        justificativa: vm.justificativa,
        manifestacao: vm.codmanifestacao,
        nfechave: vm.chaveManifestacao,
        filial: vm.filialManifestacao
      }
      vm.$axios.get('nfe-terceiro/manifestacao',{params}).then(function(request){
        if (request.data !== true) {
          vm.$q.notify({
            message: request.data,
            type: 'negative',
          })
          return
        }else{
          vm.buscaListagem()
          vm.$q.notify({
            message: 'Manifestacão enviada com sucesso',
            type: 'positive',
          })
        }
      }).catch(function(error) {
        console.log(error)
      })

    },
    downloadNFe: function(filial, chave) {
      let vm = this
      // Monta Parametros da API
      let params = {
        filial: filial,
        chave: chave
      }
      vm.$axios.get('nfe-terceiro/download-nfe',{params}).then(function(request){
        if (request.data !== true) {
          vm.$q.notify({
            message: request.data,
            type: 'negative',
          })
          return
        }else{
          vm.$q.notify({
            message: 'Download concluído',
            type: 'positive',
          })
        }
      }).catch(function(error) {
        console.log(error)
      })
    },
    carregaNota: function() {
      let vm = this
      let params= {
        chave: this.filter.chave
      }
      vm.$axios.get('nfe-terceiro/busca-nfeterceiro',{params}).then(function(request){
        if (request.data[0]){
          vm.nf = request.data[0]
          vm.carregaItens(vm.nf.codnotafiscalterceiro)
          vm.carregado = true
        }else{
          vm.carregado = false
          vm.$axios.get('nfe-terceiro/lista-dfe',{params}).then(function(request) {
            vm.dfe = request.data.data[0]
            vm.dfecarregada = true
          }).catch(function(error) {
            console.log(error)
          })
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
