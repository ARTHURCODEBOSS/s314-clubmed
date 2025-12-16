<script setup>
import { ref, onMounted } from 'vue';
import api from '../services/api';
import NavBar from '../components/NavBar.vue';
import Footer from '../components/Footer.vue';

const clubs = ref([]);
const periodes = ref([]);
const chargement = ref(true);
const erreur = ref(null);
const prixSaisis = ref({});

// Initialiser la structure de données des prix pour éviter les crashs
const initPrix = (clubId, typeId, periodeId) => {
    if (!prixSaisis.value[clubId]) prixSaisis.value[clubId] = {};
    if (!prixSaisis.value[clubId][typeId]) prixSaisis.value[clubId][typeId] = {};
    // On met 0 par défaut si vide
    if (prixSaisis.value[clubId][typeId][periodeId] === undefined) {
        prixSaisis.value[clubId][typeId][periodeId] = 0;
    }
};

const chargerDonnees = async () => {
    chargement.value = true;
    erreur.value = null;
    
    try {
        
        const repPeriodes = await api.get('/getPeriodes'); 
        periodes.value = repPeriodes.data;

        
        const repClubs = await api.get('/getClubsEnAttente');
        clubs.value = repClubs.data;

        
        clubs.value.forEach(club => {
            if (club.types_chambres_uniques) {
                club.types_chambres_uniques.forEach(type => {
                    periodes.value.forEach(p => {
                      
                        initPrix(club.idclub, type.idtypechambre, p.numperiode);
                    });
                });
            }
        });

    } catch (e) {
        console.error(e);
        erreur.value = "Erreur de chargement : " + (e.message || "Erreur inconnue");
    } finally {
        chargement.value = false;
    }
};


const validerEtPublier = async (club) => {
    if(!confirm("Voulez-vous vraiment valider et publier ce club ?")) return;
    
    // Construction des données à envoyer
    const tarifs = [];
    
    // On parcourt les données saisies
    for (const type of club.types_chambres_uniques) {
        for (const p of periodes.value) {
            // Récupération de la valeur saisie
            const val = prixSaisis.value[club.idclub][type.idtypechambre][p.numperiode];
            const prixFinal = val ? parseFloat(val) : 0; 

            // --- DEBUT DE LA VERIFICATION ---
            // On vérifie que le prix est strictement supérieur à 0
            if (prixFinal <= 0) {
                alert(`Erreur : Le prix pour la chambre "${type.nomtype}" (Période : ${p.numperiode}) doit être supérieur à 0.`);
                return; // On arrête tout ici, rien n'est envoyé au serveur
            }
            // --- FIN DE LA VERIFICATION ---

            tarifs.push({
                numperiode: p.numperiode,
                idtypechambre: type.idtypechambre,
                prix: prixFinal 
            });
        }
    }

    
    try {
        await api.post(`/validerEtTarifer/${club.idclub}`, { tarifs });
        alert("Club validé avec succès !");
        clubs.value = clubs.value.filter(c => c.idclub !== club.idclub);
    } catch (e) {
        alert("Erreur lors de la validation");
    }
};

onMounted(() => {
  
    const userStr = localStorage.getItem('user_infos');
    
    if (!userStr) {
        alert("Accès refusé. Veuillez vous connecter.");
        router.push('/');
        return;
    }

    try {
        const user = JSON.parse(userStr);
        
        // VÉRIFICATION DU RÔLE
        if (user.role !== 'DIRECTEUR_MARKETING') {
            alert("Accès interdit : Espace réservé au Directeur Marketing.");
            router.push('/'); // Redirection
            return;
        }

        // Si c'est le bon rôle, on charge les données
        chargerDonnees();

    } catch (e) {
        // En cas de JSON corrompu dans le localStorage
        localStorage.removeItem('user');
        router.push('/');
    }
    chargerDonnees();
});
</script>

