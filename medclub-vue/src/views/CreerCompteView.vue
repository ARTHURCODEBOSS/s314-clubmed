<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import NavBar from '../components/NavBar.vue'

const route = useRoute();
const router = useRouter();
const message = ref('');
const listePays = ref([]);
const suggestions = ref([]);
const montrerSuggestions = ref(false); 
let timerRecherche = null;
const reservation = localStorage.getItem('reservation');
// Données du Client
const prenom = ref('');
const nom = ref('');
const email = ref('');
const password = ref('');
const telephone = ref('');
const genre = ref(''); 
const dateNaissance = ref('');

// Données de l'Adresse
const numRue = ref('');
const nomRue = ref('');
const codePostal = ref('');
const ville = ref('');
const pays = ref('');

// A cocher
const cguAccpete = ref(false);
const messageCgu = ref('');

// A saisir
const messageGenre = ref('');

const messagePrenom = ref('');
const messageNom = ref('');
const messageEmail = ref('');
const messageDateNaissance = ref('');
const messageNumTelephone = ref('');

const messagePasswordCourt = ref('');
const messagePasswordMaj = ref('');
const messagePasswordMin = ref('');
const messagePasswordSpe = ref('');
const messagePasswordNum = ref('');

const messageNumRue = ref('');
const messageNomRue = ref('');
const messageCodePostal = ref('');
const messageVille = ref('');
const messagePays = ref('');


onMounted(async () => {
    try {
        const response = await fetch('https://restcountries.com/v3.1/all?fields=translations');
        const data = await response.json();
        const nomsEnFrancais = data.map(pays => pays.translations.fra.common);
        listePays.value = nomsEnFrancais.sort((a, b) => a.localeCompare(b));

    } catch (error) {
        console.error("Erreur chargement pays", error);
    }
});

const chercherAdresse = async () => {
    if (!numRue.value || String(numRue.value).trim() == "") {
        messageNumRue.value = "Le numéro de l'adresse est obligatoire pour la recherche.";
        suggestions.value = []; 
        return; 
    }
    else if (nomRue.value.length < 3) {
        suggestions.value = [];
        montrerSuggestions.value = false;
        return;
    }
    clearTimeout(timerRecherche);

    timerRecherche = setTimeout(async () => {
        try {
            const recherche = `${numRue.value} ${nomRue.value}`;
            const query = encodeURIComponent(recherche);
            
            const response = await fetch(`https://api-adresse.data.gouv.fr/search/?q=${query}&limit=5`);
            
            if (response.ok) {
                const data = await response.json();
                suggestions.value = data.features;
                montrerSuggestions.value = true;
                console.log(suggestions.value);
            }
        } catch (error) {
            console.error("Erreur API", error);
        }
    }, 300);
};


const selectionnerAdresse = (feature) => {
    const props = feature.properties;
    
    nomRue.value = props.street;
    ville.value = props.city;
    codePostal.value = props.postcode;
    pays.value = "France"

    if (props.housenumber) {
        numRue.value = props.housenumber;
    }

    suggestions.value = [];
    montrerSuggestions.value = false;
};

