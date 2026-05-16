<script setup>
import { computed, unref } from 'vue'
import { Dialog, Notify } from 'quasar'
import { tempoRelativo } from './formatters'

const props = defineProps({
  auth: { type: Object, required: true },
})

const usuario = computed(() => unref(props.auth.usuario))
const permissoes = computed(() => unref(props.auth.permissoes) || [])
const ehAdmin = computed(() => !!unref(props.auth.ehAdmin))
const expiresEm = computed(() => unref(props.auth.expiresEm))
const uuidPdv = computed(() => unref(props.auth.uuidPdv))
const permiteLogin = computed(() => !!unref(props.auth.permiteLogin))

const nome = computed(() => usuario.value?.usuario || '')
const inicial = computed(() => nome.value.charAt(0).toUpperCase())
const pessoa = computed(
  () => usuario.value?.Pessoa?.pessoa || usuario.value?.pessoa?.pessoa || null,
)

function confirmarLogout() {
  Dialog.create({
    title: 'Confirmar',
    message: 'Deseja realmente sair do sistema?',
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Sair', color: 'negative' },
  }).onOk(() => props.auth.logout?.())
}

async function renovar() {
  try {
    await props.auth.renovarToken?.()
    Notify.create({ message: 'Sessão renovada', color: 'green-5', icon: 'done', timeout: 1500 })
  } catch {
    Notify.create({ message: 'Falha ao renovar sessão', color: 'red-5', icon: 'error' })
  }
}

const perfilUrl = () => {
  if (!!process.env.PESSOAS_URL) {
    return '/perfil'
  }
  return `${process.env.PESSOAS_URL}/perfil`
}
</script>

<template>
  <q-btn v-if="usuario" flat round icon="account_circle" :aria-label="nome || 'Usuário'">
    <q-menu>
      <q-card flat bordered style="width: 400px; max-width: 65vw">
        <!-- NOME -->
        <q-card-section class="q-pa-sm">
          <q-item :href="perfilUrl">
            <q-item-section avatar>
              <q-avatar color="primary" text-color="white" size="50px">
                {{ inicial }}
              </q-avatar>
            </q-item-section>
            <q-item-section>
              <q-item-label class="text-weight-bold text-primary">{{ nome }}</q-item-label>
              <q-item-label v-if="pessoa" caption>{{ pessoa }}</q-item-label>
              <q-item-label v-if="ehAdmin" caption>
                Administrador
                <q-icon name="star" size="xs" color="amber" />
              </q-item-label>
            </q-item-section>
          </q-item>
        </q-card-section>

        <!-- EXPIRA -->
        <!-- <pre>{{ props.auth }}</pre> -->
        <template>
          <q-separator />
          <q-card-section class="q-pa-sm">
            <q-item>
              <q-item-section>
                <q-item-label v-if="expiresEm" caption class="text-grey-7">
                  Expira {{ tempoRelativo(expiresEm) }}
                  <q-btn flat dense @click="renovar" icon="new"></q-btn>
                </q-item-label>
                <q-item-label v-if="uuidPdv" caption class="text-grey-7 ellipsis">
                  PDV {{ uuidPdv }}
                </q-item-label>
              </q-item-section>
            </q-item>
          </q-card-section>
        </template>

        <!-- GRUPOS -->
        <template v-if="permissoes.length">
          <q-separator />
          <q-card-section class="q-pa-sm">
            <!-- <q-item-label overline class="text-grey-7">Permissões</q-item-label> -->
            <q-chip
              v-for="perm in permissoes"
              :key="perm"
              size="sm"
              color="primary"
              text-color="white"
            >
              {{ perm }}
            </q-chip>
          </q-card-section>
        </template>
      </q-card>
      <q-list>
        <q-separator />

        <q-item v-if="auth.renovarToken" clickable v-close-popup>
          <q-item-section avatar>
            <q-icon name="refresh" color="primary" />
          </q-item-section>
          <q-item-section>
            <q-item-label>Renovar</q-item-label>
          </q-item-section>
        </q-item>

        <q-item clickable v-close-popup @click="confirmarLogout">
          <q-item-section avatar>
            <q-icon name="logout" color="negative" />
          </q-item-section>
          <q-item-section>
            <q-item-label>Sair</q-item-label>
          </q-item-section>
        </q-item>
      </q-list>
    </q-menu>
  </q-btn>

  <q-btn
    v-else-if="permiteLogin"
    flat
    round
    icon="login"
    color="negative"
    aria-label="Conectar"
    @click="auth.login?.()"
  />
</template>
