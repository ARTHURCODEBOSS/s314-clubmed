<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import NavBar from '../components/NavBar.vue';
const router = useRouter()
const message = ref('');
const reservations = ref([]);
const loading = ref(true);
const error = ref('');
const token = localStorage.getItem('user_token');
const data = ref(null)
const mail_envoye = ref(false)
const show_resort_modal = ref(false)
const resorts_alternatifs = ref([])
const reservation_en_cours = ref(null)
const loading_resorts = ref(false)
const filtres = ref({
    selection: 'TOUS',
    recherche: ''
});

const resetFiltres = () => {
    filtres.value = { etat: 'TOUS', mois: 'TOUS', recherche: '' };
};
const moisAnnee = [
    { val: 'MOIS_0', label: 'Janvier' }, 
    { val: 'MOIS_1', label: 'Février' }, 
    { val: 'MOIS_2', label: 'Mars' }, 
    { val: 'MOIS_3', label: 'Avril' },
    { val: 'MOIS_4', label: 'Mai' }, 
    { val: 'MOIS_5', label: 'Juin' },
    { val: 'MOIS_6', label: 'Juillet' }, 
    { val: 'MOIS_7', label: 'Août' },
    { val: 'MOIS_8', label: 'Septembre' }, 
    { val: 'MOIS_9', label: 'Octobre' },
    { val: 'MOIS_10', label: 'Novembre' }, 
    { val: 'MOIS_11', label: 'Décembre' },
];

onMounted(async () => {
    try{
         const token = localStorage.getItem('user_token');
        const response = await fetch('http://51.83.36.122:8039/api/check-token', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': `Bearer ${token}` 
            }
        });
        if(response.ok){
            const data = await response.json();
            if (data.user.role != 'VENTE'){
                router.push('/connexion');
            }
        }
        else {
            localStorage.removeItem('user_token');
            router.push('/connexion');
        }
    } catch(e){
        error.value = "Probleme Lors de la connexion";
        console.error(e);
    }
   
    try {
        const token = localStorage.getItem('user_token');
        const response = await axios.get('http://51.83.36.122:8039/api/reservations', {
            headers: { Authorization: `Bearer ${token}` }
        });
        reservations.value = response.data;
        loading.value = false;
        console.log(reservations)
    } catch (e) {
        error.value = "Impossible de charger les réservations.";
        loading.value = false;
        console.error(e);
    }
});

const toggleConfirmationActivite = async (reservation, activite, event) => {
    const nouvelEtat = event.target.checked;
    const ancienEtat = activite.pivot.disponibilite_confirmee;
    
    activite.pivot.disponibilite_confirmee = nouvelEtat;
    
    try {
        const response = await axios.put(
            `http://51.83.36.122:8039/api/reservations/${reservation.numreservation}/activites/${activite.idactivite}/disponibilite`,
            { 
                disponibilite_confirmee: nouvelEtat ? 1 : 0
            },
            { headers: { Authorization: `Bearer ${token}` } }
        );
                
    } catch (e) {
        console.error('Erreur de sauvegarde:', e.response?.data || e.message);
        activite.pivot.disponibilite_confirmee = ancienEtat;
        alert(`Erreur : impossible de sauvegarder l'état de l'activité #${activite.idactivite}`);
    }
};

const retour_page = () =>{
    mail_envoye.value = false;
}

const ProposerResortAlternatif = async (reservation) => {
    console.log('Reservation:', reservation);
    reservation_en_cours.value = reservation;
    show_resort_modal.value = true;
    loading_resorts.value = true;
    
    try {
        let idcategorie = null;
        
        if (reservation.club.categorie && reservation.club.categorie.length > 0) {
            idcategorie = reservation.club.categorie[0].numcategory;
        }
        
        if (!idcategorie) {
            console.error('Pas de catégorie trouvée pour ce club');
            resorts_alternatifs.value = [];
            loading_resorts.value = false;
            return;
        }
        
        console.log('Recherche des clubs de catégorie:', idcategorie);
        
        const response = await axios.get(`http://51.83.36.122:8039/api/clubs/categorie/${idcategorie}`, {
            headers: { Authorization: `Bearer ${token}` }
        });
        
        resorts_alternatifs.value = response.data.filter(club => club.idclub !== reservation.idclub);
        loading_resorts.value = false;
    } catch (e) {
        console.error('Erreur lors du chargement des resorts:', e);
        resorts_alternatifs.value = [];
        loading_resorts.value = false;
    }
}

const fermer_modal_resort = () => {
    show_resort_modal.value = false;
    reservation_en_cours.value = null;
    resorts_alternatifs.value = [];
}

