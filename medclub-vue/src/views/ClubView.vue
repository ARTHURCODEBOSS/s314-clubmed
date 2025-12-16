<script setup>
import { useRoute, useRouter } from 'vue-router';
import { ref, onMounted, computed, watch } from 'vue'; 
import { getClubImageUrl } from '../utils/imageUtils';
import api from '../services/api';
import NavBar from '../components/NavBar.vue';
import Footer from '../components/Footer.vue';

const router = useRouter();
const route = useRoute();
const id = route.params.id;
const club = ref(null); 
const erreur = ref(null);
const chargement = ref(true);
const typechambre = ref([]);
const activeSection = ref('station');

const activitesEnfants = ref([]);
const activitesAdulte = ref([]);
const activitesPourTous = ref([]);
const groupeTranchedAge = ref({});
const groupeTypeActivite = ref({});

const transport = ref([]);
const TransportSelect = ref(null);

const modalOuverte = ref(false);
const activiteSelectionnee = ref(null);
    // --- MODIFICATION ICI : État pour gérer la vue du modal (détails ou login) ---
    const modalView = ref('details');

const ouvrirModal = (activite) => {
    activiteSelectionnee.value = activite;
    modalView.value = 'details'; // On réinitialise toujours sur les détails à l'ouverture
    modalOuverte.value = true;
};

const fermerModal = () => {
    modalOuverte.value = false;
    setTimeout(() => {
        activiteSelectionnee.value = null;
        modalView.value = 'details';
    }, 300);
};

const scrollToSection = (sectionId) => {
    activeSection.value = sectionId; 
    const element = document.getElementById(sectionId);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth' });
    }
};

const dateDebutResa = ref();
const dateFinResa = ref();
const typeChambreSelect = ref(null);
const nbPersonne = ref();
const prixCalcule = ref(null);
const lieuRestauration = ref([]);

const nettoyerPrix = (valeur) => {
    if (!valeur) return 0;
    const texte = String(valeur);
    const prixPropre = texte.replace(/[^0-9.]/g, '');
    return Number(prixPropre);
};

const nombreJoursCalculé = computed(() => {
    if ((!dateDebutResa.value || !dateFinResa.value) && estDateInvalide.value) return 0;
    const debut = new Date(dateDebutResa.value);
    const fin = new Date(dateFinResa.value);
    const diffTime = fin - debut;
    if (diffTime <= 0) return 0;
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));     
});
const prixTransport = computed(() => {
    return TransportSelect.value?.prix ? nettoyerPrix(TransportSelect.value.prix) : 0;
});

const prixTotalAvecTransport = computed(() => {
    const prixSejour = nombreJoursCalculé.value * (prixCalcule.value || 0);
    const prixTransportTotal = prixTransport.value * (nbPersonne.value || 1);
    return prixSejour + prixTransportTotal;
});

const estDateInvalide = computed(() => {
    if (!dateDebutResa.value || !dateFinResa.value) return false;
    const dateDebut = new Date(dateDebutResa.value);
    const dateFin = new Date(dateFinResa.value);
    const aujourdhuiMinuit = new Date();
    aujourdhuiMinuit.setHours(0, 0, 0, 0);
    const erreurOrdre = dateDebut > dateFin;
    const erreurPasse = dateDebut < aujourdhuiMinuit; 
    return erreurOrdre || erreurPasse;
});


watch([nbPersonne, typeChambreSelect, dateDebutResa, dateFinResa], async () => {
    if (estDateInvalide.value) {
        prixCalcule.value = null;
        return;
    }
    if (nbPersonne.value && typeChambreSelect.value && dateDebutResa.value && dateFinResa.value) {
        if (nbPersonne.value < 1) nbPersonne.value = 1;
        try {
            const reponse = await api.get(`prixbyidtypechambre/${typeChambreSelect.value.idtypechambre}`);
            prixCalcule.value = nettoyerPrix(reponse.data); 
        } catch (e) {
            console.error("Erreur prix:", e);
            prixCalcule.value = "Erreur";
        }
        
        if (nbPersonne.value > typeChambreSelect.value.capacitemax) {
            prixCalcule.value = prixCalcule.value * Math.ceil(nbPersonne.value/typeChambreSelect.value.capacitemax);
        }
    } else {
        prixCalcule.value = null;
    }
});

const stationDetails = computed(() => {
    if (club.value && club.value.stationDetails) return club.value.stationDetails;
    
    if (club.value && club.value.stations && club.value.stations.length > 0) return club.value.stations[0];
    
    if (club.value && club.value.clubstation && club.value.clubstation.station && club.value.clubstation.station.length > 0) {
        const station = club.value.clubstation.station[0];
        station.altitudeclub = club.value.clubstation.altitudeclub; 
        return station;
    }
    return null;
});


