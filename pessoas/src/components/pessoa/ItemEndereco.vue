<template>
  <!-- DIALOG NOVO/EDITAR ENDEREÇO  -->
  <q-dialog v-model="dialogEndereco">
    <q-card style="min-width: 350px">
      <q-card-section>
        <div v-if="enderecoNovo" class="text-h6">Novo Endereço</div>
        <div v-else class="text-h6">Editar Endereço</div>
      </q-card-section>
      <q-form @submit="enderecoNovo == true ? novoEndereco(route.params.id) : salvarEndereco(route.params.id)">
        <q-card-section class="">
          <q-input outlined autofocus v-model="modelEndereco.cep" label="CEP" mask="#####-###" @change="BuscaCep()"
            unmasked-value reactive-rules :rules="[
              val => val && val.length > 7 || 'CEP inválido'
            ]" required />
          <div class="row">
            <div class="col-9">
              <q-input outlined v-model="modelEndereco.endereco" ref="endereco" class="q-pr-md" label="Endereço" :rules="[
                val => val && val.length > 0 || 'Endereço obrigatório'
              ]" required :disable="buscandoCep" />

            </div>
            <div class="col-3">
              <q-input outlined v-model="modelEndereco.numero" label="Numero" :rules="[
                val => val && val.length > 0 || 'Número obrigatório'
              ]" required :disable="buscandoCep" />

            </div>
          </div>
          <div class="row">
            <div class="col-6">
              <q-input outlined v-model="modelEndereco.bairro" label="Bairro" class="q-pr-md" :rules="[
                val => val && val.length > 0 || 'Bairro obrigatório'
              ]" required :disable="buscandoCep" />

            </div>
            <div class="col-6">
              <q-input class="" outlined v-model="modelEndereco.complemento" label="Complemento" :disable="buscandoCep" />
            </div>
          </div>

          <select-cidade v-model="modelEndereco.codcidade" :model-select-cidade="modelEndereco.codcidade"
            :cidadeEditar="options" :disable="buscandoCep" class="q-mb-md">
          </select-cidade>

          <q-input class="" outlined v-model="modelEndereco.apelido" label="Apelido" />
          <q-item-label class="">
            <q-toggle v-model="modelEndereco.cobranca" label="Cobrança" />
            <q-toggle v-model="modelEndereco.entrega" label="Entrega" />
            <q-toggle v-model="modelEndereco.nfe" label="Endereço Fiscal" />
          </q-item-label>
        </q-card-section>

        <q-card-actions align="right" class="text-primary">
          <q-btn flat label="Cancelar" v-close-popup />
          <q-btn flat label="Salvar" type="submit" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <q-card bordered>

    <q-list>
      <q-item-label header>
        Endereço
        <q-btn v-if="user.verificaPermissaoUsuario('Publico')" flat round icon="add"
          @click="modalNovoEndereco()"></q-btn>
          <q-banner rounded inline-actions class="text-white bg-red" v-if="sPessoa.item.PessoaEnderecoS.length == 0">
            Sem informar ao menos um endereço, não será possivel emitir Nota Fiscal.
          </q-banner>
      </q-item-label>


          <div v-for="element in sPessoa.item.PessoaEnderecoS" v-bind:key="element.codpessoaendereco">
            <q-separator inset />
            <q-item>
              <q-item-section avatar top>
                <q-avatar icon="location_on" color="grey-2" text-color="red" />
              </q-item-section>
              <q-item-section>
                <q-item-label lines="10" class="cursor-pointer" clickable v-ripple
                  @click="linkMaps(element.cidade, element.endereco, element.numero, element.bairro, element.cep)">

                  <q-item-label>
                    <span :class="element.inativo ? 'text-strike' : null">
                      <!-- {{ element.codpessoaendereco }} -  -->
                      {{ element.endereco }},
                      {{ element.numero }},
                      {{ element.bairro }},
                      <template v-if="element.complemento">
                        {{ element.complemento }},
                      </template>
                      {{ element.cidade }}/{{ element.uf }},
                      {{ formataCep(element.cep) }}
                    </span>
                  </q-item-label>

                  <q-item-label caption>
                    {{ element.apelido }}
                    <span v-if="element.inativo" class="text-caption text-red-14">
                      Inativo desde: {{ formataData(element.inativo) }}
                    </span>
                    <q-icon v-if="element.cobranca" class="" color="green" name="paid" />
                    <q-icon v-if="element.nfe" class="" color="green" name="description" />
                    <q-icon v-if="element.entrega" class="" color="green" name="local_shipping" />
                  </q-item-label>
                </q-item-label>
              </q-item-section>

              <q-btn-dropdown flat auto-close v-if="user.verificaPermissaoUsuario('Publico')">
                <q-btn flat round icon="edit" v-if="user.verificaPermissaoUsuario('Publico')"
                  @click="editarEndereco(element.codpessoaendereco, element.endereco, element.numero, element.cep, element.complemento, element.bairro,
                    element.codcidade, element.cobranca, element.nfe, element.entrega, element.apelido, element.cidade), enderecoNovo = false">
                </q-btn>

                <q-btn v-if="user.verificaPermissaoUsuario('Publico')" flat round icon="delete"
                  @click="excluirEndereco(element.codpessoaendereco)" />

                <q-btn v-if="user.verificaPermissaoUsuario('Publico') && !element.inativo" flat round icon="pause"
                  @click="inativar(element.codpessoaendereco)">
                  <q-tooltip transition-show="scale" transition-hide="scale">
                    Inativar
                  </q-tooltip>
                </q-btn>

                <q-btn v-if="user.verificaPermissaoUsuario('Publico') && element.inativo" flat round
                  icon="play_arrow" @click="ativar(element.codpessoaendereco)">
                  <q-tooltip transition-show="scale" transition-hide="scale">
                    Ativar
                  </q-tooltip>
                </q-btn>

                <q-btn v-if="user.verificaPermissaoUsuario('Publico')" flat round icon="expand_less"
                  @click="cima(element.codpessoa, element.codpessoaendereco)">
                  <q-tooltip transition-show="scale" transition-hide="scale">
                    Mover para cima
                  </q-tooltip>
                </q-btn>

                <q-btn v-if="user.verificaPermissaoUsuario('Publico')" flat round icon="expand_more"
                  @click="baixo(element.codpessoa, element.codpessoaendereco)">
                  <q-tooltip transition-show="scale" transition-hide="scale">
                    Mover para baixo
                  </q-tooltip>
                </q-btn>


                <q-btn flat round icon="info">
                  <q-tooltip transition-show="scale" transition-hide="scale">
                    <q-item-label class="row">Criado por: {{ element.usuariocriacao }}
                      em {{ formataData(element.criacao) }}
                    </q-item-label>
                    <q-item-label class="row">Alterado por: {{ element.usuarioalteracao }} em {{
                      formataData(element.alteracao) }}</q-item-label>
                  </q-tooltip>
                </q-btn>
              </q-btn-dropdown>
            </q-item>
          </div>
    </q-list>
  </q-card>