const EnvoyerProposition = async (nouveauClub) => {
    try {
        const response = await fetch('http://51.83.36.122:8039/api/envoyer-proposition', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json', 
                'Accept': 'application/json', 
                'Authorization': `Bearer ${token}` 
            },
            body: JSON.stringify({
                numreservation: reservation_en_cours.value.numreservation,
                ancien_club_id: reservation_en_cours.value.idclub,
                nouveau_club_id: nouveauClub.idclub,
                client_email: reservation_en_cours.value.client.email,
                client_nom: reservation_en_cours.value.client.nom,
                client_prenom: reservation_en_cours.value.client.prenom,
                datedebut: reservation_en_cours.value.datedebut,
                datefin: reservation_en_cours.value.datefin,
            })
        });
        
        if (response.ok) {
            const data = await response.json();
            console.log('Proposition envoyée:', data);
            
            // Met à jour le statut de la réservation
            reservations.value.forEach(element => {
                if (element.numreservation == reservation_en_cours.value.numreservation) {
                    element.statut = 'PROPOSITION_EN_COURS';
                    element.mail = true;
                }
            });
            
            fermer_modal_resort();
            mail_envoye.value = true;
        }
    } catch (e) {
        console.error('Erreur lors de l\'envoi de la proposition:', e);
        alert('Erreur lors de l\'envoi de la proposition');
    }
}
const EnvoyerMailPartenaire = async (activite) =>{
    try {
        console.log(reservation)
        const response = await fetch('http://51.83.36.122:8039/api/envoyer-mail-partenaire', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json', 
                'Accept': 'application/json', 
                'Authorization': `Bearer ${token}` 
            },
            body: JSON.stringify({
                idactivite: activite.idactivite,
                titre: activite.titre,
                prixmin: activite.prixmin,
                idpartenaire: activite.idpartenaire,
                nbpersonnes: activite.pivot.nbpersonnes,
            })
        });
    }
    catch (e) {
        console.error(e);
        message.value = "Erreur serveur";
    }
}
const EnvoyerMail = async (reservation) => {
    try {
        console.log(reservation)
        const response = await fetch('http://51.83.36.122:8039/api/envoyer-mail', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json', 
                'Accept': 'application/json', 
                'Authorization': `Bearer ${token}` 
            },
            body: JSON.stringify({
                numreservation: reservation.numreservation,
                idclub: reservation.idclub,
                idtransport: reservation.idtransport,
                numclient: reservation.numclient,
                datedebut: reservation.datedebut,
                datefin: reservation.datefin,
                nbpersonnes: reservation.nbpersonnes,
                lieudepart: reservation.lieudepart,
                prix: reservation.prix,
                statut: reservation.statut,
                etat_calcule: reservation.etat_calcule,
                mail: reservation.club.email,
            })
        });
        
        try {
            data.value = await response.json();
            console.log("C'est du bon JSON :", data);
            if (response.status == 200){
                mail_envoye.value = true;
                reservation.mail = true;
            }
        } catch (err) {
            console.error("Ce n'est pas du JSON valide !");
        }
    } catch (e) {
        console.error(e);
        message.value = "Erreur serveur";
    }
    
    reservations.value.forEach(element => {
        if (element.numreservation == data.value.reservation) {
            element.mail = true;
            console.log(element.mail)
        }
    });
}

const reservationsFiltrees = computed(() => {
    return reservations.value.filter(resa => {
        const choix = String(filtres.value.selection || 'TOUS');
        let matchSelection = true;

        if (choix !== 'TOUS') {
            if (choix.startsWith('MOIS_')) {
                const moisDemande = parseInt(choix.split('_')[1]);
                const d = new Date(resa.datedebut);
                d.setHours(12);
                const moisData = d.getMonth();
                matchSelection = moisData == moisDemande;
            
            } else {
                matchSelection = resa.statut == choix;
            }
        }
        if (filtres.value.recherche){
            
        }
        let texte = '';
        if (filtres.value.recherche) {
            texte = filtres.value.recherche.toLowerCase().trim();
        }

        let matchRecherche = false;

        if (!texte) {
            matchRecherche = true;
        } else if ( resa.client.nom && resa.client.nom.toLowerCase().includes(texte)) {
            matchRecherche = true;
        } else if ( resa.club.titre && resa.club.titre.toLowerCase().includes(texte)) {
            matchRecherche = true;
        } else if (String(resa.numreservation).includes(texte)) {
            matchRecherche = true;
        }
        return matchSelection && matchRecherche;
    });
});

const formaterDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR');
};
</script>

