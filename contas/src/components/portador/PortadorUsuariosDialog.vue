<script setup>
// Usuários do portador e o papel de cada um (doc-4, redefinição do dinheiro): o cadeado ao lado
// do editar. Depositante só manda dinheiro para ele (não vê nada); operador vê e movimenta;
// gestor também confirma transferência, reabre e cuida desta lista. Administrador é gestor em
// todos sem estar aqui. Só o gestor abre.
// A lista é um formulário: incluir, trocar o papel e tirar mexem só aqui; o Salvar manda a lista
// inteira e o Cancelar descarta.
import { ref, watch } from 'vue'
import MgSelect from '@components/MgSelect.vue'
import MgSelectUsuario from '@components/MgSelectUsuario.vue'
import { periodoStore, PAPEIS } from '@components/stores/periodoStore'

const store = periodoStore()

const lista = ref([])
const novo = ref({ codusuario: null, papel: 'O' })
// a opção escolhida no MgSelectUsuario: o nome para mostrar na lista antes de salvar
let escolhido = null

watch(
  () => store.dialogUsuarios,
  async (aberto) => {
    if (!aberto) return
    lista.value = []
    novo.value = { codusuario: null, papel: 'O' }
    await store.buscarUsuarios()
    lista.value = store.usuarios.map((u) => ({ ...u }))
  },
)

// quem já está na lista só troca o papel; quem entra vai para o topo, à vista
function incluir() {
  if (!novo.value.codusuario) return
  const existente = lista.value.find((u) => u.codusuario === novo.value.codusuario)
  if (existente) {
    existente.papel = novo.value.papel
  } else {
    lista.value.unshift({
      codusuario: novo.value.codusuario,
      usuario: escolhido?.label,
      usuarioinativo: escolhido?.inativo ?? null,
      papel: novo.value.papel,
    })
  }
  novo.value = { codusuario: null, papel: novo.value.papel }
}

function excluir(u) {
  lista.value = lista.value.filter((x) => x !== u)
}

async function salvar() {
  if (await store.salvarUsuarios(lista.value)) store.dialogUsuarios = false
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
            <MgSelectUsuario
              v-model="novo.codusuario"
              label="Usuário"
              autofocus
              @select="(opt) => (escolhido = opt)"
            />
          </div>
          <div class="col-8 col-sm-4">
            <MgSelect v-model="novo.papel" :options="PAPEIS" emit-value map-options label="Papel" />
          </div>
          <div class="col-4 col-sm-2 q-pt-md">
            <q-btn flat round color="primary" icon="add" type="submit" :disable="!novo.codusuario">
              <q-tooltip>Incluir ou trocar o papel</q-tooltip>
            </q-btn>
          </div>
        </q-card-section>
      </q-form>

      <q-card-section class="col scroll q-pt-none">
        <q-list separator>
          <q-item v-for="u in lista" :key="u.codusuario">
            <q-item-section>
              <q-item-label :class="u.usuarioinativo ? 'text-grey-6' : ''">
                {{ u.usuario }}
                <q-badge v-if="u.usuarioinativo" color="orange-7" class="q-ml-xs">Inativo</q-badge>
              </q-item-label>
            </q-item-section>
            <q-item-section side style="width: 160px">
              <MgSelect
                v-model="u.papel"
                :options="PAPEIS"
                emit-value
                map-options
                :outlined="false"
                borderless
              />
            </q-item-section>
            <q-item-section side>
              <q-btn flat round size="sm" color="grey-7" icon="close" @click="excluir(u)">
                <q-tooltip>Tirar da lista</q-tooltip>
              </q-btn>
            </q-item-section>
          </q-item>
          <q-item v-if="!lista.length">
            <q-item-section class="text-grey-7"
              >Ninguém na lista (só o Administrador).</q-item-section
            >
          </q-item>
        </q-list>
      </q-card-section>

      <q-separator inset />
      <q-card-actions align="right" class="col-auto">
        <q-btn flat label="Cancelar" color="grey-8" tabindex="-1" v-close-popup />
        <q-btn flat label="Salvar" color="primary" :loading="store.salvando" @click="salvar" />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>
