<template>
  <span>
    <span v-if="criacao || usuariocriacao">
      Criado
      <span v-if="criacao"> em {{ moment(criacao).format('L') }} </span>
      <span v-if="usuariocriacao">por {{ usuariocriacao }}</span>
    </span>
    <span v-if="alteracao && (alteracao !== criacao) || usuarioalteracao">
      Alterado
      <span v-if="alteracao && (alteracao !== criacao)">em {{ moment(alteracao).format('L') }}</span>
      <span v-if="usuarioalteracao">por {{ usuarioalteracao }}</span>
    </span>
  </span>
</template>
<script>
  export default {
    name: 'mg-autor',
    props: [
      'criacao',
      'usuariocriacao',
      'alteracao',
      'usuarioalteracao'
    ]
  }
</script>
