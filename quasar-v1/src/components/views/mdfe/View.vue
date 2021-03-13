<template>
  <mg-layout back-path="/mdfe/">

    <!-- Título da Página -->
    <template slot="title">
      MDF-e #{{ numeral(parseFloat(mdfe.codmdfe)).format('00000000') }}
    </template>

    <!-- Conteúdo Princial (Meio) -->
    <div slot="content">
      <div class="row q-pa-sm q-col-gutter-sm justify-center">

        <div class="col-xs-12 col-sm-12 col-md-10 col-lg-10">

            <q-list>

              <q-item>
                <q-item-section>
                  <q-item-label caption>Emitente</q-item-label>
                  <q-item-label>
                    {{mdfe.filial}} |
                    {{tipoEmitenteLabel}} |
                    {{tipoTransportadorLabel}}
                  </q-item-label>
                </q-item-section>
              </q-item>

              <q-item>
                <q-item-section>
                  <q-item-label caption>Número e Status</q-item-label>
                  <q-item-label>
                    Modelo {{mdfe.modelo}} |
                    Série {{mdfe.serie}}
                    <template v-if="mdfe.numero">
                      | Número {{mdfe.numero}}
                    </template>
                  </q-item-label>
                  <template v-if="mdfe.chmdfe">
                    <q-item-label>
                      {{ formataChave(mdfe.chmdfe) }}
                    </q-item-label>
                  </template>
                </q-item-section>
              </q-item>

              <q-item>
                <q-item-section>
                  <q-item-label caption>Emissão</q-item-label>
                  <q-item-label>
                    <q-chip dense :color="statusColor" >
                      {{mdfe.mdfestatussigla}}
                    </q-chip>
                    {{mdfe.mdfestatus}}
                    {{ tipoEmissaoLabel }}
                    <template v-if="mdfe.emissao">
                      <abbr :title="moment(mdfe.emissao).format('llll')">
                        {{ moment(mdfe.emissao).fromNow() }}
                      </abbr>
                    </template>
                  </q-item-label>
                  <q-item-label>
                    <div class="q-gutter-sm" v-if="mdfe">
                      <q-btn stack color="secondary" label="Criar XML" @click="criarXml()" icon="fas fa-file-code" v-if="[1, 2].includes(mdfe.codmdfestatus)" />
                      <q-btn stack color="secondary" label="Transmitir" @click="enviar()" icon="fas fa-upload" v-if="mdfe.chmdfe && [1, 2].includes(mdfe.codmdfestatus)" />
                      <q-btn stack color="secondary" label="Consultar Transmissão" @click="consultarEnvio()" icon="fas fa-download" v-if="mdfe.MdfeEnvioSefazS.length > 0 && [1, 2].includes(mdfe.codmdfestatus)" />
                      <q-btn stack color="secondary" label="Consultar" @click="consultar()" icon="fas fa-sync" v-if="mdfe.chmdfe"/>
                      <q-btn stack color="secondary" label="DAMDFe" @click="damdfe()" icon="fas fa-file-pdf" v-if="[3, 5].includes(mdfe.codmdfestatus)" />
                      <q-btn stack color="secondary" label="Cancelar" @click="cancelarConfirmar()" icon="fas fa-ban" v-if="[3].includes(mdfe.codmdfestatus)" />
                      <q-btn stack color="secondary" label="Encerrar" @click="encerrar()" icon="fas fa-check" v-if="[3].includes(mdfe.codmdfestatus)" />
                    </div>
                  </q-item-label>
                  <q-item-label v-if="mdfe.protocoloautorizacao">
                    Autorizada via protocolo {{ mdfe.protocoloautorizacao }}
                  </q-item-label>
                  <q-item-label v-if="mdfe.encerramento">
                    Encerrada
                    <abbr :title="moment(mdfe.encerramento).format('llll')">
                      {{ moment(mdfe.encerramento).fromNow() }}
                    </abbr>
                  </q-item-label>
                  <q-item-label v-if="mdfe.inativo">
                    Cancelada
                    <template v-if="mdfe.protocolocancelamento">
                      via protocolo {{ mdfe.protocolocancelamento }}
                    </template>
                    <abbr :title="moment(mdfe.inativo).format('llll')">
                      {{ moment(mdfe.inativo).fromNow() }}
                    </abbr>
                    <template v-if="mdfe.justificativa">
                      com a seguinte justificativa: {{mdfe.justificativa}}
                    </template>
                  </q-item-label>
                  <template v-for="envio in mdfe.MdfeEnvioSefazS">
                    <q-item-label >
                      Envio
                      <template v-if="envio.criacao">
                        <abbr :title="moment(envio.criacao).format('llll')">
                          {{ moment(envio.criacao).fromNow() }}
                        </abbr>
                      </template>
                      <template v-if="envio.cstatretorno">
                        retornou
                        {{ envio.cstatretorno }} ({{envio.xmotivo}})
                      </template>
                      <q-btn flat size="xs" color="primary" @click="consultarEnvio(envio.codmdfeenviosefaz)" icon="fas fa-sync" v-if="mdfe.MdfeEnvioSefazS.length > 0" />
                    </q-item-label>
                  </template>
                </q-item-section>
              </q-item>

              <q-item>
                <q-item-section>
                  <q-item-label caption>Viagem</q-item-label>
                  <q-item-label>
                    Inicio
                    <template v-if="mdfe.inicioviagem">
                      <abbr :title="moment(mdfe.inicioviagem).format('llll')">
                        {{ moment(mdfe.inicioviagem).fromNow() }}
                      </abbr>
                    </template>
                    por Modal {{modalLabel}}
                    de {{ mdfe.cidadecarregamento }}
                    para {{ mdfe.estadofim }}
                  </q-item-label>
                  <template v-if="mdfe.MdfeEstadoS">
                    <q-item-label v-if="mdfe.MdfeEstadoS.length > 0">
                      Passando por
                      <template v-for="estado in mdfe.MdfeEstadoS">
                        {{estado.estado}}
                      </template>
                    </q-item-label>
                  </template>
                </q-item-section>
              </q-item>

              <q-item>
                <q-item-section>
                  <q-item-label caption>Veículos</q-item-label>
                  <template v-for="veiculo in mdfe.MdfeVeiculoS">
                    <q-item-label>
                      {{ veiculo.placa }}
                      <template v-if="veiculo.proprietario">
                        de {{ veiculo.proprietario }}
                      </template>
                      <template v-if="veiculo.condutor">
                        conduzido por {{ veiculo.condutor }}
                      </template>
                    </q-item-label>
                  </template>
                </q-item-section>
              </q-item>

              <q-item>
                <q-item-section>
                  <q-item-label caption>Notas Fiscais</q-item-label>
                  <template v-for="nfe in mdfe.MdfeNfeS">
                    <q-item-label>
                      <template v-if="nfe.codnotafiscal">
                        <a :href="linkNotaFiscalMGsis(nfe.codnotafiscal)" target="_blank">
                          {{ formataChave(nfe.nfechave) }}
                        </a>
                      </template>
                      <template v-else>
                        {{ formataChave(nfe.nfechave) }}
                      </template>
                      <template v-if="nfe.valor">
                        no valor de R$ {{ numeral(parseFloat(nfe.valor)).format('0,0.00') }}
                      </template>
                      <template v-if="nfe.peso">
                        pesando {{ numeral(parseFloat(nfe.peso)).format('0,0') }} Kg
                      </template>
                      <template v-if="nfe.cidadedescarga">
                        para {{ nfe.cidadedescarga }}
                      </template>
                    </q-item-label>
                  </template>
                </q-item-section>
              </q-item>

              <template v-if="mdfe.informacoesadicionais">
                <q-item>
                  <q-item-section>
                    <q-item-label caption>Informações Adicionais</q-item-label>
                    <q-item-label>
                      {{mdfe.informacoesadicionais}}
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </template>

              <template v-if="mdfe.informacoescomplementares">
                <q-item>
                  <q-item-section>
                    <q-item-label caption>Informações Complementares</q-item-label>
                    <q-item-label>
                      {{mdfe.informacoescomplementares}}
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </template>

              <q-item>
                <q-item-section>
                  <q-item-label caption>Autoria</q-item-label>
                  <q-item-label>
                    Criado
                    <template v-if="mdfe.usuariocriacao">
                      por {{mdfe.usuariocriacao}}
                    </template>
                    <template v-if="mdfe.criacao">
                      <abbr :title="moment(mdfe.criacao).format('llll')">
                        {{ moment(mdfe.criacao).fromNow() }}
                      </abbr>
                    </template>
                    <template v-if="mdfe.criacao!=mdfe.alteracao">
                      | Alterado
                      <template v-if="mdfe.usuarioalteracao">
                        por {{mdfe.usuarioalteracao}}
                      </template>
                      <template v-if="mdfe.alteracao">
                        <abbr :title="moment(mdfe.alteracao).format('llll')">
                          {{ moment(mdfe.alteracao).fromNow() }}
                        </abbr>
                      </template>
                    </template>
                    <template v-if="mdfe.inativo">
                      | inativado
                      <template v-if="mdfe.inativo">
                        <abbr :title="moment(mdfe.inativo).format('llll')">
                          {{ moment(mdfe.inativo).fromNow() }}
                        </abbr>
                      </template>
                    </template>
                  </q-item-label>
                </q-item-section>
              </q-item>

            </q-list>

        </div>
      </div>

      <q-page-sticky corner="bottom-right" :offset="[18, 18]">
        <q-fab color="primary" icon="edit" active-icon="edit" direction="up" class="animate-pop">
          <router-link :to="{ path: '/mdfe/' + mdfe.codmdfe + '/update' }">
            <q-fab-action color="primary" icon="edit">
              <q-tooltip anchor="center left" self="center right" :offset="[20, 0]">Editar</q-tooltip>
            </q-fab-action>
          </router-link>
          <q-fab-action color="red" @click.native="destroy()" icon="delete">
            <q-tooltip anchor="center left" self="center right" :offset="[20, 0]">Excluir</q-tooltip>
          </q-fab-action>
        </q-fab>
      </q-page-sticky>

    </div>

    <div slot="footer">
      <mg-autor
        :data="mdfe"
        ></mg-autor>
    </div>

  </mg-layout>
