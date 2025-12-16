<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import { useRoute } from 'vue-router';
import api from '../services/api';
import ClubCard from '../components/ClubCard.vue';
import NavBar from '../components/NavBar.vue';

const route = useRoute();

const clubs = ref([]);
const categories = ref([]); 
const chargement = ref(true);
const erreur = ref(null); 
const sousTitre = ref('');

const currentPage = ref(1);
const itemsPerPage = 9;
const sortOrder = ref('default'); 

// Filtres
const filtresSelectionnes = ref({
    destinations: [],
    categories: [],
    activites: []
});

const totalFiltres = computed(() => {
    return filtresSelectionnes.value.destinations.length + 
           filtresSelectionnes.value.categories.length + 
           filtresSelectionnes.value.activites.length;
});

const toggleFiltre = (type, valeur) => {
    const index = filtresSelectionnes.value[type].indexOf(valeur);
    if (index === -1) {
        filtresSelectionnes.value[type].push(valeur);
    } else {
        filtresSelectionnes.value[type].splice(index, 1);
    }
    currentPage.value = 1; 
}; 

const resetFiltres = () => {
    filtresSelectionnes.value = { destinations: [], categories: [], activites: [] };
    sortOrder.value = 'default';
    currentPage.value = 1;
};

const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
        // window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const getPrixNumerique = (prixStr) => {
    if (typeof prixStr === 'number') return prixStr;
    if (!prixStr || typeof prixStr !== 'string') return Infinity;
    if (prixStr.includes('Indisp') || prixStr.includes('Fermé')) return Infinity; 
    
    const prixClean = prixStr.replace(/[^\d.]/g, '');
    const nombre = parseFloat(prixClean);
    return isNaN(nombre) ? Infinity : nombre;
};

const filteredClubs = computed(() => {
    if (!clubs.value) return [];

    return clubs.value.filter(club => {
        //  Destination
        if (filtresSelectionnes.value.destinations.length > 0) {
            if (!club.pays || !filtresSelectionnes.value.destinations.includes(club.pays.nompays)) {
                return false;
            }
        }
        
        if (filtresSelectionnes.value.categories.length > 0) {
            let clubCats = [];
            
            if (club.categories && Array.isArray(club.categories)) {
                clubCats = club.categories.map(c => c.nomcategory);
            } 
            else if (club.categorie && Array.isArray(club.categorie)) {
                clubCats = club.categorie.map(c => c.nomcategory);
            }
            else if (club.categorie && club.categorie.nomcategory) {
                clubCats = [club.categorie.nomcategory];
            }

            const hasMatch = filtresSelectionnes.value.categories.some(filtre => clubCats.includes(filtre));
            if (!hasMatch) return false;
        }

        // Activités
        if (filtresSelectionnes.value.activites.length > 0) {
            const clubActivitesStrings = [];
            if (club.activites) {
                club.activites.forEach(act => {
                    if (act.adulte && act.adulte.typeactivite) {
                        clubActivitesStrings.push(act.adulte.typeactivite.titre);
                    }
                    if (act.enfant && act.enfant.trancheage) {
                        const t = act.enfant.trancheage;
                        clubActivitesStrings.push(`Club Enfants ${t.agemin}-${t.agemax} ans`);
                    }
                });
            }
            const hasMatch = filtresSelectionnes.value.activites.some(filtre => clubActivitesStrings.includes(filtre));
            if (!hasMatch) return false;
        }

        return true;
    });
});

const sortedClubs = computed(() => {
    let listeTriee = [...filteredClubs.value]; 
    
    if (sortOrder.value === 'default') {
        return listeTriee;
    }

    listeTriee.sort((a, b) => {
        const prixA = getPrixNumerique(a.prix);
        const prixB = getPrixNumerique(b.prix);

        if (prixA === Infinity && prixB === Infinity) return 0;
        if (prixA === Infinity) return 1; 
        if (prixB === Infinity) return -1;

        if (sortOrder.value === 'prix-asc') return prixA - prixB; 
        if (sortOrder.value === 'prix-desc') return prixB - prixA; 
        return 0;
    });

    return listeTriee;
});

