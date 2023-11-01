<template>
    <div class="row">
    
      <div class="col-6">
  
        <draggable
          :list="list"
          :disabled="!enabled"
          item-key="name"
          class="list-group"
          ghost-class="ghost"
          :move="checkMove"
          @start="dragging = true"
          @end="dragging = false"
        >
          <template #item="{ element }">
            <div class="list-group-item" :class="{ 'not-draggable': !enabled }">
              {{ element.name }}
            </div>
          </template>
        </draggable>
      </div>
  
    </div>
  </template>
  
  <script>
 import draggable from 'vuedraggable'
  let id = 1;
  export default {
    name: "simple",
    display: "Simple",
    order: 0,
    components: {
      draggable
    },
    data() {
      return {
        enabled: true,
        list: [
          { name: "John", id: 0 },
          { name: "Joao", id: 1 },
          { name: "Jean", id: 2 }
        ],
        dragging: false
      };
    },
    computed: {
      draggingInfo() {
        return this.dragging ? "under drag" : "";
      }
    },
    methods: {
      add: function() {
        this.list.push({ name: "Juan " + id, id: id++ });
      },
      replace: function() {
        this.list = [{ name: "Edgard", id: id++ }];
      },
      checkMove: function(e) {
        window.console.log("Future index: " + e.draggedContext.futureIndex);
      }
    }
  };
  </script>
  <style scoped>
  .buttons {
    margin-top: 35px;
  }
  
  .ghost {
    opacity: 0.5;
    background: #c8ebfb;
  }
  
  .not-draggable {
    cursor: no-drop;
  }
  </style>