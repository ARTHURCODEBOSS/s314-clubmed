<script setup>
    import { ref, onMounted, onUnmounted, watch, computed } from 'vue';
    import api from '../services/api';
    import { useRouter } from 'vue-router';
    import NavBar from '../components/NavBar.vue';
    import Footer from '../components/Footer.vue';
    
    const router = useRouter();
    
    const wrapperRef = ref(null);
    const gererClicDehors = (event) => {
        if (afficherSuggestions.value && wrapperRef.value && !wrapperRef.value.contains(event.target)) {
            afficherSuggestions.value = false;
        }
    };
    
    const panier = ref([]);
    const erreur = ref(null);
    const chargement = ref(true);
    const voyageurs = ref([]);
    const connecter = ref(false);
    const voyageurPrincipal = ref(null);
    const passeReglement = ref(false);
    const erreursAutresPersonnes = ref([]);
    
    const aEssayeDeValider = ref(false);
    const afficherModalConfirmation = ref(false);
    
    const dateIlYa4Mois = new Date();
    dateIlYa4Mois.setMonth(dateIlYa4Mois.getMonth() - 4);
    dateIlYa4Mois.setHours(0, 0, 0, 0);
    
    const dateAujourdhui = new Date();
    dateAujourdhui.setHours(0, 0, 0, 0);
    
    const informationDeCarte = ref({
        nom: '',
        numero: '',
        expiration: '',
        cryptogramme: ''
    });
    const mesCartes = ref([]);
    const afficherSuggestions = ref(false);
    const estUneCarteSauvegardee = ref(false);
    const erreurs = ref({ nom: '', numero: '', expiration: '', cryptogramme: '' });
    const prixTotalGlobal = computed(() => {
        return panier.value.reduce((total, item) => total + (item.prixTotal || 0), 0);
    });
    const nombreTotalVoyageursSupplementaires = computed(() => {
    let totalSupplementaires = 0;
    
    panier.value.forEach(item => {
        const nbAccompagnants = Math.max(0, (item.nbPersonnes || 1) - 1);
        totalSupplementaires += nbAccompagnants;
    });

    return totalSupplementaires;
    });
    
    const cartesFiltrees = computed(() => {
        const saisie = informationDeCarte.value.numero.replace(/\D/g, '');
        if (!saisie) return mesCartes.value;
        
        const resultats = mesCartes.value.filter(carte => {
            if (!carte.numero_clair) return false;
            return carte.numero_clair.startsWith(saisie);
        });
    
        if (resultats.length === 1 && resultats[0].numero_clair === saisie) {
            return [];
        }
        return resultats;
    });
    
    // --- FONCTIONS ---
    
    const continuerAchats = () => {
        router.push('/'); // Redirection vers l'accueil ou la liste des clubs
    };
    
    const supprimerDuPanier = (index) => {
        panier.value.splice(index, 1);
        localStorage.setItem('reservationClubMed', JSON.stringify(panier.value));
        
        // Si panier vide, on redirige ou on recharge
        if (panier.value.length === 0) {
            router.push('/');
        } else {
            // On recalcule les champs voyageurs
            initialiserVoyageurs();
        }
    };
    
    const chargerMesCartes = async () => {
        if (voyageurPrincipal.value && voyageurPrincipal.value.numclient) {
            try {
                const response = await api.get(`/GetAllCarte/${voyageurPrincipal.value.numclient}`);
                
                // Correction pour avoir le numéro clair
                const cartesTraitees = response.data.map(c => {
                    // Si votre API renvoie numero_clair, on l'utilise
                    // Sinon on simule ou on utilise ce qu'on a
                    return c;
                });
                mesCartes.value = cartesTraitees;
            } catch (e) {
                console.error("Impossible de charger les cartes", e);
            }
        }
    };
    
    const selectionnerCarteSauvegardee = (carte) => {
        if (carte.numero_clair) {
            informationDeCarte.value.numero = carte.numero_clair;
        } else {
            // Fallback si pas de numero_clair (selon votre backend)
            alert("Format de carte invalide pour le remplissage auto.");
            return;
        }
        
        if (carte.dateexpiration_carte_bancaire) {
            if (carte.dateexpiration_carte_bancaire.includes('/')) {
                 const [mois, anneeCourt] = carte.dateexpiration_carte_bancaire.split('/');
                 informationDeCarte.value.expiration = `20${anneeCourt}-${mois}`;
            } else {
                 informationDeCarte.value.expiration = carte.dateexpiration_carte_bancaire;
            }
        }
    
        informationDeCarte.value.cryptogramme = '';
        afficherSuggestions.value = false;
        estUneCarteSauvegardee.value = true;    
    };
    
    const initialiserVoyageurs = () => {
        voyageurs.value = [];
        erreursAutresPersonnes.value = [];
        
        // On génère autant de champs que de personnes supplémentaires dans tout le panier
        // Note : C'est une simplification. Idéalement, on devrait lier chaque voyageur à une résa spécifique.
        // Mais pour ce code, on garde une liste globale.
        const nbAutres = nombreTotalVoyageursSupplementaires.value;
    
        for (let i = 0; i < nbAutres; i++) {
            voyageurs.value.push({
                genre: '', prenom: '', nom: '', dateNaissance: ''
            });
            erreursAutresPersonnes.value.push({
                prenom: '', nom: '', dateNaissance: ''
            });
        }
    };
    
    onMounted(() => {
        const data = localStorage.getItem('reservationClubMed');
        const token = localStorage.getItem('user_token');
        
        if (token) {
            connecter.value = true;
            voyageurPrincipal.value = JSON.parse(localStorage.getItem('user_infos'));
            chargerMesCartes(); 
        }
    
        if (data) {
            const parsed = JSON.parse(data);
            // Gestion compatibilité : si c'est un objet unique (ancienne version), on le met dans un tableau
            if (Array.isArray(parsed)) {
                panier.value = parsed;
            } else {
                panier.value = [parsed];
            }
            
            initialiserVoyageurs();
            chargement.value = false;
        } else {
            chargement.value = false; // Panier vide
        }
        
        window.addEventListener('click', gererClicDehors);
    });
    
    onUnmounted(() => {
        window.removeEventListener('click', gererClicDehors);
    });
    
    // Formatage numéro carte
    watch(() => informationDeCarte.value.numero, (valeur) => {
        if (!valeur) return;
        let nettoyage = valeur.replace(/\D/g, '');
        if (nettoyage.length > 16) nettoyage = nettoyage.substring(0, 16);
        const formatage = nettoyage.match(/.{1,4}/g)?.join('-') || '';
        if (informationDeCarte.value.numero !== formatage) {
            informationDeCarte.value.numero = formatage;
        }
    });
    
    const passageReglement = () => {
        let ilYaUneErreur = false; 
        const regexLettres = /^[a-zA-ZÀ-ÿ\s-]+$/;
        
        voyageurs.value.forEach((voyageur, index) => {
            if (!erreursAutresPersonnes.value[index]) erreursAutresPersonnes.value[index] = {};
            
            erreursAutresPersonnes.value[index] = { prenom: '', nom: '', dateNaissance: '' , genre:'' };
    
            if (!voyageur.genre) {
                erreursAutresPersonnes.value[index].genre = "Obligatoire.";
                ilYaUneErreur = true;
            }
            if (!voyageur.prenom) {
                erreursAutresPersonnes.value[index].prenom = "Obligatoire.";
                ilYaUneErreur = true;
            } else if (!regexLettres.test(voyageur.prenom)) {
                erreursAutresPersonnes.value[index].prenom = "Lettres uniquement.";
                ilYaUneErreur = true;
            }
    
            if (!voyageur.nom) {
                erreursAutresPersonnes.value[index].nom = "Obligatoire.";
                ilYaUneErreur = true;
            } else if (!regexLettres.test(voyageur.nom)) {
                erreursAutresPersonnes.value[index].nom = "Lettres uniquement.";
                ilYaUneErreur = true;
            }
            
            if (!voyageur.dateNaissance) {
                erreursAutresPersonnes.value[index].dateNaissance = "Obligatoire.";
                ilYaUneErreur = true;
            } else if (estInvalide(voyageur.dateNaissance)) { 
                erreursAutresPersonnes.value[index].dateNaissance = "Date non valide.";
                ilYaUneErreur = true;
            }
        });
    
        if(connecter.value) {
            aEssayeDeValider.value = true; 
            let VerifVoyageurPrincipal = true;
            if (!voyageurPrincipal.value.genre || !voyageurPrincipal.value.prenom || !voyageurPrincipal.value.nom) {
                 VerifVoyageurPrincipal = false;
            }
    
            if (!ilYaUneErreur && VerifVoyageurPrincipal) {
                passeReglement.value = true;
            } 
        } else {
            alert("Veuillez vous connecter.");
        }
    }
    
    const Paiement = async () => {
        const regexLettres = /^[a-zA-ZÀ-ÿ\s-]+$/;
        erreurs.value = { nom: '', numero: '', expiration: '', cryptogramme: '' };
        let toutEstValide  = true;
    
        if (!informationDeCarte.value.nom) {
            erreurs.value.nom = "Obligatoire.";
            toutEstValide = false;
        } else if (!regexLettres.test(informationDeCarte.value.nom)) {
            erreurs.value.nom = "Pas de chiffres.";
            toutEstValide = false;
        }
    
        const regexNumero = /^[0-9]{16}$/; 
        if (!informationDeCarte.value.numero) {
            erreurs.value.numero = "Obligatoire.";
            toutEstValide = false;
        } else if (!regexNumero.test(informationDeCarte.value.numero.replace(/\D/g, ''))) {
            erreurs.value.numero = "16 chiffres requis.";
            toutEstValide = false;
        }
    
        if (!informationDeCarte.value.expiration) {
            erreurs.value.expiration = "Requise.";
            toutEstValide = false;
        } else if(new Date(informationDeCarte.value.expiration) < dateAujourdhui) {
            erreurs.value.expiration = "Carte périmée.";
            toutEstValide = false;
        }
        
        const regexCVV = /^[0-9]{3}$/;
        if (!informationDeCarte.value.cryptogramme) {
            erreurs.value.cryptogramme = "Requis.";
            toutEstValide = false;
        } else if (!regexCVV.test(informationDeCarte.value.cryptogramme)) {
            erreurs.value.cryptogramme = "3 chiffres.";
            toutEstValide = false;
        }
    
        if (toutEstValide) {
            if (estUneCarteSauvegardee.value) {
                confirmerEtPayer(false); 
            } else {
                afficherModalConfirmation.value = true;
            }
        }
    }
    
    const confirmerEtPayer = async (veutEnregistrer) => {
    afficherModalConfirmation.value = false;

    try {
                if (veutEnregistrer) {
            const dateBrute = informationDeCarte.value.expiration;
            const [annee, mois] = dateBrute.split('-'); 
            const dateFormatee = `${mois}/${annee.slice(-2)}`;
            const numeroNettoye = informationDeCarte.value.numero.replace(/\D/g, '');

            const donneesCarte = {
                numclient: voyageurPrincipal.value.numclient,
                numero_carte: numeroNettoye,
                date_expiration: dateFormatee,
                cvv: String(informationDeCarte.value.cryptogramme),
                est_active: true
            };
            await api.post('/enregistrerCarte', donneesCarte);
            console.log("Carte enregistrée");
        }
        let indexVoyageurGlobal = 0; 
        const promessesReservation = panier.value.map(item => {
            const nbAccompagnantsRequis = Math.max(0, (item.nbPersonnes || 1) - 1);
            const voyageursPourCetteResa = voyageurs.value.slice(
                indexVoyageurGlobal, 
                indexVoyageurGlobal + nbAccompagnantsRequis
            );
            indexVoyageurGlobal += nbAccompagnantsRequis;

            // 4. Construction de l'objet pour l'API
            const donneesReservation = {
                idclub: item.club.idclub,
                idtransport: item.transport ? item.transport.idtransport : 1,
                numclient: voyageurPrincipal.value.numclient,
                datedebut: item.dateDebut,
                datefin: item.dateFin,
                nbpersonnes: item.nbPersonnes,
                lieudepart: item.transport ? item.transport.lieudepart : null,
                prix: item.prixTotal,
                statut: "EN_ATTENTE",
                etat_calcule: null,
                autrevoyageurs: voyageursPourCetteResa 
            };
            return api.post('/postReservation', donneesReservation);
        });
        await Promise.all(promessesReservation);
        
        console.log('Toutes les réservations sont créées en base de données !');
        alert("Commande validée avec succès ! Vos réservations sont enregistrées.");
        localStorage.removeItem('reservationClubMed');
        router.push('/'); 

    } catch (error) {
        console.error('Erreur lors du paiement:', error);
        if (error.response && error.response.data) {
             erreur.value = "Erreur serveur : " + JSON.stringify(error.response.data);
        } else {
             erreur.value = "Une erreur est survenue. Vérifiez votre connexion.";
        }
    }
}
    
    const Envoieconnexion = () => {
        localStorage.setItem('reservation', true);
        router.push('/connexion');
    }
    const EnvoieCréation = () => {
        localStorage.setItem('reservation', true);
        router.push('/inscription');
    }
    
    const estInvalide = (dateSaisie) => {
    if (!dateSaisie) return false; // Le cas vide est géré par le "Obligatoire" plus haut
    
    const dateNaissance = new Date(dateSaisie);
    const aujourdhui = new Date();
    // On remet les heures à 00:00:00 pour comparer uniquement les jours
    aujourdhui.setHours(0, 0, 0, 0);

    // Retourne true si la date est dans le futur
    return dateNaissance > aujourdhui;
};
    </script>
    
    <template>
        <NavBar />
        <p v-if="chargement" class="loader">Chargement en cours...</p>
        
        <div class="reservation-container" v-else>
    
            <section class="recap">
                <h1>Votre Panier</h1>
                
                <div v-if="panier.length === 0" class="panier-vide">
                    <p>Votre panier est vide.</p>
                </div>
    
                <div v-else>
                    <div v-for="(item, index) in panier" :key="index" class="panier-item">
                        <h3 class="club-title">{{ item.club.titre }}</h3>
                        <div class="recap-grid">
                            <div class="recap-item">
                                <span class="label">Dates</span>
                                <p>{{ item.dateDebut }} au {{ item.dateFin }}</p>
                            </div>
                            <div class="recap-item">
                                <span class="label">Détails</span>
                                <p>{{ item.nbPersonnes }} pers. - {{ item.typeChambre.nomtype }}</p>
                            </div>
                            <div class="recap-item">
                                <span class="label">Transport</span>
                                <p>{{ item.transport ? item.transport.lieudepart : 'Sans transport' }}</p>
                            </div>
                            <div class="recap-item prix-item">
                                <span class="price-small">{{ item.prixTotal }}€</span>
                            </div>
                        </div>
                        <button @click="supprimerDuPanier(index)" class="btn-supprimer">Supprimer</button>
                        <hr style="margin: 20px 0; border:0; border-top:1px solid #ddd;">
                    </div>
    
                    <div class="prix-reservation">
                        <span>TOTAL À PAYER</span>
                        <span class="price-big">{{ prixTotalGlobal }}€</span>
                    </div>
                    
                    <button v-if="!passeReglement" @click="passageReglement()" class="btn-primary">
                        Procéder au paiement
                    </button>
                </div>
    
                <button @click="continuerAchats" class="btn-outline" style="width: 100%; margin-top: 15px;">
                    Continuer mes achats
                </button>
            </section>
    
            <div class="main-column">
            
            <section class="section-content" v-if="!passeReglement">
                <div class="step-header">
                    <h2>Voyageur principal</h2>
                </div>
                
                <div v-if="connecter==false" class="auth-box">
                    <button @click="Envoieconnexion" class="btn-outline">Se connecter</button>
                    <button @click="EnvoieCréation" class="btn-outline">Créer un compte</button>
                </div>
                
                <div v-else class="info-display">
                    <p><strong>Genre :</strong> {{ voyageurPrincipal.genre }}</p>
                    <p><strong>Prénom :</strong> {{ voyageurPrincipal.prenom }}</p>
                    <p><strong>Nom :</strong> {{ voyageurPrincipal.nom }}</p>
                
                    <p><strong>Date de naissance :</strong> {{ voyageurPrincipal.datenaissance }}</p>
                    <p><strong>Email :</strong> {{ voyageurPrincipal.email }}</p>
                    <p><strong>Téléphone :</strong> {{ voyageurPrincipal.telephone }}</p>
                
                    <p class="full-width address-display">
                        <strong>Adresse :</strong> 
                        {{ voyageurPrincipal.adresse.numrue }} {{ voyageurPrincipal.adresse.nomrue }}, 
                        {{ voyageurPrincipal.adresse.codepostal }} {{ voyageurPrincipal.adresse.ville }}
                    </p>
                </div>

                <div v-for="(voyageur, index) in voyageurs" :key="index" class="traveler-block">
                    <div class="step-header">
                        <h2>Autre(s) Participants(s) {{ index + 1 }}</h2>
                    </div>
                    
                    <div class="form-grid">
                        <div class="genre-container full-width">
                            <span :class="{ 'text-error': aEssayeDeValider && !voyageur.genre }" class="label">Genre*</span>
                            <div class="radio-group">
                                <label>
                                    <input type="radio" value="Homme" v-model="voyageur.genre"> Homme
                                </label>
                                <label>
                                    <input type="radio" value="Femme" v-model="voyageur.genre"> Femme
                                </label>
                            </div>
                            <div class="error-msg" v-if="erreursAutresPersonnes[index]?.genre">
                                {{ erreursAutresPersonnes[index]?.genre }}
                            </div>
                        </div>

                        <div>
                            <p class="label">Prénom*</p>
                            <input type="text" v-model="voyageur.prenom" :class="{ 'input-error': aEssayeDeValider && erreursAutresPersonnes[index]?.prenom }">
                            <span v-if="erreursAutresPersonnes[index]?.prenom" class="error-msg">
                                {{ erreursAutresPersonnes[index]?.prenom }}
                            </span>
                        </div>
                        
                        <div>
                            <p class="label">Nom de famille*</p>
                            <input type="text" v-model="voyageur.nom" :class="{ 'input-error': aEssayeDeValider && erreursAutresPersonnes[index]?.nom }">
                            <span v-if="erreursAutresPersonnes[index]?.nom" class="error-msg">
                                {{ erreursAutresPersonnes[index]?.nom }}
                            </span>
                        </div>
                        
                        <div>
                            <p class="label">Date de naissance*</p>
                            <input type="date" v-model="voyageur.dateNaissance" :class="{ 'input-error': aEssayeDeValider && erreursAutresPersonnes[index]?.dateNaissance }">
                            <span v-if="erreursAutresPersonnes[index]?.dateNaissance" class="error-msg">
                                {{ erreursAutresPersonnes[index]?.dateNaissance }}
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="section-content" v-if="!chargement && passeReglement" >
                <div>
                    <h2>Votre Carte bancaire</h2> <p class="label">Nom du titulaire de la carte</p>
                    <input type="text" v-model="informationDeCarte.nom" :class="{ 'input-error': erreurs.nom }">
                    <span v-if="erreurs.nom" class="error-msg">{{ erreurs.nom }}</span>

                    <p class="label">Numéro de carte</p>
                    <div class="input-wrapper" ref="wrapperRef">
                        <input type="text" 
                            v-model="informationDeCarte.numero"
                            @focus="afficherSuggestions = true"
                            @input="estUneCarteSauvegardee = false" 
                            @keydown.esc="afficherSuggestions = false"
                            placeholder="1234567812345678"
                            :class="{ 'input-error': erreurs.numero }"
                            autocomplete="off"
                        >
                        <ul v-if="afficherSuggestions && cartesFiltrees.length > 0" class="suggestions-list">
                            <li v-for="(carte, index) in cartesFiltrees" :key="carte.idcb" @click="selectionnerCarteSauvegardee(carte)">
                                <div class="card-icon">💳</div>
                                <div class="card-text">
                                    <span class="card-num">{{ carte.num_visible || 'Carte N°' + (index + 1) }}</span>
                                    <span class="card-exp">Exp: {{ carte.dateexpiration_carte_bancaire }}</span>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <span v-if="erreurs.numero" class="error-msg">{{ erreurs.numero }}</span>

                    <p class="label">Expiration</p>
                    <input type="month" v-model="informationDeCarte.expiration" :class="{ 'input-error': erreurs.expiration }">
                    <span v-if="erreurs.expiration" class="error-msg">{{ erreurs.expiration }}</span>

                    <p class="label">CVV</p>
                    <input type="text" v-model="informationDeCarte.cryptogramme" placeholder="123" maxlength="3" :class="{ 'input-error': erreurs.cryptogramme }">
                    <span v-if="erreurs.cryptogramme" class="error-msg">{{ erreurs.cryptogramme }}</span>
                    <br>
                </div>
                <div class="action-buttons"> <button class="btn-paiement" @click="passeReglement=false">Retour</button>
                    <button class="btn-paiement" @click="Paiement()">PAYER</button>
                </div>
            </section>
        </div>
        </div>
        <div v-if="afficherModalConfirmation" class="modal-overlay">
            <div class="modal-box">
                <h3>Enregistrer la carte ?</h3>
                <p>Voulez-vous enregistrer cette carte bancaire pour vos prochains achats ?</p>
                <div class="modal-buttons">
                    <button class="btn-modal btn-non" @click="confirmerEtPayer(false)">Non</button>
                    <button class="btn-modal btn-oui" @click="confirmerEtPayer(true)">Oui</button>
                </div>
            </div>
        </div>
    
        <Footer />
    </template>
    
    <style scoped>  
    .panier-item {
        margin-bottom: 20px;
    }
    .club-title {
        font-size: 1.1rem;
        font-weight: bold;
        margin-bottom: 10px;
    }
    .btn-supprimer {
        background: transparent;
        border: none;
        color: #d32f2f;
        text-decoration: underline;
        cursor: pointer;
        font-size: 0.9rem;
        margin-top: 10px;
    }
    .price-small {
        font-weight: bold;
        font-size: 1.1rem;
    }
    
    .input-wrapper { 
        position: relative; width: 100%; 
    }
    .suggestions-list { 
        position: absolute; top: 100%; left: 0; width: 100%; background: white; border: 1px solid #ccc; border-top: none; list-style: none; padding: 0; margin: 0; z-index: 10; box-shadow: 0 4px 6px rgba(0,0,0,0.1); max-height: 200px; overflow-y: auto; 
    }
    .suggestions-list li { 
        padding: 10px 15px; cursor: pointer; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #eee; transition: background 0.2s; 
    }
    .suggestions-list li:hover { 
        background-color: #f0f0f0; 
    }
    .card-icon { 
        font-size: 1.2rem; }
    .card-text { 
        display: flex; flex-direction: column; 
    }
    .card-num { 
        font-weight: bold; font-size: 0.9rem; 
    }
    .card-exp { 
        font-size: 0.8rem; color: #666; 
    }
    
    .reservation-container { 
        display: flex; flex-direction: row-reverse; gap: 60px; align-items: flex-start; max-width: 1200px; margin: 0 auto; padding: 80px 20px; color: #000; 
    }
    .main-column { 
        flex: 1; 
    }
    .recap { 
        width: 350px; background: #fcfcfc; border: 1px solid #eee; padding: 30px; position: sticky; top: 80px; 
    }
    .section-content {
    margin-bottom: 50px;
    }
    h1 { 
        font-size: 22px; font-weight: 600; margin: 0 0 20px 0; 
    }
    .recap-grid { 
        display: flex; flex-direction: column; gap: 10px; 
    }
    .recap-item { 
        display: flex; justify-content: space-between; font-size: 0.9rem; 
    }
    .label { 
        font-weight: bold; display: block; margin-bottom: 5px; 
    }
    .prix-reservation { 
        margin-top: 20px; padding-top: 20px; border-top: 2px solid #000; display: flex; justify-content: space-between; align-items: center; font-weight: 600; 
    }
    .price-big { 
        font-size: 1.5rem; 
    }
    .btn-primary { 
        background: #000; color: #fff; width: 100%; padding: 16px; border: 1px solid #000; font-weight: bold; margin-top: 20px; cursor: pointer; transition: .2s; 
    }
    .btn-primary:hover { 
        background: #fff; color: #000; 
    }
    .btn-outline { 
        background: transparent; border: 1px solid #ccc; padding: 12px 24px; font-size: 0.9rem; font-weight: bold; cursor: pointer; transition: .2s; text-align: center; 
    }
    .btn-outline:hover { 
        border-color: #000; background: #000; color: #fff; 
    }
    .form-grid { 
        display: grid; grid-template-columns: 1fr; gap: 20px; margin-top: 15px; 
    }
    .full-width { 
        grid-column: 1 / -1; 
    }
    input[type="text"], input[type="date"], input[type="email"], input[type="month"] { height: 45px; width: 100%; border: 1px solid #ccc; outline: none; padding: 0 18px; 
    }
    input:focus { 
        border: 1px solid #000; 
    }
    .input-error { 
        border: 1px solid red !important; background-color: #fff0f0; 
    }
    .text-error { 
        text-decoration: underline; color: #000; 
    }
    .error-msg { 
        color: red; font-weight: bold; font-size: 0.8rem; 
    }
    .traveler-block { 
        margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; 
    }
    .modal-overlay { 
        position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); display: flex; justify-content: center; align-items: center; z-index: 1000; 
    }
    .modal-box { 
        background: white; padding: 30px; border-radius: 8px; width: 90%; max-width: 400px; text-align: center; box-shadow: 0 4px 10px rgba(0,0,0,0.2); 
    }
    .modal-buttons { 
        display: flex; justify-content: center; gap: 15px; margin-top: 20px; 
    }
    .btn-modal { 
        padding: 10px 30px; cursor: pointer; font-weight: bold; border: none; 
    }
    .btn-non { 
        background: #eee; 
    }
    .btn-oui { 
        background: #000; color: #fff; 
    }
    .action-buttons { 
        display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; 
    }
    .btn-paiement { 
        padding: 12px 24px; background: #000; color: #fff; border: 1px solid #000; font-weight: bold; cursor: pointer; 
    }
    .btn-paiement:first-child { 
        background: #fff; color: #000; border: 1px solid #ccc; 
    }
    .auth-box {
    padding: 40px;
    background: #f9f9f9;
    text-align: center;
    display: flex;
    justify-content: center;
    gap: 15px;
    border: 1px solid #eee;
    }
    .step-header {
        padding-bottom: 15px;
        margin-bottom: 30px;
    }

    .info-display {
        display: flex;
        flex-direction: column;
        gap: 10px;
        padding: 20px;
        background: #fcfcfc;
        border: 1px solid #eee;
    }

    .address-display {
        margin-top: 10px;
       color: #555;
    }
    </style>