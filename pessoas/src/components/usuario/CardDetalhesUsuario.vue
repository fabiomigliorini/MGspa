<script setup>
import { formataTimestamp, formataDataAbreviada } from '@components/formatters'
import { ref } from 'vue'
import { useQuasar } from 'quasar'
import { useRouter, useRoute } from 'vue-router'
import { usuarioStore } from 'src/stores/usuario'
import { useAuthStore } from 'src/stores'
import moment from 'moment'
import 'moment/min/locales'
moment.locale('pt-br')

const $q = useQuasar()
const router = useRouter()
const route = useRoute()
const sUsuario = usuarioStore()
const user = useAuthStore()

const dialogSenha = ref(false)
const isPwd = ref(true)
const modelSenha = ref({ senha: '', senha_confirmacao: '' })

const senhaValida = (val) => (String(val).length >= 6 ? true : 'Mínimo 6 caracteres')

const confirmacaoValida = (val) => (val === modelSenha.value.senha ? true : 'Senhas não conferem!')

const abrirAlterarSenha = () => {
  modelSenha.value = { senha: '', senha_confirmacao: '' }
  isPwd.value = true
  dialogSenha.value = true
}

const salvarSenha = async () => {
  try {
    await sUsuario.alterarSenhaUsuario(sUsuario.detalheUsuarios.codusuario, {
      senha: modelSenha.value.senha,
      senha_confirmacao: modelSenha.value.senha_confirmacao,
    })
    $q.notify({ color: 'green-5', textColor: 'white', icon: 'done', message: 'Senha alterada!' })
    dialogSenha.value = false
  } catch (error) {
    const errors = error.response?.data?.errors
    const mensagem = errors?.senha?.[0] || error.response?.data?.message || 'Erro ao alterar senha'
    $q.notify({ color: 'red-5', textColor: 'white', icon: 'error', message: mensagem })
  }
}

const excluir = (codusuario) => {
  $q.dialog({
    title: 'Excluir usuário',
    message: 'Tem certeza que deseja excluir esse usuário?',
    cancel: true,
  }).onOk(async () => {
    try {
      const ret = await sUsuario.excluirUsuario(codusuario)
      if (ret.data.result) {
        $q.notify({
          color: 'green-5',
          textColor: 'white',
          icon: 'done',
          message: 'Removido',
        })
        router.push('/usuarios')
      }
    } catch (error) {
      $q.notify({
        color: 'red-5',
        textColor: 'white',
        icon: 'warning',
        message: error.response?.data?.message || 'Erro ao excluir',
      })
    }
  })
}

const inativar = async (codusuario) => {
  try {
    const ret = await sUsuario.inativar(codusuario)
    if (ret.data) {
      $q.notify({
        color: 'green-5',
        textColor: 'white',
        icon: 'done',
        message: 'Inativado!',
      })
    }
  } catch (error) {
    $q.notify({
      color: 'negative',
      textColor: 'white',
      icon: 'error',
      message: error.response?.data || 'Erro ao inativar',
    })
  }
}

const ativar = async (codusuario) => {
  try {
    const ret = await sUsuario.ativar(codusuario)
    if (ret.data) {
      $q.notify({
        color: 'green-5',
        textColor: 'white',
        icon: 'done',
        message: 'Ativado!',
      })
    }
  } catch (error) {
    $q.notify({
      color: 'negative',
      textColor: 'white',
      icon: 'error',
      message: error.response?.data || 'Erro ao ativar',
    })
  }
}
</script>

