<template>
  <q-item-label header>Endereço
    <q-btn class="gt-xs" flat dense round icon="add" @click="novoendereco, promptnovoendereco = true"></q-btn>
  </q-item-label>

  <!-- DIALOG NOVO ENDERECO  -->
  <q-dialog v-model="promptnovoendereco">
    <q-card style="min-width: 350px">
      <q-card-section>
        <div class="text-h6">Novo Endereço</div>
      </q-card-section>
      <q-form>
        <q-card-section class="q-pt-none">

          <q-input outlined autofocus v-model="modelnovoendereco.cep" label="CEP" mask="#####-###" @change="BuscaCep()"
            unmasked-value dense reactive-rules :rules="[
              val => val && val.length > 7 || 'CEP inválido'
            ]" required />
          <q-input outlined dense v-model="modelnovoendereco.endereco" label="Endereço" :rules="[
            val => val && val.length > 0 || 'Endereço obrigatório'
          ]" required />
          <q-input outlined dense v-model="modelnovoendereco.numero" label="Numero" :rules="[
            val => val && val.length > 0 || 'Número obrigatório'
          ]" required />
          <q-input outlined dense v-model="modelnovoendereco.bairro" label="Bairro" :rules="[
            val => val && val.length > 0 || 'Bairro obrigatório'
          ]" required />
          <q-input outlined v-model="modelnovoendereco.complemento" label="Complemento" dense />
          <q-input outlined v-model="modelnovoendereco.apelido" label="Apelido" dense />
          <q-separator />
          <q-item-label class="q-pa-md">
            Cobrança:
            <q-btn-toggle v-model="modelnovoendereco.cobranca" class="my-custom-toggle" no-caps rounded unelevated
              toggle-color="primary" color="white" text-color="primary" :options="[
                { label: 'Sim', value: true },
                { label: 'Não', value: false }
              ]" />
          </q-item-label>
          <q-separator />
          <q-item-label class="q-pa-md">
            Entrega:
            <q-btn-toggle v-model="modelnovoendereco.entrega" class="my-custom-toggle" no-caps rounded unelevated
              toggle-color="primary" color="white" text-color="primary" :options="[
                { label: 'Sim', value: true },
                { label: 'Não', value: false }
              ]" />
          </q-item-label>
          <q-separator />
          <q-item-label class="q-pa-md">
            Nota Fiscal:
            <q-btn-toggle v-model="modelnovoendereco.nfe" class="my-custom-toggle" no-caps rounded unelevated
              toggle-color="primary" color="white" text-color="primary" :options="[
                { label: 'Sim', value: true },
                { label: 'Não', value: false }
              ]" />
          </q-item-label>
          <q-separator />
          <q-select outlined v-model="modelnovoendereco.codcidade" use-input input-debounce="0" label="Cidade"
            :options="options" options-label="label" options-value="value" map-options emit-value clearable
            @filter="filtrocidade" behavior="menu" required>

            <template v-slot:no-option>
              <q-item>
                <q-item-section class="text-grey">
                  Nenhum resultado encontrado.
                </q-item-section>
              </q-item>
            </template>
          </q-select>
        </q-card-section>

        <q-card-actions align="right" class="text-primary">
          <q-btn flat label="Cancelar" v-close-popup />
          <q-btn flat label="Salvar" type="button" @click="novoendereco" v-close-popup />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <div v-for="detail, detail_index in detalhes_enderecos" v-bind:key="detail_index">

    <draggable class="list-group" item-key="id" :component-data="{ tag: 'q-list', name: 'flip-list', type: 'transition' }"
      v-model="detalhes_pessoa" v-bind="dragOptions" @start="isDragging = true" :move="alteraOrdem"
      @end="isDragging = false">

      <template #item="{ element }">
        <q-list bordered>
          <q-item>
            <q-item-section avatar top>
              <q-avatar :icon="detail.icon" color="grey-2" :text-color="detail.text_color" />
            </q-item-section>
            <q-item-section>
              <q-item-label lines="10" class="cursor-pointer" clickable v-ripple
                @click="linkmaps(element.codcidade, element.endereco, element.numero, element.bairro, element.cep)">

                <div v-if="!element.inativo">
                  {{ element.endereco }}, {{ element.numero }}, {{ element.bairro }}, {{ element.complemento }}, {{
                    element.cidade }}/{{ element.uf }}, {{ formatacep(element.cep) }}
                </div>
                <div v-if="element.inativo">
                  <s> {{ element.endereco }}, {{ element.numero }}, {{ element.bairro }}, {{ element.complemento }}, {{
                    element.cidade }}/{{ element.uf }}, {{ formatacep(element.cep) }}</s>
                </div>

                <q-btn v-if="!element.entrega" class="gt-xs float-right" size="10px" color="red" flat dense round
                  icon="local_shipping"><small>Entrega <q-icon name="close" /> </small></q-btn>
                <q-btn v-if="element.entrega" class="gt-xs float-right" size="10px" color="green" flat dense round
                  icon="local_shipping"><small>Entrega <q-icon name="done" /> </small></q-btn>

                  <q-btn v-if="!element.nfe" class="gt-xs float-right" size="10px" color="red" flat dense round
                  icon="note"><small>Nota Fiscal <q-icon name="close" /> </small></q-btn>
                <q-btn v-if="element.nfe" class="gt-xs float-right" size="10px" color="green" flat dense round
                  icon="note"><small>Nota Fiscal <q-icon name="done" /> </small></q-btn>

                <q-btn v-if="!element.cobranca" class="gt-xs float-right" size="10px" color="red" flat dense round
                  icon="request_quote"><small>Cobrança <q-icon name="close" /> </small></q-btn>
                <q-btn v-if="element.cobranca" class="gt-xs float-right" size="10px" color="green" flat dense round
                  icon="request_quote"><small>Cobrança <q-icon name="done" /> </small></q-btn>

                <div v-if="element.inativo" class="text-caption text-red-14">Inativo desde: {{
                  formataData(element.inativo) }}</div>
                <small v-if="element.apelido" class="row">{{ element.apelido }}</small>

              </q-item-label>
            </q-item-section>

            <q-btn v-if="!info" class="gt-xs" size="10px" flat round icon="navigate_next" @click="handleright">
              <q-tooltip transition-show="scale" transition-hide="scale">
                Ações
              </q-tooltip>
            </q-btn>

            <q-btn v-if="info" class="gt-xs" size="10px" flat dense round icon="navigate_next" @click="handleleft">
              <q-tooltip transition-show="scale" transition-hide="scale">
                Ações
              </q-tooltip>
            </q-btn>

            <q-separator vertical></q-separator>

            <q-btn v-if="!element.inativo && info" class="gt-xs" size="10px" flat dense round icon="pause"
              @click="inativar(element.codpessoaendereco)">
              <q-tooltip transition-show="scale" transition-hide="scale">
                Inativar
              </q-tooltip>
            </q-btn>

            <q-btn v-if="element.inativo && info" class="gt-xs" size="10px" flat dense round icon="play_arrow"
              @click="ativar(element.codpessoaendereco)">
              <q-tooltip transition-show="scale" transition-hide="scale">
                Ativar
              </q-tooltip>
            </q-btn>

            <q-btn v-if="info" class="gt-xs" size="10px" flat dense round icon="info">
              <q-tooltip transition-show="scale" transition-hide="scale">
                <q-item-label class="row">Criado por: {{ element.usuariocriacao }}</q-item-label>
                <q-item-label class="row">Alterado por: {{ element.usuarioalteracao }}</q-item-label>
              </q-tooltip>
            </q-btn>

            <q-btn class="gt-xs" v-if="info" size="10px" flat dense round icon="delete"
              @click="excluirendereco(element.codpessoaendereco)">
              <q-tooltip transition-show="scale" transition-hide="scale">
                Excluir
              </q-tooltip>
            </q-btn>

            <q-btn class="gt-xs" size="10px" flat dense round icon="edit"
              @click="editarendereco(element.codpessoaendereco, element.endereco, element.numero, element.cep, element.complemento, element.bairro, element.codcidade, element.cobranca, element.nfe, element.entrega, element.apelido), prompteditarendereco = true">
              <q-tooltip transition-show="scale" transition-hide="scale">
                Editar
              </q-tooltip>
            </q-btn>
          </q-item>
        </q-list>
      </template>
    </draggable>

    <!-- DIALOG EDITAR ENDERECO -->
    <q-dialog v-model="prompteditarendereco">
      <q-card style="min-width: 350px">
        <q-card-section>
          <div class="text-h6">Editar Endereco</div>
        </q-card-section>
        <q-form @submit.prevent="salvarendereco">
          <q-card-section class="q-pt-none">
            <q-input outlined v-model="modeleditarendereco.cep" label="CEP" mask="#####-###" @change="BuscaCep()"
              unmasked-value dense reactive-rules :rules="[
                val => val && val.length > 7 || 'CEP inválido'
              ]" required autofocus />

            <q-input outlined dense v-model="modeleditarendereco.endereco" label="Endereço" :rules="[
              val => val && val.length > 0 || 'Endereço obrigatório'
            ]" required />
            <q-input outlined dense v-model="modeleditarendereco.numero" label="Numero" :rules="[
              val => val && val.length > 0 || 'Número obrigatório'
            ]" required />
            <q-input outlined dense v-model="modeleditarendereco.bairro" label="Bairro" :rules="[
              val => val && val.length > 0 || 'Bairro obrigatório'
            ]" required />

            <q-input outlined v-model="modeleditarendereco.complemento" label="Complemento" dense autofocus />
            <q-input outlined v-model="modeleditarendereco.apelido" label="Apelido" dense autofocus />
            <q-separator />
            <q-item-label class="q-pa-md">
              Cobrança:
              <q-btn-toggle v-model="modeleditarendereco.cobranca" class="my-custom-toggle" no-caps rounded unelevated
                toggle-color="primary" color="white" text-color="primary" :options="[
                  { label: 'Sim', value: true },
                  { label: 'Não', value: false }
                ]" />
            </q-item-label>
            <q-separator />
            <q-item-label class="q-pa-md">
              Entrega:
              <q-btn-toggle v-model="modeleditarendereco.entrega" class="my-custom-toggle" no-caps rounded unelevated
                toggle-color="primary" color="white" text-color="primary" :options="[
                  { label: 'Sim', value: true },
                  { label: 'Não', value: false }
                ]" />
            </q-item-label>
            <q-separator />
            <q-item-label class="q-pa-md">
              Nota Fiscal:
              <q-btn-toggle v-model="modeleditarendereco.nfe" class="my-custom-toggle" no-caps rounded unelevated
                toggle-color="primary" color="white" text-color="primary" :options="[
                  { label: 'Sim', value: true },
                  { label: 'Não', value: false }
                ]" />
            </q-item-label>
            <q-separator />
            <q-select outlined v-model="modeleditarendereco.codcidade" use-input input-debounce="0" label="Cidade"
              :options="options" options-label="label" options-value="value" map-options emit-value clearable
              @filter="filtrocidade" behavior="menu">

              <template v-slot:no-option>
                <q-item>
                  <q-item-section class="text-grey">
                    Nenhum resultado encontrado.
                  </q-item-section>
                </q-item>
              </template>
            </q-select>
          </q-card-section>

          <q-card-actions align="right" class="text-primary">
            <q-btn flat label="Cancelar" v-close-popup />
            <q-btn flat label="Salvar" type="submit" v-close-popup />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </div>