const chargerClub = async () => {
    try {
        const reponse = await api.get(`/club/${id}`);
        club.value = reponse.data; 
        
        // 1. On récupère les types de chambres uniques
        club.value.chambres.forEach(chambre => {
            if (!typechambre.value.some(t => t.idtypechambre == chambre.type_chambre.idtypechambre)){ 
                // On clone l'objet pour éviter les problèmes de référence et on l'ajoute
                typechambre.value.push({ ...chambre.type_chambre });
            }
        });

        // --- AJOUT FONDAMENTAL : Récupération du prix par période pour chaque type ---
        // On boucle sur chaque type de chambre trouvé pour demander son "vrai" prix au serveur
        for (const type of typechambre.value) {
            try {
                const prixReponse = await api.get(`prixbyidtypechambre/${type.idtypechambre}`);
                // On utilise ta fonction nettoyerPrix pour avoir un nombre propre (ex: "150 €" -> 150)
                type.prix = nettoyerPrix(prixReponse.data);
            } catch (err) {
                console.error(`Erreur prix pour le type ${type.idtypechambre}`, err);
                type.prix = 0; // Valeur par défaut en cas d'erreur
            }
        }
        // -----------------------------------------------------------------------------
        
        if (club.value.activites) {
            activitesEnfants.value = club.value.activites.filter(act => act.enfant);
            activitesAdulte.value = club.value.activites.filter(act => act.adulte);
            activitesPourTous.value = club.value.activites.filter(act => !act.adulte && !act.enfant);
        }

        // Traitement Enfants
        activitesEnfants.value.forEach(act => {
            let titre = "De " + act.enfant.trancheage.agemin + " à " + act.enfant.trancheage.agemax;
            if(!groupeTranchedAge.value[titre]){
                groupeTranchedAge.value[titre]  = {
                    activite: [],
                    nombreInclus: 0,
                    nombreAlaCarte: 0
                };
            }
            if(act.prixmin == 0){
                groupeTranchedAge.value[titre].nombreInclus++;
            } else {
                groupeTranchedAge.value[titre].nombreAlaCarte++;
            }
            groupeTranchedAge.value[titre].activite.push(act);
        });

        // Traitement Adultes
        activitesAdulte.value.forEach(act => {
            let titreType = act.adulte.typeactivite.titre;

            if(!groupeTypeActivite.value[titreType]){
                groupeTypeActivite.value[titreType] = {
                    activite: [],
                    description: '',
                    nombreInclus: 0,
                    nombreAlaCarte: 0,
                    photoUrl: null 
                };
            }

            if(act.prixmin <= 0 ){
                groupeTypeActivite.value[titreType].nombreInclus++;
            } else {
                groupeTypeActivite.value[titreType].nombreAlaCarte++;
            }

            groupeTypeActivite.value[titreType].activite.push(act);
            groupeTypeActivite.value[titreType].description = act.adulte.typeactivite.description;
            
            if (act.adulte.typeactivite.photo) {
                groupeTypeActivite.value[titreType].photoUrl = act.adulte.typeactivite.photo.url;
            }
        });

        if (club.value.lieurestauration) {
            lieuRestauration.value = club.value.lieurestauration;
        }

    } catch (e) {
        erreur.value = "Impossible de charger le club. Vérifiez l'ID et l'API.";
        console.error("ERREUR CHARGEMENT CLUB:", e);
    } 
};

const chargerTransports = async () => {
    try {
        const response = await api.get('/transports');
        
        if (response.data.success) {
            transport.value = response.data.data;
        } else {
            transport.value = response.data;
        }
        
        console.log('Transports chargés:', transport.value);
    } catch (err) {
        console.error('Erreur chargement transports:', err);
        alert('Impossible de charger les transports');
    }
};

const allerAuPaiement = () => {
    const total = nombreJoursCalculé.value * prixCalcule.value;
    const nouvelleReservation = {
        id_unique: Date.now(),
        club: club.value, 
        dateDebut: dateDebutResa.value,
        dateFin: dateFinResa.value,
        nbPersonnes: nbPersonne.value,
        typeChambre: typeChambreSelect.value,
        prixTotal: prixTotalAvecTransport.value,
        dureeSejour: nombreJoursCalculé.value,
        transport: TransportSelect.value
    };
    let panier = JSON.parse(localStorage.getItem('reservationClubMed'));
    if (!Array.isArray(panier)) {
        panier = [];
    }
    panier.push(nouvelleReservation);
    localStorage.setItem('reservationClubMed', JSON.stringify(panier));
    router.push('/reservation');
};

