// src/utils/imageUtils.js

export function getClubImageUrl(imagePath) {
    if (!imagePath) {
        console.warn("getClubImageUrl: imagePath est vide.");
        return ''; 
    }
    
    const relativeAssetPath = '../assets/images/' + imagePath;
    
    let resolvedUrl = '';
    
    try {
        resolvedUrl = new URL(relativeAssetPath, import.meta.url).href;
        
        
        return resolvedUrl;

    } catch (error) {
        console.error("ÉCHEC de la résolution d'URL (Vite/new URL) pour le chemin:", relativeAssetPath);
        console.error("Erreur complète:", error);
        
        resolvedUrl = '/assets/images/' + imagePath;
        console.warn("Tentative de repli (chemin public):", resolvedUrl);
        
        return ''; 
    }
}