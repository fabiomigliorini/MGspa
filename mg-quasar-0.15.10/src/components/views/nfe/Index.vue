<template>
  <mg-layout  back-path="/">
    <template slot="title">
      Notas Fiscais Eletrônicas
    </template>
    <div slot="content">
      <div class="row">
        <div class="col-md-6 q-pa-sm">
          <q-progress :percentage="percentualPercorrido" stripe animate style="height: 45px" />
        </div>
        <div class="col-md-2">
          <q-field>
            <q-toggle label="Rodar modoAutomatico" v-model="modoAutomatico"/>
          </q-field>
        </div>
        <div class="col-md-4 q-pa-sm">
          Notas: {{ iPendente }} / {{ pendentes.length }}
          ({{ numeral(percentualPercorrido).format(0) }} %)
          <q-btn icon="mail" label="Recarregar Listagem" @click="carregarPendentes()"/>
        </div>
      </div>

      <div class="row">

        <template v-for="nf in pendentes">
          <div class="col-md-3">
            <q-card class="q-ma-sm col-auto">
              <q-card-title class="relative-position text-no-wrap" style="overflow: hidden">
                {{ nf.fantasia }}
                <span slot="subtitle">
                  R$ {{ numeral(parseFloat(nf.valortotal)).format('0,0.00') }}
                  {{ moment(nf.emissao).fromNow() }}
                </span>
                <q-icon slot="right" name="fas fa-file-alt" v-if="nf.modelo == 55" />
                <q-icon slot="right" name="fas fa-receipt" v-if="nf.modelo == 65" />
              </q-card-title>
              <!-- <q-card-separator /> -->
              <q-list class="relative-position">

                <!-- NUMERO DA NOTA -->
                <q-item>
                  <q-item-side>
                    <q-item-tile color="green" icon="fas fa-key" />
                  </q-item-side>
                  <q-item-main>
                    <q-item-tile label>
                      {{ nf.modelo }}-{{ nf.serie }}-{{ numeral(parseInt(nf.numero)).format('00000000') }}
                    </q-item-tile>
                    <q-item-tile sublabel>
                        #{{ numeral(parseInt(nf.codnotafiscal)).format('00000000') }}
                    </q-item-tile>

                  </q-item-main>
                </q-item>

                <!-- PROCOLOS -->
                <q-item>
                  <q-item-side>
                    <q-item-tile color="red" icon="assignment" />
                  </q-item-side>
                  <q-item-main>
                    <q-item-tile label>
                      <template v-if="nf.nfeinutilizacao">
                        Inutilização {{ nf.nfeinutilizacao }} <br />
                      </template>
                      <template v-else-if="nf.nfecancelamento">
                        Cancelamento {{ nf.nfecancelamento }} <br />
                      </template>
                      <template v-else-if="nf.nfeautorizacao">
                        Autorização {{ nf.nfeautorizacao }} <br />
                      </template>
                      <template v-else>
                        Sem protocolos
                      </template>
                    </q-item-tile>
                    <q-item-tile sublabel style="font-size: 0.55em; overflow: hidden">
                      {{ nf.nfechave }}
                    </q-item-tile>
                    <q-item-tile sublabel>
                    </q-item-tile>
                  </q-item-main>
                </q-item>

                <!-- FILIAL E NATUREZA DE OPERACAO -->
                <q-item>
                  <q-item-side>
                    <q-item-tile color="blue" icon="store_mall_directory" />
                  </q-item-side>
                  <q-item-main>
                    <q-item-tile label>{{ nf.filial }}</q-item-tile>
                    <q-item-tile sublabel>{{ nf.naturezaoperacao }}</q-item-tile>
                  </q-item-main>
                </q-item>

                <!-- BOTOES DE ACAO -->
                <q-fab icon="menu" direction="up" color="primary" class="absolute" style="top: 105px; right: 10px" >
                  <q-fab-action @click="consultar(nf)" color="blue" class="white" icon="sync" />
                  <q-fab-action @click="criar(nf)" color="blue" class="white" icon="fa fa-file-code" />
                  <q-fab-action @click="enviar(nf)" color="blue" class="white" icon="send" />
                  <q-fab-action @click="abrirModalDetalhes(nf)" color="blue" class="white" icon="info" />
                  <q-fab-action @click="cancelarInutilizar(nf)" color="red" class="white" icon="thumb down" />
                </q-fab>
              </q-list>
            </q-card>


          </div>
        </template>

        <!-- CANCELAR / INUTILIZAR -->
        <q-modal v-model="modalCancelarInutilizar">
          <div class="q-pa-md">

            <q-field :count="15" helper="Texto de justificativa para o evento com no mínimo 15 caracteres">
              <q-input v-model="justificativa" float-label="Justificativa" type="textarea" label="Justificativa" />
            </q-field>

            <q-btn color="negative" @click="inutilizar()" label="Inutilizar Nota Fiscal" class="q-ma-sm" />
            <q-btn color="negative" @click="cancelar()" label="Cancelar Nota Fiscal" class="q-ma-sm" />
            <q-btn color="primary" @click="modalCancelarInutilizar = false" label="Fechar" class="q-ma-sm" />
          </div>
        </q-modal>
      </div>

      <q-modal v-model="modalDetalhes">

        <!-- CONSULTA  -->
        <div v-if="retornoConsultar[codnotafiscal]">
          <q-card :color="retornoConsultar[codnotafiscal].sucesso==true?'positive':'negative'" class="q-ma-sm">
            <q-card-title>
              Consulta
              <span slot="subtitle">
                {{ retornoConsultar[codnotafiscal].cStat }} {{ retornoConsultar[codnotafiscal].xMotivo }}
                <template v-if="retornoConsultar[codnotafiscal].error">
                  {{ retornoConsultar[codnotafiscal].error.response.data.message }}
                </template>
              </span>
            </q-card-title>
            <q-list>

              <!-- tpEvento -->
              <q-item v-if="retornoConsultar[codnotafiscal].tpEvento">
                <q-item-main>
                  <q-item-tile label>
                    tpEvento
                  </q-item-tile>
                  <q-item-tile sublabel>
                    {{ retornoConsultar[codnotafiscal].tpEvento }}
                  </q-item-tile>
                </q-item-main>
              </q-item>

              <!-- nfeautorizacao -->
              <q-item v-if="retornoConsultar[codnotafiscal].nfeautorizacao">
                <q-item-main>
                  <q-item-tile label>
                    Autorização
                    {{ moment(retornoConsultar[codnotafiscal].nfedataautorizacao).fromNow() }}
                  </q-item-tile>
                  <q-item-tile sublabel>
                    {{ retornoConsultar[codnotafiscal].nfeautorizacao }}
                  </q-item-tile>
                </q-item-main>
              </q-item>

              <!-- nfecancelamento -->
              <q-item v-if="retornoConsultar[codnotafiscal].nfecancelamento">
                <q-item-main>
                  <q-item-tile label>
                    Cancelamento
                    {{ moment(retornoConsultar[codnotafiscal].nfedatacancelamento).fromNow() }}
                  </q-item-tile>
                  <q-item-tile sublabel>
                    {{ retornoConsultar[codnotafiscal].nfecancelamento }} <br />
                    {{ retornoConsultar[codnotafiscal].justificativa }}
                  </q-item-tile>
                </q-item-main>
              </q-item>

              <!-- nfeinutilizacao -->
              <q-item v-if="retornoConsultar[codnotafiscal].nfeinutilizacao">
                <q-item-main>
                  <q-item-tile label>
                    Inutilização
                    {{ moment(retornoConsultar[codnotafiscal].nfedatainutilizacao).fromNow() }}
                  </q-item-tile>
                  <q-item-tile sublabel>
                    {{ retornoConsultar[codnotafiscal].nfeinutilizacao }} <br />
                    {{ retornoConsultar[codnotafiscal].justificativa }}
                  </q-item-tile>
                </q-item-main>
              </q-item>

              <!-- resp -->
              <q-collapsible icon="fas fa-file-code" label="XML de Resposta" v-if="retornoConsultar[codnotafiscal].resp">
                <div>
                  <pre>{{ formatXml(retornoConsultar[codnotafiscal].resp) }}</pre>
                </div>
              </q-collapsible>

            </q-list>
          </q-card>
        </div>

        <!-- Criar -->
        <div v-if="retornoCriar[codnotafiscal]">
          <q-card :color="retornoCriar[codnotafiscal].sucesso==true?'positive':'negative'" class="q-ma-sm">
            <q-card-title>
              Criação do Arquivo XML da NFe
              <span slot="subtitle"v-if="retornoCriar[codnotafiscal].error">
                {{ retornoCriar[codnotafiscal].error.response.data.message }}
              </span>
            </q-card-title>
            <!--resp -->
            <q-collapsible icon="fas fa-file-code" label="XML de Resposta" v-if="retornoCriar[codnotafiscal].resp">
              <div>
                <pre>{{ formatXml(retornoCriar[codnotafiscal].resp) }}</pre>
              </div>
            </q-collapsible>
          </q-card>
        </div>

        <!-- Enviar -->
        <div v-if="retornoEnviar[codnotafiscal]">
          <q-card :color="retornoEnviar[codnotafiscal].sucesso==true?'positive':'negative'" class="q-ma-sm">
            <q-card-title>
              Envio
              <span slot="subtitle">
                {{ retornoEnviar[codnotafiscal].cStat }} - {{ retornoEnviar[codnotafiscal].xMotivo }}
                <template v-if="retornoEnviar[codnotafiscal].error">
                  {{ retornoEnviar[codnotafiscal].error.response.data.message }}
                </template>
              </span>
            </q-card-title>
            <q-list dense>

              <!-- nfeautorizacao -->
              <q-item v-if="retornoEnviar[codnotafiscal].nfeautorizacao">
                <q-item-main>
                  <q-item-tile label>
                    Autorização
                    {{ moment(retornoEnviar[codnotafiscal].nfedataautorizacao).fromNow() }}
                  </q-item-tile>
                  <q-item-tile sublabel>
                    {{ retornoEnviar[codnotafiscal].nfeautorizacao }}
                  </q-item-tile>
                </q-item-main>
              </q-item>

              <!--resp -->
              <q-collapsible icon="fas fa-file-code" label="XML de Resposta" v-if="retornoEnviar[codnotafiscal].resp">
                <div>
                  <pre>{{ formatXml(retornoEnviar[codnotafiscal].resp) }}</pre>
                </div>
              </q-collapsible>

            </q-list>
          </q-card>
        </div>

        <!-- Cancelar -->
        <div v-if="retornoCancelar[codnotafiscal]">
          <q-card :color="retornoCancelar[codnotafiscal].sucesso==true?'positive':'negative'" class="q-ma-sm">
            <q-card-title>
              Cancelamento
              <span slot="subtitle">
                <template v-if="retornoCancelar[codnotafiscal].cStat">
                  {{ retornoCancelar[codnotafiscal].cStat }} -
                </template>
                {{ retornoCancelar[codnotafiscal].xMotivo }}
                <template v-if="retornoCancelar[codnotafiscal].error">
                  {{ retornoCancelar[codnotafiscal].error.response.data.message }}
                </template>
              </span>
            </q-card-title>
            <q-list>

              <!-- nfedatacancelamento -->
              <q-item v-if="retornoCancelar[codnotafiscal].nfecancelamento">
                <q-item-main>
                  <q-item-tile label>
                    {{ moment(retornoCancelar[codnotafiscal].nfedatacancelamento).fromNow() }}
                  </q-item-tile>
                  <q-item-tile sublabel>
                    {{ retornoCancelar[codnotafiscal].nfecancelamento }}
                  </q-item-tile>
                </q-item-main>
              </q-item>

              <!--resp -->
              <q-collapsible icon="fas fa-file-code" label="XML de Resposta" v-if="retornoCancelar[codnotafiscal].resp">
                <div>
                  <pre>{{ formatXml(retornoCancelar[codnotafiscal].resp) }}</pre>
                </div>
              </q-collapsible>

            </q-list>
          </q-card>
        </div>

        <!-- Inutilizar -->
        <div v-if="retornoInutilizar[codnotafiscal]">
          <q-card :color="retornoInutilizar[codnotafiscal].sucesso==true?'positive':'negative'" class="q-ma-sm">
            <q-card-title>
              Inutilização
              <span slot="subtitle">
                <template v-if="retornoInutilizar[codnotafiscal].cStat">
                  {{ retornoInutilizar[codnotafiscal].cStat }} -
                </template>
                {{ retornoInutilizar[codnotafiscal].xMotivo }}
                <template v-if="retornoInutilizar[codnotafiscal].error">
                  {{ retornoInutilizar[codnotafiscal].error.response.data.message }}
                </template>
              </span>
            </q-card-title>
            <q-list>

              <!-- nfeinutilizacao -->
              <q-item v-if="retornoInutilizar[codnotafiscal].nfeinutilizacao">
                <q-item-main>
                  <q-item-tile label>
                    {{ moment(retornoInutilizar[codnotafiscal].nfedatainutilizacao).fromNow() }}
                  </q-item-tile>
                  <q-item-tile sublabel>
                    {{ retornoInutilizar[codnotafiscal].nfeinutilizacao }}
                  </q-item-tile>
                </q-item-main>
              </q-item>

              <!--resp -->
              <q-collapsible icon="fas fa-file-code" label="XML de Resposta" v-if="retornoInutilizar[codnotafiscal].resp">
                <div>
                  <pre>{{ formatXml(retornoInutilizar[codnotafiscal].resp) }}</pre>
                </div>
              </q-collapsible>

            </q-list>
          </q-card>
        </div>

        <!-- FECHAR -->
        <div class="q-ma-sm">
          <q-btn color="primary" icon="close" label="FECHAR" @click="modalDetalhes = false" />
        </div>

      </q-modal>

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
  data () {
    return {
      modalDetalhes: false,
      modalCancelarInutilizar: false,
      codnotafiscal: null,
      iPendente: null,
      modoAutomatico: false,
      justificativa: '',
      pendentes: [],
      retornoConsultar: [],
      retornoCriar: [],
      retornoEnviar: [],
      retornoCancelar: [],
      retornoInutilizar: []
    }
  },
  computed: {
    percentualPercorrido: function () {
      if (this.pendentes.length == 0) {
        return 0
      }
      return (this.iPendente / this.pendentes.length) * 100
    }
  },
  watch: {
    modoAutomatico: function (val) {
      if (val) {
        this.iPendente = -1
        this.percorrer()
      }
    }
  },
  methods: {

    formatXml(xml) {
        const PADDING = ' '.repeat(2) // set desired indent size here
        const reg = /(>)(<)(\/*)/g
        let pad = 0

        xml = xml.replace(reg, '$1\r\n$2$3')

        return xml.split('\r\n').map((node, index) => {
            let indent = 0
            if (node.match(/.+<\/\w[^>]*>$/)) {
                indent = 0
            } else if (node.match(/^<\/\w/) && pad > 0) {
                pad -= 1
            } else if (node.match(/^<\w[^>]*[^\/]>.*$/)) {
                indent = 1
            } else {
                indent = 0
            }

            pad += indent

            return PADDING.repeat(pad - indent) + node
        }).join('\r\n');
    },

    carregarPendentes: function() {
      let vm = this
      // busca api Nfes pendentes
      vm.$axios.get('nfe-php/pendentes').then(function(request) {
        vm.pendentes = request.data
        if (vm.modoAutomatico) {
          vm.percorrer()
        }
      }).catch(function(error) {
        vm.$q.notify({
          message: error.response.data.message,
          color: 'negative'
        })
      })
    },

    abrirModalDetalhes: function (nf) {
      this.modalDetalhes = true
      this.codnotafiscal = nf.codnotafiscal
    },

    consultar: function (nf) {
      let vm = this
      vm.$axios.get('nfe-php/' + nf.codnotafiscal + '/consultar').then(function(request) {
        vm.retornoConsultar[nf.codnotafiscal] = request.data
        let color = 'negative'
        if (request.data.sucesso) {
          color = 'positive'
        }
        if (vm.modoAutomatico) {
          if (request.data.sucesso) {
            vm.percorrer()
          } else {
            vm.enviar(nf)
          }
        } else {
          vm.$q.notify({
            message: request.data.xMotivo,
            color: color
          })
        }
      }).catch(function(error) {
        vm.retornoConsultar[nf.codnotafiscal] = {
          sucesso: false,
          error: error
        }
        if (vm.modoAutomatico) {
          vm.enviar(nf)
        } else {
          vm.$q.notify({
            message: error.response.data.message,
            color: 'negative'
          })
        }
      })
    },

    criar: function (nf) {
      let vm = this
      vm.$axios.get('nfe-php/' + nf.codnotafiscal + '/criar').then(function(request) {
        vm.retornoCriar[nf.codnotafiscal] = {
          sucesso: true,
          resp: request.data
        }
        if (vm.modoAutomatico) {
          vm.enviar(nf, true)
        } else {
          vm.$q.notify({
            message: 'Arquivo XML Criado',
            color: 'positive'
          })
        }
      }).catch(function(error) {
        vm.retornoCriar[nf.codnotafiscal] = {
          sucesso: false,
          error: error
        }
        if (vm.modoAutomatico) {
          vm.percorrer()
        } else {
          vm.$q.notify({
            message: error.response.data.message,
            color: 'negative'
          })
        }
      })
    },

    enviar: function (nf, interromper) {
      let vm = this
      vm.$axios.get('nfe-php/' + nf.codnotafiscal + '/enviar-sincrono').then(function(request) {
        vm.retornoEnviar[nf.codnotafiscal] = request.data
        let color = 'negative'
        if (request.data.sucesso) {
          color = 'positive'
        }
        if (vm.modoAutomatico) {
          if (interromper || request.data.sucesso) {
            vm.percorrer()
          } else {
            vm.criar(nf)
          }
        } else {
          vm.$q.notify({
            message: request.data.xMotivo,
            color: color
          })
        }
      }).catch(function(error) {
        if (vm.modoAutomatico) {
          if (interromper) {
            vm.percorrer()
          } else {
            vm.criar(nf)
          }
        } else {
          vm.$q.notify({
            message: error.response.data.message,
            color: 'negative'
          })
        }
        vm.retornoEnviar[nf.codnotafiscal] = {
          sucesso: false,
          error: error
        }
      })
    },

    cancelarInutilizar: function (nf) {
      this.modalCancelarInutilizar = true
      this.codnotafiscal = nf.codnotafiscal
      this.justificativa = null
    },

    cancelar: function () {

      this.modalCancelarInutilizar = false

      let vm = this
      vm.$axios.get('nfe-php/' + this.codnotafiscal + '/cancelar', {
        params: {
          justificativa: vm.justificativa
        }
      }).then(function(request) {
        vm.retornoCancelar[vm.codnotafiscal] = request.data
        let color = 'negative'
        if (request.data.sucesso) {
          color = 'positive'
        }
        vm.$q.notify({
          message: request.data.xMotivo,
          color: color
        })
      }).catch(function(error) {
        vm.retornoCancelar[vm.codnotafiscal] = {
          sucesso: false,
          error: error
        }
        console.log(vm.retornoCancelar)
        vm.$q.notify({
          message: error.response.data.message,
          color: 'negative'
        })
      })
    },

    inutilizar: function () {

      this.modalCancelarInutilizar = false

      let vm = this
      vm.$axios.get('nfe-php/' + this.codnotafiscal + '/inutilizar', {
        params: {
          justificativa: vm.justificativa
        }
      }).then(function(request) {
        vm.retornoInutilizar[vm.codnotafiscal] = request.data
        let color = 'negative'
        if (request.data.sucesso) {
          color = 'positive'
        }
        vm.$q.notify({
          message: request.data.xMotivo,
          color: color
        })
      }).catch(function(error) {
        vm.retornoInutilizar[vm.codnotafiscal] = {
          sucesso: false,
          error: error
        }
        console.log(vm.retornoInutilizar)
        vm.$q.notify({
          message: error.response.data.message,
          color: 'negative'
        })
      })
    },

    percorrer: function () {
      this.iPendente++
      if (this.iPendente > (this.pendentes.length-1)) {
        this.iPendente = 0
        this.carregarPendentes()
      } else {
        this.consultar(this.pendentes[this.iPendente])
      }
    }

  },
  created () {
    this.carregarPendentes();
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style>
</style>
