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
const expiresAt = computed(() => unref(props.auth.expiresAt))
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
    ok: { label: 'Sair', color: 'negative', flat: true },
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

// `typeof process` é seguro em runtime (não dispara ReferenceError em prod sem polyfill).
// Webpack/vite substitui `process.env.PESSOAS_URL` em build nos apps que têm a var;
// nos que não têm (ex: pessoas), cai no fallback ''.
const PESSOAS_URL =
  (typeof process !== 'undefined' && process.env && process.env.PESSOAS_URL) || ''
const perfilUrl = computed(() => (PESSOAS_URL ? `${PESSOAS_URL}/perfil` : '/perfil'))
</script>

<template>
  <q-btn v-if="usuario" flat round icon="person" aria-label="Usuário">
    <q-menu>
      <q-card flat bordered style="width: 320px; max-width: 65vw">
        <!-- NOME -->
        <q-item :href="perfilUrl" class="q-pa-lg">
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

        <!-- EXPIRA -->
        <!-- <pre>{{ props.auth }}</pre> -->
        <q-separator />
        <q-card-section class="q-pa-sm">
          <q-item>
            <q-item-section>
              <q-item-label v-if="expiresAt" caption class="text-grey-7">
                Expira {{ tempoRelativo(expiresAt) }}
                <q-btn flat dense size="sm" @click="renovar" icon="refresh"></q-btn>
              </q-item-label>
              <q-item-label v-if="uuidPdv" caption class="text-grey-7 ellipsis">
                PDV {{ uuidPdv }}
              </q-item-label>
            </q-item-section>
          </q-item>
        </q-card-section>

        <!-- GRUPOS -->
        <template v-if="permissoes.length">
          <q-separator />
          <q-card-section class="q-pa-md">
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

        <!-- SAIR -->
        <q-separator />
        <q-item clickable v-close-popup @click="confirmarLogout" class="q-pa-lg">
          <q-item-section avatar>
            <q-icon name="logout" color="negative" />
          </q-item-section>
          <q-item-section>
            <q-item-label>Sair</q-item-label>
          </q-item-section>
        </q-item>
      </q-card>
    </q-menu>
  </q-btn>

  <!-- BOTAO LOGIN -->
  <q-btn
    v-else-if="permiteLogin"
    flat
    round
    icon="login"
    aria-label="Conectar"
    @click="auth.login?.()"
  />
</template>