const chargerPrix = async () => {
    if (club.value && club.value.idclub) {
        try {
            const url = `/clubs/prix/${club.value.idclub}`;
            const response = await api.get(url);
            club.value.prix = response.data; 
        } catch (err) {
            console.error(`Impossible de charger le prix`, err);
            club.value.prix = "N/A"; 
        }
    }
};

const getDynamicImageUrl = (url) => {
    if (url) {
        return getClubImageUrl(url); 
    }
    return 'none'; 
};

onMounted(async () => {
    try {
        await chargerClub(); 
        await chargerPrix();
        await chargerTransports(); 
    } catch (e) {
        erreur.value = erreur.value || "Une erreur inconnue est survenue.";
        console.error("Erreur fatale dans onMounted", e);
    } finally {
        chargement.value = false;
    }
});
</script>

<template>
    <NavBar />

    <div class="club-detail-container">
        
        <div v-if="chargement" class="loading-message">Chargement en cours...</div>
        
        <div v-else-if="club" class="club-content">
            
            <section class="header-section">
                <p class="localisation-text">{{ club.pays.nompays }}</p>
                <h1 class="main-title">{{ club.titre }}</h1>
                <p class="description-text">{{ club.description }}</p>
                <header>
                        
                    <img 
                        :src="getDynamicImageUrl(club.photo.url)" 
                        :alt="'Photo principale de ' + club.titre"
                        class="main-image"
                    />

                    <div class="price-note">
                        <p class="price-wrapped">
                            <span>A partir de </span><br>
                            <span class="price-value">{{ club.prix }}</span>
                        </p>
                        <p class="note-wrapped">
                            <span>Note moyenne </span> 
                            <span class="note-value">{{ Number(club.notemoyenne).toFixed(1) }}/5 ⭐</span>
                        </p>
                    </div>

                </header>

            </section>
            <p style="font-size: 16px; text-align: center; margin-top: 75px; font-style: italic; color: #777;">Commencez votre reservation dès maintenant (chambre, dates, participants)</p>
            <div class="debreservation"> 
                <select v-model="typeChambreSelect">
                    <option :value="null" selected disabled>Chambre</option>
                    <option v-for="type in typechambre" :key="type.idtypechambre" :value="type">
                        {{ type.nomtype }} 
                    </option>
                </select>
                
                <input type="date" v-model="dateDebutResa" :max="dateFinResa"> </input>
                <input 
                    type="date" 
                    v-model="dateFinResa" 
                    :min="dateDebutResa"
                    :class="{ 'input-error': estDateInvalide }"
                >

                <p v-if="estDateInvalide" style="color: red; font-size: 0.9em; margin-top: 5px;">
                    Date incorect.
                </p>
                <input type="number" placeholder="Participants" v-model="nbPersonne" :min="1"></input>
                
                <select v-model="TransportSelect">
                    <option :value="null" disabled>Transport</option>
                        <option v-for="tr in transport" :key="tr.idtransport" :value="tr">
                            {{ tr.lieudepart }}
                        </option>
                    </select>

                    <router-link 
                    :to="{ name: ''}"
                    class="lien-continents"
                >
                    <button 
                        @click="allerAuPaiement"
                        :disabled="!TransportSelect || estDateInvalide || !prixCalcule"
                        :style="{ opacity: (estDateInvalide || nombreJoursCalculé === 0) ? 0.5 : 1, cursor: (estDateInvalide || nombreJoursCalculé === 0) ? 'not-allowed' : 'pointer' }"
                    >
                        Continuer ({{ (prixTotalAvecTransport ) || 0 }} €)
                    </button>
                </router-link>
                                        
            </div>
                
            <main>

                <ul class="menu-sticky">
                    <li v-if="stationDetails" :class="{ active: activeSection === 'station' }">
                        <a href="#station" @click.prevent="scrollToSection('station')">Station</a>
                    </li>
                    <li :class="{ active: activeSection === 'hebergement' }">
                        <a href="#hebergement" @click.prevent="scrollToSection('hebergement')">Hébergement</a>
                    </li>
                    <li :class="{ active: activeSection === 'lieuRestauration' }">
                        <a href="#lieuRestauration" @click.prevent="scrollToSection('lieuRestauration')">Restauration & Bar</a>
                    </li>
                    <li :class="{ active: activeSection === 'activite' }">
                        <a href="#activite" @click.prevent="scrollToSection('activite')">Activités</a>
                    </li>
                    <li :class="{ active: activeSection === 'avis' }">
                        <a href="#avis" @click.prevent="scrollToSection('avis')">Avis</a>
                    </li>
                </ul>

                <section class="main-wrapped">

                    <section class="content-section" id="station" v-if="stationDetails">
                        <h2>Domaine Skiable</h2>
                        <div class="room-types-grid">            
                            <div class="room-type-item station-card">                
                                <div class="room-wrapped">
                                    
                                    <h3 class="room-title">{{ stationDetails.nomstation }}</h3>                                
                                    <div class="room-specs station-specs-grid">
                                        <span class="spec-tag">Altitude de la Station : {{ stationDetails.altitudestation }}m</span>
                                        <span class="spec-tag">Longueur totale des pistes : {{ stationDetails.longueurpistes }}km</span>
                                        <span class="spec-tag">Nombre de Pistes : {{ stationDetails.nbpistes }}</span>
                                    </div>                                
                                    <p class="station-info-ski">{{ stationDetails.infoski }}</p>
                                </div>       
                                <div class="room-image"></div> 
                            </div>
                        </div>
                    </section>

                    <section class="content-section" id="hebergement">
                        <h2>Hébergement</h2>
                        <div class="room-types-grid">
                            <div v-for="type in typechambre" :key="type.idtypechambre" class="room-type-item">
                                <div class="room-wrapped">
                                    <h3 class="room-title">{{ type.nomtype }}</h3>
                                    <p class="room-description">{{ type.textepresentation }}</p>
                                    
                                    <div class="room-specs">
                                        <span class="spec-tag">Capacité max : {{ type.capacitemax }} pers.</span>
                                    </div>
                                    <div class="room-prix" style="font-size: 24px; font-weight: bold; margin-top: auto;">
                                        {{ type.prix }}€
                                    </div>
                                </div>
                                <div class="room-image">

                                    <img 
                                        v-if="type.photo" 
                                        :src="getDynamicImageUrl(type.photo.url)" 
                                        :alt="type.nomtype"
                                    />

                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="content-section" id="lieuRestauration">
                        <h2>Restaurant & Bar</h2>
                        <div v-if="lieuRestauration.length > 0" class="restauration-section">
                            <div v-for="lieu in lieuRestauration" :key="lieu.numrestauration" class="restauration-item">
                                <div class="restauration-image"></div>
                                <div class="restauration-content">
                                    <h3>{{ lieu.nom }}</h3>
                                    <span class="badge presentation"><em>{{ lieu.estbar ? 'Bar' : 'Restaurant' }} {{ lieu.presentation }}</em></span>
                                    <p class="description">{{ lieu.description }}</p>
                                </div>
                            </div>
                        </div>
                        <div v-else>
                            Aucun lieu de restauration a été attribué ici
                        </div>
                            
                    </section>

                    <section class="content-section" id="activite">
                        
                        <h2 class="section-title">Nos Activités</h2>

                        <div v-if="club.activites && club.activites.length > 0">
                            
                            <!-- ADULTES -->
                            <div v-if="activitesAdulte.length > 0" class="adults-section">
                                <h3>Adultes</h3>
                                
                                <div v-for="(valeur, cle) in groupeTypeActivite" :key="cle" class="group-block">
                                    
                                    <!-- En-tête Catégorie -->
                                    <div class="group-header">
                                        <!-- Image -->
                                        <div v-if="valeur.photoUrl" class="hero-image-wrapper">
                                            <img :src="getDynamicImageUrl(valeur.photoUrl)" :alt="cle" class="hero-image"/>
                                            
                                            <h3 class="hero-title">{{ cle }}</h3>
                                        </div>

                                        <!-- Description & Badges -->
                                        <div class="group-description">
                                            <!-- Titre si pas d'image -->
                                            <h3 v-if="!valeur.photoUrl" class="category-header-title" >{{ cle }}</h3>
                                            
                                            <p>{{ valeur.description }}</p>
                                            
                                            <div class="stats-badges">
                                                <span v-if="valeur.nombreInclus > 0" class="badge-activite badge-success">
                                                    {{ valeur.nombreInclus }} Inclus
                                                </span>
                                                <span v-if="valeur.nombreAlaCarte > 0" class="badge-activite badge-warning">
                                                    {{ valeur.nombreAlaCarte }} À la carte
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Grille Cartes Activités -->
                                    <div class="cards-grid">
                                        <div v-for="act in valeur.activite" :key="act.idactivite" class="activity-card" @click="ouvrirModal(act)">
                                            <div class="card-header">
                                                <h5 class="card-title">{{ act.titre }}</h5>
                                                <span v-if="act.prixmin > 0">{{ act.prixmin }} €</span>
                                                <span v-else >Inclus</span>
                                            </div>
                                            
                                            <p class="card-desc">{{ act.description }}</p>

                                            <div class="card-footer">
                                                <span>En savoir plus</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ENFANTS -->
                            <div v-if="activitesEnfants.length > 0" class="kids-section">
                            
                                <h3 class="category-header-title">Enfants & Ados</h3>

                                <div v-for="(valeur, cle) in groupeTranchedAge" :key="cle" class="kids-block">
                                    <div class="kids-header">
                                        <h3 class="kids-title">
                                            {{ cle }} ans
                                        </h3>
                                        <div class="stats-badges">
                                            <span v-if="valeur.nombreInclus > 0" class="badge-activite">{{ valeur.nombreInclus }} Inclus</span>
                                            <span v-if="valeur.nombreAlaCarte > 0" class="badge-activite">{{ valeur.nombreAlaCarte }} À la carte</span>
                                        </div>
                                    </div>

                                    <div class="kids-grid">
                                        <div v-for="act in valeur.activite" :key="act.idactivite" class="kids-card" @click="ouvrirModal(act)">
                                            <div class="kids-card-top">
                                                <h5 class="kids-card-title">{{ act.titre }}</h5>
                                                <div v-if="act.prixmin > 0" class="kids-price">{{ act.prixmin }}€</div>
                                                <div v-else class="kids-price">Inclus</div>
                                            </div>
                                            <p class="kids-card-desc">{{ act.description }}</p>
                                            <span class="kids-link">Détails</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- Empty State -->
                        <div v-else class="empty-state">
                            <p>Aucune activité disponible pour le moment.</p>
                        </div>
                    </section>

                    <section class="content-section" id="avis">
                        <h2>Avis Clients</h2>
                        
                        <div class="section-card">
                            <div v-if="club.avis && club.avis.length > 0" class="reviews-list">
                                <div v-for="avi in club.avis" :key="avi.numavis" class="review-item"> 
                                    <span class="review-client">Client : {{avi.numclient}}</span><br>
                                    <span class="review-note">{{ avi.note }}/5 ⭐</span>
                                    <p style="font-weight: bold; font-size: 16px;"> {{avi.titre}} </p>
                                    <p class="review-comment">" {{ avi.commentaire }} "</p>
                                </div> 
                            </div>
                            <div v-else class="no-reviews">
                                <p>Aucun avis n'a été publié pour ce club.</p>
                            </div>
                        </div>
                    </section>                    
                    
                    
                </section>
            </main>
        </div>
        <div v-else>
            Erreur : Club introuvable
        </div>
        
    <!-- MODAL ACTIVITÉ -->
        <div v-if="modalOuverte && activiteSelectionnee" @click="fermerModal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center; z-index: 9999; padding: 20px;">
            <div @click.stop style="background: white; border: 1px solid #ccc; max-width: 700px; width: 100%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">

                <!-- Header Modal -->
                <div style="display: flex; justify-content: space-between; align-items: flex-start; padding: 30px; border-bottom: 1px solid #e5e7eb; position: sticky; top: 0; background: white; z-index: 10;">
                    <div style="flex: 1;">
                        <h3 style="font-size: 28px; font-weight: 600; margin-bottom: 8px; color: #000;">
                            {{ modalView === 'login' ? 'Connexion requise' : activiteSelectionnee.titre }}
                        </h3>
                        <p v-if="modalView !== 'login'" style="color: #666; font-size: 16px;">
                            {{ activiteSelectionnee.description }}
                        </p>
                        <p v-else style="color: #666; font-size: 16px;">
                                Veuillez vous connecter pour réserver cette activité.
                        </p>
                    </div>
                    <button @click="fermerModal" style="background: none; border: none; font-size: 28px; cursor: pointer; padding: 0; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; border-radius: 50%; margin-left: 20px; flex-shrink: 0;">
                        ✕
                    </button>
                </div>
            
                <div v-if="modalView === 'details'" style="padding: 30px;">

                    <div style="font-weight: bold; margin-bottom: 10px;">
                        <span v-if="activiteSelectionnee.prixmin > 0">
                            À partir de {{ activiteSelectionnee.prixmin }}€
                        </span>
                        <span v-else style="color: #16a34a;">
                            Inclus dans votre séjour
                        </span>
                    </div>
                
                    <!-- Détails Activité Adulte -->
                    <div v-if="activiteSelectionnee.adulte" style="margin-bottom: 30px; padding: 20px; background: #eff6ff; border: 1px solid #3b82f6;">
                        <h4 style="font-size: 20px; margin-bottom: 20px; color: #000;">Informations pratiques</h4>

                        <div style="display: grid; gap: 15px;">
                            <div v-if="activiteSelectionnee.adulte.typeactivite" style="display: flex; flex-direction: column; gap: 5px;">
                                <span style="font-size: 13px; color: #666; font-weight: 500;">Type d'activité</span>
                                <span style="font-size: 16px; color: #000; font-weight: 600;">{{ activiteSelectionnee.adulte.typeactivite.nomtype }}</span>
                            </div>
                        
                            <div style="display: flex; flex-direction: column; gap: 5px;">
                                <span style="font-size: 13px; color: #666; font-weight: 500;">Durée</span>
                                <span style="font-size: 16px; color: #000; font-weight: 600;">{{ activiteSelectionnee.adulte.duree }}h</span>
                            </div>
                        
                            <div style="display: flex; flex-direction: column; gap: 5px;">
                                <span style="font-size: 13px; color: #666; font-weight: 500;">Fréquence</span>
                                <span style="font-size: 16px; color: #000; font-weight: 600;">{{ activiteSelectionnee.adulte.frequence }}</span>
                            </div>
                        
                            <div style="display: flex; flex-direction: column; gap: 5px;">
                                <span style="font-size: 13px; color: #666; font-weight: 500;">Âge minimum</span>
                                <span style="font-size: 16px; color: #000; font-weight: 600;">{{ activiteSelectionnee.adulte.ageminimum }} ans</span>
                            </div>
                        </div>
                    </div>
                
                    <!-- Détails Activité Enfant -->
                    <div v-if="activiteSelectionnee.enfant" style="margin-bottom: 30px; padding: 20px; background: #faf5ff; border: 1px solid #9333ea;">
                        <h4 style="font-size: 20px; margin-bottom: 20px; color: #000;">Informations pratiques</h4>

                        <div style="display: grid; gap: 15px;">
                            <div v-if="activiteSelectionnee.enfant.trancheage" style="display: flex; flex-direction: column; gap: 5px;">
                                <span style="font-size: 13px; color: #666; font-weight: 500;">Tranche d'âge</span>
                                <span style="font-size: 16px; color: #000; font-weight: 600;">
                                    De {{ activiteSelectionnee.enfant.trancheage.agemin }} à {{ activiteSelectionnee.enfant.trancheage.agemax }} ans
                                </span>
                            </div>
                        
                            <div v-if="activiteSelectionnee.enfant.detail" style="display: flex; flex-direction: column; gap: 5px;">
                                <span style="font-size: 13px; color: #666; font-weight: 500;">Détails</span>
                                <span style="font-size: 16px; color: #000; font-weight: 400; line-height: 1.6;">{{ activiteSelectionnee.enfant.detail }}</span>
                            </div>
                        </div>
                    </div>
                
                    <!-- Actions -->
                    <div style="display: flex; gap: 15px; margin-top: 30px;">
                        <button @click="modalView = 'login'" style="flex: 1; background: #000; color: #fff; border: none; padding: 15px 30px;  font-weight: 600; font-size: 16px; cursor: pointer;">
                            Réserver cette activité
                        </button>
                        <button @click="fermerModal" style="padding: 15px 30px; border: 1px solid #ccc; background: white; font-weight: bold; cursor: pointer;">
                            Fermer
                        </button>
                    </div>
                </div>
                
                <div v-else-if="modalView === 'login'" style="padding: 30px; display: flex; flex-direction: column; gap: 20px; align-items: center;">
                        <div style="width: 100%; max-width: 400px; display: flex; flex-direction: column; gap: 15px;">
                            
                            <label style="font-weight: bold; font-size: 14px;">Email</label>
                            <input type="email" placeholder="votre@email.com" style="padding: 12px; border: 1px solid #ccc; width: 100%; font-size: 16px;" />
                            
                            <label style="font-weight: bold; font-size: 14px;">Mot de passe</label>
                            <input type="password" placeholder="Mot de passe" style="padding: 12px; border: 1px solid #ccc; width: 100%; font-size: 16px;" />
                            
                            <button style="background: #000; color: #fff; border: none; padding: 15px; font-weight: bold; cursor: pointer; margin-top: 10px;">
                                Se connecter
                            </button>
    
                            <button @click="modalView = 'details'" style="background: transparent; border: none; text-decoration: underline; cursor: pointer; margin-top: 10px; color: #666;">
                                ← Retour aux détails
                            </button>
                        </div>
                    </div>

            </div>
        </div>
    </div>
    <Footer />
