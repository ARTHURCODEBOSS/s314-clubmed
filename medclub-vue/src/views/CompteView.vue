<script setup>
import { ref, onMounted, computed, watch  } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';
import NavBar from '../components/NavBar.vue'
import Footer from '../components/Footer.vue'
const router = useRouter();
const message = ref('');
const userInfos = ref(null);
const modification= ref(false);
const donneesStockees = localStorage.getItem('user_infos');
let genre = ref('');
const lesActivites = ref([])
const lesActivitesSel = ref([])

const messagePrenom = ref('');
const messageNom = ref('');

const messageNumRue = ref('');
const messageNomRue = ref('');
const messageCodePostal = ref('');
const messageVille = ref('');
const messagePays = ref('');

const showModal = ref(false);
const selectedReservation = ref(null);

const prixTotal = ref(0);





const modalView = ref('details'); // 'details' ou 'payment'
const paiementForm = ref({
    nom: '',
    numeroCarte: '',
    expiration: '',
    cvv: ''
});


watch(lesActivitesSel, (valeur) => {
    prixTotal.value = 0;
    valeur.forEach(actSel => {
        if (!actSel.activite) return; // skip si rien sélectionné
        const cleanPrice = actSel.activite.prixmin
            ?.replace(/[^\d.,-]/g, '')   
            .replace(',', '.');         
        prixTotal.value += parseFloat(cleanPrice) * (actSel.nbpersonnes || 0);
    });
}, { deep: true });

// --- GESTION DES CARTES BANCAIRES ---
const mesCartes = ref([]);
const showCardModal = ref(false);
const newCardForm = ref({
    numero_carte: '',
    date_expiration: '',
    cvv: ''
});
const erreursNewCard = ref({});

// Récupérer les cartes du client
const fetchMesCartes = async () => {
    if (!userInfos.value || !userInfos.value.numclient) return;

    try {
        // Note: Assurez-vous que l'URL correspond à votre API
        const response = await axios.get(`http://51.83.36.122:8039/api/GetAllCarte/${userInfos.value.numclient}`);
        if (response.status === 200) {
            mesCartes.value = response.data;
        }
    } catch (error) {
        console.error("Erreur chargement cartes:", error);
    }
};

// Formatter le numéro lors de la saisie (Ajout de carte)
const formatNewCardNumber = (event) => {
    let value = event.target.value.replace(/\D/g, '');
    value = value.substring(0, 16);
    const parts = [];
    for (let i = 0; i < value.length; i += 4) {
        parts.push(value.substring(i, i + 4));
    }
    newCardForm.value.numero_carte = parts.join('-');
};

// Sauvegarder une nouvelle carte
const sauvegarderCarte = async () => {
    erreursNewCard.value = {};
    let valid = true;

    // Validation simple
    const numeroClean = newCardForm.value.numero_carte.replace(/[^0-9]/g, '');
    if (numeroClean.length !== 16) {
        erreursNewCard.value.numero_carte = "16 chiffres requis.";
        valid = false;
    }
    if (!newCardForm.value.date_expiration) {
        erreursNewCard.value.date_expiration = "Date requise.";
        valid = false;
    }
    if (!/^\d{3}$/.test(newCardForm.value.cvv)) {
        erreursNewCard.value.cvv = "3 chiffres.";
        valid = false;
    }

    if (valid) {
        try {
            const dateBrute = newCardForm.value.date_expiration;
            const [annee, mois] = dateBrute.split('-'); 
            const dateFormatee = `${mois}/${annee.slice(-2)}`;
            const numeroNettoye = newCardForm.value.numero_carte.replace(/\D/g, '');
            const payload = {
                numclient: userInfos.value.numclient,
                numero_carte: numeroNettoye, // On envoie le numéro propre au back
                date_expiration: dateFormatee,
                cvv: newCardForm.value.cvv,
                est_active: true
            };

            const response = await axios.post('http://51.83.36.122:8039/api/enregistrerCarte', payload);
            
            if (response.status === 201 || response.status === 200) {
                alert("Carte ajoutée avec succès !");
                showCardModal.value = false;
                newCardForm.value = { numero_carte: '', date_expiration: '', cvv: '' }; // Reset
                fetchMesCartes(); // Rafraichir la liste
            }
        } catch (error) {
            console.error("Erreur enregistrement carte:", error);
            alert("Erreur lors de l'enregistrement de la carte.");
        }
        
    }
};

// Helper pour masquer le numéro (ex: **** **** **** 1234)
// Note: Si votre back renvoie un Hash bcrypt, on ne peut pas afficher les 4 derniers chiffres.
// On affiche un masque générique dans ce cas.
const displayCardNumber = (num) => {
    if (!num) return '**** **** **** ****';
    // Si c'est un hash long (bcrypt), on affiche masqué
    if (num.length > 20) return 'Carte enregistrée (Sécurisée)';
    
    // Si c'est un numéro clair ou partiellement masqué
    return '**** **** **** ' + num.slice(-4);
};


