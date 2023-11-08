<template>
  <!-- DIALOG NOVO EMAIL/EDITAR EMAIL  -->
  <q-dialog v-model="dialogEmail"
    @keyup.enter="emailNovo == true ? novoEmail(route.params.id) : salvarEmail(route.params.id)">
    <q-card style="min-width: 350px">
      <q-card-section>
        <div v-if="emailNovo" class="text-h6">Novo Email</div>
        <div v-else class="text-h6">Editar Email</div>
      </q-card-section>
      <q-card-section class="q-pt-none">
        <q-input outlined dense v-model="modelEmail.email" autofocus label="Email" @keyup.enter="dialogEmail = false"
          :rules="[
            val => val && val.length > 0 || 'Email obrigatório'
          ]" 
          class="q-mb-sm"
          />
        <q-input outlined dense v-model="modelEmail.apelido" label="Apelido" @keyup.enter="dialogEmail = false" 
          class="q-mb-md"
        />
        <q-toggle v-model="modelEmail.cobranca" label="Cobrança" />
        <q-toggle v-model="modelEmail.nfe" label="Envio de NFe" />
      </q-card-section>

      <q-card-actions align="right" class="text-primary">
        <q-btn flat label="Cancelar" v-close-popup />
        <q-btn v-if="emailNovo" flat label="Salvar" @click="novoEmail(route.params.id)" v-close-popup />
        <q-btn v-else flat label="Salvar" @click="salvarEmail(route.params.id)" v-close-popup />
      </q-card-actions>
    </q-card>
  </q-dialog>


  <q-list bordered :dense="$q.screen.lt.md" class="q-ma-sm">
    <q-item-label header>Email
      <q-btn class="gt-xs" flat dense round icon="add"
        @click="novoEmail, dialogEmail = true, modelEmail = { nfe: true, cobranca: true }, emailNovo = true"></q-btn>
    </q-item-label>

    <!-- DRAG AND DROP EMAILS -->
    <draggable class="list-group" item-key="id" :component-data="{ tag: 'q-list', name: 'flip-list', type: 'transition' }"
      :move="alteraOrdem" v-model="sPessoa.item.PessoaEmailS" v-bind="dragOptions" @start="isDragging = true"
      @end="isDragging = false">

      <template #item="{ element }">

        <div>
          <q-separator inset />
          <q-item>
            <q-item-section avatar top>
              <q-avatar icon="email" color="grey-2" text-color="blue" />
            </q-item-section>
            <q-item-section>
              <q-item-label class="cursor-pointer" lines="5" @click="linkEmail(element.email)" clickable v-ripple>

                <q-item-label>
                  <s v-if="element.inativo">
                    {{ element.email }}
                  </s>
                  <span v-else>
                    {{ element.email }}
                  </span>
                  <q-icon v-if="element.verificacao" class="q-ml-xs" color="blue" name="verified" />
                </q-item-label>

                <q-item-label caption>
                  {{ element.apelido }}
                  <span v-if="element.inativo" class="text-caption text-red-14">
                    Inativo desde: {{ formataData(element.inativo) }}
                  </span>
                  <q-icon v-if="element.cobranca" class="q-ml-xs" color="green" name="paid" />
                  <q-icon v-if="element.nfe" class="q-ml-xs" color="green" name="description" />
                </q-item-label>
                
              </q-item-label>
            </q-item-section>
            <q-space />
            <q-btn v-if="!element.verificacao" label="Verificar" color="blue" dense flat
              @click="enviarEmail(element.email, element.codpessoatelefone)" />
            <q-btn-dropdown flat dense auto-close>
              <q-btn flat dense round icon="edit"
                @click="editarEmail(element.codpessoatelefone, element.email, element.apelido, element.verificacao, element.nfe, element.cobranca), emailNovo = false">
              </q-btn>

              <q-btn flat dense round icon="delete" @click="excluirEmail(element.codpessoatelefone)">
              </q-btn>

              <q-btn v-if="!element.inativo" flat dense round icon="pause"
                @click="inativar(element.codpessoa, element.codpessoatelefone)">
                <q-tooltip transition-show="scale" transition-hide="scale">
                  Inativar
                </q-tooltip>
              </q-btn>

              <q-btn v-if="element.inativo" flat dense round icon="play_arrow"
                @click="ativar(element.codpessoa, element.codpessoatelefone)">
                <q-tooltip transition-show="scale" transition-hide="scale">
                  Ativar
                </q-tooltip>
              </q-btn>

              <q-btn flat dense round icon="info">
                <q-tooltip transition-show="scale" transition-hide="scale">
                  <q-item-label class="row">Criado por: {{ element.usuariocriacao }} em {{ formataData(element.criacao)
                  }}</q-item-label>
                  <q-item-label class="row">Alterado por: {{ element.usuarioalteracao }} em {{
                    formataData(element.alteracao) }}</q-item-label>
                </q-tooltip>
              </q-btn>
            </q-btn-dropdown>
          </q-item>
        </div>
      </template>
    </draggable>
  </q-list>
</template>

<script>
import { defineComponent, onMounted } from 'vue'
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import draggable from 'vuedraggable'
import moment from 'moment'
import { pessoaStore } from 'stores/pessoa'


