<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import api from '../services/api';
import NavBar from '../components/NavBar.vue';

const router = useRouter()
const allReservations = ref([]);
const categories = ref([]); 
const localisations = ref([]);
const sejoursEnAttente = ref([]); 
const chargement = ref(true);


const moisSelectionne = ref(String(new Date().getMonth() + 1).padStart(2, '0'));
const anneeSelectionnee = ref(String(new Date().getFullYear()));

const LISTE_MOIS = [
    { id: '01', label: 'Janvier' }, { id: '02', label: 'Février' }, { id: '03', label: 'Mars' },
    { id: '04', label: 'Avril' },   { id: '05', label: 'Mai' },     { id: '06', label: 'Juin' },
    { id: '07', label: 'Juillet' }, { id: '08', label: 'Août' },    { id: '09', label: 'Septembre' },
    { id: '10', label: 'Octobre' }, { id: '11', label: 'Novembre' },{ id: '12', label: 'Décembre' }
];


const anneesDisponibles = computed(() => {
    const years = new Set();
    years.add(String(new Date().getFullYear())); 
    
    allReservations.value.forEach(r => {
        if (r.datedebut) {
            years.add(r.datedebut.substring(0, 4));
        }
    });
    return Array.from(years).sort().reverse();
});


const formatPrix = (val) => Number(val).toLocaleString("fr-FR");

const formatEvolution = (val) => {
    const signe = val > 0 ? '+' : ''; 
    return `${signe}${val}%`;
};


const getReservationsByPeriod = (monthId, year) => {
    return allReservations.value.filter(r => {
        if (!r.datedebut) return false;
        const rMonth = r.datedebut.substring(5, 7);
        const rYear = r.datedebut.substring(0, 4);
        return rMonth === monthId && rYear === year;
    });
};


const getPeriodePrecedente = (monthId, year) => {
    let m = parseInt(monthId) - 1;
    let y = parseInt(year);
    if (m === 0) {
        m = 12;
        y = y - 1;
    }
    return { month: String(m).padStart(2, '0'), year: String(y) };
};

const calculerEvolution = (actuel, precedent) => {
    if (precedent === 0) return actuel > 0 ? 100 : 0;
    const diff = ((actuel - precedent) / precedent) * 100;
    return Math.round(diff);
};

// Actuel
const reservationsActuelles = computed(() => 
    getReservationsByPeriod(moisSelectionne.value, anneeSelectionnee.value)
);
const caActuel = computed(() => reservationsActuelles.value.reduce((acc, r) => acc + Number(r.prix), 0));
const nbSejoursActuel = computed(() => reservationsActuelles.value.length);

// Précédent
const reservationsPrecedentes = computed(() => {
    const prev = getPeriodePrecedente(moisSelectionne.value, anneeSelectionnee.value);
    return getReservationsByPeriod(prev.month, prev.year);
});
const caPrecedent = computed(() => reservationsPrecedentes.value.reduce((acc, r) => acc + Number(r.prix), 0));
const nbSejoursPrecedent = computed(() => reservationsPrecedentes.value.length);

const evolutionCA = computed(() => calculerEvolution(caActuel.value, caPrecedent.value));
const evolutionNb = computed(() => calculerEvolution(nbSejoursActuel.value, nbSejoursPrecedent.value));

// Stats Annuelles
const statsAnnuelles = computed(() => {
    return LISTE_MOIS.map(m => {
        const resas = getReservationsByPeriod(m.id, anneeSelectionnee.value);
        const total = resas.reduce((acc, r) => acc + Number(r.prix), 0);
        return { label: m.label, total };
    });
});

