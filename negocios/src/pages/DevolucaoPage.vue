<script setup>
import { formataNumero } from '@components/formatters'
import { ref, computed, onMounted } from 'vue'
import { negocioStore } from 'stores/negocio'
import { useRoute } from 'vue-router'
import { produtoStore } from 'src/stores/produto'
import { Dialog } from 'quasar'
import { useRouter } from 'vue-router'
import moment from 'moment/min/moment-with-locales'
import MgInputValor from '@components/MgInputValor.vue'
moment.locale('pt-br')

const route = useRoute()
const router = useRouter()

const sNegocio = negocioStore()
const sProduto = produtoStore()

const itensDevolucao = ref([])
const totalDevolucao = ref(null)

// o que falta no cadastro do cliente para devolver (TASK-31). Os campos sao os legados
// do tblpessoa, que vem no negocio recarregado do servidor: sem endereco o cadastro grava
// 'Nao Informado', e sem telefone sobra lixo como '00 0000 0000' ou '0'
const pendencias = computed(() => {
  const p = sNegocio.negocio?.Pessoa
  if (!p || sNegocio.negocio.codpessoa == 1) {
    return []
  }
  const ret = []
  if (!p.cidade || !p.endereco || p.endereco == 'Nao Informado') {
    ret.push('endereço')
  }
  if (![p.telefone1, p.telefone2, p.telefone3].some((t) => /[1-9]/.test(t ?? ''))) {
    ret.push('telefone')
  }
  return ret
})

const urlPessoa = () => process.env.PESSOAS_URL + '/pessoa/' + sNegocio.negocio.codpessoa

onMounted(() => {
  sNegocio.carregarPeloUuid(route.params.uuid).then(() => {
    itensDevolucao.value = [...sNegocio.negocio.itens]
    itensDevolucao.value = itensDevolucao.value.filter((i) => {
      return !i.inativo
    })
    itensDevolucao.value.forEach((i) => {
      i.devolvido = 0
      i.devolucoes.forEach((d) => {
        i.devolvido += d.quantidade
      })
      i.disponivelDevolucao = i.quantidade - i.devolvido
      i.quantidadeDevolucao = null
      i.valorDevolucao = null
    })
  })
})

const calcularTotalDevolucao = () => {
  if (sNegocio.negocio.codpessoa !== 1) {
    totalDevolucao.value = itensDevolucao.value.reduce(
      (n, { valorDevolucao }) => n + valorDevolucao,
      0,
    )
  }
}

const calcularValorDevolucao = (item) => {
  let valoruitario = item.valortotal / item.quantidade
  item.valorDevolucao = Math.round(valoruitario * item.quantidadeDevolucao * 100) / 100
  calcularTotalDevolucao()
}

const marcarTodos = () => {
  itensDevolucao.value.forEach((i) => {
    if (i.disponivelDevolucao > 0) {
      i.quantidadeDevolucao = i.disponivelDevolucao
      calcularValorDevolucao(i)
    }
  })
}

const marcarNenhum = () => {
  itensDevolucao.value.forEach((i) => {
    i.quantidadeDevolucao = null
    calcularValorDevolucao(i)
  })
}

