<script setup>
// Usuários do portador e o papel de cada um (doc-4, redefinição do dinheiro): o cadeado ao lado
// do editar. Depositante só manda dinheiro para ele (não vê nada); operador vê e movimenta;
// gestor também confirma transferência, reabre e cuida desta lista. Administrador é gestor em
// todos sem estar aqui. Só o gestor abre.
import { ref, watch } from 'vue'
import { useQuasar } from 'quasar'
import MgSelect from '@components/MgSelect.vue'
import MgSelectUsuario from '@components/MgSelectUsuario.vue'
import { periodoStore, PAPEIS } from '@components/stores/periodoStore'

const $q = useQuasar()
const store = periodoStore()

const novo = ref({ codusuario: null, papel: 'O' })

watch(
  () => store.dialogUsuarios,
  (aberto) => {
    if (!aberto) return
    novo.value = { codusuario: null, papel: 'O' }
    store.buscarUsuarios()
  },
)

async function incluir() {
  if (!novo.value.codusuario) return
  if (await store.salvarUsuario(novo.value.codusuario, novo.value.papel)) {
    novo.value = { codusuario: null, papel: novo.value.papel }
  }
}

function excluir(u) {
  $q.dialog({
    title: 'Tirar da lista',
    message: `Tirar ${u.usuario} de ${store.portador.portador}?`,
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Tirar', color: 'negative', flat: true },
  }).onOk(() => store.excluirUsuario(u.codportadorusuario))
}
</script>

<template>
  <q-dialog v-model="store.dialogUsuarios">
    <q-card flat class="column no-wrap" style="width: 600px; max-width: 95vw; max-height: 90vh">
      <q-card-section class="col-auto text-grey-9 text-overline text-uppercase">
        Usuários de {{ store.portador?.portador }}
      </q-card-section>
      <q-separator inset />
      <q-card-section class="col-auto text-caption text-grey-7 q-pb-none">
        <div v-for="p in PAPEIS" :key="p.value">
          <span class="text-weight-medium">{{ p.label }}</span
          >: {{ p.descricao }}
        </div>
      </q-card-section>

      <q-form class="col-auto" @submit.prevent="incluir">
        <q-card-section class="row q-col-gutter-md items-start">
          <div class="col-12 col-sm-6">
            <MgSelectUsuario v-model="novo.codusuario" label="Usuário" autofocus />
          </div>
          <div class="col-8 col-sm-4">
            <MgSelect v-model="novo.papel" :options="PAPEIS" emit-value map-options label="Papel" />
          </div>
          <div class="col-4 col-sm-2 q-pt-md">
            <q-btn
              flat
              round
              color="primary"
              icon="add"
              type="submit"
              :disable="!novo.codusuario"
              :loading="store.salvando"
            >
              <q-tooltip>Incluir ou trocar o papel</q-tooltip>
            </q-btn>
          </div>
        </q-card-section>
      </q-form>

      <q-card-section class="col scroll q-pt-none">
        <q-list separator>
          <q-item v-for="u in store.usuarios" :key="u.codportadorusuario">
            <q-item-section>
              <q-item-label :class="u.usuarioinativo ? 'text-grey-6' : ''">
                {{ u.usuario }}
                <q-badge v-if="u.usuarioinativo" color="orange-7" class="q-ml-xs">Inativo</q-badge>
              </q-item-label>
            </q-item-section>
            <q-item-section side style="width: 160px">
              <MgSelect
                :model-value="u.papel"
                :options="PAPEIS"
                emit-value
                map-options
                :outlined="false"
                borderless
                @update:model-value="(p) => store.salvarUsuario(u.codusuario, p)"
              />
            </q-item-section>
            <q-item-section side>
              <q-btn flat round size="sm" color="grey-7" icon="close" @click="excluir(u)">
                <q-tooltip>Tirar da lista</q-tooltip>
              </q-btn>
            </q-item-section>
          </q-item>
          <q-item v-if="!store.usuarios.length">
            <q-item-section class="text-grey-7"
              >Ninguém na lista (só o Administrador).</q-item-section
            >
          </q-item>
        </q-list>
      </q-card-section>

      <q-separator inset />
      <q-card-actions align="right" class="col-auto">
        <q-btn flat label="Fechar" color="grey-8" v-close-popup />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>