<template>
    <NavBar />
    <header>
        <h1>Reservations</h1>
        <p>Suivi global des réservations et du chiffre d'affaires.</p>
    </header>
        
    <div class="filters-bar">
        <div class="search-box">
            <input 
                v-model="filtres.recherche" 
                type="text" 
                placeholder="Chercher nom, club..."
            >
        </div>

        <div style="display: flex; gap: 20px; align-items: center;">
            <div class="kpi-box">
                <strong>({{ reservationsFiltrees.length }} résultats)</strong>
            </div>

            <div class="filter-group">
                <select v-model="filtres.selection">
                    <option value="TOUS">Tout voir</option>
                    
                    <optgroup label="Par État">
                        <option value="EN_ATTENTE">En Attente</option>
                        <option value="CONFIRMEE">Confirmée</option>
                        <option value="ANNULEE">Annulée</option>
                        <option value="REMBOURSE">Remboursé</option>
                        <option value="REFUSE">Refusé</option>
                        <option value="PROPOSITION_EN_COURS">Proposition en cours</option>
                    </optgroup>

                    <optgroup label="Par Mois de départ">
                        <option v-for="m in moisAnnee" :key="m.val" :value="m.val">
                            {{ m.label }}
                        </option>
                    </optgroup>
                </select>
            </div>
        </div>
    </div>

    <div v-if="loading" class="loading">Chargement des données...</div>
    <div v-else-if="error" class="error">{{ error }}</div>
    
    <div v-else class="table">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Client</th>
                    <th>Club</th>
                    <th>Dates</th>
                    <th>Prix</th>
                    <th>Statut</th>
                    <th>Action</th>
                    <th>Disponibilité des partenaires</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="resa in reservationsFiltrees" :key="resa.numreservation">
                    <td style="font-weight: bold;">{{ resa.numreservation }}</td>
                    <td>
                        <div class="client-info">
                            {{ resa.client.prenom }} {{ resa.client.nom }}
                            <span class="email-small">{{ resa.client.email }}</span>
                        </div>
                    </td>
                    <td>{{ resa.club.titre || 'Club #' + resa.idclub }}</td>
                    <td>
                        {{ formaterDate(resa.datedebut) }} 
                        <span class="arrow">➔</span> 
                        {{ formaterDate(resa.datefin) }}
                    </td>
                    <td class="prix">{{ resa.prix }} €</td>
                    <td>
                        <span :class="['badge', `badge-${resa.statut}`]">
                            {{ resa.statut }}
                        </span>
                    </td>
                    <td>
                        <button v-if="resa.statut == 'REFUSE'" class="btn-detail" @click="ProposerResortAlternatif(resa)">
                            Proposition
                        </button>
                        <button v-else-if="!resa.mail" class="btn-detail" @click="EnvoyerMail(resa)">
                            Envoyer Mail
                        </button>
                        <div v-else>Envoyée</div>
                    </td>
                    <td>
                        <div  class="activities-list">
                            <!-- v-if="resa.statut == 'CONFIRMEE'" -->
                            <div 
                                v-for="activite in resa.activites" 
                                :key="activite.idactivite" 
                                class="activity-item"
                            >
                            <!-- <label 
                                :for="'resa-' + resa.numreservation + '-act-' + activite.idactivite" 
                                style="cursor: pointer;"
                                @click = "EnvoyerMailPartenaire"
                            >
                                {{ activite.nom || activite.titre || 'Activité' }} 
                                ({{ activite.pivot.nbpersonnes }} pers.)
                            </label> -->
                            <p> 
                                {{activite}}
                            </p>
                            </div>
                           
                        </div>
                    </td>
                </tr>
                <tr v-if="reservationsFiltrees.length == 0">
                    <td colspan="8" class="empty-row">
                        Aucune réservation ne correspond à ces critères.
                    </td>
                </tr>
            </tbody>
        </table>

    </div>
    <div v-if="mail_envoye" class="overlay">
        <div class="popup popup-success">
            <div class="icon-circle success">
                <svg xmlns="http://www.w3.org/2000/svg" height="40px" viewBox="0 -960 960 960" width="40px" fill="currentColor">
                    <path d="M382-240 154-468l57-57 171 171 367-367 57 57-424 424Z"/>
                </svg>
            </div>
            <h2>Mail Envoyé !</h2>
            <button @click="retour_page" class="btn-popup">Retour aux Reservations</button>
        </div>
    </div>
    <div v-if="show_resort_modal" class="overlay">
        <div class="popup popup-resorts">
            <div class="popup-header">
                <h2>Proposer un Resort Alternatif</h2>
                <button @click="fermer_modal_resort" class="btn-close">×</button>
            </div>
            
            <div v-if="reservation_en_cours" class="resort-info">
                <p><strong>Client:</strong> {{ reservation_en_cours.client.prenom }} {{ reservation_en_cours.client.nom }}</p>
                <p><strong>Resort indisponible:</strong> {{ reservation_en_cours.club.titre }}</p>
                <p><strong>Dates:</strong> {{ formaterDate(reservation_en_cours.datedebut) }} → {{ formaterDate(reservation_en_cours.datefin) }}</p>
            </div>

            <div v-if="loading_resorts" class="loading-resorts">
                Chargement des resorts disponibles...
            </div>

            <div v-else-if="resorts_alternatifs.length === 0" class="no-resorts">
                Aucun resort alternatif trouvé dans la même catégorie.
            </div>

            <div v-else class="resorts-list">
                <h3>Resorts de la même catégorie :</h3>
                <div class="resort-cards">
                    <div 
                        v-for="club in resorts_alternatifs" 
                        :key="club.idclub" 
                        class="resort-card"
                    >
                        <div class="resort-card-content">
                            <h4>{{ club.titre }}</h4>
                            <p class="resort-description">{{ club.description?.substring(0, 100) }}...</p>
                            <p class="resort-location">📍 {{ club.pays.nompays || 'Pays inconnu' }}</p>
                            <p class="resort-note">⭐ {{ Number(club.notemoyenne).toFixed(1) || 'N/A' }}/5</p>
                        </div>
                        <button @click="EnvoyerProposition(club)" class="btn-proposer">
                            Proposer ce resort
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
header {
    text-align: center;
}

