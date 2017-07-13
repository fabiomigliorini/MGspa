import Vue from 'vue'

const Case = {
  twoWay: true,
  update: function (el, binding, vnode) {
    let input = el.querySelector('div').querySelector('input')
    let string = input.value

    string = string.replace(/'/g, ' ')
    string = string.replace(/'/g, ' ')
    string = string.trim()
    string = string.replace(/\s{2,}/g, ' ')
    string = string.replace(/(-)1+/gi, '-')
    string = string.replace(/[á|ã|â|à|ª]/g, 'a')
    string = string.replace(/[Á|Ã|Â|À]/g, 'A')
    string = string.replace(/[é|ẽ|ê|è]/g, 'e')
    string = string.replace(/[É|Ẽ|Ê|È]/g, 'E')
    string = string.replace(/[í|ĩ|î|ì]/g, 'i')
    string = string.replace(/[Í|Ĩ|Î|Ì]/g, 'I')
    string = string.replace(/[ó|õ|ô|ò|º]/g, 'o')
    string = string.replace(/[Ó|Õ|Ô|Ò]/g, 'O')
    string = string.replace(/[ú|ũ|û|ù]/g, 'u')
    string = string.replace(/[Ú|Ũ|Û|Ù]/g, 'U')
    string = string.replace(/[ĉ|ç]/g, 'c')
    string = string.replace(/[Ĉ|Ç]/g, 'C')
    string = string.replace(/[ń|ñ|ǹ]/gi, 'n')
    string = string.replace(/[Ń|Ñ|Ǹ]/gi, 'N')

    function findVModelName (vnode) {
    	return vnode.data.directives.find(function (o) {
        return o.name === 'model'
      }).expression
    }

    function setVModelValue (string, vnode) {
      vnode.context[findVModelName(vnode)] = string
    }
    setVModelValue()

    input.value = string
    console.log(vnode.context)
    console.log(string)
  }
}

Vue.directive('case', Case)

export default Case
