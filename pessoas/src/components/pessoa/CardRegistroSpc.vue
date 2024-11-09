<template>
    <q-card bordered>
      <q-list>
        <q-item-label header>
          Registro Spc
          <q-btn flat round icon="add" v-if="user.verificaPermissaoUsuario('Publico')"
            @click="dialogNovoRegistroSpc = true, editarRegistro = false, modelRegistroSpc = {}" />

            <q-radio v-model="filtroRegistroSpc" val="todos" label="Todos" @click="filtroSpc()"/>
            <q-radio v-model="filtroRegistroSpc" val="abertos" label="Abertos" @click="filtroSpc()" />

        </q-item-label>

        <div v-for="registro in sPessoa.item.RegistroSpc" v-bind:key="registro.codregistrospc">
          <q-separator inset />
          <q-item>
            <q-item-section avatar>
              <q-avatar>
                <q-icon name="money_off" color="primary" />
              </q-avatar>
            </q-item-section>

            <q-item-section>
              <q-item-label lines="5">
                <span  class="text-weight-bold row">{{ registro.valor.toLocaleString('pt-br', {
                  style: 'currency', currency:
                    'BRL'
                }) }}</span>
                <span class="row" v-if="registro.observacoes">{{ registro.observacoes }}</span>
                <span class="row" >Por: {{ registro.usuariocriacao }}</span>

                <span v-if="registro.baixa" class="row">Baixado em:
                  <span class="text-weight-bold">&nbsp;{{ Documentos.formataDatasemHr(registro.baixa) }}</span>
                </span>
              </q-item-label>
            </q-item-section>

            <q-item-section side top>
              {{ Documentos.formataDatasemHr(registro.inclusao) }}
            </q-item-section>

            <q-btn-dropdown flat auto-close dense v-if="user.verificaPermissaoUsuario('Publico')">
              <q-btn flat round icon="edit" v-if="user.verificaPermissaoUsuario('Publico')"
                @click="editarRegistroSpc(registro.codregistrospc, registro.valor, registro.inclusao, registro.baixa, registro.observacoes)" />

              <q-btn flat round icon="delete" v-if="user.verificaPermissaoUsuario('Publico')" @click="excluirRegistro(registro.codregistrospc)" />

            </q-btn-dropdown>
          </q-item>
        </div>
        <q-separator inset="item" />
      </q-list>
    </q-card>

  <!-- Dialog novo Registro Spc -->
  <q-dialog v-model="dialogNovoRegistroSpc">
    <q-card style="min-width: 350px">
      <q-form @submit="editarRegistro == false ? novoRegistroSpc() : salvarRegistro()">
        <q-card-section>
          <div v-if="editarRegistro" class="text-h6">Editar Registro Spc</div>
          <div v-else class="text-h6">Novo Registro Spc</div>
        </q-card-section>
        <q-card-section class="">
          <div class="col-6">
            <q-input outlined v-model="modelRegistroSpc.inclusao" mask="##/##/####" label="Inclusão"
              :rules="[
                val => val && val.length > 0 || 'Inclusão obrigatório'
              ]">
              <template v-slot:append>
                <q-icon name="event" class="cursor-pointer">
                  <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                    <q-date v-model="modelRegistroSpc.inclusao" :locale="brasil" mask="DD/MM/YYYY">
                      <div class="row items-center justify-end">
                        <q-btn v-close-popup label="Fechar" color="primary" flat />
                      </div>
                    </q-date>
                  </q-popup-proxy>
                </q-icon>
              </template>
            </q-input>

            <q-input outlined v-model="modelRegistroSpc.baixa" mask="##/##/####" class="q-mb-md" label="Baixa">
              <template v-slot:append>
                <q-icon name="event" class="cursor-pointer">
                  <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                    <q-date v-model="modelRegistroSpc.baixa" :locale="brasil" mask="DD/MM/YYYY">
                      <div class="row items-center justify-end">
                        <q-btn v-close-popup label="Fechar" color="primary" flat />
                      </div>
                    </q-date>
                  </q-popup-proxy>
                </q-icon>
              </template>
            </q-input>

            <q-input outlined v-model="modelRegistroSpc.valor" label="Valor" type="numeric" :rules="[
              val => val !== null && val !== '' && val !== undefined || 'Valor obrigatório'
            ]" />

            <q-input outlined v-model="modelRegistroSpc.observacoes" label="Observações" borderless
              autogrow type="textarea" />

          </div>
        </q-card-section>

        <q-card-actions align="right" class="text-primary">
          <q-btn flat label="Cancelar" v-close-popup />
          <q-btn flat label="Salvar" type="submit" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>

<script>
import { defineComponent, defineAsyncComponent } from 'vue'
import { useQuasar, debounce } from "quasar"
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import { pessoaStore } from 'stores/pessoa'
import { guardaToken } from 'src/stores'
import { formataDocumetos } from 'src/stores/formataDocumentos'

