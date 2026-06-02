<script setup>
import { formataDataAbreviada } from '@components/formatters'
import { ref, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { usuarioStore } from 'src/stores/usuario'
import { useAuthStore } from 'src/stores'
import MGLayout from 'layouts/MGLayout.vue'
import moment from 'moment'
import 'moment/min/locales'
moment.locale('pt-br')

const $q = useQuasar()
const sUsuario = usuarioStore()
const user = useAuthStore()

// Refs
const dialogAlterarSenha = ref(false)
const isPwd = ref(true)
const modelPerfilUsuario = ref({})

// Functions
const senhaValida = (val) => {
  if (String(val).length < 6) {
    return 'Mínimo 6 letras ou números!'
  }
  return true
}

const confirmacaoValida = (val) => {
  if (modelPerfilUsuario.value.senha !== val) {
    return 'Senhas não batem!'
  }
  return true
}

const editar = () => {
  modelPerfilUsuario.value = {
    codusuario: user.usuario.codusuario,
    usuario: user.usuario.usuario,
  }
  dialogAlterarSenha.value = true
}

const salvar = async () => {
  try {
    await sUsuario.alterarMinhaSenha({
      senha_antiga: modelPerfilUsuario.value.senha_antiga,
      senha: modelPerfilUsuario.value.senha,
      senha_confirmacao: modelPerfilUsuario.value.senha_confirmacao,
    })
    $q.notify({
      color: 'green-5',
      textColor: 'white',
      icon: 'done',
      message: 'Senha alterada!',
    })
    dialogAlterarSenha.value = false
  } catch (error) {
    const errors = error.response?.data?.errors
    const mensagem =
      errors?.senha?.[0] ||
      errors?.senha_antiga?.[0] ||
      error.response?.data?.message ||
      'Erro ao alterar senha'

    $q.notify({
      color: 'red-5',
      textColor: 'white',
      icon: 'error',
      message: mensagem,
    })
  }
}

// Lifecycle
onMounted(async () => {
  await sUsuario.getUsuario(user.usuario.codusuario)
})
</script>

<template>
  <MGLayout>
    <template #tituloPagina>Perfil - Usuário</template>

    <template #content v-if="sUsuario.detalheUsuarios">
      <div style="max-width: 1280px; margin: auto; min-height: 100vh">
        <div class="row q-col-gutter-md q-pa-md">
          <div class="col-xs-12 col-md-6">
            <q-card bordered flat>
              <q-card-section class="text-grey-9 text-overline q-pb-none">
                <div class="text-h6">{{ sUsuario.detalheUsuarios.usuario }}</div>
                <q-badge v-if="sUsuario.detalheUsuarios.inativo" color="red"> Inativo </q-badge>
              </q-card-section>

              <q-card-section class="q-pt-none">
                <q-btn
                  flat
                  dense
                  size="sm"
                  color="primary"
                  label="Alterar Senha"
                  icon="lock"
                  @click="editar()"
                />
              </q-card-section>

              <q-separator inset />

              <q-list>
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
              </q-list>
            </q-card>
          </div>
        </div>
      </div>

      <!-- Dialog Alterar Senha -->
      <q-dialog v-model="dialogAlterarSenha">
        <q-card style="width: 400px">
          <q-form @submit.prevent="salvar()">
            <q-card-section class="text-grey-9 text-overline"> ALTERAR SENHA </q-card-section>

            <q-separator inset />

            <q-card-section class="q-gutter-md">
              <q-input
                outlined
                v-model="modelPerfilUsuario.senha_antiga"
                label="Senha antiga"
                :type="isPwd ? 'password' : 'text'"
                :rules="[(val) => !!val || 'Campo obrigatório']"
              >
                <template #prepend>
                  <q-icon name="lock" />
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
                v-model="modelPerfilUsuario.senha"
                label="Nova Senha"
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
                v-model="modelPerfilUsuario.senha_confirmacao"
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

            <q-card-actions align="right" class="text-primary">
              <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
              <q-btn flat label="Salvar" type="submit" />
            </q-card-actions>
          </q-form>
        </q-card>
      </q-dialog>
    </template>
  </MGLayout>
</template>