const register = async () => {
    let champSaisie = (true);
    let dateAujourdhui = new Date()
    dateAujourdhui.setFullYear(dateAujourdhui.getFullYear() -18 );
    console.log(dateNaissance.value);
    if(dateNaissance.value && (dateNaissance.value > dateAujourdhui.toLocaleDateString('fr-CA'))){
        messageDateNaissance.value = "Vous devez être agé d'au moins 18 ans";
        champSaisie = false;
        console.log(dateAujourdhui.toLocaleDateString('fr-FR'));
    }
    else{
        messageDateNaissance.value = '';
    }
    if(!genre.value){
        messageGenre.value = "La saisie du Genre est obligatoire";
        champSaisie = false;
    }
    else{
        messageGenre.value = '';
    }
    if(!cguAccpete.value){
        messageCgu.value = "Field is required";
        champSaisie = false;
    }
    else{
        messageCgu.value = '';
    }
    if(!prenom.value){
        messagePrenom.value = "Veuillez indiquer votre prénom";
        champSaisie = false;
    }
    else{
        messagePrenom.value = '';
    }
    if(!nom.value){
        messageNom.value = "Le nom est obligatoire";
        champSaisie = false;
    }
    else{
        messageNom.value = ''
    }
    if(!email.value){
        messageEmail.value = "Veuillez indiquer votre Email";
        champSaisie = false;
    }
    else{
        messageEmail.value = '';
    }
    if(!/^0[67][0-9]{8}$/.test(telephone.value) ){
        messageNumTelephone.value = "Le numero est incorrect";
        champSaisie = false;
    }
    else{
        messageNumTelephone = "";
    }

    if(password.value.length < 12){
        messagePasswordCourt.value = "Au moins 12 caractères";
        champSaisie = false
    }
    else{
        messagePasswordCourt.value = '';
    }
    if(!/[A-Z]/.test(password.value)){
        messagePasswordMaj.value = "Au moins un caractère majuscule (A-Z)";
        champSaisie = false;
    }
    else{
        messagePasswordMaj.value = '';
    }
    if(!/[a-z]/.test(password.value)){
        messagePasswordMin.value = "Au moins un caractère minuscule (a-z)";
        champSaisie = false;
    }
    else{
        messagePasswordMin.value = '';
    }
    if (!/[0-9]/.test(password.value)) {
        messagePasswordNum.value = "Le mot de passe doit contenir au moins un chiffre.";
        champSaisie = false;
    }
    else{
        messagePasswordNum.value = '';
    }
    if (!/[^a-zA-Z0-9]/.test(password.value)){
        messagePasswordSpe.value = "Au moins un caractère spécial #$%&'()*@?!";
        champSaisie = false;
    }
    else{
        messagePasswordSpe.value = '';
    }

    if(!numRue.value){
        messageNumRue.value = "Le numero de l'adresse est obligatoire.";
        champSaisie = false;
    }
    else {
        messageNumRue.value = '';
    }
    if(!nomRue.value){
        messageNomRue.value = "Veuillez indiquez le nom de la Rue.";
        champSaisie = false;
    }
    else{
        messageNomRue.value = '';
    }
    if(!codePostal.value){
        messageCodePostal.value = "Le Code Postal est obligatoire.";
        champSaisie = false;
    }
    else{
        messageCodePostal.value = '';
    }
    if(!ville.value){
        messageVille.value = "Veuillez indiquez la ville."
        champSaisie = false
    }
    else{
        messageVille.value  = '';
    }
    if(!pays.value){
        messagePays.value = " Le Pays est obligatoire ";
        champSaisie = false;
    }
    else{
        messagePays.value = '';
    }

    if(!champSaisie){
        return;
    }


    try {
        const response = await fetch('http://51.83.36.122:8039/api/inscription', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                prenom: prenom.value,
                nom: nom.value,
                email: email.value,
                password: password.value,
                telephone: telephone.value,
                genre: genre.value,
                datenaissance: dateNaissance.value,

                numrue: numRue.value,
                nomrue: nomRue.value,
                codepostal: codePostal.value,
                ville: ville.value,
                pays: pays.value
            })
        });
        const data = await response.json();

        if (response.ok) {
            localStorage.setItem('user_token', data.token);
            localStorage.setItem('user_infos', JSON.stringify(data.user));
            if(reservation){
                localStorage.removeItem('reservation')
                router.push('/reservation');
            }
            else{
                router.push('/compte');
            }
        } else {
            message.value = data.message || "Erreur lors de l'inscription";
            console.log(data.errors); 
        }

    } catch (e) {
        console.error(e);
        message.value = "Erreur serveur";
    }
};
</script>