export default defineComponent({
  name: "CardRegistroSpc",

  methods: {

    filtroSpc() {
    if(this.filtroRegistroSpc == 'abertos') {
    let todos = this.sPessoa.item.RegistroSpc.filter(x => !x.baixa)
    this.sPessoa.item.RegistroSpc = todos
    }
    if(this.filtroRegistroSpc == 'todos') {
      this.sPessoa.item.RegistroSpc = this.registrosS
    }
    },

    async novoRegistroSpc() {
      this.modelRegistroSpc.codpessoa = this.route.params.id

      const novoRegistro = {...this.modelRegistroSpc}

     if(novoRegistro.inclusao) {
      novoRegistro.inclusao = this.Documentos.dataFormatoSql(novoRegistro.inclusao)
     }
     if(novoRegistro.baixa) {
      novoRegistro.baixa = this.Documentos.dataFormatoSql(novoRegistro.baixa)
     }


     if(novoRegistro.valor.indexOf(',') > -1) {
       var removeVirgula =  novoRegistro.valor.replace(/,([^,]*)$/,".$1")
       novoRegistro.valor = removeVirgula
      }

      try {
        const ret = await this.sPessoa.novoRegistroSpc(this.route.params.id, novoRegistro)
        if (ret.data.data) {
          this.$q.notify({
            color: 'green-5',
            textColor: 'white',
            icon: 'done',
            message: 'Registro Spc criado!'
          })

          this.dialogNovoRegistroSpc = false
          this.sPessoa.item.RegistroSpc.push(ret.data.data)
        }
      } catch (error) {
        this.$q.notify({
          color: 'red-5',
          textColor: 'white',
          icon: 'warning',
          message: error.response.data.message
        })
      }
    },

    editarRegistroSpc(codregistrospc, valor, inclusao, baixa, observacoes) {
      this.dialogNovoRegistroSpc = true
      this.editarRegistro = true

      this.modelRegistroSpc = {
        codregistrospc: codregistrospc, valor: valor, inclusao: inclusao ? this.Documentos.formataDataInput(inclusao) : null,
        baixa: baixa ? this.Documentos.formataDataInput(baixa) : null, observacoes: observacoes
      }
    },

    async salvarRegistro() {


      const editRegistro = {...this.modelRegistroSpc}

      if(editRegistro.inclusao) {
        editRegistro.inclusao = this.Documentos.dataFormatoSql(editRegistro.inclusao)
      }
      if(editRegistro.baixa) {
        editRegistro.baixa = this.Documentos.dataFormatoSql(editRegistro.baixa)
      }

      if(editRegistro.valor.toString().indexOf(',') > -1) {
       var removeVirgula =  editRegistro.valor.replace(/,([^,]*)$/,".$1")
        editRegistro.valor = removeVirgula
      }

      try {
        const ret = await this.sPessoa.salvarEdicaoRegistro(this.route.params.id, editRegistro.codregistrospc, editRegistro)
        if (ret.data.data) {
          this.$q.notify({
            color: 'green-5',
            textColor: 'white',
            icon: 'done',
            message: 'Registro Spc alterado!'
          })
          const i = this.sPessoa.item.RegistroSpc.findIndex(item => item.codregistrospc === this.modelRegistroSpc.codregistrospc)
          this.sPessoa.item.RegistroSpc[i] = ret.data.data
          this.dialogNovoRegistroSpc = false
          this.editarRegistro = false
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

    async excluirRegistro(codregistrospc) {

      this.$q.dialog({
        title: 'Excluir Registro Spc',
        message: 'Tem certeza que deseja excluir esse registro?',
        cancel: true,
      }).onOk(async () => {
        try {
          const ret = await this.sPessoa.excluirRegistroSpc(this.route.params.id, codregistrospc)
          this.$q.notify({
            color: 'green-5',
            textColor: 'white',
            icon: 'done',
            message: 'Registro excluido'
          })
          this.sPessoa.get(this.route.params.id)
        } catch (error) {
          this.$q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'error',
            message: error.response.data.message
          })
        }
      })
    }
  },

  setup() {

    const $q = useQuasar()
    const sPessoa = pessoaStore()
    const loading = ref(true)
    const route = useRoute()
    const dialogNovoRegistroSpc = ref(false)
    const modelRegistroSpc = ref({})
    const editarRegistro = ref(false)
    const filtroRegistroSpc = ref('abertos')
    const user = guardaToken()
    const Documentos = formataDocumetos()
    const RegistroSpc = ref([])
    const Paginas = ref({
      page: 1
    })

    const registrosS = ref([])

    return {
      sPessoa,
      RegistroSpc,
      Documentos,
      filtroRegistroSpc,
      route,
      Paginas,
      user,
      loading,
      dialogNovoRegistroSpc,
      modelRegistroSpc,
      editarRegistro,
      registrosS,
      brasil: {
        days: 'Domingo_Segunda_Terça_Quarta_Quinta_Sexta_Sábado'.split('_'),
        daysShort: 'Dom_Seg_Ter_Qua_Qui_Sex_Sáb'.split('_'),
        months: 'Janeiro_Fevereiro_Março_Abril_Maio_Junho_Julho_Agosto_Setembro_Outubro_Novembro_Dezembro'.split('_'),
        monthsShort: 'Jan_Fev_Mar_Abr_Mai_Jun_Jul_Ago_Set_Out_Nov_Dez'.split('_'),
        firstDayOfWeek: 0,
        format24h: true,
        pluralDay: 'dias'
      },
    }
  },
  mounted() {
    this.registrosS = this.sPessoa.item.RegistroSpc

    let todos = this.sPessoa.item.RegistroSpc.filter(x => !x.baixa)
    this.sPessoa.item.RegistroSpc = todos
  }
})
</script>

<style scoped></style>
