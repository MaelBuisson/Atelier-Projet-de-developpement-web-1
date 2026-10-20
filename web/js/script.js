
// le script s'exécute une fois le HTML chargé, pour que les cartes et les filtres existent
document.addEventListener('DOMContentLoaded', () => {

    const cartesSpectacles = document.querySelectorAll('.spectacle-card');

    // clic sur une carte : ouverture de la page de détail de la soirée
    cartesSpectacles.forEach(carte => {
        carte.addEventListener('click', () => {
            window.location.href = 'soiree.html'
        })
    })


    // filtres de la liste : style de musique et date
    const selectStyle = document.getElementById('filter-style');
const selectDate = document.getElementById('filter-date');

// filtrage temporaire côté client, sur les attributs data-style et data-date des cartes,
// en attendant que l'API fournisse les spectacles filtrés
function filtrerCartes() {
    const styleChoisi = selectStyle.value;
    const dateChoisie = selectDate.value;

    cartesSpectacles.forEach(carte => {
        const styleCarte = carte.getAttribute('data-style');
        const dateCarte = carte.getAttribute('data-date');

        // 'tous' désactive le filtre correspondant
        const correspondStyle = (styleChoisi === 'tous' || styleCarte === styleChoisi);
        const correspondDate = (dateChoisie === 'tous' || dateCarte === dateChoisie);

        // une carte reste visible seulement si elle correspond aux deux filtres
        if (correspondStyle && correspondDate) {
            carte.style.display = '';
        } else {
            carte.style.display = 'none';
        }
    });
}


// le filtrage est relancé à chaque changement de sélection
selectStyle.addEventListener('change', filtrerCartes);
selectDate.addEventListener('change', filtrerCartes);

})
