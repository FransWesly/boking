const menuToggles = document.querySelectorAll('.menu-toggle');
menuToggles.forEach(button => {
    button.addEventListener('click', () => {
        const nav = button.closest('header')?.querySelector('.nav-links');
        if (nav) {
            nav.classList.toggle('active');
        }
    });
});

const hero = document.querySelector('.hero-home');
const heroSlides = [
    'gaya/1.jpg',
    'gaya/2.jpg',
    'gaya/3.jpg'
];
let heroIndex = 0;

function updateHeroBackground() {
    if (!hero) return;
    const slider = hero.querySelector('.hero-slider');
    if (!slider) return;
    slider.style.backgroundImage = `url('${heroSlides[heroIndex]}')`;
    heroIndex = (heroIndex + 1) % heroSlides.length;
}

if (hero) {
    heroSlides.forEach(src => {
        const img = new Image();
        img.src = src;
    });
    updateHeroBackground();
    setInterval(updateHeroBackground, 6000);
}

window.addEventListener('scroll', () => {
    const header = document.querySelector('header');
    if (!header) return;
    if (window.scrollY > 20) {
        header.classList.add('header-scrolled');
    } else {
        header.classList.remove('header-scrolled');
    }
});