export default defineComponent({
  name: "ItemEmail",

  components: {
    draggable,
  },

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

  methods: {
    formataData(data) {
      var dataformatada = moment(data).format('DD/MM/YYYY hh:mm')
      return dataformatada
    },

    alteraOrdem: async function (e) {
      try {
        if (e.willInsertAfter === true) {
          await this.sPessoa.emailParaBaixo(e.draggedContext.element.codpessoa, e.draggedContext.element.codpessoatelefone)
        } else {
          await this.sPessoa.emailParaCima(e.draggedContext.element.codpessoa, e.draggedContext.element.codpessoatelefone)
        }
      } catch (error) {
        this.$q.notify({
          color: 'red-5',
          textColor: 'white',
          icon: 'error',
          message: error.message
        })
        this.sPessoa.get(e.draggedContext.element.codpessoa)
        return
      }
    },

    async novoEmail() {

      if (this.modelEmail.email !== '') {
        this.modelEmail.codpessoa = this.route.params.id
        try {
          this.emailNovo = false
          const ret = await this.sPessoa.emailNovo(this.route.params.id, this.modelEmail)
          if (ret.data.data) {
            this.$q.notify({
              color: 'green-5',
              textColor: 'white',
              icon: 'done',
              message: 'Email criado.'
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
      } else {
        this.$q.notify({
          color: 'red-5',
          textColor: 'white',
          icon: 'error',
          message: 'Campo Email é obrigatório!'
        })
      }

    },

    async excluirEmail(codpessoatelefone) {

      this.$q.dialog({
        title: 'Excluir Email',
        message: 'Tem certeza que deseja excluir esse email?',
        cancel: true,
        persistent: true
      }).onOk(async () => {
        try {
          const ret = await this.sPessoa.emailExcluir(this.route.params.id, codpessoatelefone)
          if (ret) {
            this.$q.notify({
              color: 'green-5',
              textColor: 'white',
              icon: 'done',
              message: 'Email excluido'
            })
            this.sPessoa.get(this.route.params.id)
          }
        } catch (error) {
          this.$q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'error',
            message: error.message
          })
        }
      })

    },

    editarEmail(codpessoatelefone, email, apelido, verificacao, nfe, cobranca) {
      this.dialogEmail = true
      this.modelEmail = { codpessoatelefone: codpessoatelefone, email: email, apelido: apelido, verificacao: verificacao, nfe: nfe, cobranca: cobranca }
    },

    async salvarEmail(codpessoa) {
      this.dialogEmail = false
      try {

        const ret = this.sPessoa.emailSalvar(codpessoa, this.modelEmail.codpessoatelefone, this.modelEmail)
        if (ret) {
          this.$q.notify({
            color: 'green-5',
            textColor: 'white',
            icon: 'done',
            message: 'Email alterado'
          })
        } else {
          this.$q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'error',
            message: 'Erro ao alterar email, tente novamente'
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

    async inativar(codpessoa, codpessoatelefone) {
      try {
        const ret = await this.sPessoa.emailInativar(codpessoa, codpessoatelefone)
        if (ret.data) {
          this.$q.notify({
            color: 'green-5',
            textColor: 'white',
            icon: 'done',
            message: 'Inativado!'
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

    async ativar(codpessoa, codpessoatelefone) {

      try {
        const ret = await this.sPessoa.emailAtivar(codpessoa, codpessoatelefone)
        if (ret.data) {
          this.$q.notify({
            color: 'green-5',
            textColor: 'white',
            icon: 'done',
            message: 'Ativado!'
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

    linkEmail(email) {
      var a = document.createElement('a');
      a.href = "mailto:" + email
      a.click();
    },

    enviarEmail(email, codpessoatelefone) {

      this.$q.dialog({
        title: 'Verificação de E-mail',
        message: 'Deseja enviar o código de verificação para o e-mail  ' + email + ' ?',
        cancel: true,
      }).onOk(() => {
        this.sPessoa.emailVerificar(this.route.params.id, codpessoatelefone).then((resp) => {
          if (resp.data) {
            this.$q.notify({
              color: 'green-5',
              textColor: 'white',
              icon: 'done',
              message: 'Código enviado para o e-mail'
            })
          }
        })
        this.confirmaEmail(email, codpessoatelefone)

      })
    },

    confirmaEmail(email, codpessoatelefone) {

      this.$q.dialog({
        title: 'Verificação de E-mail',
        message: 'Digite o código enviado para o e-mail ' + email,
        prompt: {
          model: '',
          type: 'number',
          step: '1'
        },
        cancel: true,
        persistent: true
      }).onOk(codverificacao => {
        this.postEmail(email, codpessoatelefone, codverificacao)
      })
    },


    async postEmail(email, codpessoatelefone, codverificacao) {

      try {
        const ret = await this.sPessoa.emailConfirmaVerificacao(this.route.params.id, codpessoatelefone, codverificacao)
        if (ret.data.data) {
          this.$q.notify({
            color: 'green-5',
            textColor: 'white',
            icon: 'done',
            message: 'E-mail Verificado!'
          })

        }
      } catch (error) {
        this.$q.notify({
          color: 'red-5',
          textColor: 'white',
          icon: 'error',
          message: error.response.data.message
        })
        this.confirmaEmail(email, codpessoatelefone)
      }
    }
  },

  setup() {

    const route = useRoute()
    const $q = useQuasar()
    const sPessoa = pessoaStore()
    const modelEmail = ref({ codpessoa: '', email: '', apelido: '', verificacao: '', nfe: '', cobranca: '' })

    return {
      sPessoa,
      route,
      modelEmail,
      emailNovo: ref(false),
      dialogEmail: ref(false),
    }
  },
})
</script>

<style scoped></style>
