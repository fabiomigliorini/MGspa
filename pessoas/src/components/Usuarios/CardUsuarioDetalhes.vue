<template v-if="sUsuario.detalheUsuarios">
  <q-card bordered>
    <q-list>
      <q-item>
        <q-item-section avatar>
          <q-avatar color="primary" class="q-my-md" size="70px" text-color="white" v-if="sUsuario.detalheUsuarios.usuario">
            {{ primeiraLetra(sUsuario.detalheUsuarios.usuario).toUpperCase() }}
          </q-avatar>
        </q-item-section>
        <q-item-label header>
          <q-item-label>
            <span class="text-h4 text-weight-bold"
              :class="sUsuario.detalheUsuarios.inativo ? 'text-strike text-red-14' : null">{{
                sUsuario.detalheUsuarios.usuario }}</span>

            <q-btn flat round icon="edit" @click="editarDetalhes()"
              v-if="user.verificaPermissaoUsuario('Administrador')" />
            <q-btn flat round icon="delete"
              @click="removerPessoa(sUsuario.detalheUsuarios.codpessoa, sUsuario.item.pessoa)" />

            <q-btn v-if="user.verificaPermissaoUsuario('Administrador') && !sUsuario.detalheUsuarios.inativo" flat round
              icon="pause" @click="inativar(sUsuario.item.codpessoa)">
              <q-tooltip transition-show="scale" transition-hide="scale">
                Inativar
              </q-tooltip>
            </q-btn>

            <q-btn v-if="user.verificaPermissaoUsuario('Administrador') && sUsuario.detalheUsuarios.inativo" flat round
              icon="play_arrow" @click="ativar(sUsuario.item.codpessoa)">
              <q-tooltip transition-show="scale" transition-hide="scale">
                Ativar
              </q-tooltip>
            </q-btn>

            <q-btn flat icon="info">
              <q-tooltip transition-show="scale" transition-hide="scale">
                <q-item-label class="row">Criado por {{ sUsuario.item.usuariocriacao }} em {{
                  Documentos.formataData(sUsuario.item.criacao)
                }}</q-item-label>
                <q-item-label class="row">Alterado por {{ sUsuario.item.usuarioalteracao }} em {{
                  Documentos.formataData(sUsuario.item.alteracao) }}</q-item-label>
              </q-tooltip>
            </q-btn>

          </q-item-label>
          <q-item-label v-if="sUsuario.detalheUsuarios.inativo">
            Inativo
          </q-item-label>
          <q-item-label caption>
            {{ moment(sUsuario.detalheUsuarios.inativo).format('DD/MM/YYYY hh:mm') }}
          </q-item-label>
        </q-item-label>

      </q-item>
      <q-separator inset />

      <div class="row">
        <div class="col-xs-12 col-sm-12">
          <q-item>
            <q-item-section avatar top>
              <q-avatar icon="badge" color="grey-2" text-color="blue" />
            </q-item-section>
            <q-item-section top>
              <q-item-label v-if="sUsuario.detalheUsuarios.pessoa">
                #00{{ sUsuario.detalheUsuarios.codusuario }} <br>
                <q-btn flat dense :label="sUsuario.detalheUsuarios.pessoa.pessoa"
                  :to="'/pessoa/' + sUsuario.detalheUsuarios.pessoa.codpessoa" target="_blank" />
              </q-item-label>
              <q-item-label caption>
                Pessoa
              </q-item-label>
            </q-item-section>
          </q-item>
          <q-separator inset />

          <q-item>
            <q-item-section avatar top>
              <q-avatar icon="corporate_fare" color="grey-2" text-color="blue" />
            </q-item-section>
            <q-item-section top>
              <q-item-label v-if="sUsuario.detalheUsuarios.filial">
                {{ sUsuario.detalheUsuarios.filial.filial }}
              </q-item-label>
              <q-item-label caption>
                <span>Filial</span>
              </q-item-label>
            </q-item-section>
          </q-item>
          <q-separator inset />

        </div>

        <div class="col-xs-12 col-sm-12">
          <q-item>
            <q-item-section avatar top>
              <q-avatar icon="print" color="grey-2" text-color="blue" />
            </q-item-section>
            <q-item-section top>
              <q-item-label>
                <span v-if="sUsuario.detalheUsuarios.impressoramatricial">{{ sUsuario.detalheUsuarios.impressoramatricial
                }}</span>
                <span v-else>Vazio</span>
              </q-item-label>
              <q-item-label caption>
                Impressora Matricial
              </q-item-label>
            </q-item-section>
          </q-item>
          <q-separator inset />

          <q-item>
            <q-item-section avatar top>
              <q-avatar icon="print" color="grey-2" text-color="blue" />
            </q-item-section>
            <q-item-section top>
              <q-item-label>
                <span v-if="sUsuario.detalheUsuarios.impressoratermica">{{ sUsuario.detalheUsuarios.impressoratermica
                }}</span>
                <span v-else>Vazio</span>
              </q-item-label>
              <q-item-label caption>
                <span>Impressora Térmica</span>
              </q-item-label>
            </q-item-section>
          </q-item>
          <q-separator inset />

        </div>
        <div class="col-xs-12 col-sm-12">
          <q-item>
            <q-item-section avatar top>
              <q-avatar icon="login" color="grey-2" text-color="blue" />
            </q-item-section>
            <q-item-section top>
              <q-item-label>
                {{ moment(sUsuario.detalheUsuarios.ultimoacesso).format("DD/MMM/YYYY") }} -
                {{ moment(sUsuario.detalheUsuarios.ultimoacesso).fromNow() }}
              </q-item-label>
              <q-item-label caption>
                Último acesso
              </q-item-label>
            </q-item-section>
          </q-item>
          <q-separator inset />

        </div>
      </div>
    </q-list>
  </q-card>
</template>

<script>
import { defineComponent, defineAsyncComponent } from 'vue'
import { useQuasar } from "quasar"
import { ref } from 'vue'
import { usuarioStore } from 'stores/usuario'
import { useRoute } from 'vue-router'
import { formataDocumetos } from 'src/stores/formataDocumentos'
import { guardaToken } from 'stores/index'
import moment from "moment";
import "moment/min/locales";
moment.locale("pt-br");


export default defineComponent({
  name: "CardUsuarioDetalhes",

  methods: {

    primeiraLetra(fantasia) {
      if (fantasia.charAt(0) == ' ') {
        return fantasia.charAt(1)
      }
      return fantasia.charAt(0)
    },
  },

  setup() {

    const $q = useQuasar()
    const sUsuario = usuarioStore()
    const route = useRoute()
    const Documentos = formataDocumetos()
    const user = guardaToken()

    return {
      formapagamento: ref({}),
      sUsuario,
      route,
      Documentos,
      user,
      moment,
      DialogDetalhes: ref(false),
      modelEditarDetalhes: ref([]),
    }
  },
})
</script>

<style scoped></style>