<template>
  <q-card bordered flat v-if="sUsuario.detalheUsuarios">
    <q-card-section class="text-grey-9 text-overline row items-center">
      DETALHES DO USUÁRIO
      <q-space />
      <q-btn
        v-if="user.temPermissao('Administrador')"
        flat
        round
        dense
        size="sm"
        color="grey-7"
        icon="edit"
        :to="`/usuarios/${route.params.codusuario}/editar`"
      >
        <q-tooltip>Editar</q-tooltip>
      </q-btn>

      <q-btn
        v-if="user.temPermissao('Administrador')"
        flat
        round
        dense
        size="sm"
        color="grey-7"
        icon="vpn_key"
        @click="abrirAlterarSenha"
      >
        <q-tooltip>Alterar senha</q-tooltip>
      </q-btn>

      <q-btn
        flat
        round
        dense
        size="sm"
        color="grey-7"
        icon="delete"
        @click="excluir(sUsuario.detalheUsuarios.codusuario)"
      >
        <q-tooltip>Excluir</q-tooltip>
      </q-btn>

      <q-btn
        v-if="user.temPermissao('Administrador') && !sUsuario.detalheUsuarios.inativo"
        flat
        round
        dense
        size="sm"
        color="grey-7"
        icon="pause"
        @click="inativar(sUsuario.detalheUsuarios.codusuario)"
      >
        <q-tooltip>Inativar</q-tooltip>
      </q-btn>

      <q-btn
        v-if="user.temPermissao('Administrador') && sUsuario.detalheUsuarios.inativo"
        flat
        round
        dense
        size="sm"
        color="grey-7"
        icon="play_arrow"
        @click="ativar(sUsuario.detalheUsuarios.codusuario)"
      >
        <q-tooltip>Ativar</q-tooltip>
      </q-btn>
    </q-card-section>

    <q-separator inset />

    <q-list>
      <!-- PESSOA -->
      <q-item
        v-if="sUsuario.detalheUsuarios.codpessoa"
        :to="'/pessoa/' + sUsuario.detalheUsuarios.codpessoa"
      >
        <q-item-section avatar>
          <q-icon color="primary" name="badge" size="xs" />
        </q-item-section>
        <q-item-section>
          <q-item-label class="ellipsis text-caption">
            {{ sUsuario.detalheUsuarios.Pessoa?.pessoa }}
          </q-item-label>
          <q-item-label caption>Pessoa</q-item-label>
        </q-item-section>
      </q-item>

      <!-- FILIAL -->
      <q-item v-if="sUsuario.detalheUsuarios.codfilial">
        <q-item-section avatar>
          <q-icon color="primary" name="corporate_fare" size="xs" />
        </q-item-section>
        <q-item-section>
          <q-item-label class="ellipsis text-caption">
            {{ sUsuario.detalheUsuarios.filial }}
          </q-item-label>
          <q-item-label caption>Filial</q-item-label>
        </q-item-section>
      </q-item>

      <!-- PORTADOR -->
      <q-item v-if="sUsuario.detalheUsuarios.codportador">
        <q-item-section avatar>
          <q-icon color="primary" name="wallet" size="xs" />
        </q-item-section>
        <q-item-section>
          <q-item-label class="ellipsis text-caption">
            {{ sUsuario.detalheUsuarios.portador }}
          </q-item-label>
          <q-item-label caption>Portador</q-item-label>
        </q-item-section>
      </q-item>

      <!-- ÚLTIMO ACESSO -->
      <q-item v-if="sUsuario.detalheUsuarios.ultimoacesso">
        <q-item-section avatar>
          <q-icon color="primary" name="login" size="xs" />
        </q-item-section>
        <q-item-section>
          <q-item-label class="ellipsis text-caption">
            {{ formataDataAbreviada(sUsuario.detalheUsuarios.ultimoacesso, 4) }}
            - {{ moment(sUsuario.detalheUsuarios.ultimoacesso).fromNow() }}
          </q-item-label>
          <q-item-label caption>Último acesso</q-item-label>
        </q-item-section>
      </q-item>

      <!-- DATA INATIVAÇÃO -->
      <q-item v-if="sUsuario.detalheUsuarios.inativo">
        <q-item-section avatar>
          <q-icon color="red" name="event_busy" size="xs" />
        </q-item-section>
        <q-item-section>
          <q-item-label class="ellipsis text-caption">
            {{ formataTimestamp(sUsuario.detalheUsuarios.inativo) }}
          </q-item-label>
          <q-item-label caption>Data de inativação</q-item-label>
        </q-item-section>
      </q-item>
    </q-list>
  </q-card>

  <!-- Dialog Alterar Senha (admin) -->
  <q-dialog v-model="dialogSenha">
    <q-card bordered flat style="width: 400px; max-width: 90vw">
      <q-form @submit.prevent="salvarSenha">
        <q-card-section class="text-grey-9 text-overline">ALTERAR SENHA</q-card-section>

        <q-separator inset />

        <q-card-section class="q-gutter-md">
          <q-input
            outlined
            autofocus
            v-model="modelSenha.senha"
            label="Nova senha"
            :type="isPwd ? 'password' : 'text'"
            :rules="[senhaValida]"
          >
            <template #prepend>
              <q-icon name="lock_open" />
            </template>
            <template #append>
              <q-icon
                :name="isPwd ? 'visibility_off' : 'visibility'"
                class="cursor-pointer"
                @click="isPwd = !isPwd"
              />
            </template>
          </q-input>

          <q-input
            outlined
            v-model="modelSenha.senha_confirmacao"
            label="Confirmar nova senha"
            :type="isPwd ? 'password' : 'text'"
            :rules="[confirmacaoValida]"
          >
            <template #prepend>
              <q-icon name="lock_open" />
            </template>
            <template #append>
              <q-icon
                :name="isPwd ? 'visibility_off' : 'visibility'"
                class="cursor-pointer"
                @click="isPwd = !isPwd"
              />
            </template>
          </q-input>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn flat label="Salvar" color="primary" type="submit" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