</template>

<style scoped>
.club-detail-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    color: #000;
}

.main-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 64px;
    font-weight: 100;
    font-style: italic;
    margin-bottom: 10px;
    color: #000;
    text-align: center;
}
.description-text {
    font-size: 1.15em;
    margin-bottom: 30px;
    text-align: center;
    color: #777;
    font-style: italic;
}
.localisation-text {
    margin-top: 40px;
    margin-bottom: 10px;
    font-size: 1em;
    text-align: center;
}
.page-section {
    padding: 30px 0;
    border-top: 1px solid #ccc;
    margin-top: 20px;
}

header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #ccc;
    padding-bottom: 30px;
    gap: 30px;
}

.main-image {
    display: block;
    max-width: 800px;
    height: 500px;
    object-fit: cover; 
    flex-grow: 1;
    border-radius: 8px;
}

.price-note {
    align-self: flex-end;
}

.price-wrapped {
    font-size: 18px;
}

.price-value {
    font-weight: 700;
    font-size: 40px;
}

.debreservation {
    background-color: #ffffff;
    border-radius: 50px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    height: 50px;
    max-width: 900px;
    width: 90%;
    margin: 20px auto;
    border: 1px solid #e0e0e0;
    position: relative; 
    padding-left: 20px; 
}

.debreservation input,
.debreservation select {
    border: none;
    background: transparent;
    height: 100%;
    padding: 0 15px;
    font-size: 1rem;
    color: #333;
    outline: none;
    border-right: 1px solid #e0e0e0; 
}