// Catégorie
const statsCategories = computed(() => {
    if (!categories.value.length) return [];
    return categories.value.map(cat => {
        const total = reservationsActuelles.value
            .filter(r => {
                if (!r.club) return false;
                const dataCat = r.club.categorie || r.club.categories;
                if (Array.isArray(dataCat)) return dataCat.some(c => c.numcategory === cat.numcategory);
                else if (dataCat && dataCat.numcategory) return dataCat.numcategory === cat.numcategory;
                return false;
            })
            .reduce((acc, r) => acc + Number(r.prix), 0);
        return { ...cat, total };
    }).sort((a, b) => b.total - a.total); 
});

// Localisation
const statsLocalisations = computed(() => {
    if (!localisations.value.length) return [];
    return localisations.value.map(loc => {
        const paysIds = loc.souslocalisations.map(p => p.numpays);
        const total = reservationsActuelles.value
            .filter(r => r.club && r.club.pays && paysIds.includes(r.club.pays.numpays))
            .reduce((acc, r) => acc + Number(r.prix), 0);
        return { ...loc, total };
    }).sort((a, b) => b.total - a.total);
});

const statsClubs = computed(() => {
    const mapClubs = new Map();

    reservationsActuelles.value.forEach(r => {
        if (!r.club) return;
        if (!mapClubs.has(r.idclub)) mapClubs.set(r.idclub, { id: r.idclub, titre: r.club.titre, caActuel: 0, caPrecedent: 0 });
        mapClubs.get(r.idclub).caActuel += Number(r.prix);
    });

    reservationsPrecedentes.value.forEach(r => {
        if (!r.club) return;
        if (!mapClubs.has(r.idclub)) mapClubs.set(r.idclub, { id: r.idclub, titre: r.club.titre, caActuel: 0, caPrecedent: 0 });
        mapClubs.get(r.idclub).caPrecedent += Number(r.prix);
    });
    return Array.from(mapClubs.values())
        .map(c => ({ ...c, evolution: calculerEvolution(c.caActuel, c.caPrecedent) }))
        .sort((a, b) => b.caActuel - a.caActuel);
});

const chargerDonnees = async () => {
    try {
        const [resResa, resLoc, resCat, resClubs] = await Promise.all([
            api.get('/reservations'),
            api.get('/getLocalisationsWithSousLocalisation'),
            api.get('/GetAllCategorie'),
            api.get('/GetAllClub') 
        ]);
        
        allReservations.value = resResa.data;
        localisations.value = resLoc.data;
        categories.value = resCat.data;

        sejoursEnAttente.value = resClubs.data
            .filter(c => c.prix === "Indisp." || c.prix === "Fermé")
            .map(c => ({ id: c.idclub, titre: c.titre, prix_saisi: null }));
        
    } catch (error) {
        console.error("Erreur API:", error);
    } finally {
        chargement.value = false;
    }
};

onMounted(async () => {
    try {
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
            if (data.user.role != 'DIRECTEUR-VENTE') {
                router.push('/connexion');
            } else {
                await chargerDonnees();
            }
        } else {
            localStorage.removeItem('user_token');
            router.push('/connexion');
        }
    } catch (e) {
        console.error(e);
    }
});
</script>