const salvarDevolucao = async () => {
  if (pendencias.value.length) {
    return
  }
  Dialog.create({
    title: 'Devolução',
    message: 'Tem certeza que deseja devolver esses produtos?',
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'OK', color: 'primary', flat: true },
  }).onOk(async () => {
    try {
      const ret = await sNegocio.Devolucao(itensDevolucao.value)
      if (ret.data.data) {
        router.push('/negocio/' + ret.data.data.codnegocio)
      }
    } catch (error) {
      console.log(error)
    }
  })
}
</script>
<template>
  <q-page class="bg-grey-2">
    <div class="flex flex-center">
      <q-card
        style="max-width: 800px"
        class="q-pa-md q-ma-md"
        v-if="sNegocio.negocio"
        flat
        bordered
      >
        <h4 class="q-ma-md">Selecione os produtos para Devolução!</h4>

        <q-banner inline-actions class="text-white bg-red" v-if="sNegocio.negocio.codpessoa == 1">
          Informe o cadastro da pessoa para fazer uma devolução!
        </q-banner>

        <q-banner inline-actions class="text-white bg-red" v-if="pendencias.length">
          Cliente sem {{ pendencias.join(' e ') }} no cadastro! Complete o cadastro para fazer a
          devolução.
          <template #action>
            <q-btn flat label="Abrir cadastro" :href="urlPessoa()" target="_blank" />
          </template>
        </q-banner>

        <div class="row">
          <q-btn
            label="Marcar Todos"
            class="q-mt-md flex flex-center"
            color="secondary"
            @click="marcarTodos()"
            flat
          />
          <q-btn
            label="Marcar Nenhum"
            class="q-mt-md flex flex-center"
            color="secondary"
            @click="marcarNenhum()"
            flat
          />
          <q-btn
            label="Confirmar"
            class="q-mt-md flex flex-center"
            color="primary"
            @click="salvarDevolucao"
            flat
            v-if="totalDevolucao && !pendencias.length"
          />
        </div>

        <q-form @submit="salvarDevolucao()">
          <q-list class="rounded-borders">
            <template v-for="item in itensDevolucao" :key="item.codnegocioprodutobarra">
              <q-item>
                <q-item-section avatar>
                  <q-img :src="sProduto.urlImagem(item.codimagem)" style="width: 130px" />
                </q-item-section>

                <q-item-section class="col">
                  <q-item-label lines="2">{{ item.produto }}</q-item-label>
                  <q-item-label caption lines="1">
                    <span class="text-weight-bold">{{ item.barras }}</span>
                  </q-item-label>
                  <q-item-label caption lines="1">
                    {{ formataNumero(item.quantidade, 3) }}
                    de R$
                    {{ formataNumero(item.valorunitario) }}
                    <template v-if="item.valordesconto">
                      - R$
                      {{ formataNumero(item.valordesconto) }}
                      (desconto)
                    </template>
                    <template v-if="item.valorfrete">
                      + R$
                      {{ formataNumero(item.valorfrete) }}
                      (Frete)
                    </template>
                    <template v-if="item.valorseguro">
                      + R$
                      {{ formataNumero(item.valorseguro) }}
                      (Seguro)
                    </template>
                    <template v-if="item.valoroutras">
                      + R$
                      {{ formataNumero(item.valoroutras) }}
                      (Outras)
                    </template>
                    = R$
                    {{ formataNumero(item.valortotal) }}
                  </q-item-label>
                  <q-item-label overline class="text-orange-7" v-if="item.devolvido > 0">
                    {{ formataNumero(item.devolvido, 3) }}
                    já devolvido anteriormente
                  </q-item-label>
                </q-item-section>
                <q-item-section style="max-width: 160px">
                  <MgInputValor
                    v-model="item.quantidadeDevolucao"
                    :decimals="3"
                    :min="0"
                    :max="item.disponivelDevolucao"
                    :disable="item.disponivelDevolucao == 0"
                    item-aligned
                    label="Quantidade"
                    @change="calcularValorDevolucao(item)"
                    :rules="[(val) => val <= item.disponivelDevolucao]"
                  />
                </q-item-section>
                <q-item-section class="text-right" style="max-width: 90px">
                  <template v-if="item.valorDevolucao">
                    R$
                    {{ formataNumero(item.valorDevolucao) }}
                  </template>
                </q-item-section>
              </q-item>

              <q-separator />
            </template>
          </q-list>

          <template v-if="totalDevolucao">
            <h6 class="text-right q-pa-md q-ma-sm">
              Total Devolução: R$
              {{ formataNumero(totalDevolucao) }}
            </h6>
          </template>
        </q-form>
      </q-card>
    </div>
  </q-page>
</template>
