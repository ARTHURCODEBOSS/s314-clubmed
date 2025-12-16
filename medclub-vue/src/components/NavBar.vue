<script setup>
    import { ref, onMounted } from 'vue';
    import api from '../services/api';
    
    const localisations = ref([]);
    const regroupements = ref([]);
    
    const erreur = ref(null);
    const chargement = ref(true);
    const destinationEstVisible = ref(false);
    const regroupementEstVisible = ref(false);
    
    const chargerLocalisation = async () => {
        try {
            const reponse = await api.get('/getLocalisationsWithSousLocalisation');
            localisations.value = reponse.data;
            console.log(localisations)
        } catch (e) {
            erreur.value = "Impossible de charger les séjours.";
            console.error(e);
        } finally {
            chargement.value = false;
        }
    };
    
    const chargerRegroupement = async () => {
        try {
            const reponse = await api.get('/GetAllRegroupement');
            regroupements.value = reponse.data;
            console.log("Offres chargées:", regroupements.value);
        } catch (e) {
            console.error("Erreur lors du chargement des regroupements:", e);
        }
    }
    
    onMounted(() => {
        chargerLocalisation();
        chargerRegroupement();
    });
    </script>
    
    <template>
        <nav>
            <RouterLink to="/">
                <img src="../assets/logo.svg" alt="" width="140px"></img>
            </RouterLink>
            <ul>
                <li>
                    
                    <RouterLink to="/">
                        Accueil
                    </RouterLink>
    
                </li>
                <li>
                    <RouterLink to="/clubs"
                                @mouseenter="destinationEstVisible = true"
                                @mouseleave="destinationEstVisible = false"
                    >   
                        Destinations
                    </RouterLink>
                </li>
                <li><a @mouseenter="regroupementEstVisible = true"
                       @mouseleave="regroupementEstVisible = false" href="#">Nos Offres</a></li>
            </ul>
            <div class="gestion-compte">
                <RouterLink to="/reservation" class="btn-reservation">
                    Mon Panier
                </RouterLink>
                <RouterLink to="/connexion" class="se-connecter">
                    <img src="../assets/icon/account.svg" height="34px" alt="">
                </RouterLink>
            </div>
        </nav>
        <div @mouseenter="destinationEstVisible = true" 
             @mouseleave="destinationEstVisible = false" 
             class="destination" 
             v-show="destinationEstVisible">
            <div v-for="(localisation, index) in localisations" :key="index">
                <h3>
                    <router-link 
                            :to="{ name: 'clubs-par-continent', params: { id: localisation['numlocalisation'] } }"
                            class="lien-continents"
                        >
                            {{ localisation["nomlocalisation"] }}
                        </router-link>
                </h3>
                <ul>
                    <li v-for="pays in localisation['souslocalisations']" :key="pays.numpays"> 
                        <router-link 
                            :to="{ name: 'clubs-par-pays', params: { id: pays.numpays } }"
                            class="lien-pays"
                        >
                            {{ pays.nompays }}
                        </router-link>
                    </li>
                </ul>
            </div>
        </div>
    
        <div 
            class="regroupement" 
            v-show="regroupementEstVisible" 
            @mouseenter="regroupementEstVisible = true"
            @mouseleave="regroupementEstVisible = false"
        >
            <div v-if="regroupements.length > 0">
                <h3>Nos Offres</h3>
                <ul>
                    <li v-for="offre in regroupements" :key="offre.numregroupement">
                        <router-link 
                            :to="{ name: 'clubs-par-regroupement', params: { id: offre.numregroupement } }"
                        >
                            {{ offre.libelleregroupement }}
                        </router-link>
                    </li>
                </ul>
            </div>
            <div v-else-if="!chargement">
                 <p>Aucune offre promotionnelle trouvée.</p>
            </div>
            <div v-else>
                 <p>Chargement des offres...</p>
            </div>
        </div>
    
    </template>
    
    
    <style scoped>
    nav {
        position: fixed;
        top: 0;
        left: 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background-color: #fff;
        padding: 0 80px;
        width: 100%;
        height: 80px;
        border-bottom: 1px solid #ccc;
        z-index: 9999;
    }
    
    nav ul {
        height: 100%;
        display: flex;
        gap: 35px;
        list-style-type: none;
    }
    
    nav ul li {
        line-height: 80px;
        height: 100%;
    }
    
    nav ul a {
        display: block;
        font-weight: 600;
        text-decoration: none;
        color: #000;
        transition: .2s;
    }
    
    nav ul a:hover {
        opacity: .5;
    }
    
    nav p {
        font-family: "PT Serif";
        font-weight: 700;
        font-size: 20px;
        letter-spacing: 1px;
    }
    
    .gestion-compte {
        display: flex;
        gap: 15px;
        align-items: center;
    }
    
    /* STYLE AJOUTÉ POUR LE BOUTON RESERVATION */
    .btn-reservation {
        text-decoration: none;
        color: #000;
        font-weight: 600;
        transition: .2s;
    }
    .btn-reservation:hover {
        opacity: .5;
    }
    /* FIN STYLE AJOUTÉ */
    
    .se-connecter {
        color: #000;
    }
    
    .connexion {
        text-decoration: none;
    }
    
    .destination,
    .regroupement {
        position: fixed;
        top: 80px;
        color: #000;
        background-color: #fff;
        display: flex;
        flex-wrap: wrap;
        gap: 70px;
        padding: 70px 100px;
        width: 100%;
        border-bottom: 1px solid #ccc;
        z-index: 9999;
    }
    
    .destination a,
    .regroupement a {
        text-decoration: none;
        color: #000;
    }
    
    .destination ul,
    .regroupement ul {
        list-style: none;
    }
    
    .destination h3{
        position: relative;
    }
    
    .destination li,
    .regroupement li {
        position: relative;
        margin: 5px 0;
    }
    
    .destination li::after, 
    .destination h3::after,
    .regroupement li::after  {
        content: "";
        position: absolute;
        top: 50%;
        left: -15px;
        transform: translate(-50%, -50%);
        height: 5px;
        width: 5px;
        background-color: #000;
        opacity: 0;
    }
    
    .destination li:hover::after, 
    .destination h3:hover::after,
    .regroupement li:hover::after {
        opacity: 1;
    }
    
    </style>