</template>

<script>
import { defineComponent, onMounted, defineAsyncComponent } from 'vue'
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import { api } from 'boot/axios'
import { useQuasar } from 'quasar'
import moment from 'moment'
import { pessoaStore } from 'stores/pessoa'
import { guardaToken } from 'src/stores'



const modelEndereco = ref({
  endereco: '', numero: '', cep: '', complemento: '', bairro: '', codcidade: '',
  entrega: true, cobranca: true
})
const OptionsCidade = ref([])
const options = ref([])

export default defineComponent({
  name: "ItemEndereco",

  computed: {
    dragOptions() {
      return {
        animation: 500,
        group: "description",
        disabled: false,
        ghostClass: "ghost"
      };
    }
  },
  components: {
    SelectCidade: defineAsyncComponent(() => import('components/pessoa/SelectCidade.vue')),
  },

  methods: {

    async cima(codpessoa, codpessoaendereco) {
      try {
        await this.sPessoa.enderecoParaCima(codpessoa, codpessoaendereco)
        this.$q.notify({
          color: 'green-4',
          textColor: 'white',
          icon: 'done',
          message: 'Movido para cima'
        })
        this.sPessoa.get(codpessoa)
      } catch (error) {
        this.$q.notify({
          color: 'red-5',
          textColor: 'white',
          icon: 'error',
          message: error.message
        })
        this.sPessoa.get(codpessoa)
      }


    },

    async baixo(codpessoa, codpessoaendereco) {
      try {
        await this.sPessoa.enderecoParaBaixo(codpessoa, codpessoaendereco)
        this.$q.notify({
          color: 'green-4',
          textColor: 'white',
          icon: 'done',
          message: 'Movido para baixo'
        })
        this.sPessoa.get(codpessoa)
      } catch (error) {
        this.$q.notify({
          color: 'red-5',
          textColor: 'white',
          icon: 'error',
          message: error.message
        })
        this.sPessoa.get(codpessoa)
      }
    },

    formataData(data) {
      var dataformatada = moment(data).format('DD/MM/YYYY hh:mm')
      return dataformatada
    },

    formataCep(cep) {
      if (cep == null) {
        return cep
      }
      cep = cep.toString().padStart(8)
      return cep.slice(0, 2) + "." +
        cep.slice(2, 5) + "-" +
        cep.slice(5, 8)
    },

    async modalNovoEndereco() {
      this.dialogEndereco = true;
      const cobranca = (this.sPessoa.item.PessoaEnderecoS.filter(end => end.cobranca == true).length == 0);
      const entrega = (this.sPessoa.item.PessoaEnderecoS.filter(end => end.entrega == true).length == 0);
      const nfe = (this.sPessoa.item.PessoaEnderecoS.filter(end => end.nfe == true).length == 0);
      this.modelEndereco = {
        cobranca: cobranca,
        entrega: entrega,
        nfe: nfe
      };
      this.enderecoNovo = true;
    },

    async novoEndereco() {

      if (this.modelEndereco.endereco !== '') {
        this.modelEndereco.codpessoa = this.route.params.id
        try {

          const { data } = await this.sPessoa.enderecoNovo(this.route.params.id, modelEndereco.value)

          if (data) {
            this.dialogEndereco = false
            this.enderecoNovo = false
            this.$q.notify({
              color: 'green-5',
              textColor: 'white',
              icon: 'done',
              message: 'Endereço criado.'
            })
          }
        } catch (error) {

          this.$q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'error',
            message: error.response.data.message
          })
        }
      } else {
        this.$q.notify({
          color: 'red-5',
          textColor: 'white',
          icon: 'error',
          message: 'Campo endereço é obrigatório'
        })
      }
    },

    async excluirEndereco(codpessoaendereco) {

      this.$q.dialog({
        title: 'Excluir Endereço',
        message: 'Tem certeza que deseja excluir esse endereço?',
        cancel: true,
        persistent: true
      }).onOk(async () => {
        try {
          await this.sPessoa.enderecoExcluir(this.route.params.id, codpessoaendereco)
          this.$q.notify({
            color: 'green-5',
            textColor: 'white',
            icon: 'done',
            message: 'Endereço excluido'
          })
          this.sPessoa.get(this.route.params.id)
        } catch (error) {
          console.log(error)
          this.$q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'error',
            message: error.response.data.message
          })
        }
      })
    },

    async editarEndereco(codpessoaendereco, endereco, numero, cep, complemento, bairro, codcidade, cobranca, nfe, entrega, apelido, cidade) {
      this.dialogEndereco = true
      this.enderecoNovo = false
      this.modelEndereco = {
        codpessoaendereco: codpessoaendereco, endereco: endereco, numero: numero, cep: cep,
        complemento: complemento, bairro: bairro, codcidade: codcidade, cobranca: cobranca, nfe: nfe, entrega: entrega, apelido: apelido
      }

      const ret = await this.sPessoa.consultaCidade(codcidade)
      this.options = [ret.data[0]]
    },

    async salvarEndereco(codpessoa) {

      try {
        const data = await this.sPessoa.enderecoSalvar(codpessoa, this.modelEndereco.codpessoaendereco, this.modelEndereco)
        if (data) {
          this.$q.notify({
            color: 'green-5',
            textColor: 'white',
            icon: 'done',
            message: 'Endereco alterado'
          })
          this.dialogEndereco = false
          this.sPessoa.get(this.route.params.id)
        } else {
          this.$q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'error',
            message: 'Erro ao alterar endereco'
          })
        }
      } catch (error) {
        this.$q.notify({
          color: 'red-5',
          textColor: 'white',
          icon: 'error',
          message: error.response.data.message
        })
      }
    },


    removerAcentos(s) {
      var map = {
        "â": "a", "Â": "A", "à": "a", "À": "A", "á": "a", "Á": "A", "ã": "a", "Ã": "A", "ê": "e", "Ê": "E", "è": "e", "È": "E", "é": "e", "É": "E", "î": "i", "Î": "I", "ì": "i", "Ì": "I", "í": "i",
        "Í": "I", "õ": "o", "Õ": "O", "ô": "o", "Ô": "O", "ò": "o", "Ò": "O", "ó": "o", "Ó": "O", "ü": "u", "Ü": "U", "û": "u", "Û": "U", "ú": "u", "Ú": "U", "ù": "u", "Ù": "U", "ç": "c", "Ç": "C"
      }
      return s.replace(/[\W\[\] ]/g, function (a) { return map[a] || a })
    },

    async BuscaCep() {
      this.buscandoCep = true;
      // setTimeout(async () => {
      if (this.modelEndereco.cep.length == 8) {
        const { data } = await api.get('https://viacep.com.br/ws/' + this.modelEndereco.cep + '/json/')
        if (data.logradouro) {
          this.modelEndereco.endereco = data.logradouro
          this.modelEndereco.bairro = data.bairro
          const cidadeapicep = data.localidade.toLowerCase()
          const buscarcidade = await api.get('v1/select/cidade?cidade=' + this.removerAcentos(cidadeapicep))
          this.modelEndereco.codcidade = buscarcidade.data[0].value
        }
        this.buscandoCep = false;
        setTimeout(() => {
          this.$refs.endereco.focus();
        }, 500);

      }
      // }, 1000)
    },

    async linkMaps(cidade, endereco, numero, bairro, cep) {

      var a = document.createElement('a');
      a.target = "_blank"
      a.href = "https://www.google.com/maps/search/?api=1&query=" + endereco + ',' +
        numero + ',' + cidade + ',' + cep
      a.click();
    },

    async inativar(codpessoaendereco) {

      try {
        const ret = await this.sPessoa.enderecoInativar(this.route.params.id, codpessoaendereco)
        if (ret.data) {
          this.$q.notify({
            color: 'green-5',
            textColor: 'white',
            icon: 'done',
            message: 'Inativado!'
          })
        } else {
          this.$q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'error',
            message: 'Erro ao inativar'
          })
        }
      } catch (error) {
        this.$q.notify({
          color: 'red-5',
          textColor: 'white',
          icon: 'error',
          message: error.message
        })
      }
    },

    async ativar(codpessoaendereco) {
      try {
        const ret = await this.sPessoa.enderecoAtivar(this.route.params.id, codpessoaendereco)
        if (ret.data) {
          this.$q.notify({
            color: 'green-5',
            textColor: 'white',
            icon: 'done',
            message: 'Ativado!'
          })
        } else {
          this.$q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'error',
            message: 'Erro ao ativar'
          })
        }
      } catch (error) {
        this.$q.notify({
          color: 'red-5',
          textColor: 'white',
          icon: 'error',
          message: error
        })
      }
    }

  },

  setup() {

    const $q = useQuasar()
    const route = useRoute()
    const sPessoa = pessoaStore()
    const buscandoCep = ref(false);
    const user = guardaToken()

    return {
      dialogEndereco: ref(false),
      enderecoNovo: ref(false),
      modelEndereco,
      options,
      sPessoa,
      user,
      route,
      buscandoCep,
    }
  },
})
</script>

<style lang="scss">
.button {
  margin-top: 35px;
}

.flip-list-move {
  transition: transform 0.5s;
}

.no-move {
  transition: transform 0s;
}

.ghost {
  opacity: 0.5;
  background: #beff8a;
}

.list-group {
  min-height: 20px;
}

.list-group-item {
  cursor: move;
}

.list-group-item i {
  cursor: pointer;
}</style>
