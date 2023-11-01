<template>
  <q-item-label header>Email
    <q-btn class="gt-xs" flat dense round icon="add" @click="novoemail, promptnovoemail = true"></q-btn>
  </q-item-label>
  <!-- DIALOG NOVO EMAIL  -->
  <q-dialog v-model="promptnovoemail" @keyup.enter="novoemail">
    <q-card style="min-width: 350px">
      <q-card-section>
        <div class="text-h6">Novo Email</div>
      </q-card-section>
      <q-card-section class="q-pt-none">
        <q-input outlined dense v-model="modelnovoemail.email" autofocus label="Email"
          @keyup.enter="promptnovoemail = false" />

        <q-input outlined dense v-model="modelnovoemail.apelido" label="Apelido" @keyup.enter="promptnovoemail = false" />

        <q-input outlined v-model="modelnovoemail.verificacao" mask="##-##-####" label="Verificação">
          <template v-slot:append>
            <q-icon name="event" class="cursor-pointer">
              <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                <q-date v-model="modelnovoemail.verificacao" :locale="brasil" mask="DD-MM-YYYY">
                  <div class="row items-center justify-end">
                    <q-btn v-close-popup label="Fechar" color="primary" flat />
                  </div>
                </q-date>
              </q-popup-proxy>
            </q-icon>
          </template>
        </q-input>
        <q-item-label class="q-pa-md">
          Cobrança:
          <q-btn-toggle v-model="modelnovoemail.cobranca" class="my-custom-toggle" no-caps rounded unelevated
            toggle-color="primary" color="white" text-color="primary" :options="[
              { label: 'Sim', value: true },
              { label: 'Não', value: false }
            ]" />
        </q-item-label>
        <q-separator />
        <q-item-label class="q-pa-md">
          Nota Fiscal:
          <q-btn-toggle v-model="modelnovoemail.nfe" class="my-custom-toggle" no-caps rounded unelevated
            toggle-color="primary" color="white" text-color="primary" :options="[
              { label: 'Sim', value: true },
              { label: 'Não', value: false }
            ]" />
        </q-item-label>
        <q-separator />
      </q-card-section>

      <q-card-actions align="right" class="text-primary">
        <q-btn flat label="Cancelar" v-close-popup />
        <q-btn flat label="Salvar" @click="novoemail" v-close-popup />
      </q-card-actions>
    </q-card>
  </q-dialog>

  <div v-for="detail, detail_index in detalhes_email" v-bind:key="detail_index">

    <draggable class="list-group" item-key="id" :component-data="{ tag: 'q-list', name: 'flip-list', type: 'transition' }"
      :move="alteraOrdem" v-model="detalhes_pessoa" v-bind="dragOptions" @start="isDragging = true"
      @end="isDragging = false">

      <template #item="{ element }">

        <q-list bordered :dense="$q.screen.lt.md">
          <q-item>
            <q-item-section avatar top>
              <q-avatar :icon="detail.icon" color="grey-2" :text-color="detail.text_color" />
            </q-item-section>
            <q-item-section>
              <q-item-label class="cursor-pointer" lines="5" @click="linkemail(element.email)" clickable v-ripple>

                <div v-if="!element.inativo">
                  {{ element.email }}
                </div>
                <div v-if="element.inativo">
                  <s>{{ element.email }}</s>
                </div>

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

                <q-btn v-if="!element.verificacao" class="gt-xs float-right" size="10px" color="red" flat dense round
                  icon="verified"><small>Não verificado</small></q-btn>
                <q-btn v-if="element.verificacao" class="gt-xs float-right" size="10px" color="green" flat dense round
                  icon="verified"><small>Verificado</small></q-btn>

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
              @click="inativar(element.codpessoatelefone)">
              <q-tooltip transition-show="scale" transition-hide="scale">
                Inativar
              </q-tooltip>
            </q-btn>

            <q-btn v-if="element.inativo && info" class="gt-xs" size="10px" flat dense round icon="play_arrow"
              @click="ativar(element.codpessoatelefone)">
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

            <q-btn v-if="info" class="gt-xs" size="10px" flat dense round icon="delete"
              @click="excluiremail(element.codpessoatelefone)">
              <q-tooltip transition-show="scale" transition-hide="scale">
                Excluir
              </q-tooltip>
            </q-btn>

            <q-btn class="gt-xs" size="10px" flat dense round icon="edit"
              @click="editaremail(element.codpessoatelefone, element.email, element.apelido, element.verificacao, element.nfe, element.cobranca), prompteditaremail = true">
              <q-tooltip transition-show="scale" transition-hide="scale">
                Editar
              </q-tooltip>
            </q-btn>
          </q-item>
        </q-list>
      </template>
    </draggable>

    <q-separator spaced inset="item" v-if="detail_index != detalhes_email.length - 1"></q-separator>


    <!-- DIALOG EDITAR EMAIL  -->
    <q-dialog v-model="prompteditaremail" @keyup.enter="salvaremail">
      <q-card style="min-width: 350px">
        <q-card-section>
          <div class="text-h6">Editar Email</div>
        </q-card-section>
        <q-card-section class="q-pt-none">
          <q-input outlined dense v-model="modelemailupdate.email" autofocus label="Email"
            @keyup.enter="prompteditaremail = false" />

          <q-input outlined dense v-model="modelemailupdate.apelido" label="Apelido"
            @keyup.enter="prompteditaremail = false" />

          <q-input outlined v-model="modelemailupdate.verificacao" mask="##-##-####" label="Verificação">
            <template v-slot:append>
              <q-icon name="event" class="cursor-pointer">
                <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                  <q-date v-model="modelemailupdate.verificacao" :locale="brasil" mask="DD-MM-YYYY">
                    <div class="row items-center justify-end">
                      <q-btn v-close-popup label="Fechar" color="primary" flat />
                    </div>
                  </q-date>
                </q-popup-proxy>
              </q-icon>
            </template>
          </q-input>
          <q-item-label class="q-pa-md">
            Cobrança:
            <q-btn-toggle v-model="modelemailupdate.cobranca" class="my-custom-toggle" no-caps rounded unelevated
              toggle-color="primary" color="white" text-color="primary" :options="[
                { label: 'Sim', value: true },
                { label: 'Não', value: false }
              ]" />
          </q-item-label>
          <q-separator />
          <q-item-label class="q-pa-md">
            Nota Fiscal:
            <q-btn-toggle v-model="modelemailupdate.nfe" class="my-custom-toggle" no-caps rounded unelevated
              toggle-color="primary" color="white" text-color="primary" :options="[
                { label: 'Sim', value: true },
                { label: 'Não', value: false }
              ]" />
          </q-item-label>
          <q-separator />
        </q-card-section>

        <q-card-actions align="right" class="text-primary">
          <q-btn flat label="Cancelar" v-close-popup />
          <q-btn flat label="Salvar" type="button" @click="salvaremail" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<script>
