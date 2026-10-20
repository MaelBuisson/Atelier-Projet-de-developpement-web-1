
document.addEventListener('DOMContentLoaded', () => {

    const cartesSpectacles = document.querySelectorAll('.spectacle-card');

    cartesSpectacles.forEach(carte => {
        carte.addEventListener('click', () => {
            window.location.href = 'soiree.html'
        })
    })


    const selectStyle = document.getElementById('filter-style');
const selectDate = document.getElementById('filter-date');

function filtrerCartes() {
    const styleChoisi = selectStyle.value;
    const dateChoisie = selectDate.value;

    cartesSpectacles.forEach(carte => {
        const styleCarte = carte.getAttribute('data-style');
        const dateCarte = carte.getAttribute('data-date');

        const correspondStyle = (styleChoisi === 'tous' || styleCarte === styleChoisi);
        const correspondDate = (dateChoisie === 'tous' || dateCarte === dateChoisie);

        if (correspondStyle && correspondDate) {
            carte.style.display = ''; 
        } else {
            carte.style.display = 'none'; 
        }
    });
}


selectStyle.addEventListener('change', filtrerCartes);
selectDate.addEventListener('change', filtrerCartes);

})
