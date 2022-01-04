<template>
  <form>
    <div class="row justify-center" >
      <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3" >
        <q-list dense>

          <q-item>
            <q-item-section>
              <mg-select-filial outlined v-model="mdfe.codfilial" :error='hasError("codfilial")'>
                <template v-slot:error>
                  <template v-for="mensagem in errors.codfilial">
                    {{mensagem}}
                  </template>
                </template>
              </mg-select-filial>
            </q-item-section>
          </q-item>

          <q-item>
            <q-item-section>
              <mg-select-mdfe-tipo-emitente outlined v-model="mdfe.tipoemitente" :error='hasError("tipoemitente")'>
                <template v-slot:error>
                  <template v-for="mensagem in errors.tipoemitente">
                    {{mensagem}}
                  </template>
                </template>
              </mg-select-mdfe-tipo-emitente>
            </q-item-section>
          </q-item>

          <q-item>
            <q-item-section>
              <mg-select-mdfe-tipo-transportador outlined v-model="mdfe.tipotransportador" :error='hasError("tipotransportador")'>
                <template v-slot:error>
                  <template v-for="mensagem in errors.tipotransportador">
                    {{mensagem}}
                  </template>
                </template>
              </mg-select-mdfe-tipo-transportador>
            </q-item-section>
          </q-item>

          <q-item>
            <q-item-section>
              <mg-input-date-time v-model="mdfe.emissao" label="Emissão" :error='hasError("emissao")'>
                <template v-slot:error>
                  <template v-for="mensagem in errors.emissao">
                    {{mensagem}}
                  </template>
                </template>
              </mg-input-date-time>
            </q-item-section>
          </q-item>

          <q-item>
            <q-item-section>
              <mg-input-date-time v-model="mdfe.inicioviagem" label="Início da Viagem" :error='hasError("inicioviagem")'>
                <template v-slot:error>
                  <template v-for="mensagem in errors.inicioviagem">
                    {{mensagem}}
                  </template>
                </template>
              </mg-input-date-time>
            </q-item-section>
          </q-item>

          <q-item>
            <q-item-section>
              <mg-select-cidade outlined v-model="mdfe.codcidadecarregamento" label="Cidade de Carregamento" :error='hasError("codcidadecarregamento")'>
                <template v-slot:error>
                  <template v-for="mensagem in errors.codcidadecarregamento">
                    {{mensagem}}
                  </template>
                </template>
              </mg-select-cidade>
            </q-item-section>
          </q-item>

          <q-item>
            <q-item-section>
              <mg-select-estado outlined v-model="mdfe.codestadofim" label="Estado Fim da Viagem" :error='hasError("codestadofim")'>
                <template v-slot:error>
                  <template v-for="mensagem in errors.codestadofim">
                    {{mensagem}}
                  </template>
                </template>
              </mg-select-estado>
            </q-item-section>
          </q-item>

          <q-item>
            <q-item-section>
              <q-input outlined type="textarea" autogrow v-model="mdfe.informacoesadicionais" label="Informações Adicionais" :error='hasError("informacoesadicionais") '>
                <template v-slot:error>
                  <template v-for="mensagem in errors.informacoesadicionais">
                    {{mensagem}}
                  </template>
                </template>
              </q-input>
            </q-item-section>
          </q-item>

          <q-item>
            <q-item-section>
              <q-input outlined type="textarea" autogrow v-model="mdfe.informacoescomplementares" label="Informações Complementares" :error='hasError("informacoescomplementares") '>
                <template v-slot:error>
                  <template v-for="mensagem in errors.informacoescomplementares">
                    {{mensagem}}
                  </template>
                </template>
              </q-input>
            </q-item-section>
          </q-item>

          <!--


          <q-item>
            <q-item-section>
              <mg-autocomplete-pessoa label="Proprietário" v-model="mdfe.codpessoaproprietario" :error='hasError("codpessoaproprietario")'>
                <template v-slot:error>
                  <template v-for="mensagem in errors.codpessoaproprietario">
                    {{mensagem}}
                  </template>
                </template>
              </mg-autocomplete-pessoa>
            </q-item-section>
          </q-item>

          <q-item>
            <q-item-section>
              <q-input outlined type="text" v-model="mdfe.renavam" label="Renavam" mask="###########" :error='hasError("renavam")'>
                <template v-slot:error>
                  <template v-for="mensagem in errors.renavam">
                    {{mensagem}}
                  </template>
                </template>
              </q-input>
            </q-item-section>
          </q-item>

        </q-list>
      </div>
      <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3" >
        <q-list dense>
          <q-item>
            <q-item-section>
              <q-input outlined type="text" v-model="mdfe.placa" label="Placa" mask="AAA#X##" :error='hasError("placa")'>
                <template v-slot:error>
                  <template v-for="mensagem in errors.placa">
                    {{mensagem}}
                  </template>
                </template>
              </q-input>
            </q-item-section>
          </q-item>

          <q-item>
            <q-item-section>
              <mg-select-estado outlined v-model="mdfe.codestado" :error='hasError("codestado")'>
                <template v-slot:error>
                  <template v-for="mensagem in errors.codestado">
                    {{mensagem}}
                  </template>
                </template>
              </mg-select-estado>
            </q-item-section>
          </q-item>

          <q-item>
            <q-item-section>
              <q-input outlined type="number" v-model="mdfe.tara" label="Tara (KG)" step="1" min="0" max="999999" :error='hasError("tara")'>
                <template v-slot:error>
                  <template v-for="mensagem in errors.tara">
                    {{mensagem}}
                  </template>
                </template>
              </q-input>
            </q-item-section>
          </q-item>

          <q-item>
            <q-item-section>
              <q-input outlined type="number" v-model="mdfe.capacidade" label="Capacidade (KG)" step="1" min="0" max="999999" :error='hasError("capacidade")'>
                <template v-slot:error>
                  <template v-for="mensagem in errors.capacidade">
                    {{mensagem}}
                  </template>
                </template>
              </q-input>
            </q-item-section>
          </q-item>

          <q-item>
            <q-item-section>
              <q-input outlined type="number" v-model="mdfe.capacidadem3" label="Capacidade (M3)" step="1" min="0" max="999999" :error='hasError("capacidadem3")'>
                <template v-slot:error>
                  <template v-for="mensagem in errors.capacidadem3">
                    {{mensagem}}
                  </template>
                </template>
              </q-input>
            </q-item-section>
          </q-item>
        -->

        </q-list>
        <q-list dense>
          <template v-for="(veiculo, iveiculo) in mdfe.MdfeVeiculoS">

            <q-item>
              <q-item-section>
                <mg-select-veiculo outlined v-model="veiculo.codveiculo" :error='hasError("codveiculo")'>
                  <template v-slot:error>
                    <template v-for="mensagem in errors.codveiculo">
                      {{mensagem}}
                    </template>
                  </template>
                </mg-select-veiculo>
              </q-item-section>
              <q-item-section top side>
                <q-btn round flat color="negative" icon="delete" @click="delVeiculo(iveiculo)" />
              </q-item-section>
            </q-item>

            <q-item v-if="veiculo.tracao">
              <q-item-section>
                <mg-autocomplete-pessoa outlined v-model="veiculo.codpessoacondutor" label="Condutor" :error='hasError("codpessoacondutor")'>
                  <template v-slot:error>
                    <template v-for="mensagem in errors.codpessoacondutor">
                      {{mensagem}}
                    </template>
                  </template>
                </mg-autocomplete-pessoa>
              </q-item-section>
            </q-item>

          </template>

          <q-item>
            <q-item-section>
              <q-btn color="primary" icon="add" label="Adicionar Veiculo" @click="addVeiculo()" />
            </q-item-section>
          </q-item>

        </q-list>

      </div>
    </div>
  </form>