const totalPages = computed(() => {
    if (!sortedClubs.value) return 0;
    return Math.ceil(sortedClubs.value.length / itemsPerPage);
});

const paginatedClubs = computed(() => {
    if (!sortedClubs.value || sortedClubs.value.length === 0) return [];
    const start = (currentPage.value - 1) * itemsPerPage;
    return sortedClubs.value.slice(start, start + itemsPerPage);
});


const destinationsDisponibles = computed(() => {
    const mapPays = new Set();
    if (clubs.value) {
        clubs.value.forEach(club => {
            if (club.pays) mapPays.add(club.pays.nompays);
        });
    }
    return Array.from(mapPays).sort();
});

const activitesDisponibles = computed(() => {
    const uniqueActivites = new Set();
    if (clubs.value) {
        clubs.value.forEach(club => {
            if (club.activites && Array.isArray(club.activites)) {
                club.activites.forEach(act => {
                    if (act.adulte && act.adulte.typeactivite) {
                        uniqueActivites.add(act.adulte.typeactivite.titre);
                    }
                    if (act.enfant && act.enfant.trancheage) {
                        const t = act.enfant.trancheage;
                        uniqueActivites.add(`Club Enfants ${t.agemin}-${t.agemax} ans`);
                    }
                });
            }
        });
    }
    return Array.from(uniqueActivites).sort();
});

const chargerCategories = async () => {
    try {
        const reponse = await api.get('/GetAllCategorie');
        categories.value = reponse.data;
    } catch (e) {
        console.error("Erreur catégories", e);
    }
};

const chargerClubs = async () => {
    const id = route.params.id;
    erreur.value = null;
    chargement.value = true;
    clubs.value = []; 
    sousTitre.value = "";
    filtresSelectionnes.value = { destinations: [], categories: [], activites: [] };

    try {
        let url = '';
        
        if (route.name === 'recherche-pays' || (route.name === 'clubs-par-pays' && id)) {
            url = `/clubs/pays/${id}`;
            api.get('/getLocalisationsWithSousLocalisation').then(res => {
                for (const loc of res.data) {
                    const pays = loc.souslocalisations.find(p => p.numpays == id);
                    if (pays) sousTitre.value = `Voyagez au cœur de : ${pays.nompays}`;
                }
            });
        } 
        else if (route.name === 'recherche-continent' || (route.name === 'clubs-par-continent' && id)) {
            url = `/clubs/localisation/${id}`;
            api.get('/getLocalisationsWithSousLocalisation').then(res => {
                const cont = res.data.find(l => l.numlocalisation == id);
                if (cont) sousTitre.value = `Explorez le continent : ${cont.nomlocalisation}`;
            });
        } 
        else if (route.name === 'clubs-par-regroupement' && id) {
            url = `/clubs/regroupement/${id}`;
            api.get('/GetAllRegroupement').then(res => {
                const offre = res.data.find(r => r.numregroupement == id);
                if (offre) sousTitre.value = `Offre Spéciale : ${offre.libelleregroupement}`;
            });
        }
        else if (route.name === 'toutes-les-offres') {
            url = '/clubs/offres';
            sousTitre.value = "Découvrez l'ensemble de nos offres promotionnelles";
        }
        else {
            url = '/GetAllClub'; 
            sousTitre.value = "Découvrez tous nos villages vacances";
        }
        
        if(url) {
            const response = await api.get(url);
            clubs.value = response.data;
        }

    } catch (e) {
        erreur.value = "Erreur lors du chargement des données.";
        console.error(e); 
    } finally {
        chargement.value = false;
    }
};

onMounted(async () => {
    chargerCategories(); 
    await chargerClubs(); 
});