.debreservation input:hover,
.debreservation select:hover {
    background-color: #f9f9f9;
}

.debreservation input[type="date"] {
    min-width: 130px;
    cursor: pointer;
    color: #555;
}

.debreservation input[type="number"] {
    width: 150px;
    text-align: center;
}

.debreservation select {
    flex-grow: 1;
    cursor: pointer;
    min-width: 150px;
}

.lien-continents {
    height: 100%;
    display: flex;
    text-decoration: none;
}

.debreservation button {
    background-color: #FFBF00; 
    color: #333;
    font-weight: 600;
    font-size: 1rem;
    border: none;
    height: 100%;
    padding: 0 30px;
    border-radius: 0 50px 50px 0; 
    cursor: pointer;
    transition: .2s;
    white-space: nowrap; 
}

.debreservation button:hover:not(:disabled) {
    background-color: #e6ac00;
}

.debreservation button:disabled {
    background-color: #e0e0e0;
    color: #999;
    cursor: not-allowed;
}

.input-error {
    color: #d32f2f;
    background-color: #ffebee;
}

.debreservation p {
    position: absolute;
    bottom: -30px;
    left: 50%;
    transform: translateX(-50%);
    color: #d32f2f; 
    font-size: 0.8rem;
    
}

main {
    display: flex;
    gap: 50px;
    justify-content: space-between;
    margin-top: 120px;
    border-top: 1px solid #ccc;
    padding-bottom: 30px;
    padding-top: 50px;
}