</template>

<script>

import MgLayout from '../../../layouts/MgLayout'
import MgAutor from '../../utils/MgAutor'
import { debounce } from 'quasar'

export default {
  components: {
    MgLayout,
    MgAutor,
    debounce
  },

  data () {
    return {
      mdfe: false,
      id: null,
      planilha: null,
    }
  },
  computed: {
    statusColor: function () {
      var ret = this.$store.state.mdfe.colorsStatus.find(el => el.value === this.mdfe.codmdfestatus)
      if (ret) {
        return ret.color
      }
      return null;
    },
    tipoEmissaoLabel: function () {
      var ret = this.$store.state.mdfe.optionsTipoEmissao.find(el => el.value === this.mdfe.tipoemissao)
      if (ret) {
        return ret.label
      }
      return null;
    },
    tipoEmitenteLabel: function () {
      var ret = this.$store.state.mdfe.optionsTipoEmitente.find(el => el.value === this.mdfe.tipoemitente)
      if (ret) {
        return ret.label
      }
      return null;
    },
    tipoTransportadorLabel: function () {
      var ret = this.$store.state.mdfe.optionsTipoTransportador.find(el => el.value === this.mdfe.tipotransportador)
      if (ret) {
        return ret.label
      }
      return null;
    },
    modalLabel: function () {
      var ret = this.$store.state.mdfe.optionsModal.find(el => el.value === this.mdfe.modal)
      if (ret) {
        return ret.label
      }
      return null;
    },
  },
  methods: {
    formataChave: function(chave){
      if (chave == undefined) {
        return null;
      }
      if(chave.length !== 44) {
        return chave;
      } else {
    		return chave.split("").reduceRight(function(elemento, anterior){
    			var temp = anterior + elemento;
    		    if(temp.replace(/\s/g, "").length % 4 === 0) return " " + temp;
    		    else return temp;
    		});
    	}
    },

    linkNotaFiscalMGsis: function(codnotafiscal){
      return process.env.MGSIS_URL + '/index.php?r=notaFiscal/view&id=' + codnotafiscal;
    },

    criarXml: function () {
      let vm = this
      vm.$axios.post('mdfe/' + vm.mdfe.codmdfe + '/criar-xml').then(function (request) {
        vm.loadData();
        vm.$q.notify({
          message: 'Arquivo XML Criado!',
          type: 'positive',
        })
      }).catch(function (error) {
        console.log(error)
        vm.$q.notify({
          message: 'Falha ao Criar arquivo XML!',
          type: 'negative',
        })
        vm.$q.notify({
          message: error.response.data.message,
          type: 'negative',
        })
      })
    },

    enviar: function () {
      let vm = this
      vm.$axios.post('mdfe/' + vm.mdfe.codmdfe + '/enviar').then(function (request) {
        vm.loadData();
        vm.$q.notify({
          message: 'MDFe Trasmitida!',
          type: 'positive',
        })
      }).catch(function (error) {
        console.log(error)
        vm.$q.notify({
          message: 'Falha ao Transmitir MDFe!',
          type: 'negative',
        })
        vm.$q.notify({
          message: error.response.data.message,
          type: 'negative',
        })
      })
    },

    consultarEnvio: function (codmdfeenviosefaz) {
      let vm = this
      var url = 'mdfe/' + vm.mdfe.codmdfe + '/consultar-envio'
      if (codmdfeenviosefaz) {
        url += '/' + codmdfeenviosefaz;
      }
      vm.$axios.post(url).then(function (request) {
        vm.loadData();
        if (request.data.cStat) {
          var color = 'positive';
          if (request.data.cStat != 100) {
            color = 'warning'
          }
          vm.$q.notify({
            message: request.data.cStat + ' - ' + request.data.xMotivo + '!',
            type: color,
          })
        } else {
          vm.$q.notify({
            message: 'Transmissão Consultada!',
            type: 'positive',
          })
        }
      }).catch(function (error) {
        console.log(error)
        vm.$q.notify({
          message: 'Falha ao Consultar Transmissão da MDFe!',
          type: 'negative',
        })
        vm.$q.notify({
          message: error.response.data.message,
          type: 'negative',
        })
      })
    },

    consultar: function () {
      let vm = this
      vm.$axios.post('mdfe/' + vm.mdfe.codmdfe + '/consultar').then(function (request) {
        vm.loadData();
        if (request.data.cStat) {
          var color = 'positive';
          if (request.data.cStat != 100) {
            color = 'warning'
          }
          vm.$q.notify({
            message: request.data.cStat + ' - ' + request.data.xMotivo + '!',
            type: color,
          })
        } else {
          vm.$q.notify({
            message: 'Transmissão Consultada!',
            type: 'positive',
          })
        }
      }).catch(function (error) {
        console.log(error)
        vm.$q.notify({
          message: 'Falha ao Consultar MDFe!',
          type: 'negative',
        })
        console.log(error.response.data.message);
        vm.$q.notify({
          message: error.response.data.message,
          type: 'negative',
        })
      })
    },

    cancelarConfirmar: function () {
      this.$q.dialog({
        title: 'Qual o motivo do Cancelamento?',
        message: 'Mínimo de 15 caracteres!',
        prompt: {
          model: '',
          type: 'text' // optional
        },
        cancel: true,
      }).onOk(data => {
        this.cancelar(data)
      })
    },

    cancelar: function (justificativa) {
      let vm = this
      vm.$axios.post('mdfe/' + vm.mdfe.codmdfe + '/cancelar', {
        justificativa
      }).then(function (request) {
        vm.loadData();
        if (request.data.cStat) {
          var color = 'positive';
          if (request.data.cStat != 135) {
            color = 'warning'
          }
          vm.$q.notify({
            message: request.data.cStat + ' - ' + request.data.xMotivo + '!',
            type: color,
          })
        } else {
          vm.$q.notify({
            message: 'Falha ao Cancelar MDFe!',
            type: 'negative',
          })
        }
      }).catch(function (error) {
        console.log(error)
        vm.$q.notify({
          message: 'Falha ao Cancelar MDFe!',
          type: 'negative',
        })
        var errors = error.response.data.errors;
        Object.keys(errors).forEach(key => {
          vm.$q.notify({
            message: errors[key],
            type: 'negative',
          })
        });
      })
    },

    encerrar: function () {
      let vm = this
      vm.$axios.post('mdfe/' + vm.mdfe.codmdfe + '/encerrar').then(function (request) {
        vm.loadData();
        if (request.data.cStat) {
          var color = 'positive';
          if (request.data.cStat != 135) {
            color = 'warning'
          }
          vm.$q.notify({
            message: request.data.cStat + ' - ' + request.data.xMotivo + '!',
            type: color,
          })
        } else {
          vm.$q.notify({
            message: 'Falha ao Encerrar MDFe!',
            type: 'negative',
          })
        }
      }).catch(function (error) {
        console.log(error)
        vm.$q.notify({
          message: 'Falha ao Encerrar MDFe!',
          type: 'negative',
        })
      })
    },

    damdfe: function () {
      let vm = this
      vm.$axios.get('mdfe/' + vm.mdfe.codmdfe + '/damdfe', {responseType: 'blob'}).then(function (request) {
        var blob = new Blob([request.data], {type: 'application/pdf'});
        var url = URL.createObjectURL(blob);
        var printWindow = window.open(url);
        console.log(request);
        vm.$q.notify({
          message: 'DAMDFe emitida!',
          type: 'positive',
        })
      }).catch(function (error) {
        console.log(error)
        vm.$q.notify({
          message: 'Falha ao Emitir DAMDFe!',
          type: 'negative',
        })
        vm.$q.notify({
          message: error.response.data.message,
          type: 'negative',
        })
      })
    },

    // carrega registros da api
    loadData: debounce(function () {
      var vm = this;
      vm.$axios.get('mdfe/' + this.codmdfe).then(response => {
        vm.mdfe = response.data.data;
      })
    }, 500),
  },
  created () {
    this.codmdfe = this.$route.params.codmdfe;
    this.loadData()
  }

}
</script>

<style>
</style>