const erreursPaiement = ref({});
const getOptionsDisponibles = (indexActuel) => {
    return lesActivites.value.filter(activite => {
        const estDejaSelectionnee = lesActivitesSel.value.some((sel, idx) => {
            if (idx === indexActuel) return false;
            return sel.activite && sel.activite.idactivite === activite.idactivite;
        });
        return !estDejaSelectionnee;
    });
};


const testPaiement = computed(() =>{
    let verif = true
    lesActivitesSel.value.forEach(element => {
        if (element == '')
        {
            verif = false
        }
    });
    if (verif)
    {
        modalView.value = "payment"
    }
})
const minDate = computed(() => {
    const today = new Date();
    const month = (today.getMonth() + 1).toString().padStart(2, '0');
    return `${today.getFullYear()}-${month}`;
});


const formatNumeroCarte = (event) => {
    
    let value = event.target.value.replace(/\D/g, '');
    
    
    value = value.substring(0, 16);
    
    
    const parts = [];
    for (let i = 0; i < value.length; i += 4) {
        parts.push(value.substring(i, i + 4));
    }
    
    
    paiementForm.value.numeroCarte = parts.join('-');
};

const verifierPaiement = async () => {
    erreursPaiement.value = {};
    let estValide = true;

    // ... (tes validations de regex, nom, carte, date, cvv restent identiques ici) ...
    const regexLettres = /^[a-zA-ZÀ-ÿ\s\-]+$/;
    
    if (!paiementForm.value.nom.trim()) {
        erreursPaiement.value.nom = "Le nom du titulaire est obligatoire.";
        estValide = false;
    } else if (!regexLettres.test(paiementForm.value.nom)) {
        erreursPaiement.value.nom = "Le nom ne doit contenir que des lettres.";
        estValide = false;
    }
    const numeroClean = paiementForm.value.numeroCarte.replace(/[^0-9]/g, '');
    if (!/^\d{16}$/.test(numeroClean)) {
        erreursPaiement.value.numeroCarte = "Le numéro doit contenir 16 chiffres.";
        estValide = false;
    }
    if (!paiementForm.value.expiration) {
        erreursPaiement.value.expiration = "Date d'expiration requise.";
        estValide = false;
    } else {
        const [year, month] = paiementForm.value.expiration.split('-');
        const expirationDate = new Date(parseInt(year), parseInt(month) - 1); 
        const now = new Date();
        const currentMonth = new Date(now.getFullYear(), now.getMonth());
        if (expirationDate < currentMonth) {
            erreursPaiement.value.expiration = "Carte expirée.";
            estValide = false;
        }
    }
    if (!/^\d{3}$/.test(paiementForm.value.cvv)) {
        erreursPaiement.value.cvv = "CVV invalide (3 chiffres).";
        estValide = false;
    }

    if (estValide) {
        
        let donneesReservation = {
            activites: lesActivitesSel.value,
            numreservation: selectedReservation.value.numreservation,
            prixTotal: prixTotal.value
        };

        console.log("Envoi des données :", donneesReservation); // Pour vérifier dans la console

        try {
            const response = await axios.post('http://51.83.36.122:8039/api/PostReservationActivite', donneesReservation);
            
            if (response.status === 200 || response.status === 201) {
                console.log("Succès :", response.data);
                alert("Paiement validé avec succès !");
                closeModal();
                window.location.reload();
            }
        } catch (error) {
            console.error("Erreur API :", error);
            alert("Erreur lors de l'enregistrement. Vérifiez la console.");
        }
    }
};
// ---------------------------------------------------