import { defineComponent, onMounted } from 'vue'
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import { api } from 'boot/axios'
import { useQuasar } from 'quasar'
import draggable from 'vuedraggable'
import moment from 'moment'

const detalhes_email = [
  {
    icon: 'email',
    label: '',
    field: 'email',
    text_color: 'blue'
  }
]

const modelnovoemail = ref({ codpessoa: '', email: '', apelido: '', verificacao: '', nfe: '', cobranca: '' })
const modelemailupdate = ref({ codpessoatelefone: '', email: '', apelido: '', verificacao: '', nfe: '', cobranca: '' })

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

        // SE FOR PRA BAIXO 
        if (e.willInsertAfter === true) {
          const baixo = await api.post('v1/pessoa/' + e.draggedContext.element.codpessoa + '/email/' + e.draggedContext.element.codpessoatelefone + '/baixo')
          if (baixo) {
            this.$q.notify({
              color: 'green-5',
              textColor: 'white',
              icon: 'done',
              message: 'Ordem alterada'
            })
          }
          this.DetalhesEmail()

          // SE FOR PRA CIMA
        } else if (e.willInsertAfter === false) {
          const cima = await api.post('v1/pessoa/' + e.draggedContext.element.codpessoa + '/email/' + e.draggedContext.element.codpessoatelefone + '/cima')
          if (cima) {
            this.$q.notify({
              color: 'green-5',
              textColor: 'white',
              icon: 'done',
              message: 'Ordem alterada'
            })
          }
          this.DetalhesEmail()
        }

      } catch (error) {
        console.log(error)
      }
    },
  },

  setup() {

    const route = useRoute()
    const $q = useQuasar()
    const loading = ref(true)
    const detalhes_pessoa = ref([])
    const info = ref(null)
    const infoleft = ref(null)


    const novoemail = async () => {

      if (modelnovoemail.value.email !== '') {
        modelnovoemail.value.codpessoa = route.params.id

        try {
          const { data } = await api.post('v1/pessoa/' + route.params.id + '/email', modelnovoemail.value)

          if (data) {
            $q.notify({
              color: 'green-5',
              textColor: 'white',
              icon: 'done',
              message: 'Email criado.'
            })
            DetalhesEmail()
          }
        } catch (error) {
          $q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'error',
            message: error
          })
        }
      } else {
        $q.notify({
          color: 'red-5',
          textColor: 'white',
          icon: 'error',
          message: 'Campo Email é obrigatório!'
        })
      }
    }

    const editaremail = async (codpessoatelefone, email, apelido, verificacao, nfe, cobranca) => {

      modelemailupdate.value = { codpessoatelefone: codpessoatelefone, email: email, apelido: apelido, verificacao: verificacao, nfe: nfe, cobranca: cobranca }
    }

    const DetalhesEmail = async () => {

      $q.loading.show({
      })
      try {
        const { data } = await api.get('v1/pessoa/' + route.params.id + '/email')
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

    const excluiremail = async (codpessoatelefone) => {

      $q.dialog({
        title: 'Excluir Email',
        message: 'Tem certeza que deseja excluir esse email?',
        cancel: true,
        persistent: true
      }).onOk(async () => {
        try {
          const { data } = await api.delete('v1/pessoa/' + route.params.id + '/email/' + codpessoatelefone)
          $q.notify({
            color: 'green-5',
            textColor: 'white',
            icon: 'done',
            message: 'Email excluido'
          })
          DetalhesEmail()
        } catch (error) {
          $q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'error',
            message: error
          })
        }
      })
    }

    const salvaremail = async () => {

      try {
        const data = await api.put('v1/pessoa/' + route.params.id + '/email/' + modelemailupdate.value.codpessoatelefone, {
          email: modelemailupdate.value.email, apelido: modelemailupdate.value.apelido,
          verificacao: modelemailupdate.value.verificacao, cobranca: modelemailupdate.value.cobranca, nfe: modelemailupdate.value.nfe
        })

        if (data) {
          $q.notify({
            color: 'green-5',
            textColor: 'white',
            icon: 'done',
            message: 'Email alterado'
          })
          DetalhesEmail()
        } else {
          $q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'error',
            message: 'Erro ao alterar email, tente novamente'
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

    const linkemail = async (email) => {

      var a = document.createElement('a');
      a.href = "mailto:" + email
      a.click();
    }

    const inativar = async (codpessoatelefone) => {


      var datainativar = moment().format('YYYY-MM-DD')

      try {
        const data = await api.put('v1/pessoa/' + route.params.id + '/email/' + codpessoatelefone, { inativo: datainativar })

        if (data) {
          $q.notify({
            color: 'green-5',
            textColor: 'white',
            icon: 'done',
            message: 'Inativado!'
          })
          DetalhesEmail()
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

    const ativar = async (codpessoatelefone) => {

      try {
        const data = await api.put('v1/pessoa/' + route.params.id + '/email/' + codpessoatelefone, { inativo: null })

        if (data) {
          $q.notify({
            color: 'green-5',
            textColor: 'white',
            icon: 'done',
            message: 'Ativado!'
          })
          DetalhesEmail()
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
      DetalhesEmail()
    })

    return {
      detalhes_pessoa,
      detalhes_email,
      modelnovoemail,
      modelemailupdate,
      novoemail,
      editaremail,
      promptnovoemail: ref(false),
      prompteditaremail: ref(false),
      DetalhesEmail,
      loading,
      excluiremail,
      salvaremail,
      linkemail,
      info,
      infoleft,
      inativar,
      ativar,
      handleright({ evt, ...newInfo }) {
        info.value = newInfo
      },
      handleleft({ evt, ...newInfo }) {
        infoleft.value = newInfo
        info.value = ''

      },
      brasil: {
        days: 'Domingo_Segunda_Terça_Quarta_Quinta_Sexta_Sábado'.split('_'),
        daysShort: 'Dom_Seg_Ter_Qua_Qui_Sex_Sáb'.split('_'),
        months: 'Janeiro_Fevereiro_Março_Abril_Maio_Junho_Julho_Agosto_Setembro_Outubro_Novembro_Dezembro'.split('_'),
        monthsShort: 'Jan_Fev_Mar_Abr_Mai_Jun_Jul_Ago_Set_Out_Nov_Dez'.split('_'),
        firstDayOfWeek: 1,
        format24h: true,
        pluralDay: 'dias'
      },
    }
  },
})
</script>

<style scoped></style>