</template>

<script>
import { defineComponent, onMounted, onBeforeMount, Mounted } from 'vue'
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import { api } from 'boot/axios'
import { useQuasar } from 'quasar'
import draggable from 'vuedraggable'
import moment from 'moment'


const detalhes_enderecos = [
  {
    icon: 'location_on',
    label: '',
    field: 'endereco',
    text_color: 'red'
  }
]

const modelnovoendereco = ref({ endereco: '', numero: '', cep: '', complemento: '', bairro: '', codcidade: '', entrega: true, cobranca: true, nfe: true })
const modeleditarendereco = ref({ endereco: '', numero: '', cep: '', complemento: '', bairro: '', codcidade: '' })
const OptionsCidade = ref([])
const options = ref(OptionsCidade)
const detalhes_pessoa = ref([])
const cidade = ref({
  codcidade: '',
  cidade: '',
})

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
    draggable,
  },

  methods: {
    alteraOrdem: async function (e) {
      try {

        // SE FOR PRA BAIXO 
        if (e.willInsertAfter === true) {
          const baixo = await api.post('v1/pessoa/' + e.draggedContext.element.codpessoa + '/endereco/' + e.draggedContext.element.codpessoaendereco + '/baixo')
          if (baixo) {
            this.$q.notify({
              color: 'green-5',
              textColor: 'white',
              icon: 'done',
              message: 'Ordem alterada'
            })
          }
          this.DetalhesEndereco()

          // SE FOR PRA CIMA
        } else if (e.willInsertAfter === false) {
          const cima = await api.post('v1/pessoa/' + e.draggedContext.element.codpessoa + '/endereco/' + e.draggedContext.element.codpessoaendereco + '/cima')
          if (cima) {
            this.$q.notify({
              color: 'green-5',
              textColor: 'white',
              icon: 'done',
              message: 'Ordem alterada'
            })
          }
          this.DetalhesEndereco()
        }
      } catch (error) {
        console.log(error)
      }
    },

    formataData(data) {
      var dataformatada = moment(data).format('DD/MM/YYYY hh:mm')
      return dataformatada
    },

    formatacep(cep) {
      if (cep == null) {
        return cep
      }
      cep = cep.toString().padStart(8)
      return cep.slice(0, 2) + "." +
        cep.slice(2, 5) + "-" +
        cep.slice(5, 8)
    },

  },

  setup() {

    const $q = useQuasar()
    const route = useRoute()
    const loading = ref(true)
    const info = ref(null)
    const infoleft = ref(null)

    const DetalhesEndereco = async () => {

      $q.loading.show({
      })
      try {
        const { data } = await api.get('v1/pessoa/' + route.params.id + '/endereco')
        detalhes_pessoa.value = data.data


        loading.value = false
        $q.loading.hide()
      } catch (error) {
        $q.notify({
          color: 'red-5',
          textColor: 'white',
          icon: 'error',
          message: error
        })
      }

    }

    const novoendereco = async () => {

      if (modelnovoendereco.value.endereco !== '') {
        modelnovoendereco.value.codpessoa = route.params.id

        try {
          const { data } = await api.post('v1/pessoa/' + route.params.id + '/endereco', modelnovoendereco.value)

          if (data) {
            $q.notify({
              color: 'green-5',
              textColor: 'white',
              icon: 'done',
              message: 'Endereço criado.'
            })
            DetalhesEndereco()
          }
        } catch (error) {
          $q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'error',
            message: 'Erro ao criar endereço'
          })
        }
      } else {
        $q.notify({
          color: 'red-5',
          textColor: 'white',
          icon: 'error',
          message: 'Campo endereço é obrigatório'
        })
      }
    }

    const editarendereco = async (codpessoaendereco, endereco, numero, cep, complemento, bairro, codcidade, cobranca, nfe, entrega, apelido) => {

      modeleditarendereco.value = {
        codpessoaendereco: codpessoaendereco, endereco: endereco, numero: numero, cep: cep,
        complemento: complemento, bairro: bairro, codcidade: codcidade, cobranca: cobranca, nfe: nfe, entrega: entrega, apelido: apelido
      }

    }


    const salvarendereco = async () => {

      try {
        const data = await api.put('v1/pessoa/' + route.params.id + '/endereco/' + modeleditarendereco.value.codpessoaendereco, modeleditarendereco.value)

        if (data) {
          $q.notify({
            color: 'green-5',
            textColor: 'white',
            icon: 'done',
            message: 'Endereco alterado'
          })
          DetalhesEndereco()
        } else {
          $q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'error',
            message: 'Erro ao alterar endereco'
          })
        }
      } catch (error) {
        $q.notify({
          color: 'red-5',
          textColor: 'white',
          icon: 'error',
          message: 'Erro ao alterar endereco'
        })
      }

    }

    const excluirendereco = async (codpessoaendereco) => {

      $q.dialog({
        title: 'Excluir Endereço',
        message: 'Tem certeza que deseja excluir esse endereço?',
        cancel: true,
        persistent: true
      }).onOk(async () => {
        try {
          const { data } = await api.delete('v1/pessoa/' + route.params.id + '/endereco/' + codpessoaendereco)
          $q.notify({
            color: 'green-5',
            textColor: 'white',
            icon: 'done',
            message: 'Endereço excluido'
          })
          DetalhesEndereco()
        } catch (error) {
          $q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'error',
            message: 'Erro ao excluir endereço'
          })
        }
      })
    }

    const BuscaCep = async () => {
      setTimeout(async () => {

        if (modeleditarendereco.value.cep.length > 7) {
          const { data } = await api.get('https://viacep.com.br/ws/' + modeleditarendereco.value.cep + '/json/')

          if (data.logradouro) {

            modeleditarendereco.value.endereco = data.logradouro
            modeleditarendereco.value.bairro = data.bairro
            return false
          }
          if (data.erro == true) {

            return true
          }
          return
        }

        if (modelnovoendereco.value.cep.length > 7) {
          const { data } = await api.get('https://viacep.com.br/ws/' + modelnovoendereco.value.cep + '/json/')

          if (data.logradouro) {

            modelnovoendereco.value.endereco = data.logradouro
            modelnovoendereco.value.bairro = data.bairro
            return false
          }
          if (data.erro == true) {

            return true
          }
          return
        } else return

      }, 1000)
    }

    const linkmaps = async (codcidade, endereco, numero, bairro, cep) => {

      const consultacidade = await api.get('v1/select/cidade?codcidade=' + codcidade)

      var cidademaps = consultacidade.data[0].label

      cidademaps = cidademaps.split('/')
      var a = document.createElement('a');
      a.target = "_blank"
      a.href = "https://www.google.com/maps/search/?api=1&query=" + endereco + ',' +
        numero + ',' + cidademaps[0] + ',' + cep
      a.click();
    }


    const inativar = async (codpessoaendereco) => {


      var datainativar = moment().format('YYYY-MM-DD')

      try {
        const data = await api.put('v1/pessoa/' + route.params.id + '/endereco/' + codpessoaendereco, { inativo: datainativar })

        if (data) {
          $q.notify({
            color: 'green-5',
            textColor: 'white',
            icon: 'done',
            message: 'Inativado!'
          })
          DetalhesEndereco()
        } else {
          $q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'error',
            message: 'Erro ao inativar'
          })
        }
      } catch (error) {
        $q.notify({
          color: 'red-5',
          textColor: 'white',
          icon: 'error',
          message: error
        })
      }

    }

    const ativar = async (codpessoaendereco) => {

      try {
        const data = await api.put('v1/pessoa/' + route.params.id + '/endereco/' + codpessoaendereco, { inativo: null })

        if (data) {
          $q.notify({
            color: 'green-5',
            textColor: 'white',
            icon: 'done',
            message: 'Ativado!'
          })
          DetalhesEndereco()
        } else {
          $q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'error',
            message: 'Erro ao ativar'
          })
        }
      } catch (error) {
        $q.notify({
          color: 'red-5',
          textColor: 'white',
          icon: 'error',
          message: error
        })
      }

    }

    onMounted(() => {
      DetalhesEndereco()

    })

    return {
      detalhes_pessoa,
      detalhes_enderecos,
      promptnovoendereco: ref(false),
      prompteditarendereco: ref(false),
      modelnovoendereco,
      novoendereco,
      options,
      DetalhesEndereco,
      BuscaCep,
      cidade,
      modeleditarendereco,
      linkmaps,
      info,
      inativar,
      ativar,
      excluirendereco,
      salvarendereco,
      infoleft,
      editarendereco,
      filtrocidade(val, update) {
        if (val === '') {
          update(() => {
            options.value = OptionsCidade.value
          })
          return
        }
        update(async () => {
          const needle = val.toLowerCase()
          try {
            if (needle.length > 3) {
              const { data } = await api.get('v1/select/cidade?cidade=' + needle)
              options.value = data
              return
            }
          } catch (error) {
            console.log(error)
          }
        })
      },
      handleright({ evt, ...newInfo }) {
        info.value = newInfo
      },
      handleleft({ evt, ...newInfo }) {
        infoleft.value = newInfo
        info.value = ''
      },
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
}
</style>