<template>
    <NavBar />
    <div class="wrapped-creer-compte">
        <h1>Créer un compte</h1>
        <form @submit.prevent="register">
            
            <h3>Informations Personnelles</h3>
            <div>
                <label>Genre</label><br>
                <select v-model="genre">
                    <option value="M">Monsieur</option>
                    <option value="F">Madame</option>
                </select>
            </div>
            <div v-if="messageGenre" class="error">
                    <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="currentColor">
                        <path d="M480-280q17 0 28.5-11.5T520-320q0-17-11.5-28.5T480-360q-17 0-28.5 11.5T440-320q0 17 11.5 28.5T480-280Zm-40-160h80v-240h-80v240Zm40 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                    </svg>
                    <span c>{{ messageGenre }}</span>
            </div>
            <br>
            <div>
                <label>Prenom *</label><br>
                <input v-model="prenom" placeholder="Prénom" ></input>
            </div>
            <div v-if="messagePrenom" class="error">
                    <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="currentColor">
                        <path d="M480-280q17 0 28.5-11.5T520-320q0-17-11.5-28.5T480-360q-17 0-28.5 11.5T440-320q0 17 11.5 28.5T480-280Zm-40-160h80v-240h-80v240Zm40 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                    </svg>
                    <span c>{{ messagePrenom }}</span>
            </div>
            <br>    
            <div>
                <label>Nom *</label><br>
                <input v-model="nom" placeholder="Nom"  ></input>
            </div>
            <div v-if="messageNom" class="error">
                    <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="currentColor">
                        <path d="M480-280q17 0 28.5-11.5T520-320q0-17-11.5-28.5T480-360q-17 0-28.5 11.5T440-320q0 17 11.5 28.5T480-280Zm-40-160h80v-240h-80v240Zm40 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                    </svg>
                    <span>{{ messageNom }}</span>
            </div>
            <br>
            <div>
                <label>Date de Naissance</label><br>
                <input v-model="dateNaissance" type="date"></input>
            </div>
            <div v-if="messageDateNaissance" class="error">
                    <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="currentColor">
                        <path d="M480-280q17 0 28.5-11.5T520-320q0-17-11.5-28.5T480-360q-17 0-28.5 11.5T440-320q0 17 11.5 28.5T480-280Zm-40-160h80v-240h-80v240Zm40 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                    </svg>
                    <span>{{ messageDateNaissance }}</span>
            </div>
            <br>
            <div>
                <label>Email *</label><br>
                <input v-model="email" type="email" placeholder="Email"></input>
            </div>
            <div v-if="messageEmail" class="error">
                    <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="currentColor">
                        <path d="M480-280q17 0 28.5-11.5T520-320q0-17-11.5-28.5T480-360q-17 0-28.5 11.5T440-320q0 17 11.5 28.5T480-280Zm-40-160h80v-240h-80v240Zm40 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                    </svg>
                    <span>{{ messageEmail }}</span>
            </div>
            <br>
            <div>
                <label>Telephone</label><br>
                <input v-model="telephone" placeholder="Téléphone"></input>
            </div>
            <div v-if="messageNumTelephone" class="error">
                    <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="currentColor">
                        <path d="M480-280q17 0 28.5-11.5T520-320q0-17-11.5-28.5T480-360q-17 0-28.5 11.5T440-320q0 17 11.5 28.5T480-280Zm-40-160h80v-240h-80v240Zm40 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                    </svg>
                    <span>{{ messageNumTelephone }}</span>
            </div>
            <br>
            <div>
                <label>Mot de passe *</label><br>
                <input v-model="password" type="password" placeholder="Mot de passe" ></input>
            </div>
            <div v-if="messagePasswordCourt" class="error">
                    <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="currentColor">
                        <path d="M480-280q17 0 28.5-11.5T520-320q0-17-11.5-28.5T480-360q-17 0-28.5 11.5T440-320q0 17 11.5 28.5T480-280Zm-40-160h80v-240h-80v240Zm40 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                    </svg>
                    <span>{{ messagePasswordCourt }}</span>

            </div>
            <div v-if="messagePasswordMaj" class="error">
                    <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="currentColor">
                        <path d="M480-280q17 0 28.5-11.5T520-320q0-17-11.5-28.5T480-360q-17 0-28.5 11.5T440-320q0 17 11.5 28.5T480-280Zm-40-160h80v-240h-80v240Zm40 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                    </svg>
                    <span>{{ messagePasswordMaj}}</span>

            </div>
            <div v-if="messagePasswordMin" class="error">
                    <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="currentColor">
                        <path d="M480-280q17 0 28.5-11.5T520-320q0-17-11.5-28.5T480-360q-17 0-28.5 11.5T440-320q0 17 11.5 28.5T480-280Zm-40-160h80v-240h-80v240Zm40 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                    </svg>
                    <span>{{ messagePasswordMin }}</span>

            </div>
            <div v-if="messagePasswordNum" class="error">
                    <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="currentColor">
                        <path d="M480-280q17 0 28.5-11.5T520-320q0-17-11.5-28.5T480-360q-17 0-28.5 11.5T440-320q0 17 11.5 28.5T480-280Zm-40-160h80v-240h-80v240Zm40 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                    </svg>
                    <span>{{ messagePasswordNum }}</span>

            </div>
            <div v-if="messagePasswordSpe" class="error">
                    <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="currentColor">
                        <path d="M480-280q17 0 28.5-11.5T520-320q0-17-11.5-28.5T480-360q-17 0-28.5 11.5T440-320q0 17 11.5 28.5T480-280Zm-40-160h80v-240h-80v240Zm40 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                    </svg>
                    <span>{{ messagePasswordSpe}}</span>

            </div>


            <h3>Adresse</h3>
            <div>
                <label>Adresse *</label><br>
                <div style="display: flex; gap: 10px;">
                    <input v-model="numRue" placeholder="N°" style="width: 70px;">
                    <div>
                        <input v-model="nomRue" @input="chercherAdresse" placeholder="Nom de la rue" style="flex: 1;">
                        <ul v-if="montrerSuggestions && suggestions.length > 0" class="suggestions-liste">
                            <li style="list-style: none; padding: 8px 10px; cursor: pointer; border-bottom: 1px solid #ccc;" v-for="item in suggestions" :key="item.properties.id" @mousedown.prevent="selectionnerAdresse(item)" >
                                <strong>{{ item.properties.label }}</strong>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div v-if="messageNumRue" class="error" >
                    <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="currentColor">
                        <path d="M480-280q17 0 28.5-11.5T520-320q0-17-11.5-28.5T480-360q-17 0-28.5 11.5T440-320q0 17 11.5 28.5T480-280Zm-40-160h80v-240h-80v240Zm40 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                    </svg>
                    <span>{{ messageNumRue}}</span>
            </div>
            <div v-if="messageNomRue" class="error">
                    <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="currentColor">
                        <path d="M480-280q17 0 28.5-11.5T520-320q0-17-11.5-28.5T480-360q-17 0-28.5 11.5T440-320q0 17 11.5 28.5T480-280Zm-40-160h80v-240h-80v240Zm40 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                    </svg>
                    <span>{{ messageNomRue}}</span>
            </div>
            <br>
            <div>
                <label>Code postal *</label><br>
                <input v-model="codePostal" placeholder="Code Postal" ></input>
            </div>
            <div v-if="messageCodePostal" class="error" >
                    <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="currentColor">
                        <path d="M480-280q17 0 28.5-11.5T520-320q0-17-11.5-28.5T480-360q-17 0-28.5 11.5T440-320q0 17 11.5 28.5T480-280Zm-40-160h80v-240h-80v240Zm40 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                    </svg>
                    <span>{{ messageCodePostal}}</span>
            </div>
            <br>
            <div>
                <label>Ville *</label>
                <input v-model="ville" placeholder="Ville" ></input>
            </div>
            <div v-if="messageVille" class="error">
                    <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="currentColor">
                        <path d="M480-280q17 0 28.5-11.5T520-320q0-17-11.5-28.5T480-360q-17 0-28.5 11.5T440-320q0 17 11.5 28.5T480-280Zm-40-160h80v-240h-80v240Zm40 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                    </svg>
                    <span>{{ messageVille}}</span>
            </div>
            <br>
            <div class="groupe-pays">
                <label>Pays *</label><br>
                <select v-model="pays"  class="select-pays">
                    <option value="" disabled>-- Choisir un pays --</option>
                    <option v-if="listePays.length === 0" disabled>Chargement des pays...</option>
                    <option v-for="nom in listePays" :key="nom" :value="nom">
                        {{ nom }}
                    </option>
                </select>
            </div>
            <div v-if="messagePays" class="error">
                    <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="currentColor">
                        <path d="M480-280q17 0 28.5-11.5T520-320q0-17-11.5-28.5T480-360q-17 0-28.5 11.5T440-320q0 17 11.5 28.5T480-280Zm-40-160h80v-240h-80v240Zm40 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                    </svg>
                    <span>{{ messagePays}}</span>
            </div>
            <div style="display: flex; gap: 10px; margin-top: 30px;">
                <input style="height: 20px; width: 20px;" type="checkbox"  v-model="cguAccpete">
                <label style="display: flex; gap: 10px;">
                    J'accepte les CGU*
                    <a href="" target="_blank">(Lire les CGU)</a>
                </label>
                <div v-if="messageCgu" class="error">
                    <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="currentColor">
                        <path d="M480-280q17 0 28.5-11.5T520-320q0-17-11.5-28.5T480-360q-17 0-28.5 11.5T440-320q0 17 11.5 28.5T480-280Zm-40-160h80v-240h-80v240Zm40 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                    </svg>
                    <span>{{ messageCgu }}</span>

                </div>
            </div>
            <br><br>
            <button type="submit">S'inscrire</button>
        </form>
        <RouterLink to="/connexion" class="se-connecter">
                Se connecter
        </RouterLink>
        <p style="color: red">{{ message }}</p>
    </div>
