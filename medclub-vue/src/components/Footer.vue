<script setup>
import { ref, onMounted } from 'vue';

</script>

<template>

<footer>

    <div class="infos">
        <ul>
            <li><a href="#">Instagram</a></li>
            <li><a href="#">Facebook</a></li>
            <li><a href="#">X</a></li>
            <li><a href="#">Youtube</a></li>
            <li><a href="#">Pinterest</a></li>
        </ul>
        <ul>
            <li><a href="#">Informations Légales</a></li>
            <li><a href="#">Charte Cookies</a></li>
            <li><a href="#">Charte de Protection des données</a></li>
            <li><a href="#">Consentement des Cookies</a></li>
        </ul>
    </div>

    <img src="../assets/logo.svg" alt="logo-clubmed-white">

</footer>

</template>

<style scoped>
footer {
    min-height: 200px;
    width: 100%;
    background-color: #000;
    color: #fff;

    display: flex;
    justify-content: space-between;
    padding: 130px 200px;
}

.infos {
    display: flex;
    gap: 80px;
}

.infos ul {
    list-style: none;
}

.infos ul li {
    margin: 8px 0;
}

.infos ul a {
    color: #fff;
}

.infos ul a:hover {
    text-decoration: none;
}

footer img {
    width: 180px;
    filter: brightness(0) invert(1);
}
</style>