<script setup>
import { ref, onMounted } from 'vue';
import api from '../services/api'; 
import NavBar from '../components/NavBar.vue'

const reservations = ref([]);
const chargement = ref(true);
const erreur = ref(null);

// Récupérer toutes les réservations (ou faire une route API spécifique pour les filtrer côté serveur)
const chargerReservations = async () => {
    try {
        const response = await api.get('/reservations'); // Assure-toi que cette route existe dans ton Controller
        // On ne garde que celles qui sont en attente de réponse du client
        reservations.value = response.data.filter(r => r.statut === 'PROPOSITION_EN_COURS');
        chargement.value = false;
    } catch (err) {
        console.error(err);
        erreur.value = "Impossible de charger les réservations.";
        chargement.value = false;
    }
};

const refuserProposition = async (reservation) => {
    if (!confirm(`Confirmer le remboursement et l'annulation de la commande n°${reservation.numreservation} ?`)) {
        return;
    }

    try {
        // Appel à la route créée à l'étape précédente
        const response = await api.post(`/reservation/${reservation.numreservation}/refuser-proposition`);
        
        alert(`Succès : ${response.data.message}. Montant remboursé : ${response.data.montant_rembourse}€`);
        
        // On retire la réservation de la liste pour éviter de la re-cliquer
        reservations.value = reservations.value.filter(r => r.numreservation !== reservation.numreservation);

    } catch (err) {
        console.error(err);
        alert("Erreur : " + (err.response?.data?.error || "Une erreur est survenue"));
    }
};

onMounted(() => {
    chargerReservations();
});
</script>

<template>
    <NavBar />
    
    <div class="admin-container">
        <h1>Gestion des Propositions (Service Vente)</h1>

        <p v-if="chargement" class="loader">Chargement des dossiers...</p>
        <p v-if="erreur" class="text-error">{{ erreur }}</p>

        <div v-if="!chargement && reservations.length === 0" class="empty-state">
            Aucune réservation en attente de réponse client.
        </div>

        <div v-else class="reservations-list">
            <div v-for="resa in reservations" :key="resa.numreservation" class="resa-card">
                <div class="resa-info">
                    <h3>Dossier N°{{ resa.numreservation }}</h3>
                    <p><strong>Client :</strong> {{ resa.numclient }}</p>
                    <p><strong>Prix :</strong> {{ resa.prix }}€</p>
                    <span class="status-badge">Refus client</span>
                </div>
                
                <div class="resa-actions">
                    <button 
                        @click="refuserProposition(resa)" 
                        class="btn-danger">
                        Enregistrer Refus & Rembourser
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.admin-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 20px;
}

.reservations-list {
    display: grid;
    gap: 20px;
    margin-top: 30px;
}

.resa-card {
    border: 1px solid #ccc;
    padding: 20px;
    border-radius: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.status-badge {
    background-color: #fff3cd;
    color: #856404;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 0.9em;
    font-weight: bold;
}

.btn-danger {
    background-color: #dc3545;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
    transition: background 0.2s;
}

.btn-danger:hover {
    background-color: #c82333;
}

.text-error { color: red; font-weight: bold; }
.loader { text-align: center; color: #666; }
</style>