watch(sortOrder, () => {
    currentPage.value = 1;
});

watch(() => route.fullPath, () => {
    currentPage.value = 1;
    chargerClubs();
});
</script>

<template>
  <NavBar />

  <div class="recherche-sejour-container">
    <h1>Nos Clubs de Rêve</h1>
    <p v-if="sousTitre" class="sous-titre">{{ sousTitre }}</p>

    <div v-if="chargement" class="loading">Chargement en cours...</div>
    <div v-else-if="erreur" class="error">{{ erreur }}</div>

    <div class="destinations" v-else>
      
      <div class="container">
        <div class="filtre-wrapped">
          <div class="filtre-title">
            <h2 :data-count="`(${totalFiltres})`">Filtres</h2>
            <span @click="resetFiltres">effacer</span>
          </div>
          
          <div class="filtre" v-if="destinationsDisponibles.length > 0">
            <h3>Destination</h3>
            <ul>
              <li 
                v-for="pays in destinationsDisponibles" 
                :key="pays"
                @click="toggleFiltre('destinations', pays)"
                :class="{ 'selected': filtresSelectionnes.destinations.includes(pays) }"
              >
                {{ pays }}
              </li>
            </ul>
          </div>

          <div class="filtre">
            <h3>Catégories</h3>
            <ul> 
              <li 
                v-for="categorie in categories" 
                :key="categorie.numcategory"
                @click="toggleFiltre('categories', categorie.nomcategory)"
                :class="{ 'selected': filtresSelectionnes.categories.includes(categorie.nomcategory) }"
              >
                {{ categorie.nomcategory }} 
              </li>
            </ul>
          </div>

          <div class="filtre">
            <h3>Activités</h3>
            <p v-if="activitesDisponibles.length === 0" style="color:#888; font-size:0.9em">
                Chargement...
            </p>
            <ul>
              <li 
                v-for="titre in activitesDisponibles" 
                :key="titre"
                @click="toggleFiltre('activites', titre)"
                :class="{ 'selected': filtresSelectionnes.activites.includes(titre) }"
              >
                {{ titre }}
              </li>
            </ul>
          </div>
          
        </div>
        
        <div class="content-wrapped">
          
            <div class="wrapped-wrapped">
              <div class="tri-prix">
                Trier par prix :
                  <select v-model="sortOrder" name="tri" id="tri-select">
                    <option value="default">Pertinence</option>
                    <option value="prix-asc">Prix croissant</option>
                    <option value="prix-desc">Prix décroissant</option>
                  </select>
              </div>
              
              <div class="destinations-wrapped">
                  <div v-if="paginatedClubs.length === 0" class="no-results">
                    <p>Aucun club ne correspond à vos filtres.</p>
                  </div>
                <ClubCard 
                  class="dest"
                  v-for="club in paginatedClubs" 
                  :key="club.idclub"
                  :titre="club.titre"
                  :description="club.description"
                  :notemoyenne="Number(club.notemoyenne)"
                  :photoUrl="club.photo?.url"  
                  :idclub="club.idclub"
                  :prix="club.prix"
                  :nom-pays="club.pays?.nompays"
                />
              </div>

              <!-- PAGINATION -->
              <nav v-if="totalPages > 1" aria-label="Pagination navigation" class="pagination-nav">
                <ul class="flex flex-row gap-4 list-none p-0 m-0">
                  <li>
                    <button
                      type="button"
                      class="pagination-chevron"
                      :disabled="currentPage === 1"
                      @click="goToPage(currentPage - 1)"
                    >
                      <img src="../assets/icon/chevron.svg" alt="Précédent">
                    </button>
                  </li>
                  
                  <li v-for="page in totalPages" :key="page">
                    <button
                      type="button"
                      class="pagination-button"
                      :class="{'active': page === currentPage}"
                      @click="goToPage(page)"
                    >
                      {{ page }}
                    </button>
                  </li>

                  <li>
                    <button
                      type="button"
                      class="pagination-chevron next-chevron"
                      :disabled="currentPage === totalPages"
                      @click="goToPage(currentPage + 1)"
                    >
                      <img src="../assets/icon/chevron.svg" alt="Suivant">
                    </button>
                  </li>
                </ul>
              </nav>
            </div>

        </div>
        
      </div>

      

    </div>
  </div>
