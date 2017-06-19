  <template>
  <div class="container">
    <br>
    <br>
    <br>
    <br>
    <div id="login" class="col-md-4 offset-md-4" @submit.prevent="login()">
      <form>

        <!-- Usuario -->
        <fieldset class="form-group" :class="{ 'has-danger': erro }">
          <label class="form-control-label" for="usuario">Usuário</label>
          <input type="text" class="form-control" id="usuario" name="usuario" v-model="usuario">
        </fieldset>

        <!-- Senha -->
        <fieldset class="form-group" :class="{ 'has-danger': erro }">
          <label class="form-control-label" for="senha">Senha</label>
          <input type="password" class="form-control" id="senha" name="senha" v-model="senha">
          <div v-if="erro" class="form-control-feedback">
            {{ mensagem }}
          </div>
        </fieldset>

        <!-- Botao confirmar -->
        <button type="submit" class="btn btn-default">Entrar</button>

      </form>
    </div>
  </div>
</template>

<script>

export default {

  name: 'login',

  data () {
    return {
      usuario: 'fabio',
      senha: 'baseteste',
      erro: false,
      mensagem: 'mensagem'
    }
  },

  methods: {

    login: function (e) {
      var vm = this
      // Busca Autenticacao
      this.axios.post('http://api.notmig01.teste/api/auth/login', {
        usuario: this.usuario,
        senha: this.senha
      }).then(response => {
        // salva token no Local Storage
        var token = response.data.token
        localStorage.setItem('auth.token', token)
        // console.log(response)
        vm.$router.push('/')
      }).catch(error => {
        // Mensagem de erro
        console.log('erro no login')
        this.erro = true
        this.mensagem = error.response.data.mensagem
      })
    }

  }

}
</script>

<style scoped>

</style>