</template>

<script>
import MgSelectFilial from '../../utils/select/MgSelectFilial'
import MgSelectMdfeTipoEmitente from '../../utils/select/MgSelectMdfeTipoEmitente'
import MgSelectMdfeTipoTransportador from '../../utils/select/MgSelectMdfeTipoTransportador'
import MgInputDateTime from '../../utils/MgInputDateTime'
import MgSelectCidade from '../../utils/select/MgSelectCidade'
import MgSelectEstado from '../../utils/select/MgSelectEstado'
import MgSelectVeiculo from '../../utils/select/MgSelectVeiculo'
import MgAutocompletePessoa from '../../utils/autocomplete/MgAutocompletePessoa'


export default {
  name: 'mg-mdfe-form-tipo',
  components: {
    MgSelectFilial,
    MgSelectMdfeTipoEmitente,
    MgSelectMdfeTipoTransportador,
    MgInputDateTime,
    MgSelectCidade,
    MgSelectEstado,
    MgSelectVeiculo,
    MgAutocompletePessoa,
  },
  props: [
    'mdfe',
    'errors'
  ],
  data() {
    return {}
  },
  computed: {
    // a computed getter
  },
  methods: {
    hasError: function(field) {
      const item = this.errors[field];
      if (item == undefined) {
        return false;
      }
      return item.length > 0;
    },
    addVeiculo: function () {
      this.mdfe.MdfeVeiculoS.push({codveiculo: null, codpessoacondutor:null, tracao:true});
    },
    delVeiculo: function (iveiculo) {
      this.mdfe.MdfeVeiculoS.splice(iveiculo, 1);
    },
  },
  mounted() {},
  created() {
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
</style>
