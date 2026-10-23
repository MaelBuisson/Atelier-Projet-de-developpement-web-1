// On attend que la page HTML soit entièrement chargée avant d'exécuter le script
document.addEventListener('DOMContentLoaded', () => {
    // 1. Cible l'élément HTML où on va afficher le numéro de commande
    const numCommandeElement = document.getElementById('num-commande');

    // 2. Extraction de l'ID de la commande depuis l'URL (ex: confirmation.html?id=12345)
    const urlParams = new URLSearchParams(window.location.search);
    const idCommande = urlParams.get('id');

    // 3. Affichage de l'ID ou gestion de l'erreur s'il est manquant
    if (idCommande) {
        numCommandeElement.textContent = idCommande;
    } else {
        numCommandeElement.textContent = "ID non Disponible";
        numCommandeElement.style.color = "#ff4757"; // Met le texte en rouge pour signaler l'erreur
    }

    // 4. Gestion du bouton d'impression
    const btnImprimer = document.getElementById('btn-imprimer');
    if (btnImprimer) {
        btnImprimer.addEventListener('click', () => {
            // Si on a bien un ID, on ouvre la page des billets dans un nouvel onglet (_blank)
            if (idCommande) {
                window.open(`billet.html?id=${idCommande}`, '_blank');
            }
        });
    }
});