ul.menu-sticky {
    position: sticky;
    top: 100px; 
    align-self: flex-start; 
    list-style: none;
    padding: 0;
    margin: 0;
    flex-shrink: 0; 
}

ul.menu-sticky li {
    width: 230px;
    padding: 12px 24px;
    border: 1px solid #ccc;
    border-bottom: none;
    list-style: none;
}

ul.menu-sticky li:last-child {
    border-bottom: 1px solid #ccc;
}

ul.menu-sticky li a {
    height: 45px;
    width: 200px;
    text-decoration: none;
    color: #000;
}

ul.menu-sticky li.active {
    background-color: #000;
}

ul.menu-sticky li.active a {
    color: #fff;
}

section.main-wrapped {
    flex-grow: 1;
    max-width: 800px;
}

section.content-section {
    margin-bottom: 80px;
}

h2 {
    font-size: 30px;
}

.room-types-grid {
    display: flex;
    justify-content: space-between;
    gap: 30px;
    margin-top: 30px;
    flex-wrap: wrap;
}
.room-type-item {
    width: 100%;
    height: 300px;
    border: 1px solid #ccc;
    display: flex;
    justify-content: space-between;
    gap: 80px;
}
.room-wrapped {
    display: flex;
    flex-direction: column;
    padding: 20px;
}

