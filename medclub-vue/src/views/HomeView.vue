<script setup>
import { ref, onMounted } from 'vue'
import api from '../services/api'
import NavBar from '../components/NavBar.vue'
import Footer from '../components/Footer.vue'
import CategoryFiltre from '../components/CategoryFiltre.vue'

const categories = ref([])
const categoriesActives = ref(['Tout'])

const clubs = ref([]);

const chargerCategories = async () => {
  try {
    const reponse = await api.get('/GetAllCategorie')
    categories.value = reponse.data
  } catch (e) {
    console.error(e)
  }
}

const chargerClubs = async () => {
  try {
    const reponse = await api.get('/clubs')
    clubs.value = reponse.data
    console.log(clubs)
  } catch (e) {
    console.error(e)
  }
}

const toggleCategorie = (nomCategorie) => {
  if (nomCategorie === 'Tout') {
    categoriesActives.value = ['Tout']
    return
  }

  if (categoriesActives.value.includes('Tout')) {
    categoriesActives.value = []
  }

  if (categoriesActives.value.includes(nomCategorie)) {
    categoriesActives.value = categoriesActives.value.filter(c => c !== nomCategorie)
  } else {
    categoriesActives.value.push(nomCategorie)
  }

  if (categoriesActives.value.length === 0) {
    categoriesActives.value = ['Tout']
  }
}

const datePickerWrapperRef = ref(null);
const dateDropdownRef = ref(null);
const dateDebInputRef = ref(null);
const dateFinInputRef = ref(null);
const dateDisplayTextRef = ref(null);
const defaultText = "Arrivée - Départ";

const formatDateDisplay = (dateStr) => {
    if (!dateStr) return '...';
    const date = new Date(dateStr);
    return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
};

const updateDisplayText = () => {
    const deb = dateDebInputRef.value.value;
    const fin = dateFinInputRef.value.value;
    
    let display = `${formatDateDisplay(deb)} - ${formatDateDisplay(fin)}`;
    
    if (!deb && !fin) {
        display = defaultText;
        dateDisplayTextRef.value.style.color = '#777';
    } else {
        dateDisplayTextRef.value.style.color = '#333';
    }
    dateDisplayTextRef.value.textContent = display;
};

const setupSearchBarListeners = () => {
    const wrapper = datePickerWrapperRef.value;
    const dropdown = dateDropdownRef.value;
    const debInput = dateDebInputRef.value;
    const finInput = dateFinInputRef.value;

    if (!wrapper || !dropdown) return;

    updateDisplayText();

    const handleWrapperClick = (e) => {
        e.stopPropagation();
        const isVisible = dropdown.classList.toggle('visible');
        wrapper.classList.toggle('active', isVisible);
    };
    wrapper.addEventListener('click', handleWrapperClick);

    dropdown.addEventListener('click', (e) => e.stopPropagation());

    debInput.addEventListener('change', updateDisplayText);
    finInput.addEventListener('change', updateDisplayText);

    const handleOutsideClick = (e) => {
        if (dropdown.classList.contains('visible') && !wrapper.contains(e.target)) {
            dropdown.classList.remove('visible');
            wrapper.classList.remove('active');
        }
    };
    document.addEventListener('click', handleOutsideClick);
    
    return () => {
        wrapper.removeEventListener('click', handleWrapperClick);
        debInput.removeEventListener('change', updateDisplayText);
        finInput.removeEventListener('change', updateDisplayText);
        document.removeEventListener('click', handleOutsideClick);
    };
};

onMounted(() => {
  chargerCategories()
  chargerClubs()

  setupSearchBarListeners() 
})
</script>

