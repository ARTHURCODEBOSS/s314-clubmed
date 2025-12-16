<script setup>

defineProps({
    numreservation: Number,
    prix_total: Number,
    
})

</script>

<template>
    <div class="card">
        <h3>{{ numreservation }}</h3>
        <p>{{ prix_total }}</p>
    </div>
</template>