<template>
    <NavBar />

    <div class="page-container">
        <h1>Directeur des ventes</h1>

        <div v-if="chargement" class="loading">Chargement des données...</div>

        <div v-else>

            <section class="top-section">
                <div class="container-select">

                    <p>Période d'analyse :</p>
                    <div style="display: flex; gap: 20px;">

                        <select v-model="anneeSelectionnee" class="year-select">
                            <option v-for="annee in anneesDisponibles" :key="annee" :value="annee">
                                {{ annee }}
                            </option>
                        </select>

                        <select v-model="moisSelectionne">
                            <option v-for="m in LISTE_MOIS" :key="m.id" :value="m.id">
                                {{ m.label }}
                            </option>
                        </select>

                    </div>
                </div>

                <div class="wrapped-select">
                    <div>
                        <p>Chiffre d'Affaires</p>
                        <h2>{{ formatPrix(caActuel) }} €</h2>
                        <span :class="evolutionCA >= 0 ? 'pos' : 'neg'">
                            {{ formatEvolution(evolutionCA) }}
                            <small style="color: #fff;">vs mois dernier</small>
                        </span>
                    </div>

                    <div>
                        <p>Nombre de séjours</p>
                        <h2>{{ nbSejoursActuel }}</h2>
                        <span :class="evolutionNb >= 0 ? 'pos' : 'neg'">
                            {{ formatEvolution(evolutionNb) }}
                            <small style="color: #000;">vs mois dernier</small>
                        </span>
                    </div>
                </div>
            </section>

            <section class="chart-section">
                <h3>Historique Annuel {{ anneeSelectionnee }}</h3>
                <ul class="stats-annuelles">
                    <li v-for="stat in statsAnnuelles" :key="stat.label">
                        <span>{{ stat.label }}</span>
                        <span :class="{ 'zero-value': stat.total === 0 }">{{ formatPrix(stat.total) }} €</span>
                    </li>
                </ul>
            </section>

            <section class="resorts-section">
                <h3>Ventes par Resort (Club)</h3>
                <ul>
                    <li v-for="club in statsClubs" :key="club.id">
                        <span>{{ club.titre }}</span>
                        <div style="display: flex;align-items: center; gap: 20px;">
                            <span :class="club.evolution >= 0 ? 'pos' : 'neg'">{{ formatEvolution(club.evolution) }}</span>
                            <span style="font-weight: bold;">{{ formatPrix(club.caActuel) }} €</span>
                        </div>
                    </li>
                </ul>
            </section>

            <h1>Répartition des Ventes</h1>

            <section class="bottom-cat">
                <div style="width: 48%">
                    <h3>Catégorie</h3>
                    <ul>
                        <li v-for="cat in statsCategories" :key="cat.numcategory">
                            <span>{{ cat.nomcategory }}</span>
                            <span :class="{ 'zero-val': cat.total === 0 }" class="amount">{{ formatPrix(cat.total) }} €</span>
                        </li>
                    </ul>
                </div>
                <div style="width: 48%">
                    <h3>Localisation</h3>
                    <ul>
                        <li v-for="loc in statsLocalisations" :key="loc.numlocalisation">
                            <span>{{ loc.nomlocalisation }}</span>
                            <span :class="{ 'zero-val': loc.total === 0 }" class="amount">{{ formatPrix(loc.total) }} €</span>
                        </li>
                    </ul>
                </div>
            </section>
        </div>
    </div>
</template>

<style scoped>

h1 {
    text-align: center;
    margin: 120px 0 30px 0;
}

section {
    margin: 0 auto;
    max-width: 1200px;
    padding-bottom: 40px;
}

.container-select {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    padding-bottom: 20px;
    border-bottom: 1px solid #ccc;
}

.container-select p {
    font-size: 16px;
    font-weight: bold;
}

.container-select select {
    padding: 8px 12px;
    border: 1px solid #ccc;
    outline: none;
}

.container-select select:focus {
    border-color: #000;
}

.wrapped-select {
    display: flex;
    gap: 50px;
    margin-top: 50px;
}

.wrapped-select > div {
    border: 1px solid #ccc;
    padding: 30px;
    width: 300px;
    display: flex;
    flex-direction: column;
}

.wrapped-select > div p {
    font-size: 14px;
}

.wrapped-select > div:first-child {
    background-color: #000;
    color: #fff;
}

.wrapped-select > div h2 {
    font-size: 40px;
    margin: 5px 0 15px 0;
    letter-spacing: 1px;

}
.wrapped-select > div span {
    text-align: right;
}

.pos {
    color: green;
}

.neg {
    color: red;
}

section ul {
    list-style-type: none;
}

section ul li {
    padding: 15px 0;
    border-bottom: 1px solid #ccc;
    display: flex;
    justify-content: space-between;
}

section li span:last-child {
    font-weight: bold;
    letter-spacing: 0.6px;
}

section.bottom-cat {
    display: flex;
    gap: 50px;
}

</style>