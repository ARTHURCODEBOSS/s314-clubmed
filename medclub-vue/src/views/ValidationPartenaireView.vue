<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const loading = ref(true);
const reservation = ref(null);
const actionFaite = ref(false);
const message = ref('');
const reservation_token = ref("")

const token = route.query.token;
onMounted(async () => {
    try {
        const response = await axios.get(`http://51.83.36.122:8039/api/verif-token/${token}`);
        reservation.value = response.data.data;
        loading.value = false;
        reservation_token.value = response.data.type
       
    } catch (e) {
        message.value = "Lien invalide ou expiré.";
        loading.value = false;
    }
});

const repondre = async (reponse) => {
    if(reservation_token.value == 'reservation'){
        console.log("b")
        try {
            await axios.post('http://51.83.36.122:8039/api/reponse', {
                token: token,
                statut: reponse
            });
            actionFaite.value = true;
        } catch (e) {
            alert("Erreur lors de l'envoi de la réponse");
        }
    }
    else if(reservation_token.value == 'partenaire'){
        console.log("c")
        try {
            await axios.post('http://51.83.36.122:8039/api/reponse-partenaire', {
                token: token,
                statut: reponse
            });
            actionFaite.value = true;
        } catch (e) {
            alert("Erreur lors de l'envoi de la réponse");
        }
    }
    else{
        console.log("a")
    }
    
};
</script>

<template>
    <div class="container">
        <div v-if="loading">Vérification du lien...</div>
        <div v-else-if="!reservation" class="error">
            <h1> Erreur</h1>
            <p>{{ message }}</p>
        </div>

        <div v-else-if="actionFaite" class="success">
            <h1> C'est noté !</h1>
            <p>{{ message }}</p>
        </div>

        <div v-else class="card">
            <h1 v-if="reservation_token == 'reservation'">Demande de Disponibilité Pour le Club</h1>
            <h1>Demande de Disponibilité Pour l'Activité</h1>
            <p><strong>Client :</strong> {{ reservation.client.nom }}</p>
            <p><strong>Date :</strong> {{ reservation.datedebut }} Jusqu'à {{ reservation.datefin }} </p>
            <p><strong>Personnes :</strong> {{ reservation.nbpersonnes }}</p>

            <div class="buttons">
                <button @click="repondre('CONFIRME')" class="btn-oui"> Je confirme la dispo</button>
                <button @click="repondre('REFUSE')" class="btn-non"> Pas de place</button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.container {
    max-width: 550px;
    margin: 60px auto;
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
    text-align: center;
    color: #333;
}

.card {
    background: #ffffff;
    padding: 40px;
    border-radius: 8px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    border: 1px solid #e5e7eb;
}

h1 {
    color: #1f2937;
    margin-bottom: 25px;
    font-size: 24px;
    font-weight: 600;
}

p {
    font-size: 16px;
    line-height: 1.6;
    margin: 10px 0;
    color: #4b5563;
}

strong {
    color: #111827;
}

.buttons {
    margin-top: 35px;
    display: flex;
    justify-content: center;
    gap: 15px;
}

button {
    padding: 12px 25px;
    border: none;
    border-radius: 6px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    color: white;
}

button:hover {
    transform: translateY(-1px);
    opacity: 0.95;
}

button:active {
    transform: translateY(0);
}

.btn-oui {
    background-color: #27ae60;
    box-shadow: 0 2px 4px rgba(39, 174, 96, 0.2);
}

.btn-non {
    background-color: #c0392b;
    box-shadow: 0 2px 4px rgba(192, 57, 43, 0.2);
}

.success {
    background-color: #ecfdf5;
    color: #065f46;
    padding: 40px;
    border-radius: 8px;
    border: 1px solid #a7f3d0;
}

.success h1 {
    color: #059669;
    margin-bottom: 10px;
}

.error {
    background-color: #fef2f2;
    color: #991b1b;
    padding: 40px;
    border-radius: 8px;
    border: 1px solid #fecaca;
}

.error h1 {
    color: #dc2626;
    margin-bottom: 10px;
}
</style>