header h1 {
    margin-top: 120px;
}

header p {
    color: #777;
    font-style: italic;
}

.filters-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    max-width: 1400px;
    margin: 50px auto;
    padding-bottom: 20px;
    border-bottom: 1px solid #ccc;
}

.filters-bar input {
    border: 1px solid #ccc;
    padding: 12px 20px;
    outline: none;
    width: 400px;
}

.filters-bar select {
    border: 1px solid #ccc;
    padding: 8px 20px;
    outline: none;
}

.filters-bar input:focus,
.filters-bar select:focus {
    border: 1px solid black;
}

.table {
    max-width: 1400px;
    margin: 0 auto;
}

table {
    border-collapse: collapse;
    border-spacing: 0;
    width: 100%;
}

th {
    text-align: left;
    font-weight: 600;
}

table tr {
    border-bottom: 1px solid #ccc;
}

table th {
    padding: 12px 0;
}

table td {
    text-align: left;
    padding: 12px 0;
}

table button {
    font-size: 16px;
    border: none;
    background: none;
    font-weight: bold;
    text-decoration: underline;
    cursor: pointer;
}

table button:hover {
    text-decoration: none;
}

.overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.popup {
    background: white;
    padding: 40px;
    text-align: center;
    width: 90%;
    max-width: 400px;
    border-radius: 8px; /* Un peu d'arrondi c'est plus joli */
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.icon-circle {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px auto;
}


.icon-circle.success {
    background-color: #d1fae5; 
    color: #059669; 
}

.popup h2 {
    font-size: 1.5rem;
    color: #111827;
    margin-bottom: 8px;
    font-weight: 600;
}


.popup p {
    color: #6b7280;
    margin-bottom: 24px;
    line-height: 1.5;
}


.btn-popup {
    background-color: #000;
    color: white;
    border: 1px solid #000;
    padding: 10px 24px;
    font-size: 0.95rem;
    cursor: pointer;
    font-weight: bold;
    transition: all 0.2s ease;
    border-radius: 4px;
}

.btn-popup:hover {
    background-color: #fff;
    color: #000;
}

.popup-resorts {
    max-width: 800px;
    width: 95%;
    max-height: 90vh;
    overflow-y: auto;
}

.popup-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e5e7eb;
}

.popup-header h2 {
    margin: 0;
    font-size: 1.5rem;
}

.btn-close {
    background: none;
    border: none;
    font-size: 2rem;
    cursor: pointer;
    color: #6b7280;
    transition: color 0.2s;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-close:hover {
    color: #000;
}

.resort-info {
    background: #f3f4f6;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
    text-align: left;
}

.resort-info p {
    margin: 5px 0;
}

.loading-resorts, .no-resorts {
    text-align: center;
    padding: 40px;
    color: #6b7280;
}

.resorts-list h3 {
    margin-bottom: 15px;
    color: #111827;
    text-align: left;
}

.resort-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 15px;
}

.resort-card {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 15px;
    background: white;
    transition: all 0.2s;
    text-align: left;
}

.resort-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.resort-card-content h4 {
    margin: 0 0 10px 0;
    color: #111827;
    font-size: 1.1rem;
}

.resort-description {
    color: #6b7280;
    font-size: 0.9rem;
    margin: 10px 0;
    line-height: 1.4;
}

.resort-location, .resort-note {
    font-size: 0.9rem;
    color: #4b5563;
    margin: 5px 0;
}

.btn-proposer {
    width: 100%;
    margin-top: 15px;
    background-color: #000;
    color: white;
    border: 1px solid #000;
    padding: 10px;
    font-size: 0.95rem;
    cursor: pointer;
    font-weight: bold;
    transition: all 0.2s ease;
    border-radius: 4px;
}

.btn-proposer:hover {
    background-color: #fff;
    color: #000;
}

</style>