</template>

<style scoped>
.liste-suggestions {
    position: absolute;
    z-index: 1000; 
    background: white;
    width: 100%;
    border: 1px solid #ccc;
    border-radius: 0 0 4px 4px;
    list-style: none;
    padding: 0;
    margin: 0;
    max-height: 200px;
    overflow-y: auto;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.liste-suggestions li {
    padding: 10px;
    cursor: pointer;
    border-bottom: 1px solid #eee;
    transition: background 0.2s;
}

.liste-suggestions li:hover {
    background-color: #f0f0f0; 
}

.liste-suggestions strong {
    display: block;
    color: #333;
}

.liste-suggestions small {
    color: #666;
}
.wrapped-creer-compte {
  width: 350px;
  margin: 80px auto;
}

.wrapped-creer-compte h1 {
  text-align: center;
  font-family: 'Cormorant Garamond';
  font-size: 64px;
  font-weight: 100;
  font-style: italic;
  margin-bottom: 40px;
  text-wrap: nowrap;
}

.wrapped-creer-compte h3 {
    font-size: 28px;
    margin: 30px 0;
  text-wrap: nowrap;
}

.wrapped-creer-compte label {
  font-weight: bold;
}

.wrapped-creer-compte input,
.wrapped-creer-compte select {
  height: 45px;
  width: 100%;
  border: 1px solid #ccc;
  outline: none;
  padding: 0 18px;
}

.wrapped-creer-compte input:focus {
  border-color: #000;
}

.wrapped-creer-compte button {
  display: block;
  outline: none;
  border: 1px solid #000;
  background-color: #000;
  padding: 12px 24px;
  color: #fff;
  font-size: 18px;
  font-weight: bold;
  margin: 40px auto 20px auto;
  transition: .2s;
  cursor: pointer;
}

.wrapped-creer-compte button:hover {
  background-color: #fff;
  color: #000;
}

.wrapped-creer-compte a {
  display: block;
  color:#000;
  margin: 0 auto;
  text-align: center;
}

.wrapped-creer-compte a:hover {
  text-decoration: none;
}

.error{
    color: #d32f2f;
    display: flex;
    align-items: center;
    gap: 8px;
}
</style>