<template>
  <NavBar />

  <section class="hero">
    <div class="search">
        <div class="wrapped-search">
    <input class="input-field input-destination" type="text" placeholder="Destination..." />

    <div 
      ref="datePickerWrapperRef" 
      id="date-picker-wrapper" 
      class="input-field date-picker-field"
    >
        <span 
          ref="dateDisplayTextRef" 
          id="date-display-text" 
          class="date-display-text"
        >
          Arrivée - Départ
        </span>
        
        <div 
          ref="dateDropdownRef" 
          id="date-dropdown" 
          class="date-dropdown"
        >
            <label for="input-date-deb">Date d'arrivée</label>
            <input 
              ref="dateDebInputRef" 
              id="input-date-deb" 
              type="date" 
            />
            <label for="input-date-fin">Date de départ</label>
            <input 
              ref="dateFinInputRef" 
              id="input-date-fin" 
              type="date" 
            />
        </div>
    </div>
    
    <input class="input-field input-participants" type="number" placeholder="Participants" min="1" />
    
      <button>
        <RouterLink to="/clubs">
          OK
        </RouterLink>
      </button>
    
</div>
    </div>
  </section>

  <CategoryFiltre 
    :clubs="clubs" 
    :categories="categories" 
    :categories-actives="categoriesActives"
    @toggle-category="toggleCategorie"
  />

  <Footer />

</template>

<style scoped>
section.hero {
  position: relative;
  height: 80vh;
  width: 100%;
  background-image: url('../assets/images/club/magna_marbella/club_magna_marbella_1.jpg');
  background-position: center;
  background-size: cover;
  background-repeat: no-repeat;

  display: flex;
  align-items: flex-end;
  justify-content: center;
  padding-bottom: 8%;  
}

section.hero::after {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  height: 100%;
  width: 100%;
  background-color: rgba(0, 0, 0, 0.3);
  z-index: 99;
}

.search {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
    width: 100%;
    z-index: 999;
}

.wrapped-search {
    display: flex; 
    align-items: center; 
    gap: 0; 
    padding: 0; 
    border-radius: 50px; 
    background-color: #ffffff; 
    height: 50px; 
    max-width: 900px;
    width: 90%;
    margin: 0 auto;
    /* border: 1px solid #e0e0e0;  */
}
.input-field {
    height: 100%;
    padding: 0 15px; 
    border: none;
    background-color: transparent;
    font-size: 16px;
    transition: 0.3s;
    box-sizing: border-box; 
    color: #333;
    border-right: 1px solid #e0e0e0;
}

.input-field:hover {
    background-color: #f7f9fc;
}

.input-field:focus {
    outline: none;
    background-color: #f7f9fc;
}

.input-destination {
    min-width: 350px; 
    flex-grow: 5.5;
    border-radius: 50px 0 0 50px; 
    padding-left: 25px; 
}

.date-picker-field {
    position: relative;
    cursor: pointer;
    text-align: left;
    min-width: 180px; 
    flex-grow: 1.5; 
    padding-left: 15px;
    padding-right: 15px;
    display: flex;
    align-items: center;
}

.date-picker-field:hover, .date-picker-field.active {
     background-color: #f7f9fc;
}

.date-display-text {
    font-weight: 400;
    flex-grow: 1; 
    text-align: center;
}

.date-dropdown {
    position: absolute;
    top: 60px; 
    left: 50%; 
    transform: translateX(-50%);
    z-index: 10;
    background-color: #ffffff;
    padding: 15px;
    display: none; 
    flex-direction: column;
    gap: 15px;
    min-width: 350px; 
    text-align: left;
    border: 1px solid #e0e0e0;
}

.date-dropdown.visible {
    display: flex;
}

.date-dropdown label {
    font-weight: bold;
    color: #555;
    margin-bottom: 5px;
    display: block;
}

.date-dropdown input[type="date"] {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    outline: none;
    font-size: 16px;
}
.input-participants {
    width: 150px;
    text-align: center;
    border-right: 1px solid #e0e0e0; 
}

.wrapped-search button {
    height: 100%;
    padding: 0 25px;
    border: none;
    border-radius: 0 50px 50px 0; 
    background-color: #FFBF00; 
    color: #333; 
    font-weight: bold;
    font-size: 16px;
    cursor: pointer;
    transition: .2s; 
    flex-shrink: 0; 
    display: flex; 
    align-items: center;

    display: flex;
    align-items: center;
    justify-content: center;
}

.wrapped-search button a {
  text-decoration: none;
  color: #000;
}
</style>