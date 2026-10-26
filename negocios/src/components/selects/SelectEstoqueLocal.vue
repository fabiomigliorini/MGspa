<script setup>
import { ref, onMounted } from 'vue'
import { db } from 'boot/db'

const props = defineProps({
  somenteAtivos: {
    type: Boolean,
    default: true,
  },
  // controlaestoque ausente = PDV ainda não sincronizou o campo; mostra como antes
  somenteControlaEstoque: {
    type: Boolean,
    default: false,
  },
})

const opcoes = ref([])
const filtrado = ref([])

onMounted(async () => {
  let regs = db.estoqueLocal.orderBy('[codfilial+sigla]')
  if (props.somenteAtivos) {
    opcoes.value = await regs
      .filter((item) => {
        return item.inativo == null
      })
      .toArray()
  } else {
    opcoes.value = await regs.toArray()
  }
  if (props.somenteControlaEstoque) {
    opcoes.value = opcoes.value.filter((item) => item.controlaestoque !== false)
  }
  filtrado.value = opcoes.value
})

const pesquisa = (val, update) => {
  if (val === '') {
    update(() => {
      filtrado.value = opcoes.value
    })
    return
  }
  update(() => {
    const pesquisa = val.toLowerCase()
    filtrado.value = opcoes.value.filter((item) => {
      return (
        item.estoquelocal.toLowerCase().indexOf(pesquisa) > -1 ||
        item.sigla.toLowerCase().indexOf(pesquisa) > -1
      )
    })
  })
}
</script>
<template>
  <q-select
    :options="filtrado"
    use-input
    @filter="pesquisa"
    emit-value
    map-options
    option-value="codestoquelocal"
    option-label="estoquelocal"
    v-bind="$attrs"
    options-cover
  >
    <template v-slot:option="scope">
      <q-item v-bind="scope.itemProps">
        <q-item-section avatar>
          <q-icon name="warehouse" />
        </q-item-section>
        <q-item-section>
          <q-item-label>
            {{ scope.opt.estoquelocal }}
          </q-item-label>
          <q-item-label caption>
            {{ scope.opt.codestoquelocal }}
          </q-item-label>
        </q-item-section>
        <q-item-section side>
          {{ scope.opt.sigla }}
        </q-item-section>
      </q-item>
    </template>
  </q-select>
</template>