</template>

<style scoped>
h1 {
  text-align: center;
  font-family: 'Cormorant Garamond';
  font-size: 64px;
  font-weight: 100;
  font-style: italic;
  margin-top: 120px;
}

.sous-titre {
  text-align: center;
  font-size: 1.5rem;
  margin-bottom: 80px;
  color: #555;
  font-family: 'Cormorant Garamond', serif;
  font-style: italic;
}

.container {
  padding: 0 30px;
  display: flex;
  justify-content: flex-start;
  gap: 80px;
  margin-bottom: 40px;
}

.filtre-wrapped {
  min-width: 320px;
  max-width: 320px;
  border-right: 1px solid #ccc;
  padding-right: 20px;
}

h2 {
  position: relative;
  font-size: 30px;
}

h2::after {
  content: attr(data-count);
  position: absolute;
  top: 0;
  right: -15px;
  font-size: 10px;
}

.filtre-title {
  width: 100%;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.filtre-title span {
  text-decoration: underline;
  cursor: pointer;
}

.filtre-title span:hover {
  text-decoration: none;
}

.filtre {
  margin-bottom: 20px;
}

.filtre ul {
  display: flex;
  justify-content: flex-start;
  flex-wrap: wrap;
  gap: 10px 15px;
  list-style: none;
  margin: 10px 0;
}

.filtre ul li {
  padding: 8px 20px;
  border: 1px solid #ccc;
  cursor: pointer;
}

.selected {
  background-color: #000;
  color: #fff;
}

.tri-prix {
  text-align: right;
  margin-bottom: 20px;
  font-weight: bold;
}

.tri-prix select {
  padding: 8px 10px;
  border: 1px solid #ccc;
  outline: none;
}

.destinations-wrapped {
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  gap: 20px;
}

.dest {
  flex-shrink: 1;
}

@media (min-width: 768px) {
    .grid { grid-template-columns: repeat(3, 1fr); }
}
.error { color: red; font-weight: bold; }

.recherche-sejour-container nav {
  margin: 50px 0;
}

.recherche-sejour-container nav ul { 
  display: flex; 
  flex-direction: row; 
  gap: 10px; 
  justify-content: center; 
}

.recherche-sejour-container nav li { 
  display: inline-block; 
}

.recherche-sejour-container button {
  border: 1px solid #ccc;
  background: none;
  outline: none;
  height: 50px;
  width: 50px;
  border-radius: 50%;
  cursor: pointer;
}

.pagination-nav {
  display: flex;
  justify-content: center;
  padding-top: 3rem; 
  margin-top: 1.5rem; 
}

.pagination-nav ul {
  display: flex;
  flex-direction: row;
  gap: 1rem; 
}

.pagination-button {
  padding: 0.5rem 1rem;
  border-radius: 9999px; 
  border: 1px solid #000;
  cursor: pointer;
  background-color: #fff;
  color: #000;
  transition: background-color 0.2s, color 0.2s;
}

.pagination-button.active {
  background-color: #000; 
  color: #fff; 
  border-color: #000;
}

.pagination-chevron {
  padding: 0.5rem 1rem;
  font-weight: 600; 
  border-radius: 0.25rem; 
  border: none;
  background: transparent;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.pagination-chevron img {
  width: 1rem; 
  height: auto;
}

.pagination-chevron:disabled {
  color: #999999; 
  cursor: not-allowed;
}

.pagination-chevron:disabled img {
  opacity: 0.5; 
}


.next-chevron img {
  transform: rotate(180deg);
}

</style>