const openModal = async (reservation) => {
    console.log("RESERVATION REÇUE :", reservation);
    // 1. On ouvre la modal et on sélectionne la réservation
    selectedReservation.value = reservation;
    modalView.value = 'details'; // On reset sur détails à l'ouverture
    // Reset form paiement
    paiementForm.value = { nom: '', numeroCarte: '', expiration: '', cvv: '' };
    erreursPaiement.value = {};
    
    showModal.value = true;
    
    
    lesActivites.value = []; 

    try {
        
        const token = localStorage.getItem('user_token');

        // 3. Appel API 
        const response = await fetch(`http://51.83.36.122:8039/api/GetAllActivite/${reservation.club.idclub}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': `Bearer ${token}`
            }
        });

        if (response.ok) {
            const data = await response.json();
            data.activites.forEach(activite => {
                if (parseFloat(activite.prixmin) > 0)
                {
                    lesActivites.value.push(activite); 
                }
                
            });
            
            console.log("Activités chargées :", lesActivites.value);
        } else {
            console.error("Erreur API", response.status);
        }

    } catch (e) {
        console.error("Erreur chargement :", e);
    }
};

const closeModal = () => {
    lesActivitesSel.value = []
    showModal.value = false;
    selectedReservation.value = null;
    modalView.value = 'details';
};

const suggestions = ref([]);
const montrerSuggestions = ref(false); 
let timerRecherche = null;

if (donneesStockees) {
    try {
        userInfos.value = JSON.parse(donneesStockees);
    } catch (e) {
        console.error("Erreur de lecture du JSON", e);
    }
}
if (userInfos.genre = "M"){
    genre = "Mr."
}
else {
    genre = "Mme."
}
onMounted(async () => {
    const token = localStorage.getItem('user_token');
    const response = await fetch('http://51.83.36.122:8039/api/check-token', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Authorization': `Bearer ${token}`
        }
    });
    
    if (response.ok) {
        const data = await response.json();
        console.log("Données sécurisées reçues :", data.user);
        localStorage.setItem('user_infos', JSON.stringify(data.user));
        userInfos.value = data.user;
        fetchMesCartes();
        
    } else {
        console.log("Session expirée");
        localStorage.removeItem('user_token');
        router.push('/connexion');
    }
});
const logout = () => {
    localStorage.removeItem('user_token');
    localStorage.removeItem('user_infos');
    router.push('/connexion');
};

const donnerAvis = (resa) =>{
    localStorage.setItem('resa_avis', JSON.stringify(resa))
    router.push('/donner-avis');
}

const EnvoiSurVente =  () =>{
    router.push("/membre-vente")
}
const EnvoiSurAnalyse = () =>{
    router.push("/directeur-vente")
}

const register = async () => {
    console.log("La");
    let champSaisie = ref(true);
    console.log(userInfos.value.prenom)
    if(!userInfos.value.prenom){
        messagePrenom.value = "Veuillez indiquer votre prénom";
        champSaisie = false;
    }
    else{
        messagePrenom.value = '';
    }
    if(!userInfos.value.nom){
        messageNom.value = "Le nom est obligatoire";
        champSaisie = false;
    }
    else{
        messageNom.value = ''
    }
    if(!userInfos.value.adresse.numrue){
        messageNumRue.value = "Le numero de l'adresse est obligatoire.";
        champSaisie = false;
    }
    else {
        messageNumRue.value = '';
    }
    if(!userInfos.value.adresse.nomrue){
        messageNomRue.value = "Veuillez indiquez le nom de la Rue.";
        champSaisie = false;
    }
    else{
        messageNomRue.value = '';
    }
    if(!userInfos.value.adresse.codepostal){
        messageCodePostal.value = "Le Code Postal est obligatoire.";
        champSaisie = false;
    }
    else{
        messageCodePostal.value = '';
    }
    if(!userInfos.value.adresse.ville){
        messageVille.value = "Veuillez indiquez la ville."
        champSaisie = false
    }
    else{
        messageVille.value  = '';
    }
    
    if(champSaisie){
        console.log(champSaisie.value)
        modification.value = false;
    }
    try {
        const response = await fetch('http://51.83.36.122:8039/api/modification', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                idclient: userInfos.value.numclient,
                prenom: userInfos.value.prenom,
                nom: userInfos.value.nom,
                email: userInfos.value.email,
                telephone: userInfos.value.telephone,
                genre: userInfos.value.genre,
                datenaissance: userInfos.value.datenaissance,
                numadresse : userInfos.value.numadresse,
                numrue: userInfos.value.adresse.numrue,
                nomrue: userInfos.value.adresse.nomrue,
                codepostal: userInfos.value.adresse.codepostal,
                ville: userInfos.value.adresse.ville,
                pays: userInfos.value.adresse.pays
            })
        });

        const data = await response.json();

        if (response.ok) {
            localStorage.setItem('user_infos', JSON.stringify(data.user));
        } else {
            message.value = data.message || "Erreur lors de l'inscription";
            console.log(data.errors); 
        }

    } catch (e) {
        console.error(e);
        message.value = "Erreur serveur";
    }
};

const chercherAdresse = async () => {
    if (!userInfos.value.adresse.numrue || String(userInfos.value.adresse.numrue).trim() == "") {
        messageNumRue.value = "Le numéro de l'adresse est obligatoire pour la recherche.";
        suggestions.value = []; 
        return; 
    }
    else if (userInfos.value.adresse.nomrue.length < 3) {
        suggestions.value = [];
        montrerSuggestions.value = false;
        return;
    }
    clearTimeout(timerRecherche);

    timerRecherche = setTimeout(async () => {
        try {
            const recherche = `${userInfos.value.adresse.numrue} ${userInfos.value.adresse.nomrue}`;
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
    
    userInfos.value.adresse.nomrue= props.street;
    userInfos.value.adresse.ville = props.city;
    userInfos.value.adresse.codepostal = props.postcode;

    if (props.housenumber) {
        userInfos.value.adresse.numrue = props.housenumber;
    }

    suggestions.value = [];
    montrerSuggestions.value = false;
};
</script>

<template>
    <NavBar />
    <div class="client-container">
    <h1 class="main-title">Mon Espace Client</h1>
    
    <p v-if="message" :class="{'success-message': !message.includes('Erreur'), 'error-message': message.includes('Erreur')}">{{ message }}</p>

    <div class="profile-card">
        
        <div v-if="userInfos" class="profile-content">
            
            <div v-if="!modification" class="view-mode">
                <h2 class="section-title">Informations Personnelles</h2>
                
                <div class="info-group">
                    <p class="info-label">Nom Complet :</p>
                    <p class="info-value">{{genre}} {{ userInfos.prenom }} {{userInfos.nom}}</p>
                </div>
                
                <div class="info-group">
                    <p class="info-label">Email :</p>
                    <p class="info-value">{{ userInfos.email }}</p>
                </div>
                
                <div v-if="userInfos.telephone" class="info-group">
                    <p class="info-label">Téléphone :</p>
                    <p class="info-value">{{userInfos.telephone}}</p>
                </div>
                
                <div v-if="userInfos.adresse" class="address-details">
                    <h3 class="subsection-title">Adresse</h3>
                    <p class="info-value">{{userInfos.adresse.numrue}} {{userInfos.adresse.nomrue}} </p>
                    <p class="info-value">{{userInfos.adresse.codepostal}} {{userInfos.adresse.ville}}</p>
                    <p class="info-value">{{userInfos.adresse.pays}}</p>
                </div>
                
                <div class="action-buttons">
                    <button @click="modification = true" class="btn btn-primary">Modifier mes informations</button>
                    </div>
            </div>

            <div v-else class="edit-mode">
                <h2 class="section-title">Modifier mes informations</h2>
                
                <form @submit.prevent="register()">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="genre">Genre</label>
                            <select id="genre" v-model="userInfos.genre">
                                <option value="M">Monsieur</option>
                                <option value="F">Madame</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="prenom">Prénom *</label>
                            <input id="prenom" v-model="userInfos.prenom" type="text" placeholder="Prénom" />
                            <div v-if="messagePrenom" class="error-message error-field">
                                <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#f44336"><path d="M480-280q17 0 28.5-11.5T520-320q0-17-11.5-28.5T480-360q-17 0-28.5 11.5T440-320q0 17 11.5 28.5T480-280Zm-40-160h80v-240h-80v240Zm40 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/></svg>
                                <span>{{ messagePrenom }}</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="nom">Nom *</label>
                            <input id="nom" v-model="userInfos.nom" placeholder="Nom" />
                            <div v-if="messageNom" class="error-message error-field">
                                <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#f44336"><path d="M480-280q17 0 28.5-11.5T520-320q0-17-11.5-28.5T480-360q-17 0-28.5 11.5T440-320q0 17 11.5 28.5T480-280Zm-40-160h80v-240h-80v240Zm40 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/></svg>
                                <span>{{ messageNom }}</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="datenaissance">Date de Naissance</label>
                            <input id="datenaissance" v-model="userInfos.datenaissance" type="date"/>
                        </div>
                        <div class="form-group">
                            <label for="telephone">Telephone</label>
                            <input id="telephone" v-model="userInfos.telephone" placeholder="Téléphone"/>
                        </div>
                    </div>

                    <h3 class="subsection-title">Adresse</h3>
                    
                    <div class="form-row address-fields">
                        <div class="form-group num-rue">
                            <label for="numrue">N° *</label>
                            <input id="numrue" v-model="userInfos.adresse.numrue" placeholder="N°" />
                            <div v-if="messageNumRue" class="error-message error-field">
                                <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#f44336"><path d="M480-280q17 0 28.5-11.5T520-320q0-17-11.5-28.5T480-360q-17 0-28.5 11.5T440-320q0 17 11.5 28.5T480-280Zm-40-160h80v-240h-80v240Zm40 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/></svg>
                                <span>{{ messageNumRue }}</span>
                            </div>
                        </div>
                        <div class="form-group nom-rue">
                            <label for="nomrue">Nom de la rue *</label>
                            <input id="nomrue" v-model="userInfos.adresse.nomrue"  @input="chercherAdresse" placeholder="Nom de la rue" /> 
                            <ul v-if="montrerSuggestions && suggestions.length > 0">
                                <li style="list-style: none; padding: 8px 10px; cursor: pointer; border-bottom: 1px solid #ccc;" v-for="item in suggestions" :key="item.properties.id" @mousedown.prevent="selectionnerAdresse(item)" >
                                    <strong>{{ item.properties.label }}</strong>
                                </li>
                            </ul>
                    
                            <div v-if="messageNomRue" class="error-message error-field">
                                <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#f44336"><path d="M480-280q17 0 28.5-11.5T520-320q0-17-11.5-28.5T480-360q-17 0-28.5 11.5T440-320q0 17 11.5 28.5T480-280Zm-40-160h80v-240h-80v240Zm40 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/></svg>
                                <span>{{ messageNomRue }}</span>
                            </div>
                        </div>
                        <div class="form-group codepostal">
                            <label for="codepostal">Code Postal *</label>
                            <input id="codepostal" v-model="userInfos.adresse.codepostal" placeholder="Code Postal" />
                            <div v-if="messageCodePostal" class="error-message error-field">
                                <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#f44336"><path d="M480-280q17 0 28.5-11.5T520-320q0-17-11.5-28.5T480-360q-17 0-28.5 11.5T440-320q0 17 11.5 28.5T480-280Zm-40-160h80v-240h-80v240Zm40 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/></svg>
                                <span>{{ messageCodePostal }}</span>
                            </div>
                        </div>
                        <div class="form-group ville">
                            <label for="ville">Ville *</label>
                            <input id="ville" v-model="userInfos.adresse.ville" placeholder="Ville" />
                            <div v-if="messageVille" class="error-message error-field">
                                <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#f44336"><path d="M480-280q17 0 28.5-11.5T520-320q0-17-11.5-28.5T480-360q-17 0-28.5 11.5T440-320q0 17 11.5 28.5T480-280Zm-40-160h80v-240h-80v240Zm40 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/></svg>
                                <span>{{ messageVille }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                        <button type="button" @click="modification = false" class="btn btn-secondary">Annuler</button>
                    </div>
                </form>
            </div>
            
        </div>
        <div v-else class="loading-message">
            <p>Chargement des informations client...</p>
        </div>
    </div>
    <div class="profile-card" style="margin-top: 30px;">
    <div class="profile-content">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e0e0e0; margin-bottom: 20px; padding-bottom: 10px;">
            <h2 class="section-title" style="border:none; margin:0; padding:0;">Mes Cartes Bancaires</h2>
            <button @click="showCardModal = true" class="btn btn-primary" style="font-size: 0.9em; padding: 8px 15px;">
                + Ajouter une carte
            </button>
        </div>

        <div v-if="mesCartes.length > 0" class="cards-grid">
            <div v-for="carte in mesCartes" :key="carte.idcb" class="saved-card">
<div class="card-icon">💳</div>
<div class="card-info">
    <p class="card-number">{{ carte.num_visible }}</p>
    
    <p class="card-expiry">Expire le : {{ carte.dateexpiration_carte_bancaire }}</p>
</div>
</div>
        </div>
        
        <div v-else style="text-align: center; color: #777; padding: 20px;">
            <p>Aucune carte enregistrée.</p>
        </div>
    </div>
</div>

<div v-if="showCardModal" class="modal-overlay" @click.self="showCardModal = false">
    <div class="modal-content">
        <span class="close-btn" @click="showCardModal = false">&times;</span>
        <h2 class="section-title" style="margin-top:0;">Ajouter une carte</h2>
        
        <div style="display: flex; flex-direction: column; gap: 15px;">
            <div style="display: flex; flex-direction: column;">
                <label style="font-weight: bold; margin-bottom: 5px;">Numéro de carte</label>
                <input 
                    v-model="newCardForm.numero_carte" 
                    @input="formatNewCardNumber"
                    type="text" 
                    maxlength="19" 
                    placeholder="0000 0000 0000 0000" 
                />
                <span v-if="erreursNewCard.numero_carte" style="color: red; font-size: 0.8em;">{{ erreursNewCard.numero_carte }}</span>
            </div>

            <div style="display: flex; gap: 20px;">
                <div style="flex: 1; display: flex; flex-direction: column;">
                    <label style="font-weight: bold; margin-bottom: 5px;">Expiration</label>
                    <input 
                        v-model="newCardForm.date_expiration" 
                        type="month" 
                        :min="minDate"
                    />
                    <span v-if="erreursNewCard.date_expiration" style="color: red; font-size: 0.8em;">{{ erreursNewCard.date_expiration }}</span>
                </div>
                <div style="flex: 1; display: flex; flex-direction: column;">
                    <label style="font-weight: bold; margin-bottom: 5px;">CVV</label>
                    <input v-model="newCardForm.cvv" type="text" maxlength="3" placeholder="123" />
                    <span v-if="erreursNewCard.cvv" style="color: red; font-size: 0.8em;">{{ erreursNewCard.cvv }}</span>
                </div>
            </div>

            <div style="margin-top: 25px; text-align: right;">
                <button class="btn btn-primary" @click="sauvegarderCarte">Enregistrer</button>
                <button class="btn btn-secondary" @click="showCardModal = false" style="margin-left: 10px;">Annuler</button>
            </div>
        </div>
    </div>
</div>
    <div v-if = "userInfos.role == 'CLIENT'">
        <h2 class="main-title section-separator">Mes Réservations</h2>
    
        <div v-if="userInfos && userInfos.reservations && userInfos.reservations.length > 0" class="reservations-list">
            <div v-for="reservation in userInfos.reservations" :key="reservation.numreservation" class="reservation-item">
                
                <div class="reservation-details">
                    <RouterLink :to="`/club/${reservation.club.idclub}`" class="club-title">
                        {{reservation.club.titre}}
                    </RouterLink>
                    <p class="dates">{{reservation.datedebut}} - {{reservation.datefin}}</p>
                    
                    <div class="reservation-footer">
                        <p :class="['status', `status-${reservation.statut.toLowerCase()}`]">
                            {{reservation.statut}}
                        </p>
                        <p class="price-tag">{{reservation.prix}} €</p>
                    </div>
                    <p v-if="reservation.etat_calcule!='null'">{{reservation.etat_calcule}}</p>
                </div>
                <div style="display: flex; justify-content: space-between;">
                        
                    <div v-if = "reservation.etat_calcule =='PASSÉ'" >
                        <button style="background-color: #000; color: #fff;" class="btn-avis" @click="donnerAvis(reservation)">Donner un avis</button>
                    </div>
                    <button class="btn-avis" @click="openModal(reservation)">Afficher detail/modifier</button>
                
                </div> 
            </div>
        </div>
        <div v-else-if="userInfos" class="no-reservations">
            <p>Vous n'avez aucune réservation en cours ou passée.</p>
        </div>
    </div>
    <div v-else-if="userInfos.role =='VENTE'">
        <h1>reservation</h1>
        <div @click="EnvoiSurVente">
            <p>Voir les reservations</p>
        </div>
    </div>
    <div v-else-if="userInfos.role == 'DIRECTEUR-VENTE'">
        <h1>Analyse Vente</h1>
        <div @click="EnvoiSurAnalyse">
            <p>Voir les analyses ventes</p>
        </div>
    </div>
    
    
    <div v-if="!modification" class="logout-container">
        <button @click="logout" class="btn btn-logout">Se déconnecter</button>
    </div>
    
    </div>
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
        <div class="modal-content">
            <span class="close-btn" @click="closeModal">&times;</span>
            
            <h2 class="section-title" style="margin-top:0;">
                {{ modalView === 'payment' ? 'Paiement Sécurisé' : 'Détails de la réservation' }}
            </h2>
            
            <div v-if="selectedReservation && modalView === 'details'">
                <h3 class="club-title">{{ selectedReservation.club.titre }}</h3>
                
                <div class="info-group">
                    <span class="info-label">Dates :</span>
                    <span>Du {{ selectedReservation.datedebut }} au {{ selectedReservation.datefin }}</span>
                </div>

                <div class="info-group">
                    <span class="info-label">Prix total :</span>
                    <span class="price-tag" style="font-size: 1.2em;">{{ selectedReservation.prix }} €</span>
                </div>

                <div class="info-group">
                    <span class="info-label">Statut :</span>
                    <span :class="['status', `status-${selectedReservation.statut.toLowerCase()}`]">
                        {{ selectedReservation.statut }}
                    </span>
                </div>
                
                <div class="info-group">
                    <span class="info-label">Numéro de réservation :</span>
                    <span>#{{ selectedReservation.numreservation }}</span>
                    
                </div>  
                <section v-if="selectedReservation.activites.length==0">
                        <div class="info-group">
                        <span class="info-label">Ajouter activite :</span>
                        <div style="margin-top: 20px; text-align: right;">
                            <button class="btn btn-primary" @click="lesActivitesSel.push({ nbpersonnes: 1 })">Ajouter</button>
                        </div>
                    </div>
                    <div v-for="(activiteSel, index) in lesActivitesSel" :key="index" style="margin-bottom: 10px;">
                        <p style="margin-bottom: 5px; font-weight: bold;">Activité {{ index + 1 }}</p>
                        
                        <select v-model="lesActivitesSel[index].activite">
                            <option value="" disabled>Choisir une activité</option>
                            
                            <option 
                                v-for="activite in getOptionsDisponibles(index)" 
                                :key="activite.idactivite" 
                                :value="activite"
                            >
                                {{ activite.titre }} ({{ activite.prixmin }}€)
                            </option>
                        </select>
                        <p>nb personnes</p>
                        <div class="quantite-container">
                            <button 
                                class="btn-qty"
                                @click="lesActivitesSel[index].nbpersonnes > 1 && lesActivitesSel[index].nbpersonnes--" 
                            >-</button>

                            <input 
                                type="number"
                                class="input-qty"
                                v-model="lesActivitesSel[index].nbpersonnes" :max="selectedReservation.nbpersonnes" :min="1" @keydown.prevent
                            >

                            <button 
                                class="btn-qty"
                                @click="lesActivitesSel[index].nbpersonnes < selectedReservation.nbpersonnes && lesActivitesSel[index].nbpersonnes++" 
                            >+</button>
                        </div>
                        <button @click="lesActivitesSel.splice(index, 1)" style="margin-left: 10px; color: red; cursor: pointer; border:none; background:transparent;">Supprimer</button>
                        
                    </div>
                    <h1>{{prixTotal}} €</h1>
                    <div style="margin-top: 20px; text-align: right;">
                        <button class="btn btn-primary" @click="testPaiement" v-if="lesActivitesSel.length > 0">Payer</button>
                        <button class="btn btn-primary" @click="closeModal">Fermer</button>
                    </div>
                </section>
                
                <section v-else >
                    <span class="info-label">Activites</span>
                    <div v-for="(item, index) in selectedReservation.activites" :key="index">
                        <p>{{item.titre}}</p>
                        <p>NB Personne : {{item.pivot.nbpersonnes}}</p>
                    </div>
                </section>
                </div>
            
                <div v-else-if="modalView === 'payment'">
                    <p style="margin-bottom: 20px; font-size: 0.9em; color: #666;">Veuillez régler vos nouvelles activités.</p>
                    
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        
                        <div style="display: flex; flex-direction: column;">
                            <label style="font-weight: bold; margin-bottom: 5px;">Nom du titulaire</label>
                            <input v-model="paiementForm.nom" type="text" placeholder="Ex: Jean Dupont" />
                            <span v-if="erreursPaiement.nom" style="color: red; font-size: 0.8em; margin-top: 2px;">{{ erreursPaiement.nom }}</span>
                        </div>
    
                        <div style="display: flex; flex-direction: column;">
                            <label style="font-weight: bold; margin-bottom: 5px;">Numéro de carte</label>
                            <input 
                                v-model="paiementForm.numeroCarte" 
                                @input="formatNumeroCarte"
                                type="text" 
                                maxlength="19" 
                                placeholder="0000 0000 0000 0000" 
                            />
                            <span v-if="erreursPaiement.numeroCarte" style="color: red; font-size: 0.8em; margin-top: 2px;">{{ erreursPaiement.numeroCarte }}</span>
                        </div>
    
                        <div style="display: flex; gap: 20px;">
                            <div style="flex: 1; display: flex; flex-direction: column;">
                                <label style="font-weight: bold; margin-bottom: 5px;">Expiration</label>
                                
                                <input 
                                    v-model="paiementForm.expiration" 
                                    type="month" 
                                    :min="minDate"
                                />
    
                                <span v-if="erreursPaiement.expiration" style="color: red; font-size: 0.8em; margin-top: 2px;">{{ erreursPaiement.expiration }}</span>
                            </div>
                            <div style="flex: 1; display: flex; flex-direction: column;">
                                <label style="font-weight: bold; margin-bottom: 5px;">CVV</label>
                                <input v-model="paiementForm.cvv" type="text" maxlength="3" placeholder="123" />
                                <span v-if="erreursPaiement.cvv" style="color: red; font-size: 0.8em; margin-top: 2px;">{{ erreursPaiement.cvv }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div style="margin-top: 25px; text-align: right; display: flex; gap: 10px; justify-content: flex-end;">
                        <button class="btn btn-primary" @click="verifierPaiement">Valider le paiement</button>
                        <button class="btn btn-secondary" @click="modalView = 'details'">Retour</button>
                    </div>
            </div>
            
        </div>
        
        
    </div>
        
      <router-link to="/admin/propositions" v-if="userInfos.role == 'VENTE'">
        <button class="btn btn-primary" style="font-size: 0.9em; padding: 8px 15px; margin: 20px;">
            GESTION REFUS PROPOSITION
        </button>
    </router-link>

    <router-link to="/directeur-marketing" v-if="userInfos.role == 'DIRECTEUR_MARKETING'">
        <button class="btn btn-primary" style="font-size: 0.9em; padding: 8px 15px; margin: 20px;">
            GESTION NOUVEAUX CLUBS
        </button>
    </router-link>
        
    <Footer />
</template>

<style scoped>
/* Styles pour la grille des cartes */
.cards-grid {
display: grid;
grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
gap: 20px;
}

.saved-card {
border: 1px solid #e0e0e0;
border-radius: 8px;
padding: 15px;
display: flex;
align-items: center;
gap: 15px;
background-color: #fafafa;
transition: box-shadow 0.2s;
}

.saved-card:hover {
box-shadow: 0 2px 8px rgba(0,0,0,0.1);
border-color: #000;
}

.card-icon {
font-size: 2em;
}

.card-info {
flex: 1;
}

.card-number {
font-weight: bold;
font-size: 1.1em;
margin: 0;
}

.card-expiry {
font-size: 0.85em;
color: #666;
margin: 5px 0 0 0;
}

.quantite-container {
display: flex;
align-items: center;
gap: 10px;
}

.btn-qty {
width: 38px;
height: 38px;
border-radius: 10px;
border: none;
font-size: 20px;
font-weight: bold;
cursor: pointer;
transition: 0.15s ease-in-out;
display: flex;
align-items: center;
justify-content: center;

background: #f0f0f0;
color: #333;
}

/* Hover */
.btn-qty:hover {
background: #e0e0e0;
}

/* Bouton désactivé */
.btn-qty:disabled {
opacity: 0.4;
cursor: not-allowed;
}

/* Input nombre */
.input-qty {
width: 55px;
text-align: center;
font-size: 18px;
padding: 5px 0;
border-radius: 10px;
border: 2px solid #ccc;
transition: 0.15s;
}

.input-qty:focus {
border-color: #4a90e2;
outline: none;
}

    /* --- STYLES MODAL --- */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.6); /* Fond semi-transparent */
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.modal-content {
    background-color: white;
    padding: 30px;
    border-radius: 8px;
    width: 90%;
    max-width: 600px;
    position: relative;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
}

.close-btn {
    position: absolute;
    top: 10px;
    right: 15px;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    color: #aaa;
}

.close-btn:hover {
    color: #000;
}
.client-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
}

.main-title {
    text-align: center;
    font-family: 'Cormorant Garamond', serif;
    font-size: 64px;
    font-weight: 100;
    font-style: italic;
    margin: 80px 0 40px;
}

.section-separator {
    margin-top: 80px;
    margin-bottom: 40px;
}

.profile-card {
    background-color: #ffffff;
    padding: 40px;
    border: 1px solid #ccc;
}

.section-title {
    font-size: 32px;
    font-weight: 600;
    margin-bottom: 30px;
    border-bottom: 1px solid #e0e0e0;
    padding-bottom: 10px;
}
.subsection-title {
    font-size: 20px;
    margin-top: 20px;
    margin-bottom: 15px;
}

.info-group {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #eee;
    font-size: 1.1em;
}

.info-label {
    font-weight: bold;
}

.address-details p {
    margin: 5px 0;
    padding-left: 10px;
}
.action-buttons {
    margin-top: 40px;
    display: flex;
    gap: 15px;
    justify-content: flex-start;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    position: relative;
}

.form-group label {
    display: block;
    font-weight: bold;
    margin-bottom: 5px;
    color: #555;
    font-size: 0.9em;
}

input, 
select {
    outline: none;
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    box-sizing: border-box;
}

input:focus, select:focus {
    border-color: #000;
}

.address-fields {
    grid-template-columns: 80px 1fr 120px 1fr;
}

.form-actions {
    margin-top: 30px;
    display: flex;
    gap: 15px;
    justify-content: flex-end;
}

.btn {
    padding: 10px 25px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    transition:  0.2s;
}

.btn-primary {
    border: 1px solid #000;
    background-color: #000;
    color: white;
}
.btn-primary:hover {
    background-color: #fff;
    color: #000;
}

.btn-secondary {
    background: none;
    border: 1px solid #ccc;
    color: #000;
}
.btn-secondary:hover {
    color: #fff;
    background-color: #000;
}

.btn-logout {
    background-color: #f44336; /* Rouge */
    color: white;
    padding: 10px 30px;
}
.btn-logout:hover {
    background-color: #cc3322;
}

.success-message {
    padding: 15px;
    background-color: #e8f5e9; 
    color: #4caf50;
    border: 1px solid #c8e6c9;
    border-radius: 4px;
    margin-bottom: 20px;
    text-align: center;
}
.error-message {
    padding: 10px;
    background-color: #ffebee; 
    color: #f44336; /* Rouge */
    border: 1px solid #ffcdd2;
    border-radius: 4px;
    margin: 5px 0;
    font-size: 0.9em;
    display: flex;
    align-items: center;
}
.error-field span {
    margin-left: 5px;
}


.reservations-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
    gap: 20px;
}

.reservation-item {
    background-color: #fff;
    padding: 20px;
    border: 1px solid #ccc;
}

.club-title {
    font-size: 24px;
    font-weight: bold;
    color: #000;
    text-decoration: none;
    margin-bottom: 5px;
}
.club-title:hover {
    text-decoration: underline;
}

.dates {
    color: #000;
    margin-bottom: 15px;
    font-size: 0.95em;
}

.reservation-footer {
    margin-top: 10px;
    border-top: 1px solid #f0f0f0;
    padding-top: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.status {
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.8em;
    font-weight: bold;
}
/* Couleurs des statuts */
.status-confirmee { background-color: #e8f5e9; color: #4caf50; }
.status-annulee { background-color: #ffcdd2; color: #f44336; }
.status-en_attente { background-color: #fff9c4; color: #ccbb1f; }

.price-tag {
    font-size: 1.5em;
    font-weight: bold;
    color: #000;
}

.no-reservations {
    text-align: center;
    padding: 30px;
    background-color: #fff;
    border: 1px solid #ccc;
}

.btn-avis {
    padding: 8px 22px; 
    border: 1px solid #ccc; 
    background: none; 
    font-weight: bold;
    margin-top: 10px;
    transition: .2s;
    cursor: pointer;
}

.btn-avis:hover {
    background-color: #000;
    color: #fff;
}

.logout-container {
    text-align: right;
    margin-top: 40px;
}
</style>