.room-title {
    font-size: 24px;
    color: #000;
    margin-bottom: 5px;
}
.room-description {
    font-size: 16px;
    color: #777;
    margin-bottom: 10px;
}
.room-specs {
    margin-top: 10px;
    font-weight: bold;
}
.spec-tag {
    padding: 3px;
    color: #000;
}

.room-image {
    width: 500px;
    position: relative;
    background-color: #eee;
}

.room-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.restauration-section,
.activite-section {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-top: 20px;
}

.restauration-item,
.activite-item {
    width: 300px;
    text-align: center;
    border: 1px solid #ccc;
}

.restauration-image,
.activite-image {
    height: 200px;
    width: 100%;
    background: #000;
}

.restauration-content,
.activite-content {
    padding: 30px;
}

.restauration-content p {
    margin-top: 15px;
}

.reviews-list {
    margin-top: 30px;
    display: flex;
    gap: 30px;
    flex-wrap: wrap;
}
.review-item {
    flex: 1 1 300px;
    border: 1px solid #ccc;
    padding: 15px;
}

.review-item p {
    margin-top: 20px;
}

.station-specs-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 8px; 
    margin: 15px 0;
}

.station-specs-grid .spec-tag {
    background-color: #fff;
    border: 1px solid #e0e0e0;
    color: #000;
    margin: 0; 
    font-size: 0.85em;
    padding: 6px 12px;
    transition: .2s;
    display: flex;
    align-items: center;
    gap: 6px;
}

