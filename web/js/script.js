
document.addEventListener('DOMContentLoaded', () => {

    const cartesSpectacles = document.querySelectorAll('.spectacle-card');

    cartesSpectacles.forEach(carte => {
        carte.addEventListener('click', () => {
            window.location.href = 'soiree.html'
        })
    })

})