<template>
    <NavBar />
    
    <div>
        <h1>Validation Marketing</h1>

        <div v-if="chargement" class="msg">⏳ Chargement...</div>
        <div v-else-if="erreur" class="msg error"> {{ erreur }}</div>
        
        

        <div  class="cards-list">
            <div v-for="club in clubs" :key="club.idclub" class="card">
                <div class="card-head">
                    <h2>{{ club.titre }}</h2>
                    <span class="badge">EN CRÉATION</span>
                </div>
                
                <div class="card-content">
                    <p>{{ club.description }}</p>
                    
                    <div class="pricing-grid">
                        <div v-if="club.types_chambres_uniques && club.types_chambres_uniques.length > 0">
                            
                            <div v-for="type in club.types_chambres_uniques" :key="type.idtypechambre" class="room-row">
                                <h4>
                                  {{ type.nomtype }}</h4>
                                
                                <div class="inputs-row">
                                    <div v-for="p in periodes" :key="p.numperiode" class="input-group">
                                        <label>{{ p.numperiode }}</label>
                                        <div class="input-wrapper">
                                            <input 
                                                type="number" 
                                                v-model="prixSaisis[club.idclub][type.idtypechambre][p.numperiode]"
                                            >
                                            <span>€</span>
                                        </div>

                                    </div>
                                </div>
                            </div>

                        </div>
                        <div v-else class="warning">
                             Ce club n'a pas de chambres liées (table s_unit_a vide).
                        </div>
                    </div>
                </div>

                <button class="btn-valider" @click="validerEtPublier(club)">
                    VALIDER DÉFINITIVEMENT
                </button>
            </div>
        </div>
    </div>

    <Footer />
</template>
<style scoped>
.page-wrapper {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

.container {
    width: 100%;
    max-width: 1024px;
    margin: 0 auto;
    padding: 3rem 1.5rem;
    flex: 1; 
}

h1 {
    font-size: 2rem;
    font-weight: 700;
    margin: 120px 0 80px 0;
    letter-spacing: -0.025em;
    text-align: center;
}

/* --- Messages d'état --- */
.msg {
    padding: 1rem;
    margin-bottom: 2rem;
    text-align: center;
    font-weight: 500;
}
.msg.info { background-color: #dbeafe; color: #1e40af; }
.msg.error { background-color: #fee2e2; color: #991b1b; }

.cards-list {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.card {
    background-color: #ffffff;
    overflow: hidden;
    border: 1px solid #ccc;
}

/* En-tête */
.card-head {
    padding: 1.5rem;
    border-bottom: 1px solid #f3f4f6;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: #fff;
}

.card-head h2 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
}

.badge {
    background-color: #fff7ed;
    color: #c2410c;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 0.35rem 0.75rem;
    border-radius: 9999px;
    letter-spacing: 0.05em;
    border: 1px solid #ffedd5;
}

.card-content {
    padding: 2rem;
}

.description {
    color: #777;
    line-height: 1.6;
    margin-bottom: 2.5rem;
    font-size: 0.95rem;
}

.room-row {
    margin-bottom: 2.5rem;
    padding: 1.5rem;
    border: 1px solid #f3f4f6;
}

.room-row h4 {
    font-size: 0.9rem;
    font-weight: 700;
    margin-bottom: 1.25rem;
    letter-spacing: 0.05em;
    border-bottom: 2px solid #e5e7eb;
    padding-bottom: 0.5rem;
    display: inline-block;
}

.inputs-row {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); 
    gap: 1.25rem;
}

.input-group label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: #6b7280;
    margin-bottom: 0.5rem;
}

/* Input Stylisé */
.input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.input-wrapper input {
    width: 100%;
    padding: 8px 18px;
    padding-right: 2rem; 
    background-color: #ffffff;
    border: 1px solid #ccc;
    font-size: 0.95rem;
}

.input-wrapper input:focus {
    outline: none;
    border-color: black;
}

/* Suppression des flèches natives moche des inputs number */
.input-wrapper input::-webkit-outer-spin-button,
.input-wrapper input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.input-wrapper .currency {
    position: absolute;
    right: 0.75rem;
    color: #777;
    font-size: 0.9rem;
    pointer-events: none;
    font-weight: 500;
}

.warning {
    background-color: #fffbeb;
    color: #92400e;
    padding: 1rem;
    border: 1px dashed #fcd34d;
    text-align: center;
}

/* --- Actions --- */
.card-actions {
    padding: 1.5rem 2rem;
    background-color: #f9fafb;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: flex-end;
}

.btn-valider {
    background-color: #fff;
    color: #000;
    border: 1px solid #000;
    padding: 0.75rem 1.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    margin: 20px auto;
    display: block;
    transition: 0.2s;
}

.btn-valider:hover {
    background-color: #000000;
    color: #fff;
}
</style>