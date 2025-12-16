<script setup>
import { computed } from 'vue'
import ClubCard from './ClubCard.vue'

const props = defineProps({
  clubs: {
    type: Array,
    required: true
  },
  categories: {
    type: Array,
    required: true
  },
  categoriesActives: {
    type: Array,
    required: true
  }
})

const emit = defineEmits(['toggle-category'])

const clubsFiltres = computed(() => {
    
    if (props.categoriesActives.includes('Tout') || props.categoriesActives.length === 0) {
        return props.clubs
    }

    
    return props.clubs.filter(club => {
        
        if (!club.categorie || !Array.isArray(club.categorie)) {
            return false;
        }

        
        return club.categorie.some(catDuClub => 
            props.categoriesActives.includes(catDuClub.nomcategory)
        )
    })
})

const toggleLocal = (nomCategorie) => {
  emit('toggle-category', nomCategorie)
}
</script>

<template>
  <section class="categorie">
    <div class="wrapped-categorie-filtre">
      <h1>Catégories</h1>

      <ul class="filtres">
        <li
          :class="{ active: categoriesActives.includes('Tout') }"
          @click="toggleLocal('Tout')"
        >
          Tout
        </li>

        <li
          v-for="categorie in categories"
          :key="categorie.id"
          :class="{ active: categoriesActives.includes(categorie.nomcategory) }"
          @click="toggleLocal(categorie.nomcategory)"
        >
          {{ categorie.nomcategory }}
        </li>
      </ul>

      <p>
        Parcourez notre collection exclusive de Clubs et Villages de Vacances, soigneusement sélectionnés pour vous. Votre prochaine destination inoubliable vous attend.
      </p>
    </div>

    <div class="wrapped-categorie">

      <ClubCard 
          class="dest"
          v-for="club in clubsFiltres" 
          :key="club.idclub"
          :titre="club.titre"
          :description="club.description"
          :notemoyenne="Number(club.notemoyenne)"
          :photoUrl="club.photo?.url"  
          :idclub="club.idclub"
          :prix="club.prix"
          :nomPays="club.pays.nompays"
          
        />
    </div>
    <RouterLink to="/clubs">   
        voir plus
    </RouterLink>
  </section>
</template>

<style scoped>
section.categorie {
  padding: 80px 0;
}

.wrapped-categorie-filtre {
  max-width: 800px;
  display: block;
  margin: 0 auto;
}

section h1 {
  text-align: center;
  font-family: 'Cormorant Garamond';
  font-size: 64px;
  font-weight: 100;
  font-style: italic;
}

section.categorie ul {
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  gap: 15px 25px;
  list-style: none;
  margin: 50px 0 30px 0;
}

section.categorie ul li {
  padding: 8px 20px;
  border: 1px solid #ccc;
  cursor: pointer;
}

section.categorie p {
  text-align: center;
}

.wrapped-categorie {
  margin: 50px 75px;
  display: flex;
  flex-wrap: nowrap;
  gap: 30px;
  overflow-x: auto;
  overflow-y: hidden;
  /* -ms-overflow-style: none;
  scrollbar-width: none; */
  padding-bottom: 20px;
}

/* .wrapped-categorie::-webkit-scrollbar {
  display: none;
} */

.card-club {
  height: 300px;
  width: 400px;
  background-color: black;
  flex-shrink: 0;
}

section.categorie ul li.active {
  background-color: #000;
  color: #fff;
}

section.categorie > a {
    display: block;
    text-align: center;
    width: auto;
    color: #000;
}

section.categorie > a:hover {
    text-decoration: none;
}
</style>