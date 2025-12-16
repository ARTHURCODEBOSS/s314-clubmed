import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomeView,
    },
    {
      path: '/sejours',
      name: 'sejours',
      component: () => import('../views/ReservationView.vue'),
    },
    {
      path: '/connexion',
      name: 'connexion',
      component: () => import('../views/ConnexionView.vue'),
    },{
      path: '/creation',
      name: 'creation',
      component: () => import('../views/CreerCompteView.vue'),
    },{
      path: '/clubs',
      name: 'clubs',
      component: () => import('../views/RechercheSejour.vue'),
    },
    {
      path: '/destinations/pays/:id', 
      name: 'clubs-par-pays',
      component: () => import('../views/RechercheSejour.vue'),
    },
    {
      path: '/clubs/regroupement/:id', 
      name: 'clubs-par-regroupement', 
      component: () => import('../views/RechercheSejour.vue')
    },
    {
      path: '/compte', 
      name: 'compte',
      component: () => import('../views/CompteView.vue'),
    },
    {
      path: '/destinations/continent/:id', 
      name: 'clubs-par-continent',
      component: () => import('../views/RechercheSejour.vue'),
    },
    {
      path: '/club/:id', 
      name: 'club',
      component: () => import('../views/ClubView.vue'),
    },
    {
      path: '/inscription', 
      name: 'inscription',
      component: () => import('../views/CreerCompteView.vue'),
    },
    { 
      path: '/donner-avis', 
      name: 'donner-avis',
      component: () => import('../views/DonnerAvisView.vue'),
    },
    { 
      path: '/reservation', 
      name: 'reservation',
      component: () => import('../views/ReservationView.vue'),
    },
    { 
      path: '/directeur-vente', 
      name: 'directeur-vente',
      component: () => import('../views/DirecteurVenteView.vue'),
    },
    { 
      path: '/directeur-marketing', 
      name: 'directeur-marketing',
      component: () => import('../views/DirecteurMarketingView.vue'),
    },
    { 
      path: '/membre-vente', 
      name: 'membre-vente',
      component: () => import('../views/MembreVenteView.vue'),
    },
    { 
      path: '/panier', 
      name: 'panier',
      component: () => import('../views/PanierView.vue'),
    },
    {
        path: '/validation',
        name: 'validation',
        component: () => import('../views/ValidationPartenaireView.vue')
    },
    {
        path: '/admin/propositions',
        name: 'GestionPropositions',
        component: () => import('../views/GestionReservation.vue'),
        meta: { requiresAuth: true, requiresVente: true }
    },
  ],
})



router.beforeEach((to, from, next) => {
    const token = localStorage.getItem('user_token');
    const userStr = localStorage.getItem('user_infos');
    const user = userStr ? JSON.parse(userStr) : null;

    
    if (to.meta.requiresAuth && !token) {
        return next('/connexion');
    }

    
    if (to.meta.requiresVente) {
        
        if (!user || !user.role || user.role.toLowerCase() !== 'vente') {
            alert("Accès refusé : Espace réservé au Service Vente.");
            return next('/');
        }
    }

    next(); 
});


export default router
