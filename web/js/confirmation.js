document.addEventListener('DOMContentLoaded', () => {
    const numCommandeElement = document.getElementById('num-commande');

    const urlParams = new URLSearchParams(window.location.search);
    const idCommande = urlParams.get('id');

    if (idCommande) {
        numCommandeElement.textContent = idCommande;
    } else {
        numCommandeElement.textContent = "ID non Disponible";
        numCommandeElement.style.color = "#ff4757"
    }
})