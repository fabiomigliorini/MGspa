<template>
    <div>
        <q-card class="no-shadow" bordered>
            <q-card-section class="text-h6">
                Bar Chart
            </q-card-section>
            <q-card-section>

            </q-card-section>
        </q-card>
    </div>
</template>
  
<script>
import { defineComponent } from 'vue';

export default defineComponent({
    name: "BarFerias",
    setup() {
        return {
            
        }
    },

    methods: {
        SaveImage() {
            const linkSource = this.$refs.barchart.getDataURL();
            const downloadLink = document.createElement('a');
            document.body.appendChild(downloadLink);
            downloadLink.href = linkSource;
            downloadLink.target = '_self';
            downloadLink.download = 'BarChart.png';
            downloadLink.click();
        },
    }
})
</script>
  
<style scoped></style>
  