.station-specs-grid .spec-tag i {
    color: #FFBF00; 
    font-size: 0.9em;
}

.station-specs-grid .spec-tag:hover {
    border-color: #ccc;
}

.station-info-ski {
    color: #666;
    line-height: 1.6;
    font-size: 0.95em;
}

.room-image {
    height: 100%;
    background-color: #000;
}


.section-title {
    margin-bottom: 20px;
}

.group-header {
    margin-top: 30px;
    display: flex;
    gap: 20px;
    align-items: center;
}

.hero-image-wrapper {
    width: 500px;
    height: 270px;
    position: relative;
}

.hero-image-wrapper::after {
    content: '';
    height: 100%;
    width: 100%;
    position: absolute;
    top: 0;
    left: 0;
    background-color: rgba(0,0,0,0.2);
    z-index: 9;
    border-radius: 8px;
}

.hero-image {
    object-fit: cover;
    width: 500px;
    height: 270px;
    border-radius: 8px;
}

.hero-title {
    position: absolute;
    bottom: 25px;
    left: 25px;
    font-size: 24px;
    color: #fff;
    z-index: 99;
}

.kids-section {
    margin-top: 30px;
}

.kids-section h3 {
    font-size: 24px;
}

.group-header p {
    margin: 8px 0 15px 0;
}

.stats-badges {
    display: flex;
    gap: 20px;
}

.badge-activite{
    font-size: 14px;
    padding: 5px 15px;
    background-color: #000;
    color: #fff;
}

.cards-grid,
.kids-grid {
    padding: 20px 0;
    display: flex;
    flex-wrap: wrap;
    gap: 30px;
}

.activity-card,
.kids-card {
    padding: 20px 15px; 
    border: 1px solid #ccc;
    width: 350px;
    height: 180px;

    display: flex;
    flex-direction: column;
    gap: 8px;
}

.activity-card h5,
.kids-card h5 {
    font-size: 20px;
}

.card-desc,
.kids-card-desc
 {
    color: #777;
}

.kids-header {
    border-bottom: 1px solid #ccc;
    padding-bottom: 8px;
}

.kids-header h3 {
    font-size: 18px;
}

.card-header,
.kids-header,
.kids-card-top {
    display: flex;
    justify-content: space-between;
}

.kids-block {
    margin-top: 20px;
}

.card-footer,
.kids-card span {
    margin-top: auto;
    text-align: right;
}

.card-footer span,
.kids-card span {
    text-decoration: underline;
    cursor: pointer;
}

.card-footer span:hover,
.kids-card span:hover {
    text-decoration: none;
}

@media (max-width: 1024px) {
    main {
        flex-direction: column;
        gap: 30px;
        margin-top: 50px;
    }
    ul.menu-sticky {
        position: static;
        flex-direction: row;
        width: 100%;
        border-bottom: 1px solid #ccc;
    }
    ul.menu-sticky li {
        width: auto;
        flex-grow: 1;
        text-align: center;
        border-right: 1px solid #ccc;
        border-bottom: 1px solid #ccc;
        border-top: none;
    }
    ul.menu-sticky li:first-child {
        border-left: none;
    }
    ul.menu-sticky li:last-child {
        border-bottom: none;
        border-right: none;
    }
    section.main-wrapped {
        max-width: 100%;
        padding: 0 10px;
    }
    header {
        flex-direction: column;
    }
    .price-note {
        width: 100%;
        flex-direction: row;
        justify-content: space-around;
        gap: 10px;
    }
    .main-image {
        width: 100%;
        height: 350px;
    }
    .room-types-grid {
        flex-direction: column;
    }
    .room-image {
        min-width: 100%;
        height: 250px;
    }
}
@media (max-width: 600px) {
    .main-title {
        font-size: 40px;
    }
    .price-note {
        flex-direction: column;
        gap: 15px;
    }
    .price-wrapped, .note-wrapped {
        width: 100%;
    }
    .room-image {
        height: 200px;
    }
}
</style>