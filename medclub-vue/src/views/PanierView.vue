<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import NavBar from '../components/NavBar.vue';
import Footer from '../components/Footer.vue';

const router = useRouter();

// --- VARIABLES ---
const reservations = ref([]); // On va mettre les résas ici
const loading = ref(true);    // Pour afficher "Chargement..."

onMounted(async () => {
    const token = localStorage.getItem('user_token');

    // 1. Si pas connecté, on dégage
    if (!token) {
        router.push('/connexion');
        return;
    }

    try {
        
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
            
            
            if ( data.user.reservations) {
                reservations.value = data.user.reservations;
                console.log("Réservations récupérées via le profil :", reservations.value);
            }
        } else {
            // Si le token est périmé
            console.log("Session expirée");
            localStorage.removeItem('user_token');
            router.push('/connexion');
        }

    } catch (e) {
        console.error("Erreur technique :", e);
    } finally {
        loading.value = false;
    }
});

const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('fr-FR');
};
</script>

<template>
    <NavBar />
    
    <div class="panier-container">
    <h1 class="main-title">Mon Panier / Mes Réservations</h1>

    <div v-if="loading" class="msg-box">
        Chargement...
    </div>

    <div v-else-if="reservations.length > 0" class="liste-resa">
        <div v-for="resa in reservations" :key="resa.numreservation" class="carte-resa">
            
            <div class="header">
                <h3>{{ resa.club.titre }}</h3>
                <span :class="['badge', resa.statut.toLowerCase()]">{{ resa.statut }}</span>
            </div>

            <div class="details">
                <p>{{ formatDate(resa.datedebut) }} au {{ formatDate(resa.datefin) }}</p>
                <p>{{ resa.club.ville }}</p>
                <p class="prix">{{ resa.prix }} €</p>
            </div>

            <div class="actions">
                <button class="btn-detail">Voir détails</button>
            </div>

        </div>
    </div>

    <div v-else class="msg-box vide">
        Votre panier est vide (aucune réservation trouvée).
    </div>

    </div>

    <Footer />
</template>

<style scoped>
.panier-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 40px 20px;
    min-height: 60vh;
}

.main-title {
    text-align: center;
    margin-bottom: 30px;
    font-size: 2rem;
}

.msg-box {
    text-align: center;
    padding: 40px;
    background: #fff;
    border-radius: 8px;
    font-size: 1.2rem;
    color: #666;
}

.liste-resa {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.carte-resa {
    border: 1px solid #ccc;
    padding: 20px;
    background: white;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    border-bottom: 1px solid #ccc;
    padding-bottom: 10px;
}

.header h3 {
    margin: 0;
    font-size: 1.3rem;
}

.details p {
    margin: 8px 0;
}

.prix {
    font-weight: bold;
    font-size: 1.2rem;
    color: #000;
    margin-top: 10px;
}

.badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: bold;
    text-transform: uppercase;
}
/* Couleurs statuts */
.badge.confirmée { background: #e8f5e9; color: #2e7d32; }
.badge.en_attente { background: #fff8e1; color: #f57f17; }
.badge.annulée { background: #ffebee; color: #c62828; }

.actions {
    margin-top: 20px;
    text-align: right;
}

.btn-detail {
    background: black;
    color: white;
    border: none;
    padding: 10px 20px;
    border: 1px solid #000;
    font-weight: bold;
    transition: .2s;
    cursor: pointer;
}

.btn-detail:hover {
    background-color: #fff;
    color: #000;
}
</style>